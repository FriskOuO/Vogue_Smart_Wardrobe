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
GET  /ai/vector-store/preflight
POST /ai/vector-store/collection/ensure
POST /ai/pose
POST /tryon/generate
GET  /tryon/status/{task_id}
```

### 5.9 Hugging Face IDM-VTON Try-on Demo

VogueAI can route virtual try-on demo jobs through this AI Service instead of calling Hugging Face directly from Laravel:

```text
Laravel -> Python FastAPI AI Service -> gradio_client -> yisol/IDM-VTON
```

This provider is intended for a student project demo / research prototype. The public Hugging Face Space may sleep, queue, rate limit, fail, or change without notice. IDM-VTON is not treated as a commercial SLA provider in this project, and the app keeps degraded fallback behavior so Try-on L1 pose analysis still works when the Space is unavailable.

Laravel `.env`:

```env
AI_EXTERNAL_PROVIDER_CALLS=true
AI_TRYON_PROVIDER=huggingface_idm_vton
AI_TRYON_MODEL=idm-vton
AI_TRYON_API_BASE_URL=http://127.0.0.1:8001
AI_TRYON_API_KEY=
AI_TRYON_CREATE_ENDPOINT=/tryon/generate
AI_TRYON_STATUS_ENDPOINT=/tryon/status/{id}
AI_TRYON_MODE=async
AI_TRYON_OUTPUT_FORMAT=png
AI_TRYON_RETURN_BASE64=false
```

AI Service `.env`:

```env
TRYON_PROVIDER=huggingface_idm_vton
TRYON_MODEL=idm-vton
TRYON_SPACE=yisol/IDM-VTON
TRYON_API_TOKEN=
TRYON_PUBLIC_BASE_URL=http://127.0.0.1:8001
TRYON_OUTPUT_DIR=static/tryon
```

Install the client dependency:

```powershell
cd ai_service
.\.venv\Scripts\python.exe -m pip install -r requirements.txt
```

Create a local async try-on task:

```http
POST /tryon/generate
X-Internal-AI-Token: <AI_INTERNAL_TOKEN>
Content-Type: application/json
```

```json
{
  "model": "idm-vton",
  "request_id": "tryon_20260617_001",
  "user_id": 1,
  "person_image_url": "https://example.com/person.jpg",
  "clothing_image_url": "https://example.com/clothing.jpg",
  "pose_analysis": {
    "pose_quality_score": 0.82
  }
}
```

Poll the local task once:

```http
GET /tryon/status/local_hf_tryon_xxx
X-Internal-AI-Token: <AI_INTERNAL_TOKEN>
```

Successful results are copied into `ai_service/static/tryon/` and returned as `output_url`, for example `http://127.0.0.1:8001/static/tryon/local_hf_tryon_xxx.png`.

