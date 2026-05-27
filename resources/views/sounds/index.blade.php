<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione suoni - TrackPad MQTT</title>
    <link rel="stylesheet" href="{{ asset('css/navbar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/management.css') }}">
</head>
<body>
@include('partials.navbar')

<main class="page-shell">
    <section class="page-hero">
        <div>
            <p class="eyebrow">Sound bank</p>
            <h1>Gestione suoni</h1>
            <p>Modifica i pad presenti nel channel rack, cambia categoria musicale o sostituisci il file audio.</p>
        </div>

        <a class="link-button" href="{{ route('sounds.upload') }}">Upload MP3</a>
    </section>

    @if (session('status'))
        <div class="alert success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert error">Controlla i campi e riprova.</div>
    @endif

    <section class="grid">
        @forelse ($buttons as $button)
            @php
                $soundName = ucfirst(str_replace('_', ' ', pathinfo(basename($button->sound_file), PATHINFO_FILENAME)));
            @endphp

            <article class="sound-row">
                <div>
                    <p class="sound-title">{{ $soundName }}</p>
                    <span class="badge">{{ $musicTypes[$button->tipo] ?? 'Tipo ' . $button->tipo }}</span>
                    <p class="muted">{{ $button->sound_file }}</p>
                    <audio controls src="{{ asset($button->sound_file) }}"></audio>
                </div>

                <div class="form-grid">
                    <form class="inline-form" method="POST" action="{{ route('sounds.update', $button) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <label>
                            Tipo
                            <select name="tipo">
                                @foreach ($musicTypes as $typeValue => $typeLabel)
                                    <option value="{{ $typeValue }}" @selected((int) $button->tipo === (int) $typeValue)>
                                        {{ $typeLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label>
                            Sostituisci file
                            <input type="file" name="sound" accept=".mp3,.wav,.ogg,audio/*">
                        </label>

                        <button class="btn secondary" type="submit">Aggiorna</button>
                    </form>

                    <form method="POST" action="{{ route('sounds.destroy', $button) }}">
                        @csrf
                        @method('DELETE')
                        <button class="btn danger" type="submit">Elimina</button>
                    </form>
                </div>
            </article>
        @empty
            <article class="card">
                <h2>Nessun suono</h2>
                <p class="muted">Carica il primo MP3 per far comparire un pad nello studio.</p>
            </article>
        @endforelse
    </section>
</main>
</body>
</html>
