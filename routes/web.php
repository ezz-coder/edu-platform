<?php

use App\Http\Controllers\Auth\ParentAuthController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\ParentDashboardController;
use App\Http\Controllers\StudentMessageController;
use App\Http\Controllers\TeacherAnnouncementController;
use App\Http\Controllers\TeacherMessageController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminGroupController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentAccountController;
use App\Http\Controllers\StudentCardController;
use App\Http\Controllers\StudentGroupController;
use App\Http\Controllers\StudentProfileController;
use App\Http\Controllers\StudentTransferController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\StudentExamController;
use App\Http\Controllers\StudentReservationController;
use App\Http\Controllers\StudentResourceController;
use App\Http\Controllers\AssistantAuthController;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\TeacherAssistantController;
use App\Http\Controllers\TeacherCalendarController;
use App\Http\Controllers\TeacherAttendanceController;
use App\Http\Controllers\TeacherDashboardController;
use App\Http\Controllers\TeacherExamController;
use App\Http\Controllers\TeacherGradeController;
use App\Http\Controllers\TeacherGroupController;
use App\Http\Controllers\QuestionBankController;
use App\Http\Controllers\TeacherLessonController;
use App\Http\Controllers\TeacherProgressController;
use App\Http\Controllers\TeacherReportController;
use App\Http\Controllers\TeacherSettingsController;
use App\Http\Controllers\TeacherStudentImportController;
use App\Http\Controllers\TeacherReservationController;
use App\Http\Controllers\TeacherResourceController;
use App\Http\Controllers\TeacherStudentController;
use App\Http\Controllers\TeacherTermController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Admin Routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::post('/users/{user}/approve', [AdminUserController::class, 'approve'])->name('users.approve');
    Route::post('/users/{user}/reject', [AdminUserController::class, 'reject'])->name('users.reject');
    Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend'])->name('users.suspend');
    Route::get('/groups', [AdminGroupController::class, 'index'])->name('groups.index');
    Route::post('/groups/{group}/toggle', [AdminGroupController::class, 'toggleStatus'])->name('groups.toggle');

    // Sprint 21 — Teacher Registration Approval
    Route::get('/teacher-requests', [AdminController::class, 'teacherRequests'])->name('teacher-requests');
    Route::post('/teachers/{user}/approve', [AdminController::class, 'approveTeacher'])->name('teachers.approve');
    Route::post('/teachers/{user}/reject', [AdminController::class, 'rejectTeacher'])->name('teachers.reject');

    // Sprint 21 — Teacher Management
    Route::get('/teachers', [AdminController::class, 'teachers'])->name('teachers');
    Route::post('/teachers/{user}/activate', [AdminController::class, 'activateTeacher'])->name('teachers.activate');
    Route::post('/teachers/{user}/deactivate', [AdminController::class, 'deactivateTeacher'])->name('teachers.deactivate');
    Route::post('/teachers/{user}/suspend', [AdminController::class, 'suspendTeacher'])->name('teachers.suspend');
    Route::post('/teachers/{user}/restore', [AdminController::class, 'restoreTeacher'])->name('teachers.restore');
    Route::post('/teachers/{user}/soft-delete', [AdminController::class, 'softDeleteTeacher'])->name('teachers.soft-delete');

    // Sprint 21 — Activity Log
    Route::get('/activity-log', [AdminController::class, 'activityLog'])->name('activity-log');

    // Sprint 21 — Settings
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
    Route::post('/settings', [AdminController::class, 'updateSettings'])->name('settings.update');

    // Sprint 21 — Backup
    Route::get('/backup', [AdminController::class, 'backupIndex'])->name('backup.index');
    Route::post('/backup/create', [AdminController::class, 'createBackup'])->name('backup.create');
    Route::get('/backup/{backup}/download', [AdminController::class, 'downloadBackup'])->name('backup.download');
            Route::post('/backup/{backup}/delete', [AdminController::class, 'deleteBackup'])->name('backup.delete');

    // Sprint 21 — Health
    Route::get('/health', [AdminController::class, 'health'])->name('health');

    // Sprint 21 — Errors
    Route::get('/errors', [AdminController::class, 'errors'])->name('errors');
});

