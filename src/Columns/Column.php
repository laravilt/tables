<?php

namespace Laravilt\Tables\Columns;

use Closure;
use Laravilt\Support\Component;

abstract class Column extends Component
{
    protected bool $sortable = false;

    protected bool $searchable = false;

    protected bool $isSearchableIndividually = false;

    protected bool $isSearchableGlobally = true;

    /** @var array<int, string>|null */
    protected ?array $searchableColumns = null;

    protected ?Closure $formatUsing = null;

    protected ?Closure $getStateUsing = null;

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

    protected int|string|null $width = null;

    public function width(int|string|null $width): static
    {
        $this->width = $width;

        return $this;
    }

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

    /**
     * Set whether the column is searchable.
     *
     * @param  bool|array<int, string>  $condition  True to enable, false to disable, or an array of column names to search
     * @param  bool  $isIndividual  Whether to allow individual column search (per-column filter)
     * @param  bool  $isGlobal  Whether to include in global search
     */
    public function searchable(bool|array $condition = true, bool $isIndividual = false, bool $isGlobal = true): static
    {
        if (is_array($condition)) {
            $this->searchable = true;
            $this->searchableColumns = $condition;
        } else {
            $this->searchable = $condition;
        }

        $this->isSearchableIndividually = $isIndividual;
        $this->isSearchableGlobally = $isGlobal;

        return $this;
    }

    /**
     * Check if this column has individual search enabled.
     */
    public function isSearchableIndividually(): bool
    {
        return $this->isSearchableIndividually;
    }

    /**
     * Check if this column is included in global search.
     */
    public function isSearchableGlobally(): bool
    {
        return $this->isSearchableGlobally;
    }

    /**
     * Get the columns to search when searching this column.
     *
     * @return array<int, string>|null
     */
    public function getSearchableColumns(): ?array
    {
        return $this->searchableColumns;
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
     * Set a custom callback to retrieve the state/value for this column.
     * This is called before formatUsing to get the raw value.
     */
    public function getStateUsing(?Closure $callback): static
    {
        $this->getStateUsing = $callback;

        return $this;
    }

    /**
     * Get the getStateUsing callback
     */
    public function getGetStateUsing(): ?Closure
    {
        return $this->getStateUsing;
    }

    /**
     * Evaluate the getStateUsing callback to get the raw state value
     */
    public function evaluateGetStateUsing(mixed $record): mixed
    {
        if ($this->getStateUsing) {
            return ($this->getStateUsing)($record);
        }

        return null;
    }

    /**
     * Check if the column has a custom state getter
     */
    public function hasGetStateUsing(): bool
    {
        return $this->getStateUsing !== null;
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
            'isSearchableIndividually' => $this->isSearchableIndividually,
            'isSearchableGlobally' => $this->isSearchableGlobally,
            'searchableColumns' => $this->searchableColumns,
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
            'width' => $this->width,
            'hasFormatUsing' => $this->formatUsing !== null,
            'hasGetStateUsing' => $this->getStateUsing !== null,
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
