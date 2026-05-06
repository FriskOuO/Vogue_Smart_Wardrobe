# VogueAI Smart Wardrobe - Test Execution Record

## 1. 文件目的

本文件用於記錄 VogueAI Smart Wardrobe 目前後端與 AI Service 的測試執行狀態。

目前專案已完成：

```text
Smart Closet 上傳與 AI 分析流程
AI Search 以文搜圖 / keyword fallback
Try-on L1
Runway Video L1
Digital Twin L1
Python FastAPI AI Service 工程化
AI Service pytest skeleton
Laravel Feature Test skeleton
```

本文件可作為：

```text
1. GitHub 進度紀錄
2. 專題報告測試章節素材
3. 組員交接文件
4. Demo 前驗收依據
```

---

## 2. 測試環境

目前測試環境：

| 項目 | 說明 |
|---|---|
| Backend | Laravel 12 |
| Frontend | Blade + Vite + TailwindCSS |
| Database | SQLite |
| AI Service | Python FastAPI |
| AI 策略 | Mock-first / degraded |
| 測試工具 | Laravel Feature Test、pytest |

[ASSUMPTION] 測試目前以本機開發環境為主，尚未進入正式部署環境。

---

## 3. 已建立測試文件

目前已建立：

```text
docs/backend-ai-test-plan.md
docs/manual-acceptance-checklist.md
docs/test-execution-record.md
```

用途：

| 文件 | 用途 |
|---|---|
| `backend-ai-test-plan.md` | 後端 / AI 測試規劃 |
| `manual-acceptance-checklist.md` | 展示前人工驗收 checklist |
| `test-execution-record.md` | 實際測試執行紀錄 |

---

## 4. AI Service pytest 測試

### 4.1 測試檔案

目前已建立：

```text
ai_service/tests/test_health.py
ai_service/tests/test_ai_routes.py
ai_service/tests/test_security_validation.py
```

---

### 4.2 測試範圍

目前 pytest skeleton 已涵蓋：

```text
GET /health
POST /ai/attributes
POST /ai/embed/image
POST /ai/embed/text
POST /ai/search/similar
POST /ai/pose
Internal Token 驗證
Validation Error
```

---

### 4.3 執行指令

在專案根目錄：

```powershell
cd ai_service
python -m pytest
```

或：

```powershell
pytest
```

---

### 4.4 預期結果

預期所有 AI Service 測試通過：

```text
test_health.py
test_ai_routes.py
test_security_validation.py
```

測試通過代表：

```text
1. /health 可正常回應
2. 五個 AI endpoint 可正常回傳 degraded/mock
3. token 缺失或錯誤會被拒絕
4. 空 query / 空 embedding / 錯誤 query_type 可正確回傳 validation error
```

---

## 5. Laravel Feature Tests

### 5.1 測試檔案

目前已建立：

```text
tests/Feature/SmartClosetTest.php
tests/Feature/AiSearchTest.php
tests/Feature/AiJobsL1Test.php
```

---

### 5.2 SmartClosetTest

執行指令：

```powershell
php artisan test tests/Feature/SmartClosetTest.php
```

目前結果：

```text
PASS  Tests\Feature\SmartClosetTest

✓ guest cannot access closet index
✓ authenticated user can access closet index
✓ authenticated user can access closet create page
✓ closet show displays user owned clothing
✓ user cannot access other users clothing

Tests: 5 passed
Assertions: 7
```

測試涵蓋：

```text
1. 未登入不可進入 closet
2. 已登入可進入 closet.index
3. 已登入可進入 closet.create
4. 使用者可查看自己的衣物
5. 使用者不可查看別人的衣物
```

---

### 5.3 AiSearchTest

執行指令：

```powershell
php artisan test tests/Feature/AiSearchTest.php
```

目前結果：

```text
PASS  Tests\Feature\AiSearchTest

✓ guest cannot access ai search page
✓ authenticated user can access ai search page
✓ ai search page accepts empty query
✓ keyword fallback can find clothing by name when ai is unavailable

Tests: 4 passed
Assertions: 9
```

