# VogueAI Smart Wardrobe - AI Service REST API Contract

## 1. 架構定位

本專題目前採用 **A 架構**：

```text
Laravel 主後端（Web + Auth + Blade + DB） + Python FastAPI AI Service
```

Laravel 負責：

- Blade 頁面
- Laravel Breeze Session Auth
- Admin / User role 權限
- 圖片上傳
- SQLite migration
- Smart Closet / Outfit / Community 等資料管理
- 呼叫 Python AI Service

Python AI Service 負責：

- 衣物屬性辨識
- Image embedding
- Text embedding
- 相似搜尋
- 人體姿態 keypoints
- 後續 Try-on / Magic Mirror / Digital Twin / Runway Video 的 AI 任務

[ASSUMPTION] 目前 AI Service 先以 mock mode 為主，先確保 Laravel 與 Python 的資料流可以穩定串接，後續再逐步替換成真實模型。

---

## 2. 共通規則

### 2.1 Base URL

```text
http://127.0.0.1:8001
```

### 2.2 Internal Token

Laravel 呼叫 Python AI Service 時，必須帶入：

```http
X-Internal-AI-Token: change_this_internal_ai_token
```

此 token 僅供 Laravel 後端與 Python AI Service 之間使用，不應暴露在 Blade 前端畫面或 JavaScript 中。

---

### 2.3 共通 Response 狀態

| status | 說明 | Laravel 處理方式 |
|---|---|---|
| success | AI 正常完成 | 儲存 AI 結果並顯示分析內容 |
| degraded | 降級完成，例如 mock mode、Qdrant 不可用、模型不可用 | 仍可儲存結果，但需標記 `ai_status=degraded` |
| failed | AI 分析失敗 | 保留原資料，記錄錯誤，提供重新分析 |

---

### 2.4 共通錯誤碼

| 錯誤碼 | 說明 |
|---|---|
| AI_UNAUTHORIZED | Internal token 錯誤 |
| AI_VALIDATION_ERROR | Request 格式錯誤 |
| AI_TIMEOUT | AI Service 處理逾時 |
| AI_MODEL_UNAVAILABLE | 模型不存在或載入失敗 |
| AI_QDRANT_UNAVAILABLE | Qdrant 不可用 |
| AI_REDIS_UNAVAILABLE | Redis 不可用 |
| AI_IMAGE_NOT_FOUND | 圖片不存在或無法讀取 |
| AI_INTERNAL_ERROR | AI Service 未預期錯誤 |
| AI_HTTP_ERROR | AI Service 回傳非成功 HTTP 狀態碼 |
| AI_SERVICE_UNAVAILABLE | Laravel 無法連線到 AI Service |
| AI_INTERNAL_CLIENT_ERROR | Laravel 呼叫 AI Service 時發生錯誤 |

---

## 3. Timeout 策略

Laravel 呼叫 AI Service 的預設 timeout 為：

```text
30 秒
```

建議各端點 timeout：

| Endpoint | 建議 timeout |
|---|---:|
| `/ai/attributes` | 15～30 秒 |
| `/ai/embed/image` | 15～30 秒 |
| `/ai/embed/text` | 10～15 秒 |
| `/ai/search/similar` | 10～15 秒 |
| `/ai/pose` | 20～30 秒 |

若發生 timeout：

- Laravel 不應讓整個頁面錯誤。
- 原始衣物資料照樣保存。
- AI 狀態標記為 `failed` 或 `pending_retry`。
- 畫面顯示：「AI 分析暫時無法完成，可稍後重新分析。」

---

## 4. POST /ai/attributes：衣物屬性辨識

### 4.1 功能說明

此端點用於分析使用者上傳的衣物圖片，回傳衣物的基本屬性，例如：

- 類別 `category`
- 顏色 `color`
- 季節 `season`
- 場合 `occasion`
- 用途 `usage`
- 風格標籤 `style_tags`
- 信心分數 `confidence`

Laravel 會在使用者上傳圖片後呼叫此端點。Python AI Service 會根據圖片進行辨識，並將結果回傳給 Laravel，最後由 Laravel 儲存到 `clothes` 資料表。

[ASSUMPTION] 初期展示階段先使用 mock mode 回傳固定或半隨機結果，確保 Laravel 與 Python 串接流程穩定，之後再替換成真實 BLIP / CLIP / Vision Model。

---

### 4.2 Endpoint

```http
POST /ai/attributes
```

完整 URL：

```text
http://127.0.0.1:8001/ai/attributes
```

---

### 4.3 Headers

```http
Content-Type: application/json
X-Internal-AI-Token: change_this_internal_ai_token
```

| Header | 必填 | 說明 |
|---|---|---|
| Content-Type | 是 | 固定使用 `application/json` |
| X-Internal-AI-Token | 是 | Laravel 呼叫 Python AI Service 的內部驗證 token |

---

### 4.4 Request JSON 範例

```json
{
  "schema_version": "v1",
  "request_id": "req_20260426_0001",
  "user_id": 1,
  "clothing_id": 12,
  "image_path": "storage/clothes/user_1/white_shirt.jpg",
  "image_url": "http://127.0.0.1:8000/storage/clothes/user_1/white_shirt.jpg",
  "locale": "zh_TW",
  "mock_mode": true
}
```

---

### 4.5 Request 欄位說明

| 欄位 | 型別 | 必填 | 說明 |
|---|---|---|---|
| schema_version | string | 是 | API 契約版本，目前固定 `v1` |
| request_id | string | 是 | Laravel 產生的請求追蹤 ID |
| user_id | integer | 是 | 使用者 ID |
| clothing_id | integer | 是 | Laravel `clothes` 資料表的衣物 ID |
| image_path | string | 是 | Laravel 儲存的圖片相對路徑 |
| image_url | string | 建議 | Python 可讀取的圖片 URL |
| locale | string | 否 | 回傳語言，例如 `zh_TW` 或 `en` |
| mock_mode | boolean | 否 | 是否強制使用 mock 回應 |

---

### 4.6 Success Response JSON 範例

```json
{
  "schema_version": "v1",
  "request_id": "req_20260426_0001",
  "status": "success",
  "mode": "model",
  "clothing_id": 12,
  "attributes": {
    "category": "上衣",
    "subcategory": "襯衫",
    "color": "白色",
    "secondary_colors": ["淺灰"],
    "season": ["春", "夏"],
    "occasion": ["日常", "半正式"],
    "usage": ["通勤", "約會", "校園穿搭"],
    "style_tags": ["簡約", "乾淨", "韓系"],
    "material_guess": "棉質",
    "pattern": "素色"
  },
  "confidence": {
    "category": 0.94,
    "color": 0.91,
    "season": 0.82,
    "occasion": 0.78,
    "overall": 0.86
  },
  "message": "衣物屬性辨識完成"
}
```

---

### 4.7 Degraded Response JSON 範例

當 AI 模型尚未啟用、API Key 缺失、模型載入失敗，但系統仍可提供展示用結果時，回傳 `status=degraded`。

```json
{
  "schema_version": "v1",
  "request_id": "req_20260426_0001",
  "status": "degraded",
  "mode": "mock",
  "degraded_reason": "AI_MODEL_UNAVAILABLE",
  "clothing_id": 12,
  "attributes": {
    "category": "上衣",
    "subcategory": "襯衫",
    "color": "白色",
    "secondary_colors": [],
    "season": ["春", "夏"],
    "occasion": ["日常"],
    "usage": ["休閒穿搭", "校園穿搭"],
    "style_tags": ["簡約", "基本款"],
    "material_guess": "未知",
    "pattern": "素色"
  },
  "confidence": {
    "category": 0.70,
    "color": 0.70,
    "season": 0.60,
    "occasion": 0.60,
    "overall": 0.65
  },
  "message": "AI 模型目前不可用，已使用展示模式回傳預設分析結果"
}
```

Laravel 收到 `status=degraded` 時，仍然可以把結果存進資料庫，但要標記：

```text
ai_status = degraded
ai_mode = mock
```

---

### 4.8 Failed Response JSON 範例

當圖片不存在、token 錯誤、request 格式錯誤，或 AI Service 發生無法處理的錯誤時，回傳 `status=failed`。

```json
{
  "schema_version": "v1",
  "request_id": "req_20260426_0001",
  "status": "failed",
  "error": {
    "code": "AI_IMAGE_NOT_FOUND",
    "message": "圖片不存在或 AI Service 無法讀取 image_url",
    "details": {
      "image_url": "http://127.0.0.1:8000/storage/clothes/user_1/white_shirt.jpg"
    }
  }
}
```

Laravel 收到 `status=failed` 時，不應刪除使用者上傳的衣物資料，而是應該：

1. 保留 `clothes` 資料。
2. 將 `ai_status` 設為 `failed`。
3. 儲存 error code 與 message。
4. 提供「重新分析」按鈕。

