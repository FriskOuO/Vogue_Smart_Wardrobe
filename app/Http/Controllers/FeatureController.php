<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class FeatureController extends Controller
{
    public function show(Request $request, string $feature): View
    {
        $modules = $this->modules();

        if (! isset($modules[$feature])) {
            abort(404);
        }

        return view('features.show', [
            'modules' => array_values($modules),
            'current' => $modules[$feature],
            'localeKey' => 'zh',
        ]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function modules(): array
    {
        return [
            'smart-closet' => $this->module('smart-closet', '智慧衣櫥', '衣物上傳、分類、搜尋、穿著紀錄與 AI 分析的核心資料入口。', ['上傳與管理衣物', 'AI 屬性分析', '文字與備援搜尋', '穿著紀錄']),
            'ai-stylist' => $this->module('ai-stylist', 'AI 穿搭顧問', '依照場合、天氣、偏好與衣櫥資料產生可保存的穿搭建議。', ['情境式推薦', '回饋紀錄', '穿搭保存', '數位分身資料補強']),
            'virtual-try-on' => $this->module('virtual-try-on', '虛擬試穿', '透過人物照片建立姿態分析任務，作為後續真實試穿模型的前置流程。', ['姿態任務', '品質檢查', '試穿前置資料', '任務紀錄']),
            'digital-twin' => $this->module('digital-twin', '數位分身', '建立個人風格資料與衣櫥型分析，支援推薦與展示流程。', ['個人風格卡', '衣櫥統計', '風格摘要', '推薦資料來源']),
            'blind-box' => $this->module('blind-box', '穿搭盲盒', '用隨機與條件組合探索不同穿搭，增加展示與互動感。', ['隨機穿搭', '風格探索', '一鍵保存']),
            'runway-video' => $this->module('runway-video', '伸展台影片', '先建立分鏡與提示詞，後續可串接真實影片生成服務。', ['分鏡場景', '影片提示詞', '預覽狀態', '生成服務串接']),
            'community' => $this->module('community', '社群', '保留貼文、互動與分享流程，讓平台具備展示與回饋循環。', ['貼文互動', '追蹤系統', '動態牆']),
            'trend-report' => $this->module('trend-report', '趨勢報告', '彙整衣櫥與社群訊號，形成可展示的流行趨勢分析。', ['趨勢分析', '熱門標籤', '週報輸出']),
            'chat-assistant' => $this->module('chat-assistant', '聊天助理', '以對話方式提供穿搭詢問、衣物推薦與情境建議。', ['自然語言問答', '個人化建議', '情境記憶']),
            'showcase' => $this->module('showcase', '展示牆', '保留商品展示與一鍵匯入衣櫥的延伸入口。', ['商品展示', '分類篩選', '一鍵匯入']),
            'user-system' => $this->module('user-system', '使用者系統', '管理帳號、角色、偏好與安全設定，作為平台基礎。', ['角色權限', '偏好設定', '隱私與安全']),
        ];
    }

    /**
     * @param  array<int, string>  $capabilities
     * @return array<string, mixed>
     */
    private function module(string $slug, string $title, string $summary, array $capabilities): array
    {
        return [
            'slug' => $slug,
            'title' => $title,
            'summary' => [
                'zh' => $summary,
                'en' => $summary,
            ],
            'capabilities' => [
                'zh' => $capabilities,
                'en' => $capabilities,
            ],
        ];
    }
}
