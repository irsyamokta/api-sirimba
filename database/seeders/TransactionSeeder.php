<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Category;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $members = User::where('role', 'member')->get();
        $incomeCategories = Category::where('type', 'income')->pluck('id')->toArray();
        $expenseCategories = Category::where('type', 'expense')->pluck('id')->toArray();

        $transactions = [];

        foreach ($members as $member) {
            for ($i = 1; $i <= 3; $i++) {
                $transactions[] = [
                    'id' => (string) Str::uuid(),
                    'member_id' => $member->id,
                    'category_id' => $expenseCategories[array_rand($expenseCategories)],
                    'title' => 'Penarikan Dana ' . $i,
                    'amount' => rand(100000, 300000),
                    'transaction_date' => Carbon::now()->subDays(rand(1, 20)),
                    'note' => 'Penarikan oleh ' . $member->name,
                    'type' => 'expense',
                    'evidence' => 'https://res.cloudinary.com/dpmujiyre/image/upload/v1761882957/evidence_qhclud.png',
                    'public_id' => 'evidence_qhclud',
                    'payment_method' => 'cash',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        $totalTransactions = 20;
        $remaining = max($totalTransactions - count($transactions), 0);

        for ($i = 0; $i < $remaining; $i++) {
            $isIncome = rand(0, 1) === 1;
            $type = $isIncome ? 'income' : 'expense';
            $categories = $isIncome ? $incomeCategories : $expenseCategories;

            $transactions[] = [
                'id' => (string) Str::uuid(),
                'category_id' => $categories[array_rand($categories)],
                'title' => $isIncome ? 'Transaksi Pemasukan' : 'Transaksi Operasional',
                'amount' => $isIncome ? rand(200000, 600000) : rand(50000, 250000),
                'transaction_date' => Carbon::now()->subDays(rand(1, 30)),
                'note' => $isIncome ? 'Pemasukan dari kegiatan KTH' : 'Pengeluaran operasional KTH',
                'type' => $type,
                'evidence' => 'https://res.cloudinary.com/dpmujiyre/image/upload/v1761882957/evidence_qhclud.png',
                'public_id' => 'evidence_qhclud',
                'payment_method' => $isIncome ? 'bank_transfer' : 'cash',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach ($transactions as $trx) {
            Transaction::updateOrCreate(['id' => $trx['id']], $trx);
        }
    }
}
