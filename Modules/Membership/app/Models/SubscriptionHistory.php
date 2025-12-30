<?php

declare(strict_types=1);

namespace Modules\Membership\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionHistory extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'subscription_history';

    /**
     * Disable updated_at timestamp as we only track creation.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'subscription_id',
        'changed_by',
        'old_status',
        'new_status',
        'reason',
        'metadata',
        'changed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'changed_at' => 'datetime',
        ];
    }

    /**
     * Get the subscription that this history record belongs to.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Get the user who made this change.
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Create a history record for a subscription status change.
     */
    public static function logStatusChange(
        int $subscriptionId,
        ?string $oldStatus,
        string $newStatus,
        ?string $reason = null,
        ?int $changedBy = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'subscription_id' => $subscriptionId,
            'changed_by' => $changedBy,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'reason' => $reason,
            'metadata' => $metadata,
            'changed_at' => now(),
        ]);
    }
}
