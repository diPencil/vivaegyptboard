<?php

namespace Modules\Recruit\Listeners;

use Modules\Recruit\Events\JobApplicationStatusChangeEvent;
use Modules\Recruit\Notifications\JobApplicationStatusChange;
use Notification;

class JobApplicationStatusChangeListener
{

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param JobApplicationStatusChangeEvent $jobApplication
     * @return void
     */
    public function handle(JobApplicationStatusChangeEvent $jobApplication)
    {
        Notification::send($jobApplication->jobApplication, new JobApplicationStatusChange($jobApplication->jobApplication));
    }

}
