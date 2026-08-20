<?php

namespace App\Jobs;

use App\Models\SyncRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

class RunStoreSync implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 7200;
    public int $tries = 1;

    public function __construct(public int $runId) {}

    public function middleware(): array
    {
        $run = SyncRun::find($this->runId);
        return [(new WithoutOverlapping('store-sync:'.($run?->store_id ?? $this->runId)))->expireAfter(7500)->releaseAfter(60)];
    }

    public function handle(): void
    {
        $run = SyncRun::findOrFail($this->runId);
        $run->update(['status' => 'running', 'started_at' => now(), 'error' => null]);

        try {
            $tasks = $run->task === 'full' ? config('sync.full_sequence') : [$run->task];
            $output = [];
            foreach ($tasks as $task) {
                $definition = config("sync.tasks.$task");
                if (!$definition || empty($definition['command'])) throw new RuntimeException("Неизвестная операция: {$task}");

                $arguments = ['--store' => $run->store_id];
                if (!empty($definition['days']) && !empty($run->options['days'])) {
                    $arguments['--days'] = min((int) $run->options['days'], (int) ($definition['max_days'] ?? 90));
                }
                $exitCode = Artisan::call($definition['command'], $arguments);
                $output[] = "[{$definition['label']}]\n".trim(Artisan::output());
                if ($exitCode !== 0) throw new RuntimeException("Команда {$definition['command']} завершилась с кодом {$exitCode}");
            }
            $run->update(['status' => 'completed', 'output' => implode("\n\n", $output), 'finished_at' => now()]);
            Cache::put("analytics:store:{$run->store_id}:version", now()->getTimestampMs(), now()->addYear());
        } catch (Throwable $exception) {
            $run->update(['status' => 'failed', 'output' => isset($output) ? implode("\n\n", $output) : null, 'error' => mb_substr($exception->getMessage(), 0, 10000), 'finished_at' => now()]);
            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        SyncRun::whereKey($this->runId)->whereIn('status', ['queued', 'running'])->update(['status' => 'failed', 'error' => mb_substr($exception?->getMessage() ?? 'Задача остановлена очередью', 0, 10000), 'finished_at' => now()]);
    }
}
