<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Phase 1 - frontend prototype routes
|--------------------------------------------------------------------------
| Every route renders mock data. Route-level `perm:` middleware only simulates
| what each previewed role would see. It is NOT authorisation.
*/

Route::get('/', fn () => redirect()->route('dashboard'));
Route::get('/login', [AuthController::class, 'login'])->name('login');

Route::post('/demo/role', [DemoController::class, 'role'])->name('demo.role');
Route::post('/demo/finance-manager', [DemoController::class, 'financeManager'])->name('demo.finance-manager');
Route::post('/demo/reset', [DemoController::class, 'reset'])->name('demo.reset');

Route::get('/search', SearchController::class)->name('search');

Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
Route::post('/notifications/read', [NotificationController::class, 'read'])->name('notifications.read');
Route::get('/notifications/settings', [NotificationController::class, 'settings'])->name('notifications.settings');
Route::post('/notifications/settings', [NotificationController::class, 'saveSettings'])->name('notifications.settings.save');

require __DIR__.'/web_crm.php';
require __DIR__.'/web_admin.php';
require __DIR__.'/web_work.php';
require __DIR__.'/web_finance.php';
