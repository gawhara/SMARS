<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\Payroll\OvertimeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OvertimeCalculatorController extends Controller
{
    public function index(Request $request, OvertimeService $overtime): View
    {
        $request->validate([
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'hours' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'multiplier' => ['nullable', 'numeric', 'min:1', 'max:5'],
        ]);

        $employee = $request->filled('employee_id')
            ? Employee::with('company')->find($request->integer('employee_id'))
            : null;

        $result = null;
        if ($employee) {
            $from = $request->filled('date_from')
                ? Carbon::parse($request->string('date_from')->toString())
                : Carbon::now()->startOfMonth();
            $to = $request->filled('date_to')
                ? Carbon::parse($request->string('date_to')->toString())
                : Carbon::now();

            $result = $overtime->forEmployee(
                $employee,
                $from,
                $to,
                $request->filled('hours') ? (float) $request->input('hours') : null,
                $request->filled('multiplier') ? (float) $request->input('multiplier') : null,
            );
        }

        return view('overtime.calculator', [
            'result' => $result,
            'selectedEmployee' => $employee,
            'employees' => Employee::orderBy('name_en')->get(['id', 'name_ar', 'name_en', 'employee_code', 'company_id']),
            'from' => $request->string('date_from')->toString() ?: Carbon::now()->startOfMonth()->format('Y-m-d'),
            'to' => $request->string('date_to')->toString() ?: Carbon::now()->format('Y-m-d'),
            'defaultMultiplier' => $overtime->defaultMultiplier(),
        ]);
    }
}