// Teacher Routes
Route::middleware(['auth', 'role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('dashboard');
    Route::get('/groups', [TeacherGroupController::class, 'index'])->name('groups.index');
    Route::get('/groups/create', [TeacherGroupController::class, 'create'])->name('groups.create');
    Route::post('/groups', [TeacherGroupController::class, 'store'])->name('groups.store');
    Route::get('/groups/{group}', [TeacherGroupController::class, 'show'])->name('groups.show');
    Route::get('/groups/{group}/edit', [TeacherGroupController::class, 'edit'])->name('groups.edit');
    Route::put('/groups/{group}', [TeacherGroupController::class, 'update'])->name('groups.update');
    Route::post('/groups/{group}/archive', [TeacherGroupController::class, 'archive'])->name('groups.archive');
    Route::post('/groups/{group}/restore', [TeacherGroupController::class, 'restore'])->name('groups.restore');
    Route::delete('/groups/{group}', [TeacherGroupController::class, 'destroy'])->name('groups.destroy');

    Route::get('/attendance', [TeacherAttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/create', [TeacherAttendanceController::class, 'create'])->name('attendance.create');
    Route::post('/attendance', [TeacherAttendanceController::class, 'store'])->name('attendance.store');
    Route::get('/attendance/{session}', [TeacherAttendanceController::class, 'show'])->name('attendance.show');
    Route::post('/attendance/{session}/close', [TeacherAttendanceController::class, 'close'])->name('attendance.close');
    Route::post('/attendance/{session}/reopen', [TeacherAttendanceController::class, 'reopen'])->name('attendance.reopen');
    Route::post('/attendance/{session}/scan', [TeacherAttendanceController::class, 'scan'])->name('attendance.scan');
    Route::post('/attendance/{session}/record', [TeacherAttendanceController::class, 'updateRecord'])->name('attendance.record.update');

    Route::get('/term/end', [TeacherTermController::class, 'endForm'])->name('term.end');
    Route::post('/term/end/confirm', [TeacherTermController::class, 'confirm'])->name('term.confirm');
    Route::post('/term/end/execute', [TeacherTermController::class, 'execute'])->name('term.execute');
    Route::post('/term/undo', [TeacherTermController::class, 'undo'])->name('term.undo');

    // Sprint 67: bulk lesson creation (registered before wildcard routes).
    Route::get('/lessons/bulk-create', [TeacherLessonController::class, 'bulkCreate'])->name('lessons.bulk.create');
    Route::post('/lessons/bulk-create', [TeacherLessonController::class, 'bulkStore'])->name('lessons.bulk.store');

    Route::get('/lessons', [TeacherLessonController::class, 'index'])->name('lessons.index');
    Route::get('/lessons/create', [TeacherLessonController::class, 'create'])->name('lessons.create');
    Route::post('/lessons', [TeacherLessonController::class, 'store'])->name('lessons.store');
    Route::get('/lessons/{lesson}/edit', [TeacherLessonController::class, 'edit'])->name('lessons.edit');
    Route::put('/lessons/{lesson}', [TeacherLessonController::class, 'update'])->name('lessons.update');
    Route::post('/lessons/reorder', [TeacherLessonController::class, 'reorder'])->name('lessons.reorder');
    Route::post('/lessons/{lesson}/move-up', [TeacherLessonController::class, 'moveUp'])->name('lessons.move-up');
    Route::post('/lessons/{lesson}/move-down', [TeacherLessonController::class, 'moveDown'])->name('lessons.move-down');
    Route::delete('/lessons/{lesson}', [TeacherLessonController::class, 'destroy'])->name('lessons.destroy');
    Route::get('/lessons/recycle-bin', [TeacherLessonController::class, 'recycleBin'])->name('lessons.recycle-bin');
    Route::post('/lessons/{lesson}/restore', [TeacherLessonController::class, 'restore'])->name('lessons.restore');
    Route::delete('/lessons/{lesson}/force-delete', [TeacherLessonController::class, 'forceDelete'])->name('lessons.force-delete');

    Route::get('/resources', [TeacherResourceController::class, 'index'])->name('resources.index');
    Route::get('/resources/create', [TeacherResourceController::class, 'create'])->name('resources.create');
    Route::post('/resources', [TeacherResourceController::class, 'store'])->name('resources.store');
    Route::get('/resources/{resource}/edit', [TeacherResourceController::class, 'edit'])->name('resources.edit');
    Route::put('/resources/{resource}', [TeacherResourceController::class, 'update'])->name('resources.update');
    Route::post('/resources/{resource}/duplicate', [TeacherResourceController::class, 'duplicate'])->name('resources.duplicate');
    Route::post('/resources/{resource}/pin', [TeacherResourceController::class, 'togglePin'])->name('resources.pin');
    Route::delete('/resources/{resource}', [TeacherResourceController::class, 'destroy'])->name('resources.destroy');
    Route::get('/resources/recycle-bin', [TeacherResourceController::class, 'recycleBin'])->name('resources.recycle-bin');
    Route::post('/resources/{resource}/restore', [TeacherResourceController::class, 'restore'])->name('resources.restore');
    Route::delete('/resources/{resource}/force-delete', [TeacherResourceController::class, 'forceDelete'])->name('resources.force-delete');

    // Reservations Management
    Route::get('/reservations', [TeacherReservationController::class, 'index'])->name('reservations.index');
    Route::get('/reservations/create', [TeacherReservationController::class, 'create'])->name('reservations.create');
    Route::post('/reservations', [TeacherReservationController::class, 'store'])->name('reservations.store');
    Route::get('/reservations/recycle-bin', [TeacherReservationController::class, 'recycleBin'])->name('reservations.recycle-bin');
    Route::get('/reservations/{reservation}', [TeacherReservationController::class, 'show'])->name('reservations.show');
    Route::get('/reservations/{reservation}/edit', [TeacherReservationController::class, 'edit'])->name('reservations.edit');
    Route::put('/reservations/{reservation}', [TeacherReservationController::class, 'update'])->name('reservations.update');
    Route::delete('/reservations/{reservation}', [TeacherReservationController::class, 'destroy'])->name('reservations.destroy');
    Route::post('/reservations/{reservation}/restore', [TeacherReservationController::class, 'restore'])->name('reservations.restore');
    Route::delete('/reservations/{reservation}/force-delete', [TeacherReservationController::class, 'forceDelete'])->name('reservations.force-delete');
    Route::post('/reservations/{reservation}/archive', [TeacherReservationController::class, 'archive'])->name('reservations.archive');
    Route::post('/reservations/{reservation}/reopen', [TeacherReservationController::class, 'reopen'])->name('reservations.reopen');
    Route::post('/reservations/{reservation}/items/{item}/deliver', [TeacherReservationController::class, 'markDelivered'])->name('reservations.items.deliver');
    Route::post('/reservations/{reservation}/items/{item}/undo-deliver', [TeacherReservationController::class, 'undoDelivery'])->name('reservations.items.undo-deliver');

    Route::get('/exams', [TeacherExamController::class, 'index'])->name('exams.index');
    Route::get('/exams/create', [TeacherExamController::class, 'create'])->name('exams.create');
    Route::post('/exams', [TeacherExamController::class, 'store'])->name('exams.store');
    Route::get('/exams/recycle-bin', [TeacherExamController::class, 'recycleBin'])->name('exams.recycle-bin');
    Route::get('/exams/{exam}', [TeacherExamController::class, 'questions'])->name('exams.questions');
    Route::post('/exams/{exam}/questions', [TeacherExamController::class, 'addQuestion'])->name('exams.questions.add');
    Route::delete('/exams/{exam}/questions/{question}', [TeacherExamController::class, 'deleteQuestion'])->name('exams.questions.delete');
    Route::get('/exams/{exam}/edit', [TeacherExamController::class, 'edit'])->name('exams.edit');
    Route::put('/exams/{exam}', [TeacherExamController::class, 'update'])->name('exams.update');
    Route::post('/exams/{exam}/duplicate', [TeacherExamController::class, 'duplicate'])->name('exams.duplicate');
    Route::delete('/exams/{exam}', [TeacherExamController::class, 'destroy'])->name('exams.destroy');
    Route::post('/exams/{exam}/restore', [TeacherExamController::class, 'restore'])->name('exams.restore')->withTrashed();
    Route::delete('/exams/{exam}/force-delete', [TeacherExamController::class, 'forceDelete'])->name('exams.force-delete')->withTrashed();
    Route::get('/exams/{exam}/attempts', [TeacherExamController::class, 'attempts'])->name('exams.attempts');
    Route::get('/exams/{exam}/time', [TeacherExamController::class, 'editTime'])->name('exams.time.edit');
    Route::put('/exams/{exam}/time', [TeacherExamController::class, 'updateTime'])->name('exams.time.update');
    Route::get('/exams/attempt/{attempt}', [TeacherExamController::class, 'showAttempt'])->name('exams.attempt.show');
    Route::post('/exams/attempt/{attempt}/reset', [TeacherExamController::class, 'resetAttempt'])->name('exams.attempt.reset');
    Route::post('/exams/{exam}/publish', [TeacherExamController::class, 'publish'])->name('exams.publish');
    Route::post('/exams/{exam}/close', [TeacherExamController::class, 'close'])->name('exams.close');
    Route::post('/exams/{exam}/archive', [TeacherExamController::class, 'archive'])->name('exams.archive');

    Route::get('/question-bank', [QuestionBankController::class, 'index'])->name('question-bank.index');
    Route::get('/question-bank/create', [QuestionBankController::class, 'create'])->name('question-bank.create');
    Route::post('/question-bank', [QuestionBankController::class, 'store'])->name('question-bank.store');
    Route::get('/question-bank/{question}/edit', [QuestionBankController::class, 'edit'])->name('question-bank.edit');
    Route::put('/question-bank/{question}', [QuestionBankController::class, 'update'])->name('question-bank.update');
    Route::delete('/question-bank/{question}', [QuestionBankController::class, 'destroy'])->name('question-bank.destroy');
    Route::get('/question-bank/recycle-bin', [QuestionBankController::class, 'recycleBin'])->name('question-bank.recycle-bin');
    Route::post('/question-bank/{question}/restore', [QuestionBankController::class, 'restore'])->name('question-bank.restore');
    Route::delete('/question-bank/{question}/force-delete', [QuestionBankController::class, 'forceDelete'])->name('question-bank.force-delete');

    Route::get('/exams/{exam}/questions/pick', [QuestionBankController::class, 'pickForExam'])->name('exams.questions.pick');
    Route::post('/exams/{exam}/questions/attach', [QuestionBankController::class, 'attachToExam'])->name('exams.questions.attach');

    Route::get('/students', [TeacherStudentController::class, 'index'])->name('students.index');
    // Sprint 66: bulk student import from CSV / Excel.
    // NOTE: registered BEFORE the {enrollment} wildcard so "import" is not captured as an enrollment id.
    Route::get('/students/import', [TeacherStudentImportController::class, 'create'])->name('students.import');
    Route::post('/students/import', [TeacherStudentImportController::class, 'store'])->name('students.import.store');
    Route::get('/students/{enrollment}', [StudentProfileController::class, 'show'])->name('students.show');
    Route::get('/students/{enrollment}/history', [TeacherStudentController::class, 'history'])->name('students.history');
    Route::post('/students/{enrollment}/move', [TeacherStudentController::class, 'move'])->name('students.move');
    Route::post('/students/bulk-move', [TeacherStudentController::class, 'bulkMove'])->name('students.bulk.move');
    Route::post('/students/bulk-remove', [TeacherStudentController::class, 'bulkRemove'])->name('students.bulk.remove');
    Route::post('/students/bulk-restore', [TeacherStudentController::class, 'bulkRestore'])->name('students.bulk.restore');
    Route::post('/students/{enrollment}/remove', [TeacherStudentController::class, 'remove'])->name('students.remove');
    Route::post('/students/{enrollment}/restore', [TeacherStudentController::class, 'restore'])->name('students.restore');
    Route::post('/students/{enrollment}/transfer/approve', [TeacherStudentController::class, 'approveTransfer'])->name('students.transfer.approve');
    Route::post('/students/{enrollment}/transfer/reject', [TeacherStudentController::class, 'rejectTransfer'])->name('students.transfer.reject');

    Route::get('/students/{enrollment}/card', [StudentCardController::class, 'teacherShow'])->name('students.card');
    Route::get('/groups/{group}/cards/print', [StudentCardController::class, 'printGroup'])->name('groups.cards.print');
    Route::post('/groups/{group}/students', [TeacherStudentController::class, 'store'])->name('groups.students.store');

    Route::get('/grades', [TeacherGradeController::class, 'index'])->name('grades.index');
    Route::get('/grades/{exam}', [TeacherGradeController::class, 'show'])->name('grades.show');
    // Sprint 66: export exam grades to CSV (teacher's own data only).
    Route::get('/grades/{exam}/export', [TeacherGradeController::class, 'export'])->name('grades.export');
    Route::put('/grades/attempt/{attempt}', [TeacherGradeController::class, 'update'])->name('grades.update');

    // Sprint 66: teacher reports (attendance / grades / activity summaries).
    Route::get('/reports', [TeacherReportController::class, 'index'])->name('reports.index');

    // Sprint 67: calendar, student progress tracking, settings.
    Route::get('/calendar', [TeacherCalendarController::class, 'index'])->name('calendar.index');
    Route::get('/progress', [TeacherProgressController::class, 'index'])->name('progress.index');
    Route::get('/settings', [TeacherSettingsController::class, 'index'])->name('settings.index');

    // Sprint 68: messaging (teacher ↔ his enrolled students only).
    Route::get('/messages', [TeacherMessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{student}', [TeacherMessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{student}', [TeacherMessageController::class, 'store'])->name('messages.store');

    // Sprint 68: announcements.
    Route::get('/announcements', [TeacherAnnouncementController::class, 'index'])->name('announcements.index');
    Route::get('/announcements/create', [TeacherAnnouncementController::class, 'create'])->name('announcements.create');
    Route::post('/announcements', [TeacherAnnouncementController::class, 'store'])->name('announcements.store');
    Route::get('/announcements/{announcement}/edit', [TeacherAnnouncementController::class, 'edit'])->name('announcements.edit');
    Route::put('/announcements/{announcement}', [TeacherAnnouncementController::class, 'update'])->name('announcements.update');
    Route::delete('/announcements/{announcement}', [TeacherAnnouncementController::class, 'destroy'])->name('announcements.destroy');

    // Sprint 68: two-factor authentication (email OTP).
    Route::post('/two-factor/enable', [TwoFactorController::class, 'enable'])->name('two-factor.enable');
    Route::post('/two-factor/disable', [TwoFactorController::class, 'disable'])->name('two-factor.disable');

    Route::get('/assistants', [TeacherAssistantController::class, 'index'])->name('assistants.index');
    Route::get('/assistants/create', [TeacherAssistantController::class, 'create'])->name('assistants.create');
    Route::post('/assistants', [TeacherAssistantController::class, 'store'])->name('assistants.store');
    Route::get('/assistants/{assistant}/edit', [TeacherAssistantController::class, 'edit'])->name('assistants.edit');
    Route::put('/assistants/{assistant}', [TeacherAssistantController::class, 'update'])->name('assistants.update');
    Route::post('/assistants/{assistant}/disable', [TeacherAssistantController::class, 'disable'])->name('assistants.disable');
    Route::post('/assistants/{assistant}/enable', [TeacherAssistantController::class, 'enable'])->name('assistants.enable');
    Route::delete('/assistants/{assistant}', [TeacherAssistantController::class, 'destroy'])->name('assistants.destroy');
});

// Assistant Routes
Route::middleware(['web', 'assistant'])->prefix('assistant')->name('assistant.')->group(function () {
    Route::get('/dashboard', [AssistantAuthController::class, 'dashboard'])->name('dashboard');

    Route::middleware(['assistant:can_take_attendance'])->group(function () {
        Route::get('/attendance', [AssistantController::class, 'attendanceIndex'])->name('attendance.index');
        Route::get('/attendance/create', [AssistantController::class, 'attendanceCreate'])->name('attendance.create');
        Route::post('/attendance', [AssistantController::class, 'attendanceStore'])->name('attendance.store');
        Route::get('/attendance/{session}', [AssistantController::class, 'attendanceShow'])->name('attendance.show');
        Route::post('/attendance/{session}/scan', [AssistantController::class, 'attendanceScan'])->name('attendance.scan');
        Route::post('/attendance/{session}/close', [AssistantController::class, 'attendanceClose'])->name('attendance.close');
        Route::post('/attendance/{session}/reopen', [AssistantController::class, 'attendanceReopen'])->name('attendance.reopen');
        Route::post('/attendance/{session}/record', [AssistantController::class, 'attendanceUpdateRecord'])->name('attendance.record.update');
    });

    Route::middleware(['assistant:can_manage_students'])->group(function () {
        Route::get('/students', [AssistantController::class, 'studentsIndex'])->name('students.index');
        Route::post('/students/{enrollment}/move', [AssistantController::class, 'studentMove'])->name('students.move');
    });

    Route::middleware(['assistant:can_restore_students'])->group(function () {
        Route::post('/students/{enrollment}/restore', [AssistantController::class, 'studentRestore'])->name('students.restore');
    });

    Route::middleware(['assistant:can_upload_resources'])->group(function () {
        Route::get('/resources', [AssistantController::class, 'resourcesIndex'])->name('resources.index');
        Route::get('/resources/create', [AssistantController::class, 'resourcesCreate'])->name('resources.create');
        Route::post('/resources', [AssistantController::class, 'resourcesStore'])->name('resources.store');
        Route::get('/resources/{resource}/edit', [AssistantController::class, 'resourcesEdit'])->name('resources.edit');
        Route::put('/resources/{resource}', [AssistantController::class, 'resourcesUpdate'])->name('resources.update');
        Route::post('/resources/{resource}/duplicate', [AssistantController::class, 'resourcesDuplicate'])->name('resources.duplicate');
        Route::post('/resources/{resource}/pin', [AssistantController::class, 'resourcesTogglePin'])->name('resources.pin');
        Route::delete('/resources/{resource}', [AssistantController::class, 'resourcesDestroy'])->name('resources.destroy');
        Route::get('/resources/recycle-bin', [AssistantController::class, 'resourcesRecycleBin'])->name('resources.recycle-bin');
        Route::post('/resources/{resource}/restore', [AssistantController::class, 'resourcesRestore'])->name('resources.restore');
        Route::delete('/resources/{resource}/force-delete', [AssistantController::class, 'resourcesForceDelete'])->name('resources.force-delete');
        Route::get('/resources/{resource}/download', [AssistantController::class, 'resourcesDownload'])->name('resources.download');
    });

    Route::middleware(['assistant:can_manage_lessons'])->group(function () {
        Route::get('/lessons', [AssistantController::class, 'lessonsIndex'])->name('lessons.index');
        Route::get('/lessons/create', [AssistantController::class, 'lessonsCreate'])->name('lessons.create');
        Route::post('/lessons', [AssistantController::class, 'lessonsStore'])->name('lessons.store');
        Route::get('/lessons/recycle-bin', [AssistantController::class, 'lessonsRecycleBin'])->name('lessons.recycle-bin');
        Route::post('/lessons/{lesson}/move-up', [AssistantController::class, 'lessonsMoveUp'])->name('lessons.move-up');
        Route::post('/lessons/{lesson}/move-down', [AssistantController::class, 'lessonsMoveDown'])->name('lessons.move-down');
        Route::get('/lessons/{lesson}/edit', [AssistantController::class, 'lessonsEdit'])->name('lessons.edit');
        Route::put('/lessons/{lesson}', [AssistantController::class, 'lessonsUpdate'])->name('lessons.update');
        Route::delete('/lessons/{lesson}', [AssistantController::class, 'lessonsDestroy'])->name('lessons.destroy');
        Route::post('/lessons/{lesson}/restore', [AssistantController::class, 'lessonsRestore'])->name('lessons.restore');
        Route::delete('/lessons/{lesson}/force-delete', [AssistantController::class, 'lessonsForceDelete'])->name('lessons.force-delete');
    });

    Route::middleware(['assistant:can_manage_reservations'])->group(function () {
        Route::get('/reservations', [AssistantController::class, 'reservationsIndex'])->name('reservations.index');
        Route::get('/reservations/create', [AssistantController::class, 'reservationsCreate'])->name('reservations.create');
        Route::post('/reservations', [AssistantController::class, 'reservationsStore'])->name('reservations.store');
        Route::get('/reservations/{reservation}', [AssistantController::class, 'reservationsShow'])->name('reservations.show');
        Route::get('/reservations/{reservation}/edit', [AssistantController::class, 'reservationsEdit'])->name('reservations.edit');
        Route::put('/reservations/{reservation}', [AssistantController::class, 'reservationsUpdate'])->name('reservations.update');
        Route::post('/reservations/{reservation}/cancel', [AssistantController::class, 'reservationsCancel'])->name('reservations.cancel');
        Route::post('/reservations/{reservation}/items/{item}/deliver', [AssistantController::class, 'reservationsMarkDelivered'])->name('reservations.items.deliver');
        Route::post('/reservations/{reservation}/items/{item}/undo-deliver', [AssistantController::class, 'reservationsUndoDelivery'])->name('reservations.items.undo-deliver');
    });

    Route::middleware(['assistant:can_manage_exams'])->group(function () {
        Route::get('/exams', [AssistantController::class, 'examsIndex'])->name('exams.index');
        Route::get('/exams/create', [AssistantController::class, 'examsCreate'])->name('exams.create');
        Route::post('/exams', [AssistantController::class, 'examsStore'])->name('exams.store');
        Route::get('/exams/recycle-bin', [AssistantController::class, 'examsRecycleBin'])->name('exams.recycle-bin');
        Route::get('/exams/{exam}/questions', [AssistantController::class, 'examsQuestions'])->name('exams.questions');
        Route::post('/exams/{exam}/questions', [AssistantController::class, 'examsAddQuestion'])->name('exams.questions.add');
        Route::delete('/exams/{exam}/questions/{question}', [AssistantController::class, 'examsDeleteQuestion'])->name('exams.questions.delete');
        Route::get('/exams/{exam}/edit', [AssistantController::class, 'examsEdit'])->name('exams.edit');
        Route::put('/exams/{exam}', [AssistantController::class, 'examsUpdate'])->name('exams.update');
        Route::delete('/exams/{exam}', [AssistantController::class, 'examsDestroy'])->name('exams.destroy');
        Route::post('/exams/{exam}/restore', [AssistantController::class, 'examsRestore'])->name('exams.restore')->withTrashed();
        Route::delete('/exams/{exam}/force-delete', [AssistantController::class, 'examsForceDelete'])->name('exams.force-delete')->withTrashed();
        Route::get('/exams/{exam}/questions/pick', [AssistantController::class, 'examsPickFromBank'])->name('exams.questions.pick');
        Route::post('/exams/{exam}/questions/attach', [AssistantController::class, 'examsAttachFromBank'])->name('exams.questions.attach');
        Route::get('/question-bank', [AssistantController::class, 'questionBankIndex'])->name('question-bank.index');
        Route::get('/question-bank/create', [AssistantController::class, 'questionBankCreate'])->name('question-bank.create');
        Route::post('/question-bank', [AssistantController::class, 'questionBankStore'])->name('question-bank.store');
        Route::get('/question-bank/{question}/edit', [AssistantController::class, 'questionBankEdit'])->name('question-bank.edit');
        Route::put('/question-bank/{question}', [AssistantController::class, 'questionBankUpdate'])->name('question-bank.update');
        Route::delete('/question-bank/{question}', [AssistantController::class, 'questionBankDestroy'])->name('question-bank.destroy');
        Route::get('/question-bank/recycle-bin', [AssistantController::class, 'questionBankRecycleBin'])->name('question-bank.recycle-bin');
        Route::post('/question-bank/{question}/restore', [AssistantController::class, 'questionBankRestore'])->name('question-bank.restore');
        Route::delete('/question-bank/{question}/force-delete', [AssistantController::class, 'questionBankForceDelete'])->name('question-bank.force-delete');
        Route::post('/exams/{exam}/publish', [AssistantController::class, 'examsPublish'])->name('exams.publish');
        Route::post('/exams/{exam}/close', [AssistantController::class, 'examsClose'])->name('exams.close');
        Route::post('/exams/{exam}/archive', [AssistantController::class, 'examsArchive'])->name('exams.archive');
    });

    Route::middleware(['assistant:can_view_groups'])->group(function () {
        Route::get('/groups', [AssistantController::class, 'groupsIndex'])->name('groups.index');
    });

    Route::middleware(['assistant:can_manage_grades'])->group(function () {
        Route::get('/grades', [AssistantController::class, 'gradesIndex'])->name('grades.index');
        Route::get('/grades/{exam}', [AssistantController::class, 'gradesShow'])->name('grades.show');
        Route::put('/grades/attempt/{attempt}', [AssistantController::class, 'gradesUpdate'])->name('grades.update');
    });

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read.all');

    Route::middleware(['assistant'])->group(function () {
        Route::get('/profile', [AssistantController::class, 'profileEdit'])->name('profile.edit');
        Route::patch('/profile', [AssistantController::class, 'profileUpdate'])->name('profile.update');
        Route::post('/password', [AssistantController::class, 'passwordUpdate'])->name('password.update');
    });
});

// Student Routes
Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentResourceController::class, 'home'])->name('dashboard');
    Route::get('/resources', [StudentResourceController::class, 'home'])->name('resources.home');
    Route::get('/resources/{resource}', [StudentResourceController::class, 'show'])->name('resources.show');
    // Sprint 63: direct download without visiting the details page.
    Route::get('/resources/{resource}/download', [StudentResourceController::class, 'download'])->name('resources.download');
    Route::get('/groups', [StudentGroupController::class, 'index'])->name('groups.index');
    Route::get('/groups/{group}/transfer', [StudentTransferController::class, 'create'])->name('transfer.create');
    Route::post('/groups/{group}/transfer', [StudentTransferController::class, 'store'])->name('transfer.store');

    Route::get('/reservations', [StudentReservationController::class, 'index'])->name('reservations.index');
    Route::get('/reservations/history', [StudentReservationController::class, 'history'])->name('reservations.history');
    Route::get('/reservations/{reservation}', [StudentReservationController::class, 'show'])->name('reservations.show');
    Route::post('/reservations/{reservation}/reserve', [StudentReservationController::class, 'reserve'])->name('reservations.reserve');
    // Sprint 63: student cancels his own reservation item.
    Route::post('/reservations/item/{item}/cancel', [StudentReservationController::class, 'cancel'])->name('reservations.cancel');

    Route::get('/exams', [StudentExamController::class, 'index'])->name('exams.index');
    Route::post('/exams/{exam}/start', [StudentExamController::class, 'start'])->name('exams.start');
    Route::get('/exams/attempt/{attempt}', [StudentExamController::class, 'take'])->name('exams.take');
    Route::post('/exams/attempt/{attempt}/submit', [StudentExamController::class, 'submit'])->name('exams.submit');
    Route::get('/exams/attempt/{attempt}/result', [StudentExamController::class, 'result'])->name('exams.result');
    Route::get('/exams/attempt/{attempt}/review', [StudentExamController::class, 'review'])->name('exams.review');
    Route::post('/exams/attempt/{attempt}/leave', [StudentExamController::class, 'leave'])->name('exams.leave');
    Route::post('/exams/attempt/{attempt}/auto-save', [StudentExamController::class, 'autoSave'])->name('exams.autoSave');

    Route::get('/accounts', [StudentAccountController::class, 'selection'])->name('accounts');
    Route::post('/accounts/switch', [StudentAccountController::class, 'switch'])->name('accounts.switch');
    Route::get('/account', [StudentAccountController::class, 'show'])->name('account');
    Route::get('/report', [StudentAccountController::class, 'report'])->name('report');
    Route::get('/card', [StudentCardController::class, 'studentShow'])->name('card');

    // Sprint 65: separate grades / attendance / settings pages + mobile change.
    Route::get('/grades', [StudentAccountController::class, 'grades'])->name('grades');
    Route::get('/attendance', [StudentAccountController::class, 'attendance'])->name('attendance');
    Route::get('/settings', [StudentAccountController::class, 'settings'])->name('settings');
    Route::patch('/settings/mobile', [StudentAccountController::class, 'updateMobile'])->name('settings.mobile');

    // Sprint 68: messaging with his teachers only.
    Route::get('/messages', [StudentMessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{teacher}', [StudentMessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{teacher}', [StudentMessageController::class, 'store'])->name('messages.store');
});

