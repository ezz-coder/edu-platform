<?php

namespace App\Http\Controllers;

use App\Models\GroupEnrollment;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Sprint 68 — Teacher ↔ Student direct messaging.
 *
 * Rules (per specification: "direct messaging between teacher-student"):
 *  - 1:1 threads only, between a teacher and his own enrolled students.
 *  - read/unread state per side.
 *  - recipient receives a notification via the existing notification system.
 *  - Assistants and parents have no access to messaging.
 */
class TeacherMessageController extends Controller
{
    public function index()
    {
        $teacher = auth()->user();

        // Students currently enrolled in this teacher's groups.
        $students = User::where('role', 'student')
            ->whereHas('enrollments', fn ($q) => $q
                ->where('status', 'enrolled')
                ->whereHas('group', fn ($g) => $g->where('teacher_id', $teacher->id)))
            ->orderBy('name')
            ->get(['id', 'name', 'student_code']);

        $threads = $students->map(function ($student) use ($teacher) {
            $last = Message::where('teacher_id', $teacher->id)
                ->where('student_id', $student->id)
                ->latest()
                ->first();

            return (object) [
                'student' => $student,
                'last' => $last,
                'unread' => Message::unreadCount($teacher->id, Message::SENDER_TEACHER, $student->id),
            ];
        })->sortByDesc(fn ($t) => $t->last?->created_at ?? $t->student->created_at)->values();

        return view('teacher.messages.index', [
            'threads' => $threads,
            'totalUnread' => Message::unreadCount($teacher->id, Message::SENDER_TEACHER),
        ]);
    }

    public function show(User $student)
    {
        $teacher = auth()->user();

        abort_unless($this->isEnrolledStudent($teacher->id, $student->id), 403);

        Message::markThreadRead($teacher->id, $student->id, Message::SENDER_TEACHER);

        $messages = Message::where('teacher_id', $teacher->id)
            ->where('student_id', $student->id)
            ->orderBy('created_at')
            ->get();

        return view('teacher.messages.show', [
            'student' => $student,
            'messages' => $messages,
        ]);
    }

    public function store(Request $request, User $student)
    {
        $teacher = auth()->user();

        abort_unless($this->isEnrolledStudent($teacher->id, $student->id), 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        Message::create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'sender_type' => Message::SENDER_TEACHER,
            'body' => $data['body'],
        ]);

        \App\Models\Notification::notify(
            $teacher->id,
            'رسالة جديدة من المدرس',
            mb_substr($data['body'], 0, 80),
            $student->id,
            null,
            'message',
            route('student.messages.show', $teacher->id)
        );

        return redirect()->route('teacher.messages.show', $student->id)
            ->with('success', 'تم إرسال الرسالة');
    }

    private function isEnrolledStudent(int $teacherId, int $studentId): bool
    {
        return GroupEnrollment::where('student_id', $studentId)
            ->where('status', 'enrolled')
            ->whereHas('group', fn ($q) => $q->where('teacher_id', $teacherId))
            ->exists();
    }
}