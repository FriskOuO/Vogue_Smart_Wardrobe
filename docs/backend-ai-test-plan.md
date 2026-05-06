# VogueAI Smart Wardrobe - Backend / AI Test Plan

## 1. 文件目的

本文件用於規劃 VogueAI Smart Wardrobe 的後端與 AI Service 測試策略。

目前專案採用：

```text
Laravel 主後端 + Blade + SQLite + Python FastAPI AI Service
```

目前已完成：

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

本測試計畫目的：

```text
1. 確認 Laravel 後端資料流正確
2. 確認 Python AI Service endpoint 正常
3. 確認 Laravel 可以穩定呼叫 AI Service
4. 確認 AI Service 不可用時系統可降級
5. 確認 ai_jobs 任務流程可被驗收
```

[ASSUMPTION] 目前測試先以 Mock-first 與 degraded 結果為主，不要求真實 AI 模型輸出。

---

## 2. 測試範圍

### 2.1 Laravel 後端測試

測試範圍包含：

```text
Auth / 權限
Admin middleware
Smart Closet CRUD
圖片上傳
AI attributes 寫回 clothes
image embedding 寫入 ai_embeddings
AI Search
Try-on L1
Runway Video L1
Digital Twin L1
ai_jobs 任務紀錄
```

---

### 2.2 Python AI Service 測試

測試範圍包含：

```text
GET /health
POST /ai/attributes
POST /ai/embed/image
POST /ai/embed/text
POST /ai/search/similar
POST /ai/pose
Internal Token 驗證
Validation Error
degraded response
```

---

### 2.3 整合測試

測試範圍包含：

```text
Laravel → Python AI Service
Python AI Service 關閉時 Laravel fallback
AI Search fallback
Try-on / Pose 任務流程
```

---

## 3. Laravel Feature Tests 規劃

### 3.1 Auth / 權限測試

| 測試項目 | 目標 | 預期結果 |
|---|---|---|
| 未登入進入 closet | 確認受 auth 保護 | redirect login |
| 一般 user 進入 Admin | 確認 admin middleware 生效 | forbidden / redirect |
| admin 進入 Admin Users | 確認管理者可進入 | status 200 |
| user role 欄位存在 | 確認角色資料可判斷 | role = admin/user |

---

### 3.2 Smart Closet 上傳測試

| 測試項目 | 目標 | 預期結果 |
|---|---|---|
| 上傳合法圖片 | 測試 closet.store | 建立 clothes |
| 沒有圖片 | 驗證表單錯誤 | validation error |
| 沒有名稱 | 驗證表單錯誤 | validation error |
| 圖片太大 | 驗證 max:5120 | validation error |
| 上傳成功後 redirect | 確認流程完成 | redirect closet.show |

---

### 3.3 AI attributes 寫回測試

| 測試項目 | 目標 | 預期結果 |
|---|---|---|
| AI 回傳 degraded | 確認可寫回 clothes | ai_status=degraded |
| category 寫入 | 確認屬性儲存 | category=上衣 |
| color 寫入 | 確認顏色儲存 | color=白色 |
| season 寫入 | 確認 JSON 欄位 | season 有資料 |
| AI failed | 確認失敗狀態 | ai_status=failed |

---

### 3.4 Image Embedding 測試

| 測試項目 | 目標 | 預期結果 |
|---|---|---|
| 上傳後產生 embedding | 確認 ai_embeddings 建立 | embedding_type=image |
| vector_dimension | 確認維度存在 | vector_dimension=8 |
| vector_provider | 確認 fallback provider | sqlite_fallback |
| 重複 reembed | 確認 updateOrCreate | 不重複建立多筆 |
| embedding failed | 確認錯誤紀錄 | status=failed |

---

### 3.5 Closet index / show 測試

| 測試項目 | 目標 | 預期結果 |
|---|---|---|
| closet.index 顯示衣物 | 確認讀取 DB | status 200 |
| 搜尋名稱 | 測試 SQL LIKE | 找到資料 |
| 搜尋顏色 | 測試 color search | 找到資料 |
| closet.show 顯示 AI 結果 | 確認詳細頁資料 | 顯示 category/color |
| 非本人衣物 | 確認資料隔離 | 404 |

---

### 3.6 重新分析 / 重新 embedding 測試

| 測試項目 | 目標 | 預期結果 |
|---|---|---|
| closet.reanalyze | 重新呼叫 attributes | ai_status 更新 |
| closet.reembed | 重新產生 embedding | ai_embeddings 更新 |
| 非本人衣物 reanalyze | 權限保護 | 404 |
| 非本人衣物 reembed | 權限保護 | 404 |

