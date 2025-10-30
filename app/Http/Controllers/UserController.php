<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Helpers\ValidationHelper;
use App\Models\User;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class UserController extends Controller
{
    public function index(Request $request)
    {
        try {
            $search = $request->query('search');
            $role = $request->query('role');
            $gender = $request->query('gender');
            $perPage = $request->query('per_page', 10);

            $query = User::query();

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('phone', 'LIKE', "%{$search}%");
                });
            }

            if ($role) {
                $query->where('role', $role);
            }

            if ($gender) {
                $query->where('gender', $gender);
            }

            $query->orderBy('created_at', 'desc');

            $users = $query->paginate($perPage);

            return response()->json([
                'users' => $users->items(),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'last_page' => $users->lastPage(),
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, $id)
    {
        $user = User::with('submissions')->find($id);
        if (!$user) {
            return response()->json(['message' => 'Anggota tidak ditemukan.'], 404);
        }

        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'gender' => $user->gender,
            'birthdate' => $user->birthdate,
            'address' => $user->address,
            'role' => $user->role,
            'avatar' => $user->avatar,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'submissions' => $user->submissions->map(function ($submission) {
                return [
                    'id' => $submission->id,
                    'total_honey' => $submission->total_honey,
                    'submission_date' => $submission->submission_date,
                    'amount' => $submission->amount,
                    'evidence' => $submission->evidence,
                ];
            }),
        ];

        return response()->json($data, 200);
    }

    public function store(Request $request)
    {
        try {
            $validator = ValidationHelper::validateUser(request()->all(), true);
            if ($validator->fails()) {
                $errors = $validator->errors()->toArray();
                $firstField = array_key_first($errors);
                $firstMessage = $errors[$firstField][0];

                return response()->json([
                    'message' => $firstMessage
                ], 422);
            }

            $data = $validator->validated();

            if (User::where('phone', $data['phone'])->exists()) {
                return response()->json(['message' => 'Nomor telepon sudah terdaftar.'], 422);
            }

            if ($request->hasFile('avatar')) {
                $uploaded = Cloudinary::uploadApi()->upload($request->file('avatar')->getRealPath(), [
                    'folder' => 'images/avatar',
                ]);

                $data['avatar'] = $uploaded['secure_url'];
                $data['public_id'] = $uploaded['public_id'];
            }

            User::create($data);

            return response()->json([
                'message' => 'Pendaftaran anggota berhasil.',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'message' => 'Anggota tidak ditemukan.'
                ], 404);
            }

            $validator = ValidationHelper::validateUser($request->all(), false);
            if ($validator->fails()) {
                $errors = $validator->errors()->toArray();
                $firstField = array_key_first($errors);
                $firstMessage = $errors[$firstField][0];

                return response()->json([
                    'message' => $firstMessage
                ], 422);
            }

            $data = $validator->validated();
            $imageUrl = $user->avatar;
            $publicId = $user->public_id;

            if (!empty($data['phone']) && User::where('phone', $data['phone'])->where('id', '!=', $user->id)->exists()) {
                return response()->json(['message' => 'Nomor telepon sudah terdaftar.'], 422);
            }

            if ($user->role === 'member') {
                $allowed = ['name', 'gender', 'birthdate'];

                $disallowedFields = array_diff(array_keys($data), $allowed);
                if (!empty($disallowedFields)) {
                    return response()->json([
                        'message' => 'Anggota tidak diizinkan mengubah field: ' . implode(', ', $disallowedFields)
                    ], 403);
                }

                if ($request->hasFile('avatar')) {
                    return response()->json(['message' => 'Anggota tidak diizinkan mengubah avatar.'], 403);
                }
            } else {
                $allowed = ['name', 'phone', 'gender', 'birthdate', 'address'];
            }

            if ($user->role !== 'member' && $request->hasFile('avatar')) {
                if ($user->public_id) {
                    Cloudinary::uploadApi()->destroy($user->public_id);
                }

                $uploaded = Cloudinary::uploadApi()->upload($request->file('avatar')->getRealPath(), [
                    'folder' => 'images/avatar',
                ]);

                $imageUrl = $uploaded['secure_url'];
                $publicId = $uploaded['public_id'];
            }

            $filteredData = array_intersect_key($data, array_flip($allowed));
            $filteredData['avatar'] = $imageUrl;
            $filteredData['public_id'] = $publicId;

            $user->update($filteredData);

            return response()->json([
                'message' => 'Profil berhasil diperbarui.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateUserById(Request $request, $id)
    {
        try {
            $user = User::find($id);
            if (!$user) {
                return response()->json(['message' => 'Anggota tidak ditemukan.'], 404);
            }

            $validator = ValidationHelper::validateUser(request()->all(), false);
            if ($validator->fails()) {
                $errors = $validator->errors()->toArray();
                $firstField = array_key_first($errors);
                $firstMessage = $errors[$firstField][0];

                return response()->json([
                    'message' => $firstMessage
                ], 422);
            }

            $data = $validator->validated();
            $imageUrl = $user->avatar;
            $publicId = $user->public_id;

            if (!empty($data['phone']) && User::where('phone', $data['phone'])->where('id', '!=', $id)->exists()) {
                return response()->json(['message' => 'Nomor telepon sudah terdaftar.'], 422);
            }

            if ($request->has('password')) {
                $data['password'] = Hash::make($data['password']);
            }

            if ($request->hasFile('avatar')) {
                if ($user->public_id) {
                    Cloudinary::uploadApi()->destroy($user->public_id);
                }

                $uploaded = Cloudinary::uploadApi()->upload($request->file('avatar')->getRealPath(), [
                    'folder' => 'images/avatar',
                ]);

                $imageUrl = $uploaded['secure_url'];
                $publicId = $uploaded['public_id'];
            }

            $user->update([
                'name' => $data['name'] ?? $user->name,
                'phone' => $data['phone'] ?? $user->phone,
                'gender' => $data['gender'] ?? $user->gender,
                'birthdate' => $data['birthdate'] ?? $user->birthdate,
                'address' => $data['address'] ?? $user->address,
                'role' => $data['role'] ?? $user->role,
                'password' => $data['password'] ?? $user->password,
                'avatar' => $imageUrl,
                'public_id' => $publicId
            ]);

            return response()->json([
                'message' => 'Profil anggota berhasil diperbarui.',
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
            $user = User::find($id);
            if (!$user) {
                return response()->json(['message' => 'Anggota tidak ditemukan.'], 404);
            }

            if ($user->public_id) {
                Cloudinary::uploadApi()->destroy($user->public_id);
            }

            $user->delete();

            return response()->json([
                'message' => 'Anggota berhasil dihapus.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
