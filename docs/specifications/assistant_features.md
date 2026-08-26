# Assistant Module Specification

> **Document Type:** QA Reference Specification  
> **Source of Truth:** Official Business Requirements (Sprint 39)  
> **Last Updated:** Sprint 39  

---

## 1. Assistant Overview

### Role
The Assistant is a secondary account created **by a Teacher** to help manage the teacher's educational operations. The assistant does **not** register independently — only a Teacher can create, edit, disable, or delete an assistant.

### Responsibilities
- Take attendance for the teacher's groups
- Manage students (view, move between groups, restore removed students)
- Manage resources (list, upload, edit, delete, download, search, filter)
- Manage lessons (full CRUD, ordering, recycle bin)
- Manage reservations (list, create, edit, cancel, search, filter)
- Manage exams (create, edit, delete, publish, archive, restore, question bank)
- Manage grades (if Teacher grants permission)
- Manage question bank
- View groups
- View notifications
- Manage own profile (view, edit, change password, change mobile)

### Scope
- Every assistant is **scoped to exactly one Teacher** (`assistant.teacher_id`).
- The assistant **never** has access to data belonging to another teacher.
- The assistant's permissions are **explicitly granted** by the Teacher via boolean flags in `assistant_permissions`.
- The assistant uses a **separate authentication guard** (`assistant`), not the web guard.

### Relationship with Teacher
- **Teacher creates** the assistant account (username + password).
- **Teacher sets** permissions at creation time and can update them later.
- **Teacher can** enable/disable/delete the assistant.
- The assistant works **under the teacher's identity** for all data ownership (e.g., resources, exams, attendance sessions are stored with `teacher_id`).
- The assistant **cannot** create other assistants.
- The assistant **cannot** modify their own permissions.

---

## 2. Authentication

### Login
- **Route:** `GET|POST /assistant/login` → `AssistantAuthController@showLogin` / `login`
- **Route Name:** `assistant.login`
- **Guard:** `assistant` (separate session, separate provider: `App\Models\Assistant`)
- **Credentials:** `username` + `password`
- **Validation:** Both fields required, string
- **Error on failure:** `'بيانات الدخول غير صحيحة.'` (attached to `username` field)
- **Inactive assistant blocked:** If `$assistant->isActive()` returns false (status !== 'active'), the login is rejected with: `'لم يعد لديك صلاحيات للوصول إلى هذه المنصة. يرجى التواصل مع المدرس.'`
- **Session:** Regenerated on successful login
- **Redirect after login:** `redirect()->intended(route('assistant.dashboard'))`

### Logout
- **Route:** `POST /assistant/logout` → `AssistantAuthController@logout`
- **Route Name:** `assistant.logout`
- **Behavior:** Logs out the assistant guard, invalidates session, regenerates token, redirects to `assistant.login`

### Session
- The assistant uses a **separate session** from the web guard. An assistant can be logged in simultaneously with a teacher/student/admin in the same browser (different guards).

### Password
- **Official Feature:** Assistant MUST be able to change their own password via their profile.
- Current implementation: No password reset or change functionality exists for assistants. Password is set by the Teacher at creation and can be updated by the Teacher via the edit form.
- Password is hashed using Laravel's default hashing (model has `'password' => 'hashed'` cast).

### Access Control
- **Middleware:** `AssistantMiddleware` (registered as `assistant` in Kernel)
- **Behavior:**
  1. If not authenticated via `auth('assistant')` → redirect to `assistant.login`
  2. If authenticated but inactive (`!$assistant->isActive()`) → 403: `'لم يعد لديك صلاحيات للوصول إلى هذه المنصة. يرجى التواصل مع المدرس.'`
  3. If permission parameter provided (e.g., `assistant:can_take_attendance`) and assistant lacks that permission → 403: `'غير مصرح لك بتنفيذ هذا الإجراء'`

---

## 3. Assistant Dashboard

- **Route:** `GET /assistant/dashboard` → `AssistantAuthController@dashboard`
- **Route Name:** `assistant.dashboard`
- **Middleware:** `assistant` (no specific permission required)
- **View:** `assistant.dashboard`

### Official Required Behavior
The dashboard MUST display cards for EVERY permission the assistant owns:

| Permission | Card Label | Link To |
|-----------|------------|---------|
| `can_take_attendance` | تسجيل الحضور | Attendance index |
| `can_manage_students` | إدارة الطلاب | Students index |
| `can_view_groups` | مشاهدة المجموعات | Groups index |
| `can_manage_lessons` | إدارة الدروس | Lessons index |
| `can_upload_resources` | إدارة الموارد | Resources list |
| `can_manage_reservations` | إدارة الحجوزات | Reservations list |
| `can_manage_exams` | إدارة الامتحانات | Exams index |
| `can_manage_grades` | إدارة الدرجات | Grades index |
| — | الإشعارات | Notifications index |
| — | الملف الشخصي | Profile page |

### Current Implementation Status
**Official feature planned but currently incomplete.**
- The dashboard currently only shows cards for 6 permissions: `can_take_attendance`, `can_manage_students`, `can_upload_resources`, `can_manage_reservations`, `can_restore_students`, `can_view_groups`.
- Cards for `can_manage_lessons` and `can_manage_exams` are missing from the dashboard view.
- No card exists for grades.
- No card exists for profile.
- The "no permissions" fallback message only checks 6 permissions.

### What is currently shown
1. **Teacher info header:**
   - Assistant name
   - Teacher name (under whom the assistant works)
   - Logout button
2. **Allowed actions grid** (partial):
   - `can_take_attendance` → "تسجيل الحضور" card → links to `assistant.attendance.index`
   - `can_manage_students` → "إدارة الطلاب" card → links to `assistant.students.index`
   - `can_upload_resources` → "رفع الموارد" card → links to `assistant.resources.create`
   - `can_manage_reservations` → "إدارة الحجوزات" card → links to `assistant.reservations.create`
   - `can_view_groups` → "مشاهدة المجموعات" card → links to `assistant.groups.index`
   - If **no permissions** are enabled, a message is shown: `'لا توجد صلاحيات مفعّلة لحسابك حالياً. يرجى التواصل مع المدرس.'`

