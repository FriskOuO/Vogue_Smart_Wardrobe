# Vogue Smart Wardrobe

Vogue Smart Wardrobe 是一個以 Laravel + Blade + Vite + Tailwind 建置的智慧穿搭平台原型。  
目前專案採用「Laravel 主後端 + Python AI Service」架構，前端頁面優先完成高一致性的展示與操作流程，再分階段對接資料庫與 AI。

---

## 目前重點與分工

- **Laravel 主後端**：頁面、登入、權限、資料庫、檔案上傳流程
- **Python FastAPI AI Service**：屬性辨識、embedding、相似搜尋、姿態分析
- 目前前端已先完成主要 Smart Closet 介面與模組工作台，便於後續後端接線
- 目前後端 / AI 串接採用 **Mock-first** 策略，先確保流程穩定，再逐步替換成真實模型

---

## 目前已完成

### 1) 統一 Vogue 視覺系統

- 公開頁、登入註冊、Dashboard、Account、Profile Edit、Admin Users 視覺一致化
- 支援中英文切換
- 支援白晝 / 黑夜模式
- 支援骨架屏與內容 reveal 動畫

---

### 2) 左側可伸縮導覽

- 已改為左側可伸縮導覽列
- 桌機：上方側欄按鈕可收合 / 展開左側欄
- 手機：上方側欄按鈕開啟 / 關閉抽屜式側欄
- README 模組區已改為「未完成暫放區」，方便組員辨識

---

### 3) Smart Closet 前端頁面與後端接線

目前 Smart Closet 前端頁面已建立，且核心頁面已開始接上資料庫與 AI Service。

| 功能 | Route name | 目前狀態 |
|---|---|---|
| Smart Closet Hub | `closet.hub` | 已有前端頁，可顯示統計資料 |
| My Closet 列表 | `closet.index` | 已改讀 `clothes` 資料表 |
| 新增衣物 | `closet.create` | 已可上傳圖片 |
| 儲存提交 | `closet.store` | 已接正式後端流程 |
| 衣物詳細 | `closet.show` | 已顯示 DB 與 AI 分析結果 |
| AI Search | `closet.search` | 已支援以文搜圖 / fallback 搜尋 |
| AI Stylist | `closet.stylist` | 已完成 L1.5 / L2 基礎版，可從 clothes 產生 rule_based 推薦並寫入 stylist_history |
| Try-On / Pose | `closet.tryon` | 已完成 Try-on L1 任務流程，可建立 pose job |

目前已確認新增衣物表單欄位：

| 欄位 | name | 說明 |
|---|---|---|
| 衣物圖片 | `image` | 上傳衣物照片 |
| 衣物名稱 | `name` | 衣物名稱 |
| 備註 | `notes` | 使用者補充說明 |

---

### 4) README 模組未完成暫放區（Workspace）

以下模組已先做成統一工作台頁（展示用），供後端與 AI 後續對接：

- AI Stylist
- Virtual Try-On
- Community
- Blind Box
- Runway Video
- Chat Assistant
- Showcase
- Digital Twin
- Travel Packer
- Smart Storage
- Quick Snap
- Smart Tag
- Magic Mirror
- AI Bestie Call

對應路由：

```text
workspace.show（/workspace/{module}）
```

---

### 5) 帳號功能（一般使用者）

- Account Overview（Read）
- Profile Edit（Update 個資）
- Password Update（Update 密碼）
- Delete Account（Delete）
- 表單送出可攜帶 locale，後端驗證訊息可配合語系

---

### 6) Admin / User 權限與管理

- `users` 新增 `role` 欄位（admin / user）
- 新增 `EnsureUserIsAdmin` middleware
- `admin.users.*` 路由受 `auth + verified + admin` 保護
- Admin 可管理使用者 CRUD：
  - 列表
  - 建立
  - 檢視
  - 編輯
  - 刪除，避免刪除自己

---

## 後端 / AI 串接進度更新

### 7) 已完成 AI API 契約文件

已建立：

```text
docs/ai-api-contract.md
```

