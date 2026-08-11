<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['teacher_id', 'academic_term_id', 'lesson_id', 'title', 'description', 'type', 'file_path', 'url', 'publish_date', 'expires_at', 'pinned', 'status'])]
class Resource extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'publish_date' => 'date',
            'expires_at' => 'date',
            'pinned' => 'boolean',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class, 'academic_term_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(ResourceTarget::class, 'resource_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isVisible(): bool
    {
        return $this->status === 'visible' && ! $this->isExpired();
    }

    /**
     * Does this resource target the given group?
     * scope group -> group_id match
     * scope stage -> group grade_level match
     * scope gender -> group gender_type match
     * scope all   -> always
     */
    public function targetsGroup(StudyGroup $group): bool
    {
        foreach ($this->targets as $target) {
            if ($target->scope === 'all') {
                return true;
            }
            if ($target->scope === 'group' && $target->group_id === $group->id) {
                return true;
            }
            if ($target->scope === 'stage' && $target->stage === $group->grade_level) {
                return true;
            }
            if ($target->scope === 'gender' && $target->gender === $group->gender_type) {
                return true;
            }
        }

        return false;
    }
}