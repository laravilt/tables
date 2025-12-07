<?php

namespace Laravilt\Tables\Columns;

use Closure;

class TextColumn extends Column
{
    protected ?int $limit = null;

    protected bool $wrap = false;

    protected ?string $copyable = null;

    protected bool $badge = false;

    protected ?string $dateTimeFormat = null;

    protected ?string $dateFormat = null;

    protected bool $since = false;

    protected ?string $icon = null;

    protected ?string $weight = null;

    protected ?string $moneyFormat = null;

    protected ?Closure $colorCallback = null;

    protected bool $html = false;

    public function limit(?int $characters): static
    {
        $this->limit = $characters;

        return $this;
    }

    public function wrap(bool $condition = true): static
    {
        $this->wrap = $condition;

        return $this;
    }

    public function copyable(bool|string $condition = true): static
    {
        $this->copyable = is_string($condition) ? $condition : ($condition ? 'Copy' : null);

        return $this;
    }

    public function badge(bool $condition = true): static
    {
        $this->badge = $condition;

        return $this;
    }

    public function isBadge(): bool
    {
        return $this->badge;
    }

    public function dateTime(?string $format = 'Y-m-d H:i:s'): static
    {
        $this->dateTimeFormat = $format;

        return $this;
    }

    public function date(?string $format = 'Y-m-d'): static
    {
        $this->dateFormat = $format;

        return $this;
    }

    public function since(bool $condition = true): static
    {
        $this->since = $condition;

        return $this;
    }

    public function money(string $currency = 'USD', int $divideBy = 1): static
    {
        $this->moneyFormat = json_encode(['currency' => $currency, 'divideBy' => $divideBy]);

        return $this;
    }

    public function icon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function weight(?string $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    public function color(string|Closure $color): static
    {
        if ($color instanceof Closure) {
            $this->colorCallback = $color;
        } else {
            $this->colorCallback = fn ($state) => $color;
        }

        return $this;
    }

    public function html(bool $condition = true): static
    {
        $this->html = $condition;

        return $this;
    }

    protected function getVueComponent(): string
    {
        return 'TextColumn';
    }

    protected function getVueProps(): array
    {
        return [
            ...parent::getVueProps(),
            'limit' => $this->limit,
            'wrap' => $this->wrap,
            'copyable' => $this->copyable,
            'badge' => $this->badge,
            'dateTimeFormat' => $this->dateTimeFormat,
            'dateFormat' => $this->dateFormat,
            'icon' => $this->icon,
            'weight' => $this->weight,
            'moneyFormat' => $this->moneyFormat ? json_decode($this->moneyFormat, true) : null,
            'colorCallback' => $this->colorCallback !== null,
            'html' => $this->html,
            'since' => $this->since,
        ];
    }

    protected function getFlutterWidget(): string
    {
        return 'LaraviltTextColumn';
    }

    protected function getFlutterWidgetProps(): array
    {
        return [
            ...parent::getFlutterWidgetProps(),
            'limit' => $this->limit,
            'wrap' => $this->wrap,
            'copyable' => $this->copyable !== null,
            'badge' => $this->badge,
            'dateTimeFormat' => $this->dateTimeFormat,
            'dateFormat' => $this->dateFormat,
            'icon' => $this->icon,
            'weight' => $this->weight,
            'moneyFormat' => $this->moneyFormat ? json_decode($this->moneyFormat, true) : null,
        ];
    }

    public function evaluateColor(mixed $state, mixed $record = null): ?string
    {
        if ($this->colorCallback) {
            return ($this->colorCallback)($state, $record);
        }

        return null;
    }
}
