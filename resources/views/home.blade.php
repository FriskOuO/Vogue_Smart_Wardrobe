<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>VogueAI | 智慧時尚衣櫥</title>
    <meta name="description" content="VogueAI 把衣櫥管理、AI 穿搭、虛擬試穿與時尚社群整合成一體。">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="vogue-home-body vogue-is-loading text-slate-100 antialiased">
    <div class="vogue-theme-layer vogue-theme-layer-dark" aria-hidden="true"></div>
    <div class="vogue-theme-layer vogue-theme-layer-light" aria-hidden="true"></div>
    <div id="vogue-skeleton" class="vogue-skeleton" aria-hidden="true">
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
                <div class="vogue-skeleton-line vogue-skeleton-line-title-short"></div>
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

    <header class="vogue-shell py-6 md:py-8">
        <nav class="vogue-nav">
            <a href="{{ url('/') }}" class="vogue-brand">
                <span class="vogue-brand-mark">V</span>
                <span>VogueAI</span>
            </a>
            <div class="vogue-nav-links">
                <a href="#features" data-i18n="nav_features">功能</a>
                <a href="#experience" data-i18n="nav_experience">體驗</a>
                <a href="#stack" data-i18n="nav_tech">技術</a>
            </div>
            <div class="vogue-nav-cta">
                <div class="vogue-tools">
                    <button id="lang-toggle" type="button" class="vogue-switch" aria-label="Toggle language">
                        <span class="vogue-switch-label" data-i18n="switch_lang">中 / EN</span>
                    </button>
                    <button id="theme-toggle" type="button" class="vogue-switch" aria-label="Toggle theme">
                        <span class="vogue-switch-label" data-i18n="switch_theme">夜間</span>
                    </button>
                </div>
                @auth
                    <a href="{{ route('dashboard') }}" class="vogue-btn vogue-btn-soft" data-i18n="nav_dashboard">Dashboard</a>
                    <a href="{{ route('profile.show') }}" class="vogue-btn vogue-btn-outline" data-i18n="nav_account">我的帳號</a>
                    <form method="POST" action="{{ route('logout') }}" class="inline-block">
                        @csrf
                        <button type="submit" class="vogue-btn vogue-btn-solid" data-i18n="nav_logout">登出</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="vogue-btn vogue-btn-soft" data-i18n="nav_login">登入</a>
                    <a href="{{ route('register') }}" class="vogue-btn vogue-btn-solid" data-i18n="nav_start">免費開始</a>
                @endauth
            </div>
        </nav>
    </header>

    <main class="vogue-shell pb-16 md:pb-24">
        <section class="vogue-hero">
            <p class="vogue-eyebrow reveal" data-i18n="hero_eyebrow">AI Fashion Platform</p>
            <h1 class="vogue-title reveal" data-i18n="hero_title">你的衣櫥，不只收納，而是會思考的風格系統。</h1>
            <p class="vogue-subtitle reveal" data-i18n="hero_subtitle">
                從智慧衣櫥、AI 穿搭師、虛擬試穿到社群趨勢報告，
                一個首頁就能快速進入 VogueAI 全部核心能力。
            </p>
            <div class="vogue-actions reveal">
                @auth
                    <a href="{{ route('dashboard') }}" class="vogue-btn vogue-btn-solid" data-i18n="hero_auth_cta_1">前往 Dashboard</a>
                    <a href="{{ route('profile.edit') }}" class="vogue-btn vogue-btn-outline" data-i18n="hero_auth_cta_2">編輯帳號資料</a>
                @else
                    <a href="{{ route('register') }}" class="vogue-btn vogue-btn-solid" data-i18n="hero_guest_cta_1">建立帳號</a>
                    <a href="{{ route('login') }}" class="vogue-btn vogue-btn-outline" data-i18n="hero_guest_cta_2">我已經有帳號</a>
                @endauth
            </div>
        </section>

        <section id="features" class="vogue-section">
            <div class="vogue-section-head reveal">
                <h2 data-i18n="features_title">核心功能總覽</h2>
                <p data-i18n="features_desc">以模組化架構整合衣櫥管理、AI 服務與社群互動，讓每一次穿搭都可追蹤、可優化、可分享。</p>
            </div>
            <div class="vogue-grid">
                <article class="vogue-card reveal">
                    <h3>Smart Closet</h3>
                    <p data-i18n="feature_closet">上傳衣物、AI 分類、自然語言搜尋、穿著統計完整閉環。</p>
                </article>
                <article class="vogue-card reveal">
                    <h3>AI Stylist</h3>
                    <p data-i18n="feature_stylist">根據場合、天氣與偏好，生成個人化搭配建議與視覺預覽。</p>
                </article>
                <article class="vogue-card reveal">
                    <h3>Virtual Try-On</h3>
                    <p data-i18n="feature_tryon">照片試穿與導出分享，快速驗證搭配是否成立。</p>
                </article>
                <article class="vogue-card reveal">
                    <h3>Digital Twin</h3>
                    <p data-i18n="feature_twin">3D 多視角生成，提供更完整的造型展示能力。</p>
                </article>
                <article class="vogue-card reveal">
                    <h3>Community Feed</h3>
                    <p data-i18n="feature_community">分享、按讚、評論，並由 AI 產出每日潮流洞察。</p>
                </article>
                <article class="vogue-card reveal">
                    <h3>Runway Video</h3>
                    <p data-i18n="feature_video">把靜態穿搭轉成走秀動態片段，提升內容表達力。</p>
                </article>
            </div>
        </section>

        <section id="experience" class="vogue-section vogue-highlight reveal">
            <div>
                <p class="vogue-eyebrow" data-i18n="exp_eyebrow">One Hub, Multiple Modes</p>
                <h2 data-i18n="exp_title">日常穿搭、旅行打包、智慧收納同一套資料驅動</h2>
                <p data-i18n="exp_desc">
                    Version1 的模組化重構讓頁面、服務與路由更清晰，
                    你可以從首頁直達不同體驗，並逐步把 React 既有能力轉進 Laravel 前台。
                </p>
            </div>
            <ul class="vogue-points">
                <li data-i18n="exp_point_1">Laravel 主後端整合 Python AI Service（/ai）</li>
                <li data-i18n="exp_point_2">可延展到 PWA、WebSocket、BullMQ 任務流</li>
                <li data-i18n="exp_point_3">維持開發階段彈性，方便逐頁遷移與驗證</li>
            </ul>
        </section>

        <section id="stack" class="vogue-section reveal">
            <div class="vogue-section-head">
                <h2 data-i18n="stack_title">技術骨幹</h2>
                <p data-i18n="stack_desc">以 Laravel 作為頁面承載層，保留 Node / Python AI 能力，逐步完成跨框架整合。</p>
            </div>
            <div class="vogue-stack-grid">
                <div>
                    <h3>Frontend Layer</h3>
                    <p data-i18n="stack_frontend">Laravel Blade + Vite + Tailwind</p>
                </div>
                <div>
                    <h3>Backend Layer</h3>
                    <p data-i18n="stack_api">Laravel 12 + Breeze Session + SQLite</p>
                </div>
                <div>
                    <h3>AI Layer</h3>
                    <p data-i18n="stack_ai">Python FastAPI + CLIP / Gemini / Qdrant</p>
                </div>
            </div>
        </section>
    </main>

    <footer class="vogue-shell pb-8 text-center text-sm text-slate-300/70">
        <p data-i18n="footer">© <span id="year"></span> VogueAI. Built for modern wardrobes.</p>
    </footer>

    <script>
        const i18nMap = {
            zh: {
                nav_features: '功能',
                nav_experience: '體驗',
                nav_tech: '技術',
                switch_lang: '中 / EN',
                switch_theme: '夜間',
                nav_dashboard: 'Dashboard',
                nav_account: '我的帳號',
                nav_logout: '登出',
                nav_login: '登入',
                nav_start: '免費開始',
                hero_eyebrow: 'AI Fashion Platform',
                hero_title: '你的衣櫥，不只收納，而是會思考的風格系統。',
                hero_subtitle: '從智慧衣櫥、AI 穿搭師、虛擬試穿到社群趨勢報告，一個首頁就能快速進入 VogueAI 全部核心能力。',
                hero_auth_cta_1: '前往 Dashboard',
                hero_auth_cta_2: '編輯帳號資料',
                hero_guest_cta_1: '建立帳號',
                hero_guest_cta_2: '我已經有帳號',
                features_title: '核心功能總覽',
                features_desc: '以模組化架構整合衣櫥管理、AI 服務與社群互動，讓每一次穿搭都可追蹤、可優化、可分享。',
                feature_closet: '上傳衣物、AI 分類、自然語言搜尋、穿著統計完整閉環。',
                feature_stylist: '根據場合、天氣與偏好，生成個人化搭配建議與視覺預覽。',
                feature_tryon: '照片試穿與導出分享，快速驗證搭配是否成立。',
                feature_twin: '3D 多視角生成，提供更完整的造型展示能力。',
                feature_community: '分享、按讚、評論，並由 AI 產出每日潮流洞察。',
                feature_video: '把靜態穿搭轉成走秀動態片段，提升內容表達力。',
                exp_eyebrow: 'One Hub, Multiple Modes',
                exp_title: '日常穿搭、旅行打包、智慧收納同一套資料驅動',
                exp_desc: 'Version1 的模組化重構讓頁面、服務與路由更清晰，你可以從首頁直達不同體驗，並逐步把 React 既有能力轉進 Laravel 前台。',
                exp_point_1: 'Laravel 主後端整合 Python AI Service（/ai）',
                exp_point_2: '可延展到 PWA、WebSocket、BullMQ 任務流',
                exp_point_3: '維持開發階段彈性，方便逐頁遷移與驗證',
                stack_title: '技術骨幹',
                stack_desc: '以 Laravel 作為頁面承載層，保留 Node / Python AI 能力，逐步完成跨框架整合。',
                stack_frontend: 'Laravel Blade + Vite + Tailwind',
                stack_api: 'Laravel 12 + Breeze Session + SQLite',
                stack_ai: 'Python FastAPI + CLIP / Gemini / Qdrant',
                footer: '© {year} VogueAI. Built for modern wardrobes.'
            },
            en: {
                nav_features: 'Features',
                nav_experience: 'Experience',
                nav_tech: 'Tech',
                switch_lang: 'EN / 中',
                switch_theme: 'Night',
                nav_dashboard: 'Dashboard',
                nav_account: 'My Account',
                nav_logout: 'Log out',
                nav_login: 'Log in',
                nav_start: 'Get Started',
                hero_eyebrow: 'AI Fashion Platform',
                hero_title: 'Your wardrobe is not just storage. It is an intelligent style system.',
                hero_subtitle: 'From smart closet and AI stylist to virtual try-on and trend reporting, VogueAI puts your complete fashion workflow on one home screen.',
                hero_auth_cta_1: 'Go to Dashboard',
                hero_auth_cta_2: 'Edit Account',
                hero_guest_cta_1: 'Create Account',
                hero_guest_cta_2: 'I already have one',
                features_title: 'Core Feature Overview',
                features_desc: 'A modular architecture connects closet management, AI services, and community interactions so every outfit can be tracked, refined, and shared.',
                feature_closet: 'Upload clothing, classify with AI, run semantic search, and keep wear history in one loop.',
                feature_stylist: 'Generate personalized styling suggestions by occasion, weather, and preference.',
                feature_tryon: 'Try looks from photos and export results for quick sharing.',
                feature_twin: 'Generate multi-angle 3D visuals for richer outfit presentation.',
                feature_community: 'Share, like, and comment while AI summarizes daily trend signals.',
                feature_video: 'Turn static outfit shots into runway-style motion clips.',
                exp_eyebrow: 'One Hub, Multiple Modes',
                exp_title: 'Daily styling, travel packing, and smart storage on one data backbone',
                exp_desc: 'The Version1 modular refactor makes pages, services, and routing clearer, so you can migrate React capabilities into Laravel step by step without losing momentum.',
                exp_point_1: 'Laravel as main backend integrated with Python AI Service (/ai)',
                exp_point_2: 'Extensible to PWA, WebSocket, and BullMQ workflows',
                exp_point_3: 'Flexible development flow for page-by-page migration',
                stack_title: 'Technical Backbone',
                stack_desc: 'Laravel handles the presentation layer while preserving Node and Python AI capabilities for progressive integration.',
                stack_frontend: 'Laravel Blade + Vite + Tailwind',
                stack_api: 'Laravel 12 + Breeze Session + SQLite',
                stack_ai: 'Python FastAPI + CLIP / Gemini / Qdrant',
                footer: '© {year} VogueAI. Built for modern wardrobes.'
            }
        };

        const langToggle = document.getElementById('lang-toggle');
        const themeToggle = document.getElementById('theme-toggle');
        const storageKeyLang = 'vogue-home-lang';
        const storageKeyTheme = 'vogue-home-theme';

        const getPreferredTheme = () => {
            const savedTheme = localStorage.getItem(storageKeyTheme);
            if (savedTheme === 'dark' || savedTheme === 'light') {
                return savedTheme;
            }

            return window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
        };

        const applyTheme = (theme) => {
            document.body.dataset.theme = theme;
            localStorage.setItem(storageKeyTheme, theme);

            const lang = localStorage.getItem(storageKeyLang) || 'zh';
            const currentLabel = i18nMap[lang].switch_theme;
            const nextLabel = theme === 'dark'
                ? currentLabel
                : (lang === 'zh' ? '白晝' : 'Day');

            const labelEl = themeToggle.querySelector('.vogue-switch-label');
            labelEl.textContent = nextLabel;
        };

        const applyLanguage = (lang) => {
            const activeLang = i18nMap[lang] ? lang : 'zh';
            localStorage.setItem(storageKeyLang, activeLang);
            document.documentElement.lang = activeLang === 'zh' ? 'zh-Hant' : 'en';

            document.querySelectorAll('[data-i18n]').forEach((el) => {
                const key = el.dataset.i18n;
                const value = i18nMap[activeLang][key];

                if (!value) {
                    return;
                }

                if (key === 'footer') {
                    const year = new Date().getFullYear();
                    el.innerHTML = value.replace('{year}', year);
                    return;
                }

                el.textContent = value;
            });

            const currentTheme = document.body.dataset.theme || 'dark';
            const labelEl = themeToggle.querySelector('.vogue-switch-label');
            labelEl.textContent = currentTheme === 'dark'
                ? i18nMap[activeLang].switch_theme
                : (activeLang === 'zh' ? '白晝' : 'Day');
        };

        const revealEls = document.querySelectorAll('.reveal');
        const skeleton = document.getElementById('vogue-skeleton');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });

        revealEls.forEach((el) => observer.observe(el));

        const initialLang = localStorage.getItem(storageKeyLang) || 'zh';
        const initialTheme = getPreferredTheme();

        applyTheme(initialTheme);
        applyLanguage(initialLang);

        langToggle.addEventListener('click', () => {
            const current = localStorage.getItem(storageKeyLang) || 'zh';
            applyLanguage(current === 'zh' ? 'en' : 'zh');
        });

        themeToggle.addEventListener('click', () => {
            const current = document.body.dataset.theme || 'dark';
            applyTheme(current === 'dark' ? 'light' : 'dark');
        });

        window.addEventListener('load', () => {
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
