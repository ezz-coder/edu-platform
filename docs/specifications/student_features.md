# Student Module Specification

## 1. Student Overview

### Role
الطالب هو مستخدم مسجل في المنصة يمكنه الانضمام إلى مجموعة درس عند مدرس معين والاستفادة من المصادر والامتحانات والحجوزات المقدمة من ذلك المدرس.

### Responsibilities
- متابعة المصادر التعليمية (مذكرات، فيديوهات، روابط)
- أداء الامتحانات المحددة من المدرس
- حجز المواعيد المتاحة (حجوزات)
- متابعة الحضور والغياب
- طلب تغيير المجموعة (بموافقة المدرس)

### Scope
- يقتصر نطاق الطالب على حساب واحد أو أكثر مرتبط برقم هاتف واحد.
- لكل حساب طالب مستقل، يمكن الاشتراك في مجموعة درس عند مدرس واحد فقط لكل حساب.
- لا يمكن للطالب رؤية بيانات طالب آخر.
- لا يمكن للطالب رؤية بيانات مدرس غير مدرسه.

### Access Limitations
- لا يمكن للطالب الوصول إلى لوحة الأدمن أو المدرس أو المساعد.
- لا يمكن للطالب إدارة مجموعات أو طلاب.
- لا يمكن للطالب إنشاء أو تعديل امتحانات أو مصادر.

---

## 2. Authentication

### Registration (StudentRegistrationController)
- URL: `GET /register/student` (route: `register.student`)
- URL: `POST /register/student` (route: `register.student.store`)

#### Flow:
1. **Step 1 (GET):** يدخل الطالب كود المدرس في Query Parameter `?code=XXXXXX`.
   - إذا كان الكود صحيحاً، يتم عرض مجموعات ذلك المدرس النشطة.
   - إذا كان الكود خاطئاً، تظهر رسالة "كود المدرس غير صحيح".
2. **Step 2 (POST):** يقدم الطالب نموذج التسجيل بمتطلبات:
   - `teacher_code` (required, string)
   - `group_id` (required, exists:study_groups,id)
   - `name` (required, string, max:255)
   - `mobile` (required, string, max:20)
   - `parent_mobile` (required, string, max:20)
   - `password` (required, confirmed, Rules\Password::defaults())

#### Business Rules:
- يتم التحقق من صلاحية كود المدرس (يجب أن يكون teacher نشطاً).
- يتم التحقق من أن المجموعة تابعة لذلك المدرس ونشطة.
- لا يمكن التسجيل في مجموعة ممتلئة (max_students).
- لا يمكن تسجيل نفس رقم الهاتف في نفس المجموعة مرتين (duplicate enrollment prevention).
- كل تسجيل ينشئ حساب طالب جديد ومستقل (وليس حساباً مشتركاً).
- يتم إنشاء `student_code` فريد من نوعه (تنسيق: STD-000001).
- يتم إنشاء `card_id` فريد (تنسيق: CARD-XXXXXXXX).
- يتم إنشاء `qr_token` فريد.
- بعد التسجيل الناجح: يتم تسجيل الدخول تلقائياً.
- بعد التسجيل الناجح: يتم وضع `active_student_id` في الجلسة.

### Login
- URL: `GET /login` (route: `login`)
- URL: `POST /login` (route: `login`)

يستخدم نظام Laravel Breeze / Fortify (نظام المصادقة الافتراضي). يتم تسجيل الدخول باستخدام:
- البريد الإلكتروني (email) أو الهاتف (mobile) حسب الإعدادات
- كلمة المرور (password)

#### Redirect after login:
بعد تسجيل الدخول، يتم التوجيه إلى:
- `student.dashboard` إذا كان الدور student
- `teacher.dashboard` إذا كان الدور teacher
- `admin.dashboard` إذا كان الدور admin

(التوجيه يتم عبر `AuthenticatedSessionController`)

### Logout
- URL: `POST /logout` (route: `logout`)

### Session
- يتم استخدام Laravel session management.
- للطالب: يتم تخزين `active_student_id` في الجلسة لتحديد الحساب النشط.

### Access Control
- Middleware: `['auth', 'role:student']` لجميع مسارات الطالب (`/student/*`).
- Middleware `RoleMiddleware` يتحقق من أن `auth()->user()->role === 'student'` وإلا يرجع 403.

### Password
- يمكن تغيير كلمة المرور عبر `/password` (route: `password.update`).
- يمكن إعادة تعيين كلمة المرور عبر forgot-password flow.

---

## 3. Student Dashboard (Home Page)

### Official Definition
الصفحة الرئيسية للطالب هي **Home** تعرض أحدث المحتوى الخاص بالطالب. ليست Dashboard فارغة.

### Expected Content
يجب أن تعرض الصفحة الرئيسية:
1. **آخر الموارد المضافة** — أحدث الموارد التي تستهدف مجموعة الطالب.
2. **آخر الإعلانات** — أحدث الإعلانات (إن وجدت).
3. **آخر الامتحانات** — الامتحانات المتاحة أو القادمة.
4. **آخر الدروس** — أحدث الدروس مع مواردها.
5. **آخر الإشعارات** — أحدث الإشعارات غير المقروءة.
6. **أي عناصر جديدة مرتبطة بالمجموعة** — كل ما هو جديد يخص مجموعة الطالب.

### Current Implementation
- URL: `GET /student/dashboard` — Route: `student.dashboard`
- **Controller:** `StudentResourceController@home`
- **View:** `resources/views/student/resources/home.blade.php`

#### الفرق بين Dashboard و Resources Home:
- `StudentDashboardController@index` يستخدم view `student.dashboard` ويعرض معلومات الحساب.
- `StudentResourceController@home` يعرض المصادر والامتحانات.
- Route `student.dashboard` يشير فعلياً إلى `StudentResourceController@home` (مصادر + دروس + امتحانات).
- Route `student.resources.home` يشير إلى نفس الـ Controller.

### Dashboard View (`resources/views/student/dashboard.blade.php`)
يتم استدعاؤها من `StudentDashboardController@index` فقط (route name: `student.dashboard` غير مستخدم بشكل فعلي).

#### عناصر Dashboard:
1. **زر تبديل الحساب:** رابط إلى `student.accounts`
2. **بطاقة المدرس الحالي:** اسم المدرس وكود المدرس
3. **رسالة ترحيبية:** باسم الطالب
4. **بطاقة الاشتراك (Enrollment):**
   - عنوان المجموعة
   - حالة التسجيل (مسجل / طلب نقل معلق / مزال)
   - كود الطالب
   - اسم المدرس
   - كود المدرس
   - عنوان المجموعة
   - اليوم والوقت
   - المرحلة الدراسية
