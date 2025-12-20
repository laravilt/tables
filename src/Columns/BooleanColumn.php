<?php

namespace Laravilt\Tables\Columns;

/**
 * BooleanColumn - A convenience column for displaying boolean values.
 *
 * This is essentially an IconColumn with boolean mode enabled by default.
 * It displays check/x icons based on the truthiness of the value.
 */
class BooleanColumn extends IconColumn
{
    /**
     * Set up the column with boolean mode enabled by default.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->boolean = true;
    }

    protected function getVueComponent(): string
    {
        return 'IconColumn';
    }

    protected function getFlutterWidget(): string
    {
        return 'LaraviltIconColumn';
    }
}
