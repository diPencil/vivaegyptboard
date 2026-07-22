<?php

namespace Modules\Recruit\Http\Requests\Skill;

use App\Http\Requests\CoreRequest;

class StoreJobSkill extends CoreRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function rules()
    {
        return [
            'names' => 'required|unique:recruit_skills,name',
        ];
    }

    public function messages()
    {
        return [
            'names.required' => __('recruit::modules.skill.addSkills'),
        ];
    }
}
