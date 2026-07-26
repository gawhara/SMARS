<?php

namespace App\Http\Controllers;

use App\Models\AttendanceHoliday;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeLeaveRequest;
use App\Services\Attendance\PayrollDeductionReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollDeductionController extends Controller
{
    public function __construct(private readonly PayrollDeductionReportService $report)
    {
    }

    public function index(Request $request): View
    {
        $month = $this->resolveMonth($request->input('month'));
        $companies = Company::orderBy('name_en')->get();
        $company = $request->filled('company_id')
            ? $companies->firstWhere('id', $request->integer('company_id'))
            : $companies->first();

        $rows = $company ? $this->report->forCompany($company, $month) : collect();

        return view('payroll.deductions.index', [
            'companies' => $companies,
            'company' => $company,
            'month' => $month,
            'rows' => $rows,
            'totals' => [
                'employees' => $rows->count(),
                'deduction' => round($rows->sum('total_deduction'), 2),
                'reviews' => $rows->sum('review_count'),
            ],
        ]);
    }

    public function employee(Request $request, Employee $employee): View
    {
        $month = $this->resolveMonth($request->input('month'));
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $holidays = AttendanceHoliday::where('is_active', true)->whereBetween('holiday_date', [$start, $end])->get();
        $leaves = EmployeeLeaveRequest::where('status', 'approved')
            ->where('employee_id', $employee->id)
            ->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $start)
            ->get();

        return view('payroll.deductions.employee', [
            'month' => $month,
            'report' => $this->report->forEmployee($employee, $month, $holidays, $leaves),
            'employee' => $employee,
        ]);
    }

    private function resolveMonth(?string $value): Carbon
    {
        try {
            return $value ? Carbon::createFromFormat('Y-m', $value)->startOfMonth() : Carbon::now()->startOfMonth();
        } catch (\Throwable) {
            return Carbon::now()->startOfMonth();
        }
    }
}
