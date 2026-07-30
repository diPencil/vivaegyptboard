<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class AttendanceDetailsExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles, WithEvents
{
    protected $normalizedData;

    public function __construct($normalizedData)
    {
        $this->normalizedData = $normalizedData;
    }

    public function collection()
    {
        return $this->normalizedData;
    }

    public function headings(): array
    {
        return [
            'Employee',
            'Employee ID',
            'Department',
            'Designation',
            'Date',
            'Day',
            'Attendance Status',
            'Check In',
            'Check Out',
            'Worked Hours',
            'Late By',
            'Early Leaving',
            'Roster Status',
            'Scheduled Shift',
            'Rotation Employee',
            'Rotation Details',
            'Leave Type',
            'Notes',
        ];
    }

    public function map($row): array
    {
        return [
            $row['employee'],
            $row['employee_id'],
            $row['department'],
            $row['designation'],
            $row['date'],
            $row['day'],
            $row['attendance_status'],
            $row['check_in'],
            $row['check_out'],
            $row['worked_hours'],
            $row['late_by'],
            $row['early_leaving'],
            $row['roster_status'],
            $row['scheduled_shift'],
            $row['rotation_employee'],
            $row['rotation_details'],
            $row['leave_type'],
            $row['notes'],
        ];
    }

    public function title(): string
    {
        return 'Attendance Details';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Set AutoFilter on all columns
                $highestColumn = $sheet->getHighestColumn();
                $highestRow = $sheet->getHighestRow();
                $sheet->setAutoFilter('A1:' . $highestColumn . $highestRow);
                
                // Freeze first row and first column
                $sheet->freezePane('B2');
                
                // Wrap text on Rotation Details and Notes
                $sheet->getStyle('P2:P' . $highestRow)->getAlignment()->setWrapText(true);
                $sheet->getStyle('R2:R' . $highestRow)->getAlignment()->setWrapText(true);
            },
        ];
    }
}
