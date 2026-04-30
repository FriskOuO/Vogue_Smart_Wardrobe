# VogueAI AI Service

本資料夾為 VogueAI Smart Wardrobe 的 Python FastAPI AI Service。

目前採用 **Mock-first** 策略，先確保 Laravel 主後端可以穩定呼叫 AI API，後續再逐步接入真實模型，例如 CLIP、BLIP、YOLO Pose、Qdrant 等。

---

## 1. 目前定位

本 AI Service 負責提供 Laravel 呼叫的 AI API。

Laravel 負責：

```text
登入 / 註冊
Blade 頁面
權限控管
資料庫
圖片上傳
呼叫 AI Service
將 AI 結果寫回 DB
```

Python FastAPI AI Service 負責：

```text
衣物屬性辨識
image embedding
text embedding
相似搜尋
人體姿態分析
後續 Try-on / Digital Twin / Runway Video 擴充
```

---

## 2. 目前專案結構

```text
ai_service/
├─ main.py
├─ config.py
├─ schemas.py
├─ requirements.txt
├─ .env.example
├─ README.md
├─ routes/
│  ├─ __init__.py
│  └─ ai_routes.py
├─ services/
│  ├─ __init__.py
│  └─ mock_ai_service.py
├─ utils/
│  ├─ __init__.py
│  ├─ security.py
│  ├─ response.py
│  ├─ logger.py
│  └─ dependencies.py
└─ models/
   ├─ README.md
   ├─ clip/
   │  └─ README.md
   ├─ blip/
   │  └─ README.md
   └─ yolo_pose/
      └─ README.md
```

---

## 3. 各檔案用途

| 檔案 / 資料夾 | 用途 |
|---|---|
| `main.py` | FastAPI 啟動入口，只負責建立 app、掛載 router、提供 `/health` |
| `config.py` | 讀取 `.env` 與環境變數 |
| `schemas.py` | 定義 Pydantic request schema |
| `routes/ai_routes.py` | 定義所有 AI API endpoints |
| `services/mock_ai_service.py` | Mock-first AI 回傳邏輯 |
| `utils/security.py` | Internal Token 驗證 |
| `utils/response.py` | 統一 success / degraded / failed response helper |
| `utils/logger.py` | AI request logging |
| `utils/dependencies.py` | 檢查 qdrant、onnxruntime、rembg、torch、transformers、外部 API key |
| `models/` | 後續放置本機 AI 模型檔，不上傳大型模型 |

---

## 4. 已完成 API

目前已完成以下端點：

```text
GET  /health
POST /ai/attributes
POST /ai/embed/image
POST /ai/embed/text
POST /ai/search/similar
POST /ai/pose
```

---

## 5. API 說明

### 5.1 GET /health

用途：

```text
檢查 AI Service 是否正常啟動，以及目前依賴狀態。
```

回傳範例：

```json
{
  "status": "ok",
  "service": "VogueAI-AI-Service",
  "mock_mode": true,
  "version": "0.2.0",
  "dependencies": {
    "clip": "mock",
    "blip": "mock",
    "pose": "mock",
    "qdrant": "missing",
    "onnxruntime": "missing",
    "rembg": "missing",
    "gemini_api_key": "missing",
    "veo_api_key": "missing",
    "brave_search_api_key": "missing",
    "weather_api_key": "missing"
  }
}
```

---

### 5.2 POST /ai/attributes

用途：

```text
衣物屬性辨識。
```

目前 mock 回傳：

```text
category
subcategory
color
season
occasion
usage
style_tags
material_guess
pattern
confidence
```

Laravel 使用場景：

```text
衣物上傳後，自動分析類別、顏色、季節、場合與用途，並寫回 clothes 資料表。
```

---

### 5.3 POST /ai/embed/image

用途：

```text
產生 image embedding。
```

目前 mock 回傳：

```text
embedding_type = image
vector_dimension = 8
embedding
embedding_preview
vector_db.provider = sqlite_fallback
```

Laravel 使用場景：

```text
衣物上傳後產生 image embedding，並寫入 ai_embeddings 資料表。
```

---

### 5.4 POST /ai/embed/text

用途：

```text
產生 text embedding。
```

目前 mock 回傳：

```text
embedding_type = text
vector_dimension = 8
embedding
normalized_query
```

Laravel 使用場景：

```text
AI Search 以文搜圖時，先將使用者輸入轉成 text embedding。
```

目前 `/ai/embed/text` 已接入 logging，可在 AI Service 終端機看到 request log。

---

### 5.5 POST /ai/search/similar

用途：

```text
相似搜尋 topK。
```

目前 mock 回傳：

```text
clothing_id
score
reason
metadata
```

Laravel 使用場景：

```text
AI Search 依照回傳 clothing_id 查詢 clothes 資料表，並顯示衣物搜尋結果。
```

---

### 5.6 POST /ai/pose

