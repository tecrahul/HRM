<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $viewer = $request->user();
        abort_unless($viewer !== null, 403);

        $status = (string) $request->string('status', 'all');
        if (! in_array($status, ['all', 'unread', 'read'], true)) {
            $status = 'all';
        }

        $notifications = $viewer->notifications()
            ->when($status === 'unread', function ($query): void {
                $query->whereNull('read_at');
            })
            ->when($status === 'read', function ($query): void {
                $query->whereNotNull('read_at');
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('notifications.index', [
            'notifications' => $notifications,
            'status' => $status,
            'stats' => [
                'total' => $viewer->notifications()->count(),
                'unread' => $viewer->unreadNotifications()->count(),
                'read' => $viewer->readNotifications()->count(),
            ],
        ]);
    }

    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $viewer = $request->user();
        abort_unless($viewer !== null, 403);

        $record = $viewer->notifications()->whereKey($notification)->first();
        if (! $record instanceof DatabaseNotification) {
            abort(404);
        }

        if ($record->read_at === null) {
            $record->markAsRead();
        }

        return redirect()->back();
    }

    public function markUnread(Request $request, string $notification): RedirectResponse
    {
        $viewer = $request->user();
        abort_unless($viewer !== null, 403);

        $record = $viewer->notifications()->whereKey($notification)->first();
        if (! $record instanceof DatabaseNotification) {
            abort(404);
        }

        if ($record->read_at !== null) {
            $record->update(['read_at' => null]);
        }

        return redirect()->back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $viewer = $request->user();
        abort_unless($viewer !== null, 403);

        $viewer->unreadNotifications->markAsRead();

        return redirect()->back();
    }
}
