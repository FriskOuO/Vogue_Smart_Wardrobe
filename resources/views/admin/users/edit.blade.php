<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>VogueAI | {{ __('admin.edit_user') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="vogue-home-body vogue-is-loading antialiased">
    <div class="vogue-theme-layer vogue-theme-layer-dark" aria-hidden="true"></div>
    <div class="vogue-theme-layer vogue-theme-layer-light" aria-hidden="true"></div>
    <div id="admin-edit-skeleton" class="vogue-skeleton" aria-hidden="true">
        <div class="vogue-skeleton-shell">
            <div class="vogue-skeleton-nav"><div class="vogue-skeleton-brand"></div></div>
            <div class="vogue-skeleton-hero">
                <div class="vogue-skeleton-line vogue-skeleton-line-title"></div>
                <div class="vogue-skeleton-line vogue-skeleton-line-copy"></div>
            </div>
            <div class="vogue-skeleton-grid"><div></div><div></div><div></div></div>
        </div>
    </div>
    <div class="vogue-bg-orb vogue-bg-orb-a" aria-hidden="true"></div>
    <div class="vogue-bg-orb vogue-bg-orb-b" aria-hidden="true"></div>

    <x-vogue-auth-nav />

    <main class="vogue-shell pb-16 md:pb-24 space-y-6">
        <section class="vogue-highlight reveal">
            <div>
                <p class="vogue-eyebrow" data-i18n="eyebrow">ADMIN CRUD</p>
                <h2 data-i18n="title">{{ __('admin.edit_user') }} #{{ $user->id }}</h2>
                <p data-i18n="subtitle">可同步更新個資、角色與密碼，並保留危險刪除區塊。</p>
            </div>
            <ul class="vogue-points">
                <li data-i18n="point_1">更新姓名、Email、角色</li>
                <li data-i18n="point_2">密碼可選更新</li>
                <li data-i18n="point_3">保留刪除危險區</li>
            </ul>
        </section>

        @if (session('status'))
            <div class="vogue-card reveal border-amber-300/40 text-amber-100">{{ __(session('status')) }}</div>
        @endif

        <section class="vogue-grid">
            <article class="vogue-card reveal md:col-span-2">
                @include('profile.partials.update-profile-information-form')
                <div class="mt-4 text-xs uppercase tracking-[0.24em] text-slate-400">{{ __('admin.role') }} / {{ __('admin.edit') }}</div>
            </article>
            <article class="vogue-card reveal md:col-span-2">
                @include('profile.partials.update-password-form')
            </article>
        </section>

        <section id="danger-zone" class="vogue-card reveal">
            @include('profile.partials.delete-user-form')
        </section>
    </main>

    <script>
        const i18n = { zh: { nav_dashboard: '儀表板', nav_account: '帳號總覽', nav_users: '使用者', nav_detail: '詳細資料', switch_lang: '中 / EN', switch_theme: '夜間', back: '返回', logout: '登出', eyebrow: 'ADMIN CRUD', title: '編輯使用者', subtitle: '可同步更新個資、角色與密碼，並保留危險刪除區塊。', point_1: '更新姓名、Email、角色', point_2: '密碼可選更新', point_3: '保留刪除危險區' }, en: { nav_dashboard: 'Dashboard', nav_account: 'Account', nav_users: 'Users', nav_detail: 'Detail', switch_lang: 'EN / 中', switch_theme: 'Night', back: 'Back', logout: 'Log out', eyebrow: 'ADMIN CRUD', title: 'Edit User', subtitle: 'Update profile data, role, and password while keeping the danger zone intact.', point_1: 'Update name, email, role', point_2: 'Password update is optional', point_3: 'Keep the delete danger zone' } };
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
        window.addEventListener('load', () => { const skeleton = document.getElementById('admin-edit-skeleton'); window.setTimeout(() => { document.body.classList.remove('vogue-is-loading'); if (skeleton) { skeleton.classList.add('is-hidden'); window.setTimeout(() => skeleton.remove(), 1200); } }, 260); });
    </script>
</body>
</html>