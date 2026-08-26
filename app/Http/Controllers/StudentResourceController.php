<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\GroupEnrollment;
use App\Models\Lesson;
use App\Models\Resource;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StudentResourceController extends Controller
{
    /**
     * File resource types that can be downloaded directly.
     */
    private const DOWNLOADABLE_TYPES = ['pdf', 'image', 'zip', 'other'];

    /**
     * Student home = resources (not dashboard).
     * Shows: General Resources first, then Lessons ordered by teacher, then exams.
     */
    public function home(Request $request): View
    {
        $account = auth()->user()->activeStudentAccount();
        $group = $this->activeGroup($account);

        // Sprint 63: search & filters (applied AFTER the visibility/targeting scope below).
        $q = trim((string) $request->query('q', ''));
        $type = (string) $request->query('type', '');
        $lessonId = $request->query('lesson_id');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $hasFilters = $q !== '' || $type !== '' || filled($lessonId) || filled($dateFrom) || filled($dateTo);

        $inactiveSince = $this->inactivityCutoff($account);

        $baseQuery = Resource::query()
            ->where('teacher_id', $group?->teacher_id)
            ->where('status', 'visible')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->when($inactiveSince, function ($q) use ($inactiveSince) {
                $q->where('publish_date', '<', $inactiveSince);
            })
            ->where(function ($qq) use ($group) {
                $qq->whereHas('targets', function ($t) use ($group) {
                    $t->where('scope', 'all');
                    if ($group) {
                        $t->orWhere(fn ($q2) => $q2->where('scope', 'group')->where('group_id', $group->id));
                        $t->orWhere(fn ($q2) => $q2->where('scope', 'stage')->where('stage', $group->grade_level));
                        $t->orWhere(fn ($q2) => $q2->where('scope', 'gender')->where('gender', $group->gender_type));
                    }
                });
            })
            // Sprint 63: search by title.
            ->when($q !== '', fn ($qq) => $qq->where('title', 'like', "%{$q}%"))
            // Sprint 63: filter by type.
            ->when($type !== '', fn ($qq) => $qq->where('type', $type))
            // Sprint 63: filter by lesson.
            ->when(filled($lessonId), fn ($qq) => $qq->where('lesson_id', (int) $lessonId))
            // Sprint 63: filter by publish date range.
            ->when(filled($dateFrom), fn ($qq) => $qq->whereDate('publish_date', '>=', $dateFrom))
            ->when(filled($dateTo), fn ($qq) => $qq->whereDate('publish_date', '<=', $dateTo));

        // General resources = no lesson assigned
        $generalResources = (clone $baseQuery)
            ->whereNull('lesson_id')
            ->orderByDesc('pinned')
            ->orderByDesc('created_at')
            ->get();

        // Lessons with their resources
        $lessons = collect();
        if ($group) {
            $lessons = Lesson::forTeacher($group->teacher_id)
                ->where('group_id', $group->id)
                ->where('is_active', true)
                ->ordered()
                ->get()
                ->map(function ($lesson) use ($baseQuery) {
                    $resources = (clone $baseQuery)
                        ->where('lesson_id', $lesson->id)
                        ->orderByDesc('pinned')
                        ->orderByDesc('created_at')
                        ->get();

                    return [
                        'lesson' => $lesson,
                        'resources' => $resources,
                    ];
                })
                ->filter(fn ($item) => $item['resources']->isNotEmpty());
        }

        // Exams
        $exams = collect();
        if ($group) {
            $exams = Exam::where('teacher_id', $group->teacher_id)
                ->where('status', 'visible')
                ->whereHas('targetGroups', fn ($q) => $q->where('group_id', $group->id))
                ->with(['attempts' => fn ($q) => $q->where('student_id', $account->id)])
                ->latest()
                ->paginate(12);
        }

        // Sprint 68: latest announcements targeted at this student's group.
        $announcements = collect();
        if ($group) {
            $enrollment = GroupEnrollment::where('student_id', $account->id)
                ->where('group_id', $group->id)
                ->where('status', 'enrolled')
                ->first();
            if ($enrollment) {
                $announcements = \App\Models\Announcement::visibleForEnrollment($enrollment);
            }
        }

        // Sprint 69: latest unread notifications summary for this active account.
        $unreadNotifications = collect();
        $unreadCount = 0;
        if ($account) {
            $unreadCount = \App\Models\Notification::where('student_id', $account->id)
                ->whereNull('read_at')
                ->count();
            $unreadNotifications = \App\Models\Notification::where('student_id', $account->id)
                ->whereNull('read_at')
                ->latest()
                ->limit(5)
                ->get();
        }

        // Sprint 63: lessons list for the lesson filter dropdown.
        $filterLessons = collect();
        if ($group) {
            $filterLessons = Lesson::forTeacher($group->teacher_id)
                ->where('group_id', $group->id)
                ->where('is_active', true)
                ->ordered()
                ->get(['id', 'title']);
        }

        return view('student.resources.home', [
            'header' => 'المصادر',
            'account' => $account,
            'group' => $group,
            'generalResources' => $generalResources,
            'announcements' => $announcements,
            'lessons' => $lessons,
            'exams' => $exams,
            // Sprint 69 unread notifications summary.
            'unreadNotifications' => $unreadNotifications,
            'unreadCount' => $unreadCount,
            // Sprint 63 search/filter state.
            'hasFilters' => $hasFilters,
            'filterQ' => $q,
            'filterType' => $type,
            'filterLessonId' => $lessonId,
            'filterDateFrom' => $dateFrom,
            'filterDateTo' => $dateTo,
            'filterLessons' => $filterLessons,
        ]);
    }

    /**
     * Sprint 63: direct download of an allowed file resource without visiting
     * the details page. Same visibility/targeting/expiry rules as show().
     * Streams via Storage so no filesystem paths are exposed.
     */
    public function download(Resource $resource)
    {
        $account = auth()->user()->activeStudentAccount();
        $group = $this->activeGroup($account);

        if (! in_array($resource->type, self::DOWNLOADABLE_TYPES, true) || empty($resource->file_path)) {
            abort(404);
        }

        // Sprint 70: a student may only access resources owned by his own teacher.
        if (! $resource->isVisible() || ! $group || $resource->teacher_id !== $group->teacher_id || ! $resource->targetsGroup($group)) {
            abort(403);
        }

        $inactiveSince = $this->inactivityCutoff($account);
        if ($inactiveSince && $resource->publish_date && $resource->publish_date->gte($inactiveSince)) {
            abort(403);
        }

        if (! Storage::disk('public')->exists($resource->file_path)) {
            abort(404);
        }

        return Storage::disk('public')->download($resource->file_path);
    }

    /**
     * Resource details page (type-aware actions).
     */
    public function show(Resource $resource): View
    {
        $account = auth()->user()->activeStudentAccount();
        $group = $this->activeGroup($account);

        // Sprint 70: a student may only view resources owned by his own teacher.
        if (! $resource->isVisible() || ! $group || $resource->teacher_id !== $group->teacher_id || ! $resource->targetsGroup($group)) {
            abort(403);
        }

        $inactiveSince = $this->inactivityCutoff($account);
        if ($inactiveSince && $resource->publish_date && $resource->publish_date->gte($inactiveSince)) {
            abort(403);
        }

        return view('student.resources.show', [
            'header' => $resource->title,
            'resource' => $resource,
            'group' => $group,
        ]);
    }

    private function activeGroup(?User $account): ?StudyGroup
    {
        if (! $account) {
            return null;
        }

        $enrollment = GroupEnrollment::where('student_id', $account->id)
            ->where('status', 'enrolled')
            ->with('group')
            ->first();

        return $enrollment?->group;
    }

    private function inactivityCutoff(?User $account): ?\Carbon\Carbon
    {
        if (! $account) {
            return null;
        }

        $enrollment = GroupEnrollment::where('student_id', $account->id)
            ->whereIn('status', ['removed', 'inactive_due_to_absence'])
            ->whereNotNull('inactivated_at')
            ->orderByDesc('inactivated_at')
            ->first();

        return $enrollment?->inactivated_at;
    }
}