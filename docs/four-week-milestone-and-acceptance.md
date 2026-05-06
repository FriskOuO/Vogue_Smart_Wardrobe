# VogueAI Smart Wardrobe - 4 週里程碑與驗收整理

## 1. 文件目的

本文件用於整理 VogueAI Smart Wardrobe 的 4 週交付計畫、目前實作進度、功能完整性判斷與後續補強方向。

目前專案採用：

```text
Laravel 12 + Blade + Vite + Tailwind
Python FastAPI AI Service
SQLite
Mock-first / Degraded AI 策略
```

目前開發分工：

```text
前端 Laravel + Blade + Vite + Tailwind：由組員負責
後端資料設計、AI Service、Laravel 串接：由我負責
```

本專題目標是不砍原有功能模組，但採用分階段交付：

```text
L1：可操作展示版
L2：流程完整版
L3：真模型 / 高品質外部服務版
```

---

## 2. 目前整體進度判斷

目前已完成的內容可分成兩類：

### 2.1 已具備真實後端資料流的功能

這些功能已經不是單純前端展示，而是有資料庫、Controller、Service 或測試支撐。

```text
Smart Closet
clothes 資料表
圖片上傳
AI attributes 寫回
image embedding 寫入 ai_embeddings
AI Search fallback
ai_jobs 任務紀錄
Laravel AiService
Python FastAPI AI Service
測試文件與 Feature Test skeleton
```

---

### 2.2 目前仍屬 L1 展示版的 AI 亮點功能

這些功能目前可操作、可建立任務、可顯示結果，但仍以 mock / degraded 為主。

```text
Try-on L1
Runway Video L1
Digital Twin L1
```

目前狀態：

| 功能 | 目前層級 | 說明 |
|---|---|---|
| Try-on | L1 | 可選衣物、上傳人物圖片、建立 pose job、顯示 mock keypoints |
| Runway Video | L1 | 可選衣物、建立 storyboard job、顯示 prompt 與 4 個分鏡 |
| Digital Twin | L1 | 可輸入身高 / 風格偏好 / 場合，建立個人風格卡 |
| AI Stylist | 尚未完整 | 頁面已有，尚未完成真實推薦資料流 |
| Community / Trend / Chat | 尚未完整 | workspace 入口已有，平台感功能尚待補強 |

---

## 3. 4 週交付計畫總表

| 週次 | 主軸 | 交付物 | 驗收條件 | Demo 流程 | 風險與降級策略 |
|---|---|---|---|---|---|
| 第 1 週 | 全站可用：Stub / 降級 / Feature Flags / Health / Migration Discipline | 完成 Laravel + Python AI Service 基礎架構；完成 API 契約、DB schema 文件；建立 `clothes`、`ai_embeddings`、`ai_jobs`；完成 `/health` 與五個 mock AI endpoint | Laravel 可登入；Admin/User 權限正常；Python `/health` 正常；Laravel 可透過 `AiService` 呼叫 AI；migration 不再因重複 Telescope 卡住 | 登入系統 → 開 Smart Closet → 展示 `/health` → 說明 Laravel 主後端 + Python AI Service 架構 | AI Service 缺模型 / key 時回 degraded；Telescope migration 重複時移到 backup；Qdrant / Redis 不可用時先 mock 或 fallback |
| 第 2 週 | 核心做真：Closet + Attributes + Search + Stylist 基礎 | 完成 Smart Closet 上傳流程；圖片進 storage；建立 clothes；呼叫 `/ai/attributes` 寫回屬性；呼叫 `/ai/embed/image` 寫入 embedding；完成 AI Search 與 keyword fallback | 新增衣物後可顯示 AI 分析；`ai_embeddings` 有資料；AI Search 可搜尋；AI Service 關閉時 fallback 不爆掉 | 新增衣物 → 看 AI 分析 → 搜尋「白色上衣」→ 關閉 AI Service → 展示 keyword fallback | 真 CLIP 尚未接入時 embedding 先 mock；AI Search 沒有真向量資料庫時先用 mock topK + SQL fallback |
| 第 3 週 | 平台感：Community + Trend + Chat | 建立 Community / Trend / Chat 的 L1 流程；Community 可先做展示或基本 CRUD；Trend 可先做每日趨勢報告；Chat 可先做 mock fashion Q&A | 使用者可進入 Community / Trend / Chat；至少能顯示或建立互動資料；Trend 有報告；Chat 有回應 | 開 Community → 展示穿搭互動概念 → 開 Trend → 顯示每日趨勢 → 開 Chat → 問穿搭問題 | 外部 API key 缺失時回 degraded；WebSocket 尚未完成時用一般頁面刷新；平台功能先保留 L1 |
| 第 4 週 | 亮點補強：Try-on / Digital Twin / Runway Video 分層完成 | 完成 Try-on L1、Runway Video L1、Digital Twin L1；補任務紀錄、狀態顯示、測試文件、部署手冊與一鍵啟動腳本 | Try-on 可建立 pose job；Runway 可建立 storyboard；Digital Twin 可建立 profile；`ai_jobs` 有紀錄；`start-all.ps1` 可啟動三服務 | 用 `start-all.ps1` 啟動 → Try-on 建任務 → Runway 建 storyboard → Digital Twin 建 profile → 開 `/health` 說明 degraded 策略 | 真 Try-on / 真影片 / 真 3D 成本高，展示時明確說明目前 L1；後續 L2/L3 再接模型或外部服務 |

