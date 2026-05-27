<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dispositivo IoT - TrackPad MQTT</title>
    <link rel="stylesheet" href="{{ asset('css/navbar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/management.css') }}">
</head>
<body>
@include('partials.navbar')

<main class="page-shell">
    @php
        $latestCreatedAt = data_get($latestEvent, 'created_at');
        $latestCreatedAtLabel = $latestCreatedAt
            ? \Illuminate\Support\Carbon::parse($latestCreatedAt)->format('d/m/Y H:i:s')
            : 'mai ricevuto';
    @endphp

    <section class="page-hero">
        <div>
            <p class="eyebrow">ESP32</p>
            <h1>Dispositivo IoT</h1>
            <p>Stato del dispositivo, topic MQTT e formato JSON atteso dal sistema.</p>
        </div>

        <a class="link-button" href="{{ route('iot.live') }}">Vai al live</a>
    </section>

    <section class="grid two">
        <article class="card">
            <h2>ESP32 unico</h2>

            <div class="device-row">
                <div>
                    <p class="event-title">ESP32 Trackpad</p>
                    <span class="badge">{{ $mqttTopic }}</span>
                    <p class="muted">
                        Ultimo segnale:
                        {{ $latestCreatedAtLabel }}
                    </p>
                </div>
            </div>
        </article>

        <article class="card">
            <h2>JSON atteso</h2>
            <pre class="raw-json">{"button1":0,"button2":0,"button3":0,"button4":0,"button5":0,"potenziometro":3145,"pot_percentuale":76,"joystick_x_valore":1936,"joystick_x_posizione":"CENTRO","joystick_y_valore":1943,"joystick_y_posizione":"CENTRO","joystick_click":"NON_PREMUTO"}</pre>
            <p class="muted">Topic MQTT: <strong>{{ $mqttTopic }}</strong></p>
            <p class="muted">I 5 bottoni fisici vengono collegati ai 5 suoni del tipo selezionato nello Studio.</p>
        </article>
    </section>

    @if ($latestEvent)
        <section class="card" style="margin-top: 16px;">
            <h2>Ultimo evento ricevuto</h2>
            <div class="meter-grid">
                <div class="meter"><span>Potenziometro</span><strong>{{ data_get($latestEvent, 'potenziometro') }}</strong></div>
                <div class="meter"><span>Volume master</span><strong>{{ data_get($latestEvent, 'volume') }}%</strong></div>
                <div class="meter"><span>Joystick X</span><strong>{{ data_get($latestEvent, 'joystick_x_posizione', 'CENTRO') }}</strong></div>
                <div class="meter"><span>Joystick Y</span><strong>{{ data_get($latestEvent, 'joystick_y_posizione', 'CENTRO') }}</strong></div>
                <div class="meter"><span>Ora</span><strong>{{ \Illuminate\Support\Carbon::parse(data_get($latestEvent, 'created_at'))->format('H:i:s') }}</strong></div>
            </div>
        </section>
    @endif
</main>
</body>
</html>
