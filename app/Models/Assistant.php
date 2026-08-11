<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['teacher_id', 'name', 'username', 'password', 'phone', 'status'])]
#[Hidden(['password'])]
class Assistant extends Authenticatable
{
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function permissions(): HasOne
    {
        return $this->hasOne(AssistantPermission::class, 'assistant_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Virtual role so shared views (dashboard layout, composers) can treat
     * the assistant uniformly with web users via $currentUser->role.
     */
    public function getRoleAttribute(): string
    {
        return 'assistant';
    }

    /**
     * Check an explicit permission on the assistant's permission record.
     */
    public function canPerm(string $permission): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        return (bool) ($this->permissions?->{$permission} ?? false);
    }
}