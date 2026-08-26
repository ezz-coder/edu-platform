<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSession;
use App\Models\Exam;
use App\Models\StudyGroup;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Sprint 67 — Teacher Calendar.
 *
 * Monthly calendar view built ONLY from existing data:
 *  - Recurring weekly group schedule (day / start_time / end_time)
 *  - Attendance sessions (attendance_date)
 *  - Exams with starts_at / ends_at
 *
 * All data is filtered by teacher ownership.
 */
class TeacherCalendarController extends Controller
{
    private const DAY_MAP = [
        'sunday' => 0, 'الأحد' => 0,
        'monday' => 1, 'الاثنين' => 1, 'الإثنين' => 1,
        'tuesday' => 2, 'الثلاثاء' => 2,
        'wednesday' => 3, 'الأربعاء' => 3,
        'thursday' => 4, 'الخميس' => 4,
        'friday' => 5, 'الجمعة' => 5,
        'saturday' => 6, 'السبت' => 6,
    ];

    public function index(Request $request): View
    {
        $teacherId = auth()->id();

        // Month navigation (?month=YYYY-MM), defaults to current month.
        $month = now()->startOfMonth();
        if ($request->filled('month') && preg_match('/^\d{4}-\d{2}$/', $request->input('month'))) {
            try {
                $candidate = Carbon::createFromFormat('Y-m', $request->input('month'))->startOfMonth();
                $month = $candidate;
            } catch (\Throwable) {
                // invalid month → fall back to current month
            }
        }

        $groups = StudyGroup::where('teacher_id', $teacherId)->orderBy('title')->get();

        // Optional group filter.
        $selectedGroupId = $request->filled('group_id')
            ? (int) $request->input('group_id')
            : null;
        if ($selectedGroupId && ! $groups->contains('id', $selectedGroupId)) {
            abort(403);
        }

        $visibleGroups = $selectedGroupId
            ? $groups->where('id', $selectedGroupId)
            : $groups;

        $groupIds = $visibleGroups->pluck('id');

        // Build the month grid (weeks starting Sunday).
        $gridStart = $month->copy()->startOfWeek(Carbon::SUNDAY);
        $weeks = [];
        for ($w = 0; $w < 6; $w++) {
            $weekStart = $gridStart->copy()->addWeeks($w);
            if ($weekStart->month > $month->month && $weekStart->year >= $month->year) {
                break;
            }
            $days = [];
            for ($d = 0; $d < 7; $d++) {
                $date = $weekStart->copy()->addDays($d);
                $days[] = [
                    'date' => $date,
                    'in_month' => $date->month === $month->month,
                    'is_today' => $date->isToday(),
                    'events' => [],
                ];
            }
            $weeks[] = $days;
        }

        // Index days by Y-m-d for fast event assignment.
        $byDate = [];
        foreach ($weeks as &$week) {
            foreach ($week as &$day) {
                $byDate[$day['date']->toDateString()] = &$day;
            }
            unset($day);
        }
        unset($week);

        // 1) Recurring weekly group schedule.
        foreach ($visibleGroups as $group) {
            $dow = self::DAY_MAP[mb_strtolower(trim((string) $group->day))] ?? null;
            if ($dow === null) {
                continue;
            }
            $cursor = $gridStart->copy();
            while ($cursor <= $month->copy()->endOfMonth()) {
                if ($cursor->dayOfWeek === $dow) {
                    $key = $cursor->toDateString();
                    if (isset($byDate[$key])) {
                        $byDate[$key]['events'][] = [
                            'type' => 'schedule',
                            'label' => "حصة {$group->title}",
                            'time' => substr($group->start_time, 0, 5),
                            'color' => 'bg-primary-100 text-primary-700 border-primary-200',
                        ];
                    }
                }
                $cursor->addDay();
            }
        }

        // 2) Attendance sessions in this month.
        if ($groupIds->isNotEmpty()) {
            $sessions = AttendanceSession::whereIn('study_group_id', $groupIds)
                ->whereBetween('attendance_date', [$month->copy()->startOfDay(), $month->copy()->endOfMonth()->endOfDay()])
                ->with('group')
                ->get();

            foreach ($sessions as $session) {
                $key = optional($session->attendance_date)->toDateString();
                if ($key && isset($byDate[$key])) {
                    $byDate[$key]['events'][] = [
                        'type' => 'attendance',
                        'label' => "حضور {$session->group?->title} — درس {$session->lesson_number}",
                        'time' => optional($session->started_at)?->format('H:i'),
                        'status' => $session->status,
                        'url' => route('teacher.attendance.show', $session),
                        'color' => $session->isOpen()
                            ? 'bg-green-100 text-green-700 border-green-200'
                            : 'bg-gray-100 text-text-secondary border-border-light',
                    ];
                }
            }

            // 3) Exams overlapping this month.
            $exams = Exam::where('teacher_id', $teacherId)
                ->where(function ($q) use ($month) {
                    $q->whereBetween('starts_at', [$month->copy()->startOfDay(), $month->copy()->endOfMonth()->endOfDay()])
                        ->orWhereBetween('ends_at', [$month->copy()->startOfDay(), $month->copy()->endOfMonth()->endOfDay()])
                        ->orWhere(function ($q2) use ($month) {
                            $q2->where('starts_at', '<', $month->copy()->startOfDay())
                                ->where('ends_at', '>', $month->copy()->endOfMonth()->endOfDay());
                        });
                })
                ->whereIn('status', ['draft', 'visible'])
                ->with('targetGroups')
                ->get();

            foreach ($exams as $exam) {
                $targets = $exam->targetGroups->pluck('id');
                if ($selectedGroupId && ! $targets->contains($selectedGroupId)) {
                    continue;
                }
                $start = $exam->starts_at ?? $month->copy()->startOfMonth();
                $key = $start->toDateString();
                if (isset($byDate[$key])) {
                    $byDate[$key]['events'][] = [
                        'type' => 'exam',
                        'label' => "امتحان {$exam->title}",
                        'time' => $start->format('H:i'),
                        'status' => $exam->status,
                        'url' => route('teacher.exams.questions', $exam),
                        'color' => 'bg-warning-50 text-warning-600 border-warning-200',
                    ];
                }
            }
        }

        return view('teacher.calendar.index', [
            'header' => 'التقويم',
            'weeks' => $weeks,
            'month' => $month,
            'prevMonth' => $month->copy()->subMonth(),
            'nextMonth' => $month->copy()->addMonth(),
            'groups' => $groups,
            'selectedGroupId' => $selectedGroupId,
        ]);
    }
}