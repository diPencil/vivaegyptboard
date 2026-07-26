<?php

namespace App\Http\Controllers;

use App\Exports\LeadContactsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class LeadExportController extends Controller
{
    public function export(Request $request)
    {
        abort_403(!canDataTableExport());

        $export = new LeadContactsExport($request);

        return Excel::download($export, 'leads_' . now()->format('Ymd_His') . '.xlsx');
    }
}
