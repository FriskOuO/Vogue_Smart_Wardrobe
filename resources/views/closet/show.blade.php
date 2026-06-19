<x-vogue-page title="VogueAI | 衣物詳細" skeleton-id="vogue-closet-show-skeleton">
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
            <p class="vogue-eyebrow">智慧衣櫥</p>
            <h2>{{ $item['name'] }}</h2>
            <p>這裡集中顯示衣物照片、AI 分析、穿著紀錄與後續可串接的推薦入口。</p>
        </div>
        <div class="vogue-closet-toolbar">
            <span class="vogue-chip {{ $chipClass }}">AI 狀態：{{ strtoupper($item['ai_status']) }}</span>
            <a href="{{ route('closet.index') }}" class="vogue-btn vogue-btn-soft">回我的衣櫥</a>
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
                <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" class="vogue-clothing-fit-image rounded-xl" style="height: 420px;">
            </article>

            <div class="space-y-4 lg:col-span-7">
                <article class="vogue-card">
                    <h3>基本資料</h3>
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
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3>穿著紀錄</h3>
                            <p class="mt-1" style="color: var(--vogue-ink-soft);">
                                記錄每次穿著，後續推薦就能避開重複並學習你的真實習慣。
                            </p>
                        </div>
                        <span class="vogue-chip vogue-chip-degraded">
                            {{ $item['wear_count'] ?? 0 }} 次穿著
                        </span>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div>
                            <p class="vogue-label">最近穿著</p>
                            <p>{{ $item['last_worn_at'] ?? '尚未記錄' }}</p>
                        </div>
                        <div>
                            <p class="vogue-label">個人化訊號</p>
                            <p>wear_logs / manual</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('closet.wear.store', $item['id']) }}" class="mt-4 space-y-3">
                        @csrf
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="vogue-label" for="worn_at">穿著時間</label>
                                <input
                                    id="worn_at"
                                    name="worn_at"
                                    type="datetime-local"
                                    class="vogue-input"
                                    value="{{ old('worn_at', now()->format('Y-m-d\TH:i')) }}"
                                >
                            </div>
                            <div>
                                <label class="vogue-label" for="context">場合</label>
                                <input
                                    id="context"
                                    name="context"
                                    type="text"
                                    class="vogue-input"
                                    value="{{ old('context', '日常') }}"
                                    placeholder="日常、工作、晚餐"
                                >
                            </div>
                        </div>
                        <div>
                            <label class="vogue-label" for="notes">備註</label>
                            <textarea
                                id="notes"
                                name="notes"
                                rows="2"
                                class="vogue-textarea"
                                placeholder="可補充未來推薦要參考的細節"
                            >{{ old('notes') }}</textarea>
                        </div>
                        <button type="submit" class="vogue-btn vogue-btn-soft">
                            記錄穿著
                        </button>
                    </form>

                    @if (!empty($wearLogs) && $wearLogs->isNotEmpty())
                        <div class="mt-4 rounded-md border border-white/10 p-3">
                            <p class="vogue-label">最近穿著紀錄</p>
                            <div class="mt-2 space-y-2" style="color: var(--vogue-ink-soft);">
                                @foreach ($wearLogs as $wearLog)
                                    <p>
                                        {{ $wearLog['worn_at'] }}
                                        · {{ $wearLog['context'] ?? '未填場合' }}
                                        @if (!empty($wearLog['notes']))
                                            · {{ $wearLog['notes'] }}
                                        @endif
                                    </p>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </article>

                <article class="vogue-card">
                    <h3>AI 分析</h3>
                    <p class="mt-1">系統會整理類別、季節、場合、用途與風格標籤，作為搜尋與推薦基礎。</p>

                    @if ($item['ai_status'] === 'pending' || empty($item['analysis']))
                        <div class="vogue-card mt-4" style="border-color: rgba(245, 158, 11, 0.42);">
                            <p>AI 分析尚未完成，目前仍在等待處理。</p>
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

                        @if (!empty($item['analysis']['image_caption']))
                            @php($caption = $item['analysis']['image_caption'])
                            <div class="mt-4 rounded-md border border-white/10 p-3">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="vogue-label">影像描述合約</p>
                                        <p class="mt-1">{{ $caption['caption'] ?? '描述產生中' }}</p>
                                    </div>
                                    <span class="vogue-chip vogue-chip-degraded">
                                        {{ $caption['active_provider'] ?? 'mock_caption_fallback' }}
                                    </span>
                                </div>
                                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <p class="vogue-label">目標</p>
                                        <p>{{ $caption['target_provider'] ?? 'blip' }}</p>
                                    </div>
                                    <div>
                                        <p class="vogue-label">轉接器</p>
                                        <p>{{ $caption['adapter'] ?? 'blip-image-caption-v1' }}</p>
                                    </div>
                                    <div>
                                        <p class="vogue-label">備援</p>
                                        <p>{{ !empty($caption['fallback_active']) ? '啟用' : '未啟用' }}</p>
                                    </div>
                                    <div>
                                        <p class="vogue-label">模型</p>
                                        <p>{{ $caption['target_model'] ?? $caption['active_model'] ?? 'fallback-caption-provider' }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif

                    @if ($item['ai_status'] === 'degraded')
                        <div class="vogue-card mt-4" style="border-color: rgba(59, 130, 246, 0.45);">
                            <p>目前使用降級模式：{{ $item['ai_mode'] }}。</p>
                        </div>
                    @endif

                    @if ($item['ai_status'] === 'failed')
                        <div class="vogue-card mt-4" style="border-color: rgba(244, 63, 94, 0.45);">
                            <p>AI 分析失敗，可以重新分析 AI 屬性。</p>
                        </div>
                    @endif
                </article>

                <article class="vogue-card">
                    <h3>AI 操作</h3>
                    <p class="mt-1">可以重新分析衣物屬性或產生影像向量，讓搜尋、穿搭顧問與試穿功能取得更完整資料。</p>

                    <div class="mt-4 flex flex-wrap gap-3">
                        <form method="POST" action="{{ route('closet.reanalyze', $item['id']) }}">
                            @csrf
                            <button type="submit" class="vogue-btn vogue-btn-soft">
                                重新分析 AI 屬性
                            </button>
                        </form>

                        <form method="POST" action="{{ route('closet.reembed', $item['id']) }}">
                            @csrf
                            <button type="submit" class="vogue-btn vogue-btn-soft">
                                重新產生影像向量
                            </button>
                        </form>

                        <button type="button" disabled class="vogue-btn vogue-btn-soft">
                            相似搜尋
                        </button>

                        <button type="button" disabled class="vogue-btn vogue-btn-soft">
                            AI 穿搭顧問推薦
                        </button>

                        <button type="button" disabled class="vogue-btn vogue-btn-soft">
                            試穿姿態分析
                        </button>
                    </div>
                </article>
            </div>
        </div>
    </section>
</x-vogue-page>
