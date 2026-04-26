<x-vogue-page title="VogueAI | AI Search" skeleton-id="vogue-closet-search-skeleton">
    <section class="vogue-highlight reveal mt-6">
        <div>
            <p class="vogue-eyebrow">AI Search</p>
            <h2>以圖搜圖 / 以文搜圖</h2>
            <p>此頁提供 AI Search 的完整前端介面，後續可直接接上 embedImage / embedText / searchSimilar API。</p>
        </div>
        <div class="vogue-closet-toolbar">
            <span class="vogue-chip vogue-chip-degraded">MODE mock</span>
            <a href="{{ route('closet.hub') }}" class="vogue-btn vogue-btn-soft">回 Hub</a>
        </div>
    </section>

    <section class="vogue-section reveal">
        <div class="grid gap-4 lg:grid-cols-2">
            <article class="vogue-card space-y-4">
                <h3>以圖搜圖</h3>
                <div>
                    <label class="vogue-label" for="query_image">查詢圖片</label>
                    <input id="query_image" type="file" class="vogue-file-input" disabled>
                </div>
                <div>
                    <label class="vogue-label" for="search_filters">篩選條件</label>
                    <input id="search_filters" type="text" class="vogue-input" placeholder='例如：{"season":"summer"}' disabled>
                </div>
                <button type="button" class="vogue-btn vogue-btn-solid" disabled>執行 image search</button>
            </article>

            <article class="vogue-card space-y-4">
                <h3>以文搜圖</h3>
                <div>
                    <label class="vogue-label" for="query_text">文字描述</label>
                    <textarea id="query_text" rows="4" class="vogue-textarea" placeholder="例如：想找適合夏天通勤的簡約上衣" disabled></textarea>
                </div>
                <div>
                    <label class="vogue-label" for="top_k">Top K</label>
                    <input id="top_k" type="number" class="vogue-input" value="6" disabled>
                </div>
                <button type="button" class="vogue-btn vogue-btn-outline" disabled>執行 text search</button>
            </article>
        </div>

        <article class="vogue-card mt-4 reveal">
            <h3>搜尋結果預覽</h3>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($results as $result)
                    <div class="vogue-card" style="padding: 0.9rem;">
                        <p class="vogue-label">來源：{{ strtoupper($result['type']) }}</p>
                        <p style="color: var(--vogue-heading); font-weight: 700;">{{ $result['name'] }}</p>
                        <p style="color: var(--vogue-ink-soft);">similarity: {{ number_format($result['score'], 2) }}</p>
                    </div>
                @endforeach
            </div>
        </article>
    </section>
</x-vogue-page>
