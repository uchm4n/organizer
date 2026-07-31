<?php

namespace Database\Factories;

use App\Models\DownloadLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DownloadLog>
 */
class DownloadLogFactory extends Factory
{
    protected $model = DownloadLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'filename'   => fake()->word().'.'.fake()->fileExtension(),
            'ip'         => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'referer'    => fake()->boolean(70) ? fake()->url() : null,
            'extra'      => null,
            'created_at' => fake()->dateTimeThisMonth(),
        ];
    }
}
