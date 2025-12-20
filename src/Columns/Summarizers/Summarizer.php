<?php

declare(strict_types=1);

namespace Laravilt\Tables\Columns\Summarizers;

use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

abstract class Summarizer
{
    protected ?string $label = null;

    protected ?Closure $using = null;

    protected ?string $column = null;

    protected ?int $precision = null;

    protected bool $money = false;

    protected ?string $currency = null;

    final public function __construct()
    {
        //
    }

    public static function make(): static
    {
        return new static;
    }

    /**
     * Set the label for the summarizer.
     */
    public function label(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Set a custom callback for calculating the summary.
     */
    public function using(?Closure $callback): static
    {
        $this->using = $callback;

        return $this;
    }

    /**
     * Set the column to summarize.
     */
    public function column(string $column): static
    {
        $this->column = $column;

        return $this;
    }

    /**
     * Set the decimal precision.
     */
    public function precision(int $precision): static
    {
        $this->precision = $precision;

        return $this;
    }

    /**
     * Format the result as money.
     */
    public function money(bool $condition = true, ?string $currency = null): static
    {
        $this->money = $condition;
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get the label.
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * Get the column.
     */
    public function getColumn(): ?string
    {
        return $this->column;
    }

    /**
     * Get the precision.
     */
    public function getPrecision(): ?int
    {
        return $this->precision;
    }

    /**
     * Check if formatting as money.
     */
    public function isMoney(): bool
    {
        return $this->money;
    }

    /**
     * Get the currency.
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * Calculate the summary value.
     *
     * @param  Builder|Collection  $query
     */
    abstract public function summarize($query, string $column): mixed;

    /**
     * Format the result value.
     */
    protected function formatResult(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($this->precision !== null) {
            $value = round((float) $value, $this->precision);
        }

        if ($this->money) {
            // Simple money formatting - can be enhanced with currency formatters
            $currency = $this->currency ?? 'USD';

            return number_format((float) $value, 2).' '.$currency;
        }

        return $value;
    }

    /**
     * Execute the summarizer.
     *
     * @param  Builder|Collection  $query
     */
    public function execute($query, string $column): mixed
    {
        if ($this->using) {
            $value = call_user_func($this->using, $query, $column);

            return $this->formatResult($value);
        }

        $columnToUse = $this->column ?? $column;

        return $this->formatResult($this->summarize($query, $columnToUse));
    }

    /**
     * Convert to array for frontend.
     */
    public function toArray(): array
    {
        return [
            'type' => class_basename(static::class),
            'label' => $this->getLabel(),
            'column' => $this->getColumn(),
            'precision' => $this->getPrecision(),
            'isMoney' => $this->isMoney(),
            'currency' => $this->getCurrency(),
        ];
    }
}
