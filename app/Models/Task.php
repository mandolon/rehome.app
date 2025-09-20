<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Task extends Model
{
    use HasFactory;

    protected $table = 'tasks';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'category',
        'status',
        'assignee_id',
        'created_by_id',
        'due_date',
        'allow_client',
        'files_count',
        'comments_count',
    ];

    protected $casts = [
        'id' => 'string',
        'project_id' => 'string',
        'due_date' => 'date',
        'allow_client' => 'boolean',
        'files_count' => 'integer',
        'comments_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::uuid()->toString();
            }
        });
    }

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function files(): BelongsToMany
    {
        return $this->belongsToMany(File::class, 'task_files')
            ->using(TaskFile::class)
            ->withPivot('attachment_type', 'notes', 'added_by_id')
            ->withTimestamps();
    }

    public function taskFiles(): HasMany
    {
        return $this->hasMany(TaskFile::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TaskActivity::class)->orderBy('created_at', 'desc');
    }

    public function activity(): HasMany
    {
        return $this->hasMany(TaskActivity::class)->orderBy('created_at', 'desc');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeComplete($query)
    {
        return $query->where('status', 'complete');
    }

    public function scopeClientVisible($query)
    {
        return $query->where('allow_client', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())->where('status', '!=', 'complete');
    }

    // Accessors & Mutators
    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date && $this->due_date->isPast() && $this->status !== 'complete';
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'open' => 'yellow',
            'complete' => 'green',
            default => 'gray',
        };
    }

    public function getCategoryColorAttribute(): string
    {
        return match($this->category) {
            'TASK/REDLINE' => 'red',
            'PROGRESS/UPDATE' => 'blue',
            default => 'gray',
        };
    }

    // Helper Methods
    public function markAsComplete(User $user): void
    {
        $this->update(['status' => 'complete']);
        
        $this->activities()->create([
            'user_id' => $user->id,
            'action_type' => 'completed',
            'comment' => 'Task marked as complete',
            'is_system' => true,
        ]);
    }

    public function assignTo(User $assignee, User $assignedBy): void
    {
        $oldAssignee = $this->assignee;
        
        $this->update(['assignee_id' => $assignee->id]);
        
        $this->activities()->create([
            'user_id' => $assignedBy->id,
            'action_type' => 'assigned',
            'comment' => "Task assigned to {$assignee->name}",
            'metadata' => [
                'old_assignee' => $oldAssignee?->name,
                'new_assignee' => $assignee->name,
            ],
            'is_system' => true,
        ]);
    }

    public function addComment(User $user, string $comment): TaskActivity
    {
        $activity = $this->activities()->create([
            'user_id' => $user->id,
            'action_type' => 'commented',
            'comment' => $comment,
            'is_system' => false,
        ]);

        $this->increment('comments_count');

        return $activity;
    }

    public function attachFile(File $file, User $user, string $type = 'attachment', ?string $notes = null): TaskFile
    {
        $taskFile = TaskFile::create([
            'task_id' => $this->id,
            'file_id' => $file->id,
            'added_by_id' => $user->id,
            'attachment_type' => $type,
            'notes' => $notes,
        ]);

        $this->activities()->create([
            'user_id' => $user->id,
            'action_type' => 'file_attached',
            'comment' => "Attached file: {$file->original_name}",
            'metadata' => [
                'file_name' => $file->original_name,
                'file_type' => $file->mime_type,
                'attachment_type' => $type,
            ],
            'is_system' => true,
        ]);

        // files_count will be incremented automatically by TaskFile model events

        return $taskFile;
    }
}
