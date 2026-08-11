<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\GroupEnrollment;
use App\Models\Lesson;
use App\Models\Resource;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\View\View;

class StudentResourceController extends Controller
{
    /**
     * Student home = resources (not dashboard).
     * Shows: General Resources first, then Lessons ordered by teacher, then exams.
     */
    public function home(): View
    {
        $account = auth()->user()->activeStudentAccount();
        $group = $this->activeGroup($account);

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
            ->where(function ($q) use ($group) {
                $q->whereHas('targets', function ($t) use ($group) {
                    $t->where('scope', 'all');
                    if ($group) {
                        $t->orWhere(fn ($q2) => $q2->where('scope', 'group')->where('group_id', $group->id));
                        $t->orWhere(fn ($q2) => $q2->where('scope', 'stage')->where('stage', $group->grade_level));
                        $t->orWhere(fn ($q2) => $q2->where('scope', 'gender')->where('gender', $group->gender_type));
                    }
                });
            });

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

        return view('student.resources.home', [
            'header' => 'المصادر',
            'account' => $account,
            'group' => $group,
            'generalResources' => $generalResources,
            'lessons' => $lessons,
            'exams' => $exams,
        ]);
    }

    /**
     * Resource details page (type-aware actions).
     */
    public function show(Resource $resource): View
    {
        $account = auth()->user()->activeStudentAccount();
        $group = $this->activeGroup($account);

        if (! $resource->isVisible() || ! $group || ! $resource->targetsGroup($group)) {
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