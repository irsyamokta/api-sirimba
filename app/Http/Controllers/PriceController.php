<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Price;
use App\Helpers\ValidationHelper;

class PriceController extends Controller
{
    public function index()
    {
        $prices = Price::all();
        return response()->json($prices);
    }

    public function show($id)
    {
        $price = Price::find($id);
        if (!$price) {
            return response()->json(['message' => 'Harga tidak ditemukan.'], 404);
        }

        return response()->json($price);
    }

    public function store(Request $request)
    {
        try {
            $validator = ValidationHelper::validatePrice($request->all(), true);
            if ($validator->fails()) {
                $errors = $validator->errors()->toArray();
                $firstField = array_key_first($errors);
                $firstMessage = $errors[$firstField][0];

                return response()->json([
                    'message' => $firstMessage
                ], 422);
            }

            $data = $validator->validated();

            Price::create($data);

            return response()->json([
                'message' => 'Harga berhasil ditambahkan.'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $price = Price::find($id);
            if (!$price) {
                return response()->json(['message' => 'Harga tidak ditemukan.'], 404);
            }

            $validator = ValidationHelper::validatePrice($request->all(), false);
            if ($validator->fails()) {
                $errors = $validator->errors()->toArray();
                $firstField = array_key_first($errors);
                $firstMessage = $errors[$firstField][0];

                return response()->json([
                    'message' => $firstMessage
                ], 422);
            }

            $data = $validator->validated();

            $price->update($data);
            return response()->json([
                'message' => 'Harga berhasil diperbarui.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $price = Price::find($id);
            if (!$price) {
                return response()->json(['message' => 'Harga tidak ditemukan.'], 404);
            }

            $price->delete();
            return response()->json([
                'message' => 'Harga berhasil dihapus.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
