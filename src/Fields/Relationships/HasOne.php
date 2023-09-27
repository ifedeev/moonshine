<?php

declare(strict_types=1);

namespace MoonShine\Fields\Relationships;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use MoonShine\ActionButtons\ActionButton;
use MoonShine\Components\FormBuilder;
use MoonShine\Components\TableBuilder;
use MoonShine\Decorations\TextBlock;
use MoonShine\Exceptions\FieldException;
use MoonShine\Fields\Field;
use MoonShine\Fields\Fields;
use MoonShine\Fields\Hidden;
use MoonShine\Traits\WithFields;
use Illuminate\Contracts\View\View;
use Throwable;

class HasOne extends ModelRelationField
{
    use WithFields;

    protected bool $toOne = true;

    protected bool $isGroup = true;

    protected bool $outsideComponent = true;

    public function preview(): View|string
    {
        $casted = $this->getRelatedModel();

        $this->setValue($casted->{$this->getRelationName()});

        return parent::preview();
    }

    public function value(bool $withOld = false): mixed
    {
        $this->setValue($this->getRelatedModel()->{$this->getRelationName()});

        return parent::value($withOld);
    }

    public function resolveFill(
        array $raw = [],
        mixed $casted = null,
        int $index = 0
    ): Field {

        if ($casted instanceof Model) {
            $this->setRelatedModel($casted);
        }

        return $this;
    }

    /**
     * @throws Throwable
     */
    public function preparedFields(): Fields
    {
        if (! $this->hasFields()) {
            $fields = $this->getResource()->getFormFields();

            $this->fields($fields->toArray());

            return Fields::make($this->fields);
        }

        return $this->getFields()->formFields();
    }

    protected function resolvePreview(): View|string
    {
        $items = Arr::wrap($this->toValue());

        if ($this->isRawMode()) {
            return $items
                ->map(fn (Model $item) => $item->{$this->getResourceColumn()})
                ->implode(';');
        }

        $resource = $this->getResource();

        return TableBuilder::make(items: $items)
            ->fields($this->preparedFields())
            ->cast($resource->getModelCast())
            ->preview()
            ->simple()
            ->vertical()
            ->render();
    }

    /**
     * @throws FieldException
     * @throws Throwable
     */
    protected function resolveValue(): mixed
    {
        $resource = $this->getResource();

        $parentResource = moonshineRequest()->getResource();

        $item = $this->toValue();

        if(is_null($parentResource)) {
            throw new FieldException('Parent resource is required');
        }

        $parentItem = $parentResource->getItemOrInstance();

        $fields = $this->preparedFields();

        $action = to_relation_route(
            is_null($item) ? 'store' : 'update',
            $this->getRelatedModel()?->getKey(),
        );

        return FormBuilder::make($action)
            ->precognitive()
            ->async()
            ->name($this->getRelationName())
            ->fields(
                $fields->when(
                    ! is_null($item),
                    fn (Fields $fields): Fields => $fields->push(
                        Hidden::make('_method')->setValue('PUT'),
                    )
                )->push(
                    Hidden::make('_relation')->setValue($this->getRelationName()),
                )->toArray()
            )
            ->fill($item?->attributesToArray() ?? [])
            ->cast($resource->getModelCast())
            ->buttons(is_null($item) ? [] : [
                ActionButton::make(
                    __('moonshine::ui.delete'),
                    url: fn ($data): string => $resource->route('crud.destroy', $data->getKey())
                )
                    ->customAttributes(['class' => 'btn-secondary btn-lg'])
                    ->inModal(
                        fn (): array|string|null => __('moonshine::ui.delete'),
                        fn (ActionButton $action): string => (string) form(
                            $action->url(),
                            fields: [
                                Hidden::make('_method')->setValue('DELETE'),
                                TextBlock::make('', __('moonshine::ui.confirm_message')),
                            ]
                        )
                            ->submit(__('moonshine::ui.delete'), ['class' => 'btn-secondary'])
                            ->redirect(
                                to_page(
                                    $parentResource,
                                    'form-page',
                                    ['resourceItem' => $parentItem->getKey()]
                                )
                            )
                    )
                    ->showInLine(),
            ])
            ->submit(__('moonshine::ui.save'), ['class' => 'btn-primary btn-lg']);
    }
}
