<x-vogue-page title="VogueAI | 系統暫時無法完成請求" skeleton-id="vogue-error-500-skeleton">
    <section class="vogue-section mt-6">
        <x-vogue-state
            type="error"
            title="系統暫時無法完成請求"
            message="我們已保留必要的錯誤紀錄，請稍後重試。若同一操作持續失敗，請回到智慧衣櫥總覽重新開始。"
            action-label="回到智慧衣櫥"
            :action-href="route('closet.hub')"
        />
    </section>
</x-vogue-page>
