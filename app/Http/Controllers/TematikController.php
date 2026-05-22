<?php

namespace App\Http\Controllers;

use App\Models\Tematik;
use App\Support\StockHistoryRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TematikController extends Controller
{
    public function index()
    {
        return response()->json(Tematik::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nomor_rak' => 'required|string|max:20',
            'judul' => 'required|string|max:255',
            'penerbit' => 'required|string|max:100',
            'kelas' => 'required|string|max:10',
            'semester' => 'required|in:1,2,lanjutan',
            'kurikulum' => 'required|in:kurikulum_merdeka,kurikulum_2013,umum',
            'stok' => 'required|integer|min:0',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/tematik', $filename, 'public');
            $validated['gambar'] = $path;
        }
        if ((int) $validated['stok'] > 0) {
            $validated['last_stok_added_at'] = now();
        }

        $tematik = Tematik::create($validated);
        return response()->json($tematik, 201);
    }

    public function show($id)
    {
        $tematik = Tematik::findOrFail($id);
        return response()->json($tematik);
    }

    public function update(Request $request, $id)
    {
        try {
            $tematik = Tematik::findOrFail($id);

            $validated = $request->validate([
                'nomor_rak' => 'required|string|max:20',
                'judul' => 'required|string|max:255',
                'penerbit' => 'required|string|max:100',
                'kelas' => 'required|string|max:10',
                'semester' => 'required|in:1,2,lanjutan',
                'kurikulum' => 'required|in:kurikulum_merdeka,kurikulum_2013,umum',
                'stok' => 'required|integer|min:0',
                'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($request->hasFile('gambar')) {
                if ($tematik->gambar && Storage::disk('public')->exists($tematik->gambar)) {
                    Storage::disk('public')->delete($tematik->gambar);
                }

                $file = $request->file('gambar');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('uploads/tematik', $filename, 'public');
                $validated['gambar'] = $path;
            }

            $stokSebelum = (int) $tematik->stok;
            $stokSesudah = (int) $validated['stok'];
            if ($stokSesudah !== $stokSebelum) {
                $validated['last_stok_added_at'] = now();
            }

            $tematik->update($validated);
            StockHistoryRecorder::record($request, 'tematik', $tematik, $stokSebelum, $stokSesudah, $tematik->judul);
            return response()->json([
                'message' => 'Data tematik berhasil diupdate',
                'data' => $tematik,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal update data tematik',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $tematik = Tematik::findOrFail($id);

            if ($tematik->gambar && Storage::disk('public')->exists($tematik->gambar)) {
                Storage::disk('public')->delete($tematik->gambar);
            }

            $tematik->delete();
            return response()->json([
                'message' => 'Data tematik berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menghapus data tematik',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
