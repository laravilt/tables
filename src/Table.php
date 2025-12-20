<?php

namespace Laravilt\Tables;

use Closure;
use Laravilt\Support\Contracts\InertiaSerializable;
use Laravilt\Tables\Columns\Column;
use Laravilt\Tables\Filters\Filter;

class Table implements InertiaSerializable
{
    /**
     * @var array<int, Column>
     */
    protected array $columns = [];

    /**
     * @var array<int, Filter>
     */
    protected array $filters = [];

    /**
     * @var array<int, mixed>
     */
    protected array $actions = [];

    /**
     * @var array<int, mixed>
     */
    protected array $recordActions = [];

    /**
     * @var array<int, mixed>
     */
    protected array $headerActions = [];

    /**
     * @var array<int, mixed>
     */
    protected array $toolbarActions = [];

    /**
     * @var array<int, mixed>
     */
    protected array $bulkActions = [];

    protected bool $searchable = true;

    protected ?string $searchPlaceholder = null;

    protected bool $paginated = true;

    protected int $perPage = 12;

    /**
     * @var array<int>|null
     */
    protected ?array $paginationPageOptions = null;

    protected bool $infiniteScroll = false;

    protected bool $extremePaginationLinks = false;

    protected bool $striped = false;

    protected bool $hoverable = false;

    protected ?string $reorderableColumn = null;

    protected ?string $reorderRoute = null;

    /**
     * @var array<int, Grouping\Group>
     */
    protected array $groups = [];

    protected ?string $defaultGroup = null;

    protected ?int $groupedPerPage = null;

    protected ?string $pollInterval = null;

    protected ?Closure $query = null;

    protected ?string $defaultSortColumn = 'id';

    protected string $defaultSortDirection = 'desc';

    protected bool $fixedActions = false;

    protected string $filtersLayout = 'sidebar';

    // Grid-specific properties
    protected ?Card $card = null;

    protected int $cardsPerRow = 3;

    protected bool $gridOnly = false;

    protected ?string $emptyStateHeading = null;

    protected ?string $emptyStateDescription = null;

    protected ?string $emptyStateIcon = null;

    protected ?string $queryRoute = null;

    protected ?string $resourceSlug = null;

    protected ?string $model = null;

    // API-specific properties
    protected bool $apiEnabled = false;

    protected ?ApiResource $apiResource = null;

    /** @var array<int, ApiColumn>|null */
    protected ?array $apiColumns = null;

    protected ?string $apiEndpoint = null;

    /**
     * Closure to generate clickable record URL.
     * Receives the record as parameter and should return a URL string.
     */
    protected ?Closure $recordUrl = null;

    /**
     * Whether to use the first action's URL as the default record URL.
     */
    protected bool $recordUrlFromFirstAction = true;

    /**
     * Generic options for custom configuration.
     *
     * @var array<string, mixed>
     */
    protected array $options = [];

    public static function make(): static
    {
        return new static;
    }

    /**
     * @param  array<int, Column>  $columns
     */
    public function columns(array $columns): static
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * @param  array<int, Filter>  $filters
     */
    public function filters(array $filters): static
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * @param  array<int, mixed>  $actions
     */
    public function actions(array $actions): static
    {
        $this->actions = $actions;

        return $this;
    }

    /**
     * @param  array<int, mixed>  $actions
     */
    public function recordActions(array $actions): static
    {
        $this->recordActions = $actions;

        return $this;
    }

    /**
     * @return array<int, mixed>
     */
    public function getRecordActions(): array
    {
        return $this->recordActions;
    }

    /**
     * @param  array<int, mixed>  $actions
     */
    public function headerActions(array $actions): static
    {
        $this->headerActions = $actions;

        return $this;
    }

    /**
     * @param  array<int, mixed>  $actions
     */
    public function toolbarActions(array $actions): static
    {
        $this->toolbarActions = $actions;

        return $this;
    }

    /**
     * @return array<int, mixed>
     */
    public function getToolbarActions(): array
    {
        return $this->toolbarActions;
    }

    /**
     * @param  array<int, mixed>  $actions
     */
    public function bulkActions(array $actions): static
    {
        $this->bulkActions = $actions;

        return $this;
    }

    /**
     * @return array<int, mixed>
     */
    public function getBulkActions(): array
    {
        return $this->bulkActions;
    }

