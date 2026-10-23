<?php

use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\FormBuilderController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VisibilityController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    Route::middleware('perm:admin.users')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('admin.users');
        Route::post('/users', [UserController::class, 'store'])->name('admin.users.store');
        Route::post('/users/{user}/toggle', [UserController::class, 'toggle'])->name('admin.users.toggle');
        Route::post('/users/{user}/role', [UserController::class, 'role'])->name('admin.users.role');
    });

    Route::middleware('perm:admin.forms')->group(function () {
        Route::get('/forms', [FormBuilderController::class, 'index'])->name('forms.index');
        Route::get('/forms/{form}', [FormBuilderController::class, 'show'])->name('forms.show');
        Route::post('/forms/{form}/draft', [FormBuilderController::class, 'draft'])->name('forms.draft');
        Route::post('/forms/{form}/publish', [FormBuilderController::class, 'publish'])->name('forms.publish');
        Route::post('/forms/{form}/discard', [FormBuilderController::class, 'discard'])->name('forms.discard');
        Route::post('/fixed-fields', [FormBuilderController::class, 'fixed'])->name('forms.fixed');
    });

    Route::middleware('perm:admin.projects')->group(function () {
        Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::post('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
        Route::post('/statuses', [ProjectController::class, 'statuses'])->name('statuses.update');
    });

    Route::middleware('perm:admin.visibility')->group(function () {
        Route::get('/visibility', [VisibilityController::class, 'index'])->name('admin.visibility');
        Route::post('/visibility', [VisibilityController::class, 'store'])->name('admin.visibility.store');
        Route::post('/visibility/{override}/revoke', [VisibilityController::class, 'revoke'])->name('admin.visibility.revoke');
    });

    Route::middleware('perm:admin.audit,activity.view')->group(function () {
        Route::get('/activity', [ActivityController::class, 'index'])->name('admin.activity');
    });
    Route::middleware('perm:admin.audit,finance.square')->post('/errors/{error}', [ActivityController::class, 'update'])->name('admin.errors.update');

    Route::middleware('perm:admin.settings')->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('admin.settings');
        Route::post('/settings', [SettingsController::class, 'save'])->name('admin.settings.save');
    });
});
