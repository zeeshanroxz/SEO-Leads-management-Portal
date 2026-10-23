<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CalendarIntegrationController;
use App\Http\Controllers\LearningController;
use Illuminate\Support\Facades\Route;

Route::middleware('perm:audit.view')->group(function () {
    Route::get('/audits', [AuditController::class, 'index'])->name('audits.index');
    Route::get('/audits/{audit}', [AuditController::class, 'show'])->name('audits.show')->where('audit', 'A-[0-9]+');
    Route::get('/audits/{audit}/preview', [AuditController::class, 'preview'])->name('audits.preview')->where('audit', 'A-[0-9]+');
    Route::post('/audits/{audit}/send', [AuditController::class, 'send'])->name('audits.send')->where('audit', 'A-[0-9]+');
    Route::post('/audits/{audit}/status', [AuditController::class, 'status'])->name('audits.status')->where('audit', 'A-[0-9]+');
});
Route::middleware('perm:audit.request')->post('/audits', [AuditController::class, 'store'])->name('audits.store');
Route::middleware('perm:audit.author')->group(function () {
    Route::post('/audits/{audit}/version', [AuditController::class, 'version'])->name('audits.version')->where('audit', 'A-[0-9]+');
});
Route::middleware('perm:admin.users')->post('/audits/{audit}/assign', [AuditController::class, 'assign'])->name('audits.assign')->where('audit', 'A-[0-9]+');

Route::middleware('perm:calendar.use')->group(function () {
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::post('/calendar/events', [CalendarController::class, 'store'])->name('calendar.store');
    Route::put('/calendar/events/{event}', [CalendarController::class, 'update'])->name('calendar.update');
    Route::post('/calendar/events/{event}/cancel', [CalendarController::class, 'cancel'])->name('calendar.cancel');
    Route::get('/settings/calendar-integrations', [CalendarIntegrationController::class, 'index'])->name('integrations.calendar');
    Route::post('/settings/calendar-integrations', [CalendarIntegrationController::class, 'update'])->name('integrations.calendar.update');
});

Route::middleware('perm:learning.view')->get('/learning', [LearningController::class, 'index'])->name('learning.index');
Route::middleware('perm:admin.settings')->post('/learning', [LearningController::class, 'store'])->name('learning.store');
