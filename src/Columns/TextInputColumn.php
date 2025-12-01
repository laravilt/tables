<?php

namespace Laravilt\Tables\Columns;

use Closure;

class TextInputColumn extends Column
{
    protected ?Closure $beforeStateUpdated = null;

    protected ?Closure $afterStateUpdated = null;

    protected array $rules = [];

    protected string $type = 'text';

    protected string|Closure|null $prefix = null;

    protected string|Closure|null $suffix = null;

    protected string|Closure|null $prefixIcon = null;

    protected string|Closure|null $suffixIcon = null;

    protected string|Closure|null $suffixIconColor = null;

    protected string|Closure|null $prefixIconColor = null;

    public function beforeStateUpdated(Closure $callback): static
    {
        $this->beforeStateUpdated = $callback;

        return $this;
    }

    public function afterStateUpdated(Closure $callback): static
    {
        $this->afterStateUpdated = $callback;

        return $this;
    }

    public function rules(array $rules): static
    {
        $this->rules = $rules;

        return $this;
    }

    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function prefix(string|Closure $prefix): static
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function suffix(string|Closure $suffix): static
    {
        $this->suffix = $suffix;

        return $this;
    }

    public function prefixIcon(string|Closure $icon): static
    {
        $this->prefixIcon = $icon;

        return $this;
    }

    public function suffixIcon(string|Closure $icon): static
    {
        $this->suffixIcon = $icon;

        return $this;
    }

    public function prefixIconColor(string|Closure $color): static
    {
        $this->prefixIconColor = $color;

        return $this;
    }

    public function suffixIconColor(string|Closure $color): static
    {
        $this->suffixIconColor = $color;

        return $this;
    }

    public function getBeforeStateUpdated(): ?Closure
    {
        return $this->beforeStateUpdated;
    }

    public function getAfterStateUpdated(): ?Closure
    {
        return $this->afterStateUpdated;
    }

    protected function getVueComponent(): string
    {
        return 'TextInputColumn';
    }

    protected function getVueProps(): array
    {
        return [
            ...parent::getVueProps(),
            'rules' => $this->rules,
            'type' => $this->type,
            'prefix' => $this->prefix,
            'suffix' => $this->suffix,
            'prefixIcon' => $this->prefixIcon,
            'suffixIcon' => $this->suffixIcon,
            'prefixIconColor' => $this->prefixIconColor,
            'suffixIconColor' => $this->suffixIconColor,
            'editable' => true,
        ];
    }

    protected function getFlutterWidget(): string
    {
        return 'LaraviltTextInputColumn';
    }

    protected function getFlutterWidgetProps(): array
    {
        return [
            ...parent::getFlutterWidgetProps(),
            'type' => $this->type,
            'editable' => true,
        ];
    }
}
