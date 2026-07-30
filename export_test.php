<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Exports\AttendanceExport;
use App\Exports\AttendanceByMemberExport;
use App\Exports\ShiftScheduleExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Carbon;

$year = 2026;
$month = '07';
$id = 'all'; // or a specific user ID
$department = 'all';
$designation = 'all';
$startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
$endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

// Get an active user to test Member Export
$user = \App\Models\User::where('status', 'active')->first();
$userId = $user->id;
auth()->login($user);

// 1. Attendance Export
try {
    Excel::store(new AttendanceExport($year, $month, $id, $department, $designation, $startDate, $endDate), 'attendance_export_qa.xlsx', 'local');
    echo "AttendanceExport generated successfully.\n";
} catch (\Exception $e) {
    echo "Error generating AttendanceExport: " . $e->getMessage() . "\n";
}

// 2. Attendance By Member Export
try {
    Excel::store(new AttendanceByMemberExport($year, $month, $userId, $user->name, $startDate, $endDate), 'attendance_by_member_qa.xlsx', 'local');
    echo "AttendanceByMemberExport generated successfully.\n";
} catch (\Exception $e) {
    echo "Error generating AttendanceByMemberExport: " . $e->getMessage() . "\n";
}

// 3. Shift Schedule Export
try {
    Excel::store(new ShiftScheduleExport($year, $month, $id, $department, $startDate, $endDate, 'month'), 'shift_schedule_qa.xlsx', 'local');
    echo "ShiftScheduleExport generated successfully.\n";
} catch (\Exception $e) {
    echo "Error generating ShiftScheduleExport: " . $e->getMessage() . "\n";
}

echo "Done.\n";
