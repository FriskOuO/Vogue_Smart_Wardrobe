<x-vogue-page title="VogueAI | AI Search" skeleton-id="vogue-closet-search-skeleton">
    <section class="vogue-highlight reveal mt-6">
        <div>
            <p class="vogue-eyebrow">AI Search</p>
            <h2>以文搜圖 / 語意搜尋</h2>
            <p>輸入穿搭需求，系統會先產生 text embedding，再呼叫 AI 相似搜尋；若 AI 暫時不可用，會自動 fallback 到一般關鍵字搜尋。</p>
        </div>
        <div class="vogue-closet-toolbar">
            <span class="vogue-chip vogue-chip-degraded">
                MODE {{ strtoupper($searchMode ?? 'mock') }}
            </span>
            <a href="{{ route('closet.hub') }}" class="vogue-btn vogue-btn-soft">回 Hub</a>
        </div>
    </section>

    <section class="vogue-section reveal">
        @if (!empty($message))
            <div class="vogue-card reveal" style="border-color: rgba(59, 130, 246, 0.45);">
                <p style="color: var(--vogue-ink);">{{ $message }}</p>
            </div>
        @endif

        <div class="grid gap-4 lg:grid-cols-2">
            <article class="vogue-card space-y-4">
                <h3>以圖搜圖</h3>
                <p style="color: var(--vogue-ink-soft);">
                    圖片搜尋入口已保留，後續可接 image embedding 與 similar search。
                </p>
                <div>
                    <label class="vogue-label" for="query_image">查詢圖片</label>
                    <input id="query_image" type="file" class="vogue-file-input" disabled>
                </div>
                <button type="button" class="vogue-btn vogue-btn-solid" disabled>
                    Image Search 尚未啟用
                </button>
            </article>

            <article class="vogue-card space-y-4">
                <h3>以文搜圖</h3>

                <form method="GET" action="{{ route('closet.search') }}" class="space-y-4">
                    <div>
                        <label class="vogue-label" for="q">搜尋文字</label>
                        <textarea
                            id="q"
                            name="q"
                            rows="4"
                            class="vogue-textarea"
                            placeholder="例如：白色襯衫、紅色約會洋裝、適合面試的外套"
                        >{{ old('q', $query ?? '') }}</textarea>
                    </div>

                    <div>
                        <label class="vogue-label" for="top_k">Top K</label>
                        <input
                            id="top_k"
                            name="top_k"
                            type="number"
                            min="1"
                            max="20"
                            class="vogue-input"
                            value="{{ $topK ?? 6 }}"
                        >
                    </div>

                    <button type="submit" class="vogue-btn vogue-btn-outline">
                        執行 Text Search
                    </button>
                </form>
            </article>
        </div>

        <article class="vogue-card mt-4 reveal">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3>搜尋結果</h3>
                    <p style="color: var(--vogue-ink-soft);">
                        Search mode：{{ $searchMode ?? 'empty' }}
                    </p>
                </div>

                @if (!empty($query))
                    <span class="vogue-chip">
                        Query：{{ $query }}
                    </span>
                @endif
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($results as $result)
                    <div class="vogue-card" style="padding: 0.9rem;">
                        @if (!empty($result['image']))
                            <img
                                src="{{ $result['image'] }}"
                                alt="{{ $result['name'] }}"
                                class="w-full rounded-xl object-cover"
                                style="height: 180px;"
                            >
                        @endif

                        <div class="mt-3">
                            <p class="vogue-label">
                                {{ strtoupper($result['type'] ?? 'text') }}
                                @if (($result['score'] ?? 0) > 0)
                                    · similarity: {{ number_format($result['score'], 2) }}
                                @endif
                            </p>

                            <p style="color: var(--vogue-heading); font-weight: 700;">
                                {{ $result['name'] }}
                            </p>

                            <p style="color: var(--vogue-ink-soft);">
                                {{ $result['category'] ?? '未分類' }} · {{ $result['color'] ?? '未知顏色' }}
                            </p>

                            @if (!empty($result['reason']))
                                <p class="mt-2 text-sm" style="color: var(--vogue-ink-soft);">
                                    {{ $result['reason'] }}
                                </p>
                            @endif

                            @if (!empty($result['id']))
                                <div class="mt-3">
                                    <a href="{{ route('closet.show', $result['id']) }}" class="vogue-btn vogue-btn-soft">
                                        查看衣物
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="vogue-card" style="padding: 0.9rem;">
                        <p style="color: var(--vogue-ink-soft);">
                            目前沒有搜尋結果。請輸入更明確的條件，例如「白色上衣」或「日常襯衫」。
                        </p>
                    </div>
                @endforelse
            </div>
        </article>
    </section>
</x-vogue-page>