---

## 4. Assistant Permissions

### Permission Model (`AssistantPermission`)

| Column | Type | Default | Description | Status |
|--------|------|---------|-------------|--------|
| `assistant_id` | FK → assistants | — | Unique (one permission row per assistant) | Implemented |
| `can_take_attendance` | boolean | false | Take and manage attendance sessions | Implemented |
| `can_manage_students` | boolean | false | View and move students between groups | Implemented |
| `can_upload_resources` | boolean | false | Full resource management (list, upload, edit, delete) | Implemented (limited to create only) |
| `can_manage_reservations` | boolean | false | Full reservation management (list, create, edit, cancel) | Implemented (limited to create only) |
| `can_restore_students` | boolean | false | Restore removed/inactive students | Implemented |
| `can_view_groups` | boolean | false | View the teacher's groups | Implemented |
| `can_manage_lessons` | boolean | false | Full lesson management including ordering and recycle bin | Implemented (limited — missing ordering and recycle bin) |
| `can_manage_exams` | boolean | false | Full exam management including create | Implemented (limited — missing create) |
| `can_manage_grades` | **NOT IN DB** | — | Manage student grades | **Official feature planned but currently incomplete** |

### Permission → Feature Mapping

| Permission | Allowed | Denied | Conditional |
|-----------|---------|--------|-------------|
| `can_take_attendance` | View attendance sessions, create new session, scan student attendance, close session (auto-marks absent) | — | — |
| `can_manage_students` | View all students of the teacher, move student to another group | Cannot add new students, cannot remove students, cannot bulk operations | — |
| `can_upload_resources` | Full resource management (list, upload, edit, delete, download, search, filter) | — | **Official feature planned but currently incomplete** — only upload is implemented |
| `can_manage_reservations` | Full reservation management (list, create, edit, cancel, search, filter) | — | **Official feature planned but currently incomplete** — only create is implemented |
| `can_restore_students` | Restore students with status `removed`, `inactive_due_to_absence`, `inactive_due_to_teacher` | — | Requires `can_manage_students` route group |
| `can_view_groups` | View list of groups with enrollment counts | Cannot create, edit, delete, archive groups | — |
| `can_manage_lessons` | Full lesson management (CRUD, ordering, move up/down, recycle bin, restore, force delete) | — | **Official feature planned but currently incomplete** — missing ordering and recycle bin |
| `can_manage_exams` | Full exam management including create, edit, delete, publish, close, archive, restore, question bank | — | **Official feature planned but currently incomplete** — missing create exam |
| `can_manage_grades` | Manage student grades (view, edit) | — | Teacher MUST grant this permission. **Official feature planned but currently incomplete.** |

### Permission Enforcement
- **Route level:** Middleware `assistant:<permission>` on route groups
- **Controller level:** Authorization helpers (`authorizeSession`, `authorizeEnrollment`, `authorizeLesson`, `authorizeExam`, `authorizeQuestionInBank`) verify ownership via `teacher_id`
- **Model level:** `Assistant::canPerm(string $permission): bool` checks the permission record

---

## 5. Student Management

### View Students
- **Route:** `GET /assistant/students` → `AssistantController@studentsIndex`
- **Route Name:** `assistant.students.index`
- **Middleware:** `assistant:can_manage_students`
- **View:** `assistant.students.index`
- **Data:** All enrollments where the group belongs to the assistant's teacher, with student and group relations, plus all groups for the move dropdown
- **Restrictions:** Cannot add new students, cannot remove students, cannot bulk operations

### Move Student
- **Route:** `POST /assistant/students/{enrollment}/move` → `AssistantController@studentMove`
- **Route Name:** `assistant.students.move`
- **Middleware:** `assistant:can_manage_students`
- **Validation:** `group_id` required, must exist in `study_groups`
- **Restrictions:**
  - Target group must belong to the same teacher
  - Target group must be `active`
  - Target group must not be full (`isFull()`)
  - Enrollment must belong to the teacher's group (`authorizeEnrollment`)
- **Action:** Calls `$enrollment->transferTo($target->id, 'moved')`

### Restore Student
- **Route:** `POST /assistant/students/{enrollment}/restore` → `AssistantController@studentRestore`
- **Route Name:** `assistant.students.restore`
- **Middleware:** `assistant:can_restore_students`
- **Restrictions:**
  - Only works for statuses: `removed`, `inactive_due_to_absence`, `inactive_due_to_teacher`
  - Enrollment must belong to the teacher's group
- **Action:** Calls `$enrollment->restoreActive('مساعد')`

---

## 6. Attendance

### View Attendance Sessions
- **Route:** `GET /assistant/attendance` → `AssistantController@attendanceIndex`
- **Route Name:** `assistant.attendance.index`
- **Middleware:** `assistant:can_take_attendance`
- **View:** `assistant.attendance.index`
- **Data:** All attendance sessions for the teacher's groups, with group relation, ordered by `started_at` descending

### Create Attendance Session
- **Route:** `GET /assistant/attendance/create` → `AssistantController@attendanceCreate`
- **Route Name:** `assistant.attendance.create`
- **Middleware:** `assistant:can_take_attendance`
- **View:** `assistant.attendance.create`
- **Data:** Active groups only

### Store Attendance Session
- **Route:** `POST /assistant/attendance` → `AssistantController@attendanceStore`
- **Route Name:** `assistant.attendance.store`
- **Middleware:** `assistant:can_take_attendance`
- **Validation:** `group_id` (required, exists), `lesson_number` (required, integer, min:1)
- **Behavior:**
  - Group must belong to the assistant's teacher
  - If an open session already exists for that group → redirect to that session with info message
  - Creates session with status `open`, `attendance_date` = today, `started_at` = now

### Show Attendance Session
- **Route:** `GET /assistant/attendance/{session}` → `AssistantController@attendanceShow`
- **Route Name:** `assistant.attendance.show`
- **Middleware:** `assistant:can_take_attendance`
- **View:** `assistant.attendance.show`
- **Data:** Session, group, enrollments, records by student, counts (total, recorded, remaining), last record
- **Authorization:** Session must belong to the assistant's teacher

