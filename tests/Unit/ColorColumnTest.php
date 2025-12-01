<?php

use Laravilt\Tables\Columns\ColorColumn;

beforeEach(function () {
    $this->column = ColorColumn::make('color');
});

it('can be instantiated with make method', function () {
    $column = ColorColumn::make('status_color');

    expect($column)->toBeInstanceOf(ColorColumn::class);
});

it('is not copyable by default', function () {
    $props = $this->column->toInertiaProps();

    expect($props['copyable'])->toBeFalse();
});

it('can enable copyable', function () {
    $this->column->copyable();

    $props = $this->column->toInertiaProps();

    expect($props['copyable'])->toBeTrue();
});

it('can disable copyable', function () {
    $this->column->copyable(false);

    $props = $this->column->toInertiaProps();

    expect($props['copyable'])->toBeFalse();
});

it('can set copy message', function () {
    $this->column->copyMessage('Color copied!');

    $props = $this->column->toInertiaProps();

    expect($props['copyMessage'])->toBe('Color copied!');
});

it('copy message is null by default', function () {
    $props = $this->column->toInertiaProps();

    expect($props['copyMessage'])->toBeNull();
});

it('can set copy message duration', function () {
    $this->column->copyMessageDuration(3000);

    $props = $this->column->toInertiaProps();

    expect($props['copyMessageDuration'])->toBe(3000);
});

it('copy message duration is null by default', function () {
    $props = $this->column->toInertiaProps();

    expect($props['copyMessageDuration'])->toBeNull();
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

it('can disable wrap', function () {
    $this->column->wrap(false);

    $props = $this->column->toInertiaProps();

    expect($props['wrap'])->toBeFalse();
});

it('converts to inertia props with correct structure', function () {
    $column = ColorColumn::make('theme_color')
        ->label('Theme Color')
        ->copyable()
        ->copyMessage('Theme color copied!')
        ->sortable();

    $props = $column->toInertiaProps();

    expect($props)->toHaveKey('component')
        ->and($props)->toHaveKey('name')
        ->and($props)->toHaveKey('label')
        ->and($props)->toHaveKey('copyable')
        ->and($props)->toHaveKey('copyMessage')
        ->and($props)->toHaveKey('sortable')
        ->and($props['component'])->toBe('ColorColumn')
        ->and($props['name'])->toBe('theme_color')
        ->and($props['label'])->toBe('Theme Color')
        ->and($props['copyable'])->toBeTrue()
        ->and($props['copyMessage'])->toBe('Theme color copied!')
        ->and($props['sortable'])->toBeTrue();
});

it('can chain multiple methods', function () {
    $column = ColorColumn::make('brand_color')
        ->label('Brand Color')
        ->copyable()
        ->copyMessage('Copied!')
        ->copyMessageDuration(2000)
        ->wrap()
        ->sortable();

    $props = $column->toInertiaProps();

    expect($props['label'])->toBe('Brand Color')
        ->and($props['copyable'])->toBeTrue()
        ->and($props['copyMessage'])->toBe('Copied!')
        ->and($props['copyMessageDuration'])->toBe(2000)
        ->and($props['wrap'])->toBeTrue()
        ->and($props['sortable'])->toBeTrue();
});
