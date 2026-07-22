<?php

namespace Modules\Recruit\Listeners;

use Modules\Recruit\Events\UpdateOfferLetterEvent;
use Modules\Recruit\Notifications\RecruiterOfferLetter;
use Modules\Recruit\Notifications\UpdateOfferLetter;
use Notification;

class UpdateOfferLetterListener
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
     * @param object $event
     * @return void
     */
    public function handle(UpdateOfferLetterEvent $event)
    {
        $jobOffer = $event->jobOffer;

        $recipient = $jobOffer->job->recruiter;
        $notification = $jobOffer->isDirty('job_app_id') ? new RecruiterOfferLetter($jobOffer) : new UpdateOfferLetter($jobOffer);

        Notification::send($recipient, $notification);
    }


}