### Scan Student Attendance
- **Route:** `POST /assistant/attendance/{session}/scan` → `AssistantController@attendanceScan`
- **Route Name:** `assistant.attendance.scan`
- **Middleware:** `assistant:can_take_attendance`
- **Validation:** `student_id` (nullable, integer) OR `student_code` (required_without:student_id, string), `qr_token` (nullable, string)
- **Behavior:**
  - Session must be open
  - Student found by `student_id` or `student_code` (role=student)
  - Student must be enrolled in the group with status `enrolled`
  - If `qr_token` provided, must match enrollment's `qr_token`
  - Duplicate scan prevented (checks if record already exists)
  - Creates record with `attendance_type = 'present'`

### Close Attendance Session
- **Route:** `POST /assistant/attendance/{session}/close` → `AssistantController@attendanceClose`
- **Route Name:** `assistant.attendance.close`
- **Middleware:** `assistant:can_take_attendance`
- **Behavior:**
  - Session must be open
  - Auto-creates `absent` records for all enrolled students not yet recorded
  - Sets status to `closed`, `closed_at` = now

### Workflow
1. Teacher creates groups
2. Assistant starts an attendance session for a group + lesson number
3. Assistant scans students (by code or ID) as they arrive
4. Assistant closes the session → absent students are auto-recorded

---

## 7. Lessons

### Official Required Features
The assistant MUST have:
- View lessons
- Create lesson
- Edit lesson
- Delete lesson
- Lesson ordering (Move Up / Move Down)
- Restore deleted lessons
- Lesson recycle bin

### Current Implementation Status
**Official feature planned but currently incomplete.**
- View, Create, Edit, Update, Delete are implemented.
- Move Up, Move Down, Restore, Recycle Bin, Force Delete are **missing**.

### Currently Implemented

#### View Lessons
- **Route:** `GET /assistant/lessons` → `AssistantController@lessonsIndex`
- **Route Name:** `assistant.lessons.index`
- **Middleware:** `assistant:can_manage_lessons`
- **View:** `assistant.lessons.index`
- **Data:** All lessons for the teacher, with group relation, ordered by `display_order`

#### Create Lesson
- **Route:** `GET /assistant/lessons/create` → `AssistantController@lessonsCreate`
- **Route Name:** `assistant.lessons.create`
- **Middleware:** `assistant:can_manage_lessons`
- **View:** `assistant.lessons.create`
- **Data:** All groups for the teacher

#### Store Lesson
- **Route:** `POST /assistant/lessons` → `AssistantController@lessonsStore`
- **Route Name:** `assistant.lessons.store`
- **Middleware:** `assistant:can_manage_lessons`
- **Validation:** `group_id` (required, exists), `title` (required, max:255), `description` (nullable), `display_order` (nullable, integer, min:0)
- **Behavior:** Group must belong to the teacher. `display_order` defaults to max+1 if not provided.

#### Edit Lesson
- **Route:** `GET /assistant/lessons/{lesson}/edit` → `AssistantController@lessonsEdit`
- **Route Name:** `assistant.lessons.edit`
- **Middleware:** `assistant:can_manage_lessons`
- **View:** `assistant.lessons.edit`
- **Authorization:** Lesson must belong to the teacher

#### Update Lesson
- **Route:** `PUT /assistant/lessons/{lesson}` → `AssistantController@lessonsUpdate`
- **Route Name:** `assistant.lessons.update`
- **Middleware:** `assistant:can_manage_lessons`
- **Validation:** `group_id` (required, exists), `title` (required, max:255), `description` (nullable), `display_order` (nullable, integer, min:0), `is_active` (nullable, boolean)
- **Authorization:** Lesson must belong to the teacher

#### Delete Lesson
- **Route:** `DELETE /assistant/lessons/{lesson}` → `AssistantController@lessonsDestroy`
- **Route Name:** `assistant.lessons.destroy`
- **Middleware:** `assistant:can_manage_lessons`
- **Behavior:** Sets `lesson_id = null` on associated resources, then soft-deletes the lesson
- **Authorization:** Lesson must belong to the teacher

### Missing Features (Planned)
- Move Up / Move Down ordering
- Lesson recycle bin view
- Lesson restore
- Lesson force delete

---

## 8. Resources

### Official Required Features
The assistant MUST have:
- Resources list
- Upload resource
- Edit resource
- Delete resource
- Download resource
- Search resources
- Filter resources

### Current Implementation Status
**Official feature planned but currently incomplete.**
- Upload (create) is implemented.
- Resources list, Edit, Delete, Download, Search, Filter are **missing**.

### Currently Implemented

#### Create Resource (Form)
- **Route:** `GET /assistant/resources/create` → `AssistantController@resourcesCreate`
- **Route Name:** `assistant.resources.create`
- **Middleware:** `assistant:can_upload_resources`
- **View:** `assistant.resources.create`
- **Data:** All groups, all lessons (with group, ordered)

#### Store Resource
- **Route:** `POST /assistant/resources` → `AssistantController@resourcesStore`
- **Route Name:** `assistant.resources.store`
- **Middleware:** `assistant:can_upload_resources`
- **Validation:**
  - `title` (required, max:255)
  - `description` (nullable)
  - `type` (required, in: pdf, image, zip, google_drive, external_link, video_link, other)
  - `file` (nullable, file, max:20480 KB = 20MB)
  - `url` (nullable, max:1000)
  - `publish_date` (nullable, date)
  - `group_ids` (nullable, array of existing study_groups)
- **Behavior:**
  - File stored in `resources` disk (public)
  - Resource created with `teacher_id`, `academic_term_id` (active term), `pinned = false`, `status = 'visible'`
  - ResourceTarget records created for each selected group
- **Redirect:** `assistant.dashboard` with success message

---

## 9. Exams

### Official Required Features
The assistant MUST be able to:
- Create exams
- Edit exams
- Delete exams
- Publish exams
- Archive exams
- Restore exams
- Manage Question Bank
- Build exams from Question Bank

### Current Implementation Status
**Partially implemented.**
- View exams, view questions, add/delete questions, edit, update, soft-delete, restore, force-delete, publish, close, archive, recycle bin, pick/attach from bank, question bank CRUD — all implemented.
- **Exam creation** is **missing** (no create route).

