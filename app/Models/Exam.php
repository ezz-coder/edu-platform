<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'teacher_id',
    'academic_term_id',
    'title',
    'description',
    'duration_minutes',
    'starts_at',
    'ends_at',
    'status',
    'show_result_immediately',
    'allow_review_after_finish',
])]
class Exam extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'duration_minutes' => 'integer',
            'show_result_immediately' => 'boolean',
            'allow_review_after_finish' => 'boolean',
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

    public function questions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class, 'exam_id')->orderBy('sort_order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class, 'exam_id');
    }

    public function targetGroups(): BelongsToMany
    {
        return $this->belongsToMany(StudyGroup::class, 'exam_groups', 'exam_id', 'group_id')
            ->withTimestamps();
    }

    public function isVisible(): bool
    {
        return $this->status === 'visible';
    }

    public function isOpen(): bool
    {
        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }
        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    public function totalMarks(): int
    {
        return $this->questions()->sum('mark');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function canRestore(): bool
    {
        if (! $this->trashed()) {
            return false;
        }

        return $this->deleted_at->diffInDays(now()) <= 7;
    }

    public function canForceDelete(): bool
    {
        if (! $this->trashed()) {
            return false;
        }

        return $this->deleted_at->diffInDays(now()) > 7;
    }
}