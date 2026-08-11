<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'teacher_id',
    'lesson_id',
    'group_id',
    'title',
    'body',
    'type',
    'correct_answer',
    'options',
    'explanation',
    'difficulty',
    'points',
    'status',
])]
class QuestionBank extends Model
{
    use SoftDeletes;

    protected $table = 'question_bank';

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'points' => 'integer',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class, 'group_id');
    }

    public function examQuestions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class, 'question_bank_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function scopeForTeacher($query, int $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByDifficulty($query, string $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }
}