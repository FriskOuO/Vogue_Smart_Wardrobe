# VogueAI Smart Wardrobe - Try-on / Digital Twin / Runway Video 分層交付設計

## 1. 文件目的

本文件用於規劃 VogueAI Smart Wardrobe 中三個高亮點 AI 功能的分層交付策略：

```text
1. Virtual Try-on
2. Digital Twin
3. Runway Video
```

本專題目標是不砍功能，但採用分階段交付與降級策略，讓系統在不同開發階段都能展示完整概念。

---

## 2. 分層交付原則

### L1：可操作展示版

L1 的目標是：

```text
使用者可以點擊功能
系統可以產生結果
畫面可以展示流程
即使是 mock / placeholder / degraded 也可以接受
```

適合：

```text
期中展示
初步驗收
確認頁面流程與 API 契約
```

---

### L2：流程完整版

L2 的目標是：

```text
加入任務狀態
加入資料庫紀錄
加入 AI jobs
加入等待 / 成功 / 失敗 / 重試流程
部分功能開始接簡易模型或半真實處理
```

適合：

```text
期末前整合
功能驗收
展示系統完整性
```

---

### L3：高品質模型版

L3 的目標是：

```text
接入真實 AI 模型或外部 API
輸出品質更高
展示效果更接近正式產品
```

適合：

```text
最終展示
競賽展示
專題亮點展示
```

---

## 3. 共用任務狀態設計

Try-on、Digital Twin、Runway Video 都建議使用 `ai_jobs` 紀錄任務狀態。

共用狀態：

| status | 說明 |
|---|---|
| `pending` | 任務已建立，等待處理 |
| `processing` | 任務處理中 |
| `success` | 任務成功完成 |
| `degraded` | 使用降級模式完成 |
| `failed` | 任務失敗 |
| `pending_retry` | 等待重新執行 |
| `cancelled` | 任務取消 |

共用欄位概念：

```text
user_id
clothing_id
job_type
status
mode
request_id
input_json
result_json
degraded_reason
error_code
error_message
retry_count
started_at
completed_at
```

---

# 4. Virtual Try-on 分層交付

## 4.1 功能定位

Virtual Try-on 主要讓使用者上傳人物照片或使用現有衣物，產生試穿效果或試穿建議。

在本專題中，Try-on 不一定一開始就要做到真正「換衣服圖片合成」，可以先從流程展示、姿態分析、衣物疊圖或示意結果開始。

---

## 4.2 L1：Try-on 可操作展示版

### 目標

```text
使用者可以進入 Try-on 頁面
選擇一件衣物
上傳人物圖片或使用示範人物圖
按下開始 Try-on
系統回傳 mock / degraded 結果
畫面顯示 Try-on 結果卡片
```

### 可交付內容

- `closet.tryon` 頁面已存在
- 表單可選衣物
- 表單可上傳人物圖片
- Laravel 建立 `ai_jobs` 任務
- Python `/ai/pose` 回傳 mock keypoints
- 畫面顯示：
  - 任務狀態
  - 姿態分析結果
  - mock 試穿建議
  - degraded 提示

### 依賴

| 項目 | 狀態 |
|---|---|
| Laravel Blade | 已有頁面 |
| `ai_jobs` | 已建立 |
| `/ai/pose` | 已有 mock endpoint |
| YOLO Pose | L1 不需要 |
| Try-on 真模型 | L1 不需要 |

### 降級條件

| 條件 | 降級方式 |
|---|---|
| 沒有 YOLO Pose | 使用 mock keypoints |
| 沒有人物圖片 | 使用 placeholder |
| AI Service 不可用 | 建立 failed job，允許重新分析 |
| 沒有真 Try-on 模型 | 顯示試穿建議卡片，不生成真合成圖 |

### 驗收方式

```text
1. 使用者可以打開 Try-on 頁面
2. 可以送出 Try-on 表單
3. 系統建立 ai_jobs
4. 頁面顯示 degraded / mock 結果
5. 不會因缺模型而爆掉
```

### Demo 講法

```text
目前 Try-on 採 L1 降級展示模式。系統已經完成前後端資料流與任務狀態紀錄，會先回傳姿態分析與試穿建議。後續若接入 YOLO Pose 或真實 Try-on 模型，可以直接替換 AI Service 的推論邏輯，而 Laravel 流程不需要大改。
```

### 錯誤回應格式

```json
{
  "schema_version": "v1",
  "request_id": "req_xxx",
  "status": "degraded",
  "mode": "mock",
  "degraded_reason": "TRY_ON_MODEL_NOT_AVAILABLE",
  "message": "目前使用 Try-on 展示模式"
}
```

---

## 4.3 L2：Try-on 流程完整版

### 目標

```text
加入任務進度
加入 pose 分析結果
加入簡易圖片處理或人物姿態檢查
支援重新執行
```

### 可交付內容

