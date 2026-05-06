<?php

namespace App\Http\Controllers;

use App\Models\AiJob;
use App\Models\AiEmbedding;
use App\Models\Clothing;
use App\Services\AiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use App\Models\StylistHistory;

class ClosetController extends Controller
{
    public function hub(): View
    {
        return view('closet.hub', [
            'quickStats' => [
                [
                    'label' => '衣物總數',
                    'value' => (string) Clothing::where('user_id', auth()->id())->count(),
                ],
                [
                    'label' => '待分析',
                    'value' => (string) Clothing::where('user_id', auth()->id())
                        ->where('ai_status', 'pending')
                        ->count(),
                ],
                [
                    'label' => 'Mock/Degraded',
                    'value' => (string) Clothing::where('user_id', auth()->id())
                        ->where('ai_status', 'degraded')
                        ->count(),
                ],
                [
                    'label' => '本週新增',
                    'value' => (string) Clothing::where('user_id', auth()->id())
                        ->where('created_at', '>=', now()->subDays(7))
                        ->count(),
                ],
            ],
        ]);
    }

    public function index(Request $request): View
    {
        $query = trim((string) $request->string('q', ''));

        $clothesQuery = Clothing::where('user_id', auth()->id())
            ->latest();

        if ($query !== '') {
            $clothesQuery->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('category', 'like', "%{$query}%")
                    ->orWhere('subcategory', 'like', "%{$query}%")
                    ->orWhere('color', 'like', "%{$query}%");
            });
        }

        $items = $clothesQuery
            ->get()
            ->map(fn (Clothing $clothing) => $this->toViewItem($clothing));

        return view('closet.index', [
            'items' => $items,
            'query' => $query,
        ]);
    }

    public function create(): View
    {
        return view('closet.create');
    }

    public function store(Request $request, AiService $aiService): RedirectResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'max:5120'],
            'name' => ['required', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $path = $request->file('image')->store('clothes/' . auth()->id(), 'public');

        $clothing = Clothing::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'image_path' => $path,
            'image_url' => Storage::url($path),
            'notes' => $validated['notes'] ?? null,
            'ai_status' => 'pending',
            'ai_mode' => null,
        ]);

        $aiResult = $aiService->analyzeAttributes([
            'user_id' => $clothing->user_id,
            'clothing_id' => $clothing->id,
            'image_path' => $clothing->image_path,
            'image_url' => asset('storage/' . $clothing->image_path),
        ]);

        $this->applyAttributesResult($clothing, $aiResult);

        $imageEmbeddingResult = $aiService->embedImage([
            'user_id' => $clothing->user_id,
            'clothing_id' => $clothing->id,
            'image_path' => $clothing->image_path,
            'image_url' => asset('storage/' . $clothing->image_path),
            'store_to_vector_db' => true,
        ]);

        $this->saveImageEmbeddingResult($clothing, $imageEmbeddingResult);

        return redirect()
            ->route('closet.show', $clothing->id)
            ->with('status', '衣物已上傳完成，AI 分析與 Image Embedding 已寫入資料庫。');
    }

    public function show(int $id): View
    {
        $clothing = Clothing::where('user_id', auth()->id())
            ->findOrFail($id);

        return view('closet.show', [
            'item' => $this->toViewItem($clothing),
        ]);
    }

    public function reanalyze(int $id, AiService $aiService): RedirectResponse
    {
        $clothing = Clothing::where('user_id', auth()->id())
            ->findOrFail($id);

        $clothing->update([
            'ai_status' => 'pending',
            'ai_error_code' => null,
            'ai_error_message' => null,
        ]);

        $aiResult = $aiService->analyzeAttributes([
            'user_id' => $clothing->user_id,
            'clothing_id' => $clothing->id,
            'image_path' => $clothing->image_path,
            'image_url' => asset('storage/' . $clothing->image_path),
        ]);

        $this->applyAttributesResult($clothing, $aiResult);

        if (in_array($aiResult['status'] ?? 'failed', ['success', 'degraded'], true)) {
            return redirect()
                ->route('closet.show', $clothing->id)
                ->with('status', 'AI 屬性已重新分析完成。');
        }

        return redirect()
            ->route('closet.show', $clothing->id)
            ->with('status', 'AI 重新分析失敗，已保留原始衣物資料。');
    }

    public function reembed(int $id, AiService $aiService): RedirectResponse
    {
        $clothing = Clothing::where('user_id', auth()->id())
            ->findOrFail($id);

        $imageEmbeddingResult = $aiService->embedImage([
            'user_id' => $clothing->user_id,
            'clothing_id' => $clothing->id,
            'image_path' => $clothing->image_path,
            'image_url' => asset('storage/' . $clothing->image_path),
            'store_to_vector_db' => true,
        ]);

        $this->saveImageEmbeddingResult($clothing, $imageEmbeddingResult);

        if (in_array($imageEmbeddingResult['status'] ?? 'failed', ['success', 'degraded'], true)) {
            return redirect()
                ->route('closet.show', $clothing->id)
                ->with('status', 'Image Embedding 已重新產生完成。');
        }

        return redirect()
            ->route('closet.show', $clothing->id)
            ->with('status', 'Image Embedding 重新產生失敗，已記錄錯誤。');
    }

    public function search(Request $request, AiService $aiService): View
    {
        $query = trim((string) $request->string('q', ''));
        $topK = (int) $request->integer('top_k', 6);

        if ($topK < 1) {
            $topK = 6;
        }

        if ($topK > 20) {
            $topK = 20;
        }

        $results = collect();
        $searchMode = 'empty';
        $message = '請輸入搜尋文字，例如：白色襯衫、紅色約會洋裝、適合面試的外套。';
        $aiResult = null;

        if ($query !== '') {
            $textEmbeddingResult = $aiService->embedText([
                'user_id' => auth()->id(),
                'query' => $query,
            ]);

            if (in_array($textEmbeddingResult['status'] ?? 'failed', ['success', 'degraded'], true)) {
                $similarResult = $aiService->searchSimilar([
                    'user_id' => auth()->id(),
                    'query_type' => 'text',
                    'query' => $query,
                    'embedding' => $textEmbeddingResult['embedding'] ?? [],
                    'top_k' => $topK,
                    'filters' => [],
                ]);

                $aiResult = $similarResult;

                if (in_array($similarResult['status'] ?? 'failed', ['success', 'degraded'], true)) {
                    $results = $this->mapSimilarSearchResults($similarResult);

                    $searchMode = $similarResult['search_provider'] ?? 'ai_search';
                    $message = $results->isEmpty()
                        ? 'AI 搜尋有回應，但目前沒有找到對應衣物。'
                        : 'AI Search 已完成，以下為語意相似結果。';
                } else {
                    $results = $this->keywordSearch($query);
                    $searchMode = 'keyword_fallback';
                    $message = 'AI 相似搜尋暫時不可用，已改用一般關鍵字搜尋。';
                    $aiResult = $similarResult;
                }
            } else {
                $results = $this->keywordSearch($query);
                $searchMode = 'keyword_fallback';
                $message = 'AI text embedding 暫時不可用，已改用一般關鍵字搜尋。';
                $aiResult = $textEmbeddingResult;
            }
        }

        return view('closet.search', [
            'query' => $query,
            'topK' => $topK,
            'results' => $results,
            'searchMode' => $searchMode,
            'message' => $message,
            'aiResult' => $aiResult,
        ]);
    }

   public function stylist(): View
{
    $clothes = Clothing::where('user_id', auth()->id())
        ->latest()
        ->get();

    $stylistHistories = StylistHistory::where('user_id', auth()->id())
        ->latest()
        ->limit(10)
        ->get()
        ->map(function (StylistHistory $history) {
            return [
                'id' => 'STYLE-' . str_pad((string) $history->id, 4, '0', STR_PAD_LEFT),
                'occasion' => $history->occasion,
                'weather' => $history->weather,
                'style_preference' => $history->style_preference,
                'selected_items' => $history->selected_items ?? [],
                'recommendation' => $history->recommendation_json ?? [],
                'status' => $history->status,
                'mode' => $history->mode,
                'is_accepted' => $history->is_accepted,
                'created_at' => optional($history->created_at)->format('Y-m-d H:i:s'),
            ];
        });

    return view('closet.stylist', [
        'clothes' => $clothes,
        'stylistHistories' => $stylistHistories,
    ]);
}



   public function generateStylist(Request $request): RedirectResponse
{
    $validated = $request->validate([
        'occasion' => ['required', 'string', 'max:120'],
        'weather' => ['nullable', 'string', 'max:120'],
        'style_preference' => ['nullable', 'string', 'max:300'],
    ]);

    $clothes = Clothing::where('user_id', auth()->id())
        ->latest()
        ->get();

    if ($clothes->isEmpty()) {
        return redirect()
            ->route('closet.stylist')
            ->with('error', '目前衣櫥尚未有衣物，請先上傳衣物後再產生 AI Stylist 推薦。');
    }

    $occasion = $validated['occasion'];
    $weather = $validated['weather'] ?? '未提供天氣';
    $stylePreference = $validated['style_preference'] ?? '未提供風格偏好';

    $occasionMatched = $clothes->filter(function (Clothing $item) use ($occasion) {
        $occasions = collect($item->occasion ?? [])
            ->map(fn ($value) => mb_strtolower((string) $value));

        return $occasions->contains(fn ($value) => str_contains($value, mb_strtolower($occasion)));
    });

    $seasonMatched = $clothes->filter(function (Clothing $item) use ($weather) {
        $weatherText = mb_strtolower($weather);

        $targetSeasons = [];

        if (str_contains($weatherText, '熱') || str_contains($weatherText, '夏') || str_contains($weatherText, 'sunny')) {
            $targetSeasons = ['夏', '春夏', 'summer'];
        } elseif (str_contains($weatherText, '冷') || str_contains($weatherText, '冬') || str_contains($weatherText, '寒') || str_contains($weatherText, 'cold')) {
            $targetSeasons = ['冬', '秋冬', 'winter'];
        } elseif (str_contains($weatherText, '雨') || str_contains($weatherText, 'rain')) {
            $targetSeasons = ['雨天', '四季'];
        }

        if (empty($targetSeasons)) {
            return false;
        }

        $itemSeasons = collect($item->season ?? [])
            ->map(fn ($value) => mb_strtolower((string) $value));

        return $itemSeasons->contains(function ($season) use ($targetSeasons) {
            foreach ($targetSeasons as $target) {
                if (str_contains($season, mb_strtolower($target))) {
                    return true;
                }
            }

            return false;
        });
    });

    $styleMatched = $clothes->filter(function (Clothing $item) use ($stylePreference) {
        $styleText = mb_strtolower($stylePreference);

        if ($styleText === '未提供風格偏好') {
            return false;
        }

        $tags = collect($item->style_tags ?? [])
            ->map(fn ($value) => mb_strtolower((string) $value));

        return $tags->contains(function ($tag) use ($styleText) {
            return str_contains($styleText, $tag) || str_contains($tag, $styleText);
        });
    });

    $candidateItems = $occasionMatched
        ->merge($seasonMatched)
        ->merge($styleMatched)
        ->unique('id')
        ->values();

    if ($candidateItems->count() < 2) {
        $candidateItems = $candidateItems
            ->merge($clothes)
            ->unique('id')
            ->values();
    }

    $selectedItems = $candidateItems
        ->take(3)
        ->map(function (Clothing $item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'category' => $item->category,
                'subcategory' => $item->subcategory,
                'color' => $item->color,
                'season' => $item->season ?? [],
                'occasion' => $item->occasion ?? [],
                'usage' => $item->usage ?? [],
                'style_tags' => $item->style_tags ?? [],
                'image_url' => $item->display_image_url,
            ];
        })
        ->values()
        ->all();

    $mainColors = collect($selectedItems)
        ->pluck('color')
        ->filter()
        ->unique()
        ->values()
        ->all();

    $recommendation = [
        'title' => $this->buildStylistTitle($occasion, $stylePreference),
        'summary' => $this->buildStylistSummary($occasion, $weather, $stylePreference, $selectedItems),
        'reasoning' => [
            '根據你輸入的場合、天氣與風格偏好，系統會優先挑選 occasion、season、style_tags 較接近的衣物。',
            '目前為 L1.5 / L2 基礎規則式推薦，已經會讀取你的 clothes 資料表，不只是固定假資料。',
            '後續可接 Gemini、CLIP embedding、Digital Twin profile 與穿搭歷史，讓推薦更個人化。',
        ],
        'main_colors' => $mainColors,
        'styling_tips' => [
            '若想讓推薦更準確，建議每件衣物補上季節、場合與風格標籤。',
            '如果推薦結果不理想，可以先回 My Closet 補齊衣物分類，或重新上傳更多衣物。',
            '展示時可說明目前使用 rule_based 模式，未來會升級為 AI model / RAG 推薦。',
        ],
        'degraded_reason' => 'AI_STYLIST_RULE_BASED_MODE',
    ];

    StylistHistory::create([
        'user_id' => auth()->id(),
        'occasion' => $occasion,
        'weather' => $weather,
        'style_preference' => $stylePreference,
        'selected_items' => $selectedItems,
        'recommendation_json' => $recommendation,
        'status' => 'degraded',
        'mode' => 'rule_based',
        'is_accepted' => false,
    ]);

    return redirect()
        ->route('closet.stylist')
        ->with('status', 'AI Stylist 已根據你的衣櫥資料產生穿搭建議，目前為 rule_based / degraded 模式。');
}

