<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <script>
            (function () {
                const savedTheme = localStorage.getItem('vogue-home-theme');
                const theme = savedTheme === 'light' || savedTheme === 'dark'
                    ? savedTheme
                    : (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');

                document.documentElement.dataset.theme = theme;
            })();
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
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

                    <div class="vogue-auth-skeleton-field">
                        <div class="label"></div>
                        <div class="input"></div>
                    </div>
                    <div class="vogue-auth-skeleton-field">
                        <div class="label"></div>
                        <div class="input"></div>
                    </div>

                    <div class="vogue-auth-skeleton-foot">
                        <div class="link"></div>
                        <div class="button"></div>
                    </div>
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
                <div class="vogue-auth-tools" aria-label="auth language switcher">
                    <button id="auth-lang-toggle" type="button" class="vogue-auth-switch" aria-label="Toggle language">
                        <span data-i18n="switch_lang">中 / EN</span>
                    </button>
                </div>
                <p class="mt-2 text-sm text-slate-300/80" data-i18n="auth_tagline">Smart Wardrobe Platform</p>
            </div>

            <div class="w-full sm:max-w-md mt-7 px-6 py-6 sm:px-7 sm:py-7 vogue-auth-card">
                {{ $slot }}
            </div>
        </div>

        <script>
            const authI18nMap = {
                zh: {
                    switch_lang: '中 / EN',
                    auth_tagline: 'Smart Wardrobe Platform',
                    login_title: '登入你的時尚中樞',
                    login_desc: '登入後可管理衣櫥、AI 穿搭與個人帳號資料。',
                    register_title: '建立 VogueAI 帳號',
                    register_desc: '完成註冊後即可使用智慧衣櫥、AI 穿搭與社群功能。',
                    field_name: '姓名',
                    field_email: 'Email',
                    field_password: '密碼',
                    field_password_confirm: '確認密碼',
                    auth_remember: '記住我',
                    auth_to_register: '還沒有帳號？立即註冊',
                    auth_to_login: '已經有帳號？登入',
                    auth_forgot_password: '忘記密碼？',
                    auth_login_button: '登入',
                    auth_register_button: '註冊',
                    back_home: '返回首頁'
                },
                en: {
                    switch_lang: 'EN / 中',
                    auth_tagline: 'Smart Wardrobe Platform',
                    login_title: 'Sign in to your fashion hub',
                    login_desc: 'Sign in to manage your wardrobe, AI styling, and account details.',
                    register_title: 'Create a VogueAI account',
                    register_desc: 'Sign up to unlock smart closet, AI styling, and community features.',
                    field_name: 'Name',
                    field_email: 'Email',
                    field_password: 'Password',
                    field_password_confirm: 'Confirm Password',
                    auth_remember: 'Remember me',
                    auth_to_register: 'No account yet? Register now',
                    auth_to_login: 'Already registered? Log in',
                    auth_forgot_password: 'Forgot your password?',
                    auth_login_button: 'Log in',
                    auth_register_button: 'Register',
                    back_home: 'Back to home'
                }
            };

            const authLangToggle = document.getElementById('auth-lang-toggle');

            const applyAuthLanguage = (lang) => {
                const activeLang = authI18nMap[lang] ? lang : 'zh';
                localStorage.setItem('vogue-home-lang', activeLang);
                document.documentElement.lang = activeLang === 'zh' ? 'zh-Hant' : 'en';

                document.querySelectorAll('[data-i18n]').forEach((el) => {
                    const key = el.dataset.i18n;
                    const value = authI18nMap[activeLang][key];

                    if (value) {
                        el.textContent = value;
                    }
                });
            };

            const applyAuthTheme = (theme) => {
                document.body.dataset.theme = theme;
            };

            const savedLang = localStorage.getItem('vogue-home-lang') || 'zh';
            const savedTheme = localStorage.getItem('vogue-home-theme');
            const theme = savedTheme === 'light' || savedTheme === 'dark'
                ? savedTheme
                : (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');

            applyAuthTheme(theme);
            applyAuthLanguage(savedLang);

            authLangToggle?.addEventListener('click', () => {
                const current = localStorage.getItem('vogue-home-lang') || 'zh';
                applyAuthLanguage(current === 'zh' ? 'en' : 'zh');
            });

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
