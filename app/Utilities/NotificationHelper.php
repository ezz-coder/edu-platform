<?php

namespace App\Utilities;

use App\Models\Assistant;
use App\Models\GroupEnrollment;
use App\Models\Notification;
use App\Models\Resource;
use App\Models\StudyGroup;

/**
 * Sprint 19 — Smart Notification dispatch helpers.
 *
 * Centralizes the rules so controllers stay thin:
 *  - one notification per student (no duplicates for the same event)
 *  - teacher never receives per-student spam
 *  - assistant only sees what his permissions allow
 */
class NotificationHelper
{
    /**
     * Unique enrolled student ids across the given groups.
     */
    public static function groupStudentIds(array $groupIds): array
    {
        if (empty($groupIds)) {
            return [];
        }

        return GroupEnrollment::whereIn('group_id', $groupIds)
            ->where('status', 'enrolled')
            ->pluck('student_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Enrolled student ids targeted by a resource (group/stage/gender/all).
     */
    public static function resourceStudentIds(Resource $resource): array
    {
        $groups = StudyGroup::where('teacher_id', $resource->teacher_id)->get();
        $targeted = $groups->filter(fn ($g) => $resource->targetsGroup($g))->pluck('id')->all();

        return self::groupStudentIds($targeted);
    }

    /**
     * Send one notification to each student (skip if an unread identical
     * notification already exists — prevents duplicates for the same event).
     */
    public static function notifyStudents(
        int $teacherId,
        array $studentIds,
        string $type,
        string $title,
        string $message,
        ?string $link = null
    ): void {
        if (empty($studentIds)) {
            return;
        }

        $existing = Notification::where('teacher_id', $teacherId)
            ->whereIn('student_id', $studentIds)
            ->where('type', $type)
            ->where('title', $title)
            ->whereNull('read_at')
            ->pluck('student_id')
            ->all();

        foreach (array_unique($studentIds) as $sid) {
            if (in_array($sid, $existing, true)) {
                continue;
            }
            Notification::notify($teacherId, $title, $message, $sid, null, $type, $link);
        }
    }

    /**
     * Sprint 65 — Notify enrolled students of a group when a new lesson is created.
     * Only students actively enrolled in the lesson's group are notified.
     */
    public static function notifyNewLesson(\App\Models\Lesson $lesson): void
    {
        $studentIds = self::groupStudentIds([$lesson->group_id]);

        self::notifyStudents(
            $lesson->teacher_id,
            $studentIds,
            'lesson',
            'درس جديد',
            "تم إضافة درس جديد: {$lesson->title}",
            route('student.resources.home')
        );
    }

    /**
     * Sprint 65 — Notify the affected student only when his grade is edited.
     * Deduplication (same unread type+title) prevents duplicate notifications.
     */
    public static function notifyGradeUpdatedToStudent(\App\Models\ExamAttempt $attempt): void
    {
        self::notifyStudents(
            $attempt->exam->teacher_id,
            [$attempt->student_id],
            'grade_updated',
            "تعديل درجة: {$attempt->exam->title}",
            "تم تعديل درجتك في امتحان {$attempt->exam->title} إلى {$attempt->score}/{$attempt->total_marks}.",
            route('student.grades')
        );
    }

    /**
     * One notification for the teacher (smart, not spammy).
     */
    public static function notifyTeacher(
        int $teacherId,
        string $type,
        string $title,
        string $message,
        ?string $link = null
    ): void {
        Notification::notify($teacherId, $title, $message, null, null, $type, $link);
    }

    /**
     * One notification for an assistant (scoped to his teacher).
     */
    public static function notifyAssistant(
        Assistant $assistant,
        string $type,
        string $title,
        string $message,
        ?string $link = null
    ): void {
        Notification::notify(
            $assistant->teacher_id,
            $title,
            $message,
            null,
            null,
            $type,
            $link,
            $assistant->id
        );
    }

    /**
     * Notify all active assistants of a teacher that hold the given permission.
     */
    public static function notifyAssistantsWithPermission(
        int $teacherId,
        string $permission,
        string $type,
        string $title,
        string $message,
        ?string $link = null
    ): void {
        $assistants = Assistant::where('teacher_id', $teacherId)
            ->where('status', 'active')
            ->with('permissions')
            ->get();

        foreach ($assistants as $assistant) {
            if ($assistant->canPerm($permission)) {
                self::notifyAssistant($assistant, $type, $title, $message, $link);
            }
        }
    }
}