# VogueAI Smart Wardrobe - Backend / AI Progress Report

## 1. 目前架構決策

本專題目前採用 **A 架構**：

```text
Laravel 主後端（Web + Auth + Blade + DB） + Python FastAPI AI Service
```

目前不採用 C 架構作為主線，但保留未來升級可能：

```text
未來可升級為 Laravel Sanctum API + Python AI Service
```

---

## 2. 選擇 A 架構的原因

目前組員已完成的 Laravel 功能偏向傳統 Laravel Web 架構，包括：

- Laravel 12
- Blade
- Vite
- TailwindCSS
- Laravel Breeze 登入 / 註冊
- Dashboard
- Account 管理
- Admin / User 角色權限
- `role` 欄位
- `EnsureUserIsAdmin` middleware
- Admin users CRUD
- i18n 中英切換
- Dark mode
- SQLite 開發資料庫

因此目前最適合的做法是讓 Laravel 作為主後端，負責頁面、登入、權限、資料庫與圖片上傳流程；Python FastAPI 則獨立作為 AI Service，負責衣物辨識、embedding、相似搜尋與姿態分析。

---

## 3. 目前已完成項目

### 3.1 Laravel 專案環境確認

已完成：

- `composer install`
- `npm install`
- `.env` 建立
- `php artisan key:generate`
- SQLite 設定
- migration / seeder 執行
- Laravel 啟動測試
- Vite 啟動測試
- Admin / User 登入測試

目前 Laravel 專案可正常啟動。

---

### 3.2 AI 設定檔完成

已在 Laravel `.env` 新增 AI Service 相關設定：

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

### 3.3 AI REST API 契約完成

已建立文件：

```text
docs/ai-api-contract.md
```

目前已定義 Laravel 呼叫 Python AI Service 的 REST API 契約。

已完成五個端點：

```text
POST /ai/attributes
POST /ai/embed/image
POST /ai/embed/text
POST /ai/search/similar
POST /ai/pose
```

每個端點皆包含：

- 功能說明
- Request JSON 範例
- Success Response JSON 範例
- Degraded Response JSON 範例
- Failed Response JSON 範例
- 錯誤碼
- Timeout 策略
- Laravel 呼叫 pseudocode
- Python FastAPI pseudocode

---

## 4. AI API 端點說明

### 4.1 POST /ai/attributes

用途：

```text
衣物屬性辨識
```

可回傳：

- category 類別
- subcategory 子類別
- color 顏色
- season 季節
- occasion 場合
- usage 用途
- style_tags 風格標籤
- confidence 信心分數

主要對應功能：

```text
Smart Closet
AI Stylist
衣物自動分類
```

---

### 4.2 POST /ai/embed/image

用途：

```text
產生 image embedding
```

主要支援：

- 以圖搜圖
- 相似衣物搜尋
- Qdrant 向量索引
- AI Stylist 候選衣物推薦

---

### 4.3 POST /ai/embed/text

用途：

```text
產生 text embedding
```

主要支援：

- 以文搜圖
- 使用者文字需求搜尋
- AI Stylist 文字需求理解
- Chat / AI Bestie 語意理解

---

### 4.4 POST /ai/search/similar

用途：

```text
相似搜尋 topK
```

支援：

- Qdrant 向量搜尋
- SQLite fallback
- mock result 展示模式

主要對應功能：

```text
以圖搜圖
以文搜圖
Smart Closet 搜尋
AI Stylist 推薦
```

---

### 4.5 POST /ai/pose

用途：

```text
人體姿態 keypoints 分析
```

主要支援：

- Try-on
- Magic Mirror
- 姿態分析
- 高低肩 / 駝背 / 科技頸判斷
- 穿搭比例建議

---

## 5. 降級策略

目前 AI Service 採用 mock mode，回傳狀態多為：

```text
status = degraded
mode = mock
```

這是刻意設計的降級策略，目的是讓 Laravel 與 Python 的資料流先穩定串接，避免專題開發前期卡在模型安裝、GPU、Qdrant 或 API Key 問題。

目前支援的共通狀態：

| status | 說明 |
|---|---|
| success | AI 正常完成 |
| degraded | 降級完成，例如 mock mode、模型不可用、Qdrant 不可用 |
| failed | AI 分析失敗 |

Laravel 收到 `degraded` 時，仍然可以顯示結果，但畫面或資料庫應標記：

```text
ai_status = degraded
ai_mode = mock
```

---

## 6. DB Schema Plan 完成

已建立文件：

```text
docs/db-schema-plan.md
```

目前先規劃三張核心表：

```text
1. clothes
2. ai_embeddings
3. ai_jobs
```

但目前尚未正式執行 migration，原因是需要先等待組員確認是否已經建立 Smart Closet / Clothes 相關資料表，避免重複建立或命名衝突。

---

## 7. Laravel AiService 完成

已建立：

