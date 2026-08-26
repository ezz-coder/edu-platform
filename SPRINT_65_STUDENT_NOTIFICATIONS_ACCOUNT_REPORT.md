# SPRINT 65 — Student Notifications + Student Account Completion Report

## 1. Scope

- New Lesson notification للطلاب المستهدفين
- Grade edited notification للطالب المتأثر
- Student Settings page
- Change Student Mobile من واجهة الطالب
- Separate Grades page
- Separate Attendance page

خارج النطاق (لم يُلمس): Teacher Reports, Teacher Import/Export, Teacher Calendar,
Bulk Lessons, Assistant Dashboard, Messaging, Announcements, Parent Access, 2FA.

## 2. Source of Truth

- `docs/specifications/student_features.md` (Sections 4, 10, 11, 13, 15, 18)
- `docs/specifications/shared_workflows.md` (Workflows 8, 12, 17)

## 3. Existing Functionality Preserved

- Notification infrastructure (`NotificationHelper`, `Notification::notify`) — أُعيد استخدامها كما هي.
- Assistant → Teacher grade notification — لم تتغير وتستمر بالعمل.
- Multi-account switching (`active_student_id` + mobile family resolver) — لم يتغير.
- Lesson creation logic — لم يتعدل إلا إضافة notification trigger فقط.
- Teacher/Assistant Grades & Attendance logic — لم يُلمس.

## 4. Features Implemented

### 4.1 New Lesson Notification
- عند إنشاء درس (مدرس أو مساعد بصلاحية `can_manage_lessons`): إشعار نوع `lesson`
  بعنوان "درس جديد" لكل الطلاب المسجلين (`enrolled`) في مجموعة الدرس فقط.
- Duplicate prevention عبر `NotificationHelper::notifyStudents` (تخطي الإشعار غير المقروء المكرر).
- الطلاب خارج نطاق الدرس / غير النشطين لا يتلقون شيئًا.

### 4.2 Grade Edited Notification
- عند تعديل الدرجة (مدرس أو مساعد): إشعار نوع `grade_updated` بعنوان "تم تعديل درجتك"
  للطالب المتأثر فقط، مع رابط لصفحة درجات الطالب.
- إشعار المساعد → المدرس الحالي ("تم تعديل درجة بواسطة مساعد") محفوظ كما هو.

### 4.3 Student Settings Page
- Route: `GET /student/settings` (`student.settings`) — View: `student/settings.blade.php`.
- تغيير رقم الهاتف + روابط لإعادة استخدام المنطق الموجود:
  profile.edit (الاسم/البريد)، password.update، تبديل الحسابات.
- لا توجد إعدادات مُختلقة خارج المواصفة.

### 4.4 Change Student Mobile
- Route: `PATCH /student/settings/mobile` (`student.settings.mobile`).
- Validation: required, string, max:20, regex أرقام فقط (+/-).
- Duplicate guard: يرفض رقمًا يملكه مستخدم خارج عائلة حسابات الطالب الحالية
  (يمنع الاستيلاء على حسابات شخص آخر عبر switching).
- الرقم الجديد يُطبق على **جميع** حسابات العائلة (نفس mobile) للحفاظ على multi-account.
- لا يتم تعديل `card_id` / `student_code` / `qr_token`.
- `active_student_id` يعاد ضبطه ليظل متسقًا مع الرقم الجديد.

### 4.5 Separate Grades Page
- Route: `GET /student/grades` (`student.grades`) — View: `student/grades.blade.php`.
- درجات الطالب النشط فقط، finished attempts فقط، الأحدث أولًا، limit 20 (كما التقرير).

### 4.6 Separate Attendance Page
- Route: `GET /student/attendance` (`student.attendance`) — View: `student/attendance.blade.php`.
- ملخص (حضور/متأخر/غياب) + نسبة حضور + سجل تفصيلي (حصة، تاريخ، حالة) مرتب بالأحدث.
- بيانات الطالب النشط ومجموعته النشطة فقط.

