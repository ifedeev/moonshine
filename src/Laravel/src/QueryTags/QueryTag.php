<?php

declare(strict_types=1);

namespace MoonShine\Laravel\QueryTags;

use Closure;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Contracts\Core\HasCanSeeContract;
use MoonShine\Contracts\Core\HasCoreContract;
use MoonShine\Contracts\UI\HasIconContract;
use MoonShine\Contracts\UI\HasLabelContract;
use MoonShine\Core\Traits\WithCore;
use MoonShine\Support\Traits\Makeable;
use MoonShine\UI\Traits\HasCanSee;
use MoonShine\UI\Traits\WithIcon;
use MoonShine\UI\Traits\WithLabel;

/**
 * @method static static make(Closure|string $label, Closure $builder)
 */
final class QueryTag implements HasCanSeeContract, HasIconContract, HasLabelContract, HasCoreContract
{
    use Makeable;
    use WithCore;
    use WithIcon;
    use HasCanSee;
    use WithLabel;

    protected bool $isDefault = false;

    protected ?string $alias = null;

    public function __construct(
        Closure|string $label,
        /** @var Closure(Builder): Builder $builder */
        protected Closure $builder,
    ) {
        $this->setLabel($label);
    }

    public function alias(string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }

    public function getUri(): string
    {
        if (! \is_null($this->alias)) {
            return $this->alias;
        }

        return str($this->getLabel())->slug()->value();
    }

    public function default(Closure|bool|null $condition = null): self
    {
        $this->isDefault = value($condition, $this) ?? true;

        return $this;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function isActive(): bool
    {
        if ($this->isDefault() && ! request()->filled('query-tag')) {
            return true;
        }

        return request()->getScalar('query-tag') === $this->getUri();
    }

    public function apply(Builder $builder): Builder
    {
        return \call_user_func($this->builder, $builder);
    }
}
