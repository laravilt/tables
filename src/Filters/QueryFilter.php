<?php

namespace Laravilt\Tables\Filters;

use Laravilt\Forms\Components\Toggle;

/**
 * A simple toggle-based filter that applies a custom query when enabled.
 * Use this for boolean/toggle filters like "verified", "active", etc.
 */
class QueryFilter extends Filter
{
    public static function make(string $name): static
    {
        return parent::make($name);
    }

    /**
     * Apply the filter to the query.
     */
    public function apply($query, mixed $value)
    {
        // Only apply the filter if the toggle is enabled (true)
        if (! $value || $value === 'false' || $value === '0') {
            return $query;
        }

        // Use the custom query callback if defined
        if ($this->query) {
            return ($this->query)($query, $value);
        }

        return $query;
    }

    protected function getVueComponent(): string
    {
        return 'QueryFilter';
    }

    protected function getVueProps(): array
    {
        // Build a toggle form component
        $toggle = Toggle::make($this->getName())
            ->label($this->getLabel() ?? $this->getName());

        return [
            'default' => $this->default,
            'formField' => $toggle->toLaraviltProps(),
        ];
    }

    protected function getFlutterWidget(): string
    {
        return 'LaraviltQueryFilter';
    }
}
