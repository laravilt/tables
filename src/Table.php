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

    protected int $perPage = 5;

    /**
     * @var array<int>|null
     */
    protected ?array $paginationPageOptions = null;

    protected bool $infiniteScroll = false;

    protected bool $striped = false;

    protected ?Closure $query = null;

    protected ?string $defaultSortColumn = 'id';

    protected string $defaultSortDirection = 'desc';

    protected bool $fixedActions = false;

    // Grid-specific properties
    protected ?Card $card = null;

    protected int $cardsPerRow = 3;

    protected ?string $emptyStateHeading = null;

    protected ?string $emptyStateDescription = null;

    protected ?string $emptyStateIcon = null;

    protected ?string $queryRoute = null;

    protected ?string $resourceSlug = null;

    protected ?string $model = null;

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
     * Enable striped rows for the table.
     */
    public function striped(bool $condition = true): static
    {
        $this->striped = $condition;

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

        // Apply sorting
        $sortColumn = request()->get('sort', $this->defaultSortColumn);
        $sortDirection = request()->get('direction', $this->defaultSortDirection);

        if ($sortColumn) {
            $query->orderBy($sortColumn, $sortDirection);
        }

        // Get per_page from request or use default
        $perPage = request()->get('per_page', $this->perPage);

        // Paginate or get all
        if ($this->paginated) {
            $paginator = $query->paginate($perPage);

            return [
                'data' => $this->processRecords($paginator->items()),
                'pagination' => [
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
            ];
        }

        return [
            'data' => $this->processRecords($query->get()->all()),
            'pagination' => null,
        ];
    }

    /**
     * Process records to add column metadata (icons, colors, sizes, descriptions) and record actions
     */
    protected function processRecords(array $records): array
    {
        return array_map(function ($record) {
            $recordArray = is_array($record) ? $record : $record->toArray();

            // Add metadata arrays
            $recordArray['_icons'] = [];
            $recordArray['_colors'] = [];
            $recordArray['_sizes'] = [];
            $recordArray['_descriptions'] = [];

            foreach ($this->columns as $column) {
                $columnName = $column->getName();
                $value = $recordArray[$columnName] ?? null;

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

                return method_exists($actionClone, 'toArray') ? $actionClone->toArray() : (method_exists($actionClone, 'toInertiaProps') ? $actionClone->toInertiaProps() : $actionClone);
            }, $this->recordActions);

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
                fn (Filter $filter) => $filter->toInertiaProps(),
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
                $this->toolbarActions
            ),
            'bulkActions' => array_map(
                fn ($action) => method_exists($action, 'toInertiaProps') ? $action->toInertiaProps() : $action,
                $this->bulkActions
            ),
            'searchable' => $this->searchable,
            'searchPlaceholder' => $this->searchPlaceholder ?? 'Search...',
            'paginated' => $this->paginated,
            'perPage' => $this->perPage,
            'paginationPageOptions' => $this->paginationPageOptions,
            'infiniteScroll' => $this->infiniteScroll,
            'striped' => $this->striped,
            'defaultSortColumn' => $this->defaultSortColumn,
            'defaultSortDirection' => $this->defaultSortDirection,
            'fixedActions' => $this->fixedActions,
            'records' => $records['data'],
            'pagination' => $records['pagination'],
            // Grid-specific properties
            'card' => $this->card?->toInertiaProps(),
            'cardsPerRow' => $this->cardsPerRow,
            'emptyState' => [
                'heading' => $this->emptyStateHeading ?? 'No records found',
                'description' => $this->emptyStateDescription,
                'icon' => $this->emptyStateIcon,
            ],
            'queryRoute' => $this->queryRoute ?? request()->url(),
            'resourceSlug' => $this->resourceSlug ?? '',
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
