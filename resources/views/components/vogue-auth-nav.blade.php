<div id="vogue-sidebar-backdrop" class="vogue-sidebar-backdrop" aria-hidden="true"></div>

<aside id="vogue-sidebar" class="vogue-sidebar" aria-label="主選單">
    <div class="vogue-sidebar-head">
        <a href="{{ route('dashboard') }}" class="vogue-brand vogue-sidebar-brand">
            <span class="vogue-brand-mark">V</span>
            <span class="vogue-sidebar-brand-text">VogueAI</span>
        </a>
    </div>

    <div class="vogue-sidebar-section-title" data-i18n="sidebar_main">主要入口</div>
    <nav class="vogue-sidebar-nav">
        <a href="{{ route('dashboard') }}" class="vogue-sidebar-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
            <span class="vogue-sidebar-icon">•</span>
            <span data-i18n="nav_dashboard">儀表板</span>
        </a>
        <a href="{{ route('closet.index') }}" class="vogue-sidebar-link {{ request()->routeIs('closet.index') || request()->routeIs('closet.show') ? 'is-active' : '' }}">
            <span class="vogue-sidebar-icon">•</span>
            <span data-i18n="nav_closet">我的衣櫥</span>
        </a>
        <a href="{{ route('profile.show') }}" class="vogue-sidebar-link {{ request()->routeIs('profile.show') ? 'is-active' : '' }}">
            <span class="vogue-sidebar-icon">•</span>
            <span data-i18n="nav_account">帳號總覽</span>
        </a>
        @if (auth()->check() && auth()->user()->isAdmin())
            <a href="{{ route('admin.users.index') }}" class="vogue-sidebar-link {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}">
                <span class="vogue-sidebar-icon">•</span>
                <span data-i18n="nav_users">使用者管理</span>
            </a>
        @endif
    </nav>

    <div class="vogue-sidebar-section-title" data-i18n="sidebar_features">功能切換</div>
    <nav class="vogue-sidebar-nav">
        <a href="{{ route('closet.hub') }}" class="vogue-sidebar-link {{ request()->routeIs('closet.hub') ? 'is-active' : '' }}"><span class="vogue-sidebar-icon">•</span><span data-i18n="feature_smart_closet">智慧衣櫥總覽</span></a>
        <a href="{{ route('closet.create') }}" class="vogue-sidebar-link {{ request()->routeIs('closet.create') ? 'is-active' : '' }}"><span class="vogue-sidebar-icon">•</span><span data-i18n="feature_upload">上傳衣物</span></a>
        <a href="{{ route('closet.search') }}" class="vogue-sidebar-link {{ request()->routeIs('closet.search') ? 'is-active' : '' }}"><span class="vogue-sidebar-icon">•</span><span data-i18n="feature_ai_search">AI 搜尋</span></a>
        <a href="{{ route('closet.stylist') }}" class="vogue-sidebar-link {{ request()->routeIs('closet.stylist') ? 'is-active' : '' }}"><span class="vogue-sidebar-icon">•</span><span data-i18n="feature_ai_stylist">AI 穿搭顧問</span></a>
        <a href="{{ route('closet.tryon') }}" class="vogue-sidebar-link {{ request()->routeIs('closet.tryon') ? 'is-active' : '' }}"><span class="vogue-sidebar-icon">•</span><span data-i18n="feature_try_on">試穿 / 姿態</span></a>
    </nav>

    <div class="vogue-sidebar-section-title" data-i18n="sidebar_readme_modules">模組工作區</div>
    <p class="vogue-sidebar-note" data-i18n="sidebar_staging_note">這裡保留平台延伸模組入口，方便逐步接上後端與 AI 服務。</p>
    <nav class="vogue-sidebar-nav">
        <a href="{{ route('workspace.show', 'community') }}" class="vogue-sidebar-link {{ request()->routeIs('workspace.show') && request()->route('module') === 'community' ? 'is-active' : '' }}"><span class="vogue-sidebar-icon">•</span><span data-i18n="module_community">社群</span></a>
        <a href="{{ route('workspace.show', 'showcase') }}" class="vogue-sidebar-link {{ request()->routeIs('workspace.show') && request()->route('module') === 'showcase' ? 'is-active' : '' }}"><span class="vogue-sidebar-icon">•</span><span data-i18n="module_showcase">展示牆</span></a>
        <a href="{{ route('workspace.show', 'blind-box') }}" class="vogue-sidebar-link {{ request()->routeIs('workspace.show') && request()->route('module') === 'blind-box' ? 'is-active' : '' }}"><span class="vogue-sidebar-icon">•</span><span data-i18n="module_blind_box">穿搭盲盒</span></a>
        <a href="{{ route('workspace.show', 'runway-video') }}" class="vogue-sidebar-link {{ request()->routeIs('workspace.show') && request()->route('module') === 'runway-video' ? 'is-active' : '' }}"><span class="vogue-sidebar-icon">•</span><span data-i18n="module_runway_video">伸展台影片</span></a>
        <a href="{{ route('workspace.show', 'chat-assistant') }}" class="vogue-sidebar-link {{ request()->routeIs('workspace.show') && request()->route('module') === 'chat-assistant' ? 'is-active' : '' }}"><span class="vogue-sidebar-icon">•</span><span data-i18n="module_chat_assistant">聊天助理</span></a>
        <a href="{{ route('workspace.show', 'digital-twin') }}" class="vogue-sidebar-link {{ request()->routeIs('workspace.show') && request()->route('module') === 'digital-twin' ? 'is-active' : '' }}"><span class="vogue-sidebar-icon">•</span><span data-i18n="module_digital_twin">數位分身</span></a>
        <a href="{{ route('workspace.show', 'travel-packer') }}" class="vogue-sidebar-link {{ request()->routeIs('workspace.show') && request()->route('module') === 'travel-packer' ? 'is-active' : '' }}"><span class="vogue-sidebar-icon">•</span><span data-i18n="module_travel_packer">旅行打包</span></a>
        <a href="{{ route('workspace.show', 'smart-storage') }}" class="vogue-sidebar-link {{ request()->routeIs('workspace.show') && request()->route('module') === 'smart-storage' ? 'is-active' : '' }}"><span class="vogue-sidebar-icon">•</span><span data-i18n="module_smart_storage">智慧收納</span></a>
        <a href="{{ route('workspace.show', 'quick-snap') }}" class="vogue-sidebar-link {{ request()->routeIs('workspace.show') && request()->route('module') === 'quick-snap' ? 'is-active' : '' }}"><span class="vogue-sidebar-icon">•</span><span data-i18n="module_quick_snap">快速拍照</span></a>
        <a href="{{ route('workspace.show', 'smart-tag') }}" class="vogue-sidebar-link {{ request()->routeIs('workspace.show') && request()->route('module') === 'smart-tag' ? 'is-active' : '' }}"><span class="vogue-sidebar-icon">•</span><span data-i18n="module_smart_tag">智慧標籤</span></a>
        <a href="{{ route('workspace.show', 'magic-mirror') }}" class="vogue-sidebar-link {{ request()->routeIs('workspace.show') && request()->route('module') === 'magic-mirror' ? 'is-active' : '' }}"><span class="vogue-sidebar-icon">•</span><span data-i18n="module_magic_mirror">魔鏡試穿</span></a>
        <a href="{{ route('workspace.show', 'stylist-call') }}" class="vogue-sidebar-link {{ request()->routeIs('workspace.show') && request()->route('module') === 'stylist-call' ? 'is-active' : '' }}"><span class="vogue-sidebar-icon">•</span><span data-i18n="module_ai_bestie_call">AI 好友通話</span></a>
    </nav>
