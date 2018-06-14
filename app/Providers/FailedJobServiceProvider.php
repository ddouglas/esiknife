<?php

namespace ESIK\Providers;

use Illuminate\Queue\QueueManager;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\ServiceProvider;

use ESIK\Notifications\FailedJobNotification as Notification;

class FailedJobServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        app(QueueManager::class)->failing(function (JobFailed $event) {
            $notifiable = app(config('failed-job-monitor.notifiable'));

            $notification = app(config('failed-job-monitor.notification'))->setEvent($event);

            if (! $this->isValidNotificationClass($notification)) {
                $className = get_class($notification);
                throw new \Exception("Class {$className} is an invalid notification class. A notification class must extend ". Notification::class, 1);
            }

            $notifiable->notify($notification);
        });
    }

    public function isValidNotificationClass($notification): bool
    {
        if (get_class($notification) === Notification::class) {
            return true;
        }

        if (is_subclass_of($notification, Notification::class)) {
            return true;
        }

        return false;
    }
}
