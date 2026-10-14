<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravilt\Forms\Components\DatePicker;
use Laravilt\Forms\Components\TextInput;
use Laravilt\Forms\Components\TimePicker;
use Laravilt\Tables\Columns\TextColumn;
use Laravilt\Tables\Enums\FiltersLayout;
use Laravilt\Tables\Filters\Filter;
use Laravilt\Tables\Filters\SelectFilter;
use Laravilt\Tables\Filters\TernaryFilter;
use Laravilt\Tables\Filters\TrashedFilter;
use Laravilt\Tables\Table;

class FilterStateEvent extends Model
{
    use SoftDeletes;

    protected $table = 'filter_state_events';

    protected $guarded = [];
}

beforeEach(function () {
    Schema::create('filter_state_events', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->string('status')->default('draft');
        $table->boolean('featured')->default(false);
        $table->dateTime('starts_at')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    FilterStateEvent::create(['title' => 'Early', 'status' => 'draft', 'starts_at' => '2026-01-10 09:00:00']);
    FilterStateEvent::create(['title' => 'Mid', 'status' => 'live', 'starts_at' => '2026-02-10 10:30:00']);
    FilterStateEvent::create(['title' => 'Late', 'status' => 'live', 'starts_at' => '2026-03-10 18:00:00']);
});

function eventsTable(array $filters): Table
{
    return Table::make()
        ->columns([TextColumn::make('title')])
        ->filters($filters)
        ->query(fn () => FilterStateEvent::query());
}

function eventTitles(Table $table): array
{
    return collect($table->toInertiaProps()['records'])->pluck('title')->sort()->values()->all();
}

// ---- #22 filtersLayout -----------------------------------------------------

it('serializes sidebar as the default filters layout', function () {
    expect(Table::make()->toInertiaProps()['filtersLayout'])->toBe('sidebar');
});

it('serializes the documented filters layout values', function (string $layout) {
    expect(Table::make()->filtersLayout($layout)->toInertiaProps()['filtersLayout'])->toBe($layout);
})->with(['sidebar', 'dropdown', 'above_table']);

it('accepts the FiltersLayout enum', function () {
    $table = Table::make()->filtersLayout(FiltersLayout::AboveTable);

    expect($table->toInertiaProps()['filtersLayout'])->toBe('above_table');
});

it('normalizes Filament style aliases', function (string $alias, string $expected) {
    expect(Table::make()->filtersLayout($alias)->toInertiaProps()['filtersLayout'])->toBe($expected);
})->with([
    ['above-table', 'above_table'],
    ['aboveTable', 'above_table'],
    ['above_content', 'above_table'],
    ['above-content', 'above_table'],
    ['AboveContentCollapsible', 'above_table'],
    ['modal', 'sidebar'],
    ['DROPDOWN', 'dropdown'],
]);

it('accepts Filament style enum case aliases', function () {
    expect(FiltersLayout::AboveContent)->toBe(FiltersLayout::AboveTable)
        ->and(FiltersLayout::Modal)->toBe(FiltersLayout::Sidebar);
});

it('rejects unknown filters layouts', function () {
    Table::make()->filtersLayout('nowhere');
})->throws(InvalidArgumentException::class);

// ---- #23 multi field filter forms -------------------------------------------

function dateTimeFilter(): Filter
{
    return Filter::make('start_datetime')
        ->form([
            DatePicker::make('start_date')->label('Start date'),
            TimePicker::make('start_time')->label('Start time'),
        ])
        ->query(function ($query, array $data) {
            return $query
                ->when($data['start_date'] ?? null, fn ($q, $d) => $q->whereDate('starts_at', '>=', $d))
                ->when($data['start_time'] ?? null, fn ($q, $t) => $q->whereTime('starts_at', '>=', $t));
        });
}

it('serializes every field of a filter form', function () {
    $props = dateTimeFilter()->toInertiaProps();

    expect($props['formFields'])->toHaveCount(2)
        ->and(collect($props['formFields'])->pluck('name')->all())->toBe(['start_date', 'start_time'])
        ->and($props['hasFormData'])->toBeTrue();
});

it('keeps a single field schema keyed by the filter name', function () {
    $props = Filter::make('title')->schema(TextInput::make('title'))->toInertiaProps();

    expect($props['hasFormData'])->toBeFalse();
});

it('treats a one field form named like the filter as a scalar filter', function () {
    $props = Filter::make('title')->form([TextInput::make('title')])->toInertiaProps();

    expect($props['hasFormData'])->toBeFalse();
});

it('passes the full form data array to the filter query', function () {
    request()->merge(['start_datetime' => ['start_date' => '2026-02-01', 'start_time' => '']]);

    expect(eventTitles(eventsTable([dateTimeFilter()])))->toBe(['Late', 'Mid']);

    request()->merge(['start_datetime' => ['start_date' => '2026-02-01', 'start_time' => '12:00']]);

    expect(eventTitles(eventsTable([dateTimeFilter()])))->toBe(['Late']);
});

it('does not apply a form filter whose fields are all empty', function () {
    request()->merge(['start_datetime' => ['start_date' => '', 'start_time' => null]]);

    expect(eventTitles(eventsTable([dateTimeFilter()])))->toHaveCount(3);
});

it('resolves a closure form schema', function () {
    $filter = Filter::make('range')->form(fn () => [TextInput::make('from'), TextInput::make('to')]);

    expect($filter->toInertiaProps()['formFields'])->toHaveCount(2);
});

// ---- #24 filter state hydration ---------------------------------------------

it('exposes the filter state from the request', function () {
    request()->merge([
        'status' => 'live',
        'featured' => 'true',
        'trashed' => 'only',
        'start_datetime' => ['start_date' => '2026-02-01', 'start_time' => ''],
    ]);

    $table = eventsTable([
        SelectFilter::make('status')->options(['draft' => 'Draft', 'live' => 'Live']),
        TernaryFilter::make('featured'),
        TrashedFilter::make(),
        dateTimeFilter(),
        SelectFilter::make('other')->options(['a' => 'A']),
    ]);

    expect($table->toInertiaProps()['filterState'])->toBe([
        'status' => 'live',
        'featured' => 'true',
        'trashed' => 'only',
        'start_datetime' => ['start_date' => '2026-02-01'],
    ]);
});

it('exposes an empty filter state when the request has no filters', function () {
    $table = eventsTable([SelectFilter::make('status')->options(['live' => 'Live'])]);

    expect($table->toInertiaProps()['filterState'])->toBe([]);
});

it('exposes multiple select state as a list', function () {
    request()->merge(['status' => ['draft', 'live']]);

    $table = eventsTable([SelectFilter::make('status')->multiple()->options(['draft' => 'Draft', 'live' => 'Live'])]);

    expect($table->toInertiaProps()['filterState'])->toBe(['status' => ['draft', 'live']]);
});