    /**
     * Inject model into bulk actions that need it.
     * This is called before serialization to ensure DeleteBulkAction has the model.
     */
    protected function processBulkActions(): array
    {
        if ($this->model === null) {
            return $this->bulkActions;
        }

        return array_map(function ($action) {
            return $this->injectModelIntoBulkAction($action);
        }, $this->bulkActions);
    }

    /**
     * Recursively inject model into bulk actions.
     * Handles both individual actions and BulkActionGroup.
     */
    protected function injectModelIntoBulkAction($action)
    {
        // Handle BulkActionGroup
        if (method_exists($action, 'getActions')) {
            $groupActions = $action->getActions();
            $processedActions = array_map(fn ($a) => $this->injectModelIntoBulkAction($a), $groupActions);

            // Update the group's actions
            if (method_exists($action, 'setActions')) {
                $action->setActions($processedActions);
            }

            return $action;
        }

        // Handle DeleteBulkAction - inject model if not already set
        if ($action instanceof \Laravilt\Actions\DeleteBulkAction) {
            if ($action->getModel() === null) {
                $action->model($this->model);
            }
        }

        return $action;
    }

    /**
     * Inject model into toolbar actions that contain bulk action groups.
     */
    protected function processToolbarActions(): array
    {
        if ($this->model === null) {
            return $this->toolbarActions;
        }

        return array_map(function ($action) {
            return $this->injectModelIntoBulkAction($action);
        }, $this->toolbarActions);
    }

    public function searchPlaceholder(string $placeholder): static
    {
        $this->searchPlaceholder = $placeholder;

        return $this;
    }

    public function defaultSort(string $column, string $direction = 'asc'): static
    {
        $this->defaultSortColumn = $column;
        $this->defaultSortDirection = $direction;

        return $this;
    }

    public function searchable(bool $condition = true): static
    {
        $this->searchable = $condition;

        return $this;
    }

    /**
     * Set pagination options.
     *
     * @param  bool|array<int>  $condition  If bool, enables/disables pagination. If array, sets page size options.
     */
    public function paginated(bool|array $condition = true): static
    {
        if (is_array($condition)) {
            $this->paginated = true;
            $this->paginationPageOptions = $condition;
            // Set the default perPage to the first option
            if (count($condition) > 0) {
                $this->perPage = $condition[0];
            }
        } else {
            $this->paginated = $condition;
        }

        return $this;
    }

    public function perPage(int $perPage): static
    {
        $this->perPage = $perPage;

        return $this;
    }

    /**
     * Set the pagination page size options.
     *
     * @param  array<int>  $options
     */
    public function paginationPageOptions(array $options): static
    {
        $this->paginationPageOptions = $options;

        return $this;
    }

    /**
     * Set the pagination mode.
     */
    public function paginationMode(Enums\PaginationMode|string $mode): static
    {
        // Store the pagination mode value (currently handled by paginated method)
        // This method exists for Filament compatibility
        // Simple mode uses less pagination buttons, Standard shows all, Cursor uses cursor-based pagination

        return $this;
    }

    /**
     * Enable striped rows for the table.
     */
    public function striped(bool $condition = true): static
    {
        $this->striped = $condition;

        return $this;
    }

    /**
     * Set the filters layout.
     *
     * @param  string  $layout  One of 'sidebar', 'dropdown', 'above_table'
     */
    public function filtersLayout(string $layout): static
    {
        $this->filtersLayout = $layout;

        return $this;
    }

    /**
     * Enable hoverable rows for the table.
     */
    public function hoverable(bool $condition = true): static
    {
        $this->hoverable = $condition;

        return $this;
    }

    /**
     * Enable row reordering with the specified column.
     */
    public function reorderable(?string $column = 'sort_order'): static
    {
        $this->reorderableColumn = $column;

        return $this;
    }

    /**
     * Set the polling interval for auto-refresh.
     *
     * @param  string|null  $interval  Interval like '5s', '30s', '1m', etc.
     */
    public function poll(?string $interval): static
    {
        $this->pollInterval = $interval;

        return $this;
    }

    /**
     * Set the empty state heading.
     */
    public function emptyStateHeading(?string $heading): static
    {
        $this->emptyStateHeading = $heading;

        return $this;
    }