用途：

```text
人體姿態 keypoints 分析。
```

目前 mock 回傳：

```text
person_count
image_size
keypoints_format
keypoints
pose_analysis
```

Laravel 使用場景：

```text
後續 Try-on / Magic Mirror / 姿態分析使用。
```

---

## 6. 啟動方式

### 6.1 建立虛擬環境

在專案根目錄執行：

```powershell
python -m venv ai_service\.venv
```

啟用虛擬環境：

```powershell
ai_service\.venv\Scripts\activate
```

---

### 6.2 安裝套件

```powershell
pip install -r ai_service\requirements.txt
```

---

### 6.3 啟動 AI Service

```powershell
cd ai_service
uvicorn main:app --host 127.0.0.1 --port 8001 --reload
```

成功後會看到：

```text
Uvicorn running on http://127.0.0.1:8001
```

---

### 6.4 測試 health check

瀏覽器打開：

```text
http://127.0.0.1:8001/health
```

---

## 7. 環境變數

`.env.example` 建議內容：

```env
APP_NAME=VogueAI-AI-Service
APP_ENV=local
HOST=127.0.0.1
PORT=8001

AI_INTERNAL_TOKEN=change_this_internal_ai_token
AI_MOCK_MODE=true

GEMINI_API_KEY=
VEO_API_KEY=
BRAVE_SEARCH_API_KEY=
WEATHER_API_KEY=
```

注意：

```text
.env 不應上傳 GitHub。
```

---

## 8. Internal Token

Laravel 呼叫 Python AI Service 時，會透過 header 傳入：

```text
X-Internal-AI-Token
```

Python 端會在 `utils/security.py` 中檢查 token。

若 token 錯誤，會回傳：

```json
{
  "schema_version": "v1",
  "status": "failed",
  "error": {
    "code": "AI_UNAUTHORIZED",
    "message": "Internal token 錯誤"
  }
}
```

---

## 9. 回傳狀態設計

目前 AI Service 採用三種狀態：

| status | 說明 |
|---|---|
| `success` | 真實模型成功完成 |
| `degraded` | 降級模式，例如 mock、fallback、缺依賴 |
| `failed` | 任務失敗，Laravel 應記錄錯誤並允許重新分析 |

目前 mock-first 階段，大多回傳：

```text
status = degraded
mode = mock
```

---

## 10. Logging

目前已建立：

```text
utils/logger.py
```

目前 `/ai/embed/text` 已接入 logging。

log 範例：

```text
[INFO] 2026-04-29 09:12:28 vogueai_ai_service - request_id=req_xxx endpoint=/ai/embed/text status=degraded mode=mock user_id=1 clothing_id=None error_code=None latency_ms=3.21
```

後續可逐步套用到：

```text
/ai/attributes
/ai/embed/image
/ai/search/similar
/ai/pose
```

---

## 11. Dependency Check

目前已建立：

```text
utils/dependencies.py
```

可檢查：

```text
qdrant_client
onnxruntime
rembg
torch
transformers
GEMINI_API_KEY
VEO_API_KEY
BRAVE_SEARCH_API_KEY
WEATHER_API_KEY
```

`/health` 會顯示 dependencies 狀態。

---

## 12. 模型檔放置規範

模型資料夾：

```text
ai_service/models/
```

目前包含：

```text
models/
├─ README.md
├─ clip/README.md
├─ blip/README.md
└─ yolo_pose/README.md
```

大型模型檔不應上傳 GitHub，例如：

```text
*.pt
*.pth
*.onnx
*.bin
*.safetensors
*.ckpt
```

---

## 13. 缺依賴降級策略

AI Service 不應因為缺少某個套件就整個無法啟動。

範例：

| 缺少項目 | 降級策略 |
|---|---|
| `qdrant-client` | 使用 mock search 或 SQLite fallback |
| `onnxruntime` | 對應模型回傳 degraded |
| `rembg` | 去背功能暫停，使用原圖 |
| `torch` | CLIP / BLIP / YOLO 先使用 mock |
| 外部 API key | 對應功能回傳 degraded |

---

## 14. 目前完成進度

目前已完成第 5 項中的：

```text
第 5-A：AI Service 工程化設計文件
第 5-B：FastAPI 專案結構重構
第 5-C：response helper + logging 基礎
第 5-D：models 規範 + dependencies health check
第 5-E：AI Service README / 工程化紀錄
```

目前代表：

```text
Python AI Service 工程化 MVP 完成
```

---

## 15. 下一步

下一步可進入原本 10 大步驟中的第 6 項：

```text
Try-on / Digital Twin / Runway Video 分層交付
```

也可以先補第 7 項測試計畫：

```text
Laravel Feature tests
AI service pytest
Laravel -> AI service integration smoke test
```

建議下一步：

```text
先做第 6 項分層交付設計文件，再決定要先實作 Try-on L1 還是補測試。
```