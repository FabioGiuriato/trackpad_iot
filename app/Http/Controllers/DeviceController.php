<?php

namespace App\Http\Controllers;

use App\Models\Button;
use App\Support\LatestDeviceEventStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeviceController extends Controller
{
    public function index()
    {
        $latestEvent = app(LatestDeviceEventStore::class)->latest();

        return view('iot.device', [
            'latestEvent' => $latestEvent,
            'mqttTopic' => $this->mqttTopic(),
        ]);
    }

    public function live()
    {
        $latestEvent = app(LatestDeviceEventStore::class)->latest();

        return view('iot.live', [
            'latestEvent' => $latestEvent,
            'buttonNames' => $this->buttonNames(),
            'mqttTopic' => $this->mqttTopic(),
        ]);
    }

    public function latestEvents(Request $request)
    {
        $latestEvent = app(LatestDeviceEventStore::class)->latest();

        if (!$latestEvent || ($request->filled('after') && (int) $latestEvent['id'] === $request->integer('after'))) {
            return response()
                ->json(['events' => []])
                ->header('Cache-Control', 'no-store');
        }

        return response()
            ->json(['events' => [$latestEvent]])
            ->header('Cache-Control', 'no-store');
    }

    public function storeEvent(Request $request)
    {
        $payload = $this->normalizePayload($request);

        $validator = Validator::make($payload, [
            'potenziometro' => ['required', 'integer', 'min:0', 'max:4095'],
            'pot_percentuale' => ['nullable', 'integer', 'min:0', 'max:100'],
            'volume' => ['nullable', 'integer', 'min:0', 'max:100'],
            'levetta' => ['nullable', 'integer'],
            'joystick_x_valore' => ['nullable', 'integer'],
            'joystick_x_posizione' => ['nullable', 'string', 'max:20'],
            'joystick_y_valore' => ['nullable', 'integer'],
            'joystick_y_posizione' => ['nullable', 'string', 'max:20'],
            'joystick_y_value' => ['nullable', 'integer'],
            'joystick_y_position' => ['nullable', 'string', 'max:20'],
            'joystick_click' => ['nullable', 'string', 'max:30'],
            'button1' => ['required', 'integer', 'in:0,1'],
            'button2' => ['required', 'integer', 'in:0,1'],
            'button3' => ['required', 'integer', 'in:0,1'],
            'button4' => ['required', 'integer', 'in:0,1'],
            'button5' => ['required', 'integer', 'in:0,1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Payload IoT non valido.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $mqttTopic = $request->input('topic', $payload['topic'] ?? $this->mqttTopic());
        $volume = $data['pot_percentuale']
            ?? $data['volume']
            ?? (int) round(($data['potenziometro'] / 4095) * 100);

        $event = app(LatestDeviceEventStore::class)->put([
            'mqtt_topic' => $mqttTopic,
            'potenziometro' => $data['potenziometro'],
            'pot_percentuale' => $data['pot_percentuale'] ?? null,
            'volume' => $volume,
            'levetta' => $data['levetta'] ?? 0,
            'joystick_x_valore' => $data['joystick_x_valore'] ?? null,
            'joystick_x_posizione' => $data['joystick_x_posizione'] ?? null,
            'joystick_y_valore' => $data['joystick_y_valore'] ?? $data['joystick_y_value'] ?? null,
            'joystick_y_posizione' => $data['joystick_y_posizione'] ?? $data['joystick_y_position'] ?? null,
            'joystick_click' => $data['joystick_click'] ?? null,
            'button1' => $data['button1'],
            'button2' => $data['button2'],
            'button3' => $data['button3'],
            'button4' => $data['button4'],
            'button5' => $data['button5'],
            'raw_payload' => $payload,
        ]);

        return response()->json([
            'message' => 'Ultimo evento IoT aggiornato.',
            'event' => $event,
        ]);
    }

    private function normalizePayload(Request $request): array
    {
        $payload = $request->input('payload', $request->all());

        if (is_string($payload)) {
            $payload = trim($payload);
            $payload = preg_replace('/^Inviato:\s*/i', '', $payload);
            $payload = json_decode($payload, true) ?: [];
        }

        return is_array($payload) ? $payload : [];
    }

    private function buttonNames(): array
    {
        return Button::where('tipo', config('trackpad.default_type'))
            ->orderBy('id')
            ->take(5)
            ->get()
            ->mapWithKeys(function ($button, $index) {
                $name = ucfirst(str_replace('_', ' ', pathinfo(basename($button->sound_file), PATHINFO_FILENAME)));

                return [$index + 1 => $name];
            })
            ->all();
    }

    private function mqttTopic(): string
    {
        return config('services.mqtt.topic');
    }
}
