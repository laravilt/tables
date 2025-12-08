<?php

declare(strict_types=1);

namespace Laravilt\Tables\Concerns;

use Closure;
use Illuminate\Validation\Rule;

/**
 * Provides validation capabilities for API columns.
 * Follows the same pattern as form field validation for consistency.
 */
trait HasApiValidation
{
    protected bool|Closure $required = false;

    protected string|array|Closure|null $validationRules = null;

    protected array $validationMessages = [];

    protected array $validationAttributes = [];

    /**
     * Validation rules for create operations only.
     */
    protected string|array|Closure|null $createRules = null;

    /**
     * Validation rules for update operations only.
     */
    protected string|array|Closure|null $updateRules = null;

    // -------------------------------------------------------------------------
    // Core validation methods
    // -------------------------------------------------------------------------

    /**
     * Mark the field as required.
     */
    public function required(bool|Closure $condition = true): static
    {
        $this->required = $condition;

        if ($condition === true) {
            $this->addRules('required');
        }

        return $this;
    }

    /**
     * Check if the field is required.
     */
    public function isRequired(): bool
    {
        if ($this->required instanceof Closure) {
            return (bool) ($this->required)();
        }

        return $this->required;
    }

    /**
     * Set validation rules.
     *
     * @param  string|array|Closure  $rules  Validation rules
     */
    public function rules(string|array|Closure $rules): static
    {
        $this->validationRules = $rules;

        return $this;
    }

    /**
     * Add validation rules to existing ones.
     */
    public function addRules(string|array $rules): static
    {
        $currentRules = $this->validationRules ?? [];

        if (is_string($currentRules) && is_string($rules)) {
            $this->validationRules = $currentRules ? $currentRules.'|'.$rules : $rules;
        } elseif (is_array($currentRules) && is_array($rules)) {
            $this->validationRules = array_merge($currentRules, $rules);
        } else {
            $currentRules = is_string($currentRules) ? ($currentRules ? explode('|', $currentRules) : []) : (array) $currentRules;
            $rules = is_string($rules) ? explode('|', $rules) : (array) $rules;
            $this->validationRules = array_merge($currentRules, $rules);
        }

        return $this;
    }

    /**
     * Get validation rules.
     */
    public function getValidationRules(): array
    {
        $rules = $this->validationRules;

        if ($rules instanceof Closure) {
            $rules = $rules();
        }

        if (is_string($rules)) {
            return explode('|', $rules);
        }

        return $rules ?? [];
    }

    /**
     * Set validation rules for create operations only.
     */
    public function createRules(string|array|Closure $rules): static
    {
        $this->createRules = $rules;

        return $this;
    }

    /**
     * Set validation rules for update operations only.
     */
    public function updateRules(string|array|Closure $rules): static
    {
        $this->updateRules = $rules;

        return $this;
    }

    /**
     * Get validation rules for a specific operation (create/update).
     */
    public function getValidationRulesFor(string $operation = 'create'): array
    {
        $baseRules = $this->getValidationRules();

        $operationRules = match ($operation) {
            'create' => $this->createRules,
            'update' => $this->updateRules,
            default => null,
        };

        if ($operationRules === null) {
            return $baseRules;
        }

        if ($operationRules instanceof Closure) {
            $operationRules = $operationRules();
        }

        if (is_string($operationRules)) {
            $operationRules = explode('|', $operationRules);
        }

        return array_merge($baseRules, $operationRules);
    }

    /**
     * Set custom validation messages.
     */
    public function validationMessages(array $messages): static
    {
        $this->validationMessages = $messages;

        return $this;
    }

    /**
     * Get validation messages.
     */
    public function getValidationMessages(): array
    {
        $messages = [];
        foreach ($this->validationMessages as $rule => $message) {
            $messages["{$this->getName()}.{$rule}"] = $message;
        }

        return $messages;
    }

