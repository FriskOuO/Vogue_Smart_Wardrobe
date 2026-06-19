<x-vogue-page title="VogueAI | 智慧衣櫥總覽" skeleton-id="vogue-closet-hub-skeleton">
    <section class="vogue-highlight reveal mt-6">
        <div>
            <p class="vogue-eyebrow">智慧衣櫥總覽</p>
            <h2>核心功能入口</h2>
            <p>從這裡進入上傳、搜尋、推薦、試穿與各個展示模組，確認每個流程都能連到實際資料。</p>
        </div>
        <div class="vogue-closet-toolbar">
            <a href="{{ route('closet.index') }}" class="vogue-btn vogue-btn-outline">進入我的衣櫥</a>
        </div>
    </section>

    <section class="vogue-section">
        <div class="vogue-stack-grid reveal">
            @foreach ($quickStats as $stat)
                <div class="vogue-card">
                    <p class="vogue-label">{{ $stat['label'] }}</p>
                    <h3>{{ $stat['value'] }}</h3>
                </div>
            @endforeach
        </div>

        <div class="vogue-grid reveal">
            <a href="{{ route('closet.create') }}" class="vogue-card block">
                <h3>上傳衣物</h3>
                <p>新增衣物照片與備註，建立 AI 分析與影像向量所需的基礎資料。</p>
            </a>
            <a href="{{ route('closet.search') }}" class="vogue-card block">
                <h3>AI 搜尋</h3>
                <p>用文字查找衣櫥，AI 無法連線時仍會使用關鍵字備援。</p>
            </a>
            <a href="{{ route('closet.stylist') }}" class="vogue-card block">
                <h3>AI 穿搭顧問</h3>
                <p>依照場合、天氣、風格與衣櫥資料產生可保存的穿搭建議。</p>
            </a>
            <a href="{{ route('closet.tryon') }}" class="vogue-card block">
                <h3>試穿 / 姿態</h3>
                <p>建立試穿 L1 任務，檢查人物照片姿態品質與任務紀錄。</p>
            </a>
            <a href="{{ route('closet.index') }}" class="vogue-card block">
                <h3>我的衣櫥</h3>
                <p>查看所有衣物、AI 狀態、分類與詳細分析內容。</p>
            </a>
            <a href="{{ route('features.show', ['feature' => 'smart-closet']) }}" class="vogue-card block">
                <h3>功能規格</h3>
                <p>查看智慧衣櫥的功能拆解、交付層級與後續擴充方向。</p>
            </a>
            <a href="{{ route('workspace.show', 'community') }}" class="vogue-card block">
                <h3>模組工作區</h3>
                <p>查看社群、展示牆、旅行打包與其他平台模組的暫存入口。</p>
            </a>
        </div>
    </section>

    <section id="manual-qa" class="vogue-section vogue-critical-flow">
        <div class="vogue-section-head">
            <h2>人工驗收總控台</h2>
            <p>依照目前完成度排序，逐項打開功能頁確認畫面上的驗收訊號。</p>
        </div>

        <div class="vogue-grid">
            @foreach ($manualQaItems as $item)
                <article class="vogue-card">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="vogue-label">{{ $item['chip'] }}</p>
                            <h3>{{ $item['title'] }}</h3>
                        </div>
                        <span class="vogue-chip {{ ($item['status'] ?? '') === 'ready' ? 'vogue-chip-success' : 'vogue-chip-degraded' }}">
                            可人工驗收
                        </span>
                    </div>

                    <p class="mt-3" style="color: var(--vogue-ink-soft);">
                        {{ $item['expected'] }}
                    </p>

                    <div class="mt-4">
                        <a href="{{ $item['href'] }}" class="vogue-btn vogue-btn-solid">
                            前往檢查
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
</x-vogue-page>
