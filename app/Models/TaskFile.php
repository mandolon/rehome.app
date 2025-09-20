<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TaskFile extends Model
{
    use HasFactory;

    protected $table = 'task_files';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'task_id',
        'file_id',
        'added_by_id',
        'attachment_type',
        'notes',
    ];

    protected $casts = [
        'id' => 'string',
        'task_id' => 'string',
        'file_id' => 'string',
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

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by_id');
    }

    // Accessors
    public function getTypeColorAttribute(): string
    {
        return match($this->attachment_type) {
            'redline' => 'red',
            'revision' => 'blue',
            'attachment' => 'gray',
            default => 'gray',
        };
    }
}