    /**
     * Set custom validation attribute name.
     */
    public function validationAttribute(string $attribute): static
    {
        $this->validationAttributes = [$this->getName() => $attribute];

        return $this;
    }

    /**
     * Get validation attributes.
     */
    public function getValidationAttributes(): array
    {
        return $this->validationAttributes;
    }

    // -------------------------------------------------------------------------
    // String validation shortcuts
    // -------------------------------------------------------------------------

    /**
     * Validate as email.
     */
    public function email(): static
    {
        return $this->addRules('email');
    }

    /**
     * Validate as URL.
     */
    public function url(): static
    {
        return $this->addRules('url');
    }

    /**
     * Validate as active URL.
     */
    public function activeUrl(): static
    {
        return $this->addRules('active_url');
    }

    /**
     * Validate as IP address.
     */
    public function ip(): static
    {
        return $this->addRules('ip');
    }

    /**
     * Validate as IPv4 address.
     */
    public function ipv4(): static
    {
        return $this->addRules('ipv4');
    }

    /**
     * Validate as IPv6 address.
     */
    public function ipv6(): static
    {
        return $this->addRules('ipv6');
    }

    /**
     * Validate as UUID.
     */
    public function uuid(): static
    {
        return $this->addRules('uuid');
    }

    /**
     * Validate as ULID.
     */
    public function ulid(): static
    {
        return $this->addRules('ulid');
    }

    /**
     * Validate as JSON.
     */
    public function json(): static
    {
        return $this->addRules('json');
    }

    /**
     * Validate as alpha characters only.
     */
    public function alpha(): static
    {
        return $this->addRules('alpha');
    }

    /**
     * Validate as alpha-dash characters.
     */
    public function alphaDash(): static
    {
        return $this->addRules('alpha_dash');
    }

    /**
     * Validate as alphanumeric characters.
     */
    public function alphaNum(): static
    {
        return $this->addRules('alpha_num');
    }

    /**
     * Validate with regex pattern.
     */
    public function regex(string $pattern): static
    {
        return $this->addRules("regex:{$pattern}");
    }

    /**
     * Validate starts with one of the given values.
     */
    public function startsWith(string ...$values): static
    {
        return $this->addRules('starts_with:'.implode(',', $values));
    }

    /**
     * Validate ends with one of the given values.
     */
    public function endsWith(string ...$values): static
    {
        return $this->addRules('ends_with:'.implode(',', $values));
    }

    /**
     * Validate doesn't start with any of the given values.
     */
    public function doesntStartWith(string ...$values): static
    {
        return $this->addRules('doesnt_start_with:'.implode(',', $values));
    }

    /**
     * Validate doesn't end with any of the given values.
     */
    public function doesntEndWith(string ...$values): static
    {
        return $this->addRules('doesnt_end_with:'.implode(',', $values));
    }

    // -------------------------------------------------------------------------
    // Size validation shortcuts
    // -------------------------------------------------------------------------

    /**
     * Set minimum value/length.
     */
    public function min(int|float $min): static
    {
        return $this->addRules("min:{$min}");
    }

    /**
     * Set maximum value/length.
     */
    public function max(int|float $max): static
    {
        return $this->addRules("max:{$max}");
    }

    /**
     * Set minimum length (string specific).
     */
    public function minLength(int $length): static
    {
        return $this->addRules("min:{$length}");
    }

    /**
     * Set maximum length (string specific).
     */
    public function maxLength(int $length): static
    {
        return $this->addRules("max:{$length}");
    }

    /**
     * Set exact length.
     */
    public function length(int $length): static
    {
        return $this->addRules("size:{$length}");
    }

    /**
     * Set value/length between min and max.
     */
    public function between(int|float $min, int|float $max): static
    {
        return $this->addRules("between:{$min},{$max}");
    }

    /**
     * Set exact size.
     */
    public function size(int|float $size): static
    {
        return $this->addRules("size:{$size}");
    }

