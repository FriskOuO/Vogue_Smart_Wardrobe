<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>VogueAI | {{ __('admin.create_user') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="vogue-home-body vogue-is-loading antialiased">
    <div class="vogue-theme-layer vogue-theme-layer-dark" aria-hidden="true"></div>
    <div class="vogue-theme-layer vogue-theme-layer-light" aria-hidden="true"></div>
    <div id="admin-create-skeleton" class="vogue-skeleton" aria-hidden="true">
        <div class="vogue-skeleton-shell">
            <div class="vogue-skeleton-nav"><div class="vogue-skeleton-brand"></div></div>
            <div class="vogue-skeleton-hero">
                <div class="vogue-skeleton-line vogue-skeleton-line-title"></div>
                <div class="vogue-skeleton-line vogue-skeleton-line-copy"></div>
                <div class="vogue-skeleton-line vogue-skeleton-line-copy short"></div>
            </div>
            <div class="vogue-skeleton-grid"><div></div><div></div></div>
        </div>
    </div>
    <div class="vogue-bg-orb vogue-bg-orb-a" aria-hidden="true"></div>
    <div class="vogue-bg-orb vogue-bg-orb-b" aria-hidden="true"></div>

    <x-vogue-auth-nav />

    <main class="vogue-shell pb-16 md:pb-24 space-y-6">
        <section class="vogue-highlight reveal">
            <div>
                <p class="vogue-eyebrow" data-i18n="eyebrow">ADMIN CRUD</p>
                <h2>{{ __('admin.create_user') }}</h2>
                <p data-i18n="subtitle">建立新帳號並指定角色，支援 admin / user 兩種常見權限模型。</p>
            </div>
            <ul class="vogue-points">
                <li data-i18n="point_1">建立新帳號</li>
                <li data-i18n="point_2">指定角色</li>
                <li data-i18n="point_3">同步語言與主題</li>
            </ul>
        </section>

        <section class="vogue-card reveal max-w-4xl">
            <form method="POST" action="{{ route('admin.users.store') }}" class="grid gap-5 md:grid-cols-2">
                @csrf
                <input type="hidden" name="locale" class="js-locale-field" value="{{ app()->getLocale() }}">

                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-semibold text-slate-200" data-i18n="name">{{ __('admin.name') }}</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required class="mt-2 w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-inherit placeholder:text-slate-400 focus:border-amber-300/50 focus:outline-none">
                    @error('name') <p class="mt-2 text-sm text-red-300">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="email" class="block text-sm font-semibold text-slate-200">{{ __('admin.email') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required class="mt-2 w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-inherit placeholder:text-slate-400 focus:border-amber-300/50 focus:outline-none">
                    @error('email') <p class="mt-2 text-sm text-red-300">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-slate-200">{{ __('admin.password') }}</label>
                    <input id="password" name="password" type="password" required class="mt-2 w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-inherit focus:border-amber-300/50 focus:outline-none">
                    @error('password') <p class="mt-2 text-sm text-red-300">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-slate-200">{{ __('admin.confirm_password') }}</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required class="mt-2 w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-inherit focus:border-amber-300/50 focus:outline-none">
                </div>

                <div class="md:col-span-2">
                    <label for="role" class="block text-sm font-semibold text-slate-200">{{ __('admin.role') }}</label>
                    <select id="role" name="role" class="mt-2 w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-inherit focus:border-amber-300/50 focus:outline-none">
                        <option value="user" @selected(old('role', 'user') === 'user')>{{ __('admin.user') }}</option>
                        <option value="admin" @selected(old('role') === 'admin')>{{ __('admin.admin') }}</option>
                    </select>
                    @error('role') <p class="mt-2 text-sm text-red-300">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2 flex flex-wrap gap-3 pt-2">
                    <button class="vogue-btn vogue-btn-solid" data-i18n="create">{{ __('admin.create') }}</button>
                    <a href="{{ route('admin.users.index') }}" class="vogue-btn vogue-btn-soft" data-i18n="cancel">{{ __('admin.cancel') }}</a>
                </div>
            </form>
        </section>
    </main>

    <script>
        const i18n = { zh: { nav_dashboard: '儀表板', nav_account: '帳號總覽', nav_users: '使用者管理', switch_lang: '中 / EN', switch_theme: '夜間', back: '返回', logout: '登出', eyebrow: 'ADMIN CRUD', subtitle: '建立新帳號並指定角色，支援 admin / user 兩種常見權限模型。', point_1: '建立新帳號', point_2: '指定角色', point_3: '同步語言與主題', create: '建立', cancel: '取消' }, en: { nav_dashboard: 'Dashboard', nav_account: 'Account', nav_users: 'Users', switch_lang: 'EN / 中', switch_theme: 'Night', back: 'Back', logout: 'Log out', eyebrow: 'ADMIN CRUD', subtitle: 'Create a new account and assign a role with the standard admin/user model.', point_1: 'Create a new account', point_2: 'Assign a role', point_3: 'Keep language and theme in sync', create: 'Create', cancel: 'Cancel' } };
        const langToggle = document.getElementById('vogue-lang-toggle');
        const themeToggle = document.getElementById('vogue-theme-toggle');
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
        window.addEventListener('load', () => { const skeleton = document.getElementById('admin-create-skeleton'); window.setTimeout(() => { document.body.classList.remove('vogue-is-loading'); if (skeleton) { skeleton.classList.add('is-hidden'); window.setTimeout(() => skeleton.remove(), 1200); } }, 260); });
    </script>
</body>
</html>