    /**
     * Set the empty state description.
     */
    public function emptyStateDescription(?string $description): static
    {
        $this->emptyStateDescription = $description;

        return $this;
    }

    /**
     * Set the empty state icon.
     */
    public function emptyStateIcon(?string $icon): static
    {
        $this->emptyStateIcon = $icon;

        return $this;
    }

    public function query(?Closure $callback): static
    {
        $this->query = $callback;

        return $this;
    }

    public function fixActions(bool $condition = true): static
    {
        $this->fixedActions = $condition;

        return $this;
    }

    public function infiniteScroll(bool $condition = true): static
    {
        $this->infiniteScroll = $condition;

        return $this;
    }

    /**
     * Enable extreme pagination links (first/last page buttons).
     */
    public function extremePaginationLinks(bool $condition = true): static
    {
        $this->extremePaginationLinks = $condition;

        return $this;
    }

    /**
     * Set the grouping options for the table.
     *
     * @param  array<int, Grouping\Group>  $groups
     */
    public function groups(array $groups): static
    {
        $this->groups = $groups;

        return $this;
    }

    /**
     * Set the default group column.
     */
    public function defaultGroup(?string $column): static
    {
        $this->defaultGroup = $column;

        return $this;
    }

    /**
     * Set the per-page limit when grouping is active.
     * Use null to use the default (at least 50), or a specific number.
     * Use -1 to disable pagination when grouping.
     */
    public function groupedPerPage(?int $perPage): static
    {
        $this->groupedPerPage = $perPage;

        return $this;
    }

    /**
     * Get the groups configuration.
     *
     * @return array<int, Grouping\Group>
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * Check if grouping is enabled.
     */
    public function hasGroups(): bool
    {
        return count($this->groups) > 0;
    }

    /**
     * Get the current active group based on request or default.
     */
    public function getActiveGroup(): ?Grouping\Group
    {
        $groupColumn = request()->get('group', $this->defaultGroup);

        if (! $groupColumn) {
            return null;
        }

        foreach ($this->groups as $group) {
            if ($group->getColumn() === $groupColumn) {
                return $group;
            }
        }

        return null;
    }

    // Grid-specific methods

    /**
     * Set a card configuration for grid view.
     */
    public function card(Card $card): static
    {
        $this->card = $card;

        return $this;
    }

    /**
     * Get the card configuration.
     */
    public function getCard(): ?Card
    {
        return $this->card;
    }

    /**
     * Check if table has a grid/card configuration.
     */
    public function hasGrid(): bool
    {
        return $this->card !== null;
    }

    /**
     * Set grid-only mode (hide table view, show only grid).
     */
    public function gridOnly(bool $condition = true): static
    {
        $this->gridOnly = $condition;

        return $this;
    }

    /**
     * Check if grid-only mode is enabled.
     */
    public function isGridOnly(): bool
    {
        return $this->gridOnly;
    }

    /**
     * Set the number of cards per row in grid view.
     */
    public function cardsPerRow(int $columns): static
    {
        $this->cardsPerRow = $columns;

        return $this;
    }

    /**
     * Configure empty state display.
     */
    public function emptyState(
        string $heading,
        ?string $description = null,
        ?string $icon = null
    ): static {
        $this->emptyStateHeading = $heading;
        $this->emptyStateDescription = $description;
        $this->emptyStateIcon = $icon;

        return $this;
    }

    /**
     * Set the query route for AJAX reloading.
     */
    public function queryRoute(string $route): static
    {
        $this->queryRoute = $route;

        return $this;
    }

    /**
     * Get the query route.
     */
    public function getQueryRoute(): ?string
    {
        return $this->queryRoute;
    }

    /**
     * Set the resource slug.
     */
    public function resourceSlug(string $slug): static
    {
        $this->resourceSlug = $slug;

        return $this;
    }

    /**
     * Get the resource slug.
     */
    public function getResourceSlug(): ?string
    {
        return $this->resourceSlug;
    }

    /**
     * Get the column execution route name.
     */
    public function getColumnExecutionRouteName(): string
    {
        // Get current panel from registry
        $registry = app(\Laravilt\Panel\PanelRegistry::class);
        $panel = $registry->getCurrent();

        if (! $panel) {
            $panel = $registry->getDefault();
        }

        if (! $panel) {
            $allPanels = $registry->all();
            $panel = reset($allPanels) ?: null;
        }

        $panelId = $panel?->getId() ?? 'admin';

        return $panelId.'.resources.'.$this->resourceSlug.'.column.update';
    }