測試涵蓋：

```text
1. 未登入不可進入 AI Search
2. 已登入可進入 AI Search
3. 空搜尋可正常顯示提示
4. AI Service 不可用時 keyword fallback 可找到衣物
```

---

### 5.4 AiJobsL1Test

執行指令：

```powershell
php artisan test tests/Feature/AiJobsL1Test.php
```

目前結果：

```text
PASS  Tests\Feature\AiJobsL1Test

✓ authenticated user can access tryon page
✓ authenticated user can access runway video workspace
✓ authenticated user can access digital twin workspace
✓ runway video l1 creates ai job
✓ digital twin l1 creates ai job

Tests: 5 passed
Assertions: 21
```

測試涵蓋：

```text
1. 已登入可進入 Try-on L1 頁面
2. 已登入可進入 Runway Video workspace
3. 已登入可進入 Digital Twin workspace
4. Runway Video L1 可建立 ai_jobs
5. Digital Twin L1 可建立 ai_jobs
```

---

## 6. Laravel Feature Test 總結

目前 Laravel Feature Tests 已通過：

```text
SmartClosetTest：5 passed / 7 assertions
AiSearchTest：4 passed / 9 assertions
AiJobsL1Test：5 passed / 21 assertions
```

合計：

```text
14 tests passed
37 assertions
```

目前代表：

```text
Laravel 後端核心頁面、資料隔離、AI Search fallback、Runway Video L1、Digital Twin L1 的基本測試已通過。
```

---

## 7. 測試過程中修正的問題

### 7.1 重複 Telescope migration

問題：

```text
table "telescope_entries" already exists
```

原因：

```text
database/migrations 內有兩個 Telescope migration 都建立 telescope_entries。
```

修正方式：

```text
將其中一個重複 Telescope migration 移到 database/migrations_backup。
```

結果：

```text
Laravel Feature Test 可正常進入下一階段。
```

---

### 7.2 Vite manifest not found

問題：

```text
Vite manifest not found at public/build/manifest.json
```

原因：

```text
測試環境 render Blade 時會遇到 @vite，但測試時通常不會先執行 npm run build。
```

修正方式：

```php
protected function setUp(): void
{
    parent::setUp();

    $this->withoutVite();
}
```

已加入：

```text
SmartClosetTest.php
AiSearchTest.php
AiJobsL1Test.php
```

結果：

```text
Blade 頁面測試可正常執行。
```

---

### 7.3 WorkspaceController 未初始化變數

問題：

```text
Undefined variable $digitalTwinJobs
```

原因：

```text
WorkspaceController@show() 在 runway-video 頁面回傳 view 時，未先初始化 $digitalTwinJobs。
```

修正方式：

```php
$clothes = collect();
$runwayJobs = collect();
$digitalTwinJobs = collect();
```

結果：

```text
AiJobsL1Test 全部通過。
```

---

## 8. 目前尚未完全自動化的項目

目前尚未完整自動化：

```text
1. Smart Closet 圖片上傳完整流程
2. Laravel 呼叫 Python AI Service 的 integration test
3. Try-on L1 上傳人物圖片建立 pose_analysis job
4. Admin Users CRUD 測試
5. AI Service 關閉時新增衣物 failed 狀態測試
6. 真實模型接入後的模型推論測試
```

[ASSUMPTION] 目前階段以 skeleton 與核心 smoke test 為主，足夠支援 MVP 展示與報告紀錄。

---

## 9. 下一步建議

下一步可進入：

```text
第 7-F：Laravel → AI Service integration smoke test 文件或最小測試
```

或先進入原本第 8 項：

```text
部署與展示手冊
```

建議優先順序：

```text
1. 先整理第 8 項部署與展示手冊
2. 再補 Laravel → AI Service integration smoke test
3. 最後視時間補 Admin / 上傳流程 Feature Test
```