目前已定義 Laravel 呼叫 Python AI Service 的 REST API 契約，包含以下五個端點：

| 端點 | 用途 |
|---|---|
| `POST /ai/attributes` | 衣物屬性辨識，回傳類別、顏色、季節、場合、用途 |
| `POST /ai/embed/image` | 產生 image embedding，用於以圖搜圖與相似搜尋 |
| `POST /ai/embed/text` | 產生 text embedding，用於以文搜圖 |
| `POST /ai/search/similar` | 相似搜尋 topK，支援 Qdrant 或 SQLite fallback |
| `POST /ai/pose` | 人體姿態 keypoints，用於 Try-on / Magic Mirror |

每個端點皆包含：

- Request JSON 範例
- Success Response JSON 範例
- Degraded Response JSON 範例
- Failed Response JSON 範例
- 錯誤碼
- Timeout 策略
- Laravel 呼叫 pseudocode
- Python FastAPI pseudocode

---

### 8) 已完成 DB Schema 設計文件

已建立：

```text
docs/db-schema-plan.md
```

目前先規劃三張核心資料表：

| 資料表 | 用途 |
|---|---|
| `clothes` | 儲存衣物資料、圖片路徑、AI 屬性分析結果 |
| `ai_embeddings` | 儲存 image/text embedding、Qdrant point_id、fallback 資訊 |
| `ai_jobs` | 儲存 AI 任務狀態，例如 Try-on、Pose、Runway Video、AI Stylist |

---

### 9) 已完成 Laravel AI 設定

已在 Laravel `.env` 規劃 AI Service 設定：

```env
AI_SERVICE_URL=http://127.0.0.1:8001
AI_INTERNAL_TOKEN=change_this_internal_ai_token
AI_TIMEOUT_SECONDS=30
AI_MOCK_MODE=true
```

已建立：

```text
config/ai.php
```

Laravel 可透過以下方式讀取 AI 設定：

```php
config('ai.service_url')
config('ai.internal_token')
config('ai.timeout_seconds')
config('ai.mock_mode')
```

---

### 10) 已完成 Laravel AiService

已建立：

```text
app/Services/AiService.php
```

目前已封裝五個方法：

```php
analyzeAttributes()
embedImage()
embedText()
searchSimilar()
analyzePose()
```

`AiService` 負責：

- 統一呼叫 Python FastAPI AI Service
- 自動帶入 `X-Internal-AI-Token`
- 處理 timeout
- 處理 connection error
- 處理非 2xx HTTP response
- 回傳統一格式給 Laravel Controller 使用

已修正 `/ai/search/similar` 的 `filters` 格式問題：  
PHP 空陣列 `[]` 會被 JSON 編碼成 array，但 Python Pydantic 預期 dictionary/object，因此已改為空條件時送出 `{}`。

---

### 11) 已完成 Python FastAPI Mock AI Service

已建立：

```text
ai_service/
```

主要檔案：

```text
ai_service/main.py
ai_service/requirements.txt
ai_service/.env.example
```

目前已完成 mock 端點：

```text
GET  /health
POST /ai/attributes
POST /ai/embed/image
POST /ai/embed/text
POST /ai/search/similar
POST /ai/pose
```

啟動方式：

```powershell
cd ai_service
uvicorn main:app --host 127.0.0.1 --port 8001 --reload
```

健康檢查：

```text
http://127.0.0.1:8001/health
```

---

### 12) 已完成 Laravel ⇄ Python AI Service 串接測試

已使用：

```powershell
php artisan tinker
```

測試 Laravel 呼叫 Python AI Service。

已確認以下五個端點皆可正常回傳 mock / degraded 結果：

```text
POST /ai/attributes
POST /ai/embed/image
POST /ai/embed/text
POST /ai/search/similar
POST /ai/pose
```

目前代表：

```text
Laravel ⇄ Python AI Service 基本串接成功
```

---

### 13) 已完成三個核心 migration

已建立並成功執行三個核心 migration：

```text
clothes
ai_embeddings
ai_jobs
```

目前三張核心資料表已建立，可支援後續：

