<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Transaction\Pages;

use App\Models\Transaction;
use App\MoonShine\Resources\Account\AccountResource;
use App\MoonShine\Resources\JournalEntrie\JournalEntrieResource;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use App\MoonShine\Resources\Transaction\TransactionResource;
use App\TypeEntryEnum;
use Illuminate\Validation\Rule;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Fields\Relationships\RelationRepeater;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;

/**
 * @extends FormPage<TransactionResource>
 */
class TransactionFormPage extends FormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    public function fields(): array
    {
        return [
            ID::make()->sortable(),
            Date::make("Дата", "date")->required(),
            Text::make("Описание", "description")->required(),
            RelationRepeater::make(
                "Проводки",
                "journalEntries",
                resource: JournalEntrieResource::class,
            )
                ->fields([
                    ID::make(),
                    Number::make("Сумма", "amount")->min(0.01)->step(0.01)->required(),
                    Enum::make("Тип", "type")->attach(TypeEntryEnum::class)->required(),
                    BelongsTo::make(
                        "Счёт",
                        "account",
                        formatted: static fn ($model) => $model->name,
                        resource: AccountResource::class,
                    )->required(),
                ])
                ->creatable(fn (): bool => ! $this->isCurrentTransactionPosted())
                ->removable(fn (): bool => ! $this->isCurrentTransactionPosted()),
        ];
    }

    private function isCurrentTransactionPosted(): bool
    {
        $transaction = $this->getResource()->getItem()?->getOriginal();

        return $transaction instanceof Transaction && $transaction->isPosted();
    }

    protected function buttons(): ListOf
    {
        return parent::buttons();
    }

    protected function formButtons(): ListOf
    {
        return parent::formButtons();
    }

    public function filters(): array
    {
        return [];
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [
            "date" => "required|date",
            "description" => "required|string|max:255",
            "journalEntries" => ["required", "array", "min:2"],
            "journalEntries.*.amount" => ["required", "numeric", "gt:0"],
            "journalEntries.*.type" => ["required", Rule::enum(TypeEntryEnum::class)],
            "journalEntries.*.account_id" => ["required", "integer", "exists:accounts,id"],
        ];
    }

    protected function modifyFormComponent(
        FormBuilderContract $component,
    ): FormBuilderContract {
        return $component;
    }

    protected function topLayer(): array
    {
        return [...parent::topLayer()];
    }

    protected function mainLayer(): array
    {
        return [...parent::mainLayer()];
    }

    protected function bottomLayer(): array
    {
        return [...parent::bottomLayer()];
    }
}
