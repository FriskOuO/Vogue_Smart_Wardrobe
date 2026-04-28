<?php

namespace App\Http\Controllers;

use App\Models\AiEmbedding;
use App\Models\Clothing;
use App\Services\AiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

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
        return view('closet.stylist', [
            'looks' => [
                [
                    'title' => 'City Smart Casual',
                    'items' => ['Linen Resort Shirt', 'Ash Wide Denim', 'Black Structured Blazer'],
                    'status' => 'degraded/mock',
                ],
                [
                    'title' => 'Weekend Clean Fit',
                    'items' => ['Soft Knit Dress', 'Light Cardigan', 'Minimal Sneakers'],
                    'status' => 'pending',
                ],
            ],
        ]);
    }

    public function tryOn(): View
    {
        return view('closet.tryon', [
            'poseJobs' => [
                ['id' => 'POSE-2401', 'status' => 'success', 'mode' => 'mock'],
                ['id' => 'POSE-2402', 'status' => 'pending', 'mode' => 'mock'],
            ],
        ]);
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