---

## 4. 目前實際進度對照

| 原規劃 | 目前狀態 | 判斷 |
|---|---|---|
| 第 1 週：全站可用 | 已完成 | 架構、API 契約、DB schema、AI Service mock、health、migration discipline 已完成 |
| 第 2 週：核心做真 | 大部分完成 | Smart Closet 已有真後端資料流，AI Search 有 fallback |
| 第 3 週：平台感 | 尚未正式開始 | Community / Trend / Chat 目前多為 workspace 入口 |
| 第 4 週：亮點補強 | L1 已完成 | Try-on、Runway Video、Digital Twin 已可操作，但仍是 mock / degraded |

目前總結：

```text
第 1 週：完成
第 2 週：完成約 70%～80%
第 3 週：尚未完整實作
第 4 週：L1 完成，L2 / L3 尚未完成
```

---

## 5. 功能完整性分級

## 5.1 Smart Closet

| 層級 | 狀態 | 說明 |
|---|---|---|
| L1 | 已完成 | 可新增衣物、顯示衣物、上傳圖片 |
| L2 | 大部分完成 | 已接 DB、AI attributes、image embedding、重新分析、reembed |
| L3 | 尚未完成 | 尚未接真 CLIP / BLIP / Qdrant |

後續補強：

```text
1. 接真實 CLIP image embedding
2. 接 BLIP 圖片描述
3. 接 Qdrant 向量搜尋
4. 加入 wear_logs 與 outfit_logs
```

---

## 5.2 AI Search

| 層級 | 狀態 | 說明 |
|---|---|---|
| L1 | 已完成 | 可搜尋、可顯示結果 |
| L2 | 大部分完成 | 已有 text embedding 流程與 fallback |
| L3 | 尚未完成 | 尚未接真實向量資料庫與真 embedding |

後續補強：

```text
1. 使用真 CLIP text embedding
2. 使用 Qdrant 搜尋 topK
3. 加入 filter：類別、顏色、季節、場合
4. 加入搜尋結果排序與相似度說明
```

---

## 5.3 AI Stylist

| 層級 | 狀態 | 說明 |
|---|---|---|
| L1 | 尚未完成 | 可先做表單 + mock 推薦 |
| L2 | 尚未完成 | 從 clothes 資料表真的挑衣物 |
| L3 | 尚未完成 | 根據穿搭歷史與使用者偏好個人化推薦 |

建議優先補強：

```text
AI Stylist 是下一個最值得做的功能，因為它能把 Smart Closet、Digital Twin、AI Search 串起來。
```

L1 流程：

```text
輸入場合 / 天氣 / 風格
→ 從 clothes 選 2～3 件衣物
→ 建立 stylist_history
→ 顯示穿搭建議
```

---

## 5.4 Try-on

| 層級 | 狀態 | 說明 |
|---|---|---|
| L1 | 已完成 | 選衣物、上傳人物圖片、建立 pose job、顯示 mock pose |
| L2 | 尚未完成 | 接 YOLO Pose / MediaPipe，真的分析人體 keypoints |
| L3 | 尚未完成 | 接真實 virtual try-on API 或模型產生合成圖 |

後續補強：

```text
1. 接 YOLO Pose 或 MediaPipe
2. 顯示真 keypoints
3. 加入人物照片品質檢查
4. 加入重新分析按鈕
5. 未來接真 Try-on 生成模型
```

---

## 5.5 Runway Video

| 層級 | 狀態 | 說明 |
|---|---|---|
| L1 | 已完成 | 可建立 storyboard，顯示 prompt + 4 個 scenes |
| L2 | 尚未完成 | 加入 queue、任務進度、重新生成 |
| L3 | 尚未完成 | 接 Veo / RunwayML / Pika 等影片生成 API |

