<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'project_id',
        'original_name',
        'storage_path',
        'mime_type',
        'size',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(DocumentChunk::class);
    }

    public function isProcessed(): bool
    {
        return $this->status === 'completed';
    }

    public function isProcessing(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function getChunkCount(): int
    {
        return $this->chunks()->count();
    }
}
