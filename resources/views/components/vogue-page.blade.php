<!DOCTYPE html>
<html lang="zh-Hant">
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
        const zhLabels = {
            nav_dashboard: '儀表板',
            nav_closet: '我的衣櫥',
            nav_account: '帳號總覽',
            nav_users: '使用者管理',
            sidebar_main: '主要入口',
            sidebar_features: '功能切換',
            sidebar_readme_modules: '模組工作區',
            sidebar_staging_note: '這裡保留平台延伸模組入口，方便逐步接上後端與 AI 服務。',
            toggle_sidebar: '側欄',
            feature_smart_closet: '智慧衣櫥總覽',
            feature_upload: '上傳衣物',
            feature_ai_search: 'AI 搜尋',
            feature_ai_stylist: 'AI 穿搭顧問',
            feature_try_on: '試穿 / 姿態',
            module_community: '社群',
            module_showcase: '展示牆',
            module_blind_box: '穿搭盲盒',
            module_runway_video: '伸展台影片',
            module_chat_assistant: '聊天助理',
            module_digital_twin: '數位分身',
            module_travel_packer: '旅行打包',
            module_smart_storage: '智慧收納',
            module_quick_snap: '快速拍照',
            module_smart_tag: '智慧標籤',
            module_magic_mirror: '魔鏡試穿',
            module_ai_bestie_call: 'AI 好友通話',
            switch_lang: '中文',
            switch_theme: '夜間',
            switch_theme_light: '日間',
            logout: '登出'
        };

        const langToggle = document.getElementById('vogue-lang-toggle');
        const themeToggle = document.getElementById('vogue-theme-toggle');
        const storageLang = 'vogue-home-lang';
        const storageTheme = 'vogue-home-theme';

        const applyTheme = (theme) => {
            document.body.dataset.theme = theme;
            document.documentElement.dataset.theme = theme;
            localStorage.setItem(storageTheme, theme);
            if (themeToggle) {
                themeToggle.querySelector('.vogue-switch-label').textContent = theme === 'dark' ? zhLabels.switch_theme : zhLabels.switch_theme_light;
            }
        };

        const applyLanguage = () => {
            localStorage.setItem(storageLang, 'zh');
            document.documentElement.lang = 'zh-Hant';
            document.querySelectorAll('[data-i18n]').forEach((el) => {
                const value = zhLabels[el.dataset.i18n];
                if (value) {
                    el.textContent = value;
                }
            });
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

        if (window.vogueApplyTheme) {
            window.vogueApplyTheme(localStorage.getItem(storageTheme) || (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark'));
        } else {
            applyTheme(localStorage.getItem(storageTheme) || (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark'));
        }
        applyLanguage();

        if (langToggle) {
            langToggle.addEventListener('click', applyLanguage);
        }
        if (themeToggle && !window.__vogueThemeControllerReady) {
            themeToggle.addEventListener('click', () => {
                const current = document.body.dataset.theme || 'dark';
                applyTheme(current === 'dark' ? 'light' : 'dark');
            });
        }

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