// Sprint 68: Parent Access — dedicated guard, read-only.
Route::middleware('guest:parent')->group(function () {
    Route::get('/parent/login', [ParentAuthController::class, 'showLogin'])->name('parent.login');
    Route::post('/parent/login', [ParentAuthController::class, 'login']);
    Route::get('/parent/register', [ParentAuthController::class, 'showRegister'])->name('parent.register');
    Route::post('/parent/register', [ParentAuthController::class, 'register']);
});

Route::middleware(['web', 'auth:parent'])->prefix('parent')->name('parent.')->group(function () {
    Route::get('/dashboard', [ParentDashboardController::class, 'index'])->name('dashboard');
    Route::get('/students/{student}', [ParentDashboardController::class, 'showStudent'])->name('student.show');
    Route::post('/logout', [ParentAuthController::class, 'logout'])->name('logout');
});

// Sprint 68: 2FA challenge (between password check and full login).
Route::middleware('guest')->group(function () {
    Route::get('/two-factor-challenge', [TwoFactorController::class, 'showChallenge'])->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [TwoFactorController::class, 'verifyChallenge']);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read.all');
});

Route::get('auth/entry', [App\Http\Controllers\Auth\EntryController::class, 'create'])->name('auth.entry')->middleware('guest');

require __DIR__.'/auth.php';
