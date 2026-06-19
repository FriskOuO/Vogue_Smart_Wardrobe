<x-vogue-page title="VogueAI | 隱私政策" skeleton-id="vogue-privacy-skeleton">
    <section class="vogue-highlight reveal mt-6">
        <div>
            <p class="vogue-eyebrow">隱私政策</p>
            <h2>我們如何保護你的衣櫥與個人資料</h2>
            <p>VogueAI 只收集提供智慧衣櫥、搜尋、穿搭建議與帳號服務所需的資料。正式上線前，請依實際部署地區與法規再交由法務確認。</p>
        </div>
        <div class="vogue-closet-toolbar">
            <a href="{{ route('legal.terms') }}" class="vogue-btn vogue-btn-soft">服務條款</a>
            <a href="{{ route('legal.acceptable-use') }}" class="vogue-btn vogue-btn-soft">使用限制</a>
        </div>
    </section>

    <section class="vogue-section grid gap-4 lg:grid-cols-2">
        <article class="vogue-card">
            <h3>收集的資料</h3>
            <p class="mt-2" style="color: var(--vogue-ink-soft);">帳號資料、衣物照片、衣物標籤、穿搭偏好、AI 任務紀錄、系統診斷紀錄，以及你主動輸入的場合、天氣、限制條件與回饋。</p>
        </article>

        <article class="vogue-card">
            <h3>資料用途</h3>
            <p class="mt-2" style="color: var(--vogue-ink-soft);">用於建立衣櫥、搜尋相似衣物、產生穿搭建議、改善使用體驗、維護安全與排查錯誤。我們不會在未經授權下公開你的私人衣物照片。</p>
        </article>

        <article class="vogue-card">
            <h3>AI 與第三方服務</h3>
            <p class="mt-2" style="color: var(--vogue-ink-soft);">當你啟用真實 AI provider 時，必要的文字或影像資料可能送往設定的模型服務處理。API key 僅應存放於伺服器環境變數，不會顯示在頁面或提交到版本庫。</p>
        </article>

        <article class="vogue-card">
            <h3>你的控制權</h3>
            <p class="mt-2" style="color: var(--vogue-ink-soft);">你可以更新個人資料、刪除帳號，並依產品提供的功能移除衣物資料。正式部署時，應補上資料匯出、刪除請求與客服聯絡流程。</p>
        </article>
    </section>
</x-vogue-page>
