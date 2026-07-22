<?php

namespace Modules\Recruit\Entities;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecruitJobCategory extends Model
{
    use HasFactory, HasCompany;

    protected $fillable = [];
}
