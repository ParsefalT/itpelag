<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\JournalEntrie;

use App\Models\JournalEntry;
use App\MoonShine\Resources\BaseResource;
use App\MoonShine\Resources\JournalEntrie\Pages\JournalEntrieDetailPage;
use App\MoonShine\Resources\JournalEntrie\Pages\JournalEntrieFormPage;
use App\MoonShine\Resources\JournalEntrie\Pages\JournalEntrieIndexPage;
use App\Services\TransactionEntryValidator;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Core\Exceptions\ResourceException;
use MoonShine\Support\Enums\Ability;
use MoonShine\Support\Enums\PageType;
use MoonShine\Support\Enums\SortDirection;

/**
 * @extends ModelResource<JournalEntry, JournalEntrieIndexPage, JournalEntrieFormPage, JournalEntrieDetailPage>
 */
class JournalEntrieResource extends BaseResource
{
    protected string $model = JournalEntry::class;

    protected string $title = 'JournalEntries';
    protected string $sortColumn = 'id';
    protected SortDirection $sortDirection = SortDirection::ASC;
    protected ?PageType $redirectAfterSave = PageType::INDEX;

    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            JournalEntrieIndexPage::class,
            JournalEntrieFormPage::class,
            JournalEntrieDetailPage::class,
        ];
    }

    protected function isCan(Ability $ability): bool
    {
        if (! parent::isCan($ability)) {
            return false;
        }

        if (in_array($ability, [Ability::CREATE, Ability::UPDATE, Ability::DELETE], true)) {
            return false;
        }

        if ($ability !== Ability::VIEW) {
            return true;
        }

        return true;
    }

    protected function beforeCreating(DataWrapperContract $item): DataWrapperContract
    {
        $this->ensureParentMutable($item);

        return parent::beforeCreating($item);
    }

    protected function beforeUpdating(DataWrapperContract $item): DataWrapperContract
    {
        $this->ensureParentMutable($item);

        return parent::beforeUpdating($item);
    }

    protected function afterCreated(DataWrapperContract $item): DataWrapperContract
    {
        $item = parent::afterCreated($item);
        $this->assertBalancedAfterSave($item, rollbackOnFailure: true);

        return $item;
    }

    protected function afterUpdated(DataWrapperContract $item): DataWrapperContract
    {
        $item = parent::afterUpdated($item);
        $this->assertBalancedAfterSave($item, rollbackOnFailure: false);

        return $item;
    }

    protected function beforeDeleting(DataWrapperContract $item): DataWrapperContract
    {
        $this->ensureParentMutable($item);

        return parent::beforeDeleting($item);
    }

    private function ensureParentMutable(DataWrapperContract $item): void
    {
        $entry = $item->getOriginal();

        if ($entry instanceof JournalEntry && $this->isParentPosted($entry)) {
            throw new ResourceException('Нельзя изменять проводки проведённой транзакции.');
        }
    }

    private function isParentPosted(JournalEntry $entry): bool
    {
        $entry->loadMissing('transaction');

        return $entry->transaction?->isPosted() ?? false;
    }

    private function assertBalancedAfterSave(DataWrapperContract $item, bool $rollbackOnFailure): void
    {
        $entry = $item->getOriginal();

        if (! $entry instanceof JournalEntry || ! $entry->transaction_id) {
            return;
        }

        $transaction = $entry->transaction()->first();

        if ($transaction === null || $transaction->isPosted()) {
            return;
        }

        try {
            app(TransactionEntryValidator::class)->assertBalancedWhenComplete($transaction);
        } catch (\InvalidArgumentException $exception) {
            if ($rollbackOnFailure && $entry->exists) {
                $entry->delete();
            }

            throw new ResourceException($exception->getMessage());
        }
    }
}
