<x-vogue-page :title="'VogueAI | ' . $module['title']" skeleton-id="vogue-workspace-skeleton">
    <section class="vogue-highlight reveal mt-6">
        <div>
            <p class="vogue-eyebrow">模組工作區</p>
            <h2>{{ $module['title'] }}</h2>
            <p>{{ $module['summary'] }}</p>
        </div>
        <div class="vogue-closet-toolbar">
            <span class="vogue-chip {{ str_contains($module['status'], 'degraded') ? 'vogue-chip-degraded' : 'vogue-chip-pending' }}">
                {{ $module['status'] }}
            </span>
            <a href="{{ route('closet.hub') }}" class="vogue-btn vogue-btn-soft">回智慧衣櫥總覽</a>
        </div>
    </section>

    <section class="vogue-section vogue-critical-flow">
        @if (session('status'))
            <div class="vogue-card reveal" style="border-color: rgba(16, 185, 129, 0.45);">
                <p style="color: var(--vogue-ink);">{{ session('status') }}</p>
            </div>
        @endif

        @if (session('error'))
            <div class="vogue-card reveal" style="border-color: rgba(244, 63, 94, 0.45);">
                <p style="color: var(--vogue-ink);">{{ session('error') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="vogue-card reveal" style="border-color: rgba(244, 63, 94, 0.45);">
                <p style="color: var(--vogue-heading); font-weight: 700;">表單有需要修正的地方</p>
                <ul class="mt-2 list-disc pl-5" style="color: var(--vogue-ink-soft);">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-4 lg:grid-cols-3">
            <article class="vogue-card">
                <p class="vogue-label">主要操作</p>
                <h3>{{ $module['primaryAction'] }}</h3>
            </article>
            <article class="vogue-card">
                <p class="vogue-label">API 端點</p>
                <h3>{{ $module['api'] }}</h3>
            </article>
            <article class="vogue-card">
                <p class="vogue-label">串接狀態</p>
                <h3>{{ strtoupper($module['status']) }}</h3>
            </article>
        </div>

        @if ($module['slug'] === 'runway-video')
            <div class="grid gap-4 lg:grid-cols-2 mt-4">
                <article class="vogue-card">
                    <h3>建立伸展台影片 Storyboard</h3>
                    <p class="mt-1" style="color: var(--vogue-ink-soft);">
                        先建立可展示的分鏡與提示詞，之後接入 Veo、RunwayML 或其他影片服務時，可沿用同一份任務資料。
                    </p>

                    <form method="POST" action="{{ route('workspace.runway-video.store') }}" class="mt-4 space-y-4">
                        @csrf

                        <div>
                            <label class="vogue-label" for="clothing_id">選擇衣物</label>
                            <select id="clothing_id" name="clothing_id" class="vogue-input" required>
                                <option value="">請選擇一件衣物</option>
                                @foreach ($clothes as $clothing)
                                    <option value="{{ $clothing->id }}" @selected(old('clothing_id') == $clothing->id)>
                                        #{{ $clothing->id }} · {{ $clothing->name }} · {{ $clothing->category ?? '未分類' }} · {{ $clothing->color ?? '未填顏色' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="vogue-label" for="video_style">影片風格</label>
                            <input id="video_style" name="video_style" type="text" value="{{ old('video_style', 'vogue luxury runway') }}" class="vogue-input" placeholder="例如：vogue luxury runway" required>
                        </div>

                        <div>
                            <label class="vogue-label" for="camera_rhythm">鏡頭節奏</label>
                            <input id="camera_rhythm" name="camera_rhythm" type="text" value="{{ old('camera_rhythm', 'slow cinematic camera movement') }}" class="vogue-input" placeholder="例如：slow cinematic camera movement">
                        </div>

                        <div class="vogue-card" style="border-color: rgba(59, 130, 246, 0.45);">
                            <p style="color: var(--vogue-ink-soft);">
                                L1 目前建立分鏡與預覽狀態，真實影片生成服務可在正式 provider 設定後啟用。
                            </p>
                        </div>

                        <button type="submit" class="vogue-btn vogue-btn-solid">
                            建立伸展台 Storyboard
                        </button>
                    </form>
                </article>

                <article class="vogue-card">
                    <h3>伸展台影片任務紀錄</h3>

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
                                $preview = $result['preview'] ?? [];
                                $provider = $result['provider'] ?? [];
                                $generationStatus = $result['generation_status'] ?? null;
                                $videoPrompt = $result['video_prompt'] ?? ($result['prompt'] ?? null);
                            @endphp

                            <div class="vogue-card" style="padding: 0.9rem;">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p style="color: var(--vogue-heading); font-weight: 700;">{{ $job['id'] }}</p>
                                            @if ($loop->first)
                                                <span class="vogue-chip vogue-chip-success">最新任務</span>
                                            @endif
                                        </div>
                                        <p class="mt-1 text-sm" style="color: var(--vogue-ink-soft);">
                                            衣物編號：{{ $job['clothing_id'] ?? 'N/A' }} · {{ $job['created_at'] ?? '' }}
                                        </p>
                                    </div>

                                    <span class="vogue-chip {{ $chipClass }}">{{ strtoupper($job['status']) }}</span>
                                </div>

                                <p class="mt-2" style="color: var(--vogue-ink-soft);">模型狀態：{{ $job['mode'] ?? 'fallback' }}</p>

                                @if ($loop->first)
                                    <div class="vogue-card mt-3" style="border-color: rgba(34, 197, 94, 0.45); padding: 0.75rem;">
                                        <p style="color: var(--vogue-heading); font-weight: 700;">最新伸展台任務可人工驗收</p>
                                        <p style="color: var(--vogue-ink-soft);">
                                            請確認影片預覽狀態、比例 9:16、分鏡場景與影片提示詞皆正常顯示。
                                        </p>
                                    </div>
                                @endif

                                @if (!empty($job['request_id']))
                                    <p class="mt-1 text-sm" style="color: var(--vogue-ink-soft);">請求編號：{{ $job['request_id'] }}</p>
                                @endif

                                @if (!empty($generationStatus) || !empty($preview))
                                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                        <div>
                                            <p class="vogue-label">生成狀態</p>
                                            <p>{{ $generationStatus ?? 'unknown' }}</p>
                                        </div>
                                        <div>
                                            <p class="vogue-label">目標服務</p>
                                            <p>{{ $provider['target_provider'] ?? 'pending-provider' }}</p>
                                        </div>
                                    </div>

                                    <div class="mt-3 rounded-md border border-white/10 p-3">
                                        <p class="vogue-label">影片預覽</p>
                                        <p style="color: var(--vogue-heading); font-weight: 700;">
                                            {{ $preview['label'] ?? '模擬伸展台影片預覽' }}
                                        </p>
                                        <p class="mt-1 text-sm" style="color: var(--vogue-ink-soft);">
                                            狀態：{{ $preview['status'] ?? 'placeholder' }}
                                            · 秒數：{{ $preview['duration_seconds'] ?? '?' }}s
                                            · 比例：{{ $preview['aspect_ratio'] ?? '9:16' }}
                                        </p>
                                    </div>
                                @endif

                                @if (!empty($clothing))
                                    <div class="mt-3">
                                        <p class="vogue-label">伸展台單品</p>
                                        <p style="color: var(--vogue-heading); font-weight: 700;">{{ $clothing['name'] ?? 'Unknown Item' }}</p>
                                        <p style="color: var(--vogue-ink-soft);">{{ $clothing['category'] ?? '未分類' }} · {{ $clothing['color'] ?? '未填顏色' }}</p>
                                    </div>
                                @endif

                                @if (!empty($videoPrompt))
                                    <div class="mt-3">
                                        <p class="vogue-label">影片提示詞</p>
                                        <p style="color: var(--vogue-ink-soft);">{{ $videoPrompt }}</p>
                                    </div>
                                @endif

                                @if (!empty($result['prompt']))
                                    <div class="mt-3">
                                        <p class="vogue-label">提示詞</p>
                                        <p style="color: var(--vogue-ink-soft);">{{ $result['prompt'] }}</p>
                                    </div>
                                @endif

                                @if (!empty($scenes))
                                    <div class="mt-3 space-y-2">
                                        <p class="vogue-label">分鏡場景</p>

                                        @foreach ($scenes as $scene)
                                            <div class="vogue-card" style="padding: 0.75rem;">
                                                <p style="color: var(--vogue-heading); font-weight: 700;">
                                                    場景 {{ $scene['scene'] ?? '?' }} · {{ $scene['title'] ?? '未命名' }}
                                                </p>
                                                <p class="mt-1" style="color: var(--vogue-ink-soft);">{{ $scene['description'] ?? '' }}</p>
                                                <p class="mt-1 text-sm" style="color: var(--vogue-ink-soft);">
                                                    鏡頭：{{ $scene['camera'] ?? 'N/A' }} · 秒數：{{ $scene['duration_seconds'] ?? '?' }}s
                                                </p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                @if (!empty($result['message']))
                                    <div class="vogue-card mt-3" style="border-color: rgba(59, 130, 246, 0.45);">
                                        <p style="color: var(--vogue-ink-soft);">{{ $result['message'] }}</p>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="vogue-card" style="padding: 0.9rem;">
                                <p style="color: var(--vogue-ink-soft);">尚未建立伸展台影片任務。</p>
                            </div>
                        @endforelse
                    </div>
                </article>
            </div>
        @elseif ($module['slug'] === 'digital-twin')
            <div class="grid gap-4 lg:grid-cols-2 mt-4">
                <article class="vogue-card">
                    <h3>建立數位分身資料</h3>
                    <p class="mt-1" style="color: var(--vogue-ink-soft);">
                        先建立 L1 個人風格卡，再用 L2 衣櫥分析補強 AI 穿搭顧問與後續試穿流程。
                    </p>

                    <form method="POST" action="{{ route('workspace.digital-twin.store') }}" class="mt-4 space-y-4">
                        @csrf

                        <div>
                            <label class="vogue-label" for="height_cm">身高（cm）</label>
                            <input id="height_cm" name="height_cm" type="number" min="100" max="230" value="{{ old('height_cm', 170) }}" class="vogue-input" required>
                        </div>

                        <div>
                            <label class="vogue-label" for="style_preference">風格偏好</label>
                            <input id="style_preference" name="style_preference" type="text" value="{{ old('style_preference', '簡約乾淨') }}" class="vogue-input" placeholder="例如：簡約、俐落、低調奢華" required>
                        </div>

                        <div>
                            <label class="vogue-label" for="common_occasion">常見場合</label>
                            <input id="common_occasion" name="common_occasion" type="text" value="{{ old('common_occasion', '日常通勤') }}" class="vogue-input" placeholder="例如：上班、聚會、晚餐、旅行" required>
                        </div>

                        <div>
                            <label class="vogue-label" for="body_note">身形備註</label>
                            <textarea id="body_note" name="body_note" rows="4" class="vogue-textarea" placeholder="可補充比例、剪裁偏好或想修飾的地方">{{ old('body_note') }}</textarea>
                        </div>

                        <button type="submit" class="vogue-btn vogue-btn-solid">
                            建立數位分身資料
                        </button>
                    </form>

                    <div class="vogue-card mt-4" style="border-color: rgba(34, 197, 94, 0.45);">
                        <h3>數位分身 L2 衣櫥分析</h3>
                        <p class="mt-1" style="color: var(--vogue-ink-soft);">
                            根據我的衣櫥統計主要品類、顏色、場合與風格標籤，建立 closet-based profile。
                        </p>

                        <form method="POST" action="{{ route('workspace.digital-twin.analyze-closet') }}" class="mt-4">
                            @csrf
                            <button type="submit" class="vogue-btn vogue-btn-solid">
                                用我的衣櫥分析數位分身 L2
                            </button>
                        </form>
                    </div>
                </article>

                <article class="vogue-card">
                    <h3>數位分身紀錄</h3>

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
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p style="color: var(--vogue-heading); font-weight: 700;">{{ $job['id'] }}</p>
                                            @if ($loop->first)
                                                <span class="vogue-chip vogue-chip-success">最新任務</span>
                                            @endif
                                        </div>
                                        <p class="mt-1 text-sm" style="color: var(--vogue-ink-soft);">{{ $job['created_at'] ?? '' }}</p>
                                    </div>

                                    <span class="vogue-chip {{ $chipClass }}">{{ strtoupper($job['status']) }}</span>
                                </div>

                                <p class="mt-2" style="color: var(--vogue-ink-soft);">模型狀態：{{ $job['mode'] ?? 'fallback' }}</p>

                                @if ($loop->first)
                                    <div class="vogue-card mt-3" style="border-color: rgba(34, 197, 94, 0.45); padding: 0.75rem;">
                                        <p style="color: var(--vogue-heading); font-weight: 700;">最新數位分身任務可人工驗收</p>
                                        <p style="color: var(--vogue-ink-soft);">
                                            請確認分身預覽佔位、風格摘要、風格標籤與 L2 衣櫥統計資料可正常閱讀。
                                        </p>
                                    </div>
                                @endif

                                @if (!empty($job['request_id']))
                                    <p class="mt-1 text-sm" style="color: var(--vogue-ink-soft);">請求編號：{{ $job['request_id'] }}</p>
                                @endif

                                <div class="vogue-card mt-3" style="border-color: rgba(168, 85, 247, 0.45);">
                                    <p class="vogue-label">分身預覽佔位</p>
                                    <p style="color: var(--vogue-heading); font-weight: 700;">{{ $result['avatar']['label'] ?? 'VogueAI 數位分身' }}</p>
                                    <p style="color: var(--vogue-ink-soft);">目前以資料卡展示，後續可接 3D Avatar 或生成模型。</p>
                                </div>

                                @if (!empty($profile))
                                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                        @foreach ($profile as $key => $value)
                                            <div>
                                                <p class="vogue-label">{{ str_replace('_', ' ', (string) $key) }}</p>
                                                <p>{{ is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : ($value ?? 'N/A') }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                @if (!empty($styleSummary))
                                    <div class="mt-3">
                                        <p class="vogue-label">風格摘要</p>
                                        <p style="color: var(--vogue-heading); font-weight: 700;">{{ $styleSummary['headline'] ?? '' }}</p>
                                        <p class="mt-1" style="color: var(--vogue-ink-soft);">{{ $styleSummary['description'] ?? '' }}</p>
                                    </div>
                                @endif

                                @if (!empty($closetStatistics))
                                    <div class="mt-3">
                                        <p class="vogue-label">衣櫥統計</p>
                                        <div class="mt-2 grid gap-3 sm:grid-cols-2">
                                            @foreach ([
                                                'top_categories' => '主要品類',
                                                'top_colors' => '主要顏色',
                                                'top_seasons' => '主要季節',
                                                'top_occasions' => '主要場合',
                                                'top_style_tags' => '主要風格標籤',
                                            ] as $key => $label)
                                                @if (!empty($closetStatistics[$key]))
                                                    <div class="vogue-card" style="padding: 0.75rem;">
                                                        <p class="vogue-label">{{ $label }}</p>
                                                        <div class="mt-2 flex flex-wrap gap-2">
                                                            @foreach ($closetStatistics[$key] as $stat)
                                                                <span class="vogue-chip">{{ $stat['label'] }} × {{ $stat['count'] }}</span>
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
                                            <span class="vogue-chip">{{ $tag }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                @if (!empty($result['message']))
                                    <div class="vogue-card mt-3" style="border-color: rgba(59, 130, 246, 0.45);">
                                        <p style="color: var(--vogue-ink-soft);">{{ $result['message'] }}</p>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="vogue-card" style="padding: 0.9rem;">
                                <p style="color: var(--vogue-ink-soft);">尚未建立數位分身資料。</p>
                            </div>
                        @endforelse
                    </div>
                </article>
            </div>
        @else
            <div class="grid gap-4 lg:grid-cols-2 mt-4">
                <article class="vogue-card">
                    <h3>輸入區</h3>
                    <div class="mt-3 space-y-3">
                        @foreach ($module['fields'] as $field)
                            <div>
                                <label class="vogue-label">{{ $field }}</label>
                                <input type="text" class="vogue-input" placeholder="{{ $field }}" disabled>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="vogue-btn vogue-btn-solid mt-4" disabled>{{ $module['primaryAction'] }}</button>
                </article>

                <article class="vogue-card">
                    <h3>任務狀態</h3>
                    <div class="mt-3 space-y-3">
                        <div class="vogue-card" style="padding: 0.85rem;">
                            <p class="vogue-label">JOB-2401</p>
                            <p style="color: var(--vogue-heading);">狀態：{{ $module['status'] }}</p>
                            <p style="color: var(--vogue-ink-soft);">模組：{{ $module['slug'] }}</p>
                        </div>
                        <div class="vogue-card" style="padding: 0.85rem;">
                            <p class="vogue-label">JOB-2402</p>
                            <p style="color: var(--vogue-heading);">狀態：pending</p>
                            <p style="color: var(--vogue-ink-soft);">模組：{{ $module['slug'] }}</p>
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
