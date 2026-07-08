<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyRequest;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $companies = Company::query()
            ->withCount('branches')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search');
                $query->where(function ($query) use ($search): void {
                    $query->where('name_ar', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->boolean('status')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('companies.index', compact('companies'));
    }

    public function show(Company $company): View
    {
        $company->loadCount('branches');
        $company->load(['branches' => fn ($q) => $q->orderBy('name_en')]);

        return view('companies.show', compact('company'));
    }

    public function create(): View
    {
        return view('companies.form', ['company' => new Company()]);
    }

    public function store(CompanyRequest $request): RedirectResponse
    {
        Company::create($this->payload($request));

        return redirect()->route('companies.index')->with('status', __('app.saved_successfully'));
    }

    public function edit(Company $company): View
    {
        return view('companies.form', compact('company'));
    }

    public function update(CompanyRequest $request, Company $company): RedirectResponse
    {
        $company->update($this->payload($request));

        return redirect()->route('companies.index')->with('status', __('app.saved_successfully'));
    }

    public function destroy(Company $company): RedirectResponse
    {
        $company->delete();

        return redirect()->route('companies.index')->with('status', __('app.deleted_successfully'));
    }

    private function payload(CompanyRequest $request): array
    {
        return $request->safe()->merge([
            'is_active' => $request->boolean('is_active'),
        ])->all();
    }
}