- Smart Closet 衣物資料儲存
- AI 屬性分析結果寫回
- image/text embedding 儲存
- AI 任務狀態紀錄
- Try-on / Pose / Runway Video / AI Stylist 後續擴充

> 注意：因專案中存在重複 Telescope migration，執行 migration 時建議使用 `--path` 指定單一 migration，避免重複建表錯誤。

---

### 14) 已完成三個核心 Model

已建立並測試：

```text
app/Models/Clothing.php
app/Models/AiEmbedding.php
app/Models/AiJob.php
```

用途說明：

| Model | 用途 |
|---|---|
| `Clothing` | 對應 `clothes`，儲存衣物資料、圖片路徑、AI 分析結果 |
| `AiEmbedding` | 對應 `ai_embeddings`，儲存 image/text embedding 與 vector metadata |
| `AiJob` | 對應 `ai_jobs`，作為 Try-on、Pose、Runway Video、AI Stylist 等任務追蹤基礎 |

已使用 tinker 測試 Model 可正常連接資料表。

---

### 15) 已完成 Smart Closet 上傳 + AI 分析主流程

目前 `closet.store` 已從示範模式改為正式後端流程：

```text
Blade form
→ Laravel Controller 驗證
→ storage 存圖
→ 建立 clothes 資料
→ 呼叫 Python AI Service /ai/attributes
→ AI 屬性分析結果寫回 clothes
→ 呼叫 Python AI Service /ai/embed/image
→ image embedding 寫入 ai_embeddings
→ redirect 到 closet.show
→ 前端顯示 AI 分析結果
```

目前已完成：

- `closet.index` 改讀 `clothes` 資料表
- `closet.store` 已處理正式圖片上傳
- `closet.store` 已呼叫 `AiService::analyzeAttributes()`
- `closet.store` 已呼叫 `AiService::embedImage()`
- `closet.show` 改讀單筆衣物資料
- `closet.show` 可顯示 AI 分析結果
- 若 AI Service 不可用，衣物仍會保留，並記錄 failed 狀態

---

### 16) 已完成重新分析 / 失敗補救機制

目前已新增兩個後端路由：

| 功能 | Route name | 說明 |
|---|---|---|
| 重新分析 AI Attributes | `closet.reanalyze` | 重新呼叫 `/ai/attributes` 並更新 `clothes` |
| 重新產生 Image Embedding | `closet.reembed` | 重新呼叫 `/ai/embed/image` 並更新 `ai_embeddings` |

目前衣物詳細頁已支援：

```text
重新分析 AI Attributes
重新產生 Image Embedding
```

---

### 17) 已完成 AI Search 以文搜圖 / fallback 搜尋

目前 `closet.search` 已從固定假資料改為可操作的 AI Search 流程。

已完成流程：

```text
使用者輸入搜尋文字
→ Laravel 呼叫 Python AI Service /ai/embed/text
→ 取得 text embedding
→ Laravel 呼叫 Python AI Service /ai/search/similar
→ Python 回傳 topK clothing_id
→ Laravel 依 clothing_id 查詢 clothes 資料表
→ Blade 顯示搜尋結果卡片
```

目前已支援：

- 以文字搜尋衣物，例如「白色上衣」
- 顯示搜尋模式，例如 `mock` 或 `keyword_fallback`
- 顯示搜尋結果衣物卡片
- 顯示 similarity score
- 可點擊「查看衣物」進入 `closet.show`
- AI Service 關閉時自動 fallback 到 SQL LIKE 關鍵字搜尋

fallback 流程：

```text
AI Service 不可用
→ Laravel AiService 回傳 failed
→ ClosetController 改用 keywordSearch()
→ 查詢 clothes.name / category / subcategory / color
→ 頁面顯示 keyword_fallback 結果
```

---

### 18) 已完成 Try-on / Runway Video / Digital Twin L1 展示版

目前已依照分層交付策略，完成三個 AI 亮點功能的 L1 可展示版本。

L1 目標是：

