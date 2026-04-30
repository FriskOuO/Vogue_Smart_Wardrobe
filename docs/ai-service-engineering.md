# VogueAI Smart Wardrobe - AI Service Engineering Plan

## 1. 文件目的

本文件用於規劃 VogueAI Smart Wardrobe 的 Python FastAPI AI Service 工程化設計。

目前專案採用：

```text
Laravel 主後端（Web + Auth + Blade + DB） + Python FastAPI AI Service
```

Laravel 負責：

- 登入 / 註冊
- 權限控管
- Blade 頁面
- 資料庫
- 圖片上傳
- 呼叫 AI Service
- 將 AI 結果寫回 DB

Python FastAPI AI Service 負責：

- 衣物屬性辨識
- image embedding
- text embedding
- 相似搜尋
- 人體姿態分析
- 後續擴充 Try-on / Digital Twin / Runway Video

[ASSUMPTION] 目前 AI Service 先採 Mock-first 策略，確保 Laravel 串接流程穩定，後續再逐步替換為真實模型。

---

## 2. 目前 AI Service 狀態

目前已完成：

```text
ai_service/main.py
ai_service/requirements.txt
ai_service/.env.example
```

已完成端點：

```text
GET  /health
POST /ai/attributes
POST /ai/embed/image
POST /ai/embed/text
POST /ai/search/similar
POST /ai/pose
```

目前所有 AI 功能先使用 mock / degraded response。

Laravel 已成功呼叫 Python AI Service，並完成：

- 衣物上傳後呼叫 `/ai/attributes`
- AI 屬性結果寫回 `clothes`
- 呼叫 `/ai/embed/image`
- image embedding 寫入 `ai_embeddings`
- AI Search 呼叫 `/ai/embed/text`
- AI Search 呼叫 `/ai/search/similar`
- AI Service 關閉時 fallback 到 SQL LIKE 搜尋

---

## 3. 建議 FastAPI 專案結構

後續建議將目前單檔 `main.py` 拆成以下結構：

```text
ai_service/
├─ main.py
├─ config.py
├─ schemas.py
├─ routes/
│  ├─ __init__.py
│  └─ ai_routes.py
├─ services/
│  ├─ __init__.py
│  ├─ mock_ai_service.py
│  ├─ attribute_service.py
│  ├─ embedding_service.py
│  ├─ search_service.py
│  └─ pose_service.py
├─ utils/
│  ├─ __init__.py
│  ├─ security.py
│  ├─ response.py
│  └─ logger.py
├─ models/
│  ├─ clip/
│  ├─ blip/
│  ├─ yolo_pose/
│  └─ README.md
├─ requirements.txt
├─ .env
└─ .env.example
```

---

## 4. 各資料夾用途

| 路徑 | 用途 |
|---|---|
| `main.py` | FastAPI app 啟動入口，只負責建立 app 與掛載 router |
| `config.py` | 管理環境變數與設定 |
| `schemas.py` | Pydantic request / response schema |
| `routes/ai_routes.py` | 定義 AI API routes |
| `services/mock_ai_service.py` | Mock-first 回傳邏輯 |
| `services/attribute_service.py` | 衣物屬性辨識邏輯 |
| `services/embedding_service.py` | image/text embedding 邏輯 |
| `services/search_service.py` | Qdrant / SQLite fallback 搜尋邏輯 |
| `services/pose_service.py` | 人體姿態 keypoints 邏輯 |
| `utils/security.py` | Internal token 驗證 |
| `utils/response.py` | 統一 response 格式 |
| `utils/logger.py` | logging 設定 |
| `models/` | 放置本機模型檔，不建議上傳 GitHub |

---

## 5. 模型載入策略

### 5.1 Mock-first 階段

目前階段：

```text
不載入真實模型
只回傳 mock / degraded response
```

優點：

- Laravel 串接不會被模型安裝卡住
- 不需要 GPU
- 不需要 Qdrant
- 不需要外部 API key
- Demo 時功能流程可展示

---

### 5.2 Lazy Load 策略

後續接真實模型時，建議使用 lazy load：

```text
第一次呼叫該功能時才載入模型
之後將模型保留在記憶體中重複使用
```

適合：

- CLIP image embedding
- CLIP text embedding
- BLIP image captioning
- YOLO Pose

範例概念：

```python
_clip_model = None

def get_clip_model():
    global _clip_model

    if _clip_model is None:
        _clip_model = load_clip_model()

    return _clip_model
```

優點：

