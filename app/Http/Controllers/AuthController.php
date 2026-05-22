<?php

namespace App\Http\Controllers;

use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $token = $user->createToken('web-register')->plainTextToken;

        return response()->json([
            'message' => 'Registrasi berhasil.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        $token = $user->createToken('web-login')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'mode' => ['nullable', 'in:otp,link'],
        ]);

        $email = $validated['email'];
        $mode = $validated['mode'] ?? 'otp';
        $appName = config('app.name', 'Aplikasi');
        $previewKey = 'otp_preview';
        $previewValue = null;
        $subject = '';
        $textBody = '';

        if ($mode === 'link') {
            $resetToken = Str::random(64);
            $expiresAt = now()->addMinutes(30);
            $expiresText = $expiresAt->format('d-m-Y H:i');
            $resetPath = '/lupa-password?email=' . urlencode($email) . '&token=' . urlencode($resetToken);
            $frontendUrl = rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/');
            $mobileFrontendUrl = rtrim((string) env('FRONTEND_URL_MOBILE', ''), '/');
            $resetLink = $frontendUrl . $resetPath;
            $mobileResetLink = $mobileFrontendUrl !== '' ? $mobileFrontendUrl . $resetPath : null;

            PasswordResetOtp::updateOrCreate(
                ['email' => $email],
                [
                    'otp_hash' => Hash::make((string) random_int(100000, 999999)),
                    'expires_at' => $expiresAt,
                    'verified_at' => now(),
                    'reset_token_hash' => Hash::make($resetToken),
                    'reset_token_expires_at' => $expiresAt,
                    'attempts' => 0,
                ]
            );

            $subject = "Link Reset Password {$appName}";
            $textBody = "Klik link berikut untuk reset password {$appName}:\n{$resetLink}\nBerlaku sampai: {$expiresText}\nJika Anda tidak meminta reset password, abaikan email ini.";
            if ($mobileResetLink && $mobileResetLink !== $resetLink) {
                $textBody .= "\n\nJika membuka dari handphone, gunakan link ini:\n{$mobileResetLink}";
            }
            $previewKey = 'reset_link_preview';
            $previewValue = $mobileResetLink ?: $resetLink;
        } else {
            $otp = (string) random_int(100000, 999999);
            $expiresAt = now()->addMinutes(10);
            $expiresText = $expiresAt->format('d-m-Y H:i');

            PasswordResetOtp::updateOrCreate(
                ['email' => $email],
                [
                    'otp_hash' => Hash::make($otp),
                    'expires_at' => $expiresAt,
                    'verified_at' => null,
                    'reset_token_hash' => null,
                    'reset_token_expires_at' => null,
                    'attempts' => 0,
                ]
            );

            $subject = "Kode OTP Reset Password {$appName}";
            $textBody = "Kode OTP reset password {$appName}: {$otp}\nBerlaku sampai: {$expiresText}\nJangan berikan kode ini kepada siapa pun.";
            $previewValue = $otp;
        }

        $smtpReady = $this->isSmtpReady();
        $resendReady = $this->isResendReady();
        $brevoReady = $this->isBrevoReady();

        if (!$smtpReady && !$resendReady && !$brevoReady) {
            if (app()->isLocal() || config('app.debug')) {
                return response()->json([
                    'message' => $mode === 'link'
                        ? 'Provider email belum diatur. Link reset ditampilkan untuk mode lokal.'
                        : 'Provider email belum diatur. OTP ditampilkan untuk mode lokal.',
                    $previewKey => $previewValue,
                ]);
            }

            return response()->json([
                'message' => 'Konfigurasi email belum valid. Isi salah satu provider pengiriman: SMTP, RESEND_KEY, atau BREVO_API_KEY.',
            ], 422);
        }

        $errorDetails = [];

        if ($smtpReady) {
            try {
                Mail::raw(
                    $textBody,
                    function ($message) use ($email, $subject) {
                        $message->to($email)->subject($subject);
                    }
                );

                return response()->json([
                    'message' => $mode === 'link'
                        ? 'Link reset password berhasil dikirim ke email (SMTP).'
                        : 'Kode OTP berhasil dikirim ke email (SMTP).',
                ]);
            } catch (Throwable $th) {
                Log::error('Gagal mengirim OTP reset password via SMTP.', [
                    'email' => $email,
                    'mailer' => config('mail.default'),
                    'error' => $th->getMessage(),
                ]);
                $errorDetails[] = 'SMTP: ' . $th->getMessage();
            }
        }

        if ($resendReady) {
            $resendResult = $this->sendViaResend($email, $subject, $textBody);
            if ($resendResult['ok']) {
                return response()->json([
                    'message' => $mode === 'link'
                        ? 'Link reset password berhasil dikirim ke email (Resend API).'
                        : 'Kode OTP berhasil dikirim ke email (Resend API).',
                ]);
            }

            if ($this->isResendSandboxRestriction($resendResult)) {
                if (app()->isLocal() || config('app.debug')) {
                    return response()->json([
                        'message' => $mode === 'link'
                            ? 'Resend tanpa domain hanya bisa kirim ke email akun Resend Anda. Link reset ditampilkan untuk mode lokal.'
                            : 'Resend tanpa domain hanya bisa kirim ke email akun Resend Anda. OTP ditampilkan untuk mode lokal.',
                        $previewKey => $previewValue,
                        'provider_notice' => 'Gunakan domain terverifikasi untuk kirim ke semua email user.',
                    ]);
                }

                return response()->json([
                    'message' => 'Resend tanpa domain hanya untuk pengujian akun sendiri. Verifikasi domain agar bisa kirim ke semua email user.',
                ], 422);
            }

            $errorDetails[] = 'Resend: ' . $resendResult['error'];
        }

        if ($brevoReady) {
            $brevoResult = $this->sendViaBrevo($email, $subject, $textBody);
            if ($brevoResult['ok']) {
                return response()->json([
                    'message' => $mode === 'link'
                        ? 'Link reset password berhasil dikirim ke email (Brevo API).'
                        : 'Kode OTP berhasil dikirim ke email (Brevo API).',
                ]);
            }
            $errorDetails[] = 'Brevo: ' . $brevoResult['error'];
        }

        if (config('mail.default') === 'log') {
            Mail::raw(
                $textBody,
                function ($message) use ($email, $subject) {
                    $message->to($email)->subject($subject);
                }
            );

            return response()->json([
                'message' => $mode === 'link'
                    ? 'MAIL_MAILER masih mode log. Link reset belum terkirim ke inbox, cek storage/logs/laravel.log.'
                    : 'MAIL_MAILER masih mode log. OTP belum terkirim ke inbox, cek storage/logs/laravel.log.',
                $previewKey => config('app.debug') ? $previewValue : null,
            ]);
        }

        $response = [
            'message' => $mode === 'link'
                ? 'Gagal mengirim link reset ke email. Periksa konfigurasi provider email (SMTP/Resend/Brevo).'
                : 'Gagal mengirim OTP ke email. Periksa konfigurasi provider email (SMTP/Resend/Brevo).',
        ];

        if (config('app.debug') && !empty($errorDetails)) {
            $response['error_detail'] = implode(' | ', $errorDetails);
        }

        return response()->json($response, 500);
    }

    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
        ]);

        $record = PasswordResetOtp::where('email', $validated['email'])->first();
        if (!$record) {
            throw ValidationException::withMessages([
                'email' => ['Permintaan reset password tidak ditemukan.'],
            ]);
        }

        if (now()->greaterThan($record->expires_at)) {
            $record->delete();
            throw ValidationException::withMessages([
                'otp' => ['Kode OTP sudah kadaluarsa. Silakan kirim ulang OTP.'],
            ]);
        }

        if ($record->attempts >= 5) {
            $record->delete();
            throw ValidationException::withMessages([
                'otp' => ['Percobaan OTP terlalu banyak. Silakan kirim ulang OTP.'],
            ]);
        }

        if (!Hash::check($validated['otp'], $record->otp_hash)) {
            $record->increment('attempts');
            throw ValidationException::withMessages([
                'otp' => ['Kode OTP tidak valid.'],
            ]);
        }

        $resetToken = Str::random(64);
        $record->update([
            'verified_at' => now(),
            'reset_token_hash' => Hash::make($resetToken),
            'reset_token_expires_at' => now()->addMinutes(15),
            'attempts' => 0,
        ]);

        return response()->json([
            'message' => 'OTP terverifikasi.',
            'reset_token' => $resetToken,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'reset_token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $record = PasswordResetOtp::where('email', $validated['email'])->first();
        if (
            !$record ||
            !$record->verified_at ||
            !$record->reset_token_hash ||
            !$record->reset_token_expires_at
        ) {
            throw ValidationException::withMessages([
                'email' => ['Verifikasi OTP belum selesai.'],
            ]);
        }

        if (now()->greaterThan($record->reset_token_expires_at)) {
            $record->delete();
            throw ValidationException::withMessages([
                'reset_token' => ['Sesi reset password sudah kadaluarsa. Ulangi proses lupa password.'],
            ]);
        }

        if (!Hash::check($validated['reset_token'], $record->reset_token_hash)) {
            throw ValidationException::withMessages([
                'reset_token' => ['Reset token tidak valid.'],
            ]);
        }

        $user = User::where('email', $validated['email'])->firstOrFail();
        $user->password = $validated['password'];
        $user->save();

        $user->tokens()->delete();
        $record->delete();

        return response()->json([
            'message' => 'Password berhasil direset. Silakan login kembali.',
        ]);
    }

    private function isSmtpReady(): bool
    {
        if (config('mail.default') !== 'smtp') {
            return false;
        }

        $smtpUser = (string) config('mail.mailers.smtp.username');
        $smtpPass = (string) config('mail.mailers.smtp.password');
        $fromAddress = (string) config('mail.from.address');
        $usingPlaceholder =
            str_contains($smtpUser, 'yourgmail@gmail.com') ||
            str_contains($smtpPass, 'your_16_char_app_password');

        return $smtpUser !== '' && $smtpPass !== '' && $fromAddress !== '' && !$usingPlaceholder;
    }

    private function isResendReady(): bool
    {
        $resendKey = (string) config('services.resend.key');
        return $resendKey !== '';
    }

    private function isBrevoReady(): bool
    {
        $brevoKey = (string) config('services.brevo.key');
        $senderEmail = (string) config('services.brevo.sender_email');
        return $brevoKey !== '' && $senderEmail !== '';
    }

    private function sendViaResend(string $toEmail, string $subject, string $textBody): array
    {
        try {
            $fromAddress = env('RESEND_FROM_ADDRESS')
                ?: (string) config('mail.from.address')
                ?: 'onboarding@resend.dev';

            $response = Http::withToken((string) config('services.resend.key'))
                ->acceptJson()
                ->post('https://api.resend.com/emails', [
                    'from' => $fromAddress,
                    'to' => [$toEmail],
                    'subject' => $subject,
                    'text' => $textBody,
                ]);

            if ($response->successful()) {
                return [
                    'ok' => true,
                    'error' => null,
                    'status' => $response->status(),
                    'from' => $fromAddress,
                ];
            }

            return [
                'ok' => false,
                'error' => "HTTP {$response->status()} - {$response->body()}",
                'status' => $response->status(),
                'from' => $fromAddress,
            ];
        } catch (Throwable $th) {
            Log::error('Gagal mengirim OTP reset password via Resend.', [
                'email' => $toEmail,
                'error' => $th->getMessage(),
            ]);

            return [
                'ok' => false,
                'error' => $th->getMessage(),
                'status' => 0,
                'from' => null,
            ];
        }
    }

    private function isResendSandboxRestriction(array $resendResult): bool
    {
        $status = (int) ($resendResult['status'] ?? 0);
        $error = strtolower((string) ($resendResult['error'] ?? ''));
        $from = strtolower((string) ($resendResult['from'] ?? ''));

        if ($status !== 403) {
            return false;
        }

        $mentionsSandbox =
            str_contains($from, '@resend.dev') ||
            str_contains($error, 'resend.dev');

        $mentionsDomainRequirement =
            str_contains($error, 'verify a domain') ||
            str_contains($error, 'other recipients') ||
            str_contains($error, 'your own email');

        return $mentionsSandbox && $mentionsDomainRequirement;
    }

    private function sendViaBrevo(string $toEmail, string $subject, string $textBody): array
    {
        try {
            $senderEmail = (string) config('services.brevo.sender_email');
            $senderName = (string) config('services.brevo.sender_name', config('app.name', 'Aplikasi'));

            $response = Http::withHeaders([
                'api-key' => (string) config('services.brevo.key'),
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ])->post('https://api.brevo.com/v3/smtp/email', [
                'sender' => [
                    'name' => $senderName,
                    'email' => $senderEmail,
                ],
                'to' => [
                    ['email' => $toEmail],
                ],
                'subject' => $subject,
                'textContent' => $textBody,
            ]);

            if ($response->successful()) {
                return ['ok' => true, 'error' => null];
            }

            return [
                'ok' => false,
                'error' => "HTTP {$response->status()} - {$response->body()}",
            ];
        } catch (Throwable $th) {
            Log::error('Gagal mengirim OTP reset password via Brevo.', [
                'email' => $toEmail,
                'error' => $th->getMessage(),
            ]);

            return [
                'ok' => false,
                'error' => $th->getMessage(),
            ];
        }
    }
}
