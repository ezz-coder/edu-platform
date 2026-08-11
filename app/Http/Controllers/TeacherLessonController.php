<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\StudyGroup;
use App\Models\TeacherActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TeacherLessonController extends Controller
{
    public function index(): View
    {
        $lessons = Lesson::forTeacher(auth()->id())
            ->with('group')
            ->ordered()
            ->get();

        return view('teacher.lessons.index', [
            'header' => 'الدروس',
            'lessons' => $lessons,
        ]);
    }

    /**
     * Drag & Drop reorder endpoint for lessons.
     * Accepts orders[lesson_id] = new_position mapping.
     */
    public function reorder(Request $request): RedirectResponse
    {
        $orders = $request->input('orders', []);

        if (empty($orders)) {
            return back();
        }

        DB::transaction(function () use ($orders) {
            foreach ($orders as $lessonId => $newOrder) {
                $lesson = Lesson::where('teacher_id', auth()->id())
                    ->where('id', $lessonId)
                    ->first();
                if ($lesson) {
                    $lesson->update(['display_order' => (int) $newOrder]);
                }
            }
        });

        TeacherActivity::log(auth()->id(), 'lesson_reordered', 'إعادة ترتيب دروس', 'تم إعادة ترتيب الدروس عبر السحب والإفلات');

        return back()->with('success', 'تم حفظ الترتيب الجديد');
    }

    public function moveUp(Lesson $lesson): RedirectResponse
    {
        $this->authorizeLesson($lesson);

        $previous = Lesson::forTeacher(auth()->id())
            ->where('group_id', $lesson->group_id)
            ->where('display_order', '<', $lesson->display_order)
            ->orderByDesc('display_order')
            ->first();

        if ($previous) {
            DB::transaction(function () use ($lesson, $previous) {
                $previousOrder = $previous->display_order;
                $currentOrder = $lesson->display_order;
                $lesson->update(['display_order' => $previousOrder]);
                $previous->refresh();
                $previous->update(['display_order' => $currentOrder]);
            });
        }

        return back();
    }

    public function moveDown(Lesson $lesson): RedirectResponse
    {
        $this->authorizeLesson($lesson);

        $next = Lesson::forTeacher(auth()->id())
            ->where('group_id', $lesson->group_id)
            ->where('display_order', '>', $lesson->display_order)
            ->orderBy('display_order')
            ->first();

        if ($next) {
            DB::transaction(function () use ($lesson, $next) {
                $nextOrder = $next->display_order;
                $currentOrder = $lesson->display_order;
                $lesson->update(['display_order' => $nextOrder]);
                $next->refresh();
                $next->update(['display_order' => $currentOrder]);
            });
        }

        return back();
    }

    public function create(): View
    {
        $groups = StudyGroup::where('teacher_id', auth()->id())
            ->orderBy('title')
            ->get();

        return view('teacher.lessons.create', [
            'header' => 'إضافة درس',
            'groups' => $groups,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'group_id' => ['required', 'exists:study_groups,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $this->authorizeGroup($validated['group_id']);

        $order = $validated['display_order'] ?? 0;
        $maxOrder = Lesson::where('teacher_id', auth()->id())
            ->where('group_id', $validated['group_id'])
            ->max('display_order');
        $order = max(0, $order, ($maxOrder ?? -1) + 1);

        $lesson = Lesson::create([
            'teacher_id' => auth()->id(),
            'group_id' => $validated['group_id'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'display_order' => $order,
        ]);

        TeacherActivity::log(auth()->id(), 'lesson_created', 'إنشاء درس', "تم إنشاء درس {$lesson->title}");

        return redirect()->route('teacher.lessons.index')->with('success', 'تم إنشاء الدرس بنجاح');
    }

    public function edit(Lesson $lesson): View
    {
        $this->authorizeLesson($lesson);

        $groups = StudyGroup::where('teacher_id', auth()->id())
            ->orderBy('title')
            ->get();

        return view('teacher.lessons.edit', [
            'header' => 'تعديل درس',
            'lesson' => $lesson,
            'groups' => $groups,
        ]);
    }

    public function update(Request $request, Lesson $lesson): RedirectResponse
    {
        $this->authorizeLesson($lesson);

        $validated = $request->validate([
            'group_id' => ['required', 'exists:study_groups,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $this->authorizeGroup($validated['group_id']);

        $lesson->update([
            'group_id' => $validated['group_id'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'display_order' => $validated['display_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        TeacherActivity::log(auth()->id(), 'lesson_updated', 'تعديل درس', "تم تعديل درس {$lesson->title}");

        return redirect()->route('teacher.lessons.index')->with('success', 'تم تحديث الدرس بنجاح');
    }

    public function destroy(Lesson $lesson): RedirectResponse
    {
        $this->authorizeLesson($lesson);

        $lesson->resources()->update(['lesson_id' => null]);
        $lesson->delete();

        TeacherActivity::log(auth()->id(), 'lesson_deleted', 'حذف درس', "تم حذف درس {$lesson->title}");

        return redirect()->route('teacher.lessons.index')->with('success', 'تم حذف الدرس بنجاح');
    }

    public function recycleBin(): View
    {
        $deleted = Lesson::onlyTrashed()
            ->forTeacher(auth()->id())
            ->with('group')
            ->latest()
            ->paginate(20);

        return view('teacher.recycle-bin.index', [
            'header' => 'سلة المحذوفات - الدروس',
            'items' => $deleted,
            'type' => 'lesson',
        ]);
    }

    public function restore(Lesson $lesson): RedirectResponse
    {
        $this->authorizeLesson($lesson);

        if ($lesson->trashed()) {
            $lesson->restore();
        }

        TeacherActivity::log(auth()->id(), 'lesson_restored', 'استرجاع درس', "تم استرجاع درس {$lesson->title}");

        return back()->with('success', 'تم استرجاع الدرس بنجاح');
    }

    public function forceDelete(Lesson $lesson): RedirectResponse
    {
        $this->authorizeLesson($lesson);

        if ($lesson->trashed()) {
            $lesson->forceDelete();
        }

        TeacherActivity::log(auth()->id(), 'lesson_permanent_deleted', 'حذف نهائي درس', "تم حذف درس {$lesson->title} نهائياً");

        return back()->with('success', 'تم الحذف النهائي بنجاح');
    }

    private function authorizeLesson(Lesson $lesson): void
    {
        if ($lesson->teacher_id !== auth()->id()) {
            abort(403);
        }
    }

    private function authorizeGroup(int $groupId): void
    {
        $group = StudyGroup::where('id', $groupId)
            ->where('teacher_id', auth()->id())
            ->firstOrFail();
    }
}