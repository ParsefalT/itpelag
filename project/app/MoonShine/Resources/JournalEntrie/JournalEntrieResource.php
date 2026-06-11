<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\JournalEntrie;

use Illuminate\Database\Eloquent\Model;
use App\Models\JournalEntry;
use App\MoonShine\Resources\JournalEntrie\Pages\JournalEntrieIndexPage;
use App\MoonShine\Resources\JournalEntrie\Pages\JournalEntrieFormPage;
use App\MoonShine\Resources\JournalEntrie\Pages\JournalEntrieDetailPage;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Support\Enums\PageType;
use MoonShine\Support\Enums\SortDirection;

/**
 * @extends ModelResource<JournalEntry, JournalEntrieIndexPage, JournalEntrieFormPage, JournalEntrieDetailPage>
 */
class JournalEntrieResource extends ModelResource
{
    protected string $model = JournalEntry::class;

    protected string $title = "JournalEntries";
    protected string $sortColumn = "id";
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
}
