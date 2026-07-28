<?php

namespace App\Http\Requests\Lead;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeadCategory extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $company = company();
        $companyId = $company ? $company->id : null;

        $currentId = $this->route('leadCategory') ?: $this->route('id');

        return [
            'category_name' => [
                'required',
                function ($attribute, $value, $fail) use ($companyId, $currentId) {
                    $normalized = mb_strtolower(trim($value));
                    $query = \DB::table('lead_category')
                        ->whereRaw('LOWER(category_name) = ?', [$normalized])
                        ->where(function ($q) use ($companyId) {
                            $q->where('company_id', $companyId)
                                ->orWhereNull('company_id');
                        });

                    if ($currentId) {
                        $query->where('id', '<>', $currentId);
                    }

                    if ($query->exists()) {
                        $fail(trans('validation.unique', ['attribute' => $attribute]));
                    }
                }
            ],
        ];
    }

}
