<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\Services\TrialBalanceService;
use Illuminate\Support\Carbon;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Laravel\Pages\Page;
use MoonShine\Support\Enums\FormMethod;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Components\Heading;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\UI\Fields\DateRange;
use MoonShine\UI\Fields\Text;

class TrialBalancePage extends Page
{
    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            '#' => $this->getTitle(),
        ];
    }

    public function getTitle(): string
    {
        return 'Оборотно-сальдовая ведомость';
    }

    /**
     * @return list<ComponentContract>
     */
    protected function components(): iterable
    {
        $period = $this->resolvePeriod();
        $rows = app(TrialBalanceService::class)->build(
            $period['from'],
            $period['to'],
        );

        return [
            Box::make('Параметры', [
                $this->buildFilterForm($period),
            ]),
            Box::make('Отчёт', [
                Heading::make(
                    sprintf(
                        'Период: %s — %s',
                        Carbon::parse($period['from'])->format('d.m.Y'),
                        Carbon::parse($period['to'])->format('d.m.Y'),
                    ),
                )->class('mb-4'),
                $this->buildReportTable($rows),
            ]),
        ];
    }

    /**
     * @param  array{from: string, to: string}  $period
     */
    private function buildFilterForm(array $period): FormBuilder
    {
        return FormBuilder::make($this->getUrl(), FormMethod::GET)
            ->name('trial-balance-filter')
            ->fields([
                DateRange::make('Период', 'period')->required(),
            ])
            ->fill([
                'period' => [
                    'from' => $period['from'],
                    'to' => $period['to'],
                ],
            ])
            ->submit('Сформировать', [
                'class' => 'btn-primary',
            ]);
    }

    /**
     * @param  list<array<string, string>>  $rows
     */
    private function buildReportTable(array $rows): TableBuilder
    {
        return TableBuilder::make(
            fields: [
                Text::make('Код', 'code'),
                Text::make('Счёт', 'name'),
                Text::make('Сальдо нач. Дт', 'opening_debit'),
                Text::make('Сальдо нач. Кт', 'opening_credit'),
                Text::make('Оборот Дт', 'turnover_debit'),
                Text::make('Оборот Кт', 'turnover_credit'),
                Text::make('Сальдо кон. Дт', 'closing_debit'),
                Text::make('Сальдо кон. Кт', 'closing_credit'),
            ],
            items: $rows,
        )
            ->simple()
            ->trAttributes(
                static function (?DataWrapperContract $data): array {
                    if ($data === null) {
                        return [];
                    }

                    $original = $data->getOriginal();

                    if (is_array($original) && ($original['is_total'] ?? '0') === '1') {
                        return ['class' => 'font-semibold'];
                    }

                    return [];
                },
            )
            ->withNotFound();
    }

    /**
     * @return array{from: string, to: string}
     */
    private function resolvePeriod(): array
    {
        /** @var array{from?: string, to?: string}|null $period */
        $period = request()->input('period');

        $from = $this->parseDate(data_get($period, 'from'));
        $to = $this->parseDate(data_get($period, 'to'));

        if ($from === null || $to === null) {
            return [
                'from' => now()->startOfMonth()->toDateString(),
                'to' => now()->toDateString(),
            ];
        }

        if (Carbon::parse($from)->greaterThan(Carbon::parse($to))) {
            return [
                'from' => $to,
                'to' => $from,
            ];
        }

        return [
            'from' => $from,
            'to' => $to,
        ];
    }

    private function parseDate(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        if (! preg_match("/^\d{4}-\d{2}-\d{2}$/", $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
