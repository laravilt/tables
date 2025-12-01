<?php

namespace Laravilt\Tables\Columns;

use Closure;
use Laravilt\Support\Component;

abstract class Column extends Component
{
    protected bool $sortable = false;

    protected bool $searchable = false;

    protected ?Closure $formatUsing = null;

    protected bool $toggleable = true;

    protected string|Closure|null $description = null;

    protected string $descriptionPosition = 'below';

    public function sortable(bool $condition = true): static
    {
        $this->sortable = $condition;

        return $this;
    }

    public function searchable(bool $condition = true): static
    {
        $this->searchable = $condition;

        return $this;
    }

    public function formatUsing(?Closure $callback): static
    {
        $this->formatUsing = $callback;

        return $this;
    }

    public function toggleable(bool $condition = true, bool $isToggledHiddenByDefault = false): static
    {
        $this->toggleable = $condition;

        if ($isToggledHiddenByDefault) {
            $this->visible = false;
        }

        return $this;
    }

    public function description(string|Closure|null $description, string $position = 'below'): static
    {
        $this->description = $description;
        $this->descriptionPosition = $position;

        return $this;
    }

    // visible() method is inherited from Component class with Closure|bool support

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    /**
     * Evaluate icon for this column (can be overridden by subclasses)
     */
    public function evaluateIcon(mixed $state, mixed $record = null): ?string
    {
        return null;
    }

    /**
     * Evaluate color for this column (can be overridden by subclasses)
     */
    public function evaluateColor(mixed $state, mixed $record = null): ?string
    {
        return null;
    }

    /**
     * Evaluate size for this column (can be overridden by subclasses)
     */
    public function evaluateSize(mixed $state, mixed $record = null): ?string
    {
        return null;
    }

    /**
     * Evaluate description for this column
     */
    public function evaluateDescription(mixed $record = null): ?string
    {
        if ($this->description instanceof Closure) {
            return ($this->description)($record);
        }

        return $this->description;
    }

    public function toInertiaProps(): array
    {
        $props = array_merge(
            $this->toLaraviltProps(),
            $this->getVueProps()
        );

        // Override component type with the Vue component name if defined
        if (method_exists($this, 'getVueComponent')) {
            $props['component'] = $this->getVueComponent();
        }

        return $props;
    }

    /**
     * Get the Vue component name (should be overridden by subclasses)
     */
    protected function getVueComponent(): string
    {
        return class_basename(static::class);
    }

    protected function getVueProps(): array
    {
        return [
            'sortable' => $this->sortable,
            'searchable' => $this->searchable,
            'toggleable' => $this->toggleable,
            'descriptionPosition' => $this->descriptionPosition,
        ];
    }

    protected function getFlutterWidgetProps(): array
    {
        return [
            'sortable' => $this->sortable,
            'searchable' => $this->searchable,
            'toggleable' => $this->toggleable,
            'descriptionPosition' => $this->descriptionPosition,
        ];
    }
}