後續補強：

```text
1. 將 storyboard prompt 寫入 ai_jobs
2. 加入 processing / success / failed 狀態
3. 加入重新生成
4. 接外部 video generation API
```

---

## 5.6 Digital Twin

| 層級 | 狀態 | 說明 |
|---|---|---|
| L1 | 已完成 | 可建立個人風格卡 |
| L2 | 尚未完成 | 從衣櫥資料自動分析風格偏好 |
| L3 | 尚未完成 | 接 3D Avatar / 圖像生成 / 多視角生成 |

後續補強：

```text
1. 從 clothes 統計常見顏色 / 類別 / 風格
2. 從 stylist_history 學習偏好
3. 產生 Digital Twin style profile
4. 未來接 3D avatar 或圖片生成
```

---

## 5.7 Community / Trend / Chat

| 功能 | 目前狀態 | 後續方向 |
|---|---|---|
| Community | workspace 入口 | 可先做貼文 CRUD |
| Trend | workspace 入口 | 可先做每日趨勢 mock report |
| Chat | workspace 入口 | 可先做 mock fashion Q&A |

後續補強：

```text
第 3 週平台感若要補，建議先做 Trend / Chat L1，比 Community 完整 CRUD 更快展示。
```

---

## 6. 後續優先順序

| 優先 | 功能 | 原因 | 建議做法 |
|---|---|---|---|
| 1 | AI Stylist L1 / L2 | 最能串起現有資料 | 從 clothes 挑衣服，建立 stylist_history |
| 2 | Try-on L2 Pose 真分析 | 現有 Try-on L1 已完成 | 接 YOLO Pose 或 MediaPipe |
| 3 | Runway Video L2 | 讓 storyboard 更像完整任務流程 | 加入 queue 狀態、重新生成 |
| 4 | Digital Twin L2 | 可利用衣櫥資料做風格分析 | 統計衣物類別、顏色、風格 |
| 5 | Trend / Chat L1 | 提升平台感 | 先做 mock report / mock Q&A |
| 6 | Community L1 | 展示互動性 | 先做簡單貼文 CRUD |

---

## 7. 展示時建議說法

```text
目前系統採用分層交付策略。Smart Closet 與 AI Search 已經具備較完整的後端資料流，包含圖片上傳、資料庫寫入、AI attributes、embedding 與 fallback 搜尋。Try-on、Runway Video、Digital Twin 則先完成 L1 展示版，重點是確認使用者操作流程、ai_jobs 任務紀錄與前後端資料流完整。後續若要提升到 L2，會加入真實模型、任務進度、重新執行與更完整的 AI 分析。
```

```text
我們不是直接把功能砍掉，而是用 L1 / L2 / L3 的方式逐步完成。L1 先保證功能可以操作、可以建立任務、可以展示結果；L2 補上真實資料分析與任務流程；L3 再接高品質模型或外部生成服務。
```

---

## 8. 驗收總表

| 模組 | 目前可驗收項目 | 是否完成 |
|---|---|---|
| Auth | 登入、角色權限、Admin/User | 已完成 |
| Smart Closet | 上傳、列表、詳細、AI 分析 | 已完成 |
| AI Search | 以文搜圖、fallback | 已完成 |
| Try-on | L1 pose job | 已完成 |
| Runway Video | L1 storyboard job | 已完成 |
| Digital Twin | L1 profile job | 已完成 |
| AI Service | health、mock endpoint、dependencies | 已完成 |
| Tests | pytest skeleton、Feature Test skeleton | 已完成 |
| Deployment | demo guide、start-all.ps1 | 已完成 |
| AI Stylist | 真實推薦流程 | 待補強 |
| Community | 平台互動 | 待補強 |
| Trend | 趨勢報告 | 待補強 |
| Chat | 穿搭問答 | 待補強 |

---

## 9. 第 9 項結論

目前專題已經完成可展示的 MVP 骨架，不再只是單純前端畫面。

已完成：

```text
Laravel 主後端
Python AI Service
API 契約
核心資料表
Smart Closet 主流程
AI Search fallback
Try-on L1
Runway Video L1
Digital Twin L1
測試文件
Feature Test skeleton
部署手冊
一鍵啟動腳本
```

但仍需補強：

```text
AI Stylist 真實推薦
Try-on L2 真 pose
Runway Video L2 任務流程
Digital Twin L2 風格分析
Community / Trend / Chat 平台感
真實 CLIP / BLIP / Qdrant / 外部 API
```

下一步建議：

```text
第 10 項：Debug 流程與最後專題收尾提示詞整理
```