- `ai_jobs` 完整記錄 Try-on 任務
- 任務狀態顯示：
  - pending
  - processing
  - degraded
  - failed
  - success
- 使用 `/ai/pose` 分析人物圖片
- 顯示：
  - 是否全身入鏡
  - 肩膀平衡
  - 姿態提醒
  - 試穿適配建議

### 依賴

| 項目 | 狀態 |
|---|---|
| YOLO Pose 或 MediaPipe | 建議接入 |
| queue | 可選 |
| Redis | 可選 |
| storage | 需要 |

### 降級條件

| 條件 | 降級方式 |
|---|---|
| pose 模型不可用 | mock pose |
| queue 不可用 | 同步執行 |
| 圖片處理失敗 | 保留原圖並回傳建議 |
| 模型逾時 | status=failed 或 degraded |

### 驗收方式

```text
1. 任務會寫入 ai_jobs
2. Try-on 頁面可看到任務狀態
3. Pose 分析結果可顯示在頁面
4. 可重新執行任務
```

---

## 4.4 L3：Try-on 高品質模型版

### 目標

```text
接入真實 Try-on 模型或外部服務
產生試穿合成圖
```

### 可選方案

| 方案 | 說明 |
|---|---|
| 本機 Try-on model | 品質可控，但環境較複雜 |
| 外部 API | 成本較高，但展示效果較好 |
| Gemini multimodal | 可做視覺分析，不一定能真合成 |
| 第三方 virtual try-on API | 適合最終展示 |

### 降級條件

| 條件 | 降級方式 |
|---|---|
| 外部 API key 缺失 | 回 L2 pose + 建議 |
| API 超時 | 回 degraded |
| 圖片不符合要求 | 回傳重新上傳提示 |
| 生成失敗 | 顯示 mock result |

---

# 5. Digital Twin 分層交付

## 5.1 功能定位

Digital Twin 用於建立使用者的虛擬分身或穿搭模擬基礎。

本專題中可將 Digital Twin 定位為：

```text
使用者建立個人穿搭角色資料
系統記錄身形 / 風格偏好 / 常穿尺寸
後續支援 Try-on、Stylist、Runway Video
```

---

## 5.2 L1：Digital Twin Profile 展示版

### 目標

```text
使用者可以建立簡單 Digital Twin Profile
輸入身高、風格偏好、穿搭場合
系統產生一張 mock avatar 或角色卡
```

### 可交付內容

- Digital Twin workspace 頁面
- 建立 profile 表單
- 儲存到 JSON 或 `ai_jobs.result_json`
- 顯示 avatar placeholder
- 顯示：
  - 身高
  - 偏好風格
  - 常見場合
  - 推薦穿搭方向

### 依賴

| 項目 | 狀態 |
|---|---|
| Blade 頁面 | workspace 已有 |
| AI Service | 可用 mock |
| 真 3D 模型 | L1 不需要 |
| Gemini | L1 不需要 |

### 降級條件

| 條件 | 降級方式 |
|---|---|
| 沒有 3D 模型 | 顯示 avatar placeholder |
| 沒有外部 API | 回傳 mock profile |
| 使用者資料不足 | 顯示待補資料 |

### 驗收方式

```text
1. 可進入 Digital Twin workspace
2. 可建立或顯示角色卡
3. 系統回傳 mock twin profile
4. 不依賴真 3D 模型也能展示
```

### Demo 講法

```text
Digital Twin 目前採 L1 個人穿搭角色卡模式，先建立使用者偏好與身形資料。這些資料之後可以被 AI Stylist、Try-on 和 Runway Video 共用，作為個人化推薦基礎。
```

---

## 5.3 L2：Digital Twin 多資料整合版

### 目標

```text
整合穿搭紀錄
整合使用者偏好
整合衣櫥資料
產生更完整的個人風格摘要
```

### 可交付內容

- 使用 closet 資料分析常見顏色、類別
- 使用 outfit logs 分析偏好
- 使用 stylist_history 分析接受推薦紀錄
- 產生 Digital Twin style profile

### 依賴

| 項目 | 狀態 |
|---|---|
| clothes | 已有 |
| ai_embeddings | 已有 |
| outfit_logs | 待建立 |
| stylist_history | 待建立 |

### 降級條件

| 條件 | 降級方式 |
|---|---|
| 穿搭紀錄不足 | 根據衣櫥資料推估 |
| embedding 不足 | 使用 rule-based 統計 |
| AI 不可用 | 顯示統計摘要 |

---

## 5.4 L3：Digital Twin 生成版

### 目標

```text
接入外部生成式 AI 或 3D avatar 服務
產生更具視覺效果的 Digital Twin
```

### 可選方案

| 方案 | 說明 |
|---|---|
| Gemini multimodal | 產生個人穿搭風格摘要 |
| 3D Avatar API | 產生 avatar |
| 圖像生成 API | 產生角色形象 |
| WebGL / Three.js | 前端展示 3D avatar |

### 降級條件

