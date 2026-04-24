<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>VogueAI | {{ __('admin.user_detail') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="vogue-home-body vogue-is-loading antialiased">
    <div class="vogue-theme-layer vogue-theme-layer-dark" aria-hidden="true"></div>
    <div class="vogue-theme-layer vogue-theme-layer-light" aria-hidden="true"></div>
    <div id="admin-show-skeleton" class="vogue-skeleton" aria-hidden="true">
        <div class="vogue-skeleton-shell">
            <div class="vogue-skeleton-nav"><div class="vogue-skeleton-brand"></div></div>
            <div class="vogue-skeleton-hero">
                <div class="vogue-skeleton-line vogue-skeleton-line-title"></div>
                <div class="vogue-skeleton-line vogue-skeleton-line-copy"></div>
                <div class="vogue-skeleton-line vogue-skeleton-line-copy short"></div>
            </div>
            <div class="vogue-skeleton-grid"><div></div><div></div><div></div></div>
        </div>
    </div>
    <div class="vogue-bg-orb vogue-bg-orb-a" aria-hidden="true"></div>
    <div class="vogue-bg-orb vogue-bg-orb-b" aria-hidden="true"></div>

    <header class="vogue-shell py-6 md:py-8">
        <nav class="vogue-nav">
            <a href="{{ route('dashboard') }}" class="vogue-brand"><span class="vogue-brand-mark">V</span><span>VogueAI</span></a>
            <div class="vogue-nav-links">
                <a href="{{ route('admin.users.index') }}" data-i18n="nav_users">Users</a>
                <a href="{{ route('admin.users.edit', $user) }}" data-i18n="nav_edit">Edit</a>
            </div>
            <div class="vogue-nav-cta">
                <div class="vogue-tools">
                    <button id="show-lang-toggle" type="button" class="vogue-switch"><span class="vogue-switch-label" data-i18n="switch_lang">中 / EN</span></button>
                    <button id="show-theme-toggle" type="button" class="vogue-switch"><span class="vogue-switch-label" data-i18n="switch_theme">夜間</span></button>
                </div>
                <a href="{{ route('admin.users.edit', $user) }}" class="vogue-btn vogue-btn-soft" data-i18n="edit">編輯</a>
                <form method="POST" action="{{ route('logout') }}" class="inline-block">@csrf
                    <button type="submit" class="vogue-btn vogue-btn-solid" data-i18n="logout">登出</button>
                </form>
            </div>
        </nav>
    </header>

    <main class="vogue-shell pb-16 md:pb-24 space-y-6">
        <section class="vogue-highlight reveal">
            <div>
                <p class="vogue-eyebrow" data-i18n="eyebrow">ADMIN DETAIL</p>
                <h2>{{ $user->name }}</h2>
                <p>{{ $user->email }}</p>
            </div>
            <ul class="vogue-points">
                <li data-i18n="point_1">可檢視角色與加入時間</li>
                <li data-i18n="point_2">可直接跳轉編輯或刪除</li>
                <li data-i18n="point_3">與 admin / user 權限模型一致</li>
            </ul>
        </section>

        <section class="vogue-grid">
            <article class="vogue-card reveal">
                <h3 data-i18n="name">{{ __('admin.name') }}</h3>
                <p>{{ $user->name }}</p>
            </article>
            <article class="vogue-card reveal">
                <h3 data-i18n="email">{{ __('admin.email') }}</h3>
                <p>{{ $user->email }}</p>
            </article>
            <article class="vogue-card reveal">
                <h3 data-i18n="role">{{ __('admin.role') }}</h3>
                <p>{{ __('admin.' . $user->role) }}</p>
            </article>
            <article class="vogue-card reveal">
                <h3 data-i18n="joined">{{ __('admin.joined') }}</h3>
                <p>{{ optional($user->created_at)->format('Y-m-d H:i') }}</p>
            </article>
        </section>

        <section class="vogue-card reveal">
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.users.index') }}" class="vogue-btn vogue-btn-soft" data-i18n="back">返回</a>
                <a href="{{ route('admin.users.edit', $user) }}" class="vogue-btn vogue-btn-solid" data-i18n="edit">編輯</a>
                @if (auth()->id() !== $user->id)
                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('{{ __('admin.delete_this_user') }}')">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="locale" class="js-locale-field" value="{{ app()->getLocale() }}">
                        <button class="vogue-btn vogue-btn-outline text-red-200 border-red-300/30">{{ __('admin.delete') }}</button>
                    </form>
                @endif
            </div>
        </section>
    </main>

    <script>
        const i18n = { zh: { nav_users: '使用者', nav_edit: '編輯', switch_lang: '中 / EN', switch_theme: '夜間', edit: '編輯', logout: '登出', eyebrow: 'ADMIN DETAIL', point_1: '可檢視角色與加入時間', point_2: '可直接跳轉編輯或刪除', point_3: '與 admin / user 權限模型一致', name: '姓名', email: '電子郵件', role: '角色', joined: '加入時間', back: '返回' }, en: { nav_users: 'Users', nav_edit: 'Edit', switch_lang: 'EN / 中', switch_theme: 'Night', edit: 'Edit', logout: 'Log out', eyebrow: 'ADMIN DETAIL', point_1: 'Inspect role and join date', point_2: 'Jump directly to edit or delete', point_3: 'Matches the admin/user permission model', name: 'Name', email: 'Email', role: 'Role', joined: 'Joined', back: 'Back' } };
        const langToggle = document.getElementById('show-lang-toggle');
        const themeToggle = document.getElementById('show-theme-toggle');
        const storageLang = 'vogue-home-lang';
        const storageTheme = 'vogue-home-theme';
        const appLocale = '{{ app()->getLocale() }}' === 'zh_TW' ? 'zh' : 'en';
        const applyTheme = (theme) => { document.body.dataset.theme = theme; localStorage.setItem(storageTheme, theme); const lang = localStorage.getItem(storageLang) || appLocale; themeToggle.querySelector('.vogue-switch-label').textContent = theme === 'dark' ? i18n[lang].switch_theme : (lang === 'zh' ? '白晝' : 'Day'); };
        const applyLanguage = (lang) => { const active = i18n[lang] ? lang : 'zh'; localStorage.setItem(storageLang, active); document.documentElement.lang = active === 'zh' ? 'zh-Hant' : 'en'; document.querySelectorAll('[data-i18n]').forEach((el) => { const key = el.dataset.i18n; if (i18n[active][key]) el.textContent = i18n[active][key]; }); document.querySelectorAll('.js-locale-field').forEach((el) => { el.value = active === 'zh' ? 'zh_TW' : 'en'; }); themeToggle.querySelector('.vogue-switch-label').textContent = (document.body.dataset.theme || 'dark') === 'dark' ? i18n[active].switch_theme : (active === 'zh' ? '白晝' : 'Day'); };
        const observer = new IntersectionObserver((entries) => { entries.forEach((entry) => { if (entry.isIntersecting) { entry.target.classList.add('is-visible'); observer.unobserve(entry.target); } }); }, { threshold: 0.12 });
        document.querySelectorAll('.reveal').forEach((el) => observer.observe(el));
        if (!localStorage.getItem(storageLang)) localStorage.setItem(storageLang, appLocale);
        applyTheme(localStorage.getItem(storageTheme) || (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark'));
        applyLanguage(localStorage.getItem(storageLang) || appLocale);
        langToggle.addEventListener('click', () => { const current = localStorage.getItem(storageLang) || appLocale; const next = current === 'zh' ? 'en' : 'zh'; localStorage.setItem(storageLang, next); const url = new URL(window.location.href); url.searchParams.set('locale', next === 'zh' ? 'zh_TW' : 'en'); window.location.href = url.toString(); });
        themeToggle.addEventListener('click', () => { const current = document.body.dataset.theme || 'dark'; applyTheme(current === 'dark' ? 'light' : 'dark'); });
        window.addEventListener('load', () => { const skeleton = document.getElementById('admin-show-skeleton'); window.setTimeout(() => { document.body.classList.remove('vogue-is-loading'); if (skeleton) { skeleton.classList.add('is-hidden'); window.setTimeout(() => skeleton.remove(), 1200); } }, 260); });
    </script>
</body>
</html>