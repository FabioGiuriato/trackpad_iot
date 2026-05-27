<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>TrackPad MQTT Studio</title>
    <link rel="stylesheet" href="{{ asset('css/navbar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/studio.css') }}">
</head>
<body>
@include('partials.navbar')

<div class="studio">
    <header class="topbar">
        <div>
            <h1>TrackPad MQTT Studio</h1>

            @if ($selectedSong)
                <p class="loaded-song">Canzone caricata: {{ $selectedSong->title }}</p>
            @endif
        </div>

        <div class="transport">
            <input
                class="song-title-input"
                id="songTitle"
                type="text"
                maxlength="100"
                placeholder="Nome canzone"
                value="{{ $selectedSong?->title }}"
            >
            <button class="btn secondary" id="saveSongBtn" type="button">Salva canzone</button>
            <button class="btn" id="playBtn">Play</button>
            <button class="btn secondary" id="stopBtn">Stop</button>
            <button class="btn danger" id="resetBtn">Reset</button>

            <div class="bpm-control">
                <label for="bpm">BPM</label>
                <input id="bpm" type="number" min="60" max="200" value="{{ $selectedBpm }}">
            </div>
        </div>
    </header>

    <main class="main">
        <section class="panel">
            <div class="panel-heading">
                <h2>Pad</h2>

                <select id="soundTypeFilter" class="type-filter">
                    @foreach ($musicTypes as $typeValue => $typeLabel)
                        <option value="{{ $typeValue }}" @selected((int) $typeValue === (int) $defaultType)>
                            {{ $typeLabel }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="pads">
                @php
                    $slotsByType = [];
                @endphp

                @forelse ($buttons as $index => $button)
                    @php
                        $soundName = ucfirst(str_replace('_', ' ', pathinfo(basename($button->sound_file), PATHINFO_FILENAME)));
                        $key = ['Q', 'W', 'E', 'R', 'T','Y','U'][$index] ?? '-';
                        $typeValue = (int) ($button->tipo ?? 0);
                        $slotsByType[$typeValue] = ($slotsByType[$typeValue] ?? 0) + 1;
                        $slot = $slotsByType[$typeValue];
                    @endphp

                    <button
                        class="pad"
                        data-channel="{{ $index }}"
                        data-button-id="{{ $button->id }}"
                        data-sound="{{ asset($button->sound_file) }}"
                        data-tipo="{{ $typeValue }}"
                        data-slot="{{ $slot }}"
                    >
                        <strong class="pad-key">{{ $key }}</strong>
                        <span class="badge">Button {{ $slot }}</span>
                        {{ $soundName }}
                    </button>
                @empty
                    <div class="empty">
                        Nessun suono nel database.
                    </div>
                @endforelse
            </div>
        </section>

        <section class="panel rack">
            <div class="rack-toolbar">
                <h2>Channel Rack</h2>

                @if ($buttons->isNotEmpty())
                    <div class="rack-actions">
                        <label for="stepCount">Step</label>
                        <select id="stepCount">
                            <option value="16">16</option>
                            <option value="32">32</option>
                            <option value="64">64</option>
                            <option value="128">128</option>
                            <option value="256">256</option>

                        </select>
                        <button class="btn secondary compact" id="expandRackBtn" type="button">+16</button>
                    </div>
                @endif
            </div>

            @if ($buttons->isNotEmpty())
                <div class="rack-header" id="rackHeader">
                    <div>Canale</div>
                </div>

                @foreach ($musicTypes as $tipo => $label)
                    @php
                        $groupButtons = $buttons->filter(fn ($button) => (int) ($button->tipo ?? 0) === $tipo);
                    @endphp

                    @if ($groupButtons->isNotEmpty())
                        <div class="rack-group" data-tipo="{{ $tipo }}">
                            <div class="rack-group-title">{{ $label }}</div>
                        </div>

                        @foreach ($groupButtons as $channelIndex => $button)
                            @php
                                $channelIndex = $buttons->search(fn ($item) => $item->id === $button->id);
                                $soundName = ucfirst(str_replace('_', ' ', pathinfo(basename($button->sound_file), PATHINFO_FILENAME)));
                                $slot = $loop->iteration;
                            @endphp

                            <div
                                class="rack-row"
                                data-channel-row="{{ $channelIndex }}"
                                data-tipo="{{ $button->tipo ?? 0 }}"
                            >
                                <div class="channel-strip">
                                    <button class="channel-name" type="button" data-toggle-channel="{{ $channelIndex }}">
                                        Button {{ $slot }} - {{ $soundName }}
                                    </button>

                                    <div class="channel-controls">
                                        <label>
                                            Vol
                                            <input
                                                class="channel-volume"
                                                data-channel="{{ $channelIndex }}"
                                                type="range"
                                                min="0"
                                                max="1"
                                                step="0.05"
                                                value="1"
                                            >
                                        </label>

                                        <button class="mini-btn mute-btn" data-channel="{{ $channelIndex }}" type="button">M</button>
                                        <button class="mini-btn solo-btn" data-channel="{{ $channelIndex }}" type="button">S</button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                @endforeach
            @else
                <div class="empty">
                    Aggiungi prima i 5 bottoni nella tabella buttons.
                </div>
            @endif
        </section>

        <aside class="panel">
            <h2>Console</h2>

            <div class="console-item">
                <label for="volume">Volume master</label>
                <input id="volume" type="range" min="0" max="1" step="0.1" value="1">
            </div>

            <div class="console-item">
                <strong>Comandi</strong>
                <p>Q W E R T suonano i pad. Spazio avvia o mette in pausa.</p>
            </div>

            <div class="console-item">
                <strong>Stato</strong>
                <p id="status">Fermo</p>
            </div>
        </aside>
    </main>
</div>

<script>
    const pads = document.querySelectorAll('.pad');
    const playBtn = document.getElementById('playBtn');
    const saveSongBtn = document.getElementById('saveSongBtn');
    const stopBtn = document.getElementById('stopBtn');
    const resetBtn = document.getElementById('resetBtn');
    const songTitleInput = document.getElementById('songTitle');
    const bpmInput = document.getElementById('bpm');
    const volumeInput = document.getElementById('volume');
    const statusText = document.getElementById('status');
    const soundTypeFilter = document.getElementById('soundTypeFilter');
    const rackHeader = document.getElementById('rackHeader');
    const stepCountInput = document.getElementById('stepCount');
    const expandRackBtn = document.getElementById('expandRackBtn');
    const rackGroups = document.querySelectorAll('.rack-group');
    const rackRows = document.querySelectorAll('.rack-row');
    const loadedPattern = @json($selectedPattern);
    const loadedStepCount = @json($selectedStepCount);
    const loadedSongTitle = @json($selectedSong?->title);
    const musicTypes = @json($musicTypes);
    const musicTypeValues = Object.keys(musicTypes).map(Number).sort((a, b) => a - b);
    const defaultType = String(@json($defaultType));
    const saveSongUrl = @json(route('songs.store'));
    const latestEventsUrl = @json(route('iot.events.latest'));
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    const defaultStepCount = 16;
    const maxStepCount = 256;
    const keyNames = ['Q', 'W', 'E', 'R', 'T','Y','U'];
    const keyMap = ['q', 'w', 'e', 'r', 't','y','u'];
    const savedStepDurationMs = 125;

    let currentStep = 0;
    let stepCount = defaultStepCount;
    let timer = null;
    let isPlaying = false;
    let lastDeviceEventId = Number(@json($latestDeviceEventId));
    let previousDeviceCounters = normalizeDeviceCounters(@json($latestDeviceCounters));
    const devicePollDelayMs = 120;

    function getSelectedSoundType() {
        if (!soundTypeFilter) {
            return defaultType;
        }

        return soundTypeFilter.value;
    }

    function isVisibleType(element) {
        return element.dataset.tipo === getSelectedSoundType();
    }

    function getVisiblePads() {
        return Array.from(pads).filter((pad) => !pad.hidden);
    }

    function updateVisibleShortcuts() {
        getVisiblePads().forEach((pad, index) => {
            const keyLabel = pad.querySelector('.pad-key');

            if (keyLabel) {
                keyLabel.textContent = keyNames[index] ?? '-';
            }
        });
    }

    function filterSoundType() {
        pads.forEach((pad) => {
            pad.hidden = !isVisibleType(pad);
        });

        rackRows.forEach((row) => {
            row.hidden = !isVisibleType(row);
        });

        rackGroups.forEach((group) => {
            group.hidden = !isVisibleType(group);
        });

        clearPlayhead();
        updateVisibleShortcuts();
        updateRackModeState();
    }

    function getSteps() {
        return document.querySelectorAll('.step');
    }

    function getActivePattern() {
        const activePattern = new Set();

        getSteps().forEach((step) => {
            if (step.classList.contains('active')) {
                activePattern.add(`${step.dataset.channel}:${step.dataset.step}`);
            }
        });

        return activePattern;
    }

    function getLoadedPattern() {
        return new Set(loadedPattern.map((event) => {
            return `${event.channel}:${event.step}`;
        }));
    }

    function getButtonIdByChannel(channelIndex) {
        const pad = document.querySelector(`.pad[data-channel="${channelIndex}"]`);

        if (!pad) {
            return null;
        }

        return Number(pad.dataset.buttonId);
    }

    function getSongEventsForSave() {
        return Array.from(getSteps())
            .filter((step) => step.classList.contains('active'))
            .map((step) => {
                return {
                    button_id: getButtonIdByChannel(step.dataset.channel),
                    time_ms: Number(step.dataset.step) * savedStepDurationMs,
                };
            })
            .filter((event) => event.button_id);
    }

    async function saveSong() {
        const title = songTitleInput.value.trim();
        const events = getSongEventsForSave();

        if (!title) {
            statusText.textContent = 'Inserisci il nome della canzone';
            songTitleInput.focus();
            return;
        }

        if (events.length === 0) {
            statusText.textContent = 'Attiva almeno uno step prima di salvare';
            return;
        }

        saveSongBtn.disabled = true;
        saveSongBtn.textContent = 'Salvataggio...';

        try {
            const response = await fetch(saveSongUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    title,
                    bpm: Number(bpmInput.value),
                    events,
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                statusText.textContent = data.message || 'Errore durante il salvataggio';
                return;
            }

            statusText.textContent = data.message;
            window.location.href = data.redirect;
        } catch (error) {
            statusText.textContent = 'Errore di rete durante il salvataggio';
        } finally {
            saveSongBtn.disabled = false;
            saveSongBtn.textContent = 'Salva canzone';
        }
    }

    function updateRackColumns() {
        const columns = `230px repeat(${stepCount}, 42px)`;

        if (rackHeader) {
            rackHeader.style.gridTemplateColumns = columns;
        }

        rackGroups.forEach((group) => {
            group.style.gridTemplateColumns = columns;
        });

        rackRows.forEach((row) => {
            row.style.gridTemplateColumns = columns;
        });
    }

    function renderRackSteps(nextStepCount, activePattern = getActivePattern()) {
        stepCount = Math.min(Math.max(nextStepCount, defaultStepCount), maxStepCount);
        currentStep = currentStep >= stepCount ? 0 : currentStep;

        rackHeader.querySelectorAll('.step-label').forEach((label) => {
            label.remove();
        });

        for (let step = 1; step <= stepCount; step++) {
            const label = document.createElement('div');
            label.className = 'step-label';
            label.textContent = step;
            rackHeader.appendChild(label);
        }

        rackRows.forEach((row) => {
            const channel = row.dataset.channelRow;

            row.querySelectorAll('.step').forEach((step) => {
                step.remove();
            });

            for (let step = 0; step < stepCount; step++) {
                const button = document.createElement('button');
                button.className = 'step';
                button.type = 'button';
                button.dataset.channel = channel;
                button.dataset.step = step;

                if (activePattern.has(`${channel}:${step}`)) {
                    button.classList.add('active');
                }

                button.addEventListener('click', () => {
                    button.classList.toggle('active');
                });

                row.appendChild(button);
            }
        });

        updateRackColumns();

        if (stepCountInput) {
            stepCountInput.value = String(stepCount);
        }
    }

    function updateTransportView() {
        playBtn.textContent = isPlaying ? 'Pausa' : 'Play';
        statusText.textContent = isPlaying ? 'In riproduzione' : 'In pausa';
    }

    function isChannelMuted(channelIndex) {
        const muteButton = document.querySelector(`.mute-btn[data-channel="${channelIndex}"]`);
        const soloButtons = document.querySelectorAll('.solo-btn.active');
        const soloButton = document.querySelector(`.solo-btn[data-channel="${channelIndex}"]`);

        if (muteButton && muteButton.classList.contains('active')) {
            return true;
        }

        return soloButtons.length > 0 && (!soloButton || !soloButton.classList.contains('active'));
    }

    function updateRackModeState() {
        const soloChannels = Array.from(document.querySelectorAll('.solo-btn.active')).map((button) => {
            return button.dataset.channel;
        });

        const mutedChannels = Array.from(document.querySelectorAll('.mute-btn.active')).map((button) => {
            return button.dataset.channel;
        });

        rackRows.forEach((row) => {
            const channel = row.dataset.channelRow;
            const isSoloFocused = soloChannels.includes(channel);
            const isMuted = mutedChannels.includes(channel);

            row.classList.toggle('solo-focused', isSoloFocused);
            row.classList.toggle('solo-dimmed', soloChannels.length > 0 && !isSoloFocused);
            row.classList.toggle('mute-dimmed', isMuted);
        });
    }

    function getChannelVolume(channelIndex) {
        const channelVolume = document.querySelector(`.channel-volume[data-channel="${channelIndex}"]`);

        if (!channelVolume) {
            return 1;
        }

        return Number(channelVolume.value);
    }

    function playChannel(channelIndex, options = {}) {
        if (isChannelMuted(channelIndex)) {
            return;
        }

        const pad = document.querySelector(`.pad[data-channel="${channelIndex}"]`);

        if (!pad || (pad.hidden && !options.ignoreVisibility)) {
            return;
        }

        const audio = new Audio(pad.dataset.sound);
        audio.volume = Number(volumeInput.value) * getChannelVolume(channelIndex);
        audio.play();

        pad.classList.add('playing');

        setTimeout(() => {
            pad.classList.remove('playing');
        }, 120);
    }

    function clearPlayhead() {
        getSteps().forEach((step) => {
            step.classList.remove('playhead');
        });
    }

    function runStep() {
        clearPlayhead();

        const currentColumnSteps = document.querySelectorAll(`.step[data-step="${currentStep}"]`);

        currentColumnSteps.forEach((step) => {
            step.classList.add('playhead');

            if (step.classList.contains('active')) {
                playChannel(step.dataset.channel, { ignoreVisibility: true });
            }
        });

        currentStep++;

        if (currentStep >= stepCount) {
            currentStep = 0;
        }
    }

    function getStepDuration() {
        const bpm = Number(bpmInput.value);

        return 60000 / bpm / 4;
    }

    function startSequencer() {
        if (isPlaying) {
            return;
        }

        isPlaying = true;
        updateTransportView();

        runStep();

        timer = setInterval(() => {
            runStep();
        }, getStepDuration());
    }

    function pauseSequencer() {
        isPlaying = false;
        clearInterval(timer);
        updateTransportView();
    }

    function toggleSequencer() {
        if (isPlaying) {
            pauseSequencer();
            return;
        }

        startSequencer();
    }

    function stopSequencer() {
        pauseSequencer();
        currentStep = 0;
        clearPlayhead();
        statusText.textContent = 'Fermo';
        playBtn.textContent = 'Play';
    }

    function resetSequencer() {
        stopSequencer();

        renderRackSteps(defaultStepCount, new Set());
        bpmInput.value = 120;
        volumeInput.value = 1;

        document.querySelectorAll('.channel-volume').forEach((input) => {
            input.value = 1;
        });

        document.querySelectorAll('.mute-btn, .solo-btn').forEach((button) => {
            button.classList.remove('active');
        });

        if (soundTypeFilter) {
            soundTypeFilter.value = defaultType;
        }

        filterSoundType();
        updateRackModeState();
        statusText.textContent = 'Reset completato';
    }

    function clamp(value, min, max) {
        return Math.min(Math.max(value, min), max);
    }

    function setMasterVolumeFromDevice(event) {
        const percent = Number(event.pot_percentuale ?? event.volume);

        if (Number.isNaN(percent)) {
            return;
        }

        volumeInput.value = String(clamp(percent, 0, 100) / 100);
    }

    function changeSoundType(delta) {
        if (!soundTypeFilter || musicTypeValues.length === 0) {
            return;
        }

        const currentType = Number(getSelectedSoundType());
        const currentIndex = Math.max(0, musicTypeValues.indexOf(currentType));
        const nextIndex = (currentIndex + delta + musicTypeValues.length) % musicTypeValues.length;
        const nextType = musicTypeValues[nextIndex];

        soundTypeFilter.value = String(nextType);
        filterSoundType();
        statusText.textContent = `Tipo selezionato: ${musicTypes[nextType]}`;
    }

    function getRecordingStep() {
        const playhead = document.querySelector('.step.playhead');

        if (playhead) {
            return Number(playhead.dataset.step);
        }

        return currentStep;
    }

    function getPadByTypeAndSlot(type, slot) {
        return document.querySelector(`.pad[data-tipo="${type}"][data-slot="${slot}"]`);
    }

    function addStepForChannel(channelIndex) {
        const stepIndex = clamp(getRecordingStep(), 0, stepCount - 1);
        const step = document.querySelector(`.step[data-channel="${channelIndex}"][data-step="${stepIndex}"]`);

        if (step) {
            step.classList.add('active');
        }
    }

    function triggerHardwareButton(slot) {
        const selectedType = getSelectedSoundType();
        const pad = getPadByTypeAndSlot(selectedType, slot);

        if (!pad) {
            statusText.textContent = `Button ${slot} non trovato nel tipo ${musicTypes[selectedType] ?? selectedType}`;
            return;
        }

        playChannel(pad.dataset.channel, { ignoreVisibility: true });
        addStepForChannel(pad.dataset.channel);
        statusText.textContent = `ESP32: registrato Button ${slot} sullo step ${getRecordingStep() + 1}`;
    }

    function normalizeDeviceCounters(counters = {}) {
        const buttons = counters.buttons ?? {};

        return {
            buttons: {
                1: Number(buttons[1] ?? buttons['1'] ?? 0),
                2: Number(buttons[2] ?? buttons['2'] ?? 0),
                3: Number(buttons[3] ?? buttons['3'] ?? 0),
                4: Number(buttons[4] ?? buttons['4'] ?? 0),
                5: Number(buttons[5] ?? buttons['5'] ?? 0),
            },
            joystickLeft: Number(counters.joystick_left ?? 0),
            joystickRight: Number(counters.joystick_right ?? 0),
            joystickClick: Number(counters.joystick_click ?? 0),
        };
    }

    function processDeviceEvent(event) {
        const counters = normalizeDeviceCounters(event.counters);

        setMasterVolumeFromDevice(event);

        if (counters.joystickLeft > previousDeviceCounters.joystickLeft) {
            changeSoundType(-1);
        }

        if (counters.joystickRight > previousDeviceCounters.joystickRight) {
            changeSoundType(1);
        }

        if (counters.joystickClick > previousDeviceCounters.joystickClick) {
            toggleSequencer();
        }

        [1, 2, 3, 4, 5].forEach((slot) => {
            if (counters.buttons[slot] > previousDeviceCounters.buttons[slot]) {
                triggerHardwareButton(slot);
            }
        });

        previousDeviceCounters = counters;
    }

    async function pollDeviceEvents() {
        try {
            const response = await fetch(`${latestEventsUrl}?after=${lastDeviceEventId}&t=${Date.now()}`, {
                cache: 'no-store',
                headers: {
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();

            data.events.forEach((event) => {
                processDeviceEvent(event);
                lastDeviceEventId = Math.max(lastDeviceEventId, Number(event.id));
            });
        } catch (error) {
            statusText.textContent = 'Connessione live ESP32 non disponibile';
        } finally {
            setTimeout(pollDeviceEvents, devicePollDelayMs);
        }
    }

    pads.forEach((pad) => {
        pad.addEventListener('click', () => {
            playChannel(pad.dataset.channel);
        });
    });

    document.querySelectorAll('[data-toggle-channel]').forEach((button) => {
        button.addEventListener('click', () => {
            button.closest('.channel-strip').classList.toggle('expanded');
        });
    });

    document.querySelectorAll('.mute-btn, .solo-btn').forEach((button) => {
        button.addEventListener('click', () => {
            button.classList.toggle('active');
            updateRackModeState();
        });
    });

    playBtn.addEventListener('click', toggleSequencer);
    saveSongBtn.addEventListener('click', saveSong);
    stopBtn.addEventListener('click', stopSequencer);
    resetBtn.addEventListener('click', resetSequencer);

    bpmInput.addEventListener('change', () => {
        if (isPlaying) {
            clearInterval(timer);

            timer = setInterval(() => {
                runStep();
            }, getStepDuration());
        }
    });

    if (stepCountInput) {
        stepCountInput.addEventListener('change', () => {
            renderRackSteps(Number(stepCountInput.value));
        });
    }

    if (expandRackBtn) {
        expandRackBtn.addEventListener('click', () => {
            renderRackSteps(stepCount + 16);
        });
    }

    if (soundTypeFilter) {
        soundTypeFilter.addEventListener('change', filterSoundType);
    }

    document.addEventListener('keydown', (event) => {
        const activeElement = document.activeElement;

        if (activeElement && ['INPUT', 'TEXTAREA', 'SELECT'].includes(activeElement.tagName)) {
            return;
        }

        if (event.code === 'Space') {
            event.preventDefault();
            toggleSequencer();
            return;
        }

        const visiblePadIndex = keyMap.indexOf(event.key.toLowerCase());

        if (visiblePadIndex === -1) {
            return;
        }

        const pad = getVisiblePads()[visiblePadIndex];

        if (!pad) {
            return;
        }

        playChannel(pad.dataset.channel);
    });

    if (rackHeader) {
        renderRackSteps(loadedStepCount || defaultStepCount, getLoadedPattern());
        filterSoundType();

        if (loadedSongTitle) {
            statusText.textContent = `Canzone caricata: ${loadedSongTitle}`;
        }

        pollDeviceEvents();
    }
</script>
</body>
</html>
