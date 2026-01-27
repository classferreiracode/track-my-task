<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TaskBoardController;
use App\Http\Controllers\TaskColumnController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskOrderController;
use App\Http\Controllers\TaskReportController;
use App\Http\Controllers\TaskTimerController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::post('tasks/boards', [TaskBoardController::class, 'store'])->name('tasks.boards.store');
    Route::post('tasks/columns', [TaskColumnController::class, 'store'])->name('tasks.columns.store');
    Route::patch('tasks/columns/order', [TaskColumnController::class, 'order'])->name('tasks.columns.order');
    Route::patch('tasks/order', [TaskOrderController::class, 'update'])->name('tasks.order.update');
    Route::patch('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::get('tasks/report', [TaskReportController::class, 'export'])->name('tasks.report');
    Route::post('tasks/{task}/timer', [TaskTimerController::class, 'store'])->name('tasks.timer.start');
    Route::patch('tasks/{task}/timer', [TaskTimerController::class, 'update'])->name('tasks.timer.stop');
});

require __DIR__.'/settings.php';
