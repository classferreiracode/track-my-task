<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function read(DatabaseNotification $notification): RedirectResponse
    {
        $user = request()->user();

        if (! $user || $notification->notifiable_id !== $user->id) {
            abort(403);
        }

        if (! $notification->read_at) {
            $notification->markAsRead();
        }

        return back();
    }

    public function readAll(): RedirectResponse
    {
        $user = request()->user();

        if (! $user) {
            abort(403);
        }

        $user->unreadNotifications->markAsRead();

        return back();
    }
}
