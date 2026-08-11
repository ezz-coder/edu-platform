<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['exam_id', 'student_id', 'started_at', 'finished_at', 'score', 'total_marks', 'status'])]
class ExamAttempt extends Model
{
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'score' => 'integer',
            'total_marks' => 'integer',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class, 'attempt_id');
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isFinished(): bool
    {
        return $this->status === 'finished';
    }

    /**
     * Deadline = started_at + duration_minutes.
     */
    public function deadline(): ?\Carbon\Carbon
    {
        if (! $this->started_at) {
            return null;
        }

        return $this->started_at->copy()->addMinutes($this->exam->duration_minutes);
    }
}