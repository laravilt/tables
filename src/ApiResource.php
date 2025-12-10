<?php

declare(strict_types=1);

namespace Laravilt\Tables;

use Laravilt\Support\Contracts\InertiaSerializable;

/**
 * ApiResource defines the API configuration for a table/resource.
 * It specifies which columns are exposed via API and how they're transformed.
 */
class ApiResource implements InertiaSerializable
{
    /** @var array<int, ApiColumn> */
    protected array $columns = [];

    protected ?string $endpoint = null;

    protected ?string $baseUrl = null;

    protected bool $paginated = true;

    protected int $perPage = 12;

    protected ?array $allowedFilters = null;

    protected ?array $allowedSorts = null;

    protected ?array $allowedIncludes = null;

    protected ?string $description = null;

    protected ?string $version = 'v1';

    protected bool $authenticated = false;

    protected ?array $headers = null;

    protected ?array $sampleRequest = null;

    protected ?array $sampleResponse = null;

    protected ?array $fillableFields = null;

    /**
     * Whether to show the API tester interface in the panel.
     */
    protected bool $showApiTester = false;

    protected array $validationRules = [];

    protected array $createRules = [];

    protected array $updateRules = [];

    /**
     * Custom FormRequest class for create validation.
     *
     * @var class-string<\Illuminate\Foundation\Http\FormRequest>|null
     */
    protected ?string $createValidationRequest = null;

    /**
     * Custom FormRequest class for update validation.
     *
     * @var class-string<\Illuminate\Foundation\Http\FormRequest>|null
     */
    protected ?string $updateValidationRequest = null;

    /** @var array<int, ApiAction> */
    protected array $actions = [];

    protected ?string $resourceTitle = null;

    protected ?string $resourceName = null;

    /**
     * Operation configuration: [enabled, middleware]
     * null = default (enabled with auth:sanctum)
     * false = disabled
     * true = enabled with default auth
     * [] = enabled with public access (no auth)
     * ['middleware1', 'middleware2'] = enabled with custom middleware
     */
    protected ?array $listConfig = null;

    protected ?array $showConfig = null;

    protected ?array $createConfig = null;

    protected ?array $updateConfig = null;

    protected ?array $deleteConfig = null;

    protected ?array $bulkDeleteConfig = null;

    /**
     * Default middleware for authenticated operations.
     */
    protected array $defaultMiddleware = ['auth:sanctum'];

    public static function make(): static
    {
        return new static;
    }

    /**
     * Set the API columns.
     *
     * @param  array<int, ApiColumn>  $columns
     */
    public function columns(array $columns): static
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * Set the API endpoint path.
     */
    public function endpoint(?string $endpoint): static
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    /**
     * Set the base URL for the API.
     */
    public function baseUrl(?string $baseUrl): static
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    /**
     * Enable/disable pagination.
     */
    public function paginated(bool $condition = true): static
    {
        $this->paginated = $condition;

        return $this;
    }

    /**
     * Set default items per page.
     */
    public function perPage(int $perPage): static
    {
        $this->perPage = $perPage;

        return $this;
    }

    /**
     * Set allowed filter fields.
     */
    public function allowedFilters(?array $filters): static
    {
        $this->allowedFilters = $filters;

        return $this;
    }

    /**
     * Set allowed sort fields.
     */
    public function allowedSorts(?array $sorts): static
    {
        $this->allowedSorts = $sorts;

        return $this;
    }

    /**
     * Set allowed includes (relationships).
     */
    public function allowedIncludes(?array $includes): static
    {
        $this->allowedIncludes = $includes;

        return $this;
    }

