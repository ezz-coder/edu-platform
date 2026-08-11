<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['attendance_session_id', 'student_id', 'group_id', 'attendance_type', 'scanned_at'])]
class AttendanceRecord extends Model
{
    /**
     * attendance_type values:
     *  - present : scanned during an open session
     *  - late    : recorded late (after closing) or marked present manually
     *  - absent  : created automatically when the session is closed
     */

    protected function casts(): array
    {
        return [
            'scanned_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class, 'attendance_session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class, 'group_id');
    }

    public function isLate(): bool
    {
        return $this->attendance_type === 'late';
    }

    public function isPresent(): bool
    {
        return in_array($this->attendance_type, ['present', 'late']);
    }

    public function isAbsent(): bool
    {
        return $this->attendance_type === 'absent';
    }
}