5. **إذا كان معلق نقل:** رسالة صفراء "طلب النقل قيد المراجعة"
6. **إذا كان مسجلاً:** رابط "طلب نقل إلى مجموعة أخرى"
7. **إذا لم يكن مسجلاً:** رسالة "أنت غير مسجل في أي مجموعة بعد" + رابط تسجيل

### Resources Home View (`resources/views/student/resources/home.blade.php`)
يتم استدعاؤها من `StudentResourceController@home` (route: `student.resources.home`).

#### عناصر Resources Home:
1. **معلومات الحساب:** اسم الطالب النشط والمجموعة النشطة
2. **قسم المصادر العامة:** شبكة من بطاقات المصادر الغير مرتبطة بدرس (pinned items first)
3. **قسم الدروس:** لكل درس، شبكة من بطاقات المصادر المرتبطة به
4. **قسم الامتحانات:** شبكة من بطاقات الامتحانات مع عرض حالتها:
   - "سيبدأ لاحقاً" إذا لم يبدأ الوقت بعد
   - "منتهي" إذا انتهى الوقت
   - "تم الحل" إذا تم إنهاؤه
   - زر "ابدأ الامتحان" إذا كان متاحاً

### Navigation (Student Sidebar - dashboard-layout.blade.php)
روابط الطالب في الشريط الجانبي:
1. **المصادر** → `student.resources.home`
2. **الامتحانات** → `student.exams.index`
3. **الحجوزات** → `student.reservations.index`
4. **الإشعارات** → `notifications.index`
5. **حسابي** → `student.account`

### Bottom Navigation (Mobile)
نفس الروابط الخمسة معروضة في الأسفل للأجهزة المحمولة.

---

## 4. Student Profile

### Official Definition
الطالب يجب أن يستطيع:
- مشاهدة بياناته.
- تعديل بياناته المسموح بها.
- تغيير كلمة المرور.
- تغيير رقم الهاتف.

### صفحة "حسابي" — URL: `GET /student/account` — Route: `student.account`
**Controller:** `StudentAccountController@show`

#### Elemente:
1. **بطاقة الهوية:**
   - أول حرف من الاسم (دائرة زرقاء)
   - اسم الطالب
   - كود الطالب
   - زر "بطاقة الطالب" (→ `student.card`)
   - زر "تحميل التقرير" (→ `student.report`)

2. **البيانات الأساسية:**
   - كود الطالب
   - كود الكارنيه (card_id)
   - رمز QR
   - المدرس
   - المجموعة الحالية
   - الترم الحالي
   - تاريخ التسجيل
   - حالة التسجيل (مسجل / نقل معلق / مزال يدوياً / مزال لغياب / مزال بواسطة المدرس)
   - تاريخ الإزالة (إذا كان غير نشط)
   - حالة الاسترجاع (نشط / مزال)
   - الهاتف
   - هاتف ولي الأمر

3. **ملخص الحضور:**
   - عدد مرات الحضور
   - عدد مرات التأخير
   - عدد مرات الغياب

4. **ملخص الامتحانات:**
   - قائمة الامتحانات المؤداة مع الدرجات (Score/Total)

5. **ملخص الحجوزات:**
   - أحدث 5 حجوزات مع التاريخ

### صفحة "بطاقة الطالب" — URL: `GET /student/card` — Route: `student.card`
**Controller:** `StudentCardController@studentShow`

- عرض بطاقة الطالب مع QR code (يشفر qr_token)
- يمكن طباعة البطاقة
- البطاقة تحدد هوية الطالب فقط، لا تحدد المجموعة

### Avatar
- لا توجد صورة شخصية (avatar) في النظام الحالي. يتم عرض أول حرف من الاسم.

### Personal Information
- مرئية فقط في صفحة "حسابي" (student.account).
- قابلة للتعديل عبر صفحة `profile.edit` (المشتركة لكل المستخدمين).
- الهاتف وهاتف ولي الأمر معروضان ولكن لا توجد واجهة تحرير خاصة بالطالب.

### Password
- يمكن تغيير كلمة المرور عبر `/password` (route: `password.update`).

### Restrictions
- لا يمكن للطالب رؤية أو تعديل بيانات طالب آخر.
- لا توجد صفحة خاصة لتعديل الملف الشخصي للطالب بخلاف `profile.edit`.

---

## 5. Teacher Connection

### كيف ينتمي الطالب إلى المدرس؟

ينتمي الطالب إلى المدرس من خلال:
1. **كود المدرس (Teacher Code):** كود فريد من 6 خانات، تنسيق `TC-XXXXXX` (يتم إنشاؤه تلقائياً عند تسجيل المدرس).
2. **عند التسجيل:** يدخل الطالب كود المدرس في صفحة التسجيل.
3. **إذا كان الكود صحيحاً:** تظهر مجموعات ذلك المدرس النشطة.
4. **اختيار مجموعة واحدة:** يختار الطالب مجموعة درس نشطة تابعة لذلك المدرس.
5. **إنشاء حساب:** يتم إنشاء حساب طالب مستقل مرتبط بتلك المجموعة.

### آلية الاشتراك (Enrollment)

- `GroupEnrollment` هو الجدول الوسيط الذي يربط الطالب (student_id) بالمجموعة (group_id).
- للطالب حساب مستقل واحد لكل اشتراك مع كل مدرس.
- يمكن للطالب التسجيل عند أكثر من مدرس (يتم إنشاء حساب طالب جديد لكل مدرس).

### Teacher Code — الحالة الحالية

كود المدرس (TeacherCode) منفذ بالكامل:
- يتم إنشاء كود فريد عند تسجيل المدرس.
- يظهر الكود في لوحة المدرس.
- الطالب يستخدم هذا الكود للتسجيل.
- التحقق من صحة الكود يتم في `StudentRegistrationController@create` و `StudentRegistrationController@store`.

### Selecting a Teacher
- لا يقوم الطالب "بتحديد" مدرس بشكل مباشر بل من خلال إدخال الكود.
- لا توجد قائمة مفتوحة للمدرسين للاختيار منها.

### Joining a Teacher Workflow
1. الطالب يزور `/register/student` ويدخل كود المدرس.
2. النظام يبحث عن مدرس بهذا الكود (نشط).
3. إذا وجد، تظهر مجموعات ذلك المدرس النشطة.
4. الطالب يختار مجموعة.
5. يتم إنشاء حساب طالب جديد + Enrollment.
6. يتم تسجيل الدخول تلقائياً.

### Expected Business Rules
- يجب أن يكون المدرس "active" لقبول التسجيلات الجديدة.
- يجب أن تكون المجموعة "active" للتسجيل فيها.
- لا يمكن للطالب الانضمام إلى مجموعة ممتلئة.
- لا يمكن لنفس رقم الهاتف التسجيل في نفس المجموعة مرتين.