    /**
     * Set API description for documentation.
     */
    public function description(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Set API version.
     */
    public function version(?string $version): static
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Mark as requiring authentication.
     */
    public function authenticated(bool $condition = true): static
    {
        $this->authenticated = $condition;

        return $this;
    }

    /**
     * Set default headers for API requests.
     */
    public function headers(?array $headers): static
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Set a sample request for documentation/testing.
     */
    public function sampleRequest(?array $request): static
    {
        $this->sampleRequest = $request;

        return $this;
    }

    /**
     * Set a sample response for documentation/testing.
     */
    public function sampleResponse(?array $response): static
    {
        $this->sampleResponse = $response;

        return $this;
    }

    /**
     * Enable or disable the API tester interface in the panel.
     * When enabled, shows an interactive API testing UI for this resource.
     */
    public function useAPITester(bool $condition = true): static
    {
        $this->showApiTester = $condition;

        return $this;
    }

    /**
     * Check if the API tester interface is enabled.
     */
    public function hasAPITester(): bool
    {
        return $this->showApiTester;
    }

    /**
     * Get the columns.
     *
     * @return array<int, ApiColumn>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Get visible (non-hidden) columns.
     *
     * @return array<int, ApiColumn>
     */
    public function getVisibleColumns(): array
    {
        return array_filter($this->columns, fn (ApiColumn $col) => ! $col->isHidden());
    }

    /**
     * Get the full API URL.
     */
    public function getFullUrl(): string
    {
        $base = $this->baseUrl ?? config('app.url', '');
        $endpoint = $this->endpoint ?? '';

        return rtrim($base, '/').'/'.ltrim($endpoint, '/');
    }

    /**
     * Transform a record to API format.
     */
    public function transformRecord(mixed $record): array
    {
        $result = [];

        foreach ($this->getVisibleColumns() as $column) {
            $name = $column->getName();
            $value = is_array($record) ? ($record[$name] ?? null) : ($record->{$name} ?? null);
            $result[$name] = $column->transform($value, $record);
        }

        return $result;
    }

    /**
     * Transform multiple records to API format.
     */
    public function transformRecords(array $records): array
    {
        return array_map(fn ($record) => $this->transformRecord($record), $records);
    }

    /**
     * Get allowed filters from columns.
     */
    public function getAllowedFilters(): array
    {
        if ($this->allowedFilters !== null) {
            return $this->allowedFilters;
        }

        return array_values(array_map(
            fn (ApiColumn $col) => $col->getName(),
            array_filter($this->columns, fn (ApiColumn $col) => $col->isFilterable())
        ));
    }

    /**
     * Get allowed sorts from columns.
     */
    public function getAllowedSorts(): array
    {
        if ($this->allowedSorts !== null) {
            return $this->allowedSorts;
        }

        return array_values(array_map(
            fn (ApiColumn $col) => $col->getName(),
            array_filter($this->columns, fn (ApiColumn $col) => $col->isSortable())
        ));
    }

    /**
     * Get searchable columns.
     */
    public function getSearchableColumns(): array
    {
        return array_values(array_map(
            fn (ApiColumn $col) => $col->getName(),
            array_filter($this->columns, fn (ApiColumn $col) => $col->isSearchable())
        ));
    }

    /**
     * Get relationships to load.
     */
    public function getRelationships(): array
    {
        return array_filter(
            array_map(
                fn (ApiColumn $col) => $col->getRelationship(),
                $this->columns
            )
        );
    }

    /**
     * Set fillable fields for create/update.
     */
    public function fillable(?array $fields): static
    {
        $this->fillableFields = $fields;

        return $this;
    }

    /**
     * Get fillable fields (fields allowed for create/update).
     */
    public function getFillableFields(): array
    {
        if ($this->fillableFields !== null) {
            return $this->fillableFields;
        }

        // By default, use all non-hidden, non-computed columns except id, created_at, updated_at
        $excludedFields = ['id', 'created_at', 'updated_at', 'deleted_at'];

        return array_values(array_filter(
            array_map(
                fn (ApiColumn $col) => $col->getName(),
                array_filter($this->columns, fn (ApiColumn $col) => ! $col->isHidden())
            ),
            fn ($name) => ! in_array($name, $excludedFields)
        ));
    }

    /**
     * Set validation rules for all operations.
     */
    public function rules(array $rules): static
    {
        $this->validationRules = $rules;

        return $this;
    }

    /**
     * Set validation rules for create operation.
     */
    public function createRules(array $rules): static
    {
        $this->createRules = $rules;

        return $this;
    }

    /**
     * Set validation rules for update operation.
     */
    public function updateRules(array $rules): static
    {
        $this->updateRules = $rules;

        return $this;
    }

    /**
     * Set custom FormRequest class for validation.
     * This overrides the ApiColumn-based validation rules.
     *
     * @param  class-string<\Illuminate\Foundation\Http\FormRequest>  $requestClass
     * @param  string|null  $operation  'create', 'update', or null for both
     */
    public function validationRequest(string $requestClass, ?string $operation = null): static
    {
        if ($operation === null || $operation === 'create') {
            $this->createValidationRequest = $requestClass;
        }

        if ($operation === null || $operation === 'update') {
            $this->updateValidationRequest = $requestClass;
        }

        return $this;
    }

    /**
     * Set custom FormRequest class specifically for create validation.
     *
     * @param  class-string<\Illuminate\Foundation\Http\FormRequest>  $requestClass
     */
    public function createValidationRequest(string $requestClass): static
    {
        $this->createValidationRequest = $requestClass;

        return $this;
    }

    /**
     * Set custom FormRequest class specifically for update validation.
     *
     * @param  class-string<\Illuminate\Foundation\Http\FormRequest>  $requestClass
     */
    public function updateValidationRequest(string $requestClass): static
    {
        $this->updateValidationRequest = $requestClass;

        return $this;
    }

    /**
     * Get the FormRequest class for the specified operation.
     *
     * @return class-string<\Illuminate\Foundation\Http\FormRequest>|null
     */
    public function getValidationRequestClass(string $operation = 'create'): ?string
    {
        return match ($operation) {
            'create', 'store' => $this->createValidationRequest,
            'update', 'patch', 'put' => $this->updateValidationRequest,
            default => null,
        };
    }

    /**
     * Check if a custom FormRequest class is set for the operation.
     */
    public function hasValidationRequest(string $operation = 'create'): bool
    {
        return $this->getValidationRequestClass($operation) !== null;
    }

    /**
     * Get validation rules for a specific operation.
     * Priority: explicit rules > ApiColumn rules
     * Note: If a FormRequest is set, use hasValidationRequest() to check
     * and getValidationRequestClass() to get the class instead of this method.
     */
    public function getValidationRules(string $operation = 'create'): array
    {
        // First priority: explicit operation-specific rules
        if ($operation === 'create' && ! empty($this->createRules)) {
            return array_merge($this->validationRules, $this->createRules);
        }

        if ($operation === 'update' && ! empty($this->updateRules)) {
            return array_merge($this->validationRules, $this->updateRules);
        }

        // Second priority: explicit general rules
        if (! empty($this->validationRules)) {
            return $this->validationRules;
        }

        // Third priority: collect from ApiColumns
        return $this->getColumnValidationRules($operation);
    }

    /**
     * Get validation rules from ApiColumns.
     */
    protected function getColumnValidationRules(string $operation = 'create'): array
    {
        $rules = [];

        foreach ($this->columns as $column) {
            // Skip columns that shouldn't be validated for this operation
            if ($operation === 'create' && ! $column->isCreatable()) {
                continue;
            }

            if ($operation === 'update' && ! $column->isUpdatable()) {
                continue;
            }

            // Skip non-writable columns
            if (! $column->isWritable()) {
                continue;
            }

            // Skip timestamp and ID fields
            $name = $column->getName();
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            // Get the validation rules from the column
            $columnRules = $operation === 'create'
                ? $column->getCreateRules()
                : $column->getUpdateRules();

            if (! empty($columnRules)) {
                $rules[$name] = $columnRules;
            }
        }

        return $rules;
    }

    /**
     * Get CRUD endpoints info for documentation.
     * Only returns enabled operations.
     */
    public function getEndpoints(): array
    {
        $endpoint = $this->endpoint ?? '';
        $endpoints = [];

        if ($this->isOperationEnabled('list')) {
            $endpoints['index'] = [
                'method' => 'GET',
                'path' => $endpoint,
                'description' => 'List all records with pagination, filtering, and sorting',
                'enabled' => true,
                'isPublic' => empty($this->getOperationMiddleware('list')),
            ];
        }

        if ($this->isOperationEnabled('show')) {
            $endpoints['show'] = [
                'method' => 'GET',
                'path' => $endpoint.'/{id}',
                'description' => 'Get a single record by ID',
                'enabled' => true,
                'isPublic' => empty($this->getOperationMiddleware('show')),
            ];
        }

        if ($this->isOperationEnabled('create')) {
            $endpoints['store'] = [
                'method' => 'POST',
                'path' => $endpoint,
                'description' => 'Create a new record',
                'enabled' => true,
                'isPublic' => empty($this->getOperationMiddleware('create')),
            ];
        }

        if ($this->isOperationEnabled('update')) {
            $endpoints['update'] = [
                'method' => 'PUT/PATCH',
                'path' => $endpoint.'/{id}',
                'description' => 'Update an existing record',
                'enabled' => true,
                'isPublic' => empty($this->getOperationMiddleware('update')),
            ];
        }

        if ($this->isOperationEnabled('delete')) {
            $endpoints['destroy'] = [
                'method' => 'DELETE',
                'path' => $endpoint.'/{id}',
                'description' => 'Delete a single record',
                'enabled' => true,
                'isPublic' => empty($this->getOperationMiddleware('delete')),
            ];
        }

        if ($this->isOperationEnabled('bulkDelete')) {
            $endpoints['bulkDestroy'] = [
                'method' => 'DELETE',
                'path' => $endpoint,
                'description' => 'Delete multiple records by IDs',
                'enabled' => true,
                'isPublic' => empty($this->getOperationMiddleware('bulkDelete')),
            ];
        }

        return $endpoints;
    }

    /**
     * Set the resource title (for OpenAPI documentation).
     */
    public function title(string $title): static
    {
        $this->resourceTitle = $title;

        return $this;
    }

    /**
     * Set the resource name (for OpenAPI documentation).
     */
    public function name(string $name): static
    {
        $this->resourceName = $name;

        return $this;
    }

    /**
     * Set custom API actions.
     *
     * @param  array<int, ApiAction>  $actions
     */
    public function actions(array $actions): static
    {
        $this->actions = $actions;

        return $this;
    }

    /**
     * Configure list operation.
     *
     * @param  bool  $enabled  Whether the operation is enabled
     * @param  array|null  $middleware  Custom middleware (empty array = public, null = default auth)
     */
    public function list(bool $enabled = true, ?array $middleware = null): static
    {
        $this->listConfig = [
            'enabled' => $enabled,
            'middleware' => $middleware,
        ];

        return $this;
    }

    /**
     * Configure show operation.
     *
     * @param  bool  $enabled  Whether the operation is enabled
     * @param  array|null  $middleware  Custom middleware (empty array = public, null = default auth)
     */
    public function show(bool $enabled = true, ?array $middleware = null): static
    {
        $this->showConfig = [
            'enabled' => $enabled,
            'middleware' => $middleware,
        ];

        return $this;
    }

    /**
     * Configure create operation.
     *
     * @param  bool  $enabled  Whether the operation is enabled
     * @param  array|null  $middleware  Custom middleware (empty array = public, null = default auth)
     */
    public function create(bool $enabled = true, ?array $middleware = null): static
    {
        $this->createConfig = [
            'enabled' => $enabled,
            'middleware' => $middleware,
        ];

        return $this;
    }

    /**
     * Configure update operation.
     *
     * @param  bool  $enabled  Whether the operation is enabled
     * @param  array|null  $middleware  Custom middleware (empty array = public, null = default auth)
     */
    public function update(bool $enabled = true, ?array $middleware = null): static
    {
        $this->updateConfig = [
            'enabled' => $enabled,
            'middleware' => $middleware,
        ];

        return $this;
    }

    /**
     * Configure delete operation.
     *
     * @param  bool  $enabled  Whether the operation is enabled
     * @param  array|null  $middleware  Custom middleware (empty array = public, null = default auth)
     */
    public function delete(bool $enabled = true, ?array $middleware = null): static
    {
        $this->deleteConfig = [
            'enabled' => $enabled,
            'middleware' => $middleware,
        ];

        return $this;
    }

    /**
     * Configure bulk delete operation.
     *
     * @param  bool  $enabled  Whether the operation is enabled
     * @param  array|null  $middleware  Custom middleware (empty array = public, null = default auth)
     */
    public function bulkDelete(bool $enabled = true, ?array $middleware = null): static
    {
        $this->bulkDeleteConfig = [
            'enabled' => $enabled,
            'middleware' => $middleware,
        ];

        return $this;
    }

    /**
     * Set default middleware for all operations.
     */
    public function defaultMiddleware(array $middleware): static
    {
        $this->defaultMiddleware = $middleware;

        return $this;
    }

    /**
     * Check if an operation is enabled.
     */
    public function isOperationEnabled(string $operation): bool
    {
        $config = $this->getOperationConfig($operation);

        // If config is null (not set), operation is enabled by default
        if ($config === null) {
            return true;
        }

        return $config['enabled'] ?? true;
    }

    /**
     * Get middleware for an operation.
     *
     * @return array The middleware to apply (empty array means public access)
     */
    public function getOperationMiddleware(string $operation): array
    {
        $config = $this->getOperationConfig($operation);

        // If config is null (not set), use default middleware
        if ($config === null) {
            return $this->defaultMiddleware;
        }

        // If middleware is null in config, use default middleware
        if ($config['middleware'] === null) {
            return $this->defaultMiddleware;
        }

        // Return the configured middleware (empty array = public access)
        return $config['middleware'];
    }

    /**
     * Get the configuration for an operation.
     */
    protected function getOperationConfig(string $operation): ?array
    {
        return match ($operation) {
            'list', 'index' => $this->listConfig,
            'show' => $this->showConfig,
            'create', 'store' => $this->createConfig,
            'update' => $this->updateConfig,
            'delete', 'destroy' => $this->deleteConfig,
            'bulkDelete', 'bulkDestroy' => $this->bulkDeleteConfig,
            default => null,
        };
    }

    /**
     * Get all operations configuration for serialization.
     */
    public function getOperationsConfig(): array
    {
        return [
            'list' => [
                'enabled' => $this->isOperationEnabled('list'),
                'middleware' => $this->getOperationMiddleware('list'),
                'isPublic' => empty($this->getOperationMiddleware('list')),
            ],
            'show' => [
                'enabled' => $this->isOperationEnabled('show'),
                'middleware' => $this->getOperationMiddleware('show'),
                'isPublic' => empty($this->getOperationMiddleware('show')),
            ],
            'create' => [
                'enabled' => $this->isOperationEnabled('create'),
                'middleware' => $this->getOperationMiddleware('create'),
                'isPublic' => empty($this->getOperationMiddleware('create')),
            ],
            'update' => [
                'enabled' => $this->isOperationEnabled('update'),
                'middleware' => $this->getOperationMiddleware('update'),
                'isPublic' => empty($this->getOperationMiddleware('update')),
            ],
            'delete' => [
                'enabled' => $this->isOperationEnabled('delete'),
                'middleware' => $this->getOperationMiddleware('delete'),
                'isPublic' => empty($this->getOperationMiddleware('delete')),
            ],
            'bulkDelete' => [
                'enabled' => $this->isOperationEnabled('bulkDelete'),
                'middleware' => $this->getOperationMiddleware('bulkDelete'),
                'isPublic' => empty($this->getOperationMiddleware('bulkDelete')),
            ],
        ];
    }

    /**
     * Get the custom API actions.
     *
     * @return array<int, ApiAction>
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * Get visible (non-hidden) custom actions.
     *
     * @return array<int, ApiAction>
     */
    public function getVisibleActions(): array
    {
        return array_filter($this->actions, fn (ApiAction $action) => ! $action->isHidden());
    }

    /**
     * Find an action by slug.
     */
    public function findAction(string $slug): ?ApiAction
    {
        foreach ($this->actions as $action) {
            if ($action->getSlug() === $slug) {
                return $action;
            }
        }

        return null;
    }

    /**
     * Generate OpenAPI 3.0 specification.
     */
    public function toOpenApi(): array
    {
        $endpoint = $this->endpoint ?? '/api/resource';
        $baseUrl = $this->baseUrl ?? config('app.url', '');
        $title = $this->resourceTitle ?? ucfirst(basename($endpoint)).' API';
        $resourceName = $this->resourceName ?? ucfirst(basename($endpoint));

        $spec = [
            'openapi' => '3.0.3',
            'info' => [
                'title' => $title,
                'description' => $this->description ?? "API for managing {$resourceName} resources",
                'version' => $this->version ?? '1.0.0',
                'contact' => [
                    'name' => 'API Support',
                ],
            ],
            'servers' => [
                [
                    'url' => rtrim($baseUrl, '/'),
                    'description' => 'Main API Server',
                ],
            ],
            'tags' => [
                [
                    'name' => $resourceName,
                    'description' => "Operations for {$resourceName}",
                ],
                [
                    'name' => 'Custom Actions',
                    'description' => 'Custom API actions',
                ],
            ],
            'paths' => $this->buildOpenApiPaths($endpoint, $resourceName),
            'components' => [
                'schemas' => $this->buildOpenApiSchemas($resourceName),
                'securitySchemes' => $this->authenticated ? [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT',
                    ],
                    'sanctum' => [
                        'type' => 'apiKey',
                        'in' => 'cookie',
                        'name' => 'laravel_session',
                    ],
                ] : [],
            ],
        ];

        if ($this->authenticated) {
            $spec['security'] = [
                ['bearerAuth' => []],
                ['sanctum' => []],
            ];
        }

        return $spec;
    }

