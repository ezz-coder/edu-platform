# المواصفات الرسمية لموديول الأدمن — منصة بيان

> تم استخراج هذه المواصفات من الكود المصدري + القرارات الرسمية من مالك المشروع.
>
> **تاريخ الاستخراج:** 2026-07-27
>
> **تاريخ اعتماد القرارات الرسمية:** 2026-07-27
>
> **المشروع:** بيان - منصة إدارة المدرسين والطلاب

---

## 1. Admin Overview

### 1.1 الوظيفة

وفقاً لـ `PROJECT_RULES.md` والقرارات الرسمية:

- يقوم الأدمن بمراجعة طلبات تسجيل المدرسين.
- الموافقة أو الرفض.
- إدارة المنصة بالكامل.

### 1.2 المسؤوليات (المستخرجة من الكود + القرارات الرسمية)

- مراجعة طلبات تسجيل المدرسين الجدد والموافقة عليها أو رفضها.
- إدارة المدرسين: إنشاء، تعديل، تفعيل، إلغاء تفعيل، تعليق، استعادة، حذف (soft delete).
- إدارة المستخدمين: عرض جميع المستخدمين (أدمن، مدرس، طالب)، تفعيل، رفض، تعليق.
- إدارة الطلاب: عرض، بحث، فلترة، تفعيل، تعطيل، تعليق، Soft Delete، Restore، رؤية المجموعة الحالية، نقل الطالب لأي مدرس، رؤية السجل الأكاديمي (قراءة فقط).
- إدارة المساعدين: عرض، بحث، فلترة، معرفة المدرس التابع له، تعليق، تفعيل، Soft Delete، Restore.
- إدارة المجموعات الدراسية: عرض جميع المجموعات، فلترة حسب المدرس، تفعيل/تعطيل مجموعة.
- الاطلاع على سجل نشاط المنصة مع فلترة وبحث وحذف وتصدير.
- إدارة إعدادات المنصة (البريد، WhatsApp، التسجيل، النسخ الاحتياطية، النظام، الهوية البصرية).
- إنشاء وتحميل وحذف واسترجاع وجدولة النسخ الاحتياطية (Backup).
- مراقبة صحة النظام (System Health) مع أدوات الصيانة (Maintenance Mode, Clear Cache, Clear Config, Clear View Cache, Queue Status).
- عرض الأخطاء الأخيرة من Laravel log مع تنزيل وحذف وأرشفة.
- لوحة تحكم تحتوي على إحصائيات وروابط سريعة.
- إدارة الفصول الدراسية (إنشاء، تعديل، Soft Delete، Restore).
- تغيير كلمة مرور الأدمن.
- إصدار التقارير (PDF, Excel).
- الدخول كمدرس (Impersonation).
- استقبال إشعارات النظام المهمة.

### 1.3 حدود الصلاحيات (المستخرجة من الكود + القرارات الرسمية)

- الأدمن **يستطيع** إنشاء مدرس جديد مباشرة (بدون طلب تسجيل).
- الأدمن **يستطيع** تعديل بيانات المدرس (الاسم، الهاتف، البريد، كلمة المرور، الحالة، المرحلة، البيانات العامة).
- الأدمن **يستطيع** إدارة الطلاب بالكامل (عرض، بحث، فلترة، تفعيل، تعطيل، تعليق، حذف، استعادة، نقل، رؤية السجل الأكاديمي).
- الأدمن **يستطيع** إدارة المساعدين (عرض، بحث، فلترة، تعليق، تفعيل، حذف، استعادة) لكنه **لا يستطيع** تعديل صلاحيات المساعد — صلاحياته يحددها المدرس فقط.
- الأدمن **يستطيع** إنشاء وتعديل وحذف الفصول الدراسية.
- الأدمن **يستطيع** الدخول كمدرس (Impersonation).
- الأدمن **يستطيع** تغيير كلمة المرور الخاصة به.
- الأدمن **لا يستطيع** تعديل بيانات أكاديمية للطلاب مثل الدرجات أو الحضور مباشرة.
- الأدمن **لا يستطيع** الوصول إلى أي موديول خاص بالمدرس (exams, attendance, resources, lessons, reservations, question bank, students, assistants, terms) — إلا عبر Impersonation.
- الأدمن **لا يستطيع** الوصول إلى أي موديول خاص بالطالب.

---

## 2. Authentication

### 2.1 Login

- يستخدم الأدمن نفس صفحة `/login` الافتراضية في Laravel Breeze.
- المسار: `POST /login` → `AuthenticatedSessionController::store`.
- يستخدم Guard: `web` (نفس guard للمدرسين والطلاب).

### 2.2 Logout

- المسار: `POST /logout` → `AuthenticatedSessionController::destroy`.
- يتم تسجيل الخروج من الـ `auth` guard.

### 2.3 Session

- Admin يعتمد على Session Laravel الافتراضية.
- لا يوجد Custom Session Handling خاص بالأدمن.

### 2.4 Password

- يستخدم Password Reset الافتراضي من Laravel Breeze.
- مسارات:
  - `GET /forgot-password`
  - `POST /forgot-password`
  - `GET /reset-password/{token}`
  - `POST /reset-password`

### 2.5 Guards

- فقط Guard واحد: `web` (في `config/auth.php`).
- لا يوجد Guard خاص بالأدمن.
- Guard `assistant` موجود لكنه خاص بالمساعدين وليس للأدمن.

### 2.6 Middleware

- المسار المستخدم: `Route::middleware(['auth', 'role:admin'])`.
- `auth`: يمنع الوصول لغير المسجلين.
- `role:admin` (في `app/Http/Middleware/RoleMiddleware.php`): يتحقق أن `auth()->user()->role === 'admin'`.
- لو الدور غير `admin` → `abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة')`.

**ملاحظة:** لا يوجد Middleware مخصص للأدمن غير RoleMiddleware.

---

## 3. Dashboard

### 3.1 المسار

- `GET /admin/dashboard` → `AdminController::dashboard` → `admin.dashboard`

### 3.2 البطاقات والإحصائيات الموجودة

