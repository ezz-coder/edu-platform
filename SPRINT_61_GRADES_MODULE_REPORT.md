# Sprint 61 — Grades Module Completion Report

**Status: COMPLETE**

---

## 1. Requirements from Source of Truth

### Business Rules Identified (from docs/specifications/*)

| Rule | Source |
|------|--------|
| **Grade types**: No dedicated grade types. Grades ARE the exam scores (`score`/`total_marks`) stored on `ExamAttempt`. | teacher_features §11, student_features §10, shared_workflows Workflow 12 |
| **Grade → Student**: Grade linked to each student within their group (`exam_attempts.student_id`). | teacher_features §11.1 |
| **Grade → Exam**: Grade tied to an exam (`exam_attempts.exam_id`). Grade → Group via student's enrollment in exam's target groups. | teacher_features §11, student_features §10 |
| **Create**: Grades calculated automatically on exam submission. Teacher manages grades. | shared_workflows Workflow 12 step 1 |
| **Edit — Teacher**: Can edit grades even after publishing; publishing does NOT make them final; edits happen naturally. | teacher_features §11.2, §17.13 |
| **Edit — Assistant**: Only if teacher grants `can_manage_grades` (view + edit). | assistant_features §4, §11, shared_workflows Workflow 12 step 4 |
| **Delete**: NOT specified in Source of Truth → NOT implemented (no invented rules). | — |
| **View — Teacher**: Own groups' students only; cannot access other teachers' students' grades. | teacher_features §11.4, §11.5 |
| **View — Assistant**: With `can_manage_grades` only. | assistant_features §11 |
| **View — Student**: Own grades only; exam name + score/total_marks; account page (limit 5, latest first); report page (limit 20); only finished attempts; cannot edit anything. | student_features §10 |
| **Notifications**: When assistant edits grades → Notification to teacher. No notification for teacher's own grade edits (not in spec). | teacher_features §11.3, §12.1, §17.13 |
| **Publishing**: Teacher publishes grades (appear to student). Publishing ≠ final. | shared_workflows Workflow 12 step 2–3 |
| **Totals/Percentages**: Only score/total_marks display required by spec. Percentage shown as a read-only convenience column in grades tables (derived from score/total_marks — no new stored data). | student_features §10 |

---

## 2. Teacher Grades

Implemented:

- **New controller**: `app/Http/Controllers/TeacherGradeController.php`
  - `index()` — lists all teacher exams with finished-attempt counts + total grades stat.
  - `show(Exam)` — per-exam grades table: student code, name, group, score/total_marks, percentage, finish date. Search (name/code/mobile) + group filter. Stats: targeted / graded / average / max.
  - `update(ExamAttempt)` — edits the score with validation (`required`, `integer`, `min:0`, `max:total_marks`). Allowed regardless of exam publish status (per §11.2).
- **Views**: `resources/views/teacher/grades/index.blade.php`, `show.blade.php`.
- **Routes**: `teacher.grades.index`, `teacher.grades.show`, `teacher.grades.update` (PUT) under `auth + role:teacher`.
- **Navigation**: "الدرجات" sidebar item added for teacher.
- **Ownership enforcement**: `authorizeExam()` aborts 403 if `exam.teacher_id !== auth()->id()`.
- **No delete** implemented (not in Source of Truth).

## 3. Assistant Grades

Implemented using existing permission system (no rebuild):

- **Permission**: `can_manage_grades` — already declared in `AssistantPermission` model (fillable/casts), already present in `TeacherAssistantController::PERMISSION_KEYS` ("إدارة الدرجات") so it appears in Assistant Permissions UI for the teacher to grant/revoke.
- **Controller methods added to `AssistantController`**:
  - `gradesIndex()` — exams list scoped to `$this->teacherId()`.
  - `gradesShow(Exam)` — same grades table as teacher, ownership re-checked via `authorizeExam()`.
  - `gradesUpdate(ExamAttempt)` — validates and updates score, then:
    - Sends **Notification to the teacher** (`NotificationHelper::notifyTeacher`, type `grade_updated`) — required by §11.3.
    - Logs ActivityLog + TeacherActivity entries.
- **Routes**: `assistant.grades.index/show/update` inside `middleware(['assistant:can_manage_grades'])` group.
- **Enforcement verified**:
  - Assistant WITHOUT permission → 403 on all grades routes.
  - Assistant WITH permission → 200, can view/edit.
  - Permission revocation takes effect immediately (existing middleware behavior preserved).

## 4. Student Grades

Per Source of Truth there is **no dedicated student grades page** — grades appear in "حسابي" and "التقرير". These were already implemented; this sprint fixed them to match the spec exactly:

- **Bug fix**: account page now limits exam history to **5** (`latest()->limit(5)`), report page to **20** (`latest()->limit(20)`), ordered newest-first — previously unlimited/unordered.
- Privacy verified: queries filter `student_id = active account id` and `status = 'finished'`; student sees only own finished exam scores (score/total_marks).
- Student blocked from teacher/assistant grades routes (403).

## 5. Database Changes

