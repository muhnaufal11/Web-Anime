<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class ContactMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'subject',
        'message',
        'reply',
        'replied_at',
        'view_token',
        'is_closed',
        'closed_at',
    ];

    protected $casts = [
        'replied_at' => 'datetime',
        'closed_at' => 'datetime',
        'is_closed' => 'boolean',
    ];

    /**
     * Get all replies for this conversation
     */
    public function replies()
    {
        return $this->hasMany(ContactReply::class)->orderBy('created_at', 'asc');
    }

    /**
     * Check if conversation has unread admin replies
     */
    public function hasUnreadAdminReplies()
    {
        return $this->replies()->where('is_admin', true)->where('created_at', '>', $this->last_user_read_at ?? '1970-01-01')->exists();
    }

    /**
     * Check if conversation has unread user replies
     */
    public function hasUnreadUserReplies()
    {
        return $this->replies()->where('is_admin', false)->where('created_at', '>', $this->last_admin_read_at ?? '1970-01-01')->exists();
    }
}
