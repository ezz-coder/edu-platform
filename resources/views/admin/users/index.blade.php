<x-dashboard-layout title="إدارة المستخدمين">
    <div class="space-y-6">
        <!-- Success Message -->
        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        <!-- Users Table -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الاسم</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">البريد الإلكتروني</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الدور</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($users as $user)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                        @if ($user->role === 'admin') bg-purple-100 text-purple-800
                                        @elseif ($user->role === 'teacher') bg-blue-100 text-blue-800
                                        @elseif ($user->role === 'student') bg-green-100 text-green-800
                                        @endif">
                                        {{ match($user->role) {
                                            'admin' => 'أدمن',
                                            'teacher' => 'مدرس',
                                            'student' => 'طالب',
                                            default => '',
                                        } }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                        @if ($user->status === 'active') bg-green-100 text-green-800
                                        @elseif ($user->status === 'pending') bg-yellow-100 text-yellow-800
                                        @elseif ($user->status === 'rejected') bg-red-100 text-red-800
                                        @elseif ($user->status === 'suspended') bg-gray-100 text-gray-800
                                        @endif">
                                        {{ match($user->status) {
                                            'active' => 'نشط',
                                            'pending' => 'قيد الانتظار',
                                            'rejected' => 'مرفوض',
                                            'suspended' => 'معلق',
                                            default => '',
                                        } }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2 space-x-reverse">
                                    @if ($user->status !== 'active')
                                        <form method="POST" action="{{ route('admin.users.approve', $user) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-green-600 hover:text-green-900">تفعيل</button>
                                        </form>
                                    @endif

                                    @if ($user->status !== 'rejected')
                                        <form method="POST" action="{{ route('admin.users.reject', $user) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-red-600 hover:text-red-900">رفض</button>
                                        </form>
                                    @endif

                                    @if ($user->status !== 'suspended')
                                        <form method="POST" action="{{ route('admin.users.suspend', $user) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-gray-600 hover:text-gray-900">تعليق</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($users->isEmpty())
                <div class="text-center py-12">
                    <p class="text-gray-500">لا يوجد مستخدمين حالياً</p>
                </div>
            @endif
        </div>
    </div>
</x-dashboard-layout>
