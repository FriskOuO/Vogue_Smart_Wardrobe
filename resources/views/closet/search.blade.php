<x-vogue-page title="VogueAI | AI 搜尋" skeleton-id="vogue-closet-search-skeleton">
    @php
        $searchAcceptance = $searchAcceptance ?? [
            'label' => '尚未搜尋',
            'title' => '等待人工搜尋',
            'message' => '',
            'chip_class' => 'vogue-chip-pending',
        ];
    @endphp

    <section class="vogue-highlight reveal mt-6">
        <div>
            <p class="vogue-eyebrow">AI 搜尋</p>
            <h2>用文字找到適合的衣物</h2>
            <p>輸入想要的風格、場合或顏色，系統會嘗試語意搜尋；切到真實模型後，可在頁面上確認 Qdrant、CLIP 與 fallback 狀態。</p>
        </div>
        <div class="vogue-closet-toolbar">
            <span class="vogue-chip {{ $searchAcceptance['chip_class'] }}">
                {{ $searchAcceptance['label'] }}
            </span>
            <a href="#search-form" class="vogue-btn vogue-btn-solid">開始搜尋</a>
            <a href="{{ route('closet.hub') }}" class="vogue-btn vogue-btn-soft">回總覽</a>
        </div>
    </section>

    <section id="search-form" class="vogue-section vogue-critical-flow">
        @if (!empty($message))
            <div class="vogue-card reveal" style="border-color: rgba(59, 130, 246, 0.45);">
                <p style="color: var(--vogue-ink);">{{ $message }}</p>
            </div>
        @endif

        <div class="vogue-card reveal" style="border-color: rgba(34, 197, 94, 0.35);">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="vogue-label">搜尋驗收狀態</p>
                    <p class="mt-1" style="color: var(--vogue-heading); font-weight: 700;">
                        {{ $searchAcceptance['title'] }}
                    </p>
                    @if (!empty($searchAcceptance['message']))
                        <p class="mt-1" style="color: var(--vogue-ink-soft);">
                            {{ $searchAcceptance['message'] }}
                        </p>
                    @endif
                </div>
                <span class="vogue-chip {{ $searchAcceptance['chip_class'] }}">
                    {{ $searchAcceptance['label'] }}
                </span>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <article class="vogue-card space-y-4">
                <h3>圖片搜尋</h3>
                <p style="color: var(--vogue-ink-soft);">
                    這裡保留圖片向量搜尋入口，之後接上 image embedding 與 similar search 後即可啟用。
                </p>
                <div>
                    <label class="vogue-label" for="query_image">查詢圖片</label>
                    <input id="query_image" type="file" class="vogue-file-input" disabled>
                </div>
                <button type="button" class="vogue-btn vogue-btn-solid" disabled>
                    圖片搜尋尚未啟用
                </button>
            </article>

            <article class="vogue-card space-y-4">
                <h3>文字搜尋</h3>

                <form method="GET" action="{{ route('closet.search') }}" class="space-y-4">
                    <div>
                        <label class="vogue-label" for="q">查詢內容</label>
                        <textarea
                            id="q"
                            name="q"
                            rows="4"
                            class="vogue-textarea"
                            placeholder="例如：白色襯衫、晚餐約會、黑色外套、夏天通勤"
                        >{{ old('q', $query ?? '') }}</textarea>
                    </div>

                    <div>
                        <label class="vogue-label" for="top_k">回傳筆數</label>
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

                    <div>
                        <label class="vogue-label" for="provider_mode">搜尋模式</label>
                        <select id="provider_mode" name="provider_mode" class="vogue-input">
                            <option value="demo" @selected(($providerMode ?? 'demo') === 'demo')>安全備援</option>
                            <option value="real" @selected(($providerMode ?? 'demo') === 'real')>真實模型</option>
                        </select>
                    </div>

                    <button type="submit" class="vogue-btn vogue-btn-outline">
                        執行文字搜尋
                    </button>
                </form>
            </article>
        </div>

        <article class="vogue-card mt-4 reveal">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3>搜尋結果</h3>
                    <p style="color: var(--vogue-ink-soft);">
                        搜尋模式：{{ $searchMode ?? 'empty' }}
                    </p>
                </div>

                @if (!empty($query))
                    <span class="vogue-chip">
                        查詢：{{ $query }}
                    </span>
                @else
                    <span class="vogue-chip {{ $searchAcceptance['chip_class'] }}">
                        {{ $searchAcceptance['label'] }}
                    </span>
                @endif
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($results as $result)
                    @php
                        $typeLabel = [
                            'text' => '文字',
                            'keyword' => '關鍵字',
                            'vector' => '向量',
                        ][$result['type'] ?? 'text'] ?? ($result['type'] ?? '文字');
                    @endphp

                    <div class="vogue-card" style="padding: 0.9rem;">
                        @if (!empty($result['image']))
                            <img
                                src="{{ $result['image'] }}"
                                alt="{{ $result['name'] }}"
                                class="vogue-clothing-fit-image rounded-xl"
                                style="height: 180px;"
                            >
                        @endif

                        <div class="mt-3">
                            <p class="vogue-label">
                                {{ $typeLabel }}
                                @if (($result['score'] ?? 0) > 0)
                                    · 相似度 {{ number_format($result['score'], 2) }}
                                    · {{ $result['metadata']['score_percent'] ?? 0 }}%
                                @endif
                            </p>

                            <p style="color: var(--vogue-heading); font-weight: 700;">
                                {{ $result['name'] }}
                            </p>

                            <p style="color: var(--vogue-ink-soft);">
                                {{ $result['category'] ?? '未分類' }} · {{ $result['color'] ?? '未填顏色' }}
                            </p>

                            @if (!empty($result['reason']))
                                <p class="mt-2 text-sm" style="color: var(--vogue-ink-soft);">
                                    {{ $result['reason'] }}
                                </p>
                            @endif

                            @if (!empty($result['metadata']))
                                @php($metadata = $result['metadata'])
                                <div class="mt-3 rounded-md border border-white/10 p-3 text-sm">
                                    <p class="vogue-label">搜尋資訊</p>
                                    <div class="mt-2 grid gap-2 sm:grid-cols-2" style="color: var(--vogue-ink-soft);">
                                        <p>排序 #{{ $metadata['rank'] ?? '?' }}</p>
                                        <p>來源：{{ $metadata['provider'] ?? 'unknown' }}</p>
                                        @if (!empty($metadata['target_provider']))
                                            <p>目標：{{ $metadata['target_provider'] }}</p>
                                        @endif
                                        <p>模型：{{ $metadata['model'] ?? 'unknown' }}</p>
                                        <p>比對：{{ $metadata['match_type'] ?? 'unknown' }}</p>
                                        <p>信心：{{ $metadata['confidence_label'] ?? 'unknown' }}</p>
                                        <p>資料來源：{{ $metadata['source'] ?? 'unknown' }}</p>
                                        @if (!empty($metadata['vector_store_adapter']))
                                            <p>轉接器：{{ $metadata['vector_store_adapter'] }}</p>
                                        @endif
                                        @if (array_key_exists('fallback_active', $metadata))
                                            <p>備援：{{ $metadata['fallback_active'] ? '啟用' : '未啟用' }}</p>
                                        @endif
                                        @if (!empty($metadata['embedding_target_provider']))
                                            <p>向量目標：{{ $metadata['embedding_target_provider'] }}</p>
                                        @endif
                                        @if (!empty($metadata['embedding_adapter']))
                                            <p>向量轉接器：{{ $metadata['embedding_adapter'] }}</p>
                                        @endif
                                        @if (array_key_exists('embedding_fallback_active', $metadata))
                                            <p>向量備援：{{ $metadata['embedding_fallback_active'] ? '啟用' : '未啟用' }}</p>
                                        @endif
                                    </div>
                                </div>
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
                            尚未找到符合的衣物。可以改用更短的關鍵字，或先到衣櫥新增更多衣物。
                        </p>
                    </div>
                @endforelse
            </div>
        </article>
    </section>
</x-vogue-page>