---

### 4.9 錯誤碼

| 錯誤碼 | HTTP 狀態碼 | 說明 | Laravel 處理方式 |
|---|---:|---|---|
| AI_UNAUTHORIZED | 401 | Internal token 錯誤 | 不儲存 AI 結果，通知開發者檢查 `.env` |
| AI_VALIDATION_ERROR | 422 | Request 欄位缺失或格式錯誤 | 記錄錯誤，檢查 Laravel payload |
| AI_IMAGE_NOT_FOUND | 404 | 圖片不存在或無法讀取 | 保留衣物資料，顯示可重新分析 |
| AI_MODEL_UNAVAILABLE | 503 | 模型未載入或不可用 | 使用 degraded mock 結果 |
| AI_TIMEOUT | 504 | AI 分析逾時 | 標記 `pending_retry` |
| AI_INTERNAL_ERROR | 500 | AI Service 未預期錯誤 | 標記 `failed`，記錄 log |

---

### 4.10 Timeout 策略

建議 Laravel 呼叫此端點的 timeout：

```text
15～30 秒
```

Laravel 端處理策略：

- 若 15 秒內未回應，可先中止請求。
- 衣物資料保留。
- `ai_status` 設為 `pending_retry` 或 `failed`。
- 畫面顯示：「衣物已上傳成功，AI 分析暫時未完成，可稍後重新分析。」

Python 端處理策略：

- 若模型推論超過服務設定時間，回傳 `AI_TIMEOUT`。
- 若 mock mode 開啟，應在 1 秒內回傳 `degraded` 結果。

---

### 4.11 Laravel 儲存建議

Laravel 收到 response 後，建議寫入 `clothes` 表：

| Laravel 欄位 | 來源 |
|---|---|
| category | `attributes.category` |
| subcategory | `attributes.subcategory` |
| color | `attributes.color` |
| season | `attributes.season` |
| occasion | `attributes.occasion` |
| usage | `attributes.usage` |
| style_tags | `attributes.style_tags` |
| material_guess | `attributes.material_guess` |
| pattern | `attributes.pattern` |
| ai_confidence | `confidence.overall` |
| ai_status | `status` |
| ai_mode | `mode` |
| ai_raw_result | 完整 response JSON |
| ai_error_code | `error.code`，若失敗 |
| ai_error_message | `error.message`，若失敗 |

---

### 4.12 Laravel 呼叫 pseudocode

#### AiService

```php
use Illuminate\Support\Facades\Http;

class AiService
{
    public function analyzeAttributes(Clothing $clothing): array
    {
        $payload = [
            'schema_version' => 'v1',
            'request_id' => 'req_' . now()->format('Ymd_His') . '_' . $clothing->id,
            'user_id' => $clothing->user_id,
            'clothing_id' => $clothing->id,
            'image_path' => $clothing->image_path,
            'image_url' => asset('storage/' . $clothing->image_path),
            'locale' => app()->getLocale(),
            'mock_mode' => config('ai.mock_mode'),
        ];

        try {
            $response = Http::timeout(config('ai.timeout_seconds'))
                ->withHeaders([
                    'X-Internal-AI-Token' => config('ai.internal_token'),
                    'Accept' => 'application/json',
                ])
                ->post(config('ai.service_url') . '/ai/attributes', $payload);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'status' => 'failed',
                'error' => [
                    'code' => 'AI_HTTP_ERROR',
                    'message' => 'AI Service 回傳非成功狀態碼',
                    'details' => [
                        'http_status' => $response->status(),
                        'body' => $response->body(),
                    ],
                ],
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return [
                'status' => 'failed',
                'error' => [
                    'code' => 'AI_SERVICE_UNAVAILABLE',
                    'message' => '無法連線到 AI Service',
                    'details' => [
                        'exception' => $e->getMessage(),
                    ],
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'error' => [
                    'code' => 'AI_INTERNAL_CLIENT_ERROR',
                    'message' => 'Laravel 呼叫 AI Service 時發生錯誤',
                    'details' => [
                        'exception' => $e->getMessage(),
                    ],
                ],
            ];
        }
    }
}
```

#### Controller

```php
class ClothingController extends Controller
{
    public function store(Request $request, AiService $aiService)
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'image' => ['required', 'image', 'max:5120'],
        ]);

        $path = $request->file('image')->store('clothes/' . auth()->id(), 'public');

        $clothing = Clothing::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'] ?? '未命名衣物',
            'image_path' => $path,
            'ai_status' => 'pending',
        ]);

        $aiResult = $aiService->analyzeAttributes($clothing);

        if (in_array($aiResult['status'], ['success', 'degraded'])) {
            $clothing->update([
                'category' => $aiResult['attributes']['category'] ?? null,
                'subcategory' => $aiResult['attributes']['subcategory'] ?? null,
                'color' => $aiResult['attributes']['color'] ?? null,
                'season' => $aiResult['attributes']['season'] ?? [],
                'occasion' => $aiResult['attributes']['occasion'] ?? [],
                'usage' => $aiResult['attributes']['usage'] ?? [],
                'style_tags' => $aiResult['attributes']['style_tags'] ?? [],
                'material_guess' => $aiResult['attributes']['material_guess'] ?? null,
                'pattern' => $aiResult['attributes']['pattern'] ?? null,
                'ai_confidence' => $aiResult['confidence']['overall'] ?? null,
                'ai_status' => $aiResult['status'],
                'ai_mode' => $aiResult['mode'] ?? null,
                'ai_raw_result' => $aiResult,
            ]);
        } else {
            $clothing->update([
                'ai_status' => 'failed',
                'ai_error_code' => $aiResult['error']['code'] ?? 'AI_UNKNOWN_ERROR',
                'ai_error_message' => $aiResult['error']['message'] ?? 'AI 分析失敗',
                'ai_raw_result' => $aiResult,
            ]);
        }

        return redirect()
            ->route('closet.show', $clothing)
            ->with('status', '衣物已上傳完成');
    }
}
```

---

### 4.13 Python FastAPI pseudocode

```python
from fastapi import APIRouter, Header, HTTPException
from pydantic import BaseModel
from typing import Optional

router = APIRouter()

class AttributesRequest(BaseModel):
    schema_version: str
    request_id: str
    user_id: int
    clothing_id: int
    image_path: str
    image_url: Optional[str] = None
    locale: Optional[str] = "zh_TW"
    mock_mode: Optional[bool] = True

@router.post("/ai/attributes")
def analyze_attributes(
    payload: AttributesRequest,
    x_internal_ai_token: str = Header(None)
):
    if x_internal_ai_token != "change_this_internal_ai_token":
        raise HTTPException(status_code=401, detail={
            "status": "failed",
            "error": {
                "code": "AI_UNAUTHORIZED",
                "message": "Internal token 錯誤"
            }
        })

    if payload.mock_mode:
        return {
            "schema_version": "v1",
            "request_id": payload.request_id,
            "status": "degraded",
            "mode": "mock",
            "degraded_reason": "MOCK_MODE_ENABLED",
            "clothing_id": payload.clothing_id,
            "attributes": {
                "category": "上衣",
                "subcategory": "襯衫",
                "color": "白色",
                "secondary_colors": [],
                "season": ["春", "夏"],
                "occasion": ["日常"],
                "usage": ["休閒穿搭", "校園穿搭"],
                "style_tags": ["簡約", "基本款"],
                "material_guess": "未知",
                "pattern": "素色"
            },
            "confidence": {
                "category": 0.70,
                "color": 0.70,
                "season": 0.60,
                "occasion": 0.60,
                "overall": 0.65
            },
            "message": "目前為 mock mode，已回傳展示用衣物屬性"
        }

    # TODO: 後續接入真實 Vision / CLIP / BLIP 模型
    return {
        "schema_version": "v1",
        "request_id": payload.request_id,
        "status": "failed",
        "error": {
            "code": "AI_MODEL_UNAVAILABLE",
            "message": "真實 AI 模型尚未接入"
        }
    }
```
---

## 5. POST /ai/embed/image：產生 Image Embedding

### 5.1 功能說明

此端點用於將衣物圖片轉換成向量 embedding。Laravel 會在衣物新增或重新分析時呼叫此端點，Python AI Service 會使用 CLIP 或其他影像模型產生圖片向量，供後續相似搜尋與推薦使用。

用途包含：

- 以圖搜圖
- 找相似衣物
- AI Stylist 推薦
- Smart Closet 語意搜尋
- Qdrant 向量索引

[ASSUMPTION] 初期展示階段若 CLIP 模型尚未接入，Python AI Service 會回傳 mock embedding，確保 Laravel 流程可以先完成。

---

### 5.2 Endpoint

```http
POST /ai/embed/image
```

完整 URL：

