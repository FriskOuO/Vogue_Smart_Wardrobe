<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class FeatureController extends Controller
{
    /**
     * Display a feature module page.
     */
    public function show(Request $request, string $feature): View
    {
        $modules = $this->modules();

        if (! isset($modules[$feature])) {
            abort(404);
        }

        $locale = app()->getLocale() === 'zh_TW' ? 'zh' : 'en';
        $current = $modules[$feature];

        return view('features.show', [
            'modules' => array_values($modules),
            'current' => $current,
            'localeKey' => $locale,
        ]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function modules(): array
    {
        return [
            'smart-closet' => [
                'slug' => 'smart-closet',
                'title' => 'Smart Closet',
                'summary' => [
                    'zh' => '你的智慧衣櫥中心：負責分類、標籤、查詢與穿著紀錄。',
                    'en' => 'Your intelligent wardrobe center for classification, tagging, search, and wear records.',
                ],
                'capabilities' => [
                    'zh' => ['自動標籤', '多維分類', 'CLIP 圖文搜尋', '衣物 CRUD'],
                    'en' => ['Auto tagging', 'Multi-dimensional categorization', 'CLIP multimodal search', 'Garment CRUD'],
                ],
            ],
            'ai-stylist' => [
                'slug' => 'ai-stylist',
                'title' => 'AI Stylist',
                'summary' => [
                    'zh' => '依照場合、天氣與個人偏好，生成可落地的穿搭建議。',
                    'en' => 'Generate practical outfit recommendations by context, weather, and personal preference.',
                ],
                'capabilities' => [
                    'zh' => ['情境推薦', '風格學習', '造型預覽', '收藏推薦'],
                    'en' => ['Context-based recommendation', 'Style learning', 'Look preview', 'Saved recommendations'],
                ],
            ],
            'virtual-try-on' => [
                'slug' => 'virtual-try-on',
                'title' => 'Virtual Try-On',
                'summary' => [
                    'zh' => '在不實際更衣的情況下快速試穿，縮短決策時間。',
                    'en' => 'Try outfits digitally without changing clothes and speed up decision making.',
                ],
                'capabilities' => [
                    'zh' => ['AI 換裝', '即時預覽', '分享輸出'],
                    'en' => ['AI outfit transfer', 'Real-time preview', 'Shareable output'],
                ],
            ],
            'digital-twin' => [
                'slug' => 'digital-twin',
                'title' => 'Digital Twin',
                'summary' => [
                    'zh' => '建立可重複利用的虛擬分身，支援不同角度展示。',
                    'en' => 'Build a reusable digital avatar for multi-angle presentation.',
                ],
                'capabilities' => [
                    'zh' => ['多視角生成', '3D 視覺化', 'GLB 匯出'],
                    'en' => ['Multi-view generation', '3D visualization', 'GLB export'],
                ],
            ],
            'blind-box' => [
                'slug' => 'blind-box',
                'title' => 'Blind Box',
                'summary' => [
                    'zh' => '用探索式推薦打破慣性穿搭，發現新的個人風格。',
                    'en' => 'Break styling habits with exploratory recommendations and discover new looks.',
                ],
                'capabilities' => [
                    'zh' => ['隨機穿搭', '風格探索', '一鍵收藏'],
                    'en' => ['Random outfit generation', 'Style exploration', 'One-click save'],
                ],
            ],
            'runway-video' => [
                'slug' => 'runway-video',
                'title' => 'Runway Video',
                'summary' => [
                    'zh' => '把穿搭轉成走秀影片，快速做內容展示與社群傳播。',
                    'en' => 'Turn outfits into runway videos for content showcase and social distribution.',
                ],
                'capabilities' => [
                    'zh' => ['走秀動畫', 'Veo 串接', '高品質匯出'],
                    'en' => ['Runway animation', 'Veo integration', 'High-quality export'],
                ],
            ],
            'community' => [
                'slug' => 'community',
                'title' => 'Community',
                'summary' => [
                    'zh' => '建立用戶互動與內容反饋循環，提升平台黏著。',
                    'en' => 'Build interaction and feedback loops to increase platform engagement.',
                ],
                'capabilities' => [
                    'zh' => ['貼文互動', '追蹤機制', '即時動態'],
                    'en' => ['Post interactions', 'Follow system', 'Real-time feed'],
                ],
            ],
            'trend-report' => [
                'slug' => 'trend-report',
                'title' => 'Trend Report',
                'summary' => [
                    'zh' => '彙整使用與內容訊號，產出可行的時尚趨勢洞察。',
                    'en' => 'Aggregate usage and content signals to produce actionable fashion insights.',
                ],
                'capabilities' => [
                    'zh' => ['趨勢分析', '熱門標籤', '週報輸出'],
                    'en' => ['Trend analysis', 'Hot tags', 'Weekly reports'],
                ],
            ],
            'chat-assistant' => [
                'slug' => 'chat-assistant',
                'title' => 'Chat Assistant',
                'summary' => [
                    'zh' => '透過對話式助理提供即時穿搭與單品建議。',
                    'en' => 'Provide instant styling and item suggestions via conversational assistant.',
                ],
                'capabilities' => [
                    'zh' => ['自然語言問答', '個人化建議', '上下文記憶'],
                    'en' => ['Natural language Q&A', 'Personalized advice', 'Context memory'],
                ],
            ],
            'showcase' => [
                'slug' => 'showcase',
                'title' => 'Showcase',
                'summary' => [
                    'zh' => '提供品牌與商家展示頁，並串接衣櫥匯入流程。',
                    'en' => 'Provide merchant showcase pages and connect to wardrobe import workflows.',
                ],
                'capabilities' => [
                    'zh' => ['商品展示', '分類篩選', '一鍵入庫'],
                    'en' => ['Product showcase', 'Category filters', 'One-click import'],
                ],
            ],
            'user-system' => [
                'slug' => 'user-system',
                'title' => 'User System',
                'summary' => [
                    'zh' => '管理帳號、偏好與安全策略，作為平台基礎能力。',
                    'en' => 'Manage accounts, preferences, and security as the platform foundation.',
                ],
                'capabilities' => [
                    'zh' => ['角色權限', '偏好設定', '隱私安全'],
                    'en' => ['Role permissions', 'Preference settings', 'Privacy and security'],
                ],
            ],
        ];
    }
}
