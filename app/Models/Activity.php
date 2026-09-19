<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Journal des actions effectuées sur les modèles administrés
 * (comptes utilisateurs pour commencer).
 *
 * @property int $id
 * @property int|null $actor_id
 * @property string $subject_type
 * @property int $subject_id
 * @property string $action
 * @property string|null $description
 * @property array<string, mixed>|null $meta
 */
#[Fillable(['actor_id', 'subject_type', 'subject_id', 'action', 'description', 'meta'])]
class Activity extends Model
{
    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    public static function record(?User $actor, Model $subject, string $action, ?string $description = null, ?array $meta = null): self
    {
        return static::create([
            'actor_id' => $actor?->id,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'action' => $action,
            'description' => $description,
            'meta' => $meta,
        ]);
    }
}