```text
http://127.0.0.1:8001/ai/embed/image
```

---

### 5.3 Headers

```http
Content-Type: application/json
X-Internal-AI-Token: change_this_internal_ai_token
```

| Header | 必填 | 說明 |
|---|---|---|
| Content-Type | 是 | 固定使用 `application/json` |
| X-Internal-AI-Token | 是 | Laravel 呼叫 Python AI Service 的內部驗證 token |

---

### 5.4 Request JSON 範例

```json
{
  "schema_version": "v1",
  "request_id": "req_20260426_0002",
  "user_id": 1,
  "clothing_id": 12,
  "image_path": "storage/clothes/user_1/white_shirt.jpg",
  "image_url": "http://127.0.0.1:8000/storage/clothes/user_1/white_shirt.jpg",
  "model": "clip-vit-base-patch32",
  "store_to_vector_db": true,
  "mock_mode": true
}
```

---

### 5.5 Request 欄位說明

| 欄位 | 型別 | 必填 | 說明 |
|---|---|---|---|
| schema_version | string | 是 | API 契約版本，目前固定 `v1` |
| request_id | string | 是 | Laravel 產生的請求追蹤 ID |
| user_id | integer | 是 | 使用者 ID |
| clothing_id | integer | 是 | 衣物 ID |
| image_path | string | 是 | Laravel 儲存的圖片相對路徑 |
| image_url | string | 建議 | Python 可讀取的圖片 URL |
| model | string | 否 | embedding 模型名稱 |
| store_to_vector_db | boolean | 否 | 是否由 Python 直接寫入 Qdrant |
| mock_mode | boolean | 否 | 是否強制使用 mock embedding |

---

### 5.6 Success Response JSON 範例

```json
{
  "schema_version": "v1",
  "request_id": "req_20260426_0002",
  "status": "success",
  "mode": "model",
  "embedding_type": "image",
  "model": "clip-vit-base-patch32",
  "vector_dimension": 512,
  "clothing_id": 12,
  "embedding": [0.012, -0.083, 0.221, 0.004],
  "embedding_preview": [0.012, -0.083, 0.221, 0.004],
  "vector_db": {
    "provider": "qdrant",
    "collection": "clothing_images",
    "point_id": "clothing_12",
    "stored": true
  },
  "message": "image embedding 產生完成"
}
```

> 注意：正式環境中 `embedding` 可能很長，例如 512 維或 768 維。Laravel 可選擇只儲存完整 embedding 到 `ai_embeddings` 表，或由 Python 直接寫入 Qdrant，Laravel 只保存 point_id。

---

### 5.7 Degraded Response JSON 範例

當 CLIP 模型尚未啟用、Qdrant 不可用，或系統處於展示模式時，回傳 `status=degraded`。

```json
{
  "schema_version": "v1",
  "request_id": "req_20260426_0002",
  "status": "degraded",
  "mode": "mock",
  "degraded_reason": "MOCK_EMBEDDING_ENABLED",
  "embedding_type": "image",
  "model": "mock-image-embedding",
  "vector_dimension": 8,
  "clothing_id": 12,
  "embedding": [0.12, 0.08, -0.04, 0.31, 0.22, -0.18, 0.05, 0.11],
  "embedding_preview": [0.12, 0.08, -0.04, 0.31],
  "vector_db": {
    "provider": "sqlite_fallback",
    "collection": "ai_embeddings",
    "point_id": "clothing_12",
    "stored": true
  },
  "message": "目前使用 mock embedding，已改用 SQLite fallback 儲存"
}
```

Laravel 收到 `status=degraded` 時，仍可儲存 embedding，但要標記：

```text
embedding_status = degraded
embedding_mode = mock
vector_provider = sqlite_fallback
```

---

### 5.8 Failed Response JSON 範例

```json
{
  "schema_version": "v1",
  "request_id": "req_20260426_0002",
  "status": "failed",
  "error": {
    "code": "AI_IMAGE_NOT_FOUND",
    "message": "圖片不存在或 AI Service 無法讀取 image_url",
    "details": {
      "image_url": "http://127.0.0.1:8000/storage/clothes/user_1/white_shirt.jpg"
    }
  }
}
```

Laravel 收到 `status=failed` 時：

1. 保留衣物資料。
2. 不刪除圖片。
3. 將 embedding 狀態標記為 `failed`。
4. 儲存 error code 與 message。
5. 允許使用者或 Admin 重新產生 embedding。

---

### 5.9 錯誤碼

| 錯誤碼 | HTTP 狀態碼 | 說明 | Laravel 處理方式 |
|---|---:|---|---|
| AI_UNAUTHORIZED | 401 | Internal token 錯誤 | 檢查 `.env` 的 `AI_INTERNAL_TOKEN` |
| AI_VALIDATION_ERROR | 422 | Request 格式錯誤 | 記錄 payload 並修正 Laravel 呼叫格式 |
| AI_IMAGE_NOT_FOUND | 404 | 圖片不存在或不可讀取 | 標記 failed，允許重新產生 |
| AI_MODEL_UNAVAILABLE | 503 | CLIP 或 image embedding 模型不可用 | 使用 degraded mock embedding |
| AI_QDRANT_UNAVAILABLE | 503 | Qdrant 不可用 | 改用 SQLite fallback |
| AI_TIMEOUT | 504 | embedding 產生逾時 | 標記 pending_retry |
| AI_INTERNAL_ERROR | 500 | AI Service 未預期錯誤 | 標記 failed 並寫入 log |

---

### 5.10 Timeout 策略

建議 Laravel 呼叫此端點的 timeout：

```text
15~30 秒
```

Laravel 端處理策略：

```text
若 embedding 產生逾時：
1. clothes 資料照樣保留
2. ai_embeddings 可先不建立，或建立 pending_retry 狀態
3. 畫面不應中斷
4. 後續可由「重新分析」或背景任務補做 embedding
```

Python 端處理策略：

```text
若 mock mode 開啟，應在 1 秒內回傳 mock embedding。
若 Qdrant 不可用，應回傳 status=degraded 並改用 SQLite fallback 或僅回傳 embedding 給 Laravel。
```

---

### 5.11 Laravel 儲存建議

建議新增或對齊 `ai_embeddings` 表。

| Laravel 欄位 | 來源 |
|---|---|
| user_id | user_id |
| clothing_id | clothing_id |
| embedding_type | embedding_type |
| model | model |
| vector_dimension | vector_dimension |
| embedding | embedding |
| embedding_preview | embedding_preview |
| vector_provider | vector_db.provider |
| vector_collection | vector_db.collection |
| vector_point_id | vector_db.point_id |
| vector_stored | vector_db.stored |
| status | status |
| mode | mode |
| degraded_reason | degraded_reason |
| raw_result | 完整 response JSON |
| error_code | error.code，若失敗 |
| error_message | error.message，若失敗 |

---

### 5.12 Laravel 呼叫 pseudocode

#### AiService

```php
class AiService
{
    public function embedImage(Clothing $clothing): array
    {
        $payload = [
            'schema_version' => 'v1',
            'request_id' => 'req_' . now()->format('Ymd_His') . '_embed_image_' . $clothing->id,
            'user_id' => $clothing->user_id,
            'clothing_id' => $clothing->id,
            'image_path' => $clothing->image_path,
            'image_url' => asset('storage/' . $clothing->image_path),
            'model' => 'clip-vit-base-patch32',
            'store_to_vector_db' => true,
            'mock_mode' => config('ai.mock_mode'),
        ];

        try {
            $response = Http::timeout(config('ai.timeout_seconds'))
                ->withHeaders([
                    'X-Internal-AI-Token' => config('ai.internal_token'),
                    'Accept' => 'application/json',
                ])
                ->post(config('ai.service_url') . '/ai/embed/image', $payload);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'status' => 'failed',
                'error' => [
                    'code' => 'AI_HTTP_ERROR',
                    'message' => 'AI Service 回傳非成功狀態碼',
                    'details' => [
                        'http_status' => $response->status(),
                        'body' => $response->body(),
                    ],
                ],
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return [
                'status' => 'failed',
                'error' => [
                    'code' => 'AI_SERVICE_UNAVAILABLE',
                    'message' => '無法連線到 AI Service',
                    'details' => [
                        'exception' => $e->getMessage(),
                    ],
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'error' => [
                    'code' => 'AI_INTERNAL_CLIENT_ERROR',
                    'message' => 'Laravel 呼叫 image embedding 時發生錯誤',
                    'details' => [
                        'exception' => $e->getMessage(),
                    ],
                ],
            ];
        }
    }
}
```

---

#### Controller

