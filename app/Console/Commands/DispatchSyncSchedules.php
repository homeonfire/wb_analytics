<?php

namespace App\Console\Commands;

use App\Jobs\RunStoreSync;
use App\Models\SyncRun;
use App\Models\SyncSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DispatchSyncSchedules extends Command
{
    protected $signature = 'sync:dispatch-schedules';
    protected $description = 'Dispatch due store sync schedules';

    public function handle(): int
    {
        SyncSchedule::query()->where('is_enabled', true)->where('next_run_at', '<=', now())->orderBy('next_run_at')->eachById(function (SyncSchedule $schedule) {
            DB::transaction(function () use ($schedule) {
                $locked = SyncSchedule::lockForUpdate()->find($schedule->id);
                if (!$locked?->is_enabled || !$locked->next_run_at || $locked->next_run_at->isFuture()) return;
                $run = SyncRun::create(['store_id' => $locked->store_id, 'user_id' => $locked->user_id, 'sync_schedule_id' => $locked->id, 'task' => $locked->task, 'status' => 'queued', 'options' => $locked->options]);
                $locked->update(['last_run_at' => now(), 'next_run_at' => $locked->nextDate()]);
                RunStoreSync::dispatch($run->id)->afterCommit();
            });
        });
        return self::SUCCESS;
    }
}
