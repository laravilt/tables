<?php

use Laravilt\Tables\Columns\TextColumn;

beforeEach(function () {
    $this->column = TextColumn::make('name');
});

it('can be instantiated with make method', function () {
    $column = TextColumn::make('email');

    expect($column)->toBeInstanceOf(TextColumn::class);
});

it('is not sortable by default', function () {
    expect($this->column->isSortable())->toBeFalse();
});

it('can set sortable', function () {
    $this->column->sortable();

    expect($this->column->isSortable())->toBeTrue();
});

it('can disable sortable', function () {
    $this->column->sortable(false);

    expect($this->column->isSortable())->toBeFalse();
});

it('is not searchable by default', function () {
    expect($this->column->isSearchable())->toBeFalse();
});

it('can set searchable', function () {
    $this->column->searchable();

    expect($this->column->isSearchable())->toBeTrue();
});

it('is toggleable by default', function () {
    $props = $this->column->toInertiaProps();

    expect($props['toggleable'])->toBeTrue();
});

it('can disable toggleable', function () {
    $this->column->toggleable(false);

    $props = $this->column->toInertiaProps();

    expect($props['toggleable'])->toBeFalse();
});

it('can set character limit', function () {
    $this->column->limit(50);

    $props = $this->column->toInertiaProps();

    expect($props['limit'])->toBe(50);
});

it('limit is null by default', function () {
    $props = $this->column->toInertiaProps();

    expect($props['limit'])->toBeNull();
});

it('is not wrapped by default', function () {
    $props = $this->column->toInertiaProps();

    expect($props['wrap'])->toBeFalse();
});

it('can enable wrap', function () {
    $this->column->wrap();

    $props = $this->column->toInertiaProps();

    expect($props['wrap'])->toBeTrue();
});

it('is not copyable by default', function () {
    $props = $this->column->toInertiaProps();

    expect($props['copyable'])->toBeNull();
});

it('can enable copyable', function () {
    $this->column->copyable();

    $props = $this->column->toInertiaProps();

    expect($props['copyable'])->toBe('Copy');
});

it('can set custom copyable message', function () {
    $this->column->copyable('Copied to clipboard!');

    $props = $this->column->toInertiaProps();

    expect($props['copyable'])->toBe('Copied to clipboard!');
});

it('is not badge by default', function () {
    expect($this->column->isBadge())->toBeFalse();
});

it('can enable badge', function () {
    $this->column->badge();

    expect($this->column->isBadge())->toBeTrue();
});

it('can set date format', function () {
    $this->column->date('Y-m-d');

    $props = $this->column->toInertiaProps();

    expect($props['dateFormat'])->toBe('Y-m-d');
});

it('date format is null by default', function () {
    $props = $this->column->toInertiaProps();

    expect($props['dateFormat'])->toBeNull();
});

it('can set datetime format', function () {
    $this->column->dateTime('Y-m-d H:i:s');

    $props = $this->column->toInertiaProps();

    expect($props['dateTimeFormat'])->toBe('Y-m-d H:i:s');
});

it('can set money format', function () {
    $this->column->money('USD', 100);

    $props = $this->column->toInertiaProps();

    expect($props['moneyFormat'])->toBeArray()
        ->and($props['moneyFormat']['currency'])->toBe('USD')
        ->and($props['moneyFormat']['divideBy'])->toBe(100);
});

it('can set icon', function () {
    $this->column->icon('check-circle');

    $props = $this->column->toInertiaProps();

    expect($props['icon'])->toBe('check-circle');
});

it('can set weight', function () {
    $this->column->weight('bold');

    $props = $this->column->toInertiaProps();

    expect($props['weight'])->toBe('bold');
});

it('can set color as string', function () {
    $this->column->color('primary');

    $record = (object) ['name' => 'John', 'status' => 'active'];
    $color = $this->column->evaluateColor('John', $record);

    expect($color)->toBe('primary');
});

it('can set color as closure', function () {
    $this->column->color(fn ($state) => $state === 'active' ? 'success' : 'danger');

    $color = $this->column->evaluateColor('active', null);

    expect($color)->toBe('success');
});

it('is not html by default', function () {
    $props = $this->column->toInertiaProps();

    expect($props['html'])->toBeFalse();
});

it('can enable html', function () {
    $this->column->html();

    $props = $this->column->toInertiaProps();

    expect($props['html'])->toBeTrue();
});

it('converts to inertia props with correct structure', function () {
    $column = TextColumn::make('email')
        ->label('Email Address')
        ->sortable()
        ->searchable()
        ->copyable()
        ->badge()
        ->limit(100)
        ->wrap();

    $props = $column->toInertiaProps();

    expect($props)->toHaveKey('component')
        ->and($props)->toHaveKey('name')
        ->and($props)->toHaveKey('label')
        ->and($props)->toHaveKey('sortable')
        ->and($props)->toHaveKey('searchable')
        ->and($props)->toHaveKey('copyable')
        ->and($props)->toHaveKey('badge')
        ->and($props)->toHaveKey('limit')
        ->and($props)->toHaveKey('wrap')
        ->and($props['component'])->toBe('TextColumn')
        ->and($props['name'])->toBe('email')
        ->and($props['label'])->toBe('Email Address')
        ->and($props['sortable'])->toBeTrue()
        ->and($props['searchable'])->toBeTrue()
        ->and($props['copyable'])->toBe('Copy')
        ->and($props['badge'])->toBeTrue()
        ->and($props['limit'])->toBe(100)
        ->and($props['wrap'])->toBeTrue();
});

it('can chain multiple methods', function () {
    $column = TextColumn::make('price')
        ->label('Price')
        ->money('EUR', 1)
        ->sortable()
        ->copyable('Price copied!')
        ->weight('semibold');

    $props = $column->toInertiaProps();

    expect($props['label'])->toBe('Price')
        ->and($props['sortable'])->toBeTrue()
        ->and($props['copyable'])->toBe('Price copied!')
        ->and($props['moneyFormat'])->toBeArray()
        ->and($props['weight'])->toBe('semibold');
});
