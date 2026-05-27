<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Le mie canzoni - TrackPad MQTT</title>
    <link rel="stylesheet" href="{{ asset('css/navbar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/songs.css') }}">
</head>
<body>
@include('partials.navbar')

<main class="songs-page">
    <section class="songs-hero">
        <div>
            <p class="eyebrow">Library</p>
            <h1>Le mie canzoni</h1>
            <p>Qui trovi i pattern salvati nel database. Premi Riproduci per aprirli nello studio con gli step gia selezionati.</p>
        </div>
    </section>

    @if (session('status'))
        <div class="song-alert success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="song-alert error">{{ $errors->first() }}</div>
    @endif

    <section class="songs-grid">
        @forelse ($songs as $song)
            <article class="song-card">
                <div class="song-info">
                    <span class="song-id">#{{ $song->id }}</span>
                    <h2>{{ $song->title }}</h2>
                    <p>{{ $song->events_count }} note salvate - {{ $song->bpm }} BPM</p>
                </div>

                <div class="song-meta">
                    <span>Aggiornata {{ $song->updated_at?->format('d/m/Y H:i') }}</span>

                    <form class="rename-song-form" method="POST" action="{{ route('songs.update', $song) }}">
                        @csrf
                        @method('PUT')
                        <input
                            type="text"
                            name="title"
                            maxlength="100"
                            value="{{ $song->title }}"
                            aria-label="Nuovo nome per {{ $song->title }}"
                            required
                        >
                        <button class="small-button secondary" type="submit">Rinomina</button>
                    </form>

                    <a class="play-link" href="{{ route('songs.play', $song) }}">Riproduci</a>

                    <form method="POST" action="{{ route('songs.destroy', $song) }}" onsubmit="return confirm('Vuoi eliminare questa canzone?');">
                        @csrf
                        @method('DELETE')
                        <button class="small-button danger" type="submit">Elimina</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="empty-library">
                <h2>Nessuna canzone salvata</h2>
                <p>Quando salverai un pattern, comparira qui e potrai riaprirlo nello studio.</p>
                <a class="hero-button" href="{{ route('studio') }}">Crea un pattern</a>
            </div>
        @endforelse
    </section>
</main>
</body>
</html>
