<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AdminLayout extends Component
{
    public function __construct(public ?string $title = null) {}

    /**
     * Get the view that represents the component.
     */
    public function render(): View
    {
        $user = auth()->user();

        return view('layouts.admin', [
            'unreadNotifications' => $user->unreadNotifications()->latest()->limit(10)->get(),
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }
}
