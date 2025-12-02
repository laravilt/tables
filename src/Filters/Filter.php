<?php

namespace Laravilt\Tables\Filters;

use Closure;
use Laravilt\Forms\Components\Field;
use Laravilt\Support\Component;

abstract class Filter extends Component
{
    protected ?Closure $query = null;

    protected mixed $default = null;

    protected ?Field $formField = null;

    protected ?string $attribute = null;

    protected ?Closure $indicateUsing = null;

    public function query(?Closure $callback): static
    {
        $this->query = $callback;

        return $this;
    }

    public function default(mixed $value): static
    {
        $this->default = $value;

        return $this;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    /**
     * Set the database column to filter by.
     * If not set, uses the filter name.
     */
    public function attribute(string $attribute): static
    {
        $this->attribute = $attribute;

        return $this;
    }

    public function getAttribute(): ?string
    {
        return $this->attribute;
    }

    // Label is handled by parent's HasLabel trait
    // No need to override getLabel() - it's already implemented

    /**
     * Customize how the filter indicator is displayed.
     */
    public function indicateUsing(?Closure $callback): static
    {
        $this->indicateUsing = $callback;

        return $this;
    }

    /**
     * Get the indicator for this filter with the given value.
     */
    public function getIndicator(mixed $value): ?array
    {
        if ($this->indicateUsing) {
            $result = ($this->indicateUsing)($value);

            if (is_string($result)) {
                return [
                    'label' => $result,
                    'removeField' => $this->getName(),
                ];
            }

            return $result;
        }

        return null;
    }

    /**
     * Set a schema field for this filter.
     * This allows using any Laravilt Form component as a filter.
     */
    public function schema(Field $field): static
    {
        $this->formField = $field;

        return $this;
    }

    /**
     * Get the schema field for this filter.
     */
    public function getSchema(): ?Field
    {
        return $this->formField;
    }

    /**
     * Apply the filter to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    public function apply($query, mixed $value)
    {
        if ($this->query) {
            return ($this->query)($query, $value);
        }

        return $query;
    }

    protected function getVueProps(): array
    {
        $props = [
            'default' => $this->default,
        ];

        // If a custom form field is set, include its schema
        if ($this->formField) {
            $props['formField'] = $this->formField->toLaraviltProps();
        }

        return $props;
    }

    protected function getFlutterWidgetProps(): array
    {
        return [
            'default' => $this->default,
        ];
    }

    /**
     * Override parent to merge Vue-specific props.
     */
    public function toLaraviltProps(): array
    {
        return array_merge(
            parent::toLaraviltProps(),
            $this->getVueProps()
        );
    }

    /**
     * Alias for toLaraviltProps() to match table column interface.
     */
    public function toInertiaProps(): array
    {
        return $this->toLaraviltProps();
    }
}
