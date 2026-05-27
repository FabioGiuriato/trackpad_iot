<header class="site-navbar">
    <a class="site-logo" href="{{ auth()->check() ? route('studio') : route('auth') }}">Trackpad mqtt</a>

    <nav class="site-nav">
        @auth
            <a class="nav-link {{ request()->routeIs('studio') ? 'active' : '' }}" href="{{ route('studio') }}">Studio</a>
            <a class="nav-link {{ request()->routeIs('songs.*') ? 'active' : '' }}" href="{{ route('songs.index') }}">Le mie canzoni</a>
            <a class="nav-link {{ request()->routeIs('sounds.*') ? 'active' : '' }}" href="{{ route('sounds.index') }}">Suoni</a>
            <a class="nav-link {{ request()->routeIs('buttons.*') ? 'active' : '' }}" href="{{ route('buttons.mapping') }}">Pulsanti</a>
            <a class="nav-link {{ request()->routeIs('iot.*') ? 'active' : '' }}" href="{{ route('iot.live') }}">MQTT</a>
            <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.show') }}">Profilo</a>
        @else
            <a class="nav-link {{ request()->routeIs('auth') ? 'active' : '' }}" href="{{ route('auth') }}">Accesso</a>
        @endauth
    </nav>

    <div class="user-area">
        @auth
            <div class="user-badge" title="{{ auth()->user()->username }}">
                <svg class="user-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5Z"></path>
                    <path d="M4 22c0-4.42 3.58-8 8-8s8 3.58 8 8"></path>
                </svg>
                <span>{{ auth()->user()->username }}</span>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="logout-button" type="submit">Logout</button>
            </form>
        @else
            <a class="user-icon-link {{ request()->routeIs('auth') ? 'active' : '' }}" href="{{ route('auth') }}" aria-label="Vai alla pagina di accesso">
                <svg class="user-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5Z"></path>
                    <path d="M4 22c0-4.42 3.58-8 8-8s8 3.58 8 8"></path>
                </svg>
            </a>
        @endauth
    </div>
</header>
