<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_create_minimum_test_data(): void
    {
        $this->seed();

        $this->assertGreaterThanOrEqual(2, Account::count());
        $this->assertGreaterThanOrEqual(2, Transaction::count());
    }
}
