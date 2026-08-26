<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['teacher_id', 'student_id', 'sender_type', 'body', 'read_at'])]
class Message extends Model
{
    public const SENDER_TEACHER = 'teacher';
    public const SENDER_STUDENT = 'student';

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

    /**
     * Mark all messages in a thread as read for the given viewer side.
     * The teacher reads messages sent by the student and vice versa.
     */
    public static function markThreadRead(int $teacherId, int $studentId, string $viewerType): void
    {
        $sender = $viewerType === self::SENDER_TEACHER ? self::SENDER_STUDENT : self::SENDER_TEACHER;

        self::where('teacher_id', $teacherId)
            ->where('student_id', $studentId)
            ->where('sender_type', $sender)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Unread count of messages sent by the other side of the thread.
     */
    public static function unreadCount(int $viewerId, string $viewerType, ?int $counterpartId = null): int
    {
        $sender = $viewerType === self::SENDER_TEACHER ? self::SENDER_STUDENT : self::SENDER_TEACHER;

        return self::query()
            ->when($viewerType === self::SENDER_TEACHER,
                fn ($q) => $q->where('teacher_id', $viewerId),
                fn ($q) => $q->where('student_id', $viewerId))
            ->when($counterpartId !== null,
                fn ($q) => $viewerType === self::SENDER_TEACHER
                    ? $q->where('student_id', $counterpartId)
                    : $q->where('teacher_id', $counterpartId))
            ->where('sender_type', $sender)
            ->whereNull('read_at')
            ->count();
    }
}