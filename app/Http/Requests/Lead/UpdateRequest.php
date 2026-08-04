<?php

namespace App\Http\Requests\Lead;

use App\Http\Requests\CoreRequest;
use App\Traits\CustomFieldsRequestTrait;
use Illuminate\Validation\Rule;

class UpdateRequest extends CoreRequest
{
    use CustomFieldsRequestTrait;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'client_name' => 'required',
            'client_email' => 'nullable|email:rfc,strict|unique:leads,client_email,'.$this->route('lead_contact').',id,company_id,' . company()->id,
            'mobile' => 'required|string|max:30',
            'lead_requirements' => 'nullable|string|max:5000',
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('lead_category', 'id')->where(function ($query) {
                    $query->where('company_id', company()->id)
                        ->orWhereNull('company_id');
                }),
            ],
        ];

        $rules = $this->customFieldRules($rules);

        return $rules;
    }

    public function attributes()
    {
        $attributes = [];

        $attributes = $this->customFieldsAttributes($attributes);

        $attributes['client_name'] = __('app.name');
        $attributes['client_email'] = __('app.email');
        $attributes['category_id'] = __('modules.lead.leadCategory');

        return $attributes;
    }

}
