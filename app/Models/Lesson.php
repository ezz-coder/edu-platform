<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['teacher_id', 'group_id', 'title', 'description', 'display_order', 'is_active'])]
class Lesson extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class, 'group_id');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class, 'lesson_id');
    }

    public function scopeForTeacher($query, int $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('id');
    }
}