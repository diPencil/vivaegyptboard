<?php

namespace App\Services;

use App\Models\User;
use App\Models\Holiday;
use App\Models\Attendance;
use Carbon\CarbonPeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceExportService
{
    /**
     * Get eager-loaded employees for export
     */
    public static function getExportEmployees($year, $month, $id, $department, $designation, $startDate, $endDate, $viewAttendancePermission = 'all')
    {
        $employees = User::with([
            'employeeDetail.department',
            'employeeDetail.designation',
            'attendance' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween(DB::raw('DATE(attendances.clock_in_time)'), [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                      ->orderBy('attendances.clock_in_time', 'asc');
            },
            'leaves' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('leaves.leave_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->where('status', 'approved');
            },
            'leaves.type',
            'shifts' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('employee_shift_schedules.date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
            },
            'shifts.shift',
            'shifts.replacementUser',
            'shifts.replacementShift',
            'shifts.rotationSource.user',
            'shifts.rotationSource.shift'
        ])
        ->join('role_user', 'role_user.user_id', '=', 'users.id')
        ->join('roles', 'roles.id', '=', 'role_user.role_id')
        ->leftJoin('employee_details', 'employee_details.user_id', '=', 'users.id')
        ->select('users.id', 'users.name', 'users.email', 'users.created_at', 'users.status', 'users.inactive_date', 'employee_details.department_id', 'employee_details.designation_id', 'employee_details.joining_date')
        ->where('roles.name', '<>', 'client')
        ->groupBy('users.id');

        // Apply filters
        if ($id != 'all') {
            if ($viewAttendancePermission == 'owned') {
                $employees->where('users.id', user()->id);
            } else {
                $employees->where('users.id', $id);
            }
        } else if ($viewAttendancePermission == 'owned') {
            $employees->where('users.id', user()->id);
        }

        if ($department != 'all') {
            $employees->where('employee_details.department_id', $department);
        }

        if ($designation != 'all') {
            $employees->where('employee_details.designation_id', $designation);
        }

        $employees->where(function ($query) use ($startDate, $endDate) {
            $query->where('users.status','active')
                  ->orWhere(function ($subQuery) use ($startDate, $endDate) {
                      $subQuery->whereRaw('YEAR(users.inactive_date) >= ?', [$startDate->format('Y')])
                               ->whereRaw('MONTH(users.inactive_date) >= ?', [$endDate->format('m')]);
                  });
        });

        return $employees->get();
    }

    /**
     * Generate structured data and legacy matrix for export
     */
    public static function calculateAttendanceMatrix($employees, $startDate, $endDate, $holidays)
    {
        $period = CarbonPeriod::create($startDate, $endDate);
        
        $employeedata = [];
        $normalizedData = [];
        $employee_index = 0;

        // Create dictionary of holidays for fast lookup
        $holidaysByDate = [];
        foreach ($holidays as $holiday) {
            $hDate = Carbon::createFromFormat('Y-m-d', $holiday->holiday_date)->format('Y-m-d');
            if (!isset($holidaysByDate[$hDate])) {
                $holidaysByDate[$hDate] = [];
            }
            $holidaysByDate[$hDate][] = $holiday;
        }

        $now = now()->timezone(company()->timezone);

        foreach ($employees as $employee) {
            $userId = $employee->id;
            $employeedata[$employee_index]['employee_name'] = $employee->name;

            // Index employee records by date
            $leavesByDate = [];
            foreach ($employee->leaves as $leave) {
                $leavesByDate[$leave->leave_date->format('Y-m-d')] = $leave;
            }

            $shiftsByDate = [];
            foreach ($employee->shifts as $shift) {
                $shiftsByDate[$shift->date->format('Y-m-d')] = $shift;
            }

            $attendancesByDate = [];
            foreach ($employee->attendance as $att) {
                $attDate = Carbon::parse($att->clock_in_time)->format('Y-m-d');
                if (!isset($attendancesByDate[$attDate])) {
                    $attendancesByDate[$attDate] = collect();
                }
                $attendancesByDate[$attDate]->push($att);
            }

            // Loop through each date in the period
            foreach ($period->toArray() as $date) {
                $dateStr = $date->format('Y-m-d');
                $isFuture = $date->greaterThan($now);
                
                $dayAttendances = $attendancesByDate[$dateStr] ?? collect();
                $hasAttendance = $dayAttendances->count() > 0;
                
                $dayLeave = $leavesByDate[$dateStr] ?? null;
                $dayShift = $shiftsByDate[$dateStr] ?? null;
                $dayHolidays = $holidaysByDate[$dateStr] ?? [];

                // Fallback struct for normalization
                $norm = [
                    'employee' => $employee->name,
                    'employee_id' => $employee->id,
                    'department' => $employee->employeeDetail && $employee->employeeDetail->department ? $employee->employeeDetail->department->team_name : '--',
                    'designation' => $employee->employeeDetail && $employee->employeeDetail->designation ? $employee->employeeDetail->designation->name : '--',
                    'date' => $dateStr,
                    'day' => $date->format('l'),
                    'attendance_status' => '--',
                    'check_in' => '--',
                    'check_out' => '--',
                    'worked_hours' => '0s',
                    'late_by' => '--',
                    'early_leaving' => '--',
                    'roster_status' => '--',
                    'scheduled_shift' => '--',
                    'rotation_employee' => '--',
                    'rotation_details' => '--',
                    'leave_type' => '--',
                    'notes' => '--',
                ];

                // Determine Roster details from EmployeeShiftSchedule
                if ($dayShift) {
                    if ($dayShift->status_type == 'rotation_day_off') {
                        $norm['roster_status'] = __('modules.rotationDayOff');
                        $timeStr = '';
                        if ($dayShift->replacementShift) {
                            $timeStr = Carbon::parse($dayShift->replacementShift->office_start_time)->format(company()->time_format) . ' - ' . Carbon::parse($dayShift->replacementShift->office_end_time)->format(company()->time_format);
                        }
                        $norm['scheduled_shift'] = ($dayShift->replacementShift ? $dayShift->replacementShift->shift_name : '') . ' ' . $timeStr;
                        $norm['rotation_employee'] = $dayShift->replacementUser ? $dayShift->replacementUser->name : '--';
                        $norm['rotation_details'] = __('modules.coveredBy') . ' ' . $norm['rotation_employee'];
                    } elseif ($dayShift->rotation_source_schedule_id != null) {
                        $norm['roster_status'] = __('modules.rotationCover');
                        $timeStr = '';
                        if ($dayShift->shift) {
                            $timeStr = Carbon::parse($dayShift->shift->office_start_time)->format(company()->time_format) . ' - ' . Carbon::parse($dayShift->shift->office_end_time)->format(company()->time_format);
                        }
                        $norm['scheduled_shift'] = ($dayShift->shift ? $dayShift->shift->shift_name : '') . ' ' . $timeStr;
                        $norm['rotation_employee'] = $dayShift->rotationSource && $dayShift->rotationSource->user ? $dayShift->rotationSource->user->name : '--';
                        $norm['rotation_details'] = __('modules.coveringFor') . ' ' . $norm['rotation_employee'];
                    } else {
                        // normal shift
                        if ($dayShift->status_type == 'day_off') {
                            $norm['roster_status'] = __('modules.dayOff');
                        } elseif (!empty($dayShift->status_type)) {
                            $norm['roster_status'] = __('modules.' . \Illuminate\Support\Str::camel($dayShift->status_type)) ?? $dayShift->status_type;
                        } else {
                            $norm['roster_status'] = $dayShift->shift ? $dayShift->shift->shift_name : '--';
                        }
                        $timeStr = '';
                        if ($dayShift->shift && $dayShift->shift->shift_name != 'Day Off') {
                            $timeStr = Carbon::parse($dayShift->shift->office_start_time)->format(company()->time_format) . ' - ' . Carbon::parse($dayShift->shift->office_end_time)->format(company()->time_format);
                        }
                        $norm['scheduled_shift'] = ($dayShift->shift ? $dayShift->shift->shift_name : '') . ' ' . $timeStr;
                    }
                }

                // Default attendance status calculation similar to AttendanceExport
                if (!$hasAttendance) {
                    if (!$isFuture) {
                        $status = 'Absent';
                        if ($dayLeave) {
                            $status = 'On Leave';
                            $norm['leave_type'] = $dayLeave->type ? $dayLeave->type->type_name : '--';
                        }
                        // Holidays logic for user
                        foreach ($dayHolidays as $holiday) {
                            $hasDep = is_null($holiday->department_id_json) || ($employee->employeeDetail && in_array($employee->employeeDetail->department_id, json_decode($holiday->department_id_json, true) ?? []));
                            $hasDes = is_null($holiday->designation_id_json) || ($employee->employeeDetail && in_array($employee->employeeDetail->designation_id, json_decode($holiday->designation_id_json, true) ?? []));
                            $hasEmp = is_null($holiday->employment_type_json) || ($employee->employeeDetail && in_array($employee->employeeDetail->employment_type, json_decode($holiday->employment_type_json, true) ?? []));
                            if ($hasDep && $hasDes && $hasEmp) {
                                $status = 'Holiday';
                            }
                        }

                        // Override status for Rotation Day Off and normal Day Off, but never override Holiday
                        $monthly_status = $status;
                        $rotation_comment = '';

                        if ($dayShift && $status !== 'Holiday') {
                            if ($dayShift->status_type == 'rotation_day_off') {
                                $status = '--'; // Roster Status column carries this, Attendance Status stays blank
                                $monthly_status = __('modules.rotationDayOff');
                                $rotation_comment = __('modules.rotationDayOff') . "\n" . __('modules.coveredBy') . ': ' . $norm['rotation_employee'] . "\nScheduled Shift: " . $norm['scheduled_shift'];
                            } elseif ($dayShift->status_type == 'day_off' || ($dayShift->shift && $dayShift->shift->shift_name == 'Day Off')) {
                                $status = '--'; // Day Off is a Roster Status, not an Attendance Status
                                $monthly_status = 'Day Off';
                            }
                        }

                        $norm['attendance_status'] = $status;
                        
                        // Populate matrix for Monthly Summary
                        $employeedata[$employee_index]['dates'][$date->day] = [
                            'total_hours' => 0,
                            'comments' => [
                                'status' => $monthly_status,
                                'clock_in' => '',
                                'rotation' => $rotation_comment,
                            ]
                        ];
                    } else {
                        // Future date
                        $employeedata[$employee_index]['dates'][$date->day] = [
                            'total_hours' => 0,
                            'comments' => [
                                'status' => '--',
                                'clock_in' => '',
                                'rotation' => '',
                            ]
                        ];
                    }
                } else {
                    // Has actual attendance
                    $total_hours = 0;
                    $clock_in_str = '';
                    $firstAtt = $dayAttendances->first();
                    $lastAtt = $dayAttendances->last();
                    
                    $norm['check_in'] = Carbon::parse($firstAtt->clock_in_time)->timezone(company()->timezone)->format(company()->time_format);
                    if ($lastAtt->clock_out_time) {
                        $norm['check_out'] = Carbon::parse($lastAtt->clock_out_time)->timezone(company()->timezone)->format(company()->time_format);
                    }
                    
                    $status = 'Present';
                    if ($firstAtt->half_day == 'yes') {
                        $status = 'Half Day';
                    } elseif ($firstAtt->late == 'yes') {
                        $status = 'Late';
                    }
                    
                    // Check holidays even with attendance (existing system logic)
                    foreach ($dayHolidays as $holiday) {
                        $hasDep = is_null($holiday->department_id_json) || ($employee->employeeDetail && in_array($employee->employeeDetail->department_id, json_decode($holiday->department_id_json, true) ?? []));
                        $hasDes = is_null($holiday->designation_id_json) || ($employee->employeeDetail && in_array($employee->employeeDetail->designation_id, json_decode($holiday->designation_id_json, true) ?? []));
                        $hasEmp = is_null($holiday->employment_type_json) || ($employee->employeeDetail && in_array($employee->employeeDetail->employment_type, json_decode($holiday->employment_type_json, true) ?? []));
                        if ($hasDep && $hasDes && $hasEmp) {
                            $status = 'Holiday';
                        }
                    }
                    
                    foreach ($dayAttendances as $att) {
                        $clockInTime = \Carbon\Carbon::parse($att->clock_in_time)->timezone(company()->timezone);
                        if (!is_null($att->clock_out_time)) {
                            $clockOutTime = \Carbon\Carbon::parse($att->clock_out_time)->timezone(company()->timezone);
                            $total_hours += $clockOutTime->diffInMinutes($clockInTime);
                        }
                        
                        $clock_in_str .= 'Clock In : ' . $clockInTime->format(company()->time_format) . ' Clock Out : ' . ($att->clock_out_time ? $clockOutTime->format(company()->time_format) : '--') . "\n";
                    }
                    
                    $norm['attendance_status'] = $status;
                    $norm['worked_hours'] = \Carbon\CarbonInterval::formatHuman($total_hours);

                    // Add rotation cover visual to Monthly Summary cell comment if applicable
                    $rotation_comment = '';
                    if ($dayShift && $dayShift->rotation_source_schedule_id != null) {
                        $rotation_comment = __('modules.rotationCover') . "\n" . __('modules.coveringFor') . ': ' . $norm['rotation_employee'] . "\nScheduled Shift: " . $norm['scheduled_shift'];
                    }
                    
                    $employeedata[$employee_index]['dates'][$date->day] = [
                        'total_hours' => $total_hours,
                        'comments' => [
                            'status' => $status,
                            'clock_in' => $clock_in_str,
                            'rotation' => $rotation_comment,
                        ]
                    ];
                }

                $normalizedData[] = $norm;
            }

            $employee_index++;
        }

        return [
            'employeedata' => collect($employeedata),
            'normalizedData' => collect($normalizedData)
        ];
    }
}
