<?php

namespace App\Http\Requests\Lead;

use App\Http\Requests\CoreRequest;
use App\Traits\CustomFieldsRequestTrait;
use Illuminate\Validation\Rule;

class StoreRequest extends CoreRequest
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
        $rules = array();

        $rules['client_name'] = 'required';
        $rules['client_email'] = 'nullable|email:rfc,strict|unique:leads,client_email,null,id,company_id,' . company()->id;
        $rules['mobile'] = 'required|string|max:30';
        $rules['lead_requirements'] = 'nullable|string|max:5000';
        $rules['category_id'] = [
            'nullable',
            'integer',
            Rule::exists('lead_category', 'id')->where(function ($query) {
                $query->where('company_id', company()->id)
                    ->orWhereNull('company_id');
            }),
        ];

        if (request()->has('create_deal') && request()->create_deal == 'on') {
            $rules['name'] = 'required';
            $rules['pipeline'] = 'required';
            $rules['stage_id'] = 'required';
            $rules['close_date'] = 'required';
            $rules['value'] = 'required';
            $rules['deal_category_id'] = [
                'nullable',
                'integer',
                Rule::exists('lead_category', 'id')->where(function ($query) {
                    $query->where('company_id', company()->id)
                        ->orWhereNull('company_id');
                }),
            ];
        }

        return $this->customFieldRules($rules);

    }

    public function attributes()
    {
        $attributes = [];

        $attributes = $this->customFieldsAttributes($attributes);

        $attributes['client_name'] = __('app.name');
        $attributes['client_email'] = __('app.email');
        $attributes['name'] = __('modules.deal.dealName');
        $attributes['stage_id'] = __('modules.deal.leadStages');
        $attributes['category_id'] = __('modules.lead.leadCategory');
        $attributes['deal_category_id'] = __('modules.deal.dealCategory');

        return $attributes;
    }

}
