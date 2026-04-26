# VogueAI Smart Wardrobe - DB Schema Plan

## 1. 文件目的

本文件用於規劃 VogueAI Smart Wardrobe 後端資料表設計，主要對應目前採用的 A 架構：

```text
Laravel 主後端（Web + Auth + Blade + DB） + Python FastAPI AI Service
```

目前此文件先作為 schema 草稿，暫時不直接執行 migration，避免與組員可能已建立的 Smart Closet、Clothes、Outfits 或 Community 相關資料表衝突。

---

## 2. 目前專案狀態

已知 Laravel 專案已完成：

- Laravel 12 + Blade + Vite + Tailwind
- Laravel Breeze 登入 / 註冊
- Dashboard
- Account 管理
- Admin / User 角色權限
- `role` 欄位
- `EnsureUserIsAdmin` middleware
- i18n 中英文切換
- Dark mode
- Admin users CRUD
- SQLite 開發資料庫

[ASSUMPTION] 目前 Smart Closet / Clothes / Outfits 相關資料表是否已存在尚未確認，因此先以文件方式設計 schema。  
[ASSUMPTION] 開發環境使用 SQLite，JSON 欄位在 SQLite 中會以文字形式儲存，Laravel 可透過 `$casts` 轉成 array。  
[ASSUMPTION] 初期 AI 服務先採 mock mode，後續再替換成 CLIP、BLIP、YOLO Pose、Qdrant 等真實模型。  
[ASSUMPTION] 初期 AI 分析流程先採同步呼叫，未來可升級為 queue / background job。

---

## 3. 核心資料表規劃

最小可跑版本先規劃 3 張表：

```text
1. clothes
2. ai_embeddings
3. ai_jobs
```

後續擴充再加入：

```text
4. outfits
5. outfit_items
6. community_posts
7. chat_messages
8. trend_reports
9. showcase_items
10. pose_results
```

---

# 4. clothes：衣物資料表

## 4.1 用途

`clothes` 用於儲存使用者衣櫥中的衣物資料，是 Smart Closet 的核心資料表。

主要支援：

- 衣物圖片上傳
- 衣物基本資料管理
- AI 屬性辨識結果
- 穿搭推薦
- 以圖搜圖 / 以文搜圖
- 穿著次數與最近穿著日期

---

## 4.2 欄位設計

| 欄位 | 型別 | 說明 |
|---|---|---|
| id | bigint | 主鍵 |
| user_id | foreignId | 對應 users.id |
| name | string / nullable | 衣物名稱 |
| image_path | string | Laravel storage 中的圖片路徑 |
| image_url | string / nullable | 對外可讀取圖片 URL |
| category | string / nullable | 類別，例如上衣、褲子、外套、鞋子 |
| subcategory | string / nullable | 子類別，例如襯衫、T-shirt、牛仔褲 |
| color | string / nullable | 主要顏色 |
| secondary_colors | json / nullable | 次要顏色陣列 |
| season | json / nullable | 適合季節，例如春、夏、秋、冬 |
| occasion | json / nullable | 適合場合，例如日常、正式、約會 |
| usage | json / nullable | 用途，例如通勤、校園穿搭、聚會 |
| style_tags | json / nullable | 風格標籤，例如簡約、韓系、休閒 |
| material_guess | string / nullable | AI 推測材質 |
| pattern | string / nullable | 圖樣，例如素色、條紋、格紋 |
| brand | string / nullable | 品牌 |
| price | decimal / nullable | 價格 |
| size | string / nullable | 尺寸 |
| note | text / nullable | 使用者備註 |
| wear_count | unsignedInteger | 穿著次數，預設 0 |
| last_worn_at | timestamp / nullable | 最近穿著時間 |
| ai_status | string | pending / success / degraded / failed |
| ai_mode | string / nullable | model / mock / fallback |
| ai_confidence | decimal / nullable | AI 整體信心分數 |
| ai_raw_result | json / nullable | AI 完整回傳結果 |
| ai_error_code | string / nullable | AI 錯誤碼 |
| ai_error_message | text / nullable | AI 錯誤訊息 |
| created_at | timestamp | 建立時間 |
| updated_at | timestamp | 更新時間 |
| deleted_at | softDeletes / nullable | 軟刪除 |

