<?php

namespace App\Services\Attendance;

use App\Models\AttendancePolicy;
use App\Models\Employee;

class AttendancePolicyService
{
    public function forEmployee(Employee $employee): AttendancePolicy
    {
        $policy = AttendancePolicy::where('company_id', $employee->company_id)->where('is_active', true)->first();

        return $policy ?? AttendancePolicy::defaults($employee->company_id);
    }
}
