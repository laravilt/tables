<?php

namespace Laravilt\Tables\Columns;

use Closure;

class ImageColumn extends Column
{
    protected string|int|Closure|null $imageWidth = null;

    protected string|int|Closure|null $imageHeight = null;

    protected bool|Closure $square = false;

    protected bool|Closure $circular = false;

    protected bool|Closure $stacked = false;

    protected int|Closure $ring = 3;

    protected int|Closure $overlap = 4;

    protected int|Closure|null $limit = null;

    protected bool|Closure $limitedRemainingText = false;

    protected string|Closure $limitedRemainingTextSize = 'sm';

    protected bool|Closure $wrap = false;

    protected string|Closure|null $disk = null;

    protected string|Closure|null $visibility = null;

    protected string|Closure|null $defaultImageUrl = null;

    protected bool|Closure $checkFileExistence = true;

    protected array|Closure $extraImgAttributes = [];

    protected bool $mergeExtraImgAttributes = false;

    public function imageWidth(string|int|Closure $width): static
    {
        $this->imageWidth = $width;

        return $this;
    }

    public function imageHeight(string|int|Closure $height): static
    {
        $this->imageHeight = $height;

        return $this;
    }

    public function imageSize(string|int|Closure $size): static
    {
        $this->imageWidth = $size;
        $this->imageHeight = $size;

        return $this;
    }

    public function square(bool|Closure $condition = true): static
    {
        $this->square = $condition;

        return $this;
    }

    public function circular(bool|Closure $condition = true): static
    {
        $this->circular = $condition;

        return $this;
    }

    public function stacked(bool|Closure $condition = true): static
    {
        $this->stacked = $condition;

        return $this;
    }

    public function ring(int|Closure $ring): static
    {
        $this->ring = $ring;

        return $this;
    }

    public function overlap(int|Closure $overlap): static
    {
        $this->overlap = $overlap;

        return $this;
    }

