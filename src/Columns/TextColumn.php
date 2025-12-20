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

    protected ?string $iconPosition = 'before';

    protected ?string $weight = null;

    protected ?string $moneyFormat = null;

    protected ?Closure $colorCallback = null;

    protected bool $html = false;

    protected ?string $separator = null;

    protected bool $listWithLineBreaks = false;

    protected bool $bulleted = false;

    protected ?string $countsRelation = null;

    protected ?array $numericFormat = null;

    protected mixed $action = null;

    /** @var array<int, Summarizers\Summarizer> */
    protected array $summarizers = [];

    /**
     * Count a relationship for this column.
     * The column name should be `relation_count` and will be populated via withCount.
     */
    public function counts(string $relationship): static
    {
        $this->countsRelation = $relationship;

        return $this;
    }

    public function getCountsRelation(): ?string
    {
        return $this->countsRelation;
    }

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

    /**
     * Format the value as a number with locale-aware formatting.
     *
     * @param  int|null  $decimalPlaces  Number of decimal places
     * @param  string|null  $decimalSeparator  Custom decimal separator
     * @param  string|null  $thousandsSeparator  Custom thousands separator
     * @param  string|null  $locale  Locale for number formatting (e.g., 'en', 'de', 'fr')
     */
    public function numeric(
        ?int $decimalPlaces = null,
        ?string $decimalSeparator = null,
        ?string $thousandsSeparator = null,
        ?string $locale = null,
    ): static {
        $this->numericFormat = [
            'decimalPlaces' => $decimalPlaces,
            'decimalSeparator' => $decimalSeparator,
            'thousandsSeparator' => $thousandsSeparator,
            'locale' => $locale,
        ];

        return $this;
    }

    public function icon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function iconPosition(string $position): static
    {
        $this->iconPosition = $position;

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

    public function separator(?string $separator = ','): static
    {
        $this->separator = $separator;

        return $this;
    }

    public function listWithLineBreaks(bool $condition = true): static
    {
        $this->listWithLineBreaks = $condition;

        return $this;
    }

    public function bulleted(bool $condition = true): static
    {
        $this->bulleted = $condition;
        $this->listWithLineBreaks = true;

        return $this;
    }

    /**
     * Set an action to be triggered when clicking on this column.
     *
     * @param  mixed  $action  Action instance or closure
     */
    public function action(mixed $action): static
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get the action for this column.
     */
    public function getAction(): mixed
    {
        return $this->action;
    }

    /**
     * Add summarizers to calculate aggregate values for this column.
     *
     * @param  array<int, Summarizers\Summarizer>|Summarizers\Summarizer  $summarizers
     */
    public function summarize(array|Summarizers\Summarizer $summarizers): static
    {
        if (! is_array($summarizers)) {
            $summarizers = [$summarizers];
        }

        $this->summarizers = $summarizers;

        return $this;
    }

    /**
     * Get the summarizers for this column.
     *
     * @return array<int, Summarizers\Summarizer>
     */
    public function getSummarizers(): array
    {
        return $this->summarizers;
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
            'iconPosition' => $this->iconPosition,
            'weight' => $this->weight,
            'moneyFormat' => $this->moneyFormat ? json_decode($this->moneyFormat, true) : null,
            'colorCallback' => $this->colorCallback !== null,
            'html' => $this->html,
            'since' => $this->since,
            'separator' => $this->separator,
            'listWithLineBreaks' => $this->listWithLineBreaks,
            'bulleted' => $this->bulleted,
            'numericFormat' => $this->numericFormat,
            'hasAction' => $this->action !== null,
            'summarizers' => array_map(
                fn (Summarizers\Summarizer $summarizer) => $summarizer->toArray(),
                $this->summarizers
            ),
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
            'iconPosition' => $this->iconPosition,
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