- 啟動 FastAPI 較快
- 未使用的模型不會佔記憶體
- 本機展示更穩定

缺點：

- 第一次推論會比較慢

---

### 5.3 預載策略

若正式展示時需要穩定速度，可在 FastAPI startup 時預載模型。

適合：

```text
展示當天
固定展示機
GPU / RAM 足夠
```

不建議開發初期使用，因為容易因模型或依賴缺失導致服務無法啟動。

---

## 6. 記憶體管理策略

AI Service 後續可能載入多個模型，因此需要避免一次全部載入。

建議策略：

| 模型 | 建議策略 |
|---|---|
| CLIP image/text | lazy load |
| BLIP | lazy load |
| YOLO Pose | lazy load |
| Try-on model | job queue / external service |
| Runway Video / Veo | external API |
| Digital Twin | external API 或 async job |

建議原則：

```text
1. 開發階段只啟用需要的模型
2. Demo 階段可預先 warm up 常用模型
3. 大型模型不要放在 request function 內重複載入
4. 模型檔不要 commit 到 GitHub
5. 模型載入失敗時回傳 degraded，不讓整個服務崩潰
```

---

## 7. 模型檔放置規範

建議模型放在：

```text
ai_service/models/
```

資料夾建議：

```text
ai_service/models/
├─ clip/
│  ├─ clip-vit-base-patch32.pt
│  └─ README.md
├─ blip/
│  ├─ blip-base.pth
│  └─ README.md
├─ yolo_pose/
│  ├─ yolo11n-pose.pt
│  └─ README.md
└─ README.md
```

[ASSUMPTION] 若模型檔案太大，不上傳 GitHub，只在 README 說明下載來源與放置位置。

`.gitignore` 應加入：

```gitignore
ai_service/models/*
!ai_service/models/README.md
!ai_service/models/**/README.md
```

---

## 8. 推論超時策略

### 8.1 Laravel 呼叫 timeout

建議：

| API | Laravel timeout |
|---|---:|
| `/ai/attributes` | 30 秒 |
| `/ai/embed/image` | 30 秒 |
| `/ai/embed/text` | 15 秒 |
| `/ai/search/similar` | 15 秒 |
| `/ai/pose` | 30 秒 |

---

### 8.2 Python 內部 timeout

Python 端建議：

| 任務 | Python timeout |
|---|---:|
| text embedding | 10 秒 |
| image embedding | 30 秒 |
| attributes | 30 秒 |
| search similar | 10 秒 |
| pose | 30 秒 |

若超時，回傳：

```json
{
  "schema_version": "v1",
  "request_id": "req_xxx",
  "status": "failed",
  "error": {
    "code": "AI_TIMEOUT",
    "message": "AI 推論逾時"
  }
}
```

或在可降級情況下：

```json
{
  "schema_version": "v1",
  "request_id": "req_xxx",
  "status": "degraded",
  "mode": "mock",
  "degraded_reason": "AI_TIMEOUT_FALLBACK",
  "message": "模型逾時，已回傳展示用結果"
}
```

---

## 9. 重試策略

### 9.1 不建議立即重試的任務

以下任務耗時較高，不建議在同一 request 內多次重試：

- image embedding
- pose
- try-on
- runway video
- digital twin

建議：

```text
失敗後記錄 ai_jobs
由使用者點擊重新分析 / 重新產生
或後續改用 queue retry
```

---

### 9.2 可重試的任務

以下任務較輕量，可以做一次重試：

- text embedding
- Qdrant search
- metadata parse

建議最多重試：

```text
1 次
```

避免使用者等待過久。

---

## 10. 統一回傳格式

### 10.1 Success

```json
{
  "schema_version": "v1",
  "request_id": "req_xxx",
  "status": "success",
  "mode": "model",
  "data": {},
  "message": "AI 任務完成"
}
```

---

### 10.2 Degraded

```json
{
  "schema_version": "v1",
  "request_id": "req_xxx",
  "status": "degraded",
  "mode": "mock",
  "degraded_reason": "MOCK_MODE_ENABLED",
  "data": {},
  "message": "目前使用降級模式回傳展示用結果"
}
```

---

### 10.3 Failed

```json
{
  "schema_version": "v1",
  "request_id": "req_xxx",
  "status": "failed",
  "error": {
    "code": "AI_INTERNAL_ERROR",
    "message": "AI Service 發生錯誤",
    "details": {}
  }
}
```

---

## 11. 缺依賴降級策略

AI Service 不應因為缺少某個套件就整個無法啟動。

### 11.1 onnxruntime 缺失