    /**
     * Validate greater than another field.
     */
    public function gt(string $field): static
    {
        return $this->addRules("gt:{$field}");
    }

    /**
     * Validate greater than or equal to another field.
     */
    public function gte(string $field): static
    {
        return $this->addRules("gte:{$field}");
    }

    /**
     * Validate less than another field.
     */
    public function lt(string $field): static
    {
        return $this->addRules("lt:{$field}");
    }

    /**
     * Validate less than or equal to another field.
     */
    public function lte(string $field): static
    {
        return $this->addRules("lte:{$field}");
    }

    // -------------------------------------------------------------------------
    // Numeric validation shortcuts
    // -------------------------------------------------------------------------

    /**
     * Validate as numeric.
     */
    public function numeric(): static
    {
        return $this->addRules('numeric');
    }

    /**
     * Validate as integer.
     */
    public function integer(): static
    {
        return $this->addRules('integer');
    }

    /**
     * Validate as decimal with specified places.
     */
    public function decimal(int $min, ?int $max = null): static
    {
        $rule = "decimal:{$min}";
        if ($max !== null) {
            $rule .= ",{$max}";
        }

        return $this->addRules($rule);
    }

    /**
     * Validate as multiple of a number.
     */
    public function multipleOf(int|float $value): static
    {
        return $this->addRules("multiple_of:{$value}");
    }

    // -------------------------------------------------------------------------
    // Date validation shortcuts
    // -------------------------------------------------------------------------

    /**
     * Validate as date.
     */
    public function date(): static
    {
        return $this->addRules('date');
    }

    /**
     * Validate as date with specific format.
     */
    public function dateFormat(string $format): static
    {
        return $this->addRules("date_format:{$format}");
    }

    /**
     * Validate date is after another date/field.
     */
    public function after(string $dateOrField): static
    {
        return $this->addRules("after:{$dateOrField}");
    }

    /**
     * Validate date is after or equal to another date/field.
     */
    public function afterOrEqual(string $dateOrField): static
    {
        return $this->addRules("after_or_equal:{$dateOrField}");
    }

    /**
     * Validate date is before another date/field.
     */
    public function before(string $dateOrField): static
    {
        return $this->addRules("before:{$dateOrField}");
    }

    /**
     * Validate date is before or equal to another date/field.
     */
    public function beforeOrEqual(string $dateOrField): static
    {
        return $this->addRules("before_or_equal:{$dateOrField}");
    }

    /**
     * Validate date equals another date/field.
     */
    public function dateEquals(string $date): static
    {
        return $this->addRules("date_equals:{$date}");
    }

    /**
     * Validate timezone.
     */
    public function timezone(): static
    {
        return $this->addRules('timezone');
    }

    // -------------------------------------------------------------------------
    // Database validation shortcuts
    // -------------------------------------------------------------------------

    /**
     * Validate uniqueness in database.
     */
    public function unique(?string $table = null, ?string $column = null, mixed $ignore = null, ?string $ignoreColumn = null): static
    {
        if ($table === null) {
            // Mark as unique for documentation purposes
            return $this;
        }

        $rule = Rule::unique($table, $column ?? $this->getName());

        if ($ignore !== null) {
            $rule->ignore($ignore, $ignoreColumn ?? 'id');
        }

        return $this->addRules([$rule]);
    }

    /**
     * Validate existence in database.
     */
    public function exists(string $table, ?string $column = null): static
    {
        $column = $column ?? $this->getName();

        return $this->addRules("exists:{$table},{$column}");
    }

    /**
     * Validate field matches confirmation field.
     */
    public function confirmed(): static
    {
        return $this->addRules('confirmed');
    }

    /**
     * Validate different from another field.
     */
    public function different(string $field): static
    {
        return $this->addRules("different:{$field}");
    }

    /**
     * Validate same as another field.
     */
    public function same(string $field): static
    {
        return $this->addRules("same:{$field}");
    }

