<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceMachineRequest;
use App\Models\AttendanceMachine;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Services\Attendance\ZktecoReadOnlySyncService;

class BiometricDeviceController extends Controller
{
    public function index(Request $request): View
    {
        $devices = AttendanceMachine::query()
            ->with(['company', 'branch'])
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->integer('company_id')))
            ->when($request->filled('connection_type'), fn ($q) => $q->where('connection_type', $request->string('connection_type')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($q) use ($request): void {
                $search = trim((string) $request->string('search'));
                $q->where(function ($q) use ($search): void {
                    foreach (['device_name', 'device_model', 'serial_number', 'ip_address', 'domain', 'location_description'] as $column) {
                        $q->orWhere($column, 'like', "%{$search}%");
                    }
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $stats = [
            'total' => AttendanceMachine::count(),
            'active' => AttendanceMachine::where('is_active', true)->count(),
            'online' => AttendanceMachine::where('status', 'online')->where('is_active', true)->count(),
            'unreachable' => AttendanceMachine::whereIn('status', ['unreachable', 'sync_failed'])->count(),
        ];

        return view('devices.index', [
            'devices' => $devices,
            'stats' => $stats,
            'companies' => Company::orderBy('name_en')->get(),
        ]);
    }

    public function create(): View
    {
        return view('devices.form', $this->formData(new AttendanceMachine(['is_active' => true, 'connection_type' => 'lan', 'port' => 4370, 'device_model' => 'ZKTeco MB1000', 'timezone' => 'Asia/Riyadh'])));
    }

    public function store(AttendanceMachineRequest $request): RedirectResponse
    {
        $device = AttendanceMachine::create($this->payload($request));

        return redirect()->route('devices.show', $device)->with('status', __('app.saved_successfully'));
    }

    public function show(AttendanceMachine $device): View
    {
        $device->load(['company', 'branch', 'creator', 'updater'])->loadCount('enrollments');

        return view('devices.show', compact('device'));
    }

    public function edit(AttendanceMachine $device): View
    {
        return view('devices.form', $this->formData($device));
    }

    public function update(AttendanceMachineRequest $request, AttendanceMachine $device): RedirectResponse
    {
        $device->update($this->payload($request));

        return redirect()->route('devices.show', $device)->with('status', __('app.saved_successfully'));
    }

    public function destroy(AttendanceMachine $device): RedirectResponse
    {
        $device->delete();

        return redirect()->route('devices.index')->with('status', __('app.deleted_successfully'));
    }

    /**
     * Probe reachability with a short TCP connection to host:port and record the result.
     */
    public function testConnection(AttendanceMachine $device): RedirectResponse
    {
        $host = $device->host();

        if (! $host) {
            return back()->with('error', __('app.device.no_target'));
        }

        $errno = 0;
        $errstr = '';
        $connection = @fsockopen($host, (int) $device->port, $errno, $errstr, 3);

        if ($connection) {
            fclose($connection);
            $device->forceFill(['status' => 'online', 'last_successful_connection_at' => now()])->save();

            return back()->with('status', __('app.device.connection_ok'));
        }

        $device->forceFill(['status' => 'unreachable', 'last_failed_connection_at' => now()])->save();

        return back()->with('error', __('app.device.connection_failed', ['error' => $errstr ?: __('app.device.timeout')]));
    }

    public function sync(AttendanceMachine $device, ZktecoReadOnlySyncService $service): RedirectResponse
    {
        try { $batch=$service->sync($device,(int)request()->user()->id); return back()->with('status',__('app.device.sync_ok',['count'=>$batch->imported_count])); }
        catch (\Throwable $e) { return back()->with('error',__('app.device.sync_failed').': '.$e->getMessage()); }
    }

    /**
     * Read attendance logs from every active device in one pass. Each device is
     * read read-only and duplicate punches are skipped (per-device dedup lives in
     * the sync service); an unreachable device is recorded and the loop continues.
     */
    public function syncAll(ZktecoReadOnlySyncService $service): RedirectResponse
    {
        $devices = AttendanceMachine::where('is_active', true)->get();

        if ($devices->isEmpty()) {
            return back()->with('error', __('app.device.no_active_devices'));
        }

        $imported = 0;
        $succeeded = 0;
        $failed = [];

        foreach ($devices as $device) {
            try {
                $imported += $service->sync($device, (int) request()->user()->id)->imported_count;
                $succeeded++;
            } catch (\Throwable) {
                $failed[] = $device->device_name;
            }
        }

        $message = __('app.device.sync_all_summary', ['devices' => $succeeded, 'count' => $imported]);

        if (! empty($failed)) {
            return back()->with('error', $message.' '.__('app.device.sync_all_failed', [
                'count' => count($failed),
                'names' => implode(', ', $failed),
            ]));
        }

        return back()->with('status', $message);
    }

    private function formData(AttendanceMachine $device): array
    {
        return [
            'device' => $device,
            'companies' => Company::where('is_active', true)->orderBy('name_en')->get(),
            'branches' => Branch::where('is_active', true)->orderBy('name_en')->get(['id', 'company_id', 'name_ar', 'name_en']),
        ];
    }

    private function payload(AttendanceMachineRequest $request): array
    {
        $data = $request->safe()->merge(['is_active' => $request->boolean('is_active'),'automatic_sync_enabled'=>$request->boolean('automatic_sync_enabled')])->except(['password']);

        // Only overwrite the stored password when a new one is supplied.
        if ($request->filled('password')) {
            $data['password'] = $request->input('password');
        }

        return $data;
    }
}
