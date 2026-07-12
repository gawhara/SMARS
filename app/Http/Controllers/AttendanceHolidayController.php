<?php
namespace App\Http\Controllers;
use App\Http\Requests\AttendanceHolidayRequest;
use App\Models\AttendanceHoliday;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Models\Employee;
use App\Services\Attendance\AttendanceDailySummaryService;
class AttendanceHolidayController extends Controller
{
    public function index(): View { return view('attendance.holidays.index',['holidays'=>AttendanceHoliday::with('company')->orderByDesc('holiday_date')->paginate(20)]); }
    public function create(): View { return view('attendance.holidays.form',['holiday'=>new AttendanceHoliday(['is_paid'=>true,'is_active'=>true]),'companies'=>Company::orderBy('name_en')->get()]); }
    public function store(AttendanceHolidayRequest $request, AttendanceDailySummaryService $summaries): RedirectResponse { $holiday=AttendanceHoliday::create($this->payload($request)); $this->rebuild($holiday,$summaries); return redirect()->route('attendance.holidays.index')->with('status',__('app.saved_successfully')); }
    public function edit(AttendanceHoliday $holiday): View { return view('attendance.holidays.form',['holiday'=>$holiday,'companies'=>Company::orderBy('name_en')->get()]); }
    public function update(AttendanceHolidayRequest $request, AttendanceHoliday $holiday, AttendanceDailySummaryService $summaries): RedirectResponse { $oldDate=$holiday->holiday_date->copy(); $oldCompany=$holiday->company_id; $holiday->update($this->payload($request)); $this->rebuildDate($oldDate,$oldCompany,$summaries); $this->rebuild($holiday,$summaries); return redirect()->route('attendance.holidays.index')->with('status',__('app.saved_successfully')); }
    public function destroy(AttendanceHoliday $holiday, AttendanceDailySummaryService $summaries): RedirectResponse { $date=$holiday->holiday_date->copy(); $company=$holiday->company_id; $holiday->delete(); $this->rebuildDate($date,$company,$summaries); return back()->with('status',__('app.deleted_successfully')); }
    private function payload(AttendanceHolidayRequest $request): array { return $request->safe()->merge(['is_paid'=>$request->boolean('is_paid'),'is_active'=>$request->boolean('is_active')])->all(); }
    private function rebuild(AttendanceHoliday $holiday, AttendanceDailySummaryService $summaries): void { $this->rebuildDate($holiday->holiday_date,$holiday->company_id,$summaries); }
    private function rebuildDate($date, ?int $companyId, AttendanceDailySummaryService $summaries): void { Employee::when($companyId,fn($q)=>$q->where('company_id',$companyId))->get()->each(fn($employee)=>$summaries->rebuild($employee,$date)); }
}
