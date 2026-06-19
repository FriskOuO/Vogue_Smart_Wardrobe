<x-vogue-page title="VogueAI | 服務條款" skeleton-id="vogue-terms-skeleton">
    <section class="vogue-highlight reveal mt-6">
        <div>
            <p class="vogue-eyebrow">服務條款</p>
            <h2>使用 VogueAI 前請先了解服務邊界</h2>
            <p>VogueAI 提供衣櫥管理、AI 搜尋、穿搭建議與相關創作工具。AI 產出可作為參考，不應視為專業、醫療、法律或財務建議。</p>
        </div>
        <div class="vogue-closet-toolbar">
            <a href="{{ route('legal.privacy') }}" class="vogue-btn vogue-btn-soft">隱私政策</a>
            <a href="{{ route('legal.acceptable-use') }}" class="vogue-btn vogue-btn-soft">使用限制</a>
        </div>
    </section>

    <section class="vogue-section grid gap-4 lg:grid-cols-2">
        <article class="vogue-card">
            <h3>帳號責任</h3>
            <p class="mt-2" style="color: var(--vogue-ink-soft);">你需妥善保管登入資訊，並確保上傳的照片、文字與偏好資料由你合法持有或已取得必要授權。</p>
        </article>

        <article class="vogue-card">
            <h3>AI 產出限制</h3>
            <p class="mt-2" style="color: var(--vogue-ink-soft);">AI 可能產生不完整、不準確或不符合你期待的結果。重要決策前，請自行檢查內容並保留人工判斷。</p>
        </article>

        <article class="vogue-card">
            <h3>服務可用性</h3>
            <p class="mt-2" style="color: var(--vogue-ink-soft);">模型服務、向量資料庫、第三方 API 或網路狀態可能造成延遲或失敗。系統會盡量提供錯誤提示、備援結果與重試入口。</p>
        </article>

        <article class="vogue-card">
            <h3>變更與終止</h3>
            <p class="mt-2" style="color: var(--vogue-ink-soft);">若帳號違反使用限制或危害服務安全，VogueAI 可限制、暫停或終止相關功能。正式上線時應補上通知與申訴流程。</p>
        </article>
    </section>
</x-vogue-page>
