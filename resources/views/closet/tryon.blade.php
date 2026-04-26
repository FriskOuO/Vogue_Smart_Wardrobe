<x-vogue-page title="VogueAI | Try-On / Pose" skeleton-id="vogue-closet-tryon-skeleton">
    <section class="vogue-highlight reveal mt-6">
        <div>
            <p class="vogue-eyebrow">Try-On / Pose</p>
            <h2>虛擬試穿與姿態分析</h2>
            <p>此頁整合人物照片、衣物輸入與姿態分析狀態，預留 /ai/pose 串接欄位。</p>
        </div>
        <div class="vogue-closet-toolbar">
            <span class="vogue-chip vogue-chip-degraded">degraded/mock</span>
            <a href="{{ route('closet.hub') }}" class="vogue-btn vogue-btn-soft">回 Hub</a>
        </div>
    </section>

    <section class="vogue-section reveal">
        <div class="grid gap-4 lg:grid-cols-2">
            <article class="vogue-card space-y-4">
                <h3>Try-on 輸入</h3>
                <div>
                    <label class="vogue-label" for="person_photo">人物照片</label>
                    <input id="person_photo" type="file" class="vogue-file-input" disabled>
                </div>
                <div>
                    <label class="vogue-label" for="garment_photo">衣物照片</label>
                    <input id="garment_photo" type="file" class="vogue-file-input" disabled>
                </div>
                <button type="button" class="vogue-btn vogue-btn-solid" disabled>執行 Try-on 與 Pose 分析</button>
            </article>

            <article class="vogue-card">
                <h3>Pose Job 狀態</h3>
                <div class="mt-4 space-y-3">
                    @foreach ($poseJobs as $job)
                        <div class="vogue-card" style="padding: 0.8rem;">
                            <div class="flex items-center justify-between">
                                <p style="color: var(--vogue-heading); font-weight: 700;">{{ $job['id'] }}</p>
                                <span class="vogue-chip {{ $job['status'] === 'pending' ? 'vogue-chip-pending' : 'vogue-chip-success' }}">{{ $job['status'] }}</span>
                            </div>
                            <p class="mt-2" style="color: var(--vogue-ink-soft);">mode: {{ $job['mode'] }}</p>
                        </div>
                    @endforeach
                </div>
            </article>
        </div>
    </section>
</x-vogue-page>
