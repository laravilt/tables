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

        // If this is a relationship filter, use whereHas
        if ($this->relationship) {
            if ($this->multiple) {
                // For multiple selection with relationship
                if (is_array($value) && count($value) > 0) {
                    return $query->whereHas($this->relationship, function ($q) use ($value) {
                        $q->whereIn('id', $value);
                    });
                }
            } else {
                // For single selection with relationship
                if ($value !== null && $value !== '') {
                    return $query->whereHas($this->relationship, function ($q) use ($value) {
                        $q->where('id', $value);
                    });
                }
            }

            return $query;
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

    /**
     * Load options from the configured relationship.
     */
    protected function loadRelationshipOptions(): array
    {
        if (! $this->relationship || ! $this->relationshipTitleAttribute) {
            return $this->options;
        }

        // Get the model from the table
        $model = $this->getModel();
        if (! $model) {
            return $this->options;
        }

        try {
            // Create a new model instance to access the relationship
            $modelInstance = new $model;

            if (! method_exists($modelInstance, $this->relationship)) {
                return $this->options;
            }

            // Get the related model
            $relation = $modelInstance->{$this->relationship}();
            $query = $relation->getRelated()->query();

            // Apply custom query modifications
            if ($this->relationshipModifyQueryUsing) {
                $query = call_user_func($this->relationshipModifyQueryUsing, $query);
            }

            // Get the options
            $titleAttribute = $this->relationshipTitleAttribute;
            $keyName = $relation->getRelated()->getKeyName();

            $options = $query->pluck($titleAttribute, $keyName)->toArray();

            // Add empty option if enabled
            if ($this->hasEmptyOption) {
                $options = ['' => $this->emptyRelationshipOptionLabel] + $options;
            }

            return $options;
        } catch (\Exception $e) {
            // If relationship loading fails, return empty options
            return $this->options;
        }
    }

    protected function getVueProps(): array
    {
        // Load options from relationship if configured and preload is enabled
        $options = $this->options;
        if ($this->relationship && $this->preload) {
            $options = $this->loadRelationshipOptions();
        }

        // Build the Select form component
        $select = Select::make($this->getName())
            ->label($this->getLabel() ?? $this->getName())
            ->options($options)
            ->placeholder($this->getPlaceholder() ?? ($this->selectablePlaceholder ? 'All' : null));

        if ($this->multiple) {
            $select->multiple();
        }

        if ($this->searchable) {
            $select->searchable();
        }

        // If relationship is configured but not preloaded, pass relationship info for dynamic loading
        if ($this->relationship && ! $this->preload) {
            $select->relationship($this->relationship, $this->relationshipTitleAttribute);
        }

        return [
            'default' => $this->default,
            'options' => $options,
            'multiple' => $this->multiple,
            'searchable' => $this->searchable,
            'formField' => $select->toLaraviltProps(),
            'relationship' => $this->relationship,
            'relationshipTitleAttribute' => $this->relationshipTitleAttribute,
            'preload' => $this->preload,
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