---

## 4.3 Laravel migration 草稿

```php
Schema::create('clothes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();

    $table->string('name')->nullable();
    $table->string('image_path');
    $table->string('image_url')->nullable();

    $table->string('category')->nullable();
    $table->string('subcategory')->nullable();
    $table->string('color')->nullable();
    $table->json('secondary_colors')->nullable();
    $table->json('season')->nullable();
    $table->json('occasion')->nullable();
    $table->json('usage')->nullable();
    $table->json('style_tags')->nullable();

    $table->string('material_guess')->nullable();
    $table->string('pattern')->nullable();
    $table->string('brand')->nullable();
    $table->decimal('price', 10, 2)->nullable();
    $table->string('size')->nullable();
    $table->text('note')->nullable();

    $table->unsignedInteger('wear_count')->default(0);
    $table->timestamp('last_worn_at')->nullable();

    $table->string('ai_status')->default('pending');
    $table->string('ai_mode')->nullable();
    $table->decimal('ai_confidence', 5, 4)->nullable();
    $table->json('ai_raw_result')->nullable();
    $table->string('ai_error_code')->nullable();
    $table->text('ai_error_message')->nullable();

    $table->timestamps();
    $table->softDeletes();

    $table->index(['user_id', 'category']);
    $table->index(['user_id', 'color']);
    $table->index(['user_id', 'ai_status']);
});
```

---

## 4.4 Model casts 建議

```php
protected $casts = [
    'secondary_colors' => 'array',
    'season' => 'array',
    'occasion' => 'array',
    'usage' => 'array',
    'style_tags' => 'array',
    'ai_raw_result' => 'array',
    'last_worn_at' => 'datetime',
    'ai_confidence' => 'float',
];
```

---

# 5. ai_embeddings：AI 向量資料表

## 5.1 用途

`ai_embeddings` 用於儲存 image embedding 或 text embedding 的結果。

主要支援：

- image embedding
- text embedding
- 以圖搜圖
- 以文搜圖
- Qdrant point_id 對應
- SQLite fallback 搜尋

---

## 5.2 欄位設計

| 欄位 | 型別 | 說明 |
|---|---|---|
| id | bigint | 主鍵 |
| user_id | foreignId | 對應 users.id |
| clothing_id | foreignId / nullable | 對應 clothes.id，text query 可為 null |
| embedding_type | string | image / text |
| source_type | string / nullable | clothing / search_query / outfit |
| source_text | text / nullable | text embedding 的原始文字 |
| model | string / nullable | embedding 模型名稱 |
| vector_dimension | integer / nullable | 向量維度 |
| embedding | json / nullable | 完整向量 |
| embedding_preview | json / nullable | 前幾個值，方便除錯 |
| vector_provider | string / nullable | qdrant / sqlite_fallback / mock |
| vector_collection | string / nullable | Qdrant collection 名稱 |
| vector_point_id | string / nullable | Qdrant point ID |
| vector_stored | boolean | 是否已存入向量資料庫 |
| status | string | pending / success / degraded / failed |
| mode | string / nullable | model / mock / fallback |
| degraded_reason | string / nullable | 降級原因 |
| raw_result | json / nullable | AI 完整回傳結果 |
| error_code | string / nullable | 錯誤碼 |
| error_message | text / nullable | 錯誤訊息 |
| created_at | timestamp | 建立時間 |
| updated_at | timestamp | 更新時間 |

---

## 5.3 Laravel migration 草稿

