<x-vogue-page title="VogueAI | AI Stylist" skeleton-id="vogue-closet-stylist-skeleton">
    <section class="vogue-highlight reveal mt-6">
        <div>
            <p class="vogue-eyebrow">AI Stylist</p>
            <h2>情境穿搭建議</h2>
            <p>可填寫場合、氣候與風格偏好，後續直接接到 AI Stylist 推薦邏輯。</p>
        </div>
        <div class="vogue-closet-toolbar">
            <span class="vogue-chip vogue-chip-pending">status pending</span>
            <a href="{{ route('closet.hub') }}" class="vogue-btn vogue-btn-soft">回 Hub</a>
        </div>
    </section>

    <section class="vogue-section reveal">
        <div class="grid gap-4 lg:grid-cols-2">
            <article class="vogue-card space-y-4">
                <h3>推薦條件</h3>
                <div>
                    <label class="vogue-label" for="occasion">場合</label>
                    <input id="occasion" type="text" class="vogue-input" placeholder="例如：商務會議、約會、旅行" disabled>
                </div>
                <div>
                    <label class="vogue-label" for="weather">天氣</label>
                    <input id="weather" type="text" class="vogue-input" placeholder="例如：24C，濕度 70%" disabled>
                </div>
                <div>
                    <label class="vogue-label" for="style_pref">風格偏好</label>
                    <textarea id="style_pref" rows="4" class="vogue-textarea" placeholder="例如：俐落、低飽和、中性色" disabled></textarea>
                </div>
                <button type="button" class="vogue-btn vogue-btn-solid" disabled>產生穿搭建議</button>
            </article>

            <article class="vogue-card">
                <h3>推薦結果（展示）</h3>
                <div class="mt-4 space-y-3">
                    @foreach ($looks as $look)
                        <div class="vogue-card" style="padding: 0.85rem;">
                            <div class="flex items-center justify-between gap-2">
                                <p style="color: var(--vogue-heading); font-weight: 700;">{{ $look['title'] }}</p>
                                <span class="vogue-chip {{ str_contains($look['status'], 'pending') ? 'vogue-chip-pending' : 'vogue-chip-degraded' }}">{{ $look['status'] }}</span>
                            </div>
                            <p class="mt-2" style="color: var(--vogue-ink-soft);">{{ implode(' + ', $look['items']) }}</p>
                        </div>
                    @endforeach
                </div>
            </article>
        </div>
    </section>
</x-vogue-page>
