<?php

namespace Laravilt\Tables\Columns;

use Closure;

class BadgeColumn extends TextColumn
{
    protected array $colors = [];

    protected ?Closure $colorsCallback = null;

    /**
     * Set up the column with badge enabled by default.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->badge = true;
    }

    /**
     * Set the badge colors based on state values.
     *
     * @param  array|Closure  $colors  Array of colors keyed by state value or a closure
     *                                 Example: ['draft' => 'gray', 'reviewing' => 'warning', 'published' => 'success']
     *                                 Or: ['primary', 'success' => 'active', 'danger' => 'inactive']
     */
    public function colors(array|Closure $colors): static
    {
        if ($colors instanceof Closure) {
            $this->colorsCallback = $colors;
        } else {
            $this->colors = $colors;
        }

        return $this;
    }

    public function getColors(): array
    {
        return $this->colors;
    }

    /**
     * Evaluate the color for a given state value.
     */
    public function evaluateColor(mixed $state, mixed $record = null): ?string
    {
        // First check if there's a direct color callback from parent
        $parentColor = parent::evaluateColor($state, $record);
        if ($parentColor !== null) {
            return $parentColor;
        }

        // Then check colors callback
        if ($this->colorsCallback) {
            return ($this->colorsCallback)($state, $record);
        }

        // Finally check colors array
        if (! empty($this->colors)) {
            foreach ($this->colors as $color => $condition) {
                // Handle both ['success' => 'active'] and ['primary'] formats
                if (is_int($color)) {
                    // Default color (no condition)
                    if ($condition === 'primary' || $condition === 'secondary' || $condition === 'success' ||
                        $condition === 'warning' || $condition === 'danger' || $condition === 'info' ||
                        $condition === 'gray') {
                        return $condition;
                    }
                } elseif ($state === $condition) {
                    return $color;
                }
            }
        }

        return 'gray';
    }

    protected function getVueProps(): array
    {
        return [
            ...parent::getVueProps(),
            'colors' => $this->colors,
        ];
    }

    protected function getFlutterWidgetProps(): array
    {
        return [
            ...parent::getFlutterWidgetProps(),
            'colors' => $this->colors,
        ];
    }
}
