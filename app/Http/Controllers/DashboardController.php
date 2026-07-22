<?php

namespace App\Http\Controllers;

use App\Models\AttendanceDailySummary;
use App\Models\AttendanceMachine;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $latestAttendanceDate = AttendanceDailySummary::max('attendance_date');
        $latestDate = $latestAttendanceDate ? Carbon::parse($latestAttendanceDate) : null;
        $employeeCount = Employee::where('status', 'active')->count();

        $latestQuery = AttendanceDailySummary::query()
            ->when($latestDate, fn ($query) => $query->whereDate('attendance_date', $latestDate));
        $latestStatusCounts = (clone $latestQuery)
            ->selectRaw('status, COUNT(*) total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $latestAttendanceCount = $latestStatusCounts->sum();

        $companies = Company::query()
            ->withCount(['employees as employees_count' => fn ($query) => $query->where('status', 'active')])
            ->orderBy('id')
            ->get()
            ->each(function (Company $company) use ($latestDate): void {
                $company->setAttribute('latest_attendance_count', $latestDate
                    ? AttendanceDailySummary::whereDate('attendance_date', $latestDate)
                        ->whereHas('employee', fn ($query) => $query->where('company_id', $company->id))
                        ->count()
                    : 0);
            });

        $machine = AttendanceMachine::orderByDesc('last_sync_at')->first();

        return view('dashboard', [
            'companies' => $companies,
            'machine' => $machine,
            'latestDate' => $latestDate,
            'latestStatusCounts' => $latestStatusCounts,
            'recentSummaries' => $latestDate
                ? AttendanceDailySummary::with(['employee.company'])
                    ->whereDate('attendance_date', $latestDate)
                    ->orderByDesc('has_exception')
                    ->orderBy('employee_id')
                    ->limit(7)
                    ->get()
                : collect(),
            'stats' => [
                'companies' => Company::count(),
                'employees' => $employeeCount,
                'today_attendance' => AttendanceDailySummary::whereDate('attendance_date', today())->count(),
                'today_exceptions' => AttendanceDailySummary::whereDate('attendance_date', today())->where('has_exception', true)->count(),
                'open_exceptions' => AttendanceDailySummary::where('has_exception', true)->where('reconciliation_status', 'open')->count(),
                'active_schedules' => Shift::where('is_active', true)->distinct('schedule_id')->count('schedule_id'),
                'punches' => AttendanceRecord::count(),
                'matched_rate' => AttendanceRecord::count() > 0
                    ? round(AttendanceRecord::matched()->count() / AttendanceRecord::count() * 100)
                    : 0,
                'latest_attendance' => $latestAttendanceCount,
                'attendance_coverage' => $employeeCount > 0 ? min(100, round($latestAttendanceCount / $employeeCount * 100)) : 0,
            ],
        ]);
    }
}