```php
class ClothingController extends Controller
{
    public function generateImageEmbedding(Clothing $clothing, AiService $aiService)
    {
        $this->authorize('update', $clothing);

        $result = $aiService->embedImage($clothing);

        if (in_array($result['status'], ['success', 'degraded'])) {
            AiEmbedding::updateOrCreate(
                [
                    'clothing_id' => $clothing->id,
                    'embedding_type' => 'image',
                ],
                [
                    'user_id' => $clothing->user_id,
                    'model' => $result['model'] ?? null,
                    'vector_dimension' => $result['vector_dimension'] ?? null,
                    'embedding' => $result['embedding'] ?? [],
                    'embedding_preview' => $result['embedding_preview'] ?? [],
                    'vector_provider' => $result['vector_db']['provider'] ?? null,
                    'vector_collection' => $result['vector_db']['collection'] ?? null,
                    'vector_point_id' => $result['vector_db']['point_id'] ?? null,
                    'vector_stored' => $result['vector_db']['stored'] ?? false,
                    'status' => $result['status'],
                    'mode' => $result['mode'] ?? null,
                    'degraded_reason' => $result['degraded_reason'] ?? null,
                    'raw_result' => $result,
                ]
            );
        }

        if ($result['status'] === 'failed') {
            AiEmbedding::updateOrCreate(
                [
                    'clothing_id' => $clothing->id,
                    'embedding_type' => 'image',
                ],
                [
                    'user_id' => $clothing->user_id,
                    'status' => 'failed',
                    'error_code' => $result['error']['code'] ?? 'AI_UNKNOWN_ERROR',
                    'error_message' => $result['error']['message'] ?? 'image embedding 產生失敗',
                    'raw_result' => $result,
                ]
            );
        }

        return back()->with('status', 'Image embedding 任務已處理完成');
    }
}
```

---

### 5.13 Python FastAPI pseudocode

```python
from fastapi import APIRouter, Header, HTTPException
from pydantic import BaseModel
from typing import Optional

router = APIRouter()

class ImageEmbeddingRequest(BaseModel):
    schema_version: str
    request_id: str
    user_id: int
    clothing_id: int
    image_path: str
    image_url: Optional[str] = None
    model: Optional[str] = "clip-vit-base-patch32"
    store_to_vector_db: Optional[bool] = True
    mock_mode: Optional[bool] = True

@router.post("/ai/embed/image")
def embed_image(
    payload: ImageEmbeddingRequest,
    x_internal_ai_token: str = Header(None)
):
    if x_internal_ai_token != "change_this_internal_ai_token":
        raise HTTPException(status_code=401, detail={
            "status": "failed",
            "error": {
                "code": "AI_UNAUTHORIZED",
                "message": "Internal token 錯誤"
            }
        })

    if payload.mock_mode:
        return {
            "schema_version": "v1",
            "request_id": payload.request_id,
            "status": "degraded",
            "mode": "mock",
            "degraded_reason": "MOCK_EMBEDDING_ENABLED",
            "embedding_type": "image",
            "model": "mock-image-embedding",
            "vector_dimension": 8,
            "clothing_id": payload.clothing_id,
            "embedding": [0.12, 0.08, -0.04, 0.31, 0.22, -0.18, 0.05, 0.11],
            "embedding_preview": [0.12, 0.08, -0.04, 0.31],
            "vector_db": {
                "provider": "sqlite_fallback",
                "collection": "ai_embeddings",
                "point_id": f"clothing_{payload.clothing_id}",
                "stored": True
            },
            "message": "目前為 mock mode，已回傳展示用 image embedding"
        }

    # TODO: 後續接入真實 CLIP 模型
    return {
        "schema_version": "v1",
        "request_id": payload.request_id,
        "status": "failed",
        "error": {
            "code": "AI_MODEL_UNAVAILABLE",
            "message": "真實 image embedding 模型尚未接入"
        }
    }
```
---

## 6. POST /ai/embed/text：產生 Text Embedding

### 6.1 功能說明

此端點用於將使用者輸入的文字轉換成向量 embedding。Laravel 會在使用者進行文字搜尋、穿搭需求描述、AI Stylist 推薦或 Chat 對話時呼叫此端點。

用途包含：

- 以文搜圖
- 依語意搜尋衣物
- AI Stylist 文字需求理解
- Chat 穿搭助理語意分析
- Trend / Showcase 內容推薦

[ASSUMPTION] 初期展示階段若 CLIP text encoder 或其他文字向量模型尚未接入，Python AI Service 會回傳 mock text embedding，確保 Laravel 流程可以先完成。

---

### 6.2 Endpoint

```http
POST /ai/embed/text
```

完整 URL：

```text
http://127.0.0.1:8001/ai/embed/text
```

---

### 6.3 Headers

```http
Content-Type: application/json
X-Internal-AI-Token: change_this_internal_ai_token
```

| Header | 必填 | 說明 |
|---|---|---|
| Content-Type | 是 | 固定使用 `application/json` |
| X-Internal-AI-Token | 是 | Laravel 呼叫 Python AI Service 的內部驗證 token |

---

### 6.4 Request JSON 範例

```json
{
  "schema_version": "v1",
  "request_id": "req_20260426_0003",
  "user_id": 1,
  "query": "紅色約會洋裝",
  "locale": "zh_TW",
  "model": "clip-vit-base-patch32",
  "mock_mode": true
}
```

---

### 6.5 Request 欄位說明

| 欄位 | 型別 | 必填 | 說明 |
|---|---|---|---|
| schema_version | string | 是 | API 契約版本，目前固定 `v1` |
| request_id | string | 是 | Laravel 產生的請求追蹤 ID |
| user_id | integer | 是 | 使用者 ID |
| query | string | 是 | 使用者輸入的搜尋文字或穿搭需求 |
| locale | string | 否 | 語言，例如 `zh_TW` 或 `en` |
| model | string | 否 | text embedding 模型名稱 |
| mock_mode | boolean | 否 | 是否強制使用 mock embedding |

---

### 6.6 Success Response JSON 範例

```json
{
  "schema_version": "v1",
  "request_id": "req_20260426_0003",
  "status": "success",
  "mode": "model",
  "embedding_type": "text",
  "model": "clip-vit-base-patch32",
  "vector_dimension": 512,
  "query": "紅色約會洋裝",
  "normalized_query": "紅色 約會 洋裝",
  "embedding": [0.031, -0.041, 0.118, 0.206],
  "embedding_preview": [0.031, -0.041, 0.118, 0.206],
  "message": "text embedding 產生完成"
}
```

> 注意：正式環境中 `embedding` 可能是 512 維或 768 維。文件中只放前幾個值作為範例。

---

### 6.7 Degraded Response JSON 範例

當文字 embedding 模型不可用，或系統處於展示模式時，回傳 `status=degraded`。

```json
{
  "schema_version": "v1",
  "request_id": "req_20260426_0003",
  "status": "degraded",
  "mode": "mock",
  "degraded_reason": "MOCK_TEXT_EMBEDDING_ENABLED",
  "embedding_type": "text",
  "model": "mock-text-embedding",
  "vector_dimension": 8,
  "query": "紅色約會洋裝",
  "normalized_query": "紅色 約會 洋裝",
  "embedding": [0.20, -0.11, 0.07, 0.18, 0.03, -0.04, 0.09, 0.14],
  "embedding_preview": [0.20, -0.11, 0.07, 0.18],
  "message": "目前使用 mock text embedding，已回傳展示用向量"
}
```

Laravel 收到 `status=degraded` 時，仍可用此向量繼續做展示用相似搜尋，但要標記：

```text
embedding_status = degraded
embedding_mode = mock
```

---

### 6.8 Failed Response JSON 範例

```json
{
  "schema_version": "v1",
  "request_id": "req_20260426_0003",
  "status": "failed",
  "error": {
    "code": "AI_VALIDATION_ERROR",
    "message": "query 不可為空",
    "details": {
      "field": "query"
    }
  }
}
```

Laravel 收到 `status=failed` 時：

1. 不進行語意搜尋。
2. 保留使用者輸入的原始 query。
3. 顯示「搜尋暫時無法完成，請稍後再試」。
4. 可改用一般關鍵字搜尋 fallback。

---

### 6.9 錯誤碼

| 錯誤碼 | HTTP 狀態碼 | 說明 | Laravel 處理方式 |
|---|---:|---|---|
| AI_UNAUTHORIZED | 401 | Internal token 錯誤 | 檢查 `.env` 的 `AI_INTERNAL_TOKEN` |
| AI_VALIDATION_ERROR | 422 | query 缺失或格式錯誤 | 顯示表單錯誤或改用一般搜尋 |
| AI_MODEL_UNAVAILABLE | 503 | text embedding 模型不可用 | 使用 degraded mock embedding |
| AI_TIMEOUT | 504 | text embedding 產生逾時 | 改用一般關鍵字搜尋 |
| AI_INTERNAL_ERROR | 500 | AI Service 未預期錯誤 | 記錄 log，顯示搜尋失敗訊息 |

