<?php

namespace App\Listeners;

use App\Models\User;
use App\Events\NotifyUser;
use App\Models\Company;
use App\Models\Employee;
use App\Notifications\UserNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Queue\InteractsWithQueue;

class UserNotificationListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(NotifyUser $event): void
    {
        if ($event->data['employees_ids'] !== null) {
            $employee_ids = $event->data['employees_ids'];
            $employees = Employee::whereIn('id', $employee_ids)
                ->with('user')
                ->get();
            foreach ($employees as $employee) {
                $user = $employee->user;
                $user->notify(new UserNotification($event->data));
            }
        }

        if ($event->data['companies_ids'] !== null) {
            $companies_ids = $event->data['companies_ids'];
            $companies = Company::whereIn('id', $companies_ids)
                ->with('user')
                ->get();
            foreach ($companies as $company) {
                $user = $company->user;
                $user->notify(new UserNotification($event->data));
            }
        }
    }
}
