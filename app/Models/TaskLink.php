<?php

namespace App\Models;

class TaskLink extends BaseModel
{
    protected $fillable = ['task_id', 'link_name', 'link_url', 'description', 'added_by', 'last_updated_by'];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function lastUpdatedBy()
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }
}
