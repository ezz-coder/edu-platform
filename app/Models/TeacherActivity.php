<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['teacher_id', 'type', 'title', 'description'])]
class TeacherActivity extends Model
{
    public $timestamps = false;

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Log an activity for a teacher. Reusable across controllers.
     */
    public static function log(int $teacherId, string $type, string $title, ?string $description = null): void
    {
        self::create([
            'teacher_id' => $teacherId,
            'type' => $type,
            'title' => $title,
            'description' => $description,
        ]);
    }
}