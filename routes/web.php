<?php

use Illuminate\Support\Facades\Route;
use Lchris44\EmailPreferenceCenter\Http\Controllers\UnsubscribeController;

$path = config('email-preferences.dashboard.path', 'email-preferences');

Route::get("{$path}/unsubscribe", [UnsubscribeController::class, 'show'])
    ->name('email-preferences.unsubscribe');

Route::post("{$path}/unsubscribe", [UnsubscribeController::class, 'handle'])
    ->name('email-preferences.unsubscribe.post');
