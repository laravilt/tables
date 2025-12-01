<?php

namespace Laravilt\Tables\Filters;

use Closure;
use Laravilt\Forms\Components\Select;

class TernaryFilter extends Filter
{
    protected ?string $trueLabel = 'Yes';

    protected ?string $falseLabel = 'No';

    protected ?string $placeholderLabel = null;

    protected bool $nullable = false;

    protected ?Closure $trueQuery = null;

    protected ?Closure $falseQuery = null;

    protected ?Closure $blankQuery = null;

    public static function make(string $name): static
    {
        return parent::make($name);
    }

    /**
     * Set custom label for the true state.
     */
    public function trueLabel(string $label): static
    {
        $this->trueLabel = $label;

        return $this;
    }

    /**
     * Set custom label for the false state.
     */
    public function falseLabel(string $label): static
    {
        $this->falseLabel = $label;

        return $this;
    }

    /**
     * Set custom placeholder label.
     */
    public function placeholderLabel(?string $label): static
    {
        $this->placeholderLabel = $label;

        return $this;
    }

    /**
     * Enable nullable state treatment.
     */
    public function nullable(bool $condition = true): static
    {
        $this->nullable = $condition;

        return $this;
    }

    /**
     * Define custom queries for each state.
     *
     * @param  Closure|null  $true  Query for true state
     * @param  Closure|null  $false  Query for false state
     * @param  Closure|null  $blank  Query for blank state
     */
    public function queries(?Closure $true = null, ?Closure $false = null, ?Closure $blank = null): static
    {
        $this->trueQuery = $true;
        $this->falseQuery = $false;
        $this->blankQuery = $blank;

        return $this;
    }

    /**
     * Apply the filter to the query.
     */
    public function apply($query, mixed $value)
    {
        // If value is null or empty, apply blank query (or no modification)
        if ($value === null || $value === '' || $value === 'blank') {
            if ($this->blankQuery) {
                return ($this->blankQuery)($query);
            }

            return $query;
        }

        // Convert value to boolean
        $boolValue = filter_var($value, FILTER_VALIDATE_BOOLEAN);

        // If custom queries are defined, use them
        if ($boolValue && $this->trueQuery) {
            return ($this->trueQuery)($query);
        }

        if (! $boolValue && $this->falseQuery) {
            return ($this->falseQuery)($query);
        }

        // Default behavior: use attribute
        $attribute = $this->getAttribute() ?? $this->getName();

        if ($boolValue) {
            // True state: check for non-null or true value
            if ($this->nullable) {
                return $query->whereNotNull($attribute);
            }

            return $query->where($attribute, true);
        } else {
            // False state: check for null or false value
            if ($this->nullable) {
                return $query->whereNull($attribute);
            }

            return $query->where($attribute, false);
        }
    }

    protected function getVueComponent(): string
    {
        return 'TernaryFilter';
    }

    protected function getVueProps(): array
    {
        // Build the Select form component with three options
        $select = Select::make($this->getName())
            ->label($this->getLabel() ?? $this->getName())
            ->options([
                'true' => $this->trueLabel,
                'false' => $this->falseLabel,
            ])
            ->placeholder($this->placeholderLabel ?? 'All');

        return [
            'default' => $this->default,
            'formField' => $select->toLaraviltProps(),
        ];
    }

    protected function getFlutterWidget(): string
    {
        return 'LaraviltTernaryFilter';
    }

    protected function getFlutterWidgetProps(): array
    {
        return [
            ...parent::getFlutterWidgetProps(),
            'trueLabel' => $this->trueLabel,
            'falseLabel' => $this->falseLabel,
            'placeholderLabel' => $this->placeholderLabel,
            'nullable' => $this->nullable,
        ];
    }
}
