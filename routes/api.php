<?php

use App\Http\Controllers\Api\V1\TaskBoardController;
use App\Http\Controllers\Api\V1\TaskColumnController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\TaskLabelController;
use App\Http\Controllers\Api\V1\TaskOrderController;
use App\Http\Controllers\Api\V1\TaskReportController;
use App\Http\Controllers\Api\V1\TaskTagController;
use App\Http\Controllers\Api\V1\TaskTimerController;
use App\Http\Controllers\Api\V1\WorkspaceController;
use App\Http\Controllers\Api\V1\WorkspaceInvitationController;
use App\Http\Controllers\Api\V1\WorkspaceMemberController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('workspaces', [WorkspaceController::class, 'index']);
    Route::post('workspaces', [WorkspaceController::class, 'store']);
    Route::get('workspaces/{workspace}/members', [WorkspaceMemberController::class, 'index']);
    Route::patch('workspaces/{workspace}/members/{user}', [WorkspaceMemberController::class, 'update']);
    Route::post('workspaces/{workspace}/invitations', [WorkspaceInvitationController::class, 'store']);
    Route::post('workspaces/invitations/{token}/accept', [WorkspaceInvitationController::class, 'accept']);

    Route::get('boards', [TaskBoardController::class, 'index']);
    Route::post('boards', [TaskBoardController::class, 'store']);
    Route::get('boards/{board}/columns', [TaskColumnController::class, 'index']);
    Route::get('boards/{board}/tasks', [TaskController::class, 'index']);

    Route::get('labels', [TaskLabelController::class, 'index']);
    Route::post('labels', [TaskLabelController::class, 'store']);
    Route::get('tags', [TaskTagController::class, 'index']);
    Route::post('tags', [TaskTagController::class, 'store']);

    Route::post('columns', [TaskColumnController::class, 'store']);
    Route::patch('columns/order', [TaskColumnController::class, 'order']);

    Route::post('tasks', [TaskController::class, 'store']);
    Route::patch('tasks/order', [TaskOrderController::class, 'update']);
    Route::patch('tasks/{task}', [TaskController::class, 'update']);
    Route::delete('tasks/{task}', [TaskController::class, 'destroy']);

    Route::post('tasks/{task}/timer', [TaskTimerController::class, 'store']);
    Route::patch('tasks/{task}/timer', [TaskTimerController::class, 'update']);

    Route::get('reports/time-entries', [TaskReportController::class, 'index']);
});
