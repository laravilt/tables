<?php

use Laravilt\Tables\Columns\IconColumn;

beforeEach(function () {
    $this->column = IconColumn::make('status_icon');
});

it('can be instantiated with make method', function () {
    $column = IconColumn::make('verified');

    expect($column)->toBeInstanceOf(IconColumn::class);
});

it('can set icon', function () {
    $this->column->icon('check-circle');

    $icon = $this->column->evaluateIcon('check-circle', null);

    expect($icon)->toBe('check-circle');
});

it('can set icon as closure', function () {
    $this->column->icon(fn ($state) => $state ? 'check' : 'x');

    $icon = $this->column->evaluateIcon(true, null);

    expect($icon)->toBe('check');
});

it('can set color', function () {
    $this->column->color('success');

    $color = $this->column->evaluateColor('any', null);

    expect($color)->toBe('success');
});

it('can set color as closure', function () {
    $this->column->color(fn ($state) => $state ? 'success' : 'danger');

    $color = $this->column->evaluateColor(true, null);

    expect($color)->toBe('success');
});

it('can set size', function () {
    $this->column->size('large');

    $size = $this->column->evaluateSize('any', null);

    expect($size)->toBe('large');
});

it('default size is large', function () {
    $size = $this->column->evaluateSize('any', null);

    expect($size)->toBe('large');
});

it('can set size as closure', function () {
    $this->column->size(fn ($state) => $state > 100 ? 'large' : 'small');

    $size = $this->column->evaluateSize(150, null);

    expect($size)->toBe('large');
});

it('is not boolean by default', function () {
    $props = $this->column->toInertiaProps();

    expect($props['boolean'])->toBeFalse();
});

it('can enable boolean mode', function () {
    $this->column->boolean();

    $props = $this->column->toInertiaProps();

    expect($props['boolean'])->toBeTrue();
});

it('uses default icons for boolean true', function () {
    $this->column->boolean();

    $icon = $this->column->evaluateIcon(true, null);

    expect($icon)->toBe('CheckCircle');
});

it('uses default icons for boolean false', function () {
    $this->column->boolean();

    $icon = $this->column->evaluateIcon(false, null);

    expect($icon)->toBe('XCircle');
});

it('can set custom true icon', function () {
    $this->column->boolean()->trueIcon('thumbs-up');

    $icon = $this->column->evaluateIcon(true, null);

    expect($icon)->toBe('thumbs-up');
});

it('can set custom false icon', function () {
    $this->column->boolean()->falseIcon('thumbs-down');

    $icon = $this->column->evaluateIcon(false, null);

    expect($icon)->toBe('thumbs-down');
});

it('uses default colors for boolean true', function () {
    $this->column->boolean();

    $color = $this->column->evaluateColor(true, null);

    expect($color)->toBe('success');
});

it('uses default colors for boolean false', function () {
    $this->column->boolean();

    $color = $this->column->evaluateColor(false, null);

    expect($color)->toBe('danger');
});

it('can set custom true color', function () {
    $this->column->boolean()->trueColor('primary');

    $color = $this->column->evaluateColor(true, null);

    expect($color)->toBe('primary');
});

it('can set custom false color', function () {
    $this->column->boolean()->falseColor('gray');

    $color = $this->column->evaluateColor(false, null);

    expect($color)->toBe('gray');
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

it('uses state value as icon name when no icon callback is set', function () {
    $icon = $this->column->evaluateIcon('star', null);

    expect($icon)->toBe('star');
});

it('converts to inertia props with correct structure', function () {
    $column = IconColumn::make('verified')
        ->label('Verified')
        ->boolean()
        ->sortable();

    $props = $column->toInertiaProps();

    expect($props)->toHaveKey('component')
        ->and($props)->toHaveKey('name')
        ->and($props)->toHaveKey('label')
        ->and($props)->toHaveKey('boolean')
        ->and($props)->toHaveKey('sortable')
        ->and($props['component'])->toBe('IconColumn')
        ->and($props['name'])->toBe('verified')
        ->and($props['label'])->toBe('Verified')
        ->and($props['boolean'])->toBeTrue()
        ->and($props['sortable'])->toBeTrue();
});

it('can chain multiple methods', function () {
    $column = IconColumn::make('status')
        ->label('Status')
        ->icon('check')
        ->color('success')
        ->size('medium')
        ->sortable()
        ->wrap();

    $props = $column->toInertiaProps();

    expect($props['label'])->toBe('Status')
        ->and($props['sortable'])->toBeTrue()
        ->and($props['wrap'])->toBeTrue();
});
