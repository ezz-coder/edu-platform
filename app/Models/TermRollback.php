<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['teacher_id', 'old_term_id', 'new_term_id', 'mode', 'moved_group_ids', 'executed_at'])]
class TermRollback extends Model
{
    protected function casts(): array
    {
        return [
            'moved_group_ids' => 'array',
            'executed_at' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function oldTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class, 'old_term_id');
    }

    public function newTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class, 'new_term_id');
    }
}