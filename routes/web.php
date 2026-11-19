<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\SignInController;
use App\Http\Controllers\GateController;
use App\Http\Controllers\Portal;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

// The code page is the only public page. Every other URL answers 404 until a valid code has been entered.
Route::get('/', [GateController::class, 'show'])->name('gate');
Route::post('/enter', [GateController::class, 'enter'])->middleware('throttle:gate')->name('gate.enter');

Route::middleware('gate')->group(function () {
    Route::get('/sign-in', [SignInController::class, 'show'])->name('signin');
    Route::post('/sign-in', [SignInController::class, 'signIn'])->middleware('throttle:signin')->name('signin.post');
    Route::post('/sign-in/claim', [SignInController::class, 'claim'])->middleware('throttle:signin')->name('signin.claim');
    Route::post('/sign-in/code', [SignInController::class, 'code'])->middleware('throttle:signin')->name('signin.code');
    Route::post('/sign-in/code/resend', [SignInController::class, 'resend'])->middleware('throttle:signin')->name('signin.resend');
    Route::post('/leave', [GateController::class, 'leave'])->name('gate.leave');
});

Route::middleware(['auth', 'throttle:portal'])->group(function () {
    Route::get('/dashboard', [Portal\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/reserve', [Portal\DashboardController::class, 'agreement'])->name('reserve');
    Route::post('/reserve', [Portal\DashboardController::class, 'reserve'])->middleware('throttle:6,1')->name('reserve.store');
    Route::get('/invoices/{invoice}/pdf', [Portal\DashboardController::class, 'invoicePdf'])->name('invoice.pdf');
    Route::get('/vehicle', [VehicleController::class, 'index'])->name('vehicle');
    Route::get('/vehicle/{slug}', [VehicleController::class, 'show'])->where('slug', '[a-z-]+')->name('vehicle.show');
    Route::get('/vehicle/{slug}/video', [MediaController::class, 'video'])->where('slug', '[a-z-]+')->name('vehicle.video');
    Route::get('/media/{filename}', [MediaController::class, 'show'])->where('filename', '[A-Za-z0-9]{40}\.(png|jpg|webp)')->name('media.show');
    Route::post('/sign-out', [SignInController::class, 'logout'])->name('logout');
    Route::post('/admin/exit-member', [Admin\MemberController::class, 'exit'])->name('admin.exit');
});

Route::middleware(['auth', 'staff'])->prefix('admin')->group(function () {
    Route::view('/', 'admin.home')->name('admin.home');

    Route::middleware('can:members.manage')->group(function () {
        Route::get('/invites', [Admin\InviteController::class, 'index'])->name('admin.invites');
        Route::post('/invites', [Admin\InviteController::class, 'store'])->name('admin.invites.store');
        Route::get('/invites/export', [Admin\InviteController::class, 'export'])->name('admin.invites.export');
        Route::post('/invites/{invite}/regenerate', [Admin\InviteController::class, 'regenerate'])->name('admin.invites.regenerate');
        Route::post('/invites/{invite}/revoke', [Admin\InviteController::class, 'revoke'])->name('admin.invites.revoke');
        Route::get('/members', [Admin\MemberController::class, 'index'])->name('admin.members');
        Route::get('/funnel', Admin\FunnelController::class)->name('admin.funnel');
        Route::get('/reservations', [Admin\ReservationController::class, 'index'])->name('admin.reservations');
    });
    Route::post('/members/{user}/impersonate', [Admin\MemberController::class, 'impersonate'])->middleware('can:impersonate')->name('admin.impersonate');
    Route::middleware('can:content.edit')->group(function () {
        Route::get('/content', [Admin\ContentController::class, 'index'])->name('admin.content');
        Route::get('/content/{section:slug}', [Admin\ContentController::class, 'edit'])->name('admin.content.edit');
        Route::post('/content/{section:slug}', [Admin\ContentController::class, 'update'])->name('admin.content.update');
    });
    Route::middleware('can:admin.manage')->group(function () {
        Route::get('/agreement', [Admin\AgreementController::class, 'edit'])->name('admin.agreement');
        Route::post('/agreement', [Admin\AgreementController::class, 'publish'])->name('admin.agreement.publish');
        Route::get('/settings', [Admin\SettingsController::class, 'edit'])->name('admin.settings');
        Route::post('/settings', [Admin\SettingsController::class, 'update'])->name('admin.settings.save');
    });
});
