<?php

namespace App\Providers;

use App\Models\JournalEntry;
use App\Observers\JournalEntryObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JournalEntry::observe(JournalEntryObserver::class);
    }
}