If the Space is unavailable or the dependency is missing, the endpoint returns a degraded / failed contract instead of crashing. Duplicating the Space or using GPU hardware on Hugging Face can improve stability, but may create Hugging Face billing.

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
  },
  "vector_store": {
    "target_provider": "qdrant",
    "active_provider": "mock_sqlite_fallback",
    "adapter": "qdrant-vector-store-v1",
    "status": "planned",
    "client_package": "missing",
    "target_url": "http://127.0.0.1:6333",
    "target_collection": "vogueai_clothing_embeddings",
    "fallback_collection": "ai_embeddings",
    "fallback_active": true,
    "api_key_configured": false,
    "connection_check": "not_attempted",
    "degraded_reason": "QDRANT_CLIENT_NOT_INSTALLED"
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
image_caption.target_provider = blip
image_caption.active_provider = mock_caption_fallback
image_caption.adapter = blip-image-caption-v1
image_caption.target_model = Salesforce/blip-image-captioning-base
image_caption.model_repository = Salesforce/blip-image-captioning-base
image_caption.adapter_methods.image_caption = blip_generate_caption
```

正式 adapter method 已保留於 `services/blip_caption_service.py`：

```text
blip_generate_caption(image_path)
```

安裝 `ai_service/requirements-ml.txt` 並備妥模型 cache 後，可產生真實圖片描述；目前 `/ai/attributes` 仍走 mock-first fallback。

若 request 明確傳入 `mock_mode=false`，API 會先嘗試 `blip_generate_caption()`；若依賴或模型不可用，會保留 mock caption，並在 `real_adapter_attempt` 回傳失敗原因。

Laravel 端會依照 `AI_MOCK_MODE` 設定自動帶入 `mock_mode`；預設 `AI_MOCK_MODE=true` 保持 demo fallback，正式環境可改為 `false` 來啟用 adapter attempt。

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
model = mock-image-embedding
target_model = clip-vit-base-patch32
embedding_provider.model_repository = openai/clip-vit-base-patch32
embedding_provider.target_provider = clip
embedding_provider.active_provider = mock_embedding_fallback
embedding_provider.adapter = clip-embedding-v1
vector_db.provider = mock_sqlite_fallback
vector_db.target_provider = qdrant
vector_db.target_url = http://127.0.0.1:6333
vector_db.connection_check = not_attempted
vector_db.target_vector_size = 512
vector_db.active_vector_size = 8
vector_db.distance = Cosine
```

正式 adapter method 已保留於 `services/clip_embedding_service.py`：

```text
clip_embed_image(image_path)
```

安裝 `ai_service/requirements-ml.txt` 並備妥模型 cache 後，可產生 512D `clip_image` vector；目前 `/ai/embed/image` 仍走 mock-first fallback。

若 request 明確傳入 `mock_mode=false`，API 會先嘗試 `clip_embed_image()`；若成功且 `store_to_vector_db=true`，會再嘗試 `qdrant_upsert_clothing_embedding()`。任一正式依賴不可用時會保留 mock embedding，並在 `real_adapter_attempt` 回傳失敗原因。

Laravel 端會依照 `AI_MOCK_MODE` 設定自動帶入 `mock_mode`；預設 `AI_MOCK_MODE=true` 保持 demo fallback，正式環境可改為 `false` 來啟用 adapter attempt。

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
model = mock-text-embedding
target_model = clip-vit-base-patch32
embedding_provider.model_repository = openai/clip-vit-base-patch32
embedding_provider.target_provider = clip
embedding_provider.active_provider = mock_embedding_fallback
embedding_provider.adapter = clip-embedding-v1
```

正式 adapter method 已保留於 `services/clip_embedding_service.py`：

```text
clip_embed_text(query)
```

安裝 `ai_service/requirements-ml.txt` 並備妥模型 cache 後，可產生 512D `clip_text` vector；目前 `/ai/embed/text` 仍走 mock-first fallback。

若 request 明確傳入 `mock_mode=false`，API 會先嘗試 `clip_embed_text()`；若依賴或模型不可用，會保留 mock embedding，並在 `real_adapter_attempt` 回傳失敗原因。

Laravel 端會依照 `AI_MOCK_MODE` 設定自動帶入 `mock_mode`；預設 `AI_MOCK_MODE=true` 保持 demo fallback，正式環境可改為 `false` 來啟用 adapter attempt。

Laravel 使用場景：

```text
AI Search 以文搜圖時，先將使用者輸入轉成 text embedding。
```

目前 `/ai/embed/text` 已接入 logging，可在 AI Service 終端機看到 request log。

---

### 5.5 GET /ai/vector-store/preflight

用途：

```text
檢查 Qdrant vector store adapter 正式接入前的設定、fallback 與下一步。
```

預設不主動連線 Qdrant daemon，只檢查本機設定與 `qdrant-client` 是否可用：

```text
vector_store.target_provider = qdrant
vector_store.active_provider = mock_sqlite_fallback
vector_store.adapter = qdrant-vector-store-v1
vector_store.target_url = http://127.0.0.1:6333
vector_store.target_collection = vogueai_clothing_embeddings
vector_store.target_vector_size = 512
vector_store.active_vector_size = 8
vector_store.distance = Cosine
vector_store.collection_schema.named_vectors.clip_image.size = 512
vector_store.collection_schema.named_vectors.clip_text.size = 512
adapter_methods.ensure_collection = qdrant_ensure_collection(create_missing=True)
adapter_methods.upsert = qdrant_upsert_clothing_embedding
adapter_methods.search = qdrant_search_similar_clothing
adapter_methods.activation_mode = manual_internal_endpoint
collection_plan.dry_run = true
collection_plan.operation = create_or_verify_collection
collection_plan.vectors_config.clip_image.size = 512
collection_plan.vectors_config.clip_text.distance = Cosine
upsert_plan.operation = upsert_clothing_embedding
upsert_plan.vector_name = clip_image
upsert_plan.point_id_template = <clothing_id>
search_plan.operation = search_similar_clothing
search_plan.query_vector_name = clip_image
search_plan.filter_template.must[0].key = user_id
dimension_validation.expected_vector_size = 512
dimension_validation.actual_vector_size = 8
dimension_validation.error_code = VECTOR_DIMENSION_MISMATCH
dimension_validation.fallback_required = true
vector_store.connection_check = not_attempted
readiness.fallback_safe = true
```

`collection_plan` 是 dry-run 規格，不會建立 Qdrant collection。正式接入時需先安裝 `qdrant-client`、啟動 Qdrant daemon，並確認不要把目前 mock 8D embeddings 寫入 512D target collection。
`upsert_plan` 與 `search_plan` 固定 adapter 要使用的 point id、named vector、payload 與 user filter。Qdrant point id 使用整數 `clothing_id`，衣物識別資訊也會保留在 payload。`qdrant_upsert_clothing_embedding()` 與 `qdrant_search_similar_clothing()` 已提供正式 method path，並會先用 `dimension_validation` 阻擋 mock 8D vector。
`dimension_validation` 會明確阻擋目前 mock 8D vector 進入 512D Qdrant collection；只有真 CLIP 512D embedding 就緒後才可切換。

### 5.6 POST /ai/vector-store/collection/ensure

用途：

```text
正式 Qdrant adapter 的 internal collection 建立 / 驗證入口。
```

此端點需要 `X-Internal-AI-Token`。預設 `create_missing=false` 只驗證 collection 是否存在；加上 `create_missing=true` 才會在 `qdrant-client` 已安裝且 Qdrant daemon 可連線時呼叫 `recreate_collection`，並建立 payload keyword indexes。

```text
POST /ai/vector-store/collection/ensure?create_missing=true
```

目前未安裝 `qdrant-client` 時會安全回傳：

```text
status = degraded
operation = ensure_collection
error_code = QDRANT_CLIENT_NOT_INSTALLED
fallback_safe = true
```

這個端點不會被 `/health`、`/ai/embed/image` 或 `/ai/search/similar` 自動觸發，避免 demo 啟動時意外重建 collection。

AI Service `.env` 需要保留以下 Qdrant / embedding 設定：

```text
EMBEDDING_PROVIDER=clip
EMBEDDING_MODEL=clip-vit-base-patch32
EMBEDDING_MODEL_REPOSITORY=openai/clip-vit-base-patch32
VECTOR_STORE_PROVIDER=qdrant
VECTOR_STORE_COLLECTION=vogueai_clothing_embeddings
VECTOR_STORE_URL=http://127.0.0.1:6333
VECTOR_STORE_API_KEY=
VECTOR_STORE_TARGET_VECTOR_SIZE=512
VECTOR_STORE_ACTIVE_VECTOR_SIZE=8
VECTOR_STORE_DISTANCE=Cosine
```

`qdrant-client` 已宣告於 `ai_service/requirements.txt`。CLIP / BLIP 正式 adapter 的 `torch`、`transformers`、`pillow` 則放在 `ai_service/requirements-ml.txt`。未安裝前，preflight、embedding flow 與 caption flow 仍會回傳 degraded fallback。

若要嘗試真連線檢查，可使用：

```text
GET /ai/vector-store/preflight?check_connection=true
```

沒有安裝 `qdrant-client` 時會安全回傳：

```text
connection.connection_check = skipped
connection.error_code = QDRANT_CLIENT_NOT_INSTALLED
readiness.fallback_safe = true
```

此 endpoint 需要 `X-Internal-AI-Token`。

---

### 5.7 POST /ai/search/similar

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

### 5.8 POST /ai/pose

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

Windows hidden/background launch can use the stable wrapper:

```powershell
cd ai_service
.\.venv\Scripts\pythonw.exe run_server.py
```

`run_server.py` disables Uvicorn's stdout-dependent log formatter so `pythonw.exe` can keep the service alive without a visible console. If startup fails, it writes the traceback to `storage/logs/ai-service-idm-vton-python.log`.

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
