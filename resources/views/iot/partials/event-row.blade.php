@php
    $createdAt = data_get($event, 'created_at');
    $createdAtLabel = $createdAt ? \Illuminate\Support\Carbon::parse($createdAt)->format('H:i:s') : '-';
@endphp

<article class="event-row">
    <div>
        <p class="event-title">ESP32 Trackpad - {{ $createdAtLabel }}</p>
        <p class="muted">Topic {{ data_get($event, 'mqtt_topic') }}</p>
        <div>
            <span class="button-state {{ data_get($event, 'button1') ? 'active' : '' }}" title="{{ $buttonNames[1] ?? 'Button 1' }}">1</span>
            <span class="button-state {{ data_get($event, 'button2') ? 'active' : '' }}" title="{{ $buttonNames[2] ?? 'Button 2' }}">2</span>
            <span class="button-state {{ data_get($event, 'button3') ? 'active' : '' }}" title="{{ $buttonNames[3] ?? 'Button 3' }}">3</span>
            <span class="button-state {{ data_get($event, 'button4') ? 'active' : '' }}" title="{{ $buttonNames[4] ?? 'Button 4' }}">4</span>
            <span class="button-state {{ data_get($event, 'button5') ? 'active' : '' }}" title="{{ $buttonNames[5] ?? 'Button 5' }}">5</span>
        </div>
        <p class="muted">
            Potenziometro {{ data_get($event, 'potenziometro') }}
            - Volume {{ data_get($event, 'volume') }}%
            - Joystick {{ data_get($event, 'joystick_x_posizione', 'CENTRO') }}
            - Click {{ data_get($event, 'joystick_click', 'NON_PREMUTO') }}
        </p>
    </div>

    <pre class="raw-json">{{ json_encode(data_get($event, 'raw_payload'), JSON_PRETTY_PRINT) }}</pre>
</article>
