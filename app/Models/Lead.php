<?php

namespace App\Models;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\RequestType;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property LeadStatus $status
 * @property RequestType $request_type
 * @property LeadSource $source
 */
#[Fillable(['name', 'company', 'email', 'phone', 'sector_id', 'request_type', 'budget', 'message', 'status', 'source', 'assigned_to', 'notes', 'consent_at', 'consent_ip'])]
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
            'consent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Sector, $this>
     */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
