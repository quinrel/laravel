<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Tracking;

use Illuminate\Support\Carbon;

class GoogleAlgorithmUpdates
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getUpdates(): array
    {
        $defaults = [
            [
                'name' => 'March 2025 Core Update',
                'date' => '2025-03-13',
                'type' => 'Core',
            ],
            [
                'name' => 'December 2024 Core Update',
                'date' => '2024-12-12',
                'type' => 'Core',
            ],
            [
                'name' => 'November 2024 Core Update',
                'date' => '2024-11-11',
                'type' => 'Core',
            ],
            [
                'name' => 'August 2024 Core Update',
                'date' => '2024-08-15',
                'type' => 'Core',
            ],
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

        $custom = config('quinrel.gsc.algorithm_updates', []);
        if (is_array($custom) && $custom !== []) {
            /** @var array<int, array<string, mixed>> $validCustom */
            $validCustom = array_values(array_filter(
                $custom,
                fn (mixed $item): bool => is_array($item) && isset($item['name'], $item['date'])
            ));

            return array_merge($validCustom, $defaults);
        }

        return $defaults;
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
