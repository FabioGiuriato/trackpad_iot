<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accesso TrackPad MQTT Studio</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body>
<main class="auth-page">
    <section class="auth-panel">
        <div class="brand">
            <span class="brand-mark">TP</span>
            <div>
                <h1>TrackPad Studio</h1>
                <p>Accedi o crea un profilo per salvare i tuoi pattern.</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert error-alert">
                Controlla i campi evidenziati e riprova.
            </div>
        @endif

        @if (session('status'))
            <div class="alert success-alert">
                {{ session('status') }}
            </div>
        @endif

        <div class="auth-tabs" role="tablist">
            <button class="tab-button active" type="button" data-auth-tab="login">Login</button>
            <button class="tab-button" type="button" data-auth-tab="register">Register</button>
        </div>

        <div class="auth-forms">
            <form class="auth-form active" id="loginForm" method="POST" action="{{ route('login') }}">
                @csrf

                <label>
                    Email
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        required
                    >
                    @error('email')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </label>

                <label>
                    Password
                    <input
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        required
                    >
                    @error('password')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </label>

                <button class="submit-button" type="submit">Accedi</button>
            </form>

            <form class="auth-form" id="registerForm" method="POST" action="{{ route('register') }}">
                @csrf

                <label>
                    Username
                    <input
                        type="text"
                        name="username"
                        value="{{ old('username') }}"
                        autocomplete="username"
                        required
                    >
                    @error('username')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </label>

                <label>
                    Email
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        required
                    >
                    @error('email')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </label>

                <label>
                    Password
                    <input
                        type="password"
                        name="password"
                        autocomplete="new-password"
                        minlength="8"
                        placeholder="Minimo 8 caratteri"
                        required
                    >
                    @error('password')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </label>

                <label>
                    Conferma password
                    <input
                        type="password"
                        name="password_confirmation"
                        autocomplete="new-password"
                        minlength="8"
                        placeholder="Minimo 8 caratteri"
                        required
                    >
                </label>

                <button class="submit-button" type="submit">Crea account</button>
            </form>
        </div>
    </section>
</main>

<script>
    const tabButtons = document.querySelectorAll('[data-auth-tab]');
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');

    function showAuthForm(formName) {
        tabButtons.forEach((button) => {
            button.classList.toggle('active', button.dataset.authTab === formName);
        });

        loginForm.classList.toggle('active', formName === 'login');
        registerForm.classList.toggle('active', formName === 'register');
    }

    tabButtons.forEach((button) => {
        button.addEventListener('click', () => {
            showAuthForm(button.dataset.authTab);
        });
    });

    if (window.location.hash === '#register') {
        showAuthForm('register');
    }

    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            window.location.reload();
        }
    });
</script>
</body>
</html>
