<?php

declare(strict_types=1);

namespace Laravilt\Tables;

use Closure;
use Laravilt\Support\Contracts\InertiaSerializable;

/**
 * ApiAction defines custom API actions for a resource.
 * Similar to Filament Actions but for API endpoints.
 */
class ApiAction implements InertiaSerializable
{
    protected string $name;

    protected ?string $slug = null;

    protected ?string $label = null;

    protected ?string $description = null;

    protected string $method = 'POST';

    protected ?Closure $action = null;

    protected ?Closure $beforeAction = null;

    protected ?Closure $afterAction = null;

    protected bool $requiresRecord = true;

    protected bool $bulk = false;

    protected bool $requiresConfirmation = false;

    protected ?string $confirmationMessage = null;

    protected array $validationRules = [];

    protected ?array $requestSchema = null;

    protected ?array $responseSchema = null;

    protected ?string $successMessage = null;

    protected ?string $errorMessage = null;

    protected bool $hidden = false;

    protected ?string $icon = null;

    protected ?string $color = null;

    /** @var array<int, ApiColumn> */
    protected array $fields = [];

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->slug = $this->generateSlug($name);
    }

    public static function make(string $name): static
    {
        return new static($name);
    }

    /**
     * Generate URL-friendly slug from name.
     */
    protected function generateSlug(string $name): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
    }

    /**
     * Set the action slug (URL path).
     */
    public function slug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Set the action label.
     */
    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Set the action description.
     */
    public function description(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Set the HTTP method (GET, POST, PUT, PATCH, DELETE).
     */
    public function method(string $method): static
    {
        $this->method = strtoupper($method);

        return $this;
    }

    /**
     * Shorthand for GET method.
     */
    public function get(): static
    {
        return $this->method('GET');
    }

    /**
     * Shorthand for POST method.
     */
    public function post(): static
    {
        return $this->method('POST');
    }

    /**
     * Shorthand for PUT method.
     */
    public function put(): static
    {
        return $this->method('PUT');
    }

    /**
     * Shorthand for PATCH method.
     */
    public function patch(): static
    {
        return $this->method('PATCH');
    }

    /**
     * Shorthand for DELETE method.
     */
    public function delete(): static
    {
        return $this->method('DELETE');
    }

    /**
     * Set the action callback.
     * The callback receives the record (or null) and the request.
     *
     * @param  Closure(mixed $record, \Illuminate\Http\Request $request): mixed  $action
     */
    public function action(Closure $action): static
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Set a before action hook.
     *
     * @param  Closure(mixed $record, \Illuminate\Http\Request $request): void  $callback
     */
    public function before(Closure $callback): static
    {
        $this->beforeAction = $callback;

        return $this;
    }

    /**
     * Set an after action hook.
     *
     * @param  Closure(mixed $record, \Illuminate\Http\Request $request, mixed $result): void  $callback
     */
    public function after(Closure $callback): static
    {
        $this->afterAction = $callback;

        return $this;
    }

    /**
     * Mark action as not requiring a specific record.
     */
    public function requiresRecord(bool $condition = true): static
    {
        $this->requiresRecord = $condition;

        return $this;
    }

    /**
     * Mark action as operating on multiple records (bulk action).
     */
    public function bulk(bool $condition = true): static
    {
        $this->bulk = $condition;
        if ($condition) {
            $this->requiresRecord = false;
        }

        return $this;
    }

    /**
     * Require confirmation before executing.
     */
    public function requiresConfirmation(bool $condition = true, ?string $message = null): static
    {
        $this->requiresConfirmation = $condition;
        $this->confirmationMessage = $message;

        return $this;
    }

    /**
     * Set validation rules for the action.
     */
    public function rules(array $rules): static
    {
        $this->validationRules = $rules;

        return $this;
    }

    /**
     * Set request schema for documentation.
     */
    public function requestSchema(array $schema): static
    {
        $this->requestSchema = $schema;

        return $this;
    }

    /**
     * Set response schema for documentation.
     */
    public function responseSchema(array $schema): static
    {
        $this->responseSchema = $schema;

        return $this;
    }

    /**
     * Set success message.
     */
    public function successMessage(string $message): static
    {
        $this->successMessage = $message;

        return $this;
    }

    /**
     * Set error message.
     */
    public function errorMessage(string $message): static
    {
        $this->errorMessage = $message;

        return $this;
    }

    /**
     * Hide the action from the API tester UI.
     */
    public function hidden(bool $condition = true): static
    {
        $this->hidden = $condition;

        return $this;
    }

    /**
     * Set the action icon (Lucide icon name).
     */
    public function icon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Set the action color.
     */
    public function color(?string $color): static
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Alias for requiresConfirmation with a message.
     */
    public function confirmable(?string $message = null): static
    {
        $this->requiresConfirmation = true;
        $this->confirmationMessage = $message;

        return $this;
    }

    /**
     * Set the action fields (ApiColumns for input).
     *
     * @param  array<int, ApiColumn>  $fields
     */
    public function fields(array $fields): static
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Get the action icon.
     */
    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * Get the action color.
     */
    public function getColor(): ?string
    {
        return $this->color;
    }

    /**
     * Get the action fields.
     *
     * @return array<int, ApiColumn>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Get the action name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the action slug.
     */
    public function getSlug(): string
    {
        return $this->slug ?? $this->generateSlug($this->name);
    }

    /**
     * Get the action label.
     */
    public function getLabel(): string
    {
        return $this->label ?? ucwords(str_replace(['-', '_'], ' ', $this->name));
    }

    /**
     * Get the action description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Get the HTTP method.
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Check if action requires a record.
     */
    public function doesRequireRecord(): bool
    {
        return $this->requiresRecord;
    }

    /**
     * Check if action is a bulk action.
     */
    public function isBulk(): bool
    {
        return $this->bulk;
    }

    /**
     * Check if action requires confirmation.
     */
    public function doesRequireConfirmation(): bool
    {
        return $this->requiresConfirmation;
    }

    /**
     * Get confirmation message.
     */
    public function getConfirmationMessage(): ?string
    {
        return $this->confirmationMessage;
    }

    /**
     * Get validation rules.
     */
    public function getValidationRules(): array
    {
        return $this->validationRules;
    }

    /**
     * Check if action is hidden.
     */
    public function isHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * Execute the action.
     */
    public function execute(mixed $record, \Illuminate\Http\Request $request): mixed
    {
        // Run before hook
        if ($this->beforeAction instanceof Closure) {
            ($this->beforeAction)($record, $request);
        }

        // Run main action
        $result = null;
        if ($this->action instanceof Closure) {
            $result = ($this->action)($record, $request);
        }

        // Run after hook
        if ($this->afterAction instanceof Closure) {
            ($this->afterAction)($record, $request, $result);
        }

        return $result;
    }

    /**
     * Get the endpoint path for this action.
     */
    public function getEndpointPath(string $basePath): string
    {
        if ($this->requiresRecord) {
            return "{$basePath}/{id}/actions/{$this->getSlug()}";
        }

        if ($this->bulk) {
            return "{$basePath}/actions/{$this->getSlug()}";
        }

        return "{$basePath}/actions/{$this->getSlug()}";
    }

    /**
     * Get OpenAPI operation spec for this action.
     */
    public function toOpenApiOperation(string $basePath): array
    {
        $operation = [
            'summary' => $this->getLabel(),
            'description' => $this->description ?? "Execute {$this->getLabel()} action",
            'operationId' => 'action_'.$this->getSlug(),
            'tags' => ['Custom Actions'],
            'responses' => [
                '200' => [
                    'description' => $this->successMessage ?? 'Action executed successfully',
                    'content' => [
                        'application/json' => [
                            'schema' => $this->responseSchema ?? [
                                'type' => 'object',
                                'properties' => [
                                    'success' => ['type' => 'boolean'],
                                    'message' => ['type' => 'string'],
                                    'data' => ['type' => 'object'],
                                ],
                            ],
                        ],
                    ],
                ],
                '422' => [
                    'description' => 'Validation error',
                ],
                '500' => [
                    'description' => $this->errorMessage ?? 'Action failed',
                ],
            ],
        ];

        if ($this->requiresRecord) {
            $operation['parameters'] = [
                [
                    'name' => 'id',
                    'in' => 'path',
                    'required' => true,
                    'schema' => ['type' => 'integer'],
                    'description' => 'Record ID',
                ],
            ];
        }

        if ($this->bulk) {
            $operation['requestBody'] = [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => $this->requestSchema ?? [
                            'type' => 'object',
                            'properties' => [
                                'ids' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'integer'],
                                    'description' => 'IDs of records to process',
                                ],
                            ],
                            'required' => ['ids'],
                        ],
                    ],
                ],
            ];
        } elseif ($this->requestSchema && in_array($this->method, ['POST', 'PUT', 'PATCH'])) {
            $operation['requestBody'] = [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => $this->requestSchema,
                    ],
                ],
            ];
        }

        return $operation;
    }

    public function toInertiaProps(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->getSlug(),
            'label' => $this->getLabel(),
            'description' => $this->description,
            'method' => $this->method,
            'requiresRecord' => $this->requiresRecord,
            'bulk' => $this->bulk,
            'requiresConfirmation' => $this->requiresConfirmation,
            'confirmationMessage' => $this->confirmationMessage,
            'hidden' => $this->hidden,
            'icon' => $this->icon,
            'color' => $this->color,
            'fields' => array_map(
                fn (ApiColumn $field) => $field->toInertiaProps(),
                $this->fields
            ),
            'requestSchema' => $this->requestSchema,
            'responseSchema' => $this->responseSchema,
            'successMessage' => $this->successMessage,
        ];
    }

    public function toFlutterProps(): array
    {
        return $this->toInertiaProps();
    }
}
