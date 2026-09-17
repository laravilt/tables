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
