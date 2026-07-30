<?php

namespace App\Exports;

use App\Models\Holiday;
use App\Services\AttendanceExportService;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AttendanceMultiSheetExport implements WithMultipleSheets
{
    use Exportable;

    public $year;
    public $month;
    public $userId;
    public $department;
    public $designation;
    public $startdate;
    public $enddate;

    public function __construct($year, $month, $id, $department, $designation, $startdate, $enddate)
    {
        $this->year = $year;
        $this->month = $month;
        $this->userId = $id;
        $this->department = $department;
        $this->designation = $designation;
        $this->startdate = $startdate;
        $this->enddate = $enddate;
    }

    public function sheets(): array
    {
        $viewAttendancePermission = user()->permission('view_attendance');

        // 1. Fetch Shared Eager-Loaded Data
        $employees = AttendanceExportService::getExportEmployees(
            $this->year,
            $this->month,
            $this->userId,
            $this->department,
            $this->designation,
            $this->startdate,
            $this->enddate,
            $viewAttendancePermission
        );

        $holidays = Holiday::getHolidayByDates($this->startdate, $this->enddate, $this->userId);

        // 2. Process Matrix once
        $matrixResult = AttendanceExportService::calculateAttendanceMatrix($employees, $this->startdate, $this->enddate, $holidays);
        $employeedata = $matrixResult['employeedata'];
        $normalizedData = $matrixResult['normalizedData'];

        // 3. Return three sheets in exact order
        return [
            new AttendanceDetailsExport($normalizedData),
            new AttendanceExport($this->year, $this->month, $this->userId, $this->department, $this->designation, $this->startdate, $this->enddate, $employees, $employeedata),
            new ShiftScheduleExport($this->year, $this->month, $this->userId, $this->department, $this->startdate, $this->enddate, 'month', $employees),
        ];
    }
}
