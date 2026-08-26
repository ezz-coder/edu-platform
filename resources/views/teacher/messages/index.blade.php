<x-dashboard-layout>
    <x-slot name="header">الرسائل</x-slot>
    <x-slot name="title">الرسائل</x-slot>

    <div class="max-w-4xl mx-auto space-y-4">
        @if(session('success'))
            <div class="bg-green-50 text-green-700 border border-green-200 rounded-lg p-3">{{ session('success') }}</div>
        @endif

        <h1 class="text-xl font-bold text-text-primary">الرسائل</h1>

        @if($threads->isEmpty())
            <div class="bg-bg-card border border-border-light rounded-lg p-6 text-center text-text-muted">
                لا يوجد طلاب مسجلون في مجموعاتك بعد.
            </div>
        @else
            <div class="bg-bg-card border border-border-light rounded-lg divide-y divide-border-light">
                @foreach($threads as $thread)
                    <a href="{{ route('teacher.messages.show', $thread->student) }}"
                       class="flex items-center justify-between p-4 hover:bg-gray-50 transition-colors">
                        <div>
                            <p class="font-medium text-text-primary">{{ $thread->student->name }}</p>
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