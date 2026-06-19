<x-vogue-page title="VogueAI | 儀表板" skeleton-id="vogue-dashboard-skeleton">
    <div id="overview">
        <section class="vogue-highlight reveal mt-6">
            <div>
                <p class="vogue-eyebrow">儀表板</p>
                <h2>{{ auth()->user()->name }}，歡迎回來</h2>
                <p>這裡整理目前可操作的核心入口，方便快速確認智慧衣櫥、AI 搜尋、穿搭推薦與展示模組的完成度。</p>
            </div>
            <ul class="vogue-points">
                <li>集中進入衣櫥與 AI 功能</li>
                <li>快速確認目前模組狀態</li>
                <li>後續可接續模型與平台功能</li>
            </ul>
        </section>

        <section id="features" class="vogue-section">
            <div class="vogue-section-head reveal">
                <h2>功能入口</h2>
                <p>依照展示流程排序，從衣櫥資料建立到 AI 任務都可以直接進入。</p>
            </div>
            <div class="vogue-grid">
                <a href="{{ route('closet.hub') }}" class="vogue-card reveal block"><h3>智慧衣櫥總覽</h3><p>核心入口、快速統計與功能導覽。</p></a>
                <a href="{{ route('closet.index') }}" class="vogue-card reveal block"><h3>我的衣櫥</h3><p>查看衣物、AI 狀態、分類與穿著紀錄。</p></a>
                <a href="{{ route('closet.create') }}" class="vogue-card reveal block"><h3>上傳衣物</h3><p>新增衣物照片與備註，建立 AI 分析資料。</p></a>
                <a href="{{ route('closet.search') }}" class="vogue-card reveal block"><h3>AI 搜尋</h3><p>用文字搜尋衣櫥，並保留 keyword fallback。</p></a>
                <a href="{{ route('closet.stylist') }}" class="vogue-card reveal block"><h3>AI 穿搭顧問</h3><p>產生推薦、送出回饋並保存穿搭紀錄。</p></a>
                <a href="{{ route('closet.tryon') }}" class="vogue-card reveal block"><h3>試穿 / 姿態</h3><p>建立姿態任務並檢查 pose quality。</p></a>
                <a href="{{ route('workspace.show', 'runway-video') }}" class="vogue-card reveal block"><h3>伸展台影片</h3><p>建立分鏡、提示詞與預覽狀態。</p></a>
                <a href="{{ route('workspace.show', 'digital-twin') }}" class="vogue-card reveal block"><h3>數位分身</h3><p>建立個人資料與衣櫥型風格分析。</p></a>
                <a href="{{ route('workspace.show', 'community') }}" class="vogue-card reveal block"><h3>社群工作區</h3><p>保留平台模組入口，後續接貼文與互動。</p></a>
                <a href="{{ route('workspace.show', 'showcase') }}" class="vogue-card reveal block"><h3>展示牆工作區</h3><p>保留商品展示與匯入流程入口。</p></a>
                <a href="{{ route('profile.show') }}" class="vogue-card reveal block"><h3>帳號總覽</h3><p>查看目前登入帳號與權限資料。</p></a>
            </div>
        </section>

        <section id="platform" class="vogue-section reveal">
            <div class="vogue-section-head">
                <h2>平台狀態</h2>
                <p>目前核心流程具備安全備援狀態；正式上線前可逐步確認真實模型、外部服務與錯誤處理。</p>
            </div>
            <div class="vogue-stack-grid">
                <div><h3>前端層</h3><p>Laravel Blade + Vite + Tailwind</p></div>
                <div><h3>後端層</h3><p>Laravel 12 + Breeze Session + SQLite</p></div>
                <div><h3>AI 層</h3><p>FastAPI + Gemini + CLIP + Qdrant + Veo</p></div>
            </div>
        </section>
    </div>
</x-vogue-page>
