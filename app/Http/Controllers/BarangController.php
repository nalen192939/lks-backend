<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Support\StockHistoryRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BarangController extends Controller
{
    protected function mapBarangResponse(Barang $barang): array
    {
        return [
            'id' => $barang->id,
            'kode_barang' => $barang->kode_barang,
            'nama_barang' => $barang->nama_barang,
            'kategori' => $barang->kategori,
            'satuan' => $barang->satuan,
            'stok' => (int) ($barang->stok ?? 0),
            'jumlah' => (int) ($barang->jumlah ?? 0),
            'last_stok_added_at' => $barang->last_stok_added_at,
            'keterangan' => $barang->keterangan,
            'gambar' => $barang->gambar,
            'created_at' => $barang->created_at,
            'updated_at' => $barang->updated_at,

            // Backward compatibility for old frontend fields:
            'nama' => $barang->nama_barang,
            'merk' => $barang->keterangan,
        ];
    }

    protected function buildPayload(Request $request): array
    {
        $kodeBarang = trim((string) ($request->input('kode_barang') ?? $request->input('nomor') ?? ''));
        if ($kodeBarang === '') {
            $kodeBarang = 'ATK-' . now()->format('YmdHis');
        }

        $namaBarang = trim((string) ($request->input('nama_barang') ?? $request->input('nama') ?? ''));
        $keterangan = $request->input('keterangan');
        if (($keterangan === null || $keterangan === '') && $request->filled('merk')) {
            $keterangan = $request->input('merk');
        }

        return [
            'kode_barang' => $kodeBarang,
            'nama_barang' => $namaBarang,
            'kategori' => $request->input('kategori', 'alat_tulis'),
            'satuan' => $request->input('satuan', 'pcs'),
            'stok' => (int) $request->input('stok', 0),
            'jumlah' => (int) $request->input('jumlah', 0),
            'keterangan' => $keterangan,
        ];
    }

    protected function rules(bool $isUpdate = false): array
    {
        return [
            'kode_barang' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:50'],
            'nama_barang' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'kategori' => [$isUpdate ? 'sometimes' : 'required', 'in:alat_tulis,kertas,peralatan,lainnya'],
            'satuan' => [$isUpdate ? 'sometimes' : 'required', 'in:pcs,box,pak,rim'],
            'stok' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'min:0'],
            'jumlah' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'min:0'],
            'keterangan' => ['nullable', 'string'],
            'gambar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ];
    }

    public function index()
    {
        $rows = Barang::orderBy('id')->get()->map(function (Barang $barang) {
            return $this->mapBarangResponse($barang);
        });

        return response()->json($rows);
    }

    public function store(Request $request)
    {
        $payload = $this->buildPayload($request);
        $validator = Validator::make(
            array_merge($payload, ['gambar' => $request->file('gambar')]),
            $this->rules()
        );
        $validated = $validator->validate();

        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/barang', $filename, 'public');
            $validated['gambar'] = $path;
        }
        if ((int) $validated['stok'] > 0) {
            $validated['last_stok_added_at'] = now();
        }

        $barang = Barang::create($validated);
        return response()->json($this->mapBarangResponse($barang), 201);
    }

    public function show($id)
    {
        $barang = Barang::findOrFail($id);
        return response()->json($this->mapBarangResponse($barang));
    }

    public function update(Request $request, $id)
    {
        try {
            $barang = Barang::findOrFail($id);
            $payload = $this->buildPayload($request);

            // Keep existing values when field is not provided by client.
            if (!$request->filled('kode_barang') && !$request->filled('nomor')) {
                $payload['kode_barang'] = $barang->kode_barang;
            }
            if (!$request->filled('nama_barang') && !$request->filled('nama')) {
                $payload['nama_barang'] = $barang->nama_barang;
            }
            if (!$request->filled('kategori')) {
                $payload['kategori'] = $barang->kategori;
            }
            if (!$request->filled('satuan')) {
                $payload['satuan'] = $barang->satuan;
            }
            if (!$request->has('stok')) {
                $payload['stok'] = (int) $barang->stok;
            }
            if (!$request->has('jumlah')) {
                $payload['jumlah'] = (int) ($barang->jumlah ?? 0);
            }
            if (!$request->filled('keterangan') && !$request->filled('merk')) {
                $payload['keterangan'] = $barang->keterangan;
            }

            $validator = Validator::make(
                array_merge($payload, ['gambar' => $request->file('gambar')]),
                $this->rules(true)
            );
            $validated = $validator->validate();

            if ($request->hasFile('gambar')) {
                if ($barang->gambar && Storage::disk('public')->exists($barang->gambar)) {
                    Storage::disk('public')->delete($barang->gambar);
                }

                $file = $request->file('gambar');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('uploads/barang', $filename, 'public');
                $validated['gambar'] = $path;
            } else {
                unset($validated['gambar']);
            }

            $stokSebelum = (int) $barang->stok;
            $stokSesudah = (int) $validated['stok'];
            if ($stokSesudah !== $stokSebelum) {
                $validated['last_stok_added_at'] = now();
            }

            $barang->update($validated);
            StockHistoryRecorder::record($request, 'barang', $barang, $stokSebelum, $stokSesudah, $barang->nama_barang);

            return response()->json([
                'message' => 'Data barang berhasil diupdate',
                'data' => $this->mapBarangResponse($barang),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal update data barang',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $barang = Barang::findOrFail($id);

            if ($barang->gambar && Storage::disk('public')->exists($barang->gambar)) {
                Storage::disk('public')->delete($barang->gambar);
            }

            $barang->delete();

            return response()->json([
                'message' => 'Data barang berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menghapus data barang',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