---

## 6. Groups

### Viewing Groups
- URL: `GET /student/groups` — Route: `student.groups.index`
- **Controller:** `StudentGroupController@index`
- **View:** `resources/views/student/groups/index.blade.php`

#### What the Student Sees:
- جميع المجموعات النشطة في النظام (status = 'active').
- معلومات كل مجموعة: الاسم، المرحلة الدراسية، الوصف، اسم المدرس، اليوم، الوقت، النوع (بنين/بنات/مشترك).
- رابط "سجّل في هذه المجموعة" → ينقل إلى صفحة التسجيل `/register/student`.

#### Choosing a Group:
يتم اختيار المجموعة أثناء عملية التسجيل فقط. لا توجد واجهة مباشرة لاختيار مجموعة بعد التسجيل.

#### Changing Group (Transfer):
عبر `StudentTransferController`:
- URL: `GET /student/groups/{group}/transfer` — Route: `student.transfer.create`
- URL: `POST /student/groups/{group}/transfer` — Route: `student.transfer.store`

**Workflow:**
1. الطالب يضغط على "طلب نقل إلى مجموعة أخرى" من لوحة التحكم.
2. يعرض له قائمة المجموعات النشطة الأخرى لنفس المدرس.
3. يختار مجموعة جديدة ويرسل الطلب.
4. حالة الـ enrollment تتغير إلى `pending_transfer` مع `requested_group_id`.
5. المدرس يوافق أو يرفض الطلب (من خلال `TeacherStudentController@approveTransfer` / `rejectTransfer`).
6. لا يتم النقل فوراً، بل ينتظر موافقة المدرس.

#### Restrictions:
- لا يمكن للطالب تغيير مجموعته بنفسه مباشرة (يحتاج موافقة المدرس).
- لا يمكن نقل الطالب إلى مجموعة تابعة لمدرس آخر.
- لا يمكن النقل إلى مجموعة ممتلئة (التحقق في `TeacherStudentController`).
- لا يمكن إرسال طلب نقل إذا لم يكن الطالب في حالة "enrolled".

---

## 7. Lessons

### How Student Sees Lessons
الدروس تظهر في صفحة المصادر (Resources Home) عند `student.resources.home`.

#### View:
- في `StudentResourceController@home`، يتم جلب الدروس المرتبطة بمجموعة الطالب النشطة.
- كل درس يعرض مع مصادره (Resources).
- يتم الترتيب حسب `ordered()` (وهم order field في Lesson model).
- فقط دروس `is_active = true` تظهر.

#### Opening a Lesson:
- الدروس نفسها ليست صفحات منفصلة للطالب.
- الطالب يرى المصادر المرتبطة بكل درس (resources).
- لا توجد صفحة "عرض درس" خاصة بالطالب.

#### Progress:
- لا يوجد تتبع للتقدم (progress tracking) في النظام الحالي للطالب.

#### Ordering:
- الدروس مرتبة حسب `sort_order` في الـ Lesson model.
- المدرس يمكنه تغيير الترتيب (move-up / move-down).

#### Restrictions:
- الطالب يرى فقط دروس مجموعته النشطة.
- الطالب يرى فقط الدروس النشطة (is_active = true).
- الطالب يرى فقط مصادر الدروس التي تستهدفه (targets).

---

## 8. Resources

### Official Definition
الطالب يستطيع:
- مشاهدة الموارد.
- البحث.
- الفلترة.
- تحميل الملفات.
- فتح روابط Drive.
- مشاهدة PDF.

### Viewing Resources
- URL: `GET /student/resources` — Route: `student.resources.home`
- URL: `GET /student/resources/{resource}` — Route: `student.resources.show`
- **Controller:** `StudentResourceController`

#### Resource Types:
1. `pdf` — يعرض في iframe مع رابط تحميل
2. `image` — يعرض الصورة مع رابط تحميل
3. `zip` — رابط تحميل
4. `other` — رابط تحميل
5. `video_link` — رابط خارجي (YouTube)
6. `google_drive` — رابط Google Drive
7. `external_link` — رابط خارجي

#### Resource Targeting:
المصادر تستهدف الطلاب من خلال `ResourceTarget` model:
- `scope = 'all'` — جميع الطلاب
- `scope = 'group'` — مجموعة محددة
- `scope = 'stage'` — مرحلة دراسية (grade_level)
- `scope = 'gender'` — نوع الجنس (gender_type)

#### Filtering Logic (StudentResourceController@home):
1. المصادر مرتبطة بمدرس المجموعة النشطة للطالب.
2. المصادر يجب أن تكون `status = 'visible'`.
3. المصادر يجب ألا تكون منتهية الصلاحية (expires_at).
4. المصادر يجب أن تستهدف الطالب عبر أحد النطاقات (targets).
5. للطلاب غير النشطين (inactive): تظهر فقط المصادر المنشورة قبل تاريخ الإلغاء (inactivity cutoff).

#### Search & Filtering:
- **حالياً:** لا توجد واجهة بحث أو فلترة في صفحة المصادر.
- **المطلوب رسمياً:** يجب إضافة إمكانية البحث والفلترة حسب:
  - عنوان المورد
  - النوع (PDF, image, video, etc.)
  - الدرس المرتبط
  - تاريخ النشر

#### Downloading Resources:
- الروابط تؤدي إلى `storage/app/public/...` (نظام Laravel storage).
- لا يوجد تحقق إضافي للتحميل (مجرد رابط مباشر).

#### Allowed File Types:
يتم تحديدها من قبل المدرس عند رفع المورد.

#### Restrictions:
- الطالب يرى فقط مصادر مدرسه (teacher_id).
- الطالب يرى فقط المصادر المرئية (visible) وغير منتهية الصلاحية.
- الطالب يرى فقط المصادر التي تستهدف مجموعته.

---

## 9. Exams

### Official Definition
الطالب يستطيع:
- رؤية الامتحانات.
- دخول الامتحان.
- إرسال الإجابة.
- رؤية النتيجة حسب إعداد المدرس.
- مراجعة المحاولات.

### Available Exams
- URL: `GET /student/exams` — Route: `student.exams.index`
- **Controller:** `StudentExamController@index`
- **View:** `resources/views/student/exams/index.blade.php`

#### What Student Sees:
- فقط الامتحانات التي تستهدف مجموعته (targetGroups).
- فقط الامتحانات `status = 'visible'`.
- لكل امتحان: العنوان، المدة، وقت البدء، وقت الانتهاء، الحالة.

#### Exam States:
1. **سيبدأ لاحقاً (Not Started)** — إذا كان `starts_at > now()`.
2. **منتهي (Ended)** — إذا كان `ends_at < now()`.
3. **تم الحل (Finished)** — إذا كان للطالب محاولة بحالة finished.
4. **متاح للبدء** — إذا كان الوقت الحالي ضمن النطاق ولم يتم حله.

