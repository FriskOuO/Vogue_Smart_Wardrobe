<x-vogue-page title="VogueAI | AI 穿搭顧問" skeleton-id="vogue-closet-stylist-skeleton">
    <section class="vogue-highlight reveal mt-6">
        <div>
            <p class="vogue-eyebrow">AI 穿搭顧問</p>
            <h2>依照場合產生穿搭建議</h2>
            <p>
                輸入場合、天氣、正式程度與風格偏好，系統會根據衣櫥資料產生可保存、可回饋的穿搭紀錄。
                要測 Gemini，請在下方表單把生成模式切到「真實模型」後送出。
            </p>
        </div>
        <div class="vogue-closet-toolbar">
            <span class="vogue-chip vogue-chip-success">Gemini 可人工測試</span>
            <a href="#stylist-form" class="vogue-btn vogue-btn-solid">開始產生</a>
            <a href="{{ route('closet.hub') }}" class="vogue-btn vogue-btn-soft">回總覽</a>
            <a href="{{ route('closet.index') }}" class="vogue-btn vogue-btn-soft">我的衣櫥</a>
        </div>
    </section>

    @if (session('status'))
        <section class="vogue-section reveal">
            <div class="vogue-card" style="border-color: rgba(34, 197, 94, 0.45);">
                <p>{{ session('status') }}</p>
            </div>
        </section>
    @endif

    @if (session('error'))
        <section class="vogue-section reveal">
            <div class="vogue-card" style="border-color: rgba(239, 68, 68, 0.45);">
                <p>{{ session('error') }}</p>
            </div>
        </section>
    @endif

    @if ($errors->any())
        <section class="vogue-section reveal">
            <div class="vogue-card" style="border-color: rgba(239, 68, 68, 0.45);">
                <p style="font-weight: 700;">表單有需要修正的地方</p>
                <ul class="mt-2 list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    <section id="stylist-form" class="vogue-section vogue-critical-flow">
        <div class="grid gap-4 lg:grid-cols-2">
            <article class="vogue-card space-y-4">
                <div>
                    <h3>建立穿搭需求</h3>
                    <p class="mt-1" style="color: var(--vogue-ink-soft);">
                        目前衣櫥有 {{ $clothes->count() }} 件衣物。選「真實模型」送出後，最新結果會出現在右側推薦紀錄最上方。
                    </p>
                </div>

                @if (!empty($latestDigitalTwinProfile))
                    <div class="vogue-card" style="border-color: rgba(34, 197, 94, 0.45);">
                        <p class="vogue-label">數位分身資料</p>
                        <p class="mt-1" style="color: var(--vogue-heading); font-weight: 700;">
                            {{ $latestDigitalTwinProfile['source_job_id'] ?? '數位分身 L2' }}
                        </p>
                        <p class="mt-1" style="color: var(--vogue-ink-soft);">
                            主要品類：{{ $latestDigitalTwinProfile['dominant_category'] ?? 'unknown' }}
                            · 主要顏色：{{ $latestDigitalTwinProfile['dominant_color'] ?? 'unknown' }}
                            · 主要風格：{{ $latestDigitalTwinProfile['dominant_style'] ?? 'unknown' }}
                        </p>
                    </div>
                @else
                    <div class="vogue-card" style="border-color: rgba(59, 130, 246, 0.45);">
                        <p class="vogue-label">數位分身資料</p>
                        <p style="color: var(--vogue-ink-soft);">
                            尚未建立數位分身 L2 衣櫥資料。可以先到數位分身工作區分析衣櫥，讓推薦更個人化。
                        </p>
                    </div>
                @endif

                <form method="POST" action="{{ route('closet.stylist.generate') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label class="vogue-label" for="occasion">場合</label>
                        <input id="occasion" name="occasion" type="text" class="vogue-input" value="{{ old('occasion', '日常通勤') }}" placeholder="例如：晚餐、上班、旅行、聚會" required>
                    </div>

                    <div>
                        <label class="vogue-label" for="weather">天氣</label>
                        <input id="weather" name="weather" type="text" class="vogue-input" value="{{ old('weather', '舒適 24°C') }}" placeholder="例如：涼爽傍晚、晴天 28°C、微雨">
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="vogue-label" for="season_context">季節</label>
                            <input id="season_context" name="season_context" type="text" class="vogue-input" value="{{ old('season_context', '春夏') }}" placeholder="例如：春夏、秋冬、換季">
                        </div>

                        <div>
                            <label class="vogue-label" for="formality_level">正式程度</label>
                            <input id="formality_level" name="formality_level" type="text" class="vogue-input" value="{{ old('formality_level', 'smart casual') }}" placeholder="例如：休閒、正式、smart casual">
                        </div>
                    </div>

                    <div>
                        <label class="vogue-label" for="mood_context">心情 / 氣質</label>
                        <input id="mood_context" name="mood_context" type="text" class="vogue-input" value="{{ old('mood_context', '乾淨俐落') }}" placeholder="例如：自信、溫柔、俐落、低調">
                    </div>

                    <div>
                        <label class="vogue-label" for="style_preference">風格偏好</label>
                        <textarea id="style_preference" name="style_preference" rows="4" class="vogue-textarea" placeholder="例如：極簡、低調奢華、想要顯瘦、不要太正式">{{ old('style_preference', '簡約乾淨') }}</textarea>
                    </div>

                    <div>
                        <label class="vogue-label" for="avoid_notes">避免事項</label>
                        <textarea id="avoid_notes" name="avoid_notes" rows="3" class="vogue-textarea" placeholder="例如：避免紅色、不要高跟鞋、不要太厚重">{{ old('avoid_notes') }}</textarea>
                    </div>

                    <div>
                        <label class="vogue-label" for="provider_mode">生成模式</label>
                        <select id="provider_mode" name="provider_mode" class="vogue-input">
                            <option value="demo" @selected(old('provider_mode', 'demo') === 'demo')>安全備援</option>
                            <option value="real" @selected(old('provider_mode') === 'real')>真實模型</option>
                        </select>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="vogue-btn vogue-btn-solid">
                            產生 AI 穿搭建議
                        </button>
                        <a href="{{ route('closet.create') }}" class="vogue-btn vogue-btn-soft">
                            新增衣物
                        </a>
                        <a href="{{ route('closet.search') }}" class="vogue-btn vogue-btn-soft">
                            前往 AI 搜尋
                        </a>
                    </div>
                </form>
            </article>

            <article class="vogue-card">
                <h3>推薦紀錄</h3>
                <p class="mt-1" style="color: var(--vogue-ink-soft);">
                    推薦結果會保存在資料庫，也能送出喜歡 / 不適合的回饋，或保存成穿搭紀錄。
                </p>

                <div class="mt-4 space-y-3">
                    @forelse ($stylistHistories as $history)
                        @php
                            $recommendation = $history['recommendation'] ?? [];
                            $items = $history['selected_items'] ?? [];

                            $chipClass = [
                                'ready' => 'vogue-chip-success',
                                'success' => 'vogue-chip-success',
                                'degraded' => 'vogue-chip-degraded',
                                'pending' => 'vogue-chip-pending',
                                'failed' => 'vogue-chip-pending',
                            ][$history['status']] ?? 'vogue-chip-degraded';
                        @endphp

                        <div class="vogue-card" style="padding: 0.9rem;">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p style="color: var(--vogue-heading); font-weight: 700;">
                                        {{ $recommendation['title'] ?? 'AI 穿搭建議' }}
                                    </p>
                                    <p class="mt-1 text-sm" style="color: var(--vogue-ink-soft);">
                                        {{ $history['id'] }} · {{ $history['created_at'] }}
                                    </p>
                                </div>

                                <span class="vogue-chip {{ $chipClass }}">
                                    {{ $history['mode'] }} / {{ $history['status'] }}
                                </span>
                            </div>

                            <div class="mt-3 grid gap-2 sm:grid-cols-3">
                                <div>
                                    <p class="vogue-label">場合</p>
                                    <p>{{ $history['occasion'] ?? '未提供' }}</p>
                                </div>
                                <div>
                                    <p class="vogue-label">天氣</p>
                                    <p>{{ $history['weather'] ?? '未提供' }}</p>
                                </div>
                                <div>
                                    <p class="vogue-label">風格</p>
                                    <p>{{ $history['style_preference'] ?? '未提供' }}</p>
                                </div>
                            </div>

                            @if (!empty($history['context']))
                                @php($context = $history['context'])
                                <div class="mt-3 grid gap-2 sm:grid-cols-4">
                                    <div>
                                        <p class="vogue-label">季節</p>
                                        <p>{{ $context['season_context'] ?? '未提供' }}</p>
                                    </div>
                                    <div>
                                        <p class="vogue-label">正式程度</p>
                                        <p>{{ $context['formality_level'] ?? '未提供' }}</p>
                                    </div>
                                    <div>
                                        <p class="vogue-label">心情</p>
                                        <p>{{ $context['mood_context'] ?? '未提供' }}</p>
                                    </div>
                                    <div>
                                        <p class="vogue-label">避免事項</p>
                                        <p>{{ $context['avoid_notes'] ?? '未提供' }}</p>
                                    </div>
                                </div>
                            @endif

                            <div class="mt-3 rounded-md border border-white/10 p-3">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p class="vogue-label">回饋</p>
                                        <p style="color: var(--vogue-ink-soft);">
                                            {{ $history['feedback_status'] ?? '尚未回饋' }}
                                            @if (!empty($history['feedback_submitted_at']))
                                                · {{ $history['feedback_submitted_at'] }}
                                            @endif
                                        </p>
                                    </div>

                                    <form method="POST" action="{{ route('closet.stylist.feedback', $history['database_id']) }}">
                                        @csrf
                                        <input type="hidden" name="feedback_status" value="liked">
                                        <button type="submit" class="vogue-btn vogue-btn-soft">
                                            喜歡這套
                                        </button>
                                    </form>
                                </div>

                                @if (!empty($history['feedback_reason']))
                                    <p class="mt-2 text-sm" style="color: var(--vogue-ink-soft);">
                                        {{ $history['feedback_reason'] }}
                                    </p>
                                @endif

                                <form method="POST" action="{{ route('closet.stylist.feedback', $history['database_id']) }}" class="mt-3 space-y-2">
                                    @csrf
                                    <input type="hidden" name="feedback_status" value="rejected">
                                    <textarea name="feedback_reason" rows="2" class="vogue-textarea" placeholder="可以說明不適合的原因，之後推薦會參考">{{ old('feedback_reason') }}</textarea>
                                    <button type="submit" class="vogue-btn vogue-btn-soft">
                                        標記不適合
                                    </button>
                                </form>
                            </div>

                            <div class="mt-3 rounded-md border border-white/10 p-3">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="vogue-label">穿搭紀錄</p>
                                        <p style="color: var(--vogue-ink-soft);">
                                            {{ $history['outfit_logs_count'] ?? 0 }} 筆已保存穿搭
                                        </p>
                                    </div>
                                    <span class="vogue-chip vogue-chip-degraded">ai_stylist</span>
                                </div>

                                <form method="POST" action="{{ route('closet.stylist.outfit-log', $history['database_id']) }}" class="mt-3 space-y-2">
                                    @csrf
                                    <div class="grid gap-2 sm:grid-cols-2">
                                        <div>
                                            <label class="vogue-label" for="outfit_name_{{ $history['database_id'] }}">名稱</label>
                                            <input id="outfit_name_{{ $history['database_id'] }}" name="name" type="text" class="vogue-input" value="{{ old('name', $recommendation['title'] ?? 'AI 穿搭') }}">
                                        </div>
                                        <div>
                                            <label class="vogue-label" for="logged_at_{{ $history['database_id'] }}">保存時間</label>
                                            <input id="logged_at_{{ $history['database_id'] }}" name="logged_at" type="datetime-local" class="vogue-input" value="{{ old('logged_at', now()->format('Y-m-d\TH:i')) }}">
                                        </div>
                                    </div>
                                    <textarea name="notes" rows="2" class="vogue-textarea" placeholder="可補充未來推薦要參考的穿搭細節">{{ old('notes') }}</textarea>
                                    <button type="submit" class="vogue-btn vogue-btn-soft">
                                        保存穿搭紀錄
                                    </button>
                                </form>
                            </div>

                            @if (!empty($recommendation['summary']))
                                <p class="mt-3" style="color: var(--vogue-ink-soft);">
                                    {{ $recommendation['summary'] }}
                                </p>
                            @endif

                            @if (!empty($recommendation['digital_twin_profile']))
                                @php($profile = $recommendation['digital_twin_profile'])
                                <div class="vogue-card mt-3" style="border-color: rgba(34, 197, 94, 0.45); padding: 0.75rem;">
                                    <p class="vogue-label">已使用的數位分身資料</p>
                                    <p style="color: var(--vogue-heading); font-weight: 700;">
                                        {{ $profile['source_job_id'] ?? '數位分身 L2' }}
                                    </p>
                                    <p class="mt-1 text-sm" style="color: var(--vogue-ink-soft);">
                                        品類：{{ $profile['dominant_category'] ?? 'unknown' }}
                                        · 顏色：{{ $profile['dominant_color'] ?? 'unknown' }}
                                        · 風格：{{ $profile['dominant_style'] ?? 'unknown' }}
                                    </p>
                                </div>
                            @endif

                            @if (!empty($recommendation['text_generation']))
                                @php($textGeneration = $recommendation['text_generation'])
                                @php($textGenerationStatus = $textGeneration['status'] ?? 'planned')
                                @php($textGenerationMode = $textGeneration['mode'] ?? 'fallback')
                                @php($textGenerationFallbackActive = (bool) ($textGeneration['fallback_active'] ?? true))
                                @php($textGenerationChipClass = ($textGenerationStatus === 'ready' && ! $textGenerationFallbackActive) ? 'vogue-chip-success' : (($textGenerationMode === 'real_adapter_attempt') ? 'vogue-chip-pending' : 'vogue-chip-degraded'))
                                <div class="mt-3 rounded-md border border-white/10 p-3">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="vogue-label">Gemini 文字轉接器</p>
                                        <span class="vogue-chip {{ $textGenerationChipClass }}">
                                            {{ $textGenerationMode }} / {{ $textGenerationStatus }}
                                        </span>
                                    </div>
                                    <p class="mt-2 text-sm" style="color: var(--vogue-ink-soft);">
                                        provider: {{ $textGeneration['provider'] ?? 'gemini' }}
                                        · adapter: {{ $textGeneration['adapter'] ?? 'gemini-stylist-text-v1' }}
                                        · status: {{ $textGenerationStatus }}
                                    </p>
                                    <p class="mt-1 text-sm" style="color: var(--vogue-ink-soft);">
                                        fallback: {{ $textGeneration['fallback'] ?? 'rule_based_text' }}
                                        @if (array_key_exists('fallback_active', $textGeneration))
                                            · fallback_active: {{ $textGeneration['fallback_active'] ? 'true' : 'false' }}
                                        @endif
                                    </p>
                                    @if (!empty($textGeneration['error_code']))
                                        <p class="mt-1 text-sm" style="color: var(--vogue-ink-soft);">
                                            error: {{ $textGeneration['error_code'] }}
                                        </p>
                                    @endif
                                    @if (!empty($textGeneration['endpoint']))
                                        <p class="mt-1 text-sm" style="color: var(--vogue-ink-soft);">
                                            endpoint: {{ $textGeneration['endpoint'] }}
                                        </p>
                                    @endif
                                    @if (!empty($textGeneration['reasoning_notes']))
                                        <p class="mt-1 text-sm" style="color: var(--vogue-ink-soft);">
                                            notes: {{ collect($textGeneration['reasoning_notes'])->implode(' / ') }}
                                        </p>
                                    @endif
                                </div>
                            @endif

                            @if (!empty($recommendation['embedding_signals']['top_matches']))
                                @php($embeddingSignals = $recommendation['embedding_signals'])
                                <div class="mt-3 rounded-md border border-white/10 p-3">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="vogue-label">向量訊號</p>
                                        <span class="vogue-chip vogue-chip-degraded">
                                            {{ $embeddingSignals['mode'] ?? 'local_cosine' }}
                                        </span>
                                    </div>
                                    <div class="mt-2 space-y-1 text-sm" style="color: var(--vogue-ink-soft);">
                                        @foreach ($embeddingSignals['top_matches'] as $match)
                                            <p>
                                                {{ $match['name'] ?? 'Unknown item' }}
                                                · score {{ number_format((float) ($match['score'] ?? 0), 3) }}
                                            </p>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if (!empty($items))
                                <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                    @foreach ($items as $item)
                                        <div class="vogue-card" style="padding: 0.75rem;">
                                            @if (!empty($item['image_url']))
                                                <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] ?? '衣物' }}" class="vogue-clothing-fit-image rounded-xl" style="height: 150px;">
                                            @endif

                                            <p class="mt-2" style="color: var(--vogue-heading); font-weight: 700;">
                                                {{ $item['name'] ?? '未命名衣物' }}
                                            </p>
                                            <p class="mt-1 text-sm" style="color: var(--vogue-ink-soft);">
                                                {{ $item['category'] ?? '未分類' }} · {{ $item['color'] ?? '未填顏色' }}
                                            </p>

                                            @if (isset($item['embedding_score']))
                                                <p class="mt-1 text-sm" style="color: var(--vogue-ink-soft);">
                                                    向量分數：{{ number_format((float) $item['embedding_score'], 3) }}
                                                </p>
                                            @endif

                                            @if (!empty($item['id']))
                                                <a href="{{ route('closet.show', $item['id']) }}" class="vogue-btn vogue-btn-soft mt-3">
                                                    查看衣物
                                                </a>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if (!empty($recommendation['reasoning']))
                                <div class="mt-3">
                                    <p class="vogue-label">推薦理由</p>
                                    <ul class="mt-2 list-disc pl-5" style="color: var(--vogue-ink-soft);">
                                        @foreach ($recommendation['reasoning'] as $reason)
                                            <li>{{ $reason }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if (!empty($recommendation['styling_tips']))
                                <div class="mt-3">
                                    <p class="vogue-label">搭配建議</p>
                                    <ul class="mt-2 list-disc pl-5" style="color: var(--vogue-ink-soft);">
                                        @foreach ($recommendation['styling_tips'] as $tip)
                                            <li>{{ $tip }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="vogue-card" style="padding: 0.9rem;">
                            <p style="color: var(--vogue-ink-soft);">
                                尚未有推薦紀錄。先填入場合與風格偏好，就能產生第一筆 AI 穿搭建議。
                            </p>
                        </div>
                    @endforelse
                </div>
            </article>
        </div>
    </section>
</x-vogue-page>
