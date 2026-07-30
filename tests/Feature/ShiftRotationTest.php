<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;
use App\Models\EmployeeShift;
use App\Models\EmployeeShiftSchedule;
use App\Models\Company;
use App\Models\Leave;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ShiftRotationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::firstOrCreate(['id' => 1], ['company_name' => 'Test']);
        $this->admin = User::firstOrCreate(['id' => 1], ['company_id' => $this->company->id, 'name' => 'Admin', 'email' => 'admin@test.com', 'password' => '123']);
        $this->originalEmployee = User::firstOrCreate(['id' => 2], ['company_id' => $this->company->id, 'name' => 'O', 'email' => 'o@test.com', 'password' => '123', 'status' => 'active']);
        $this->replacementEmployee = User::firstOrCreate(['id' => 3], ['company_id' => $this->company->id, 'name' => 'R', 'email' => 'r@test.com', 'password' => '123', 'status' => 'active']);
        $this->crossCompanyEmployee = User::firstOrCreate(['id' => 4], ['company_id' => 2, 'name' => 'X', 'email' => 'x@test.com', 'password' => '123', 'status' => 'active']);
        $this->dayOffShift = EmployeeShift::firstOrCreate(['id' => 1], ['company_id' => $this->company->id, 'shift_name' => 'Day Off', 'shift_short_code' => 'DO', 'color' => '#000', 'office_start_time' => '09:00', 'office_end_time' => '17:00']);
        $this->workingShift = EmployeeShift::firstOrCreate(['id' => 2], ['company_id' => $this->company->id, 'shift_name' => 'General', 'shift_short_code' => 'GE', 'color' => '#000', 'office_start_time' => '09:00', 'office_end_time' => '17:00']);
        $this->crossCompanyShift = EmployeeShift::firstOrCreate(['id' => 3], ['company_id' => 2, 'shift_name' => 'X Shift', 'shift_short_code' => 'XS', 'color' => '#000', 'office_start_time' => '09:00', 'office_end_time' => '17:00']);
        
        $this->actingAs($this->admin);
    }

    public function test_valid_rotation_with_employee_who_has_no_roster()
    {
        $date = Carbon::now()->addDays(2)->format('Y-m-d');

        $response = $this->post(route('shifts.store'), [
            'user_id' => $this->originalEmployee->id,
            'shift_date' => $date,
            'status_type' => 'rotation_day_off',
            'replacement_user_id' => $this->replacementEmployee->id,
            'replacement_shift_id' => $this->workingShift->id,
        ]);

        $response->assertStatus(200);

        // Assert original has rotation_day_off
        $this->assertDatabaseHas('employee_shift_schedules', [
            'user_id' => $this->originalEmployee->id,
            'date' => $date,
            'status_type' => 'rotation_day_off',
            'replacement_user_id' => $this->replacementEmployee->id,
            'replacement_shift_id' => $this->workingShift->id,
        ]);

        $originalShift = EmployeeShiftSchedule::where('user_id', $this->originalEmployee->id)->first();

        // Assert replacement has auto coverage
        $this->assertDatabaseHas('employee_shift_schedules', [
            'user_id' => $this->replacementEmployee->id,
            'date' => $date,
            'status_type' => 'working_shift',
            'rotation_source_schedule_id' => $originalShift->id,
            'rotation_coverage_mode' => 'auto_generated',
        ]);
    }

    public function test_valid_rotation_using_existing_day_off_record()
    {
        $date = Carbon::now()->addDays(2)->format('Y-m-d');

        EmployeeShiftSchedule::create([
            'user_id' => $this->replacementEmployee->id,
            'date' => $date,
            'status_type' => 'day_off',
            'employee_shift_id' => $this->dayOffShift->id,
            'company_id' => $this->company->id,
            'added_by' => $this->admin->id,
            'last_updated_by' => $this->admin->id,
        ]);

        $response = $this->post(route('shifts.store'), [
            'user_id' => $this->originalEmployee->id,
            'shift_date' => $date,
            'status_type' => 'rotation_day_off',
            'replacement_user_id' => $this->replacementEmployee->id,
            'replacement_shift_id' => $this->workingShift->id,
        ]);

        $response->assertStatus(200);

        $originalShift = EmployeeShiftSchedule::where('user_id', $this->originalEmployee->id)->first();

        // Assert replacement reused Day Off
        $this->assertDatabaseHas('employee_shift_schedules', [
            'user_id' => $this->replacementEmployee->id,
            'date' => $date,
            'status_type' => 'working_shift',
            'rotation_source_schedule_id' => $originalShift->id,
            'rotation_coverage_mode' => 'reused_day_off', 'rotation_previous_shift_id' => $this->dayOffShift->id,
        ]);
    }

    public function test_cancellation_restores_day_off()
    {
        $date = Carbon::now()->addDays(2)->format('Y-m-d');

        EmployeeShiftSchedule::create([
            'user_id' => $this->replacementEmployee->id,
            'date' => $date,
            'status_type' => 'day_off',
            'employee_shift_id' => $this->dayOffShift->id,
            'company_id' => $this->company->id,
            'added_by' => $this->admin->id,
            'last_updated_by' => $this->admin->id,
        ]);

        // Assign rotation
        $this->post(route('shifts.store'), [
            'user_id' => $this->originalEmployee->id,
            'shift_date' => $date,
            'status_type' => 'rotation_day_off',
            'replacement_user_id' => $this->replacementEmployee->id,
            'replacement_shift_id' => $this->workingShift->id,
        ]);

        $originalShift = EmployeeShiftSchedule::where('user_id', $this->originalEmployee->id)->first();

        // Cancel rotation
        $response = $this->put(route('shifts.update', $originalShift->id), [
            'status_type' => 'working_shift',
            'employee_shift_id' => $this->workingShift->id,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('employee_shift_schedules', [
            'user_id' => $this->replacementEmployee->id,
            'date' => $date,
            'status_type' => 'day_off',
            'rotation_source_schedule_id' => null,
            'rotation_coverage_mode' => null, 'rotation_previous_shift_id' => null,
            'employee_shift_id' => $this->dayOffShift->id,
        ]);
    }

    public function test_cancellation_removes_automatically_generated_coverage_record()
    {
        $date = Carbon::now()->addDays(2)->format('Y-m-d');

        $this->post(route('shifts.store'), [
            'user_id' => $this->originalEmployee->id,
            'shift_date' => $date,
            'status_type' => 'rotation_day_off',
            'replacement_user_id' => $this->replacementEmployee->id,
            'replacement_shift_id' => $this->workingShift->id,
        ]);

        $originalShift = EmployeeShiftSchedule::where('user_id', $this->originalEmployee->id)->first();

        $this->put(route('shifts.update', $originalShift->id), [
            'status_type' => 'working_shift',
            'employee_shift_id' => $this->workingShift->id,
        ]);

        $this->assertDatabaseMissing('employee_shift_schedules', [
            'user_id' => $this->replacementEmployee->id,
            'date' => $date,
        ]);
    }

    public function test_replacement_has_approved_leave()
    {
        $date = Carbon::now()->addDays(2)->format('Y-m-d');
        $leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);
        Leave::factory()->create([
            'user_id' => $this->replacementEmployee->id,
            'leave_date' => $date,
            'status' => 'approved',
            'leave_type_id' => $leaveType->id,
        ]);

        $response = $this->post(route('shifts.store'), [
            'user_id' => $this->originalEmployee->id,
            'shift_date' => $date,
            'status_type' => 'rotation_day_off',
            'replacement_user_id' => $this->replacementEmployee->id,
            'replacement_shift_id' => $this->workingShift->id,
        ]);

        // Should return JSON error if ajax or throw validation exception
        $response->assertInvalid(['replacement_user_id' => __('modules.replacementOnApprovedLeave')]);
    }

    public function test_replacement_has_working_shift()
    {
        $date = Carbon::now()->addDays(2)->format('Y-m-d');

        EmployeeShiftSchedule::create([
            'user_id' => $this->replacementEmployee->id,
            'date' => $date,
            'status_type' => 'working_shift',
            'employee_shift_id' => $this->workingShift->id,
            'company_id' => $this->company->id,
            'added_by' => $this->admin->id,
            'last_updated_by' => $this->admin->id,
        ]);

        $response = $this->post(route('shifts.store'), [
            'user_id' => $this->originalEmployee->id,
            'shift_date' => $date,
            'status_type' => 'rotation_day_off',
            'replacement_user_id' => $this->replacementEmployee->id,
            'replacement_shift_id' => $this->workingShift->id,
        ]);

        $response->assertInvalid(['replacement_user_id' => __('modules.replacementHasConflict')]);
    }

    public function test_replacement_is_already_covering_another_employee()
    {
        $date = Carbon::now()->addDays(2)->format('Y-m-d');
        $anotherEmployee = User::factory()->create(['company_id' => $this->company->id, 'status' => 'active']);

        $this->post(route('shifts.store'), [
            'user_id' => $anotherEmployee->id,
            'shift_date' => $date,
            'status_type' => 'rotation_day_off',
            'replacement_user_id' => $this->replacementEmployee->id,
            'replacement_shift_id' => $this->workingShift->id,
        ]);

        $response = $this->post(route('shifts.store'), [
            'user_id' => $this->originalEmployee->id,
            'shift_date' => $date,
            'status_type' => 'rotation_day_off',
            'replacement_user_id' => $this->replacementEmployee->id,
            'replacement_shift_id' => $this->workingShift->id,
        ]);

        $response->assertInvalid(['replacement_user_id' => __('modules.replacementHasConflict')]);
    }

    public function test_duplicate_save_request_does_not_duplicate_coverage()
    {
        $date = Carbon::now()->addDays(2)->format('Y-m-d');

        $this->post(route('shifts.store'), [
            'user_id' => $this->originalEmployee->id,
            'shift_date' => $date,
            'status_type' => 'rotation_day_off',
            'replacement_user_id' => $this->replacementEmployee->id,
            'replacement_shift_id' => $this->workingShift->id,
        ]);

        $originalShift = EmployeeShiftSchedule::where('user_id', $this->originalEmployee->id)->first();

        // Duplicate request (Update)
        $this->put(route('shifts.update', $originalShift->id), [
            'status_type' => 'rotation_day_off',
            'replacement_user_id' => $this->replacementEmployee->id,
            'replacement_shift_id' => $this->workingShift->id,
        ]);

        $count = EmployeeShiftSchedule::where('user_id', $this->replacementEmployee->id)->where('date', $date)->count();
        $this->assertEquals(1, $count);
    }

    public function test_replacement_employee_changed()
    {
        $date = Carbon::now()->addDays(2)->format('Y-m-d');
        $anotherEmployee = User::factory()->create(['company_id' => $this->company->id, 'status' => 'active']);

        $this->post(route('shifts.store'), [
            'user_id' => $this->originalEmployee->id,
            'shift_date' => $date,
            'status_type' => 'rotation_day_off',
            'replacement_user_id' => $this->replacementEmployee->id,
            'replacement_shift_id' => $this->workingShift->id,
        ]);

        $originalShift = EmployeeShiftSchedule::where('user_id', $this->originalEmployee->id)->first();

        // Change replacement employee
        $this->put(route('shifts.update', $originalShift->id), [
            'status_type' => 'rotation_day_off',
            'replacement_user_id' => $anotherEmployee->id,
            'replacement_shift_id' => $this->workingShift->id,
        ]);

        $this->assertDatabaseMissing('employee_shift_schedules', [
            'user_id' => $this->replacementEmployee->id,
            'date' => $date,
        ]);

        $this->assertDatabaseHas('employee_shift_schedules', [
            'user_id' => $anotherEmployee->id,
            'date' => $date,
            'status_type' => 'working_shift',
            'rotation_source_schedule_id' => $originalShift->id,
        ]);
    }

    public function test_replacement_shift_changed()
    {
        $date = Carbon::now()->addDays(2)->format('Y-m-d');
        $anotherShift = EmployeeShift::factory()->create(['company_id' => $this->company->id, 'shift_name' => 'Night']);

        $this->post(route('shifts.store'), [
            'user_id' => $this->originalEmployee->id,
            'shift_date' => $date,
            'status_type' => 'rotation_day_off',
            'replacement_user_id' => $this->replacementEmployee->id,
            'replacement_shift_id' => $this->workingShift->id,
        ]);

        $originalShift = EmployeeShiftSchedule::where('user_id', $this->originalEmployee->id)->first();

        $this->put(route('shifts.update', $originalShift->id), [
            'status_type' => 'rotation_day_off',
            'replacement_user_id' => $this->replacementEmployee->id,
            'replacement_shift_id' => $anotherShift->id,
        ]);

        $this->assertDatabaseHas('employee_shift_schedules', [
            'user_id' => $this->replacementEmployee->id,
            'date' => $date,
            'employee_shift_id' => $anotherShift->id,
        ]);
    }

    public function test_cross_company_employee_rejected()
    {
        $date = Carbon::now()->addDays(2)->format('Y-m-d');
        $otherCompany = Company::factory()->create();
        $otherCompanyEmployee = User::factory()->create(['company_id' => $otherCompany->id, 'status' => 'active']);

        $response = $this->post(route('shifts.store'), [
            'user_id' => $this->originalEmployee->id,
            'shift_date' => $date,
            'status_type' => 'rotation_day_off',
            'replacement_user_id' => $otherCompanyEmployee->id,
            'replacement_shift_id' => $this->workingShift->id,
        ]);

        $response->assertInvalid(['replacement_user_id']);
    }

    public function test_cross_company_shift_rejected()
    {
        $date = Carbon::now()->addDays(2)->format('Y-m-d');
        $otherCompany = Company::factory()->create();
        $otherCompanyShift = EmployeeShift::factory()->create(['company_id' => $otherCompany->id, 'shift_name' => 'Other']);

        $response = $this->post(route('shifts.store'), [
            'user_id' => $this->originalEmployee->id,
            'shift_date' => $date,
            'status_type' => 'rotation_day_off',
            'replacement_user_id' => $this->replacementEmployee->id,
            'replacement_shift_id' => $otherCompanyShift->id,
        ]);

        $response->assertInvalid(['replacement_user_id']); // My generic invalid cross-company error triggers on replacement_user_id
    }

    public function test_original_employee_cannot_replace_themselves()
    {
        $date = Carbon::now()->addDays(2)->format('Y-m-d');

        $response = $this->post(route('shifts.store'), [
            'user_id' => $this->originalEmployee->id,
            'shift_date' => $date,
            'status_type' => 'rotation_day_off',
            'replacement_user_id' => $this->originalEmployee->id,
            'replacement_shift_id' => $this->workingShift->id,
        ]);

        $response->assertInvalid(['replacement_user_id']);
    }

    public function test_bulk_endpoint_rejects_rotation_day_off()
    {
        $response = $this->post(route('shifts.bulk_shift'), [
            'assign_shift_by' => 'month',
            'year' => date('Y'),
            'month' => date('m'),
            'user_id' => [$this->originalEmployee->id],
            'shift' => $this->workingShift->id,
            'status_type' => 'rotation_day_off',
        ]);

        $response->assertInvalid(['status_type']);
    }

    public function test_normal_shift_roster_statuses_still_work()
    {
        $date = Carbon::now()->addDays(2)->format('Y-m-d');

        $response = $this->post(route('shifts.store'), [
            'user_id' => $this->originalEmployee->id,
            'shift_date' => $date,
            'status_type' => 'working_shift',
            'employee_shift_id' => $this->workingShift->id,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('employee_shift_schedules', [
            'user_id' => $this->originalEmployee->id,
            'date' => $date,
            'status_type' => 'working_shift',
            'employee_shift_id' => $this->workingShift->id,
        ]);
    }
}
