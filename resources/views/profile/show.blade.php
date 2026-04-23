<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>VogueAI | Account</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="vogue-home-body vogue-is-loading antialiased">
    <div class="vogue-theme-layer vogue-theme-layer-dark" aria-hidden="true"></div>
    <div class="vogue-theme-layer vogue-theme-layer-light" aria-hidden="true"></div>
    <div id="vogue-account-skeleton" class="vogue-skeleton" aria-hidden="true">
        <div class="vogue-skeleton-shell">
            <div class="vogue-skeleton-nav"><div class="vogue-skeleton-brand"></div></div>
            <div class="vogue-skeleton-hero">
                <div class="vogue-skeleton-line vogue-skeleton-line-title"></div>
                <div class="vogue-skeleton-line vogue-skeleton-line-copy"></div>
                <div class="vogue-skeleton-line vogue-skeleton-line-copy short"></div>
            </div>
            <div class="vogue-skeleton-grid"><div></div><div></div><div></div><div></div></div>
        </div>
    </div>

    <header class="vogue-shell py-6 md:py-8">
        <nav class="vogue-nav">
            <a href="{{ route('dashboard') }}" class="vogue-brand"><span class="vogue-brand-mark">V</span><span>VogueAI</span></a>
            <div class="vogue-nav-links">
                <a href="{{ route('dashboard') }}" data-i18n="nav_dashboard">Dashboard</a>
                <a href="{{ route('profile.show') }}" data-i18n="nav_account">Account</a>
                <a href="{{ route('profile.edit') }}" data-i18n="nav_edit">Edit</a>
            </div>
            <div class="vogue-nav-cta">
                <div class="vogue-tools">
                    <button id="account-lang-toggle" type="button" class="vogue-switch"><span class="vogue-switch-label" data-i18n="switch_lang">中 / EN</span></button>
                    <button id="account-theme-toggle" type="button" class="vogue-switch"><span class="vogue-switch-label" data-i18n="switch_theme">夜間</span></button>
                </div>
                <a href="{{ route('profile.edit') }}" class="vogue-btn vogue-btn-soft" data-i18n="go_edit">編輯資料</a>
                <form method="POST" action="{{ route('logout') }}" class="inline-block">@csrf
                    <button type="submit" class="vogue-btn vogue-btn-solid" data-i18n="logout">登出</button>
                </form>
            </div>
        </nav>
    </header>

    <main class="vogue-shell pb-16 md:pb-24">
        <section class="vogue-highlight reveal">
            <div>
                <p class="vogue-eyebrow" data-i18n="eyebrow">ACCOUNT OVERVIEW</p>
                <h2>{{ $user->name }}</h2>
                <p>{{ $user->email }}</p>
            </div>
            <ul class="vogue-points">
                <li data-i18n="crud_read">Read: 檢視帳號資訊與驗證狀態</li>
                <li data-i18n="crud_update">Update: 進入編輯頁修改個資與密碼</li>
                <li data-i18n="crud_delete">Delete: 進入編輯頁永久刪除帳號</li>
            </ul>
        </section>

        <section class="vogue-section">
            <div class="vogue-grid">
                <article class="vogue-card reveal">
                    <h3 data-i18n="joined_title">註冊時間</h3>
                    <p>{{ optional($user->created_at)->format('Y-m-d H:i') }}</p>
                </article>
                <article class="vogue-card reveal">
                    <h3 data-i18n="verify_title">Email 驗證狀態</h3>
                    <p>{{ $user->email_verified_at ? __('Verified') : __('Not verified yet') }}</p>
                </article>
                <article class="vogue-card reveal">
                    <h3 data-i18n="crud_title">CRUD 快捷操作</h3>
                    <div class="flex flex-wrap gap-2 pt-2">
                        <a href="{{ route('profile.show') }}" class="vogue-btn vogue-btn-outline" data-i18n="crud_read_btn">Read</a>
                        <a href="{{ route('profile.edit') }}" class="vogue-btn vogue-btn-soft" data-i18n="crud_update_btn">Update</a>
                        <a href="{{ route('profile.edit') }}#danger-zone" class="vogue-btn vogue-btn-solid" data-i18n="crud_delete_btn">Delete</a>
                    </div>
                </article>
            </div>
        </section>
    </main>

    <script>
        const i18n = {
            zh: {
                nav_dashboard: '儀表板', nav_account: '帳號總覽', nav_edit: '編輯帳號', switch_lang: '中 / EN', switch_theme: '夜間',
                go_edit: '編輯資料', logout: '登出', eyebrow: 'ACCOUNT OVERVIEW', crud_read: 'Read: 檢視帳號資訊與驗證狀態',
                crud_update: 'Update: 進入編輯頁修改個資與密碼', crud_delete: 'Delete: 進入編輯頁永久刪除帳號', joined_title: '註冊時間',
                verify_title: 'Email 驗證狀態', crud_title: 'CRUD 快捷操作', crud_read_btn: 'Read', crud_update_btn: 'Update', crud_delete_btn: 'Delete'
            },
            en: {
                nav_dashboard: 'Dashboard', nav_account: 'Account', nav_edit: 'Edit', switch_lang: 'EN / 中', switch_theme: 'Night',
                go_edit: 'Edit Profile', logout: 'Log out', eyebrow: 'ACCOUNT OVERVIEW', crud_read: 'Read: view profile and verification status',
                crud_update: 'Update: edit profile information and password', crud_delete: 'Delete: go to edit page and remove account', joined_title: 'Joined At',
                verify_title: 'Email Verification', crud_title: 'CRUD Quick Actions', crud_read_btn: 'Read', crud_update_btn: 'Update', crud_delete_btn: 'Delete'
            }
        };

        const langToggle = document.getElementById('account-lang-toggle');
        const themeToggle = document.getElementById('account-theme-toggle');
        const storageLang = 'vogue-home-lang';
        const storageTheme = 'vogue-home-theme';

        const applyTheme = (theme) => {
            document.body.dataset.theme = theme;
            localStorage.setItem(storageTheme, theme);
            const lang = localStorage.getItem(storageLang) || 'zh';
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
            document.querySelectorAll('.js-locale-field').forEach((el) => {
                el.value = active === 'zh' ? 'zh_TW' : 'en';
            });
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

        applyTheme(localStorage.getItem(storageTheme) || (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark'));
        applyLanguage(localStorage.getItem(storageLang) || 'zh');

        langToggle.addEventListener('click', () => {
            const current = localStorage.getItem(storageLang) || 'zh';
            applyLanguage(current === 'zh' ? 'en' : 'zh');
        });
        themeToggle.addEventListener('click', () => {
            const current = document.body.dataset.theme || 'dark';
            applyTheme(current === 'dark' ? 'light' : 'dark');
        });

        window.addEventListener('load', () => {
            const skeleton = document.getElementById('vogue-account-skeleton');
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
