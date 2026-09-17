<?php

namespace App\Models;

use Database\Factories\StatisticFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $key
 * @property string $label
 * @property string $value
 * @property bool $is_active
 * @property int $sort_order
 */
#[Fillable(['key', 'label', 'value', 'is_active', 'sort_order'])]
class Statistic extends Model
{
    /** @use HasFactory<StatisticFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Statistic>  $query
     * @return Builder<Statistic>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Statistic>  $query
     * @return Builder<Statistic>
     */
    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