| 條件 | 降級方式 |
|---|---|
| API key 缺失 | 回 L2 style profile |
| 生成失敗 | 回 avatar placeholder |
| 3D 不可用 | 回 2D profile card |

---

# 6. Runway Video 分層交付

## 6.1 功能定位

Runway Video 是專題展示亮點，目標是讓使用者選擇衣物或穿搭，產生類似時尚走秀的展示效果。

---

## 6.2 L1：Runway Storyboard 展示版

### 目標

```text
使用者選擇一件衣物或一套穿搭
系統產生 runway storyboard
顯示 mock 影片卡片或動畫 placeholder
```

### 可交付內容

- Workspace Runway Video 頁面
- 選擇衣物
- 建立 `ai_jobs`
- 回傳 mock storyboard：
  - scene 1：走秀開場
  - scene 2：正面展示
  - scene 3：細節展示
  - scene 4：結尾展示
- 顯示 degraded / mock 狀態

### 依賴

| 項目 | 狀態 |
|---|---|
| clothes | 已有 |
| ai_jobs | 已有 |
| 真影片生成 | L1 不需要 |
| Veo API | L1 不需要 |

### 降級條件

| 條件 | 降級方式 |
|---|---|
| 沒有影片生成 API | 顯示 storyboard |
| 沒有衣物圖片 | 顯示 placeholder |
| AI Service 不可用 | 建立 failed job |

### 驗收方式

```text
1. 可以建立 runway video job
2. 頁面顯示 storyboard
3. 顯示 mock/degraded 狀態
4. 不需要真影片也能展示概念
```

### Demo 講法

```text
Runway Video 目前採 L1 Storyboard 模式，系統會依照衣物產生走秀分鏡與展示卡片。未來若接入 Veo 或其他影片生成 API，可以將 storyboard prompt 直接轉成影片生成任務。
```

---

## 6.3 L2：Runway Prompt + Queue 版

### 目標

```text
產生影片 prompt
建立 queue job
顯示任務狀態
支援重新生成
```

### 可交付內容

- 根據衣物資料產生 prompt
- 寫入 `ai_jobs.input_json`
- 任務狀態：
  - pending
  - processing
  - degraded
  - failed
  - success
- 頁面顯示 prompt 與 storyboard

### 依賴

| 項目 | 狀態 |
|---|---|
| ai_jobs | 已有 |
| queue | 可選 |
| Laravel scheduler | 可選 |
| 外部影片 API | 可選 |

### 降級條件

| 條件 | 降級方式 |
|---|---|
| queue 不可用 | 同步建立 job |
| 外部 API 不可用 | 保留 prompt/storyboard |
| API 超時 | pending_retry |

---

## 6.4 L3：Veo / 外部影片生成版

### 目標

```text
接入 Google Veo 或其他 image-to-video API
產生真實 runway video
```

### 可選方案

| 方案 | 說明 |
|---|---|
| Google Veo | 高品質影片生成 |
| RunwayML API | 影片生成服務 |
| Pika / Kling | 其他生成式影片服務 |
| 本地模型 | 成本低但硬體要求高 |

### 降級條件

| 條件 | 降級方式 |
|---|---|
| Veo key 缺失 | 回 L2 storyboard |
| API 額度不足 | 回 pending_retry |
| 生成失敗 | 顯示 prompt + storyboard |
| 等待時間過長 | 顯示 processing |

---

# 7. 共用錯誤回應格式

## 7.1 Degraded

```json
{
  "schema_version": "v1",
  "request_id": "req_xxx",
  "status": "degraded",
  "mode": "mock",
  "degraded_reason": "MODEL_NOT_AVAILABLE",
  "message": "目前使用展示模式回傳結果"
}
```

---

## 7.2 Failed

```json
{
  "schema_version": "v1",
  "request_id": "req_xxx",
  "status": "failed",
  "error": {
    "code": "AI_JOB_FAILED",
    "message": "AI 任務執行失敗",
    "details": {}
  }
}
```

---

# 8. 優先順序建議

## 第一優先：Try-on L1

原因：

```text
/ai/pose 已有 mock endpoint
closet.tryon 頁面已存在
ai_jobs 已存在
最容易接出可展示流程
```

---

## 第二優先：Runway Video L1

原因：

```text
ai_jobs 已存在
可用 storyboard 模式展示
不需要真影片 API
展示效果佳
```

---

## 第三優先：Digital Twin L1

原因：

```text
workspace 頁面已存在
可先做個人風格角色卡
但需要先定義 profile 資料格式
```

---

# 9. 下一步

建議下一步先做：

```text
第 6-A：建立 Try-on L1 任務流程
```

流程：

```text
closet.tryon
→ 選擇衣物
→ 上傳人物圖片或使用示範圖
→ 建立 ai_jobs
→ 呼叫 /ai/pose
→ 寫回 ai_jobs.result_json
→ 頁面顯示 mock pose / degraded 結果
```