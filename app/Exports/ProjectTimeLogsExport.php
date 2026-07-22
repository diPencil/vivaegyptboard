<?php

namespace App\Exports;

use App\Models\ProjectTimeLog;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProjectTimeLogsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithColumnFormatting, WithStyles, Responsable
{

    /**
     * Optional filters
     */
    protected ?string $startDate;
    protected ?string $endDate;
    protected ?int $userId;

    /**
     * The file name for download
     *
     * @var string
     */
    public string $fileName = 'project-time-logs.xlsx';

    public function __construct(?string $startDate = null, ?string $endDate = null, ?int $userId = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->userId = $userId;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // Determine date window: default to current month
        $companyTz = company() ? company()->timezone : config('app.timezone');
        $startCompanyTz = $this->startDate
            ? Carbon::parse($this->startDate, $companyTz)->startOfDay()
            : now($companyTz)->startOfMonth();
        $endCompanyTz = $this->endDate
            ? Carbon::parse($this->endDate, $companyTz)->endOfDay()
            : now($companyTz)->endOfMonth()->endOfDay();

        // Convert the company window to UTC for querying stored datetimes
        $start = $startCompanyTz->clone()->setTimezone('UTC');
        $end = $endCompanyTz->clone()->setTimezone('UTC');

        $query = ProjectTimeLog::query()
            ->without('breaks', 'activeBreak')
            ->with(['user', 'activeBreak', 'project', 'task'])
            ->withSum('breaks', 'total_minutes')
            ->select(['id', 'user_id', 'project_id', 'task_id', 'start_time', 'end_time', 'total_minutes'])
            ->whereBetween('start_time', [$start, $end])
            ->when($this->userId, function ($q) {
                $q->where('user_id', $this->userId);
            })
            ->orderBy('start_time', 'desc');

        return $query->get();
    }

    public function headings(): array
    {
        return [
            __('app.employee'),
            __('modules.taskCode'),
            __('app.task'),
            __('app.project'),
            __('modules.timeLogs.startTime'),
            __('modules.timeLogs.endTime'),
            __('modules.timeLogs.totalHours'),
        ];
    }

    /**
     * @param ProjectTimeLog $log
     */
    public function map($log): array
    {
        $companyTz = company() ? company()->timezone : config('app.timezone');
        $start = $log->start_time instanceof Carbon ? $log->start_time : Carbon::parse($log->start_time);
        $end = $log->end_time ? ($log->end_time instanceof Carbon ? $log->end_time : Carbon::parse($log->end_time)) : null;

        // Stored in UTC; convert to company timezone for display
        $start = $start->clone()->tz($companyTz)->translatedFormat(company()->date_format . ' ' . company()->time_format);
        $end = $end ? $end->clone()->tz($companyTz)->translatedFormat(company()->date_format . ' ' . company()->time_format) : null;

        return [
            optional($log->user)->name,
            optional($log->project)->project_short_code,
            optional($log->task)->heading,
            optional($log->project)->project_name,
            $start,
            $end,
            $this->formatTotalHours($log),
        ];
    }

    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_DATE_DATETIME,
            'F' => NumberFormat::FORMAT_DATE_DATETIME,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Bold header row and center it
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A1:G1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        return [];
    }

    protected function formatTotalHours(ProjectTimeLog $log): string
    {
        $breakMinutes = (int) ($log->breaks_sum_total_minutes ?? 0);

        if (is_null($log->end_time)) {
            // Open timer: compute up to now or leave as running duration
            $end = $log->activeBreak ? $log->activeBreak->start_time : now();
            $totalMinutes = $end->diffInMinutes($log->start_time) - $breakMinutes;
        } else {
            $totalMinutes = ((int) $log->total_minutes) - $breakMinutes;
        }

        if ($totalMinutes < 0) {
            $totalMinutes = 0;
        }

        $hours = (int) floor($totalMinutes / 60);
        $minutes = (int) ($totalMinutes % 60);

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    /**
     * Create an HTTP response that represents the object.
     */
    public function toResponse($request)
    {
        return Excel::download($this, $this->fileName);
    }
}


