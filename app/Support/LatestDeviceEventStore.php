<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LatestDeviceEventStore
{
    public function latest(): ?array
    {
        if (!File::exists($this->path())) {
            return null;
        }

        $event = json_decode(File::get($this->path()), true);

        return is_array($event) ? $event : null;
    }

    public function put(array $event): array
    {
        $previous = $this->latest();
        $event['id'] = (int) ($previous['id'] ?? 0) + 1;
        $event['counters'] = $this->nextCounters($event, $previous);
        $event['created_at'] = now()->toISOString();

        File::ensureDirectoryExists(dirname($this->path()));
        File::put($this->path(), json_encode($event, JSON_PRETTY_PRINT), true);

        return $event;
    }

    private function nextCounters(array $event, ?array $previous): array
    {
        $counters = $previous['counters'] ?? [
            'buttons' => [
                1 => 0,
                2 => 0,
                3 => 0,
                4 => 0,
                5 => 0,
            ],
            'joystick_left' => 0,
            'joystick_right' => 0,
            'joystick_click' => 0,
        ];

        for ($slot = 1; $slot <= 5; $slot++) {
            $current = (int) ($event["button{$slot}"] ?? 0);
            $before = (int) ($previous["button{$slot}"] ?? 0);

            if ($current === 1 && $before !== 1) {
                $counters['buttons'][$slot] = (int) ($counters['buttons'][$slot] ?? 0) + 1;
            }
        }

        $currentDirection = Str::upper($event['joystick_x_posizione'] ?? 'CENTRO');
        $previousDirection = Str::upper($previous['joystick_x_posizione'] ?? 'CENTRO');

        if ($currentDirection !== $previousDirection && in_array($currentDirection, ['SINISTRA', 'LEFT'], true)) {
            $counters['joystick_left'] = (int) ($counters['joystick_left'] ?? 0) + 1;
        }

        if ($currentDirection !== $previousDirection && in_array($currentDirection, ['DESTRA', 'RIGHT'], true)) {
            $counters['joystick_right'] = (int) ($counters['joystick_right'] ?? 0) + 1;
        }

        if ($this->isPressed($event['joystick_click'] ?? null) && !$this->isPressed($previous['joystick_click'] ?? null)) {
            $counters['joystick_click'] = (int) ($counters['joystick_click'] ?? 0) + 1;
        }

        return $counters;
    }

    private function isPressed(mixed $value): bool
    {
        $normalized = Str::upper((string) $value);

        return in_array($normalized, ['PREMUTO', 'PRESSED', '1', 'TRUE'], true);
    }

    private function path(): string
    {
        return storage_path('app/trackpad/latest-device-event.json');
    }
}
