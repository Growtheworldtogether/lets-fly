<?php

use App\Http\Controllers\Auth\SignInController;
use App\Http\Controllers\GateController;
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
    Route::view('/dashboard', 'member.dashboard')->name('dashboard');
    Route::post('/sign-out', [SignInController::class, 'logout'])->name('logout');
});

Route::middleware(['auth', 'staff'])->prefix('admin')->group(function () {
    Route::view('/', 'admin.home')->name('admin.home');
});