### Starting an Exam
- URL: `POST /student/exams/{exam}/start` — Route: `student.exams.start`
- **Controller:** `StudentExamController@start`

#### Flow:
1. التحقق من أن الامتحان يستهدف مجموعة الطالب.
2. التحقق من أن الامتحان مفتوح (isOpen).
3. إنشاء محاولة جديدة (ExamAttempt) إذا لم توجد.
4. إذا كانت هناك محاولة سابقة منتهية، يعيد التوجيه إلى النتيجة مع رسالة "لقد أديت هذا الامتحان مسبقاً".
5. إذا كانت المحاولة موجودة ولم تنته، يستأنف الامتحان.

**هام:** لا توجد محاولات متعددة. `firstOrCreate` + التحقق من `isFinished` يمنع المحاولة الثانية.

### Taking an Exam
- URL: `GET /student/exams/attempt/{attempt}` — Route: `student.exams.take`
- **Controller:** `StudentExamController@take`
- **View:** `resources/views/student/exams/take.blade.php`

#### Features:
- صفحة واحدة مع كل الأسئلة.
- تنقل بين الأسئلة (سابق / تالي).
- Auto-save عند تغيير الإجابة (AJAX → `student.exams.autoSave`).
- Timer (عد تنازلي من `started_at + duration_minutes`).
- عند انتهاء الوقت: يتم تقديم الامتحان تلقائياً.
- حماية Anti-cheat: منع copy, cut, context menu, select.
- تحذير عند إغلاق المتصفح (beforeunload).

### Submitting an Exam
- URL: `POST /student/exams/attempt/{attempt}/submit` — Route: `student.exams.submit`
- **Controller:** `StudentExamController@submit`

#### Flow:
1. جمع كل الإجابات من الـ form.
2. لكل سؤال: مقارنة الإجابة المختارة مع `correct_answer`.
3. حساب الدرجة (score) والمجموع الكلي (total_marks).
4. حفظ الإجابات في `ExamAnswer` (updateOrCreate).
5. تحديث المحاولة: `finished_at`, `score`, `total_marks`, `status = 'finished'`.
6. إشعار المدرس (مرة واحدة فقط عند أول finish).
7. التوجيه إلى صفحة النتيجة.

### Result Visibility
- URL: `GET /student/exams/attempt/{attempt}/result` — Route: `student.exams.result`

#### Behavior:
- إذا كان `exam.show_result_immediately = true`: عرض الدرجة (score/total) مع وقت التسليم.
- إذا كان `exam.show_result_immediately = false`: عرض صفحة "result-hidden" (لا تظهر الدرجة).
- **لا يتم عرض إجابات الطالب الصحيحة/الخاطئة.**

### Review Attempts
- **حالياً:** لا توجد صفحة منفصلة لمراجعة المحاولات السابقة.
- **المطلوب رسمياً:** يجب أن يستطيع الطالب مراجعة محاولاته السابقة (الامتحانات التي قام بها) مع النتائج.

### Timer
- يتم حساب الوقت المتبقي من `started_at + duration_minutes`.
- يتم التحديث كل ثانية عبر JavaScript.
- عند انتهاء الوقت، يتم تقديم الامتحان تلقائياً.
- لا يوجد تمديد للوقت.

### Attempts
- **محاولة واحدة فقط:** `firstOrCreate` في `start()` يضمن عدم إنشاء أكثر من محاولة.
- لا يمكن إعادة الامتحان بعد التسليم.
- يمكن الخروج من الامتحان (leave) → يتم وضع `status = 'cancelled'`.

### Business Rules
- الامتحان متاح فقط في الوقت المحدد (starts_at → ends_at).
- الامتحان متاح فقط للمجموعات المستهدفة.
- محاولة واحدة فقط لكل طالب.
- لا يمكن بدء الامتحان إذا كان الوقت خارج النطاق.
- النتيجة تظهر فوراً إذا كان `show_result_immediately = true`.

---

## 10. Grades

### Official Definition
الطالب يستطيع مشاهدة درجاته فقط. ولا يستطيع تعديل أي شيء.

### Current Implementation
**لا توجد صفحة منفصلة للدرجات.**
الدرجات تظهر ضمن:
1. **صفحة "حسابي" (student.account):** ملخص الامتحانات (آخر الامتحانات المؤداة مع الدرجات).
2. **صفحة "التقرير" (student.report):** سجل الامتحانات مع الدرجات.

### What is Visible:
- اسم الامتحان
- الدرجة المحصلة / المجموع الكلي (score/total_marks)

### Sorting & Filtering:
- لا توجد خيارات فرز أو تصفية منفصلة.
- الترتيب في صفحة account: حسب `latest()` (الأحدث أولاً).
- يظهر فقط الامتحانات التي حالة `status = 'finished'`.

### Exam History:
- متاح في صفحة "حسابي" (limit 5).
- متاح في صفحة "التقرير" (limit 20).

### Restrictions:
- يظهر فقط امتحانات الطالب نفسه (student_id).
- يظهر فقط الامتحانات المنتهية (finished).
- **لا يمكن للطالب تعديل أي درجة.**

---

## 11. Attendance

### Official Definition
الطالب يستطيع مشاهدة:
- الحضور.
- الغياب.
- الإحصائيات.
ولا يستطيع التعديل.

### Current Implementation
**لا توجد صفحة منفصلة للحضور للطالب.**
الحضور يظهر ضمن:
1. **صفحة "حسابي" (student.account):** ملخص الحضور (حضور / متأخر / غياب).
2. **صفحة "التقرير" (student.report):** نفس الملخص.

### What is Visible:
- عدد مرات الحضور (present)
- عدد مرات التأخير (late)
- عدد مرات الغياب (absent)

### Percentage:
- لا توجد نسبة مئوية. فقط أرقام مطلقة.

### History:
- **لا يوجد سجل تفصيلي للحضور.** فقط إحصائيات مجمعة.

### How It's Calculated:
```php
\DB::table('attendance_records')
    ->join('attendance_sessions', ...)
    ->where('attendance_sessions.study_group_id', $enrollment->group_id)
    ->where('attendance_records.student_id', $account->id)
    ->selectRaw('status, count(*) as c')
    ->groupBy('status')
    ->pluck('c', 'status')
```

### Restrictions:
- يظهر فقط حضور المجموعة النشطة للطالب.
- يظهر فقط حضور الطالب نفسه.
- **لا يمكن للطالب تعديل أي سجل حضور.**

---

## 12. Reservations