---

### 6.10 Timeout 策略

建議 Laravel 呼叫此端點的 timeout：

```text
10~15 秒
```

Laravel 端處理策略：

```text
若 text embedding 產生逾時：
1. 不阻塞整個頁面
2. 改用 SQL LIKE 關鍵字搜尋
3. 顯示「目前使用一般搜尋模式」
4. 可將此次 query 記錄到 search_logs
```

Python 端處理策略：

```text
若 mock mode 開啟，應在 1 秒內回傳 mock text embedding。
若模型不可用，回傳 status=degraded 或 failed。
```

---

### 6.11 Laravel 使用情境

此端點通常不直接建立資料表紀錄，而是搭配搜尋流程使用：

```text
使用者輸入搜尋文字
↓
Laravel 呼叫 /ai/embed/text
↓
取得 text embedding
↓
Laravel 呼叫 /ai/search/similar
↓
回傳相似衣物 topK
```

若要保存搜尋紀錄，可另外建立：

```text
search_logs
```

建議欄位：

| 欄位 | 說明 |
|---|---|
| user_id | 使用者 ID |
| query | 原始搜尋文字 |
| normalized_query | 正規化後文字 |
| search_mode | semantic / keyword / degraded |
| result_count | 回傳結果數 |
| raw_result | 完整 response JSON |

---

### 6.12 Laravel 呼叫 pseudocode

#### AiService

```php
class AiService
{
    public function embedText(int $userId, string $query): array
    {
        $payload = [
            'schema_version' => 'v1',
            'request_id' => 'req_' . now()->format('Ymd_His') . '_embed_text_' . $userId,
            'user_id' => $userId,
            'query' => $query,
            'locale' => app()->getLocale(),
            'model' => 'clip-vit-base-patch32',
            'mock_mode' => config('ai.mock_mode'),
        ];

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-Internal-AI-Token' => config('ai.internal_token'),
                    'Accept' => 'application/json',
                ])
                ->post(config('ai.service_url') . '/ai/embed/text', $payload);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'status' => 'failed',
                'error' => [
                    'code' => 'AI_HTTP_ERROR',
                    'message' => 'AI Service 回傳非成功狀態碼',
                    'details' => [
                        'http_status' => $response->status(),
                        'body' => $response->body(),
                    ],
                ],
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return [
                'status' => 'failed',
                'error' => [
                    'code' => 'AI_SERVICE_UNAVAILABLE',
                    'message' => '無法連線到 AI Service',
                    'details' => [
                        'exception' => $e->getMessage(),
                    ],
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'error' => [
                    'code' => 'AI_INTERNAL_CLIENT_ERROR',
                    'message' => 'Laravel 呼叫 text embedding 時發生錯誤',
                    'details' => [
                        'exception' => $e->getMessage(),
                    ],
                ],
            ];
        }
    }
}
```

---

#### Controller

```php
class ClosetSearchController extends Controller
{
    public function search(Request $request, AiService $aiService)
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'max:255'],
        ]);

        $query = $validated['query'];

        $embeddingResult = $aiService->embedText(auth()->id(), $query);

        if (in_array($embeddingResult['status'], ['success', 'degraded'])) {
            // 下一步會呼叫 /ai/search/similar
            // 此處先保留語意搜尋流程入口
            return view('closet.search', [
                'query' => $query,
                'searchMode' => $embeddingResult['status'] === 'degraded'
                    ? 'semantic_degraded'
                    : 'semantic',
                'embeddingResult' => $embeddingResult,
            ]);
        }

        // fallback：一般關鍵字搜尋
        $results = Clothing::where('user_id', auth()->id())
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('category', 'like', "%{$query}%")
                  ->orWhere('color', 'like', "%{$query}%");
            })
            ->get();

        return view('closet.search', [
            'query' => $query,
            'searchMode' => 'keyword_fallback',
            'results' => $results,
            'message' => 'AI 搜尋暫時不可用，目前使用一般關鍵字搜尋',
        ]);
    }
}
```

---

### 6.13 Python FastAPI pseudocode

```python
from fastapi import APIRouter, Header, HTTPException
from pydantic import BaseModel
from typing import Optional

router = APIRouter()

class TextEmbeddingRequest(BaseModel):
    schema_version: str
    request_id: str
    user_id: int
    query: str
    locale: Optional[str] = "zh_TW"
    model: Optional[str] = "clip-vit-base-patch32"
    mock_mode: Optional[bool] = True

@router.post("/ai/embed/text")
def embed_text(
    payload: TextEmbeddingRequest,
    x_internal_ai_token: str = Header(None)
):
    if x_internal_ai_token != "change_this_internal_ai_token":
        raise HTTPException(status_code=401, detail={
            "status": "failed",
            "error": {
                "code": "AI_UNAUTHORIZED",
                "message": "Internal token 錯誤"
            }
        })

    if not payload.query.strip():
        raise HTTPException(status_code=422, detail={
            "status": "failed",
            "error": {
                "code": "AI_VALIDATION_ERROR",
                "message": "query 不可為空"
            }
        })

    if payload.mock_mode:
        return {
            "schema_version": "v1",
            "request_id": payload.request_id,
            "status": "degraded",
            "mode": "mock",
            "degraded_reason": "MOCK_TEXT_EMBEDDING_ENABLED",
            "embedding_type": "text",
            "model": "mock-text-embedding",
            "vector_dimension": 8,
            "query": payload.query,
            "normalized_query": payload.query.strip(),
            "embedding": [0.20, -0.11, 0.07, 0.18, 0.03, -0.04, 0.09, 0.14],
            "embedding_preview": [0.20, -0.11, 0.07, 0.18],
            "message": "目前為 mock mode，已回傳展示用 text embedding"
        }

    # TODO: 後續接入真實 CLIP text encoder 或 sentence embedding 模型
    return {
        "schema_version": "v1",
        "request_id": payload.request_id,
        "status": "failed",
        "error": {
            "code": "AI_MODEL_UNAVAILABLE",
            "message": "真實 text embedding 模型尚未接入"
        }
    }
```
---

## 7. POST /ai/search/similar：相似搜尋 topK

### 7.1 功能說明

此端點用於根據 image embedding 或 text embedding 搜尋相似衣物，回傳 topK 結果。  
Laravel 可用於 Smart Closet 的「以文搜圖」、「以圖搜圖」、AI Stylist 候選衣物篩選，以及 Showcase / Trend 的相似穿搭推薦。

搜尋來源可分為：

- `qdrant`：優先使用 Qdrant 向量資料庫搜尋
- `sqlite_fallback`：Qdrant 不可用時，改用 SQLite 中的分類、顏色、場合等欄位做簡易 fallback
- `mock`：展示模式下回傳模擬搜尋結果

[ASSUMPTION] 初期展示階段若 Qdrant 尚未接入，Python AI Service 會回傳 `status=degraded`，並改用 SQLite fallback 或 mock result 確保展示流程不中斷。

---

### 7.2 Endpoint

```http
POST /ai/search/similar
```

完整 URL：

```text
http://127.0.0.1:8001/ai/search/similar
```

---

### 7.3 Headers

```http
Content-Type: application/json
X-Internal-AI-Token: change_this_internal_ai_token
```

| Header | 必填 | 說明 |
|---|---|---|
| Content-Type | 是 | 固定使用 `application/json` |
| X-Internal-AI-Token | 是 | Laravel 呼叫 Python AI Service 的內部驗證 token |

---

### 7.4 Request JSON 範例：以文搜圖

```json
{
  "schema_version": "v1",
  "request_id": "req_20260426_0004",
  "user_id": 1,
  "query_type": "text",
  "query": "紅色約會洋裝",
  "embedding": [0.20, -0.11, 0.07, 0.18, 0.03, -0.04, 0.09, 0.14],
  "top_k": 5,
  "filters": {
    "category": null,
    "color": "紅色",
    "season": null,
    "occasion": "約會"
  },
  "fallback_enabled": true,
  "mock_mode": true
}
```

---

### 7.5 Request JSON 範例：以圖搜圖

```json
{
  "schema_version": "v1",
  "request_id": "req_20260426_0005",
  "user_id": 1,
  "query_type": "image",
  "source_clothing_id": 12,
  "embedding": [0.12, 0.08, -0.04, 0.31, 0.22, -0.18, 0.05, 0.11],
  "top_k": 5,
  "filters": {
    "category": "上衣",
    "color": null,
    "season": null,
    "occasion": null
  },
  "fallback_enabled": true,
  "mock_mode": true
}
```

---

### 7.6 Request 欄位說明

