<x-vogue-page title="VogueAI | 新增衣物" skeleton-id="vogue-closet-create-skeleton">
    <section class="vogue-highlight reveal mt-6">
        <div>
            <p class="vogue-eyebrow">智慧衣櫥</p>
            <h2>新增衣物</h2>
            <p>上傳衣物照片並填入簡短備註，系統會建立衣物資料並觸發 AI 分析流程。</p>
        </div>
        <div class="vogue-closet-toolbar">
            <a href="{{ route('closet.index') }}" class="vogue-btn vogue-btn-soft">回我的衣櫥</a>
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
                <input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="例如：白色亞麻襯衫" required class="vogue-input">
                @error('name')
                    <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="notes" class="vogue-label">備註</label>
                <textarea id="notes" name="notes" rows="5" placeholder="可以填入材質、版型、常穿場合或搭配想法" class="vogue-textarea">{{ old('notes') }}</textarea>
                @error('notes')
                    <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                @enderror
            </div>

            <div class="vogue-card" style="border-color: rgba(59, 130, 246, 0.45);">
                <p style="color: var(--vogue-ink-soft);">目前會先建立可展示的 AI 分析資料；真實模型接入後可沿用同一個流程。</p>
            </div>

            <button type="submit" class="vogue-btn vogue-btn-solid">送出</button>
        </form>
    </section>
</x-vogue-page>
