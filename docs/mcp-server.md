# MCP Server Integration

The Laravilt Tables package can be integrated with MCP (Model Context Protocol) server for AI agent interaction.

## Available Generator Command

### make:table
Generate a new table class.

**Usage:**
```bash
php artisan make:table UserTable
php artisan make:table Admin/UserTable
php artisan make:table UserTable --actions
php artisan make:table UserTable --force
```

**Arguments:**
- `name` (string, required): Table class name (StudlyCase)

**Options:**
- `--actions`: Include row and bulk actions
- `--force`: Overwrite existing file

**Generated Structure (Basic):**
```php
<?php

namespace App\Tables;

use Laravilt\Tables\Table;
use Laravilt\Tables\Columns\TextColumn;
use Laravilt\Tables\Columns\ImageColumn;
use Laravilt\Tables\Columns\BadgeColumn;

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
                    ->searchable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'active' => 'success',
                        'inactive' => 'secondary',
                    ]),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->date()
                    ->sortable(),
            ])
            ->searchable()
            ->paginated()
            ->defaultSort('created_at', 'desc');
    }
}
```

**Generated Structure (With Actions):**
```php
<?php

namespace App\Tables;

use Laravilt\Tables\Table;
use Laravilt\Tables\Columns\TextColumn;
use Laravilt\Actions\Action;

class UserTable
{
    public static function make(): Table
    {
        return Table::make()
            ->columns([
                // Columns...
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

## Column Types Reference

For MCP tools to provide column type information:

- **TextColumn**: Display text with formatting, search, and sorting
- **ImageColumn**: Display images/avatars
- **BadgeColumn**: Display status badges with colors
- **IconColumn**: Display icons with colors
- **SelectColumn**: Inline select dropdown for quick editing
- **ToggleColumn**: Inline toggle switch for boolean values
- **DateColumn**: Formatted dates (uses TextColumn with date formatting)
- **MoneyColumn**: Formatted currency (uses TextColumn with money formatting)
- **NumberColumn**: Formatted numbers (uses TextColumn with numeric formatting)

## Filter Types Reference

For MCP tools to provide filter type information:

- **SelectFilter**: Dropdown select filter (single or multiple)
- **DateRangeFilter**: Date range picker filter
- **SearchFilter**: Full-text search across columns

## Integration Example

MCP server tools should provide:

1. **list-tables** - List all table classes in the application
2. **table-info** - Get details about a specific table class
3. **generate-table** - Generate a new table class with specified columns/actions
4. **list-column-types** - List all available column types
5. **list-filter-types** - List all available filter types

## Security

The MCP server runs with the same permissions as your Laravel application. Ensure:
- Proper file permissions on the app/Tables directory
- Secure configuration of the MCP server
- Limited access to the MCP configuration file