    // -------------------------------------------------------------------------
    // Array validation shortcuts
    // -------------------------------------------------------------------------

    /**
     * Validate as array.
     */
    public function array(?array $keys = null): static
    {
        if ($keys === null) {
            return $this->addRules('array');
        }

        return $this->addRules('array:'.implode(',', $keys));
    }

    /**
     * Validate array has distinct values.
     */
    public function distinct(bool $strict = false, bool $ignoreCase = false): static
    {
        $rule = 'distinct';
        if ($strict) {
            $rule .= ':strict';
        }
        if ($ignoreCase) {
            $rule .= ':ignore_case';
        }

        return $this->addRules($rule);
    }

    /**
     * Validate array has minimum count.
     */
    public function minCount(int $count): static
    {
        return $this->addRules("min:{$count}");
    }

    /**
     * Validate array has maximum count.
     */
    public function maxCount(int $count): static
    {
        return $this->addRules("max:{$count}");
    }

    // -------------------------------------------------------------------------
    // In/Not In validation shortcuts
    // -------------------------------------------------------------------------

    /**
     * Validate value is in list.
     */
    public function in(array $values): static
    {
        return $this->addRules([Rule::in($values)]);
    }

    /**
     * Validate value is not in list.
     */
    public function notIn(array $values): static
    {
        return $this->addRules([Rule::notIn($values)]);
    }

    // -------------------------------------------------------------------------
    // File validation shortcuts (for file upload APIs)
    // -------------------------------------------------------------------------

    /**
     * Validate as file.
     */
    public function file(): static
    {
        return $this->addRules('file');
    }

    /**
     * Validate as image.
     */
    public function image(): static
    {
        return $this->addRules('image');
    }

    /**
     * Validate file MIME types.
     */
    public function mimes(string ...$mimeTypes): static
    {
        return $this->addRules('mimes:'.implode(',', $mimeTypes));
    }

    /**
     * Validate file MIME type by MIME string.
     */
    public function mimetypes(string ...$mimeTypes): static
    {
        return $this->addRules('mimetypes:'.implode(',', $mimeTypes));
    }

    /**
     * Validate image dimensions.
     */
    public function dimensions(array $constraints): static
    {
        $parts = [];
        foreach ($constraints as $key => $value) {
            $parts[] = "{$key}={$value}";
        }

        return $this->addRules('dimensions:'.implode(',', $parts));
    }

    // -------------------------------------------------------------------------
    // Conditional validation
    // -------------------------------------------------------------------------

    /**
     * Only validate when condition is true.
     */
    public function requiredIf(string $field, mixed $value): static
    {
        if (is_array($value)) {
            return $this->addRules('required_if:'.$field.','.implode(',', $value));
        }

        return $this->addRules("required_if:{$field},{$value}");
    }

    /**
     * Only validate when condition is false.
     */
    public function requiredUnless(string $field, mixed $value): static
    {
        if (is_array($value)) {
            return $this->addRules('required_unless:'.$field.','.implode(',', $value));
        }

        return $this->addRules("required_unless:{$field},{$value}");
    }

    /**
     * Required when other field is present.
     */
    public function requiredWith(string ...$fields): static
    {
        return $this->addRules('required_with:'.implode(',', $fields));
    }

    /**
     * Required when all other fields are present.
     */
    public function requiredWithAll(string ...$fields): static
    {
        return $this->addRules('required_with_all:'.implode(',', $fields));
    }

    /**
     * Required when other field is not present.
     */
    public function requiredWithout(string ...$fields): static
    {
        return $this->addRules('required_without:'.implode(',', $fields));
    }

    /**
     * Required when all other fields are not present.
     */
    public function requiredWithoutAll(string ...$fields): static
    {
        return $this->addRules('required_without_all:'.implode(',', $fields));
    }

    /**
     * Required when array has keys.
     */
    public function requiredArrayKeys(string ...$keys): static
    {
        return $this->addRules('required_array_keys:'.implode(',', $keys));
    }