| البطاقة                           | المفتاح في $stats         | مصدر البيانات                                  |
|-----------------------------------|---------------------------|-------------------------------------------------|
| إجمالي المدرسين                   | `teachers`                | `User::where('role', 'teacher')->count()`       |
| مدرسون نشطون                      | `active_teachers`         | `where('role', 'teacher')->where('status', 'active')` |
| مدرسون منتظرون                    | `pending_teachers`        | `where('role', 'teacher')->where('status', 'pending')` |
| مدرسون معلقون                     | `suspended_teachers`      | `where('role', 'teacher')->where('status', 'suspended')` |
| مدرسون مرفوضون                    | `rejected_teachers`       | `where('role', 'teacher')->where('status', 'rejected')` |
| إجمالي الطلاب                     | `students`                | `User::where('role', 'student')->count()`       |
| طلاب غير نشطين                    | `inactive_students`       | `where('role', 'student')->where('status', 'inactive')` |
| المساعدون                         | `assistants`              | `Assistant::count()`                            |
| المجموعات النشطة                  | `active_groups`           | `StudyGroup::where('status', 'active')->count()`|
| إجمالي المجموعات                  | `groups`                  | `StudyGroup::count()`                           |
| الموارد التعليمية                 | `resources`               | `Resource::count()`                             |
| الحجوزات                          | `reservations`            | `Reservation::count()`                          |
| الامتحانات المنشورة               | `published_exams`         | `Exam::where('status', 'published')->count()`   |
| إجمالي الامتحانات                 | `exams`                   | `Exam::count()`                                 |
| جلسات الحضور                      | `attendance_sessions`     | `AttendanceSession::count()`                    |
| القيود النشطة (Enrollments)       | `enrollments`             | `GroupEnrollment::where('status', 'enrolled')->count()` |

### 3.3 Widget / Quick Actions

- كارد "آخر فصل دراسي": يعرض `AcademicTerm::latest()->first()` مع title, year, status.
- **إجراءات سريعة:**
  - رابط لـ `admin.teacher-requests` (طلبات المدرسين)
  - رابط لـ `admin.teachers` (إدارة المدرسين)
  - رابط لـ `admin.activity-log` (سجل النشاط)
  - رابط لـ `admin.settings` (الإعدادات)

### 3.4 Counters

- لا يوجد Counters إضافية خارج الجدول أعلاه.

### 3.5 ملاحظات

- لا يوجد Charts.
- لا يوجد Graphs.
- لا يوجد Real-time data.
- لا يوجد Widgets قابلة للتخصيص.
- لا يوجد أي JavaScript معقد في لوحة التحكم.

---

## 4. Teacher Management

### 4.1 هل يوجد Teacher Management؟

نعم، موجود.

### 4.2 إنشاء مدرس

**معتمد رسمياً.** الأدمن يستطيع إنشاء حساب مدرس مباشرة بدون طلب تسجيل.

البيانات المطلوبة:
- الاسم
- البريد الإلكتروني
- رقم الموبايل
- كلمة المرور
- الحالة (افتراضي active)
- أي بيانات أخرى ضرورية

### 4.3 تعديل مدرس

**معتمد رسمياً.** الأدمن يستطيع تعديل:
- الاسم
- الهاتف
- البريد
- كلمة المرور (Reset Password)
- الحالة
- المرحلة
- البيانات العامة

### 4.4 حذف (Soft Delete)

موجود حالياً.
- زر "حذف" يظهر لكل المدرسين الذين status !== 'deleted'.
- المسار: `POST /admin/teachers/{user}/soft-delete` → `AdminController::softDeleteTeacher`.
- الإجراء: `$user->update(['status' => 'deleted'])`.
- يسجل Activity Log.

### 4.5 أرشفة

**غير موجودة.** لا توجد وظيفة أرشفة منفصلة. الحذف soft delete يعتبر أقرب بديل.

### 4.6 استعادة

موجود.
- زر "استعادة" يظهر عندما يكون status === 'suspended' أو 'inactive' أو 'deleted'.
- المسار: `POST /admin/teachers/{user}/restore` → `AdminController::restoreTeacher`.
- الإجراء: `$user->update(['status' => 'active'])`.
- يسجل Activity Log.

### 4.7 بحث

**معتمد رسمياً.** يجب أن يتوفر بحث في صفحة إدارة المدرسين.

### 4.8 فلترة

**معتمدة رسمياً.** يجب أن تتوفر فلاتر في صفحة إدارة المدرسين.

### 4.9 الحالات (Statuses) المستخدمة

| الحالة         | الوصف               |
|----------------|---------------------|
| `active`       | نشط                 |
| `pending`      | قيد الانتظار        |
| `rejected`     | مرفوض               |
| `suspended`    | معلق                |
| `inactive`     | غير نشط             |
| `deleted`      | محذوف               |

### 4.10 العمليات المتاحة (حالياً + المعتمدة)

| العملية              | الحالة         | ملاحظات                              |
|----------------------|----------------|---------------------------------------|
| إنشاء (Create)       | معتمد رسمياً   | إضافة مدرس مباشرة بدون طلب تسجيل      |
| تعديل (Edit)         | معتمد رسمياً   | الاسم، الهاتف، البريد، كلمة المرور، الحالة، المرحلة |
| تفعيل (Activate)     | موجود          |                                       |
| إلغاء تفعيل (Deactivate) | موجود      |                                       |
| تعليق (Suspend)      | موجود          |                                       |
| استعادة (Restore)    | موجود          |                                       |
| حذف (Soft Delete)    | موجود          |                                       |
| بحث (Search)         | معتمد رسمياً   |                                       |
| فلترة (Filter)       | معتمدة رسمياً  |                                       |

جميع العمليات تسجل في Activity Log.

---

## 5. Student Management

### 5.1 الحالة

**معتمد رسمياً.** الأدمن يمتلك إدارة كاملة للطلاب.

### 5.2 الإمكانيات المعتمدة

| الإمكانية               | الحالة         | ملاحظات                                |
|-------------------------|----------------|----------------------------------------|
| عرض جميع الطلاب         | معتمد رسمياً   |                                        |
| بحث                     | معتمد رسمياً   |                                        |
| فلترة                   | معتمدة رسمياً  |                                        |
| تفعيل الطالب            | معتمد رسمياً   |                                        |
| تعطيل الطالب            | معتمد رسمياً   |                                        |
| تعليق الطالب            | معتمد رسمياً   |                                        |
| Soft Delete             | معتمد رسمياً   |                                        |
| Restore                 | معتمد رسمياً   |                                        |
| رؤية المجموعة الحالية   | معتمد رسمياً   |                                        |
| نقل الطالب لأي مدرس     | معتمد رسمياً   | عند الحاجة                             |
| رؤية السجل الأكاديمي    | معتمد رسمياً   | قراءة فقط                              |

