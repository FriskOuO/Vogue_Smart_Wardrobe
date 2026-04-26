<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class WorkspaceController extends Controller
{
    public function show(string $module): View
    {
        $modules = $this->modules();

        abort_unless(isset($modules[$module]), 404);

        $current = $modules[$module];

        return view('workspace.show', [
            'module' => $current,
            'modules' => $modules,
        ]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function modules(): array
    {
        return [
            'ai-stylist' => [
                'slug' => 'ai-stylist',
                'title' => 'AI Stylist Workspace',
                'summary' => '情境穿搭推薦工作台，對接 recommend 流程與搭配結果儲存。',
                'primaryAction' => '產生穿搭建議',
                'api' => '/api/stylist/recommend',
                'status' => 'pending',
                'fields' => ['場合', '天氣', '風格偏好'],
            ],
            'virtual-try-on' => [
                'slug' => 'virtual-try-on',
                'title' => 'Virtual Try-On Workspace',
                'summary' => '人物照片與衣物圖片輸入頁，後續對接 try-on 與姿態分析。',
                'primaryAction' => '執行 Try-On',
                'api' => '/api/tryon/generate',
                'status' => 'degraded/mock',
                'fields' => ['人物照片', '衣物圖片', '角度設定'],
            ],
            'community' => [
                'slug' => 'community',
                'title' => 'Community Workspace',
                'summary' => '貼文、按讚、評論工作台，預留社群 API 對接欄位。',
                'primaryAction' => '發布貼文',
                'api' => '/api/community/posts',
                'status' => 'pending',
                'fields' => ['貼文內容', '圖片', '標籤'],
            ],
            'blind-box' => [
                'slug' => 'blind-box',
                'title' => 'Blind Box Workspace',
                'summary' => '盲盒穿搭前端流程，顯示隨機穿搭結果與收藏入口。',
                'primaryAction' => '抽取盲盒',
                'api' => '/api/blindbox/generate',
                'status' => 'degraded/mock',
                'fields' => ['偏好風格', '場景', '限制條件'],
            ],
            'runway-video' => [
                'slug' => 'runway-video',
                'title' => 'Runway Video Workspace',
                'summary' => '走秀影片生成流程頁，預留影片任務 queue 狀態顯示。',
                'primaryAction' => '生成 Runway Video',
                'api' => '/api/video/generate',
                'status' => 'pending',
                'fields' => ['穿搭圖', '影片風格', '鏡頭節奏'],
            ],
            'chat-assistant' => [
                'slug' => 'chat-assistant',
                'title' => 'Chat Assistant Workspace',
                'summary' => 'AI 對話與穿搭問答頁面，保留 prompt / context 設定。',
                'primaryAction' => '送出問題',
                'api' => '/api/gemini/visual-stylist-call',
                'status' => 'pending',
                'fields' => ['使用者問題', '上下文衣櫥', '語氣模式'],
            ],
            'showcase' => [
                'slug' => 'showcase',
                'title' => 'Showcase Workspace',
                'summary' => '商家商品展示與一鍵入庫界面，先做前台卡片與篩選。',
                'primaryAction' => '加入衣櫥',
                'api' => '/api/import/confirm',
                'status' => 'pending',
                'fields' => ['品牌', '品類', '價格區間'],
            ],
            'digital-twin' => [
                'slug' => 'digital-twin',
                'title' => 'Digital Twin Workspace',
                'summary' => '3D 多視角生成流程頁，預留任務狀態與圖像牆。',
                'primaryAction' => '生成多視角',
                'api' => '/api/digital-twin/generate-all',
                'status' => 'degraded/mock',
                'fields' => ['身高', '體重', '風格提示詞'],
            ],
            'travel-packer' => [
                'slug' => 'travel-packer',
                'title' => 'Travel Packer Workspace',
                'summary' => '旅行打包清單生成與天氣資料輸入頁。',
                'primaryAction' => '產生打包清單',
                'api' => '/api/travel/packing-list',
                'status' => 'pending',
                'fields' => ['目的地', '天數', '活動型態'],
            ],
            'smart-storage' => [
                'slug' => 'smart-storage',
                'title' => 'Smart Storage Workspace',
                'summary' => '收納箱與衣物位置管理頁，對接 storage 相關 API。',
                'primaryAction' => '新增收納箱',
                'api' => '/api/storage/boxes',
                'status' => 'pending',
                'fields' => ['收納箱名稱', '區域', '分類標籤'],
            ],
            'quick-snap' => [
                'slug' => 'quick-snap',
                'title' => 'Quick Snap Workspace',
                'summary' => '快速拍照入庫流程與即時預覽。',
                'primaryAction' => '拍照入庫',
                'api' => '/api/import/scan',
                'status' => 'pending',
                'fields' => ['相機來源', '衣物名稱', '備註'],
            ],
            'smart-tag' => [
                'slug' => 'smart-tag',
                'title' => 'Smart Tag Workspace',
                'summary' => '吊牌/發票掃描辨識工作台。',
                'primaryAction' => '掃描辨識',
                'api' => '/api/import/scan',
                'status' => 'degraded/mock',
                'fields' => ['圖片', 'OCR 語言', '品牌線索'],
            ],
            'magic-mirror' => [
                'slug' => 'magic-mirror',
                'title' => 'Magic Mirror Workspace',
                'summary' => '姿態與體態分析入口，展示分析結果與建議。',
                'primaryAction' => '開始分析',
                'api' => '/api/magic-mirror/analyze',
                'status' => 'pending',
                'fields' => ['人物照片', '站姿', '身體關鍵點'],
            ],
            'stylist-call' => [
                'slug' => 'stylist-call',
                'title' => 'AI Bestie Call Workspace',
                'summary' => '視訊風格諮詢工作台，先完成前端流程與狀態設計。',
                'primaryAction' => '啟動通話',
                'api' => '/api/gemini/visual-stylist-call',
                'status' => 'pending',
                'fields' => ['通話主題', '語音輸入', '語言偏好'],
            ],
        ];
    }
}
