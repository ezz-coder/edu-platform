# Teacher Module Runtime Testing Plan

Based on `docs/specifications/teacher_features.md` and `routes/web.php`

## Test Checklist

### 1. Authentication & Access
- [ ] Teacher login with mobile/password
- [ ] Teacher dashboard access
- [ ] Role middleware protection (teacher role required)
- [ ] Ownership checks (can't access other teachers' data)

### 2. Dashboard
- [ ] Dashboard loads correctly
- [ ] Statistics cards show: total groups, enrolled students, exams, attendance sessions
- [ ] Active vs inactive groups shown
- [ ] Total seats, remaining seats
- [ ] Pending transfer requests
- [ ] Today's groups (sorted by start time)
- [ ] Last 15 activities (TeacherActivity)
- [ ] Active academic term shown
- [ ] Term undo history (if any)

### 3. Groups
- [ ] List groups (latest first)
- [ ] Create group: title, description, day, start/end time, gender, grade_level, max_students
- [ ] Auto-link to active academic term
- [ ] View group details: enrollments (paginated 20), other groups for transfer, last 15 activities, attendance sessions
- [ ] Edit group: all fields + status (active/inactive)
- [ ] Validation: cannot reduce max_students below current enrollment
- [ ] Schedule change → student notifications
- [ ] Archive group (status = archived)
- [ ] Restore archived group
- [ ] Delete group (soft delete → archive, warning shown)
- [ ] Ownership: can only access own groups

### 4. Students
- [ ] List all enrollments with search (name, student_code, mobile)
- [ ] Filter by group, status
- [ ] Show pending transfer requests separately
- [ ] Add student to group: creates user (role=student), generates student_code, creates GroupEnrollment with card_id & qr_token
- [ ] Validation: group must be active and not full
- [ ] Remove student: status = inactive_due_to_teacher, notification sent
- [ ] Restore removed student
- [ ] View student profile (StudentProfileController)
- [ ] View transfer history (EnrollmentHistory)
- [ ] Move student between groups (single & bulk)
- [ ] Bulk move, bulk remove, bulk restore
- [ ] Transfer requests: approve/reject
- [ ] Student card with QR code
- [ ] Print cards for group
- [ ] Ownership: only students in teacher's groups

### 5. Attendance
- [ ] Create session: select group, lesson_number
- [ ] Rule: only one open session per group (redirect if exists)
- [ ] Scan QR code or manual student_id/student_code
- [ ] Mark present during open session
- [ ] Close session: status=closed, unmarked=absent, apply consecutive absence rule
- [ ] Consecutive absence rule: 2 consecutive absences → inactive_due_to_absence
- [ ] Manual correction (closed sessions only): present↔absent
- [ ] Reopen closed session
- [ ] Edge cases: unenrolled student rejected, invalid QR rejected, duplicate scan rejected, closed session can't scan