### Official Definition
عندما يقوم الطالب بإلغاء الحجز:
- لا يتم حذف الحجز فقط.
- بل يجب أيضاً إنشاء Notification للمدرس.
- يعلم المدرس أن الطالب قام بإلغاء الحجز.
- هذه قاعدة Business Logic رسمية.

### Viewing Reservations
- URL: `GET /student/reservations` — Route: `student.reservations.index`
- **Controller:** `StudentReservationController@index`
- **View:** `resources/views/student/reservations/index.blade.php`

#### What Student Sees:
- الحجوزات المفتوحة للمجموعة النشطة للطالب.
- لكل حجز: العنوان، الوصف، المدرس، تاريخ الإغلاق، الكمية المتبقية، الحالة (متاح/مغلق/غير متاح).
- إشعار بالحجوزات الغير مقروءة (نقطة برتقالية).

### Viewing Single Reservation
- URL: `GET /student/reservations/{reservation}` — Route: `student.reservations.show`
- **Controller:** `StudentReservationController@show`

### Creating a Reservation (Reserving)
- URL: `POST /student/reservations/{reservation}/reserve` — Route: `student.reservations.reserve`
- **Controller:** `StudentReservationController@reserve`

#### Workflow:
1. الطالب يضغط على زر الحجز في صفحة تفاصيل الحجز.
2. يتم إنشاء `ReservationItem` مع `student_id` و `group_id` و `delivery_status = 'pending'`.
3. إذا كان الحجز محدود الكمية (not unlimited)، يتم إغلاق الحجز تلقائياً إذا وصلت الكمية للصفر.

### Cancelling a Reservation
- **حالياً:** لا توجد وظيفة إلغاء حجز للطالب. لا توجد route للإلغاء في `StudentReservationController`.
- **المطلوب رسمياً:** يجب إضافة وظيفة إلغاء الحجز مع:
  1. حذف `ReservationItem` أو تحديث حالته.
  2. إنشاء `Notification` للمدرس لإعلامه بأن الطالب قام بإلغاء الحجز.
  3. إذا كان الحجز محدود الكمية، يجب زيادة الكمية المتبقية.

### Viewing Reservation History
- URL: `GET /student/reservations/history` — Route: `student.reservations.history`
- **Controller:** `StudentReservationController@history`
- **View:** `resources/views/student/reservations/history.blade.php`

#### What Student Sees:
- جميع الحجوزات السابقة للطالب (ReservationItems).
- مرتبة حسب `reserved_at` (الأحدث أولاً).

### Restrictions:
- لا يمكن الحجز إذا كان الطالب غير نشط في المجموعة.
- لا يمكن الحجز إذا كان الحجز مغلقاً.
- لا يمكن الحجز إذا نفدت الكمية (للحجوزات المحدودة).
- لا يمكن الحجز مرتين على نفس الحجز.
- الطالب يرى فقط حجوزات مجموعته النشطة.
- الطالب يرى فقط حجوزات مدرسه.

---

## 13. Notifications

### Official Definition
الطالب يجب أن يستقبل إشعارات تلقائياً عند:
- إضافة مورد جديد.
- إضافة درس جديد.
- إضافة امتحان.
- تعديل موعد.
- إلغاء حجز.
- تعديل درجة.
- أي حدث يخص مجموعته.

الطالب يستطيع:
- مشاهدة الإشعارات.
- فتح الإشعار.
- تعليمه كمقروء.
- تعليم الكل كمقروء.

### URL: `GET /notifications` — Route: `notifications.index`
**Controller:** `NotificationController@index` (مشترك لكل الأدوار)

### What Student Sees:
- الإشعارات الخاصة به (حيث `student_id = $user->id`).
- الإشعارات غير المقروءة أولاً، ثم الأحدث.
- كل إشعار: العنوان، الرسالة، النوع، الوقت النسبي، رابط.

### Reading Notifications:
- `GET /notifications/{notification}/open` — Route: `notifications.open`
  - يتم تعليم الإشعار كمقروء.
  - إعادة توجيه إلى الرابط المخزن (إن وجد).
- `POST /notifications/{notification}/read` — Route: `notifications.read`
  - تعليم كمقروء والعودة للخلف.
- `POST /notifications/read-all` — Route: `notifications.read.all`
  - تعليم جميع الإشعارات كمقروءة.

### Current Triggers (When Notifications Are Created):
- **الامتحانات:** عند أول تسليم امتحان من أي طالب، يتم إرسال إشعار للمدرس (NotificationHelper::notifyTeacher).
  - نوع الإشعار: `exam`
  - العنوان: "بدأ استقبال إجابات الامتحان"
- **الحجوزات:** إشعارات الحجوزات تظهر للطالب (نوع `reservation`).
- لا توجد إشعارات تلقائية أخرى للطالب في الكود الحالي.

### Required Triggers (Official — Not Yet Implemented):
يجب إضافة إشعارات تلقائية للطالب عند:
1. **إضافة مورد جديد** — إشعار للطالب عند إضافة مورد يستهدف مجموعته.
2. **إضافة درس جديد** — إشعار للطالب عند إضافة درس جديد لمجموعته.
3. **إضافة امتحان** — إشعار للطالب عند إضافة امتحان جديد.
4. **تعديل موعد** — إشعار للطالب عند تعديل موعد امتحان أو حجز.
5. **إلغاء حجز** — إشعار للمدرس عند إلغاء الطالب لحجزه.
6. **تعديل درجة** — إشعار للطالب عند تعديل درجته.
7. **أي حدث يخص مجموعته** — إشعار عام لكل حدث جديد.

### Unread Count:
- في `StudentReservationController@index`: يتم حساب عدد الإشعارات غير المقروءة (نوع `reservation`).

### Notification Types:
- `exam` — متعلق بالامتحانات
- `reservation` — متعلق بالحجوزات
- `system` — النظام (افتراضي)

---

## 14. Downloads

### What Student Can Download:
1. **ملفات المصادر:** رابط تحميل مباشر لكل مورد (PDF, image, zip, other).
   - يتم التحميل عبر `asset('storage/' . $resource->file_path)`.
2. **التقرير (Report):** صفحة HTML قابلة للطباعة تضم:
   - معلومات الطالب
   - سجل الحضور
   - سجل الامتحانات
   - سجل الحجوزات
   - URL: `GET /student/report` — Route: `student.report`
   - **Controller:** `StudentAccountController@report`
   - **View:** `resources/views/student/report.blade.php`

### Conditions:
- لا توجد صلاحيات إضافية للتحميل (أي طالب مسجل يمكنه تحميل الموارد المتاحة له).
- لا يمكن تحميل الموارد منتهية الصلاحية.

---

## 15. Student Settings

### Current Implementation:
**لا توجد صفحة إعدادات خاصة بالطالب.**

