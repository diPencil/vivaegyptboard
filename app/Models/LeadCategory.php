<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\LeadCategory
 *
 * @property int $id
 * @property string $category_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $added_by
 * @property int|null $last_updated_by
 * @method static \Illuminate\Database\Eloquent\Builder|LeadCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LeadCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LeadCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder|LeadCategory whereAddedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LeadCategory whereCategoryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LeadCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LeadCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LeadCategory whereLastUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LeadCategory whereUpdatedAt($value)
 * @property int|null $company_id
 * @property-read \App\Models\Company|null $company
 * @method static \Illuminate\Database\Eloquent\Builder|LeadCategory whereCompanyId($value)
 * @mixin \Eloquent
 */
class LeadCategory extends BaseModel
{

    use HasCompany;

    /**
     * Scope to return categories visible to a given company.
     * Includes company-specific categories and global categories (company_id IS NULL).
     * If $companyId is null, return all categories (super-admin / console).
     */
    public function scopeVisibleToCompany($query, $companyId)
    {
        if (is_null($companyId)) {
            return $query;
        }

        // remove the default CompanyScope so we can include global (NULL company_id) rows
        return $query->withoutGlobalScope(\App\Scopes\CompanyScope::class)
            ->where(function ($q) use ($companyId) {
                $q->where($this->getTable() . '.company_id', $companyId)
                    ->orWhereNull($this->getTable() . '.company_id');
            });
    }

    protected $table = 'lead_category';
    protected $default = ['id', 'category_name'];

    public function enabledAgents(): HasMany
    {
        return $this->hasMany(LeadAgent::class, 'lead_category_id')->where('status', '=', 'enabled');
    }

}
