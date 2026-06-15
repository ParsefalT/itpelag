<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\User;
use App\TypeEntryEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'api@example.com',
            'password' => 'password',
        ]);
    }

    public function test_store_creates_transaction_via_api(): void
    {
        $debitAccount = Account::factory()->create();
        $creditAccount = Account::factory()->create();

        $response = $this->withBasicAuth($this->user->email, 'password')
            ->postJson('/api/v1/transactions', [
                'date' => '2026-06-11',
                'description' => 'API transaction',
                'entries' => [
                    [
                        'account_id' => $debitAccount->id,
                        'amount' => 200,
                        'type' => TypeEntryEnum::DEBIT->value,
                    ],
                    [
                        'account_id' => $creditAccount->id,
                        'amount' => 200,
                        'type' => TypeEntryEnum::CREDIT->value,
                    ],
                ],
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.description', 'API transaction')
            ->assertJsonPath('data.is_posted', true)
            ->assertJsonCount(2, 'data.journal_entries');

        $this->assertDatabaseHas('transactions', [
            'description' => 'API transaction',
            'is_posted' => true,
        ]);
    }

    public function test_store_rejects_unbalanced_entries(): void
    {
        $debitAccount = Account::factory()->create();
        $creditAccount = Account::factory()->create();

        $response = $this->withBasicAuth($this->user->email, 'password')
            ->postJson('/api/v1/transactions', [
                'date' => '2026-06-11',
                'description' => 'Broken transaction',
                'entries' => [
                    [
                        'account_id' => $debitAccount->id,
                        'amount' => 100,
                        'type' => TypeEntryEnum::DEBIT->value,
                    ],
                    [
                        'account_id' => $creditAccount->id,
                        'amount' => 90,
                        'type' => TypeEntryEnum::CREDIT->value,
                    ],
                ],
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonStructure(['error']);

        $this->assertDatabaseMissing('transactions', [
            'description' => 'Broken transaction',
        ]);
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/transactions', [
            'date' => '2026-06-11',
            'description' => 'No auth',
            'entries' => [],
        ]);

        $response->assertUnauthorized();
    }

    public function test_store_validates_request_payload(): void
    {
        $response = $this->withBasicAuth($this->user->email, 'password')
            ->postJson('/api/v1/transactions', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date', 'description', 'entries']);
    }

    public function test_index_lists_transactions(): void
    {
        $debitAccount = Account::factory()->create();
        $creditAccount = Account::factory()->create();

        $this->withBasicAuth($this->user->email, 'password')
            ->postJson('/api/v1/transactions', [
                'date' => '2026-06-11',
                'description' => 'Listed transaction',
                'entries' => [
                    [
                        'account_id' => $debitAccount->id,
                        'amount' => 100,
                        'type' => TypeEntryEnum::DEBIT->value,
                    ],
                    [
                        'account_id' => $creditAccount->id,
                        'amount' => 100,
                        'type' => TypeEntryEnum::CREDIT->value,
                    ],
                ],
            ])
            ->assertCreated();

        $response = $this->withBasicAuth($this->user->email, 'password')
            ->getJson('/api/v1/transactions');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.description', 'Listed transaction');
    }

    public function test_show_returns_single_transaction(): void
    {
        $debitAccount = Account::factory()->create();
        $creditAccount = Account::factory()->create();

        $transactionId = $this->withBasicAuth($this->user->email, 'password')
            ->postJson('/api/v1/transactions', [
                'date' => '2026-06-11',
                'description' => 'Show me',
                'entries' => [
                    [
                        'account_id' => $debitAccount->id,
                        'amount' => 100,
                        'type' => TypeEntryEnum::DEBIT->value,
                    ],
                    [
                        'account_id' => $creditAccount->id,
                        'amount' => 100,
                        'type' => TypeEntryEnum::CREDIT->value,
                    ],
                ],
            ])
            ->json('data.id');

        $response = $this->withBasicAuth($this->user->email, 'password')
            ->getJson("/api/v1/transactions/{$transactionId}");

        $response
            ->assertOk()
            ->assertJsonPath('data.description', 'Show me')
            ->assertJsonCount(2, 'data.journal_entries');
    }

    public function test_update_rejects_posted_transaction(): void
    {
        $debitAccount = Account::factory()->create();
        $creditAccount = Account::factory()->create();

        $transactionId = $this->withBasicAuth($this->user->email, 'password')
            ->postJson('/api/v1/transactions', [
                'date' => '2026-06-11',
                'description' => 'Before update',
                'entries' => [
                    [
                        'account_id' => $debitAccount->id,
                        'amount' => 100,
                        'type' => TypeEntryEnum::DEBIT->value,
                    ],
                    [
                        'account_id' => $creditAccount->id,
                        'amount' => 100,
                        'type' => TypeEntryEnum::CREDIT->value,
                    ],
                ],
            ])
            ->json('data.id');

        $response = $this->withBasicAuth($this->user->email, 'password')
            ->putJson("/api/v1/transactions/{$transactionId}", [
                'date' => '2026-06-12',
                'description' => 'After update',
                'entries' => [
                    [
                        'account_id' => $debitAccount->id,
                        'amount' => 150,
                        'type' => TypeEntryEnum::DEBIT->value,
                    ],
                    [
                        'account_id' => $creditAccount->id,
                        'amount' => 150,
                        'type' => TypeEntryEnum::CREDIT->value,
                    ],
                ],
            ]);

        $response->assertUnprocessable();
    }

    public function test_delete_rejects_posted_transaction(): void
    {
        $debitAccount = Account::factory()->create();
        $creditAccount = Account::factory()->create();

        $transactionId = $this->withBasicAuth($this->user->email, 'password')
            ->postJson('/api/v1/transactions', [
                'date' => '2026-06-11',
                'description' => 'Delete me',
                'entries' => [
                    [
                        'account_id' => $debitAccount->id,
                        'amount' => 100,
                        'type' => TypeEntryEnum::DEBIT->value,
                    ],
                    [
                        'account_id' => $creditAccount->id,
                        'amount' => 100,
                        'type' => TypeEntryEnum::CREDIT->value,
                    ],
                ],
            ])
            ->json('data.id');

        $this->withBasicAuth($this->user->email, 'password')
            ->deleteJson("/api/v1/transactions/{$transactionId}")
            ->assertUnprocessable();

        $this->assertDatabaseHas('transactions', ['id' => $transactionId]);
    }
}
