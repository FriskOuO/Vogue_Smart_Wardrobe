<x-vogue-page title="VogueAI | Add Clothing" skeleton-id="vogue-closet-create-skeleton">
    <section class="vogue-highlight reveal mt-6">
        <div>
            <p class="vogue-eyebrow">Smart Closet</p>
            <h2>新增衣物</h2>
            <p>先把上傳與文字欄位流程確認完成，後續直接接到你正在做的資料表與 AI 串接。</p>
        </div>
        <div class="vogue-closet-toolbar">
            <a href="{{ route('closet.index') }}" class="vogue-btn vogue-btn-soft">回衣櫥列表</a>
        </div>
    </section>

    <section class="vogue-section reveal">
        <form method="POST" action="{{ route('closet.store') }}" enctype="multipart/form-data" class="vogue-card space-y-5">
            @csrf

            <div>
                <label for="image" class="vogue-label">上傳圖片</label>
                <input id="image" name="image" type="file" accept="image/*" class="vogue-file-input">
                @error('image')
                    <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="name" class="vogue-label">衣物名稱</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="例如：White Linen Shirt" required class="vogue-input">
                @error('name')
                    <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="notes" class="vogue-label">備註</label>
                <textarea id="notes" name="notes" rows="5" placeholder="可先填寫材質、購買地點、搭配想法..." class="vogue-textarea">{{ old('notes') }}</textarea>
                @error('notes')
                    <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                @enderror
            </div>

            <div class="vogue-card" style="border-color: rgba(59, 130, 246, 0.45);">
                <p style="color: var(--vogue-ink-soft);">目前為前端展示模式：送出後回到列表顯示成功訊息，尚未寫入資料庫。</p>
            </div>

            <button type="submit" class="vogue-btn vogue-btn-solid">送出</button>
        </form>
    </section>
</x-vogue-page>