### 5.3 حدود صلاحيات الطلاب

- الأدمن **لا يعدل** بيانات أكاديمية مثل الدرجات أو الحضور مباشرة.
- الأدمن **يستطيع** نقل الطالب بين المدرسين عند الحاجة.

---

## 6. Assistant Management (إدارة المساعدين)

### 6.1 الحالة

**معتمد رسمياً.** الأدمن يمتلك إدارة عامة للمساعدين.

### 6.2 الإمكانيات المعتمدة

| الإمكانية                    | الحالة         | ملاحظات                                |
|------------------------------|----------------|----------------------------------------|
| عرض جميع المساعدين          | معتمد رسمياً   |                                        |
| بحث                          | معتمد رسمياً   |                                        |
| فلترة                        | معتمدة رسمياً  |                                        |
| معرفة المدرس التابع له       | معتمد رسمياً   |                                        |
| تعليق الحساب                 | معتمد رسمياً   |                                        |
| تفعيل الحساب                 | معتمد رسمياً   |                                        |
| Soft Delete                  | معتمد رسمياً   |                                        |
| Restore                      | معتمد رسمياً   |                                        |

### 6.3 حدود صلاحيات المساعدين

- الأدمن **لا يستطيع** تعديل صلاحيات المساعد — صلاحياته يحددها المدرس فقط.

---

## 7. Requests (طلبات تسجيل المدرسين)

### 7.1 Teacher Requests

موجود.
- المسار: `GET /admin/teacher-requests` → `AdminController::teacherRequests` → `admin.teachers.requests`
- يستعلم عن: `User::where('role', 'teacher')->where('status', 'pending')->latest()->paginate(20)`

### 7.2 Approval

موجود.
- المسار: `POST /admin/teachers/{user}/approve` → `AdminController::approveTeacher`
- الإجراء: `$user->update(['status' => 'active'])`
- Validation: `$user->role !== 'teacher' || $user->status !== 'pending'` → return error.
- يسجل Activity Log.

### 7.3 Reject

موجود.
- المسار: `POST /admin/teachers/{user}/reject` → `AdminController::rejectTeacher`
- الإجراء: `$user->update(['status' => 'rejected'])`
- Validation: `$user->role !== 'teacher' || $user->status !== 'pending'` → return error.
- يقبل Reason اختياري: `rejection_reason` مع `max:500`.
- يسجل Activity Log.

### 7.4 Reason للموافقة

غير موجود. Reason فقط للرفض.

### 7.5 History

غير موجود. لا توجد صفحة تاريخ للطلبات السابقة. فقط يعرض `status === 'pending'`.

### 7.6 ملخص Teacher Requests

| الميزة          | موجود؟ | ملاحظات                     |
|-----------------|--------|-----------------------------|
| طلب تسجيل       | نعم    |                             |
| موافقة          | نعم    |                             |
| رفض مع سبب      | نعم    | سبب الرفض اختياري           |
| تاريخ الطلبات   | لا     | فقط يعرض pending requests   |
| إشعار للمدرس   | معتمد رسمياً | إشعارات النظام             |

---

## 8. Activity Log

### 8.1 المسار

`GET /admin/activity-log` → `AdminController::activityLog` → `admin.activities.index`

### 8.2 الإمكانيات (الحالية)

- عرض جميع الأنشطة مع Pagination (30 لكل صفحة).
- **فلاتر:**
  - `category`: Authentication, Teacher, Assistant, Student, Groups, Attendance, Resources, Reservations, Exams, Academic Terms, Administration.
  - `action`: نص الإجراء (free text filter من خلال `search` أيضاً).
  - `actor_type`: أدمن, مدرس, طالب, مساعد.
  - `date_from`: تاريخ البداية.
  - `date_to`: تاريخ النهاية.
  - `search`: بحث نصي في `description` و `action`.
- عرض الأعمدة: التاريخ، الوقت، المستخدم، نوع المستخدم، الإجراء، التصنيف، الوصف، IP.
- رابط "إعادة تعيين" لإزالة الفلاتر.

### 8.3 الإمكانيات المعتمدة (جديد)

| الإمكانية          | الحالة         | ملاحظات                    |
|--------------------|----------------|----------------------------|
| حذف السجل          | معتمد رسمياً   |                            |
| تصدير السجل        | معتمد رسمياً   |                            |
| فلترة متقدمة       | معتمدة رسمياً  |                            |

### 8.4 مصدر البيانات

`ActivityLog` model مع `latest()`.

### 8.5 دوال التسجيل المستخدمة من قبل Admin

| الإجراء (action)         | التصنيف (category)  | الوصف                                           |
|--------------------------|---------------------|--------------------------------------------------|
| `teacher_approved`       | Teacher             | تم الموافقة على المدرس: {name}                   |
| `teacher_rejected`       | Teacher             | تم رفض المدرس: {name} - السبب: {reason}        |
| `teacher_activated`      | Teacher             | تم تفعيل المدرس: {name}                          |
| `teacher_deactivated`    | Teacher             | تم إلغاء تفعيل المدرس: {name}                    |
| `teacher_suspended`      | Teacher             | تم تعليق المدرس: {name}                          |
| `teacher_restored`       | Teacher             | تم استعادة المدرس: {name}                        |
| `teacher_deleted`        | Teacher             | تم حذف المدرس: {name}                            |
| `settings_updated`       | Administration      | تم تحديث إعدادات المنصة                          |
| `backup_created`         | Administration      | تم إنشاء نسخة احتياطية: {filename}               |
| `backup_deleted`         | Administration      | تم حذف النسخة الاحتياطية: {filename}             |

### 8.6 ملاحظات

- Model `ActivityLog` يحتوي على `actor_id`, `actor_type`, `actor_type_label`, `action`, `category`, `description`, `ip_address`, `owner_id`, `owner_type`.
- دالة `::log()` تستخدم `request()->ip()` للحصول على IP.
- يدعم `scopeCategory` و `scopeAction`.

---

## 9. Errors Viewer

### 9.1 المسار

`GET /admin/errors` → `AdminController::errors` → `admin.errors.index`

