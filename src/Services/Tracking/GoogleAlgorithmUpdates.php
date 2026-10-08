<?php

declare(strict_types=1);

namespace Holvek\Laravel\Services\Tracking;

use Illuminate\Support\Carbon;

class GoogleAlgorithmUpdates
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getUpdates(): array
    {
        return [
            [
                'name' => 'March 2024 Core Update',
                'date' => '2024-03-05',
                'type' => 'Core',
            ],
            [
                'name' => 'November 2023 Core Update',
                'date' => '2023-11-02',
                'type' => 'Core',
            ],
            [
                'name' => 'October 2023 Core Update',
                'date' => '2023-10-05',
                'type' => 'Core',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getOverlaysForPeriod(Carbon $start, Carbon $end): array
    {
        return array_filter($this->getUpdates(), function (array $update) use ($start, $end) {
            $date = Carbon::parse((string) $update['date']);

            return $date->between($start, $end);
        });
    }
}
