<?php

namespace Modules\Recruit\Entities;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class RecruitJobAlert extends Model
{
    use HasCompany, Notifiable;

    protected $fillable = [];

    public function workExperience()
    {
        return $this->belongsTo(RecruitWorkExperience::class, 'recruit_work_experience_id');
    }
}
