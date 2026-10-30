<?php

namespace App\Models;

use App\Enums\ContactMessageStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'status' => ContactMessageStatus::class,
        'read_at' => 'datetime',
        'replied_at' => 'datetime',
    ];

    public function scopeInbox(Builder $query): Builder
    {
        return $query->whereIn('status', [
            ContactMessageStatus::New,
            ContactMessageStatus::Read,
            ContactMessageStatus::Replied,
        ]);
    }

    public function scopeNewMessages(Builder $query): Builder
    {
        return $query->where('status', ContactMessageStatus::New);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('subject', 'like', "%{$term}%")
                ->orWhere('message', 'like', "%{$term}%");
        });
    }

    public function markRead(): void
    {
        $this->status = ContactMessageStatus::Read;
        $this->read_at = now();
        $this->save();
    }

    public function markUnread(): void
    {
        $this->status = ContactMessageStatus::New;
        $this->read_at = null;
        $this->save();
    }

    public function markReplied(): void
    {
        $this->status = ContactMessageStatus::Replied;
        $this->replied_at = now();
        $this->save();
    }

    public function archive(): void
    {
        $this->status = ContactMessageStatus::Archived;
        $this->save();
    }

    public function markSpam(): void
    {
        $this->status = ContactMessageStatus::Spam;
        $this->save();
    }

    public function restoreToInbox(): void
    {
        $this->status = ContactMessageStatus::New;
        $this->read_at = null;
        $this->replied_at = null;
        $this->save();
    }
}