| 欄位 | 型別 | 必填 | 說明 |
|---|---|---|---|
| schema_version | string | 是 | API 契約版本，目前固定 `v1` |
| request_id | string | 是 | Laravel 產生的請求追蹤 ID |
| user_id | integer | 是 | 使用者 ID |
| query_type | string | 是 | `text` 或 `image` |
| query | string | 否 | 文字搜尋時使用 |
| source_clothing_id | integer | 否 | 以圖搜圖時的來源衣物 ID |
| embedding | array | 是 | text 或 image embedding |
| top_k | integer | 否 | 回傳筆數，預設 5 |
| filters | object | 否 | 搜尋條件，例如 category、color、season、occasion |
| fallback_enabled | boolean | 否 | Qdrant 不可用時是否啟用 fallback |
| mock_mode | boolean | 否 | 是否強制使用 mock result |

---

### 7.7 Success Response JSON 範例

```json
{
  "schema_version": "v1",
  "request_id": "req_20260426_0004",
  "status": "success",
  "mode": "model",
  "query_type": "text",
  "search_provider": "qdrant",
  "top_k": 5,
  "results": [
    {
      "rank": 1,
      "clothing_id": 21,
      "score": 0.92,
      "reason": "文字需求與衣物向量高度相似",
      "metadata": {
        "category": "洋裝",
        "color": "紅色",
        "season": ["春", "夏"],
        "occasion": ["約會", "派對"]
      }
    },
    {
      "rank": 2,
      "clothing_id": 18,
      "score": 0.87,
      "reason": "顏色與場合條件符合",
      "metadata": {
        "category": "裙子",
        "color": "紅色",
        "season": ["春", "夏"],
        "occasion": ["約會"]
      }
    }
  ],
  "message": "相似搜尋完成"
}
```

---

### 7.8 Degraded Response JSON 範例

當 Qdrant 不可用、embedding 維度不一致，或系統處於展示模式時，回傳 `status=degraded`。

```json
{
  "schema_version": "v1",
  "request_id": "req_20260426_0004",
  "status": "degraded",
  "mode": "fallback",
  "degraded_reason": "AI_QDRANT_UNAVAILABLE",
  "query_type": "text",
  "search_provider": "sqlite_fallback",
  "top_k": 5,
  "results": [
    {
      "rank": 1,
      "clothing_id": 21,
      "score": 0.75,
      "reason": "Qdrant 不可用，改用 category / color / occasion 欄位比對",
      "metadata": {
        "category": "洋裝",
        "color": "紅色",
        "season": ["春", "夏"],
        "occasion": ["約會"]
      }
    }
  ],
  "message": "Qdrant 目前不可用，已改用 SQLite fallback 搜尋"
}
```

Laravel 收到 `status=degraded` 時，仍可顯示搜尋結果，但畫面可標示：

```text
目前使用一般搜尋模式，語意相似度可能較低。
```

---

### 7.9 Failed Response JSON 範例

```json
{
  "schema_version": "v1",
  "request_id": "req_20260426_0004",
  "status": "failed",
  "error": {
    "code": "AI_VALIDATION_ERROR",
    "message": "embedding 不可為空",
    "details": {
      "field": "embedding"
    }
  }
}
```

Laravel 收到 `status=failed` 時：

1. 不顯示 AI 搜尋結果。
2. 若有 query，改用 Laravel SQL LIKE 關鍵字搜尋。
3. 顯示「AI 搜尋暫時不可用，已改用一般搜尋」。
4. 將錯誤記錄到 log 或 search_logs。

---

### 7.10 錯誤碼

| 錯誤碼 | HTTP 狀態碼 | 說明 | Laravel 處理方式 |
|---|---:|---|---|
| AI_UNAUTHORIZED | 401 | Internal token 錯誤 | 檢查 `.env` 的 `AI_INTERNAL_TOKEN` |
| AI_VALIDATION_ERROR | 422 | query_type、embedding 或 top_k 格式錯誤 | 改用一般搜尋或提示錯誤 |
| AI_QDRANT_UNAVAILABLE | 503 | Qdrant 無法連線或 collection 不存在 | 改用 SQLite fallback |
| AI_EMBEDDING_DIMENSION_MISMATCH | 422 | embedding 維度與 collection 不一致 | 重新產生 embedding |
| AI_TIMEOUT | 504 | 搜尋逾時 | 改用一般搜尋 |
| AI_INTERNAL_ERROR | 500 | AI Service 未預期錯誤 | 記錄 log，回傳 fallback 訊息 |

---

### 7.11 Timeout 策略

建議 Laravel 呼叫此端點的 timeout：

```text
10~15 秒
```

Laravel 端處理策略：

```text
若相似搜尋逾時：
1. 不阻塞頁面
2. 改用 SQL LIKE 或欄位條件搜尋
3. 顯示「目前使用一般搜尋模式」
4. 可記錄 search_provider = keyword_fallback
```

Python 端處理策略：

```text
Qdrant 搜尋應盡量在 3 秒內完成。
若 Qdrant 不可用且 fallback_enabled=true，直接改回 sqlite_fallback。
若 mock_mode=true，應在 1 秒內回傳 mock results。
```

---

### 7.12 Laravel 使用情境

#### 以文搜圖流程

```text
使用者輸入「紅色約會洋裝」
↓
Laravel 呼叫 /ai/embed/text
↓
取得 text embedding
↓
Laravel 呼叫 /ai/search/similar
↓
Python 回傳 topK 衣物 ID
↓
Laravel 用 clothing_id 查詢 clothes 資料
↓
Blade 顯示搜尋結果
```

#### 以圖搜圖流程

```text
使用者選擇一件衣服按「找相似」
↓
Laravel 取得該衣服 image embedding
↓
Laravel 呼叫 /ai/search/similar
↓
Python 回傳 topK 衣物 ID
↓
Laravel 查詢 clothes 資料
↓
Blade 顯示相似衣物
```

---

### 7.13 Laravel 呼叫 pseudocode

#### AiService

```php
class AiService
{
    public function searchSimilar(
        int $userId,
        string $queryType,
        array $embedding,
        int $topK = 5,
        array $filters = [],
        ?string $query = null,
        ?int $sourceClothingId = null
    ): array {
        $payload = [
            'schema_version' => 'v1',
            'request_id' => 'req_' . now()->format('Ymd_His') . '_search_' . $userId,
            'user_id' => $userId,
            'query_type' => $queryType,
            'query' => $query,
            'source_clothing_id' => $sourceClothingId,
            'embedding' => $embedding,
            'top_k' => $topK,
            'filters' => $filters,
            'fallback_enabled' => true,
            'mock_mode' => config('ai.mock_mode'),
        ];

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-Internal-AI-Token' => config('ai.internal_token'),
                    'Accept' => 'application/json',
                ])
                ->post(config('ai.service_url') . '/ai/search/similar', $payload);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'status' => 'failed',
                'error' => [
                    'code' => 'AI_HTTP_ERROR',
                    'message' => 'AI Service 回傳非成功狀態碼',
                    'details' => [
                        'http_status' => $response->status(),
                        'body' => $response->body(),
                    ],
                ],
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return [
                'status' => 'failed',
                'error' => [
                    'code' => 'AI_SERVICE_UNAVAILABLE',
                    'message' => '無法連線到 AI Service',
                    'details' => [
                        'exception' => $e->getMessage(),
                    ],
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'error' => [
                    'code' => 'AI_INTERNAL_CLIENT_ERROR',
                    'message' => 'Laravel 呼叫相似搜尋時發生錯誤',
                    'details' => [
                        'exception' => $e->getMessage(),
                    ],
                ],
            ];
        }
    }
}
```

---

#### Controller：以文搜圖

```php
class ClosetSearchController extends Controller
{
    public function semanticSearch(Request $request, AiService $aiService)
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'max:255'],
        ]);

        $query = $validated['query'];

        $embeddingResult = $aiService->embedText(auth()->id(), $query);

        if (! in_array($embeddingResult['status'], ['success', 'degraded'])) {
            return $this->keywordFallback($query);
        }

        $searchResult = $aiService->searchSimilar(
            userId: auth()->id(),
            queryType: 'text',
            embedding: $embeddingResult['embedding'],
            topK: 5,
            filters: [],
            query: $query
        );

        if (in_array($searchResult['status'], ['success', 'degraded'])) {
            $ids = collect($searchResult['results'])->pluck('clothing_id')->all();

            $clothes = Clothing::where('user_id', auth()->id())
                ->whereIn('id', $ids)
                ->get();

            return view('closet.search', [
                'query' => $query,
                'searchMode' => $searchResult['search_provider'],
                'clothes' => $clothes,
                'aiResult' => $searchResult,
            ]);
        }

        return $this->keywordFallback($query);
    }

    private function keywordFallback(string $query)
    {
        $clothes = Clothing::where('user_id', auth()->id())
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('category', 'like', "%{$query}%")
                  ->orWhere('color', 'like', "%{$query}%")
                  ->orWhere('occasion', 'like', "%{$query}%");
            })
            ->get();

        return view('closet.search', [
            'query' => $query,
            'searchMode' => 'keyword_fallback',
            'clothes' => $clothes,
            'message' => 'AI 搜尋暫時不可用，目前使用一般關鍵字搜尋',
        ]);
    }
}
```

