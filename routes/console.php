<?php

use App\Jobs\CleanupDeviceLogsJob;
use App\Jobs\NotifyDeviceBatteryLowJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(CleanupDeviceLogsJob::class)->daily();
Schedule::job(NotifyDeviceBatteryLowJob::class)->dailyAt('10:00');
