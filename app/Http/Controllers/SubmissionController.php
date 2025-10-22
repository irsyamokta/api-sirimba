<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Submission;
use App\Models\Price;
use App\Helpers\ValidationHelper;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Carbon\Carbon;

class SubmissionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $search = $request->query('search');
            $memberId = $request->query('member_id');
            $perPage = $request->query('per_page', 10);

            if ($memberId) {
                if (in_array($user->role, ['admin', 'super_admin'])) {
                    $totalAmount = Submission::where('member_id', $memberId)->sum('amount');
                } elseif ($user->role === 'member' && $user->id == $memberId) {
                    $totalAmount = Submission::where('member_id', $user->id)->sum('amount');
                } else {
                    return response()->json(['message' => 'Akses ditolak.'], 403);
                }

                return response()->json([
                    'total_amount' => $totalAmount,
                ], 200);
            }

            $query = Submission::with(['member:id,name,phone']);

            if ($user->role === 'member') {
                $query->where('member_id', $user->id);
            } else {
                $query->when($search, function ($q) use ($search) {
                    $q->whereHas('member', function ($memberQuery) use ($search) {
                        $memberQuery->where('name', 'like', '%' . $search . '%');
                    });
                });
            }

            $query->orderBy('submission_date', 'desc');
            $submissions = $query->paginate($perPage);

            $totalAmount = null;
            if ($user->role === 'member') {
                $totalAmount = Submission::where('member_id', $user->id)->sum('amount');
            }

            return response()->json([
                'submission' => $submissions->items(),
                'pagination' => [
                    'current_page' => $submissions->currentPage(),
                    'last_page' => $submissions->lastPage(),
                    'per_page' => $submissions->perPage(),
                    'total' => $submissions->total(),
                ],
                'total_amount' => $totalAmount,
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
        $submission = Submission::with([
            'member:id,name,phone',
        ])->find($id);

        if (!$submission) {
            return response()->json(['message' => 'Pengajuan tidak ditemukan.'], 404);
        }

        return response()->json($submission);
    }

    public function store(Request $request)
    {
        try {
            $validator = ValidationHelper::validateSubmission(request()->all(), true);
            if ($validator->fails()) {
                $errors = $validator->errors()->toArray();
                $firstField = array_key_first($errors);
                $firstMessage = $errors[$firstField][0];

                return response()->json([
                    'message' => $firstMessage
                ], 422);
            }

            $data = $validator->validated();

            if ($request->hasFile('evidence')) {
                $uploaded = Cloudinary::uploadApi()->upload($request->file('evidence')->getRealPath(), [
                    'folder' => 'images/submission',
                ]);

                $data['evidence'] = $uploaded['secure_url'];
                $data['public_id'] = $uploaded['public_id'];
            }

            $price = Price::first();

            Submission::create([
                'member_id' => $data['member_id'],
                'total_honey' => $data['total_honey'],
                'amount' => $data['total_honey'] * $price->price,
                'submission_date' => Carbon::now(),
                'evidence' => $data['evidence'],
                'public_id' => $data['public_id']
            ]);

            return response()->json([
                'message' => 'Pengajuan berhasil ditambahkan.',
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
            $submission = Submission::find($id);
            if (!$submission) {
                return response()->json(['message' => 'Pengajuan tidak ditemukan.'], 404);
            }

            $validator = ValidationHelper::validateSubmission(request()->all(), false);
            if ($validator->fails()) {
                $errors = $validator->errors()->toArray();
                $firstField = array_key_first($errors);
                $firstMessage = $errors[$firstField][0];

                return response()->json([
                    'message' => $firstMessage
                ], 422);
            }

            $data = $validator->validated();

            $imageUrl = $submission->evidence;
            $publicId = $submission->public_id;

            if ($request->hasFile('evidence')) {
                if ($submission->public_id) {
                    Cloudinary::uploadApi()->destroy($submission->public_id);
                }

                $uploaded = Cloudinary::uploadApi()->upload($request->file('evidence')->getRealPath(), [
                    'folder' => 'images/submission',
                ]);

                $imageUrl = $uploaded['secure_url'];
                $publicId = $uploaded['public_id'];
            }

            $price = Price::first();

            $submission->update([
                'member_id' => $data['member_id'] ?? $submission->member_id,
                'total_honey' => $data['total_honey'] ?? $submission->total_honey,
                'amount' => $data['total_honey'] * $price->price ?? $submission->amount,
                'submission_date' => $submission->submission_date,
                'evidence' => $imageUrl,
                'public_id' => $publicId
            ]);

            return response()->json([
                'message' => 'Pengajuan berhasil diperbarui.',
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
            $submission = Submission::find($id);
            if (!$submission) {
                return response()->json(['message' => 'Pengajuan tidak ditemukan.'], 404);
            }

            if ($submission->public_id) {
                Cloudinary::uploadApi()->destroy($submission->public_id);
            }

            $submission->delete();
            return response()->json([
                'message' => 'Pengajuan berhasil dihapus.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
