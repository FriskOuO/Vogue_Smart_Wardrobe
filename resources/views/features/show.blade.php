<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>VogueAI | {{ $current['title'] }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="vogue-home-body vogue-is-loading antialiased">
    <div class="vogue-theme-layer vogue-theme-layer-dark" aria-hidden="true"></div>
    <div class="vogue-theme-layer vogue-theme-layer-light" aria-hidden="true"></div>
    <div id="vogue-feature-skeleton" class="vogue-skeleton" aria-hidden="true">
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
            <div class="vogue-skeleton-grid"><div></div><div></div><div></div></div>
        </div>
    </div>
    <div class="vogue-bg-orb vogue-bg-orb-a" aria-hidden="true"></div>
    <div class="vogue-bg-orb vogue-bg-orb-b" aria-hidden="true"></div>

    <x-vogue-auth-nav />

    <main class="vogue-shell pb-16 md:pb-24 space-y-6">
        <section class="vogue-highlight reveal">
            <div>
                <p class="vogue-eyebrow" data-i18n="eyebrow">FEATURE MODULE</p>
                <h2>{{ $current['title'] }}</h2>
                <p>{{ $current['summary'][$localeKey] }}</p>
            </div>
            <ul class="vogue-points">
                <li data-i18n="point_1">點擊左側模組可快速切換功能頁</li>
                <li data-i18n="point_2">各模組頁共用統一導覽與主題設定</li>
                <li data-i18n="point_3">目前為版本一功能入口與規劃面板</li>
            </ul>
        </section>

        <section class="vogue-feature-layout">
            <aside class="vogue-card vogue-feature-sidebar reveal">
                <h3 data-i18n="sidebar_title">模組切換</h3>
                <div class="vogue-feature-links mt-4">
                    @foreach ($modules as $module)
                        <a
                            href="{{ route('features.show', ['feature' => $module['slug'], 'locale' => app()->getLocale()]) }}"
                            class="vogue-feature-link {{ $module['slug'] === $current['slug'] ? 'is-active' : '' }}"
                        >
                            <span>{{ $module['title'] }}</span>
                            <small>{{ $module['summary'][$localeKey] }}</small>
                        </a>
                    @endforeach
                </div>
            </aside>

            <section class="vogue-card reveal">
                <div class="vogue-section-head">
                    <h2 data-i18n="cap_title">核心能力</h2>
                    <p data-i18n="cap_desc">以下能力可作為此模組頁的實作分期與待辦基線。</p>
                </div>
                <div class="vogue-stack-grid mt-4">
                    @foreach ($current['capabilities'][$localeKey] as $capability)
                        <div>
                            <h3>{{ $current['title'] }}</h3>
                            <p>{{ $capability }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('dashboard') }}" class="vogue-btn vogue-btn-soft" data-i18n="back_dashboard">回 Dashboard</a>
                    <a href="{{ route('profile.show') }}" class="vogue-btn vogue-btn-outline" data-i18n="account">帳號總覽</a>
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.users.index') }}" class="vogue-btn vogue-btn-solid" data-i18n="users_manage">使用者管理</a>
                    @endif
                </div>
            </section>
        </section>
    </main>

    <script>
        const i18n = {
            zh: {
                nav_dashboard: '儀表板', nav_account: '帳號總覽', nav_users: '使用者管理',
                nav_closet: 'My Closet', sidebar_main: '主要入口', sidebar_features: '功能切換', sidebar_readme_modules: '未完成暫放區', sidebar_staging_note: '這裡是舊版 README 模組的暫放工作台，待後端完成後再正式串接。', toggle_sidebar: '側欄',
                feature_smart_closet: 'Smart Closet Hub', feature_upload: '上傳衣物', feature_ai_search: 'AI 搜尋', feature_ai_stylist: 'AI Stylist', feature_try_on: 'Try-On / 姿態', feature_digital_twin: 'Digital Twin', feature_community: 'Community',
                module_community: 'Community', module_showcase: 'Showcase', module_blind_box: 'Blind Box', module_runway_video: 'Runway Video', module_chat_assistant: 'Chat Assistant', module_digital_twin: 'Digital Twin', module_travel_packer: 'Travel Packer', module_smart_storage: 'Smart Storage', module_quick_snap: 'Quick Snap', module_smart_tag: 'Smart Tag', module_magic_mirror: 'Magic Mirror', module_ai_bestie_call: 'AI Bestie Call',
                switch_lang: '中 / EN', switch_theme: '夜間', logout: '登出',
                eyebrow: 'FEATURE MODULE', point_1: '點擊左側模組可快速切換功能頁', point_2: '各模組頁共用統一導覽與主題設定', point_3: '目前為版本一功能入口與規劃面板',
                sidebar_title: '模組切換', cap_title: '核心能力', cap_desc: '以下能力可作為此模組頁的實作分期與待辦基線。',
                back_dashboard: '回 Dashboard', account: '帳號總覽', users_manage: '使用者管理'
            },
            en: {
                nav_dashboard: 'Dashboard', nav_account: 'Account', nav_users: 'User Management',
                nav_closet: 'My Closet', sidebar_main: 'Main', sidebar_features: 'Features', sidebar_readme_modules: 'Staging Modules', sidebar_staging_note: 'Temporary workspace for legacy README modules before backend integration is completed.', toggle_sidebar: 'Sidebar',
                feature_smart_closet: 'Smart Closet Hub', feature_upload: 'Upload Garment', feature_ai_search: 'AI Search', feature_ai_stylist: 'AI Stylist', feature_try_on: 'Try-On / Pose', feature_digital_twin: 'Digital Twin', feature_community: 'Community',
                module_community: 'Community', module_showcase: 'Showcase', module_blind_box: 'Blind Box', module_runway_video: 'Runway Video', module_chat_assistant: 'Chat Assistant', module_digital_twin: 'Digital Twin', module_travel_packer: 'Travel Packer', module_smart_storage: 'Smart Storage', module_quick_snap: 'Quick Snap', module_smart_tag: 'Smart Tag', module_magic_mirror: 'Magic Mirror', module_ai_bestie_call: 'AI Bestie Call',
                switch_lang: 'EN / 中', switch_theme: 'Night', logout: 'Log out',
                eyebrow: 'FEATURE MODULE', point_1: 'Use the sidebar to switch between feature pages quickly', point_2: 'All feature pages share the same navigation and theme behavior', point_3: 'This is the V1 module entry and planning panel',
                sidebar_title: 'Module Switcher', cap_title: 'Core Capabilities', cap_desc: 'Use these capabilities as implementation phases and task baselines.',
                back_dashboard: 'Back to Dashboard', account: 'Account', users_manage: 'User Management'
            }
        };

        const langToggle = document.getElementById('vogue-lang-toggle');
        const themeToggle = document.getElementById('vogue-theme-toggle');
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
                if (i18n[active][key]) {
                    el.textContent = i18n[active][key];
                }
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

        if (!localStorage.getItem(storageLang)) {
            localStorage.setItem(storageLang, appLocale);
        }

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
            const skeleton = document.getElementById('vogue-feature-skeleton');
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
