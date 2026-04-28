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
| AI Stylist | `closet.stylist` | 前端頁已建立，後續接推薦流程 |
| Try-On / Pose | `closet.tryon` | 前端頁已建立，後續接姿態分析 |

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

## 目前 10 大步驟進度

| 編號 | 項目 | 狀態 |
|---:|---|---|
| 1 | 架構決策 | 已完成 |
| 2 | Laravel ⇄ AI Service API 契約 | 已完成 |
| 3 | DB Schema + Laravel migrations | MVP 核心版已完成 |
| 4 | 圖片上傳資料流 | 完整 MVP 已完成，含 AI Search 以文搜圖與 fallback 搜尋 |
| 5 | Python AI 服務工程化 | Mock-first 基礎版已完成，後續可拆 routes/services/config |
| 6 | Try-on / Digital Twin / Runway Video 分層交付 | 尚未正式展開 |
| 7 | 後端 / AI 測試計畫 | 尚未正式展開 |
| 8 | 部署與展示手冊 | 手動啟動流程已完成，文件待整理 |
| 9 | 4 週里程碑 | 待整理 |
| 10 | Debug 流程 | 已實際使用 |

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

- 進入第 5 項：Python AI 服務工程化
- 將目前單檔 `ai_service/main.py` 拆成正式 FastAPI 專案結構
- 建議拆分：
  - `config.py`
  - `schemas.py`
  - `routes/ai_routes.py`
  - `services/mock_ai_service.py`
  - `utils/security.py`
- 保持現有五個 AI endpoint 功能不變
- 重構後重新測試 `/health` 與五個 AI endpoint

### 中期目標

- 串接 `closet.stylist`
- 使用 `ai_embeddings` 作為 AI Stylist 候選衣物資料
- 補上 wear_logs / outfit_logs
- 加入穿搭紀錄與 RAG 記憶基礎

### 後期擴充

- 接入真實 CLIP / BLIP / YOLO Pose
- 接入 Qdrant 向量資料庫
- 完成 Try-on / Pose / Magic Mirror
- 完成 Runway Video / Digital Twin / Community / Chat / Showcase
- 補齊 Feature / Unit 測試與關鍵流程 E2E

---

## Git commit 建議

```text
feat: complete smart closet AI search flow
```
