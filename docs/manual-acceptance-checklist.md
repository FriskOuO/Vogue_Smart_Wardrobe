# VogueAI Smart Wardrobe - Manual Acceptance Checklist

## 1. 文件目的

本文件用於 VogueAI Smart Wardrobe 展示前的手動驗收。

目前專案已完成：

```text
Smart Closet 上傳流程
AI attributes 分析
image embedding 寫入
AI Search 以文搜圖 / fallback 搜尋
Try-on L1
Runway Video L1
Digital Twin L1
Python AI Service 工程化
```

本 checklist 目標是確保：

```text
1. Demo 前功能可正常操作
2. Laravel 與 Python AI Service 可正常串接
3. AI Service 不可用時可降級
4. ai_jobs 任務紀錄可正常建立
5. 頁面不會因 mock / degraded 狀態而壞掉
```

---

## 2. Demo 啟動前檢查

### 2.1 環境檢查

```text
[ ] 已進入專案根目錄
[ ] .env 存在
[ ] database/database.sqlite 存在
[ ] storage link 已建立
[ ] composer install 已完成
[ ] npm install 已完成
[ ] ai_service/.venv 已建立
[ ] ai_service/requirements.txt 已安裝
```

---

### 2.2 三個服務啟動

Laravel：

```powershell
php artisan serve
```

檢查：

```text
[ ] Laravel 可開啟 http://127.0.0.1:8000
```

Vite：

```powershell
npm run dev
```

檢查：

```text
[ ] Vite 正常啟動
[ ] 頁面 CSS / Tailwind 正常
```

Python AI Service：

```powershell
cd ai_service
uvicorn main:app --host 127.0.0.1 --port 8001 --reload
```

檢查：

```text
[ ] AI Service 可開啟 http://127.0.0.1:8001/health
[ ] /health 回傳 status=ok
[ ] /health 顯示 dependencies
```

---

## 3. 帳號與權限驗收

### 3.1 登入功能

```text
[ ] 可進入登入頁
[ ] Admin 帳號可登入
[ ] User 帳號可登入
[ ] 登入後可進入 Dashboard
```

測試帳號：

```text
Admin
Email: admin.dev@vogueai.local
Password: Admin@123456

User
Email: demo.user@vogueai.local
Password: User@123456
```

---

### 3.2 Admin 權限

```text
[ ] Admin 可進入 Admin Users
[ ] Admin 可查看使用者列表
[ ] Admin 可搜尋使用者
[ ] User 不可進入 Admin Users
[ ] Admin 不可刪除自己
```

---

## 4. Smart Closet 驗收

### 4.1 Closet Hub

網址：

```text
/ smart-closet
```

或：

```text
http://127.0.0.1:8000/smart-closet
```

檢查：

```text
[ ] Smart Closet Hub 可正常開啟
[ ] 顯示衣物總數
[ ] 顯示待分析數量
[ ] 顯示 Mock / Degraded 數量
[ ] 顯示本週新增
```

---

### 4.2 新增衣物

網址：

```text
/closet/create
```

操作：

```text
1. 上傳衣物圖片
2. 輸入衣物名稱
3. 輸入備註
4. 送出表單
```

檢查：

```text
[ ] 圖片可成功上傳
[ ] clothes 建立成功
[ ] 頁面 redirect 到 closet.show
[ ] 顯示成功訊息
[ ] 圖片可正常顯示
```

---

### 4.3 AI Attributes

新增衣物後檢查：

```text
[ ] Laravel 呼叫 /ai/attributes
[ ] clothes.ai_status = degraded 或 success
[ ] category 有資料
[ ] color 有資料
[ ] season 有資料
[ ] occasion 有資料
[ ] usage 有資料
[ ] style_tags 有資料
[ ] closet.show 顯示 AI 分析結果
```

---

### 4.4 Image Embedding

新增衣物後用 tinker 檢查：

```powershell
php artisan tinker
```

