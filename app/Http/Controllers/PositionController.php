<?php

namespace App\Http\Controllers;

use App\Http\Requests\PositionRequest;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PositionController extends Controller
{
    public function index(Request $request): View
    {
        $positions = Position::query()
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->boolean('status')))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search');
                $query->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('positions.index', compact('positions'));
    }

    public function create(): View
    {
        return view('positions.form', ['position' => new Position(['is_active' => true])]);
    }

    public function store(PositionRequest $request): RedirectResponse
    {
        Position::create($this->payload($request));

        return redirect()->route('positions.index')->with('status', __('app.saved_successfully'));
    }

    public function edit(Position $position): View
    {
        return view('positions.form', compact('position'));
    }

    public function update(PositionRequest $request, Position $position): RedirectResponse
    {
        $position->update($this->payload($request));

        return redirect()->route('positions.index')->with('status', __('app.saved_successfully'));
    }

    public function destroy(Position $position): RedirectResponse
    {
        $position->delete();

        return redirect()->route('positions.index')->with('status', __('app.deleted_successfully'));
    }

    private function payload(PositionRequest $request): array
    {
        return $request->safe()->merge([
            'is_active' => $request->boolean('is_active'),
        ])->all();
    }
}
