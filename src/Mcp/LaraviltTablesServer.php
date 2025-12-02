<?php

namespace Laravilt\Tables\Mcp;

use Laravel\Mcp\Server;
use Laravilt\Tables\Mcp\Tools\GenerateTableTool;
use Laravilt\Tables\Mcp\Tools\SearchDocsTool;

class LaraviltTablesServer extends Server
{
    protected string $name = 'Laravilt Tables';

    protected string $version = '1.0.0';

    protected string $instructions = <<<'MARKDOWN'
        This server provides data table capabilities for Laravilt projects.

        You can:
        - Generate new table classes with columns
        - Generate tables with actions
        - Search tables documentation
        - Access information about column types, filters, and actions

        Tables support search, filters, sorting, bulk actions, and pagination.
    MARKDOWN;

    protected array $tools = [
        GenerateTableTool::class,
        SearchDocsTool::class,
    ];
}
