<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'VogueAI' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="vogue-home-body vogue-is-loading antialiased">
    <div class="vogue-theme-layer vogue-theme-layer-dark" aria-hidden="true"></div>
    <div class="vogue-theme-layer vogue-theme-layer-light" aria-hidden="true"></div>

    <div id="{{ $skeletonId ?? 'vogue-page-skeleton' }}" class="vogue-skeleton" aria-hidden="true">
        <div class="vogue-skeleton-shell">
            <div class="vogue-skeleton-nav">
                <div class="vogue-skeleton-brand"></div>
                <div class="vogue-skeleton-nav-links"><span></span><span></span><span></span></div>
                <div class="vogue-skeleton-nav-actions"><span></span><span></span><span></span></div>
            </div>
            <div class="vogue-skeleton-hero">
                <div class="vogue-skeleton-line vogue-skeleton-line-eyebrow"></div>
                <div class="vogue-skeleton-line vogue-skeleton-line-title"></div>
                <div class="vogue-skeleton-line vogue-skeleton-line-copy"></div>
                <div class="vogue-skeleton-actions"><span></span><span></span></div>
            </div>
            <div class="vogue-skeleton-grid"><div></div><div></div><div></div></div>
        </div>
    </div>

    <div class="vogue-bg-orb vogue-bg-orb-a" aria-hidden="true"></div>
    <div class="vogue-bg-orb vogue-bg-orb-b" aria-hidden="true"></div>

    <x-vogue-auth-nav />

    <main class="vogue-shell pb-16 md:pb-24">
        {{ $slot }}
    </main>

    <script>
        const i18n = {
            zh: {
                nav_dashboard: '儀表板',
                nav_closet: 'My Closet',
                nav_account: '帳號總覽',
                nav_users: '使用者管理',
                sidebar_main: '主要入口',
                sidebar_features: '功能切換',
                sidebar_readme_modules: '未完成暫放區',
                sidebar_staging_note: '這裡是舊版 README 模組的暫放工作台，待後端完成後再正式串接。',
                toggle_sidebar: '側欄',
                feature_smart_closet: 'Smart Closet Hub',
                feature_upload: '上傳衣物',
                feature_ai_search: 'AI 搜尋',
                feature_ai_stylist: 'AI Stylist',
                feature_try_on: 'Try-On / 姿態',
                feature_digital_twin: 'Digital Twin',
                feature_community: 'Community',
                module_community: 'Community',
                module_showcase: 'Showcase',
                module_blind_box: 'Blind Box',
                module_runway_video: 'Runway Video',
                module_chat_assistant: 'Chat Assistant',
                module_digital_twin: 'Digital Twin',
                module_travel_packer: 'Travel Packer',
                module_smart_storage: 'Smart Storage',
                module_quick_snap: 'Quick Snap',
                module_smart_tag: 'Smart Tag',
                module_magic_mirror: 'Magic Mirror',
                module_ai_bestie_call: 'AI Bestie Call',
                switch_lang: '中 / EN',
                switch_theme: '夜間',
                logout: '登出'
            },
            en: {
                nav_dashboard: 'Dashboard',
                nav_closet: 'My Closet',
                nav_account: 'Account',
                nav_users: 'User Management',
                sidebar_main: 'Main',
                sidebar_features: 'Features',
                sidebar_readme_modules: 'Staging Modules',
                sidebar_staging_note: 'Temporary workspace for legacy README modules before backend integration is completed.',
                toggle_sidebar: 'Sidebar',
                feature_smart_closet: 'Smart Closet Hub',
                feature_upload: 'Upload Garment',
                feature_ai_search: 'AI Search',
                feature_ai_stylist: 'AI Stylist',
                feature_try_on: 'Try-On / Pose',
                feature_digital_twin: 'Digital Twin',
                feature_community: 'Community',
                module_community: 'Community',
                module_showcase: 'Showcase',
                module_blind_box: 'Blind Box',
                module_runway_video: 'Runway Video',
                module_chat_assistant: 'Chat Assistant',
                module_digital_twin: 'Digital Twin',
                module_travel_packer: 'Travel Packer',
                module_smart_storage: 'Smart Storage',
                module_quick_snap: 'Quick Snap',
                module_smart_tag: 'Smart Tag',
                module_magic_mirror: 'Magic Mirror',
                module_ai_bestie_call: 'AI Bestie Call',
                switch_lang: 'EN / 中',
                switch_theme: 'Night',
                logout: 'Log out'
            }
        };

        const langToggle = document.getElementById('vogue-lang-toggle');
        const themeToggle = document.getElementById('vogue-theme-toggle');
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
            const skeleton = document.getElementById('{{ $skeletonId ?? 'vogue-page-skeleton' }}');
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