```php
\App\Models\AiEmbedding::latest()->first();
```

檢查：

```text
[ ] ai_embeddings 有新增或更新資料
[ ] embedding_type = image
[ ] status = degraded 或 success
[ ] mode = mock 或 model
[ ] vector_dimension 有資料
[ ] vector_provider 有資料
```

---

### 4.5 Closet Index / Show

網址：

```text
/closet
```

檢查：

```text
[ ] closet.index 可正常開啟
[ ] 可看到資料庫中的衣物
[ ] 可搜尋衣物名稱
[ ] 可搜尋顏色
[ ] 點擊衣物可進入 closet.show
[ ] closet.show 可顯示圖片
[ ] closet.show 可顯示 AI 分析資料
```

---

### 4.6 重新分析 / 重新產生 embedding

在衣物詳細頁操作：

```text
[ ] 點擊「重新分析 AI Attributes」
[ ] 頁面顯示成功或失敗訊息
[ ] clothes.ai_status 有更新
[ ] 點擊「重新產生 Image Embedding」
[ ] 頁面顯示成功或失敗訊息
[ ] ai_embeddings 有更新
[ ] 同一件衣物不應重複新增多筆 image embedding
```

---

## 5. AI Search 驗收

網址：

```text
/closet/ai-search
```

---

### 5.1 正常模式

確認 Python AI Service 有開。

操作：

```text
1. 輸入「白色上衣」
2. 按下 Text Search
```

檢查：

```text
[ ] 頁面不報錯
[ ] 呼叫 /ai/embed/text
[ ] 呼叫 /ai/search/similar
[ ] Search mode 顯示 mock 或 ai_search
[ ] 有搜尋結果時顯示衣物卡片
[ ] 卡片顯示 similarity score
[ ] 可點擊查看衣物
```

---

### 5.2 Fallback 模式

關閉 Python AI Service：

```text
Ctrl + C
```

重新搜尋：

```text
白色
```

檢查：

```text
[ ] 頁面不報錯
[ ] 顯示 keyword_fallback
[ ] 顯示 AI text embedding 暫時不可用相關訊息
[ ] 可用 name / category / color 搜尋資料庫
[ ] 搜尋結果仍可顯示衣物卡片
```

測完後重新啟動 Python AI Service。

---

## 6. Try-on L1 驗收

網址：

```text
/closet/try-on
```

操作：

```text
1. 選擇一件衣物
2. 上傳人物照片
3. 按「建立 Try-on L1 任務」
```

檢查：

```text
[ ] 頁面可正常開啟
[ ] 衣物下拉選單有資料
[ ] 可上傳人物照片
[ ] 送出後建立 ai_jobs
[ ] job_type = pose_analysis
[ ] status = degraded 或 success
[ ] mode = mock
[ ] result_json 有 pose_analysis
[ ] result_json 有 keypoints
[ ] 頁面顯示 posture notes
[ ] 頁面顯示 fit notes
[ ] 頁面顯示 keypoints 數量
```

Tinker 檢查：

```php
\App\Models\AiJob::where('job_type', 'pose_analysis')->latest()->first();
```

---

## 7. Runway Video L1 驗收

網址：

```text
/workspace/runway-video
```

操作：

```text
1. 選擇一件衣物
2. 影片風格輸入 vogue luxury runway
3. 鏡頭節奏輸入 slow cinematic camera movement
4. 按「建立 Runway Storyboard」
```

檢查：

```text
[ ] 頁面可正常開啟
[ ] 衣物下拉選單有資料
[ ] 可送出表單
[ ] 建立 ai_jobs
[ ] job_type = runway_video
[ ] status = degraded
[ ] mode = mock
[ ] result_json 有 prompt
[ ] result_json 有 scenes
[ ] scenes 數量為 4
[ ] 頁面顯示 storyboard scenes
[ ] 頁面顯示 prompt
```

Tinker 檢查：

