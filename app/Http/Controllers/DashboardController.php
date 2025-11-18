<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Submission;
use App\Models\Transaction;
use App\Models\Price;
use App\Helpers\PercentageHelper;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            $user = auth()->user();
            $role = $user->role;

            $now = now();
            $currentMonth = $now->month;
            $previousMonth = $now->subMonth()->month;

            // Helper untuk hitung persentase
            $percent = function ($current, $previous) {
                if ($previous == 0) {
                    return $current > 0 ? 100 : 0;
                }
                return round((($current - $previous) / $previous) * 100, 2);
            };

            if (in_array($role, ['admin', 'super_admin'])) {
                // Total users (lifetime)
                $totalUsers = User::whereNotIn('role', ['super_admin', 'admin'])->count();

                // Total users this month
                $totalUsersCurrent = User::whereNotIn('role', ['super_admin', 'admin'])
                    ->whereMonth('created_at', now()->month)
                    ->count();

                // Income lifetime
                $income = Transaction::where('type', 'income')->sum('amount');

                // Income this month
                $incomeCurrent = Transaction::where('type', 'income')
                    ->whereMonth('transaction_date', now()->month)
                    ->sum('amount');

                // Income last month
                $incomePrev = Transaction::where('type', 'income')
                    ->whereMonth('transaction_date', now()->subMonth()->month)
                    ->sum('amount');

                // Expense lifetime
                $expense = Transaction::where('type', 'expense')->sum('amount');

                // Expense this month
                $expenseCurrent = Transaction::where('type', 'expense')
                    ->whereMonth('transaction_date', now()->month)
                    ->sum('amount');

                // Expense last month
                $expensePrev = Transaction::where('type', 'expense')
                    ->whereMonth('transaction_date', now()->subMonth()->month)
                    ->sum('amount');

                // Revenue lifetime
                $revenue = $income - $expense;

                // Revenue this month vs last month
                $revenueCurrent = $incomeCurrent - $expenseCurrent;

                // Recent transactions
                $latestTransactions = Transaction::with('category:id,category_name')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get(['id', 'type', 'title', 'category_id', 'amount', 'transaction_date'])
                    ->map(function ($t) {
                        return [
                            'type' => $t->type,
                            'title' => $t->title,
                            'category' => $t->category->category_name ?? null,
                            'amount' => $t->amount,
                            'transaction_date' => $t->transaction_date
                        ];
                    });

                // Chart
                $chart = [];
                for ($i = 1; $i <= 12; $i++) {
                    $chart[] = [
                        'month' => $i,
                        'income' => Transaction::where('type', 'income')->whereMonth('transaction_date', $i)->sum('amount'),
                        'expense' => Transaction::where('type', 'expense')->whereMonth('transaction_date', $i)->sum('amount'),
                    ];
                }

                return response()->json([
                    'cards' => [
                        'total_users' => [
                            'value' => $totalUsers,
                            'percentage_change' => $totalUsersCurrent
                        ],
                        'income' => [
                            'value' => $income,
                            'percentage_change' => $percent($incomeCurrent, $incomePrev)
                        ],
                        'expense' => [
                            'value' => $expense,
                            'percentage_change' => $percent($expenseCurrent, $expensePrev)
                        ],
                        'revenue' => [
                            'value' => $revenue,
                            'percentage_change' => $revenueCurrent
                        ],
                    ],
                    'chart' => $chart,
                    'latest_transactions' => $latestTransactions
                ]);
            } else {
                $memberId = $user->id;

                // Price of honey
                $latestPrice = Price::latest()->value('price') ?? 0;

                // Submission count lifetime
                $submissionTotal = Submission::where('member_id', $memberId)->count();

                // Submission this month
                $submissionCurrent = Submission::where('member_id', $memberId)
                    ->whereMonth('submission_date', now()->month)
                    ->count();

                // Submission last month
                $submissionPrev = Submission::where('member_id', $memberId)
                    ->whereMonth('submission_date', now()->subMonth()->month)
                    ->count();

                // Withdraw lifetime (sum)
                $withdrawTotal = Transaction::where('member_id', $memberId)
                    ->where('type', 'expense')
                    ->sum('amount');

                // Withdraw this month
                $withdrawCurrent = Transaction::where('member_id', $memberId)
                    ->where('type', 'expense')
                    ->whereMonth('transaction_date', now()->month)
                    ->sum('amount');

                // Withdraw last month
                $withdrawPrev = Transaction::where('member_id', $memberId)
                    ->where('type', 'expense')
                    ->whereMonth('transaction_date', now()->subMonth()->month)
                    ->sum('amount');

                // Saldo (NO percentage)
                $submissionNominal = Submission::where('member_id', $memberId)->sum('amount');
                $available = $submissionNominal - $withdrawTotal;

                // Recent transactions
                $latestTransactions = Transaction::with('category:id,category_name')
                    ->where('member_id', $memberId)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get(['id', 'type', 'title', 'category_id', 'amount', 'transaction_date'])
                    ->map(function ($t) {
                        return [
                            'type' => $t->type,
                            'title' => $t->title,
                            'category' => $t->category->category_name ?? null,
                            'amount' => $t->amount,
                            'transaction_date' => $t->transaction_date
                        ];
                    });

                // Chart
                $chart = [];
                for ($i = 1; $i <= 12; $i++) {
                    $chart[] = [
                        'month' => $i,
                        'submission' => Submission::where('member_id', $memberId)
                            ->whereMonth('submission_date', $i)->sum('amount'),
                        'withdraw' => Transaction::where('member_id', $memberId)
                            ->where('type', 'expense')
                            ->whereMonth('transaction_date', $i)
                            ->sum('amount'),
                    ];
                }

                return response()->json([
                    'cards' => [
                        'honey_price' => [
                            'value' => $latestPrice,
                        ],
                        'submission_total' => [
                            'value' => $submissionTotal,
                            'percentage_change' => $percent($submissionCurrent, $submissionPrev)
                        ],
                        'withdraw_total' => [
                            'value' => $withdrawTotal,
                            'percentage_change' => $percent($withdrawCurrent, $withdrawPrev)
                        ],
                        'available_balance' => [
                            'value' => $available,
                        ],
                    ],
                    'chart' => $chart,
                    'latest_transactions' => $latestTransactions
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
