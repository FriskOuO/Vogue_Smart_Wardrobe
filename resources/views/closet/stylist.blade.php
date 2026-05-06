<x-vogue-page title="VogueAI | AI Stylist" skeleton-id="vogue-closet-stylist-skeleton">
    <section class="vogue-highlight reveal mt-6">
        <div>
            <p class="vogue-eyebrow">AI Stylist</p>
            <h2>智慧穿搭推薦</h2>
            <p>
                根據你的衣櫥資料、場合、天氣與風格偏好，產生可保存的穿搭建議。
                目前為 rule_based / degraded 模式，已經會讀取 clothes 資料表，不只是固定假資料。
            </p>
        </div>
        <div class="vogue-closet-toolbar">
            <span class="vogue-chip vogue-chip-degraded">rule_based / degraded</span>
            <a href="{{ route('closet.hub') }}" class="vogue-btn vogue-btn-soft">回 SmartCloset Hub</a>
            <a href="{{ route('closet.index') }}" class="vogue-btn vogue-btn-soft">My Closet</a>
        </div>
    </section>

    @if (session('status'))
        <section class="vogue-section reveal">
            <div class="vogue-card" style="border-color: rgba(34, 197, 94, 0.45);">
                <p>{{ session('status') }}</p>
            </div>
        </section>
    @endif

    @if (session('error'))
        <section class="vogue-section reveal">
            <div class="vogue-card" style="border-color: rgba(239, 68, 68, 0.45);">
                <p>{{ session('error') }}</p>
            </div>
        </section>
    @endif

    @if ($errors->any())
        <section class="vogue-section reveal">
            <div class="vogue-card" style="border-color: rgba(239, 68, 68, 0.45);">
                <p style="font-weight: 700;">表單資料需要修正：</p>
                <ul class="mt-2 list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    <section class="vogue-section reveal">
        <div class="grid gap-4 lg:grid-cols-2">
            <article class="vogue-card space-y-4">
                <div>
                    <h3>建立穿搭建議</h3>
                    <p class="mt-1" style="color: var(--vogue-ink-soft);">
                        輸入今天的情境，系統會從你的衣櫥資料中挑選候選衣物，並建立一筆 Stylist History。
                    </p>
                </div>

                <div class="vogue-card" style="border-color: rgba(59, 130, 246, 0.45);">
                    <p style="color: var(--vogue-ink-soft);">
                        目前衣櫥可用衣物數量：{{ $clothes->count() }} 件。
                        若衣物太少，建議先到上傳衣物頁新增更多單品。
                    </p>
                </div>

                <form method="POST" action="{{ route('closet.stylist.generate') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label class="vogue-label" for="occasion">場合</label>
                        <input
                            id="occasion"
                            name="occasion"
                            type="text"
                            class="vogue-input"
                            value="{{ old('occasion', '校園日常') }}"
                            placeholder="例如：校園日常、約會、面試、通勤"
                            required
                        >
                    </div>

                    <div>
                        <label class="vogue-label" for="weather">天氣</label>
                        <input
                            id="weather"
                            name="weather"
                            type="text"
                            class="vogue-input"
                            value="{{ old('weather', '晴天 24°C') }}"
                            placeholder="例如：晴天 24°C、下雨、偏冷、炎熱"
                        >
                    </div>

                    <div>
                        <label class="vogue-label" for="style_preference">風格偏好</label>
                        <textarea
                            id="style_preference"
                            name="style_preference"
                            rows="4"
                            class="vogue-textarea"
                            placeholder="例如：簡約都會風、韓系休閒、不要太正式、喜歡黑白灰"
                        >{{ old('style_preference', '簡約都會風') }}</textarea>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="vogue-btn vogue-btn-solid">
                            產生 AI Stylist 推薦
                        </button>
                        <a href="{{ route('closet.create') }}" class="vogue-btn vogue-btn-soft">
                            先上傳衣物
                        </a>
                        <a href="{{ route('closet.search') }}" class="vogue-btn vogue-btn-soft">
                            前往 AI 搜尋
                        </a>
                    </div>
                </form>
            </article>

            <article class="vogue-card">
                <h3>最近推薦紀錄</h3>
                <p class="mt-1" style="color: var(--vogue-ink-soft);">
                    推薦結果會保存到 stylist_history，之後可延伸成接受 / 拒絕紀錄與個人化學習。
                </p>

                <div class="mt-4 space-y-3">
                    @forelse ($stylistHistories as $history)
                        @php
                            $recommendation = $history['recommendation'] ?? [];
                            $items = $history['selected_items'] ?? [];

                            $chipClass = [
                                'success' => 'vogue-chip-success',
                                'degraded' => 'vogue-chip-degraded',
                                'pending' => 'vogue-chip-pending',
                                'failed' => 'vogue-chip-pending',
                            ][$history['status']] ?? 'vogue-chip-degraded';
                        @endphp

                        <div class="vogue-card" style="padding: 0.9rem;">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p style="color: var(--vogue-heading); font-weight: 700;">
                                        {{ $recommendation['title'] ?? 'AI Stylist 推薦' }}
                                    </p>
                                    <p class="mt-1 text-sm" style="color: var(--vogue-ink-soft);">
                                        {{ $history['id'] }} · {{ $history['created_at'] }}
                                    </p>
                                </div>

                                <span class="vogue-chip {{ $chipClass }}">
                                    {{ $history['mode'] }} / {{ $history['status'] }}
                                </span>
                            </div>

                            <div class="mt-3 grid gap-2 sm:grid-cols-3">
                                <div>
                                    <p class="vogue-label">場合</p>
                                    <p>{{ $history['occasion'] ?? '未提供' }}</p>
                                </div>
                                <div>
                                    <p class="vogue-label">天氣</p>
                                    <p>{{ $history['weather'] ?? '未提供' }}</p>
                                </div>
                                <div>
                                    <p class="vogue-label">風格</p>
                                    <p>{{ $history['style_preference'] ?? '未提供' }}</p>
                                </div>
                            </div>

                            @if (!empty($recommendation['summary']))
                                <p class="mt-3" style="color: var(--vogue-ink-soft);">
                                    {{ $recommendation['summary'] }}
                                </p>
                            @endif

                            @if (!empty($items))
                                <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                    @foreach ($items as $item)
                                        <div class="vogue-card" style="padding: 0.75rem;">
                                            @if (!empty($item['image_url']))
                                                <img
                                                    src="{{ $item['image_url'] }}"
                                                    alt="{{ $item['name'] ?? 'clothing item' }}"
                                                    class="w-full rounded-xl object-cover"
                                                    style="height: 150px;"
                                                >
                                            @endif

                                            <p class="mt-2" style="color: var(--vogue-heading); font-weight: 700;">
                                                {{ $item['name'] ?? '未命名衣物' }}
                                            </p>
                                            <p class="mt-1 text-sm" style="color: var(--vogue-ink-soft);">
                                                {{ $item['category'] ?? '未分類' }} · {{ $item['color'] ?? '未設定顏色' }}
                                            </p>

                                            @if (!empty($item['id']))
                                                <a
                                                    href="{{ route('closet.show', $item['id']) }}"
                                                    class="vogue-btn vogue-btn-soft mt-3"
                                                >
                                                    查看衣物
                                                </a>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if (!empty($recommendation['reasoning']))
                                <div class="mt-3">
                                    <p class="vogue-label">推薦理由</p>
                                    <ul class="mt-2 list-disc pl-5" style="color: var(--vogue-ink-soft);">
                                        @foreach ($recommendation['reasoning'] as $reason)
                                            <li>{{ $reason }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if (!empty($recommendation['styling_tips']))
                                <div class="mt-3">
                                    <p class="vogue-label">Styling Tips</p>
                                    <ul class="mt-2 list-disc pl-5" style="color: var(--vogue-ink-soft);">
                                        @foreach ($recommendation['styling_tips'] as $tip)
                                            <li>{{ $tip }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="vogue-card" style="padding: 0.9rem;">
                            <p style="color: var(--vogue-ink-soft);">
                                目前尚無推薦紀錄。請先輸入場合、天氣與風格偏好，產生第一筆 AI Stylist 建議。
                            </p>
                        </div>
                    @endforelse
                </div>
            </article>
        </div>
    </section>
</x-vogue-page>