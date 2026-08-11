<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['teacher_id', 'student_id', 'assistant_id', 'group_id', 'type', 'title', 'message', 'link', 'read_at'])]
class Notification extends Model
{
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function assistant(): BelongsTo
    {
        return $this->belongsTo(Assistant::class, 'assistant_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class, 'group_id');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Central type definition for this notification (icon/color/label).
     */
    public function typeDef(): array
    {
        return config("notifications.types.{$this->type}", config('notifications.types.system'));
    }

    public function icon(): string
    {
        return $this->typeDef()['icon'];
    }

    public function color(): string
    {
        return $this->typeDef()['color'];
    }

    public function hex(): string
    {
        return $this->typeDef()['hex'];
    }

    public function actionLabel(): string
    {
        return $this->typeDef()['action'] ?? 'عرض';
    }

    /**
     * Human-friendly relative time in Arabic (no raw timestamps).
     */
    public function relativeTime(): string
    {
        $diff = now()->diffInSeconds($this->created_at);

        if ($diff < 60) {
            return 'الآن';
        }
        if ($diff < 3600) {
            return 'قبل ' . ceil($diff / 60) . ' دقيقة';
        }
        if ($diff < 86400) {
            return 'قبل ' . ceil($diff / 3600) . ' ساعة';
        }
        if ($diff < 172800) {
            return 'أمس';
        }
        if ($diff < 604800) {
            return 'قبل ' . ceil($diff / 86400) . ' أيام';
        }

        return $this->created_at->translatedFormat('j M Y');
    }

    /**
     * Create a notification for a teacher (and optionally a student or assistant).
     */
    public static function notify(
        int $teacherId,
        string $title,
        ?string $message = null,
        ?int $studentId = null,
        ?int $groupId = null,
        string $type = 'system',
        ?string $link = null,
        ?int $assistantId = null
    ): self {
        return self::create([
            'teacher_id' => $teacherId,
            'student_id' => $studentId,
            'assistant_id' => $assistantId,
            'group_id' => $groupId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
        ]);
    }
}