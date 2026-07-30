<?php

namespace App\Exports;

use App\Models\Attendance;
use Carbon\CarbonInterval;
use App\Models\AttendanceSetting;
use App\Models\EmployeeDetails;
use App\Models\EmployeeShiftSchedule;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\CompanyAddress;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

use Maatwebsite\Excel\Concerns\WithTitle;
use App\Services\AttendanceExportService;

class AttendanceExport implements FromCollection, WithHeadings, WithMapping, WithEvents, WithTitle
{

    /**
     * @return Collection
     */
    public static $sum;
    public static $startDate;
    public static $endDate;
    public $year;
    public $month;
    public $userId;
    public $viewAttendancePermission;
    public $department;
    public $designation;
    public $startdate;
    public $enddate;
    public $employees;
    public $employeedata;

    public function __construct($year, $month, $id, $department, $designation, $startdate, $enddate, $employees = null, $employeedata = null)
    {
        $this->viewAttendancePermission = user() ? user()->permission('view_attendance') : 'all';
        $this->year = $year;
        $this->month = $month;
        $this->userId = $id;
        $this->department = $department;
        $this->designation = $designation;
        $this->startdate = $startdate;
        $this->enddate = $enddate;
        $this->employees = $employees;
        $this->employeedata = $employeedata;
    }

    public function title(): string
    {
        return 'Monthly Summary';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => [self::class, 'afterSheet'],
        ];
    }

    public static function afterSheet(AfterSheet $event)
    {
        $emp_status = self::$sum;
        $total = count($emp_status);
        $arr = array('B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
        $j = 2;

        if (self::$startDate && self::$endDate) {
            $period = \Carbon\CarbonPeriod::create(self::$startDate, self::$endDate);
            $dateToColIndex = [];
            $colIdx = 0;
            foreach ($period->toArray() as $date) {
                $dateToColIndex[$date->day] = $colIdx++;
            }
        } else {
            return;
        }

        for ($index = 0; $index < $total; $index++) {
            if (isset($emp_status[$index]['dates'])) {
                foreach ($dateToColIndex as $day => $colIdx) {
                    if (isset($emp_status[$index]['dates'][$day]) && $emp_status[$index]['dates'][$day]['total_hours'] > 0) {
                        $event->sheet->getDelegate()->getComment($arr[$colIdx] . $j)->getText()->createTextRun(
                            ['Status : ' . $emp_status[$index]['dates'][$day]['comments']['status'],
                                $emp_status[$index]['dates'][$day]['comments']['clock_in'],
                            ]
                        );
                    }
                }
            }
            $j++;
        }

        $event->sheet->getDelegate()->getStyle('b:ag')
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    public function headings(): array
    {
        $arr = array();
        $period = CarbonPeriod::create($this->startdate, $this->enddate); // Get All Dates from start to end date
        $arr[] = __('app.empdate');

        foreach ($period->toArray() as $date) {
            $arr[] = $date->format('d-m-Y');
        }

        return [
            $arr,
        ];
    }

    public function collection()
    {
        if ($this->employeedata) {
            $employeedata = collect($this->employeedata)->filter(function ($value, $key) {
                return $key === 0 || $key === 'dates' || $key === 'employee_name';
            })->toArray();
            
            $formattedData = [];
            foreach ($this->employeedata as $emp) {
                $formattedData[] = [
                    'employee_name' => $emp['employee_name'] ?? '--',
                    'dates' => $emp['dates'] ?? []
                ];
            }
            
            $result = collect($formattedData);
            self::$sum = $result;
            return $result;
        }

        $startDate = $this->startdate;
        $endDate = $this->enddate;
        $id = $this->userId;

        $employees = AttendanceExportService::getExportEmployees(
            $this->year,
            $this->month,
            $this->userId,
            $this->department,
            $this->designation,
            $this->startdate,
            $this->enddate,
            $this->viewAttendancePermission
        );

        $holidays = Holiday::getHolidayByDates($this->startdate, $this->enddate, $this->userId);

        $matrixResult = AttendanceExportService::calculateAttendanceMatrix($employees, $this->startdate, $this->enddate, $holidays);
        $employeedata = $matrixResult['employeedata'];

        $formattedData = [];
        foreach ($employeedata as $emp) {
            $formattedData[] = [
                'employee_name' => $emp['employee_name'] ?? '--',
                'dates' => $emp['dates'] ?? []
            ];
        }

        $result = collect($formattedData);
        self::$sum = $result;
        self::$startDate = $this->startdate;
        self::$endDate = $this->enddate;
        return $result;
    }

    public function map($employeedata): array
    {
        $data = array();
        $data[] = $employeedata['employee_name'];
        $period = \Carbon\CarbonPeriod::create($this->startdate, $this->enddate);
        
        foreach ($period->toArray() as $date) {
            $day = $date->day;
            if (isset($employeedata['dates'][$day])) {
                $emp_status = $employeedata['dates'][$day]['comments']['status'];

                if (str_contains($emp_status, 'Holiday') || str_contains($emp_status, 'Rotation Day Off') || str_contains($emp_status, 'Rotation Cover') || $employeedata['dates'][$day]['total_hours'] < 1) {
                    $data[] = $emp_status;
                }
                else {
                    $data[] = \Carbon\CarbonInterval::formatHuman($employeedata['dates'][$day]['total_hours']);
                }
            } else {
                $data[] = '--';
            }
        }

        return $data;
    }

    public function checkHolidays($attendances, $date)
    {
        foreach ($attendances as $attendance) {
            if ($date->format('Y-m-d') == \Carbon\Carbon::parse($attendance->clock_in_time)->format('Y-m-d')) {
                $attendance->status = '';
            }
        }
    }

    private function getDefaultClockOutTime($date, $attendanceSettings)
    {

        if ($attendanceSettings) {
            $attendanceSettings = $attendanceSettings->shift;

        }
        else {
            $attendanceSettings = AttendanceSetting::first()->shift; // Do not get this from session here
        }

        $defaultClockOutTime = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $attendanceSettings->office_end_time, $attendanceSettings->company->timezone);

        if ($defaultClockOutTime->lessThan($date)) {
            $defaultClockOutTime = $date;
        }

        return $defaultClockOutTime;
    }

}
