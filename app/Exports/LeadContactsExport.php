<?php

namespace App\Exports;

use App\Models\Lead;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class LeadContactsExport implements FromQuery, WithMapping, WithHeadings, WithEvents
{
    protected $request;

    public function __construct(Request $request = null)
    {
        $this->request = $request ?: request();
    }

    public function query()
    {
        $model = Lead::query();

        $model = $model->select(
            'leads.client_name',
            'leads.client_email',
            'leads.mobile',
            'lead_category.category_name as category_name',
            'leads.lead_requirements',
            'leads.created_at'
        )
        ->leftJoin('lead_category', 'lead_category.id', 'leads.category_id');

        // apply filters similar to DataTable if present
        if ($this->request->filled('searchText')) {
            $term = $this->request->get('searchText');
            $model->where(function ($q) use ($term) {
                $q->where('leads.client_name', 'like', "%{$term}%")
                    ->orWhere('leads.client_email', 'like', "%{$term}%")
                    ->orWhere('leads.mobile', 'like', "%{$term}%");
            });
        }

        if ($this->request->filled('category_id') && $this->request->get('category_id') != 'all') {
            $model->where('category_id', $this->request->get('category_id'));
        }

        return $model->orderBy('leads.id');
    }

    public function map($lead): array
    {
        return [
            $this->safeValue($lead->client_name),
            $this->safeValue($lead->client_email),
            $this->safeValue($lead->mobile),
            $this->safeValue($lead->category_name),
            $this->safeValue(strip_tags($lead->lead_requirements ?? '')),
            optional($lead->created_at)->format('Y-m-d H:i:s'),
        ];
    }

    public function headings(): array
    {
        return [
            trans('modules.leadContact.contactName'),
            trans('modules.lead.email'),
            trans('modules.lead.mobile'),
            trans('modules.lead.leadCategory'),
            trans('modules.lead.leadRequirements'),
            trans('app.createdOn'),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $highestRow = $sheet->getHighestDataRow();

                // Force mobile column (third column -> 'C') to be text and sanitize potential formulas
                for ($row = 2; $row <= $highestRow; $row++) {
                    $cell = 'C' . $row;
                    $value = $sheet->getCell($cell)->getValue();
                    if (is_string($value) && strlen($value) > 0) {
                        // If value starts with = + - @ treat as text by prefixing single quote
                        if (preg_match('/^[=+\-@]/', $value)) {
                            $value = "'" . $value;
                        }
                        $sheet->setCellValueExplicit($cell, (string) $value, DataType::TYPE_STRING);
                    }
                }

                // Also ensure all lead requirements (column E) are text
                for ($row = 2; $row <= $highestRow; $row++) {
                    $cell = 'E' . $row;
                    $value = $sheet->getCell($cell)->getValue();
                    if (!is_null($value)) {
                        $sheet->setCellValueExplicit($cell, (string) $value, DataType::TYPE_STRING);
                    }
                }
            },
        ];
    }

    protected function safeValue($value)
    {
        if (is_null($value)) {
            return '';
        }

        $value = trim((string) $value);

        // Prevent CSV/Excel formula injection by prefixing with single quote when needed
        if (preg_match('/^[=+\-@]/', $value)) {
            return "'" . $value;
        }

        return $value;
    }
}