</aside>

<header class="vogue-shell vogue-topbar-wrap py-5 md:py-6">
    <nav class="vogue-nav vogue-topbar">
        <button id="vogue-sidebar-toggle" type="button" class="vogue-switch" aria-label="切換側欄">
            <span data-i18n="toggle_sidebar">側欄</span>
        </button>

        <div class="vogue-nav-cta">
            <div class="vogue-tools">
                <button id="vogue-lang-toggle" type="button" class="vogue-switch" aria-label="固定中文">
                    <span class="vogue-switch-label" data-i18n="switch_lang">中文</span>
                </button>
                <button id="vogue-theme-toggle" type="button" class="vogue-switch" aria-label="切換主題">
                    <span class="vogue-switch-label" data-i18n="switch_theme">夜間</span>
                </button>
            </div>

            @auth
                <form method="POST" action="{{ route('logout') }}" class="inline-block">
                    @csrf
                    <button type="submit" class="vogue-btn vogue-btn-solid" data-i18n="logout">登出</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="vogue-btn vogue-btn-soft">登入</a>
                <a href="{{ route('register') }}" class="vogue-btn vogue-btn-solid">註冊</a>
            @endauth
        </div>
    </nav>
</header>

<script>
    (function () {
        if (window.__vogueThemeControllerReady) {
            return;
        }

        window.__vogueThemeControllerReady = true;

        const storageTheme = 'vogue-home-theme';
        const themeToggle = document.getElementById('vogue-theme-toggle');
        const themeLabel = themeToggle ? themeToggle.querySelector('.vogue-switch-label') : null;
        const validTheme = (theme) => theme === 'light' || theme === 'dark';
        const preferredTheme = () => {
            const savedTheme = localStorage.getItem(storageTheme);

            if (validTheme(savedTheme)) {
                return savedTheme;
            }

            return window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
        };
        const applyTheme = (theme) => {
            const nextTheme = validTheme(theme) ? theme : preferredTheme();

            document.documentElement.dataset.theme = nextTheme;
            document.body.dataset.theme = nextTheme;
            localStorage.setItem(storageTheme, nextTheme);

            if (themeLabel) {
                themeLabel.textContent = nextTheme === 'dark' ? '夜間' : '日間';
            }
        };

        window.vogueApplyTheme = applyTheme;
        applyTheme(preferredTheme());

        if (themeToggle) {
            themeToggle.addEventListener('click', () => {
                const current = document.body.dataset.theme || preferredTheme();
                applyTheme(current === 'dark' ? 'light' : 'dark');
            });
        }
    })();

    (function () {
        const body = document.body;
        body.classList.add('vogue-has-sidebar');

        const storageKey = 'vogue-sidebar-collapsed';
        const sidebar = document.getElementById('vogue-sidebar');
        const backdrop = document.getElementById('vogue-sidebar-backdrop');
        const toggleBtn = document.getElementById('vogue-sidebar-toggle');

        if (!sidebar || !toggleBtn || !backdrop) {
            return;
        }

        const isDesktop = () => window.innerWidth > 980;
        const setCollapsed = (collapsed) => {
            body.classList.toggle('vogue-sidebar-collapsed', collapsed);
            localStorage.setItem(storageKey, collapsed ? '1' : '0');
        };
        const setMobileOpen = (open) => body.classList.toggle('vogue-sidebar-open', open);

        setCollapsed(localStorage.getItem(storageKey) === '1');

        toggleBtn.addEventListener('click', () => {
            if (isDesktop()) {
                setCollapsed(!body.classList.contains('vogue-sidebar-collapsed'));
                return;
            }

            setMobileOpen(!body.classList.contains('vogue-sidebar-open'));
        });

        backdrop.addEventListener('click', () => setMobileOpen(false));
        window.addEventListener('resize', () => {
            if (window.innerWidth > 980) {
                setMobileOpen(false);
            }
        });
    })();
</script>
