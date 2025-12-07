<?php

namespace Laravilt\Tables\Columns;

use Closure;

class IconColumn extends Column
{
    protected string|Closure|null $icon = null;

    protected string|Closure|null $color = null;

    protected string|Closure|null $iconSize = 'large';

    protected bool $boolean = false;

    protected string|Closure|null $trueIcon = null;

    protected string|Closure|null $falseIcon = null;

    protected string|Closure|null $trueColor = null;

    protected string|Closure|null $falseColor = null;

    protected bool $wrap = false;

    public function icon(string|Closure|null $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function color(string|Closure|null $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function iconSize(string|Closure|null $size): static
    {
        $this->iconSize = $size;

        return $this;
    }

    public function boolean(bool|Closure $condition = true): static
    {
        $this->boolean = $condition instanceof Closure ? $condition : (bool) $condition;

        return $this;
    }

    public function trueIcon(string|Closure|null $icon): static
    {
        $this->trueIcon = $icon;

        return $this;
    }

    public function falseIcon(string|Closure|null $icon): static
    {
        $this->falseIcon = $icon;

        return $this;
    }

    public function trueColor(string|Closure|null $color): static
    {
        $this->trueColor = $color;

        return $this;
    }

    public function falseColor(string|Closure|null $color): static
    {
        $this->falseColor = $color;

        return $this;
    }

    public function wrap(bool $condition = true): static
    {
        $this->wrap = $condition;

        return $this;
    }

    protected function getVueComponent(): string
    {
        return 'IconColumn';
    }

    protected function getVueProps(): array
    {
        return [
            ...parent::getVueProps(),
            'boolean' => $this->boolean,
            'wrap' => $this->wrap,
        ];
    }

    protected function getFlutterWidget(): string
    {
        return 'LaraviltIconColumn';
    }

    protected function getFlutterWidgetProps(): array
    {
        return [
            ...parent::getFlutterWidgetProps(),
            'boolean' => $this->boolean,
            'wrap' => $this->wrap,
        ];
    }

    public function evaluateIcon(mixed $state, mixed $record = null): ?string
    {
        if ($this->boolean) {
            $isTruthy = (bool) $state;

            if ($isTruthy && $this->trueIcon) {
                return $this->evaluate($this->trueIcon, ['state' => $state, 'record' => $record]);
            }

            if (! $isTruthy && $this->falseIcon) {
                return $this->evaluate($this->falseIcon, ['state' => $state, 'record' => $record]);
            }

            // Default boolean icons
            return $isTruthy ? 'CheckCircle' : 'XCircle';
        }

        if ($this->icon) {
            return $this->evaluate($this->icon, ['state' => $state, 'record' => $record]);
        }

        // If no explicit icon callback, use the state value as the icon name
        return $state;
    }

    public function evaluateColor(mixed $state, mixed $record = null): ?string
    {
        if ($this->boolean) {
            $isTruthy = (bool) $state;

            if ($isTruthy && $this->trueColor) {
                return $this->evaluate($this->trueColor, ['state' => $state, 'record' => $record]);
            }

            if (! $isTruthy && $this->falseColor) {
                return $this->evaluate($this->falseColor, ['state' => $state, 'record' => $record]);
            }

            // Default boolean colors
            return $isTruthy ? 'success' : 'danger';
        }

        if ($this->color) {
            return $this->evaluate($this->color, ['state' => $state, 'record' => $record]);
        }

        return null;
    }

    public function evaluateIconSize(mixed $state, mixed $record = null): ?string
    {
        return $this->evaluate($this->iconSize, ['state' => $state, 'record' => $record]);
    }

    protected function evaluate(mixed $value, array $parameters = []): mixed
    {
        if ($value instanceof Closure) {
            return $value($parameters['state'] ?? null, $parameters['record'] ?? null);
        }

        return $value;
    }
}
