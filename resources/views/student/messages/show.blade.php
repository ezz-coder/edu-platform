<x-dashboard-layout>
    <x-slot name="header">محادثة مع {{ $teacher->name }}</x-slot>
    <x-slot name="title">الرسائل</x-slot>

    <div class="max-w-3xl mx-auto space-y-4">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold text-text-primary">{{ $teacher->name }}</h1>
            <a href="{{ route('student.messages.index') }}" class="text-primary-600 hover:underline text-sm">← كل المحادثات</a>
        </div>

        <div class="bg-bg-card border border-border-light rounded-lg p-4 space-y-3 min-h-[300px]">
            @forelse($messages as $message)
                <div class="flex {{ $message->sender_type === 'student' ? 'justify-start' : 'justify-end' }}">
                    <div class="max-w-[75%] rounded-lg px-3 py-2 text-sm
                        {{ $message->sender_type === 'student'
                            ? 'bg-primary-100 text-text-primary'
                            : 'bg-gray-100 text-text-primary' }}">
                        <p>{{ $message->body }}</p>
                        <p class="text-[11px] text-text-muted mt-1">{{ $message->created_at->format('Y-m-d H:i') }}</p>
                    </div>
                </div>
            @empty
                <p class="text-center text-text-muted py-10">لا توجد رسائل بعد — ابدأ المحادثة.</p>
            @endforelse
        </div>

        <form method="POST" action="{{ route('student.messages.store', $teacher) }}"
              class="bg-bg-card border border-border-light rounded-lg p-4 flex gap-2">
            @csrf
            <textarea name="body" rows="2" required maxlength="2000" placeholder="اكتب رسالتك..."
                      class="flex-1 border border-border-light rounded-lg p-2 text-sm"></textarea>
            <button type="submit" class="btn-primary btn-sm self-end">إرسال</button>
        </form>
    </div>
</x-dashboard-layout>