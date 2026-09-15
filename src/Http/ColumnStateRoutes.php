<?php

namespace Laravilt\Tables\Http;

use Illuminate\Support\Facades\Route;
use Laravilt\Panel\Http\Middleware\HandleLocalization;
use Laravilt\Panel\Middleware\IdentifyPanel;
use Laravilt\Panel\Middleware\IdentifyTenant;
use Laravilt\Panel\Panel;
use Laravilt\Panel\PanelRegistry;
use Laravilt\Tables\Http\Controllers\UpdateColumnStateController;

/**
 * Registers the inline column update endpoint (SelectColumn, TextInputColumn, CheckboxColumn,
 * ToggleColumn) under every panel, behind the same middleware stack as the panel's own pages.
 */
class ColumnStateRoutes
{
    public const ROUTE_NAME = 'tables.column.update';

    public static function register(): void
    {
        if (! class_exists(PanelRegistry::class)) {
            return;
        }

        foreach (app(PanelRegistry::class)->all() as $panel) {
            Route::middleware(static::middlewareFor($panel))
                ->prefix($panel->getPath())
                ->name($panel->getId().'.')
                ->group(function () use ($panel) {
                    Route::patch('_tables/{resource}/{record}/column', UpdateColumnStateController::class)
                        ->defaults('laraviltPanel', $panel->getId())
                        ->name(static::ROUTE_NAME);
                });
        }
    }

    /**
     * Mirrors the panel's path-based route middleware: session + panel identification + auth + tenant scoping.
     *
     * @return array<int, string>
     */
    public static function middlewareFor(Panel $panel): array
    {
        $toPanelAuth = fn ($middleware) => $middleware === 'auth' ? 'panel.auth' : $middleware;

        $middleware = array_filter(
            array_map($toPanelAuth, $panel->getMiddleware()),
            fn ($middleware) => $middleware !== 'panel.auth',
        );

        return array_values(array_unique(array_merge(
            $middleware,
            [IdentifyPanel::class.':'.$panel->getId()],
            array_map($toPanelAuth, $panel->getAuthMiddleware()),
            [HandleLocalization::class, IdentifyTenant::class],
        ), SORT_REGULAR));
    }
}
