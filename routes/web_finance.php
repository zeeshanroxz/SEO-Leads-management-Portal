<?php

use App\Http\Controllers\AgreementController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CommissionController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\RevenueController;
use App\Http\Controllers\SigningController;
use Illuminate\Support\Facades\Route;

// Public customer signing page MOCKUP (no real secure link / token in Phase 1).
Route::get('/sign/{agreement}', [SigningController::class, 'show'])->name('sign.show')->where('agreement', 'AG-[0-9]+');
Route::post('/sign/{agreement}', [SigningController::class, 'complete'])->name('sign.complete')->where('agreement', 'AG-[0-9]+');

Route::middleware('perm:finance.handoff')->group(function () {
    Route::get('/finance/handoff', [FinanceController::class, 'handoff'])->name('finance.handoff');
    Route::post('/finance/handoff/{lead}/claim', [FinanceController::class, 'claim'])->name('finance.handoff.claim');
});
Route::middleware('perm:finance.dashboard')->get('/finance/dashboard', [FinanceController::class, 'dashboard'])->name('finance.dashboard');

Route::middleware('perm:agreements.view')->group(function () {
    Route::get('/agreements', [AgreementController::class, 'index'])->name('agreements.index');
    Route::get('/agreements/{agreement}', [AgreementController::class, 'show'])->name('agreements.show')->where('agreement', 'AG-[0-9]+');
});
Route::middleware('perm:agreements.manage')->group(function () {
    Route::get('/agreements/create', [AgreementController::class, 'create'])->name('agreements.create');
    Route::post('/agreements', [AgreementController::class, 'store'])->name('agreements.store');
    Route::post('/agreements/{agreement}/status', [AgreementController::class, 'status'])->name('agreements.status')->where('agreement', 'AG-[0-9]+');
    Route::post('/agreements/{agreement}/offline', [AgreementController::class, 'offline'])->name('agreements.offline')->where('agreement', 'AG-[0-9]+');
    Route::post('/agreements/{agreement}/offline-review', [AgreementController::class, 'offlineReview'])->name('agreements.offline.review')->where('agreement', 'AG-[0-9]+');
    Route::post('/clauses/{clause}', [AgreementController::class, 'clause'])->name('clauses.update');
});

Route::middleware('perm:finance.square')->group(function () {
    Route::get('/finance/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('/finance/billing/map/{record}', [BillingController::class, 'map'])->name('billing.map');
    Route::post('/finance/billing/subscription/{subscription}', [BillingController::class, 'subscription'])->name('billing.subscription');
    Route::post('/finance/billing/webhooks/{webhook}/retry', [BillingController::class, 'retry'])->name('billing.retry');
    Route::post('/finance/billing/webhooks/simulate', [BillingController::class, 'simulate'])->name('billing.simulate');
    Route::post('/finance/billing/reconcile/{record}', [BillingController::class, 'reconcile'])->name('billing.reconcile');
});
Route::middleware('perm:admin.integrations')->group(function () {
    Route::get('/integrations/square', [BillingController::class, 'settings'])->name('integrations.square');
    Route::post('/integrations/square', [BillingController::class, 'saveSettings'])->name('integrations.square.save');
    Route::post('/integrations/square/test', [BillingController::class, 'test'])->name('integrations.square.test');
});

Route::middleware('perm:commission.view')->group(function () {
    Route::get('/commissions', [CommissionController::class, 'index'])->name('commissions.index');
    Route::get('/commissions/{commission}', [CommissionController::class, 'show'])->name('commissions.show')->where('commission', 'CM-[0-9]+');
});
Route::middleware('perm:commission.create')->post('/commissions', [CommissionController::class, 'store'])->name('commissions.store');
Route::middleware('perm:commission.approve')->group(function () {
    Route::post('/commissions/{commission}/transition', [CommissionController::class, 'transition'])->name('commissions.transition')->where('commission', 'CM-[0-9]+');
    Route::post('/commissions/{commission}/adjust', [CommissionController::class, 'adjust'])->name('commissions.adjust')->where('commission', 'CM-[0-9]+');
    Route::post('/commissions/recalculate', [CommissionController::class, 'recalculate'])->name('commissions.recalculate');
});
Route::middleware('perm:commission.manage_rules')->group(function () {
    Route::get('/commissions-rules', [CommissionController::class, 'rules'])->name('commissions.rules');
    Route::post('/commissions-rules/{rule}', [CommissionController::class, 'updateRule'])->name('commissions.rules.update');
    Route::post('/commissions-settings', [CommissionController::class, 'settings'])->name('commissions.settings');
});

Route::middleware('perm:revenue.mrr')->get('/revenue', [RevenueController::class, 'index'])->name('revenue.index');
