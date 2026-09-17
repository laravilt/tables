<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Laravilt\Tables\Columns\TextColumn;
use Laravilt\Tables\Table;

class DateFormattingEvent extends Model
{
    protected $table = 'events';

    protected $guarded = [];

    protected $casts = [
        'starts_at' => 'datetime',
    ];
}

beforeEach(function () {
    Schema::create('events', function (Blueprint $table) {
        $table->id();
        $table->dateTime('starts_at')->nullable();
        $table->string('legacy_date')->nullable();
        $table->timestamps();
    });

    Carbon::setTestNow('2026-02-03 12:00:00');

    DateFormattingEvent::create(['starts_at' => '2026-02-01 09:05:00', 'legacy_date' => '2026-02-01']);
});

afterEach(fn () => Carbon::setTestNow());

function firstEventRecord(array $columns): array
{
    return Table::make()
        ->columns($columns)
        ->query(fn () => DateFormattingEvent::query())
        ->toInertiaProps()['records'][0];
}

it('formats date columns with the given PHP format', function () {
    $record = firstEventRecord([
        TextColumn::make('starts_at')->date('M d, Y'),
    ]);

    expect($record['_formatted']['starts_at'])->toBe('Feb 01, 2026');
});

it('formats dateTime columns with the given PHP format', function () {
    $record = firstEventRecord([
        TextColumn::make('starts_at')->dateTime('d/m/Y H:i'),
    ]);

    expect($record['_formatted']['starts_at'])->toBe('01/02/2026 09:05');
});

it('uses the default formats of date() and dateTime()', function () {
    $record = firstEventRecord([
        TextColumn::make('starts_at')->dateTime(),
        TextColumn::make('legacy_date')->date(),
    ]);

    expect($record['_formatted'])->toBe([
        'starts_at' => '2026-02-01 09:05:00',
        'legacy_date' => '2026-02-01',
    ]);
});

it('formats since() columns as a relative time', function () {
    $record = firstEventRecord([
        TextColumn::make('starts_at')->since(),
    ]);

    expect($record['_formatted']['starts_at'])->toBe('2 days ago');
});

it('formats dates in the app timezone', function () {
    config()->set('app.timezone', 'Asia/Baghdad');

    $column = TextColumn::make('starts_at')->dateTime('Y-m-d H:i');

    expect($column->formatDateState('2026-02-01T22:30:00.000000Z'))->toBe('2026-02-02 01:30');
});

it('leaves the raw attribute untouched so forms keep a parsable value', function () {
    $record = firstEventRecord([
        TextColumn::make('legacy_date')->date('M d, Y'),
    ]);

    expect($record['legacy_date'])->toBe('2026-02-01')
        ->and($record['_formatted']['legacy_date'])->toBe('Feb 01, 2026');
});

it('skips empty and unparsable states', function () {
    DateFormattingEvent::query()->update(['starts_at' => null, 'legacy_date' => 'not a date']);

    $record = firstEventRecord([
        TextColumn::make('starts_at')->date('M d, Y'),
        TextColumn::make('legacy_date')->date('M d, Y'),
    ]);

    expect($record['_formatted'])->toBe([]);
});

it('lets formatStateUsing take precedence over the date format', function () {
    $record = firstEventRecord([
        TextColumn::make('legacy_date')->date('M d, Y')->formatStateUsing(fn ($state) => 'custom '.$state),
    ]);

    expect($record['_formatted'])->toBe([])
        ->and($record['legacy_date'])->toBe('custom 2026-02-01');
});

it('does not format columns without date formatting', function () {
    $record = firstEventRecord([
        TextColumn::make('legacy_date'),
    ]);

    expect($record['_formatted'])->toBe([]);
});