    public function limit(int|Closure $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    public function limitedRemainingText(bool|Closure $condition = true, string|Closure $size = 'sm'): static
    {
        $this->limitedRemainingText = $condition;
        $this->limitedRemainingTextSize = $size;

        return $this;
    }

    public function wrap(bool|Closure $condition = true): static
    {
        $this->wrap = $condition;

        return $this;
    }

    public function disk(string|Closure $disk): static
    {
        $this->disk = $disk;

        return $this;
    }

    public function visibility(string|Closure $visibility): static
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function defaultImageUrl(string|Closure $url): static
    {
        $this->defaultImageUrl = $url;

        return $this;
    }

    public function checkFileExistence(bool|Closure $condition = true): static
    {
        $this->checkFileExistence = $condition;

        return $this;
    }

    public function extraImgAttributes(array|Closure $attributes, bool $merge = false): static
    {
        if ($merge && ! $this->mergeExtraImgAttributes) {
            $this->mergeExtraImgAttributes = true;
        }

        if ($this->mergeExtraImgAttributes && is_array($attributes)) {
            $existing = is_array($this->extraImgAttributes) ? $this->extraImgAttributes : [];
            $this->extraImgAttributes = array_merge($existing, $attributes);
        } else {
            $this->extraImgAttributes = $attributes;
        }

        return $this;
    }

    protected function getVueComponent(): string
    {
        return 'ImageColumn';
    }

    protected function getVueProps(): array
    {
        return [
            ...parent::getVueProps(),
            'imageWidth' => $this->imageWidth,
            'imageHeight' => $this->imageHeight,
            'square' => $this->square,
            'circular' => $this->circular,
            'stacked' => $this->stacked,
            'ring' => $this->ring,
            'overlap' => $this->overlap,
            'limit' => $this->limit,
            'limitedRemainingText' => $this->limitedRemainingText,
            'limitedRemainingTextSize' => $this->limitedRemainingTextSize,
            'wrap' => $this->wrap,
            'disk' => $this->disk,
            'visibility' => $this->visibility,
            'defaultImageUrl' => $this->defaultImageUrl,
            'checkFileExistence' => $this->checkFileExistence,
            'extraImgAttributes' => $this->extraImgAttributes,
        ];
    }

    protected function getFlutterWidget(): string
    {
        return 'LaraviltImageColumn';
    }

    protected function getFlutterWidgetProps(): array
    {
        return [
            ...parent::getFlutterWidgetProps(),
            'imageWidth' => $this->imageWidth,
            'imageHeight' => $this->imageHeight,
            'square' => $this->square,
            'circular' => $this->circular,
            'stacked' => $this->stacked,
            'wrap' => $this->wrap,
        ];
    }

    public function evaluateImageWidth(mixed $state, mixed $record = null): string|int|null
    {
        if ($this->imageWidth instanceof Closure) {
            return ($this->imageWidth)($state, $record);
        }

        return $this->imageWidth;
    }

    public function evaluateImageHeight(mixed $state, mixed $record = null): string|int|null
    {
        if ($this->imageHeight instanceof Closure) {
            return ($this->imageHeight)($state, $record);
        }

        return $this->imageHeight;
    }

    public function evaluateSquare(mixed $state, mixed $record = null): bool
    {
        if ($this->square instanceof Closure) {
            return ($this->square)($state, $record);
        }

        return $this->square;
    }

    public function evaluateCircular(mixed $state, mixed $record = null): bool
    {
        if ($this->circular instanceof Closure) {
            return ($this->circular)($state, $record);
        }

        return $this->circular;
    }

    public function evaluateStacked(mixed $state, mixed $record = null): bool
    {
        if ($this->stacked instanceof Closure) {
            return ($this->stacked)($state, $record);
        }

        return $this->stacked;
    }

    public function evaluateRing(mixed $state, mixed $record = null): int
    {
        $ring = $this->ring;
        if ($ring instanceof Closure) {
            $ring = $ring($state, $record);
        }

        return max(0, min(8, $ring));
    }

    public function evaluateOverlap(mixed $state, mixed $record = null): int
    {
        $overlap = $this->overlap;
        if ($overlap instanceof Closure) {
            $overlap = $overlap($state, $record);
        }

        return max(0, min(8, $overlap));
    }

    public function evaluateLimit(mixed $state, mixed $record = null): ?int
    {
        if ($this->limit instanceof Closure) {
            return ($this->limit)($state, $record);
        }

        return $this->limit;
    }

    public function evaluateLimitedRemainingText(mixed $state, mixed $record = null): bool
    {
        if ($this->limitedRemainingText instanceof Closure) {
            return ($this->limitedRemainingText)($state, $record);
        }

        return $this->limitedRemainingText;
    }

    public function evaluateLimitedRemainingTextSize(mixed $state, mixed $record = null): string
    {
        if ($this->limitedRemainingTextSize instanceof Closure) {
            return ($this->limitedRemainingTextSize)($state, $record);
        }

        return $this->limitedRemainingTextSize;
    }

    public function evaluateWrap(mixed $state, mixed $record = null): bool
    {
        if ($this->wrap instanceof Closure) {
            return ($this->wrap)($state, $record);
        }

        return $this->wrap;
    }

    public function evaluateDisk(mixed $state, mixed $record = null): ?string
    {
        if ($this->disk instanceof Closure) {
            return ($this->disk)($state, $record);
        }

        return $this->disk;
    }

    public function evaluateVisibility(mixed $state, mixed $record = null): ?string
    {
        if ($this->visibility instanceof Closure) {
            return ($this->visibility)($state, $record);
        }

        return $this->visibility;
    }

    public function evaluateDefaultImageUrl(mixed $state, mixed $record = null): ?string
    {
        if ($this->defaultImageUrl instanceof Closure) {
            return ($this->defaultImageUrl)($state, $record);
        }

        return $this->defaultImageUrl;
    }

    public function evaluateCheckFileExistence(mixed $state, mixed $record = null): bool
    {
        if ($this->checkFileExistence instanceof Closure) {
            return ($this->checkFileExistence)($state, $record);
        }

        return $this->checkFileExistence;
    }

    public function evaluateExtraImgAttributes(mixed $state, mixed $record = null): array
    {
        if ($this->extraImgAttributes instanceof Closure) {
            return ($this->extraImgAttributes)($state, $record);
        }

        return $this->extraImgAttributes;
    }
}
