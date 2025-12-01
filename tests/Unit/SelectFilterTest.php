<?php

use Laravilt\Tables\Filters\SelectFilter;

beforeEach(function () {
    $this->filter = SelectFilter::make('status');
});

it('can be instantiated with make method', function () {
    $filter = SelectFilter::make('role');

    expect($filter)->toBeInstanceOf(SelectFilter::class);
});

it('can set options', function () {
    $this->filter->options([
        'active' => 'Active',
        'inactive' => 'Inactive',
    ]);

    $props = $this->filter->toInertiaProps();

    expect($props['options'])->toBeArray()
        ->and($props['options'])->toHaveKey('active')
        ->and($props['options'])->toHaveKey('inactive')
        ->and($props['options']['active'])->toBe('Active')
        ->and($props['options']['inactive'])->toBe('Inactive');
});

it('options are empty array by default', function () {
    $props = $this->filter->toInertiaProps();

    expect($props['options'])->toBeArray()
        ->and($props['options'])->toBeEmpty();
});

it('is not multiple by default', function () {
    $props = $this->filter->toInertiaProps();

    expect($props['multiple'])->toBeFalse();
});

it('can enable multiple', function () {
    $this->filter->multiple();

    $props = $this->filter->toInertiaProps();

    expect($props['multiple'])->toBeTrue();
});

it('can disable multiple', function () {
    $this->filter->multiple(false);

    $props = $this->filter->toInertiaProps();

    expect($props['multiple'])->toBeFalse();
});

it('is not searchable by default', function () {
    $props = $this->filter->toInertiaProps();

    expect($props['searchable'])->toBeFalse();
});

it('can enable searchable', function () {
    $this->filter->searchable();

    $props = $this->filter->toInertiaProps();

    expect($props['searchable'])->toBeTrue();
});

it('can disable searchable', function () {
    $this->filter->searchable(false);

    $props = $this->filter->toInertiaProps();

    expect($props['searchable'])->toBeFalse();
});

it('converts to inertia props with correct structure', function () {
    $filter = SelectFilter::make('category')
        ->label('Category')
        ->options([
            'tech' => 'Technology',
            'business' => 'Business',
        ])
        ->multiple()
        ->searchable();

    $props = $filter->toInertiaProps();

    expect($props)->toHaveKey('name')
        ->and($props)->toHaveKey('label')
        ->and($props)->toHaveKey('options')
        ->and($props)->toHaveKey('multiple')
        ->and($props)->toHaveKey('searchable')
        ->and($props['name'])->toBe('category')
        ->and($props['label'])->toBe('Category')
        ->and($props['options'])->toHaveCount(2)
        ->and($props['multiple'])->toBeTrue()
        ->and($props['searchable'])->toBeTrue();
});

it('can chain multiple methods', function () {
    $filter = SelectFilter::make('tags')
        ->label('Tags')
        ->options(['tag1' => 'Tag 1', 'tag2' => 'Tag 2'])
        ->multiple()
        ->searchable();

    $props = $filter->toInertiaProps();

    expect($props['label'])->toBe('Tags')
        ->and($props['options'])->toHaveCount(2)
        ->and($props['multiple'])->toBeTrue()
        ->and($props['searchable'])->toBeTrue();
});
