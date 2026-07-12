<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PayrollPeriod;
use App\Services\Attendance\PayrollPeriodService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollPeriodController extends Controller
{
    public function __construct(private readonly PayrollPeriodService $service)
    {
    }

    public function index(Request $request): View
    {
        // Make sure the current month exists for every company.
        foreach (Company::pluck('id') as $companyId) {
            $this->service->ensurePeriod($companyId, Carbon::now());
        }

        $periods = PayrollPeriod::query()
            ->with(['company', 'lockedBy'])
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->integer('company_id')))
            ->orderByDesc('period_month')
            ->orderBy('company_id')
            ->get();

        return view('payroll.periods.index', [
            'periods' => $periods,
            'companies' => Company::orderBy('name_en')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'period_month' => ['required', 'date_format:Y-m'],
        ]);

        $this->service->ensurePeriod($validated['company_id'], Carbon::createFromFormat('Y-m', $validated['period_month']));

        return back()->with('status', __('app.saved_successfully'));
    }

    public function lock(PayrollPeriod $period): RedirectResponse
    {
        $period->update(['status' => 'locked', 'locked_at' => now(), 'locked_by' => request()->user()->id]);

        return back()->with('status', __('app.pay.locked_notice', ['period' => $period->label()]));
    }

    public function unlock(PayrollPeriod $period): RedirectResponse
    {
        $period->update(['status' => 'open', 'locked_at' => null, 'locked_by' => null]);

        return back()->with('status', __('app.pay.unlocked_notice', ['period' => $period->label()]));
    }

    public function export(PayrollPeriod $period): StreamedResponse
    {
        $period->loadMissing('company');
        $month = Carbon::parse($period->period_month);
        $rows = $this->service->exportRows($period->company, $month);

        $period->forceFill(['exported_at' => now()])->save();

        $filename = 'payroll_'.$period->company->code.'_'.$month->format('Y_m').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Employee', 'Code', 'Department', 'Present', 'Late', 'Absent', 'Leave', 'Holiday', 'Rest', 'Worked days', 'Worked hours', 'Overtime hours', 'Late minutes', 'Basic salary']);

            foreach ($rows as $row) {
                $employee = $row['employee'];
                fputcsv($out, [
                    $employee->name_en,
                    $employee->employee_code,
                    $employee->department?->name_en,
                    $row['present'], $row['late'], $row['absent'], $row['leave'], $row['holiday'], $row['rest'],
                    $row['worked_days'], $row['worked_hours'], $row['overtime_hours'], $row['late_minutes'],
                    $row['basic_salary'],
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