- صفحة `profile.edit` (مشتركة لكل المستخدمين) تسمح بتعديل:
  - الاسم (name)
  - البريد الإلكتروني (email)
  - بعض المعلومات الأساسية
- تغيير كلمة المرور عبر `/password` (route: `password.update`).

### What Is NOT Configurable:
- لا توجد إعدادات إشعارات.
- لا توجد إعدادات عرض.
- لا توجد إعدادات لغة (اللغة عربية فقط).
- لا يمكن للطالب تغيير رقم الهاتف المرتبط بالحساب من واجهة خاصة بالطالب.

---

## 16. Business Rules

### Official Business Rules

**General:**
1. الطالب لا يمكنه رؤية بيانات طالب آخر.
2. الطالب يمكنه فقط الوصول إلى موارد مدرسه.
3. الطالب يمكنه فقط رؤية مجموعاته النشطة.
4. الطالب يمكنه التسجيل عند أكثر من مدرس (حساب مستقل لكل مدرس).
5. كل حساب طالب له `student_code` فريد.
6. كل اشتراك (Enrollment) له `card_id` و `qr_token` فريدين.

**Registration:**
7. لا يمكن التسجيل في مجموعة ممتلئة (تحقق `isFull()`).
8. لا يمكن لنفس رقم الهاتف التسجيل في نفس المجموعة مرتين.
9. المدرس يجب أن يكون `status = 'active'` لقبول التسجيلات.
10. المجموعة يجب أن تكون `status = 'active'` للتسجيل.

**Groups:**
11. لا يمكن للطالب تغيير مجموعته بنفسه مباشرة (يحتاج موافقة المدرس).
12. لا يمكن نقل الطالب إلى مجموعة تابعة لمدرس آخر.
13. يمكن للطالب إرسال طلب نقل إلى مجموعة أخرى لنفس المدرس (pending_transfer).

**Resources:**
14. الطالب يرى فقط المصادر المرئية (visible) وغير منتهية الصلاحية.
15. الطالب يرى فقط المصادر التي تستهدف مجموعته (targets).
16. للطالب الغير نشط (inactive): ترشيح المصادر بما لا يتجاوز تاريخ الإلغاء.
17. الطالب يستطيع البحث والفلترة في الموارد.

**Exams:**
18. الامتحانات متاحة فقط في الوقت المحدد (starts_at → ends_at).
19. الطالب يمكنه فقط رؤية الامتحانات المستهدفة لمجموعته.
20. محاولة واحدة فقط لكل امتحان لكل طالب.
21. لا يمكن بدء امتحان منتهٍ أو لم يبدأ بعد.
22. عند انتهاء الوقت، يتم تقديم الامتحان تلقائياً.
23. النتيجة تظهر فوراً إذا كان `show_result_immediately = true`.
24. لا يتم عرض الإجابات الصحيحة/الخاطئة.
25. إشعار المدرس يتم إرساله مرة واحدة فقط (عند أول finish من أي طالب).
26. الطالب يستطيع مراجعة محاولاته السابقة.

**Reservations:**
27. الطالب لا يمكنه الحجز إذا كان غير نشط في المجموعة.
28. الطالب لا يمكنه الحجز إذا كان الحجز مغلقاً.
29. الطالب لا يمكنه الحجز إذا نفدت الكمية.
30. الطالب لا يمكنه الحجز مرتين على نفس الحجز.
31. الحجوزات محدودة الكمية يتم إغلاقها تلقائياً عند نفاد الكمية.
32. **عند إلغاء الحجز:** يجب إنشاء Notification للمدرس.

**Notifications:**
33. الطالب يرى فقط إشعاراته الخاصة (student_id).
34. الإشعارات مرتبة: غير مقروءة أولاً، ثم الأحدث.
35. **الطالب يستقبل إشعارات تلقائياً عند:** إضافة مورد جديد، إضافة درس جديد، إضافة امتحان، تعديل موعد، إلغاء حجز، تعديل درجة، أي حدث يخص مجموعته.

**Dashboard:**
36. الطالب يرى فقط مجموعته النشطة في لوحة التحكم.
37. الصفحة الرئيسية تعرض أحدث المحتوى (موارد، إعلانات، امتحانات، دروس، إشعارات).

**Inactivity:**
38. الطالب الذي تمت إزالته (inactive) لا يمكنه الحجز.
39. الطالب الذي تمت إزالته يرى فقط الموارد القديمة (قبل تاريخ الإزالة).

**Account Switching:**
40. الطالب يمكنه التبديل بين حساباته المتعددة (نفس رقم الهاتف).
41. الحساب النشط يتم تحديده عبر `active_student_id` في الجلسة.

**Profile:**
42. الطالب يستطيع مشاهدة بياناته الشخصية.
43. الطالب يستطيع تعديل بياناته المسموح بها (الاسم، البريد الإلكتروني).
44. الطالب يستطيع تغيير كلمة المرور.
45. الطالب يستطيع تغيير رقم الهاتف.

**Grades:**
46. الطالب يستطيع مشاهدة درجاته فقط.
47. الطالب لا يستطيع تعديل أي درجة.

**Attendance:**
48. الطالب يستطيع مشاهدة الحضور والغياب والإحصائيات.
49. الطالب لا يستطيع تعديل أي سجل حضور.

---

## 17. Runtime Features

### Feature List

