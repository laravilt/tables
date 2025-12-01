<?php

namespace Laravilt\Tables\Columns;

use Closure;

class ToggleColumn extends Column
{
    protected ?Closure $beforeStateUpdated = null;

    protected ?Closure $afterStateUpdated = null;

    protected array $rules = [];

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
        return 'ToggleColumn';
    }

    protected function getVueProps(): array
    {
        return [
            ...parent::getVueProps(),
            'rules' => $this->rules,
            'editable' => true,
        ];
    }

    protected function getFlutterWidget(): string
    {
        return 'LaraviltToggleColumn';
    }

    protected function getFlutterWidgetProps(): array
    {
        return [
            ...parent::getFlutterWidgetProps(),
            'editable' => true,
        ];
    }
}