### 9.2 الإمكانيات (الحالية)

- يقرأ آخر 500 سطر من `storage/logs/laravel.log` (معكوسة).
- يستخرج الأخطاء باستخدام Regex.
- يعرض حتى 100 خطأ فقط.
- الأعمدة: التاريخ، الرسالة، الملف، السطر، المستوى (خطأ/حرج/تحذير/ملاحظة/معلومات).

### 9.3 الإمكانيات المعتمدة (جديد)

| الإمكانية          | الحالة         | ملاحظات                    |
|--------------------|----------------|----------------------------|
| تنزيل laravel.log  | معتمد رسمياً   |                            |
| حذف السجل          | معتمد رسمياً   | Clear log file             |
| أرشفة السجل        | معتمد رسمياً   | حفظ نسخة قبل الحذف         |

### 9.4 مستويات الأخطاء

| المستوى الأصلي | الترجمة     |
|----------------|-------------|
| ERROR          | خطأ         |
| CRITICAL       | حرج         |
| WARNING        | تحذير       |
| NOTICE         | ملاحظة      |
| INFO           | معلومات     |

### 9.5 ملاحظات

- لا يوجد فلترة في Errors Viewer.
- لا يوجد Pagination (يعرض مرة واحدة).
- لا يوجد Refresh تلقائي.
- يعتمد على وجود ملف `laravel.log`.

---

## 10. System Health

### 10.1 المسار

`GET /admin/health` → `AdminController::health` → `admin.health.index`

### 10.2 الإمكانيات (الحالية)

- إصدار التطبيق (من `config('app.version', '1.0.0')`).
- إصدار Laravel.
- إصدار PHP (`phpversion()`).
- حالة قاعدة البيانات (متصل/غير متصل) باستخدام `DB::connection()->getPdo()`.
- حالة التخزين (قابل للكتابة/غير قابل للكتابة) باستخدام `is_writable(storage_path())`.
- حالة الكاش (يعمل/غير متاح) — يخزن قيمة اختبار ويسترجعها.
- آخر نسخة احتياطية (`Backup::latest()->first()?->created_at`).
- البيئة (environment).

### 10.3 أدوات النظام المعتمدة (جديد)

| الأداة               | الحالة         | ملاحظات                    |
|----------------------|----------------|----------------------------|
| Maintenance Mode     | معتمد رسمياً   | تشغيل/إيقاف وضع الصيانة    |
| Clear Cache          | معتمد رسمياً   | `php artisan cache:clear`  |
| Clear Config         | معتمد رسمياً   | `php artisan config:clear` |
| Clear View Cache     | معتمد رسمياً   | `php artisan view:clear`   |
| Queue Status         | معتمد رسمياً   | عرض حالة الـ Queue         |

### 10.4 ملاحظات

- لا يوجد Health Checks مخصصة (مثل uptime, response time).
- لا يوجد صفحة إعدادات Health.

---

## 11. Backup

### 11.1 المسار

`GET /admin/backup` → `AdminController::backupIndex` → `admin.backup.index`

### 11.2 الإمكانيات (الحالية)

- **عرض النسخ الاحتياطية:** جدول مع اسم الملف، الحجم، النوع، تاريخ الإنشاء مع Pagination.
- **إنشاء نسخة احتياطية:**
  - المسار: `POST /admin/backup/create` → `AdminController::createBackup`.
  - يستخدم `mysqldump` عبر `exec()`.
  - يتحقق من توفر `mysqldump` باستخدام `where` (Windows) أو `which` (Linux/Mac).
  - يحفظ الملف في `storage/app/backups/`.
  - ينشئ سجل في `Backup` model.
  - يسجل Activity Log.
- **تحميل نسخة احتياطية:**
  - المسار: `GET /admin/backup/{backup}/download` → `AdminController::downloadBackup`.
  - يستخدم `response()->download()`.
- **حذف نسخة احتياطية:**
  - المسار: `POST /admin/backup/{backup}/delete` → `AdminController::deleteBackup`.
  - يحذف الملف والسجل من قاعدة البيانات.
  - يسجل Activity Log.

### 11.3 الإمكانيات المعتمدة (جديد)

| الإمكانية                  | الحالة         | ملاحظات                                |
|----------------------------|----------------|----------------------------------------|
| استرجاع (Restore)          | معتمد رسمياً   | مع شاشة تأكيد قوية قبل التنفيذ         |
| جدولة (Scheduled)          | معتمد رسمياً   | يومي / أسبوعي / شهري                   |
| سجل العمليات               | معتمد رسمياً   |                                        |

### 11.4 ملاحظات

- نوع النسخ الاحتياطية حالياً: `manual` فقط.
- Model `Backup` يحتوي على: `filename`, `path`, `size`, `type`.

---

## 12. Notifications (للأدمن)

### 12.1 الحالة

**معتمد رسمياً.** الأدمن يستقبل إشعارات النظام المهمة فقط.

### 12.2 أنواع الإشعارات المعتمدة

| الإشعار                        | النوع            | ملاحظات                     |
|--------------------------------|------------------|------------------------------|
| تسجيل مدرس جديد                | System           |                              |
| تسجيل طالب جديد                | System           |                              |
| Backup Failed                  | System           |                              |
| Critical Error                 | System           |                              |
| System Health Failure          | System           |                              |
| حذف مجموعة                     | System           |                              |
| حذف امتحان                     | System           |                              |
| امتلاء مساحة التخزين           | System           |                              |
| انتهاء SSL                     | System           |                              |
| أي خطأ حرج في النظام           | System           |                              |

---

## 13. Search (بحث)

### 13.1 هل يوجد بحث في موديول الأدمن؟

موجود حالياً في **مكان واحد فقط**: Activity Log.

### 13.2 أين؟

- صفحة `admin.activity-log` (سجل النشاط) — موجود.
- صفحة إدارة المدرسين — **معتمد رسمياً**.
- صفحة إدارة الطلاب — **معتمد رسمياً**.
- صفحة إدارة المساعدين — **معتمد رسمياً**.

### 13.3 كيف يعمل (حالياً)؟

- حقل بحث نصي `search` في الفورم.
- يتم البحث في `description` و `action` باستخدام `LIKE "%{search}%"`.
- لا يوجد Full-text Search.

---

## 14. Filters (فلاتر)

