<?php

namespace App\Listeners;

use App\Notifications\NewUserNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendEmailNewUserListener
{

    public function handle(Registered $event)
    {
        $event->user->notify(new NewUserNotification());
    }
}
