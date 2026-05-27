<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mappatura pulsanti - TrackPad MQTT</title>
    <link rel="stylesheet" href="{{ asset('css/navbar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/management.css') }}">
</head>
<body>
@include('partials.navbar')

<main class="page-shell">
    <section class="page-hero">
        <div>
            <p class="eyebrow">Hardware mapping</p>
            <h1>Mappatura pulsanti {{ $typeLabel }}</h1>
            <p>Scegli quali suoni usare quando nello Studio selezioni la categoria {{ $typeLabel }}. Le altre categorie si gestiscono dalla pagina Suoni.</p>
        </div>

        <a class="link-button secondary" href="{{ route('studio') }}">Apri studio</a>
    </section>

    @if (session('status'))
        <div class="alert success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert error">Controlla la selezione dei pulsanti.</div>
    @endif

    <section class="grid two">
        <article class="card">
            <h2>I tuoi 5 pulsanti {{ $typeLabel }}</h2>

            <form class="form-grid" method="POST" action="{{ route('buttons.mapping.update') }}">
                @csrf
                @method('PUT')

                @for ($slot = 1; $slot <= 5; $slot++)
                    <label>
                        Button {{ $slot }}
                        <select name="buttons[{{ $slot }}]" required>
                            @foreach ($buttons as $button)
                                @php
                                    $soundName = ucfirst(str_replace('_', ' ', pathinfo(basename($button->sound_file), PATHINFO_FILENAME)));
                                @endphp

                                <option
                                    value="{{ $button->id }}"
                                    @selected(($mappings[$slot]?->button_id ?? null) === $button->id)
                                >
                                    {{ $typeLabel }} - {{ $soundName }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                @endfor

                <button class="btn" type="submit">Salva mappatura</button>
            </form>
        </article>

        <article class="card">
            <h2>Preset standard</h2>
            <p class="muted">Lo standard iniziale usa i primi 5 suoni della categoria {{ $typeLabel }}.</p>

            <form method="POST" action="{{ route('buttons.mapping.reset') }}">
                @csrf
                <button class="btn secondary" type="submit">Reset pulsanti {{ $typeLabel }}</button>
            </form>
        </article>
    </section>
</main>
</body>
</html>
