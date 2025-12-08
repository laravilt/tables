<?php

declare(strict_types=1);

namespace Laravilt\Tables;

use Closure;
use Laravilt\Support\Component;
use Laravilt\Tables\Concerns\HasApiValidation;

/**
 * ApiColumn is used to define columns for API responses.
 * Similar to Table columns but optimized for API output.
 * Supports validation for create/update operations.
 */
class ApiColumn extends Component
{
    use HasApiValidation;

    protected ?string $type = null;

    protected ?string $format = null;

    protected bool $nullable = false;

    protected ?string $description = null;

    protected mixed $example = null;

    protected ?Closure $transformUsing = null;

    protected ?string $castAs = null;

    protected bool $sortable = false;

    protected bool $filterable = false;

    protected bool $searchable = false;

    protected ?array $enum = null;

    protected ?string $relationship = null;

    protected ?array $nestedColumns = null;

    /**
     * Whether this field is writable (can be included in create/update).
     */
    protected bool $writable = true;

    /**
     * Whether this field is readable (included in API responses).
     */
    protected bool $readable = true;

    /**
     * Whether this field can be created.
     */
    protected bool $creatable = true;

    /**
     * Whether this field can be updated.
     */
    protected bool $updatable = true;

    /**
     * Default value for this field.
     */
    protected mixed $default = null;

    /**
     * Set the field type (string, integer, boolean, datetime, array, object, etc.).
     */
    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set the format (e.g., 'date-time', 'email', 'uri', 'uuid').
     */
    public function format(?string $format): static
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Mark field as nullable.
     */
    public function nullable(bool $condition = true): static
    {
        $this->nullable = $condition;

        return $this;
    }