### 14.1 فلاتر Activity Log

| الفلتر        | النوع       | ملاحظات                |
|---------------|-------------|------------------------|
| category      | Select      | 11 تصنيف مختلف         |
| actor_type    | Select      | أدمن / مدرس / طالب / مساعد |
| date_from     | Date input  |                        |
| date_to       | Date input  |                        |
| search        | Text input  | بحث في description و action |

### 14.2 فلاتر Groups

| الفلتر        | النوع       | ملاحظات                        |
|---------------|-------------|---------------------------------|
| teacher_id    | Select      | جميع المدرسين النشطين          |

### 14.3 فلاتر معتمدة (جديد)

| الصفحة                  | الفلاتر المعتمدة     | ملاحظات           |
|-------------------------|----------------------|-------------------|
| Teacher Management      | معتمدة رسمياً        |                   |
| Student Management      | معتمدة رسمياً        |                   |
| Assistant Management    | معتمدة رسمياً        |                   |
| Activity Log            | فلترة متقدمة         | معتمدة رسمياً     |

---

## 15. Academic Terms Management (إدارة الفصول الدراسية)

### 15.1 الحالة

**معتمد رسمياً.**

### 15.2 الإمكانيات المعتمدة

| الإمكانية          | الحالة         | ملاحظات                    |
|--------------------|----------------|----------------------------|
| إنشاء فصل          | معتمد رسمياً   |                            |
| تعديل فصل          | معتمد رسمياً   |                            |
| Soft Delete        | معتمد رسمياً   |                            |
| Restore            | معتمد رسمياً   |                            |

---

## 16. Admin Reports (تقارير الأدمن)

### 16.1 الحالة

**معتمد رسمياً.**

### 16.2 أنواع التقارير المعتمدة

| التقرير                        | الحالة         | ملاحظات                     |
|--------------------------------|----------------|------------------------------|
| تقرير المدرسين                 | معتمد رسمياً   |                              |
| تقرير الطلاب                   | معتمد رسمياً   |                              |
| تقرير المجموعات                | معتمد رسمياً   |                              |
| تقرير الامتحانات               | معتمد رسمياً   |                              |
| تقرير الحضور                   | معتمد رسمياً   |                              |
| تقرير الموارد                  | معتمد رسمياً   |                              |
| تقرير الحجوزات                 | معتمد رسمياً   |                              |
| تقرير النشاط                   | معتمد رسمياً   |                              |
| تقرير النسخ الاحتياطية         | معتمد رسمياً   |                              |
| تقرير أداء النظام              | معتمد رسمياً   |                              |

### 16.3 صيغ التصدير المعتمدة

| الصيغة   | الحالة         |
|----------|----------------|
| PDF      | معتمد رسمياً   |
| Excel    | معتمد رسمياً   |

---

## 17. Login As Teacher (Impersonation)

### 17.1 الحالة

**معتمد رسمياً.**

### 17.2 المواصفات المعتمدة

- يدخل الأدمن بحساب المدرس بدون كلمة مرور.
- يظهر Banner واضح أن الأدمن يعمل كمدرس.
- يوجد زر "العودة لحساب الأدمن".
- تسجل العملية بالكامل في Activity Log.

---

## 18. Change Password (تغيير كلمة مرور الأدمن)

### 18.1 الحالة

**معتمد رسمياً.** Feature مستقلة داخل لوحة الأدمن.

### 18.2 المواصفات المعتمدة

- إدخال كلمة المرور الحالية.
- إدخال كلمة مرور جديدة.
- تأكيد كلمة المرور الجديدة.
- التحقق الكامل من الصحة قبل الحفظ.

---

## 19. System Settings (إعدادات النظام)

### 19.1 المسار

`GET /admin/settings` → `AdminController::settings` → `admin.settings.index`

### 19.2 الإعدادات الحالية

- اسم المنصة
- شعار المنصة (رابط)
- اللون الأساسي
- اللون الثانوي
- رقم الدعم
- بريد الدعم
- وصف المنصة
- رسالة الترحيب
- نص التذييل

### 19.3 الإعدادات المعتمدة (جديد)

| المجموعة              | الإعدادات المعتمدة     | ملاحظات           |
|-----------------------|------------------------|-------------------|
| البريد                | إعدادات SMTP          | معتمد رسمياً      |
| WhatsApp              | إعدادات WhatsApp      | معتمد رسمياً      |
| التسجيل               | إعدادات التسجيل       | معتمد رسمياً      |
| النسخ الاحتياطية      | إعدادات الجدولة       | معتمد رسمياً      |
| النظام                | إعدادات عامة          | معتمد رسمياً      |
| الهوية البصرية        | ألوان، شعار، خطوط     | معتمد رسمياً      |

---

## 20. Runtime Features (جدول)

### 20.1 الميزات الموجودة (Working)

