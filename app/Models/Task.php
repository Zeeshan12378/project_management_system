<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'project_id',
        'assigned_to',
        'title',
        'description',
        'status',
        'priority',
        'order',
        'due_date'
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    // PHP 8.4 property hooks in the Model layer
    // (Used directly in Livewire v4 components too — see section 5)
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'todo'        => 'To Do',
            'in_progress' => 'In Progress',
            'review'      => 'In Review',
            'done'        => 'Done',
            default       => ucfirst($this->status),
        };
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
    public function comments()
    {
        return $this->hasMany(Comment::class)->latest();
    }
    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function scopeByStatus($q, string $s)
    {
        return $q->where('status', $s);
    }
}