### Currently Implemented

#### View Exams
- **Route:** `GET /assistant/exams` → `AssistantController@examsIndex`
- **Route Name:** `assistant.exams.index`
- **Middleware:** `assistant:can_manage_exams`
- **View:** `assistant.exams.index`
- **Data:** All exams for the teacher, with questions count, attempts count, finished attempts count, paginated (15 per page)

#### View Exam Questions
- **Route:** `GET /assistant/exams/{exam}/questions` → `AssistantController@examsQuestions`
- **Route Name:** `assistant.exams.questions`
- **Middleware:** `assistant:can_manage_exams`
- **View:** `assistant.exams.questions`
- **Data:** Questions ordered by `sort_order`, all groups for the teacher
- **Authorization:** Exam must belong to the teacher

#### Add Question to Exam
- **Route:** `POST /assistant/exams/{exam}/questions` → `AssistantController@examsAddQuestion`
- **Route Name:** `assistant.exams.questions.add`
- **Middleware:** `assistant:can_manage_exams`
- **Validation:** `question` (required), `option_a/b/c/d` (required), `correct_answer` (required, in:a,b,c,d), `mark` (required, integer, min:1), `sort_order` (nullable, integer, min:0)
- **Authorization:** Exam must belong to the teacher

#### Delete Question from Exam
- **Route:** `DELETE /assistant/exams/{exam}/questions/{question}` → `AssistantController@examsDeleteQuestion`
- **Route Name:** `assistant.exams.questions.delete`
- **Middleware:** `assistant:can_manage_exams`
- **Authorization:** Exam must belong to the teacher

#### Edit Exam
- **Route:** `GET /assistant/exams/{exam}/edit` → `AssistantController@examsEdit`
- **Route Name:** `assistant.exams.edit`
- **Middleware:** `assistant:can_manage_exams`
- **View:** `assistant.exams.edit`
- **Data:** Exam, all groups for the teacher
- **Authorization:** Exam must belong to the teacher

#### Update Exam
- **Route:** `PUT /assistant/exams/{exam}` → `AssistantController@examsUpdate`
- **Route Name:** `assistant.exams.update`
- **Middleware:** `assistant:can_manage_exams`
- **Validation:** `title` (required, max:255), `description` (nullable), `duration_minutes` (required, integer, min:1), `starts_at` (nullable, date), `ends_at` (nullable, date, after_or_equal:starts_at), `status` (required, in:visible,hidden), `group_ids` (required, array, min:1, each exists in study_groups)
- **Authorization:** Exam must belong to the teacher

#### Delete Exam (Soft)
- **Route:** `DELETE /assistant/exams/{exam}` → `AssistantController@examsDestroy`
- **Route Name:** `assistant.exams.destroy`
- **Middleware:** `assistant:can_manage_exams`
- **Authorization:** Exam must belong to the teacher

#### Restore Exam
- **Route:** `POST /assistant/exams/{exam}/restore` → `AssistantController@examsRestore`
- **Route Name:** `assistant.exams.restore`
- **Middleware:** `assistant:can_manage_exams`
- **Authorization:** Exam must belong to the teacher

#### Force Delete Exam
- **Route:** `DELETE /assistant/exams/{exam}/force-delete` → `AssistantController@examsForceDelete`
- **Route Name:** `assistant.exams.force-delete`
- **Middleware:** `assistant:can_manage_exams`
- **Authorization:** Exam must belong to the teacher

#### Publish Exam
- **Route:** `POST /assistant/exams/{exam}/publish` → `AssistantController@examsPublish`
- **Route Name:** `assistant.exams.publish`
- **Middleware:** `assistant:can_manage_exams`
- **Behavior:** Sets status to `visible`, notifies targeted students
- **Authorization:** Exam must belong to the teacher

#### Close Exam
- **Route:** `POST /assistant/exams/{exam}/close` → `AssistantController@examsClose`
- **Route Name:** `assistant.exams.close`
- **Middleware:** `assistant:can_manage_exams`
- **Behavior:** Sets status to `hidden`, notifies targeted students
- **Authorization:** Exam must belong to the teacher

#### Archive Exam
- **Route:** `POST /assistant/exams/{exam}/archive` → `AssistantController@examsArchive`
- **Route Name:** `assistant.exams.archive`
- **Middleware:** `assistant:can_manage_exams`
- **Behavior:** Sets status to `hidden`, then soft-deletes the exam
- **Authorization:** Exam must belong to the teacher

#### Recycle Bin
- **Route:** `GET /assistant/exams/recycle-bin` → `AssistantController@examsRecycleBin`
- **Route Name:** `assistant.exams.recycle-bin`
- **Middleware:** `assistant:can_manage_exams`
- **View:** `assistant.recycle-bin.index` (shared with question bank recycle bin)
- **Data:** Soft-deleted exams for the teacher

#### Pick Questions from Bank
- **Route:** `GET /assistant/exams/{exam}/questions/pick` → `AssistantController@examsPickFromBank`
- **Route Name:** `assistant.exams.questions.pick`
- **Middleware:** `assistant:can_manage_exams`
- **View:** `assistant.exams.questions-pick`
- **Filters:** `type`, `difficulty`, `lesson_id`, `group_id`, `search`
- **Authorization:** Exam must belong to the teacher

#### Attach Question from Bank
- **Route:** `POST /assistant/exams/{exam}/questions/attach` → `AssistantController@examsAttachFromBank`
- **Route Name:** `assistant.exams.questions.attach`
- **Middleware:** `assistant:can_manage_exams`
- **Validation:** `question_id` (required, exists:question_bank,id), `points` (nullable, integer, min:1)
- **Authorization:** Exam and question must belong to the teacher

### Question Bank

#### View Questions
- **Route:** `GET /assistant/question-bank` → `AssistantController@questionBankIndex`
- **Route Name:** `assistant.question-bank.index`
- **Middleware:** `assistant:can_manage_exams`
- **View:** `assistant.question-bank.index`
- **Filters:** `type`, `difficulty`, `lesson_id`, `group_id`, `search`