- **No new tables/models needed** — grades are `exam_attempts.score`/`total_marks` (Source of Truth defines grades as exam scores; no separate grades entity described).
- **Bug-fix migration added**: `database/migrations/2026_08_24_000001_add_can_manage_grades_to_assistant_permissions.php`
  - Root cause: migration `2026_07_25_000001_add_missing_assistant_permissions` was recorded as ran but the `can_manage_grades` column was never actually applied to the SQLite database (only `can_manage_lessons`/`can_manage_exams` existed).
  - Fix adds the missing boolean column (default false), guarded by `Schema::hasColumn` so it is idempotent.
  - Ran successfully against the current database. No unrelated tables touched; no data deleted.

## 6. Permission Changes

- No permission-system rebuild. Reused:
  - `assistant_permissions.can_manage_grades` column (fixed via migration above).
  - Existing `assistant:<permission>` route middleware.
  - Existing `Assistant::canPerm()` model check.
  - Existing `PERMISSION_KEYS` in `TeacherAssistantController` (teacher UI checkbox already existed from Sprint 60).
- Verified end-to-end: grant → access allowed; revoke → 403 immediately.

## 7. Notifications

- New notification type `grade_updated` added to `config/notifications.php` (icon/color/label/action).
- Trigger implemented: assistant edits a grade → one notification to the owning teacher with details (student, exam, old→new score) linking to `teacher.grades.show`.
- Uses existing `NotificationHelper::notifyTeacher` mechanism — no new notification system created.
- Teacher's own grade edits do NOT notify (not required by Source of Truth).

## 8. Bugs Fixed

| Bug | Root Cause | Fix | Re-test |
|-----|-----------|-----|---------|
| `can_manage_grades` column missing from DB despite migration marked "Ran" | Original Sprint-60 migration recorded in migrations table before column actually applied (schema drift) | New guarded migration `2026_08_24_000001_add_can_manage_grades_to_assistant_permissions` | PASS — column exists, permission checks work |
| Student account/report showed ALL finished attempts (unlimited, unordered) | Controller used `->get()` without limit/order | Added `->latest()->limit(5)` (account) and `->limit(20)` (report) per spec | PASS — limits enforced |

Note: `php artisan test` suite failures (33 failed, "no such table: users" on :memory: SQLite) are **pre-existing** environment issues caused by broken legacy migration `2026_07_21_000002_add_owner_to_activity_logs_table` (duplicate column on fresh migrate). Unrelated to Grades; not touched per Bug Handling rules.

## 9. Tests Performed

### Runtime verification (`sprint61_grades_runtime_test.php`) — 16/16 PASS
- Teacher exists / has exams / exam with finished attempts found
- **Teacher grade update persists** (score changed → reloaded → correct)
- Grade restored (data integrity)
- Student sees own grades only
- Assistant permission record exists; `can_manage_grades` field works; `canPerm()` consistent
- Notification creation works (before/after count)
- Model relations intact (ExamAttempt↔Exam↔Student)
- `can_manage_grades` DB column exists

### Regression (`sprint61_regression_test.php`) — 17/17 PASS
See section 10.

## 10. Regression

| Path | Result |
|------|--------|
| Teacher Dashboard | 200 ✅ |
| Teacher Groups | 200 ✅ |
| Teacher Exams | 200 ✅ |
| Teacher Students | 200 ✅ |
| Teacher Attendance (+create) | 200 ✅ |
| Teacher Grades index/show | 200 ✅ |
| Teacher blocked from other teacher's grades | 403 ✅ |
| Assistant Dashboard | 200 ✅ |
| Assistant Attendance | 200 ✅ |
| Assistant WITHOUT `can_manage_grades` → grades | 403 ✅ |
| Assistant WITH permission → grades index/show | 200 ✅ |
| Assistant after revoking permission → grades | 403 ✅ |
| Student Account | 200 ✅ |
| Student Report | 200 ✅ |
| Student Exams | 200 ✅ |
| Student blocked from teacher grades | 403 ✅ |
| Guest redirected from protected grades | 302 ✅ |
| Notifications page | 200 ✅ |

## 11. Remaining Work

None blocking for the Grades Module scope defined by Source of Truth. Noted for future sprints (explicitly out of scope here):
- **Grade export** (§11.4 mentions "تصدير الدرجات" but §18.1 lists Export Data as a missing feature with no format specified) — left unimplemented to avoid inventing rules.
- Pre-existing broken PHPUnit environment (legacy `2026_07_21_000002` migration duplicate-column failure on fresh databases) — separate infrastructure issue.

---

## Final Status

**COMPLETE**

All Grades requirements specified in the Source of Truth are implemented and verified at runtime:
- Teacher: view + edit grades (own groups only), persistence confirmed.
- Assistant: full flow gated by `can_manage_grades` (grant → success, revoke → 403), teacher notified on edit.
- Student: own finished grades only, correct limits (5/20), privacy enforced.
- Database: minimal, guarded migration fixing the missing permission column.
- Notifications: assistant-edit → teacher notification working.