| # | Feature                    | Status    | Route                                  | Controller              | View                       | ملاحظات                                     |
|---|----------------------------|-----------|----------------------------------------|-------------------------|----------------------------|---------------------------------------------|
| 1 | Dashboard                  | Working   | `GET /admin/dashboard`                 | AdminController         | admin.dashboard            | 16 إحصائية + روابط سريعة + آخر فصل دراسي   |
| 2 | Teacher Requests           | Working   | `GET /admin/teacher-requests`          | AdminController         | admin.teachers.requests    | فقط pending, مع موافقة ورفض               |
| 3 | Approve Teacher            | Working   | `POST /admin/teachers/{user}/approve`  | AdminController         | —                          |                                             |
| 4 | Reject Teacher             | Working   | `POST /admin/teachers/{user}/reject`   | AdminController         | —                          | مع سبب اختياري                              |
| 5 | Teacher Management (List)  | Working   | `GET /admin/teachers`                  | AdminController         | admin.teachers.index       | عرض + تفعيل/تعليق/حذف/استعادة              |
| 6 | Activate Teacher           | Working   | `POST /admin/teachers/{user}/activate` | AdminController         | —                          |                                             |
| 7 | Deactivate Teacher         | Working   | `POST /admin/teachers/{user}/deactivate`| AdminController        | —                          |                                             |
| 8 | Suspend Teacher            | Working   | `POST /admin/teachers/{user}/suspend`  | AdminController         | —                          |                                             |
| 9 | Restore Teacher            | Working   | `POST /admin/teachers/{user}/restore`  | AdminController         | —                          | يعيد الحالة إلى active                      |
|10 | Soft Delete Teacher        | Working   | `POST /admin/teachers/{user}/soft-delete`| AdminController       | —                          |                                             |
|11 | Activity Log               | Working   | `GET /admin/activity-log`              | AdminController         | admin.activities.index     | مع فلاتر وبحث وتاريخ                        |
|12 | Settings (View)            | Working   | `GET /admin/settings`                  | AdminController         | admin.settings.index       |                                             |
|13 | Settings (Update)          | Working   | `POST /admin/settings`                 | AdminController         | —                          |                                             |
|14 | Backup Index               | Working   | `GET /admin/backup`                    | AdminController         | admin.backup.index         |                                             |
|15 | Backup Create              | Working   | `POST /admin/backup/create`            | AdminController         | —                          | يستخدم mysqldump                            |
|16 | Backup Download            | Working   | `GET /admin/backup/{backup}/download`  | AdminController         | —                          |                                             |
|17 | Backup Delete              | Working   | `POST /admin/backup/{backup}/delete`   | AdminController         | —                          |                                             |
|18 | System Health              | Working   | `GET /admin/health`                    | AdminController         | admin.health.index         | 8 مؤشرات                                    |
|19 | Errors Viewer              | Working   | `GET /admin/errors`                    | AdminController         | admin.errors.index         | من laravel.log، آخر 100 خطأ                 |
|20 | Users Management (List)    | Working   | `GET /admin/users`                     | AdminUserController     | admin.users.index          | عرض جميع المستخدمين                         |
|21 | User Approve               | Working   | `POST /admin/users/{user}/approve`     | AdminUserController     | —                          |                                             |
|22 | User Reject                | Working   | `POST /admin/users/{user}/reject`      | AdminUserController     | —                          |                                             |
|23 | User Suspend               | Working   | `POST /admin/users/{user}/suspend`     | AdminUserController     | —                          |                                             |
|24 | Groups Management (List)   | Working   | `GET /admin/groups`                    | AdminGroupController    | admin.groups.index         | مع فلتر حسب المدرس                          |
|25 | Group Toggle Status        | Working   | `POST /admin/groups/{group}/toggle`    | AdminGroupController    | —                          | active ↔ inactive                           |

### 20.2 الميزات المعتمدة رسمياً (Approved — لم تنفذ بعد)

| # | Feature                    | Status    | ملاحظات                                   |
|---|----------------------------|-----------|--------------------------------------------|
|26 | Student Management         | Approved  | عرض، بحث، فلترة، تفعيل، تعطيل، تعليق، حذف، استعادة، نقل، سجل أكاديمي |
|27 | Assistant Management       | Approved  | عرض، بحث، فلترة، تعليق، تفعيل، حذف، استعادة |
|28 | Teacher Create             | Approved  | إنشاء مدرس مباشرة بدون طلب تسجيل           |
|29 | Teacher Edit               | Approved  | الاسم، الهاتف، البريد، كلمة المرور، الحالة، المرحلة |
|30 | Teacher Search             | Approved  | بحث في المدرسين                            |
|31 | Teacher Filters            | Approved  | فلاتر في المدرسين                          |
|32 | Backup Restore             | Approved  | استرجاع مع شاشة تأكيد                      |
|33 | Backup Scheduled           | Approved  | يومي / أسبوعي / شهري                       |
|34 | Backup Log                 | Approved  | سجل عمليات النسخ الاحتياطية                |
|35 | Notifications (Admin)      | Approved  | إشعارات النظام المهمة                      |
|36 | Admin Reports              | Approved  | 10 تقارير + PDF + Excel                    |
|37 | Login As Teacher           | Approved  | Impersonation مع Banner وعودة              |
|38 | Change Password            | Approved  | Feature مستقلة                             |
|39 | Academic Terms Management  | Approved  | إنشاء، تعديل، Soft Delete، Restore         |
|40 | Activity Log Delete        | Approved  | حذف السجل                                  |
|41 | Activity Log Export        | Approved  | تصدير السجل                                |
|42 | Activity Log Advanced Filter | Approved | فلترة متقدمة                              |
|43 | Errors Download            | Approved  | تنزيل laravel.log                          |
|44 | Errors Delete              | Approved  | حذف السجل                                  |
|45 | Errors Archive             | Approved  | أرشفة السجل                                |
|46 | Maintenance Mode           | Approved  | تشغيل/إيقاف                                |
|47 | Clear Cache                | Approved  | cache:clear                                |
|48 | Clear Config               | Approved  | config:clear                               |
|49 | Clear View Cache           | Approved  | view:clear                                 |
|50 | Queue Status               | Approved  | عرض حالة الـ Queue                         |
|51 | Settings (Email)           | Approved  | إعدادات SMTP                               |
|52 | Settings (WhatsApp)        | Approved  | إعدادات WhatsApp                           |
|53 | Settings (Registration)    | Approved  | إعدادات التسجيل                            |
|54 | Settings (Backup)          | Approved  | إعدادات الجدولة                            |
|55 | Settings (System)          | Approved  | إعدادات عامة                               |
|56 | Settings (Branding)        | Approved  | الهوية البصرية                             |

---

## 21. Business Rules

### 21.1 قواعد الموافقة على المدرس

1. لا يمكن الموافقة على مدرس `role !== 'teacher'` أو `status !== 'pending'`.
2. بعد الموافقة يصبح `status = 'active'`.

### 21.2 قواعد رفض المدرس

1. لا يمكن رفض مدرس `role !== 'teacher'` أو `status !== 'pending'`.
2. بعد الرفض يصبح `status = 'rejected'`.
3. سبب الرفض اختياري (max 500 characters).

### 21.3 قواعد تفعيل / تعليق / حذف المدرس

1. لا يمكن تنفيذ أي عملية على مستخدم `role !== 'teacher'`.
2. Activate: يغير status إلى `active`.
3. Deactivate: يغير status إلى `inactive`.
4. Suspend: يغير status إلى `suspended`.
5. Restore: يغير status إلى `active` (دائماً).
6. Soft Delete: يغير status إلى `deleted`.

### 21.4 قواعد النشاط (Activity Log)