```php
\App\Models\AiJob::where('job_type', 'runway_video')->latest()->first();
```

---

## 8. Digital Twin L1 驗收

網址：

```text
/workspace/digital-twin
```

操作：

```text
1. 身高輸入 170
2. 風格偏好輸入 簡約都會風
3. 常見場合輸入 校園日常
4. 補充說明輸入 喜歡寬鬆版型
5. 按「建立 Digital Twin Profile」
```

檢查：

```text
[ ] 頁面可正常開啟
[ ] 可送出表單
[ ] 建立 ai_jobs
[ ] job_type = digital_twin
[ ] status = degraded
[ ] mode = mock
[ ] result_json 有 profile
[ ] result_json 有 style_summary
[ ] result_json 有 style_tags
[ ] 頁面顯示 Avatar Placeholder
[ ] 頁面顯示身高
[ ] 頁面顯示風格偏好
[ ] 頁面顯示常見場合
[ ] 頁面顯示 Style Summary
```

Tinker 檢查：

```php
\App\Models\AiJob::where('job_type', 'digital_twin')->latest()->first();
```

---

## 9. Python AI Service 驗收

### 9.1 Health

網址：

```text
http://127.0.0.1:8001/health
```

檢查：

```text
[ ] status = ok
[ ] service 有顯示
[ ] mock_mode 有顯示
[ ] version 有顯示
[ ] dependencies 有顯示
[ ] qdrant 狀態有顯示
[ ] clip / blip / pose 狀態有顯示
```

---

### 9.2 Laravel tinker smoke test

```php
$ai = app(\App\Services\AiService::class);

$attr = $ai->analyzeAttributes([
    'user_id' => 1,
    'clothing_id' => 1,
    'image_path' => 'clothes/1/test.jpg',
    'image_url' => 'http://127.0.0.1:8000/storage/clothes/1/test.jpg',
]);

$imageEmbed = $ai->embedImage([
    'user_id' => 1,
    'clothing_id' => 1,
    'image_path' => 'clothes/1/test.jpg',
    'image_url' => 'http://127.0.0.1:8000/storage/clothes/1/test.jpg',
]);

$textEmbed = $ai->embedText([
    'user_id' => 1,
    'query' => '白色上衣',
]);

$search = $ai->searchSimilar([
    'user_id' => 1,
    'query_type' => 'text',
    'query' => '白色上衣',
    'embedding' => $textEmbed['embedding'],
    'top_k' => 5,
]);

$pose = $ai->analyzePose([
    'user_id' => 1,
    'image_path' => 'tryon/1/full_body.jpg',
    'image_url' => 'http://127.0.0.1:8000/storage/tryon/1/full_body.jpg',
    'task_type' => 'magic_mirror',
]);
```

檢查：

```text
[ ] attributes status = degraded
[ ] image embedding status = degraded
[ ] text embedding status = degraded
[ ] similar search status = degraded
[ ] pose status = degraded
[ ] 五個 endpoint 都有 request_id
```

---

## 10. 降級狀態驗收

### 10.1 Degraded 顯示

```text
[ ] Smart Closet 可顯示 degraded/mock
[ ] AI Search 可顯示 mock 或 keyword_fallback
[ ] Try-on 可顯示 degraded/mock
[ ] Runway Video 可顯示 degraded/mock
[ ] Digital Twin 可顯示 degraded/mock
```

---

### 10.2 Failed 狀態

關閉 Python AI Service 後測試：

```text
[ ] 新增衣物不應造成整站爆掉
[ ] AI Search 會 fallback
[ ] Try-on 任務失敗時可記錄錯誤
[ ] 頁面可顯示錯誤訊息
```

---

## 11. GitHub 上傳前檢查

執行：

```powershell
git status
```

確認不要上傳：

