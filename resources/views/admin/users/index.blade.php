<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>VogueAI | Admin Users</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="vogue-home-body vogue-is-loading antialiased">
    <div class="vogue-theme-layer vogue-theme-layer-dark" aria-hidden="true"></div>
    <div class="vogue-theme-layer vogue-theme-layer-light" aria-hidden="true"></div>
    <div id="admin-users-skeleton" class="vogue-skeleton" aria-hidden="true">
        <div class="vogue-skeleton-shell">
            <div class="vogue-skeleton-nav">
                <div class="vogue-skeleton-brand"></div>
                <div class="vogue-skeleton-nav-links"><span></span><span></span><span></span></div>
                <div class="vogue-skeleton-nav-actions"><span></span><span></span></div>
            </div>
            <div class="vogue-skeleton-hero">
                <div class="vogue-skeleton-line vogue-skeleton-line-eyebrow"></div>
                <div class="vogue-skeleton-line vogue-skeleton-line-title"></div>
                <div class="vogue-skeleton-line vogue-skeleton-line-copy"></div>
            </div>
            <div class="vogue-skeleton-grid">
                <div></div><div></div><div></div><div></div>
            </div>
        </div>
    </div>
    <div class="vogue-bg-orb vogue-bg-orb-a" aria-hidden="true"></div>
    <div class="vogue-bg-orb vogue-bg-orb-b" aria-hidden="true"></div>

    <header class="vogue-shell py-6 md:py-8">
        <nav class="vogue-nav">
            <a href="{{ route('dashboard') }}" class="vogue-brand"><span class="vogue-brand-mark">V</span><span>VogueAI</span></a>
            <div class="vogue-nav-links">
                <a href="{{ route('dashboard') }}" data-i18n="nav_dashboard">Dashboard</a>
                <a href="{{ route('profile.show') }}" data-i18n="nav_account">Account</a>
                <a href="{{ route('admin.users.index') }}" data-i18n="nav_users">Users</a>
            </div>
            <div class="vogue-nav-cta">
                <div class="vogue-tools">
                    <button id="admin-lang-toggle" type="button" class="vogue-switch"><span class="vogue-switch-label" data-i18n="switch_lang">中 / EN</span></button>
                    <button id="admin-theme-toggle" type="button" class="vogue-switch"><span class="vogue-switch-label" data-i18n="switch_theme">夜間</span></button>
                </div>
                <a href="{{ route('admin.users.create') }}" class="vogue-btn vogue-btn-solid" data-i18n="create_user">建立使用者</a>
                <form method="POST" action="{{ route('logout') }}" class="inline-block">@csrf
                    <button type="submit" class="vogue-btn vogue-btn-soft" data-i18n="logout">登出</button>
                </form>
            </div>
        </nav>
    </header>

    <main class="vogue-shell pb-16 md:pb-24 space-y-6">
        <section class="vogue-highlight reveal">
            <div>
                <p class="vogue-eyebrow" data-i18n="eyebrow">ADMIN PANEL</p>
                <h2 data-i18n="title">{{ __('admin.user_management') }}</h2>
                <p data-i18n="subtitle">{{ __('admin.overview_copy') }}</p>
            </div>
            <ul class="vogue-points">
                <li data-i18n="point_1">CRUD：檢視、建立、編輯、刪除所有帳號</li>
                <li data-i18n="point_2">Roles：區分 admin 與 user</li>
                <li data-i18n="point_3">搜尋：以姓名或 Email 快速定位</li>
            </ul>
        </section>

        @if (session('status'))
            <div class="vogue-card reveal border-amber-300/40 text-amber-100">
                {{ __(session('status')) }}
            </div>
        @endif

        <section class="vogue-card reveal">
            <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap gap-3 items-center">
                <input type="hidden" name="locale" class="js-locale-field" value="{{ app()->getLocale() }}">
                <input
                    type="text"
                    name="q"
                    value="{{ $query }}"
                    placeholder="{{ __('admin.search_placeholder') }}"
                    class="w-full md:max-w-md rounded-full border border-white/10 bg-white/5 px-4 py-3 text-sm text-inherit placeholder:text-slate-400 focus:border-amber-300/50 focus:outline-none focus:ring-0"
                >
                <button class="vogue-btn vogue-btn-outline" data-i18n="search">{{ __('admin.search') }}</button>
                <a href="{{ route('admin.users.index') }}" class="vogue-btn vogue-btn-soft" data-i18n="reset">重設</a>
            </form>
        </section>

        <section class="vogue-card reveal overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-white/10">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-[0.24em] text-slate-400">
                            <th class="px-4 py-3">{{ __('admin.id') }}</th>
                            <th class="px-4 py-3">{{ __('admin.name') }}</th>
                            <th class="px-4 py-3">{{ __('admin.email') }}</th>
                            <th class="px-4 py-3">{{ __('admin.role') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('admin.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse ($users as $user)
                            <tr class="align-top">
                                <td class="px-4 py-4 text-sm">{{ $user->id }}</td>
                                <td class="px-4 py-4 text-sm">{{ $user->name }}</td>
                                <td class="px-4 py-4 text-sm">{{ $user->email }}</td>
                                <td class="px-4 py-4 text-sm">
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $user->role === 'admin' ? 'bg-amber-200/15 text-amber-200 border border-amber-200/30' : 'bg-slate-200/10 text-slate-200 border border-white/10' }}">
                                        {{ __('admin.' . $user->role) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right text-sm">
                                    <div class="inline-flex flex-wrap items-center justify-end gap-2">
                                        <a href="{{ route('admin.users.show', $user) }}" class="vogue-btn vogue-btn-soft">{{ __('admin.view') }}</a>
                                        <a href="{{ route('admin.users.edit', $user) }}" class="vogue-btn vogue-btn-solid">{{ __('admin.edit') }}</a>
                                        @if (auth()->id() !== $user->id)
                                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('{{ __('admin.delete_this_user') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="locale" class="js-locale-field" value="{{ app()->getLocale() }}">
                                                <button class="vogue-btn vogue-btn-outline text-red-200 border-red-300/30">{{ __('admin.delete') }}</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-300">{{ __('admin.no_users') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-5">
                {{ $users->links() }}
            </div>
        </section>
    </main>

    <script>
        const i18n = {
            zh: { nav_dashboard: '儀表板', nav_account: '帳號總覽', nav_users: '使用者', switch_lang: '中 / EN', switch_theme: '夜間', create_user: '建立使用者', logout: '登出', eyebrow: 'ADMIN PANEL', title: '使用者管理', subtitle: '可建立、檢視、更新與刪除整個系統中的使用者帳號。', point_1: 'CRUD：檢視、建立、編輯、刪除所有帳號', point_2: 'Roles：區分 admin 與 user', point_3: '搜尋：以姓名或 Email 快速定位', search: '搜尋', reset: '重設' },
            en: { nav_dashboard: 'Dashboard', nav_account: 'Account', nav_users: 'Users', switch_lang: 'EN / 中', switch_theme: 'Night', create_user: 'Create User', logout: 'Log out', eyebrow: 'ADMIN PANEL', title: 'User Management', subtitle: 'Create, inspect, update, and delete user accounts across the system.', point_1: 'CRUD: view, create, edit, delete every account', point_2: 'Roles: separate admin and user', point_3: 'Search: find by name or email', search: 'Search', reset: 'Reset' }
        };
        const langToggle = document.getElementById('admin-lang-toggle');
        const themeToggle = document.getElementById('admin-theme-toggle');
        const storageLang = 'vogue-home-lang';
        const storageTheme = 'vogue-home-theme';
        const appLocale = '{{ app()->getLocale() }}' === 'zh_TW' ? 'zh' : 'en';

        const applyTheme = (theme) => {
            document.body.dataset.theme = theme;
            localStorage.setItem(storageTheme, theme);
            const lang = localStorage.getItem(storageLang) || appLocale;
            themeToggle.querySelector('.vogue-switch-label').textContent = theme === 'dark' ? i18n[lang].switch_theme : (lang === 'zh' ? '白晝' : 'Day');
        };

        const applyLanguage = (lang) => {
            const active = i18n[lang] ? lang : 'zh';
            localStorage.setItem(storageLang, active);
            document.documentElement.lang = active === 'zh' ? 'zh-Hant' : 'en';
            document.querySelectorAll('[data-i18n]').forEach((el) => {
                const key = el.dataset.i18n;
                if (i18n[active][key]) el.textContent = i18n[active][key];
            });
            document.querySelectorAll('.js-locale-field').forEach((el) => { el.value = active === 'zh' ? 'zh_TW' : 'en'; });
            const currentTheme = document.body.dataset.theme || 'dark';
            themeToggle.querySelector('.vogue-switch-label').textContent = currentTheme === 'dark' ? i18n[active].switch_theme : (active === 'zh' ? '白晝' : 'Day');
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });
        document.querySelectorAll('.reveal').forEach((el) => observer.observe(el));

        if (!localStorage.getItem(storageLang)) localStorage.setItem(storageLang, appLocale);
        applyTheme(localStorage.getItem(storageTheme) || (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark'));
        applyLanguage(localStorage.getItem(storageLang) || appLocale);

        langToggle.addEventListener('click', () => {
            const current = localStorage.getItem(storageLang) || appLocale;
            const next = current === 'zh' ? 'en' : 'zh';
            localStorage.setItem(storageLang, next);
            const url = new URL(window.location.href);
            url.searchParams.set('locale', next === 'zh' ? 'zh_TW' : 'en');
            window.location.href = url.toString();
        });
        themeToggle.addEventListener('click', () => {
            const current = document.body.dataset.theme || 'dark';
            applyTheme(current === 'dark' ? 'light' : 'dark');
        });

        window.addEventListener('load', () => {
            const skeleton = document.getElementById('admin-users-skeleton');
            window.setTimeout(() => {
                document.body.classList.remove('vogue-is-loading');
                if (skeleton) {
                    skeleton.classList.add('is-hidden');
                    window.setTimeout(() => skeleton.remove(), 1200);
                }
            }, 260);
        });
    </script>
</body>
</html>