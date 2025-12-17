![tables](https://raw.githubusercontent.com/laravilt/tables/master/arts/screenshot.jpg)

# Laravilt Tables

[![Latest Stable Version](https://poser.pugx.org/laravilt/tables/version.svg)](https://packagist.org/packages/laravilt/tables)
[![License](https://poser.pugx.org/laravilt/tables/license.svg)](https://packagist.org/packages/laravilt/tables)
[![Downloads](https://poser.pugx.org/laravilt/tables/d/total.svg)](https://packagist.org/packages/laravilt/tables)
[![Dependabot Updates](https://github.com/laravilt/tables/actions/workflows/dependabot/dependabot-updates/badge.svg)](https://github.com/laravilt/tables/actions/workflows/dependabot/dependabot-updates)
[![PHP Code Styling](https://github.com/laravilt/tables/actions/workflows/fix-php-code-styling.yml/badge.svg)](https://github.com/laravilt/tables/actions/workflows/fix-php-code-styling.yml)
[![Tests](https://github.com/laravilt/tables/actions/workflows/tests.yml/badge.svg)](https://github.com/laravilt/tables/actions/workflows/tests.yml)


Complete table system with columns, filters, sorting, bulk actions, and pagination for Laravilt. Build powerful data tables with search, filters, inline editing, and row/bulk actions.

## Features

- 📊 **9 Column Types** - Text, Image, Badge, Icon, Select, Toggle, Color, Relationship, Custom
- 🔍 **5 Filter Types** - Text, Select, Multi-Select, Boolean, Date Range
- ↕️ **Sorting** - Multi-column sorting with direction control
- ✅ **Bulk Actions** - Select and perform actions on multiple rows
- 📄 **Pagination** - Built-in pagination with customizable page sizes
- 📱 **Responsive** - Mobile-friendly with column visibility control

## Column Types

| Column | Description |
|--------|-------------|
| `TextColumn` | Text display with formatting options |
| `ImageColumn` | Image thumbnails with lightbox |
| `BadgeColumn` | Status badges with colors |
| `IconColumn` | Boolean icons (check/x) |
| `SelectColumn` | Inline select editing |
| `ToggleColumn` | Inline toggle switches |
| `ColorColumn` | Color swatches |
| `RelationshipColumn` | Display related model data |
| `CustomColumn` | Custom Vue component rendering |

## Quick Example

```php
use Laravilt\Tables\Table;
use Laravilt\Tables\Columns\TextColumn;
use Laravilt\Tables\Columns\BadgeColumn;
use Laravilt\Tables\Columns\ToggleColumn;
use Laravilt\Tables\Filters\SelectFilter;
use Laravilt\Tables\Actions\BulkAction;

Table::make()
    ->columns([
        TextColumn::make('name')
            ->searchable()
            ->sortable(),

        BadgeColumn::make('status')
            ->colors([
                'success' => 'active',
                'danger' => 'inactive',
            ]),

        ToggleColumn::make('is_featured'),
    ])
    ->filters([
        SelectFilter::make('status')
            ->options(['active', 'inactive']),
    ])
    ->toolbarActions([
        BulkAction::make('delete')
            ->label('Delete Selected')
            ->action(fn ($records) => $records->each->delete()),
    ]);
```

## Installation

```bash
composer require laravilt/tables
```

## Generator Commands

```bash
php artisan make:table UserTable
php artisan make:table UserTable --actions
```

## Documentation

- **[Complete Documentation](docs/index.md)** - All column types, filters, and actions
- **[MCP Server Guide](docs/mcp-server.md)** - AI agent integration

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