```text
[ ] .env 沒有被加入
[ ] ai_service/.env 沒有被加入
[ ] ai_service/.venv 沒有被加入
[ ] database/database.sqlite 沒有被加入
[ ] vendor/ 沒有被加入
[ ] node_modules/ 沒有被加入
[ ] storage/ 沒有被加入
[ ] public/storage 沒有被加入
[ ] __pycache__/ 沒有被加入
```

可以上傳：

```text
[ ] README.md
[ ] docs/*.md
[ ] app/Http/Controllers/*.php
[ ] app/Models/*.php
[ ] app/Services/AiService.php
[ ] config/ai.php
[ ] database/migrations/*.php
[ ] resources/views/**/*.blade.php
[ ] ai_service/*.py
[ ] ai_service/routes/*.py
[ ] ai_service/services/*.py
[ ] ai_service/utils/*.py
[ ] ai_service/models/**/README.md
[ ] ai_service/README.md
[ ] ai_service/.env.example
```

---

## 12. 展示前最小驗收順序

若時間不足，至少依照以下順序驗收：

```text
[ ] 1. Laravel 首頁 / Dashboard 可開
[ ] 2. 登入成功
[ ] 3. Smart Closet 新增衣物成功
[ ] 4. 衣物詳細頁顯示 AI 分析結果
[ ] 5. AI Search 正常模式可搜尋
[ ] 6. AI Search fallback 可搜尋
[ ] 7. Try-on L1 可建立任務
[ ] 8. Runway Video L1 可建立 storyboard
[ ] 9. Digital Twin L1 可建立 profile
[ ] 10. /health 正常
```

---

## 13. 驗收結果紀錄

| 日期 | 驗收人 | 項目 | 結果 | 備註 |
|---|---|---|---|---|
|  |  | Smart Closet |  |  |
|  |  | AI Search |  |  |
|  |  | Try-on L1 |  |  |
|  |  | Runway Video L1 |  |  |
|  |  | Digital Twin L1 |  |  |
|  |  | AI Service |  |  |



【專案上下文】

畢業專題：VogueAI Smart Wardrobe。

前端目前改為 Laravel 12 + Blade + Vite + Tailwind，由組員負責。前端已完成：
- 登入 / 註冊
- Dashboard
- Account 管理
- Admin / User 角色權限
- users.role 欄位
- EnsureUserIsAdmin middleware
- i18n 中英切換
- dark mode
- Admin Users CRUD，含搜尋
- Smart Closet 主要頁面
- Workspace 模組入口頁
- 左側可伸縮導覽
- 功能切換入口

開發 DB 使用 SQLite。

我負責：
- 後端資料設計
- AI 模型 / AI Service
- Laravel 與 Python AI Service 串接
- API 契約
- 上傳流程
- DB schema / migration 對齊
- fallback / degraded 策略
- 測試與部署文件
- 讓每個功能逐步從 L1 展示版升級成可實際應用的功能

目前架構：
Laravel 主後端 + Python FastAPI AI Service。

Laravel 負責：
- 頁面
- 登入 / 權限
- Controller
- DB
- 檔案上傳
- 呼叫 AI Service
- 將 AI 結果寫回 DB
- 顯示任務狀態
- 處理 failed / degraded / fallback

Python FastAPI AI Service 負責：
- attributes 衣物屬性辨識
- image embedding
- text embedding
- similar search
- pose analysis
- health / dependencies check
- 未來接 CLIP / BLIP / YOLO Pose / Qdrant / 外部生成 API

目前採用 Mock-first / Degraded 策略：
先確保功能可以操作、資料可以寫入、任務可以建立、頁面可以展示，再逐步換成真實模型。

所有假設請用 [ASSUMPTION] 標註。
遇到缺資料時，請先列出缺資料清單，然後用合理假設產出草稿，不要直接停下來。

---

【非常重要：所有功能都要有主要入口與實際應用路徑】

之後不管新增或修改任何功能，都必須注意：