#### Create Question
- **Route:** `GET /assistant/question-bank/create` → `AssistantController@questionBankCreate`
- **Route Name:** `assistant.question-bank.create`
- **Middleware:** `assistant:can_manage_exams`
- **View:** `assistant.question-bank.create`

#### Store Question
- **Route:** `POST /assistant/question-bank` → `AssistantController@questionBankStore`
- **Route Name:** `assistant.question-bank.store`
- **Middleware:** `assistant:can_manage_exams`
- **Validation:** `title` (nullable, max:255), `body` (required), `type` (required, in:mcq,true_false,fill_blank,short_answer), `correct_answer` (nullable), `options` (nullable, array), `explanation` (nullable), `difficulty` (required, in:easy,medium,hard), `points` (required, integer, min:1), `lesson_id` (nullable, exists), `group_id` (nullable, exists)
- **Behavior:** Sets `teacher_id` to assistant's teacher, `status` to `active`

#### Edit Question
- **Route:** `GET /assistant/question-bank/{question}/edit` → `AssistantController@questionBankEdit`
- **Route Name:** `assistant.question-bank.edit`
- **Middleware:** `assistant:can_manage_exams`
- **View:** `assistant.question-bank.edit`
- **Authorization:** Question must belong to the teacher

#### Update Question
- **Route:** `PUT /assistant/question-bank/{question}` → `AssistantController@questionBankUpdate`
- **Route Name:** `assistant.question-bank.update`
- **Middleware:** `assistant:can_manage_exams`
- **Validation:** Same as store + `status` (required, in:active,inactive)
- **Authorization:** Question must belong to the teacher

#### Delete Question
- **Route:** `DELETE /assistant/question-bank/{question}` → `AssistantController@questionBankDestroy`
- **Route Name:** `assistant.question-bank.destroy`
- **Middleware:** `assistant:can_manage_exams`
- **Authorization:** Question must belong to the teacher

#### Recycle Bin
- **Route:** `GET /assistant/question-bank/recycle-bin` → `AssistantController@questionBankRecycleBin`
- **Route Name:** `assistant.question-bank.recycle-bin`
- **Middleware:** `assistant:can_manage_exams`
- **View:** `assistant.recycle-bin.index`

#### Restore Question
- **Route:** `POST /assistant/question-bank/{question}/restore` → `AssistantController@questionBankRestore`
- **Route Name:** `assistant.question-bank.restore`
- **Middleware:** `assistant:can_manage_exams`
- **Authorization:** Question must belong to the teacher

#### Force Delete Question
- **Route:** `DELETE /assistant/question-bank/{question}/force-delete` → `AssistantController@questionBankForceDelete`
- **Route Name:** `assistant.question-bank.force-delete`
- **Middleware:** `assistant:can_manage_exams`
- **Authorization:** Question must belong to the teacher

### Missing Features (Planned)
- Create exam (form and store)

---

## 10. Reservations

### Official Required Features
The assistant MUST have:
- Reservation list
- Create reservation
- Edit reservation
- Cancel reservation
- Search reservations
- Filter reservations

### Current Implementation Status
**Official feature planned but currently incomplete.**
- Create is implemented (draft status only).
- Reservation list, Edit, Cancel, Search, Filter are **missing**.

### Currently Implemented

#### Create Reservation (Form)
- **Route:** `GET /assistant/reservations/create` → `AssistantController@reservationsCreate`
- **Route Name:** `assistant.reservations.create`
- **Middleware:** `assistant:can_manage_reservations`
- **View:** `assistant.reservations.create`
- **Data:** All groups for the teacher, reservation types

#### Store Reservation
- **Route:** `POST /assistant/reservations` → `AssistantController@reservationsStore`
- **Route Name:** `assistant.reservations.store`
- **Middleware:** `assistant:can_manage_reservations`
- **Validation:** `title` (required, max:255), `description` (nullable), `type` (required, in: reservation types), `quantity` (nullable, integer, min:1), `open_date` (nullable, date), `close_date` (nullable, date, after_or_equal:open_date), `group_ids` (required, array, min:1, each exists)
- **Behavior:** Creates reservation with `status = 'draft'`, syncs target groups
- **Redirect:** `assistant.dashboard` with success message

---

## 11. Grades

### Official Business Rule
The assistant MUST be able to manage grades **ONLY IF** the Teacher grants the `can_manage_grades` permission.

Whenever the assistant edits a grade, the Teacher **MUST receive a notification**.

### Current Implementation Status
**Official feature planned but currently incomplete.**
- There are **no** grade-related routes, controllers, or views for the assistant.
- The `can_manage_grades` permission column does **not** exist in the `assistant_permissions` table yet.
- No grade notification logic exists.

---

## 12. Notifications

### View Notifications
- **Route:** `GET /assistant/notifications` → `NotificationController@index`
- **Route Name:** `assistant.notifications.index`
- **Middleware:** `assistant` (no specific permission)
- **Note:** This uses the same `NotificationController` as the web guard, but the assistant's notifications are filtered by `assistant_id` in the controller.

### Open Notification
- **Route:** `GET /assistant/notifications/{notification}/open` → `NotificationController@open`
- **Route Name:** `assistant.notifications.open`
- **Middleware:** `assistant`

### Mark Notification as Read
- **Route:** `POST /assistant/notifications/{notification}/read` → `NotificationController@markRead`
- **Route Name:** `assistant.notifications.read`
- **Middleware:** `assistant`

### Mark All Notifications as Read
- **Route:** `POST /assistant/notifications/read-all` → `NotificationController@markAllRead`
- **Route Name:** `assistant.notifications.read.all`
- **Middleware:** `assistant`

### Triggers for Assistant Notifications
1. **Assistant created** → "تم إنشاء حسابك كمساعد لدى المدرس. صلاحياتك محددة حسب ما يسمح به."
2. **Permissions changed** → "قام المدرس بتحديث صلاحياتك كمساعد. راجع حسابك لمعرفة ما يمكنك الوصول إليه."
3. **Assistant disabled** → "قام المدرس بتعطيل حسابك كمساعد. لن تتمكن من الدخول."
4. **Assistant enabled** → "قام المدرس بتفعيل حسابك كمساعد مرة أخرى."
5. **Assistant removed** → "قام المدرس بإنهاء صلاحيتك كمساعد."

