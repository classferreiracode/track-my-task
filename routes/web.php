<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\TaskBoardController;
use App\Http\Controllers\TaskColumnController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskLabelController;
use App\Http\Controllers\TaskOrderController;
use App\Http\Controllers\TaskReportController;
use App\Http\Controllers\TaskTagController;
use App\Http\Controllers\TaskTimerController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkspaceInvitationController;
use App\Http\Controllers\WorkspaceMemberController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('invitations/{token}', [WorkspaceInvitationController::class, 'show'])
    ->name('workspaces.invitations.show');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'onboarding'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
    Route::match(['get', 'post'], 'invitations/{token}/accept', [WorkspaceInvitationController::class, 'accept'])
        ->name('workspaces.invitations.accept');
});

Route::middleware(['auth', 'verified', 'onboarding'])->group(function () {
    Route::get('teams', [TeamController::class, 'index'])->name('teams.index');
    Route::post('workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');
    Route::post('workspaces/{workspace}/invitations', [WorkspaceInvitationController::class, 'store'])
        ->middleware('workspace.limit:invite_member,workspace')
        ->name('workspaces.invitations.store');
    Route::delete('workspaces/{workspace}/members/leave', [WorkspaceMemberController::class, 'leave'])
        ->name('workspaces.members.leave');
    Route::patch('workspaces/{workspace}/members/{user}', [WorkspaceMemberController::class, 'update'])
        ->name('workspaces.members.update');
    Route::delete('workspaces/{workspace}/members/{user}', [WorkspaceMemberController::class, 'destroy'])
        ->whereNumber('user')
        ->name('workspaces.members.destroy');

    Route::post('notifications/{notification}/read', [NotificationController::class, 'read'])
        ->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])
        ->name('notifications.read-all');

    Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::post('tasks/boards', [TaskBoardController::class, 'store'])->name('tasks.boards.store');
    Route::delete('tasks/boards/{board}', [TaskBoardController::class, 'destroy'])->name('tasks.boards.destroy');
    Route::post('tasks/columns', [TaskColumnController::class, 'store'])->name('tasks.columns.store');
    Route::post('tasks/labels', [TaskLabelController::class, 'store'])->name('tasks.labels.store');
    Route::post('tasks/tags', [TaskTagController::class, 'store'])->name('tasks.tags.store');
    Route::patch('tasks/labels/{label}', [TaskLabelController::class, 'update'])->name('tasks.labels.update');
    Route::patch('tasks/tags/{tag}', [TaskTagController::class, 'update'])->name('tasks.tags.update');
    Route::patch('tasks/columns/order', [TaskColumnController::class, 'order'])->name('tasks.columns.order');
    Route::patch('tasks/order', [TaskOrderController::class, 'update'])->name('tasks.order.update');
    Route::patch('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::get('tasks/report', [TaskReportController::class, 'export'])->name('tasks.report');
    Route::post('tasks/{task}/timer', [TaskTimerController::class, 'store'])->name('tasks.timer.start');
    Route::patch('tasks/{task}/timer', [TaskTimerController::class, 'update'])->name('tasks.timer.stop');
    Route::get('tasks/{task}/comments', [TaskCommentController::class, 'index'])->name('tasks.comments.index');
    Route::post('tasks/{task}/comments', [TaskCommentController::class, 'store'])->name('tasks.comments.store');
});

require __DIR__.'/settings.php';
require __DIR__.'/management.php';
