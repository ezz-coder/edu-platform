<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['study_group_id', 'teacher_id', 'lesson_number', 'academic_term_id', 'attendance_date', 'started_at', 'closed_at', 'status'])]
class AttendanceSession extends Model
{
    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'started_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class, 'study_group_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class, 'academic_term_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'attendance_session_id');
    }

    /**
     * Number of students recorded in this session.
     */
    public function recordedCount(): int
    {
        return $this->records()->count();
    }

    /**
     * Whether the session is currently open.
     */
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}