<?php

namespace Database\Factories;

use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->firstName().'\'s TRMNL',
            'mac_address' => $this->faker->macAddress(),
            'default_refresh_interval' => '900',
            'friendly_id' => Str::random(6),
            'api_key' => Str::random(22),
            'user_id' => 1,
            'last_battery_voltage' => $this->faker->randomFloat(2, 3.0, 4.2),
            'last_rssi_level' => $this->faker->numberBetween(-100, 0),
            'last_firmware_version' => '1.6.0',
            'last_log_request' => ['status' => 'success', 'timestamp' => Carbon::now()->toDateTimeString()],
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
