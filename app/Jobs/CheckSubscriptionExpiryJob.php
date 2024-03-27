<?php

namespace App\Jobs;

use App\Mail\AcountSuspentionEmail;
use App\Mail\SubscriptionEndedEmail;
use App\Mail\SubscriptionExpiryReminder;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class CheckSubscriptionExpiryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * Create a new job instance.
     */


    public function __construct()
    {
        // $this->user = $user;
    }

    /**
     * Execute the job.
     */

    public function handle(): void
    {



        $tenants = Tenant::where('status', '1')->get();

        foreach ($tenants as $tenant) {
            $user = $tenant->user;
            if ($user->status == '1') {
                if ($tenant->subscriber) {
                    $subscriber = $tenant->subscriber;
                    if ($subscriber) {
                        // expiry date is the date on which the subscription of the user is being ended.
                        $expiry_date = $subscriber->end_date;
                        $subscription_ending_date = $expiry_date;
                        $current_date = now()->format('Y-m-d'); //getting the current date

                        // notification_date is the date on which we will send an email notification to the tenant that his subscription is going to be ended in 7 days
                        $notification_date = Carbon::parse($expiry_date)->subDays(7);
                        $notification_date = $notification_date->format('Y-m-d');

                        /**
                         * The account suspension date is the date on which the tenant's account will be suspended,
                         * which occurs 7 days after the expiry date.
                         * After suspension, the tenant, along with their employees and associated companies,
                         * will no longer be able to log in to the system.
                         */
                        $acount_suspention_date = Carbon::parse($expiry_date)->addDays(7);
                        $email = $user->email;

                        $data = [
                            'name' => $tenant->name,
                            'subscription_ending_date' => $expiry_date,
                            'acount_suspention_date' => $acount_suspention_date
                        ];
                        if ($current_date == $notification_date) {
                            $mail = new SubscriptionExpiryReminder($data);
                            Mail::to($email)->send($mail);
                        }

                        if ($current_date == $subscription_ending_date) {
                            $mail = new SubscriptionEndedEmail($data);

                            Mail::to($email)->send($mail);
                        }


                        if ($current_date == $acount_suspention_date) {

                            $mail = new AcountSuspentionEmail ($data);
                            Mail::to($email)->send($mail);

                            // logout the tenant and deactivate his acount

                            $user->tokens()->delete();
                            $user->update(['status' => '0']);
                            //logout the employees and deactivate their acount
                            $employees =  $tenant->employees;
                            if (count($employees) > 0) {

                                foreach ($employees as $employee) {
                                    $employee_user = $employee->user;
                                    $employee_user->tokens()->delete();
                                    $employee_user->update(['status' => '0']);
                                }
                            }
                            //logout the companies and deactivate their acount
                            $companies = $tenant->companies;
                            if (count($companies) > 0) {


                                foreach ($companies as $company) {
                                    $company_user = $company->user;
                                    $company_user->tokens()->delete();
                                    $company_user->update(['status' => '0']);
                                }
                            }
                        }
                    }
                }
            }
        }

    }
}
