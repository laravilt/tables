<?php

declare(strict_types=1);

namespace Laravilt\Tables\Enums;

use InvalidArgumentException;

/**
 * Where a table renders its filters.
 *
 * - Sidebar: a slide-over panel opened from the toolbar (default).
 * - Dropdown: a popover opened from the toolbar.
 * - AboveTable: always visible, rendered in a bar above the records.
 */
enum FiltersLayout: string
{
    case Sidebar = 'sidebar';
    case Dropdown = 'dropdown';
    case AboveTable = 'above_table';

    // Filament style aliases.
    public const AboveContent = self::AboveTable;

    public const AboveContentCollapsible = self::AboveTable;

    public const Modal = self::Sidebar;

    /**
     * Resolve a layout from an enum case or a string such as 'above_table',
     * 'above-table', 'aboveTable' or the Filament names ('above_content', 'modal').
     */
    public static function resolve(self|string $layout): self
    {
        if ($layout instanceof self) {
            return $layout;
        }

        $key = strtolower(str_replace(['-', ' '], '_', preg_replace('/(?<=[a-z])(?=[A-Z])/', '_', trim($layout))));

        return match ($key) {
            'sidebar', 'modal', 'drawer' => self::Sidebar,
            'dropdown' => self::Dropdown,
            'above_table', 'above_content', 'above_content_collapsible' => self::AboveTable,
            default => throw new InvalidArgumentException(
                "Unknown filters layout [{$layout}]. Use 'sidebar', 'dropdown' or 'above_table'."
            ),
        };
    }
}
