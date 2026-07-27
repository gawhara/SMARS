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
        [$from, $to] = $this->resolveRange($request);
        $companies = Company::orderBy('name_en')->get();
        $company = $request->filled('company_id')
            ? $companies->firstWhere('id', $request->integer('company_id'))
            : $companies->first();

        $rows = $company ? $this->report->forCompany($company, $from, $to) : collect();

        return view('payroll.deductions.index', [
            'companies' => $companies,
            'company' => $company,
            'from' => $from,
            'to' => $to,
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
        [$from, $to] = $this->resolveRange($request);

        $holidays = AttendanceHoliday::where('is_active', true)->whereBetween('holiday_date', [$from, $to])->get();
        $leaves = EmployeeLeaveRequest::where('status', 'approved')
            ->where('employee_id', $employee->id)
            ->whereDate('start_date', '<=', $to)->whereDate('end_date', '>=', $from)
            ->get();

        return view('payroll.deductions.employee', [
            'from' => $from,
            'to' => $to,
            'report' => $this->report->forEmployee($employee, $from, $to, $holidays, $leaves),
            'employee' => $employee,
        ]);
    }

    /**
     * Resolve the pay period from date_from/date_to (any custom range, e.g. the
     * 22nd to the 22nd), falling back to the current calendar month.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveRange(Request $request): array
    {
        $from = $this->date($request->input('date_from'), Carbon::now()->startOfMonth());
        $to = $this->date($request->input('date_to'), Carbon::now()->endOfMonth());

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from->startOfDay(), $to->endOfDay()];
    }

    private function date(?string $value, Carbon $default): Carbon
    {
        try {
            return $value ? Carbon::parse($value) : $default->copy();
        } catch (\Throwable) {
            return $default->copy();
        }
    }
}