1. 功能不能只是一個孤立頁面。
2. 功能要能從主要入口進入。
3. 功能要能在 Dashboard / SmartCloset Hub / Workspace / 側邊欄 / 功能切換中被看見。
4. 功能要有清楚的 Route name。
5. 功能要有可操作流程。
6. 功能要能寫入 DB 或 ai_jobs，不能只停留在靜態 UI。
7. 功能即使還是 L1，也要有實際應用價值，例如：
   - 建立資料
   - 顯示結果
   - 儲存紀錄
   - 可重新執行
   - 可 fallback
   - 可展示給老師看
8. 若目前是真模型尚未完成，請明確標示為 L1 / mock / degraded，不要假裝已經是真 AI。
9. 每次補功能時，都要同步考慮：
   - Dashboard 是否要加入口或統計
   - SmartCloset Hub 是否要加入口
   - Workspace 是否要加入口
   - 左側導覽 / 功能切換是否要加入口
   - README / docs 是否要更新
   - 測試或驗收 checklist 是否要補

---

【系統必須包含並逐步實際應用的主要入口與功能】

請你之後協助開發時，要確保以下功能都有實際可用入口與逐步可應用流程：

主要入口與管理功能：

1. 儀表板 Dashboard
   - 顯示系統總覽
   - 顯示衣物數量
   - 顯示 AI 任務狀態
   - 顯示最近新增衣物
   - 顯示快速入口
   - 後續可顯示推薦、趨勢、任務提醒

2. My Closet
   - 衣物列表
   - 搜尋 / 篩選
   - 查看衣物詳細
   - 管理衣物資料
   - 顯示 AI 分析結果

3. 帳號總覽 Account Overview
   - 使用者資料
   - 個資修改
   - 密碼更新
   - 帳號刪除
   - 後續可顯示風格偏好、Digital Twin profile

4. 使用者管理 Admin Users
   - Admin 可管理使用者
   - 搜尋使用者
   - 建立 / 查看 / 編輯 / 刪除
   - 權限 role 管理

5. 功能切換 / 左側導覽 / Workspace
   - 所有主要模組都要能從這裡進入
   - 不要讓功能只存在 route 卻沒有入口
   - 新增功能時要提醒我是否需要補導覽連結

---

【Smart Closet 相關功能】

6. SmartCloset Hub
   - 作為智慧衣櫥主入口
   - 顯示衣物統計
   - 顯示 AI 狀態統計
   - 顯示快速功能入口：
     - My Closet
     - 上傳衣物
     - AI Search
     - AI Stylist
     - Try-On / Pose
     - SmartTag
     - QuickSnap
     - Smart Storage
   - 後續可顯示最近任務、推薦穿搭、待補資料

7. 上傳衣物 Upload Clothing
   - 可上傳衣物圖片
   - 建立 clothes
   - 呼叫 /ai/attributes
   - 寫回類別、顏色、季節、場合、用途、style tags
   - 呼叫 /ai/embed/image
   - 寫入 ai_embeddings
   - AI 失敗時仍保留衣物並標記 failed / degraded
   - 可重新分析與 reembed

8. AI 搜尋 AI Search
   - 可輸入文字搜尋衣物
   - 呼叫 /ai/embed/text
   - 呼叫 /ai/search/similar
   - 顯示 similarity score
   - AI Service 關閉時 fallback 到 SQL LIKE
   - 後續接 Qdrant 與真 CLIP embedding

9. AI Stylist
   - 不能只停留在頁面展示
   - L1：輸入場合 / 天氣 / 風格，產生 mock 穿搭建議
   - L2：從 clothes 真實挑衣物組合
   - L3：根據穿搭紀錄、Digital Twin、接受 / 拒絕紀錄做個人化推薦
   - 建議建立 stylist_history
   - 要能從 SmartCloset Hub、Dashboard 或 Workspace 進入

