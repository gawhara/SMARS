<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendancePolicyRequest;
use App\Models\AttendancePolicy;
use App\Models\Company;
use App\Models\AttendanceRecord;
use App\Services\Attendance\AttendanceDailySummaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AttendancePolicyController extends Controller
{
    public function index(): View
    {
        $companies = Company::with('attendancePolicy')->orderBy('name_en')->get();

        return view('attendance.policies.index', compact('companies'));
    }

    public function edit(Company $company): View
    {
        $policy = $company->attendancePolicy ?? AttendancePolicy::defaults($company->id);

        return view('attendance.policies.form', compact('company', 'policy'));
    }

    public function update(AttendancePolicyRequest $request, Company $company, AttendanceDailySummaryService $summaries): RedirectResponse
    {
        AttendancePolicy::updateOrCreate(
            ['company_id' => $company->id],
            $request->safe()->merge(['is_active' => $request->boolean('is_active')])->all(),
        );

        $summaries->rebuildForRecords(
            AttendanceRecord::matched()->where('company_id', $company->id)->get(),
        );

        return redirect()->route('attendance.policies.index')->with('status', __('app.att.policy_saved'));
    }
}
