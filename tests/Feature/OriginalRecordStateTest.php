<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravilt\Tables\Columns\TextColumn;
use Laravilt\Tables\Table;

class OriginalStateComment extends Model
{
    protected $table = 'comments';

    protected $guarded = [];
}

beforeEach(function () {
    Schema::create('comments', function (Blueprint $table) {
        $table->id();
        $table->string('body');
        $table->string('status')->default('pending');
        $table->timestamps();
    });

    OriginalStateComment::create(['body' => 'Nice', 'status' => 'pending']);
});

it('keeps the original state of attributes a column replaced with a display value', function () {
    $record = Table::make()
        ->columns([
            TextColumn::make('body'),
            TextColumn::make('status')->formatStateUsing(fn ($state) => 'Awaiting review'),
        ])
        ->query(fn () => OriginalStateComment::query())
        ->toInertiaProps()['records'][0];

    expect($record['status'])->toBe('Awaiting review')
        ->and($record['_original'])->toBe(['status' => 'pending']);
});

it('reports no original state when columns leave the attributes untouched', function () {
    $record = Table::make()
        ->columns([
            TextColumn::make('body'),
            TextColumn::make('status'),
        ])
        ->query(fn () => OriginalStateComment::query())
        ->toInertiaProps()['records'][0];

    expect($record['status'])->toBe('pending')
        ->and($record['_original'])->toBe([]);
});

it('never exposes hidden attributes in the original state', function () {
    $model = new class extends Model
    {
        protected $table = 'comments';

        protected $hidden = ['status'];
    };

    $record = Table::make()
        ->columns([
            TextColumn::make('status')->getStateUsing(fn () => 'masked'),
        ])
        ->query(fn () => $model->newQuery())
        ->toInertiaProps()['records'][0];

    expect($record['_original'])->toBe([]);
});

it('does not send the unsanitized markup of html columns as original state', function () {
    OriginalStateComment::query()->update(['body' => '<p>hi</p><script>alert(1)</script>']);

    $record = Table::make()
        ->columns([
            TextColumn::make('body')->html(),
        ])
        ->query(fn () => OriginalStateComment::query())
        ->toInertiaProps()['records'][0];

    expect($record['body'])->toBe('<p>hi</p>')
        ->and($record['_original'])->toBe([]);
});
