<?php

use App\Models\Transaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->boolean('is_posted')->default(false)->after('description');
        });

        Transaction::query()
            ->with('journalEntries')
            ->each(function (Transaction $transaction): void {
                $transaction
                    ->forceFill(['is_posted' => $transaction->shouldBePosted()])
                    ->saveQuietly();
            });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('is_posted');
        });
    }
};
