<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Song;
use Illuminate\Http\Request;

class SongApiController extends Controller
{
    public function index(Request $request)
    {
        $songs = $request->user()
            ->songs()
            ->withCount('events')
            ->latest()
            ->get()
            ->map(fn (Song $song) => [
                'id' => $song->id,
                'title' => $song->title,
                'bpm' => $song->bpm,
                'events_count' => $song->events_count,
                'created_at' => $song->created_at?->toISOString(),
                'updated_at' => $song->updated_at?->toISOString(),
            ]);

        return response()->json([
            'songs' => $songs,
        ]);
    }

    public function show(Request $request, Song $song)
    {
        if ((int) $song->user_id !== (int) $request->user()->id) {
            abort(403);
        }

        $song->load(['events.button']);

        $events = $song->events
            ->sortBy('time_ms')
            ->values()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'button_id' => $event->button_id,
                    'time_ms' => $event->time_ms,
                    'step' => (int) round($event->time_ms / 125),
                    'sound' => $this->buttonPayload($event->button),
                ];
            });

        $channels = $song->events
            ->groupBy('button_id')
            ->map(function ($events) {
                $firstEvent = $events->first();

                return [
                    'button_id' => $firstEvent->button_id,
                    'sound' => $this->buttonPayload($firstEvent->button),
                    'steps' => $events
                        ->map(fn ($event) => (int) round($event->time_ms / 125))
                        ->unique()
                        ->sort()
                        ->values(),
                ];
            })
            ->values();

        $maxStep = $events->max('step') ?? 0;
        $stepCount = min(256, max(16, (int) ceil(($maxStep + 1) / 16) * 16));

        return response()->json([
            'song' => [
                'id' => $song->id,
                'title' => $song->title,
                'bpm' => $song->bpm,
                'step_count' => $stepCount,
                'created_at' => $song->created_at?->toISOString(),
                'updated_at' => $song->updated_at?->toISOString(),
                'events' => $events,
                'channels' => $channels,
            ],
        ]);
    }

    private function buttonPayload($button): ?array
    {
        if (!$button) {
            return null;
        }

        return [
            'id' => $button->id,
            'sound_file' => $button->sound_file,
            'sound_url' => asset($button->sound_file),
            'tipo' => (int) $button->tipo,
            'nome_tipo' => $button->nome_tipo,
            'type_label' => $button->type_name,
            'name' => ucfirst(str_replace('_', ' ', pathinfo(basename($button->sound_file), PATHINFO_FILENAME))),
        ];
    }
}
