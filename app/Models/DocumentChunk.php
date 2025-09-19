<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentChunk extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'document_id',
        'content',
        'embedding',
        'chunk_index',
        'token_count',
        'metadata',
    ];

    protected $casts = [
        'embedding' => 'array',
        'metadata' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function hasEmbedding(): bool
    {
        return !empty($this->embedding);
    }

    public function getPreview(int $length = 100): string
    {
        return substr($this->content, 0, $length) . (strlen($this->content) > $length ? '...' : '');
    }
}
