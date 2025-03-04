<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Filament\Facades\Filament;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/private-files/{path}', function (string $path) {
    // Check if user is authenticated with Filament
    if (!auth()->check()) {
        // Redirect to Filament login page instead of the default Laravel login
        return redirect(Filament::getLoginUrl());
    }

    // Check if file exists
    if (!Storage::disk('private')->exists($path)) {
        abort(404);
    }

    // Return file
    return Response::file(storage_path("app/private/{$path}"));
})->where('path', '.*');
