<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Sprint 68 — Student side of teacher ↔ student messaging.
 * The student can only message teachers he is enrolled with,
 * and only sees his own threads.
 */
class StudentMessageController extends Controller
{
    public function index()
    {
        $account = auth()->user()->activeStudentAccount();
        abort_unless($account, 403);

        // Teachers of the active account's enrollments.
        $teachers = User::whereIn('id', function ($q) use ($account) {
            $q->select('teacher_id')
                ->from('study_groups')
                ->whereIn('id', function ($q2) use ($account) {
                    $q2->select('group_id')->from('group_enrollments')
                        ->where('student_id', $account->id);
                });
        })->get(['id', 'name']);

        $threads = $teachers->map(function ($teacher) use ($account) {
            $last = Message::where('teacher_id', $teacher->id)
                ->where('student_id', $account->id)
                ->latest()
                ->first();

            return (object) [
                'teacher' => $teacher,
                'last' => $last,
                'unread' => Message::unreadCount($account->id, Message::SENDER_STUDENT, $teacher->id),
            ];
        })->values();

        return view('student.messages.index', [
            'threads' => $threads,
            'totalUnread' => Message::unreadCount($account->id, Message::SENDER_STUDENT),
        ]);
    }

    public function show(User $teacher)
    {
        $account = auth()->user()->activeStudentAccount();
        abort_unless($account && $this->isEnrolledTeacher($account->id, $teacher->id), 403);

        Message::markThreadRead($teacher->id, $account->id, Message::SENDER_STUDENT);

        $messages = Message::where('teacher_id', $teacher->id)
            ->where('student_id', $account->id)
            ->orderBy('created_at')
            ->get();

        return view('student.messages.show', [
            'teacher' => $teacher,
            'messages' => $messages,
        ]);
    }

    public function store(Request $request, User $teacher)
    {
        $account = auth()->user()->activeStudentAccount();
        abort_unless($account && $this->isEnrolledTeacher($account->id, $teacher->id), 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        Message::create([
            'teacher_id' => $teacher->id,
            'student_id' => $account->id,
            'sender_type' => Message::SENDER_STUDENT,
            'body' => $data['body'],
        ]);

        \App\Models\Notification::notify(
            $teacher->id,
            'رسالة جديدة من طالب',
            "{$account->name}: " . mb_substr($data['body'], 0, 60),
            null,
            null,
            'message',
            route('teacher.messages.show', $account->id)
        );

        return redirect()->route('student.messages.show', $teacher->id)
            ->with('success', 'تم إرسال الرسالة');
    }

    private function isEnrolledTeacher(int $studentId, int $teacherId): bool
    {
        return \App\Models\GroupEnrollment::where('student_id', $studentId)
            ->whereHas('group', fn ($q) => $q->where('teacher_id', $teacherId))
            ->exists();
    }
}