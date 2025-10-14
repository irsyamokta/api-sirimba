<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Validator;

class ValidationHelper
{
    public static function validateLogin($data)
    {
        return Validator::make(
            $data,
            [
                'phone' => 'required|numeric',
                'password' => 'required',
            ],
            [
                'phone.required' => 'Nomor telepon wajib diisi.',
                'phone.numeric' => 'Nomor telepon harus berupa angka.',
                'password.required' => 'Kata sandi wajib diisi.',
            ],
        );
    }

    public static function validateUser($data, $isStore = false)
    {
        return Validator::make(
            $data,
            [
                'name' => ($isStore ? 'required' : 'sometimes|required') . '|string|max:255',
                'phone' => ($isStore ? 'required' : 'sometimes|required') . '|digits_between:10,15',
                'gender' => ($isStore ? 'required' : 'sometimes|required') . '|in:male,female',
                'birthdate' => ($isStore ? 'required' : 'sometimes|required') . '|string|date_format:Y-m-d',
                'address' => ($isStore ? 'required' : 'sometimes|required') . '|string',
                'password' => ($isStore ? 'required' : 'sometimes|required') . '|string|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/',
                'role' => ($isStore ? 'required' : 'sometimes|required') . '|in:super_admin,admin,member',
                'avatar' => ($isStore ? 'required' : 'sometimes|required') . '|mimetypes:image/jpeg,image/png,image/jpg|max:2048',
            ],
            [
                'name.required' => 'Nama wajib diisi.',
                'name.string' => 'Nama harus berupa teks.',
                'name.max' => 'Nama tidak boleh lebih dari 255 karakter.',
                'phone.required' => 'Nomor telepon wajib diisi.',
                'phone.digits_between' => 'Nomor telepon harus berupa angka antara 10 sampai 15 digit.',
                'gender.required' => 'Jenis kelamin wajib diisi.',
                'gender.in' => 'Jenis kelamin harus berupa "male" atau "female".',
                'birthdate.required' => 'Tanggal lahir wajib diisi.',
                'birthdate.string' => 'Tanggal lahir harus berupa teks.',
                'address.required' => 'Alamat wajib diisi.',
                'address.string' => 'Alamat harus berupa teks.',
                'role.required' => 'Role wajib diisi.',
                'role.in' => 'Role harus berupa "super_admin", "admin", atau "member".',
                'password.required' => 'Kata sandi wajib diisi.',
                'password.min' => 'Kata sandi minimal harus terdiri dari 8 karakter.',
                'password.regex' => 'Kata sandi harus mengandung setidaknya 1 huruf besar, 1 huruf kecil, 1 angka, dan 1 karakter khusus.',
                'avatar.required' => 'Avatar wajib diisi.',
                'avatar.mimetypes' => 'Avatar harus berupa file JPEG, PNG, atau JPG.',
                'avatar.max' => 'Ukuran file avatar tidak boleh lebih dari 2 MB.',
            ]
        );
    }

    public static function validateCategory($data, $isStore = false)
    {
        return Validator::make(
            $data,
            [
                'category_name' => ($isStore ? 'required' : 'sometimes|required') . '|string|max:255',
                'type' => ($isStore ? 'required' : 'sometimes|required') . '|string|in:income,expense',
            ],
            [
                'category_name.required' => 'Nama kategori wajib diisi.',
                'category_name.string' => 'Nama kategori harus berupa teks.',
                'category_name.max' => 'Nama kategori tidak boleh lebih dari 255 karakter.',
                'type.required' => 'Tipe kategori wajib diisi.',
                'type.string' => 'Tipe kategori harus berupa teks.',
                'type.in' => 'Tipe kategori harus berupa "income" atau "expense".',
            ]
        );
    }
}
