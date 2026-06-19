<x-vogue-page title="VogueAI | 試穿 / 姿態" skeleton-id="vogue-closet-tryon-skeleton">
    <section class="vogue-highlight reveal mt-6">
        <div>
            <p class="vogue-eyebrow">試穿 / 姿態</p>
            <h2>虛擬試穿</h2>
            <p>選擇衣物並上傳人物照片。系統會先檢查照片品質，再建立真實試穿任務。</p>
        </div>
        <div class="vogue-closet-toolbar">
            <span class="vogue-chip vogue-chip-degraded">照片檢查與真實試穿</span>
            <a href="#tryon-form" class="vogue-btn vogue-btn-solid">建立新任務</a>
            <a href="{{ route('closet.hub') }}" class="vogue-btn vogue-btn-soft">回總覽</a>
        </div>
    </section>

    <section id="tryon-form" class="vogue-section vogue-critical-flow">
        @if (session('status'))
            <div class="vogue-card reveal" style="border-color: rgba(16, 185, 129, 0.45);">
                <p style="color: var(--vogue-ink);">{{ session('status') }}</p>
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

        <div class="grid gap-4">
            <article class="vogue-card space-y-4">
                <h3>建立試穿任務</h3>
                <p style="color: var(--vogue-ink-soft);">
                    送出後，新任務會出現在下方紀錄最上方。建立完成後按一次「查詢試穿結果」，即可等待 Hugging Face 回傳圖片。
                </p>

                <form method="POST" action="{{ route('closet.tryon.store') }}" enctype="multipart/form-data" class="space-y-4">
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
                            照片請正面全身入鏡、肩膀與髖部清楚，手臂稍微離開身體。
                        </p>
                    </div>

                    <button type="submit" class="vogue-btn vogue-btn-solid">
                        建立試穿任務
                    </button>
                </form>
            </article>

            <article class="vogue-card">
                <h3>試穿任務紀錄</h3>

                <div class="mt-4 space-y-3">
                    @forelse ($poseJobs as $job)
                        @php
                            $poseAnalysis = $job['result']['pose_analysis'] ?? null;
                            $keypoints = $job['result']['keypoints'] ?? [];
                            $poseQualityScore = $job['result']['pose_quality_score'] ?? ($poseAnalysis['pose_quality_score'] ?? null);
                            $poseQualityStatus = $job['result']['pose_quality_status'] ?? ($poseAnalysis['pose_quality_status'] ?? 'unknown');
                            $qualityChecks = $job['result']['quality_checks'] ?? [];
                            $improvementTips = $poseAnalysis['improvement_tips'] ?? [];
                            $tryOnAttempt = $job['result']['tryon_provider_attempt'] ?? [];
                            $tryOnLatest = $tryOnAttempt['latest_status'] ?? ($job['result']['tryon_provider_status'] ?? $tryOnAttempt);
                            $tryOnTaskId = $tryOnLatest['provider_task_id'] ?? ($tryOnAttempt['provider_task_id'] ?? ($tryOnAttempt['provider_job_id'] ?? null));
                            $tryOnStatus = $tryOnLatest['status'] ?? ($tryOnAttempt['status'] ?? null);
                            $tryOnOutputUrl = $job['result']['tryon_output_url'] ?? ($tryOnLatest['output_url'] ?? ($tryOnAttempt['output_url'] ?? null));
                            $displayStatus = $tryOnStatus ?: $job['status'];
                            $statusLabel = [
                                'success' => '已完成',
                                'degraded' => '降級完成',
                                'pending' => '等待中',
                                'processing' => '處理中',
                                'failed' => '失敗',
                            ][$displayStatus] ?? strtoupper((string) $displayStatus);
                            $chipClass = [
                                'success' => 'vogue-chip-success',
                                'degraded' => 'vogue-chip-degraded',
                                'pending' => 'vogue-chip-pending',
                                'processing' => 'vogue-chip-pending',
                                'failed' => 'vogue-chip-pending',
                            ][$displayStatus] ?? 'vogue-chip-pending';
                            $qualityLabels = [
                                'full_body_visible' => '全身完整入鏡',
                                'shoulders_detected' => '肩膀辨識',
                                'hips_detected' => '髖部辨識',
                                'keypoint_confidence' => '姿態辨識信心度',
                            ];
                            $qualityMessages = [
                                'full_body_visible' => '全身構圖符合試穿需求。',
                                'shoulders_detected' => '左右肩膀皆已辨識。',
                                'hips_detected' => '左右髖部皆已辨識。',
                                'keypoint_confidence' => '姿態關鍵點的信心度足夠。',
                            ];
                            $tipTranslations = [
                                'Use a straight full-body photo with both shoulders and hips visible.' => '請使用正面全身照片，確保雙肩與髖部清楚可見。',
                                'Keep arms slightly away from the torso for cleaner garment fitting.' => '手臂請稍微離開身體，讓衣物貼合效果更乾淨。',
                            ];
                        @endphp

                        <div class="vogue-card" style="padding: 0.9rem;">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p style="color: var(--vogue-heading); font-weight: 700;">
                                            {{ $job['id'] }}
                                        </p>
                                        @if ($loop->first)
                                            <span class="vogue-chip vogue-chip-success">最新任務</span>
                                        @elseif (!empty($job['error_code']))
                                            <span class="vogue-chip vogue-chip-pending">歷史失敗</span>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-sm" style="color: var(--vogue-ink-soft);">
                                        衣物編號：{{ $job['clothing_id'] ?? 'N/A' }} · {{ $job['created_at'] ?? '' }}
                                    </p>
                                </div>

                                <span class="vogue-chip {{ $chipClass }}">
                                    {{ $statusLabel }}
                                </span>
                            </div>

                            <p class="mt-2" style="color: var(--vogue-ink-soft);">
                                姿態檢查：{{ ($job['mode'] ?? null) === 'mock' ? '展示模式' : '正式模式' }}
                            </p>

                            @if (!empty($tryOnTaskId) || !empty($tryOnStatus) || !empty($tryOnOutputUrl))
                                <div class="vogue-card mt-3" style="border-color: rgba(59, 130, 246, 0.45); padding: 0.75rem;">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <p class="vogue-label">真實試穿任務</p>
                                            <p style="color: var(--vogue-heading); font-weight: 700;">
                                                {{ strtoupper((string) ($tryOnStatus ?? 'unknown')) }}
                                            </p>
                                            @if (!empty($tryOnTaskId))
                                                <p class="mt-1 text-sm" style="color: var(--vogue-ink-soft);">
                                                    Provider Task：{{ $tryOnTaskId }}
                                                </p>
                                            @endif
                                        </div>

                                        @if (!empty($tryOnTaskId) && !empty($job['database_id']))
                                            <form
                                                method="POST"
                                                action="{{ route('ai-jobs.tryon-status', $job['database_id']) }}"
                                                @if ($loop->first && $tryOnStatus === 'processing') data-tryon-auto-poll @endif
                                            >
                                                @csrf
                                                <button type="submit" class="vogue-btn vogue-btn-soft">
                                                    {{ $tryOnStatus === 'processing' ? '手動更新' : '再次查詢結果' }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>

                                    @if (!empty($tryOnOutputUrl))
                                        <div class="mt-3">
                                            <img src="{{ $tryOnOutputUrl }}" alt="試穿結果" style="width: 100%; max-height: 520px; object-fit: contain; border-radius: 8px; border: 1px solid rgba(148, 163, 184, 0.35); background: rgba(255,255,255,0.65);">
                                        </div>
                                    @elseif (!empty($tryOnTaskId) && $tryOnStatus === 'processing')
                                        <p class="mt-3 text-sm" style="color: var(--vogue-ink-soft);">
                                            系統正在背景生成試穿圖片，完成後會自動更新並顯示。Hugging Face 免費服務可能需要排隊。
                                        </p>
                                    @endif
                                </div>
                            @endif

                            @if ($loop->first)
                                <div class="vogue-card mt-3" style="border-color: {{ !empty($job['error_code']) ? 'rgba(244, 63, 94, 0.45)' : 'rgba(34, 197, 94, 0.45)' }}; padding: 0.75rem;">
                                    @if (!empty($job['error_code']))
                                        <p style="color: var(--vogue-heading); font-weight: 700;">最新任務失敗</p>
                                        <p style="color: var(--vogue-ink-soft);">
                                            {{ $job['error_message'] ?: '試穿服務未完成此任務，請查看下方錯誤原因。' }}
                                        </p>
                                    @else
                                        <p style="color: var(--vogue-heading); font-weight: 700;">最新任務可人工驗收</p>
                                        <p style="color: var(--vogue-ink-soft);">
                                            姿態品質 {{ $poseQualityStatus }}，請確認下方分數、品質檢查與改善建議是否正常顯示。
                                        </p>
                                    @endif
                                </div>
                            @endif

                            @if ($loop->first && !empty($job['request_id']))
                                <p class="mt-1 text-sm" style="color: var(--vogue-ink-soft);">
                                    請求編號：{{ $job['request_id'] }}
                                </p>
                            @endif

                            @if ($loop->first && $poseAnalysis)
                                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                    <div>
                                        <p class="vogue-label">姿態品質</p>
                                        <p>
                                            @if ($poseQualityScore !== null)
                                                {{ number_format((float) $poseQualityScore * 100) }}%
                                            @else
                                                N/A
                                            @endif
                                        </p>
                                    </div>
                                    <div>
                                        <p class="vogue-label">品質狀態</p>
                                        <p>{{ $poseQualityStatus }}</p>
                                    </div>
                                    <div>
                                        <p class="vogue-label">全身入鏡</p>
                                        <p>{{ ($poseAnalysis['full_body_visible'] ?? false) ? '是' : '否' }}</p>
                                    </div>
                                    <div>
                                        <p class="vogue-label">肩線平衡</p>
                                        <p>{{ $poseAnalysis['shoulder_balance'] ?? 'unknown' }}</p>
                                    </div>
                                </div>

                                @if (!empty($qualityChecks))
                                    <div class="mt-3">
                                        <p class="vogue-label">品質檢查</p>
                                        <div class="mt-2 space-y-2">
                                            @foreach ($qualityChecks as $checkName => $check)
                                                <div class="flex items-start justify-between gap-3 rounded-md border border-white/10 p-2">
                                                    <div>
                                                        <p style="color: var(--vogue-heading); font-weight: 700;">
                                                            {{ $qualityLabels[$checkName] ?? str_replace('_', ' ', (string) $checkName) }}
                                                        </p>
                                                        <p class="text-sm" style="color: var(--vogue-ink-soft);">
                                                            {{ $qualityMessages[$checkName] ?? ($check['message'] ?? '') }}
                                                        </p>
                                                    </div>
                                                    <span class="vogue-chip {{ ($check['passed'] ?? false) ? 'vogue-chip-success' : 'vogue-chip-pending' }}">
                                                        {{ ($check['passed'] ?? false) ? '通過' : '待確認' }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if (!empty($poseAnalysis['posture_notes']))
                                    <div class="mt-3">
                                        <p class="vogue-label">姿態備註</p>
                                        <ul class="mt-1 list-disc pl-5" style="color: var(--vogue-ink-soft);">
                                            @foreach ($poseAnalysis['posture_notes'] as $note)
                                                <li>{{ $note }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if (!empty($poseAnalysis['fit_notes']))
                                    <div class="mt-3">
                                        <p class="vogue-label">合身備註</p>
                                        <ul class="mt-1 list-disc pl-5" style="color: var(--vogue-ink-soft);">
                                            @foreach ($poseAnalysis['fit_notes'] as $note)
                                                <li>{{ $note }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if (!empty($improvementTips))
                                    <div class="mt-3">
                                        <p class="vogue-label">改善建議</p>
                                        <ul class="mt-1 list-disc pl-5" style="color: var(--vogue-ink-soft);">
                                            @foreach ($improvementTips as $tip)
                                                <li>{{ $tipTranslations[$tip] ?? $tip }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            @endif

                            @if ($loop->first && !empty($keypoints))
                                <div class="mt-3">
                                    <p class="vogue-label">關鍵點</p>
                                    <p style="color: var(--vogue-ink-soft);">
                                        共 {{ count($keypoints) }} 個姿態關鍵點
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
                                尚未建立試穿 / 姿態任務。選擇衣物並上傳人物照片即可開始。
                            </p>
                        </div>
                    @endforelse
                </div>
            </article>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('form[data-tryon-auto-poll]');

            if (!form) {
                return;
            }

            let attempts = 0;
            const maxAttempts = 90;

            const poll = async () => {
                attempts += 1;

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const payload = await response.json();

                    if (payload.status === 'processing' && attempts < maxAttempts) {
                        window.setTimeout(poll, 4000);
                        return;
                    }

                    window.location.reload();
                } catch (error) {
                    if (attempts < maxAttempts) {
                        window.setTimeout(poll, 8000);
                    }
                }
            };

            window.setTimeout(poll, 3000);
        });
    </script>
</x-vogue-page>
