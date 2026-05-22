<?php

namespace App\Support;

use App\Models\StockHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class StockHistoryRecorder
{
    public static function record(Request $request, string $itemType, Model $item, int $stokSebelum, int $stokSesudah, ?string $itemName = null): void
    {
        if ($stokSebelum === $stokSesudah) {
            return;
        }

        $perubahan = $stokSesudah - $stokSebelum;
        $userName = self::resolveUserEmail($request);

        StockHistory::create([
            'item_type' => $itemType,
            'item_id' => (int) $item->getKey(),
            'item_name' => $itemName,
            'stok_sebelum' => $stokSebelum,
            'stok_sesudah' => $stokSesudah,
            'perubahan' => $perubahan,
            'aksi' => $perubahan > 0 ? 'tambah' : 'kurang',
            'user_name' => $userName,
            'keterangan' => $request->input('stock_note') ?: ($perubahan > 0 ? 'Tambah stok' : 'Kurang stok'),
        ]);
    }

    private static function resolveUserEmail(Request $request): string
    {
        $candidates = [
            $request->user()?->email,
            self::emailFromBearerToken($request),
            $request->header('X-User-Email'),
            $request->input('updated_by'),
            $request->header('X-User-Name'),
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                return $candidate;
            }
        }

        return 'User';
    }

    private static function emailFromBearerToken(Request $request): ?string
    {
        $token = $request->bearerToken();
        if (!$token) {
            return null;
        }

        $accessToken = PersonalAccessToken::findToken($token);

        return $accessToken?->tokenable?->email;
    }
}
