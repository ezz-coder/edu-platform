<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminGroupController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\StudentGroupController;
use App\Http\Controllers\TeacherDashboardController;
use App\Http\Controllers\TeacherGroupController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Admin Routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::post('/users/{user}/approve', [AdminUserController::class, 'approve'])->name('users.approve');
    Route::post('/users/{user}/reject', [AdminUserController::class, 'reject'])->name('users.reject');
    Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend'])->name('users.suspend');
    Route::get('/groups', [AdminGroupController::class, 'index'])->name('groups.index');
    Route::post('/groups/{group}/toggle', [AdminGroupController::class, 'toggleStatus'])->name('groups.toggle');
});

// Teacher Routes
Route::middleware(['auth', 'role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('dashboard');
    Route::get('/groups', [TeacherGroupController::class, 'index'])->name('groups.index');
    Route::get('/groups/create', [TeacherGroupController::class, 'create'])->name('groups.create');
    Route::post('/groups', [TeacherGroupController::class, 'store'])->name('groups.store');
    Route::get('/groups/{group}/edit', [TeacherGroupController::class, 'edit'])->name('groups.edit');
    Route::put('/groups/{group}', [TeacherGroupController::class, 'update'])->name('groups.update');
    Route::delete('/groups/{group}', [TeacherGroupController::class, 'destroy'])->name('groups.destroy');
});

// Student Routes
Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
    Route::get('/groups', [StudentGroupController::class, 'index'])->name('groups.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
