<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use App\Models\EmployeeLeaveRequest;
class EmployeeLeaveRequestForm extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['employee_id'=>['required','integer','exists:employees,id'],'leave_type'=>['required',Rule::in(['annual','sick','unpaid','business','training'])],'start_date'=>['required','date'],'end_date'=>['required','date','after_or_equal:start_date'],'reason'=>['nullable','string','max:1000']]; }
    public function after(): array { return [function(Validator $validator): void { if(!$this->filled('employee_id')||!$this->filled('start_date')||!$this->filled('end_date')) return; $overlap=EmployeeLeaveRequest::where('employee_id',$this->integer('employee_id'))->whereIn('status',['pending','approved'])->whereDate('start_date','<=',$this->input('end_date'))->whereDate('end_date','>=',$this->input('start_date'))->exists(); if($overlap) $validator->errors()->add('start_date',__('app.att.leave_overlap')); }]; }
}
