<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Live MQTT - TrackPad MQTT</title>
    <link rel="stylesheet" href="{{ asset('css/navbar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/management.css') }}">
</head>
<body>
@include('partials.navbar')

<main class="page-shell">
    <section class="page-hero">
        <div>
            <p class="eyebrow">MQTT monitor</p>
            <h1>Live MQTT</h1>
            <p>Qui vedrai in tempo reale i messaggi JSON mandati dal dispositivo sul topic <strong>{{ $mqttTopic }}</strong>. I bottoni fisici lavorano sul tipo selezionato nello Studio.</p>
        </div>

    </section>

    <section class="grid">
        <article class="card">
            <h2>Ultimi valori</h2>
            <div class="meter-grid" id="latestMeters">
                <div class="meter"><span>Potenziometro</span><strong>-</strong></div>
                <div class="meter"><span>Volume master</span><strong>-</strong></div>
                <div class="meter"><span>Joystick X</span><strong>-</strong></div>
                <div class="meter"><span>Joystick Y</span><strong>-</strong></div>
                <div class="meter"><span>Ora</span><strong>-</strong></div>
            </div>
            <p class="muted" id="iotStatus">In attesa del listener MQTT.</p>
        </article>
    </section>

    <section class="card" style="margin-top: 16px;">
        <h2>Ultimo messaggio ricevuto</h2>
        <div id="latestEventCard">
            @if ($latestEvent)
                @include('iot.partials.event-row', ['event' => $latestEvent, 'buttonNames' => $buttonNames])
            @else
                <p class="muted">Nessun messaggio ricevuto dal dispositivo.</p>
            @endif
        </div>
    </section>
</main>

<script>
    const latestEventCard = document.getElementById('latestEventCard');
    const latestMeters = document.getElementById('latestMeters');
    const statusText = document.getElementById('iotStatus');
    const latestEventsUrl = @json(route('iot.events.latest'));
    const buttonNames = @json($buttonNames);
    const mqttTopic = @json($mqttTopic);
    let lastEventId = Number(@json($latestEvent['id'] ?? 0));
    const pollDelayMs = 100;
    const pollTimeoutMs = 1000;
    let pollInFlight = false;

    function buttonState(value, label) {
        const name = buttonNames[label] || `Button ${label}`;

        return `<span class="button-state ${Number(value) === 1 ? 'active' : ''}" title="${name}">${label}</span>`;
    }

    function renderEvent(event) {
        const createdAt = new Date(event.created_at).toLocaleTimeString();
        const joystickX = event.joystick_x_posizione ?? 'CENTRO';
        const joystickY = event.joystick_y_posizione ?? 'CENTRO';

        return `
            <article class="event-row">
                <div>
                    <p class="event-title">ESP32 Trackpad - ${createdAt}</p>
                    <p class="muted">Topic ${event.mqtt_topic ?? mqttTopic}</p>
                    <div>
                        ${buttonState(event.button1, '1')}
                        ${buttonState(event.button2, '2')}
                        ${buttonState(event.button3, '3')}
                        ${buttonState(event.button4, '4')}
                        ${buttonState(event.button5, '5')}
                    </div>
                    <p class="muted">Potenziometro ${event.potenziometro} - Volume ${event.volume}% - Joystick X ${joystickX} - Y ${joystickY} - Click ${event.joystick_click ?? 'NON_PREMUTO'}</p>
                </div>
                <pre class="raw-json">${JSON.stringify(event.raw_payload, null, 2)}</pre>
            </article>
        `;
    }

    function updateMeters(event) {
        if (!event) {
            return;
        }

        const createdAt = new Date(event.created_at).toLocaleTimeString();
        const joystickX = event.joystick_x_posizione ?? 'CENTRO';
        const joystickY = event.joystick_y_posizione ?? 'CENTRO';

        latestMeters.innerHTML = `
            <div class="meter"><span>Potenziometro</span><strong>${event.potenziometro}</strong></div>
            <div class="meter"><span>Volume master</span><strong>${event.volume}%</strong></div>
            <div class="meter"><span>Joystick X</span><strong>${joystickX}</strong></div>
            <div class="meter"><span>Joystick Y</span><strong>${joystickY}</strong></div>
            <div class="meter"><span>Ora</span><strong>${createdAt}</strong></div>
        `;
    }

    async function loadEvents() {
        if (pollInFlight) {
            setTimeout(loadEvents, pollDelayMs);
            return;
        }

        pollInFlight = true;
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), pollTimeoutMs);

        try {
            const response = await fetch(`${latestEventsUrl}?after=${lastEventId}&t=${Date.now()}`, {
                cache: 'no-store',
                signal: controller.signal,
                headers: {
                    'Accept': 'application/json',
                },
            });
            const data = await response.json();

            if (data.events.length > 0) {
                const event = data.events[0];
                latestEventCard.innerHTML = renderEvent(event);
                updateMeters(event);
                lastEventId = Number(event.id);
                statusText.textContent = 'Messaggi MQTT in arrivo.';
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                statusText.textContent = 'Listener MQTT non raggiungibile o server non attivo.';
            }
        } finally {
            clearTimeout(timeoutId);
            pollInFlight = false;
            setTimeout(loadEvents, pollDelayMs);
        }
    }

    async function loadInitialEvent() {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), pollTimeoutMs);

        try {
            const response = await fetch(`${latestEventsUrl}?t=${Date.now()}`, {
                cache: 'no-store',
                signal: controller.signal,
                headers: {
                    'Accept': 'application/json',
                },
            });
            const data = await response.json();

            if (data.events.length > 0) {
                const event = data.events[0];
                latestEventCard.innerHTML = renderEvent(event);
                updateMeters(event);
                lastEventId = Number(event.id);
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                statusText.textContent = 'Listener MQTT non raggiungibile o server non attivo.';
            }
        } finally {
            clearTimeout(timeoutId);
        }
    }

    loadInitialEvent().finally(() => setTimeout(loadEvents, pollDelayMs));
</script>
</body>
</html>