    /**
     * Set a description for documentation.
     */
    public function description(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Set an example value for documentation.
     */
    public function example(mixed $example): static
    {
        $this->example = $example;

        return $this;
    }

    /**
     * Transform the value using a callback.
     */
    public function transformUsing(?Closure $callback): static
    {
        $this->transformUsing = $callback;

        return $this;
    }

    /**
     * Cast the value to a specific type.
     */
    public function castAs(?string $type): static
    {
        $this->castAs = $type;

        return $this;
    }

    /**
     * Mark as sortable in API queries.
     */
    public function sortable(bool $condition = true): static
    {
        $this->sortable = $condition;

        return $this;
    }

    /**
     * Mark as filterable in API queries.
     */
    public function filterable(bool $condition = true): static
    {
        $this->filterable = $condition;

        return $this;
    }

    /**
     * Mark as searchable in API queries.
     */
    public function searchable(bool $condition = true): static
    {
        $this->searchable = $condition;

        return $this;
    }

    /**
     * Set allowed enum values.
     */
    public function enum(?array $values): static
    {
        $this->enum = $values;

        return $this;
    }

    /**
     * Set a relationship to load.
     */
    public function relationship(?string $relationship): static
    {
        $this->relationship = $relationship;

        return $this;
    }

    /**
     * Set nested columns for relationships/objects.
     *
     * @param  array<int, ApiColumn>|null  $columns
     */
    public function nestedColumns(?array $columns): static
    {
        $this->nestedColumns = $columns;

        return $this;
    }

    /**
     * Transform the value for API output.
     */
    public function transform(mixed $value, mixed $record = null): mixed
    {
        // Apply custom transformation first
        if ($this->transformUsing instanceof Closure) {
            $value = ($this->transformUsing)($value, $record);
        }

        if ($value === null && $this->nullable) {
            return null;
        }

        // Apply cast
        if ($this->castAs) {
            return $this->castValue($value, $this->castAs);
        }

        // Apply type-based transformation
        return match ($this->type) {
            'datetime' => $value instanceof \DateTimeInterface ? $value->format($this->format ?? 'c') : $value,
            'date' => $value instanceof \DateTimeInterface ? $value->format($this->format ?? 'Y-m-d') : $value,
            'time' => $value instanceof \DateTimeInterface ? $value->format($this->format ?? 'H:i:s') : $value,
            'boolean', 'bool' => (bool) $value,
            'integer', 'int' => (int) $value,
            'number', 'float', 'double' => (float) $value,
            'string' => (string) $value,
            'array' => is_array($value) ? $value : (array) $value,
            'object' => is_object($value) ? $value : (object) $value,
            default => $value,
        };
    }

    /**
     * Cast a value to a specific type.
     */
    protected function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'bool', 'boolean' => (bool) $value,
            'int', 'integer' => (int) $value,
            'float', 'double' => (float) $value,
            'string' => (string) $value,
            'array' => (array) $value,
            'object' => (object) $value,
            'json' => is_string($value) ? json_decode($value, true) : $value,
            default => $value,
        };
    }

    /**
     * Check if this column is sortable.
     */
    public function isSortable(): bool
    {
        return $this->sortable;
    }

    /**
     * Check if this column is filterable.
     */
    public function isFilterable(): bool
    {
        return $this->filterable;
    }

    /**
     * Check if this column is searchable.
     */
    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    /**
     * Get the relationship name.
     */
    public function getRelationship(): ?string
    {
        return $this->relationship;
    }

    /**
     * Get nested columns.
     *
     * @return array<int, ApiColumn>|null
     */
    public function getNestedColumns(): ?array
    {
        return $this->nestedColumns;
    }

    /**
     * Get the type.
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Get the example value.
     */
    public function getExample(): mixed
    {
        return $this->example;
    }

    /**
     * Serialize for Inertia/Vue.
     */
    public function toInertiaProps(): array
    {
        return [
            'name' => $this->getName(),
            'type' => $this->type,
            'format' => $this->format,
            'nullable' => $this->nullable,
            'hidden' => $this->isHidden(),
            'description' => $this->description,
            'example' => $this->example,
            'sortable' => $this->sortable,
            'filterable' => $this->filterable,
            'searchable' => $this->searchable,
            'enum' => $this->enum,
            'relationship' => $this->relationship,
            'nestedColumns' => $this->nestedColumns ? array_map(
                fn (ApiColumn $col) => $col->toInertiaProps(),
                $this->nestedColumns
            ) : null,
            // Validation and access control
            'required' => $this->isRequired(),
            'validation' => $this->getValidationConfig(),
            'writable' => $this->writable,
            'readable' => $this->readable,
            'creatable' => $this->creatable,
            'updatable' => $this->updatable,
            'default' => $this->default,
        ];
    }

    protected function getVueComponent(): string
    {
        return 'ApiColumn';
    }

    protected function getVueProps(): array
    {
        return [
            'type' => $this->type,
            'format' => $this->format,
            'nullable' => $this->nullable,
            'hidden' => $this->isHidden(),
            'description' => $this->description,
            'example' => $this->example,
            'sortable' => $this->sortable,
            'filterable' => $this->filterable,
            'searchable' => $this->searchable,
            'enum' => $this->enum,
            'relationship' => $this->relationship,
            'nestedColumns' => $this->nestedColumns ? array_map(
                fn (ApiColumn $col) => $col->toInertiaProps(),
                $this->nestedColumns
            ) : null,
            // Validation and access control
            'required' => $this->isRequired(),
            'validation' => $this->getValidationConfig(),
            'writable' => $this->writable,
            'readable' => $this->readable,
            'creatable' => $this->creatable,
            'updatable' => $this->updatable,
            'default' => $this->default,
        ];
    }

    protected function getFlutterWidget(): string
    {
        return 'ApiColumn';
    }

    protected function getFlutterWidgetProps(): array
    {
        return $this->getVueProps();
    }

    /**
     * Mark field as writable (can be included in create/update).
     */
    public function writable(bool $condition = true): static
    {
        $this->writable = $condition;

        return $this;
    }

    /**
     * Mark field as not writable (alias for writable(false)).
     */
    public function notWritable(bool $condition = true): static
    {
        $this->writable = ! $condition;

        return $this;
    }

    /**
     * Mark field as readable (included in API responses).
     */
    public function readable(bool $condition = true): static
    {
        $this->readable = $condition;

        return $this;
    }

    /**
     * Mark field as write-only (not included in API responses).
     */
    public function writeOnly(bool $condition = true): static
    {
        $this->readable = ! $condition;

        return $this;
    }

    /**
     * Mark field as creatable (can be set during create).
     */
    public function creatable(bool $condition = true): static
    {
        $this->creatable = $condition;

        return $this;
    }

    /**
     * Mark field as updatable (can be set during update).
     */
    public function updatable(bool $condition = true): static
    {
        $this->updatable = $condition;

        return $this;
    }

    /**
     * Set default value for this field.
     */
    public function default(mixed $value): static
    {
        $this->default = $value;

        return $this;
    }

    /**
     * Check if field is writable.
     */
    public function isWritable(): bool
    {
        return $this->writable;
    }

    /**
     * Check if field is readable.
     */
    public function isReadable(): bool
    {
        return $this->readable;
    }

    /**
     * Check if field is creatable.
     */
    public function isCreatable(): bool
    {
        return $this->creatable;
    }

    /**
     * Check if field is updatable.
     */
    public function isUpdatable(): bool
    {
        return $this->updatable;
    }

    /**
     * Get the default value.
     */
    public function getDefault(): mixed
    {
        return $this->default;
    }

    /**
     * Check if field is nullable.
     */
    public function isNullable(): bool
    {
        return $this->nullable;
    }
}
