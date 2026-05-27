<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload MP3 - TrackPad MQTT</title>
    <link rel="stylesheet" href="{{ asset('css/navbar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/management.css') }}">
</head>
<body>
@include('partials.navbar')

<main class="page-shell">
    <section class="page-hero">
        <div>
            <p class="eyebrow">Upload</p>
            <h1>Upload MP3</h1>
            <p>Carica un nuovo suono. Il file verra salvato in <strong>public/sounds</strong> e aggiunto alla tabella <strong>buttons</strong>.</p>
        </div>

        <a class="link-button secondary" href="{{ route('sounds.index') }}">Torna ai suoni</a>
    </section>

    @if ($errors->any())
        <div class="alert error">File non valido o dati mancanti.</div>
    @endif

    <section class="card">
        <form class="form-grid" method="POST" action="{{ route('sounds.store') }}" enctype="multipart/form-data">
            @csrf

            <label>
                Categoria
                <select name="tipo" required>
                    @foreach ($musicTypes as $typeValue => $typeLabel)
                        <option value="{{ $typeValue }}">{{ $typeLabel }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                File audio
                <input type="file" name="sound" accept=".mp3,.wav,.ogg,audio/*" required>
            </label>

            <button class="btn" type="submit">Carica suono</button>
        </form>
    </section>
</main>
</body>
</html>
