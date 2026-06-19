<!DOCTYPE html>
<html lang="zh-Hant">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'VogueAI') }}</title>

        <script>
            (function () {
                const savedTheme = localStorage.getItem('vogue-home-theme');
                const theme = savedTheme === 'light' || savedTheme === 'dark'
                    ? savedTheme
                    : (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');

                document.documentElement.dataset.theme = theme;
            })();
        </script>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="vogue-auth-body vogue-is-loading antialiased">
        <div class="vogue-auth-theme-layer vogue-auth-theme-layer-dark" aria-hidden="true"></div>
        <div class="vogue-auth-theme-layer vogue-auth-theme-layer-light" aria-hidden="true"></div>
        <div id="vogue-auth-skeleton" class="vogue-auth-skeleton" aria-hidden="true">
            <div class="vogue-auth-skeleton-shell">
                <div class="vogue-auth-skeleton-brand"></div>
                <div class="vogue-auth-skeleton-card">
                    <div class="vogue-auth-skeleton-heading"></div>
                    <div class="vogue-auth-skeleton-copy"></div>
                    <div class="vogue-auth-skeleton-copy short"></div>
                    <div class="vogue-auth-skeleton-field"><div class="label"></div><div class="input"></div></div>
                    <div class="vogue-auth-skeleton-field"><div class="label"></div><div class="input"></div></div>
                    <div class="vogue-auth-skeleton-foot"><div class="link"></div><div class="button"></div></div>
                </div>
            </div>
        </div>

        <div class="vogue-auth-orb vogue-auth-orb-a" aria-hidden="true"></div>
        <div class="vogue-auth-orb vogue-auth-orb-b" aria-hidden="true"></div>

        <div class="min-h-screen flex flex-col justify-center items-center px-4 py-8">
            <div class="text-center vogue-auth-topbar">
                <a href="/" class="vogue-auth-brand">
                    <span class="vogue-brand-mark">V</span>
                    <span>VogueAI</span>
                </a>
                <div class="vogue-auth-tools" aria-label="登入頁工具">
                    <button id="auth-lang-toggle" type="button" class="vogue-auth-switch" aria-label="套用中文">
                        <span data-i18n="switch_lang">中文</span>
                    </button>
                </div>
                <p class="mt-2 text-sm text-slate-300/80" data-i18n="auth_tagline">智慧衣櫥平台</p>
            </div>

            <div class="w-full sm:max-w-md mt-7 px-6 py-6 sm:px-7 sm:py-7 vogue-auth-card">
                {{ $slot }}
            </div>
        </div>

        <script>
            const authI18nMap = {
                switch_lang: '中文',
                auth_tagline: '智慧衣櫥平台',
                login_title: '登入 VogueAI',
                login_desc: '登入後可以管理衣櫥、AI 穿搭推薦與帳號資料。',
                register_title: '建立 VogueAI 帳號',
                register_desc: '建立帳號後即可開始整理衣櫥、使用 AI 搜尋與穿搭推薦。',
                field_name: '姓名',
                field_email: '電子郵件',
                field_password: '密碼',
                field_password_confirm: '確認密碼',
                auth_remember: '記住我',
                auth_to_register: '還沒有帳號？立即註冊',
                auth_to_login: '已經有帳號？前往登入',
                auth_forgot_password: '忘記密碼？',
                auth_login_button: '登入',
                auth_register_button: '註冊',
                back_home: '回首頁'
            };

            const applyAuthLanguage = () => {
                localStorage.setItem('vogue-home-lang', 'zh');
                document.documentElement.lang = 'zh-Hant';
                document.querySelectorAll('.js-locale-input').forEach((input) => {
                    input.value = 'zh_TW';
                });
                document.querySelectorAll('[data-i18n]').forEach((el) => {
                    const value = authI18nMap[el.dataset.i18n];
                    if (value) {
                        el.textContent = value;
                    }
                });
            };

            const savedTheme = localStorage.getItem('vogue-home-theme');
            document.body.dataset.theme = savedTheme === 'light' || savedTheme === 'dark'
                ? savedTheme
                : (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');

            applyAuthLanguage();
            document.getElementById('auth-lang-toggle')?.addEventListener('click', applyAuthLanguage);

            window.addEventListener('load', () => {
                window.setTimeout(() => {
                    document.body.classList.remove('vogue-is-loading');
                    const skeleton = document.getElementById('vogue-auth-skeleton');
                    if (skeleton) {
                        skeleton.classList.add('is-hidden');
                        window.setTimeout(() => skeleton.remove(), 900);
                    }
                }, 220);
            });
        </script>
    </body>
</html>
