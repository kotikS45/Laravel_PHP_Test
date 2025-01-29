<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::get("/auth/register", [AuthController::class, 'showRegisterForm'])->name('register.form');
Route::post('/auth/register', [AuthController::class, 'register'])->name('register');
Route::get("/auth/login", [AuthController::class, 'showLoginForm'])->name('login.form');
Route::post('/auth/login', [AuthController::class, 'login'])->name('login');
Route::get('/auth/google/redirect', [AuthController::class, 'redirectToGoogle'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
