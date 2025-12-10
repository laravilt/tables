<?php

namespace Laravilt\Tables\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Laravilt\Forms\Components\Select;

/**
 * TrashedFilter for handling soft deleted records.
 *
 * Provides three states:
 * - All records (including trashed)
 * - Only active records (without trashed) - default
 * - Only trashed records
 */
class TrashedFilter extends Filter
{
    protected bool $native = true;

    public static function make(?string $name = null): static
    {
        $filter = parent::make($name ?? 'trashed');

        return $filter
            ->label(__('tables::tables.filters.trashed.label'))
            ->default('without');
    }

    /**
     * Apply the filter to the query.
     */
    public function apply($query, mixed $value)
    {
        // Handle null or empty value as "without trashed" (default)
        if ($value === null || $value === '') {
            $value = 'without';
        }

        return match ($value) {
            'with' => $query->withTrashed(),
            'only' => $query->onlyTrashed(),
            'without' => $query->withoutTrashed(),
            default => $query,
        };
    }

    /**
     * Modify the base query before other filters are applied.
     * This ensures soft deleted records can be included.
     */
    public function modifyBaseQuery(Builder $query, mixed $value): Builder
    {
        // Remove the SoftDeletingScope temporarily so we can apply our own logic
        // This is needed because Laravel applies the scope by default
        if ($value === 'with' || $value === 'only') {
            return $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        return $query;
    }

    /**
     * Check if this filter should modify the base query.
     */
    public function shouldModifyBaseQuery(): bool
    {
        return true;
    }

    /**
     * Get the current trashed state value.
     */
    public function getTrashedState(mixed $value): string
    {
        return $value ?? 'without';
    }

    protected function getVueComponent(): string
    {
        return 'TrashedFilter';
    }

    protected function getVueProps(): array
    {
        // Build the Select form component with three options
        $select = Select::make($this->getName())
            ->label($this->getLabel())
            ->options([
                'without' => __('tables::tables.filters.trashed.without'),
                'with' => __('tables::tables.filters.trashed.with'),
                'only' => __('tables::tables.filters.trashed.only'),
            ])
            ->default('without');

        return [
            'default' => $this->default ?? 'without',
            'formField' => $select->toLaraviltProps(),
            'isTrashedFilter' => true,
        ];
    }

    protected function getFlutterWidget(): string
    {
        return 'LaraviltTrashedFilter';
    }

    protected function getFlutterWidgetProps(): array
    {
        return [
            ...parent::getFlutterWidgetProps(),
            'options' => [
                ['value' => 'without', 'label' => __('tables::tables.filters.trashed.without')],
                ['value' => 'with', 'label' => __('tables::tables.filters.trashed.with')],
                ['value' => 'only', 'label' => __('tables::tables.filters.trashed.only')],
            ],
        ];
    }
}
