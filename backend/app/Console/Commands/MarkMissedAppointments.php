<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MarkMissedAppointments extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'appointments:mark-missed {--grace-hours=2 : Hours after appointment end time before marking missed}';

    /**
     * The console command description.
     */
    protected $description = 'Mark past uncompleted scheduled appointments as missed after a grace period';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $graceHours = (int)$this->option('grace-hours');
        $cutoff = Carbon::now('Asia/Manila')->subHours($graceHours)->format('Y-m-d H:i:s');

        $affected = DB::table('patients')
            ->whereNotNull('appointment_date')
            ->whereIn(DB::raw('LOWER(TRIM(status))'), ['scheduled', 'confirmed', 'approved', 'for_approval', 'for approval', 'pending'])
            ->whereRaw("TIMESTAMP(CONCAT(appointment_date, ' ', COALESCE(scheduled_time, '08:00:00'))) <= ?", [$cutoff])
            ->update([
                'status' => 'no-show',
                'updated_at' => Carbon::now('Asia/Manila')
            ]);

        $this->info("Successfully marked {$affected} past appointment(s) as missed (no-show).");

        return Command::SUCCESS;
    }
}

