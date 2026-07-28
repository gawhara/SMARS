<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LatencyPolicy;
use App\Services\Attendance\LatencyCalculatorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LatencyCalculatorController extends Controller
{
    public function index(Request $request, LatencyCalculatorService $calculator): View
    {
        $request->validate([
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'policy_id' => ['nullable', 'integer', 'exists:latency_policies,id'],
        ]);

        $result = null;
        $employee = $request->filled('employee_id')
            ? Employee::with(['company', 'latencyPolicy'])->find($request->integer('employee_id'))
            : null;

        if ($employee) {
            $from = $request->filled('date_from')
                ? Carbon::parse($request->string('date_from')->toString())
                : Carbon::now()->startOfMonth();
            $to = $request->filled('date_to')
                ? Carbon::parse($request->string('date_to')->toString())
                : Carbon::now();

            // An explicit policy override, else the employee's effective policy.
            $policy = $request->filled('policy_id')
                ? LatencyPolicy::find($request->integer('policy_id'))
                : null;

            $result = $calculator->calculate($employee, $from, $to, $policy);
        }

        return view('latency.calculator', [
            'result' => $result,
            'selectedEmployee' => $employee,
            'employees' => Employee::orderBy('name_en')->get(['id', 'name_ar', 'name_en', 'employee_code', 'company_id', 'latency_policy_id']),
            'policies' => LatencyPolicy::active()->orderBy('name')->get(),
            'from' => $request->string('date_from')->toString() ?: Carbon::now()->startOfMonth()->format('Y-m-d'),
            'to' => $request->string('date_to')->toString() ?: Carbon::now()->format('Y-m-d'),
        ]);
    }
}
