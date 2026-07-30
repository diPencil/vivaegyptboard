<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\EmployeeShift;
use App\Models\EmployeeShiftSchedule;
use App\Models\Company;
use App\Models\Leave;
use App\Models\LeaveType;
use Carbon\Carbon;

class ShiftRotationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Setup initial data if needed or rely on factories.
        // Assuming there's a basic seed or we create manually.
        $this->company = Company::factory()->create();
        $this->admin = User::factory()->create(['company_id' => $this->company->id]);
        $this->originalEmployee = User::factory()->create(['company_id' => $this->company->id, 'status' => 'active']);
        $this->replacementEmployee = User::factory()->create(['company_id' => $this->company->id, 'status' => 'active']);
        $this->dayOffShift = EmployeeShift::factory()->create(['company_id' => $this->company->id, 'shift_name' => 'Day Off']);
        $this->workingShift = EmployeeShift::factory()->create(['company_id' => $this->company->id, 'shift_name' => 'General']);
        
        // Login as admin
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
            'remarks' => 'auto_coverage',
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
            'remarks' => 'reused_day_off_' . $this->dayOffShift->id,
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
            'remarks' => null,
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
