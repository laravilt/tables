<?php

namespace Laravilt\Tables\Filters;

use Laravilt\Forms\Components\Field;

class BaseFilter extends Filter
{
    /**
     * Create a filter with a custom schema field.
     *
     * Example:
     * Filter::make('status')
     *     ->schema(Select::make('status')->label('Status')->options([...]))
     *     ->query(fn($query, $value) => $query->where('status', $value))
     */
    public static function make(?string $name = null): static
    {
        $filter = new static($name);

        return $filter;
    }

    protected function getVueComponent(): string
    {
        return 'BaseFilter';
    }

    protected function getFlutterWidget(): string
    {
        return 'LaraviltBaseFilter';
    }
}
