<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShiftRequest;
use App\Models\Shift;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
                $query->where(function ($query) use ($search): void {
                    $query->where('schedule_name_ar', 'like', "%{$search}%")
                        ->orWhere('name_ar', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->orderBy('shift_number')
            ->get();

        $shiftSchedules = $shifts->groupBy(fn (Shift $shift) => $shift->schedule_id ?: 'legacy-'.$shift->id);

        return view('shifts.index', compact('shiftSchedules'));
    }

    public function create(): View
    {
        return view('shifts.form', [
            'shift' => new Shift(['is_active' => true, 'shift_number' => 1]),
        ]);
    }

    public function store(ShiftRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $scheduleId = (string) Str::uuid();
            Shift::create($this->payload($request, 1, $scheduleId));

            if ($request->input('schedule_mode') === 'double') {
                Shift::create([
                    'schedule_id' => $scheduleId,
                    'shift_number' => 2,
                    'schedule_name_ar' => $request->input('schedule_name_ar'),
                    'name_ar' => 'الوردية 2 '.$scheduleId,
                    'name_en' => 'Shift 2 '.$scheduleId,
                    'start_time' => $request->input('second_start_time'),
                    'end_time' => $request->input('second_end_time'),
                    'is_active' => $request->boolean('is_active'),
                ]);
            }
        });

        return redirect()->route('shifts.index')->with('status', __('app.saved_successfully'));
    }

    public function edit(Shift $shift): View
    {
        return view('shifts.form', compact('shift'));
    }

    public function update(ShiftRequest $request, Shift $shift): RedirectResponse
    {
        $shift->update($this->payload($request, $shift->shift_number, $shift->schedule_id));

        if ($request->filled('schedule_name_ar')) {
            Shift::where('schedule_id', $shift->schedule_id)->update(['schedule_name_ar' => $request->input('schedule_name_ar')]);
        }

        return redirect()->route('shifts.index')->with('status', __('app.saved_successfully'));
    }

    public function destroy(Shift $shift): RedirectResponse
    {
        Shift::where('schedule_id', $shift->schedule_id)->delete();

        return redirect()->route('shifts.index')->with('status', __('app.deleted_successfully'));
    }

    private function payload(ShiftRequest $request, int $number, ?string $scheduleId): array
    {
        return $request->safe()->merge([
            'schedule_id' => $scheduleId,
            'shift_number' => $number,
            'schedule_name_ar' => $request->input('schedule_name_ar', $request->route('shift')?->schedule_name_ar),
            'name_ar' => $request->route('shift')?->name_ar ?? 'الوردية '.$number.' '.$scheduleId,
            'name_en' => $request->route('shift')?->name_en ?? 'Shift '.$number.' '.$scheduleId,
            'is_active' => $request->boolean('is_active'),
        ])->only(['schedule_id', 'shift_number', 'schedule_name_ar', 'name_ar', 'name_en', 'start_time', 'end_time', 'is_active']);
    }
}
