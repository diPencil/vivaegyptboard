<?php

namespace App\Observers;

use App\Models\TaskLink;

class TaskLinkObserver
{
    public function creating(TaskLink $link)
    {
        if (!isRunningInConsoleOrSeeding() && user()) {
            $link->added_by = user()->id;
        }
    }

    public function updating(TaskLink $link)
    {
        if (!isRunningInConsoleOrSeeding() && user()) {
            $link->last_updated_by = user()->id;
        }
    }
}
