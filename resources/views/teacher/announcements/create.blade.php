<x-dashboard-layout>
    <x-slot name="header">إعلان جديد</x-slot>
    <x-slot name="title">الإعلانات</x-slot>

    <div class="max-w-2xl mx-auto">
        <h1 class="text-xl font-bold text-text-primary mb-4">إعلان جديد</h1>

        <form method="POST" action="{{ route('teacher.announcements.store') }}"
              class="bg-bg-card border border-border-light rounded-lg p-6 space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-text-primary mb-1">العنوان</label>
                <input type="text" name="title" required maxlength="255" value="{{ old('title') }}"
                       class="w-full border border-border-light rounded-lg p-2 text-sm">
                @error('title')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-text-primary mb-1">نص الإعلان</label>
                <textarea name="body" rows="5" required maxlength="5000"
                          class="w-full border border-border-light rounded-lg p-2 text-sm">{{ old('body') }}</textarea>
                @error('body')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-text-primary mb-1">المجموعات المستهدفة</label>
                <div class="space-y-1">
                    @foreach($groups as $group)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="group_ids[]" value="{{ $group->id }}"
                                   {{ in_array($group->id, old('group_ids', [])) ? 'checked' : '' }}>
                            {{ $group->title }}
                        </label>
                    @endforeach
                </div>
                @error('group_ids.*')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                @error('group_ids')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="btn-primary">نشر الإعلان</button>
        </form>
    </div>
</x-dashboard-layout>