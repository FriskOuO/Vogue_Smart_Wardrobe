<x-vogue-page :title="'VogueAI | ' . $current['title']" skeleton-id="vogue-feature-skeleton">
    <section class="vogue-highlight reveal mt-6">
        <div>
            <p class="vogue-eyebrow">功能模組</p>
            <h2>{{ $current['title'] }}</h2>
            <p>{{ $current['summary'][$localeKey] }}</p>
        </div>
        <ul class="vogue-points">
            <li>用左側與下方入口切換不同功能頁</li>
            <li>每個模組都對應後續可實作的階段</li>
            <li>目前保留為規格確認與展示說明頁</li>
        </ul>
    </section>

    <section class="vogue-feature-layout">
        <aside class="vogue-card vogue-feature-sidebar reveal">
            <h3>模組切換</h3>
            <div class="vogue-feature-links mt-4">
                @foreach ($modules as $module)
                    <a
                        href="{{ route('features.show', ['feature' => $module['slug']]) }}"
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
                <h2>核心能力</h2>
                <p>這些項目可作為後續實作階段與驗收基準。</p>
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
                <a href="{{ route('dashboard') }}" class="vogue-btn vogue-btn-soft">回儀表板</a>
                <a href="{{ route('profile.show') }}" class="vogue-btn vogue-btn-outline">帳號總覽</a>
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.users.index') }}" class="vogue-btn vogue-btn-solid">使用者管理</a>
                @endif
            </div>
        </section>
    </section>
</x-vogue-page>