10. Try-On / 姿態
   - 目前 L1 已完成：
     - 選衣物
     - 上傳人物圖片
     - 建立 pose_analysis ai_job
     - 呼叫 /ai/pose
     - 顯示 mock keypoints、posture notes、fit notes
   - L2：接 YOLO Pose / MediaPipe，真實分析 keypoints
   - L3：接 virtual try-on API 或模型，產生換裝圖
   - 要保留 failed / degraded / retry 流程

---

【平台與互動功能】

11. Community
   - 不能只停留在 Workspace 展示
   - L1：穿搭貼文展示 / 建立貼文
   - L2：comments / likes / follows
   - L3：趨勢互動與推薦
   - 可建立 community_posts、comments、likes、follows
   - 要能從 Workspace 或 Dashboard 進入

12. Showcase
   - 商家展示 / 穿搭展示入口
   - L1：展示商家或精選穿搭卡片
   - L2：一鍵匯入衣物到衣櫥
   - L3：與推薦、趨勢、商家資料串接
   - 可與 Smart Closet 的上傳 / 匯入流程串接

13. Blind Box
   - 穿搭盲盒
   - L1：使用者選擇場合後產生 mock 穿搭盲盒
   - L2：從 clothes 隨機但合理組合
   - L3：根據風格偏好與歷史紀錄個人化產生
   - 建議寫入 ai_jobs 或 blind_box_history

14. Runway Video
   - 目前 L1 已完成：
     - 選衣物
     - 輸入影片風格
     - 建立 runway_video ai_job
     - 產生 prompt + 4 個 storyboard scenes
   - L2：queue / processing / success / failed / retry
   - L3：接 Veo / RunwayML / Pika 影片生成 API
   - 要能從 Workspace、Dashboard 或 Showcase 入口進入

15. Chat Assistant
   - L1：mock fashion Q&A
   - L2：讀取 clothes / style profile 給穿搭建議
   - L3：接 Gemini / RAG / trend memory
   - 要保留對話紀錄或至少有 chat_logs / ai_jobs
   - 不能只是靜態輸入框

16. Digital Twin
   - 目前 L1 已完成：
     - 輸入身高
     - 風格偏好
     - 常見場合
     - 建立 digital_twin ai_job
     - 顯示 avatar placeholder / style summary / style tags
   - L2：從 clothes 統計使用者真實風格
   - L3：接 3D avatar / 多視角生成 / image generation
   - 後續要與 AI Stylist、Try-On、Runway Video 串接

17. Travel Packer
   - 旅行打包助手
   - L1：輸入目的地 / 天數 / 場合，產生 mock packing list
   - L2：從 clothes 挑選衣物
   - L3：結合天氣 API、行程、穿搭推薦
   - 建議建立 travel_packing_jobs 或寫入 ai_jobs

18. Smart Storage
   - 智慧收納
   - L1：建立收納箱 / QR code 概念展示
   - L2：衣物綁定 storage box
   - L3：掃 QR code 查看箱內衣物
   - 建議資料表：
     - storage_boxes
     - storage_items
   - 要能跟 My Closet 串接

19. QuickSnap
   - 快速拍照入庫
   - L1：快速上傳照片
   - L2：自動建立 clothes 草稿
   - L3：自動分類、去背、標籤分析
   - 可重用 Upload Clothing + /ai/attributes 流程

20. SmartTag
   - 標籤 / 吊牌 / 發票掃描
   - L1：上傳吊牌或輸入商品資訊
   - L2：解析品牌、價格、材質
   - L3：OCR + 搜尋補圖 + 自動建立衣物
   - 可與 Brave Search / OCR / clothes 串接

21. Magic Mirror
   - 魔鏡 / 姿態與穿搭分析
   - L1：上傳人物照，回傳 mock 姿態與穿搭建議
   - L2：接 YOLO Pose / MediaPipe
   - L3：即時鏡頭、體態分析、天氣建議
   - 可共用 /ai/pose 與 Try-On 的 pose_analysis 流程

