<?php

use App\Http\Controllers\Management\AuthController;
use App\Http\Controllers\Management\DashboardController;
use App\Http\Controllers\Management\PlanController;
use App\Http\Controllers\Management\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('management')->name('management.')->group(function () {
    Route::get('login', [AuthController::class, 'create'])->name('login');
    Route::post('login', [AuthController::class, 'store'])->name('login.store');
    Route::post('logout', [AuthController::class, 'destroy'])->name('logout');

    Route::middleware('management.auth')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('plans', [PlanController::class, 'index'])->name('plans.index');
        Route::patch('plans/{plan}', [PlanController::class, 'update'])->name('plans.update');
        Route::get('users', [UserController::class, 'index'])->name('users.index');
    });
});
