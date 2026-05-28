<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Button;
use App\Models\Song;
use App\Models\UserButtonMapping;
use App\Support\LatestDeviceEventStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudioController extends Controller
{
    public function index(Request $request)
    {
        $customizableType = config('trackpad.customizable_type');
        $defaultType = config('trackpad.default_type');
        $latestDeviceEvent = app(LatestDeviceEventStore::class)->latest();
        $customButtons = UserButtonMapping::buttonsFor(Auth::user());
        $buttons = collect(config('trackpad.types'))
            ->keys()
            ->flatMap(function ($type) use ($customizableType, $customButtons) {
                if ((int) $type === (int) $customizableType) {
                    return $customButtons;
                }

                return Button::where('tipo', $type)->orderBy('id')->get();
            })
            ->values();
        $songs = Auth::user()->songs()->latest()->get();
        $selectedSong = null;
        $selectedPattern = [];
        $selectedStepCount = 16;
        $selectedBpm = 120;

        if ($request->filled('song')) {
            $selectedSong = Song::with('events')
                ->where('user_id', Auth::id())
                ->findOrFail($request->integer('song'));
            $selectedBpm = $selectedSong->bpm;

            $buttonChannels = $buttons
                ->values()
                ->mapWithKeys(fn ($button, $index) => [$button->id => $index]);

            $stepDurationMs = 125;
            $maxStep = 0;

            $selectedPattern = $selectedSong->events
                ->map(function ($event) use ($buttonChannels, $stepDurationMs, &$maxStep) {
                    if (!$buttonChannels->has($event->button_id)) {
                        return null;
                    }

                    $step = max(0, (int) round($event->time_ms / $stepDurationMs));
                    $maxStep = max($maxStep, $step);

                    return [
                        'channel' => $buttonChannels->get($event->button_id),
                        'step' => $step,
                    ];
                })
                ->filter()
                ->values()
                ->all();

            $selectedStepCount = min(256, max(16, (int) ceil(($maxStep + 1) / 16) * 16));
        }

        return view('studio.index', [
            'buttons' => $buttons,
            'songs' => $songs,
            'selectedSong' => $selectedSong,
            'selectedPattern' => $selectedPattern,
            'selectedStepCount' => $selectedStepCount,
            'selectedBpm' => $selectedBpm,
            'musicTypes' => config('trackpad.types'),
            'defaultType' => $defaultType,
            'latestDeviceEventId' => $latestDeviceEvent['id'] ?? 0,
            'latestDeviceCounters' => $latestDeviceEvent['counters'] ?? [],
            'latestDeviceJoystickX' => $latestDeviceEvent['joystick_x_posizione'] ?? 'CENTRO',
        ]);
    }
}
