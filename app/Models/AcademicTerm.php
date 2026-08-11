<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['teacher_id', 'title', 'year', 'term_number', 'status'])]
class AcademicTerm extends Model
{
    protected function casts(): array
    {
        return [
            'term_number' => 'integer',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(StudyGroup::class, 'academic_term_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    /**
     * Create the default first term for a teacher (2026 / First Term / active).
     */
    public static function createDefaultFor(int $teacherId): self
    {
        return self::create([
            'teacher_id' => $teacherId,
            'title' => 'الترم الأول',
            'year' => '2026',
            'term_number' => 1,
            'status' => 'active',
        ]);
    }
}