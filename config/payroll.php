<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Attendance-deduction payroll policy
    |--------------------------------------------------------------------------
    */

    // Salary basis for attendance deductions (policy section 26).
    //   gosi  = GOSI-registered salary (basic_salary_gosi + housing_allowance_gosi)
    //   basic = basic_salary only
    //   full  = basic + housing + transport + other allowances
    'salary_basis' => env('PAYROLL_SALARY_BASIS', 'gosi'),

    // Fixed day divisor for the daily salary rate used by absence penalties
    // (policy section 32): daily rate = salary basis ÷ day_divisor.
    'day_divisor' => (int) env('PAYROLL_DAY_DIVISOR', 30),

];