### Future Notification Trigger (Planned)
6. **Grade edited by assistant** → Teacher receives notification "قام المساعد بتعديل درجات طالب."

---

## 13. Assistant Profile

### Official Feature
The assistant MUST be able to:
- View their own profile
- Edit their own profile
- Change their own password
- Change their mobile number
- Update personal information (name, etc.)

### Current Implementation Status
**Official feature planned but currently incomplete.**
- There are **no** profile-related routes, controllers, or views for the assistant.
- Currently, the assistant's personal information can only be modified by the Teacher via `teacher.assistants.edit`.

---

## 14. Business Rules

1. **Teacher ownership:** Every assistant is scoped to exactly one teacher (`assistant.teacher_id`). All data created by the assistant (attendance sessions, resources, exams, etc.) is owned by the teacher (`teacher_id` on the respective tables).

2. **Permission control:** Assistant permissions are **explicitly granted** by the Teacher. The assistant cannot modify their own permissions. All permissions default to `false`.

3. **Inactive assistant:** An assistant with `status !== 'active'` cannot log in. If already authenticated, they receive a 403 error on any request.

4. **Data isolation:** An assistant can never access another teacher's data. All authorization helpers verify that the resource's `teacher_id` matches the assistant's `teacher_id`.

5. **No self-registration:** Assistants cannot register themselves. Only a Teacher can create an assistant account.

6. **No cascading permissions:** Each permission is independent. Having `can_manage_students` does not automatically grant `can_restore_students`.

7. **Separate authentication:** The assistant uses a separate authentication guard (`assistant`) with its own session. This is independent from the web guard (teacher/student/admin).

8. **No password reset:** There is no password reset mechanism for assistants. However, the assistant MUST be able to change their own password via their profile (official feature — currently incomplete).

9. **Activity logging:** All assistant actions are logged via `ActivityLog` (with `actor_type = 'assistant'`) and `TeacherActivity`.

10. **Grades require permission:** The assistant can only manage grades if the Teacher explicitly grants the `can_manage_grades` permission. Whenever the assistant edits a grade, the Teacher MUST receive a notification.

11. **No student addition/removal:** The assistant can view and move students but cannot add new students to groups or remove students from groups.

12. **Nothing should appear if permission is denied:** Every feature, card, link, and action must be hidden when the assistant lacks the corresponding permission.

---

## 15. Runtime Features

