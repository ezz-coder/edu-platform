<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'exam_id',
    'question_bank_id',
    'type',
    'question',
    'option_a',
    'option_b',
    'option_c',
    'option_d',
    'correct_answer',
    'mark',
    'sort_order',
])]
class ExamQuestion extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'mark' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }

    public function questionBank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class, 'question_id');
    }

    public function options(): array
    {
        return [
            'a' => $this->option_a,
            'b' => $this->option_b,
            'c' => $this->option_c,
            'd' => $this->option_d,
        ];
    }
}