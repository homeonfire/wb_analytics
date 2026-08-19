<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('sync_schedule_id')->nullable()->index();
            $table->string('task', 50);
            $table->string('status', 20)->default('queued')->index();
            $table->json('options')->nullable();
            $table->text('output')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['store_id', 'task', 'created_at']);
        });

        Schema::create('sync_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('task', 50);
            $table->string('frequency', 20);
            $table->time('run_at')->nullable();
            $table->json('options')->nullable();
            $table->boolean('is_enabled')->default(true)->index();
            $table->timestamp('next_run_at')->nullable()->index();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
            $table->index(['store_id', 'is_enabled', 'next_run_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_schedules');
        Schema::dropIfExists('sync_runs');
    }
};
