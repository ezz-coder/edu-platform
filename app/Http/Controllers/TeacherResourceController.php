<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Notification;
use App\Models\Resource;
use App\Models\ResourceTarget;
use App\Models\StudyGroup;
use App\Models\TeacherActivity;
use App\Utilities\NotificationHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherResourceController extends Controller
{
    private const TYPES = ['pdf', 'image', 'zip', 'google_drive', 'external_link', 'video_link', 'other'];

    private function activeTermId(): ?int
    {
        $term = \App\Models\AcademicTerm::where('teacher_id', auth()->id())
            ->where('status', 'active')
            ->first();

        return $term?->id;
    }

    public function index(): View
    {
        $resources = Resource::where('teacher_id', auth()->id())
            ->with(['targets', 'lesson'])
            ->latest()
            ->paginate(15);

        return view('teacher.resources.index', [
            'header' => 'المصادر',
            'resources' => $resources,
        ]);
    }

    public function create(): View
    {
        $groups = StudyGroup::where('teacher_id', auth()->id())
            ->orderBy('title')
            ->get();
        $stages = StudyGroup::where('teacher_id', auth()->id())
            ->distinct('grade_level')->pluck('grade_level')->filter();
        $lessons = Lesson::forTeacher(auth()->id())
            ->with('group')
            ->ordered()
            ->get();

        return view('teacher.resources.create', [
            'header' => 'إنشاء مصدر',
            'groups' => $groups,
            'stages' => $stages,
            'lessons' => $lessons,
            'types' => self::TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:' . implode(',', self::TYPES)],
            'file' => ['nullable', 'file', 'max:20480'],
            'url' => ['nullable', 'string', 'max:1000'],
            'lesson_id' => ['nullable', 'exists:lessons,id'],
            'publish_date' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:publish_date'],
            'pinned' => ['nullable', 'boolean'],
            'target_scope' => ['required', 'in:group,stage,gender,all'],
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['integer', 'exists:study_groups,id'],
            'stage' => ['nullable', 'string'],
            'gender' => ['nullable', 'in:boys,girls,mixed'],
        ]);

        if (($validated['lesson_id'] ?? null) !== null) {
            $lesson = Lesson::where('id', $validated['lesson_id'])
                ->where('teacher_id', auth()->id())
                ->firstOrFail();
        }

        $filePath = null;
        if (in_array($validated['type'], ['pdf', 'image', 'zip', 'other']) && $request->hasFile('file')) {
            $filePath = $request->file('file')->store('resources', 'public');
        }

        $resource = Resource::create([
            'teacher_id' => auth()->id(),
            'academic_term_id' => $this->activeTermId(),
            'lesson_id' => $validated['lesson_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'type' => $validated['type'],
            'file_path' => $filePath,
            'url' => $validated['url'] ?? null,
            'publish_date' => $validated['publish_date'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'pinned' => $request->boolean('pinned'),
            'status' => 'visible',
        ]);

        $this->syncTargets($resource, $validated);
        $this->enforcePinLimit();

        TeacherActivity::log(auth()->id(), 'resource_created', 'إنشاء مصدر', "تم إنشاء مصدر {$resource->title}");

        $students = NotificationHelper::resourceStudentIds($resource);
        NotificationHelper::notifyStudents(
            auth()->id(),
            $students,
            'resource',
            'مصدر جديد',
            "تم نشر مصدر جديد: {$resource->title}",
            route('student.resources.show', $resource)
        );

        NotificationHelper::notifyAssistantsWithPermission(
            auth()->id(),
            'can_upload_resources',
            'resource',
            'مصدر جديد',
            "نشر المدرس مصدراً جديداً: {$resource->title}"
        );

        return redirect()->route('teacher.resources.index')->with('success', 'تم إنشاء المصدر بنجاح');
    }

    public function edit(Resource $resource): View
    {
        $this->authorizeResource($resource);

        $groups = StudyGroup::where('teacher_id', auth()->id())->orderBy('title')->get();
        $stages = StudyGroup::where('teacher_id', auth()->id())
            ->distinct('grade_level')->pluck('grade_level')->filter();
        $lessons = Lesson::forTeacher(auth()->id())
            ->with('group')
            ->ordered()
            ->get();

        return view('teacher.resources.edit', [
            'header' => 'تعديل مصدر',
            'resource' => $resource,
            'groups' => $groups,
            'stages' => $stages,
            'lessons' => $lessons,
            'types' => self::TYPES,
        ]);
    }

    public function update(Request $request, Resource $resource): RedirectResponse
    {
        $this->authorizeResource($resource);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:' . implode(',', self::TYPES)],
            'file' => ['nullable', 'file', 'max:20480'],
            'url' => ['nullable', 'string', 'max:1000'],
            'lesson_id' => ['nullable', 'exists:lessons,id'],
            'publish_date' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:publish_date'],
            'pinned' => ['nullable', 'boolean'],
            'status' => ['required', 'in:visible,hidden,archived'],
            'target_scope' => ['required', 'in:group,stage,gender,all'],
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['integer', 'exists:study_groups,id'],
            'stage' => ['nullable', 'string'],
            'gender' => ['nullable', 'in:boys,girls,mixed'],
        ]);

        if (($validated['lesson_id'] ?? null) !== null) {
            $lesson = Lesson::where('id', $validated['lesson_id'])
                ->where('teacher_id', auth()->id())
                ->firstOrFail();
        }

        $filePath = $resource->file_path;
        if (in_array($validated['type'], ['pdf', 'image', 'zip', 'other']) && $request->hasFile('file')) {
            $filePath = $request->file('file')->store('resources', 'public');
        }

        $resource->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'type' => $validated['type'],
            'file_path' => $filePath,
            'url' => $validated['url'],
            'lesson_id' => $validated['lesson_id'] ?? null,
            'publish_date' => $validated['publish_date'],
            'expires_at' => $validated['expires_at'],
            'pinned' => $request->boolean('pinned'),
            'status' => $validated['status'],
        ]);

        $this->syncTargets($resource, $validated);
        $this->enforcePinLimit();

        TeacherActivity::log(auth()->id(), 'resource_updated', 'تعديل مصدر', "تم تعديل مصدر {$resource->title}");

        return redirect()->route('teacher.resources.index')->with('success', 'تم تحديث المصدر بنجاح');
    }

    public function destroy(Resource $resource): RedirectResponse
    {
        $this->authorizeResource($resource);
        $resource->delete();

        return redirect()->route('teacher.resources.index')->with('success', 'تم حذف المصدر بنجاح');
    }

    public function recycleBin(): View
    {
        $deleted = Resource::onlyTrashed()
            ->where('teacher_id', auth()->id())
            ->latest()
            ->paginate(20);

        return view('teacher.recycle-bin.index', [
            'header' => 'سلة المحذوفات - المصادر',
            'items' => $deleted,
            'type' => 'resource',
        ]);
    }

    public function restore(Resource $resource): RedirectResponse
    {
        $this->authorizeResource($resource);

        if ($resource->trashed()) {
            $resource->restore();
        }

        TeacherActivity::log(auth()->id(), 'resource_restored', 'استرجاع مصدر', "تم استرجاع مصدر {$resource->title}");

        return back()->with('success', 'تم استرجاع المصدر بنجاح');
    }

    public function forceDelete(Resource $resource): RedirectResponse
    {
        $this->authorizeResource($resource);

        if ($resource->trashed()) {
            $resource->forceDelete();
        }

        TeacherActivity::log(auth()->id(), 'resource_permanent_deleted', 'حذف نهائي مصدر', "تم حذف مصدر {$resource->title} نهائياً");

        return back()->with('success', 'تم الحذف النهائي بنجاح');
    }

    public function duplicate(Resource $resource): RedirectResponse
    {
        $this->authorizeResource($resource);

        $copy = Resource::create([
            'teacher_id' => auth()->id(),
            'academic_term_id' => $this->activeTermId(),
            'lesson_id' => $resource->lesson_id,
            'title' => $resource->title . ' (نسخة)',
            'description' => $resource->description,
            'type' => $resource->type,
            'file_path' => $resource->file_path,
            'url' => $resource->url,
            'publish_date' => $resource->publish_date,
            'expires_at' => $resource->expires_at,
            'pinned' => false,
            'status' => 'visible',
        ]);

        foreach ($resource->targets as $target) {
            ResourceTarget::create([
                'resource_id' => $copy->id,
                'scope' => $target->scope,
                'group_id' => $target->group_id,
                'stage' => $target->stage,
                'gender' => $target->gender,
            ]);
        }

        return redirect()->route('teacher.resources.edit', $copy)->with('success', 'تم نسخ المصدر. عدّل الهدف إن لزم.');
    }

    public function togglePin(Resource $resource): RedirectResponse
    {
        $this->authorizeResource($resource);

        $resource->update(['pinned' => ! $resource->pinned]);
        $this->enforcePinLimit();

        return back()->with('success', 'تم تحديث التثبيت');
    }

    private function syncTargets(Resource $resource, array $validated): void
    {
        $resource->targets()->delete();

        $scope = $validated['target_scope'];
        if ($scope === 'group') {
            foreach ($validated['group_ids'] ?? [] as $gid) {
                ResourceTarget::create([
                    'resource_id' => $resource->id,
                    'scope' => 'group',
                    'group_id' => $gid,
                ]);
            }
        } elseif ($scope === 'stage') {
            ResourceTarget::create([
                'resource_id' => $resource->id,
                'scope' => 'stage',
                'stage' => $validated['stage'],
            ]);
        } elseif ($scope === 'gender') {
            ResourceTarget::create([
                'resource_id' => $resource->id,
                'scope' => 'gender',
                'gender' => $validated['gender'],
            ]);
        } else {
            ResourceTarget::create([
                'resource_id' => $resource->id,
                'scope' => 'all',
            ]);
        }
    }

    private function enforcePinLimit(): void
    {
        $pinned = Resource::where('teacher_id', auth()->id())
            ->where('pinned', true)
            ->orderBy('updated_at', 'desc')
            ->get();

        if ($pinned->count() > 5) {
            $pinned->slice(5)->each->update(['pinned' => false]);
        }
    }

    private function authorizeResource(Resource $resource): void
    {
        if ($resource->teacher_id !== auth()->id()) {
            abort(403);
        }
    }
}