---

### 7.14 Python FastAPI pseudocode

```python
from fastapi import APIRouter, Header, HTTPException
from pydantic import BaseModel
from typing import Optional, List, Dict, Any

router = APIRouter()

class SimilarSearchRequest(BaseModel):
    schema_version: str
    request_id: str
    user_id: int
    query_type: str
    query: Optional[str] = None
    source_clothing_id: Optional[int] = None
    embedding: List[float]
    top_k: Optional[int] = 5
    filters: Optional[Dict[str, Any]] = {}
    fallback_enabled: Optional[bool] = True
    mock_mode: Optional[bool] = True

@router.post("/ai/search/similar")
def search_similar(
    payload: SimilarSearchRequest,
    x_internal_ai_token: str = Header(None)
):
    if x_internal_ai_token != "change_this_internal_ai_token":
        raise HTTPException(status_code=401, detail={
            "status": "failed",
            "error": {
                "code": "AI_UNAUTHORIZED",
                "message": "Internal token 錯誤"
            }
        })

    if payload.query_type not in ["text", "image"]:
        raise HTTPException(status_code=422, detail={
            "status": "failed",
            "error": {
                "code": "AI_VALIDATION_ERROR",
                "message": "query_type 必須是 text 或 image"
            }
        })

    if not payload.embedding:
        raise HTTPException(status_code=422, detail={
            "status": "failed",
            "error": {
                "code": "AI_VALIDATION_ERROR",
                "message": "embedding 不可為空"
            }
        })

    if payload.mock_mode:
        return {
            "schema_version": "v1",
            "request_id": payload.request_id,
            "status": "degraded",
            "mode": "mock",
            "degraded_reason": "MOCK_SEARCH_ENABLED",
            "query_type": payload.query_type,
            "search_provider": "mock",
            "top_k": payload.top_k,
            "results": [
                {
                    "rank": 1,
                    "clothing_id": 21,
                    "score": 0.75,
                    "reason": "目前為 mock mode，回傳展示用相似衣物結果",
                    "metadata": {
                        "category": "上衣",
                        "color": "白色",
                        "season": ["春", "夏"],
                        "occasion": ["日常"]
                    }
                },
                {
                    "rank": 2,
                    "clothing_id": 18,
                    "score": 0.68,
                    "reason": "目前為 mock mode，依模擬相似度排序",
                    "metadata": {
                        "category": "外套",
                        "color": "米色",
                        "season": ["秋", "冬"],
                        "occasion": ["通勤"]
                    }
                }
            ],
            "message": "目前為 mock mode，已回傳展示用相似搜尋結果"
        }

    # TODO: 優先使用 Qdrant 搜尋
    # TODO: 若 Qdrant 不可用且 fallback_enabled=True，回傳 sqlite_fallback 結果

    return {
        "schema_version": "v1",
        "request_id": payload.request_id,
        "status": "failed",
        "error": {
            "code": "AI_QDRANT_UNAVAILABLE",
            "message": "Qdrant 尚未接入或目前不可用"
        }
    }
```
---

## 8. POST /ai/pose：人體姿態 Keypoints

### 8.1 功能說明

此端點用於分析使用者上傳的人像或穿搭照片，回傳人體姿態 keypoints。Laravel 可用於 Try-on、Magic Mirror、穿搭比例分析與姿態提醒。

用途包含：

- Try-on 虛擬試穿前的人體定位
- Magic Mirror 即時穿搭分析
- 姿態偵測，例如高低肩、駝背、科技頸
- 穿搭比例建議，例如上短下長、腰線位置
- 穿搭照片品質檢查，例如是否全身入鏡

[ASSUMPTION] 初期展示階段若 YOLO Pose / MediaPipe / OpenPose 尚未接入，Python AI Service 會回傳 mock keypoints，確保 Laravel 與前端展示流程可以先完成。

---

### 8.2 Endpoint

```http
POST /ai/pose
```

完整 URL：

```text
http://127.0.0.1:8001/ai/pose
```

---

### 8.3 Headers

```http
Content-Type: application/json
X-Internal-AI-Token: change_this_internal_ai_token
```

| Header | 必填 | 說明 |
|---|---|---|
| Content-Type | 是 | 固定使用 `application/json` |
| X-Internal-AI-Token | 是 | Laravel 呼叫 Python AI Service 的內部驗證 token |

---

### 8.4 Request JSON 範例

```json
{
  "schema_version": "v1",
  "request_id": "req_20260426_0006",
  "user_id": 1,
  "image_path": "storage/tryon/user_1/full_body.jpg",
  "image_url": "http://127.0.0.1:8000/storage/tryon/user_1/full_body.jpg",
  "task_type": "magic_mirror",
  "return_annotated_image": true,
  "mock_mode": true
}
```

---

### 8.5 Request 欄位說明

| 欄位 | 型別 | 必填 | 說明 |
|---|---|---|---|
| schema_version | string | 是 | API 契約版本，目前固定 `v1` |
| request_id | string | 是 | Laravel 產生的請求追蹤 ID |
| user_id | integer | 是 | 使用者 ID |
| image_path | string | 是 | Laravel 儲存的人像或穿搭圖片路徑 |
| image_url | string | 建議 | Python 可讀取的圖片 URL |
| task_type | string | 否 | `try_on`、`magic_mirror`、`pose_check` |
| return_annotated_image | boolean | 否 | 是否回傳標註骨架圖 |
| mock_mode | boolean | 否 | 是否強制使用 mock keypoints |

---

### 8.6 Success Response JSON 範例

```json
{
  "schema_version": "v1",
  "request_id": "req_20260426_0006",
  "status": "success",
  "mode": "model",
  "pose_model": "yolo11-pose",
  "person_count": 1,
  "image_size": {
    "width": 1080,
    "height": 1440
  },
  "keypoints_format": "coco_17",
  "keypoints": [
    {
      "name": "nose",
      "x": 540,
      "y": 188,
      "confidence": 0.96
    },
    {
      "name": "left_shoulder",
      "x": 410,
      "y": 390,
      "confidence": 0.92
    },
    {
      "name": "right_shoulder",
      "x": 670,
      "y": 398,
      "confidence": 0.91
    },
    {
      "name": "left_hip",
      "x": 440,
      "y": 760,
      "confidence": 0.88
    },
    {
      "name": "right_hip",
      "x": 650,
      "y": 755,
      "confidence": 0.87
    }
  ],
  "pose_analysis": {
    "full_body_visible": true,
    "shoulder_balance": "slightly_unbalanced",
    "shoulder_tilt_degree": 2.1,
    "posture_notes": ["右肩略低", "整體站姿穩定"],
    "fit_notes": ["照片適合進行 Try-on 或 Magic Mirror 分析"]
  },
  "annotated_image_url": "http://127.0.0.1:8000/storage/ai_results/pose_req_20260426_0006.jpg",
  "message": "人體姿態分析完成"
}
```

---

### 8.7 Degraded Response JSON 範例

當 pose 模型尚未啟用、圖片品質不足，或系統處於展示模式時，回傳 `status=degraded`。

```json
{
  "schema_version": "v1",
  "request_id": "req_20260426_0006",
  "status": "degraded",
  "mode": "mock",
  "degraded_reason": "MOCK_POSE_ENABLED",
  "pose_model": "mock-pose",
  "person_count": 1,
  "image_size": {
    "width": 1080,
    "height": 1440
  },
  "keypoints_format": "coco_17",
  "keypoints": [
    {
      "name": "nose",
      "x": 540,
      "y": 180,
      "confidence": 0.70
    },
    {
      "name": "left_shoulder",
      "x": 410,
      "y": 390,
      "confidence": 0.70
    },
    {
      "name": "right_shoulder",
      "x": 670,
      "y": 398,
      "confidence": 0.70
    },
    {
      "name": "left_hip",
      "x": 440,
      "y": 760,
      "confidence": 0.65
    },
    {
      "name": "right_hip",
      "x": 650,
      "y": 755,
      "confidence": 0.65
    }
  ],
  "pose_analysis": {
    "full_body_visible": true,
    "shoulder_balance": "unknown",
    "shoulder_tilt_degree": null,
    "posture_notes": ["目前使用展示模式，姿態分析僅供流程展示"],
    "fit_notes": ["可用於 Try-on / Magic Mirror 介面測試"]
  },
  "annotated_image_url": null,
  "message": "目前使用 mock pose，已回傳展示用 keypoints"
}
```

