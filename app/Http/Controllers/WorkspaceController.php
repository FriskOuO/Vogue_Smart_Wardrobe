<?php

namespace App\Http\Controllers;

use App\Models\AiJob;
use App\Models\Clothing;
use App\Services\ExternalModelProviderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkspaceController extends Controller
{
    public function show(string $module): View
{
    $modules = $this->modules();

    abort_unless(isset($modules[$module]), 404);

    $current = $modules[$module];

    $clothes = collect();
    $runwayJobs = collect();
    $digitalTwinJobs = collect();

    if ($module === 'runway-video') {
        $clothes = Clothing::where('user_id', auth()->id())
            ->latest()
            ->get();

        $runwayJobs = AiJob::where('user_id', auth()->id())
            ->where('job_type', 'runway_video')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function (AiJob $job) {
                return [
                    'id' => 'RUNWAY-' . str_pad((string) $job->id, 4, '0', STR_PAD_LEFT),
                    'status' => $job->status,
                    'mode' => $job->mode ?? 'mock',
                    'clothing_id' => $job->clothing_id,
                    'request_id' => $job->request_id,
                    'result' => $job->result_json,
                    'error_code' => $job->error_code,
                    'error_message' => $job->error_message,
                    'created_at' => optional($job->created_at)->format('Y-m-d H:i:s'),
                ];
            });
    }
    if ($module === 'digital-twin') {
    $digitalTwinJobs = AiJob::where('user_id', auth()->id())
        ->whereIn('job_type', ['digital_twin', 'digital_twin_style_analysis'])
        ->latest()
        ->limit(10)
        ->get()
        ->map(function (AiJob $job) {
            return [
                'id' => 'TWIN-' . str_pad((string) $job->id, 4, '0', STR_PAD_LEFT),
                'status' => $job->status,
                'mode' => $job->mode ?? 'mock',
                'request_id' => $job->request_id,
                'result' => $job->result_json,
                'error_code' => $job->error_code,
                'error_message' => $job->error_message,
                'created_at' => optional($job->created_at)->format('Y-m-d H:i:s'),
            ];
        });
}
    return view('workspace.show', [
        'module' => $current,
        'modules' => $modules,
        'clothes' => $clothes,
        'runwayJobs' => $runwayJobs,
        'digitalTwinJobs' => $digitalTwinJobs,
    ]);
}

    public function storeRunwayVideo(Request $request, ExternalModelProviderService $externalModelProvider): RedirectResponse
{
    $validated = $request->validate([
        'clothing_id' => ['required', 'integer'],
        'video_style' => ['required', 'string', 'max:120'],
        'camera_rhythm' => ['nullable', 'string', 'max:120'],
    ]);

    $clothing = Clothing::where('user_id', auth()->id())
        ->findOrFail($validated['clothing_id']);

    $cameraRhythm = $validated['camera_rhythm'] ?? '平順伸展台';
    $videoPrompt = sprintf(
        '產生一支 %s 風格的伸展台影片，以 %s 為主角，色彩重點為 %s，鏡頭節奏為 %s。請使用直式時尚編輯構圖、乾淨光線與以單品細節為主的鏡頭。',
        $validated['video_style'],
        $clothing->name,
        $clothing->color ?? 'neutral',
        $cameraRhythm
    );

    $storyboard = [
        'title' => '伸展台影片 L1 Storyboard',
        'clothing' => [
            'id' => $clothing->id,
            'name' => $clothing->name,
            'category' => $clothing->category,
            'color' => $clothing->color,
            'image_url' => $clothing->display_image_url,
        ],
        'video_style' => $validated['video_style'],
        'camera_rhythm' => $validated['camera_rhythm'] ?? '平順伸展台',
        'scenes' => [
            [
                'scene' => 1,
                'title' => 'Opening Walk',
                'description' => '模特兒從簡約伸展台入口走出，畫面聚焦整體穿搭輪廓。',
                'camera' => 'wide shot',
                'duration_seconds' => 3,
            ],
            [
                'scene' => 2,
                'title' => 'Front Look',
                'description' => '鏡頭切到正面，展示衣物顏色、版型與整體比例。',
                'camera' => 'medium shot',
                'duration_seconds' => 4,
            ],
            [
                'scene' => 3,
                'title' => '細節特寫',
                'description' => '鏡頭靠近衣物細節，呈現材質、紋理與搭配亮點。',
                'camera' => 'close-up',
                'duration_seconds' => 3,
            ],
            [
                'scene' => 4,
                'title' => 'Final Pose',
                'description' => '模特兒停在伸展台中央，以定格姿勢完成時尚展示。',
                'camera' => 'slow zoom out',
                'duration_seconds' => 3,
            ],
        ],
        'prompt' => sprintf(
            '產生一支 %s 風格的伸展台影片，以 %s 為主角，色彩重點為 %s，鏡頭節奏為 %s。',
            $validated['video_style'],
            $clothing->name,
            $clothing->color ?? 'neutral',
            $validated['camera_rhythm'] ?? 'smooth'
        ),
        'degraded_reason' => 'RUNWAY_VIDEO_API_NOT_CONNECTED',
        'message' => '目前為伸展台影片 L1 Storyboard 安全備援狀態，真實影片生成 API 可在正式 provider 設定後啟用。',
    ];

    $storyboard['title'] = '伸展台影片 L2 預覽任務';
    $storyboard['camera_rhythm'] = $cameraRhythm;
    $storyboard['generation_status'] = 'degraded_placeholder';
    $storyboard['pipeline_stage'] = 'preview_ready';
    $storyboard['provider'] = [
        'name' => 'fallback-runway-adapter',
        'target_provider' => 'veo',
        'connected' => false,
    ];
    $storyboard['preview'] = [
        'type' => 'placeholder',
        'status' => 'ready',
        'label' => '模擬伸展台影片預覽',
        'poster_url' => $clothing->display_image_url,
        'video_url' => null,
        'duration_seconds' => collect($storyboard['scenes'] ?? [])->sum('duration_seconds'),
        'aspect_ratio' => '9:16',
    ];
    $storyboard['scene_timeline'] = $storyboard['scenes'] ?? [];
    $storyboard['prompt'] = $videoPrompt;
    $storyboard['video_prompt'] = $videoPrompt;
    $storyboard['export'] = [
        'format' => 'mp4',
        'resolution' => '1080x1920',
        'status' => 'not_generated',
    ];
    $storyboard['next_steps'] = [
        '接上真實影片服務轉接器。',
        '用服務回傳的 video_url 取代佔位預覽。',
        '保存服務任務 id 與輪詢狀態。',
    ];
    $storyboard['message'] = '伸展台影片 L2 目前使用預覽狀態，真實影片服務可在 provider 完成後啟用。';
    $storyboard['provider_attempt'] = $externalModelProvider->generateVideo([
        'request_id' => 'runway_l1_' . now()->format('YmdHis') . '_' . uniqid(),
        'user_id' => auth()->id(),
        'clothing_id' => $clothing->id,
        'image_url' => $clothing->display_image_url,
        'prompt' => $videoPrompt,
        'aspect_ratio' => '9:16',
        'duration_seconds' => collect($storyboard['scenes'] ?? [])->sum('duration_seconds'),
        'scene_timeline' => $storyboard['scenes'] ?? [],
    ]);

    if (($storyboard['provider_attempt']['status'] ?? null) === 'ready') {
        $storyboard['generation_status'] = 'ready';
        $storyboard['pipeline_stage'] = 'provider_submitted';
        $storyboard['provider']['name'] = $storyboard['provider_attempt']['provider'];
        $storyboard['provider']['connected'] = true;
        $storyboard['preview']['video_url'] = $storyboard['provider_attempt']['output_url'] ?? null;
        $storyboard['export']['status'] = 'provider_submitted';
        $storyboard['message'] = '伸展台影片任務已送出至真實影片 provider。';
    }

    AiJob::create([
        'user_id' => auth()->id(),
        'clothing_id' => $clothing->id,
        'job_type' => 'runway_video',
        'status' => ($storyboard['provider_attempt']['status'] ?? null) === 'ready' ? 'ready' : 'degraded',
        'mode' => ($storyboard['provider_attempt']['status'] ?? null) === 'ready' ? 'real_adapter' : 'mock',
        'request_id' => 'runway_l1_' . now()->format('YmdHis') . '_' . uniqid(),
        'input_json' => [
            'clothing_id' => $clothing->id,
            'video_style' => $validated['video_style'],
            'camera_rhythm' => $cameraRhythm,
            'target_provider' => 'veo',
            'expected_aspect_ratio' => '9:16',
        ],
        'result_json' => $storyboard,
        'degraded_reason' => 'RUNWAY_VIDEO_API_NOT_CONNECTED',
        'error_code' => null,
        'error_message' => null,
        'started_at' => now(),
        'completed_at' => now(),
    ]);

    return redirect()
        ->route('workspace.show', 'runway-video')
        ->with('status', '伸展台影片 L2 預覽任務已建立，可人工驗收：預覽狀態 ready / 9:16。');
}

    public function storeDigitalTwin(Request $request, ExternalModelProviderService $externalModelProvider): RedirectResponse
{
    $validated = $request->validate([
        'height_cm' => ['required', 'integer', 'min:100', 'max:230'],
        'style_preference' => ['required', 'string', 'max:160'],
        'common_occasion' => ['required', 'string', 'max:160'],
        'body_note' => ['nullable', 'string', 'max:500'],
    ]);

    $profile = [
        'title' => '數位分身 L1 風格資料',
        'avatar' => [
            'type' => 'placeholder',
            'label' => 'VogueAI 數位分身',
            'image_url' => null,
        ],
        'profile' => [
            'height_cm' => (int) $validated['height_cm'],
            'style_preference' => $validated['style_preference'],
            'common_occasion' => $validated['common_occasion'],
            'body_note' => $validated['body_note'] ?? null,
        ],
        'style_summary' => [
            'headline' => '以個人偏好建立的 L1 風格分身',
            'description' => sprintf(
                '此數位分身根據身高 %d cm、偏好「%s」與常見場合「%s」建立，目前為安全備援個人風格卡。',
                (int) $validated['height_cm'],
                $validated['style_preference'],
                $validated['common_occasion']
            ),
            'recommended_direction' => [
                '以衣櫥現有單品建立個人化穿搭基準',
                '後續可串接 AI 穿搭顧問，依照場合與風格偏好推薦穿搭',
                '未來可接 3D Avatar 或多視角生成服務',
            ],
        ],
        'style_tags' => [
            $validated['style_preference'],
            $validated['common_occasion'],
            '數位分身 L1',
            'fallback profile',
        ],
        'degraded_reason' => 'DIGITAL_TWIN_3D_MODEL_NOT_CONNECTED',
        'message' => '目前為數位分身 L1 個人風格卡安全備援狀態，真實 3D 或外部生成服務可在 provider 完成後啟用。',
    ];

    $profile['provider_attempt'] = $externalModelProvider->generateDigitalTwin([
        'request_id' => 'digital_twin_l1_' . now()->format('YmdHis') . '_' . uniqid(),
        'user_id' => auth()->id(),
        'profile' => $profile['profile'],
        'style_summary' => $profile['style_summary'],
        'style_tags' => $profile['style_tags'],
    ]);

    if (($profile['provider_attempt']['status'] ?? null) === 'ready') {
        $profile['avatar']['type'] = 'provider_avatar';
        $profile['avatar']['image_url'] = $profile['provider_attempt']['output_url'] ?? null;
        $profile['message'] = '數位分身任務已送出至真實 avatar provider。';
    }

    AiJob::create([
        'user_id' => auth()->id(),
        'clothing_id' => null,
        'job_type' => 'digital_twin',
        'status' => ($profile['provider_attempt']['status'] ?? null) === 'ready' ? 'ready' : 'degraded',
        'mode' => ($profile['provider_attempt']['status'] ?? null) === 'ready' ? 'real_adapter' : 'mock',
        'request_id' => 'digital_twin_l1_' . now()->format('YmdHis') . '_' . uniqid(),
        'input_json' => [
            'height_cm' => (int) $validated['height_cm'],
            'style_preference' => $validated['style_preference'],
            'common_occasion' => $validated['common_occasion'],
            'body_note' => $validated['body_note'] ?? null,
        ],
        'result_json' => $profile,
        'degraded_reason' => 'DIGITAL_TWIN_3D_MODEL_NOT_CONNECTED',
        'error_code' => null,
        'error_message' => null,
        'started_at' => now(),
        'completed_at' => now(),
    ]);

    return redirect()
        ->route('workspace.show', 'digital-twin')
        ->with('status', '數位分身 L1 風格資料已建立，可人工驗收：fallback profile / degraded。');
}

    public function analyzeDigitalTwinCloset(): RedirectResponse
{
    $clothes = Clothing::where('user_id', auth()->id())
        ->latest()
        ->get();

    if ($clothes->isEmpty()) {
        return redirect()
            ->route('workspace.show', 'digital-twin')
            ->with('error', '目前衣櫥尚未有衣物，請先上傳衣物後再進行數位分身 L2 衣櫥風格分析。');
    }

    $categoryCounts = $this->countClothingField($clothes, 'category');
    $colorCounts = $this->countClothingField($clothes, 'color');
    $seasonCounts = $this->countClothingArrayField($clothes, 'season');
    $occasionCounts = $this->countClothingArrayField($clothes, 'occasion');
    $styleTagCounts = $this->countClothingArrayField($clothes, 'style_tags');

    $topCategories = $this->topCountItems($categoryCounts);
    $topColors = $this->topCountItems($colorCounts);
    $topSeasons = $this->topCountItems($seasonCounts);
    $topOccasions = $this->topCountItems($occasionCounts);
    $topStyleTags = $this->topCountItems($styleTagCounts);

    $dominantCategory = $topCategories[0]['label'] ?? '未分類';
    $dominantColor = $topColors[0]['label'] ?? '未設定顏色';
    $dominantOccasion = $topOccasions[0]['label'] ?? '日常';
    $dominantStyle = $topStyleTags[0]['label'] ?? '尚未建立風格標籤';

    $profile = [
        'title' => '數位分身 L2 衣櫥風格分析',
        'avatar' => [
            'type' => 'closet_profile',
            'label' => 'VogueAI 衣櫥型數位分身',
            'image_url' => null,
        ],
        'profile' => [
            'source' => 'clothes',
            'total_items' => $clothes->count(),
            'dominant_category' => $dominantCategory,
            'dominant_color' => $dominantColor,
            'dominant_occasion' => $dominantOccasion,
            'dominant_style' => $dominantStyle,
        ],
        'style_summary' => [
            'headline' => '根據衣櫥資料建立的數位分身 L2 風格摘要',
            'description' => sprintf(
                '系統分析了你目前衣櫥中的 %d 件衣物，發現你最常出現的類別是「%s」，主要顏色是「%s」，常見場合偏向「%s」，整體風格可歸納為「%s」。',
                $clothes->count(),
                $dominantCategory,
                $dominantColor,
                $dominantOccasion,
                $dominantStyle
            ),
            'recommended_direction' => [
                '可將此風格摘要提供給 AI 穿搭顧問，讓穿搭推薦更貼近使用者真實衣櫥。',
                '若想讓分析更準確，建議補齊每件衣物的季節、場合與 style_tags。',
                '後續可加入穿搭接受 / 拒絕紀錄，讓數位分身逐步學習個人偏好。',
            ],
        ],
        'closet_statistics' => [
            'top_categories' => $topCategories,
            'top_colors' => $topColors,
            'top_seasons' => $topSeasons,
            'top_occasions' => $topOccasions,
            'top_style_tags' => $topStyleTags,
        ],
        'style_tags' => collect([
            $dominantCategory,
            $dominantColor,
            $dominantOccasion,
            $dominantStyle,
            '數位分身 L2',
            '衣櫥分析',
        ])->filter()->unique()->values()->all(),
        'degraded_reason' => 'DIGITAL_TWIN_RULE_BASED_CLOSET_ANALYSIS',
        'message' => '目前為數位分身 L2 衣櫥統計分析模式，尚未接入真實 3D Avatar 或生成式模型。',
    ];

    AiJob::create([
        'user_id' => auth()->id(),
        'clothing_id' => null,
        'job_type' => 'digital_twin_style_analysis',
        'status' => 'degraded',
        'mode' => 'rule_based',
        'request_id' => 'digital_twin_l2_' . now()->format('YmdHis') . '_' . uniqid(),
        'input_json' => [
            'source' => 'clothes',
            'total_items' => $clothes->count(),
        ],
        'result_json' => $profile,
        'degraded_reason' => 'DIGITAL_TWIN_RULE_BASED_CLOSET_ANALYSIS',
        'error_code' => null,
        'error_message' => null,
        'started_at' => now(),
        'completed_at' => now(),
    ]);

    return redirect()
        ->route('workspace.show', 'digital-twin')
        ->with('status', '數位分身 L2 衣櫥風格分析已建立，可人工驗收：rule_based / degraded。');
}

    private function countClothingField($clothes, string $field): array
{
    return $clothes
        ->pluck($field)
        ->filter()
        ->map(fn ($value) => trim((string) $value))
        ->filter()
        ->countBy()
        ->sortDesc()
        ->all();
}

