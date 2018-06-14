<?php

namespace ESIK;

use Illuminate\Notifications\Notifiable as NotifiableTrait;

class Notifiable
{
    use NotifiableTrait;

    public function routeNotificationForMail(): string
    {
        return config('failed-job-monitor.mail.to');
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return 1;
    }
}
