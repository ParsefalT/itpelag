<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            [
                "code" => "128",
                "name" => "Блинчики",
                "type" => "asset",
                "is_active" => true,
            ],
            [
                "code" => "200",
                "name" => "Кекс",
                "type" => "revenue",
                "is_active" => true,
            ],
            [
                "code" => "135",
                "name" => "Милка",
                "type" => "expense",
                "is_active" => true,
            ],
        ];

        foreach ($accounts as $account) {
            Account::query()->updateOrCreate(
                ["code" => $account["code"]],
                $account,
            );
        }
    }
}
