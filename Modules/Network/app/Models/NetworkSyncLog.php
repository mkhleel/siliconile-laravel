<?php

declare(strict_types=1);

namespace Modules\Network\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Membership\Models\Member;
use Modules\Network\Enums\SyncAction;

/**
 * Network Sync Log Model.
 *
 * Tracks all Mikrotik synchronization operations for auditing.
 *
 * @property int $id
 * @property int|null $member_id
 * @property SyncAction $action
 * @property string $status
 * @property string|null $error_message
 * @property array|null $metadata
 * @property string|null $router_ip
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class NetworkSyncLog extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'network_sync_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'member_id',
        'action',
        'status',
        'error_message',
        'metadata',
        'router_ip',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => SyncAction::class,
            'metadata' => 'array',
        ];
    }

    /**
     * Get the member associated with this log entry.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Scope a query to only include successful operations.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope a query to only include failed operations.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope a query to filter by action type.
     */
    public function scopeForAction($query, SyncAction $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope a query to filter by member.
     */
    public function scopeForMember($query, int $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    /**
     * Scope a query to get recent logs.
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