private function countClothingArrayField($clothes, string $field): array
{
    return $clothes
        ->flatMap(function (Clothing $item) use ($field) {
            $values = $item->{$field} ?? [];

            if (is_string($values)) {
                $decoded = json_decode($values, true);
                $values = is_array($decoded) ? $decoded : [$values];
            }

            return collect($values)
                ->map(fn ($value) => trim((string) $value))
                ->filter();
        })
        ->countBy()
        ->sortDesc()
        ->all();
}

private function topCountItems(array $counts, int $limit = 5): array
{
    return collect($counts)
        ->take($limit)
        ->map(fn ($count, $label) => [
            'label' => (string) $label,
            'count' => (int) $count,
        ])
        ->values()
        ->all();
}

    /**
     * @return array<string, array<string, mixed>>
     */
    private function modules(): array
    {
        return [
            'ai-stylist' => [
                'slug' => 'ai-stylist',
                'title' => 'AI 穿搭顧問工作區',
                'summary' => '情境穿搭推薦工作台，對接 recommend 流程與搭配結果儲存。',
                'primaryAction' => '產生穿搭建議',
                'api' => '/api/stylist/recommend',
                'status' => 'pending',
                'fields' => ['場合', '天氣', '風格偏好'],
            ],
            'virtual-try-on' => [
                'slug' => 'virtual-try-on',
                'title' => '虛擬試穿工作區',
                'summary' => '人物照片與衣物圖片輸入頁，後續對接 try-on 與姿態分析。',
                'primaryAction' => '執行試穿',
                'api' => '/api/tryon/generate',
                'status' => 'degraded/fallback',
                'fields' => ['人物照片', '衣物圖片', '角度設定'],
            ],
            'community' => [
                'slug' => 'community',
                'title' => '社群工作區',
                'summary' => '貼文、按讚、評論工作台，預留社群 API 對接欄位。',
                'primaryAction' => '發布貼文',
                'api' => '/api/community/posts',
                'status' => 'pending',
                'fields' => ['貼文內容', '圖片', '標籤'],
            ],
            'blind-box' => [
                'slug' => 'blind-box',
                'title' => '穿搭盲盒工作區',
                'summary' => '盲盒穿搭前端流程，顯示隨機穿搭結果與收藏入口。',
                'primaryAction' => '抽取盲盒',
                'api' => '/api/blindbox/generate',
                'status' => 'degraded/fallback',
                'fields' => ['偏好風格', '場景', '限制條件'],
            ],
            'runway-video' => [
                'slug' => 'runway-video',
                'title' => '伸展台影片工作區',
                'summary' => '走秀影片生成流程頁，預留影片任務 queue 狀態顯示。',
                'primaryAction' => '生成伸展台影片',
                'api' => '/api/video/generate',
                'status' => 'pending',
                'fields' => ['穿搭圖', '影片風格', '鏡頭節奏'],
            ],
            'chat-assistant' => [
                'slug' => 'chat-assistant',
                'title' => '聊天助理工作區',
                'summary' => 'AI 對話與穿搭問答頁面，保留 prompt / context 設定。',
                'primaryAction' => '送出問題',
                'api' => '/api/gemini/visual-stylist-call',
                'status' => 'pending',
                'fields' => ['使用者問題', '上下文衣櫥', '語氣模式'],
            ],
            'showcase' => [
                'slug' => 'showcase',
                'title' => '展示牆工作區',
                'summary' => '商家商品展示與一鍵入庫界面，先做前台卡片與篩選。',
                'primaryAction' => '加入衣櫥',
                'api' => '/api/import/confirm',
                'status' => 'pending',
                'fields' => ['品牌', '品類', '價格區間'],
            ],
            'digital-twin' => [
                'slug' => 'digital-twin',
                'title' => '數位分身工作區',
                'summary' => '3D 多視角生成流程頁，預留任務狀態與圖像牆。',
                'primaryAction' => '生成多視角',
                'api' => '/api/digital-twin/generate-all',
                'status' => 'degraded/fallback',
                'fields' => ['身高', '體重', '風格提示詞'],
            ],
            'travel-packer' => [
                'slug' => 'travel-packer',
                'title' => '旅行打包工作區',
                'summary' => '旅行打包清單生成與天氣資料輸入頁。',
                'primaryAction' => '產生打包清單',
                'api' => '/api/travel/packing-list',
                'status' => 'pending',
                'fields' => ['目的地', '天數', '活動型態'],
            ],
            'smart-storage' => [
                'slug' => 'smart-storage',
                'title' => '智慧收納工作區',
                'summary' => '收納箱與衣物位置管理頁，對接 storage 相關 API。',
                'primaryAction' => '新增收納箱',
                'api' => '/api/storage/boxes',
                'status' => 'pending',
                'fields' => ['收納箱名稱', '區域', '分類標籤'],
            ],
            'quick-snap' => [
                'slug' => 'quick-snap',
                'title' => '快速拍照工作區',
                'summary' => '快速拍照入庫流程與即時預覽。',
                'primaryAction' => '拍照入庫',
                'api' => '/api/import/scan',
                'status' => 'pending',
                'fields' => ['相機來源', '衣物名稱', '備註'],
            ],
            'smart-tag' => [
                'slug' => 'smart-tag',
                'title' => '智慧標籤工作區',
                'summary' => '吊牌/發票掃描辨識工作台。',
                'primaryAction' => '掃描辨識',
                'api' => '/api/import/scan',
                'status' => 'degraded/fallback',
                'fields' => ['圖片', 'OCR 語言', '品牌線索'],
            ],
            'magic-mirror' => [
                'slug' => 'magic-mirror',
                'title' => '魔鏡試穿工作區',
                'summary' => '姿態與體態分析入口，展示分析結果與建議。',
                'primaryAction' => '開始分析',
                'api' => '/api/magic-mirror/analyze',
                'status' => 'pending',
                'fields' => ['人物照片', '站姿', '身體關鍵點'],
            ],
            'stylist-call' => [
                'slug' => 'stylist-call',
                'title' => 'AI 好友通話工作區',
                'summary' => '視訊風格諮詢工作台，先完成前端流程與狀態設計。',
                'primaryAction' => '啟動通話',
                'api' => '/api/gemini/visual-stylist-call',
                'status' => 'pending',
                'fields' => ['通話主題', '語音輸入', '語言偏好'],
            ],
        ];
    }
}