    /**
     * Get the reorder route name.
     */
    public function getReorderRouteName(): string
    {
        // Get current panel from registry
        $registry = app(\Laravilt\Panel\PanelRegistry::class);
        $panel = $registry->getCurrent();

        if (! $panel) {
            $panel = $registry->getDefault();
        }

        if (! $panel) {
            $allPanels = $registry->all();
            $panel = reset($allPanels) ?: null;
        }

        $panelId = $panel?->getId() ?? 'admin';

        return $panelId.'.resources.'.$this->resourceSlug.'.reorder';
    }

    /**
     * Set a custom reorder route.
     */
    public function reorderRoute(string $route): static
    {
        $this->reorderRoute = $route;

        return $this;
    }

    /**
     * Set the model class for bulk actions.
     */
    public function model(string $model): static
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Get the model class.
     */
    public function getModel(): ?string
    {
        return $this->model;
    }

    /**
     * Set a custom URL generator for clickable table rows.
     * The closure receives the record as parameter.
     *
     * @param  Closure|string|null  $url  A closure that receives the record and returns a URL, or a static URL
     */
    public function recordUrl(Closure|string|null $url): static
    {
        if (is_string($url)) {
            $this->recordUrl = fn ($record) => $url;
        } else {
            $this->recordUrl = $url;
        }

        return $this;
    }

    /**
     * Disable using the first action's URL as default record URL.
     */
    public function disableRecordUrlFromFirstAction(): static
    {
        $this->recordUrlFromFirstAction = false;

        return $this;
    }

    /**
     * Get the record URL closure.
     */
    public function getRecordUrl(): ?Closure
    {
        return $this->recordUrl;
    }

    /**
     * Evaluate record URL for a specific record.
     *
     * @param  mixed  $record  The record instance or array
     * @return string|null The URL for the record
     */
    public function evaluateRecordUrl(mixed $record): ?string
    {
        // If custom recordUrl is set, use it
        if ($this->recordUrl !== null) {
            return call_user_func($this->recordUrl, $record);
        }

        // If disabled, return null
        if (! $this->recordUrlFromFirstAction) {
            return null;
        }

        // Default: try to get URL from first action
        if (! empty($this->recordActions)) {
            $firstAction = $this->recordActions[0] ?? null;

            if ($firstAction !== null) {
                // Get the action's URL after resolving record context
                $actionClone = clone $firstAction;

                if (method_exists($actionClone, 'resolveRecordContext')) {
                    $recordId = is_object($record) && method_exists($record, 'getKey')
                        ? $record->getKey()
                        : ($record['id'] ?? null);

                    if ($recordId) {
                        $actionClone->resolveRecordContext($recordId);
                    }
                }

                // Get the URL from the action
                if (method_exists($actionClone, 'getUrl')) {
                    return $actionClone->getUrl();
                }
            }
        }

        return null;
    }

