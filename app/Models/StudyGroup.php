<?php

namespace App\Models;

use Database\Factories\StudyGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['teacher_id', 'academic_term_id', 'title', 'description', 'day', 'start_time', 'end_time', 'gender_type', 'grade_level', 'max_students', 'status'])]
class StudyGroup extends Model
{
    /** @use HasFactory<StudyGroupFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'max_students' => 'integer',
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

    public function enrollments(): HasMany
    {
        return $this->hasMany(GroupEnrollment::class, 'group_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(GroupEnrollment::class, 'group_id')
            ->where('status', 'enrolled');
    }

    /**
     * Number of currently enrolled (active) students.
     */
    public function currentStudents(): int
    {
        return $this->students()->count();
    }

    /**
     * Remaining seats in the group.
     */
    public function remainingSeats(): int
    {
        return max(0, $this->max_students - $this->currentStudents());
    }

    /**
     * Whether the group has reached its maximum capacity.
     */
    public function isFull(): bool
    {
        return $this->remainingSeats() <= 0;
    }

    /**
     * Scope to only active groups (not archived).
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to archived groups.
     */
    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    /**
     * Scope to non-archived groups.
     */
    public function scopeNotArchived($query)
    {
        return $query->where('status', '!=', 'archived');
    }

    /**
     * Whether the group is archived.
     */
    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }
}