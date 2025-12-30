<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Auth Routes
Route::post('/login', [AuthController::class, 'login']);

Route::get('/check', [AuthController::class, 'checkAuth']);

Route::get('/user', function (Request $request) {
    return response()->json($request->user());
});
