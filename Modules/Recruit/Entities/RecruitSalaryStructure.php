<?php

namespace Modules\Recruit\Entities;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Model;

class RecruitSalaryStructure extends Model
{
    use HasCompany;

    protected $fillable = [];
}
