<?php

namespace Laravilt\Tables\Filters;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Laravilt\Forms\Components\Field;
use Laravilt\Support\Component;

class Filter extends Component
{
    protected ?Closure $query = null;

    protected mixed $default = null;

    protected ?Field $formField = null;

    /** @var array<int, Field>|null */
    protected ?array $formFields = null;

    protected ?string $attribute = null;

    protected ?Closure $indicateUsing = null;

    protected ?string $model = null;

    /**
     * Set the model class for this filter.
     * This is used for relationship filters to load options.
     */
    public function model(string $model): static
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Get the model class for this filter.
     */
    public function getModel(): ?string
    {
        return $this->model;
    }

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
     * Set form schema for this filter (Filament compatibility).
     * Accepts an array of form components or a closure that returns the array.
     *
     * @param  array<int, Field>|Closure  $schema
     */
    public function form(array|Closure $schema): static
    {
        // If it's a closure, resolve it
        if ($schema instanceof Closure) {
            $schema = $schema();
        }

        $fields = array_values($schema);

        if (count($fields) > 0) {
            // The first field is kept for backwards compatibility with single-field consumers
            $this->formField = $fields[0];
            // Every field is serialized so the frontend can render the whole form
            $this->formFields = $fields;
        }

        return $this;
    }

    /**
     * Whether the filter state is an array keyed by form field name (Filament style
     * `$data`), as opposed to one scalar value keyed by the filter name.
     *
     * A form made of a single field named like the filter keeps the scalar state
     * the filter always had, so existing `fn ($query, $value)` callbacks keep working.
     */
    public function hasFormData(): bool
    {
        if ($this->formFields === null || count($this->formFields) === 0) {
            return false;
        }

        return ! (count($this->formFields) === 1 && $this->formFields[0]->getName() === $this->getName());
    }

    /**
     * Normalize a raw request value into the state sent to the frontend:
     * empty values are dropped and a state with nothing left becomes null.
     */
    public function normalizeState(mixed $value): mixed
    {
        if (is_array($value)) {
            $isList = array_is_list($value);
            $value = array_filter(
                array_map(fn ($item) => $this->normalizeState($item), $value),
                fn ($item) => $item !== null
            );

            if ($value === []) {
                return null;
            }

            return $isList ? array_values($value) : $value;
        }

        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        return $value;
    }

    /**
     * Whether a form-data filter has no value in any of its fields, in which
     * case it must not be applied to the query.
     */
    public function isEmptyFormData(mixed $value): bool
    {
        return $this->hasFormData() && $this->normalizeState($value) === null;
    }

    /**
     * Build the `$data` array handed to the query callback of a form filter:
     * one key per form field, null for fields the request did not fill in.
     *
     * @return array<string, mixed>
     */
    protected function resolveFormData(mixed $value): array
    {
        $names = array_map(fn (Field $field) => $field->getName(), $this->getFormFields());
        $data = array_fill_keys($names, null);

        if (is_array($value)) {
            return array_merge($data, $value);
        }

        $data[$names[0]] = $value;

        return $data;
    }

    /**
     * Get the schema field for this filter.
     */
    public function getSchema(): ?Field
    {
        return $this->formField;
    }

    /**
     * Get all form fields for this filter.
     *
     * @return array<int, Field>
     */
    public function getFormFields(): array
    {
        return $this->formFields ?? ($this->formField ? [$this->formField] : []);
    }

    /**
     * Apply the filter to the query.
     *
     * @param  Builder<Model>  $query
     */
    public function apply($query, mixed $value)
    {
        if ($this->query) {
            return ($this->query)($query, $this->hasFormData() ? $this->resolveFormData($value) : $value);
        }

        return $query;
    }

    protected function getVueProps(): array
    {
        $props = [
            'default' => $this->default,
            'hasFormData' => $this->hasFormData(),
        ];

        // If multiple form fields are set, include all of them
        if ($this->formFields && count($this->formFields) > 0) {
            $props['formFields'] = array_map(
                fn (Field $field) => $field->toLaraviltProps(),
                $this->formFields
            );
            // Also include the first field for backwards compatibility
            $props['formField'] = $this->formFields[0]->toLaraviltProps();
        }
        // If a single custom form field is set, include its schema
        elseif ($this->formField) {
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
