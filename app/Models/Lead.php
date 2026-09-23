<?php

namespace App\Models;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\ProspectType;
use App\Enums\RequestType;
use App\Support\SlaClock;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $reference
 * @property string $name
 * @property string $email
 * @property ProspectType|null $prospect_type
 * @property string|null $residence_country
 * @property string|null $target_territory
 * @property LeadStatus $status
 * @property RequestType $request_type
 * @property LeadSource $source
 * @property string|null $origin_page
 * @property string|null $utm_source
 * @property string|null $utm_medium
 * @property string|null $utm_campaign
 * @property string|null $next_action
 * @property Carbon|null $next_action_at
 * @property Carbon|null $deadline
 * @property int|null $estimated_amount
 * @property Carbon|null $notified_at
 * @property Carbon|null $first_contacted_at
 */
#[Fillable(['reference', 'name', 'company', 'email', 'phone', 'prospect_type', 'residence_country', 'target_territory', 'sector_id', 'expertise_id', 'request_type', 'budget', 'message', 'status', 'source', 'origin_page', 'utm_source', 'utm_medium', 'utm_campaign', 'assigned_to', 'notes', 'next_action', 'next_action_at', 'deadline', 'estimated_amount', 'consent_at', 'consent_ip', 'first_contacted_at'])]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'request_type' => RequestType::class,
            'status' => LeadStatus::class,
            'source' => LeadSource::class,
            'prospect_type' => ProspectType::class,
            'consent_at' => 'datetime',
            'notified_at' => 'datetime',
            'first_contacted_at' => 'datetime',
            'next_action_at' => 'datetime',
            'deadline' => 'date',
            'estimated_amount' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Référence lisible type SIB-00231, générée une fois l'id connu.
        static::created(function (Lead $lead): void {
            if ($lead->reference === null) {
                $lead->updateQuietly([
                    'reference' => 'SIB-'.str_pad((string) $lead->id, 5, '0', STR_PAD_LEFT),
                ]);
            }
        });

        // SLA 24h : fige l'heure du premier contact dès la première sortie de « Nouveau ».
        static::saving(function (Lead $lead): void {
            if ($lead->first_contacted_at !== null) {
                return;
            }

            if ($lead->exists) {
                $original = $lead->getOriginal('status');

                if ($original instanceof LeadStatus) {
                    $original = $original->value;
                }

                if ($original === LeadStatus::Nouveau->value && $lead->status !== LeadStatus::Nouveau) {
                    $lead->first_contacted_at = now();
                }
            } elseif ($lead->status !== LeadStatus::Nouveau) {
                $lead->first_contacted_at = now();
            }
        });
    }

    public function responseTimeInMinutes(): ?int
    {
        if ($this->first_contacted_at === null || $this->created_at === null) {
            return null;
        }

        // SLA ouvrée lun–sam : dimanches et fériés ivoiriens exclus.
        return SlaClock::workingMinutesBetween($this->created_at, $this->first_contacted_at);
    }

    public function respondedWithinHours(int $hours = 24): ?bool
    {
        $minutes = $this->responseTimeInMinutes();

        if ($minutes === null) {
            return null;
        }

        return $minutes <= $hours * 60;
    }

    public function slaDeadline(int $hours = 24): ?Carbon
    {
        if ($this->created_at === null) {
            return null;
        }

        return SlaClock::addWorkingHours($this->created_at, $hours);
    }

    public function isSlaBreached(int $hours = 24): bool
    {
        if ($this->first_contacted_at !== null) {
            return $this->respondedWithinHours($hours) === false;
        }

        $deadline = $this->slaDeadline($hours);

        return $deadline !== null && now()->greaterThan($deadline);
    }

    public function isNextActionOverdue(): bool
    {
        return $this->next_action_at !== null
            && $this->next_action_at->isPast()
            && $this->status instanceof LeadStatus
            && $this->status->isOpen();
    }

    /**
     * @return BelongsTo<Sector, $this>
     */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    /**
     * @return BelongsTo<Expertise, $this>
     */
    public function expertise(): BelongsTo
    {
        return $this->belongsTo(Expertise::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return HasMany<LeadActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->latest();
    }
}