```text
可操作
可建立任務
可產生結果
可顯示狀態
即使是 mock / degraded 也能完整展示流程
```

---

#### Try-on L1

目前 Try-on L1 已完成：

```text
closet.tryon
→ 選擇衣物
→ 上傳人物圖片
→ 建立 ai_jobs
→ 呼叫 Python AI Service /ai/pose
→ 寫回 ai_jobs.result_json
→ 頁面顯示 mock / degraded pose 結果
```

已完成內容：

- 可從 Try-on 頁面建立任務
- 可選擇衣櫥中的衣物
- 可上傳人物照片
- 任務會寫入 `ai_jobs`
- 會呼叫 `/ai/pose`
- 回傳 mock keypoints
- 頁面會顯示任務狀態、mode、request_id、posture notes、fit notes、keypoints 數量

目前定位：

```text
Try-on L1 採 Pose mock / degraded 展示模式，先完成任務流程與姿態分析展示，後續可升級 YOLO Pose 或真實 Try-on 模型。
```

---

#### Runway Video L1

目前 Runway Video L1 已完成：

```text
workspace / runway-video
→ 選擇衣物
→ 輸入影片風格與鏡頭節奏
→ 建立 ai_jobs
→ 產生 mock storyboard
→ 寫回 ai_jobs.result_json
→ 頁面顯示 prompt + 分鏡結果
```

已完成內容：

- 可從 Runway Video workspace 建立任務
- 可選擇衣櫥中的衣物
- 可輸入影片風格與鏡頭節奏
- 任務會寫入 `ai_jobs`
- 會產生 storyboard：
  - Opening Walk
  - Front Look
  - Detail Focus
  - Final Pose
- 頁面會顯示任務狀態、mode、request_id、prompt、4 個 storyboard scenes

目前定位：

```text
Runway Video L1 採 Storyboard 展示模式，尚未接真實影片生成 API。後續可升級成 queue 任務，並接入 Veo / RunwayML / Pika 等影片生成服務。
```

---

#### Digital Twin L1

目前 Digital Twin L1 已完成：

```text
workspace / digital-twin
→ 輸入身高、風格偏好、常見場合
→ 建立 ai_jobs
→ 產生 mock Digital Twin Profile
→ 寫回 ai_jobs.result_json
→ 頁面顯示個人風格卡與 avatar placeholder
```

已完成內容：

- 可從 Digital Twin workspace 建立個人風格卡
- 可輸入身高、風格偏好、常見穿搭場合與補充說明
- 任務會寫入 `ai_jobs`
- 頁面會顯示 Avatar Placeholder、Style Summary、recommended direction、style tags

目前定位：

```text
Digital Twin L1 採個人風格卡展示模式，尚未接真實 3D Avatar 或多視角生成服務。後續可與 AI Stylist、Try-on、Runway Video 共用個人化資料。
```

---

### 19) 第 6 項分層交付目前進度

目前第 6 項已完成 L1 展示版：

| 功能 | L1 狀態 | 說明 |
|---|---|---|
| Try-on | 已完成 | 可建立 Pose 任務並顯示 mock / degraded keypoints |
| Runway Video | 已完成 | 可建立 storyboard 任務並顯示 prompt + 分鏡 |
| Digital Twin | 已完成 | 可建立個人風格卡並顯示 mock profile |
| L2 流程完整版 | 尚未開始 | 後續加入 queue、任務進度、重新執行 |
| L3 高品質模型版 | 尚未開始 | 後續接真模型或外部 API |

---

### 20) 已完成後端 / AI 測試計畫與測試骨架

目前第 7 項已完成測試規劃與基本測試骨架。

已建立文件：

```text
docs/backend-ai-test-plan.md
docs/manual-acceptance-checklist.md
docs/test-execution-record.md
```

已建立 AI Service pytest：

```text
ai_service/tests/test_health.py
ai_service/tests/test_ai_routes.py
ai_service/tests/test_security_validation.py
```

已建立 Laravel Feature Tests：

```text
tests/Feature/SmartClosetTest.php
tests/Feature/AiSearchTest.php
tests/Feature/AiJobsL1Test.php
```

