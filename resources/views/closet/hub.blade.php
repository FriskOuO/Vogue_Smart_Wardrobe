<x-vogue-page title="VogueAI | Smart Closet Hub" skeleton-id="vogue-closet-hub-skeleton">
    <section class="vogue-highlight reveal mt-6">
        <div>
            <p class="vogue-eyebrow">Smart Closet Hub</p>
            <h2>AI 工作流入口</h2>
            <p>把上傳、搜尋、Stylist、Try-on 全部集中在這個介面，方便快速切換與驗證前端流程。</p>
        </div>
        <div class="vogue-closet-toolbar">
            <a href="{{ route('closet.index') }}" class="vogue-btn vogue-btn-outline">進入 My Closet</a>
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
                <h3>Upload Garment</h3>
                <p>上傳衣物圖片與備註，建立後續 AI 分析所需資料。</p>
            </a>
            <a href="{{ route('closet.search') }}" class="vogue-card block">
                <h3>AI Search</h3>
                <p>提供以圖搜圖與以文搜圖介面，預留 embedding / similar search 串接位置。</p>
            </a>
            <a href="{{ route('closet.stylist') }}" class="vogue-card block">
                <h3>AI Stylist</h3>
                <p>以場合與偏好生成穿搭建議，顯示 pending / degraded 狀態。</p>
            </a>
            <a href="{{ route('closet.tryon') }}" class="vogue-card block">
                <h3>Try-On / Pose</h3>
                <p>展示人物照片、衣物與姿態分析流程的前端操作入口。</p>
            </a>
            <a href="{{ route('closet.index') }}" class="vogue-card block">
                <h3>My Closet</h3>
                <p>查看衣物卡片、AI 狀態、詳細分析與標籤。</p>
            </a>
            <a href="{{ route('features.show', ['feature' => 'smart-closet']) }}" class="vogue-card block">
                <h3>Feature Spec</h3>
                <p>回到功能模組規劃頁，對齊簡報與開發分工。</p>
            </a>
            <a href="{{ route('workspace.show', 'community') }}" class="vogue-card block">
                <h3>README Modules Workspace</h3>
                <p>開啟舊版 README 高頻模組的統一介面集合（Community、Showcase、Travel 等）。</p>
            </a>
        </div>
    </section>
</x-vogue-page>
