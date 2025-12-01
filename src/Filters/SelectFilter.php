<?php

namespace Laravilt\Tables\Filters;

use Closure;
use Laravilt\Forms\Components\Select;

class SelectFilter extends Filter
{
    /**
     * @var array<string|int, string>
     */
    protected array $options = [];

    protected bool $multiple = false;

    protected bool $searchable = false;

    protected bool $selectablePlaceholder = true;

    protected ?string $relationship = null;

    protected ?string $relationshipTitleAttribute = null;

    protected ?Closure $relationshipModifyQueryUsing = null;

    protected bool $preload = false;

    protected bool $hasEmptyOption = false;

    protected string $emptyRelationshipOptionLabel = 'None';

    public static function make(string $name): static
    {
        return parent::make($name);
    }

    /**
     * Set the options for the select filter.
     *
     * @param  array<string|int, string>|Closure  $options
     */
    public function options(array|Closure $options): static
    {
        if ($options instanceof Closure) {
            $this->options = $options();
        } else {
            $this->options = $options;
        }

        return $this;
    }

    /**
     * Enable multiple selection.
     */
    public function multiple(bool $condition = true): static
    {
        $this->multiple = $condition;

        return $this;
    }

    /**
     * Enable searchable dropdown.
     */
    public function searchable(bool $condition = true): static
    {
        $this->searchable = $condition;

        return $this;
    }

    /**
     * Control whether the placeholder option is selectable.
     */
    public function selectablePlaceholder(bool $condition = true): static
    {
        $this->selectablePlaceholder = $condition;

        return $this;
    }

    /**
     * Load options from a relationship.
     */
    public function relationship(string $relationship, string $titleAttribute, ?Closure $modifyQueryUsing = null): static
    {
        $this->relationship = $relationship;
        $this->relationshipTitleAttribute = $titleAttribute;
        $this->relationshipModifyQueryUsing = $modifyQueryUsing;

        return $this;
    }

    /**
     * Preload relationship options on page load.
     */
    public function preload(bool $condition = true): static
    {
        $this->preload = $condition;

        return $this;
    }

    /**
     * Include an empty option for null relationships.
     */
    public function hasEmptyOption(bool $condition = true): static
    {
        $this->hasEmptyOption = $condition;

        return $this;
    }

    /**
     * Customize the label for the empty relationship option.
     */
    public function emptyRelationshipOptionLabel(string $label): static
    {
        $this->emptyRelationshipOptionLabel = $label;

        return $this;
    }

    /**
     * Apply the filter to the query.
     */
    public function apply($query, mixed $value)
    {
        // If custom query is defined, use it
        if ($this->query) {
            return ($this->query)($query, $value);
        }

        // Default behavior: filter by attribute
        $attribute = $this->getAttribute() ?? $this->getName();

        if ($this->multiple) {
            // For multiple selection, use whereIn
            if (is_array($value) && count($value) > 0) {
                return $query->whereIn($attribute, $value);
            }
        } else {
            // For single selection, use where
            if ($value !== null && $value !== '') {
                return $query->where($attribute, $value);
            }
        }

        return $query;
    }

    protected function getVueComponent(): string
    {
        return 'SelectFilter';
    }

    protected function getVueProps(): array
    {
        // Build the Select form component
        $select = Select::make($this->getName())
            ->label($this->getLabel() ?? $this->getName())
            ->options($this->options)
            ->placeholder($this->getPlaceholder() ?? ($this->selectablePlaceholder ? 'All' : null));

        if ($this->multiple) {
            $select->multiple();
        }

        if ($this->searchable) {
            $select->searchable();
        }

        return [
            'default' => $this->default,
            'options' => $this->options,
            'multiple' => $this->multiple,
            'searchable' => $this->searchable,
            'formField' => $select->toLaraviltProps(),
        ];
    }

    protected function getFlutterWidget(): string
    {
        return 'LaraviltSelectFilter';
    }

    protected function getFlutterWidgetProps(): array
    {
        return [
            ...parent::getFlutterWidgetProps(),
            'options' => $this->options,
            'multiple' => $this->multiple,
            'searchable' => $this->searchable,
        ];
    }
}