目前 Laravel Feature Test 執行結果：

```text
SmartClosetTest：5 passed / 7 assertions
AiSearchTest：4 passed / 9 assertions
AiJobsL1Test：5 passed / 21 assertions
```

合計：

```text
14 tests passed
37 assertions
```

目前代表：

```text
Smart Closet 基礎頁面、使用者資料隔離、AI Search fallback、Runway Video L1、Digital Twin L1 的 Feature Test skeleton 已通過。
```

測試指令：

```powershell
php artisan test tests/Feature/SmartClosetTest.php
php artisan test tests/Feature/AiSearchTest.php
php artisan test tests/Feature/AiJobsL1Test.php
```

AI Service pytest 指令：

```powershell
cd ai_service
python -m pytest
```

---

### 21) 已完成部署與展示手冊

目前第 8 項已完成本機部署與展示流程整理。

已建立文件：

```text
docs/demo-deployment-guide.md
```

文件內容包含：

- 本機展示部署流程
- Laravel / Vite / Python AI Service 三服務啟動方式
- `.env` 與 `ai_service/.env` 設定重點
- SQLite / migration 注意事項
- 測試帳號
- 展示路線
- Laravel Feature Test 與 AI Service pytest 指令
- 常見錯誤排除
- Demo 前最小檢查清單
- 展示講法

---

#### start-all.ps1 一鍵啟動腳本

已建立：

```text
start-all.ps1
```

用途：

```text
一次開啟三個 PowerShell 視窗：
1. Laravel：php artisan serve
2. Vite：npm run dev
3. Python AI Service：uvicorn main:app --host 127.0.0.1 --port 8001 --reload
```

執行方式：

```powershell
.\start-all.ps1
```

若 PowerShell 不允許執行腳本，可先執行：

```powershell
Set-ExecutionPolicy -Scope CurrentUser RemoteSigned
```

目前代表：

```text
第 8 項：部署與展示手冊基本完成
```

---

### 22) 已完成 AI Stylist L1.5 / L2 基礎版

目前 AI Stylist 已從原本的展示頁，升級為可操作、可寫入資料庫、可讀取衣櫥資料的穿搭推薦流程。

已完成流程：

```text
SmartCloset Hub / AI Stylist
→ 使用者輸入場合、天氣、風格偏好
→ Laravel 讀取目前使用者的 clothes 資料
→ 依照 occasion / season / style_tags 進行 rule_based 推薦
→ 產生推薦標題、摘要、推薦理由與 styling tips
→ 寫入 stylist_history
→ 回到 AI Stylist 頁面顯示推薦紀錄
```

已新增資料表：

```text
stylist_history
```

已新增 Model：

```text
app/Models/StylistHistory.php
```

已新增 / 更新功能：

- `GET /closet/stylist`
- `POST /closet/stylist`
- `closet.stylist`
- `closet.stylist.generate`
- `ClosetController@stylist`
- `ClosetController@generateStylist`
- `resources/views/closet/stylist.blade.php`

目前推薦模式：

```text
status = degraded
mode = rule_based
```

目前代表：

```text
AI Stylist 已不是單純靜態展示頁，而是會讀取使用者自己的衣櫥資料，產生可保存的穿搭推薦紀錄。
```

目前仍屬 L1.5 / L2 基礎版，尚未接 Gemini、CLIP embedding、Digital Twin profile 或 RAG 個人化記憶。

後續可補強：

```text
1. 根據 Digital Twin profile 調整推薦
2. 根據穿搭接受 / 拒絕紀錄學習偏好
3. 根據 ai_embeddings 做相似風格推薦
4. 串接 Gemini 產生更自然的推薦說明
5. 加入 weather API 與場合權重
```

已新增 Feature Test：

```text
tests/Feature/AiStylistTest.php
```

測試涵蓋：

```text
1. 未登入不可進入 AI Stylist
2. 登入後可進入 AI Stylist
3. 沒有衣物時不可產生推薦
4. 有衣物時可建立 stylist_history
5. 使用者只能看到自己的推薦紀錄
```