影響：

```text
部分推論模型無法執行
```

處理：

```text
回傳 status=degraded
mode=mock
degraded_reason=ONNXRUNTIME_NOT_INSTALLED
```

---

### 11.2 rembg 缺失

影響：

```text
去背功能不可用
```

處理：

```text
Try-on 或 Smart Tag 去背流程改用原圖
回傳 degraded
```

---

### 11.3 qdrant-client 缺失

影響：

```text
向量資料庫搜尋不可用
```

處理：

```text
改用 SQLite fallback 或 mock search
回傳 degraded_reason=QDRANT_CLIENT_NOT_INSTALLED
```

---

### 11.4 外部 API key 缺失

可能缺少：

- Gemini API key
- Veo API key
- Brave Search API key
- Weather API key

處理：

```text
不讓 app crash
該功能回傳 degraded
提示缺少對應 key
```

---

## 12. Logging 建議

建議使用 Python logging。

記錄：

- request_id
- endpoint
- user_id
- clothing_id
- status
- mode
- degraded_reason
- error_code
- latency_ms

範例格式：

```text
[INFO] request_id=req_xxx endpoint=/ai/embed/text status=degraded mode=mock latency_ms=35
[ERROR] request_id=req_xxx endpoint=/ai/pose error_code=AI_POSE_MODEL_UNAVAILABLE
```

---

## 13. Metrics 建議

初期不必導入 Prometheus，可先用 log 或簡單 counters。

可追蹤：

| 指標 | 說明 |
|---|---|
| total_requests | AI Service 總請求數 |
| failed_requests | 失敗請求數 |
| degraded_requests | 降級請求數 |
| avg_latency_ms | 平均推論時間 |
| model_loaded | 模型是否成功載入 |
| qdrant_available | Qdrant 是否可用 |

後續若要更正式，可加入：

```text
prometheus-fastapi-instrumentator
```

---

## 14. Health Check 設計

目前已有：

```text
GET /health
```

建議回傳：

```json
{
  "status": "ok",
  "service": "VogueAI AI Service",
  "mock_mode": true,
  "dependencies": {
    "qdrant": "disabled",
    "clip": "mock",
    "pose": "mock"
  }
}
```

後續也可新增：

```text
GET /health/models
```

用於檢查模型是否載入成功。

---

## 15. 階段性工程化計畫

### Phase 1：Mock Service 拆分

目標：

```text
將 main.py 拆成 config / schemas / routes / services / utils
```

驗收：

```text
/health 可正常回應
五個 AI endpoint 可正常回應
Laravel AiService 不需要修改
```

---

### Phase 2：加入 logging / response helper

目標：

```text
統一 success / degraded / failed 回傳格式
所有 endpoint 記錄 request_id 與 status
```

---

### Phase 3：接入 CLIP embedding

目標：

```text
/ai/embed/image 使用真實 CLIP image encoder
/ai/embed/text 使用真實 CLIP text encoder
```

降級：

```text
若 torch / transformers / model 不可用，回傳 mock embedding
```

---

### Phase 4：接入 Qdrant

目標：

```text
/ai/search/similar 優先查 Qdrant
Qdrant 不可用時 fallback
```

---

### Phase 5：接入 Pose

目標：

```text
/ai/pose 接 YOLO Pose 或 MediaPipe
不可用時回傳 mock pose
```

---

## 16. 與 Laravel 對接原則

Laravel 不應直接知道模型細節，只需要依賴 API 契約。

Laravel 只需要判斷：

```text
status = success / degraded / failed
```

處理原則：

| AI status | Laravel 行為 |
|---|---|
| success | 寫入正式結果 |
| degraded | 寫入結果，但標記 mock / fallback |
| failed | 保留原資料，記錄錯誤，允許重新分析 |

目前 Laravel 已完成：

- `AiService`
- `ClosetController@store`
- `ClosetController@reanalyze`
- `ClosetController@reembed`
- `ClosetController@search`
- fallback 搜尋

---

## 17. 下一步

下一步進入：

```text
第 5-B：重構 FastAPI 專案結構
```

目標：

```text
將 ai_service/main.py 拆成：
config.py
schemas.py
routes/ai_routes.py
services/mock_ai_service.py
utils/security.py
```

重構驗收：

```text
1. python -m py_compile ai_service/main.py
2. uvicorn main:app --host 127.0.0.1 --port 8001 --reload
3. /health 正常
4. Laravel 五個 AI 端點測試正常
5. AI Search 正常模式與 fallback 模式都正常
```