<x-vogue-page title="VogueAI | 服務維護中" skeleton-id="vogue-error-503-skeleton">
    <section class="vogue-section mt-6">
        <x-vogue-state
            type="retry"
            title="服務正在維護或忙碌中"
            message="目前部分 AI 或平台服務暫時無法使用。請稍後重新整理，或先返回衣櫥查看已保存的資料。"
            action-label="回到我的衣櫥"
            :action-href="route('closet.index')"
        />
    </section>
</x-vogue-page>
