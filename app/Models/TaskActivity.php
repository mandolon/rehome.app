<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TaskActivity extends Model
{
    use HasFactory;

    protected $table = 'task_activity';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'task_id',
        'user_id',
        'action_type',
        'comment',
        'metadata',
        'is_system',
    ];

    protected $casts = [
        'id' => 'string',
        'task_id' => 'string',
        'metadata' => 'array',
        'is_system' => 'boolean',
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
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeComments($query)
    {
        return $query->where('action_type', 'commented');
    }

    public function scopeSystemActivity($query)
    {
        return $query->where('is_system', true);
    }

    public function scopeUserActivity($query)
    {
        return $query->where('is_system', false);
    }

    // Accessors
    public function getActionColorAttribute(): string
    {
        return match($this->action_type) {
            'created' => 'green',
            'updated' => 'blue',
            'completed' => 'green',
            'commented' => 'purple',
            'file_attached' => 'blue',
            'file_removed' => 'red',
            'assigned' => 'yellow',
            'unassigned' => 'gray',
            default => 'gray',
        };
    }

    public function getFormattedMessageAttribute(): string
    {
        if (!$this->is_system) {
            return $this->comment;
        }

        return match($this->action_type) {
            'created' => 'Task created',
            'updated' => $this->formatUpdateMessage(),
            'completed' => 'Task marked as complete',
            'assigned' => $this->comment,
            'unassigned' => 'Task unassigned',
            'file_attached' => $this->comment,
            'file_removed' => $this->comment,
            default => $this->comment,
        };
    }

    private function formatUpdateMessage(): string
    {
        if (!$this->metadata) {
            return 'Task updated';
        }

        $changes = [];
        foreach ($this->metadata as $field => $change) {
            if (is_array($change) && isset($change['old'], $change['new'])) {
                $changes[] = ucfirst($field) . " changed from '{$change['old']}' to '{$change['new']}'";
            }
        }

        return $changes ? implode(', ', $changes) : 'Task updated';
    }
}
