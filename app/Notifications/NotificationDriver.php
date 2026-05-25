<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Notification;

class NotificationDriver extends Notification
{
    public function __construct(private Order $order, private string $title, private string $message) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id'   => $this->order->id,
            'order_num'  => $this->order->order_num,
            'branch_id'  => $this->order->branch_id,
            'title'      => $this->title,
            'message'    => $this->message,
            'type'       => 'new_order',
        ];
    }
}
