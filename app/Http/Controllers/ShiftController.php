<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShiftRequest;
use App\Models\Shift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShiftController extends Controller
{
    public function index(Request $request): View
    {
        $shifts = Shift::query()
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->boolean('status')))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search');
                $query->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('shifts.index', compact('shifts'));
    }

    public function create(): View
    {
        return view('shifts.form', ['shift' => new Shift(['is_active' => true])]);
    }

    public function store(ShiftRequest $request): RedirectResponse
    {
        Shift::create($this->payload($request));

        return redirect()->route('shifts.index')->with('status', __('app.saved_successfully'));
    }

    public function edit(Shift $shift): View
    {
        return view('shifts.form', compact('shift'));
    }

    public function update(ShiftRequest $request, Shift $shift): RedirectResponse
    {
        $shift->update($this->payload($request));

        return redirect()->route('shifts.index')->with('status', __('app.saved_successfully'));
    }

    public function destroy(Shift $shift): RedirectResponse
    {
        $shift->delete();

        return redirect()->route('shifts.index')->with('status', __('app.deleted_successfully'));
    }

    private function payload(ShiftRequest $request): array
    {
        return $request->safe()->merge([
            'is_active' => $request->boolean('is_active'),
        ])->all();
    }
}
