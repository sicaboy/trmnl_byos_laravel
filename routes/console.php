<?php

use App\Jobs\CleanupDeviceLogsJob;
use App\Jobs\FirmwarePollJob;
use App\Jobs\NotifyDeviceBatteryLowJob;
use Illuminate\Support\Facades\Schedule;

//Schedule::job(FirmwarePollJob::class)->daily();
Schedule::job(CleanupDeviceLogsJob::class)->daily();
Schedule::job(NotifyDeviceBatteryLowJob::class)->dailyAt('10:00');
