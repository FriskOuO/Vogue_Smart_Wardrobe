<?php

namespace App\Http\Controllers;

use App\Models\AiJob;
use App\Models\AiEmbedding;
use App\Models\Clothing;
use App\Models\OutfitLog;
use App\Models\WearLog;
use App\Services\AiService;
use App\Services\ExternalModelProviderService;
use App\Services\StylistTextGenerationService;
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
            'manualQaItems' => [
                [
                    'title' => 'AI 搜尋真實模型',
                    'href' => route('closet.search', ['provider_mode' => 'real', 'q' => 'white shirt']),
                    'chip' => 'Qdrant / CLIP',
                    'expected' => '確認畫面顯示「真實搜尋可人工驗收」、qdrant、clip-vit-base-patch32、fallback 未啟用。',
                    'status' => 'ready',
                ],
                [
                    'title' => 'AI 穿搭顧問',
                    'href' => route('closet.stylist'),
                    'chip' => 'Gemini',
                    'expected' => '選「真實模型」送出，確認最新推薦紀錄顯示 real_adapter / ready 與 fallback_active: false。',
                    'status' => 'ready',
                ],
                [
                    'title' => '試穿 / 姿態',
                    'href' => route('closet.tryon'),
                    'chip' => 'Try-on L1',
                    'expected' => '建立新任務，確認最新紀錄顯示「最新任務可人工驗收」、姿態品質與品質檢查。',
                    'status' => 'ready',
                ],
                [
                    'title' => '伸展台影片',
                    'href' => route('workspace.show', 'runway-video'),
                    'chip' => 'Runway L2',
                    'expected' => '建立 Storyboard，確認最新任務顯示「最新伸展台任務可人工驗收」與預覽狀態。',
                    'status' => 'ready',
                ],
                [
                    'title' => '數位分身',
                    'href' => route('workspace.show', 'digital-twin'),
                    'chip' => 'Digital Twin L2',
                    'expected' => '建立 L1 profile 或 L2 衣櫥分析，確認最新任務顯示「最新數位分身任務可人工驗收」。',
                    'status' => 'ready',
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
            'wearLogs' => $clothing->wearLogs()
                ->latest('worn_at')
                ->limit(5)
                ->get()
                ->map(function (WearLog $wearLog) {
                    return [
                        'id' => $wearLog->id,
                        'worn_at' => optional($wearLog->worn_at)->format('Y-m-d H:i'),
                        'context' => $wearLog->context,
                        'source' => $wearLog->source,
                        'notes' => $wearLog->notes,
                    ];
                }),
        ]);
    }

    public function storeWearLog(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'worn_at' => ['nullable', 'date'],
            'context' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $clothing = Clothing::where('user_id', auth()->id())
            ->findOrFail($id);
        $wornAt = isset($validated['worn_at']) ? \Carbon\Carbon::parse($validated['worn_at']) : now();

        WearLog::create([
            'user_id' => auth()->id(),
            'clothing_id' => $clothing->id,
            'worn_at' => $wornAt,
            'context' => $validated['context'] ?? null,
            'source' => 'manual',
            'notes' => $validated['notes'] ?? null,
            'metadata' => [
                'source_route' => 'closet.show',
                'clothing_name' => $clothing->name,
            ],
        ]);

        $clothing->increment('wear_count');

        if (! $clothing->last_worn_at || $wornAt->greaterThan($clothing->last_worn_at)) {
            $clothing->forceFill([
                'last_worn_at' => $wornAt,
            ])->save();
        }

        return redirect()
            ->route('closet.show', $clothing->id)
            ->with('status', 'Wear log 已記錄，衣物穿著次數與最後穿著時間已更新。');
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

        if ($this->isAiUsableStatus($aiResult['status'] ?? 'failed')) {
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

        if ($this->isAiUsableStatus($imageEmbeddingResult['status'] ?? 'failed')) {
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
        $providerMode = (string) $request->query('provider_mode', 'demo');
        $providerMode = $providerMode === 'real' ? 'real' : 'demo';
        $useRealProvider = $providerMode === 'real';

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
                'mock_mode' => ! $useRealProvider,
            ]);

            if ($this->isAiUsableStatus($textEmbeddingResult['status'] ?? 'failed')) {
                $similarResult = $aiService->searchSimilar([
                    'user_id' => auth()->id(),
                    'query_type' => 'text',
                    'query' => $query,
                    'embedding' => $textEmbeddingResult['embedding'] ?? [],
                    'top_k' => $topK,
                    'filters' => [],
                    'mock_mode' => ! $useRealProvider,
                ]);

                $aiResult = $similarResult;

                if ($this->isAiUsableStatus($similarResult['status'] ?? 'failed')) {
                    $results = $this->mapSimilarSearchResults($similarResult);

                    $searchMode = $similarResult['search_provider'] ?? 'ai_search';
                    $message = $results->isEmpty()
                        ? 'AI 搜尋有回應，但目前沒有找到對應衣物。'
                        : 'AI 搜尋已完成，以下為語意相似結果。';

                    if ($results->isEmpty()) {
                        $keywordResults = $this->keywordSearch($query);

                        if ($keywordResults->isNotEmpty()) {
                            $results = $keywordResults;
                            $searchMode = ($similarResult['search_provider'] ?? 'ai_search') . '_empty_keyword_fallback';
                            $message = 'AI 搜尋有回應但沒有對應目前使用者衣物，已改用一般關鍵字搜尋。';
                        }
                    }
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
            'providerMode' => $providerMode,
            'results' => $results,
            'searchMode' => $searchMode,
            'message' => $message,
            'aiResult' => $aiResult,
            'searchAcceptance' => $this->buildSearchAcceptance($providerMode, $searchMode, $aiResult, $results, $query),
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
                'context' => $history->context_json ?? [],
                'selected_items' => $history->selected_items ?? [],
                'recommendation' => $history->recommendation_json ?? [],
                'status' => $history->status,
                'mode' => $history->mode,
                'is_accepted' => $history->is_accepted,
                'feedback_status' => $history->feedback_status,
                'feedback_reason' => $history->feedback_reason,
                'feedback_submitted_at' => optional($history->feedback_submitted_at)->format('Y-m-d H:i:s'),
                'outfit_logs_count' => $history->outfitLogs()->count(),
                'database_id' => $history->id,
                'created_at' => optional($history->created_at)->format('Y-m-d H:i:s'),
            ];
        });

    $latestDigitalTwinProfile = $this->latestDigitalTwinStylistContext((int) auth()->id());

    return view('closet.stylist', [
        'clothes' => $clothes,
        'stylistHistories' => $stylistHistories,
        'latestDigitalTwinProfile' => $latestDigitalTwinProfile,
    ]);
}



   public function generateStylist(Request $request, StylistTextGenerationService $stylistTextGeneration): RedirectResponse
{
    $validated = $request->validate([
        'occasion' => ['required', 'string', 'max:120'],
        'weather' => ['nullable', 'string', 'max:120'],
        'style_preference' => ['nullable', 'string', 'max:300'],
        'season_context' => ['nullable', 'string', 'max:80'],
        'formality_level' => ['nullable', 'string', 'max:80'],
        'mood_context' => ['nullable', 'string', 'max:160'],
        'avoid_notes' => ['nullable', 'string', 'max:300'],
        'provider_mode' => ['nullable', 'string', 'in:demo,real'],
    ]);
    $providerMode = ($validated['provider_mode'] ?? 'demo') === 'real' ? 'real' : 'demo';
    $useRealProvider = $providerMode === 'real';

    $clothes = Clothing::where('user_id', auth()->id())
        ->latest()
        ->get();

    if ($clothes->isEmpty()) {
        return redirect()
            ->route('closet.stylist')
            ->with('error', '目前衣櫥尚未有衣物，請先上傳衣物後再產生 AI 穿搭顧問推薦。');
    }

    $occasion = $validated['occasion'];
    $weather = $validated['weather'] ?? '未提供天氣';
    $stylePreference = $validated['style_preference'] ?? '未提供風格偏好';
    $seasonContext = $validated['season_context'] ?? '未提供季節';
    $formalityLevel = $validated['formality_level'] ?? '未提供正式程度';
    $moodContext = $validated['mood_context'] ?? '未提供心情';
    $avoidNotes = $validated['avoid_notes'] ?? '未提供避免事項';
    $stylistContext = [
        'occasion' => $occasion,
        'weather' => $weather,
        'season_context' => $seasonContext,
        'formality_level' => $formalityLevel,
        'mood_context' => $moodContext,
        'style_preference' => $stylePreference,
        'avoid_notes' => $avoidNotes,
        'provider_mode' => $providerMode,
    ];
    $digitalTwinProfile = $this->latestDigitalTwinStylistContext((int) auth()->id());

    $occasionMatched = $clothes->filter(function (Clothing $item) use ($occasion) {
        $occasions = collect($item->occasion ?? [])
            ->map(fn ($value) => mb_strtolower((string) $value));

        return $occasions->contains(fn ($value) => str_contains($value, mb_strtolower($occasion)));
    });

    $seasonMatched = $clothes->filter(function (Clothing $item) use ($weather, $seasonContext) {
        $weatherText = mb_strtolower($weather . ' ' . $seasonContext);

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

    $digitalTwinMatched = $digitalTwinProfile
        ? $clothes->filter(fn (Clothing $item) => $this->itemMatchesDigitalTwinContext($item, $digitalTwinProfile))
        : collect();
    $embeddingMatches = $this->embeddingRankedStylistItems(
        $clothes,
        $stylistContext,
        $digitalTwinProfile,
        (int) auth()->id()
    );
    $embeddingMatchedItems = $embeddingMatches->pluck('item');

    $candidateItems = $occasionMatched
        ->merge($seasonMatched)
        ->merge($styleMatched)
        ->merge($digitalTwinMatched)
        ->merge($embeddingMatchedItems)
        ->unique('id')
        ->values();

    if ($avoidNotes !== '未提供避免事項') {
        $filteredItems = $candidateItems
            ->reject(fn (Clothing $item) => $this->itemMatchesAvoidNotes($item, $avoidNotes))
            ->values();

        if ($filteredItems->isNotEmpty()) {
            $candidateItems = $filteredItems;
        }
    }

    if ($candidateItems->count() < 2) {
        $candidateItems = $candidateItems
            ->merge($clothes)
            ->unique('id')
            ->values();
    }

    if ($avoidNotes !== '未提供避免事項') {
        $filteredItems = $candidateItems
            ->reject(fn (Clothing $item) => $this->itemMatchesAvoidNotes($item, $avoidNotes))
            ->values();

        if ($filteredItems->isNotEmpty()) {
            $candidateItems = $filteredItems;
        }
    }

    $embeddingSignalById = $embeddingMatches->keyBy(fn (array $match) => $match['item']->id);

    if ($embeddingSignalById->isNotEmpty()) {
        $candidateItems = $candidateItems
            ->sortByDesc(fn (Clothing $item) => $embeddingSignalById->get($item->id)['score'] ?? -1)
            ->values();
    }

    $selectedItems = $candidateItems
        ->take(3)
        ->map(function (Clothing $item) use ($embeddingSignalById) {
            $embeddingSignal = $embeddingSignalById->get($item->id);

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
                'embedding_score' => $embeddingSignal['score'] ?? null,
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

    $reasoning = [
        '根據你輸入的場合、天氣與風格偏好，系統會優先挑選 occasion、season、style_tags 較接近的衣物。',
        sprintf(
            '本次情境包含季節「%s」、正式程度「%s」、心情/氛圍「%s」、避免事項「%s」。',
            $seasonContext,
            $formalityLevel,
            $moodContext,
            $avoidNotes
        ),
        '目前為 L1.5 / L2 基礎規則式推薦，已經會讀取你的 clothes 資料表，不只是固定假資料。',
    ];

    if ($digitalTwinProfile) {
        $reasoning[] = sprintf(
            '已串接數位分身 L2 衣櫥資料：主要品類 %s、主要顏色 %s、主要風格 %s，並用它補強候選衣物排序。',
            $digitalTwinProfile['dominant_category'] ?? 'unknown',
            $digitalTwinProfile['dominant_color'] ?? 'unknown',
            $digitalTwinProfile['dominant_style'] ?? 'unknown'
        );
    } else {
        $reasoning[] = '尚未找到數位分身 L2 衣櫥資料；可先到數位分身工作區執行衣櫥分析，讓推薦更個人化。';
    }

    $reasoning[] = '後續可接 Gemini、CLIP embedding、數位分身資料與穿搭歷史，讓推薦更個人化。';

    if ($embeddingMatches->isNotEmpty()) {
        $reasoning[] = '已使用 ai_embeddings image vectors 以 local cosine similarity 補強候選排序。';
    }

    $embeddingSignals = [
        'mode' => 'local_cosine',
        'context_vector_dimension' => 8,
        'top_matches' => $embeddingMatches
            ->take(5)
            ->map(function (array $match) {
                return [
                    'clothing_id' => $match['item']->id,
                    'name' => $match['item']->name,
                    'score' => $match['score'],
                    'embedding_id' => $match['embedding_id'],
                    'provider' => $match['provider'],
                ];
            })
            ->values()
            ->all(),
    ];

    $generatedText = $stylistTextGeneration->generate([
        'context' => $stylistContext,
        'selected_items' => $selectedItems,
        'digital_twin_profile' => $digitalTwinProfile,
        'embedding_signals' => $embeddingSignals,
        'mock_mode' => ! $useRealProvider,
    ]);
    $textGeneration = $generatedText['text_generation'];
    $historyStatus = ($textGeneration['status'] ?? null) === 'ready' ? 'ready' : 'degraded';
    $historyMode = ($textGeneration['mode'] ?? null) === 'real_adapter' ? 'real_adapter' : 'rule_based';
    if ($historyStatus === 'ready') {
        $reasoning[] = 'Gemini text generation adapter 已使用真實模型產生 structured styling response。';
    } elseif ($useRealProvider) {
        $reasoning[] = 'Gemini text generation adapter 已執行真實模型嘗試；若缺少 API key 或 API 不可用，會保留 rule_based fallback。';
    } else {
        $reasoning[] = 'Gemini text generation adapter 已規劃完成；外部 provider 不可用時會使用安全備援文字生成契約回填。';
    }

    $recommendation = [
        'title' => $generatedText['title'],
        'summary' => $generatedText['summary'],
        'context' => $stylistContext,
        'digital_twin_profile' => $digitalTwinProfile,
        'embedding_signals' => $embeddingSignals,
        'text_generation' => $generatedText['text_generation'],
        'reasoning' => $reasoning,
        'main_colors' => $mainColors,
        'styling_tips' => $generatedText['styling_tips'],
        'degraded_reason' => $historyStatus === 'ready'
            ? null
            : ($textGeneration['degraded_reason'] ?? 'AI_STYLIST_RULE_BASED_MODE'),
    ];

    StylistHistory::create([
        'user_id' => auth()->id(),
        'occasion' => $occasion,
        'weather' => $weather,
        'style_preference' => $stylePreference,
        'context_json' => $stylistContext,
        'selected_items' => $selectedItems,
        'recommendation_json' => $recommendation,
        'status' => $historyStatus,
        'mode' => $historyMode,
        'is_accepted' => false,
    ]);

    $statusMessage = match (true) {
        $historyStatus === 'ready' => 'AI 穿搭顧問已使用 Gemini 真實模型產生建議，最新紀錄已標示 ready / real_adapter。',
        $useRealProvider => 'AI 穿搭顧問已嘗試 Gemini 真實模型，但目前使用安全 fallback；請查看最新紀錄的錯誤碼。',
        default => 'AI 穿搭顧問已根據你的衣櫥資料產生安全備援穿搭建議，目前為規則式備援狀態。',
    };

    return redirect()
        ->route('closet.stylist')
        ->with('status', $statusMessage);
}

public function updateStylistFeedback(Request $request, int $history): RedirectResponse
{
    $validated = $request->validate([
        'feedback_status' => ['required', 'string', 'in:liked,rejected'],
        'feedback_reason' => ['nullable', 'string', 'max:500'],
    ]);

    $stylistHistory = StylistHistory::where('user_id', auth()->id())
        ->findOrFail($history);

    $feedbackStatus = $validated['feedback_status'];
    $feedbackReason = $validated['feedback_reason'] ?? null;

    $stylistHistory->update([
        'is_accepted' => $feedbackStatus === 'liked',
        'feedback_status' => $feedbackStatus,
        'feedback_reason' => $feedbackStatus === 'rejected' ? $feedbackReason : null,
        'feedback_json' => [
            'source' => 'ai_stylist_feedback',
            'status' => $feedbackStatus,
            'reason' => $feedbackStatus === 'rejected' ? $feedbackReason : null,
            'submitted_from' => 'closet.stylist',
        ],
        'feedback_submitted_at' => now(),
    ]);

    return redirect()
        ->route('closet.stylist')
        ->with('status', '已保存這次 AI 穿搭顧問推薦回饋。');
}

public function storeStylistOutfitLog(Request $request, int $history): RedirectResponse
{
    $validated = $request->validate([
        'name' => ['nullable', 'string', 'max:120'],
        'logged_at' => ['nullable', 'date'],
        'notes' => ['nullable', 'string', 'max:500'],
    ]);

    $stylistHistory = StylistHistory::where('user_id', auth()->id())
        ->findOrFail($history);
    $selectedItems = collect($stylistHistory->selected_items ?? [])
        ->filter(fn ($item) => is_array($item))
        ->values();

    if ($selectedItems->isEmpty()) {
        return redirect()
            ->route('closet.stylist')
            ->with('error', '這筆 AI 穿搭顧問推薦沒有可保存的穿搭單品。');
    }

    $recommendation = $stylistHistory->recommendation_json ?? [];
    $loggedAt = isset($validated['logged_at']) ? \Carbon\Carbon::parse($validated['logged_at']) : now();
    $name = trim((string) ($validated['name'] ?? ''));

    if ($name === '') {
        $name = (string) ($recommendation['title'] ?? ($stylistHistory->occasion . ' outfit'));
    }

    OutfitLog::create([
        'user_id' => auth()->id(),
        'stylist_history_id' => $stylistHistory->id,
        'name' => $name,
        'logged_at' => $loggedAt,
        'occasion' => $stylistHistory->occasion,
        'weather' => $stylistHistory->weather,
        'source' => 'ai_stylist',
        'selected_items' => $selectedItems->all(),
        'item_ids' => $selectedItems->pluck('id')->filter()->values()->all(),
        'item_count' => $selectedItems->count(),
        'context_json' => $stylistHistory->context_json ?? [],
        'notes' => $validated['notes'] ?? null,
        'metadata' => [
            'source_route' => 'closet.stylist',
            'stylist_history_code' => 'STYLE-' . str_pad((string) $stylistHistory->id, 4, '0', STR_PAD_LEFT),
            'recommendation_mode' => $stylistHistory->mode,
            'recommendation_status' => $stylistHistory->status,
        ],
    ]);

    return redirect()
        ->route('closet.stylist')
        ->with('status', '穿搭紀錄已保存，這套 AI 穿搭顧問推薦之後可用於個人化學習。');
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

private function latestDigitalTwinStylistContext(int $userId): ?array
{
    $job = AiJob::where('user_id', $userId)
        ->where('job_type', 'digital_twin_style_analysis')
        ->whereIn('status', $this->aiUsableStatuses())
        ->latest()
        ->first();

    if (! $job || ! is_array($job->result_json)) {
        return null;
    }

    $result = $job->result_json;
    $profile = $result['profile'] ?? [];
    $styleSummary = $result['style_summary'] ?? [];
    $statistics = $result['closet_statistics'] ?? [];

    return [
        'source' => 'digital_twin_style_analysis',
        'source_job_id' => 'TWIN-' . str_pad((string) $job->id, 4, '0', STR_PAD_LEFT),
        'total_items' => $profile['total_items'] ?? null,
        'dominant_category' => $profile['dominant_category'] ?? null,
        'dominant_color' => $profile['dominant_color'] ?? null,
        'dominant_occasion' => $profile['dominant_occasion'] ?? null,
        'dominant_style' => $profile['dominant_style'] ?? null,
        'headline' => $styleSummary['headline'] ?? null,
        'top_categories' => array_slice($statistics['top_categories'] ?? [], 0, 3),
        'top_colors' => array_slice($statistics['top_colors'] ?? [], 0, 3),
        'top_style_tags' => array_slice($statistics['top_style_tags'] ?? [], 0, 3),
    ];
}

private function itemMatchesDigitalTwinContext(Clothing $item, array $context): bool
{
    $category = mb_strtolower((string) ($item->category ?? ''));
    $color = mb_strtolower((string) ($item->color ?? ''));
    $styleTags = collect($item->style_tags ?? [])
        ->map(fn ($value) => mb_strtolower((string) $value));

    $dominantCategory = mb_strtolower((string) ($context['dominant_category'] ?? ''));
    $dominantColor = mb_strtolower((string) ($context['dominant_color'] ?? ''));
    $dominantStyle = mb_strtolower((string) ($context['dominant_style'] ?? ''));

    if ($dominantCategory !== '' && ($category === $dominantCategory || str_contains($category, $dominantCategory) || str_contains($dominantCategory, $category))) {
        return true;
    }

    if ($dominantColor !== '' && ($color === $dominantColor || str_contains($color, $dominantColor) || str_contains($dominantColor, $color))) {
        return true;
    }

    if ($dominantStyle !== '') {
        return $styleTags->contains(function ($tag) use ($dominantStyle) {
            return $tag !== '' && (str_contains($tag, $dominantStyle) || str_contains($dominantStyle, $tag));
        });
    }

    return false;
}

private function itemMatchesAvoidNotes(Clothing $item, string $avoidNotes): bool
{
    $avoidText = mb_strtolower($avoidNotes);

    if ($avoidText === '') {
        return false;
    }

    $values = collect([
        $item->name,
        $item->category,
        $item->subcategory,
        $item->color,
    ])
        ->merge($item->style_tags ?? [])
        ->filter()
        ->map(fn ($value) => mb_strtolower((string) $value));

    return $values->contains(function ($value) use ($avoidText) {
        return $value !== '' && (str_contains($avoidText, $value) || str_contains($value, $avoidText));
    });
}

/**
 * @return Collection<int, array{item: Clothing, score: float, embedding_id: int, provider: string}>
 */
private function embeddingRankedStylistItems(Collection $clothes, array $stylistContext, ?array $digitalTwinProfile, int $userId): Collection
{
    $contextVector = $this->buildStylistContextVector($stylistContext, $digitalTwinProfile);
    $clothingIds = $clothes->pluck('id')->filter()->values()->all();

    if (empty($clothingIds)) {
        return collect();
    }

    $embeddings = AiEmbedding::where('user_id', $userId)
        ->where('embedding_type', 'image')
        ->whereIn('clothing_id', $clothingIds)
        ->whereIn('status', $this->aiUsableStatuses())
        ->get()
        ->keyBy('clothing_id');

    return $clothes
        ->map(function (Clothing $item) use ($embeddings, $contextVector) {
            $embedding = $embeddings->get($item->id);

            if (! $embedding || ! is_array($embedding->embedding)) {
                return null;
            }

            $score = $this->cosineSimilarity($contextVector, $embedding->embedding);

            if ($score === null) {
                return null;
            }

            return [
                'item' => $item,
                'score' => round($score, 4),
                'embedding_id' => $embedding->id,
                'provider' => $embedding->vector_provider ?? $embedding->mode ?? 'local',
            ];
        })
        ->filter()
        ->sortByDesc('score')
        ->values();
}

/**
 * @return array<int, float>
 */
private function buildStylistContextVector(array $stylistContext, ?array $digitalTwinProfile): array
{
    $text = mb_strtolower(collect([
        $stylistContext['occasion'] ?? '',
        $stylistContext['weather'] ?? '',
        $stylistContext['season_context'] ?? '',
        $stylistContext['formality_level'] ?? '',
        $stylistContext['mood_context'] ?? '',
        $stylistContext['style_preference'] ?? '',
        $digitalTwinProfile['dominant_category'] ?? '',
        $digitalTwinProfile['dominant_color'] ?? '',
        $digitalTwinProfile['dominant_occasion'] ?? '',
        $digitalTwinProfile['dominant_style'] ?? '',
    ])->filter()->implode(' '));

    $vector = array_fill(0, 8, 0.0);
    $rules = [
        0 => ['formal', 'smart', 'elegant', 'luxury', '正式', '優雅', '俐落'],
        1 => ['casual', 'sport', 'gym', '休閒', '運動'],
        2 => ['cold', 'winter', 'coat', 'layer', '秋冬', '冬', '冷'],
        3 => ['hot', 'summer', 'light', '春夏', '夏', '熱'],
        4 => ['black', 'white', 'minimal', 'monochrome', '黑', '白', '簡約'],
        5 => ['red', 'colorful', 'bright', '紅', '亮色'],
        6 => ['daily', 'campus', 'commute', 'work', '日常', '校園', '通勤'],
        7 => ['evening', 'dinner', 'gallery', 'date', 'party', '晚', '約會', '展覽'],
    ];

    foreach ($rules as $dimension => $terms) {
        foreach ($terms as $term) {
            if (str_contains($text, mb_strtolower($term))) {
                $vector[$dimension] += 1.0;
            }
        }
    }

    if (array_sum($vector) === 0.0) {
        $vector[0] = 1.0;
    }

    return $this->normalizeVector($vector);
}

/**
 * @param array<int, float|int|string> $vector
 * @return array<int, float>
 */
private function normalizeVector(array $vector): array
{
    $numericVector = array_map(fn ($value) => (float) $value, array_values($vector));
    $magnitude = sqrt(array_sum(array_map(fn (float $value) => $value * $value, $numericVector)));

    if ($magnitude <= 0.0) {
        return $numericVector;
    }

    return array_map(fn (float $value) => $value / $magnitude, $numericVector);
}

/**
 * @param array<int, float> $a
 * @param array<int, mixed> $b
 */
private function cosineSimilarity(array $a, array $b): ?float
{
    $b = $this->normalizeVector($b);
    $dimension = min(count($a), count($b));

    if ($dimension === 0) {
        return null;
    }

    $dot = 0.0;

    for ($index = 0; $index < $dimension; $index++) {
        $dot += ((float) $a[$index]) * ((float) $b[$index]);
    }

    return $dot;
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
                'database_id' => $job->id,
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

    public function storeTryOn(
        Request $request,
        AiService $aiService,
        ExternalModelProviderService $externalModelProvider,
    ): RedirectResponse
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

    $poseResult['tryon_provider_attempt'] = $externalModelProvider->generateTryOn([
        'request_id' => $poseResult['request_id'] ?? 'tryon_' . now()->format('YmdHis') . '_' . uniqid(),
        'user_id' => auth()->id(),
        'clothing_id' => $clothing->id,
        'person_image_url' => asset('storage/' . $personPhotoPath),
        'clothing_image_url' => $clothing->display_image_url,
        'pose_analysis' => $poseResult['pose_analysis'] ?? null,
    ]);

    if ($this->isAiUsableStatus($poseResult['status'] ?? 'failed')) {
        $poseQualityStatus = (string) ($poseResult['pose_quality_status']
            ?? data_get($poseResult, 'pose_analysis.pose_quality_status', 'unknown'));
        $poseQualityScore = $poseResult['pose_quality_score']
            ?? data_get($poseResult, 'pose_analysis.pose_quality_score');
        $poseQualityPercent = is_numeric($poseQualityScore)
            ? number_format((float) $poseQualityScore * 100) . '%'
            : 'N/A';

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
            ->with('status', "試穿 L1 姿態任務已完成，可人工驗收：姿態品質 {$poseQualityStatus} / {$poseQualityPercent}。");
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
        ->with('status', '試穿 L1 任務失敗，已記錄錯誤。');
}

    /**
     * @return array<int, string>
     */
    private function aiUsableStatuses(): array
    {
        return ['success', 'ready', 'degraded'];
    }

    private function isAiUsableStatus(?string $status): bool
    {
        return in_array($status, $this->aiUsableStatuses(), true);
    }

    private function applyAttributesResult(Clothing $clothing, array $aiResult): void
    {
        if ($this->isAiUsableStatus($aiResult['status'] ?? 'failed')) {
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
        if ($this->isAiUsableStatus($imageEmbeddingResult['status'] ?? 'failed')) {
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

        $provider = $similarResult['search_provider'] ?? 'ai_search';
        $targetProvider = $similarResult['target_search_provider'] ?? $similarResult['vector_store']['target_provider'] ?? null;
        $vectorStore = $similarResult['vector_store'] ?? [];
        $embeddingProvider = $similarResult['embedding_provider'] ?? [];
        $model = $similarResult['query_model'] ?? $similarResult['model'] ?? 'text_embedding';

        return collect($similarResult['results'] ?? [])
            ->map(function (array $result, int $index) use ($clothes, $provider, $targetProvider, $vectorStore, $embeddingProvider, $model) {
                $clothingId = $result['clothing_id'] ?? null;
                $clothing = $clothingId ? $clothes->get($clothingId) : null;

                if (! $clothing) {
                    return null;
                }

                $score = (float) ($result['score'] ?? 0);

                return [
                    'id' => $clothing->id,
                    'name' => $clothing->name,
                    'image' => $clothing->display_image_url,
                    'category' => $clothing->category ?? '未分類',
                    'color' => $clothing->color ?? '未知顏色',
                    'score' => $score,
                    'type' => 'text',
                    'reason' => $result['reason'] ?? 'AI 語意相似搜尋結果',
                    'ai_status' => $clothing->ai_status,
                    'metadata' => [
                        'rank' => $index + 1,
                        'provider' => $result['vector_provider'] ?? $provider,
                        'target_provider' => $result['target_vector_provider'] ?? $targetProvider,
                        'model' => $result['model'] ?? $model,
                        'match_type' => $result['match_type'] ?? 'vector_similarity',
                        'confidence_label' => $this->similarityConfidenceLabel($score),
                        'score_percent' => (int) round($score * 100),
                        'source' => 'ai_similarity',
                        'vector_store_status' => $vectorStore['status'] ?? null,
                        'vector_store_adapter' => $vectorStore['adapter'] ?? null,
                        'fallback_active' => $vectorStore['fallback_active'] ?? null,
                        'embedding_target_provider' => $embeddingProvider['target_provider'] ?? null,
                        'embedding_adapter' => $embeddingProvider['adapter'] ?? null,
                        'embedding_fallback_active' => $embeddingProvider['fallback_active'] ?? null,
                    ],
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @param Collection<int, array<string, mixed>> $results
     * @return array{status: string, label: string, title: string, message: string, chip_class: string}
     */
    private function buildSearchAcceptance(
        string $providerMode,
        string $searchMode,
        ?array $aiResult,
        Collection $results,
        string $query
    ): array {
        if ($query === '') {
            return [
                'status' => 'idle',
                'label' => '尚未搜尋',
                'title' => '等待人工搜尋',
                'message' => '輸入查詢後，可在這裡確認安全備援或真實模型的搜尋狀態。',
                'chip_class' => 'vogue-chip-pending',
            ];
        }

        if (str_contains($searchMode, 'keyword_fallback')) {
            return [
                'status' => 'fallback',
                'label' => '關鍵字備援',
                'title' => '目前使用關鍵字備援',
                'message' => 'AI 搜尋未取得可用向量結果，頁面已改用一般關鍵字搜尋，衣櫥流程仍可操作。',
                'chip_class' => 'vogue-chip-degraded',
            ];
        }

        $vectorStore = $aiResult['vector_store'] ?? [];
        $embeddingProvider = $aiResult['embedding_provider'] ?? [];
        $searchProvider = (string) ($aiResult['search_provider'] ?? $vectorStore['active_provider'] ?? $searchMode);
        $queryModel = (string) ($aiResult['query_model'] ?? $aiResult['model'] ?? 'text_embedding');
        $vectorFallbackActive = (bool) ($vectorStore['fallback_active'] ?? true);
        $embeddingFallbackActive = (bool) ($embeddingProvider['fallback_active'] ?? true);

        $readyRealSearch = $providerMode === 'real'
            && ($aiResult['status'] ?? null) === 'ready'
            && $results->isNotEmpty()
            && $searchProvider === 'qdrant'
            && $vectorFallbackActive === false
            && $embeddingFallbackActive === false;

        if ($readyRealSearch) {
            return [
                'status' => 'ready',
                'label' => '真實搜尋可人工驗收',
                'title' => '真實搜尋可人工驗收',
                'message' => "已使用 {$queryModel} 產生文字向量，並透過 Qdrant 回傳相似衣物；fallback 未啟用。",
                'chip_class' => 'vogue-chip-success',
            ];
        }

        if (($aiResult['status'] ?? null) === 'ready' && $results->isEmpty()) {
            return [
                'status' => 'empty',
                'label' => 'AI 無對應衣物',
                'title' => 'AI 搜尋完成但無衣物結果',
                'message' => 'AI Service 有回應，但結果沒有對應目前帳號的衣物，請改用更明確的查詢或新增衣物。',
                'chip_class' => 'vogue-chip-pending',
            ];
        }

        return [
            'status' => 'degraded',
            'label' => '搜尋完成 / 展示或備援',
            'title' => '搜尋結果可檢查',
            'message' => '目前結果可用於流程檢查；若要驗收真實搜尋，請切換真實模型並確認 Qdrant、CLIP 與 fallback 未啟用。',
            'chip_class' => 'vogue-chip-degraded',
        ];
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
            ->map(function (Clothing $clothing, int $index) {
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
                    'metadata' => [
                        'rank' => $index + 1,
                        'provider' => 'sql_like',
                        'model' => 'keyword_fallback',
                        'match_type' => 'keyword',
                        'confidence_label' => 'fallback',
                        'score_percent' => 0,
                        'source' => 'sql_keyword',
                    ],
                ];
            });
    }

    private function similarityConfidenceLabel(float $score): string
    {
        if ($score >= 0.85) {
            return 'high';
        }

        if ($score >= 0.6) {
            return 'medium';
        }

        if ($score > 0) {
            return 'low';
        }

        return 'fallback';
    }

    /**
     * 將 Clothing Model 轉成現有 Blade 需要的 array 格式。
     *
     * @return array<string, mixed>
     */
    private function toViewItem(Clothing $clothing): array
    {
        $analysis = null;
        $rawResult = $clothing->ai_raw_result ?? [];
        $imageCaption = is_array($rawResult) ? ($rawResult['image_caption'] ?? null) : null;

        if ($clothing->ai_status !== 'pending') {
            $analysis = [
                'subcategory' => $clothing->subcategory ?? '未分類',
                'season' => $clothing->season ?? [],
                'occasion' => $clothing->occasion ?? [],
                'usage' => $clothing->usage ?? [],
                'style_tags' => $clothing->style_tags ?? [],
                'image_caption' => is_array($imageCaption) ? $imageCaption : null,
            ];
        }

        return [
            'id' => $clothing->id,
            'name' => $clothing->name,
            'category' => $clothing->category ?? '未分類',
            'color' => $clothing->color ?? '未知顏色',
            'image' => $clothing->display_image_url ?? asset('images/placeholder-clothing.png'),
            'wear_count' => (int) ($clothing->wear_count ?? 0),
            'last_worn_at' => optional($clothing->last_worn_at)->format('Y-m-d H:i'),
            'ai_status' => $clothing->ai_status ?? 'pending',
            'ai_mode' => $clothing->ai_mode ?? 'mock',
            'analysis' => $analysis,
        ];
    }
}