### 6. Lessons
- [ ] List lessons
- [ ] Create: title, description, group (teacher's), display_order (auto), is_active
- [ ] Edit: same fields
- [ ] Delete: soft delete, resources get lesson_id=null
- [ ] Move Up / Move Down (display_order)
- [ ] Drag & Drop ordering
- [ ] Recycle bin: restore, force delete

### 7. Resources
- [ ] Types: pdf, image, zip, google_drive, external_link, video_link, other
- [ ] Upload: file (pdf/image/zip/other max 20MB) OR URL (google_drive/external_link/video_link)
- [ ] Target scopes: group, stage, gender, all
- [ ] Operations: create, edit, delete (soft), restore, download, open Drive, search, filter
- [ ] Visibility: visible/hidden/archived
- [ ] expires_at: expired resources hidden from students
- [ ] publish_date: scheduled publishing
- [ ] Pin: max 5 pinned (newest unpins oldest)
- [ ] Duplicate: copies with all targets, title + " (نسخة)"
- [ ] Notifications: new resource → students + assistants with can_upload_resources

### 8. Exams
- [ ] Question Bank: teacher-owned, isolated, types: mcq/true_false/fill_blank/short_answer, correct_answer, options, difficulty, points, linked to lesson/group
- [ ] Create exam: title, description, duration, start_time, end_time, target groups
- [ ] Edit: same + status (visible/hidden)
- [ ] Questions: add manually or pick from bank, sort_order, delete individual
- [ ] Publish: status=visible, notify students
- [ ] Close: status=hidden, no new attempts, running attempts continue
- [ ] Attempts: one per student, states: running/finished/cancelled
- [ ] Start exam: creates Attempt, saves answers progressively
- [ ] If student exits: cannot re-enter, attempt finished, grade based on saved answers
- [ ] Reset attempt: teacher can delete attempt for retry
- [ ] Results: auto-grade, teacher views answers, show_result_immediately, allow_review_after_finish
- [ ] Delete exam: soft delete → archive 7 days → restore within 7 days → force delete
- [ ] Exam time settings: separate edit page

### 9. Question Bank
- [ ] CRUD questions
- [ ] Recycle bin: restore, force delete
- [ ] Pick questions for exam
- [ ] Attach selected to exam

### 10. Reservations
- [ ] Types: printed_notes, books, booklets, practice_sheets, external_material, other
- [ ] Workflow: draft → open → close → archive → reopen
- [ ] Quantity (0=unlimited), open/close dates, target groups
- [ ] Delivery tracking: ReservationItem per student, status pending/delivered
- [ ] Teacher marks delivery manually, can undo
- [ ] Permissions: teacher manages
- [ ] Restrictions: can't edit archived, can't delete archived (force-delete), recycle bin

### 11. Grades (MISSING per spec)
- [ ] **MISSING**: No grades module in code

### 12. Notifications
- [ ] Teacher receives: group schedule change, attendance session closed, exam publish/close, term end, assistant changes, student reservation, student cancel reservation, assistant uploads resource, assistant creates exam, assistant modifies grades, important group events
- [ ] One notification per event (non-spammy)
- [ ] Students receive: enrollment, schedule change, exam publish, new resource, remove/restore group, term start/end, reservation open/close/reopen

### 13. Reports (MISSING per spec)
- [ ] **MISSING**: No dedicated reports module

### 14. Settings (MISSING per spec)
- [ ] **MISSING**: No teacher settings module

### 15. Assistant Management
- [ ] Permissions: can_take_attendance, can_manage_students, can_upload_resources, can_manage_reservations, can_restore_students, can_view_groups, can_manage_lessons, can_manage_exams
- [ ] Create assistant: name, username (unique), password, phone (optional), permissions, status=active
- [ ] Edit permissions anytime
- [ ] Disable/enable (status=inactive/active)
- [ ] Delete: soft delete (status=inactive), retains data
- [ ] Notifications: create→teacher+assistant, edit→assistant, disable/delete→both, enable→both
- [ ] Restrictions: one teacher per assistant, disabled can't login, permissions instant, routes protected by assistant guard + permission middleware

### 16. Teacher Profile
- [ ] Editable: name, email (unique)
- [ ] Non-editable: mobile, teacher_code
- [ ] ProfileController (Laravel default)
- [ ] Delete account possible

### 17. Term End
- [ ] keep_groups: move selected groups to new term
- [ ] new_groups: start fresh
- [ ] Can undo if no new work created (no new attendance/enrollment/group)
- [ ] Cannot undo after new work created

### 18. Missing Features (Per Spec)
- [ ] Grades Module - NOT IMPLEMENTED
- [ ] Reports Module - NOT IMPLEMENTED
- [ ] Teacher Settings - NOT IMPLEMENTED
- [ ] Password Reset for Teacher - NOT IMPLEMENTED
- [ ] Email Verification - NOT IMPLEMENTED
- [ ] 2FA - NOT IMPLEMENTED
- [ ] Advanced Student Search - LIMITED
- [ ] Import Students (CSV/Excel) - NOT IMPLEMENTED
- [ ] Export Data - NOT IMPLEMENTED
- [ ] Bulk Create Lessons - NOT IMPLEMENTED
- [ ] Teacher Calendar - NOT IMPLEMENTED
- [ ] Student Progress Tracking - NOT IMPLEMENTED
- [ ] Messaging System - NOT IMPLEMENTED
- [ ] Announcements - NOT IMPLEMENTED
- [ ] Parent Access - NOT IMPLEMENTED

## Test Execution Strategy

1. **Login as teacher** (using existing credentials)
2. **Navigate through each route** and verify UI loads
3. **Test CRUD operations** for each module
4. **Test business rules** (validations, ownership, edge cases)
5. **Test notifications** (verify they appear)
6. **Document results** with screenshots/logs
7. **Generate runtime report** with pass/fail status

## Known Test Credentials (from CREDENTIALS_SUMMARY.md)
- Teacher: teacher@test.com / password (or phone/password)