測試指令：

```powershell
php artisan test tests/Feature/AiStylistTest.php
```

---

### 23) 已完成 Digital Twin L2 衣櫥風格分析

目前 Digital Twin 已從原本的 L1 個人風格卡，升級為可讀取使用者真實衣櫥資料的 L2 基礎分析流程。

已完成流程：

```text
Workspace / Digital Twin
→ 使用者點擊「從衣櫥分析 Digital Twin L2」
→ Laravel 讀取目前使用者的 clothes 資料
→ 統計常見類別、顏色、季節、場合與 style_tags
→ 產生 closet-based style profile
→ 寫入 ai_jobs
→ job_type = digital_twin_style_analysis
→ 回到 Digital Twin 頁面顯示 Closet Statistics
```

已新增 Route：

```text
POST /workspace/digital-twin/analyze-closet
route name: workspace.digital-twin.analyze-closet
```

已新增 / 更新功能：

- `WorkspaceController@analyzeDigitalTwinCloset`
- `job_type = digital_twin_style_analysis`
- `mode = rule_based`
- `status = degraded`
- `result_json.closet_statistics`
- `resources/views/workspace/show.blade.php` 顯示 Closet Statistics

目前可統計欄位：

```text
top_categories
top_colors
top_seasons
top_occasions
top_style_tags
```

目前代表：

```text
Digital Twin 已經不只是手動輸入的個人風格卡，而是可以根據使用者自己的衣櫥資料建立風格摘要。
```

目前仍屬 L2 基礎版，尚未接真實 3D Avatar、多視角生成、Gemini 或圖像生成服務。

後續可補強：

```text
1. 將 Digital Twin L2 profile 提供給 AI Stylist 使用
2. 根據 stylist_history 的接受 / 拒絕紀錄調整風格偏好
3. 加入 wear_logs / outfit_logs 形成長期穿搭記憶
4. 串接 Gemini 產生更自然的個人風格描述
5. 未來接 3D Avatar 或多視角生成服務
```

已補 Feature Test：

```text
tests/Feature/AiJobsL1Test.php
```

新增測試涵蓋：

```text
1. 沒有衣物時不可建立 Digital Twin L2 分析
2. 有衣物時可建立 digital_twin_style_analysis ai_job
3. result_json 具有 closet_statistics
4. 只會讀取目前使用者自己的 clothes
```

測試指令：

```powershell
php artisan test tests/Feature/AiJobsL1Test.php
```

---

## 目前 10 大步驟進度

| 編號 | 項目 | 狀態 |
|---:|---|---|
| 1 | 架構決策 | 已完成 |
| 2 | Laravel ⇄ AI Service API 契約 | 已完成 |
| 3 | DB Schema + Laravel migrations | MVP 核心版已完成 |
| 4 | 圖片上傳資料流 | 完整 MVP 已完成，含 AI Search 以文搜圖與 fallback 搜尋 |
| 5 | Python AI 服務工程化 | 工程化 MVP 已完成，包含 FastAPI 拆分、logging、dependencies health check、models 規範 |
| 6 | Try-on / Digital Twin / Runway Video 分層交付 | L1 展示版已完成，包含 Try-on、Runway Video、Digital Twin |
| 7 | 後端 / AI 測試計畫 | 測試計畫文件、手動驗收 checklist、AI Service pytest skeleton、Laravel Feature Test skeleton 已完成 |
| 8 | 部署與展示手冊 | 已完成 demo-deployment-guide.md 與 start-all.ps1 一鍵啟動腳本 |
| 9 | 4 週里程碑 | 已完成 four-week-milestone-and-acceptance.md 與後續開發完整提示詞 |
| 10 | Debug 流程與功能完整性補強 | 已進入功能補強階段，AI Stylist L1.5 / L2 基礎版與 Digital Twin L2 基礎版已完成 |

---

## 測試帳號

由 DatabaseSeeder 建立：

### Admin

```text
Email: admin.dev@vogueai.local
Password: Admin@123456
Role: admin
```

