<?php

use Laravilt\Tables\Columns\TextColumn;
use Laravilt\Tables\Filters\SelectFilter;
use Laravilt\Tables\Table;

beforeEach(function () {
    $this->table = Table::make();
});

it('can be instantiated with make method', function () {
    $table = Table::make();

    expect($table)->toBeInstanceOf(Table::class);
});

it('can set columns', function () {
    $columns = [
        TextColumn::make('name')->label('Name'),
        TextColumn::make('email')->label('Email'),
    ];

    $this->table->columns($columns);

    expect($this->table->getColumns())->toHaveCount(2)
        ->and($this->table->getColumns()[0])->toBeInstanceOf(TextColumn::class)
        ->and($this->table->getColumns()[1])->toBeInstanceOf(TextColumn::class);
});

it('can set filters', function () {
    $filters = [
        SelectFilter::make('status')->options(['active' => 'Active', 'inactive' => 'Inactive']),
        SelectFilter::make('role')->options(['admin' => 'Admin', 'user' => 'User']),
    ];

    $this->table->filters($filters);

    expect($this->table->getFilters())->toHaveCount(2)
        ->and($this->table->getFilters()[0])->toBeInstanceOf(SelectFilter::class);
});

it('is searchable by default', function () {
    $props = $this->table->toInertiaProps();

    expect($props['searchable'])->toBeTrue();
});

it('can disable searchable', function () {
    $this->table->searchable(false);

    $props = $this->table->toInertiaProps();

    expect($props['searchable'])->toBeFalse();
});

it('has default search placeholder', function () {
    $props = $this->table->toInertiaProps();

    expect($props['searchPlaceholder'])->toBe('Search...');
});

it('can set custom search placeholder', function () {
    $this->table->searchPlaceholder('Search users...');

    $props = $this->table->toInertiaProps();

    expect($props['searchPlaceholder'])->toBe('Search users...');
});

it('is paginated by default', function () {
    $props = $this->table->toInertiaProps();

    expect($props['paginated'])->toBeTrue();
});

it('can disable pagination', function () {
    $this->table->paginated(false);

    $props = $this->table->toInertiaProps();

    expect($props['paginated'])->toBeFalse();
});

it('has default per page value', function () {
    $props = $this->table->toInertiaProps();

    expect($props['perPage'])->toBe(12);
});

it('can set custom per page value', function () {
    $this->table->perPage(25);

    $props = $this->table->toInertiaProps();

    expect($props['perPage'])->toBe(25);
});

it('can set default sort column and direction', function () {
    $this->table->defaultSort('name', 'desc');

    $props = $this->table->toInertiaProps();

    expect($props['defaultSortColumn'])->toBe('name')
        ->and($props['defaultSortDirection'])->toBe('desc');
});

it('default sort direction is asc', function () {
    $this->table->defaultSort('email');

    $props = $this->table->toInertiaProps();

    expect($props['defaultSortDirection'])->toBe('asc');
});

it('converts to inertia props with all table properties', function () {
    $this->table
        ->columns([TextColumn::make('name')])
        ->filters([SelectFilter::make('status')->options(['active' => 'Active'])])
        ->searchable()
        ->searchPlaceholder('Find records...')
        ->paginated()
        ->perPage(15)
        ->defaultSort('created_at', 'desc');

    $props = $this->table->toInertiaProps();

    expect($props)->toHaveKey('columns')
        ->and($props)->toHaveKey('filters')
        ->and($props)->toHaveKey('searchable')
        ->and($props)->toHaveKey('searchPlaceholder')
        ->and($props)->toHaveKey('paginated')
        ->and($props)->toHaveKey('perPage')
        ->and($props)->toHaveKey('defaultSortColumn')
        ->and($props)->toHaveKey('defaultSortDirection')
        ->and($props['columns'])->toBeArray()
        ->and($props['filters'])->toBeArray()
        ->and($props['searchPlaceholder'])->toBe('Find records...')
        ->and($props['perPage'])->toBe(15)
        ->and($props['defaultSortColumn'])->toBe('created_at')
        ->and($props['defaultSortDirection'])->toBe('desc');
});

it('can set record actions', function () {
    $actions = [
        ['name' => 'edit', 'label' => 'Edit'],
        ['name' => 'delete', 'label' => 'Delete'],
    ];

    $this->table->recordActions($actions);

    expect($this->table->getRecordActions())->toHaveCount(2);
});

it('can set toolbar actions', function () {
    $actions = [
        ['name' => 'export', 'label' => 'Export'],
        ['name' => 'import', 'label' => 'Import'],
    ];

    $this->table->toolbarActions($actions);

    expect($this->table->getToolbarActions())->toHaveCount(2);
});

it('can chain multiple methods', function () {
    $table = Table::make()
        ->columns([TextColumn::make('name')])
        ->filters([SelectFilter::make('status')->options(['active' => 'Active'])])
        ->searchable(false)
        ->paginated(false)
        ->perPage(50)
        ->defaultSort('id', 'asc');

    $props = $table->toInertiaProps();

    expect($props['searchable'])->toBeFalse()
        ->and($props['paginated'])->toBeFalse()
        ->and($props['perPage'])->toBe(50)
        ->and($props['defaultSortColumn'])->toBe('id');
});
