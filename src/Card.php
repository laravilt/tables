<?php

declare(strict_types=1);

namespace Laravilt\Tables;

use Closure;
use Laravilt\Support\Contracts\FlutterSerializable;
use Laravilt\Support\Contracts\InertiaSerializable;

class Card implements FlutterSerializable, InertiaSerializable
{
    /** @var array<int, \Laravilt\Core\Component> */
    protected array $schema = [];

    /** @var array<int, \Laravilt\Tables\Columns\Column> */
    protected array $columns = [];

    protected ?Closure $header = null;

    protected ?Closure $footer = null;

    protected ?string $image = null;

    protected bool $hoverable = true;

    protected ?string $aspectRatio = null;

    protected ?string $imageField = null;

    protected ?string $titleField = null;

    protected ?string $descriptionField = null;

    protected ?string $priceField = null;

    protected ?string $badgeField = null;

    protected bool $showImage = true;

    protected ?string $imagePosition = 'top'; // top, left, right, background

    protected ?string $padding = null; // sm, md, lg, xl

    protected ?string $gap = null; // sm, md, lg

    protected ?string $actionsPosition = 'top-right'; // top-right, top-left, bottom, bottom-center, bottom-left, bottom-right

    protected string $style = 'simple'; // simple, media, product

    public static function make(): static
    {
        return new static;
    }

    /**
     * Quick setup for a product card layout
     * Perfect for e-commerce: image, title, description, price, and status badge
     */
    public static function product(
        string $imageField = 'image',
        string $titleField = 'name',
        string $priceField = 'price',
        ?string $descriptionField = 'description',
        ?string $badgeField = 'status'
    ): static {
        return static::make()
            ->style('product')
            ->imageField($imageField)
            ->titleField($titleField)
            ->priceField($priceField)
            ->descriptionField($descriptionField)
            ->badgeField($badgeField)
            ->showImage()
            ->hoverable()
            ->aspectRatio('4/3')
            ->padding('md')
            ->gap('sm');
    }

    /**
     * Quick setup for a simple card layout
     * Clean minimal design: title, description and optional badge
     */
    public static function simple(
        string $titleField = 'name',
        ?string $descriptionField = 'description'
    ): static {
        return static::make()
            ->style('simple')
            ->titleField($titleField)
            ->descriptionField($descriptionField)
            ->showImage(false)
            ->hoverable()
            ->padding('md')
            ->gap('sm');
    }

    /**
     * Quick setup for a media card layout
     * Full-width image with overlay text: great for galleries, articles, portfolios
     */
    public static function media(
        string $imageField = 'image',
        string $titleField = 'name',
        ?string $descriptionField = 'description'
    ): static {
        return static::make()
            ->style('media')
            ->imageField($imageField)
            ->titleField($titleField)
            ->descriptionField($descriptionField)
            ->showImage()
            ->hoverable()
            ->aspectRatio('16/9')
            ->imagePosition('background')
            ->padding('md')
            ->gap('sm');
    }

    public function style(string $style): static
    {
        $this->style = $style;

        return $this;
    }

    /**
     * @param  array<int, \Laravilt\Core\Component>  $schema
     */
    public function schema(array $schema): static
    {
        $this->schema = $schema;

        return $this;
    }

    /**
     * Set specific columns to display in the card body
     *
     * @param  array<int, \Laravilt\Tables\Columns\Column>  $columns
     */
    public function columns(array $columns): static
    {
        $this->columns = $columns;

        return $this;
    }

    public function header(Closure $callback): static
    {
        $this->header = $callback;

        return $this;
    }

    public function footer(Closure $callback): static
    {
        $this->footer = $callback;

        return $this;
    }

    public function image(string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function hoverable(bool $condition = true): static
    {
        $this->hoverable = $condition;

        return $this;
    }

    public function aspectRatio(string $ratio): static
    {
        $this->aspectRatio = $ratio;

        return $this;
    }

    public function imageField(string $field): static
    {
        $this->imageField = $field;

        return $this;
    }

    public function titleField(string $field): static
    {
        $this->titleField = $field;

        return $this;
    }

    public function descriptionField(?string $field): static
    {
        $this->descriptionField = $field;

        return $this;
    }

    public function priceField(string $field): static
    {
        $this->priceField = $field;

        return $this;
    }

    public function badgeField(?string $field): static
    {
        $this->badgeField = $field;

        return $this;
    }

    public function showImage(bool $condition = true): static
    {
        $this->showImage = $condition;

        return $this;
    }

    public function imagePosition(string $position): static
    {
        $this->imagePosition = $position;

        return $this;
    }

    public function padding(string $size): static
    {
        $this->padding = $size;

        return $this;
    }

    public function gap(string $size): static
    {
        $this->gap = $size;

        return $this;
    }

    public function actionsPosition(string $position): static
    {
        $this->actionsPosition = $position;

        return $this;
    }

    /**
     * @return array<int, \Laravilt\Core\Component>
     */
    public function getSchema(): array
    {
        return $this->schema;
    }

    public function evaluateHeader(mixed $record): mixed
    {
        if ($this->header) {
            return ($this->header)($record);
        }

        return null;
    }

    public function evaluateFooter(mixed $record): mixed
    {
        if ($this->footer) {
            return ($this->footer)($record);
        }

        return null;
    }

    public function toInertiaProps(): array
    {
        return [
            'component' => 'Card',
            'schema' => array_map(
                fn ($component) => $component->toInertiaProps(),
                $this->schema
            ),
            'columns' => array_map(
                fn ($column) => $column->toInertiaProps(),
                $this->columns
            ),
            'image' => $this->image,
            'hoverable' => $this->hoverable,
            'aspectRatio' => $this->aspectRatio,
            'imageField' => $this->imageField,
            'titleField' => $this->titleField,
            'descriptionField' => $this->descriptionField,
            'priceField' => $this->priceField,
            'badgeField' => $this->badgeField,
            'showImage' => $this->showImage,
            'imagePosition' => $this->imagePosition,
            'padding' => $this->padding,
            'gap' => $this->gap,
            'actionsPosition' => $this->actionsPosition,
            'style' => $this->style,
        ];
    }

    public function toFlutterProps(): array
    {
        return [
            'widget' => 'LaraviltCard',
            'schema' => array_map(
                fn ($component) => $component->toFlutterProps(),
                $this->schema
            ),
            'image' => $this->image,
            'hoverable' => $this->hoverable,
            'aspectRatio' => $this->aspectRatio,
        ];
    }
}