```php
Schema::create('ai_embeddings', function (Blueprint $table) {
    $table->id();

    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('clothing_id')->nullable()->constrained('clothes')->nullOnDelete();

    $table->string('embedding_type'); // image / text
    $table->string('source_type')->nullable(); // clothing / search_query / outfit
    $table->text('source_text')->nullable();

    $table->string('model')->nullable();
    $table->unsignedInteger('vector_dimension')->nullable();
    $table->json('embedding')->nullable();
    $table->json('embedding_preview')->nullable();

    $table->string('vector_provider')->nullable(); // qdrant / sqlite_fallback / mock
    $table->string('vector_collection')->nullable();
    $table->string('vector_point_id')->nullable();
    $table->boolean('vector_stored')->default(false);

    $table->string('status')->default('pending');
    $table->string('mode')->nullable();
    $table->string('degraded_reason')->nullable();

    $table->json('raw_result')->nullable();
    $table->string('error_code')->nullable();
    $table->text('error_message')->nullable();

    $table->timestamps();

    $table->index(['user_id', 'embedding_type']);
    $table->index(['clothing_id', 'embedding_type']);
    $table->index(['vector_provider', 'vector_collection']);
});
```

---

## 5.4 Model casts 建議

```php
protected $casts = [
    'embedding' => 'array',
    'embedding_preview' => 'array',
    'raw_result' => 'array',
    'vector_stored' => 'boolean',
];
```

---

# 6. ai_jobs：AI 任務資料表

## 6.1 用途

`ai_jobs` 用於儲存較長時間或非同步的 AI 任務，例如 Try-on、Digital Twin、Runway Video、Magic Mirror、AI Stylist。

主要支援：

- 任務狀態追蹤
- 任務輸入資料
- 任務輸出結果
- 錯誤紀錄
- 降級策略
- 未來 queue / background job 擴充

---

## 6.2 job_type 建議

| job_type | 說明 |
|---|---|
| clothing_attributes | 衣物屬性辨識 |
| image_embedding | 圖片 embedding |
| text_embedding | 文字 embedding |
| similar_search | 相似搜尋 |
| pose_analysis | 姿態分析 |
| stylist_recommendation | AI Stylist 穿搭推薦 |
| try_on | 虛擬試穿 |
| digital_twin | 3D 數位分身 |
| runway_video | Runway Video |
| trend_report | 趨勢報告 |
| chat_response | AI Chat 回覆 |

---

## 6.3 status 建議

| status | 說明 |
|---|---|
| pending | 等待處理 |
| processing | 處理中 |
| success | 成功 |
| degraded | 降級成功 |
| failed | 失敗 |
| pending_retry | 等待重試 |
| cancelled | 已取消 |

---

## 6.4 欄位設計

| 欄位 | 型別 | 說明 |
|---|---|---|
| id | bigint | 主鍵 |
| user_id | foreignId | 對應 users.id |
| clothing_id | foreignId / nullable | 對應 clothes.id |
| job_type | string | 任務類型 |
| status | string | 任務狀態 |
| mode | string / nullable | model / mock / fallback |
| request_id | string / nullable | API request ID |
| input_json | json / nullable | 任務輸入資料 |
| result_json | json / nullable | 任務輸出結果 |
| degraded_reason | string / nullable | 降級原因 |
| error_code | string / nullable | 錯誤碼 |
| error_message | text / nullable | 錯誤訊息 |
| retry_count | unsignedInteger | 重試次數 |
| started_at | timestamp / nullable | 開始時間 |
| completed_at | timestamp / nullable | 完成時間 |
| created_at | timestamp | 建立時間 |
| updated_at | timestamp | 更新時間 |

---

## 6.5 Laravel migration 草稿

```php
Schema::create('ai_jobs', function (Blueprint $table) {
    $table->id();

    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('clothing_id')->nullable()->constrained('clothes')->nullOnDelete();

    $table->string('job_type');
    $table->string('status')->default('pending');
    $table->string('mode')->nullable();

    $table->string('request_id')->nullable();
    $table->json('input_json')->nullable();
    $table->json('result_json')->nullable();

    $table->string('degraded_reason')->nullable();
    $table->string('error_code')->nullable();
    $table->text('error_message')->nullable();

    $table->unsignedInteger('retry_count')->default(0);
    $table->timestamp('started_at')->nullable();
    $table->timestamp('completed_at')->nullable();

    $table->timestamps();

    $table->index(['user_id', 'job_type']);
    $table->index(['status']);
    $table->index(['request_id']);
});
```

---

