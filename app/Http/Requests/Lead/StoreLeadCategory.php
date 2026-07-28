<?php

namespace App\Http\Requests\Lead;

use App\Http\Requests\CoreRequest;
use Illuminate\Validation\Rule;

class StoreLeadCategory extends CoreRequest
{

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if ($this->has('category_name')) {
            $this->merge(['category_name' => trim($this->input('category_name'))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $company = company();
        $companyId = $company ? $company->id : null;

        return [
            'category_name' => [
                'required',
                // custom closure to enforce uniqueness across current company + global categories
                function ($attribute, $value, $fail) use ($companyId) {
                    $normalized = mb_strtolower(trim($value));
                    $query = \DB::table('lead_category')
                        ->whereRaw('LOWER(category_name) = ?', [$normalized])
                        ->where(function ($q) use ($companyId) {
                            $q->where('company_id', $companyId)
                                ->orWhereNull('company_id');
                        });

                    if ($query->exists()) {
                        $fail(trans('validation.unique', ['attribute' => $attribute]));
                    }
                }
            ]
        ];
    }

}
