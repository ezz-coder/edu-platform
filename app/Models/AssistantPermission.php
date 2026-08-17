<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'assistant_id',
    'can_take_attendance',
    'can_manage_students',
    'can_upload_resources',
    'can_manage_reservations',
    'can_restore_students',
    'can_view_groups',
    'can_manage_lessons',
    'can_manage_exams',
    'can_manage_grades',
])]
class AssistantPermission extends Model
{
    protected function casts(): array
    {
        return [
            'can_take_attendance' => 'boolean',
            'can_manage_students' => 'boolean',
            'can_upload_resources' => 'boolean',
            'can_manage_reservations' => 'boolean',
            'can_restore_students' => 'boolean',
            'can_view_groups' => 'boolean',
            'can_manage_lessons' => 'boolean',
            'can_manage_exams' => 'boolean',
            'can_manage_grades' => 'boolean',
        ];
    }

    public function assistant(): BelongsTo
    {
        return $this->belongsTo(Assistant::class, 'assistant_id');
    }
}