---

### 3.7 AI Search 測試

| 測試項目 | 目標 | 預期結果 |
|---|---|---|
| 有搜尋文字 | 呼叫 embedText + searchSimilar | 顯示結果 |
| 空搜尋 | 顯示提示 | 不報錯 |
| AI mock 回傳 id | Laravel 查 clothes | 顯示衣物卡片 |
| AI Service 不可用 | fallback keywordSearch | searchMode=keyword_fallback |
| fallback 搜尋 name | SQL LIKE | 找到資料 |
| fallback 搜尋 category | SQL LIKE | 找到資料 |

---

### 3.8 Try-on L1 測試

| 測試項目 | 目標 | 預期結果 |
|---|---|---|
| 開啟 tryon 頁 | 頁面可進入 | status 200 |
| 建立 Try-on 任務 | 測試 storeTryOn | 建立 ai_jobs |
| 上傳人物照片 | 測試 storage | 檔案保存 |
| 呼叫 /ai/pose | 測試 AI flow | result_json 有 keypoints |
| 任務狀態 | 確認 degraded | status=degraded |
| 沒選衣物 | validation | error |
| 沒人物照片 | validation | error |

---

### 3.9 Runway Video L1 測試

| 測試項目 | 目標 | 預期結果 |
|---|---|---|
| 開啟 runway workspace | 頁面可進入 | status 200 |
| 建立 storyboard 任務 | 測試 storeRunwayVideo | 建立 ai_jobs |
| result_json prompt | 確認 prompt | 有 prompt |
| result_json scenes | 確認分鏡 | 有 4 scenes |
| 任務狀態 | 確認 degraded | status=degraded |
| 沒選衣物 | validation | error |
| 沒影片風格 | validation | error |

---

### 3.10 Digital Twin L1 測試

| 測試項目 | 目標 | 預期結果 |
|---|---|---|
| 開啟 digital twin workspace | 頁面可進入 | status 200 |
| 建立 profile 任務 | 測試 storeDigitalTwin | 建立 ai_jobs |
| result_json profile | 確認個人資料 | 有 height/style |
| result_json style_summary | 確認摘要 | 有 description |
| 任務狀態 | 確認 degraded | status=degraded |
| 身高不合法 | validation | error |
| 缺風格偏好 | validation | error |

---

## 4. AI Service pytest 規劃

### 4.1 Health Check

| 測試項目 | 目標 | 預期結果 |
|---|---|---|
| GET /health | 確認服務啟動 | status=ok |
| dependencies | 確認依賴狀態 | 有 qdrant/clip/pose |
| mock_mode | 確認 mock 狀態 | true |

---

### 4.2 Internal Token 測試

| 測試項目 | 目標 | 預期結果 |
|---|---|---|
| 正確 token | 可呼叫 API | status 200 |
| 缺 token | 阻擋請求 | status 401 |
| 錯誤 token | 阻擋請求 | status 401 |
| error code | 確認錯誤碼 | AI_UNAUTHORIZED |

---

### 4.3 /ai/attributes 測試

| 測試項目 | 目標 | 預期結果 |
|---|---|---|
| 合法 payload | 回傳衣物屬性 | status=degraded |
| clothing_id | 確認回傳一致 | clothing_id 相同 |
| attributes | 確認欄位完整 | category/color/season |
| confidence | 確認信心分數 | overall 存在 |

---

### 4.4 /ai/embed/image 測試

| 測試項目 | 目標 | 預期結果 |
|---|---|---|
| 合法 payload | 回傳 image embedding | status=degraded |
| vector_dimension | 確認維度 | 8 |
| embedding | 確認向量 | list |
| vector_db | 確認 fallback | sqlite_fallback |

---

### 4.5 /ai/embed/text 測試

| 測試項目 | 目標 | 預期結果 |
|---|---|---|
| 合法 query | 回傳 text embedding | status=degraded |
| 空 query | 驗證錯誤 | status 422 |
| normalized_query | 確認 trim | 不為空 |
| logging | 確認 log | 有 request_id |

---

### 4.6 /ai/search/similar 測試

| 測試項目 | 目標 | 預期結果 |
|---|---|---|
| query_type=text | 可搜尋 | status=degraded |
| query_type=image | 可搜尋 | status=degraded |
| query_type 不合法 | validation | 422 |
| embedding 空 | validation | 422 |
| results | 回傳 topK | 有 clothing_id/score |

---

### 4.7 /ai/pose 測試

