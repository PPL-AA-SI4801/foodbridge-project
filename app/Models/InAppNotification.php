<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * In-app notifications.
 *
 * The table is named notifications like Laravel's own notification
 * table, but the schema is different, so the model is named
 * InAppNotification to keep it apart. The database channel of the
 * Notifiable trait must not be used against this table.
 */
#[Table('notifications')]
#[Fillable(['judul', 'pesan', 'tipe', 'is_read', 'user_id'])]
class InAppNotification extends Model
{
    /**
     * The default attribute values for a new notification.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_read' => false,
    ];

    /**
     * The table has no updated_at column.
     */
    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    /**
     * Limit the query to notifications that have not been read yet.
     */
    public function scopeUnread(Builder $query): void
    {
        $query->where('is_read', false);
    }

    /**
     * Mark this notification as read.
     */
    public function markAsRead(): bool
    {
        if ($this->is_read) {
            return true;
        }

        return $this->update(['is_read' => true]);
    }

    /**
     * The account that receives this notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