| # | Feature | URL | Controller Method | View | Expected Result | Permissions |
|---|---------|-----|-------------------|------|-----------------|-------------|
| 1 | تسجيل طالب | GET /register/student | StudentRegistrationController@create | auth.register-student | صفحة تسجيل مع إدخال كود المدرس | guest |
| 2 | تسجيل طالب (POST) | POST /register/student | StudentRegistrationController@store | - | إنشاء حساب طالب + توجيه للوحة | guest |
| 3 | لوحة تحكم الطالب | GET /student/dashboard | StudentDashboardController@index | student.dashboard | عرض لوحة الطالب | student |
| 4 | المصادر الرئيسية | GET /student/resources | StudentResourceController@home | student.resources.home | عرض المصادر والدروس والامتحانات | student |
| 5 | تفاصيل المصدر | GET /student/resources/{resource} | StudentResourceController@show | student.resources.show | عرض تفاصيل المورد وعرض/تحميل | student |
| 6 | المجموعات المتاحة | GET /student/groups | StudentGroupController@index | student.groups.index | عرض جميع المجموعات النشطة | student |
| 7 | نموذج طلب نقل | GET /student/groups/{group}/transfer | StudentTransferController@create | student.transfer | عرض نموذج طلب نقل مجموعة | student |
| 8 | إرسال طلب نقل | POST /student/groups/{group}/transfer | StudentTransferController@store | - | إرسال طلب نقل مع تحديث الحالة | student |
| 9 | الحجوزات المفتوحة | GET /student/reservations | StudentReservationController@index | student.reservations.index | عرض الحجوزات المتاحة | student |
| 10 | تفاصيل الحجز | GET /student/reservations/{reservation} | StudentReservationController@show | student.reservations.show | عرض تفاصيل الحجز | student |
| 11 | حجز مورد | POST /student/reservations/{reservation}/reserve | StudentReservationController@reserve | - | إنشاء حجز للطالب | student |
| 12 | سجل الحجوزات | GET /student/reservations/history | StudentReservationController@history | student.reservations.history | عرض تاريخ الحجوزات | student |
| 13 | قائمة الامتحانات | GET /student/exams | StudentExamController@index | student.exams.index | عرض الامتحانات المتاحة | student |
| 14 | بدء الامتحان | POST /student/exams/{exam}/start | StudentExamController@start | - | بدء أو استئناف محاولة امتحان | student |
| 15 | أداء الامتحان | GET /student/exams/attempt/{attempt} | StudentExamController@take | student.exams.take | عرض أسئلة الامتحان مع timer | student |
| 16 | تقديم الامتحان | POST /student/exams/attempt/{attempt}/submit | StudentExamController@submit | - | تقديم الإجابات وحساب الدرجة | student |
| 17 | نتيجة الامتحان | GET /student/exams/attempt/{attempt}/result | StudentExamController@result | student.exams.result / result-hidden | عرض النتيجة أو إخفائها | student |
| 18 | خروج من الامتحان | POST /student/exams/attempt/{attempt}/leave | StudentExamController@leave | - | إلغاء المحاولة (cancelled) | student |
| 19 | Auto-save | POST /student/exams/attempt/{attempt}/auto-save | StudentExamController@autoSave | JSON | حفظ إجابة أثناء الامتحان | student |
| 20 | اختيار الحساب | GET /student/accounts | StudentAccountController@selection | student.accounts | عرض قائمة الحسابات المتاحة | student |
| 21 | تبديل الحساب | POST /student/accounts/switch | StudentAccountController@switch | - | تغيير الحساب النشط في الجلسة | student |
| 22 | حسابي | GET /student/account | StudentAccountController@show | student.account | عرض بيانات الطالب والإحصائيات | student |
| 23 | التقرير | GET /student/report | StudentAccountController@report | student.report | عرض تقرير شامل للطباعة | student |
| 24 | بطاقة الطالب | GET /student/card | StudentCardController@studentShow | student.card | عرض بطاقة الطالب مع QR | student |
| 25 | الإشعارات | GET /notifications | NotificationController@index | notifications.index | عرض الإشعارات | all auth |
| 26 | فتح إشعار | GET /notifications/{notification}/open | NotificationController@open | - | تعليم كمقروء + توجيه | all auth |
| 27 | تعليم مقروء | POST /notifications/{notification}/read | NotificationController@markRead | - | تعليم إشعار كمقروء | all auth |
| 28 | تعليم الكل مقروء | POST /notifications/read-all | NotificationController@markAllRead | - | تعليم جميع الإشعارات كمقروءة | all auth |
| 29 | تعديل الملف الشخصي | GET /profile | ProfileController@edit | profile.edit | عرض نموذج تعديل الملف | auth |
| 30 | تحديث الملف الشخصي | PATCH /profile | ProfileController@update | - | تحديث بيانات المستخدم | auth |

---

## 18. Features Missing

الميزات المطلوبة رسمياً ولكنها غير موجودة في المشروع حالياً:

1. **صفحة Grades/الدرجات منفصلة:** لا توجد صفحة `student.grades.*`. الدرجات تظهر فقط كجزء من "حسابي" و "التقرير".

2. **صفحة Attendance/الحضور منفصلة:** لا توجد صفحة `student.attendance.*`. الحضور يظهر فقط كملخص في "حسابي".

3. **إلغاء الحجز (Cancelling Reservation):** لا توجد وظيفة للطالب لإلغاء حجزه مع إشعار المدرس.

4. **Student Settings/الإعدادات:** لا توجد صفحة إعدادات خاصة بالطالب.

5. **التحميل المباشر للمصادر بدون صفحة تفاصيل:** لا توجد route للتحميل المباشر دون زيارة صفحة التفاصيل.

6. **إشعارات تلقائية للطالب:** لا توجد إشعارات تلقائية عند إضافة مورد جديد، درس جديد، امتحان جديد، تعديل موعد، تعديل درجة.

7. **إشعار للمدرس عند إلغاء الحجز:** لا توجد وظيفة إلغاء حجز أصلاً، وبالتالي لا يوجد إشعار للمدرس.

8. **البحث والفلترة في الموارد:** لا توجد واجهة بحث أو فلترة في صفحة المصادر.

9. **مراجعة المحاولات السابقة للامتحانات:** لا توجد صفحة منفصلة لعرض تاريخ المحاولات.

10. **تغيير رقم الهاتف من واجهة الطالب:** لا توجد واجهة خاصة للطالب لتغيير رقم الهاتف.

---

## 19. Partially Implemented

الميزات الموجودة جزئياً فقط:

1. **الصفحة الرئيسية (Dashboard):**
   - **الموجود:** تعرض المصادر والدروس والامتحانات.
   - **الناقص:** لا تعرض آخر الإعلانات، آخر الإشعارات، أو تجميع لكل المحتوى الجديد في صفحة واحدة.

2. **الملف الشخصي (Profile):**
   - **الموجود:** يمكن مشاهدة البيانات الأساسية وتغيير كلمة المرور عبر `/password`.
   - **الناقص:** لا يمكن تغيير رقم الهاتف من واجهة الطالب. لا توجد صفحة إعدادات منفصلة.

3. **الموارد (Resources):**
   - **الموجود:** مشاهدة الموارد، تحميل الملفات، فتح روابط Drive، مشاهدة PDF.
   - **الناقص:** لا توجد إمكانية بحث أو فلترة.

4. **الامتحانات (Exams):**
   - **الموجود:** رؤية الامتحانات، دخول الامتحان، إرسال الإجابة، رؤية النتيجة.
   - **الناقص:** لا توجد صفحة لمراجعة المحاولات السابقة.

5. **الحجوزات (Reservations):**
   - **الموجود:** عرض الحجوزات المفتوحة، إنشاء حجز جديد، عرض سجل الحجوزات.
   - **الناقص:** لا توجد وظيفة إلغاء حجز.

6. **الإشعارات (Notifications):**
   - **الموجود:** مشاهدة الإشعارات، فتح الإشعار، تعليمه مقروء، تعليم الكل مقروء.
   - **الناقص:** لا توجد إشعارات تلقائية عند إضافة مورد/درس/امتحان جديد، تعديل موعد، تعديل درجة.

