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
        Account::create([
            "name" => "Блинчики",
            "code" => "128",
            "type" => "asset",
            "is_active" => true,
        ]);

        Account::create([
            "name" => "Кекс",
            "code" => "200",
            "type" => "revenue",
            "is_active" => true,
        ]);

        Account::create([
            "name" => "Милка",
            "code" => "135",
            "type" => "expense",
            "is_active" => true,
        ]);
    }
}