    /**
     * Set a custom option.
     */
    public function setOption(string $key, mixed $value): static
    {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * Get a custom option.
     */
    public function getOption(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }

    /**
     * Get all custom options.
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    // API-specific methods

    /**
     * Enable API view/tab for this table.
     *
     * @param  bool|string  $condition  If string, it's treated as the API endpoint.
     */
    public function api(bool|string $condition = true): static
    {
        if (is_string($condition)) {
            $this->apiEnabled = true;
            $this->apiEndpoint = $condition;
        } else {
            $this->apiEnabled = $condition;
        }

        return $this;
    }

    /**
     * Set the API resource configuration.
     *
     * @param  ApiResource|string|array<int, ApiColumn>  $resource
     */
    public function apiResource(ApiResource|string|array $resource): static
    {
        $this->apiEnabled = true;

        if ($resource instanceof ApiResource) {
            $this->apiResource = $resource;
        } elseif (is_string($resource)) {
            // Assume it's a class name
            $this->apiResource = new $resource;
        } else {
            // It's an array of ApiColumns
            $this->apiColumns = $resource;
        }

        return $this;
    }

    /**
     * Set the API endpoint.
     */
    public function apiEndpoint(?string $endpoint): static
    {
        $this->apiEndpoint = $endpoint;

        return $this;
    }

    /**
     * Check if API view is enabled.
     */
    public function hasApi(): bool
    {
        return $this->apiEnabled;
    }

    /**
     * Get the API resource.
     */
    public function getApiResource(): ?ApiResource
    {
        // If we have a direct ApiResource, return it
        if ($this->apiResource !== null) {
            return $this->apiResource;
        }

        // If we have API columns, create a resource from them
        if ($this->apiColumns !== null) {
            return ApiResource::make()
                ->columns($this->apiColumns)
                ->endpoint($this->apiEndpoint ?? $this->getDefaultApiEndpoint());
        }

        // If API is enabled but no columns specified, auto-generate from table columns
        if ($this->apiEnabled) {
            return $this->generateApiResourceFromColumns();
        }

        return null;
    }

    /**
     * Get the API columns.
     *
     * @return array<int, ApiColumn>|null
     */
    public function getApiColumns(): ?array
    {
        if ($this->apiColumns !== null) {
            return $this->apiColumns;
        }

        if ($this->apiResource !== null) {
            return $this->apiResource->getColumns();
        }

        return null;
    }

    /**
     * Get the API endpoint.
     */
    public function getApiEndpoint(): ?string
    {
        return $this->apiEndpoint ?? $this->getDefaultApiEndpoint();
    }

    /**
     * Get the default API endpoint based on resource slug.
     */
    protected function getDefaultApiEndpoint(): string
    {
        $slug = $this->resourceSlug ?? 'records';

        return "/api/{$slug}";
    }

    /**
     * Generate an ApiResource from the table columns.
     */
    protected function generateApiResourceFromColumns(): ApiResource
    {
        $apiColumns = [];

        foreach ($this->columns as $column) {
            $apiColumn = ApiColumn::make($column->getName())
                ->label($column->getLabel())
                ->sortable($column->isSortable())
                ->searchable($column->isSearchable());

            $apiColumns[] = $apiColumn;
        }

        return ApiResource::make()
            ->columns($apiColumns)
            ->endpoint($this->apiEndpoint ?? $this->getDefaultApiEndpoint())
            ->paginated($this->paginated)
            ->perPage($this->perPage);
    }

    /**
     * @return array<int, Column>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @return array<int, Filter>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Execute the query and get records with applied filters, search, and sorting.
     */
    protected function getRecords(): array
    {
        if ($this->query === null) {
            return [
                'data' => [],
                'pagination' => [
                    'total' => 0,
                    'per_page' => $this->perPage,
                    'current_page' => 1,
                    'last_page' => 1,
                    'from' => 0,
                    'to' => 0,
                ],
            ];
        }

        $query = call_user_func($this->query);

        // Apply withCount for columns using counts()
        $countsRelations = collect($this->columns)
            ->filter(fn (Column $column) => $column instanceof Columns\TextColumn && $column->getCountsRelation())
            ->map(fn (Column $column) => $column->getCountsRelation())
            ->unique()
            ->toArray();

        if (! empty($countsRelations)) {
            $query->withCount($countsRelations);
        }

        // Eager load relations for columns using dot notation (e.g., 'customer.full_name')
        $relations = collect($this->columns)
            ->map(fn (Column $column) => $column->getName())
            ->filter(fn (string $name) => str_contains($name, '.'))
            ->map(fn (string $name) => explode('.', $name)[0])
            ->unique()
            ->toArray();

        if (! empty($relations)) {
            $query->with($relations);
        }

        // Apply search
        $search = request()->get('search');
        if ($search && $this->searchable) {
            $searchableColumns = collect($this->columns)
                ->filter(fn (Column $column) => $column->isSearchable())
                ->map(fn (Column $column) => $column->getName())
                ->toArray();

            if (count($searchableColumns) > 0) {
                $query->where(function ($q) use ($search, $searchableColumns) {
                    foreach ($searchableColumns as $column) {
                        $q->orWhere($column, 'like', "%{$search}%");
                    }
                });
            }
        }

        // Apply filters
        foreach ($this->filters as $filter) {
            $value = request()->get($filter->getName());
            if ($value !== null) {
                $filter->apply($query, $value);
            }
        }

        // Get active group
        $activeGroup = $this->getActiveGroup();

        // Apply group ordering first (if grouping is active)
        if ($activeGroup && $activeGroup->shouldOrderQuery()) {
            $query->orderBy($activeGroup->getColumn(), 'asc');
        }

        // Apply sorting
        // Use query() instead of get() to avoid conflict with middleware setting 'direction' attribute
        $sortColumn = request()->query('sort', $this->defaultSortColumn);
        $sortDirection = request()->query('direction', $this->defaultSortDirection);

        // Validate sort direction - only accept 'asc' or 'desc'
        if (! in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'asc';
        }

        if ($sortColumn) {
            // Handle sorting by relation columns (e.g., 'customer.first_name')
            if (str_contains($sortColumn, '.')) {
                [$relationName, $relationColumn] = explode('.', $sortColumn, 2);

                // Get the model to determine table names
                $model = $query->getModel();
                $mainTable = $model->getTable();

                // Check if the relation exists on the model
                if (method_exists($model, $relationName)) {
                    $relation = $model->{$relationName}();
                    $relatedModel = $relation->getRelated();
                    $relatedTable = $relatedModel->getTable();

                    // Check if the column exists in the related table (skip virtual/accessor columns)
                    $schema = $query->getConnection()->getSchemaBuilder();
                    $columnExists = $schema->hasColumn($relatedTable, $relationColumn);

                    // Handle virtual columns like 'full_name' by sorting on first real column (e.g., first_name)
                    if (! $columnExists && $relationColumn === 'full_name') {
                        // Try to sort by first_name instead for full_name accessor
                        if ($schema->hasColumn($relatedTable, 'first_name')) {
                            $relationColumn = 'first_name';
                            $columnExists = true;
                        }
                    }

                    if ($columnExists) {
                        // Handle BelongsTo relation
                        if ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
                            $foreignKey = $relation->getForeignKeyName();
                            $ownerKey = $relation->getOwnerKeyName();

                            $query->leftJoin($relatedTable, "{$mainTable}.{$foreignKey}", '=', "{$relatedTable}.{$ownerKey}")
                                ->orderBy("{$relatedTable}.{$relationColumn}", $sortDirection)
                                ->select("{$mainTable}.*");
                        }
                        // Handle HasOne relation
                        elseif ($relation instanceof \Illuminate\Database\Eloquent\Relations\HasOne) {
                            $foreignKey = $relation->getForeignKeyName();
                            $localKey = $relation->getLocalKeyName();

                            $query->leftJoin($relatedTable, "{$mainTable}.{$localKey}", '=', "{$relatedTable}.{$foreignKey}")
                                ->orderBy("{$relatedTable}.{$relationColumn}", $sortDirection)
                                ->select("{$mainTable}.*");
                        }
                    }
                }
            } else {
                $query->orderBy($sortColumn, $sortDirection);
            }
        }

        // Get per_page from request or use default
        $perPage = request()->get('per_page', $this->perPage);

        // When grouping is active, adjust pagination
        $shouldPaginate = $this->paginated;
        if ($activeGroup && ! request()->has('per_page')) {
            if ($this->groupedPerPage === -1) {
                // Explicitly disable pagination when grouping (use with caution - may timeout)
                $shouldPaginate = false;
            } elseif ($this->groupedPerPage !== null && $this->groupedPerPage > 0) {
                // Use custom per-page for grouped view
                $perPage = $this->groupedPerPage;
            } else {
                // Default: use higher per-page when grouping to show more records per page
                $perPage = 100;
            }
        }

        // Paginate or get all
        if ($shouldPaginate) {
            $paginator = $query->paginate($perPage);

            return [
                'data' => $this->processRecords($paginator->items(), $activeGroup),
                'pagination' => [
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
                'activeGroup' => $activeGroup?->getColumn(),
            ];
        }

        return [
            'data' => $this->processRecords($query->get()->all(), $activeGroup),
            'pagination' => null,
            'activeGroup' => $activeGroup?->getColumn(),
        ];
    }

    /**
     * Get a value from an array using dot notation.
     */
    protected function getValueByDotNotation(array $array, string $key): mixed
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        if (! str_contains($key, '.')) {
            return null;
        }

        $segments = explode('.', $key);
        $value = $array;

        foreach ($segments as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Process records to add column metadata (icons, colors, sizes, descriptions) and record actions
     */
    protected function processRecords(array $records, ?Grouping\Group $activeGroup = null): array
    {
        return array_map(function ($record) use ($activeGroup) {
            $recordArray = is_array($record) ? $record : $record->toArray();

            // Ensure deleted_at is included for soft-deletable models (needed for action visibility)
            if (is_object($record) && method_exists($record, 'trashed')) {
                $recordArray['deleted_at'] = $record->deleted_at;
            }

            // Add metadata arrays
            $recordArray['_icons'] = [];
            $recordArray['_colors'] = [];
            $recordArray['_sizes'] = [];
            $recordArray['_descriptions'] = [];

            foreach ($this->columns as $column) {
                $columnName = $column->getName();

                // Check if column has a custom getStateUsing callback
                if ($column->hasGetStateUsing()) {
                    // Use custom state getter to get the value
                    $value = $column->evaluateGetStateUsing($record);
                    // Store the custom value in the record array for frontend access
                    $recordArray[$columnName] = $value;
                } else {
                    // Support dot notation for nested relation data (e.g., 'customer.full_name')
                    $value = $this->getValueByDotNotation($recordArray, $columnName);

                    // If using dot notation, flatten the value into the record array for frontend access
                    if (str_contains($columnName, '.') && $value !== null) {
                        $recordArray[$columnName] = $value;
                    }
                }

                // Evaluate icon
                $icon = $column->evaluateIcon($value, $record);
                if ($icon !== null) {
                    $recordArray['_icons'][$columnName] = $icon;
                }

                // Evaluate color
                $color = $column->evaluateColor($value, $record);
                if ($color !== null) {
                    $recordArray['_colors'][$columnName] = $color;
                }

                // Evaluate size
                $size = $column->evaluateSize($value, $record);
                if ($size !== null) {
                    $recordArray['_sizes'][$columnName] = $size;
                }

                // Evaluate description
                $description = $column->evaluateDescription($record);
                if ($description !== null) {
                    $recordArray['_descriptions'][$columnName] = $description;
                }

                // Evaluate formatUsing - apply format callback to transform the value
                if ($column->hasFormatUsing()) {
                    $formattedValue = $column->evaluateFormatUsing($value, $record);
                    if ($formattedValue !== $value) {
                        $recordArray[$columnName] = $formattedValue;
                    }
                }
            }

            // Evaluate card badge color if card has badge color callback
            if ($this->card !== null && $this->card->hasBadgeColorCallback()) {
                $badgeField = $this->card->toInertiaProps()['badgeField'] ?? null;
                if ($badgeField && isset($recordArray[$badgeField])) {
                    $recordArray['_badgeColor'] = $this->card->evaluateBadgeColor($recordArray[$badgeField]);
                }
            }

            // Add record-specific actions with resolved record context
            $recordArray['_actions'] = array_map(function ($action) use ($record) {
                // Clone the action to avoid modifying the original
                $actionClone = clone $action;

                // Let the action auto-configure itself based on record context
                if (method_exists($actionClone, 'resolveRecordContext')) {
                    $recordId = is_object($record) && method_exists($record, 'getKey') ? $record->getKey() : ($record['id'] ?? $record);
                    $actionClone->resolveRecordContext($recordId);
                }

                // Use toArrayWithRecord to evaluate visibility with record context
                if (method_exists($actionClone, 'toArrayWithRecord')) {
                    return $actionClone->toArrayWithRecord($record);
                }

                return method_exists($actionClone, 'toArray') ? $actionClone->toArray() : (method_exists($actionClone, 'toInertiaProps') ? $actionClone->toInertiaProps() : $actionClone);
            }, $this->recordActions);

            // Add group metadata if grouping is active
            if ($activeGroup) {
                $groupColumn = $activeGroup->getColumn();
                $groupValue = $recordArray[$groupColumn] ?? null;
                $recordArray['_group'] = [
                    'column' => $groupColumn,
                    'value' => $groupValue,
                    'title' => $activeGroup->getTitleForRecord($record, $groupValue),
                    'description' => $activeGroup->getDescriptionForRecord($record, $groupValue),
                ];
            }

            // Evaluate and add record URL for clickable rows
            $recordUrl = $this->evaluateRecordUrl($record);
            if ($recordUrl !== null) {
                $recordArray['_url'] = $recordUrl;
            }

            return $recordArray;
        }, $records);
    }

    public function toInertiaProps(): array
    {
        $records = $this->getRecords();

        // Collect filter indicators
        $filterIndicators = [];
        foreach ($this->filters as $filter) {
            $value = request()->get($filter->getName());
            if ($value !== null && $value !== '' && $value !== false) {
                $indicator = $filter->getIndicator($value);
                if ($indicator) {
                    $filterIndicators[] = $indicator;
                }
            }
        }

        return [
            'columns' => array_map(
                fn (Column $column) => $column->toInertiaProps(),
                $this->columns
            ),
            'filters' => array_map(
                function (Filter $filter) {
                    // Pass the table's model to the filter for relationship loading
                    if ($this->model) {
                        $filter->model($this->model);
                    }

                    return $filter->toInertiaProps();
                },
                $this->filters
            ),
            'filterIndicators' => $filterIndicators,
            'actions' => array_map(
                fn ($action) => method_exists($action, 'toInertiaProps') ? $action->toInertiaProps() : $action,
                $this->actions
            ),
            'recordActions' => array_map(
                fn ($action) => method_exists($action, 'toInertiaProps') ? $action->toInertiaProps() : $action,
                $this->recordActions
            ),
            'headerActions' => array_map(
                fn ($action) => method_exists($action, 'toInertiaProps') ? $action->toInertiaProps() : $action,
                $this->headerActions
            ),
            'toolbarActions' => array_map(
                fn ($action) => method_exists($action, 'toInertiaProps') ? $action->toInertiaProps() : $action,
                $this->processToolbarActions()
            ),
            'bulkActions' => array_map(
                fn ($action) => method_exists($action, 'toInertiaProps') ? $action->toInertiaProps() : $action,
                $this->processBulkActions()
            ),
            'searchable' => $this->searchable,
            'searchPlaceholder' => $this->searchPlaceholder ?? 'Search...',
            'paginated' => $this->paginated,
            'perPage' => $this->perPage,
            'paginationPageOptions' => $this->paginationPageOptions,
            'extremePaginationLinks' => $this->extremePaginationLinks,
            'infiniteScroll' => $this->infiniteScroll,
            'striped' => $this->striped,
            'hoverable' => $this->hoverable,
            'filtersLayout' => $this->filtersLayout,
            'reorderable' => $this->reorderableColumn !== null,
            'reorderableColumn' => $this->reorderableColumn,
            'reorderRoute' => $this->reorderRoute ?? ($this->resourceSlug ? route($this->getReorderRouteName()) : null),
            'groups' => array_map(
                fn (Grouping\Group $group) => $group->toInertiaProps(),
                $this->groups
            ),
            'defaultGroup' => $this->defaultGroup,
            'activeGroup' => $records['activeGroup'] ?? null,
            'pollInterval' => $this->pollInterval,
            'defaultSortColumn' => $this->defaultSortColumn,
            'defaultSortDirection' => $this->defaultSortDirection,
            'fixedActions' => $this->fixedActions,
            'records' => $records['data'],
            'pagination' => $records['pagination'],
            // Grid-specific properties
            'card' => $this->card?->toInertiaProps(),
            'cardsPerRow' => $this->cardsPerRow,
            'gridOnly' => $this->gridOnly,
            'emptyState' => [
                'heading' => $this->emptyStateHeading ?? 'No records found',
                'description' => $this->emptyStateDescription,
                'icon' => $this->emptyStateIcon,
            ],
            'queryRoute' => $this->queryRoute ?? request()->url(),
            'resourceSlug' => $this->resourceSlug ?? '',
            'columnExecutionRoute' => $this->resourceSlug ? route($this->getColumnExecutionRouteName(), ['id' => '__ID__']) : null,
            'model' => $this->model,
            // API-specific properties
            'apiEnabled' => $this->apiEnabled,
            'apiResource' => $this->getApiResource()?->toInertiaProps(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toFlutterProps(): array
    {
        return [
            'columns' => array_map(
                fn (Column $column) => $column->toFlutterProps(),
                $this->columns
            ),
            'filters' => array_map(
                fn (Filter $filter) => $filter->toFlutterProps(),
                $this->filters
            ),
            'actions' => $this->actions,
            'searchable' => $this->searchable,
            'paginated' => $this->paginated,
            'perPage' => $this->perPage,
        ];
    }
}
