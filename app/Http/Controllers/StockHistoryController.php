<?php

namespace App\Http\Controllers;

use App\Models\StockHistory;
use Illuminate\Http\Request;

class StockHistoryController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'item_type' => 'nullable|in:keterangan,tematik,barang,atk',
            'item_id' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $query = StockHistory::query()->latest();

        if (!empty($validated['item_type'])) {
            $query->where('item_type', $validated['item_type']);
        }

        if (!empty($validated['item_id'])) {
            $query->where('item_id', $validated['item_id']);
        }

        return response()->json($query->limit($validated['limit'] ?? 30)->get());
    }
}