private function buildStylistTitle(string $occasion, string $stylePreference): string
{
    if ($stylePreference !== '未提供風格偏好') {
        return $occasion . ' × ' . $stylePreference . ' 穿搭建議';
    }

    return $occasion . ' 穿搭建議';
}

private function buildStylistSummary(string $occasion, string $weather, string $stylePreference, array $selectedItems): string
{
    $itemNames = collect($selectedItems)
        ->pluck('name')
        ->filter()
        ->implode('、');

    if ($itemNames === '') {
        $itemNames = '目前衣櫥中的可用衣物';
    }

    return sprintf(
        '這套建議以「%s」為主要場合，參考天氣「%s」與風格偏好「%s」，從你的衣櫥中挑選出：%s。',
        $occasion,
        $weather,
        $stylePreference,
        $itemNames
    );
}

   public function tryOn(): View
{
    $clothes = Clothing::where('user_id', auth()->id())
        ->latest()
        ->get();

    $poseJobs = AiJob::where('user_id', auth()->id())
        ->where('job_type', 'pose_analysis')
        ->latest()
        ->limit(10)
        ->get()
        ->map(function (AiJob $job) {
            return [
                'id' => 'POSE-' . str_pad((string) $job->id, 4, '0', STR_PAD_LEFT),
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

    return view('closet.tryon', [
        'clothes' => $clothes,
        'poseJobs' => $poseJobs,
    ]);
}

    public function storeTryOn(Request $request, AiService $aiService): RedirectResponse
{
    $validated = $request->validate([
        'clothing_id' => ['required', 'integer'],
        'person_photo' => ['required', 'image', 'max:5120'],
    ]);

    $clothing = Clothing::where('user_id', auth()->id())
        ->findOrFail($validated['clothing_id']);

    $personPhotoPath = $request->file('person_photo')
        ->store('tryon/' . auth()->id(), 'public');

    $job = AiJob::create([
        'user_id' => auth()->id(),
        'clothing_id' => $clothing->id,
        'job_type' => 'pose_analysis',
        'status' => 'processing',
        'mode' => 'mock',
        'input_json' => [
            'person_photo_path' => $personPhotoPath,
            'person_photo_url' => asset('storage/' . $personPhotoPath),
            'clothing_id' => $clothing->id,
            'clothing_name' => $clothing->name,
            'clothing_image_url' => $clothing->display_image_url,
            'task_type' => 'try_on_l1',
        ],
        'started_at' => now(),
    ]);

    $poseResult = $aiService->analyzePose([
        'user_id' => auth()->id(),
        'image_path' => $personPhotoPath,
        'image_url' => asset('storage/' . $personPhotoPath),
        'task_type' => 'try_on_l1',
    ]);

    if (in_array($poseResult['status'] ?? 'failed', ['success', 'degraded'], true)) {
        $job->update([
            'status' => $poseResult['status'],
            'mode' => $poseResult['mode'] ?? 'mock',
            'request_id' => $poseResult['request_id'] ?? null,
            'result_json' => $poseResult,
            'degraded_reason' => $poseResult['degraded_reason'] ?? null,
            'error_code' => null,
            'error_message' => null,
            'completed_at' => now(),
        ]);

        return redirect()
            ->route('closet.tryon')
            ->with('status', 'Try-on L1 任務已完成，目前使用 Pose mock / degraded 結果展示。');
    }

    $job->update([
        'status' => 'failed',
        'mode' => null,
        'request_id' => $poseResult['request_id'] ?? null,
        'result_json' => $poseResult,
        'error_code' => $poseResult['error']['code'] ?? 'AI_POSE_UNKNOWN_ERROR',
        'error_message' => $poseResult['error']['message'] ?? 'Pose 分析失敗',
        'completed_at' => now(),
    ]);

    return redirect()
        ->route('closet.tryon')
        ->with('status', 'Try-on L1 任務失敗，已記錄錯誤。');
}

    private function applyAttributesResult(Clothing $clothing, array $aiResult): void
    {
        if (in_array($aiResult['status'] ?? 'failed', ['success', 'degraded'], true)) {
            $attributes = $aiResult['attributes'] ?? [];
            $confidence = $aiResult['confidence'] ?? [];

            $clothing->update([
                'category' => $attributes['category'] ?? null,
                'subcategory' => $attributes['subcategory'] ?? null,
                'color' => $attributes['color'] ?? null,
                'secondary_colors' => $attributes['secondary_colors'] ?? [],
                'season' => $attributes['season'] ?? [],
                'occasion' => $attributes['occasion'] ?? [],
                'usage' => $attributes['usage'] ?? [],
                'style_tags' => $attributes['style_tags'] ?? [],
                'material_guess' => $attributes['material_guess'] ?? null,
                'pattern' => $attributes['pattern'] ?? null,
                'ai_status' => $aiResult['status'],
                'ai_mode' => $aiResult['mode'] ?? null,
                'ai_confidence' => $confidence['overall'] ?? null,
                'ai_raw_result' => $aiResult,
                'ai_error_code' => null,
                'ai_error_message' => null,
            ]);

            return;
        }

        $clothing->update([
            'ai_status' => 'failed',
            'ai_mode' => null,
            'ai_raw_result' => $aiResult,
            'ai_error_code' => $aiResult['error']['code'] ?? 'AI_UNKNOWN_ERROR',
            'ai_error_message' => $aiResult['error']['message'] ?? 'AI 分析失敗',
        ]);
    }

    private function saveImageEmbeddingResult(Clothing $clothing, array $imageEmbeddingResult): void
    {
        if (in_array($imageEmbeddingResult['status'] ?? 'failed', ['success', 'degraded'], true)) {
            AiEmbedding::updateOrCreate(
                [
                    'clothing_id' => $clothing->id,
                    'embedding_type' => 'image',
                ],
                [
                    'user_id' => $clothing->user_id,
                    'source_type' => 'clothing',
                    'source_text' => null,
                    'model' => $imageEmbeddingResult['model'] ?? null,
                    'vector_dimension' => $imageEmbeddingResult['vector_dimension'] ?? null,
                    'embedding' => $imageEmbeddingResult['embedding'] ?? [],
                    'embedding_preview' => $imageEmbeddingResult['embedding_preview'] ?? [],
                    'vector_provider' => $imageEmbeddingResult['vector_db']['provider'] ?? null,
                    'vector_collection' => $imageEmbeddingResult['vector_db']['collection'] ?? null,
                    'vector_point_id' => $imageEmbeddingResult['vector_db']['point_id'] ?? null,
                    'vector_stored' => $imageEmbeddingResult['vector_db']['stored'] ?? false,
                    'status' => $imageEmbeddingResult['status'],
                    'mode' => $imageEmbeddingResult['mode'] ?? null,
                    'degraded_reason' => $imageEmbeddingResult['degraded_reason'] ?? null,
                    'raw_result' => $imageEmbeddingResult,
                    'error_code' => null,
                    'error_message' => null,
                ]
            );

            return;
        }

        AiEmbedding::updateOrCreate(
            [
                'clothing_id' => $clothing->id,
                'embedding_type' => 'image',
            ],
            [
                'user_id' => $clothing->user_id,
                'source_type' => 'clothing',
                'status' => 'failed',
                'mode' => null,
                'raw_result' => $imageEmbeddingResult,
                'error_code' => $imageEmbeddingResult['error']['code'] ?? 'AI_EMBEDDING_UNKNOWN_ERROR',
                'error_message' => $imageEmbeddingResult['error']['message'] ?? 'image embedding 產生失敗',
            ]
        );
    }

    /**
     * 將 Python AI Search 回傳的 clothing_id 結果轉成 Blade 可顯示格式。
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function mapSimilarSearchResults(array $similarResult): Collection
    {
        $ids = collect($similarResult['results'] ?? [])
            ->pluck('clothing_id')
            ->filter()
            ->unique()
            ->values();

        $clothes = Clothing::where('user_id', auth()->id())
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return collect($similarResult['results'] ?? [])
            ->map(function (array $result) use ($clothes) {
                $clothingId = $result['clothing_id'] ?? null;
                $clothing = $clothingId ? $clothes->get($clothingId) : null;

                if (! $clothing) {
                    return null;
                }

                return [
                    'id' => $clothing->id,
                    'name' => $clothing->name,
                    'image' => $clothing->display_image_url,
                    'category' => $clothing->category ?? '未分類',
                    'color' => $clothing->color ?? '未知顏色',
                    'score' => $result['score'] ?? 0,
                    'type' => 'text',
                    'reason' => $result['reason'] ?? 'AI 語意相似搜尋結果',
                    'ai_status' => $clothing->ai_status,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * AI 搜尋失敗時的 fallback：一般 SQL LIKE 關鍵字搜尋。
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function keywordSearch(string $query): Collection
    {
        return Clothing::where('user_id', auth()->id())
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('category', 'like', "%{$query}%")
                    ->orWhere('subcategory', 'like', "%{$query}%")
                    ->orWhere('color', 'like', "%{$query}%");
            })
            ->latest()
            ->get()
            ->map(function (Clothing $clothing) {
                return [
                    'id' => $clothing->id,
                    'name' => $clothing->name,
                    'image' => $clothing->display_image_url,
                    'category' => $clothing->category ?? '未分類',
                    'color' => $clothing->color ?? '未知顏色',
                    'score' => 0,
                    'type' => 'keyword',
                    'reason' => '一般關鍵字搜尋結果',
                    'ai_status' => $clothing->ai_status,
                ];
            });
    }

    /**
     * 將 Clothing Model 轉成現有 Blade 需要的 array 格式。
     *
     * @return array<string, mixed>
     */
    private function toViewItem(Clothing $clothing): array
    {
        $analysis = null;

        if ($clothing->ai_status !== 'pending') {
            $analysis = [
                'subcategory' => $clothing->subcategory ?? '未分類',
                'season' => $clothing->season ?? [],
                'occasion' => $clothing->occasion ?? [],
                'usage' => $clothing->usage ?? [],
                'style_tags' => $clothing->style_tags ?? [],
            ];
        }

        return [
            'id' => $clothing->id,
            'name' => $clothing->name,
            'category' => $clothing->category ?? '未分類',
            'color' => $clothing->color ?? '未知顏色',
            'image' => $clothing->display_image_url ?? asset('images/placeholder-clothing.png'),
            'ai_status' => $clothing->ai_status ?? 'pending',
            'ai_mode' => $clothing->ai_mode ?? 'mock',
            'analysis' => $analysis,
        ];
    }
}