```text
app/Services/AiService.php
```

目前包含五個方法：

```php
analyzeAttributes()
embedImage()
embedText()
searchSimilar()
analyzePose()
```

AiService 負責：

- 統一呼叫 Python AI Service
- 自動帶入 Internal Token
- 處理 timeout
- 處理 connection error
- 處理非 2xx HTTP response
- 回傳統一格式給 Laravel Controller 使用

目前不直接接 Controller，也不影響組員現有頁面。

---

## 8. Python FastAPI Mock AI Service 完成

已建立：

```text
ai_service/
```

主要檔案：

```text
ai_service/main.py
ai_service/requirements.txt
ai_service/.env
ai_service/.env.example
```

已完成 mock 端點：

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

成功回應：

```json
{
  "status": "ok",
  "service": "VogueAI AI Service",
  "mock_mode": true
}
```

---

## 9. Laravel ⇄ Python 串接測試完成

已使用：

```powershell
php artisan tinker
```

測試 Laravel 呼叫 Python AI Service。

已確認五個端點皆可正常回傳 mock / degraded 結果：

```text
POST /ai/attributes
POST /ai/embed/image
POST /ai/embed/text
POST /ai/search/similar
POST /ai/pose
```

目前表示：

```text
Laravel ⇄ Python AI Service 基本串接成功
```

---

## 10. 已修正問題紀錄

### 10.1 filters 格式問題

測試 `/ai/search/similar` 時曾出現錯誤：

```text
filters 應該是 object / dictionary，但 Laravel 送出的是 []
```

原因：

```php
'filters' => $data['filters'] ?? []
```

PHP 空陣列 `[]` 會被 JSON 編碼成：

```json
[]
```

但 Python Pydantic 預期：

```json
{}
```

修正方式：

```php
'filters' => empty($data['filters']) ? new \stdClass() : $data['filters']
```

修正後 `/ai/search/similar` 可正常回傳 mock results。

---

## 11. 目前尚未進行的項目

目前尚未進行：

- 正式建立 `clothes` migration
- 正式建立 `ai_embeddings` migration
- 正式建立 `ai_jobs` migration
- 建立 Clothing Model
- 建立 AiEmbedding Model
- 建立 AiJob Model
- 建立 Smart Closet Controller
- 建立 Smart Closet Blade 頁面
- 串接實際圖片上傳流程
- 接入真實 CLIP / BLIP / YOLO Pose / Qdrant

原因：

```text
仍在等待組員確認是否已有 Smart Closet / Clothes / Routes / Model 相關進度，避免重複開發與衝突。
```

---

## 12. 需要組員確認的事項

請組員協助確認：

```text
1. 目前是否已經有 Smart Closet / My Closet 頁面？
2. 是否已經建立 clothes、closet_items、outfits 或 community_posts 資料表？
3. 是否已經建立 Clothing、ClosetItem 或 Outfit Model？
4. 是否已經新增 closet / clothes 相關 routes 或 Controller？
5. 圖片上傳功能目前由誰負責？
6. Smart Closet 頁面預計使用哪個 route name？
7. 衣物圖片預計存放在哪個 storage 路徑？
```

---

## 13. 下一步計畫

若組員尚未建立 Smart Closet 相關功能，下一步建議：

```text
1. 建立 clothes / ai_embeddings / ai_jobs migration
2. 建立 Clothing / AiEmbedding / AiJob Model
3. 建立 Smart Closet 基礎 Controller
4. 建立衣物圖片上傳流程
5. 上傳後呼叫 AiService::analyzeAttributes()
6. 將 AI 回傳結果寫入 clothes 表
7. Blade 顯示 AI 分析結果
```

若組員已經建立部分 Smart Closet 功能，下一步改為：

```text
1. 對齊現有資料表命名
2. 對齊現有 Model
3. 避免重複建立 routes
4. 將 AiService 接到既有上傳流程
5. 補上 AI 欄位或新增 ai_embeddings / ai_jobs 表
```

---

## 14. 專題報告可用說法

目前後端與 AI 串接已先採用 mock-first 策略。  
Laravel 作為主後端，負責登入、權限、資料庫與圖片上傳流程；Python FastAPI 作為獨立 AI Service，提供衣物屬性辨識、embedding 產生、相似搜尋與人體姿態分析等 API。

在模型尚未正式接入前，AI Service 會回傳 `status=degraded` 的展示模式結果，確保系統流程能完整運作。此設計可以降低展示風險，即使模型、Qdrant 或外部 API 暫時不可用，系統仍可保留資料並提供基本功能，後續再逐步替換為真實 CLIP、BLIP、YOLO Pose 與 Qdrant 向量搜尋。

---

## 15. Git commit 建議訊息

```text
docs: add backend AI API contract and schema plan

feat: add Laravel AiService for Python AI integration

feat: add mock FastAPI AI service for local integration testing
```