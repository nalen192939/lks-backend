<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Data;
use Illuminate\Validation\Rule;

class DataController extends Controller
{
    public function index() {
        return response()->json(Data::all());
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'nik' => 'required|string|unique:data,nik',
            'nama' => 'required|string',
            'telepon' => 'required|string|unique:data,telepon',
            'alamat' => 'required|string',
        ], [
            'nik.unique' => 'NIK sudah terdaftar dalam sistem',
            'telepon.unique' => 'Nomor telepon sudah terdaftar dalam sistem',
        ]);

        $data = Data::create($validated);
        return response()->json($data, 201);
    }

    public function update(Request $request, $id) {
        $data = Data::findOrFail($id);
        
        $validated = $request->validate([
            'nik' => [
                'required',
                'string',
                Rule::unique('data')->ignore($data->id)
            ],
            'nama' => 'required|string',
            'telepon' => [
                'required',
                'string',
                Rule::unique('data')->ignore($data->id)
            ],
            'alamat' => 'required|string',
        ], [
            'nik.unique' => 'NIK sudah terdaftar dalam sistem',
            'telepon.unique' => 'Nomor telepon sudah terdaftar dalam sistem',
        ]);

        $data->update($validated);
        return response()->json($data);
    }

    public function destroy($id) {
        $data = Data::findOrFail($id);
        $data->delete();
        return response()->json(['message' => 'Data berhasil dihapus']);
    }
}