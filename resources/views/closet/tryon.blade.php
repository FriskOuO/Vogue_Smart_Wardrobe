<x-vogue-page title="VogueAI | Try-On / Pose" skeleton-id="vogue-closet-tryon-skeleton">
    <section class="vogue-highlight reveal mt-6">
        <div>
            <p class="vogue-eyebrow">Try-On / Pose</p>
            <h2>Virtual Try-on L1</h2>
            <p>目前為 L1 展示版：上傳人物照片並選擇衣物後，系統會建立 AI Job，呼叫 /ai/pose，並回傳 mock / degraded 姿態分析結果。</p>
        </div>
        <div class="vogue-closet-toolbar">
            <span class="vogue-chip vogue-chip-degraded">L1 degraded/mock</span>
            <a href="{{ route('closet.hub') }}" class="vogue-btn vogue-btn-soft">回 Hub</a>
        </div>
    </section>

    <section class="vogue-section reveal">
        @if (session('status'))
            <div class="vogue-card reveal" style="border-color: rgba(16, 185, 129, 0.45);">
                <p style="color: var(--vogue-ink);">{{ session('status') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="vogue-card reveal" style="border-color: rgba(244, 63, 94, 0.45);">
                <p style="color: var(--vogue-heading); font-weight: 700;">表單驗證失敗</p>
                <ul class="mt-2 list-disc pl-5" style="color: var(--vogue-ink-soft);">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-4 lg:grid-cols-2">
            <article class="vogue-card space-y-4">
                <h3>建立 Try-on L1 任務</h3>
                <p style="color: var(--vogue-ink-soft);">
                    選擇衣櫥中的衣物，並上傳一張人物照片。此版本先做 Pose mock / degraded 分析，用於展示 Try-on 任務流程。
                </p>

                <form method="POST" action="{{ route('closet.tryon.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div>
                        <label class="vogue-label" for="clothing_id">選擇衣物</label>
                        <select id="clothing_id" name="clothing_id" class="vogue-input" required>
                            <option value="">請選擇一件衣物</option>
                            @foreach ($clothes as $clothing)
                                <option value="{{ $clothing->id }}" @selected(old('clothing_id') == $clothing->id)>
                                    #{{ $clothing->id }}｜{{ $clothing->name }}｜{{ $clothing->category ?? '未分類' }}｜{{ $clothing->color ?? '未知顏色' }}
                                </option>
                            @endforeach
                        </select>
                        @error('clothing_id')
                            <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="vogue-label" for="person_photo">人物照片</label>
                        <input id="person_photo" name="person_photo" type="file" accept="image/*" class="vogue-file-input" required>
                        @error('person_photo')
                            <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="vogue-card" style="border-color: rgba(59, 130, 246, 0.45);">
                        <p style="color: var(--vogue-ink-soft);">
                            L1 說明：目前不產生真實換衣圖片，而是建立 Try-on 任務、呼叫 Pose 分析，並顯示 mock 姿態結果與 degraded 狀態。
                        </p>
                    </div>

                    <button type="submit" class="vogue-btn vogue-btn-solid">
                        建立 Try-on L1 任務
                    </button>
                </form>
            </article>

            <article class="vogue-card">
                <h3>Pose Job 紀錄</h3>

                <div class="mt-4 space-y-3">
                    @forelse ($poseJobs as $job)
                        @php
                            $chipClass = [
                                'success' => 'vogue-chip-success',
                                'degraded' => 'vogue-chip-degraded',
                                'pending' => 'vogue-chip-pending',
                                'processing' => 'vogue-chip-pending',
                                'failed' => 'vogue-chip-pending',
                            ][$job['status']] ?? 'vogue-chip-pending';

                            $poseAnalysis = $job['result']['pose_analysis'] ?? null;
                            $keypoints = $job['result']['keypoints'] ?? [];
                        @endphp

                        <div class="vogue-card" style="padding: 0.9rem;">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p style="color: var(--vogue-heading); font-weight: 700;">
                                        {{ $job['id'] }}
                                    </p>
                                    <p class="mt-1 text-sm" style="color: var(--vogue-ink-soft);">
                                        clothing_id: {{ $job['clothing_id'] ?? 'N/A' }} · {{ $job['created_at'] ?? '' }}
                                    </p>
                                </div>

                                <span class="vogue-chip {{ $chipClass }}">
                                    {{ strtoupper($job['status']) }}
                                </span>
                            </div>

                            <p class="mt-2" style="color: var(--vogue-ink-soft);">
                                mode: {{ $job['mode'] ?? 'mock' }}
                            </p>

                            @if (!empty($job['request_id']))
                                <p class="mt-1 text-sm" style="color: var(--vogue-ink-soft);">
                                    request_id: {{ $job['request_id'] }}
                                </p>
                            @endif

                            @if ($poseAnalysis)
                                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                    <div>
                                        <p class="vogue-label">Full body visible</p>
                                        <p>{{ ($poseAnalysis['full_body_visible'] ?? false) ? 'Yes' : 'No' }}</p>
                                    </div>
                                    <div>
                                        <p class="vogue-label">Shoulder balance</p>
                                        <p>{{ $poseAnalysis['shoulder_balance'] ?? 'unknown' }}</p>
                                    </div>
                                </div>

                                @if (!empty($poseAnalysis['posture_notes']))
                                    <div class="mt-3">
                                        <p class="vogue-label">Posture Notes</p>
                                        <ul class="mt-1 list-disc pl-5" style="color: var(--vogue-ink-soft);">
                                            @foreach ($poseAnalysis['posture_notes'] as $note)
                                                <li>{{ $note }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if (!empty($poseAnalysis['fit_notes']))
                                    <div class="mt-3">
                                        <p class="vogue-label">Fit Notes</p>
                                        <ul class="mt-1 list-disc pl-5" style="color: var(--vogue-ink-soft);">
                                            @foreach ($poseAnalysis['fit_notes'] as $note)
                                                <li>{{ $note }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            @endif

                            @if (!empty($keypoints))
                                <div class="mt-3">
                                    <p class="vogue-label">Keypoints</p>
                                    <p style="color: var(--vogue-ink-soft);">
                                        已回傳 {{ count($keypoints) }} 個 mock keypoints。
                                    </p>
                                </div>
                            @endif

                            @if (!empty($job['error_code']))
                                <div class="vogue-card mt-3" style="border-color: rgba(244, 63, 94, 0.45);">
                                    <p style="color: var(--vogue-heading); font-weight: 700;">
                                        {{ $job['error_code'] }}
                                    </p>
                                    <p class="mt-1" style="color: var(--vogue-ink-soft);">
                                        {{ $job['error_message'] }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="vogue-card" style="padding: 0.9rem;">
                            <p style="color: var(--vogue-ink-soft);">
                                目前尚無 Try-on / Pose 任務。請先建立一筆 L1 任務。
                            </p>
                        </div>
                    @endforelse
                </div>
            </article>
        </div>
    </section>
</x-vogue-page>