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

    protected bool $isToggledHiddenByDefault = false;

    protected string|Closure|null $description = null;

    protected string $descriptionPosition = 'below';

    protected string $alignment = 'start';

    protected string|Closure|null $tooltip = null;

    protected string|Closure|null $url = null;

    protected bool $openUrlInNewTab = false;

    protected ?string $prefix = null;

    protected ?string $suffix = null;

    protected bool $grow = false;

    protected ?string $size = null;

    public function alignment(string $alignment): static
    {
        $this->alignment = $alignment;

        return $this;
    }

    public function alignStart(): static
    {
        return $this->alignment('start');
    }

    public function alignCenter(): static
    {
        return $this->alignment('center');
    }

    public function alignEnd(): static
    {
        return $this->alignment('end');
    }

    public function alignJustify(): static
    {
        return $this->alignment('justify');
    }

    public function tooltip(string|Closure|null $tooltip): static
    {
        $this->tooltip = $tooltip;

        return $this;
    }

    public function url(string|Closure|null $url, bool $openInNewTab = false): static
    {
        $this->url = $url;
        $this->openUrlInNewTab = $openInNewTab;

        return $this;
    }

    public function openUrlInNewTab(bool $condition = true): static
    {
        $this->openUrlInNewTab = $condition;

        return $this;
    }

    public function prefix(?string $prefix): static
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function suffix(?string $suffix): static
    {
        $this->suffix = $suffix;

        return $this;
    }

    public function grow(bool $condition = true): static
    {
        $this->grow = $condition;

        return $this;
    }

    public function size(?string $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function evaluateTooltip(mixed $state, mixed $record = null): ?string
    {
        if ($this->tooltip instanceof Closure) {
            return ($this->tooltip)($state, $record);
        }

        return $this->tooltip;
    }

    public function evaluateUrl(mixed $state, mixed $record = null): ?string
    {
        if ($this->url instanceof Closure) {
            return ($this->url)($state, $record);
        }

        return $this->url;
    }

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

    /**
     * Alias for formatUsing to match FilamentPHP API
     */
    public function formatStateUsing(?Closure $callback): static
    {
        return $this->formatUsing($callback);
    }

    /**
     * Get the format callback
     */
    public function getFormatUsing(): ?Closure
    {
        return $this->formatUsing;
    }

    /**
     * Evaluate the format callback on a given state
     */
    public function evaluateFormatUsing(mixed $state, mixed $record = null): mixed
    {
        if ($this->formatUsing) {
            return ($this->formatUsing)($state, $record);
        }

        return $state;
    }

    /**
     * Check if the column has a format callback
     */
    public function hasFormatUsing(): bool
    {
        return $this->formatUsing !== null;
    }

    public function toggleable(bool $condition = true, bool $isToggledHiddenByDefault = false): static
    {
        $this->toggleable = $condition;
        $this->isToggledHiddenByDefault = $isToggledHiddenByDefault;

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
            'isToggledHiddenByDefault' => $this->isToggledHiddenByDefault,
            'descriptionPosition' => $this->descriptionPosition,
            'alignment' => $this->alignment,
            'hasTooltip' => $this->tooltip !== null,
            'hasUrl' => $this->url !== null,
            'openUrlInNewTab' => $this->openUrlInNewTab,
            'prefix' => $this->prefix,
            'suffix' => $this->suffix,
            'grow' => $this->grow,
            'size' => $this->size,
            'hasFormatUsing' => $this->formatUsing !== null,
        ];
    }

    protected function getFlutterWidgetProps(): array
    {
        return [
            'sortable' => $this->sortable,
            'searchable' => $this->searchable,
            'toggleable' => $this->toggleable,
            'descriptionPosition' => $this->descriptionPosition,
            'alignment' => $this->alignment,
            'hasTooltip' => $this->tooltip !== null,
            'hasUrl' => $this->url !== null,
            'openUrlInNewTab' => $this->openUrlInNewTab,
            'prefix' => $this->prefix,
            'suffix' => $this->suffix,
            'grow' => $this->grow,
            'size' => $this->size,
        ];
    }
}
