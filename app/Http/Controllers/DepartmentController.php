<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepartmentRequest;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(Request $request): View
    {
        $departments = Department::query()
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->boolean('status')))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search');
                $query->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('departments.index', compact('departments'));
    }

    public function create(): View
    {
        return view('departments.form', ['department' => new Department(['is_active' => true])]);
    }

    public function store(DepartmentRequest $request): RedirectResponse
    {
        Department::create($this->payload($request));

        return redirect()->route('departments.index')->with('status', __('app.saved_successfully'));
    }

    public function edit(Department $department): View
    {
        return view('departments.form', compact('department'));
    }

    public function update(DepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->update($this->payload($request));

        return redirect()->route('departments.index')->with('status', __('app.saved_successfully'));
    }

    public function destroy(Department $department): RedirectResponse
    {
        $department->delete();

        return redirect()->route('departments.index')->with('status', __('app.deleted_successfully'));
    }

    private function payload(DepartmentRequest $request): array
    {
        return $request->safe()->merge([
            'is_active' => $request->boolean('is_active'),
        ])->all();
    }
}