### Sidebar
- أُضيف روابط "الدرجات"، "الحضور"، "الإعدادات" لشريط الطالب في `dashboard-layout.blade.php`.

## 5. Bugs Found

1. **500 عند إنشاء درس بدون وصف**: `TeacherLessonController@store` كان يستخدم
   `$validated['description']` بدون `?? null` بينما الحقل nullable.
2. **ملخص حضور خاطئ في "حسابي" والتقرير**: الـ query كان يستخدم عمود `status`
   (الذي يحل إلى `attendance_sessions.status` = open/closed) بدل
   `attendance_records.attendance_type` (regular|late|absent).
3. **عرض حالة خاطئ في صفحة الحضور الجديدة**: القيم الفعلية `regular|late|absent`
   وليس `present`.

## 6. Root Causes

1. عدم اتساق مع نسخة المساعد التي تستخدم `$validated['description'] ?? null`.
2. غياب عمود `status` في `attendance_records` أصلاً (الموجود في sessions فقط).
3. افتراض قيم enum غير مطابقة لقيم قاعدة البيانات.

جميعها أُصلحت بـ Minimal Fix وأعيد اختبارها.

## 7. Files Modified

| File | Change |
|---|---|
| app/Http/Controllers/TeacherLessonController.php | lesson notification + description bugfix |
| app/Http/Controllers/AssistantController.php | lessonsStore + gradesUpdate notifications |
| app/Http/Controllers/TeacherGradeController.php | student grade notification |
| app/Http/Controllers/StudentAccountController.php | grades(), attendance(), settings(), updateMobile() + attendance_type bugfix |
| routes/web.php | student.grades / student.attendance / student.settings / student.settings.mobile |
| resources/views/student/grades.blade.php | new |
| resources/views/student/attendance.blade.php | new |
| resources/views/student/settings.blade.php | new |
| resources/views/components/dashboard-layout.blade.php | student sidebar links |
| config/notifications.php | types `lesson` + `grade_updated` |
| tests/Feature/Sprint65StudentNotificationsAccountTest.php | new test suite |

## 8. Tests

`tests/Feature/Sprint65StudentNotificationsAccountTest.php` — **14 tests, all passing**:

- Lesson notification: targeted students only / out-of-scope none / no duplicates / assistant path ✅
- Grade notification: teacher edit → affected student only; assistant edit → student notified + teacher notification preserved; unrelated none ✅
- Mobile: valid change persists / invalid rejected / duplicate (other family) rejected / sibling accounts updated + switching works ✅
- Grades: own grades only / latest-first ordering / unfinished excluded ✅
- Attendance: own records only with correct totals & percentage / late+absent counts ✅
- Privacy: guests redirected from all new pages ✅

## 9. Regression

- Sprint 63 suite (19 tests): PASS
- Sprint 64 suite (5 tests): PASS
- Total: 24 passed, 90 assertions.

## 10. Final Feature Audit

| Feature | Required | Implemented | Verified | Status |
|---|---|---|---|---|
| New lesson notification | Yes (spec §13/§18) | Yes | Feature tests | COMPLETE |
| Grade edited notification | Yes (spec §13/§18) | Yes | Feature tests | COMPLETE |
| Student settings | Yes (spec §18.4) | Yes (mobile + reuse of existing profile/password) | Feature test | COMPLETE |
| Change mobile | Yes (spec §18.10) | Yes | Feature tests | COMPLETE |
| Separate grades page | Yes (spec §18.1) | Yes | Feature tests | COMPLETE |
| Separate attendance page | Yes (spec §18.2) | Yes | Feature tests | COMPLETE |

## 11. Remaining Issues

- Runtime browser verification لم تُنفذ (السلوك مثبت بـ Feature tests حسب أولوية
  Verification المعتمدة). يمكن إضافة Playwright QA لاحقًا إن رغب المالك.
- لا توجد مشاكل معروفة متبقية داخل نطاق Sprint 65.

---
**Sprint 65 CLOSED — HARD STOP respected. No out-of-scope modules touched.**