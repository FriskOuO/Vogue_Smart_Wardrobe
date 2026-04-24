<header class="vogue-shell py-6 md:py-8">
    <nav class="vogue-nav">
        <a href="{{ route('dashboard') }}" class="vogue-brand">
            <span class="vogue-brand-mark">V</span>
            <span>VogueAI</span>
        </a>

        <div class="vogue-nav-links">
            <a href="{{ route('dashboard') }}" data-i18n="nav_dashboard">儀表板</a>
            <a href="{{ route('profile.show') }}" data-i18n="nav_account">帳號總覽</a>
            @if (auth()->user()->isAdmin())
                <a href="{{ route('admin.users.index') }}" data-i18n="nav_users">使用者管理</a>
            @endif
        </div>

        <div class="vogue-nav-cta">
            <div class="vogue-tools">
                <button id="vogue-lang-toggle" type="button" class="vogue-switch" aria-label="Toggle language">
                    <span class="vogue-switch-label" data-i18n="switch_lang">中 / EN</span>
                </button>
                <button id="vogue-theme-toggle" type="button" class="vogue-switch" aria-label="Toggle theme">
                    <span class="vogue-switch-label" data-i18n="switch_theme">夜間</span>
                </button>
            </div>

            <form method="POST" action="{{ route('logout') }}" class="inline-block">
                @csrf
                <button type="submit" class="vogue-btn vogue-btn-solid" data-i18n="logout">登出</button>
            </form>
        </div>
    </nav>
</header>