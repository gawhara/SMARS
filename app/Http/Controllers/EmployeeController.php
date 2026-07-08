<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeRequest;
use App\Models\Bank;
use App\Models\Company;
use App\Models\Country;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Shift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $employees = Employee::query()
            ->with(['company', 'orgBranch', 'department', 'position'])
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->integer('company_id')))
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->integer('department_id')))
            ->when($request->filled('saudi_non_saudi'), fn ($q) => $q->where('saudi_non_saudi', $request->string('saudi_non_saudi')))
            ->when($request->filled('employment_status'), fn ($q) => $q->where('employment_status', $request->string('employment_status')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($q) use ($request): void {
                $search = trim((string) $request->string('search'));
                $q->where(function ($q) use ($search): void {
                    foreach (['name_ar', 'name_en', 'hr_employee_id', 'financial_employee_id', 'employee_code', 'national_id', 'phone', 'email', 'passport_id'] as $column) {
                        $q->orWhere($column, 'like', "%{$search}%");
                    }
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('employees.index', [
            'employees' => $employees,
            'companies' => Company::orderBy('name_en')->get(),
            'departments' => Department::orderBy('name_en')->get(),
        ]);
    }

    public function create(): View
    {
        return view('employees.form', $this->formData(new Employee(['status' => 'active', 'employment_status' => 'active'])));
    }

    public function store(EmployeeRequest $request): RedirectResponse
    {
        $employee = Employee::create($request->validated());

        return redirect()->route('employees.show', $employee)->with('status', __('app.saved_successfully'));
    }

    public function show(Employee $employee): View
    {
        $employee->load(['company', 'orgBranch', 'department', 'position', 'shift', 'creator', 'updater']);

        return view('employees.show', [
            'employee' => $employee,
            'country' => $employee->nationality
                ? Country::where('iso2', $employee->nationality)->first()
                : null,
            'bank' => $employee->bank
                ? Bank::where('code', $employee->bank)->first()
                : null,
        ]);
    }

    public function edit(Employee $employee): View
    {
        return view('employees.form', $this->formData($employee));
    }

    public function update(EmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update($request->validated());

        return redirect()->route('employees.show', $employee)->with('status', __('app.saved_successfully'));
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        return redirect()->route('employees.index')->with('status', __('app.deleted_successfully'));
    }

    public function restore(int $employee): RedirectResponse
    {
        Employee::onlyTrashed()->findOrFail($employee)->restore();

        return redirect()->route('employees.index')->with('status', __('app.restored_successfully'));
    }

    /**
     * Shared data for create/edit forms.
     */
    private function formData(Employee $employee): array
    {
        return [
            'employee' => $employee,
            'companies' => Company::where('is_active', true)->orderBy('name_en')->get(),
            'branches' => \App\Models\Branch::where('is_active', true)->orderBy('name_en')->get(['id', 'company_id', 'name_ar', 'name_en']),
            'departments' => Department::where('is_active', true)->orderBy('name_en')->get(),
            'positions' => Position::where('is_active', true)->orderBy('name_en')->get(),
            'shifts' => Shift::where('is_active', true)->orderBy('name_en')->get(),
            'banks' => Bank::where('is_active', true)->orderBy('name_en')->get(),
            'countries' => Country::ordered()->get(),
        ];
    }
}
