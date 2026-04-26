<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>VogueAI | Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="vogue-home-body vogue-is-loading antialiased">
    <div class="vogue-theme-layer vogue-theme-layer-dark" aria-hidden="true"></div>
    <div class="vogue-theme-layer vogue-theme-layer-light" aria-hidden="true"></div>
    <div id="vogue-dashboard-skeleton" class="vogue-skeleton" aria-hidden="true">
        <div class="vogue-skeleton-shell">
            <div class="vogue-skeleton-nav">
                <div class="vogue-skeleton-brand"></div>
                <div class="vogue-skeleton-nav-links">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <div class="vogue-skeleton-nav-actions">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
            <div class="vogue-skeleton-hero">
                <div class="vogue-skeleton-line vogue-skeleton-line-eyebrow"></div>
                <div class="vogue-skeleton-line vogue-skeleton-line-title"></div>
                <div class="vogue-skeleton-line vogue-skeleton-line-copy"></div>
                <div class="vogue-skeleton-line vogue-skeleton-line-copy short"></div>
                <div class="vogue-skeleton-actions">
                    <span></span>
                    <span></span>
                </div>
            </div>
            <div class="vogue-skeleton-grid">
                <div></div>
                <div></div>
                <div></div>
                <div></div>
                <div></div>
                <div></div>
            </div>
        </div>
    </div>
    <div class="vogue-bg-orb vogue-bg-orb-a" aria-hidden="true"></div>
    <div class="vogue-bg-orb vogue-bg-orb-b" aria-hidden="true"></div>

    <x-vogue-auth-nav />

    <main class="vogue-shell pb-16 md:pb-24" id="overview">
        <section class="vogue-highlight reveal">
            <div>
                <p class="vogue-eyebrow" data-i18n="welcome">WELCOME</p>
                <h2 data-i18n="title">{{ auth()->user()->name }}，歡迎回來</h2>
                <p data-i18n="subtitle">這裡是登入後的功能總覽介面，整合產品能力、AI 模組與平台技術，方便團隊安排開發優先序。</p>
            </div>
            <ul class="vogue-points">
                <li data-i18n="point_1">集中查看功能狀態與子能力清單</li>
                <li data-i18n="point_2">同步 README 能力面與開發規劃</li>
                <li data-i18n="point_3">快速進入帳號與設定流程</li>
            </ul>
        </section>

        <section id="features" class="vogue-section">
            <div class="vogue-section-head reveal">
                <h2 data-i18n="features_title">功能模組地圖</h2>
                <p data-i18n="features_desc">以情境與能力分組展示，登入後可以即時看到每個模組要做的核心內容。</p>
            </div>
            <div class="vogue-grid">
                <a href="{{ route('closet.index') }}" class="vogue-card reveal block"><h3>Smart Closet</h3><p data-i18n="f1">自動標籤、多維分類、CLIP 搜尋、CRUD、穿著統計</p></a>
                <a href="{{ route('features.show', ['feature' => 'ai-stylist']) }}" class="vogue-card reveal block"><h3>AI Stylist</h3><p data-i18n="f2">情境推薦、風格學習、預覽</p></a>
                <a href="{{ route('features.show', ['feature' => 'virtual-try-on']) }}" class="vogue-card reveal block"><h3>Virtual Try-On</h3><p data-i18n="f3">換裝、預覽、分享</p></a>
                <a href="{{ route('features.show', ['feature' => 'digital-twin']) }}" class="vogue-card reveal block"><h3>Digital Twin</h3><p data-i18n="f4">多視角生成、GLB 導出</p></a>
                <a href="{{ route('features.show', ['feature' => 'blind-box']) }}" class="vogue-card reveal block"><h3>Blind Box</h3><p data-i18n="f5">隨機穿搭、收藏</p></a>
                <a href="{{ route('features.show', ['feature' => 'runway-video']) }}" class="vogue-card reveal block"><h3>Runway Video</h3><p data-i18n="f6">走秀影片（Veo）</p></a>
                <a href="{{ route('features.show', ['feature' => 'community']) }}" class="vogue-card reveal block"><h3>Community</h3><p data-i18n="f7">貼文 / 讚 / 評 / 追蹤 + WebSocket</p></a>
                <a href="{{ route('features.show', ['feature' => 'trend-report']) }}" class="vogue-card reveal block"><h3>Trend Report</h3><p data-i18n="f8">趨勢分析、熱門標籤</p></a>
                <a href="{{ route('features.show', ['feature' => 'chat-assistant']) }}" class="vogue-card reveal block"><h3>Chat Assistant</h3><p data-i18n="f9">Gemini 時尚諮詢</p></a>
                <a href="{{ route('features.show', ['feature' => 'showcase']) }}" class="vogue-card reveal block"><h3>Showcase</h3><p data-i18n="f10">商家商品展示、一鍵入庫</p></a>
                <a href="{{ route('features.show', ['feature' => 'user-system']) }}" class="vogue-card reveal block"><h3>User System</h3><p data-i18n="f11">JWT、個資 / 偏好、隱私</p></a>
            </div>
        </section>

        <section id="platform" class="vogue-section reveal">
            <div class="vogue-section-head">
                <h2 data-i18n="platform_title">技術與平台能力</h2>
                <p data-i18n="platform_desc">這些能力會支撐各功能模組落地到實際產品流程。</p>
            </div>
            <div class="vogue-stack-grid">
                <div><h3 data-i18n="stack_front">Frontend</h3><p>Laravel + Blade + Vite + Tailwind</p></div>
                <div><h3 data-i18n="stack_back">Backend</h3><p>Laravel 12 + Breeze Session + SQLite</p></div>
                <div><h3 data-i18n="stack_ai">AI Service</h3><p>FastAPI + Gemini + CLIP + Qdrant + Veo</p></div>
            </div>
        </section>
    </main>

    <footer class="vogue-shell pb-8 text-center text-sm text-slate-300/70">
        <p data-i18n="footer">© <span id="year"></span> VogueAI Dashboard.</p>
    </footer>

    <script>
        const i18n = {
            zh: {
                nav_overview: '總覽', nav_features: '功能', nav_platform: '平台', nav_dashboard: '儀表板', nav_account: '帳號總覽', switch_lang: '中 / EN', switch_theme: '夜間',
                nav_users: '使用者管理', nav_closet: 'My Closet', sidebar_main: '主要入口', sidebar_features: '功能切換', sidebar_readme_modules: '未完成暫放區', sidebar_staging_note: '這裡是舊版 README 模組的暫放工作台，待後端完成後再正式串接。', toggle_sidebar: '側欄',
                feature_smart_closet: 'Smart Closet Hub', feature_upload: '上傳衣物', feature_ai_search: 'AI 搜尋', feature_ai_stylist: 'AI Stylist', feature_try_on: 'Try-On / 姿態', feature_digital_twin: 'Digital Twin', feature_community: 'Community',
                module_community: 'Community', module_showcase: 'Showcase', module_blind_box: 'Blind Box', module_runway_video: 'Runway Video', module_chat_assistant: 'Chat Assistant', module_digital_twin: 'Digital Twin', module_travel_packer: 'Travel Packer', module_smart_storage: 'Smart Storage', module_quick_snap: 'Quick Snap', module_smart_tag: 'Smart Tag', module_magic_mirror: 'Magic Mirror', module_ai_bestie_call: 'AI Bestie Call',
                users_manage: '使用者管理', account: '帳號總覽', settings: '編輯帳號', logout: '登出', welcome: 'WELCOME',
                title: '{{ auth()->user()->name }}，歡迎回來', subtitle: '這裡是登入後的功能總覽介面，整合產品能力、AI 模組與平台技術，方便團隊安排開發優先序。',
                point_1: '集中查看功能狀態與子能力清單', point_2: '同步 README 能力面與開發規劃', point_3: '快速進入帳號與設定流程',
                features_title: '功能模組地圖', features_desc: '以情境與能力分組展示，登入後可以即時看到每個模組要做的核心內容。',
                f1: '自動標籤、多維分類、CLIP 搜尋、CRUD、穿著統計', f2: '情境推薦、風格學習、預覽', f3: '換裝、預覽、分享', f4: '多視角生成、GLB 導出',
                f5: '隨機穿搭、收藏', f6: '走秀影片（Veo）', f7: '貼文 / 讚 / 評 / 追蹤 + WebSocket', f8: '趨勢分析、熱門標籤',
                f9: 'Gemini 時尚諮詢', f10: '商家商品展示、一鍵入庫', f11: 'JWT、個資 / 偏好、隱私',
                platform_title: '技術與平台能力', platform_desc: '這些能力會支撐各功能模組落地到實際產品流程。',
                stack_front: '前端層', stack_back: '後端層', stack_ai: 'AI 層', footer: '© {year} VogueAI Dashboard.'
            },
            en: {
                nav_overview: 'Overview', nav_features: 'Features', nav_platform: 'Platform', nav_dashboard: 'Dashboard', nav_account: 'Account', switch_lang: 'EN / 中', switch_theme: 'Night',
                nav_users: 'User Management', nav_closet: 'My Closet', sidebar_main: 'Main', sidebar_features: 'Features', sidebar_readme_modules: 'Staging Modules', sidebar_staging_note: 'Temporary workspace for legacy README modules before backend integration is completed.', toggle_sidebar: 'Sidebar',
                feature_smart_closet: 'Smart Closet Hub', feature_upload: 'Upload Garment', feature_ai_search: 'AI Search', feature_ai_stylist: 'AI Stylist', feature_try_on: 'Try-On / Pose', feature_digital_twin: 'Digital Twin', feature_community: 'Community',
                module_community: 'Community', module_showcase: 'Showcase', module_blind_box: 'Blind Box', module_runway_video: 'Runway Video', module_chat_assistant: 'Chat Assistant', module_digital_twin: 'Digital Twin', module_travel_packer: 'Travel Packer', module_smart_storage: 'Smart Storage', module_quick_snap: 'Quick Snap', module_smart_tag: 'Smart Tag', module_magic_mirror: 'Magic Mirror', module_ai_bestie_call: 'AI Bestie Call',
                users_manage: 'User Management', account: 'Account', settings: 'Settings', logout: 'Log out', welcome: 'WELCOME',
                title: '{{ auth()->user()->name }}, welcome back', subtitle: 'This post-login dashboard maps product capabilities, AI modules, and platform stack for better planning.',
                point_1: 'Track modules and capability checklists in one place', point_2: 'Keep README scope aligned with implementation', point_3: 'Jump to account and settings quickly',
                features_title: 'Feature Map', features_desc: 'Grouped by capabilities so the team can prioritize and execute faster.',
                f1: 'Auto tagging, multidimensional classification, CLIP search, CRUD, wear stats', f2: 'Context recommendations, style learning, preview',
                f3: 'Change outfit, preview, share', f4: 'Multi-view generation, GLB export', f5: 'Random outfits, favorites', f6: 'Runway video generation (Veo)',
                f7: 'Posts / likes / comments / follow + WebSocket', f8: 'Trend analytics and hot tags', f9: 'Gemini fashion consulting',
                f10: 'Merchant showcase and one-click import', f11: 'JWT, profile/preferences, privacy',
                platform_title: 'Platform Capabilities', platform_desc: 'These layers support each feature module in production workflows.',
                stack_front: 'Frontend Layer', stack_back: 'Backend Layer', stack_ai: 'AI Layer', footer: '© {year} VogueAI Dashboard.'
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
            const label = theme === 'dark' ? i18n[lang].switch_theme : (lang === 'zh' ? '白晝' : 'Day');
            themeToggle.querySelector('.vogue-switch-label').textContent = label;
        };

        const applyLanguage = (lang) => {
            const active = i18n[lang] ? lang : 'zh';
            localStorage.setItem(storageLang, active);
            document.documentElement.lang = active === 'zh' ? 'zh-Hant' : 'en';
            document.querySelectorAll('[data-i18n]').forEach((el) => {
                const key = el.dataset.i18n;
                const value = i18n[active][key];
                if (!value) return;
                if (key === 'footer') {
                    el.innerHTML = value.replace('{year}', new Date().getFullYear());
                } else {
                    el.textContent = value;
                }
            });
            const currentTheme = document.body.dataset.theme || 'dark';
            themeToggle.querySelector('.vogue-switch-label').textContent = currentTheme === 'dark' ? i18n[active].switch_theme : (active === 'zh' ? '白晝' : 'Day');
        };

        const revealEls = document.querySelectorAll('.reveal');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });
        revealEls.forEach((el) => observer.observe(el));

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
            const skeleton = document.getElementById('vogue-dashboard-skeleton');
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
