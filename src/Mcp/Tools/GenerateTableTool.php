<?php

namespace Laravilt\Tables\Mcp\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GenerateTableTool extends Tool
{
    protected string $description = 'Generate a new table class with columns, filters, and actions';

    public function handle(Request $request): Response
    {
        $name = $request->string('name');

        $command = 'php '.base_path('artisan').' make:table "'.$name.'" --no-interaction';

        if ($request->boolean('actions', false)) {
            $command .= ' --actions';
        }

        if ($request->boolean('force', false)) {
            $command .= ' --force';
        }

        exec($command, $output, $returnCode);

        if ($returnCode === 0) {
            $response = "✅ Table '{$name}' created successfully!\n\n";
            $response .= "📖 Location: app/Tables/{$name}.php\n\n";
            $response .= "📦 Available column types: TextColumn, ImageColumn, BadgeColumn, IconColumn, SelectColumn, ToggleColumn\n";

            return Response::text($response);
        } else {
            return Response::text('❌ Failed to create table: '.implode("\n", $output));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('Table class name in StudlyCase (e.g., "UserTable")')
                ->required(),
            'actions' => $schema->boolean()
                ->description('Include row and bulk actions')
                ->default(false),
            'force' => $schema->boolean()
                ->description('Overwrite existing file')
                ->default(false),
        ];
    }
}
