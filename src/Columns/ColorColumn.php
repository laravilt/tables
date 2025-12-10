<?php

namespace Laravilt\Tables\Columns;

class ColorColumn extends Column
{
    protected bool $copyable = false;

    protected ?string $copyMessage = null;

    protected ?int $copyMessageDuration = null;

    protected bool $wrap = false;

    protected int $maxVisible = 4;

    public function copyable(bool $condition = true): static
    {
        $this->copyable = $condition;

        return $this;
    }

    public function copyMessage(string $message): static
    {
        $this->copyMessage = $message;

        return $this;
    }

    public function copyMessageDuration(int $duration): static
    {
        $this->copyMessageDuration = $duration;

        return $this;
    }

    public function wrap(bool $condition = true): static
    {
        $this->wrap = $condition;

        return $this;
    }

    /**
     * Set maximum number of visible colors before showing "+X more".
     */
    public function maxVisible(int $count): static
    {
        $this->maxVisible = $count;

        return $this;
    }

    protected function getVueComponent(): string
    {
        return 'ColorColumn';
    }

    protected function getVueProps(): array
    {
        return [
            ...parent::getVueProps(),
            'copyable' => $this->copyable,
            'copyMessage' => $this->copyMessage,
            'copyMessageDuration' => $this->copyMessageDuration,
            'wrap' => $this->wrap,
            'maxVisible' => $this->maxVisible,
        ];
    }

    protected function getFlutterWidget(): string
    {
        return 'LaraviltColorColumn';
    }

    protected function getFlutterWidgetProps(): array
    {
        return [
            ...parent::getFlutterWidgetProps(),
            'copyable' => $this->copyable,
            'wrap' => $this->wrap,
        ];
    }
}
