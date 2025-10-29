<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Submission;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class SubmissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $members = User::where('role', 'member')->get();

        foreach ($members as $member) {
            for ($i = 1; $i <= 10; $i++) {
                $totalHoney = rand(1, 3);
                $amount = $totalHoney * 120000;

                Submission::updateOrCreate(
                    [
                        'member_id' => $member->id,
                        'submission_date' => Carbon::now()->subDays(rand(1, 30)),
                    ],
                    [
                        'id' => (string) Str::uuid(),
                        'total_honey' => $totalHoney,
                        'amount' => $amount,
                        'evidence' => 'https://res.cloudinary.com/dpmujiyre/image/upload/v1761745643/Madu_d7ulxz.png',
                        'public_id' => 'Madu_d7ulxz',
                    ]
                );
            }
        }
    }
}
