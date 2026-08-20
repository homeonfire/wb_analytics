<?php

namespace App\Http\Controllers;

use App\Jobs\RunStoreSync;
use App\Models\SyncRun;
use App\Models\SyncSchedule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SyncController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeManualSync($request);
        $store = app('current_store');
        $canManageSchedules = (bool) $request->user()->is_super_admin;

        return Inertia::render('Sync/Index', [
            'tasks' => collect(config('sync.tasks'))->map(fn ($task, $key) => ['key' => $key, ...$task])->values(),
            'runs' => SyncRun::where('store_id', $store->id)->latest()->limit(50)->get(),
            'schedules' => $canManageSchedules ? SyncSchedule::where('store_id', $store->id)->latest()->get() : [],
            'canManageSchedules' => $canManageSchedules,
        ]);
    }

    public function run(Request $request)
    {
        $this->authorizeManualSync($request);
        $data = $request->validate([
            'task' => ['required', Rule::in(array_keys(config('sync.tasks')))],
            'days' => ['nullable', 'integer', 'min:1', 'max:90'],
        ]);
        $run = SyncRun::create([
            'store_id' => app('current_store')->id,
            'user_id' => $request->user()->id,
            'task' => $data['task'],
            'status' => 'queued',
            'options' => ['days' => $data['days'] ?? null],
        ]);
        RunStoreSync::dispatch($run->id);

        return back()->with('success', 'Синхронизация поставлена в очередь.');
    }

    public function schedule(Request $request)
    {
        $this->authorizeScheduleManagement($request);
        $data = $request->validate([
            'task' => ['required', Rule::in(array_keys(config('sync.tasks')))],
            'frequency' => ['required', Rule::in(['15_minutes', '30_minutes', 'hourly', 'daily'])],
            'run_at' => ['nullable', 'date_format:H:i'],
            'days' => ['nullable', 'integer', 'min:1', 'max:90'],
        ]);
        $schedule = new SyncSchedule([
            'store_id' => app('current_store')->id,
            'user_id' => $request->user()->id,
            'task' => $data['task'],
            'frequency' => $data['frequency'],
            'run_at' => $data['run_at'] ?? null,
            'options' => ['days' => $data['days'] ?? null],
            'is_enabled' => true,
        ]);
        $schedule->next_run_at = $schedule->nextDate();
        $schedule->save();

        return back()->with('success', 'Расписание создано.');
    }

    public function toggle(Request $request, SyncSchedule $schedule)
    {
        $this->authorizeScheduleManagement($request);
        abort_unless($schedule->store_id === app('current_store')->id, 404);
        $schedule->is_enabled = !$schedule->is_enabled;
        $schedule->next_run_at = $schedule->is_enabled ? $schedule->nextDate() : null;
        $schedule->save();

        return back();
    }

    public function destroy(Request $request, SyncSchedule $schedule)
    {
        $this->authorizeScheduleManagement($request);
        abort_unless($schedule->store_id === app('current_store')->id, 404);
        $schedule->delete();

        return back();
    }

    private function authorizeManualSync(Request $request): void
    {
        abort_unless((bool) $request->user()?->is_super_admin || (bool) $request->user()?->can_run_sync, 403);
        abort_unless(app()->bound('current_store'), 404);
    }

    private function authorizeScheduleManagement(Request $request): void
    {
        abort_unless((bool) $request->user()?->is_super_admin, 403);
        abort_unless(app()->bound('current_store'), 404);
    }
}
