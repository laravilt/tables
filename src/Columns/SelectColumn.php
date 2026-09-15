<?php

namespace Laravilt\Tables\Columns;

use Closure;
use Illuminate\Validation\Rule;
use Laravilt\Tables\Columns\Contracts\EditableColumn;

class SelectColumn extends Column implements EditableColumn
{
    protected array|Closure $options = [];

    protected array $rules = [];

    protected bool $native = true;

    protected bool $optionsSearchable = false;

    protected ?Closure $beforeStateUpdated = null;

    protected ?Closure $afterStateUpdated = null;

    protected ?Closure $disableOptionWhen = null;

    protected bool $selectablePlaceholder = true;

    public function options(array|Closure $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function rules(array $rules): static
    {
        $this->rules = $rules;

        return $this;
    }

    public function native(bool $condition = true): static
    {
        $this->native = $condition;

        return $this;
    }

    public function optionsSearchable(bool $condition = true): static
    {
        $this->optionsSearchable = $condition;

        return $this;
    }

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

    public function disableOptionWhen(Closure $callback): static
    {
        $this->disableOptionWhen = $callback;

        return $this;
    }

    public function selectablePlaceholder(bool $condition = true): static
    {
        $this->selectablePlaceholder = $condition;

        return $this;
    }

    public function getOptions(): array
    {
        if ($this->options instanceof Closure) {
            return ($this->options)();
        }

        return $this->options;
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    public function getStateValidationRules(): array
    {
        return [
            $this->selectablePlaceholder ? 'nullable' : 'required',
            Rule::in(array_map('strval', array_keys($this->getOptions()))),
            ...$this->rules,
        ];
    }

    public function dehydrateState(mixed $state): mixed
    {
        return $state === '' ? null : $state;
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
        return 'SelectColumn';
    }

    protected function getVueProps(): array
    {
        return [
            ...parent::getVueProps(),
            'options' => $this->getOptions(),
            'rules' => $this->rules,
            'native' => $this->native,
            'optionsSearchable' => $this->optionsSearchable,
            'selectablePlaceholder' => $this->selectablePlaceholder,
            'editable' => true,
        ];
    }

    protected function getFlutterWidget(): string
    {
        return 'LaraviltSelectColumn';
    }

    protected function getFlutterWidgetProps(): array
    {
        return [
            ...parent::getFlutterWidgetProps(),
            'options' => $this->getOptions(),
            'rules' => $this->rules,
        ];
    }
}
