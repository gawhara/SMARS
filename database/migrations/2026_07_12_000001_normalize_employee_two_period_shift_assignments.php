<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void {
        $secondary=DB::table('shifts')->where('shift_number','>',1)->whereNotNull('schedule_id')->get();
        foreach($secondary as $shift){ $primary=DB::table('shifts')->where('schedule_id',$shift->schedule_id)->where('shift_number',1)->value('id'); if($primary) DB::table('employees')->where('shift_id',$shift->id)->update(['shift_id'=>$primary]); }
    }
    public function down(): void {}
};