| # | Feature | Route | Controller Method | View | Permission | Implementation Status |
|---|---------|-------|-------------------|------|------------|----------------------|
| 1 | Login form | `GET /assistant/login` | `showLogin` | `assistant.login` | None (guest) | ✅ Implemented |
| 2 | Login POST | `POST /assistant/login` | `login` | — | None (guest) | ✅ Implemented |
| 3 | Logout | `POST /assistant/logout` | `logout` | — | `assistant` | ✅ Implemented |
| 4 | Dashboard | `GET /assistant/dashboard` | `dashboard` | `assistant.dashboard` | `assistant` | ⚠️ Partial — missing cards for lessons, exams, grades, profile |
| 5 | Attendance index | `GET /assistant/attendance` | `attendanceIndex` | `assistant.attendance.index` | `can_take_attendance` | ✅ Implemented |
| 6 | Attendance create form | `GET /assistant/attendance/create` | `attendanceCreate` | `assistant.attendance.create` | `can_take_attendance` | ✅ Implemented |
| 7 | Attendance store | `POST /assistant/attendance` | `attendanceStore` | — | `can_take_attendance` | ✅ Implemented |
| 8 | Attendance show | `GET /assistant/attendance/{session}` | `attendanceShow` | `assistant.attendance.show` | `can_take_attendance` | ✅ Implemented |
| 9 | Attendance scan | `POST /assistant/attendance/{session}/scan` | `attendanceScan` | — | `can_take_attendance` | ✅ Implemented |
| 10 | Attendance close | `POST /assistant/attendance/{session}/close` | `attendanceClose` | — | `can_take_attendance` | ✅ Implemented |
| 11 | Students index | `GET /assistant/students` | `studentsIndex` | `assistant.students.index` | `can_manage_students` | ✅ Implemented |
| 12 | Student move | `POST /assistant/students/{enrollment}/move` | `studentMove` | — | `can_manage_students` | ✅ Implemented |
| 13 | Student restore | `POST /assistant/students/{enrollment}/restore` | `studentRestore` | — | `can_restore_students` | ✅ Implemented |
| 14 | Groups index | `GET /assistant/groups` | `groupsIndex` | `assistant.groups.index` | `can_view_groups` | ✅ Implemented |
| 15 | Lessons index | `GET /assistant/lessons` | `lessonsIndex` | `assistant.lessons.index` | `can_manage_lessons` | ✅ Implemented |
| 16 | Lesson create form | `GET /assistant/lessons/create` | `lessonsCreate` | `assistant.lessons.create` | `can_manage_lessons` | ✅ Implemented |
| 17 | Lesson store | `POST /assistant/lessons` | `lessonsStore` | — | `can_manage_lessons` | ✅ Implemented |
| 18 | Lesson edit form | `GET /assistant/lessons/{lesson}/edit` | `lessonsEdit` | `assistant.lessons.edit` | `can_manage_lessons` | ✅ Implemented |
| 19 | Lesson update | `PUT /assistant/lessons/{lesson}` | `lessonsUpdate` | — | `can_manage_lessons` | ✅ Implemented |
| 20 | Lesson delete | `DELETE /assistant/lessons/{lesson}` | `lessonsDestroy` | — | `can_manage_lessons` | ✅ Implemented |
| 21 | Lesson move up | — | — | — | `can_manage_lessons` | ❌ Official feature planned but currently incomplete |
| 22 | Lesson move down | — | — | — | `can_manage_lessons` | ❌ Official feature planned but currently incomplete |
| 23 | Lesson recycle bin | — | — | — | `can_manage_lessons` | ❌ Official feature planned but currently incomplete |
| 24 | Lesson restore | — | — | — | `can_manage_lessons` | ❌ Official feature planned but currently incomplete |
| 25 | Lesson force delete | — | — | — | `can_manage_lessons` | ❌ Official feature planned but currently incomplete |
| 26 | Resources list | — | — | — | `can_upload_resources` | ❌ Official feature planned but currently incomplete |
| 27 | Resource create form | `GET /assistant/resources/create` | `resourcesCreate` | `assistant.resources.create` | `can_upload_resources` | ✅ Implemented |
| 28 | Resource store | `POST /assistant/resources` | `resourcesStore` | — | `can_upload_resources` | ✅ Implemented |
| 29 | Resource edit | — | — | — | `can_upload_resources` | ❌ Official feature planned but currently incomplete |
| 30 | Resource delete | — | — | — | `can_upload_resources` | ❌ Official feature planned but currently incomplete |
| 31 | Resource download | — | — | — | `can_upload_resources` | ❌ Official feature planned but currently incomplete |
| 32 | Resource search/filter | — | — | — | `can_upload_resources` | ❌ Official feature planned but currently incomplete |
| 33 | Reservation list | — | — | — | `can_manage_reservations` | ❌ Official feature planned but currently incomplete |
| 34 | Reservation create form | `GET /assistant/reservations/create` | `reservationsCreate` | `assistant.reservations.create` | `can_manage_reservations` | ✅ Implemented |
| 35 | Reservation store | `POST /assistant/reservations` | `reservationsStore` | — | `can_manage_reservations` | ✅ Implemented |
| 36 | Reservation edit | — | — | — | `can_manage_reservations` | ❌ Official feature planned but currently incomplete |
| 37 | Reservation cancel | — | — | — | `can_manage_reservations` | ❌ Official feature planned but currently incomplete |
| 38 | Reservation search/filter | — | — | — | `can_manage_reservations` | ❌ Official feature planned but currently incomplete |
| 39 | Exams index | `GET /assistant/exams` | `examsIndex` | `assistant.exams.index` | `can_manage_exams` | ✅ Implemented |
| 40 | Exam create form | — | — | — | `can_manage_exams` | ❌ Official feature planned but currently incomplete |
| 41 | Exam store | — | — | — | `can_manage_exams` | ❌ Official feature planned but currently incomplete |
| 42 | Exam questions | `GET /assistant/exams/{exam}/questions` | `examsQuestions` | `assistant.exams.questions` | `can_manage_exams` | ✅ Implemented |
| 43 | Exam add question | `POST /assistant/exams/{exam}/questions` | `examsAddQuestion` | — | `can_manage_exams` | ✅ Implemented |
| 44 | Exam delete question | `DELETE /assistant/exams/{exam}/questions/{question}` | `examsDeleteQuestion` | — | `can_manage_exams` | ✅ Implemented |
| 45 | Exam edit form | `GET /assistant/exams/{exam}/edit` | `examsEdit` | `assistant.exams.edit` | `can_manage_exams` | ✅ Implemented |
| 46 | Exam update | `PUT /assistant/exams/{exam}` | `examsUpdate` | — | `can_manage_exams` | ✅ Implemented |
| 47 | Exam delete | `DELETE /assistant/exams/{exam}` | `examsDestroy` | — | `can_manage_exams` | ✅ Implemented |
| 48 | Exam restore | `POST /assistant/exams/{exam}/restore` | `examsRestore` | — | `can_manage_exams` | ✅ Implemented |
| 49 | Exam force delete | `DELETE /assistant/exams/{exam}/force-delete` | `examsForceDelete` | — | `can_manage_exams` | ✅ Implemented |
| 50 | Exam publish | `POST /assistant/exams/{exam}/publish` | `examsPublish` | — | `can_manage_exams` | ✅ Implemented |
| 51 | Exam close | `POST /assistant/exams/{exam}/close` | `examsClose` | — | `can_manage_exams` | ✅ Implemented |
| 52 | Exam archive | `POST /assistant/exams/{exam}/archive` | `examsArchive` | — | `can_manage_exams` | ✅ Implemented |
| 53 | Exam recycle bin | `GET /assistant/exams/recycle-bin` | `examsRecycleBin` | `assistant.recycle-bin.index` | `can_manage_exams` | ✅ Implemented |
| 54 | Pick from bank | `GET /assistant/exams/{exam}/questions/pick` | `examsPickFromBank` | `assistant.exams.questions-pick` | `can_manage_exams` | ✅ Implemented |
| 55 | Attach from bank | `POST /assistant/exams/{exam}/questions/attach` | `examsAttachFromBank` | — | `can_manage_exams` | ✅ Implemented |
| 56 | Question bank index | `GET /assistant/question-bank` | `questionBankIndex` | `assistant.question-bank.index` | `can_manage_exams` | ✅ Implemented |
| 57 | Question bank create form | `GET /assistant/question-bank/create` | `questionBankCreate` | `assistant.question-bank.create` | `can_manage_exams` | ✅ Implemented |
| 58 | Question bank store | `POST /assistant/question-bank` | `questionBankStore` | — | `can_manage_exams` | ✅ Implemented |
| 59 | Question bank edit form | `GET /assistant/question-bank/{question}/edit` | `questionBankEdit` | `assistant.question-bank.edit` | `can_manage_exams` | ✅ Implemented |
| 60 | Question bank update | `PUT /assistant/question-bank/{question}` | `questionBankUpdate` | — | `can_manage_exams` | ✅ Implemented |
| 61 | Question bank delete | `DELETE /assistant/question-bank/{question}` | `questionBankDestroy` | — | `can_manage_exams` | ✅ Implemented |
| 62 | Question bank recycle bin | `GET /assistant/question-bank/recycle-bin` | `questionBankRecycleBin` | `assistant.recycle-bin.index` | `can_manage_exams` | ✅ Implemented |
| 63 | Question bank restore | `POST /assistant/question-bank/{question}/restore` | `questionBankRestore` | — | `can_manage_exams` | ✅ Implemented |
| 64 | Question bank force delete | `DELETE /assistant/question-bank/{question}/force-delete` | `questionBankForceDelete` | — | `can_manage_exams` | ✅ Implemented |
| 65 | Grades management | — | — | — | `can_manage_grades` | ❌ Official feature planned but currently incomplete |
| 66 | Profile view | — | — | — | `assistant` | ❌ Official feature planned but currently incomplete |
| 67 | Profile edit | — | — | — | `assistant` | ❌ Official feature planned but currently incomplete |
| 68 | Password change | — | — | — | `assistant` | ❌ Official feature planned but currently incomplete |
| 69 | Mobile change | — | — | — | `assistant` | ❌ Official feature planned but currently incomplete |
| 70 | Notifications index | `GET /assistant/notifications` | `NotificationController@index` | — | `assistant` | ✅ Implemented |
| 71 | Notification open | `GET /assistant/notifications/{notification}/open` | `NotificationController@open` | — | `assistant` | ✅ Implemented |
| 72 | Notification mark read | `POST /assistant/notifications/{notification}/read` | `NotificationController@markRead` | — | `assistant` | ✅ Implemented |
| 73 | Notification mark all read | `POST /assistant/notifications/read-all` | `NotificationController@markAllRead` | — | `assistant` | ✅ Implemented |

