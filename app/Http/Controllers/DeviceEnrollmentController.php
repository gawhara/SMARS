<?php

namespace App\Http\Controllers;

use App\Models\AttendanceMachine;
use App\Models\DeviceEnrollment;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeviceEnrollmentController extends Controller
{
    public function index(AttendanceMachine $device): View
    {
        $device->load(['enrollments.employee.company']);
        $enrolledIds = $device->enrollments->pluck('employee_id');

        return view('devices.enrollments', [
            'device' => $device,
            'enrollments' => $device->enrollments->sortBy(fn ($e) => $e->employee?->name_en)->values(),
            'available' => Employee::whereNotIn('id', $enrolledIds)->orderBy('name_en')->get(['id', 'name_ar', 'name_en', 'employee_code']),
            'otherDevices' => AttendanceMachine::where('id', '!=', $device->id)->orderBy('device_name')->get(),
        ]);
    }

    public function store(Request $request, AttendanceMachine $device): RedirectResponse
    {
        $validated = $request->validate([
            'employee_ids' => ['required', 'array'],
            'employee_ids.*' => ['integer', Rule::exists('employees', 'id')->whereNull('deleted_at')],
        ]);

        $added = 0;
        foreach (Employee::whereIn('id', $validated['employee_ids'])->get() as $employee) {
            $enrollment = DeviceEnrollment::firstOrCreate(
                ['attendance_machine_id' => $device->id, 'employee_id' => $employee->id],
                ['device_user_id' => $employee->employee_code, 'enrolled_at' => now()],
            );

            if ($enrollment->wasRecentlyCreated) {
                $added++;
            }
        }

        return back()->with('status', __('app.enroll.enrolled_count', ['count' => $added]));
    }

    public function destroy(AttendanceMachine $device, DeviceEnrollment $enrollment): RedirectResponse
    {
        abort_unless($enrollment->attendance_machine_id === $device->id, 404);
        $enrollment->delete();

        return back()->with('status', __('app.deleted_successfully'));
    }

    /**
     * Copy every enrolment on this device to a target device or to all other devices.
     */
    public function copy(Request $request, AttendanceMachine $device): RedirectResponse
    {
        $request->validate([
            'target' => ['required'],
        ]);

        $targets = $request->input('target') === 'all'
            ? AttendanceMachine::where('id', '!=', $device->id)->get()
            : AttendanceMachine::where('id', '!=', $device->id)->where('id', (int) $request->input('target'))->get();

        if ($targets->isEmpty()) {
            return back()->with('error', __('app.enroll.no_target'));
        }

        $source = $device->enrollments()->get();

        if ($source->isEmpty()) {
            return back()->with('error', __('app.enroll.source_empty'));
        }

        $copied = 0;
        $skipped = 0;

        DB::transaction(function () use ($targets, $source, $device, &$copied, &$skipped): void {
            foreach ($targets as $target) {
                $existing = $target->enrollments()->pluck('employee_id')->flip();

                foreach ($source as $enrollment) {
                    if ($existing->has($enrollment->employee_id)) {
                        $skipped++;

                        continue;
                    }

                    DeviceEnrollment::create([
                        'attendance_machine_id' => $target->id,
                        'employee_id' => $enrollment->employee_id,
                        'device_user_id' => $enrollment->device_user_id,
                        'source_machine_id' => $device->id,
                        'enrolled_at' => now(),
                    ]);

                    $copied++;
                }
            }
        });

        return back()->with('status', __('app.enroll.copy_summary', [
            'copied' => $copied,
            'devices' => $targets->count(),
            'skipped' => $skipped,
        ]));
    }
}
