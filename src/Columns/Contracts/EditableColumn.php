<?php

namespace Laravilt\Tables\Columns\Contracts;

use Closure;

/**
 * A column whose state can be edited inline and persisted through the column update endpoint.
 */
interface EditableColumn
{
    public function getName(): string;

    public function isDisabled(): bool;

    /**
     * The developer-supplied validation rules (set through rules()).
     *
     * @return array<int, mixed>
     */
    public function getRules(): array;

    /**
     * The full rule set used to validate an incoming state value: type rules plus getRules().
     *
     * @return array<int, mixed>
     */
    public function getStateValidationRules(): array;

    /**
     * Convert a validated incoming value to what is stored on the model.
     */
    public function dehydrateState(mixed $state): mixed;

    public function getBeforeStateUpdated(): ?Closure;

    public function getAfterStateUpdated(): ?Closure;
}
