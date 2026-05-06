<x-vogue-page :title="'VogueAI | ' . $module['title']" skeleton-id="vogue-workspace-skeleton">
    <section class="vogue-highlight reveal mt-6">
        <div>
            <p class="vogue-eyebrow">Module Workspace</p>
            <h2>{{ $module['title'] }}</h2>
            <p>{{ $module['summary'] }}</p>
        </div>
        <div class="vogue-closet-toolbar">
            <span class="vogue-chip {{ str_contains($module['status'], 'degraded') ? 'vogue-chip-degraded' : 'vogue-chip-pending' }}">
                {{ $module['status'] }}
            </span>
            <a href="{{ route('closet.hub') }}" class="vogue-btn vogue-btn-soft">回 Smart Hub</a>
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

        <div class="grid gap-4 lg:grid-cols-3">
            <article class="vogue-card">
                <p class="vogue-label">Primary Action</p>
                <h3>{{ $module['primaryAction'] }}</h3>
            </article>
            <article class="vogue-card">
                <p class="vogue-label">API Endpoint</p>
                <h3>{{ $module['api'] }}</h3>
            </article>
            <article class="vogue-card">
                <p class="vogue-label">Integration Status</p>
                <h3>{{ strtoupper($module['status']) }}</h3>
            </article>
        </div>

        @if ($module['slug'] === 'runway-video')
            <div class="grid gap-4 lg:grid-cols-2 mt-4">
                <article class="vogue-card">
                    <h3>建立 Runway Video L1 Storyboard</h3>
                    <p class="mt-1" style="color: var(--vogue-ink-soft);">
                        L1 階段先不接真實影片生成 API，而是根據衣物建立走秀分鏡 storyboard，並寫入 ai_jobs。
                    </p>

                    <form method="POST" action="{{ route('workspace.runway-video.store') }}" class="mt-4 space-y-4">
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
                        </div>

                        <div>
                            <label class="vogue-label" for="video_style">影片風格</label>
                            <input
                                id="video_style"
                                name="video_style"
                                type="text"
                                value="{{ old('video_style', 'vogue luxury runway') }}"
                                class="vogue-input"
                                placeholder="例如：vogue luxury runway"
                                required
                            >
                        </div>

                        <div>
                            <label class="vogue-label" for="camera_rhythm">鏡頭節奏</label>
                            <input
                                id="camera_rhythm"
                                name="camera_rhythm"
                                type="text"
                                value="{{ old('camera_rhythm', 'slow cinematic camera movement') }}"
                                class="vogue-input"
                                placeholder="例如：slow cinematic camera movement"
                            >
                        </div>

                        <div class="vogue-card" style="border-color: rgba(59, 130, 246, 0.45);">
                            <p style="color: var(--vogue-ink-soft);">
                                L1 說明：目前只建立 Runway Storyboard，不產生真實影片。後續若接入 Veo / RunwayML / Pika，可直接把 prompt 送到影片生成服務。
                            </p>
                        </div>

                        <button type="submit" class="vogue-btn vogue-btn-solid">
                            建立 Runway Storyboard
                        </button>
                    </form>
                </article>

                <article class="vogue-card">
                    <h3>Runway Video Job 紀錄</h3>

                    <div class="mt-4 space-y-3">
                        @forelse ($runwayJobs as $job)
                            @php
                                $chipClass = [
                                    'success' => 'vogue-chip-success',
                                    'degraded' => 'vogue-chip-degraded',
                                    'pending' => 'vogue-chip-pending',
                                    'processing' => 'vogue-chip-pending',
                                    'failed' => 'vogue-chip-pending',
                                ][$job['status']] ?? 'vogue-chip-pending';

                                $result = $job['result'] ?? [];
                                $scenes = $result['scenes'] ?? [];
                                $clothing = $result['clothing'] ?? [];
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

                                @if (!empty($clothing))
                                    <div class="mt-3">
                                        <p class="vogue-label">Runway Item</p>
                                        <p style="color: var(--vogue-heading); font-weight: 700;">
                                            {{ $clothing['name'] ?? 'Unknown Item' }}
                                        </p>
                                        <p style="color: var(--vogue-ink-soft);">
                                            {{ $clothing['category'] ?? '未分類' }} · {{ $clothing['color'] ?? '未知顏色' }}
                                        </p>
                                    </div>
                                @endif

                                @if (!empty($result['prompt']))
                                    <div class="mt-3">
                                        <p class="vogue-label">Prompt</p>
                                        <p style="color: var(--vogue-ink-soft);">
                                            {{ $result['prompt'] }}
                                        </p>
                                    </div>
                                @endif

                                @if (!empty($scenes))
                                    <div class="mt-3 space-y-2">
                                        <p class="vogue-label">Storyboard Scenes</p>

                                        @foreach ($scenes as $scene)
                                            <div class="vogue-card" style="padding: 0.75rem;">
                                                <p style="color: var(--vogue-heading); font-weight: 700;">
                                                    Scene {{ $scene['scene'] ?? '?' }}｜{{ $scene['title'] ?? 'Untitled' }}
                                                </p>
                                                <p class="mt-1" style="color: var(--vogue-ink-soft);">
                                                    {{ $scene['description'] ?? '' }}
                                                </p>
                                                <p class="mt-1 text-sm" style="color: var(--vogue-ink-soft);">
                                                    camera: {{ $scene['camera'] ?? 'N/A' }} · duration: {{ $scene['duration_seconds'] ?? '?' }}s
                                                </p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                @if (!empty($result['message']))
                                    <div class="vogue-card mt-3" style="border-color: rgba(59, 130, 246, 0.45);">
                                        <p style="color: var(--vogue-ink-soft);">
                                            {{ $result['message'] }}
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
                                    目前尚無 Runway Video 任務。請先建立一筆 L1 Storyboard。
                                </p>
                            </div>
                        @endforelse
                    </div>
                </article>
            </div>

                @elseif ($module['slug'] === 'digital-twin')
            <div class="grid gap-4 lg:grid-cols-2 mt-4">
                <article class="vogue-card">
                    <h3>建立 Digital Twin L1 個人風格卡</h3>
                    <p class="mt-1" style="color: var(--vogue-ink-soft);">
                        L1 階段先不接真實 3D Avatar，而是建立個人風格 Profile，作為 Try-on、AI Stylist 與 Runway Video 後續個人化基礎。
                    </p>

                    <form method="POST" action="{{ route('workspace.digital-twin.store') }}" class="mt-4 space-y-4">
                        @csrf

                        <div>
                            <label class="vogue-label" for="height_cm">身高（cm）</label>
                            <input
                                id="height_cm"
                                name="height_cm"
                                type="number"
                                min="100"
                                max="230"
                                value="{{ old('height_cm', 170) }}"
                                class="vogue-input"
                                required
                            >
                        </div>

                        <div>
                            <label class="vogue-label" for="style_preference">風格偏好</label>
                            <input
                                id="style_preference"
                                name="style_preference"
                                type="text"
                                value="{{ old('style_preference', '簡約都會風') }}"
                                class="vogue-input"
                                placeholder="例如：簡約都會風、韓系休閒、正式商務"
                                required
                            >
                        </div>

                        <div>
                            <label class="vogue-label" for="common_occasion">常見穿搭場合</label>
                            <input
                                id="common_occasion"
                                name="common_occasion"
                                type="text"
                                value="{{ old('common_occasion', '校園日常') }}"
                                class="vogue-input"
                                placeholder="例如：校園日常、約會、面試、通勤"
                                required
                            >
                        </div>

                        <div>
                            <label class="vogue-label" for="body_note">補充說明</label>
                            <textarea
                                id="body_note"
                                name="body_note"
                                rows="4"
                                class="vogue-textarea"
                                placeholder="例如：喜歡寬鬆版型、不喜歡太亮的顏色"
                            >{{ old('body_note') }}</textarea>
                        </div>

                        <div class="vogue-card" style="border-color: rgba(59, 130, 246, 0.45);">
                            <p style="color: var(--vogue-ink-soft);">
                                L1 說明：目前建立個人風格卡，不產生真實 3D 模型。後續可接 Gemini、多視角生成或 3D Avatar 服務。
                            </p>
                        </div>

                        <button type="submit" class="vogue-btn vogue-btn-solid">
                            建立 Digital Twin Profile
                        </button>
                    </form>
                    <div class="vogue-card mt-4" style="border-color: rgba(34, 197, 94, 0.45);">
                    <h3>Digital Twin L2 衣櫥風格分析</h3>
                    <p class="mt-1" style="color: var(--vogue-ink-soft);">
                        L2 階段會讀取你的 My Closet 資料，統計常見類別、顏色、季節、場合與風格標籤，
                        建立 closet-based Digital Twin，後續可提供給 AI Stylist 做個人化推薦。
                    </p>

                    <form method="POST" action="{{ route('workspace.digital-twin.analyze-closet') }}" class="mt-4">
                        @csrf

                        <button type="submit" class="vogue-btn vogue-btn-solid">
                            從衣櫥分析 Digital Twin L2
                        </button>
                    </form>
                </div>
                </article>

                <article class="vogue-card">
                    <h3>Digital Twin Profile 紀錄</h3>

                    <div class="mt-4 space-y-3">
                        @forelse ($digitalTwinJobs as $job)
                            @php
                                $chipClass = [
                                    'success' => 'vogue-chip-success',
                                    'degraded' => 'vogue-chip-degraded',
                                    'pending' => 'vogue-chip-pending',
                                    'processing' => 'vogue-chip-pending',
                                    'failed' => 'vogue-chip-pending',
                                ][$job['status']] ?? 'vogue-chip-pending';

                                $result = $job['result'] ?? [];
                                $profile = $result['profile'] ?? [];
                                $styleSummary = $result['style_summary'] ?? [];
                                $styleTags = $result['style_tags'] ?? [];
                                $closetStatistics = $result['closet_statistics'] ?? [];
                            @endphp

                            <div class="vogue-card" style="padding: 0.9rem;">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p style="color: var(--vogue-heading); font-weight: 700;">
                                            {{ $job['id'] }}
                                        </p>
                                        <p class="mt-1 text-sm" style="color: var(--vogue-ink-soft);">
                                            {{ $job['created_at'] ?? '' }}
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

                                <div class="vogue-card mt-3" style="border-color: rgba(168, 85, 247, 0.45);">
                                    <p class="vogue-label">Avatar Placeholder</p>
                                    <p style="color: var(--vogue-heading); font-weight: 700;">
                                        {{ $result['avatar']['label'] ?? 'VogueAI Digital Twin' }}
                                    </p>
                                    <p style="color: var(--vogue-ink-soft);">
                                        目前為 L1 個人風格分身展示卡。
                                    </p>
                                </div>

                                @if (!empty($profile))
                                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                        <div>
                                            <p class="vogue-label">身高</p>
                                            <p>{{ $profile['height_cm'] ?? 'N/A' }} cm</p>
                                        </div>
                                        <div>
                                            <p class="vogue-label">常見場合</p>
                                            <p>{{ $profile['common_occasion'] ?? 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <p class="vogue-label">風格偏好</p>
                                            <p>{{ $profile['style_preference'] ?? 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <p class="vogue-label">補充</p>
                                            <p>{{ $profile['body_note'] ?? '無' }}</p>
                                        </div>
                                    </div>
                                @endif

                                @if (!empty($styleSummary))
                                    <div class="mt-3">
                                        <p class="vogue-label">Style Summary</p>
                                        <p style="color: var(--vogue-heading); font-weight: 700;">
                                            {{ $styleSummary['headline'] ?? '' }}
                                        </p>
                                        <p class="mt-1" style="color: var(--vogue-ink-soft);">
                                            {{ $styleSummary['description'] ?? '' }}
                                        </p>

                                        @if (!empty($styleSummary['recommended_direction']))
                                            <ul class="mt-2 list-disc pl-5" style="color: var(--vogue-ink-soft);">
                                                @foreach ($styleSummary['recommended_direction'] as $direction)
                                                    <li>{{ $direction }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                @endif

                                @if (!empty($closetStatistics))
                                    <div class="mt-3">
                                        <p class="vogue-label">Closet Statistics</p>

                                        <div class="mt-2 grid gap-3 sm:grid-cols-2">
                                            @foreach ([
                                                'top_categories' => '常見類別',
                                                'top_colors' => '常見顏色',
                                                'top_seasons' => '常見季節',
                                                'top_occasions' => '常見場合',
                                                'top_style_tags' => '常見風格標籤',
                                            ] as $key => $label)
                                                @if (!empty($closetStatistics[$key]))
                                                    <div class="vogue-card" style="padding: 0.75rem;">
                                                        <p class="vogue-label">{{ $label }}</p>
                                                        <div class="mt-2 flex flex-wrap gap-2">
                                                            @foreach ($closetStatistics[$key] as $stat)
                                                                <span class="vogue-chip">
                                                                    {{ $stat['label'] }} × {{ $stat['count'] }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                                @if (!empty($styleTags))
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach ($styleTags as $tag)
                                            <span class="vogue-chip">
                                                {{ $tag }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                @if (!empty($result['message']))
                                    <div class="vogue-card mt-3" style="border-color: rgba(59, 130, 246, 0.45);">
                                        <p style="color: var(--vogue-ink-soft);">
                                            {{ $result['message'] }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="vogue-card" style="padding: 0.9rem;">
                                <p style="color: var(--vogue-ink-soft);">
                                    目前尚無 Digital Twin Profile。請先建立一筆 L1 個人風格卡。
                                </p>
                            </div>
                        @endforelse
                    </div>
                </article>
            </div>

        @else
            <div class="grid gap-4 lg:grid-cols-2 mt-4">
                <article class="vogue-card">
                    <h3>輸入欄位（展示用）</h3>
                    <div class="mt-3 space-y-3">
                        @foreach ($module['fields'] as $field)
                            <div>
                                <label class="vogue-label">{{ $field }}</label>
                                <input type="text" class="vogue-input" placeholder="{{ $field }}" disabled>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="vogue-btn vogue-btn-solid mt-4" disabled>
                        {{ $module['primaryAction'] }}
                    </button>
                </article>

                <article class="vogue-card">
                    <h3>任務狀態（展示用）</h3>
                    <div class="mt-3 space-y-3">
                        <div class="vogue-card" style="padding: 0.85rem;">
                            <p class="vogue-label">JOB-2401</p>
                            <p style="color: var(--vogue-heading);">status: {{ $module['status'] }}</p>
                            <p style="color: var(--vogue-ink-soft);">module: {{ $module['slug'] }}</p>
                        </div>
                        <div class="vogue-card" style="padding: 0.85rem;">
                            <p class="vogue-label">JOB-2402</p>
                            <p style="color: var(--vogue-heading);">status: pending</p>
                            <p style="color: var(--vogue-ink-soft);">module: {{ $module['slug'] }}</p>
                        </div>
                    </div>
                </article>
            </div>
        @endif

        <article class="vogue-card mt-4">
            <h3>其他模組</h3>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($modules as $item)
                    <a href="{{ route('workspace.show', $item['slug']) }}" class="vogue-btn {{ $module['slug'] === $item['slug'] ? 'vogue-btn-solid' : 'vogue-btn-soft' }}">
                        {{ $item['title'] }}
                    </a>
                @endforeach
            </div>
        </article>
    </section>
</x-vogue-page>