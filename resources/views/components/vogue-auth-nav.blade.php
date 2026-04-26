<div id="vogue-sidebar-backdrop" class="vogue-sidebar-backdrop" aria-hidden="true"></div>

<aside id="vogue-sidebar" class="vogue-sidebar" aria-label="Sidebar Navigation">
    <div class="vogue-sidebar-head">
        <a href="{{ route('dashboard') }}" class="vogue-brand vogue-sidebar-brand">
            <span class="vogue-brand-mark">V</span>
            <span class="vogue-sidebar-brand-text">VogueAI</span>
        </a>
    </div>

    <div class="vogue-sidebar-section-title" data-i18n="sidebar_main">主要入口</div>
    <nav class="vogue-sidebar-nav">
        <a href="{{ route('dashboard') }}" class="vogue-sidebar-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
            <span class="vogue-sidebar-icon">◉</span>
            <span data-i18n="nav_dashboard">儀表板</span>
        </a>
        <a href="{{ route('closet.index') }}" class="vogue-sidebar-link {{ request()->routeIs('closet.index') || request()->routeIs('closet.show') ? 'is-active' : '' }}">
            <span class="vogue-sidebar-icon">◎</span>
            <span data-i18n="nav_closet">My Closet</span>
        </a>
        <a href="{{ route('profile.show') }}" class="vogue-sidebar-link {{ request()->routeIs('profile.show') ? 'is-active' : '' }}">
            <span class="vogue-sidebar-icon">◌</span>
            <span data-i18n="nav_account">帳號總覽</span>
        </a>
        @if (auth()->user()->isAdmin())
            <a href="{{ route('admin.users.index') }}" class="vogue-sidebar-link {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}">
                <span class="vogue-sidebar-icon">◆</span>
                <span data-i18n="nav_users">使用者管理</span>
            </a>
        @endif
    </nav>

    <div class="vogue-sidebar-section-title" data-i18n="sidebar_features">功能切換</div>
    <nav class="vogue-sidebar-nav">
        <a href="{{ route('closet.hub') }}" class="vogue-sidebar-link {{ request()->routeIs('closet.hub') ? 'is-active' : '' }}"><span class="vogue-sidebar-icon">•</span><span data-i18n="feature_smart_closet">Smart Closet Hub</span></a>
        <a href="{{ route('closet.create') }}" class="vogue-sidebar-link {{ request()->routeIs('closet.create') ? 'is-active' : '' }}"><span class="vogue-sidebar-icon">•</span><span data-i18n="feature_upload">Upload Garment</span></a>
        <a href="{{ route('closet.search') }}" class="vogue-sidebar-link {{ request()->routeIs('closet.search') ? 'is-active' : '' }}"><span class="vogue-sidebar-icon">•</span><span data-i18n="feature_ai_search">AI Search</span></a>
        <a href="{{ route('closet.stylist') }}" class="vogue-sidebar-link {{ request()->routeIs('closet.stylist') ? 'is-active' : '' }}"><span class="vogue-sidebar-icon">•</span><span data-i18n="feature_ai_stylist">AI Stylist</span></a>
        <a href="{{ route('closet.tryon') }}" class="vogue-sidebar-link {{ request()->routeIs('closet.tryon') ? 'is-active' : '' }}"><span class="vogue-sidebar-icon">•</span><span data-i18n="feature_try_on">Try-On / Pose</span></a>
    </nav>

    <div class="vogue-sidebar-section-title" data-i18n="sidebar_readme_modules">未完成暫放區</div>
    <p class="vogue-sidebar-note" data-i18n="sidebar_staging_note">這裡是舊版 README 模組的暫放工作台，待後端完成後再正式串接。</p>
    <nav class="vogue-sidebar-nav">
        <a href="{{ route('workspace.show', 'community') }}" class="vogue-sidebar-link {{ request()->routeIs('workspace.show') && request()->route('module') === 'community' ? 'is-active' : '' }}"><span class="vogue-sidebar-icon">•</span><span data-i18n="module_community">Community</span></a>
        <a href="{{ route('workspace.show', 'showcase') }}" class="vogue-sidebar-link {{ request()->routeIs('workspace.show') && request()->route('module') === 'showcase' ? 'is-active' : '' }}"><span class="vogue-sidebar-icon">•</span><span data-i18n="module_showcase">Showcase</span></a>
        <a href="{{ route('workspace.show', 'blind-box') }}" class="vogue-sidebar-link {{ request()->routeIs('workspace.show') && request()->route('module') === 'blind-box' ? 'is-active' : '' }}"><span class="vogue-sidebar-icon">•</span><span data-i18n="module_blind_box">Blind Box</span></a>
        <a href="{{ route('workspace.show', 'runway-video') }}" class="vogue-sidebar-link {{ request()->routeIs('workspace.show') && request()->route('module') === 'runway-video' ? 'is-active' : '' }}"><span class="vogue-sidebar-icon">•</span><span data-i18n="module_runway_video">Runway Video</span></a>
        <a href="{{ route('workspace.show', 'chat-assistant') }}" class="vogue-sidebar-link {{ request()->routeIs('workspace.show') && request()->route('module') === 'chat-assistant' ? 'is-active' : '' }}"><span class="vogue-sidebar-icon">•</span><span data-i18n="module_chat_assistant">Chat Assistant</span></a>
        <a href="{{ route('workspace.show', 'digital-twin') }}" class="vogue-sidebar-link {{ request()->routeIs('workspace.show') && request()->route('module') === 'digital-twin' ? 'is-active' : '' }}"><span class="vogue-sidebar-icon">•</span><span data-i18n="module_digital_twin">Digital Twin</span></a>
        <a href="{{ route('workspace.show', 'travel-packer') }}" class="vogue-sidebar-link {{ request()->routeIs('workspace.show') && request()->route('module') === 'travel-packer' ? 'is-active' : '' }}"><span class="vogue-sidebar-icon">•</span><span data-i18n="module_travel_packer">Travel Packer</span></a>
        <a href="{{ route('workspace.show', 'smart-storage') }}" class="vogue-sidebar-link {{ request()->routeIs('workspace.show') && request()->route('module') === 'smart-storage' ? 'is-active' : '' }}"><span class="vogue-sidebar-icon">•</span><span data-i18n="module_smart_storage">Smart Storage</span></a>
        <a href="{{ route('workspace.show', 'quick-snap') }}" class="vogue-sidebar-link {{ request()->routeIs('workspace.show') && request()->route('module') === 'quick-snap' ? 'is-active' : '' }}"><span class="vogue-sidebar-icon">•</span><span data-i18n="module_quick_snap">Quick Snap</span></a>
        <a href="{{ route('workspace.show', 'smart-tag') }}" class="vogue-sidebar-link {{ request()->routeIs('workspace.show') && request()->route('module') === 'smart-tag' ? 'is-active' : '' }}"><span class="vogue-sidebar-icon">•</span><span data-i18n="module_smart_tag">Smart Tag</span></a>
        <a href="{{ route('workspace.show', 'magic-mirror') }}" class="vogue-sidebar-link {{ request()->routeIs('workspace.show') && request()->route('module') === 'magic-mirror' ? 'is-active' : '' }}"><span class="vogue-sidebar-icon">•</span><span data-i18n="module_magic_mirror">Magic Mirror</span></a>
        <a href="{{ route('workspace.show', 'stylist-call') }}" class="vogue-sidebar-link {{ request()->routeIs('workspace.show') && request()->route('module') === 'stylist-call' ? 'is-active' : '' }}"><span class="vogue-sidebar-icon">•</span><span data-i18n="module_ai_bestie_call">AI Bestie Call</span></a>
    </nav>
</aside>

<header class="vogue-shell vogue-topbar-wrap py-5 md:py-6">
    <nav class="vogue-nav vogue-topbar">
        <button id="vogue-sidebar-toggle" type="button" class="vogue-switch" aria-label="Toggle sidebar">
            <span data-i18n="toggle_sidebar">選單</span>
        </button>

        <div class="vogue-nav-cta">
            <div class="vogue-tools">
                <button id="vogue-lang-toggle" type="button" class="vogue-switch" aria-label="Toggle language">
                    <span class="vogue-switch-label" data-i18n="switch_lang">中 / EN</span>
                </button>
                <button id="vogue-theme-toggle" type="button" class="vogue-switch" aria-label="Toggle theme">
                    <span class="vogue-switch-label" data-i18n="switch_theme">夜間</span>
                </button>
            </div>

            <form method="POST" action="{{ route('logout') }}" class="inline-block">
                @csrf
                <button type="submit" class="vogue-btn vogue-btn-solid" data-i18n="logout">登出</button>
            </form>
        </div>
    </nav>
</header>

<script>
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

        const setMobileOpen = (open) => {
            body.classList.toggle('vogue-sidebar-open', open);
        };

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