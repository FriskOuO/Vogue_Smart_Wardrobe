<x-vogue-page title="VogueAI | 我的衣櫥" skeleton-id="vogue-closet-index-skeleton">
    <section class="vogue-highlight reveal mt-6">
        <div>
            <p class="vogue-eyebrow">智慧衣櫥</p>
            <h2>我的衣櫥</h2>
            <p>集中查看已上傳的衣物、AI 狀態與分類資料，後續搜尋與推薦都會以這裡的資料為基礎。</p>
        </div>
        <div class="vogue-closet-toolbar">
            <p class="text-sm" style="color: var(--vogue-ink-soft);">共 {{ $items->count() }} 件衣物</p>
            <a href="{{ route('closet.create') }}" class="vogue-btn vogue-btn-solid">新增衣物</a>
        </div>
    </section>

    <section class="vogue-section">
        @if (session('status'))
            <div class="vogue-card reveal" style="border-color: rgba(16, 185, 129, 0.45);">
                <p style="color: var(--vogue-ink);">{{ session('status') }}</p>
            </div>
        @endif

        <form method="GET" action="{{ route('closet.index') }}" class="vogue-card reveal mt-4">
            <label for="q" class="vogue-label">搜尋衣物</label>
            <div class="flex flex-col gap-3 sm:flex-row">
                <input id="q" name="q" type="text" value="{{ $query }}" placeholder="輸入名稱、類別或顏色" class="vogue-input">
                <button type="submit" class="vogue-btn vogue-btn-outline">搜尋</button>
            </div>
        </form>

        <div class="vogue-closet-grid">
            @forelse ($items as $item)
                @php
                    $chipClass = [
                        'success' => 'vogue-chip-success',
                        'pending' => 'vogue-chip-pending',
                        'degraded' => 'vogue-chip-degraded',
                    ][$item['ai_status']] ?? 'vogue-chip-pending';
                @endphp

                <article class="vogue-card vogue-closet-item reveal">
                    <div class="vogue-closet-item-image-frame">
                        <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" class="vogue-closet-item-image">
                    </div>
                    <div class="vogue-closet-item-body">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3>{{ $item['name'] }}</h3>
                                <p>{{ $item['category'] }} · {{ $item['color'] }}</p>
                            </div>
                            <span class="vogue-chip {{ $chipClass }}">AI 狀態：{{ strtoupper($item['ai_status']) }}</span>
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('closet.show', $item['id']) }}" class="vogue-btn vogue-btn-soft">查看詳細</a>
                        </div>
                    </div>
                </article>
            @empty
                <article class="vogue-card vogue-empty reveal">
                    <h3>目前還沒有衣物</h3>
                    <p>先新增第一件衣物，後續就能進行 AI 分析、搜尋與推薦。</p>
                </article>
            @endforelse
        </div>
    </section>
</x-vogue-page>