1. كل عملية من عمليات الأدمن على المدرسين تسجل في Activity Log تلقائياً.
2. كل عملية تحديث إعدادات المنصة تسجل.
3. كل عملية إنشاء أو حذف نسخة احتياطية تسجل.
4. كل عملية Impersonation تسجل بالكامل.

### 21.5 قواعد المجموعات (Groups)

1. الأدمن يستطيع فقط تفعيل/تعطيل المجموعة (toggle بين `active` و `inactive`).
2. لا يستطيع إنشاء أو تعديل أو حذف المجموعات.

### 21.6 قواعد النسخ الاحتياطي

1. يجب أن يكون `mysqldump` مثبتاً على الخادم.
2. يتم إنشاء الملف في `storage/app/backups/`.
3. الاسترجاع يتطلب شاشة تأكيد قوية قبل التنفيذ.
4. الجدولة: يومي / أسبوعي / شهري.

### 21.7 قواعد المستخدمين (Users)

1. Users management يعرض جميع المستخدمين (بدون فلترة حسب الدور).
2. العمليات: تفعيل (approve)، رفض (reject)، تعليق (suspend).
3. لا توجد عملية حذف مستخدم من صفحة Users (على عكس صفحة Teachers التي فيها حذف).

### 21.8 قواعد Dashboard

1. الإحصائيات تعتمد على Query مباشر بدون Cache.
2. لا توجد إحصائيات تاريخية أو مقارنات.

### 21.9 قواعد الطلاب (Student Management)

1. الأدمن يرى السجل الأكاديمي للطالب قراءة فقط.
2. الأدمن لا يعدل درجات أو حضور الطالب مباشرة.
3. نقل الطالب لمدرس آخر متاح عند الحاجة.

### 21.10 قواعد المساعدين (Assistant Management)

1. الأدمن لا يعدل صلاحيات المساعد — صلاحياته يحددها المدرس فقط.
2. جميع عمليات المساعدين تسجل في Activity Log.

### 21.11 قواعد Impersonation

1. الأدمن يدخل بحساب المدرس بدون كلمة مرور.
2. يظهر Banner واضح أن الأدمن يعمل كمدرس.
3. يوجد زر "العودة لحساب الأدمن".
4. العملية تسجل بالكامل في Activity Log.

### 21.12 قواعد تغيير كلمة المرور

1. يجب إدخال كلمة المرور الحالية للتحقق.
2. يجب إدخال كلمة المرور الجديدة والتأكيد.
3. التحقق الكامل من الصحة قبل الحفظ.

---

## 22. Missing Features (غير معتمد)

الميزات التالية لم يعتمدها مالك المشروع بعد:

- Teacher Request History (تاريخ طلبات التسجيل)
- Charts / Graphs في Dashboard
- Teacher Archive (أرشفة)

---

## 23. Unknown

لا توجد عناصر غير محسومة حالياً.

---

## 24. Questions For Owner

جميع الأسئلة السابقة تمت الإجابة عليها بقرارات رسمية من مالك المشروع.

لا توجد أسئلة جديدة في الوقت الحالي.

---

## 25. Runtime QA Checklist

### 25.1 Authentication
- [ ] Admin can login via `/login` with admin credentials
- [ ] Admin can logout via `/logout`
- [ ] Admin cannot access `/admin/*` without login (redirects to `/login`)
- [ ] Teacher cannot access `/admin/*` (403)
- [ ] Student cannot access `/admin/*` (403)
- [ ] Assistant cannot access `/admin/*` (403)

### 25.2 Dashboard
- [ ] Dashboard loads at `GET /admin/dashboard`
- [ ] All 16 stats cards display correct numbers
- [ ] "مدرسون منتظرون" card shows correct count
- [ ] "مدرسون معلقون" card shows correct count
- [ ] "آخر فصل دراسي" card shows when term exists
- [ ] Quick actions buttons link to correct routes
- [ ] Page title is "لوحة التحكم"

### 25.3 Teacher Requests
- [ ] `GET /admin/teacher-requests` loads
- [ ] Only pending teachers are shown
- [ ] "موافقة" button works and changes status to `active`
- [ ] "رفض" button works and changes status to `rejected`
- [ ] Rejection reason textarea is optional
- [ ] Activity Log is created after approve/reject
- [ ] Empty state shows when no pending requests
- [ ] Pagination works when >20 requests

### 25.4 Teacher Management
- [ ] `GET /admin/teachers` loads
- [ ] All teachers shown with correct status badges
- [ ] "تفعيل" button works for non-active teachers
- [ ] "إلغاء تفعيل" button works for active teachers
- [ ] "تعليق" button works for non-suspended/deleted teachers
- [ ] "استعادة" button works for suspended/inactive/deleted teachers
- [ ] "حذف" button works for non-deleted teachers
- [ ] Confirmation modals appear for all actions
- [ ] Activity Log is created after each action
- [ ] Pagination works when >20 teachers
- [ ] **إنشاء مدرس** — form works, teacher created without registration request
- [ ] **تعديل مدرس** — edit form works, all fields update correctly
- [ ] **بحث** — search works in teacher list
- [ ] **فلترة** — filters work in teacher list

### 25.5 Student Management
- [ ] `GET /admin/students` loads
- [ ] All students displayed with current group info
- [ ] Search works
- [ ] Filters work
- [ ] "تفعيل" button works
- [ ] "تعطيل" button works
- [ ] "تعليق" button works
- [ ] Soft Delete works
- [ ] Restore works
- [ ] Transfer student to another teacher works
- [ ] Academic record view (read-only) works

### 25.6 Assistant Management
- [ ] `GET /admin/assistants` loads
- [ ] All assistants displayed with teacher info
- [ ] Search works
- [ ] Filters work
- [ ] "تعليق" button works
- [ ] "تفعيل" button works
- [ ] Soft Delete works
- [ ] Restore works
- [ ] Permission edit is NOT available (teacher-only)

### 25.7 Users Management
- [ ] `GET /admin/users` loads
- [ ] All users (admin, teacher, student) are displayed
- [ ] "تفعيل" button works
- [ ] "رفض" button works
- [ ] "تعليق" button works

### 25.8 Groups Management
- [ ] `GET /admin/groups` loads
- [ ] Teacher filter works correctly
- [ ] "تفعيل/تعطيل" toggle works
- [ ] Group details (name, teacher, grade, day, time, gender) shown

