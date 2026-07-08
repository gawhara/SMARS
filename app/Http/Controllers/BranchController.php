<?php

namespace App\Http\Controllers;

use App\Http\Requests\BranchRequest;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(Request $request): View
    {
        $branches = Branch::query()
            ->with('company')
            ->when($request->filled('company_id'), fn ($query) => $query->where('company_id', $request->integer('company_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->boolean('status')))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search');
                $query->where(function ($query) use ($search): void {
                    $query->where('name_ar', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('branches.index', [
            'branches' => $branches,
            'companies' => Company::withCount('branches')->orderBy('name_en')->get(),
        ]);
    }

    public function create(): View
    {
        return view('branches.form', [
            'branch' => new Branch(['is_active' => true]),
            'companies' => Company::where('is_active', true)->orderBy('name_en')->get(),
        ]);
    }

    public function store(BranchRequest $request): RedirectResponse
    {
        Branch::create($this->payload($request));

        return redirect()->route('branches.index')->with('status', __('app.saved_successfully'));
    }

    public function edit(Branch $branch): View
    {
        return view('branches.form', [
            'branch' => $branch,
            'companies' => Company::where('is_active', true)->orderBy('name_en')->get(),
        ]);
    }

    public function update(BranchRequest $request, Branch $branch): RedirectResponse
    {
        $branch->update($this->payload($request));

        return redirect()->route('branches.index')->with('status', __('app.saved_successfully'));
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $branch->delete();

        return redirect()->route('branches.index')->with('status', __('app.deleted_successfully'));
    }

    private function payload(BranchRequest $request): array
    {
        return $request->safe()->merge([
            'is_active' => $request->boolean('is_active'),
        ])->all();
    }
}
