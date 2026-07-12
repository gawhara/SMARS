<?php
namespace App\Services\Attendance;
use App\Models\{AttendanceMachine,AttendanceRecord,AttendanceSyncBatch}; use Carbon\Carbon; use Symfony\Component\Process\Process; use Illuminate\Support\Facades\DB;
class ZktecoReadOnlySyncService
{
 public function __construct(private AttendanceService $attendance,private AttendanceDailySummaryService $summaries){}
 public function sync(AttendanceMachine $device,?int $userId=null):AttendanceSyncBatch{
  $process=new Process(['python',base_path('scripts/zkteco_readonly_sync.py'),'--ip',(string)$device->host(),'--port',(string)$device->port,'--comm-key',(string)$device->comm_key,'--timeout','8']);$process->setTimeout(30);$process->run();$data=json_decode($process->getOutput(),true);
  if(!$process->isSuccessful()||!($data['ok']??false)){ $device->forceFill(['status'=>'sync_failed','last_failed_connection_at'=>now()])->save(); throw new \RuntimeException($data['error']??$process->getErrorOutput()?:'Device sync failed'); }
  $batch=DB::transaction(function()use($data,$device,$userId){$batch=AttendanceSyncBatch::create(['source'=>'device','attendance_machine_id'=>$device->id,'total_rows'=>count($data['records']),'created_by'=>$userId]);$imported=$matched=$unmatched=$duplicate=0;foreach($data['records'] as $row){$at=Carbon::parse($row['punch_at']);if(AttendanceRecord::where('attendance_machine_id',$device->id)->where('device_user_id',$row['device_user_id'])->where('punch_at',$at)->exists()){$duplicate++;continue;}$employee=$this->attendance->resolveEmployee($row['device_user_id']);AttendanceRecord::create(['employee_id'=>$employee?->id,'attendance_machine_id'=>$device->id,'device_user_id'=>$row['device_user_id'],'punch_at'=>$at,'punch_type'=>$this->attendance->normalizePunchType($row['punch_type']),'raw_punch_type'=>$row['punch_type'],'verification_type'=>$row['verification_type'],'source'=>'device','company_id'=>$employee?->company_id,'branch_id'=>$employee?->branch_id,'sync_batch_id'=>$batch->id]);$imported++;$employee?$matched++:$unmatched++;}$batch->update(['imported_count'=>$imported,'matched_count'=>$matched,'unmatched_count'=>$unmatched,'duplicate_count'=>$duplicate]);return $batch;});
  $records=AttendanceRecord::where('sync_batch_id',$batch->id)->matched()->get();$this->summaries->rebuildForRecords($records);$device->forceFill(['status'=>'online','last_sync_at'=>now(),'last_successful_connection_at'=>now(),'last_attendance_at'=>$records->max('punch_at')])->save();return $batch;
 }
}
