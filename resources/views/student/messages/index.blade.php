<x-dashboard-layout>
    <x-slot name="header">الرسائل</x-slot>
    <x-slot name="title">الرسائل</x-slot>

    <div class="max-w-4xl mx-auto space-y-4">
        <h1 class="text-xl font-bold text-text-primary">الرسائل</h1>

        @if($threads->isEmpty())
            <div class="bg-bg-card border border-border-light rounded-lg p-6 text-center text-text-muted">
                لا يوجد مدرسون للتواصل معهم بعد.
            </div>
        @else
            <div class="bg-bg-card border border-border-light rounded-lg divide-y divide-border-light">
                @foreach($threads as $thread)
                    <a href="{{ route('student.messages.show', $thread->teacher) }}"
                       class="flex items-center justify-between p-4 hover:bg-gray-50 transition-colors">
                        <div>
                            <p class="font-medium text-text-primary">{{ $thread->teacher->name }}</p>
                            <p class="text-sm text-text-muted truncate max-w-md">
                                {{ $thread->last ? \Illuminate\Support\Str::limit($thread->last->body, 60) : 'لا توجد رسائل بعد' }}
                            </p>
                        </div>
                        @if($thread->unread > 0)
                            <span class="bg-primary-600 text-white text-xs rounded-full px-2 py-1">{{ $thread->unread }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-dashboard-layout>