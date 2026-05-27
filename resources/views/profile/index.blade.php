<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilo - TrackPad MQTT</title>
    <link rel="stylesheet" href="{{ asset('css/navbar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/management.css') }}">
</head>
<body>
@include('partials.navbar')

<main class="page-shell">
    <section class="page-hero">
        <div>
            <p class="eyebrow">Account</p>
            <h1>Profilo</h1>
            <p>Gestisci i dati del tuo account e aggiorna la password.</p>
        </div>
    </section>

    @if (session('status'))
        <div class="alert success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert error">Controlla i campi evidenziati e riprova.</div>
    @endif

    <section class="grid two">
        <article class="card">
            <h2>Dati account</h2>

            <form class="form-grid" method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PUT')

                <label>
                    Username
                    <input type="text" name="username" value="{{ old('username', $user->username) }}" required>
                </label>

                <label>
                    Email
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
                </label>

                <button class="btn" type="submit">Salva profilo</button>
            </form>
        </article>

        <article class="card">
            <h2>Cambia password</h2>

            <form class="form-grid" method="POST" action="{{ route('profile.password') }}">
                @csrf
                @method('PUT')

                <label>
                    Password attuale
                    <input type="password" name="current_password" required>
                </label>

                <label>
                    Nuova password
                    <input type="password" name="password" minlength="8" required>
                </label>

                <label>
                    Conferma nuova password
                    <input type="password" name="password_confirmation" minlength="8" required>
                </label>

                <button class="btn secondary" type="submit">Aggiorna password</button>
            </form>
        </article>
    </section>
</main>
</body>
</html>
