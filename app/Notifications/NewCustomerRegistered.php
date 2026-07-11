<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewCustomerRegistered extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public User $customer) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'customer_email' => $this->customer->email,
            'message' => "New customer \"{$this->customer->name}\" registered and is awaiting approval.",
        ];
    }
}