### User

```text
Email: demo.user@vogueai.local
Password: User@123456
Role: user
```

---

## 技術棧

- Backend: Laravel 12, PHP 8.2+
- Frontend: Blade, Vite, TailwindCSS
- Database: SQLite（開發環境）
- Auth: Laravel Breeze（Session）+ 自訂帳號頁與角色權限
- AI Service: Python FastAPI（Mock-first，可回傳 `success` / `degraded` / `failed`）

---

## 本機啟動

### 安裝依賴

```powershell
composer install
npm install
```

### 設定環境

```powershell
cp .env.example .env
php artisan key:generate
```

Windows PowerShell 可使用：

```powershell
copy .env.example .env
```

### 遷移與種子

若只需要套用指定 migration，可使用：

```powershell
php artisan migrate --path=database/migrations/2026_04_24_090000_add_role_to_users_table.php --force
php artisan db:seed --force
```

若要套用後端 / AI 相關資料表，建議指定 migration：

```powershell
php artisan migrate --path=database/migrations/xxxx_xx_xx_xxxxxx_create_clothes_table.php
php artisan migrate --path=database/migrations/xxxx_xx_xx_xxxxxx_create_ai_embeddings_table.php
php artisan migrate --path=database/migrations/xxxx_xx_xx_xxxxxx_create_ai_jobs_table.php
```

### 啟動 Laravel

```powershell
php artisan serve
npm run dev
```

### 啟動 Python AI Service

```powershell
cd ai_service
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
uvicorn main:app --host 127.0.0.1 --port 8001 --reload
```

---

## 重要注意事項

目前專案中存在重複的 Telescope migration：

```text
2026_04_22_161640_create_telescope_entries_table.php
2026_04_22_161722_create_telescope_entries_table.php
```

在既有 SQLite 資料庫上執行完整：

```powershell
php artisan migrate
```

可能因重複建表失敗。

若只需套用特定變更，建議使用：

```powershell
php artisan migrate --path=database/migrations/指定檔案.php
```

---

## 後續建議

### 短期下一步

- 繼續第 10 項：功能完整性補強
- 下一個建議補強 Try-on L2，讓系統可使用真實 Pose 分析或更完整的姿態品質檢查
- 將 Try-on / Magic Mirror 共用 `/ai/pose` 的姿態分析流程
- 後續再補 Runway Video L2、Trend / Chat L1、SmartTag / QuickSnap / Smart Storage

### 中期目標

- 將 AI Stylist 從 rule_based 推薦升級為結合 Digital Twin profile 的個人化推薦
- 使用 `ai_embeddings` 作為 AI Stylist 候選衣物資料
- 補上 wear_logs / outfit_logs
- 加入穿搭接受 / 拒絕紀錄與 RAG 記憶基礎

### 後期擴充

- 接入真實 CLIP / BLIP / YOLO Pose
- 接入 Qdrant 向量資料庫
- 完成 Try-on / Pose / Magic Mirror
- 完成 Runway Video / Digital Twin / Community / Chat / Showcase
- 補齊 Feature / Unit 測試與關鍵流程 E2E

---

## 目前最新階段摘要

目前專題後端與 AI 串接已完成：

```text
1) 架構決策：完成
2) Laravel ⇄ AI Service API 契約：完成
3) DB Schema + Laravel migrations：MVP 核心版完成
4) 圖片上傳資料流：完整 MVP 完成
5) Python AI 服務工程化：MVP 完成
6) Try-on / Digital Twin / Runway Video 分層交付：L1 展示版完成
7) 後端 / AI 測試計畫：測試文件與 skeleton 已完成
8) 部署與展示手冊：完成
9) 4 週里程碑與驗收整理：完成
10) 功能完整性補強：AI Stylist L1.5 / L2 基礎版、Digital Twin L2 基礎版完成
```

目前下一步建議：

```text
Try-on L2：補強姿態品質檢查，讓 Try-on / Magic Mirror 可共用姿態分析基礎。
```

---

## Git commit 建議

```text
feat: add digital twin closet style analysis
```
