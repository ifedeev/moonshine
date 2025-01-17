<?php

declare(strict_types=1);

use MoonShine\UI\Contracts\DefaultValueTypes\CanBeArray;
use MoonShine\UI\Fields\DateRange;

uses()->group('fields');
uses()->group('date-field');

beforeEach(function (): void {
    $this->field = DateRange::make('Range')
        ->fromTo('start', 'end');
});

describe('basic methods', function () {
    it('change fill', function () {
        $from = now();
        $to = now()->addDay();
        $values = ['start' => $from, 'end' => $to];

        expect($this->field->changeFill(static fn () => $values)->fill([]))
            ->toValue()
            ->toBe($values);

        $from = now();
        $values = ['start' => $from];

        expect($this->field->changeFill(static fn () => $values)->fill([]))
            ->toValue()
            ->toBe(['start' => $from, 'end' => '']);
    });

    it('set value', function () {
        $from = now();
        $to = now()->addDay();
        $values = ['start' => $from, 'end' => $to];

        expect($this->field->setValue($values))
            ->toValue()
            ->toBe($values)
            ->and($this->field->preview())
            ->toContain($from, $to);

        $from = now();
        $values = ['start' => $from];

        expect($this->field->setValue($values))
            ->toValue()
            ->toBe($values)
            ->and($this->field->setValue($values)->preview())
            ->not->toContain($to)
            ->toContain($from);
    });

    it('non value', function () {
        $from = now();
        $to = now()->addDay();
        $values = ['start' => $from, 'end' => $to];

        expect($this->field)
            ->toValue()
            ->toBeNull()
            ->and($this->field->preview())
            ->toBeEmpty()
            ->and($this->field->default($values)->preview())
            ->toBeEmpty()
        ;
    });

    it('change preview', function () {
        expect($this->field->changePreview(static fn () => 'changed'))
            ->preview()
            ->toBe('changed');
    });

    it('formatted value', function () {
        $field = DateRange::make('Range', formatted: static fn () => ['changed'])
            ->fromTo('start', 'end')
            ->fill([]);

        expect($field->toFormattedValue())
            ->toBe(['changed']);
    });

    it('default value', function () {
        $from = now();
        $to = now()->addMonth();

        $field = DateRange::make('Range')
            ->fromTo('start', 'end')
            ->default([$from, $to]);

        expect($field->toValue())
            ->toBe([$from, $to]);

        $fromFilled = now()->addMonth();
        $toFilled = now()->addYear();

        $field = DateRange::make('Range')
            ->fromTo('start', 'end')
            ->default([$from, $to])
            ->fill(['start' => $fromFilled, 'end' => $toFilled])
        ;

        expect($field->toValue())
            ->toBe(['start' => $fromFilled, 'end' => $toFilled]);
    });

    it('applies', function () {
        $field = DateRange::make('Range')
            ->fromTo('start', 'end')
        ;

        expect()
            ->applies($field);
        ;
    });
});

describe('common field methods', function () {
    it('names', function (): void {
        expect($this->field)
            ->getNameAttribute()
            ->toBe('range[]')
            ->getNameAttribute('start')
            ->toBe('range[start]');
    });

    it('names in render', function (): void {
        expect((string) $this->field->render())
            ->toContain('range[start]')
            ->toContain('range[end]');
    });

    it('correct interfaces', function (): void {
        expect($this->field)
            ->toBeInstanceOf(CanBeArray::class)
        ;
    });

    it('type', function (): void {
        expect($this->field->getAttributes()->get('type'))
            ->toBe('date');
    });

    it('view', function (): void {
        expect($this->field->getView())
            ->toBe('moonshine::fields.range');
    });

    it('preview', function (): void {
        $from = now();
        $to = now()->addMonth();

        expect($this->field->fill(['start' => $from, 'end' => $to])->preview())
            ->toBe($from . ' - ' . $to);
    });

    it('is group', function (): void {
        expect($this->field->isGroup())
            ->toBeTrue();
    });

    it('is nullable', function (): void {
        expect($this->field->preview())
            ->toBeEmpty();
    });
});

describe('unique field methods', function () {
    it('format carbon', function (): void {
        $from = now();
        $to = now()->addMonth();

        $field = $this->field
            ->fill(['start' => $from, 'end' => $to])
            ->format('d.m');

        expect($field->preview())
            ->toBe($from->format('d.m') . ' - ' . $to->format('d.m'))
            ->and($field->getValue())
                ->toBe([
                    $field->fromField => $from->format('Y-m-d'),
                    $field->toField => $to->format('Y-m-d'),
                ]);
    });

    it('format string', function (): void {
        $from = '2020-01-01';
        $to = '2020-02-01';

        $field = $this->field
            ->fill(['start' => $from, 'end' => $to])
            ->format('d.m');

        expect($field->preview())
            ->toBe('01.01 - 01.02')
            ->and($field->getValue())
            ->toBe([
                $field->fromField => $from,
                $field->toField => $to,
            ]);
    });

    it('format with time', function (): void {
        $from = '2020-01-01';
        $to = '2020-02-01';

        $field = $this->field
            ->fill(['start' => $from, 'end' => $to])
            ->withTime();

        expect($field->getAttributes()->get('type'))
            ->toBe('datetime-local')
            ->and($field->preview())
            ->toBe('2020-01-01 00:00:00 - 2020-02-01 00:00:00')
            ->and($field->getValue())
            ->toBe([
                $field->fromField => $from . 'T00:00',
                $field->toField => $to . 'T00:00',
            ]);
    });

    it('step', function (): void {
        $from = now();
        $to = now()->addMonth();

        $this->field
            ->fill(['start' => $from, 'end' => $to])
            ->step(5);

        expect($this->field->getAttributes()->get('step'))
            ->toBe('5');

        $this->field
            ->fill(['start' => $from, 'end' => $to])
            ->step(5.5);

        expect($this->field->getAttributes()->get('step'))
            ->toBe('5.5');

        $this->field
            ->fill(['start' => $from, 'end' => $to])
            ->step('5');

        expect($this->field->getAttributes()->get('step'))
            ->toBe('5');
    });

    it('attribute order', function (): void {
        $from = now();
        $to = now()->addMonth();

        $fieldOne = clone $this->field;

        $fieldOne
            ->fill(['start' => $from, 'end' => $to])
            ->fromAttributes(['class' => 'bg-lime-500'])
            ->toAttributes(['class' => 'bg-lime-500'])
            ->step(10);

        // because prepareBeforeRender fill and merge from/to attributes
        $fieldOne->render();

        $fromAttributes = $fieldOne->getFromAttributes();
        $toAttributes = $fieldOne->getToAttributes();

        expect($fromAttributes->get('step'))
            ->toBe('10')
            ->and($toAttributes->get('step'))
            ->toBe('10');

        $fieldTwo = clone $this->field;

        $fieldTwo
            ->fill(['start' => $from, 'end' => $to])
            ->step('11')
            ->fromAttributes(['class' => 'bg-lime-500'])
            ->toAttributes(['class' => 'bg-lime-500'])
        ;

        $fieldTwo->render();

        $fromAttributes = $fieldTwo->getFromAttributes();
        $toAttributes = $fieldTwo->getToAttributes();

        expect($fromAttributes->get('step'))
            ->toBe('11')
            ->and($toAttributes->get('step'))
            ->toBe('11');
    });
});