Laravel 收到 `status=degraded` 時，仍可顯示姿態資訊，但畫面要標示：

```text
目前為展示模式，姿態分析結果僅供流程展示。
```

---

### 8.8 Failed Response JSON 範例

```json
{
  "schema_version": "v1",
  "request_id": "req_20260426_0006",
  "status": "failed",
  "error": {
    "code": "AI_NO_PERSON_DETECTED",
    "message": "圖片中未偵測到人體",
    "details": {
      "image_url": "http://127.0.0.1:8000/storage/tryon/user_1/full_body.jpg"
    }
  }
}
```

Laravel 收到 `status=failed` 時：

1. 保留原始圖片。
2. 不執行 Try-on 或 Magic Mirror 後續流程。
3. 顯示「未偵測到完整人體，請重新上傳清楚的全身照片」。
4. 儲存錯誤碼與完整 response，方便除錯。

---

### 8.9 錯誤碼

| 錯誤碼 | HTTP 狀態碼 | 說明 | Laravel 處理方式 |
|---|---:|---|---|
| AI_UNAUTHORIZED | 401 | Internal token 錯誤 | 檢查 `.env` 的 `AI_INTERNAL_TOKEN` |
| AI_VALIDATION_ERROR | 422 | Request 欄位缺失或格式錯誤 | 顯示表單錯誤或記錄 payload |
| AI_IMAGE_NOT_FOUND | 404 | 圖片不存在或無法讀取 | 要求重新上傳 |
| AI_NO_PERSON_DETECTED | 422 | 沒有偵測到人體 | 顯示重新上傳提示 |
| AI_MULTI_PERSON_DETECTED | 422 | 偵測到多人，不適合個人試穿 | 提醒使用單人照片 |
| AI_POSE_MODEL_UNAVAILABLE | 503 | pose 模型不可用 | 使用 degraded mock pose |
| AI_TIMEOUT | 504 | 姿態分析逾時 | 標記 pending_retry |
| AI_INTERNAL_ERROR | 500 | AI Service 未預期錯誤 | 記錄 log，顯示分析失敗 |

---

### 8.10 Timeout 策略

建議 Laravel 呼叫此端點的 timeout：

```text
20~30 秒
```

Laravel 端處理策略：

```text
若姿態分析逾時：
1. 保留原始圖片與任務資料
2. pose_status 設為 pending_retry 或 failed
3. 不繼續執行 Try-on / Magic Mirror 後續任務
4. 提示使用者稍後重試
```

Python 端處理策略：

```text
若 mock mode 開啟，應在 1 秒內回傳 mock keypoints。
若模型推論超時，回傳 AI_TIMEOUT。
若圖片中沒有人體，回傳 AI_NO_PERSON_DETECTED。
若圖片中有多人，回傳 AI_MULTI_PERSON_DETECTED。
```

---

### 8.11 Laravel 儲存建議

建議後續可建立 `pose_results` 或併入 `ai_jobs`。

| Laravel 欄位 | 來源 |
|---|---|
| user_id | user_id |
| task_type | task_type |
| image_path | image_path |
| status | status |
| mode | mode |
| pose_model | pose_model |
| person_count | person_count |
| keypoints_format | keypoints_format |
| keypoints | keypoints |
| pose_analysis | pose_analysis |
| annotated_image_url | annotated_image_url |
| raw_result | 完整 response JSON |
| error_code | error.code，若失敗 |
| error_message | error.message，若失敗 |

---

### 8.12 Laravel 呼叫 pseudocode

#### AiService

```php
class AiService
{
    public function analyzePose(
        int $userId,
        string $imagePath,
        string $taskType = 'magic_mirror',
        bool $returnAnnotatedImage = true
    ): array {
        $payload = [
            'schema_version' => 'v1',
            'request_id' => 'req_' . now()->format('Ymd_His') . '_pose_' . $userId,
            'user_id' => $userId,
            'image_path' => $imagePath,
            'image_url' => asset('storage/' . $imagePath),
            'task_type' => $taskType,
            'return_annotated_image' => $returnAnnotatedImage,
            'mock_mode' => config('ai.mock_mode'),
        ];

        try {
            $response = Http::timeout(config('ai.timeout_seconds'))
                ->withHeaders([
                    'X-Internal-AI-Token' => config('ai.internal_token'),
                    'Accept' => 'application/json',
                ])
                ->post(config('ai.service_url') . '/ai/pose', $payload);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'status' => 'failed',
                'error' => [
                    'code' => 'AI_HTTP_ERROR',
                    'message' => 'AI Service 回傳非成功狀態碼',
                    'details' => [
                        'http_status' => $response->status(),
                        'body' => $response->body(),
                    ],
                ],
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return [
                'status' => 'failed',
                'error' => [
                    'code' => 'AI_SERVICE_UNAVAILABLE',
                    'message' => '無法連線到 AI Service',
                    'details' => [
                        'exception' => $e->getMessage(),
                    ],
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'error' => [
                    'code' => 'AI_INTERNAL_CLIENT_ERROR',
                    'message' => 'Laravel 呼叫 pose 分析時發生錯誤',
                    'details' => [
                        'exception' => $e->getMessage(),
                    ],
                ],
            ];
        }
    }
}
```

---

#### Controller

```php
class MagicMirrorController extends Controller
{
    public function analyze(Request $request, AiService $aiService)
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'max:8192'],
            'task_type' => ['nullable', 'string'],
        ]);

        $path = $request->file('image')->store('tryon/' . auth()->id(), 'public');

        $result = $aiService->analyzePose(
            userId: auth()->id(),
            imagePath: $path,
            taskType: $validated['task_type'] ?? 'magic_mirror',
            returnAnnotatedImage: true
        );

        if (in_array($result['status'], ['success', 'degraded'])) {
            // 可存入 pose_results 或 ai_jobs
            return view('magic-mirror.result', [
                'imagePath' => $path,
                'poseResult' => $result,
                'isDegraded' => $result['status'] === 'degraded',
            ]);
        }

        return back()->withErrors([
            'image' => $result['error']['message'] ?? '姿態分析失敗，請重新上傳清楚的全身照片',
        ]);
    }
}
```

---

### 8.13 Python FastAPI pseudocode

```python
from fastapi import APIRouter, Header, HTTPException
from pydantic import BaseModel
from typing import Optional

router = APIRouter()

class PoseRequest(BaseModel):
    schema_version: str
    request_id: str
    user_id: int
    image_path: str
    image_url: Optional[str] = None
    task_type: Optional[str] = "magic_mirror"
    return_annotated_image: Optional[bool] = True
    mock_mode: Optional[bool] = True

@router.post("/ai/pose")
def analyze_pose(
    payload: PoseRequest,
    x_internal_ai_token: str = Header(None)
):
    if x_internal_ai_token != "change_this_internal_ai_token":
        raise HTTPException(status_code=401, detail={
            "status": "failed",
            "error": {
                "code": "AI_UNAUTHORIZED",
                "message": "Internal token 錯誤"
            }
        })

    if payload.mock_mode:
        return {
            "schema_version": "v1",
            "request_id": payload.request_id,
            "status": "degraded",
            "mode": "mock",
            "degraded_reason": "MOCK_POSE_ENABLED",
            "pose_model": "mock-pose",
            "person_count": 1,
            "image_size": {
                "width": 1080,
                "height": 1440
            },
            "keypoints_format": "coco_17",
            "keypoints": [
                {
                    "name": "nose",
                    "x": 540,
                    "y": 180,
                    "confidence": 0.70
                },
                {
                    "name": "left_shoulder",
                    "x": 410,
                    "y": 390,
                    "confidence": 0.70
                },
                {
                    "name": "right_shoulder",
                    "x": 670,
                    "y": 398,
                    "confidence": 0.70
                },
                {
                    "name": "left_hip",
                    "x": 440,
                    "y": 760,
                    "confidence": 0.65
                },
                {
                    "name": "right_hip",
                    "x": 650,
                    "y": 755,
                    "confidence": 0.65
                }
            ],
            "pose_analysis": {
                "full_body_visible": True,
                "shoulder_balance": "unknown",
                "shoulder_tilt_degree": None,
                "posture_notes": ["目前使用展示模式，姿態分析僅供流程展示"],
                "fit_notes": ["可用於 Try-on / Magic Mirror 介面測試"]
            },
            "annotated_image_url": None,
            "message": "目前為 mock mode，已回傳展示用 pose keypoints"
        }

    # TODO: 後續接入 YOLO Pose / MediaPipe / OpenPose
    return {
        "schema_version": "v1",
        "request_id": payload.request_id,
        "status": "failed",
        "error": {
            "code": "AI_POSE_MODEL_UNAVAILABLE",
            "message": "真實 pose 模型尚未接入"
        }
    }
```