---

## 16. Missing Features

Features that are **official requirements** but **not yet implemented** (or partially implemented):

### Fully Missing
1. **Grades management** — No grade routes, controllers, views, or permission column. Required: `can_manage_grades` permission + notification to Teacher on grade edit.
2. **Profile management** — No profile routes, controllers, or views. Required: view profile, edit profile, change password, change mobile.

### Partially Implemented
3. **Resources** — Only upload exists. Missing: list, edit, delete, download, search, filter.
4. **Reservations** — Only create exists. Missing: list, edit, cancel, search, filter.
5. **Lessons** — CRUD exists. Missing: move up, move down, recycle bin, restore, force delete.
6. **Exams** — All management exists. Missing: create exam.
7. **Dashboard** — Cards exist for 6/10 permissions. Missing: cards for lessons, exams, grades, profile.

---

## 17. Broken Features

After reading the implementation, the following appear broken or inconsistent:

1. **Dashboard missing cards:** The dashboard view only checks for 6 permissions (`can_take_attendance`, `can_manage_students`, `can_upload_resources`, `can_manage_reservations`, `can_restore_students`, `can_view_groups`) but does **not** show cards for `can_manage_lessons` or `can_manage_exams`, even though those permissions exist and have routes.

2. **"No permissions" message logic:** The "no permissions" fallback message on the dashboard checks only 6 permissions (`can_take_attendance`, `can_manage_students`, `can_upload_resources`, `can_manage_reservations`, `can_restore_students`, `can_view_groups`). If an assistant has only `can_manage_lessons` or `can_manage_exams` enabled, the dashboard would show the "no permissions" message even though they have active permissions.

---

## 18. Demo Credentials (Development Only)

From `DevelopmentDemoSeeder`:

| Role | Username | Password |
|------|----------|----------|
| Assistant | `assistant@bayan.local` | `Assistant@123` |

The demo assistant has **all permissions** enabled and is linked to the demo teacher (`teacher@bayan.local`).

---

## 19. Database Schema

### `assistants` table
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| teacher_id | bigint FK → users | Cascade on delete |
| name | string | |
| username | string | Unique |
| password | string | Hashed |
| phone | string | Nullable |
| status | string | `active` or `inactive` |
| created_at | timestamp | |
| updated_at | timestamp | |

Index: `(teacher_id, status)`

### `assistant_permissions` table
| Column | Type | Default | Notes |
|--------|------|---------|-------|
| id | bigint PK | | |
| assistant_id | bigint FK → assistants | | Unique, cascade on delete |
| can_take_attendance | boolean | false | |
| can_manage_students | boolean | false | |
| can_upload_resources | boolean | false | |
| can_manage_reservations | boolean | false | |
| can_restore_students | boolean | false | |
| can_view_groups | boolean | false | |
| can_manage_exams | boolean | false | |
| can_manage_lessons | boolean | false | Added in later migration |
| can_manage_grades | boolean (NOT IN DB) | false | **Official feature planned but currently incomplete** |
| created_at | timestamp | | |
| updated_at | timestamp | | |

Unique: `assistant_id`

---

## 20. Document Status

This document is the **official QA reference** for the Assistant module.

All browser QA MUST depend on this file.

Every feature is documented with its current implementation status and its official required behavior.

---

# SPRINT 71 FINAL SIGN-OFF UPDATE (2026-08-26)

هذا القسم يحدّث حالة المواصفة لتطابق الحالة النهائية للإصدار BAYAN v1.0.0:

| Feature | الحالة النهائية | الدليل |
|---|---|---|
| Assistant Login/Logout (guard `assistant`) | COMPLETE — RUNTIME VERIFIED | Sprints 37–40, 70 |
| Dashboard + permission-gated navigation | COMPLETE — RUNTIME VERIFIED | Sprint 64 |
| Profile (edit name/username/password) | COMPLETE — RUNTIME VERIFIED | Sprint 42 |
| Groups / Students / Lessons (حسب الصلاحيات) | COMPLETE — RUNTIME VERIFIED | Sprints 40/45/64 |
| Attendance QR (create/scan/close/reopen/correct) | COMPLETE — RUNTIME VERIFIED | Sprint 70 (BUG E fixed) |
| Resources (upload/edit/delete/download) | COMPLETE — RUNTIME VERIFIED | Sprints 43/44 |
| Reservations (create/open/deliver/undo/close) | COMPLETE — RUNTIME VERIFIED | Sprints 46/70 |
| Exams + Question Bank (`can_manage_exams`) | COMPLETE — RUNTIME VERIFIED | Sprints 41/70 |
| Grades (`can_manage_grades` — أُضيف للـDB في migration 2026_08_24) | COMPLETE — RUNTIME VERIFIED | Sprint 61/70 |
| Permission isolation (403 فوري عند سحب الصلاحية) | COMPLETE — RUNTIME VERIFIED | Sprint 70 security matrix |

ملاحظة: حقل `can_manage_grades` المشار إليه أعلاه بـ "NOT IN DB" أصبح موجوداً فعلياً عبر
`database/migrations/2026_08_24_000001_add_can_manage_grades_to_assistant_permissions.php`.
لا توجد Regression معروفة تمنع الاستخدام.
