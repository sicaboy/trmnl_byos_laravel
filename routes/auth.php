<?php

use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    // Redirect to Users.au OAuth login
    Route::get('login', function () {
        return redirect()->route('usersau.login');
    })->name('login');

    // Redirect to Users.au OAuth registration
    if (config('app.registration.enabled')) {
        Route::get('register', function () {
            return redirect()->route('usersau.register');
        })->name('register');
    }

    Volt::route('forgot-password', 'auth.forgot-password')
        ->name('password.request');

    Volt::route('reset-password/{token}', 'auth.reset-password')
        ->name('password.reset');

});

Route::middleware('auth')->group(function () {
    Volt::route('verify-email', 'auth.verify-email')
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Volt::route('confirm-password', 'auth.confirm-password')
        ->name('password.confirm');
});

// Redirect to Users.au OAuth logout
Route::match(['get', 'post'], 'logout', function () {
    return redirect()->route('usersau.logout');
})->name('logout');
