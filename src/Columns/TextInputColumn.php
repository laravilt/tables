<?php

namespace Laravilt\Tables\Columns;

use Closure;

class TextInputColumn extends Column
{
    protected ?Closure $beforeStateUpdated = null;

    protected ?Closure $afterStateUpdated = null;

    protected array $rules = [];

    protected string $type = 'text';

    protected string|Closure|null $inputPrefix = null;

    protected string|Closure|null $inputSuffix = null;

    protected string|Closure|null $inputPrefixIcon = null;

    protected string|Closure|null $inputSuffixIcon = null;

    protected string|Closure|null $inputSuffixIconColor = null;

    protected string|Closure|null $inputPrefixIconColor = null;

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

    public function inputPrefix(string|Closure $prefix): static
    {
        $this->inputPrefix = $prefix;

        return $this;
    }

    public function inputSuffix(string|Closure $suffix): static
    {
        $this->inputSuffix = $suffix;

        return $this;
    }

    public function inputPrefixIcon(string|Closure $icon): static
    {
        $this->inputPrefixIcon = $icon;

        return $this;
    }

    public function inputSuffixIcon(string|Closure $icon): static
    {
        $this->inputSuffixIcon = $icon;

        return $this;
    }

    public function inputPrefixIconColor(string|Closure $color): static
    {
        $this->inputPrefixIconColor = $color;

        return $this;
    }

    public function inputSuffixIconColor(string|Closure $color): static
    {
        $this->inputSuffixIconColor = $color;

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
            'prefix' => $this->inputPrefix,
            'suffix' => $this->inputSuffix,
            'prefixIcon' => $this->inputPrefixIcon,
            'suffixIcon' => $this->inputSuffixIcon,
            'prefixIconColor' => $this->inputPrefixIconColor,
            'suffixIconColor' => $this->inputSuffixIconColor,
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
