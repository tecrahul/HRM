<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    public const TYPE_DIRECT = 'direct';
    public const TYPE_BROADCAST_ALL = 'broadcast_all';
    public const TYPE_BROADCAST_TEAM = 'broadcast_team';
    public const TYPE_BROADCAST_TARGETED = 'broadcast_targeted';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'created_by_user_id',
        'direct_user_low_id',
        'direct_user_high_id',
        'subject',
        'last_message_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function directLowUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'direct_user_low_id');
    }

    public function directHighUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'direct_user_high_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
