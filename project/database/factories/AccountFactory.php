<?php

namespace Database\Factories;

use App\Models\Account;
use App\TypeAccountEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Account> */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'code' => (string) fake()->unique()->numberBetween(100, 999),
            'type' => fake()->randomElement(TypeAccountEnum::cases())->value,
            'is_active' => true,
        ];
    }
}
