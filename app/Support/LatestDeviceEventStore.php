<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LatestDeviceEventStore
{
    public function latest(): ?array
    {
        return $this->withLock(LOCK_SH, fn () => $this->readLatestFromDisk());
    }

    public function put(array $event): array
    {
        return $this->withLock(LOCK_EX, function () use ($event) {
            $previous = $this->readLatestFromDisk();
            $event['id'] = (int) ($previous['id'] ?? 0) + 1;
            $event['counters'] = $this->nextCounters($event, $previous);
            $event['created_at'] = now()->toISOString();

            file_put_contents($this->path(), json_encode($event, JSON_UNESCAPED_SLASHES));

            return $event;
        });
    }

    private function readLatestFromDisk(): ?array
    {
        $path = $this->path();

        if (!File::exists($path)) {
            return null;
        }

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $contents = @file_get_contents($path);

            if ($contents === false || $contents === '') {
                usleep(2000);
                continue;
            }

            $event = json_decode($contents, true);

            if (is_array($event)) {
                return $event;
            }

            usleep(2000);
        }

        return null;
    }

    private function withLock(int $operation, callable $callback): mixed
    {
        $path = $this->path();

        File::ensureDirectoryExists(dirname($path));

        $lock = fopen($this->lockPath(), 'c');

        if (!$lock) {
            return $callback();
        }

        try {
            flock($lock, $operation);

            return $callback();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
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
            'joystick_up' => 0,
            'joystick_down' => 0,
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

        $currentVerticalDirection = Str::upper($event['joystick_y_posizione'] ?? $event['joystick_y_position'] ?? 'CENTRO');
        $previousVerticalDirection = Str::upper($previous['joystick_y_posizione'] ?? $previous['joystick_y_position'] ?? 'CENTRO');

        if ($currentVerticalDirection !== $previousVerticalDirection && in_array($currentVerticalDirection, ['SOPRA', 'SU', 'UP', 'ALTO'], true)) {
            $counters['joystick_up'] = (int) ($counters['joystick_up'] ?? 0) + 1;
        }

        if ($currentVerticalDirection !== $previousVerticalDirection && in_array($currentVerticalDirection, ['SOTTO', 'GIU', 'GIU\'', 'DOWN', 'BASSO'], true)) {
            $counters['joystick_down'] = (int) ($counters['joystick_down'] ?? 0) + 1;
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

    private function lockPath(): string
    {
        return storage_path('app/trackpad/latest-device-event.lock');
    }
}