22. AI Bestie Call
   - AI 好友視訊 / 語音穿搭建議
   - L1：展示通話介面與 mock 建議
   - L2：語音輸入 / Web Speech API / chat response
   - L3：即時視覺分析 + 語音對話
   - 可與 Chat Assistant、Magic Mirror、BLIP 串接

---

【目前已完成內容】

1. 架構決策
已採用：
Laravel 主後端 + Python FastAPI AI Service。

2. Laravel ⇄ AI Service API 契約
已建立：
docs/ai-api-contract.md

已定義端點：
- GET /health
- POST /ai/attributes
- POST /ai/embed/image
- POST /ai/embed/text
- POST /ai/search/similar
- POST /ai/pose

3. DB Schema / migrations
已建立核心資料表：
- clothes
- ai_embeddings
- ai_jobs

已建立核心 Model：
- App\Models\Clothing
- App\Models\AiEmbedding
- App\Models\AiJob

4. Laravel AiService
已建立：
app/Services/AiService.php

已封裝方法：
- analyzeAttributes()
- embedImage()
- embedText()
- searchSimilar()
- analyzePose()

5. Smart Closet 主流程
已完成：
Blade form
→ Laravel Controller 驗證
→ storage 存圖
→ 建立 clothes
→ 呼叫 /ai/attributes
→ AI 分析結果寫回 clothes
→ 呼叫 /ai/embed/image
→ image embedding 寫入 ai_embeddings
→ redirect closet.show
→ 顯示 AI 分析結果

6. AI Search
已完成：
使用者輸入文字
→ Laravel 呼叫 /ai/embed/text
→ 取得 text embedding
→ 呼叫 /ai/search/similar
→ 回傳 topK clothing_id
→ Laravel 查 clothes
→ 顯示搜尋結果
→ AI Service 關閉時 fallback 到 SQL LIKE keywordSearch

7. Python AI Service 工程化
已完成：
ai_service/
- main.py
- config.py
- schemas.py
- routes/ai_routes.py
- services/mock_ai_service.py
- utils/security.py
- utils/response.py
- utils/logger.py
- utils/dependencies.py
- models/README.md
- models/clip/README.md
- models/blip/README.md
- models/yolo_pose/README.md
- ai_service/README.md

8. Try-on L1
已完成：
closet.tryon
→ 選擇衣物
→ 上傳人物圖片
→ 建立 ai_jobs
→ 呼叫 /ai/pose
→ 寫回 ai_jobs.result_json
→ 頁面顯示 mock / degraded pose 結果

9. Runway Video L1
已完成：
workspace/runway-video
→ 選擇衣物
→ 輸入影片風格與鏡頭節奏
→ 建立 ai_jobs
→ 產生 mock storyboard
→ 寫回 ai_jobs.result_json
→ 頁面顯示 prompt + 4 個 scenes

10. Digital Twin L1
已完成：
workspace/digital-twin
→ 輸入身高、風格偏好、常見場合
→ 建立 ai_jobs
→ 產生 mock profile
→ 寫回 ai_jobs.result_json
→ 顯示 avatar placeholder / style summary / style tags

11. 測試
已建立文件：
- docs/backend-ai-test-plan.md
- docs/manual-acceptance-checklist.md
- docs/test-execution-record.md

已建立 AI Service pytest：
- ai_service/tests/test_health.py
- ai_service/tests/test_ai_routes.py
- ai_service/tests/test_security_validation.py

已建立 Laravel Feature Tests：
- tests/Feature/SmartClosetTest.php
- tests/Feature/AiSearchTest.php
- tests/Feature/AiJobsL1Test.php

目前 Laravel Feature Test 結果：
- SmartClosetTest：5 passed / 7 assertions
- AiSearchTest：4 passed / 9 assertions
- AiJobsL1Test：5 passed / 21 assertions
合計：14 tests passed / 37 assertions

12. 部署與展示
已建立：
- docs/demo-deployment-guide.md
- start-all.ps1

13. 4 週里程碑
已建立：
docs/four-week-milestone-and-acceptance.md

