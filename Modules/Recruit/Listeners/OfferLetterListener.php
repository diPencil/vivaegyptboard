<?php

namespace Modules\Recruit\Listeners;

use App\Models\User;
use Modules\Recruit\Events\OfferLetterEvent;
use Modules\Recruit\Notifications\AdminNewOfferLetter;
use Modules\Recruit\Notifications\RecruiterOfferLetter;
use Modules\Recruit\Notifications\SendOfferLetter;
use Notification;

class OfferLetterListener
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
    public function handle(OfferLetterEvent $jobOffer)
    {
        $companyId = $jobOffer->jobOffer->jobApplication->job->company->id;
        $companyAdmins = User::allAdmins($companyId)->pluck('id')->toArray();
        $recruiterId = $jobOffer->jobOffer->jobApplication->job->recruiter->id;

        $adminNotification = new AdminNewOfferLetter($jobOffer->jobOffer);

        if (in_array($recruiterId, $companyAdmins)) {
            Notification::send(User::allAdmins($companyId), $adminNotification);
        }
        else {
            Notification::send(User::allAdmins($companyId), $adminNotification);
            Notification::send($jobOffer->jobOffer->jobApplication->job->recruiter, new RecruiterOfferLetter($jobOffer->jobOffer));
        }

        $applicant = $jobOffer->jobOffer->jobApplication;

        if ($applicant->email) {
            Notification::send($applicant, new SendOfferLetter($jobOffer->jobOffer));
        }
    }


}
