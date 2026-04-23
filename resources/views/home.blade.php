<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>VogueAI | 智慧時尚衣櫥</title>
    <meta name="description" content="VogueAI 把衣櫥管理、AI 穿搭、虛擬試穿與時尚社群整合成一體。">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="vogue-home-body text-slate-100 antialiased">
    <div class="vogue-bg-orb vogue-bg-orb-a" aria-hidden="true"></div>
    <div class="vogue-bg-orb vogue-bg-orb-b" aria-hidden="true"></div>

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
                <a href="{{ route('login') }}" class="vogue-btn vogue-btn-soft">登入</a>
                <a href="{{ route('register') }}" class="vogue-btn vogue-btn-solid">免費開始</a>
            </div>
        </nav>
    </header>

    <main class="vogue-shell pb-16 md:pb-24">
        <section class="vogue-hero">
            <p class="vogue-eyebrow reveal">AI Fashion Platform</p>
            <h1 class="vogue-title reveal">你的衣櫥，不只收納，而是會思考的風格系統。</h1>
            <p class="vogue-subtitle reveal">
                從智慧衣櫥、AI 穿搭師、虛擬試穿到社群趨勢報告，
                一個首頁就能快速進入 VogueAI 全部核心能力。
            </p>
            <div class="vogue-actions reveal">
                <a href="{{ route('register') }}" class="vogue-btn vogue-btn-solid">建立帳號</a>
                <a href="{{ route('login') }}" class="vogue-btn vogue-btn-outline">我已經有帳號</a>
            </div>
        </section>

        <section id="features" class="vogue-section">
            <div class="vogue-section-head reveal">
                <h2>核心功能總覽</h2>
                <p>以模組化架構整合衣櫥管理、AI 服務與社群互動，讓每一次穿搭都可追蹤、可優化、可分享。</p>
            </div>
            <div class="vogue-grid">
                <article class="vogue-card reveal">
                    <h3>Smart Closet</h3>
                    <p>上傳衣物、AI 分類、自然語言搜尋、穿著統計完整閉環。</p>
                </article>
                <article class="vogue-card reveal">
                    <h3>AI Stylist</h3>
                    <p>根據場合、天氣與偏好，生成個人化搭配建議與視覺預覽。</p>
                </article>
                <article class="vogue-card reveal">
                    <h3>Virtual Try-On</h3>
                    <p>照片試穿與導出分享，快速驗證搭配是否成立。</p>
                </article>
                <article class="vogue-card reveal">
                    <h3>Digital Twin</h3>
                    <p>3D 多視角生成，提供更完整的造型展示能力。</p>
                </article>
                <article class="vogue-card reveal">
                    <h3>Community Feed</h3>
                    <p>分享、按讚、評論，並由 AI 產出每日潮流洞察。</p>
                </article>
                <article class="vogue-card reveal">
                    <h3>Runway Video</h3>
                    <p>把靜態穿搭轉成走秀動態片段，提升內容表達力。</p>
                </article>
            </div>
        </section>

        <section id="experience" class="vogue-section vogue-highlight reveal">
            <div>
                <p class="vogue-eyebrow">One Hub, Multiple Modes</p>
                <h2>日常穿搭、旅行打包、智慧收納同一套資料驅動</h2>
                <p>
                    Version1 的模組化重構讓頁面、服務與路由更清晰，
                    你可以從首頁直達不同體驗，並逐步把 React 既有能力轉進 Laravel 前台。
                </p>
            </div>
            <ul class="vogue-points">
                <li>前後端相對路徑整合：/api 與 /ai</li>
                <li>可延展到 PWA、WebSocket、BullMQ 任務流</li>
                <li>維持開發階段彈性，方便逐頁遷移與驗證</li>
            </ul>
        </section>

        <section id="stack" class="vogue-section reveal">
            <div class="vogue-section-head">
                <h2>技術骨幹</h2>
                <p>以 Laravel 作為頁面承載層，保留 Node / Python AI 能力，逐步完成跨框架整合。</p>
            </div>
            <div class="vogue-stack-grid">
                <div>
                    <h3>Frontend Layer</h3>
                    <p>Laravel Blade + Vite + Tailwind</p>
                </div>
                <div>
                    <h3>API Layer</h3>
                    <p>Node.js + Express + SQLite + JWT</p>
                </div>
                <div>
                    <h3>AI Layer</h3>
                    <p>Python FastAPI + CLIP / Gemini / Qdrant</p>
                </div>
            </div>
        </section>
    </main>

    <footer class="vogue-shell pb-8 text-center text-sm text-slate-300/70">
        <p>© <span id="year"></span> VogueAI. Built for modern wardrobes.</p>
    </footer>

    <script>
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
        document.getElementById('year').textContent = new Date().getFullYear();
    </script>
</body>
</html>
