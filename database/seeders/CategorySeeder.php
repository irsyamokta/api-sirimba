<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'id' => Str::uuid(),
                'category_name' => 'Penjualan Madu Hutan',
                'type' => 'income',
            ],
            [
                'id' => Str::uuid(),
                'category_name' => 'Pembelian Peralatan',
                'type' => 'expense',
            ],
            [
                'id' => Str::uuid(),
                'category_name' => 'Operasional KTH',
                'type' => 'expense',
            ],
            [
                'id' => Str::uuid(),
                'category_name' => 'Pembayaran Anggota',
                'type' => 'expense',
            ],
            [
                'id' => Str::uuid(),
                'category_name' => 'Donasi atau Bantuan',
                'type' => 'income',
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