---

【目前功能完整性判斷】

已比較接近真實後端功能：
- Dashboard / 基礎入口
- My Closet
- Account Overview
- Admin Users
- Smart Closet
- clothes DB
- 圖片上傳
- AI attributes 寫回
- image embedding 寫入
- AI Search fallback
- ai_jobs 任務紀錄
- Laravel AiService
- Python AI Service mock endpoints
- 測試與部署文件

目前仍是 L1 / mock / degraded：
- AI Stylist
- Try-on
- Community
- Showcase
- Blind Box
- Runway Video
- Chat Assistant
- Digital Twin
- Travel Packer
- Smart Storage
- QuickSnap
- SmartTag
- Magic Mirror
- AI Bestie Call
- 真實 CLIP / BLIP / Qdrant
- 真實 YOLO Pose
- 真實影片生成
- 真實 3D avatar

請回答時不要把 L1 包裝成已完成真實 AI 模型，要誠實標明目前是 L1 / mock / degraded。
但也不要只說「還沒做」，要協助我把每個功能設計成可逐步完成、可展示、可驗收、可寫入資料的實際應用流程。

---

【後續補強優先順序】

優先順序 1：AI Stylist L1 / L2
原因：
AI Stylist 最能把目前已完成的 Smart Closet、Digital Twin、AI Search 串起來。

優先順序 2：Try-on L2
原因：
Try-on L1 已完成，延伸最自然。

優先順序 3：Runway Video L2
原因：
L1 storyboard 已完成，可以補任務流程完整性。

優先順序 4：Digital Twin L2
原因：
可以從現有衣櫥資料做風格分析。

優先順序 5：Trend / Chat L1
原因：
提升平台感，展示上加分。

優先順序 6：SmartTag / QuickSnap / Smart Storage
原因：
這三個最容易和 Smart Closet 串接，能讓「衣物進入系統」更完整。

優先順序 7：Community / Showcase / Blind Box / Travel Packer / Magic Mirror / AI Bestie Call
原因：
這些是展示完整平台感與亮點的功能，可逐步 L1 化，再慢慢補 L2。

---

【回答格式要求】

當我貼錯誤 log 時，請用：

定位 →
原因 →
修法 →
驗證 →

當我要新增功能時，請用：

目前狀態 →
目標 →
入口與路由 →
檔案修改清單 →
完整程式碼 →
測試指令 →
驗收方式 →
下一步 →

當我要整理文件時，請直接給我可以貼進 md 的完整內容。

當我要改 Laravel 程式時，請注意：
- 不要破壞現有前端組員做好的 Blade / Tailwind 架構
- 優先最小修改
- route name 要明確
- Controller 要處理 auth user 資料隔離
- 如果會寫入 DB，要說明 migration / model / fillable / casts 是否需要改
- 如果會呼叫 AI Service，要有 failed / degraded fallback
- 如果是測試，記得加 $this->withoutVite()
- 新功能必須有入口，不能只有 route
- 新功能應優先接到 Dashboard / SmartCloset Hub / Workspace / 功能切換其中至少一個入口

當我要改 Python AI Service 時，請注意：
- 現在 ai_service 已拆分成 routes / services / utils / config / schemas
- 不要再把所有東西塞回 main.py
- API response 要維持 schema_version / request_id / status / mode
- 缺依賴要 degraded，不要讓整個服務啟動失敗
- Internal token 要檢查
- 要補 pytest 或至少說明測試方式

---

【目前下一步】

我現在準備進入後續專題收尾與功能完整性補強。

請你根據以上上下文，協助我做下一步時：
1. 先判斷目前是在哪一個大步驟
2. 說明這一步要解決什麼問題
3. 確認功能入口在哪裡
4. 給我最小可行修改方案
5. 如果是文件，請給完整 md
6. 如果是程式，請給完整檔案或明確插入位置
7. 最後給測試指令與驗收條件