<?php

namespace App\Http\Controllers;

use App\Models\Atk;
use App\Support\StockHistoryRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AtkController extends Controller
{
    public function index()
    {
        return response()->json(Atk::orderBy('id')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_barang' => 'required|string|max:50',
            'barcode' => 'nullable|string|max:100',
            'nama_barang' => 'required|string|max:255',
            'kategori' => 'required|in:alat_tulis,kertas,peralatan,lainnya',
            'satuan' => 'required|in:pcs,box,pak,rim',
            'stok' => 'required|integer|min:0',
            'merk' => 'required|string|max:100',
            'jumlah' => 'required|integer|min:0',
            'keterangan' => 'nullable|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/atk', $filename, 'public');
            $validated['gambar'] = $path;
        }
        if ((int) $validated['stok'] > 0) {
            $validated['last_stok_added_at'] = now();
        }

        $atk = Atk::create($validated);

        return response()->json($atk, 201);
    }

    public function show($id)
    {
        return response()->json(Atk::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $atk = Atk::findOrFail($id);

        $validated = $request->validate([
            'kode_barang' => 'required|string|max:50',
            'barcode' => 'nullable|string|max:100',
            'nama_barang' => 'required|string|max:255',
            'kategori' => 'required|in:alat_tulis,kertas,peralatan,lainnya',
            'satuan' => 'required|in:pcs,box,pak,rim',
            'stok' => 'required|integer|min:0',
            'merk' => 'required|string|max:100',
            'jumlah' => 'required|integer|min:0',
            'keterangan' => 'nullable|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('gambar')) {
            if ($atk->gambar && Storage::disk('public')->exists($atk->gambar)) {
                Storage::disk('public')->delete($atk->gambar);
            }

            $file = $request->file('gambar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/atk', $filename, 'public');
            $validated['gambar'] = $path;
        }

        $stokSebelum = (int) $atk->stok;
        $stokSesudah = (int) $validated['stok'];
        if ($stokSesudah !== $stokSebelum) {
            $validated['last_stok_added_at'] = now();
        }

        $atk->update($validated);
        StockHistoryRecorder::record($request, 'atk', $atk, $stokSebelum, $stokSesudah, $atk->nama_barang);

        return response()->json([
            'message' => 'Data ATK berhasil diupdate',
            'data' => $atk,
        ]);
    }

    public function destroy($id)
    {
        $atk = Atk::findOrFail($id);

        if ($atk->gambar && Storage::disk('public')->exists($atk->gambar)) {
            Storage::disk('public')->delete($atk->gambar);
        }

        $atk->delete();

        return response()->json([
            'message' => 'Data ATK berhasil dihapus',
        ]);
    }
}
