<?php

namespace App\Http\Controllers;

use App\Models\AdministrativePenalty;
use App\Models\Company;
use App\Models\Employee;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdministrativePenaltyController extends Controller
{
    public function index(Request $request): View
    {
        $penalties = AdministrativePenalty::query()
            ->with(['employee.company', 'creator'])
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->integer('employee_id')))
            ->when($request->filled('company_id'), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('company_id', $request->integer('company_id'))))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('penalty_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('penalty_date', '<=', $request->date('date_to')))
            ->orderByDesc('penalty_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('penalties.index', [
            'penalties' => $penalties,
            'companies' => Company::orderBy('name_en')->get(),
            'employees' => Employee::orderBy('name_en')->get(['id', 'name_ar', 'name_en', 'employee_code', 'company_id']),
            'types' => AdministrativePenalty::TYPES,
            'stats' => [
                'active' => AdministrativePenalty::active()->count(),
                'amount' => round((float) AdministrativePenalty::active()->sum('amount'), 2),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'penalty_date' => ['required', 'date'],
            'type' => ['required', Rule::in(AdministrativePenalty::TYPES)],
            'reason' => ['required', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $penalty = AdministrativePenalty::create($validated + ['amount' => $validated['amount'] ?? 0, 'status' => 'active']);
        $penalty->loadMissing('employee');

        AuditLogger::record('penalty.created', $penalty, $penalty->employee?->localizedName(), [
            'type' => $penalty->type,
            'amount' => (float) $penalty->amount,
        ]);

        return back()->with('status', __('app.penalty.saved'));
    }

    public function cancel(Request $request, AdministrativePenalty $penalty): RedirectResponse
    {
        $penalty->update(['status' => 'cancelled']);
        $penalty->loadMissing('employee');

        AuditLogger::record('penalty.cancelled', $penalty, $penalty->employee?->localizedName(), [
            'amount' => (float) $penalty->amount,
        ]);

        return back()->with('status', __('app.penalty.cancelled_notice'));
    }
}
