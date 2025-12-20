<?php

declare(strict_types=1);

namespace Laravilt\Tables\Enums;

enum PaginationMode: string
{
    case Simple = 'simple';
    case Standard = 'standard';
    case Cursor = 'cursor';
}