## 6.6 Model casts 建議

```php
protected $casts = [
    'input_json' => 'array',
    'result_json' => 'array',
    'started_at' => 'datetime',
    'completed_at' => 'datetime',
];
```

---

# 7. 未來擴充資料表

## 7.1 outfits

用於儲存穿搭組合。

| 欄位 | 說明 |
|---|---|
| user_id | 使用者 |
| title | 穿搭名稱 |
| description | 穿搭說明 |
| occasion | 場合 |
| weather | 天氣 |
| style | 風格 |
| image_path | 穿搭圖片 |
| ai_score | AI 評分 |
| ai_reason | AI 評語 |

---

## 7.2 outfit_items

用於關聯 outfits 與 clothes。

| 欄位 | 說明 |
|---|---|
| outfit_id | 穿搭 ID |
| clothing_id | 衣物 ID |
| layer_order | 穿搭層次順序 |
| role | top / bottom / shoes / accessory |

---

## 7.3 community_posts

用於社群貼文。

| 欄位 | 說明 |
|---|---|
| user_id | 發文者 |
| outfit_id | 對應穿搭 |
| caption | 貼文文字 |
| image_path | 貼文圖片 |
| visibility | public / private |
| like_count | 按讚數 |
| comment_count | 留言數 |

---

## 7.4 chat_messages

用於 AI Chat / AI Bestie 對話紀錄。

| 欄位 | 說明 |
|---|---|
| user_id | 使用者 |
| role | user / assistant |
| message | 對話內容 |
| context_json | 對話上下文 |
| ai_status | AI 回覆狀態 |

---

## 7.5 trend_reports

用於 Daily Trend Report。

| 欄位 | 說明 |
|---|---|
| user_id | 使用者 |
| report_date | 報告日期 |
| trend_summary | 趨勢摘要 |
| suggested_styles | 推薦風格 |
| raw_result | AI 完整結果 |

---

# 8. 與 API 契約對應關係

| API 端點 | 主要資料表 |
|---|---|
| `POST /ai/attributes` | clothes、ai_jobs |
| `POST /ai/embed/image` | ai_embeddings、ai_jobs |
| `POST /ai/embed/text` | ai_embeddings 或 search_logs |
| `POST /ai/search/similar` | ai_embeddings、clothes |
| `POST /ai/pose` | ai_jobs 或 pose_results |

---

# 9. 階段性實作建議

## Phase 1：最小可跑版本

先實作：

```text
clothes
ai_embeddings
ai_jobs
```

目標：

- 可以新增衣物。
- 可以存圖片。
- 可以存 AI 屬性分析結果。
- 可以存 image embedding。
- 可以記錄 AI job 狀態。

---

## Phase 2：Smart Closet 搜尋與推薦

新增或擴充：

```text
search_logs
outfits
outfit_items
```

目標：

- 以文搜圖。
- 以圖搜圖。
- AI Stylist 推薦穿搭。
- 儲存穿搭組合。

---

## Phase 3：Try-on / Magic Mirror / Runway Video

新增或擴充：

```text
pose_results
ai_jobs
```

目標：

- 儲存姿態分析結果。
- 儲存 Try-on 任務結果。
- 儲存 Runway Video 任務狀態。

---

## Phase 4：Community / Trend / Chat / Showcase

新增：

```text
community_posts
comments
likes
chat_messages
trend_reports
showcase_items
```

目標：

- 社群分享。
- AI Chat。
- 趨勢報告。
- 展示作品集。

---

# 10. 注意事項

1. 若組員已建立 `clothes` 或 `closet_items`，不要重複建立同名資料表。
2. 若組員已使用不同表名，需先對齊命名。
3. migration 執行前，應先確認現有資料表。
4. 若正式開發仍使用 SQLite，JSON 欄位可用，但查詢 JSON 內容不如 MySQL / PostgreSQL 方便。
5. 若未來使用 Qdrant，Laravel 不一定要保存完整 embedding，可只保存 `vector_point_id`。
6. 若 AI 模型尚未完成，允許 `status=degraded` 與 `mode=mock` 作為展示降級策略。