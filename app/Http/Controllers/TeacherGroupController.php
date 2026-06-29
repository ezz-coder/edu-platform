<?php

namespace App\Http\Controllers;

use App\Models\StudyGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherGroupController extends Controller
{
    public function index(): View
    {
        $groups = StudyGroup::where('teacher_id', auth()->id())->latest()->get();

        return view('teacher.groups.index', [
            'header' => 'مجموعاتي',
            'groups' => $groups,
        ]);
    }

    public function create(): View
    {
        return view('teacher.groups.create', [
            'header' => 'إنشاء مجموعة جديدة',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'day' => 'required|string',
            'start_time' => 'required',
            'end_time' => 'required',
            'gender_type' => 'required|in:boys,girls,mixed',
            'grade_level' => 'required|string',
        ]);

        StudyGroup::create([
            ...$validated,
            'teacher_id' => auth()->id(),
        ]);

        return redirect()->route('teacher.groups.index')->with('success', 'تم إنشاء المجموعة بنجاح');
    }

    public function edit(StudyGroup $group): View
    {
        $this->authorizeGroup($group);

        return view('teacher.groups.edit', [
            'header' => 'تعديل المجموعة',
            'group' => $group,
        ]);
    }

    public function update(Request $request, StudyGroup $group): RedirectResponse
    {
        $this->authorizeGroup($group);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'day' => 'required|string',
            'start_time' => 'required',
            'end_time' => 'required',
            'gender_type' => 'required|in:boys,girls,mixed',
            'grade_level' => 'required|string',
            'status' => 'required|in:active,inactive',
        ]);

        $group->update($validated);

        return redirect()->route('teacher.groups.index')->with('success', 'تم تحديث المجموعة بنجاح');
    }

    public function destroy(StudyGroup $group): RedirectResponse
    {
        $this->authorizeGroup($group);

        $group->delete();

        return redirect()->route('teacher.groups.index')->with('success', 'تم حذف المجموعة بنجاح');
    }

    private function authorizeGroup(StudyGroup $group): void
    {
        if ($group->teacher_id !== auth()->id()) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه المجموعة');
        }
    }
}
