<?php

declare(strict_types=1);

namespace Holvek\Laravel\Traits;

use Holvek\Laravel\Models\HolvekMeta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @mixin Model
 *
 * @property ?HolvekMeta $holvekMeta
 */
trait HasHolvekMeta
{
    /**
     * @return MorphOne<HolvekMeta, $this>
     */
    public function holvekMeta(): MorphOne
    {
        return $this->morphOne(HolvekMeta::class, 'model');
    }

    public function getSeoTitle(): ?string
    {
        return $this->holvekMeta?->title;
    }

    public function getSeoDescription(): ?string
    {
        return $this->holvekMeta?->description;
    }
}
