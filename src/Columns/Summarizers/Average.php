<?php

declare(strict_types=1);

namespace Laravilt\Tables\Columns\Summarizers;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

class Average extends Summarizer
{
    /**
     * Calculate the average of values.
     *
     * @param  Builder|Collection  $query
     */
    public function summarize($query, string $column): mixed
    {
        if ($query instanceof Collection) {
            return $query->avg($column);
        }

        return $query->avg($column);
    }
}
