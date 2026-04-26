<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClosetController extends Controller
{
    public function hub(): View
    {
        return view('closet.hub', [
            'quickStats' => [
                ['label' => '衣物總數', 'value' => '48'],
                ['label' => '待分析', 'value' => '6'],
                ['label' => 'Mock/Degraded', 'value' => '4'],
                ['label' => '本週新增', 'value' => '9'],
            ],
        ]);
    }

    public function index(Request $request): View
    {
        $query = trim((string) $request->string('q', ''));
        $items = collect($this->closetItems());

        if ($query !== '') {
            $items = $items->filter(function (array $item) use ($query): bool {
                return str_contains(strtolower($item['name']), strtolower($query))
                    || str_contains(strtolower($item['category']), strtolower($query))
                    || str_contains(strtolower($item['color']), strtolower($query));
            })->values();
        }

        return view('closet.index', [
            'items' => $items,
            'query' => $query,
        ]);
    }

    public function create(): View
    {
        return view('closet.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'image' => ['nullable', 'image', 'max:5120'],
            'name' => ['required', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        return redirect()->route('closet.index')->with('status', '衣物已加入暫存清單（示範模式，尚未寫入資料庫）。');
    }

    public function show(int $id): View
    {
        $item = collect($this->closetItems())->firstWhere('id', $id);

        abort_if(! $item, 404);

        return view('closet.show', [
            'item' => $item,
        ]);
    }

    public function search(): View
    {
        return view('closet.search', [
            'results' => [
                ['name' => 'Cloud Linen Shirt', 'score' => 0.93, 'type' => 'image'],
                ['name' => 'Monotone Layered Coat', 'score' => 0.87, 'type' => 'text'],
                ['name' => 'Urban Soft Knit', 'score' => 0.84, 'type' => 'image'],
            ],
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

    /**
     * @return array<int, array<string, mixed>>
     */
    private function closetItems(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Linen Resort Shirt',
                'category' => '上衣',
                'color' => '米白',
                'image' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=900&q=80',
                'ai_status' => 'success',
                'ai_mode' => 'live',
                'analysis' => [
                    'subcategory' => '襯衫',
                    'season' => ['春', '夏'],
                    'occasion' => ['日常', '旅遊'],
                    'usage' => ['通勤', '休閒'],
                    'style_tags' => ['Minimal', 'Resort', 'Clean'],
                ],
            ],
            [
                'id' => 2,
                'name' => 'Ash Wide Denim',
                'category' => '下身',
                'color' => '灰藍',
                'image' => 'https://images.unsplash.com/photo-1541099649105-f69ad21f3246?auto=format&fit=crop&w=900&q=80',
                'ai_status' => 'degraded',
                'ai_mode' => 'mock',
                'analysis' => [
                    'subcategory' => '牛仔褲',
                    'season' => ['四季'],
                    'occasion' => ['日常', '校園'],
                    'usage' => ['休閒'],
                    'style_tags' => ['Street', 'Relaxed'],
                ],
            ],
            [
                'id' => 3,
                'name' => 'Black Structured Blazer',
                'category' => '外套',
                'color' => '黑色',
                'image' => 'https://images.unsplash.com/photo-1592878904946-b3cd3f1f8455?auto=format&fit=crop&w=900&q=80',
                'ai_status' => 'pending',
                'ai_mode' => 'mock',
                'analysis' => null,
            ],
            [
                'id' => 4,
                'name' => 'Soft Knit Dress',
                'category' => '洋裝',
                'color' => '藕粉',
                'image' => 'https://images.unsplash.com/photo-1495385794356-15371f348c31?auto=format&fit=crop&w=900&q=80',
                'ai_status' => 'success',
                'ai_mode' => 'live',
                'analysis' => [
                    'subcategory' => '針織洋裝',
                    'season' => ['秋', '冬'],
                    'occasion' => ['約會', '聚會'],
                    'usage' => ['外出'],
                    'style_tags' => ['Soft', 'Feminine', 'Elegant'],
                ],
            ],
        ];
    }
}