| 測試項目 | 目標 | 預期結果 |
|---|---|---|
| 合法 payload | 回傳 pose keypoints | status=degraded |
| keypoints | 確認座標 | list |
| pose_analysis | 確認分析 | full_body_visible |
| task_type | 確認任務 | magic_mirror / try_on_l1 |

---

## 5. Laravel → AI Service Integration Smoke Test

### 5.1 測試目標

確認 Laravel 可以透過 `App\Services\AiService` 呼叫 Python AI Service。

---

### 5.2 測試項目

| 測試項目 | 步驟 | 預期 |
|---|---|---|
| analyzeAttributes | tinker 呼叫 | status=degraded |
| embedImage | tinker 呼叫 | 有 embedding |
| embedText | tinker 呼叫 | 有 text embedding |
| searchSimilar | 使用 text embedding 呼叫 | 有 results |
| analyzePose | tinker 呼叫 | 有 keypoints |

---

### 5.3 Smoke Test 指令

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

---

## 6. 降級與錯誤測試

### 6.1 AI Service 關閉

| 測試項目 | 預期 |
|---|---|
| 新增衣物 | 衣物仍建立，ai_status=failed |
| AI Search | fallback 到 keywordSearch |
| Try-on L1 | job failed 或顯示錯誤 |
| 頁面 | 不應整頁爆掉 |

---

### 6.2 Mock / Degraded

| 測試項目 | 預期 |
|---|---|
| attributes degraded | Laravel 可寫回 DB |
| image embedding degraded | ai_embeddings 可寫入 |
| search degraded | 可顯示 mock 結果 |
| pose degraded | ai_jobs 可顯示 mock keypoints |

---

### 6.3 Validation Error

| 測試項目 | 預期 |
|---|---|
| 空 query | 422 |
| 空 embedding | 422 |
| 不合法 query_type | 422 |
| 缺上傳圖片 | Laravel validation error |
| 缺 clothing_id | Laravel validation error |

---

## 7. 最終驗收 Checklist

### 7.1 Smart Closet

```text
[ ] 可新增衣物
[ ] 可上傳圖片
[ ] 可寫入 clothes
[ ] 可呼叫 /ai/attributes
[ ] 可寫回 AI 屬性
[ ] 可呼叫 /ai/embed/image
[ ] 可寫入 ai_embeddings
[ ] 可重新分析 attributes
[ ] 可重新產生 embedding
[ ] 可查看衣物詳細頁
```

---

### 7.2 AI Search

```text
[ ] 可輸入搜尋文字
[ ] 可呼叫 /ai/embed/text
[ ] 可呼叫 /ai/search/similar
[ ] 可顯示搜尋結果
[ ] AI Service 關閉時可 fallback
[ ] fallback 可搜尋 name/category/color
```

---

### 7.3 Try-on L1

```text
[ ] 可進入 Try-on 頁
[ ] 可選擇衣物
[ ] 可上傳人物照片
[ ] 可建立 ai_jobs
[ ] 可呼叫 /ai/pose
[ ] 可顯示 keypoints 數量
[ ] 可顯示 posture notes
[ ] 可顯示 fit notes
```

---

### 7.4 Runway Video L1

```text
[ ] 可進入 runway-video workspace
[ ] 可選擇衣物
[ ] 可輸入影片風格
[ ] 可建立 ai_jobs
[ ] 可顯示 prompt
[ ] 可顯示 4 個 storyboard scenes
```

---

### 7.5 Digital Twin L1

```text
[ ] 可進入 digital-twin workspace
[ ] 可輸入身高
[ ] 可輸入風格偏好
[ ] 可輸入常見場合
[ ] 可建立 ai_jobs
[ ] 可顯示 avatar placeholder
[ ] 可顯示 style summary
[ ] 可顯示 style tags
```

---

### 7.6 AI Service

```text
[ ] /health 正常
[ ] /health 顯示 dependencies
[ ] /ai/attributes 正常
[ ] /ai/embed/image 正常
[ ] /ai/embed/text 正常
[ ] /ai/search/similar 正常
[ ] /ai/pose 正常
[ ] token 錯誤會被拒絕
[ ] validation error 會回傳 422
```

---

## 8. 下一步

下一步建議：

```text
第 7-B：先實作最小 smoke test 或整理測試 checklist
```

建議先做：

```text
1. 手動驗收 checklist
2. AI Service pytest skeleton
3. Laravel Feature Test skeleton
```

[ASSUMPTION] 若時間不足，專題展示前至少完成手動驗收 checklist 與 smoke test 紀錄。