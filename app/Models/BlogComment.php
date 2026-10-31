<?php

namespace App\Models;

use App\Enums\BlogCommentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'blog_post_id',
        'name',
        'email',
        'content',
        'status',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => BlogCommentStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class, 'blog_post_id');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', BlogCommentStatus::Approved);
    }

    public function approve(): void
    {
        $this->update([
            'status' => BlogCommentStatus::Approved,
            'approved_at' => now(),
        ]);
    }

    public function reject(): void
    {
        $this->update([
            'status' => BlogCommentStatus::Rejected,
            'approved_at' => null,
        ]);
    }
}
