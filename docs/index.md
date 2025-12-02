# Laravilt Tables Documentation

Complete table system with columns, filters, sorting, bulk actions, and pagination for Laravilt.

## Table of Contents

1. [Getting Started](#getting-started)
2. [Architecture](#architecture)
3. [Table Generation](#table-generation)
4. [Column Types](#column-types)
5. [Filters](#filters)
6. [Actions](#actions)
7. [API Reference](#api-reference)
8. [MCP Server Integration](mcp-server.md)

## Overview

Laravilt Tables provides a comprehensive data table system with:

- **Multiple Column Types**: Text, image, badge, icon, select, toggle
- **Search & Filter**: Full-text search and advanced filters
- **Sorting**: Multi-column sorting support
- **Bulk Actions**: Select and perform actions on multiple rows
- **Row Actions**: Actions for individual rows
- **Pagination**: Built-in pagination with customizable page sizes
- **Empty States**: Customizable empty state messages
- **Responsive**: Mobile-friendly responsive layouts
- **Inertia Integration**: Seamless Vue 3 integration

## Quick Start

```bash
# Generate a new table class
php artisan make:table UserTable

# Generate with actions
php artisan make:table UserTable --actions

# Use in your controller
use App\Tables\UserTable;

$table = UserTable::make()
    ->query(User::query())
    ->paginated();

return Inertia::render('Users/Index', [
    'table' => $table->toInertiaProps(),
]);
```

## Key Features

### 📊 Column Types
- **TextColumn**: Display text with formatting
- **ImageColumn**: Display images/avatars
- **BadgeColumn**: Status badges with colors
- **IconColumn**: Icons with colors
- **SelectColumn**: Inline select dropdown
- **ToggleColumn**: Inline toggle switch
- **DateColumn**: Formatted dates
- **MoneyColumn**: Formatted currency
- **NumberColumn**: Formatted numbers

### 🔍 Filtering
- Text search across columns
- Select filters
- Date range filters
- Multi-select filters
- Custom filters
- Filter presets

### 🎯 Actions
- Row actions (edit, delete, etc.)
- Bulk actions (bulk delete, export, etc.)
- Action modals
- Action authorization

### 📄 Pagination
- Cursor pagination
- Page number pagination
- Per-page selection
- Custom page sizes

## System Requirements

- PHP 8.3+
- Laravel 12+
- Inertia.js v2+
- Vue 3

## Installation

```bash
composer require laravilt/tables
```

The service provider is auto-discovered and will register automatically.

## Basic Usage

### Creating a Table Class

```php
<?php

namespace App\Tables;

use Laravilt\Tables\Table;
use Laravilt\Tables\Columns\TextColumn;
use Laravilt\Tables\Columns\ImageColumn;
use Laravilt\Tables\Columns\BadgeColumn;
use Laravilt\Tables\Filters\SelectFilter;
use Laravilt\Actions\Action;

class UserTable
{
    public static function make(): Table
    {
        return Table::make()
            ->columns([
                ImageColumn::make('avatar')
                    ->label('Avatar')
                    ->circular()
                    ->size(40),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('role')
                    ->label('Role')
                    ->colors([
                        'admin' => 'danger',
                        'editor' => 'warning',
                        'viewer' => 'secondary',
                    ]),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'active' => 'success',
                        'inactive' => 'secondary',
                    ]),

                TextColumn::make('created_at')
                    ->label('Joined')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Role')
                    ->options([
                        'admin' => 'Admin',
                        'editor' => 'Editor',
                        'viewer' => 'Viewer',
                    ]),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
            ])
            ->actions([
                Action::make('edit')
                    ->label('Edit')
                    ->icon('pencil')
                    ->url(fn ($record) => route('users.edit', $record)),

                Action::make('delete')
                    ->label('Delete')
                    ->icon('trash-2')
                    ->color('danger')
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Action::make('delete')
                    ->label('Delete Selected')
                    ->icon('trash-2')
                    ->color('danger')
                    ->requiresConfirmation(),
            ])
            ->searchable()
            ->paginated()
            ->defaultSort('created_at', 'desc');
    }
}
```

### Using in a Controller

```php
use App\Models\User;
use App\Tables\UserTable;
use Inertia\Inertia;

public function index(Request $request)
{
    $table = UserTable::make()
        ->query(User::query())
        ->search($request->get('search'))
        ->filter($request->get('filters', []))
        ->sort(
            $request->get('sort_column', 'created_at'),
            $request->get('sort_direction', 'desc')
        )
        ->paginated(
            perPage: $request->get('per_page', 15),
            page: $request->get('page', 1)
        );

    return Inertia::render('Users/Index', [
        'table' => $table->toInertiaProps(),
    ]);
}
```

### Using in Vue

```vue
<template>
  <div>
    <Table
      :data="table"
      @search="handleSearch"
      @filter="handleFilter"
      @sort="handleSort"
      @paginate="handlePaginate"
      @action="handleAction"
      @bulk-action="handleBulkAction"
    />
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import Table from '@/components/tables/Table.vue'

const props = defineProps({
  table: Object
})

const handleSearch = (search) => {
  router.get('/users', { search }, { preserveState: true })
}

const handleFilter = (filters) => {
  router.get('/users', { filters }, { preserveState: true })
}

const handleSort = (column, direction) => {
  router.get('/users', {
    sort_column: column,
    sort_direction: direction
  }, { preserveState: true })
}

const handlePaginate = (page) => {
  router.get('/users', { page }, { preserveState: true })
}
</script>
```

## Column Types Reference

### TextColumn

```php
TextColumn::make('name')
    ->label('Name')
    ->searchable()
    ->sortable()
    ->limit(50)
    ->wrap()
    ->description(fn ($record) => $record->email)
    ->copyable()
    ->color(fn ($value) => $value ? 'primary' : null);
```

### ImageColumn

```php
ImageColumn::make('avatar')
    ->label('Avatar')
    ->circular()
    ->size(40)
    ->defaultImageUrl('/images/default-avatar.png');
```

### BadgeColumn

```php
BadgeColumn::make('status')
    ->label('Status')
    ->colors([
        'active' => 'success',
        'pending' => 'warning',
        'inactive' => 'secondary',
        'cancelled' => 'danger',
    ]);
```

### IconColumn

```php
IconColumn::make('verified')
    ->label('Verified')
    ->boolean()
    ->trueIcon('check-circle')
    ->falseIcon('x-circle')
    ->trueColor('success')
    ->falseColor('secondary');
```

### SelectColumn

```php
SelectColumn::make('status')
    ->label('Status')
    ->options([
        'active' => 'Active',
        'inactive' => 'Inactive',
    ])
    ->disableOptionWhen(fn ($value) => $value === 'archived');
```

### ToggleColumn

```php
ToggleColumn::make('is_active')
    ->label('Active')
    ->onUpdateAction(fn ($record, $value) => $record->update(['is_active' => $value]));
```

### DateColumn

```php
TextColumn::make('created_at')
    ->label('Created')
    ->date('M j, Y')
    ->sortable();

TextColumn::make('updated_at')
    ->label('Last Updated')
    ->since() // "2 hours ago"
    ->sortable();
```

### MoneyColumn

```php
TextColumn::make('price')
    ->label('Price')
    ->money('USD')
    ->sortable();
```

### NumberColumn

```php
TextColumn::make('views')
    ->label('Views')
    ->numeric(decimalPlaces: 0)
    ->sortable();
```

## Filters

### Select Filter

```php
SelectFilter::make('status')
    ->label('Status')
    ->options([
        'active' => 'Active',
        'inactive' => 'Inactive',
    ])
    ->multiple();
```

### Date Range Filter

```php
DateRangeFilter::make('created_at')
    ->label('Date Range');
```

### Search Filter

```php
Table::make()
    ->searchable()
    ->searchPlaceholder('Search users...')
    ->searchColumns(['name', 'email', 'username']);
```

## Actions

### Row Actions

```php
Action::make('edit')
    ->label('Edit')
    ->icon('pencil')
    ->url(fn ($record) => route('users.edit', $record));

Action::make('impersonate')
    ->label('Impersonate')
    ->icon('user-check')
    ->authorize(fn ($record) => auth()->user()->can('impersonate', $record))
    ->action(fn ($record) => auth()->impersonate($record));
```

### Bulk Actions

```php
Action::make('delete')
    ->label('Delete Selected')
    ->icon('trash-2')
    ->color('danger')
    ->requiresConfirmation()
    ->modalHeading('Delete Users')
    ->modalDescription('Are you sure you want to delete the selected users?')
    ->action(function ($records) {
        foreach ($records as $record) {
            $record->delete();
        }
    });

Action::make('export')
    ->label('Export Selected')
    ->icon('download')
    ->action(function ($records) {
        return Excel::download(new UsersExport($records), 'users.xlsx');
    });
```

## Pagination

### Basic Pagination

```php
Table::make()
    ->paginated()
    ->perPage(15)
    ->perPageOptions([10, 25, 50, 100]);
```

### Cursor Pagination

```php
Table::make()
    ->cursorPaginated()
    ->perPage(15);
```

## Generator Command

```bash
# Generate a basic table
php artisan make:table UserTable

# Generate with actions
php artisan make:table UserTable --actions

# Force overwrite existing file
php artisan make:table UserTable --force
```

## Best Practices

1. **Use Table Classes**: Create dedicated table classes for reusability
2. **Add Search**: Enable search for better user experience
3. **Optimize Queries**: Use eager loading to prevent N+1 queries
4. **Add Filters**: Provide filters for large datasets
5. **Limit Columns**: Don't show too many columns at once
6. **Sort Smartly**: Set sensible default sorting
7. **Handle Empty States**: Show helpful messages when no data
8. **Authorize Actions**: Always check permissions for actions

## Examples

### Product Table

```php
class ProductTable
{
    public static function make(): Table
    {
        return Table::make()
            ->columns([
                ImageColumn::make('image')
                    ->size(50),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sku')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('price')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),

                BadgeColumn::make('status')
                    ->colors([
                        'active' => 'success',
                        'draft' => 'secondary',
                        'archived' => 'danger',
                    ]),

                TextColumn::make('created_at')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'draft' => 'Draft',
                        'archived' => 'Archived',
                    ]),

                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name'),

                DateRangeFilter::make('created_at')
                    ->label('Created Date'),
            ])
            ->actions([
                Action::make('edit')
                    ->icon('pencil')
                    ->url(fn ($record) => route('products.edit', $record)),

                Action::make('duplicate')
                    ->icon('copy')
                    ->action(fn ($record) => $record->replicate()->save()),

                Action::make('delete')
                    ->icon('trash-2')
                    ->color('danger')
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Action::make('publish')
                    ->label('Publish Selected')
                    ->action(fn ($records) => $records->each->update(['status' => 'active'])),

                Action::make('delete')
                    ->label('Delete Selected')
                    ->color('danger')
                    ->requiresConfirmation(),
            ])
            ->searchable()
            ->paginated()
            ->perPage(25)
            ->defaultSort('created_at', 'desc');
    }
}
```

## Support

- GitHub Issues: github.com/laravilt/tables
- Documentation: docs.laravilt.com
- Discord: discord.laravilt.com
