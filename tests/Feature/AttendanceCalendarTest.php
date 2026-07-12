<?php
namespace Tests\Feature;

use App\Models\AttendanceDailySummary;
use App\Models\AttendanceHoliday;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeLeaveRequest;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AttendanceCalendarTest extends TestCase
{
    use RefreshDatabase;

    private function employee(): Employee
    {
        $company=Company::create(['name_ar'=>'شركة','name_en'=>'Company','code'=>'CAL','is_active'=>true]);
        return Employee::create(['company_id'=>$company->id,'name_ar'=>'موظف','name_en'=>'Employee','employee_code'=>'CAL-1','hr_employee_id'=>'HR-CAL-1','national_id'=>'1234567892','nationality'=>'SA','saudi_non_saudi'=>'saudi','passport_id'=>'CAL-P','status'=>'active']);
    }

    public function test_holiday_and_approved_leave_are_not_counted_as_absence(): void
    {
        $user=User::factory()->create(['is_active'=>true]);
        $employee=$this->employee();
        AttendanceHoliday::create(['company_id'=>$employee->company_id,'name_ar'=>'عطلة','name_en'=>'Holiday','holiday_date'=>'2026-06-01','is_active'=>true,'is_paid'=>true]);
        EmployeeLeaveRequest::create(['employee_id'=>$employee->id,'leave_type'=>'annual','start_date'=>'2026-06-02','end_date'=>'2026-06-02','status'=>'approved','requested_by'=>$user->id]);

        $response=$this->actingAs($user)->get(route('attendance.report',['date_from'=>'2026-06-01','date_to'=>'2026-06-03']));
        $row=collect($response->viewData('rows'))->firstWhere('employee.id',$employee->id);
        $this->assertSame(1,$row['holiday']);
        $this->assertSame(1,$row['leave']);
        $this->assertSame(1,$row['absent']);
    }

    public function test_two_period_schedule_requires_and_totals_both_sessions(): void
    {
        $user=User::factory()->create(['is_active'=>true]);
        $employee=$this->employee();
        $schedule=(string)Str::uuid();
        $first=Shift::create(['schedule_id'=>$schedule,'shift_number'=>1,'schedule_name_ar'=>'دوامين','name_ar'=>'أولى '.$schedule,'name_en'=>'First '.$schedule,'start_time'=>'08:00','end_time'=>'12:00','is_active'=>true]);
        Shift::create(['schedule_id'=>$schedule,'shift_number'=>2,'schedule_name_ar'=>'دوامين','name_ar'=>'ثانية '.$schedule,'name_en'=>'Second '.$schedule,'start_time'=>'13:00','end_time'=>'17:00','is_active'=>true]);
        $employee->update(['shift_id'=>$first->id]);

        foreach([['08:00','in'],['12:00','out']] as [$time,$type]) $this->actingAs($user)->post(route('attendance.store'),['employee_id'=>$employee->id,'punch_at'=>"2026-07-20T{$time}",'punch_type'=>$type])->assertRedirect(route('attendance.index'));
        $summary=AttendanceDailySummary::firstOrFail();
        $this->assertContains('missing_period',$summary->exception_codes);
        $this->assertSame(480,$summary->scheduled_minutes);

        foreach([['13:00','in'],['17:00','out']] as [$time,$type]) $this->actingAs($user)->post(route('attendance.store'),['employee_id'=>$employee->id,'punch_at'=>"2026-07-20T{$time}",'punch_type'=>$type]);
        $summary->refresh();
        $this->assertFalse($summary->has_exception);
        $this->assertSame(480,$summary->worked_minutes);
    }

    public function test_holiday_and_leave_pages_load(): void
    {
        $user=User::factory()->create(['is_active'=>true]);
        $this->actingAs($user)->get(route('attendance.holidays.index'))->assertOk();
        $this->actingAs($user)->get(route('attendance.leaves.index'))->assertOk();
    }

    public function test_calendar_changes_materialize_daily_summaries(): void
    {
        $user=User::factory()->create(['is_active'=>true]);
        $employee=$this->employee();

        $this->actingAs($user)->post(route('attendance.holidays.store'),['name_ar'=>'عطلة','name_en'=>'Holiday','holiday_date'=>'2026-08-01','company_id'=>$employee->company_id,'is_paid'=>'1','is_active'=>'1'])->assertRedirect();
        $this->assertDatabaseHas('attendance_daily_summaries',['employee_id'=>$employee->id,'status'=>'holiday']);

        $this->actingAs($user)->post(route('attendance.leaves.store'),['employee_id'=>$employee->id,'leave_type'=>'annual','start_date'=>'2026-08-02','end_date'=>'2026-08-03','reason'=>'Annual leave'])->assertRedirect();
        $leave=EmployeeLeaveRequest::latest('id')->firstOrFail();
        $this->actingAs($user)->put(route('attendance.leaves.approve',$leave),['review_notes'=>'Approved'])->assertRedirect();
        $this->assertSame(2,AttendanceDailySummary::where('employee_id',$employee->id)->where('status','leave')->count());
    }

    public function test_overlapping_leave_is_rejected_and_schedule_is_one_employee_option(): void
    {
        $user=User::factory()->create(['is_active'=>true]);
        $employee=$this->employee();
        EmployeeLeaveRequest::create(['employee_id'=>$employee->id,'leave_type'=>'annual','start_date'=>'2026-09-01','end_date'=>'2026-09-05','status'=>'approved','requested_by'=>$user->id]);
        $this->actingAs($user)->post(route('attendance.leaves.store'),['employee_id'=>$employee->id,'leave_type'=>'sick','start_date'=>'2026-09-03','end_date'=>'2026-09-06'])->assertSessionHasErrors('start_date');

        $schedule=(string)Str::uuid();
        $first=Shift::create(['schedule_id'=>$schedule,'shift_number'=>1,'schedule_name_ar'=>'دوامين','name_ar'=>'أولى '.$schedule,'name_en'=>'First '.$schedule,'start_time'=>'08:00','end_time'=>'12:00','is_active'=>true]);
        Shift::create(['schedule_id'=>$schedule,'shift_number'=>2,'schedule_name_ar'=>'دوامين','name_ar'=>'ثانية '.$schedule,'name_en'=>'Second '.$schedule,'start_time'=>'13:00','end_time'=>'17:00','is_active'=>true]);
        $employee->update(['shift_id'=>$first->id]);
        $response=$this->actingAs($user)->get(route('employees.edit',$employee));
        $this->assertCount(1,$response->viewData('shifts'));
    }
}
