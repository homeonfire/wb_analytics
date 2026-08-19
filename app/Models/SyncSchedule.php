<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class SyncSchedule extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['options' => 'array', 'is_enabled' => 'boolean', 'next_run_at' => 'datetime', 'last_run_at' => 'datetime'];
    }

    public function nextDate(?Carbon $from = null): Carbon
    {
        $from = ($from ?? now())->copy();

        return match ($this->frequency) {
            '15_minutes' => $from->addMinutes(15)->startOfMinute(),
            '30_minutes' => $from->addMinutes(30)->startOfMinute(),
            'hourly' => $from->addHour()->startOfHour(),
            'daily' => tap($from->copy()->setTimeFromTimeString($this->run_at ?: '03:00'), function (Carbon $date) use ($from) {
                if ($date->lessThanOrEqualTo($from)) $date->addDay();
            }),
            default => $from->addDay(),
        };
    }
}