    /**
     * Build OpenAPI paths specification.
     */
    protected function buildOpenApiPaths(string $endpoint, string $resourceName): array
    {
        $paths = [];
        $singularName = rtrim($resourceName, 's');

        // Collection endpoints (index, store, bulk delete)
        $collectionMethods = [];

        // GET (list) operation
        if ($this->isOperationEnabled('list')) {
            $collectionMethods['get'] = [
                'summary' => "List all {$resourceName}",
                'description' => 'Retrieve a paginated list of records with optional filtering and sorting',
                'operationId' => 'index'.ucfirst($resourceName),
                'tags' => [$resourceName],
                'parameters' => $this->buildIndexParameters(),
                'responses' => [
                    '200' => [
                        'description' => 'Successful response',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/'.$singularName.'ListResponse',
                                ],
                            ],
                        ],
                    ],
                    '401' => ['description' => 'Unauthorized'],
                ],
            ];

            // Add security note if public
            if (empty($this->getOperationMiddleware('list'))) {
                unset($collectionMethods['get']['responses']['401']);
                $collectionMethods['get']['security'] = [];
            }
        }

        // POST (create) operation
        if ($this->isOperationEnabled('create')) {
            $collectionMethods['post'] = [
                'summary' => "Create a new {$singularName}",
                'description' => 'Create a new record',
                'operationId' => 'store'.ucfirst($singularName),
                'tags' => [$resourceName],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/'.$singularName.'CreateRequest',
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '201' => [
                        'description' => 'Record created successfully',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/'.$singularName.'Response',
                                ],
                            ],
                        ],
                    ],
                    '422' => ['description' => 'Validation error'],
                    '401' => ['description' => 'Unauthorized'],
                ],
            ];

            if (empty($this->getOperationMiddleware('create'))) {
                unset($collectionMethods['post']['responses']['401']);
                $collectionMethods['post']['security'] = [];
            }
        }

        // DELETE (bulk delete) operation
        if ($this->isOperationEnabled('bulkDelete')) {
            $collectionMethods['delete'] = [
                'summary' => "Bulk delete {$resourceName}",
                'description' => 'Delete multiple records by IDs',
                'operationId' => 'bulkDestroy'.ucfirst($resourceName),
                'tags' => [$resourceName],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'ids' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'integer'],
                                        'description' => 'IDs of records to delete',
                                    ],
                                ],
                                'required' => ['ids'],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Records deleted successfully',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'message' => ['type' => 'string'],
                                        'deleted_count' => ['type' => 'integer'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    '400' => ['description' => 'No IDs provided'],
                    '401' => ['description' => 'Unauthorized'],
                ],
            ];

            if (empty($this->getOperationMiddleware('bulkDelete'))) {
                unset($collectionMethods['delete']['responses']['401']);
                $collectionMethods['delete']['security'] = [];
            }
        }

        if (! empty($collectionMethods)) {
            $paths[$endpoint] = $collectionMethods;
        }

        // Single record endpoints (show, update, delete)
        $recordMethods = [];
        $idParam = [
            'name' => 'id',
            'in' => 'path',
            'required' => true,
            'schema' => ['type' => 'integer'],
            'description' => 'Record ID',
        ];

        // GET (show) operation
        if ($this->isOperationEnabled('show')) {
            $recordMethods['get'] = [
                'summary' => "Get a {$singularName}",
                'description' => 'Retrieve a single record by ID',
                'operationId' => 'show'.ucfirst($singularName),
                'tags' => [$resourceName],
                'parameters' => [$idParam],
                'responses' => [
                    '200' => [
                        'description' => 'Successful response',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/'.$singularName.'Response',
                                ],
                            ],
                        ],
                    ],
                    '404' => ['description' => 'Record not found'],
                    '401' => ['description' => 'Unauthorized'],
                ],
            ];

            if (empty($this->getOperationMiddleware('show'))) {
                unset($recordMethods['get']['responses']['401']);
                $recordMethods['get']['security'] = [];
            }
        }

        // PUT (update) operation
        if ($this->isOperationEnabled('update')) {
            $recordMethods['put'] = [
                'summary' => "Update a {$singularName}",
                'description' => 'Update an existing record',
                'operationId' => 'update'.ucfirst($singularName),
                'tags' => [$resourceName],
                'parameters' => [$idParam],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/'.$singularName.'UpdateRequest',
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Record updated successfully',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/'.$singularName.'Response',
                                ],
                            ],
                        ],
                    ],
                    '404' => ['description' => 'Record not found'],
                    '422' => ['description' => 'Validation error'],
                    '401' => ['description' => 'Unauthorized'],
                ],
            ];

            // PATCH operation (same as PUT for partial updates)
            $recordMethods['patch'] = [
                'summary' => "Partially update a {$singularName}",
                'description' => 'Partially update an existing record',
                'operationId' => 'patch'.ucfirst($singularName),
                'tags' => [$resourceName],
                'parameters' => [$idParam],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/'.$singularName.'UpdateRequest',
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Record updated successfully',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/'.$singularName.'Response',
                                ],
                            ],
                        ],
                    ],
                    '404' => ['description' => 'Record not found'],
                    '422' => ['description' => 'Validation error'],
                    '401' => ['description' => 'Unauthorized'],
                ],
            ];

            if (empty($this->getOperationMiddleware('update'))) {
                unset($recordMethods['put']['responses']['401']);
                unset($recordMethods['patch']['responses']['401']);
                $recordMethods['put']['security'] = [];
                $recordMethods['patch']['security'] = [];
            }
        }

        // DELETE operation
        if ($this->isOperationEnabled('delete')) {
            $recordMethods['delete'] = [
                'summary' => "Delete a {$singularName}",
                'description' => 'Delete a single record by ID',
                'operationId' => 'destroy'.ucfirst($singularName),
                'tags' => [$resourceName],
                'parameters' => [$idParam],
                'responses' => [
                    '200' => [
                        'description' => 'Record deleted successfully',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'message' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    '404' => ['description' => 'Record not found'],
                    '401' => ['description' => 'Unauthorized'],
                ],
            ];

            if (empty($this->getOperationMiddleware('delete'))) {
                unset($recordMethods['delete']['responses']['401']);
                $recordMethods['delete']['security'] = [];
            }
        }

        if (! empty($recordMethods)) {
            $paths[$endpoint.'/{id}'] = $recordMethods;
        }

        // Add custom actions paths
        foreach ($this->getVisibleActions() as $action) {
            $actionPath = $action->getEndpointPath($endpoint);
            $method = strtolower($action->getMethod());

            if (! isset($paths[$actionPath])) {
                $paths[$actionPath] = [];
            }

            $paths[$actionPath][$method] = $action->toOpenApiOperation($endpoint);
            $paths[$actionPath][$method]['tags'] = ['Custom Actions'];
        }

        return $paths;
    }

    /**
     * Build index endpoint parameters.
     */
    protected function buildIndexParameters(): array
    {
        $params = [
            [
                'name' => 'page',
                'in' => 'query',
                'schema' => ['type' => 'integer', 'default' => 1],
                'description' => 'Page number',
            ],
            [
                'name' => 'per_page',
                'in' => 'query',
                'schema' => ['type' => 'integer', 'default' => $this->perPage],
                'description' => 'Items per page',
            ],
        ];

        // Add search parameter if searchable columns exist
        $searchableColumns = $this->getSearchableColumns();
        if (! empty($searchableColumns)) {
            $params[] = [
                'name' => 'search',
                'in' => 'query',
                'schema' => ['type' => 'string'],
                'description' => 'Search term (searches: '.implode(', ', $searchableColumns).')',
            ];
        }

        // Add filter parameters
        foreach ($this->getAllowedFilters() as $filter) {
            $params[] = [
                'name' => $filter,
                'in' => 'query',
                'schema' => ['type' => 'string'],
                'description' => "Filter by {$filter}",
            ];
        }

        // Add sort parameters
        if (! empty($this->getAllowedSorts())) {
            $params[] = [
                'name' => 'sort',
                'in' => 'query',
                'schema' => [
                    'type' => 'string',
                    'enum' => $this->getAllowedSorts(),
                ],
                'description' => 'Sort field',
            ];
            $params[] = [
                'name' => 'direction',
                'in' => 'query',
                'schema' => [
                    'type' => 'string',
                    'enum' => ['asc', 'desc'],
                    'default' => 'asc',
                ],
                'description' => 'Sort direction',
            ];
        }

        // Add include parameter
        if (! empty($this->allowedIncludes)) {
            $params[] = [
                'name' => 'include',
                'in' => 'query',
                'schema' => ['type' => 'string'],
                'description' => 'Include relationships (comma-separated): '.implode(', ', $this->allowedIncludes),
            ];
        }

        return $params;
    }

    /**
     * Build OpenAPI schemas specification.
     */
    protected function buildOpenApiSchemas(string $resourceName): array
    {
        $singularName = rtrim($resourceName, 's');
        $properties = [];
        $requiredFields = [];

        foreach ($this->columns as $column) {
            $prop = $this->columnToOpenApiProperty($column);
            $properties[$column->getName()] = $prop;

            // Fields without nullable and not in excluded list are required
            if (! in_array($column->getName(), ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                $requiredFields[] = $column->getName();
            }
        }

        // Build create request schema (exclude id, timestamps)
        $createProperties = array_filter($properties, function ($key) {
            return ! in_array($key, ['id', 'created_at', 'updated_at', 'deleted_at']);
        }, ARRAY_FILTER_USE_KEY);

        // Build update request schema (all fields optional)
        $updateProperties = $createProperties;

        return [
            $singularName => [
                'type' => 'object',
                'properties' => $properties,
            ],
            $singularName.'CreateRequest' => [
                'type' => 'object',
                'properties' => $createProperties,
                'required' => array_values(array_intersect($requiredFields, array_keys($createProperties))),
            ],
            $singularName.'UpdateRequest' => [
                'type' => 'object',
                'properties' => $updateProperties,
            ],
            $singularName.'Response' => [
                'type' => 'object',
                'properties' => [
                    'data' => [
                        '$ref' => '#/components/schemas/'.$singularName,
                    ],
                    'message' => [
                        'type' => 'string',
                    ],
                ],
            ],
            $singularName.'ListResponse' => [
                'type' => 'object',
                'properties' => [
                    'data' => [
                        'type' => 'array',
                        'items' => [
                            '$ref' => '#/components/schemas/'.$singularName,
                        ],
                    ],
                    'meta' => [
                        'type' => 'object',
                        'properties' => [
                            'current_page' => ['type' => 'integer'],
                            'last_page' => ['type' => 'integer'],
                            'per_page' => ['type' => 'integer'],
                            'total' => ['type' => 'integer'],
                            'from' => ['type' => 'integer', 'nullable' => true],
                            'to' => ['type' => 'integer', 'nullable' => true],
                        ],
                    ],
                    'links' => [
                        'type' => 'object',
                        'properties' => [
                            'first' => ['type' => 'string', 'format' => 'uri'],
                            'last' => ['type' => 'string', 'format' => 'uri'],
                            'prev' => ['type' => 'string', 'format' => 'uri', 'nullable' => true],
                            'next' => ['type' => 'string', 'format' => 'uri', 'nullable' => true],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Convert an ApiColumn to OpenAPI property specification.
     */
    protected function columnToOpenApiProperty(ApiColumn $column): array
    {
        $prop = [];

        $type = $column->getType();
        switch ($type) {
            case 'integer':
            case 'int':
                $prop['type'] = 'integer';
                break;
            case 'number':
            case 'float':
            case 'double':
            case 'decimal':
                $prop['type'] = 'number';
                $prop['format'] = 'double';
                break;
            case 'boolean':
            case 'bool':
                $prop['type'] = 'boolean';
                break;
            case 'datetime':
                $prop['type'] = 'string';
                $prop['format'] = 'date-time';
                break;
            case 'date':
                $prop['type'] = 'string';
                $prop['format'] = 'date';
                break;
            case 'time':
                $prop['type'] = 'string';
                $prop['format'] = 'time';
                break;
            case 'array':
                $prop['type'] = 'array';
                $prop['items'] = ['type' => 'string'];
                break;
            case 'object':
                $prop['type'] = 'object';
                break;
            default:
                $prop['type'] = 'string';
        }

        if ($column->getExample() !== null) {
            $prop['example'] = $column->getExample();
        }

        return $prop;
    }

    /**
     * Export OpenAPI spec as JSON string.
     */
    public function toOpenApiJson(): string
    {
        return json_encode($this->toOpenApi(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Export OpenAPI spec as YAML string.
     */
    public function toOpenApiYaml(): string
    {
        if (function_exists('yaml_emit')) {
            return yaml_emit($this->toOpenApi());
        }

        // Fallback to simple YAML conversion
        return $this->arrayToYaml($this->toOpenApi());
    }

    /**
     * Simple array to YAML converter.
     */
    protected function arrayToYaml(array $array, int $indent = 0): string
    {
        $yaml = '';
        $prefix = str_repeat('  ', $indent);

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (empty($value)) {
                    $yaml .= "{$prefix}{$key}: []\n";
                } elseif (array_keys($value) === range(0, count($value) - 1)) {
                    // Sequential array
                    $yaml .= "{$prefix}{$key}:\n";
                    foreach ($value as $item) {
                        if (is_array($item)) {
                            $yaml .= "{$prefix}- ".ltrim($this->arrayToYaml($item, $indent + 2));
                        } else {
                            $yaml .= "{$prefix}- ".$this->formatYamlValue($item)."\n";
                        }
                    }
                } else {
                    // Associative array
                    $yaml .= "{$prefix}{$key}:\n";
                    $yaml .= $this->arrayToYaml($value, $indent + 1);
                }
            } else {
                $yaml .= "{$prefix}{$key}: ".$this->formatYamlValue($value)."\n";
            }
        }

        return $yaml;
    }

    /**
     * Format a value for YAML output.
     */
    protected function formatYamlValue(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }
        if ($value === true) {
            return 'true';
        }
        if ($value === false) {
            return 'false';
        }
        if (is_numeric($value)) {
            return (string) $value;
        }
        if (preg_match('/^[a-zA-Z0-9_\-\/\.]+$/', $value)) {
            return $value;
        }

        return '"'.addslashes($value).'"';
    }

    public function toInertiaProps(): array
    {
        return [
            'columns' => array_map(
                fn (ApiColumn $column) => $column->toInertiaProps(),
                $this->columns
            ),
            'endpoint' => $this->endpoint,
            'baseUrl' => $this->baseUrl ?? config('app.url', ''),
            'fullUrl' => $this->getFullUrl(),
            'paginated' => $this->paginated,
            'perPage' => $this->perPage,
            'allowedFilters' => $this->getAllowedFilters(),
            'allowedSorts' => $this->getAllowedSorts(),
            'allowedIncludes' => $this->allowedIncludes,
            'searchableColumns' => $this->getSearchableColumns(),
            'description' => $this->description,
            'version' => $this->version,
            'authenticated' => $this->authenticated,
            'headers' => $this->headers ?? [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'sampleRequest' => $this->sampleRequest,
            'sampleResponse' => $this->sampleResponse,
            'endpoints' => $this->getEndpoints(),
            'fillableFields' => $this->getFillableFields(),
            'actions' => array_map(
                fn (ApiAction $action) => $action->toInertiaProps(),
                $this->getVisibleActions()
            ),
            'operations' => $this->getOperationsConfig(),
            'openApiSpec' => $this->toOpenApi(),
            'showApiTester' => $this->showApiTester,
        ];
    }

    public function toFlutterProps(): array
    {
        return $this->toInertiaProps();
    }
}