    /**
     * Sometimes validate (only if present).
     */
    public function sometimes(): static
    {
        return $this->addRules('sometimes');
    }

    /**
     * Mark as present (must exist but can be empty).
     */
    public function present(): static
    {
        return $this->addRules('present');
    }

    /**
     * Mark as prohibited.
     */
    public function prohibited(): static
    {
        return $this->addRules('prohibited');
    }

    /**
     * Prohibited if another field equals value.
     */
    public function prohibitedIf(string $field, mixed $value): static
    {
        if (is_array($value)) {
            return $this->addRules('prohibited_if:'.$field.','.implode(',', $value));
        }

        return $this->addRules("prohibited_if:{$field},{$value}");
    }

    /**
     * Prohibited unless another field equals value.
     */
    public function prohibitedUnless(string $field, mixed $value): static
    {
        if (is_array($value)) {
            return $this->addRules('prohibited_unless:'.$field.','.implode(',', $value));
        }

        return $this->addRules("prohibited_unless:{$field},{$value}");
    }

    /**
     * Prohibits other fields when this field is present.
     */
    public function prohibits(string ...$fields): static
    {
        return $this->addRules('prohibits:'.implode(',', $fields));
    }

    // -------------------------------------------------------------------------
    // Utility methods for API
    // -------------------------------------------------------------------------

    /**
     * Get validation config for OpenAPI spec generation.
     */
    public function getValidationConfig(): array
    {
        $rules = $this->getValidationRules();
        $config = [
            'required' => $this->isRequired(),
            'rules' => $rules,
        ];

        // Parse rules for OpenAPI-compatible constraints
        foreach ($rules as $rule) {
            if (is_string($rule)) {
                if (preg_match('/^min:(\d+(?:\.\d+)?)$/', $rule, $matches)) {
                    $config['minimum'] = (float) $matches[1];
                }
                if (preg_match('/^max:(\d+(?:\.\d+)?)$/', $rule, $matches)) {
                    $config['maximum'] = (float) $matches[1];
                }
                if (preg_match('/^between:(\d+(?:\.\d+)?),(\d+(?:\.\d+)?)$/', $rule, $matches)) {
                    $config['minimum'] = (float) $matches[1];
                    $config['maximum'] = (float) $matches[2];
                }
                if (preg_match('/^size:(\d+)$/', $rule, $matches)) {
                    $config['exactLength'] = (int) $matches[1];
                }
                if (str_contains($rule, 'email')) {
                    $config['format'] = 'email';
                }
                if (str_contains($rule, 'url') || str_contains($rule, 'active_url')) {
                    $config['format'] = 'uri';
                }
                if (str_contains($rule, 'uuid')) {
                    $config['format'] = 'uuid';
                }
                if (str_contains($rule, 'ip')) {
                    $config['format'] = 'ipv4';
                }
                if (str_contains($rule, 'ipv6')) {
                    $config['format'] = 'ipv6';
                }
                if (str_contains($rule, 'date')) {
                    $config['format'] = 'date';
                }
                if (preg_match('/^regex:(.+)$/', $rule, $matches)) {
                    $config['pattern'] = $matches[1];
                }
            }
        }

        return $config;
    }

    /**
     * Build full validation rules array for Laravel validator.
     */
    public function buildValidationRule(string $operation = 'create'): array
    {
        return [
            $this->getName() => $this->getValidationRulesFor($operation),
        ];
    }

    /**
     * Get validation rules for create operation.
     * Alias for getValidationRulesFor('create').
     */
    public function getCreateRules(): array
    {
        return $this->getValidationRulesFor('create');
    }

    /**
     * Get validation rules for update operation.
     * Alias for getValidationRulesFor('update').
     */
    public function getUpdateRules(): array
    {
        return $this->getValidationRulesFor('update');
    }
}
