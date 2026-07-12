<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use App\Models\AttendanceHoliday;
class AttendanceHolidayRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['company_id'=>['nullable','integer','exists:companies,id'],'name_ar'=>['required','string','max:255'],'name_en'=>['required','string','max:255'],'holiday_date'=>['required','date'],'is_paid'=>['nullable','boolean'],'is_active'=>['nullable','boolean']]; }
    public function after(): array { return [function(Validator $validator): void { if(!$this->filled('holiday_date')) return; $query=AttendanceHoliday::whereDate('holiday_date',$this->input('holiday_date')); $this->filled('company_id')?$query->where('company_id',$this->integer('company_id')):$query->whereNull('company_id'); if($holiday=$this->route('holiday')) $query->whereKeyNot($holiday->id); if($query->exists()) $validator->errors()->add('holiday_date',__('app.att.holiday_duplicate')); }]; }
}
