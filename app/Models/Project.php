<?php

namespace App\Models;

use App\Traits\HasApiDateFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory, HasApiDateFormat;

    protected $fillable = [
        'account_id',
        'user_id',
        'name',
        'description',
        'status',
        'phase',
        'zoning',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getDocumentCount(): int
    {
        return $this->documents()->count();
    }

    public function getProcessedDocumentCount(): int
    {
        return $this->documents()->where('status', 'completed')->count();
    }

    /**
     * Get project members using the project_members pivot table
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'project_members', 'project_id', 'user_id')
                    ->withPivot(['is_lead'])
                    ->withTimestamps();
    }

    /**
     * Check if a user is a member of this project
     * Checks both project ownership and project_members assignment
     */
    public function hasMember(User $user): bool
    {
        // Check if user is project owner
        if ($this->user_id === $user->id) {
            return true;
        }
        
        // Check if user is in project_members table
        return $this->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Query helper for project visibility based on user role
     * Admin: sees all projects in account
     * Team: sees only projects where they are assigned as members
     */
    public static function forUser(User $user)
    {
        $role = $user->roleIn($user->account);
        
        return static::query()
            ->where('account_id', $user->account_id)
            ->when($role === 'team', function ($query) use ($user) {
                $query->where(function ($q) use ($user) {
                    // Include projects owned by user OR where they are a member
                    $q->where('user_id', $user->id)
                      ->orWhereHas('members', fn($m) => $m->where('user_id', $user->id));
                });
            });
            // Admin gets no additional filtering - sees all projects in account
    }
}
