<x-vogue-page title="VogueAI | Clothing Detail" skeleton-id="vogue-closet-show-skeleton">
    @php
        $chipClass = [
            'success' => 'vogue-chip-success',
            'pending' => 'vogue-chip-pending',
            'degraded' => 'vogue-chip-degraded',
            'failed' => 'vogue-chip-pending',
        ][$item['ai_status']] ?? 'vogue-chip-pending';
    @endphp

    <section class="vogue-highlight reveal mt-6">
        <div>
            <p class="vogue-eyebrow">Smart Closet</p>
            <h2>{{ $item['name'] }}</h2>
            <p>衣物詳細資訊與 AI 分析結果展示區塊，已支援 pending / degraded / failed 狀態顯示。</p>
        </div>
        <div class="vogue-closet-toolbar">
            <span class="vogue-chip {{ $chipClass }}">AI {{ strtoupper($item['ai_status']) }}</span>
            <a href="{{ route('closet.index') }}" class="vogue-btn vogue-btn-soft">回衣櫥列表</a>
        </div>
    </section>

    <section class="vogue-section reveal">
        @if (session('status'))
            <div class="vogue-card reveal" style="border-color: rgba(16, 185, 129, 0.45);">
                <p style="color: var(--vogue-ink);">{{ session('status') }}</p>
            </div>
        @endif

        <div class="grid gap-4 lg:grid-cols-12">
            <article class="vogue-card lg:col-span-5">
                <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" class="w-full rounded-xl object-cover" style="height: 420px;">
            </article>

            <div class="space-y-4 lg:col-span-7">
                <article class="vogue-card">
                    <h3>基礎資訊</h3>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div>
                            <p class="vogue-label">類別</p>
                            <p>{{ $item['category'] }}</p>
                        </div>
                        <div>
                            <p class="vogue-label">顏色</p>
                            <p>{{ $item['color'] }}</p>
                        </div>
                    </div>
                </article>

                <article class="vogue-card">
                    <h3>AI 分析結果</h3>
                    <p class="mt-1">類別、顏色、季節、場合、用途與風格標籤</p>

                    @if ($item['ai_status'] === 'pending' || empty($item['analysis']))
                        <div class="vogue-card mt-4" style="border-color: rgba(245, 158, 11, 0.42);">
                            <p>AI 分析尚未完成，狀態為 pending。</p>
                        </div>
                    @else
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div>
                                <p class="vogue-label">子類別</p>
                                <p>{{ $item['analysis']['subcategory'] ?? '未分類' }}</p>
                            </div>
                            <div>
                                <p class="vogue-label">季節</p>
                                <p>{{ implode(' / ', $item['analysis']['season'] ?? []) }}</p>
                            </div>
                            <div>
                                <p class="vogue-label">場合</p>
                                <p>{{ implode(' / ', $item['analysis']['occasion'] ?? []) }}</p>
                            </div>
                            <div>
                                <p class="vogue-label">用途</p>
                                <p>{{ implode(' / ', $item['analysis']['usage'] ?? []) }}</p>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach (($item['analysis']['style_tags'] ?? []) as $tag)
                                <span class="vogue-chip" style="background: color-mix(in srgb, var(--vogue-panel-bg) 74%, transparent); border-color: var(--vogue-line); color: var(--vogue-ink);">
                                    {{ $tag }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    @if ($item['ai_status'] === 'degraded')
                        <div class="vogue-card mt-4" style="border-color: rgba(59, 130, 246, 0.45);">
                            <p>目前使用展示模式：degraded / {{ $item['ai_mode'] }}。</p>
                        </div>
                    @endif

                    @if ($item['ai_status'] === 'failed')
                        <div class="vogue-card mt-4" style="border-color: rgba(244, 63, 94, 0.45);">
                            <p>AI 分析失敗，請點擊下方「重新分析 AI Attributes」再試一次。</p>
                        </div>
                    @endif
                </article>

                <article class="vogue-card">
                    <h3>AI 功能入口</h3>
                    <p class="mt-1">可重新觸發 AI 屬性分析與 Image Embedding，後續可再串接相似搜尋、Stylist 與 Try-on。</p>

                    <div class="mt-4 flex flex-wrap gap-3">
                        <form method="POST" action="{{ route('closet.reanalyze', $item['id']) }}">
                            @csrf
                            <button type="submit" class="vogue-btn vogue-btn-soft">
                                重新分析 AI Attributes
                            </button>
                        </form>

                        <form method="POST" action="{{ route('closet.reembed', $item['id']) }}">
                            @csrf
                            <button type="submit" class="vogue-btn vogue-btn-soft">
                                重新產生 Image Embedding
                            </button>
                        </form>

                        <button type="button" disabled class="vogue-btn vogue-btn-soft">
                            相似搜尋 AI Search
                        </button>

                        <button type="button" disabled class="vogue-btn vogue-btn-soft">
                            AI Stylist 推薦
                        </button>

                        <button type="button" disabled class="vogue-btn vogue-btn-soft">
                            Try-on 姿態分析
                        </button>
                    </div>
                </article>
            </div>
        </div>
    </section>
</x-vogue-page>