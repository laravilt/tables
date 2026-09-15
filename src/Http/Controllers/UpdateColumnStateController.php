<?php

namespace Laravilt\Tables\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Laravilt\Panel\PanelRegistry;
use Laravilt\Tables\Columns\Contracts\EditableColumn;
use Laravilt\Tables\Table;

/**
 * Persists a single inline-edited column value.
 *
 * The table is rebuilt from the resource (as the panel's other table endpoints do), so only columns the
 * developer declared as editable can be written, only to their own attribute, validated with their rules,
 * and only when the current user may update the record.
 */
class UpdateColumnStateController
{
    public function __invoke(Request $request, string $resource, string $record): JsonResponse
    {
        $resourceClass = $this->resolveResource($request, $resource);

        $column = $this->resolveColumn($resourceClass, (string) $request->input('column'));

        $model = $this->resolveRecord($resourceClass, $record);

        $this->authorize($resourceClass, $model);

        $name = $column->getName();

        $validated = Validator::make(
            ['value' => $request->input('value')],
            ['value' => $column->getStateValidationRules()],
            [],
            ['value' => method_exists($column, 'getLabel') && $column->getLabel() ? $column->getLabel() : $name],
        )->validate();

        $value = $column->dehydrateState($validated['value'] ?? null);

        if ($before = $column->getBeforeStateUpdated()) {
            $before($model, $name, $value);
        }

        // Only this column's attribute is written, regardless of what else the request contains
        $model->setAttribute($name, $value);
        $model->save();

        if ($after = $column->getAfterStateUpdated()) {
            $after($model, $name, $value);
        }

        return response()->json([
            'column' => $name,
            'state' => $model->getAttribute($name),
        ]);
    }

    /**
     * @return class-string
     */
    protected function resolveResource(Request $request, string $slug): string
    {
        $registry = app(PanelRegistry::class);
        $panelId = $request->route('laraviltPanel');
        $panel = $panelId ? $registry->get($panelId) : $registry->getCurrent();

        foreach ($panel?->getResources() ?? [] as $resourceClass) {
            if ($resourceClass::getSlug() === $slug) {
                return $resourceClass;
            }
        }

        abort(404);
    }

    protected function resolveColumn(string $resourceClass, string $name): EditableColumn
    {
        abort_if($name === '', 422, 'The column field is required.');

        $table = $resourceClass::table(new Table);

        foreach ($table->getColumns() as $column) {
            if ($column->getName() !== $name) {
                continue;
            }

            // Plain display columns (TextColumn, ...) can never be written through this endpoint
            abort_unless($column instanceof EditableColumn, 403, 'This column is not editable.');
            // Relationship paths ("author.name") are display-only: only the record's own attribute is updated
            abort_if(str_contains($name, '.'), 403, 'Relationship columns cannot be edited inline.');
            abort_if($column->isDisabled(), 403, 'This column is disabled.');

            return $column;
        }

        abort(404, 'Column not found.');
    }

    protected function resolveRecord(string $resourceClass, string $key): Model
    {
        // getEloquentQuery() applies the resource's tenant scoping
        $query = method_exists($resourceClass, 'getEloquentQuery')
            ? $resourceClass::getEloquentQuery()
            : $resourceClass::getModel()::query();

        return $query->whereKey($key)->firstOrFail();
    }

    protected function authorize(string $resourceClass, Model $model): void
    {
        // Resource authorization honours $usePolicies (policy "update") or the panel's permission system
        if (method_exists($resourceClass, 'canUpdate')) {
            abort_unless($resourceClass::canUpdate($model), 403);

            return;
        }

        if (Gate::getPolicyFor($model)) {
            Gate::authorize('update', $model);

            return;
        }

        abort_unless(auth()->check(), 403);
    }
}
