<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Keterangan;
use App\Support\StockHistoryRecorder;
use Illuminate\Support\Facades\Storage;

class KeteranganController extends Controller
{
    public function index()
    {
        return response()->json(Keterangan::all());
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
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // Handle upload gambar
        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/buku', $filename, 'public');
            $validated['gambar'] = $path;
        }
        if ((int) $validated['stok'] > 0) {
            $validated['last_stok_added_at'] = now();
        }

        $keterangan = Keterangan::create($validated);
        return response()->json($keterangan, 201);
    }

    public function show($id)
    {
        $keterangan = Keterangan::findOrFail($id);
        return response()->json($keterangan);
    }

    public function update(Request $request, $id)
    {
        try {
            $keterangan = Keterangan::findOrFail($id);
            
            $validated = $request->validate([
                'nomor_rak' => 'required|string|max:20',
                'judul' => 'required|string|max:255',
                'penerbit' => 'required|string|max:100',
                'kelas' => 'required|string|max:10',
                'semester' => 'required|in:1,2,lanjutan',
                'kurikulum' => 'required|in:kurikulum_merdeka,kurikulum_2013,umum',
                'stok' => 'required|integer|min:0',
                'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            // Handle upload gambar baru
            if ($request->hasFile('gambar')) {
                if ($keterangan->gambar && Storage::disk('public')->exists($keterangan->gambar)) {
                    Storage::disk('public')->delete($keterangan->gambar);
                }
                
                $file = $request->file('gambar');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('uploads/buku', $filename, 'public');
                $validated['gambar'] = $path;
            }

            $stokSebelum = (int) $keterangan->stok;
            $stokSesudah = (int) $validated['stok'];
            if ($stokSesudah !== $stokSebelum) {
                $validated['last_stok_added_at'] = now();
            }

            $keterangan->update($validated);
            StockHistoryRecorder::record($request, 'keterangan', $keterangan, $stokSebelum, $stokSesudah, $keterangan->judul);
            return response()->json([
                'message' => 'Data berhasil diupdate',
                'data' => $keterangan
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal update data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $keterangan = Keterangan::findOrFail($id);
            
            if ($keterangan->gambar && Storage::disk('public')->exists($keterangan->gambar)) {
                Storage::disk('public')->delete($keterangan->gambar);
            }
            
            $keterangan->delete();
            return response()->json([
                'message' => 'Data berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menghapus data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
