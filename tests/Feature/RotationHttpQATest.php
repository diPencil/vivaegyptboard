<?php
namespace Tests\Feature;

use App\Models\User;
use App\Models\EmployeeShift;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RotationHttpQATest extends TestCase
{
    // Use DatabaseTransactions so we don't pollute the db
    use DatabaseTransactions;

    public function testRotationPostRequest()
    {
        // 1. Setup Data
        config(['database.connections.mysql.database' => 'vivaboard_rotation_qa']);
        config(['mail.default' => 'log']);
        
        $u1 = User::first();
        $u2 = User::where('id', '!=', $u1->id)->where('company_id', $u1->company_id)->first();
        $s = EmployeeShift::where('shift_name', '!=', 'Day Off')->first();
        $dayOff = EmployeeShift::where('shift_name', 'Day Off')->first();

        \DB::table('employee_shift_schedules')->truncate();

        // 2. Auth Context
        $this->actingAs($u1);

        // 3. Post Data
        $data = [
            'shift_date' => '2026-07-29',
            'user_id' => $u1->id,
            'status_type' => 'rotation_day_off',
            'replacement_user_id' => $u2->id,
            'replacement_shift_id' => $s->id,
            'employee_shift_id' => $dayOff->id,
            'reason' => '',
            'approved_by' => '',
            'half_day_period' => 'first_half'
        ];

        // 4. Send HTTP Request
        $response = $this->post('/account/shifts', $data);

        // 5. Assertions and Exception Printing
        if ($response->status() == 500) {
            echo "EXCEPTION: " . $response->exception->getMessage() . "\n";
            echo "CLASS: " . get_class($response->exception) . "\n";
            echo "FILE: " . $response->exception->getFile() . "\n";
            echo "LINE: " . $response->exception->getLine() . "\n";
            echo "TRACE: " . $response->exception->getTraceAsString() . "\n";
        }

        $response->assertStatus(200);
    }
}
