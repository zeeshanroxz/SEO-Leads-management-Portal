<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', DashboardController::class)->name('dashboard');

Route::middleware('perm:leads.view')->group(function () {
    Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
    Route::get('/leads/{lead}', [LeadController::class, 'show'])->name('leads.show')->where('lead', 'L-[0-9]+');
    Route::post('/leads/{lead}/activity', [LeadController::class, 'activity'])->name('leads.activity');
    Route::post('/leads/{lead}/email', [LeadController::class, 'email'])->name('leads.email')->where('lead', 'L-[0-9]+');
});

Route::middleware('perm:leads.create')->group(function () {
    Route::get('/leads/create', [LeadController::class, 'create'])->name('leads.create');
    Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');
    Route::post('/leads/duplicates', [LeadController::class, 'duplicates'])->name('leads.duplicates');
    Route::get('/leads/{lead}/edit', [LeadController::class, 'edit'])->name('leads.edit')->where('lead', 'L-[0-9]+');
    Route::put('/leads/{lead}', [LeadController::class, 'update'])->name('leads.update')->where('lead', 'L-[0-9]+');
    Route::post('/leads/{lead}/submit', [LeadController::class, 'submit'])->name('leads.submit')->where('lead', 'L-[0-9]+');
});

Route::middleware('perm:leads.review,leads.review_own')->group(function () {
    Route::get('/review-queue', [LeadController::class, 'review'])->name('review.index');
    Route::post('/leads/{lead}/accept', [LeadController::class, 'accept'])->name('leads.accept');
    Route::post('/leads/{lead}/reject', [LeadController::class, 'reject'])->name('leads.reject');
});

Route::middleware('perm:leads.sales_edit')->post('/leads/{lead}/status', [LeadController::class, 'status'])->name('leads.status');
Route::middleware('perm:admin.visibility')->group(function () {
    Route::post('/leads/{lead}/visibility', [LeadController::class, 'visibility'])->name('leads.visibility');
    Route::post('/leads/{lead}/reopen', [LeadController::class, 'reopen'])->name('leads.reopen');
});

Route::middleware('perm:customers.view')->group(function () {
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::post('/customers/{customer}/contacts', [CustomerController::class, 'addContact'])->name('customers.contacts.store');
});
