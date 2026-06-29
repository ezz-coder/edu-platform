<?php

namespace App\Models;

use Database\Factories\StudyGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['teacher_id', 'title', 'description', 'day', 'start_time', 'end_time', 'gender_type', 'grade_level', 'status'])]
class StudyGroup extends Model
{
    /** @use HasFactory<StudyGroupFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
