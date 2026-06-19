<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>VogueAI | 智慧衣櫥平台</title>
    <meta name="description" content="VogueAI 智慧衣櫥平台，整合衣物管理、AI 搜尋、穿搭推薦、試穿與數位分身流程。">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="vogue-home-body vogue-is-loading text-slate-100 antialiased">
    <div class="vogue-theme-layer vogue-theme-layer-dark" aria-hidden="true"></div>
    <div class="vogue-theme-layer vogue-theme-layer-light" aria-hidden="true"></div>
    <div id="vogue-skeleton" class="vogue-skeleton" aria-hidden="true">
        <div class="vogue-skeleton-shell">
            <div class="vogue-skeleton-nav">
                <div class="vogue-skeleton-brand"></div>
                <div class="vogue-skeleton-nav-links"><span></span><span></span><span></span></div>
                <div class="vogue-skeleton-nav-actions"><span></span><span></span><span></span></div>
            </div>
            <div class="vogue-skeleton-hero">
                <div class="vogue-skeleton-line vogue-skeleton-line-eyebrow"></div>
                <div class="vogue-skeleton-line vogue-skeleton-line-title"></div>
                <div class="vogue-skeleton-line vogue-skeleton-line-title-short"></div>
                <div class="vogue-skeleton-line vogue-skeleton-line-copy"></div>
                <div class="vogue-skeleton-line vogue-skeleton-line-copy short"></div>
                <div class="vogue-skeleton-actions"><span></span><span></span></div>
            </div>
            <div class="vogue-skeleton-grid"><div></div><div></div><div></div><div></div><div></div><div></div></div>
        </div>
    </div>

    <header class="vogue-shell py-6 md:py-8">
        <nav class="vogue-nav">
            <a href="{{ url('/') }}" class="vogue-brand">
                <span class="vogue-brand-mark">V</span>
                <span>VogueAI</span>
            </a>
            <div class="vogue-nav-links">
                <a href="#features">功能</a>
                <a href="#experience">體驗</a>
                <a href="#stack">技術</a>
            </div>
            <div class="vogue-nav-cta">
                <div class="vogue-tools">
                    <button id="theme-toggle" type="button" class="vogue-switch" aria-label="切換主題">
                        <span class="vogue-switch-label">夜間</span>
                    </button>
                </div>
                @auth
                    <a href="{{ route('dashboard') }}" class="vogue-btn vogue-btn-soft">儀表板</a>
                    <a href="{{ route('profile.show') }}" class="vogue-btn vogue-btn-outline">帳號總覽</a>
                    <form method="POST" action="{{ route('logout') }}" class="inline-block">
                        @csrf
                        <button type="submit" class="vogue-btn vogue-btn-solid">登出</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="vogue-btn vogue-btn-soft">登入</a>
                    <a href="{{ route('register') }}" class="vogue-btn vogue-btn-solid">建立帳號</a>
                @endauth
            </div>
        </nav>
    </header>

    <main class="vogue-shell pb-16 md:pb-24">
        <section class="vogue-hero">
            <p class="vogue-eyebrow reveal">AI 時尚平台</p>
            <h1 class="vogue-title reveal">VogueAI</h1>
            <p class="vogue-subtitle reveal">
                把衣櫥管理、AI 搜尋、穿搭推薦、試穿任務與數位分身放在同一個流程裡，
                讓每件衣物都能被記錄、搜尋、搭配與延伸展示。
            </p>
            <div class="vogue-actions reveal">
                @auth
                    <a href="{{ route('dashboard') }}" class="vogue-btn vogue-btn-solid">前往儀表板</a>
                    <a href="{{ route('closet.hub') }}" class="vogue-btn vogue-btn-outline">智慧衣櫥總覽</a>
                @else
                    <a href="{{ route('register') }}" class="vogue-btn vogue-btn-solid">建立帳號</a>
                    <a href="{{ route('login') }}" class="vogue-btn vogue-btn-outline">我已有帳號</a>
                @endauth
            </div>
        </section>

        <section id="features" class="vogue-section">
            <div class="vogue-section-head reveal">
                <h2>核心功能</h2>
                <p>目前已把專題展示所需的衣櫥、搜尋、推薦、試穿、影片與數位分身流程串成可操作頁面。</p>
            </div>
            <div class="vogue-grid">
                <article class="vogue-card reveal"><h3>智慧衣櫥</h3><p>上傳衣物、查看 AI 狀態、搜尋與記錄穿著。</p></article>
                <article class="vogue-card reveal"><h3>AI 穿搭顧問</h3><p>依照場合、天氣與偏好產生推薦，並保存穿搭紀錄。</p></article>
                <article class="vogue-card reveal"><h3>虛擬試穿</h3><p>建立姿態任務，確認人物照片品質與試穿前置資料。</p></article>
                <article class="vogue-card reveal"><h3>數位分身</h3><p>建立個人風格資料，並用衣櫥統計補強推薦。</p></article>
                <article class="vogue-card reveal"><h3>社群展示</h3><p>保留平台模組入口，後續可接貼文、展示牆與互動功能。</p></article>
                <article class="vogue-card reveal"><h3>伸展台影片</h3><p>先建立分鏡與提示詞，後續可串接真實影片生成服務。</p></article>
            </div>
        </section>

        <section id="experience" class="vogue-section vogue-highlight reveal">
            <div>
                <p class="vogue-eyebrow">一個入口，多個流程</p>
                <h2>從日常穿搭到展示任務，都共用同一份衣櫥資料。</h2>
                <p>
                    Laravel 負責主要畫面與資料流程，Python AI Service 負責 AI 端點。
                    即使外部模型暫時無法回應，也能透過安全備援狀態讓核心流程保持可用。
                </p>
            </div>
            <ul class="vogue-points">
                <li>衣櫥資料可供搜尋、推薦、試穿與影片模組共用</li>
                <li>AI 服務不可用時仍有備援資料可展示</li>
                <li>後續可逐步接入真實模型與外部 API</li>
            </ul>
        </section>

        <section id="stack" class="vogue-section reveal">
            <div class="vogue-section-head">
                <h2>技術架構</h2>
                <p>目前以 Laravel、Blade、SQLite 與 FastAPI 組成主體，方便展示也方便後續擴充。</p>
            </div>
            <div class="vogue-stack-grid">
                <div><h3>前端層</h3><p>Laravel Blade + Vite + Tailwind</p></div>
                <div><h3>後端層</h3><p>Laravel 12 + Breeze Session + SQLite</p></div>
                <div><h3>AI 層</h3><p>Python FastAPI + CLIP / Gemini / Qdrant</p></div>
            </div>
        </section>
    </main>

    <footer class="vogue-shell pb-8 text-center text-sm text-slate-300/70">
        <p>© <span id="year"></span> VogueAI.</p>
        <div class="mt-3 flex flex-wrap justify-center gap-3">
            <a href="{{ route('legal.privacy') }}">隱私政策</a>
            <a href="{{ route('legal.terms') }}">服務條款</a>
            <a href="{{ route('legal.acceptable-use') }}">使用限制</a>
        </div>
    </footer>

    <script>
        const themeToggle = document.getElementById('theme-toggle');
        const storageKeyTheme = 'vogue-home-theme';
        const getPreferredTheme = () => localStorage.getItem(storageKeyTheme) || (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
        const applyTheme = (theme) => {
            document.body.dataset.theme = theme;
            localStorage.setItem(storageKeyTheme, theme);
            themeToggle.querySelector('.vogue-switch-label').textContent = theme === 'dark' ? '夜間' : '日間';
        };
        document.getElementById('year').textContent = new Date().getFullYear();
        document.querySelectorAll('.reveal').forEach((el) => {
            new IntersectionObserver((entries, observer) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12 }).observe(el);
        });
        applyTheme(getPreferredTheme());
        themeToggle.addEventListener('click', () => applyTheme((document.body.dataset.theme || 'dark') === 'dark' ? 'light' : 'dark'));
        window.addEventListener('load', () => {
            const skeleton = document.getElementById('vogue-skeleton');
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
