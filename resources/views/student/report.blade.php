@php
    $group = $enrollment?->group;
    $teacher = $group?->teacher;
    $term = $enrollment?->term;
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>تقرير الطالب - {{ $account->name }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Cairo', sans-serif; color: #111827; padding: 32px; background: #fff; }
        .report { max-width: 760px; margin: 0 auto; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden; }
        .header { background: #2563eb; color: #fff; padding: 24px 28px; display: flex; align-items: center; justify-content: space-between; }
        .header h1 { font-size: 22px; font-weight: 700; }
        .header p { font-size: 13px; opacity: .9; margin-top: 4px; }
        .brand { text-align: left; }
        .brand .logo { font-size: 26px; font-weight: 700; }
        .body { padding: 28px; }
        .section-title { font-size: 15px; font-weight: 700; color: #1f2937; border-bottom: 2px solid #eff6ff; padding-bottom: 6px; margin: 22px 0 12px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 24px; }
        .info-grid div { font-size: 14px; }
        .info-grid span { color: #6b7280; margin-left: 6px; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 8px; }
        .stat { border-radius: 12px; padding: 14px; text-align: center; }
        .stat.green { background: #ecfdf5; } .stat.orange { background: #fff7ed; } .stat.red { background: #fef2f2; } .stat.blue { background: #eff6ff; }
        .stat .n { font-size: 22px; font-weight: 700; }
        .stat .l { font-size: 12px; color: #6b7280; margin-top: 2px; }
        .exam-row, .res-row { display: flex; justify-content: space-between; font-size: 13px; padding: 6px 0; border-bottom: 1px dashed #e5e7eb; }
        .card-row { display: flex; align-items: center; gap: 16px; margin-top: 18px; border-top: 1px solid #f3f4f6; padding-top: 18px; }
        .qr { width: 110px; height: 110px; border: 2px solid #2563eb; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-direction: column; color: #2563eb; font-size: 11px; text-align: center; }
        .qr .code { font-weight: 700; font-size: 14px; margin-top: 6px; }
        .card-info { font-size: 13px; }
        .footer { text-align: center; font-size: 11px; color: #9ca3af; padding: 16px; }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align:center; margin-bottom:16px;">
        <button onclick="window.print()" style="background:#2563eb;color:#fff;border:0;padding:10px 22px;border-radius:10px;font-family:'Cairo';font-size:14px;cursor:pointer;">
            <i class="bi bi-download"></i> تحميل PDF
        </button>
        <a href="{{ route('student.account') }}" style="margin-right:10px;color:#6b7280;font-size:14px;">رجوع</a>
    </div>

    <div class="report">
        <div class="header">
            <div>
                <h1>تقرير مسيرة الطالب</h1>
                <p>منصة بيان — {{ now()->format('Y-m-d') }}</p>
            </div>
            <div class="brand">
                <div class="logo">بيان</div>
            </div>
        </div>

        <div class="body">
            <div class="section-title">بيانات الطالب</div>
            <div class="info-grid">
                <div><span>الاسم:</span> {{ $account->name }}</div>
                <div><span>كود الطالب:</span> {{ $account->student_code }}</div>
                <div><span>المدرس:</span> {{ $teacher?->name ?? '—' }}</div>
                <div><span>المجموعة:</span> {{ $group?->title ?? '—' }}</div>
                <div><span>الترم الحالي:</span> {{ $term?->title ?? ($enrollment?->term?->name ?? '—') }}</div>
                <div><span>الهاتف:</span> {{ $account->mobile ?? '—' }}</div>
                <div><span>هاتف ولي الأمر:</span> {{ $account->parent_mobile ?? '—' }}</div>
            </div>

            <div class="section-title">الحضور</div>
            <div class="stats">
                <div class="stat green"><div class="n">{{ $attendance['present'] ?? 0 }}</div><div class="l">حضور</div></div>
                <div class="stat orange"><div class="n">{{ $attendance['late'] ?? 0 }}</div><div class="l">متأخر</div></div>
                <div class="stat red"><div class="n">{{ $attendance['absent'] ?? 0 }}</div><div class="l">غياب</div></div>
            </div>

            <div class="section-title">الامتحانات</div>
            @if ($examAttempts->isNotEmpty())
                @foreach ($examAttempts as $a)
                    <div class="exam-row"><span>{{ $a->exam->title }}</span><strong>{{ $a->score }}/{{ $a->total_marks }}</strong></div>
                @endforeach
            @else
                <p style="font-size:13px;color:#6b7280;">لا توجد امتحانات مؤداة بعد.</p>
            @endif

            <div class="section-title">الحجوزات</div>
            @if ($reservations->isNotEmpty())
                @foreach ($reservations as $r)
                    <div class="res-row"><span>{{ $r->title }}</span><span>{{ $r->reserved_at?->format('Y-m-d') ?? '—' }}</span></div>
                @endforeach
            @else
                <p style="font-size:13px;color:#6b7280;">لا توجد حجوزات بعد.</p>
            @endif

            <div class="card-row">
                <div class="qr">
                    <i class="bi bi-qr-code" style="font-size:34px;"></i>
                    <div class="code">{{ $account->student_code }}</div>
                </div>
                <div class="card-info">
                    <p style="font-weight:700;font-size:15px;">{{ $account->name }}</p>
                    <p style="color:#6b7280;">{{ $group?->title ?? '—' }} — {{ $teacher?->name ?? '—' }}</p>
                    <p style="color:#9ca3af;font-size:12px;margin-top:4px;">أظهر هذا الرمز عند الدخول.</p>
                </div>
            </div>
        </div>

        <div class="footer">هذا التقرير مُولّد تلقائياً من منصة بيان ويمكن مشاركته مع ولي الأمر.</div>
    </div>
</body>
</html>