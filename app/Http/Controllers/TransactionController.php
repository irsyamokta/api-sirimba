<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\ValidationHelper;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Submission;
use Carbon\Carbon;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $search = $request->query('search');
            $perPage = $request->query('per_page', 10);
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            $type = $request->query('type');
            $categoryId = $request->query('category_id');

            $query = Transaction::with([
                'member:id,name,phone',
                'category:id,category_name'
            ]);

            if ($user->role === 'member') {
                $query->where('member_id', $user->id);
            } else {
                $query->when($search, function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhereHas('member', function ($memberQuery) use ($search) {
                            $memberQuery->where('name', 'like', "%{$search}%");
                        });
                });
            }

            if ($startDate && $endDate) {
                $start = Carbon::parse($startDate)->startOfDay();
                $end = Carbon::parse($endDate)->endOfDay();
                $query->whereBetween('transaction_date', [$start, $end]);
            }

            if ($type) {
                $typeValues = is_array($type) ? $type : explode(',', $type);
                $query->whereIn('type', $typeValues);
            }

            if ($categoryId) {
                $categoryValues = is_array($categoryId) ? $categoryId : explode(',', $categoryId);
                $query->whereIn('category_id', $categoryValues);
            }

            $query->orderBy('transaction_date', 'desc');

            $transactions = $query->paginate($perPage);

            return response()->json([
                'transactions' => $transactions->items(),
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, $id)
    {
        $transaction = Transaction::with([
            'member:id,name,phone',
            'category:id,category_name'
        ])->find($id);

        if (!$transaction) {
            return response()->json([
                'message' => 'Transaksi tidak ditemukan.'
            ], 404);
        }

        return response()->json($transaction);
    }

    public function store(Request $request)
    {
        try {
            $validator = ValidationHelper::validateTransaction(request()->all(), true);
            if ($validator->fails()) {
                $errors = $validator->errors()->toArray();
                $firstField = array_key_first($errors);
                $firstMessage = $errors[$firstField][0];

                return response()->json(['message' => $firstMessage], 422);
            }

            $data = $validator->validated();

            if ($data['type'] === 'expense' && isset($data['member_id'])) {
                $member = User::find($data['member_id']);

                if (!$member) {
                    return response()->json([
                        'message' => 'Anggota tidak ditemukan.'
                    ], 404);
                }

                $totalAmount = Submission::where('member_id', $member->id)->sum('amount');

                $totalWithdrawn = Transaction::where('type', 'expense')
                    ->where('member_id', $member->id)
                    ->sum('amount');

                $remainingLimit = $totalAmount - $totalWithdrawn;

                if ($data['amount'] > $remainingLimit) {
                    return response()->json([
                        'message' => 'Jumlah penarikan melebihi batas maksimal. Maksimal penarikan tersisa: ' . number_format($remainingLimit, 0, ',', '.')
                    ], 400);
                }
            }

            DB::beginTransaction();

            if ($request->hasFile('evidence')) {
                $uploaded = Cloudinary::uploadApi()->upload(
                    $request->file('evidence')->getRealPath(),
                    ['folder' => 'images/transactions']
                );
                $data['evidence'] = $uploaded['secure_url'];
                $data['public_id'] = $uploaded['public_id'];
            }

            Transaction::create([
                'title' => $data['title'],
                'amount' => $data['amount'],
                'transaction_date' => $data['transaction_date'],
                'type' => $data['type'],
                'category_id' => $data['category_id'],
                'payment_method' => $data['payment_method'],
                'note' => $data['note'] ?? null,
                'member_id' => $data['member_id'] ?? null,
                'evidence' => $data['evidence'],
                'public_id' => $data['public_id'],
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Transaksi berhasil ditambahkan.'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Terjadi kesalahan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $transaction = Transaction::find($id);
            if (!$transaction) {
                return response()->json([
                    'message' => 'Transaksi tidak ditemukan.'
                ], 404);
            }

            $validator = ValidationHelper::validateTransaction(request()->all(), false);
            if ($validator->fails()) {
                $errors = $validator->errors()->toArray();
                $firstField = array_key_first($errors);
                $firstMessage = $errors[$firstField][0];

                return response()->json(['message' => $firstMessage], 422);
            }

            $data = $validator->validated();
            $imageUrl = $transaction->evidence;
            $publicId = $transaction->public_id;

            if ($data['type'] === 'expense' && isset($data['member_id'])) {
                $member = User::find($data['member_id']);

                if (!$member) {
                    return response()->json([
                        'message' => 'Anggota tidak ditemukan.'
                    ], 404);
                }

                $totalAmount = Submission::where('member_id', $member->id)->sum('amount');

                $totalWithdrawn = Transaction::where('type', 'expense')
                    ->where('member_id', $member->id)
                    ->sum('amount');

                $remainingLimit = $totalAmount - $totalWithdrawn;

                if ($data['amount'] > $remainingLimit) {
                    return response()->json([
                        'message' => 'Jumlah penarikan melebihi batas maksimal. Maksimal penarikan tersisa: ' . number_format($remainingLimit, 0, ',', '.')
                    ], 400);
                }
            }

            DB::beginTransaction();

            if ($request->hasFile('evidence')) {
                if ($transaction->public_id) {
                    Cloudinary::uploadApi()->destroy($transaction->public_id);
                }

                $uploaded = Cloudinary::uploadApi()->upload(
                    $request->file('evidence')->getRealPath(),
                    ['folder' => 'images/transactions',]
                );

                $imageUrl = $uploaded['secure_url'];
                $publicId = $uploaded['public_id'];
            }

            $transaction->update([
                'title' => $data['title'] ?? $transaction->title,
                'amount' => $data['amount'] ?? $transaction->amount,
                'transaction_date' => $data['transaction_date'] ?? $transaction->transaction_date,
                'type' => $data['type'] ?? $transaction->type,
                'category_id' => $data['category_id'] ?? $transaction->category_id,
                'payment_method' => $data['payment_method'] ?? $transaction->payment_method,
                'note' => $data['note'] ?? $transaction->note ?? null,
                'member_id' => $data['member_id'] ?? $transaction->member_id ?? null,
                'evidence' => $imageUrl,
                'public_id' => $publicId,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Transaksi berhasil diperbarui.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Terjadi kesalahan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $transaction = Transaction::find($id);
            if (!$transaction) {
                return response()->json([
                    'message' => 'Transaksi tidak ditemukan.'
                ], 404);
            }

            if ($transaction->public_id) {
                Cloudinary::uploadApi()->destroy($transaction->public_id);
            }

            $transaction->delete();

            return response()->json([
                'message' => 'Transaksi berhasil dihapus.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
