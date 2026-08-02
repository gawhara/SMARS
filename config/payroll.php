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

    /*
    |--------------------------------------------------------------------------
    | GOSI (General Organization for Social Insurance) contributions
    |--------------------------------------------------------------------------
    | Contribution wage = GOSI-registered basic + GOSI housing, clamped to the
    | statutory floor/ceiling, then multiplied by the nationality-class rate.
    | Standard current rates; overridable via env for the 2024 phased reform.
    */
    'gosi' => [
        'wage_floor' => (float) env('GOSI_WAGE_FLOOR', 1500),
        'wage_ceiling' => (float) env('GOSI_WAGE_CEILING', 45000),

        // Saudi nationals: 9% annuities + 0.75% SANED (+2% occupational hazard on employer).
        'saudi' => [
            'employee_rate' => (float) env('GOSI_SAUDI_EMPLOYEE_RATE', 0.0975),
            'employer_rate' => (float) env('GOSI_SAUDI_EMPLOYER_RATE', 0.1175),
        ],

        // Non-Saudi: occupational-hazard branch only, employer-paid.
        'non_saudi' => [
            'employee_rate' => (float) env('GOSI_NON_SAUDI_EMPLOYEE_RATE', 0.0),
            'employer_rate' => (float) env('GOSI_NON_SAUDI_EMPLOYER_RATE', 0.02),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | End-of-Service Benefit (مكافأة نهاية الخدمة) — Labor Law arts. 84–85
    |--------------------------------------------------------------------------
    | Wage = sum of these employee columns (the full last wage). Award is
    | ½ month per year for the first N years, 1 month per year thereafter,
    | prorated for partial years, then scaled for resignation.
    */
    'eosb' => [
        'wage_components' => ['basic_salary', 'housing_allowance', 'transportation_allowance', 'other_allowances'],
        'first_years_threshold' => 5,
        'first_years_rate' => 0.5,
        'later_years_rate' => 1.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Overtime (العمل الإضافي) — Labor Law art. 107
    |--------------------------------------------------------------------------
    | Overtime is paid at the hourly wage plus 50% (i.e. ×1.5).
    */
    'overtime' => [
        'rate_multiplier' => (float) env('OVERTIME_MULTIPLIER', 1.5),
    ],

];