7. **الدرجات (Grades):**
   - **الموجود:** مشاهدة الدرجات في صفحة "حسابي" و "التقرير".
   - **الناقص:** لا توجد صفحة منفصلة للدرجات مع فرز وتصفية.

8. **الحضور (Attendance):**
   - **الموجود:** مشاهدة ملخص الحضور (أرقام فقط) في صفحة "حسابي".
   - **الناقص:** لا توجد صفحة منفصلة للحضور مع تفاصيل وإحصائيات ونسبة مئوية.

---

## 20. Broken Features

تم التحليل بناءً على قراءة الكود فقط دون اختبار فعلي:

1. **Route `student.dashboard` يشير إلى Controller خاطئ:**
   - Route name `student.dashboard` يشير إلى `StudentResourceController@home` (وليس `StudentDashboardController@index`).
   - `StudentDashboardController@index` (الذي يعرض `student.dashboard`) غير مستخدم في أي route.
   - قد يكون هذا intentional أو broken. يحتاج توضيح.

2. **حالة `removed` في `StudentAccountController@show`:**
   - Query يبحث عن enrollments بحالة `enrolled`, `pending_transfer`, `inactive_due_to_absence` باستخدام `whereIn`.
   - ثم `orWhere` لحالة `inactive_due_to_teacher` بدون `whereIn`.
   - قد يسبب هذا نتائج غير متوقعة لأنه `orWhere` قد يتجاوز الـ `whereIn` constraint.

3. **صفحة `student.dashboard` تستخدم `first()` بدون تحديد حالة `enrolled` حصراً:**
   - في `resources/views/student/dashboard.blade.php` سطر 32: `where('status', '!=', 'removed')->first()`.
   - هذا قد يعرض بيانات لطلاب غير نشطين (مثلاً `inactive_due_to_absence`).

---

## 21. Needs Clarification

1. **الفرق بين `student.dashboard` و `student.resources.home`:** كلاهما يشير إلى `StudentResourceController@home`.

2. **هل يجب أن تكون صفحة Dashboard الرئيسية هي صفحة `student.account` (حسابي) أم صفحة المصادر؟** حالياً route name `student.dashboard` يذهب إلى صفحة المصادر.

3. **هل الـ route `student.dashboard` يجب أن يشير إلى `StudentDashboardController@index` بدلاً من `StudentResourceController@home`؟** الـ `StudentDashboardController` موجود ومنفذ ولكن لا route يستخدمه.

4. **كيف يتم إلغاء حجز الطالب؟** لا توجد route للإلغاء.

5. **هل هناك إخطارات تلقائية للطالب عند نشر موارد جديدة أو دروس جديدة؟** لا توجد في الكود الحالي.

---

# الملفات المقروءة
1. PROJECT_RULES.md
2. README.md
3. routes/web.php
4. routes/auth.php
5. app/Http/Controllers/StudentDashboardController.php
6. app/Http/Controllers/StudentResourceController.php
7. app/Http/Controllers/StudentAccountController.php
8. app/Http/Controllers/StudentProfileController.php
9. app/Http/Controllers/StudentGroupController.php
10. app/Http/Controllers/StudentTransferController.php
11. app/Http/Controllers/StudentReservationController.php
12. app/Http/Controllers/StudentExamController.php
13. app/Http/Controllers/StudentCardController.php
14. app/Http/Controllers/NotificationController.php
15. app/Http/Controllers/Auth/StudentRegistrationController.php
16. app/Http/Middleware/RoleMiddleware.php
17. app/Models/User.php
18. app/Models/GroupEnrollment.php
19. app/Models/Exam.php
20. app/Models/ExamAttempt.php
21. app/Models/StudyGroup.php
22. app/Models/Resource.php
23. app/Models/Notification.php
24. resources/views/student/dashboard.blade.php
25. resources/views/student/resources/home.blade.php
26. resources/views/student/resources/show.blade.php
27. resources/views/student/account.blade.php
28. resources/views/student/accounts.blade.php
29. resources/views/student/card.blade.php
30. resources/views/student/groups/index.blade.php
31. resources/views/student/exams/index.blade.php
32. resources/views/student/exams/take.blade.php
33. resources/views/student/exams/result.blade.php
34. resources/views/student/reservations/index.blade.php
35. resources/views/components/dashboard-layout.blade.php

# الملفات المعتمدة
1. docs/specifications/student_features.md (الملف الذي تم تحديثه)

# الفيوتشرز المستخرجة
1. تسجيل طالب جديد باستخدام كود المدرس
2. تسجيل الدخول / تسجيل الخروج
3. لوحة تحكم الطالب (مصادر + دروس + امتحانات)
4. صفحة تفاصيل المصدر (عرض/تحميل)
5. قائمة المجموعات المتاحة
6. طلب نقل مجموعة
7. قائمة الحجوزات المفتوحة
8. إنشاء حجز جديد
9. سجل الحجوزات السابقة
10. قائمة الامتحانات المتاحة
11. بدء وأداء الامتحان (مع Timer + Auto-save)
12. تقديم الامتحان وحساب الدرجة تلقائياً
13. عرض نتيجة الامتحان
14. اختيار وتبديل الحسابات المتعددة
15. صفحة "حسابي" مع ملخص الحضور والدرجات والحجوزات
16. بطاقة الطالب (مع QR code)
17. تقرير الطالب (للطباعة)
18. الإشعارات (فتح، تعليم مقروء، تعليم الكل مقروء)

# الفيوتشرز غير المكتملة
1. صفحة منفصلة للدرجات/Grades
2. صفحة منفصلة للحضور/Attendance
3. تصفية المجموعات حسب المرحلة والنوع
4. إلغاء حجز من قبل الطالب مع إشعار المدرس
5. صفحة إعدادات خاصة بالطالب
6. تحميل مباشر للمصادر (بدون صفحة تفاصيل)
7. إشعارات تلقائية للطالب (مورد جديد، درس جديد، امتحان جديد، تعديل موعد، تعديل درجة)
8. البحث والفلترة في الموارد
9. مراجعة المحاولات السابقة للامتحانات
10. تغيير رقم الهاتف من واجهة الطالب

# الفيوتشرز غير الواضحة
1. هل صفحة Dashboard الرئيسية يجب أن تعرض المصادر أم معلومات الحساب؟
2. كيفية إلغاء الحجز من قبل الطالب (لا توجد route للإلغاء)
3. هل هناك إخطارات تلقائية للموارد الجديدة أو الدروس الجديدة؟

# هل تم اختراع أي Feature
لا

# هل تم تعديل أي ملف غير ملف المواصفات
لا

# مكان ملف المواصفات النهائي
docs/specifications/student_features.md