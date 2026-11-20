<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth;
use App\Http\Controllers\Auth\SignInController;
use App\Http\Controllers\GateController;
use App\Http\Controllers\Portal;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

// The code page is the only public page. Every other URL answers 404 until a valid code has been entered.
Route::get('/', [GateController::class, 'show'])->name('gate');
Route::post('/enter', [GateController::class, 'enter'])->middleware('throttle:gate')->name('gate.enter');
// Uploaded fonts must load on the code page too; the file names are long random strings and anything else is a 404.
Route::get('/fonts/{filename}', [MediaController::class, 'font'])->where('filename', '[A-Za-z0-9]{40}\.woff2')->name('font');
Route::get('/brand/{filename}', [MediaController::class, 'logo'])->where('filename', '[A-Za-z0-9]{40}\.(png|jpg|webp)')->name('logo');

Route::middleware('gate')->group(function () {
    Route::get('/sign-in', [SignInController::class, 'show'])->name('signin');
    Route::post('/sign-in', [SignInController::class, 'signIn'])->middleware('throttle:signin')->name('signin.post');
    Route::post('/sign-in/claim', [SignInController::class, 'claim'])->middleware('throttle:signin')->name('signin.claim');
    Route::post('/sign-in/code', [SignInController::class, 'code'])->middleware('throttle:signin')->name('signin.code');
    Route::post('/sign-in/code/resend', [SignInController::class, 'resend'])->middleware('throttle:signin')->name('signin.resend');
    Route::get('/sign-in/reset', [Auth\ResetController::class, 'show'])->name('reset');
    Route::post('/sign-in/reset/send', [Auth\ResetController::class, 'send'])->middleware('throttle:signin')->name('reset.send');
    Route::post('/sign-in/reset', [Auth\ResetController::class, 'update'])->middleware('throttle:signin')->name('reset.update');
    Route::post('/leave', [GateController::class, 'leave'])->name('gate.leave');
});

Route::middleware(['auth', 'throttle:portal'])->group(function () {
    Route::get('/dashboard', [Portal\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/reserve', [Portal\DashboardController::class, 'agreement'])->name('reserve');
    Route::post('/reserve', [Portal\DashboardController::class, 'reserve'])->middleware('throttle:6,1')->name('reserve.store');
    Route::get('/invoices/{invoice}/pdf', [Portal\DashboardController::class, 'invoicePdf'])->name('invoice.pdf');
    Route::post('/invoices/{invoice}/proof', [Portal\DashboardController::class, 'proof'])->middleware('throttle:10,1')->name('invoice.proof');
    Route::get('/payments/{payment}/proof', [Portal\DashboardController::class, 'proofFile'])->name('payment.proof');
    Route::get('/welcome', [VehicleController::class, 'welcome'])->name('welcome');
    Route::post('/welcome', [VehicleController::class, 'welcomed'])->name('welcome.done');
    Route::get('/vehicle', [VehicleController::class, 'index'])->name('vehicle');
    Route::get('/vehicle/{slug}', [VehicleController::class, 'show'])->where('slug', '[a-z-]+')->name('vehicle.show');
    Route::get('/vehicle/{slug}/video', [MediaController::class, 'video'])->where('slug', '[a-z-]+')->name('vehicle.video');
    Route::get('/member.css', [MediaController::class, 'memberCss'])->name('member.css');
    Route::get('/vehicle-art/{name}', [MediaController::class, 'art'])->where('name', '[a-z]+-default\.(png|jpg)')->name('vehicle.art');
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
    Route::middleware('can:payments.verify')->group(function () {
        Route::get('/reservations/{reservation}', [Admin\ReservationController::class, 'show'])->name('admin.reservation');
        Route::post('/payments/{payment}/verify', [Admin\ReservationController::class, 'verify'])->name('admin.payment.verify');
        Route::post('/payments/{payment}/reject', [Admin\ReservationController::class, 'reject'])->name('admin.payment.reject');
        Route::post('/reservations/{reservation}/extend', [Admin\ReservationController::class, 'extend'])->name('admin.reservation.extend');
        Route::post('/reservations/{reservation}/milestone', [Admin\ReservationController::class, 'milestone'])->name('admin.reservation.milestone');
        Route::post('/reservations/{reservation}/release', [Admin\ReservationController::class, 'release'])->name('admin.reservation.release');
        Route::post('/reservations/{reservation}/update', [Admin\ReservationController::class, 'update'])->name('admin.reservation.update');
        Route::post('/reservations/{reservation}/cancel', [Admin\ReservationController::class, 'cancel'])->name('admin.reservation.cancel');
        Route::post('/invoices/{invoice}/resend', [Admin\ReservationController::class, 'resend'])->name('admin.invoice.resend');
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
        Route::get('/system', [Admin\SystemController::class, 'show'])->name('admin.system');
        Route::post('/system/check', [Admin\SystemController::class, 'check'])->middleware('throttle:10,1')->name('admin.system.check');
        Route::post('/system/update', [Admin\SystemController::class, 'install'])->middleware('throttle:5,1')->name('admin.system.update');
        Route::get('/system/progress', [Admin\SystemController::class, 'progress'])->name('admin.system.progress');
        Route::post('/system/restore', [Admin\SystemController::class, 'restore'])->middleware('throttle:5,1')->name('admin.system.restore');
        Route::post('/system/cancel', [Admin\SystemController::class, 'cancel'])->name('admin.system.cancel');
        Route::get('/settings', [Admin\SettingsController::class, 'edit'])->name('admin.settings');
        Route::post('/settings', [Admin\SettingsController::class, 'update'])->name('admin.settings.save');
        Route::post('/settings/logo/{role}', [Admin\SettingsController::class, 'uploadLogo'])->where('role', 'site|vehicle|backdrop')->middleware('throttle:20,1')->name('admin.logo.upload');
        Route::delete('/settings/logo/{role}', [Admin\SettingsController::class, 'removeLogo'])->where('role', 'site|vehicle|backdrop')->name('admin.logo.remove');
        Route::post('/settings/fonts', [Admin\SettingsController::class, 'uploadFont'])->middleware('throttle:20,1')->name('admin.fonts.upload');
        Route::post('/settings/fonts/assign', [Admin\SettingsController::class, 'assignFonts'])->name('admin.fonts.assign');
        Route::delete('/settings/fonts/{font}', [Admin\SettingsController::class, 'deleteFont'])->name('admin.fonts.delete');
    });
});
