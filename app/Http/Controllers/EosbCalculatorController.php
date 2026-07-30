<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\Payroll\EosbService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EosbCalculatorController extends Controller
{
    public function index(Request $request, EosbService $eosb): View
    {
        $request->validate([
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'end_date' => ['nullable', 'date'],
            'reason' => ['nullable', 'in:'.EosbService::REASON_TERMINATION.','.EosbService::REASON_RESIGNATION],
        ]);

        $employee = $request->filled('employee_id')
            ? Employee::with('company')->find($request->integer('employee_id'))
            : null;

        $reason = $request->string('reason')->toString() ?: EosbService::REASON_TERMINATION;
        $result = null;

        if ($employee) {
            $end = $request->filled('end_date')
                ? Carbon::parse($request->string('end_date')->toString())
                : Carbon::today();
            $result = $eosb->forEmployee($employee, $end, $reason);
        }

        return view('eosb.calculator', [
            'result' => $result,
            'selectedEmployee' => $employee,
            'reason' => $reason,
            'employees' => Employee::orderBy('name_en')->get(['id', 'name_ar', 'name_en', 'employee_code', 'company_id', 'start_date']),
            'endDate' => $request->string('end_date')->toString() ?: Carbon::today()->format('Y-m-d'),
        ]);
    }
}
