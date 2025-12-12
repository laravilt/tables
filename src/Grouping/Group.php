<?php

namespace Laravilt\Tables\Grouping;

use Closure;
use Laravilt\Support\Contracts\InertiaSerializable;

class Group implements InertiaSerializable
{
    protected string $column;

    protected ?string $label = null;

    protected bool $collapsible = true;

    protected ?Closure $getTitleFromRecordUsing = null;

    protected ?Closure $getDescriptionFromRecordUsing = null;

    protected ?string $titleAttribute = null;

    protected ?string $descriptionAttribute = null;

    protected bool $orderQueryUsing = true;

    public function __construct(string $column)
    {
        $this->column = $column;
    }

    public static function make(string $column): static
    {
        return new static($column);
    }

    public function label(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function collapsible(bool $condition = true): static
    {
        $this->collapsible = $condition;

        return $this;
    }

    public function getTitleFromRecordUsing(?Closure $callback): static
    {
        $this->getTitleFromRecordUsing = $callback;

        return $this;
    }

    public function getDescriptionFromRecordUsing(?Closure $callback): static
    {
        $this->getDescriptionFromRecordUsing = $callback;

        return $this;
    }

    public function titleAttribute(?string $attribute): static
    {
        $this->titleAttribute = $attribute;

        return $this;
    }

    public function descriptionAttribute(?string $attribute): static
    {
        $this->descriptionAttribute = $attribute;

        return $this;
    }

    public function orderQueryUsing(bool $condition = true): static
    {
        $this->orderQueryUsing = $condition;

        return $this;
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function isCollapsible(): bool
    {
        return $this->collapsible;
    }

    public function shouldOrderQuery(): bool
    {
        return $this->orderQueryUsing;
    }

    /**
     * Get the title for a group based on its record value.
     */
    public function getTitleForRecord(mixed $record, mixed $value): string
    {
        if ($this->getTitleFromRecordUsing) {
            return ($this->getTitleFromRecordUsing)($record, $value);
        }

        if ($this->titleAttribute && is_object($record)) {
            return $record->{$this->titleAttribute} ?? (string) $value;
        }

        return (string) $value;
    }

    /**
     * Get the description for a group based on its record value.
     */
    public function getDescriptionForRecord(mixed $record, mixed $value): ?string
    {
        if ($this->getDescriptionFromRecordUsing) {
            return ($this->getDescriptionFromRecordUsing)($record, $value);
        }

        if ($this->descriptionAttribute && is_object($record)) {
            return $record->{$this->descriptionAttribute} ?? null;
        }

        return null;
    }

    public function toInertiaProps(): array
    {
        $label = $this->label ?? ucfirst(str_replace('_', ' ', $this->column));

        return [
            'column' => $this->column,
            'label' => __($label), // Translate label
            'collapsible' => $this->collapsible,
            'titleAttribute' => $this->titleAttribute,
            'descriptionAttribute' => $this->descriptionAttribute,
        ];
    }
}
