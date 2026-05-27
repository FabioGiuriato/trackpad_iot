<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Button;
use App\Models\Song;
use App\Models\UserButtonMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $buttons = $this->availableButtons($request);

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

        $stepsByButtonId = $song->events
            ->groupBy('button_id')
            ->map(fn ($events) => $events
                ->map(fn ($event) => (int) round($event->time_ms / 125))
                ->unique()
                ->sort()
                ->values()
            );

        $channels = $buttons
            ->values()
            ->map(fn ($button) => [
                'button_id' => $button->id,
                'sound' => $this->buttonPayload($button),
                'steps' => $stepsByButtonId->get($button->id, collect())->values()->all(),
            ]);

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

    public function update(Request $request, Song $song)
    {
        if ((int) $song->user_id !== (int) $request->user()->id) {
            abort(403);
        }

        $data = $request->validate([
            'bpm' => ['required', 'integer', 'min:60', 'max:200'],
            'events' => ['present', 'array'],
            'events.*.button_id' => ['required', 'integer', 'exists:buttons,id'],
            'events.*.time_ms' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($song, $data) {
            $song->update([
                'bpm' => $data['bpm'],
            ]);

            $song->events()->delete();

            if (!empty($data['events'])) {
                $song->events()->createMany($data['events']);
            }
        });

        return response()->json([
            'message' => 'Canzone aggiornata correttamente.',
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

    private function availableButtons(Request $request)
    {
        $customizableType = config('trackpad.customizable_type');
        $customButtons = UserButtonMapping::buttonsFor($request->user());

        return collect(config('trackpad.types'))
            ->keys()
            ->flatMap(function ($type) use ($customizableType, $customButtons) {
                if ((int) $type === (int) $customizableType) {
                    return $customButtons;
                }

                return Button::where('tipo', $type)->orderBy('id')->get();
            })
            ->values();
    }
}
