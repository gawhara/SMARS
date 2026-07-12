<?php
namespace App\Http\Controllers;
use App\Http\Requests\EmployeeLeaveRequestForm;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeLeaveRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Services\Attendance\AttendanceDailySummaryService;
class EmployeeLeaveController extends Controller
{
    public function index(Request $request): View
    {
        $leaves=EmployeeLeaveRequest::with(['employee.company','requester','reviewer'])->when($request->filled('status'),fn($q)=>$q->where('status',$request->input('status')))->when($request->filled('company_id'),fn($q)=>$q->whereHas('employee',fn($e)=>$e->where('company_id',$request->integer('company_id'))))->latest()->paginate(20)->withQueryString();
        return view('attendance.leaves.index',compact('leaves')+['companies'=>Company::orderBy('name_en')->get()]);
    }
    public function create(): View { return view('attendance.leaves.form',['employees'=>Employee::orderBy('name_en')->get()]); }
    public function store(EmployeeLeaveRequestForm $request): RedirectResponse { EmployeeLeaveRequest::create($request->safe()->merge(['status'=>'pending','requested_by'=>$request->user()->id])->all()); return redirect()->route('attendance.leaves.index')->with('status',__('app.att.leave_submitted')); }
    public function approve(Request $request, EmployeeLeaveRequest $leave, AttendanceDailySummaryService $summaries): RedirectResponse { abort_unless($leave->status==='pending',409); $leave->update(['status'=>'approved','reviewed_by'=>$request->user()->id,'review_notes'=>$request->input('review_notes'),'reviewed_at'=>now()]); $summaries->rebuildRange($leave->employee,$leave->start_date,$leave->end_date); return back()->with('status',__('app.att.leave_approved_message')); }
    public function reject(Request $request, EmployeeLeaveRequest $leave): RedirectResponse { $data=$request->validate(['review_notes'=>['required','string','min:3','max:1000']]); abort_unless($leave->status==='pending',409); $leave->update(['status'=>'rejected','reviewed_by'=>$request->user()->id,'review_notes'=>$data['review_notes'],'reviewed_at'=>now()]); return back()->with('status',__('app.att.leave_rejected_message')); }
}