### 25.9 Activity Log
- [ ] `GET /admin/activity-log` loads
- [ ] Category filter works
- [ ] Actor type filter works
- [ ] Date from/to filters work
- [ ] Search text filter works in description and action
- [ ] Reset button clears filters
- [ ] Pagination works
- [ ] All admin actions appear in logs
- [ ] **حذف السجل** works
- [ ] **تصدير السجل** works
- [ ] **فلترة متقدمة** works

### 25.10 Settings
- [ ] `GET /admin/settings` loads
- [ ] All setting fields are displayed correctly
- [ ] Update settings works (`POST /admin/settings`)
- [ ] Validation works (required fields, max lengths)
- [ ] Activity Log is created on update
- [ ] **Email settings** section works
- [ ] **WhatsApp settings** section works
- [ ] **Registration settings** section works
- [ ] **Backup settings** section works
- [ ] **System settings** section works
- [ ] **Branding settings** section works

### 25.11 Backup
- [ ] `GET /admin/backup` loads
- [ ] Create backup button shows modal
- [ ] `POST /admin/backup/create` works (if mysqldump available)
- [ ] Download backup works
- [ ] Delete backup works with modal confirmation
- [ ] Activity Log is created on create/delete
- [ ] Empty state shows when no backups
- [ ] **Restore backup** works with confirmation screen
- [ ] **Scheduled backups** (daily/weekly/monthly) work
- [ ] **Backup log** shows all operations

### 25.12 System Health
- [ ] `GET /admin/health` loads
- [ ] App version displayed
- [ ] Laravel version displayed
- [ ] PHP version displayed
- [ ] Database status shows "متصل"
- [ ] Storage shows "قابل للكتابة" or "غير قابل للكتابة"
- [ ] Cache status shows "يعمل" or "غير متاح"
- [ ] Last backup date shown (or "لا توجد")

### 25.13 System Tools
- [ ] **Maintenance Mode** toggle works
- [ ] **Clear Cache** button works
- [ ] **Clear Config** button works
- [ ] **Clear View Cache** button works
- [ ] **Queue Status** displays correctly

### 25.14 Errors Viewer
- [ ] `GET /admin/errors` loads
- [ ] Errors are displayed if laravel.log exists
- [ ] Severity levels are translated
- [ ] Empty state shows when no errors
- [ ] **Download log** works
- [ ] **Delete log** works
- [ ] **Archive log** works

### 25.15 Academic Terms Management
- [ ] `GET /admin/terms` loads
- [ ] Create term works
- [ ] Edit term works
- [ ] Soft Delete works
- [ ] Restore works

### 25.16 Admin Reports
- [ ] `GET /admin/reports` loads
- [ ] Teacher report works
- [ ] Student report works
- [ ] Groups report works
- [ ] Exams report works
- [ ] Attendance report works
- [ ] Resources report works
- [ ] Reservations report works
- [ ] Activity report works
- [ ] Backup report works
- [ ] System performance report works
- [ ] **PDF export** works
- [ ] **Excel export** works

### 25.17 Login As Teacher (Impersonation)
- [ ] Admin can login as teacher without password
- [ ] Banner shows "أنت تعمل كمدرس"
- [ ] "العودة لحساب الأدمن" button works
- [ ] Activity Log records the impersonation

### 25.18 Change Password
- [ ] `GET /admin/change-password` loads
- [ ] Current password validation works
- [ ] New password confirmation works
- [ ] Password updates successfully

### 25.19 Notifications
- [ ] Admin receives notification when new teacher registers
- [ ] Admin receives notification when new student registers
- [ ] Admin receives notification on Backup Failed
- [ ] Admin receives notification on Critical Error
- [ ] Admin receives notification on System Health Failure
- [ ] Admin receives notification on group deletion
- [ ] Admin receives notification on exam deletion
- [ ] Admin receives notification on storage full
- [ ] Admin receives notification on SSL expiry

### 25.20 General
- [ ] All admin routes use `auth` and `role:admin` middleware
- [ ] 403 page shows for unauthorized access
- [ ] RTL layout works correctly
- [ ] All text is in Arabic
- [ ] Responsive design works on mobile

---

## التقرير النهائي

| البند                                   | القيمة         |
|-----------------------------------------|----------------|
| الملفات المعدلة                         | 1 ملف          |
| اسم الملف المعدل                        | `docs/specifications/admin_features.md` |
| عدد القرارات الرسمية التي أضيفت         | 16 قراراً      |
| الأقسام التي أصبحت رسمية                | 25 قسماً       |
| هل أصبح الملف المرجع الرسمي النهائي للأدمن؟ | نعم        |
| هل تم تعديل أي ملف آخر داخل المشروع؟    | لا             |

### القرارات الرسمية الـ 16 المضافة

1. **Student Management** — إدارة كاملة للطلاب (عرض، بحث، فلترة، تفعيل، تعطيل، تعليق، حذف، استعادة، نقل، سجل أكاديمي)
2. **Assistant Management** — إدارة عامة للمساعدين (عرض، بحث، فلترة، تعليق، تفعيل، حذف، استعادة) بدون تعديل صلاحيات
3. **Teacher Create** — إنشاء مدرس مباشرة بدون طلب تسجيل
4. **Teacher Edit** — تعديل بيانات المدرس (الاسم، الهاتف، البريد، كلمة المرور، الحالة، المرحلة)
5. **Admin Notifications** — إشعارات النظام المهمة (10 أنواع)
6. **Admin Reports** — 10 تقارير مع PDF و Excel
7. **Scheduled Backups** — يومي / أسبوعي / شهري
8. **Backup Restore** — استرجاع مع شاشة تأكيد
9. **Login As Teacher** — Impersonation مع Banner وعودة
10. **System Tools** — Maintenance Mode, Clear Cache, Clear Config, Clear View Cache, Queue Status
11. **Activity Log Management** — حذف، تصدير، فلترة متقدمة
12. **Errors Management** — تنزيل، حذف، أرشفة
13. **Extended Settings** — البريد، WhatsApp، التسجيل، النسخ الاحتياطية، النظام، الهوية البصرية
14. **Academic Terms Management** — إنشاء، تعديل، Soft Delete، Restore
15. **Backup Management Extended** — إنشاء، تنزيل، حذف، Restore، Schedule، سجل
16. **Change Password** — Feature مستقلة داخل لوحة الأدمن