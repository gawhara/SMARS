<?php

namespace App\Http\Controllers;

use App\Models\AttendanceMachine;
use App\Models\Employee;
use App\Services\Biometric\BiometricProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BiometricProvisioningController extends Controller
{
    public function __construct(private readonly BiometricProvisioningService $service)
    {
    }

    public function index(AttendanceMachine $device): View
    {
        $device->load('enrollments.employee.company');

        // Candidates: whoever is enrolled on this source device (the machine that
        // physically holds their templates). Fall back to all employees if none
        // are recorded yet, so a first-time provisioning is still possible.
        $enrolled = $device->enrollments
            ->map(fn ($enrollment) => $enrollment->employee)
            ->filter()
            ->sortBy('name_en')
            ->values();

        $candidates = $enrolled->isNotEmpty()
            ? $enrolled
            : Employee::orderBy('name_en')->get(['id', 'name_ar', 'name_en', 'employee_code', 'hr_employee_id', 'company_id']);

        return view('devices.provision', [
            'device' => $device,
            'candidates' => $candidates,
            'otherDevices' => AttendanceMachine::where('id', '!=', $device->id)->orderBy('device_name')->get(),
        ]);
    }

    public function copy(Request $request, AttendanceMachine $device): RedirectResponse
    {
        [$employees, $targets] = $this->resolve($request, $device, requireTarget: true);

        $result = $this->service->copy($device, $targets, $employees);

        return $this->flash($device, $result, __('app.provision.copied_summary', [
            'written' => $result['written'] ?? 0,
            'templates' => $result['templates'] ?? 0,
            'devices' => $result['targets'] ?? 0,
        ]));
    }

    public function move(Request $request, AttendanceMachine $device): RedirectResponse
    {
        [$employees, $targets] = $this->resolve($request, $device, requireTarget: true);

        $result = $this->service->move($device, $targets, $employees);

        return $this->flash($device, $result, __('app.provision.moved_summary', [
            'written' => $result['written'] ?? 0,
            'templates' => $result['templates'] ?? 0,
            'devices' => $result['targets'] ?? 0,
            'deleted' => $result['source_deleted'] ?? 0,
        ]));
    }

    public function destroy(Request $request, AttendanceMachine $device): RedirectResponse
    {
        [$employees] = $this->resolve($request, $device, requireTarget: false);

        $result = $this->service->delete($device, $employees);

        return $this->flash($device, $result, __('app.provision.deleted_summary', [
            'deleted' => $result['deleted'] ?? 0,
        ]));
    }

    /**
     * @return array{0: Collection<int, Employee>, 1: Collection<int, AttendanceMachine>}
     */
    private function resolve(Request $request, AttendanceMachine $device, bool $requireTarget): array
    {
        $rules = [
            'employee_ids' => ['required', 'array'],
            'employee_ids.*' => ['integer', Rule::exists('employees', 'id')],
        ];

        if ($requireTarget) {
            $rules['target'] = ['required'];
        }

        $request->validate($rules);

        $employees = Employee::whereIn('id', $request->input('employee_ids', []))
            ->get(['id', 'name_ar', 'name_en', 'employee_code', 'hr_employee_id', 'company_id']);

        $targets = collect();
        if ($requireTarget) {
            $targets = $request->input('target') === 'all'
                ? AttendanceMachine::where('id', '!=', $device->id)->get()
                : AttendanceMachine::where('id', '!=', $device->id)->whereKey((int) $request->input('target'))->get();
        }

        return [$employees, $targets];
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function flash(AttendanceMachine $device, array $result, string $success): RedirectResponse
    {
        $redirect = redirect()->route('devices.provision', $device);

        if (! ($result['ok'] ?? false)) {
            return $redirect->with('error', $result['error'] ?? __('app.provision.device_failed'));
        }

        // Partial success (some targets failed, or the source could not be cleared).
        if (! empty($result['failed_targets'])) {
            return $redirect->with('error', __('app.provision.partial_failure', [
                'devices' => implode(', ', $result['failed_targets']),
            ]));
        }

        if (! empty($result['source_error'])) {
            return $redirect->with('error', __('app.provision.source_not_cleared', ['error' => $result['source_error']]));
        }

        if (! empty($result['missing'])) {
            $success .= ' '.__('app.provision.missing_note', ['count' => $result['missing']]);
        }

        return $redirect->with('status', $success);
    }
}
