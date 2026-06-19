# VogueAI Core Progress Tracker

## 2026-06-19 本機模型 Provider 完整化與真實試穿驗收

### 本次完成的大進度

- Hugging Face IDM-VTON 真實試穿已成功跑出合成結果，最新人工畫面驗收顯示 POSE-0029 為 SUCCESS，並在試穿任務紀錄中直接顯示輸出圖片。
- 試穿頁已具備自動查詢完成結果的前端訊號：最新 processing 任務會透過 `data-tryon-auto-poll` 自動送出一次狀態查詢，使用者不需要一直手動點「查詢試穿結果」。
- AI Service 已接入本機模型資料夾 `C:/Users/User/smart_wardrobe/backend/models`：
  - fine-tuned CLIP：替換文字搜尋與圖片 embedding 的模型來源。
  - BLIP Large：替換衣物圖片描述模型。
  - YOLO11s Pose：替換 mock 姿態檢查，`AI_MOCK_MODE=false` 時由 `/ai/pose` 實際推論。
  - fashion multi-output classifier：接入 `/ai/attributes`，補 category、subcategory、color、season、occasion、usage、style、material、pattern。
  - BLIP VQA：新增 `/ai/vqa`，並整合到 `/ai/attributes` 的進階衣物理解結果。
  - IDM-VTON：繼續負責真正合成試穿圖。
- Qdrant 已確認可啟動、建立 collection、upsert 向量並回傳相似搜尋結果。
- Laravel provider matrix 已擴充成 13 個能力，新增 BLIP VQA、多輸出分類、YOLO Pose，並把 CLIP / BLIP 顯示同步成目前本機模型。
- Manual acceptance gate 已更新 Try-on 驗收規則，改用穩定程式訊號檢查 provider 任務狀態、自動 polling、成功輸出與失敗狀態。

### 目前模型矩陣狀態

- PASS：Gemini 穿搭顧問。
- PASS：Gemini 聊天助理。
- PASS：Gemini 文字理解。
- PASS：fine-tuned CLIP 文字搜尋。
- PASS：fine-tuned CLIP 圖片向量。
- PASS：BLIP Large 衣物描述。
- PASS：BLIP VQA 進階衣物理解。
- PASS：多輸出分類衣物自動標籤。
- PASS：YOLO Pose 姿態檢查。
- PASS：Qdrant 正式向量資料庫。
- PASS：Hugging Face IDM-VTON 真實換裝模型。
- PASS：Runway / Veo 影片生成 provider contract 與設定。
- WARN：Digital Twin 3D / 多視角 / avatar provider contract 已存在，但尚未提供正式 avatar provider endpoint / key，因此仍不是完整真 3D 生成。

### 本次驗證

- Laravel 全測試：`.\\vendor\\bin\\pest.bat --no-coverage` -> 128 passed, 705 assertions。
- AI Service 測試：`ai_service/.venv/Scripts/python.exe -m pytest tests -q` -> 47 passed。
- Manual acceptance：`php artisan vogueai:manual-acceptance` -> 0 failed, 1 warning。
- Provider matrix：`php artisan vogueai:provider-matrix` -> 0 failed, 1 warning。
- Provider warning 目前只剩 Digital Twin avatar provider 尚未接正式外部服務。
- GitHub：本輪沒有 staging、commit、push，也沒有上傳 GitHub。

### 重要限制

- Hugging Face IDM-VTON 是免費 demo / research prototype 方案，可能排隊、休眠、限流或失敗，不等於商用 SLA。
- 目前 Python venv 使用 CPU 版 PyTorch；本機有 RTX 5060，但尚未切換 CUDA 版 PyTorch。若要大量使用 BLIP Large / VQA / YOLO / CLIP，下一步建議補 GPU runtime。
- Digital Twin 真 3D/avatar provider 尚未完成，因此 provider matrix 保留 warning 是正確狀態。
- `.env` 與模型權重不可上傳 GitHub；上傳前必須再跑 production / upload scope / github readiness 檢查。



## 2026-06-17 外部 Provider 順序接入：Try-on 第一站

- 目標：照順序先接 Try-on 真實換裝模型，再接 Runway/Veo，最後接 Digital Twin/avatar provider。
- 已完成：
  - `ExternalProviderSmokeService` 支援單一 provider selector。
  - `php artisan vogueai:external-provider-smoke --only=tryon` 可單獨驗收 Try-on。
  - `tests/Feature/ExternalProviderSmokeTest.php` 已補 `tryon` selector、未知 selector、指令執行測試。
  - `tests/Feature/ExternalProviderSmokeTest.php tests/Feature/ExternalModelProviderTest.php` -> 9 passed / 38 assertions。
  - `php artisan vogueai:external-provider-smoke --only=tryon` -> 0 failed / 1 warning。
- 目前阻塞：
  - `.env` 尚未設定 `AI_EXTERNAL_PROVIDER_CALLS=true`。
  - `.env` 尚未設定 `AI_TRYON_API_BASE_URL`。
  - `.env` 尚未設定 `AI_TRYON_API_KEY`。
  - 因此 Try-on adapter 已就緒，但尚不能真的打第三方換裝模型產生圖片。
- 下一步需要使用者提供或選定 Try-on provider：
  - Provider base URL。
  - API key。
  - 請求格式是否相容目前 `/tryon/generate` contract，若不相容，需要補 provider-specific payload mapper。

## 2026-06-17 模型 Provider 完整化推進完成

- 本次大進度目標：補齊 Gemini、CLIP、BLIP、Qdrant、Try-on、Runway/Veo、Digital Twin 的 provider contract、runtime smoke gate 與測試保護。
- Gemini：
  - 已新增 `app/Services/GeminiJsonProviderService.php` 作為共用 Gemini JSON adapter。
  - 已新增 `app/Services/GeminiChatAssistantService.php`，聊天助理可走 `gemini-chat-assistant-v1`，缺 key 時安全降級，有 key 時可進 `ready / real_adapter`。
  - 已新增 `app/Services/GeminiTextUnderstandingService.php`，文字理解可走 `gemini-text-understanding-v1`，可抽取 intent、normalized_query、entities、categories、colors、style_tags、confidence。
  - `php artisan vogueai:provider-matrix` 顯示 Gemini 穿搭顧問、聊天助理、文字理解皆為 PASS。
- CLIP / BLIP / Qdrant：
  - `ModelProviderMatrixService` 現在把 CLIP text、CLIP image、BLIP、Qdrant 作為 adapter implementation PASS。
  - 新增 `app/Services/ProviderRuntimeSmokeService.php`。
  - 新增 `php artisan vogueai:provider-runtime-smoke`。
  - 新增 `php artisan vogueai:provider-runtime-smoke --connect-qdrant`，可實際驗證 AI Service、CLIP text、CLIP image、BLIP、Qdrant daemon/collection。
  - 本機實測結果：`provider-runtime-smoke --connect-qdrant` -> 0 failed / 0 warnings。
- Try-on / Runway-Veo / Digital Twin：
  - 既有 `ExternalModelProviderService` 已保留真實 provider adapter。
  - 新增 `app/Services/ExternalProviderSmokeService.php`。
  - 新增 `php artisan vogueai:external-provider-smoke`。
  - 目前 smoke 結果：0 failed / 3 warnings，原因是第三方 Try-on、Runway/Veo、Digital Twin avatar provider 尚未提供正式 endpoint/key 並開啟 `AI_EXTERNAL_PROVIDER_CALLS=true`。
  - 這 3 個不是程式缺 route/adapter，而是等待真實第三方服務設定；未確認前不會主動送出外部 HTTP，避免誤用額度或打錯 endpoint。
- 測試與驗證：
  - 新增 `tests/Feature/GeminiProviderCompletionTest.php`。
  - 新增 `tests/Feature/ProviderRuntimeSmokeTest.php`。
  - 新增 `tests/Feature/ExternalProviderSmokeTest.php`。
  - Provider 相關測試：22 passed / 104 assertions。
  - 完整 Laravel 測試：`.\vendor\bin\pest.bat` -> 118 passed / 645 assertions。
  - `npm run build` -> passed。
  - `php artisan vogueai:provider-matrix` -> 0 failed / 3 warnings。
  - `php artisan vogueai:provider-runtime-smoke --connect-qdrant` -> 0 failed / 0 warnings。
  - `php artisan vogueai:provider-check` -> 0 failed / 1 warning，warning 來自外部 provider 尚未啟用。
  - `php artisan vogueai:production-check` -> 0 failed / 3 warnings。
- GitHub 狀態：
  - 本次沒有 stage、commit、push、PR。
  - API key 值沒有被列印或寫入文件。

下一個大進度建議：第三方外部 Provider 啟用驗收。需要先決定 Try-on provider、Runway 或 Veo provider、Digital Twin/avatar provider 的正式 endpoint、API key、費用與輸出格式，再把 `AI_EXTERNAL_PROVIDER_CALLS=true` 開啟並逐一 smoke test。

## 2026-06-16 核心功能正式化完成

- 本次大進度目標：把「我的衣櫥、AI Search、AI Stylist、Try-on、Runway Video、Digital Twin」整理成可正式驗收的核心功能線，而不是只靠人工看頁面判斷。
- 新增核心功能 Gate：
  - `app/Services/CoreFeatureReadinessService.php`
  - `php artisan vogueai:core-feature-check`
  - 覆蓋 My Closet、AI Search、AI Stylist、Try-on、Runway Video、Digital Twin 六大功能。
  - 目前結果：0 failed / 3 warnings。
  - warnings 只來自 Try-on、Runway Video、Digital Twin 的真實外部 Provider 尚未開啟；功能路由、任務紀錄、provider adapter、輪詢與重試入口皆已就緒。
- 外部模型呼叫正式化：
  - 新增 `app/Services/ExternalModelProviderService.php`。
  - Try-on 接 `generateTryOn()` provider attempt。
  - Runway / Veo 接 `generateVideo()` provider attempt，保存 provider job metadata、video_url 欄位位置與任務結果。
  - Digital Twin 接 `generateDigitalTwin()` provider attempt，保存 avatar provider 回傳位置與 profile 結果。
  - 新增 `.env.example` / `config/ai.php` 設定：`AI_EXTERNAL_PROVIDER_CALLS=false`。
  - 未明確開啟時不會送出外部 HTTP，避免測試或人工 QA 誤打真實 API。
  - 開啟條件：確認 provider endpoint / key / quota / 費用後，設定 `AI_EXTERNAL_PROVIDER_CALLS=true`。
- AI Job 任務正式化：
  - 新增 `app/Http/Controllers/AiJobController.php`。
  - 新增 `GET /ai-jobs/{job}`：給 Try-on / Runway / Digital Twin 共用的任務狀態輪詢 JSON。
  - 新增 `POST /ai-jobs/{job}/retry`：安全標記 `pending_retry`、累加 `retry_count`、保留原始任務與前次錯誤。
  - 僅任務擁有者可查詢或重試，其他使用者會 404。
- Provider Matrix 更新：
  - `php artisan vogueai:provider-matrix` 現在會把 `AI_EXTERNAL_PROVIDER_CALLS` 納入 Try-on / Runway / Digital Twin 的正式 readiness。
  - API key 值仍不會被列印。
- 測試與驗證：
  - `php -l`：`ExternalModelProviderService`、`CoreFeatureReadinessService`、`AiJobController`、`ModelProviderMatrixService`、`routes/web.php`、`routes/console.php` 全過。
  - `.\vendor\bin\pest.bat tests\Feature\ExternalModelProviderTest.php tests\Feature\CoreFeatureReadinessTest.php tests\Feature\AiJobStatusTest.php tests\Feature\ModelProviderMatrixTest.php tests\Feature\ProviderReadinessTest.php tests\Feature\ProductionReadinessTest.php tests\Feature\AiJobsL1Test.php` -> 31 passed / 201 assertions。
  - `.\vendor\bin\pest.bat` -> 109 passed / 607 assertions。
  - `php artisan vogueai:core-feature-check` -> 0 failed / 3 warnings。
  - `php artisan vogueai:provider-matrix` -> 0 failed / 9 warnings。
  - `php artisan vogueai:production-check` -> 0 failed / 3 warnings。
  - `npm run build` -> passed。
  - `php artisan vogueai:upload-scope` -> 133 changed/untracked entries，`needs-manual-review: 0`，無 `.env` 或大型模型檔風險。
- GitHub 狀態：
  - 本次沒有 stage、commit、push、PR。
  - 上傳前仍需人工確認 133 個 changed/untracked entries 與 Telescope duplicate migration cleanup。

下一個大進度建議：把「真實外部 Provider 啟用驗收」拆成小步驟，先確認 `.env` 的 Try-on / Video / Digital Twin endpoint 與 key，再用 `AI_EXTERNAL_PROVIDER_CALLS=true` 做單一 provider smoke test，避免一次打開所有昂貴模型。

## 2026-06-16 後續大進度：正式上架基礎 Gate 完成

- 完成正式上架基礎檢查指令：
  - 新增 `app/Services/ProductionReadinessService.php`。
  - 新增 `php artisan vogueai:production-check`。
  - 檢查範圍包含正式環境設定、AI provider 設定、queue/storage/log、privacy / terms / acceptable-use、錯誤/空狀態/重試 UX、`.env` / API key / 大型模型檔外洩風險。
  - 指令只做本機檢查，不會 stage、commit、push 或開 PR。
- 完成正式政策頁基礎：
  - `/privacy` -> 隱私政策。
  - `/terms` -> 服務條款。
  - `/acceptable-use` -> 使用限制。
  - 首頁 footer 已加入三個公開入口。
- 完成錯誤、loading、空狀態、失敗重試基礎：
  - 新增 `resources/views/components/vogue-state.blade.php`，可重用於 empty / error / retry / loading / success。
  - 新增 `resources/views/errors/500.blade.php` 與 `resources/views/errors/503.blade.php`，正式環境錯誤不顯示技術細節，提供返回與重試方向。
  - 既有 `x-vogue-page` loading skeleton 保留。
- 移除高曝光頁面的 demo/mock 誤導文案：
  - 首頁與儀表板改為「安全備援狀態」「真實模型」「外部服務」等正式產品語氣。
  - AI Search / AI Stylist 的使用者選項改為「安全備援 / 真實模型」。
  - Try-on、Runway Video、Digital Twin 的使用者可見文字改為「模型狀態」「預覽狀態」「安全備援」。
  - 內部技術欄位如 `mock_mode`、`provider_mode=demo`、資料庫 `mode=mock` 暫時保留，因為它們仍是程式契約與既有測試資料的一部分，不直接作為正式產品文案。
- Guest-safe 導覽修正：
  - `resources/views/components/vogue-auth-nav.blade.php` 避免未登入訪客呼叫 `auth()->user()->isAdmin()`。
  - 未登入時頂部 CTA 顯示登入 / 註冊，不顯示登出。
- 上傳範圍審核同步更新：
  - `ProductionReadinessService` 與 `ProductionReadinessTest` 會歸到正式 gate / provider gate 分組。
  - `php artisan vogueai:upload-scope` 目前 125 changed/untracked entries，`needs-manual-review: 0`。
  - 無 `.env`、API key 檔或大型模型 artifact 風險。
- 驗證：
  - `php -l app\Services\ProductionReadinessService.php` -> passed。
  - `php -l routes\console.php` -> passed。
  - `php -l routes\web.php` -> passed。
  - `php -l tests\Feature\ProductionReadinessTest.php` -> passed。
  - `php artisan vogueai:production-check` -> 0 failed / 2 warnings；warnings 為本機不是 production、且本機仍允許 fallback，屬正式部署前需用 production env 重跑的預期提醒。
  - `.\vendor\bin\pest.bat tests\Feature\ProductionReadinessTest.php` -> 4 passed / 18 assertions。
  - `.\vendor\bin\pest.bat tests\Feature\UploadScopeReviewTest.php tests\Feature\ProductionReadinessTest.php` -> 8 passed / 40 assertions。
  - `.\vendor\bin\pest.bat tests\Feature\SmartClosetTest.php tests\Feature\ManualAcceptanceTest.php tests\Feature\AiStylistTest.php tests\Feature\AiSearchTest.php` -> 34 passed / 255 assertions。
  - `php artisan vogueai:manual-acceptance` -> 0 failed / 3 warnings；warnings 為本機 Laravel server 未啟動、provider gate 與 real-mode gate 有外部服務提醒。
  - `php artisan vogueai:real-mode-check` -> 0 failed / 1 warning；warning 為外部 provider/Qdrant 啟動提醒。
  - `npm run build` -> passed。
  - `php artisan vogueai:github-check` -> 2 blockers / 0 warnings；blockers 仍為 125 個 changed/untracked entries 需要人工確認，以及 Telescope duplicate migration deletion 需要明確確認。
- 本階段沒有上傳 GitHub、沒有 stage、沒有 commit、沒有 push。

下一個大進度：啟動本機服務並做正式上架前人工瀏覽器 QA，檢查 `/privacy`、`/terms`、`/acceptable-use`、`/smart-closet`、AI Search、AI Stylist、Try-on、Runway Video、Digital Twin 是否都能正常顯示正式語氣與錯誤/空狀態。

## 2026-06-16 後續大進度：上傳前模糊分類歸零完成

- 完成 GitHub 前審核清單的 `needs-manual-review` 清理：
  - `public/hot.disabled` 判定為本機 Vite hot 狀態檔，不應進入 GitHub 上傳範圍，已加入 `.gitignore`。
  - `tests/Feature/ManualAcceptanceTest.php` 判定為 manual acceptance / provider gate 測試，已自動歸入 `demo-readiness-provider-gates`。
  - `php artisan vogueai:upload-scope` 的 `needs-manual-review` 從 2 降為 0。
  - 總 changed/untracked entries 從 118 降為 117。
- 測試更新：
  - `UploadScopeReviewTest` 新增 `test_manual_acceptance_test_is_grouped_with_provider_gates`。
  - 確認 `ManualAcceptanceTest.php` 不會再被丟進模糊分類。
- 驗證：
  - `php -l app\Services\UploadScopeReviewService.php` -> passed。
  - `php -l tests\Feature\UploadScopeReviewTest.php` -> passed。
  - `.\vendor\bin\pest.bat tests\Feature\UploadScopeReviewTest.php` -> 4 passed / 22 assertions。
  - `php artisan vogueai:upload-scope` -> 117 changed/untracked entries；`needs-manual-review: 0`；無 `.env` 或大型模型 artifact 風險。
- 目前 GitHub 前仍需人工確認：
  - 117 個 changed/untracked entries 是否全部都要上傳。
  - Telescope cleanup 是否確認保留 `2026_04_22_161640` 並刪除 `2026_04_22_161722`。
- 本階段沒有上傳 GitHub、沒有 stage、沒有 commit、沒有 push。

下一個大進度：執行 GitHub readiness gate 最終確認；若仍跳過 GitHub，則進入 production hardening，補正式部署前的環境、queue、storage、log、provider fallback 與錯誤監控檢查。

## 2026-06-15 後續大進度：GitHub 前上傳範圍審核清單完成

- 完成 `php artisan vogueai:upload-scope` 的上傳前審核明細化：
  - 原本只列出每個範圍的檔案數量，現在會額外輸出「Suggested commit group file lists」。
  - 每個建議提交群組都會列出實際檔案路徑與狀態，例如 `modified`、`untracked`、`deleted`。
  - 目前建議分成 7 組：AI service adapter/contracts、Laravel closet/stylist workflows、demo readiness/provider gates、localized UI/manual polish、project docs/roadmap、Telescope duplicate migration cleanup、needs manual review。
  - 這份清單只用於人工審核與後續分批提交規劃，不會自動 stage、commit、push 或開 PR。
- `UploadScopeReviewService` 擴充：
  - 保留既有 `groups` 與 `commit_groups` 數量摘要。
  - 新增 `group_entries` 與 `commit_group_entries`，讓後續工具或指令可以讀到每個分組底下的完整檔案清單。
  - 新增狀態標籤轉換：`modified`、`untracked`、`deleted`、`added`、`renamed`。
- 測試更新：
  - `UploadScopeReviewTest` 現在會驗證 AI service 檔案會進入 `ai-service-adapter-contracts`。
  - 驗證 Telescope duplicate migration deletion 會進入 `telescope-duplicate-migration-cleanup` 並標示為 `deleted`。
  - 驗證 Artisan command 會輸出 `Suggested commit group file lists:`。
- 驗證：
  - `php -l app\Services\UploadScopeReviewService.php` -> passed。
  - `php -l routes\console.php` -> passed。
  - `php -l tests\Feature\UploadScopeReviewTest.php` -> passed。
  - `.\vendor\bin\pest.bat tests\Feature\UploadScopeReviewTest.php` -> 3 passed / 19 assertions。
  - `php artisan vogueai:upload-scope` -> 118 changed/untracked entries，完整列出 7 個建議提交群組與檔案明細。
- 目前 GitHub 前仍需人工確認：
  - 118 個 changed/untracked entries 是否全部都要上傳。
  - Telescope cleanup 是否確認保留 `2026_04_22_161640` 並刪除 `2026_04_22_161722`。
  - `public/hot.disabled` 與 `tests/Feature/ManualAcceptanceTest.php` 仍被歸類在 `needs-manual-review`，GitHub 前需要確認是否歸到既有群組或另行處理。
- 本階段沒有上傳 GitHub、沒有 stage、沒有 commit、沒有 push。

下一個大進度：根據這份上傳範圍清單，先人工確認 `needs-manual-review` 與 Telescope cleanup，再決定是否進入分批 stage / commit 的 GitHub 前流程；若仍跳過 GitHub，則可進入 production hardening：正式環境設定、錯誤監控、任務佇列與模型服務部署檢查。

## 2026-06-15 後續大進度：最終核心驗收 Sweep 完成

- 完成目前核心功能的整體驗收 sweep：
  - Laravel 本機服務重新啟動並確認 `/smart-closet` -> HTTP 200。
  - AI Service 重新啟動並確認 `/health` -> HTTP 200。
  - Qdrant 重新啟動並確認 `/` -> HTTP 200。
  - 不執行 GitHub 上傳，只做本機檢查、風險盤點與文件更新。
- 修正 AI Service 測試穩定性：
  - `ai_service/tests/test_ai_routes.py` 原本有兩個測試假設 Qdrant daemon 未啟動。
  - 由於本輪 final sweep 需要同時啟動 Qdrant 做 provider gate，測試會收到 `ready` 而不是預期的 `degraded`。
  - 已把這兩個測試改為 monkeypatch Qdrant client connection failure，確保測試不受本機 daemon 狀態影響。
- 驗證：
  - `.\vendor\bin\pest.bat` -> 92 passed / 515 assertions。
  - `ai_service\.venv\Scripts\python.exe -m pytest ai_service\tests` -> 39 passed。
  - `npm.cmd run build` -> passed。
  - `php artisan vogueai:provider-check` -> 0 failed / 0 warnings。
  - `php artisan vogueai:real-mode-check` -> 0 failed / 0 warnings。
  - `php artisan vogueai:manual-acceptance` -> 0 failed / 0 warnings。
  - `php artisan vogueai:demo-check` -> 0 failed / 1 warning；warning 仍是既有 Telescope migration cleanup 需要 GitHub 前人工確認。
  - `php artisan vogueai:upload-scope` -> 118 changed/untracked entries，無 `.env` 或大型模型 artifact 風險。
  - `php artisan vogueai:github-check` -> 2 blockers / 0 warnings；blockers 是 118 changed/untracked entries 需要人工分組提交，以及 Telescope duplicate migration deletion 需要明確確認。
- 登入 demo 帳號後核心頁面 sweep：
  - Dashboard -> HTTP 200，包含「功能入口」。
  - Smart Closet Hub -> HTTP 200，包含「人工驗收總控台」。
  - My Closet -> HTTP 200，包含「搜尋衣物」。
  - AI Search real mode -> HTTP 200，包含「搜尋驗收狀態」。
  - AI Stylist -> HTTP 200，包含「Gemini 可人工測試」。
  - Try-on -> HTTP 200，包含「姿態任務紀錄」。
  - Runway -> HTTP 200，包含「伸展台影片任務紀錄」。
  - Digital Twin -> HTTP 200，包含「數位分身紀錄」。
  - Account -> HTTP 200，包含「帳號總覽」。
  - Profile -> HTTP 200，包含「個人資料」。
- 本階段沒有上傳 GitHub、沒有 stage、沒有 commit、沒有 push。

下一個大進度：進入人工確認 `/smart-closet` 總控台與五個核心流程；若人工確認都通過，再處理 GitHub 前兩個 blocker：變更分組確認、Telescope duplicate migration deletion 確認。

## 2026-06-14 後續大進度：人工驗收總控台完成

- 完成 Smart Closet Hub 人工驗收總控台：
  - `/smart-closet` 新增「人工驗收總控台」區塊。
  - 集中列出 AI 搜尋真實模型、AI 穿搭顧問、試穿 / 姿態、伸展台影片、數位分身五個目前需要人工確認的核心流程。
  - 每個流程都提供「前往檢查」入口與預期畫面訊號。
  - AI Search 項目直接連到 `provider_mode=real&q=white shirt`，方便檢查 Qdrant / CLIP 真實搜尋。
  - Smart Closet Hub 主驗收區加入 `vogue-critical-flow`，避免 reveal animation 造成總控台空白。
- Manual acceptance gate 擴充：
  - 新增 `Manual QA cockpit signals`。
  - Critical flow visibility 納入 Smart Closet Hub。
  - Manual QA test coverage 納入 Smart Closet Hub 總控台測試。
- 補測試：
  - `SmartClosetTest::test_smart_closet_hub_displays_manual_acceptance_cockpit` 驗證總控台、五個功能入口與關鍵驗收文字。
  - `ManualAcceptanceTest` 驗證 command 會輸出 `[PASS] Manual QA cockpit signals`。
- 驗證：
  - `php -l app\Http\Controllers\ClosetController.php` -> passed。
  - `php -l resources\views\closet\hub.blade.php` -> passed。
  - `php -l app\Services\ManualAcceptanceService.php` -> passed。
  - `php -l tests\Feature\SmartClosetTest.php` -> passed。
  - `php -l tests\Feature\ManualAcceptanceTest.php` -> passed。
  - `.\vendor\bin\pest.bat tests\Feature\SmartClosetTest.php tests\Feature\ManualAcceptanceTest.php` -> 11 passed / 52 assertions。
  - `npm.cmd run build` -> passed。
  - `php artisan vogueai:provider-check` -> 0 failed / 0 warnings。
  - `php artisan vogueai:real-mode-check` -> 0 failed / 0 warnings。
  - `php artisan vogueai:manual-acceptance` -> 0 failed / 0 warnings。
  - 登入 demo 帳號後讀取 `/smart-closet` -> HTTP 200，頁面包含「人工驗收總控台」、AI 搜尋真實模型、AI 穿搭顧問、試穿 / 姿態、伸展台影片、數位分身與「真實搜尋可人工驗收」。
- 本階段沒有上傳 GitHub、沒有 stage、沒有 commit、沒有 push。

下一個大進度：人工打開 `/smart-closet`，從「人工驗收總控台」依序進入五個功能檢查；全部人工確認後，再進入 GitHub 前最終總檢查或繼續補其他非核心模組。

## 2026-06-14 後續大進度：Workspace 亮點模組人工驗收可視化完成

- 完成 Workspace 亮點模組人工檢查可視化修正：
  - `/workspace/runway-video` 最新任務會顯示「最新任務」與「最新伸展台任務可人工驗收」。
  - Runway Video 成功建立後的 session 訊息會顯示「可人工驗收：mock preview ready / 9:16」。
  - Runway Video 任務卡明確提示要檢查 mock preview ready、9:16、分鏡場景與影片提示詞。
  - `/workspace/digital-twin` 最新任務會顯示「最新任務」與「最新數位分身任務可人工驗收」。
  - Digital Twin L1 成功建立後會顯示「可人工驗收：mock profile / degraded」。
  - Digital Twin L2 衣櫥分析成功後會顯示「可人工驗收：rule_based / degraded」。
  - Workspace 主操作區加入 `vogue-critical-flow`，避免 reveal animation 造成人工檢查時頁面空白。
- Manual acceptance gate 擴充：
  - 新增 `Workspace acceptance signals`。
  - Route gate 納入 `workspace.show`、Runway store、Digital Twin store、Digital Twin closet analysis。
  - Manual QA test coverage 納入 Runway / Digital Twin 人工驗收訊號。
- 補測試：
  - Runway Video L2 preview job 會顯示「最新伸展台任務可人工驗收」與 `mock preview ready`。
  - Digital Twin L1 profile job 會顯示「最新數位分身任務可人工驗收」與分身預覽佔位。
  - Digital Twin L2 closet analysis job 會顯示「最新數位分身任務可人工驗收」與衣櫥統計。
  - Manual acceptance command 會輸出 `[PASS] Workspace acceptance signals`。
- 驗證：
  - `php -l app\Http\Controllers\WorkspaceController.php` -> passed。
  - `php -l resources\views\workspace\show.blade.php` -> passed。
  - `php -l app\Services\ManualAcceptanceService.php` -> passed。
  - `php -l tests\Feature\AiJobsL1Test.php` -> passed。
  - `php -l tests\Feature\ManualAcceptanceTest.php` -> passed。
  - `.\vendor\bin\pest.bat tests\Feature\AiJobsL1Test.php tests\Feature\ManualAcceptanceTest.php` -> 14 passed / 118 assertions。
  - `npm.cmd run build` -> passed。
  - `php artisan vogueai:provider-check` -> 0 failed / 0 warnings。
  - `php artisan vogueai:real-mode-check` -> 0 failed / 0 warnings。
  - `php artisan vogueai:manual-acceptance` -> 0 failed / 0 warnings。
  - 登入 demo 帳號後建立 Runway Video 任務，`/workspace/runway-video` -> HTTP 200，頁面包含「最新伸展台任務可人工驗收」與 `mock preview ready`。
  - 登入 demo 帳號後建立 Digital Twin L1 任務，`/workspace/digital-twin` -> HTTP 200，頁面包含「最新數位分身任務可人工驗收」與「分身預覽佔位」。
- 本階段沒有上傳 GitHub、沒有 stage、沒有 commit、沒有 push。

下一個大進度：人工打開 `/workspace/runway-video` 建立一筆 Storyboard，確認最新任務顯示「最新伸展台任務可人工驗收」；再打開 `/workspace/digital-twin` 建立 L1 profile 或執行 L2 衣櫥分析，確認最新任務顯示「最新數位分身任務可人工驗收」。

## 2026-06-14 後續大進度：AI Search 真實模型人工驗收可視化完成

- 完成 AI Search 真實模型人工檢查可視化修正：
  - `/closet/ai-search` 新增 `搜尋驗收狀態` 區塊。
  - 真實模型搜尋成功時會顯示「真實搜尋可人工驗收」。
  - ready 條件會檢查 `provider_mode=real`、`status=ready`、Qdrant、CLIP embedding、vector fallback 未啟用、embedding fallback 未啟用。
  - AI 搜尋失敗或無法匹配目前使用者衣物時，會顯示「關鍵字備援」，避免誤判為真實搜尋已完成。
  - 搜尋主操作區加入 `vogue-critical-flow`，避免 reveal animation 導致人工檢查時表單空白。
- 補測試：
  - AI Search ready Qdrant vector result 會顯示「真實搜尋可人工驗收」與 `fallback 未啟用`。
  - `provider_mode=real` 會對 AI Service 傳 `mock_mode=false`，並顯示真實搜尋驗收訊號。
  - keyword fallback flow 會顯示「關鍵字備援」。
  - Manual acceptance gate 已納入 `AI Search acceptance signals`。
- 驗證：
  - `php -l app\Http\Controllers\ClosetController.php` -> passed。
  - `php -l resources\views\closet\search.blade.php` -> passed。
  - `php -l app\Services\ManualAcceptanceService.php` -> passed。
  - `php -l tests\Feature\AiSearchTest.php` -> passed。
  - `php -l tests\Feature\ManualAcceptanceTest.php` -> passed。
  - `php artisan view:clear` -> passed。
  - `.\vendor\bin\pest.bat tests\Feature\AiSearchTest.php tests\Feature\ManualAcceptanceTest.php` -> 10 passed / 61 assertions。
  - `npm.cmd run build` -> passed。
  - Laravel 已重新啟動：`http://127.0.0.1:8000/closet/ai-search` -> HTTP 200。
  - AI Service 已重新啟動：`http://127.0.0.1:8001/health` -> HTTP 200。
  - Qdrant 已重新啟動：`http://127.0.0.1:6333/` -> HTTP 200。
  - `php artisan vogueai:provider-check` -> 0 failed / 0 warnings。
  - `php artisan vogueai:real-mode-check` -> 0 failed / 0 warnings。
  - `php artisan vogueai:manual-acceptance` -> 0 failed / 0 warnings。
  - 登入 demo 帳號後讀取 `/closet/ai-search?q=white%20shirt&provider_mode=real` -> HTTP 200，頁面包含「搜尋驗收狀態」、「真實搜尋可人工驗收」、`fallback 未啟用`、`qdrant`、`clip-vit-base-patch32`、`qdrant_vector_similarity`。
- 本階段沒有上傳 GitHub、沒有 stage、沒有 commit、沒有 push。

下一個大進度：人工打開 `/closet/ai-search`，選「真實模型」搜尋 `white shirt` 或 `白色上衣`，確認畫面上方顯示「真實搜尋可人工驗收」，結果卡顯示 `qdrant`、`clip-vit-base-patch32`、`fallback 未啟用`；人工確認後再進入下一個功能模組驗收。

## 2026-06-14 後續大進度：可重跑人工驗收 Gate 完成

- 新增 `App\Services\ManualAcceptanceService`。
- 新增 artisan 指令：
  - `php artisan vogueai:manual-acceptance`
- Gate 檢查內容：
  - Stylist、Try-on、AI Search、Smart Closet Hub route 是否存在。
  - Stylist / Try-on 主操作區是否有 `vogue-critical-flow`，避免 reveal animation 造成空白。
  - AI Stylist 是否能在畫面顯示 `ready / real_adapter`、fallback 狀態與 Gemini 文字轉接器資訊。
  - Try-on 是否能在畫面顯示「最新任務可人工驗收」與「最新任務失敗」。
  - 核心進度與 manual checklist 是否包含目前人工驗收上下文。
  - Feature tests 是否覆蓋 Stylist / Try-on 人工驗收訊號。
  - Laravel 本機 HTTP 是否可開 `/closet/try-on`。
  - Provider gate 是否 0 failed / 0 warnings。
  - Real-mode gate 是否 0 failed / 0 warnings。
  - GitHub 是否仍保持 review-only，沒有要求 stage / commit / push / PR。
- 新增測試：
  - `tests\Feature\ManualAcceptanceTest.php`
  - 驗證 service 能檢查目前人工 QA 訊號。
  - 驗證 artisan command 會輸出摘要且不做 GitHub 動作。
- 驗證：
  - `php -l app\Services\ManualAcceptanceService.php` -> passed。
  - `php -l routes\console.php` -> passed。
  - `php -l tests\Feature\ManualAcceptanceTest.php` -> passed。
  - `.\vendor\bin\pest.bat tests\Feature\ManualAcceptanceTest.php` -> 2 passed / 9 assertions。
  - `npm.cmd run build` -> passed。
  - `php artisan vogueai:provider-check` -> 0 failed / 0 warnings。
  - `php artisan vogueai:real-mode-check` -> 0 failed / 0 warnings。
  - `php artisan vogueai:manual-acceptance` -> 0 failed / 0 warnings。
- 本階段沒有上傳 GitHub、沒有 stage、沒有 commit、沒有 push。

下一個大進度：進入人工瀏覽器驗收，依序實測 `/closet/stylist` 真實模型與 `/closet/try-on` 新任務；每次驗收前可先跑 `php artisan vogueai:manual-acceptance` 確認本機狀態。

## 2026-06-14 後續大進度：Try-on L1 新任務人工驗收可視化完成

- 完成 Try-on L1 人工驗收可視化修正：
  - 建立新姿態任務成功後，session 訊息會顯示「可人工驗收」與姿態品質狀態 / 分數。
  - 最新姿態任務卡新增「最新任務」標籤。
  - 最新任務成功時顯示「最新任務可人工驗收」，並提示確認分數、品質檢查與改善建議。
  - 最新任務失敗時顯示「最新任務失敗」，並提示 AI Service 可能未啟動。
  - 舊的 `AI_SERVICE_UNAVAILABLE` 會保留在歷史紀錄，但不再被誤認為目前服務仍壞。
- 補測試：
  - Try-on L1 成功 flow 會寫入 pose quality，頁面可看到「最新任務」、「最新任務可人工驗收」、「86%」、「品質檢查」、「改善建議」。
  - Try-on L1 AI Service 失敗 flow 會寫入 `AI_SERVICE_UNAVAILABLE`，頁面可看到「最新任務失敗」與服務未啟動提示。
- 驗證：
  - `php -l app\Http\Controllers\ClosetController.php` -> passed。
  - `php -l resources\views\closet\tryon.blade.php` -> passed。
  - `php -l tests\Feature\AiJobsL1Test.php` -> passed。
  - `.\vendor\bin\pest.bat tests\Feature\AiJobsL1Test.php --filter=tryon` -> 3 passed / 29 assertions。
  - `npm.cmd run build` -> passed。
  - AI Service 已重新啟動：`http://127.0.0.1:8001/health` -> HTTP 200。
  - Qdrant 已重新啟動：`http://127.0.0.1:6333/` -> HTTP 200。
  - Qdrant collection ensure -> `status: ready`、`verified: true`。
  - `POST /ai/pose` smoke -> `status: degraded`、`mode: mock`、`pose_quality_score: 0.86`、`pose_quality_status: usable`。
  - `php artisan vogueai:provider-check` -> 0 failed / 0 warnings。
  - `php artisan vogueai:real-mode-check` -> 0 failed / 0 warnings。
  - `/closet/try-on` -> HTTP 200。
- 本階段沒有上傳 GitHub、沒有 stage、沒有 commit、沒有 push。

下一個大進度：人工刷新 `/closet/try-on`，重新選衣物與人物照片建立一筆新任務，確認最新紀錄顯示「最新任務可人工驗收」、姿態品質 `86%`、品質檢查與改善建議；之後再進入整體人工驗收清單或 GitHub 前 gate。

## 2026-06-14 後續大進度：Stylist 真實模型結果可視化驗收完成

- 完成 AI Stylist 真實模型人工檢查可視化修正：
  - `generateStylist()` 送出後的 session 訊息改為依結果動態顯示。
  - Gemini 成功時顯示「已使用 Gemini 真實模型產生建議」與 `ready / real_adapter`。
  - Gemini real provider 失敗但 fallback 可用時，顯示「已嘗試 Gemini 真實模型，但目前使用安全 fallback」。
  - 展示模式仍保留 `rule_based / degraded` 說明，避免誤認為所有流程都已是真實 provider。
- 完成 AI Stylist History 顯示修正：
  - 主狀態 chip 新增 `ready` -> success 樣式。
  - Gemini 文字轉接器 chip 會依 `ready`、`real_adapter_attempt`、`degraded/mock` 切換 success / pending / degraded 樣式。
  - 最新紀錄現在可直接在頁面上看到 `real_adapter / ready` 與 `fallback_active: false`，人工檢查不必只讀 metadata。
- 補測試：
  - 缺 Gemini key 的 real-provider flow 會顯示安全 fallback 訊息。
  - Gemini fake ready response 會寫入 `ready / real_adapter`，頁面可看到 `Gemini Ready Outfit`、`real_adapter / ready`、`fallback_active: false`。
- 驗證：
  - `php -l app\Http\Controllers\ClosetController.php` -> passed。
  - `php -l resources\views\closet\stylist.blade.php` -> passed。
  - `php -l tests\Feature\AiStylistTest.php` -> passed。
  - `.\vendor\bin\pest.bat tests\Feature\AiStylistTest.php` -> 15 passed / 153 assertions。
  - `npm.cmd run build` -> passed。
  - `php artisan vogueai:gemini-smoke` -> `ready / real_adapter / fallback_active: no`。
  - 已重新啟動 Laravel：`http://127.0.0.1:8000/closet/stylist` -> HTTP 200。
- 本階段沒有上傳 GitHub、沒有 stage、沒有 commit、沒有 push。

下一個大進度：人工刷新 `/closet/stylist`，選「真實模型」送出，確認最新推薦紀錄顯示 `real_adapter / ready` 與 `fallback_active: false`；之後再進入 Try-on L1 新任務人工驗收。

## 2026-06-13 人工檢查修復：Provider 服務已恢復，Try-on 舊失敗需重建任務

- 使用者在 `/closet/stylist` 與 `/closet/try-on` 人工檢查時回報「沒有跑出來」：
  - Stylist 畫面看到舊的 `rule_based / degraded` 區塊。
  - Try-on 任務紀錄顯示 `AI_SERVICE_UNAVAILABLE` / 無法連線到 AI Service。
- 已確認這不是 Gemini key 問題：
  - `php artisan vogueai:gemini-smoke` -> `Status: ready`、`Mode: real_adapter`、`Fallback active: no`。
- 已重新啟動並確認本機 provider 服務：
  - AI Service `http://127.0.0.1:8001/health` -> HTTP 200。
  - Qdrant `http://127.0.0.1:6333/` -> HTTP 200。
  - Qdrant collection ensure -> `status: ready`、`verified: True`、`created: False`。
- 已重新跑 provider gate：
  - `php artisan config:clear`
  - `php artisan vogueai:provider-check` -> 0 failed / 0 warnings。
  - `php artisan vogueai:real-mode-check` -> 0 failed / 0 warnings。
- 已直接測 AI pose endpoint：
  - `POST /ai/pose` 使用內部 token -> `status: degraded`、`mode: mock`、`pose_quality_score: 0.86`、`pose_quality_status: usable`。
- 人工檢查重點：
  - Try-on 頁面的舊 `AI_SERVICE_UNAVAILABLE` 是歷史失敗紀錄，不會自動變成功；需重新選衣物、選人物照片，再按「建立試穿 L1 任務」產生新紀錄。
  - 目前 Try-on L1 仍是 pose mock/degraded 流程，預期結果是姿態任務成功與品質分數，不是產生真正換裝圖片。
  - Stylist 頁面上方的 `rule_based / degraded` 是舊靜態說明/展示提示；要測 Gemini 必須在表單選「真實模型」重新送出，檢查最新 history 是否顯示 `real_adapter`、`ready`、`fallback_active: false`。
- 後續人工檢查發現 `/closet/stylist` 首屏下方出現大片空白，主操作表單沒有立即顯示。
- 已修正：
  - Stylist / Try-on 主操作區加上 `vogue-critical-flow`，不再依賴 reveal 動畫才可見。
  - Stylist hero 改為明確提示「真實模型」人工測試，新增「開始產生」錨點按鈕。
  - Try-on hero 改為明確提示舊失敗紀錄會保留，新增「建立新任務」錨點按鈕。
  - CSS 加入 critical flow fallback，避免表單區因動畫初始化失敗變成透明空白。
- 驗證：
  - `php -l resources\views\closet\stylist.blade.php` -> passed。
  - `php -l resources\views\closet\tryon.blade.php` -> passed。
  - `npm.cmd run build` -> passed。
  - `php artisan view:clear` -> passed。
  - `/closet/stylist`、`/closet/try-on`、`/build/manifest.json` -> HTTP 200。
- 本階段沒有上傳 GitHub、沒有 stage、沒有 commit、沒有 push。

下一個大進度：請刷新 `/closet/stylist` 與 `/closet/try-on`，確認表單直接可見；再人工重建 Try-on L1 任務與 Stylist 真實模型任務。

## 2026-06-10 後續大進度 13：Gemini API 外部 Smoke 通過

- 使用者已提供 Gemini key，但 key 一開始放在 `.env.example`。
- 已用不印出 key 的方式將 `GEMINI_API_KEY` 搬到本機 `.env`，並清空 `.env.example` 的 `GEMINI_API_KEY`，避免 GitHub 外洩。
- 已執行 `php artisan config:clear`。
- 第一次 `php artisan vogueai:gemini-smoke` 回 `GEMINI_HTTP_ERROR / HTTP 503`，判斷為外部 Gemini 暫時不可用或模型端波動。
- 第二次 `php artisan vogueai:gemini-smoke` 成功：
  - `Status: ready`
  - `Mode: real_adapter`
  - `Fallback active: no`
  - `Endpoint: /v1beta/models/gemini-2.5-flash:generateContent`
  - `Summary: Gemini API smoke passed.`
- `php artisan vogueai:provider-check` 在 AI Service / Qdrant 未啟動時已從 3 warnings 降為 2 warnings；Gemini config 已通過。
- 短暫啟動 AI Service + Qdrant 並 ensure collection 後：
  - `php artisan vogueai:provider-check` -> 0 failed / 0 warnings
  - `php artisan vogueai:real-mode-check` -> 0 failed / 0 warnings
- 已修正 `RealModeAcceptanceService` 在 0 warnings 時的提示文字，現在會顯示 provider gate fully ready。
- 驗證：
  - `php -l app\Services\RealModeAcceptanceService.php` -> passed
  - `.\vendor\bin\pest.bat tests\Feature\RealModeAcceptanceTest.php tests\Feature\ProviderReadinessTest.php tests\Feature\GeminiSmokeTest.php` -> 7 passed / 30 assertions
- 本階段沒有上傳 GitHub、沒有 stage、沒有 commit、沒有 push。

下一個大進度：人工測 `/closet/stylist` 選「真實模型」產生建議，確認 history 實際寫入 `ready / real_adapter`；或進入 GitHub blocker 確認。

## 2026-06-10 後續大進度 12：Gemini API Smoke Gate 完成

- 已確認現有 `StylistTextGenerationService` 已具備 Gemini REST adapter：
  - `provider_mode=real` / `mock_mode=false` 時會嘗試 Gemini。
  - 未設定 `GEMINI_API_KEY` 時會安全回到 `degraded / real_adapter_attempt / GEMINI_API_KEY_MISSING`。
  - Gemini 回傳 structured JSON 時會進入 `ready / real_adapter`。
- 已新增 artisan 指令：
  - `php artisan vogueai:gemini-smoke`
- `gemini-smoke` 會用固定測試 payload 呼叫 `StylistTextGenerationService`，不寫入資料庫、不印出 API key。
- 目前本機 `.env` 尚未設定 `GEMINI_API_KEY`，所以實際指令結果為預期的安全失敗：
  - `Status: degraded`
  - `Mode: real_adapter_attempt`
  - `Error code: GEMINI_API_KEY_MISSING`
- 已新增測試：
  - 缺 key 時 smoke 指令安全失敗。
  - fake Gemini structured response 時 smoke 指令通過 `ready / real_adapter`。
- `php artisan vogueai:upload-scope` 最新狀態已更新為 115 changed/untracked entries；新增的 `GeminiSmokeTest` 已歸入 `demo-readiness-provider-gates`，`needs-manual-review` 維持 0。
- 驗證：
  - `php -l routes\console.php` -> passed
  - `php -l tests\Feature\GeminiSmokeTest.php` -> passed
  - `.\vendor\bin\pest.bat tests\Feature\GeminiSmokeTest.php tests\Feature\AiStylistTest.php tests\Feature\ProviderReadinessTest.php` -> 20 passed / 170 assertions
  - `.\vendor\bin\pest.bat tests\Feature\UploadScopeReviewTest.php tests\Feature\GeminiSmokeTest.php` -> 5 passed / 25 assertions
  - `php artisan vogueai:gemini-smoke` -> failed as expected because `GEMINI_API_KEY` is missing
- 本階段沒有上傳 GitHub、沒有 stage、沒有 commit、沒有 push。

下一個大進度：使用者在本機 `.env` 填入 `GEMINI_API_KEY` 後，執行外部 Gemini smoke；通過後再人工測 `/closet/stylist` 的「真實模型」是否寫入 `ready / real_adapter`。

## 2026-06-09 後續大進度 11：可重跑 Upload Scope Review Gate 完成

- 已新增 `UploadScopeReviewService`，把 GitHub 前分組檢查做成可重跑的只讀 gate。
- 已新增 artisan 指令：
  - `php artisan vogueai:upload-scope`
- 指令會輸出：
  - changed/untracked entries 總數
  - 高階分類：AI service、Laravel backend、Views and UI、Tests、Docs、Config and scripts、Database migrations、Assets、Other
  - 建議 commit groups
  - 仍需使用者確認的項目
  - `.env` / 大型模型 artifact 風險
  - 明確標示不會 stage、commit、push 或 PR
- 目前最新結果：
  - Total: 115 changed/untracked entries
  - AI service: 26
  - Laravel backend: 17
  - Views and UI: 34
  - Tests: 20
  - Docs: 6
  - Config and scripts: 4
  - Database migrations: 5
  - Assets: 3
  - Other: 0
- 建議 commit group 最新結果：
  - `ai-service-adapter-contracts`: 26
  - `laravel-closet-stylist-workflows`: 19
  - `demo-readiness-provider-gates`: 21
  - `localized-ui-and-manual-polish`: 42
  - `project-docs-and-roadmap`: 6
  - `telescope-duplicate-migration-cleanup`: 1
  - `needs-manual-review`: 0
- `php artisan vogueai:github-check` 仍正確阻擋上傳：2 blockers / 0 warnings。
- 兩個 blocker 仍是：
  - Worktree 115 changed/untracked entries 需要使用者確認全部屬於上傳範圍。
  - Telescope duplicate migration deletion 需要使用者明確確認。
- 驗證：
  - `php -l app\Services\UploadScopeReviewService.php` -> passed
  - `php -l routes\console.php` -> passed
  - `php -l tests\Feature\UploadScopeReviewTest.php` -> passed
  - `.\vendor\bin\pest.bat tests\Feature\UploadScopeReviewTest.php tests\Feature\GithubReadinessTest.php` -> 7 passed / 29 assertions
- 本階段沒有上傳 GitHub、沒有 stage、沒有 commit、沒有 push。

下一個大進度：等待使用者確認 GitHub blockers，或改走 Gemini API key 外部 smoke / 人工頁面驗收。

## 2026-06-09 後續大進度 10：GitHub 前人工整理 / 分組檢查完成

- 已依目前最新 worktree 重新整理 GitHub 前人工檢查，但沒有 stage、commit、push，也沒有上傳 GitHub。
- 已更新 `docs/github-upload-review-checklist.md` 到 2026-06-09 最新狀態。
- 目前 `git status --short --untracked-files=all` 分組：
  - AI service: 26
  - Laravel backend: 16
  - Views and UI: 34
  - Tests: 18
  - Docs: 6
  - Config and scripts: 4
  - Database migrations: 5
  - Assets: 3
  - Other: 0
  - Total: 112 changed/untracked entries
- 已把新增的 Provider readiness / Real-mode acceptance 檔案補入 dry-run staging manifest：
  - `app/Services/ProviderReadinessService.php`
  - `app/Services/RealModeAcceptanceService.php`
  - `tests/Feature/ProviderReadinessTest.php`
  - `tests/Feature/RealModeAcceptanceTest.php`
  - `docs/model-integration-readiness.md`
  - `start-qdrant.ps1`
- `php artisan vogueai:github-check` 仍正確阻擋上傳：2 blockers / 0 warnings。
- 兩個 blocker 仍是：
  - Worktree 112 changed/untracked entries 需要使用者確認全部屬於上傳範圍。
  - Telescope duplicate migration deletion 需要使用者明確確認。
- `.env` 沒有出現在 git status；大型模型 artifact 沒有出現在 git status。
- 本階段沒有上傳 GitHub、沒有 stage、沒有 commit、沒有 push。

下一個大進度：等待使用者決定是否確認 Telescope migration cleanup 與上傳範圍；若仍不處理 GitHub，則進入 `GEMINI_API_KEY` 外部 smoke 或人工頁面驗收。

## 2026-06-09 後續大進度 9：完整 Regression Gate 完成

- 已完成目前專案的完整 regression gate，不接外部 Gemini key、不上傳 GitHub。
- Laravel 全量測試通過：
  - `.\vendor\bin\pest.bat` -> 83 passed / 427 assertions
- AI Service 全量測試通過：
  - `ai_service\.venv\Scripts\python.exe -m pytest ai_service\tests` -> 39 passed / 1 warning
  - warning 為 Qdrant daemon 未啟動時的 client-server compatibility 提醒，屬於既有 degraded 測試情境。
- 前端 production build 通過：
  - `npm.cmd run build` -> passed
  - 第一次在 sandbox 內因 Vite config 路徑讀取權限失敗；使用已核准的 build 權限重跑後通過。
- Readiness / acceptance checks：
  - `php artisan vogueai:demo-check` -> 0 failed / 1 warning（既有 Telescope migration GitHub gate）
  - `php artisan vogueai:real-mode-check`（服務未啟動）-> 0 failed / 1 warning（提醒先開 AI Service + Qdrant）
  - `php artisan vogueai:provider-check`（服務未啟動）-> 0 failed / 3 warnings（AI Service、Qdrant、Gemini key）
  - 啟動 AI Service + Qdrant 並 ensure collection 後，`php artisan vogueai:provider-check` -> 0 failed / 1 warning（只剩 `GEMINI_API_KEY`）
  - 啟動 AI Service + Qdrant 並 ensure collection 後，`php artisan vogueai:real-mode-check` -> 0 failed / 0 warnings
- GitHub readiness：
  - `php artisan vogueai:github-check` -> 2 blockers / 0 warnings
  - blockers 為 worktree 112 changed/untracked entries，以及 Telescope migration deletion 需上傳前人工確認。
- 本階段沒有上傳 GitHub、沒有 stage、沒有 commit、沒有 push。

下一個大進度：若仍跳過 Gemini key，進入 GitHub 前人工整理/分組檢查；若提供 `GEMINI_API_KEY`，則進入外部 Gemini smoke。

## 2026-06-09 後續大進度 8：AI Search / AI Stylist 真實模式驗收 Gate 完成

- 已新增 `RealModeAcceptanceService`，集中檢查 AI Search 與 AI Stylist 真實模式人工驗收前狀態。
- 已新增 artisan 指令：
  - `php artisan vogueai:real-mode-check`
- Gate 會檢查：
  - AI Search / AI Stylist routes 是否註冊。
  - AI Search 是否有 `provider_mode=real` 入口，且本次請求會傳 `mock_mode=false`。
  - AI Search 真實模式 feature test 是否覆蓋 `mock_mode=false`。
  - AI Stylist 是否有 `provider_mode=real` 入口。
  - Gemini key 缺失時是否保留 `GEMINI_API_KEY_MISSING` fallback。
  - fake Gemini ready response 是否可寫入 `ready / real_adapter`。
  - provider startup gate 是否已到預期狀態。
  - manual acceptance checklist 是否包含真實模式驗收項。
- 未啟動 AI Service / Qdrant 時，`php artisan vogueai:real-mode-check` 結果為 `0 failed / 1 warning`，提醒先啟動 provider。
- 啟動 AI Service + Qdrant 並 ensure collection 後，`php artisan vogueai:real-mode-check` 結果為 `0 failed / 0 warnings`。
- 同一輪 `php artisan vogueai:provider-check` 維持 `0 failed / 1 warning`，剩餘唯一 warning 是 `GEMINI_API_KEY` 尚未設定。
- 驗證：
  - `php -l app\Services\RealModeAcceptanceService.php` -> passed
  - `php -l routes\console.php` -> passed
  - `php -l tests\Feature\RealModeAcceptanceTest.php` -> passed
  - `.\vendor\bin\pest.bat tests\Feature\RealModeAcceptanceTest.php` -> 2 passed / 7 assertions
  - `.\vendor\bin\pest.bat tests\Feature\RealModeAcceptanceTest.php tests\Feature\ProviderReadinessTest.php tests\Feature\AiSearchTest.php tests\Feature\AiStylistTest.php` -> 28 passed / 210 assertions
  - `php artisan vogueai:real-mode-check` -> 0 failed / 1 warning（服務未啟動時提醒先開 AI Service + Qdrant）
  - `php artisan vogueai:demo-check` -> 0 failed / 1 warning（既有 Telescope migration GitHub gate）
- 本階段沒有上傳 GitHub、沒有 stage、沒有 commit、沒有 push。

下一個大進度：補跑 AI Search / AI Stylist focused regression tests，或提供 `GEMINI_API_KEY` 後做外部 Gemini smoke。

## 2026-06-09 後續大進度 7：Provider Gate 啟動驗收完成

- 已用本機短暫背景服務啟動 Qdrant 與 AI Service，完成真實 provider gate smoke。
- AI Service `/health` 回應 `status=ok`。
- 已呼叫 `/ai/vector-store/collection/ensure?create_missing=true`，Qdrant collection ensure 回應 `ready`。
- `php artisan vogueai:provider-check` 已從「0 failed / 3 warnings」收斂到「0 failed / 1 warning」。
- 剩餘唯一 warning 是 `GEMINI_API_KEY` 尚未設定；這是外部 Gemini 憑證條件，不影響 CLIP / BLIP / Qdrant 本機 provider gate。
- `php artisan vogueai:demo-check` 維持「0 failed / 1 warning」，剩餘 warning 仍是 GitHub 前的 Telescope migration 確認項。
- 本階段沒有上傳 GitHub、沒有 stage、沒有 commit、沒有 push。

下一個大進度：準備 Gemini key 外部 smoke，或先進行 AI Search / AI Stylist 真實模式人工驗收。

## 2026-06-09 後續大進度 6：真實 Provider 啟動前總驗收 Gate 完成

- 已新增 `ProviderReadinessService`，集中檢查真實 provider 啟動前的必要條件與風險。
- 已新增 artisan 指令：
  - `php artisan vogueai:provider-check`
- Provider gate 目前會檢查：
  - AI service venv python
  - `ai_service/requirements-ml.txt`
  - `start-qdrant.ps1`
  - CLIP / BLIP Hugging Face model cache
  - AI Search `provider_mode=real` 入口
  - AI Stylist `provider_mode=real` / Gemini fallback 入口
  - AI service `/health`
  - Qdrant preflight
  - Gemini key/model 設定
  - Qdrant runtime 與 Hugging Face cache 是否被 Git 忽略
- Gate 規則：必要結構缺失才 `fail`；AI service/Qdrant 未啟動、Gemini key 未填則 `warn`，不阻擋 demo-safe 開發。
- 目前執行結果：
  - `php artisan vogueai:provider-check` -> 0 failed / 3 warnings
  - warnings 為：AI service 未啟動、Qdrant preflight 未執行、`GEMINI_API_KEY` 未設定
- 驗證：
  - `php -l app\Services\ProviderReadinessService.php` -> passed
  - `php -l routes\console.php` -> passed
  - `php -l tests\Feature\ProviderReadinessTest.php` -> passed
  - `.\vendor\bin\pest.bat tests\Feature\ProviderReadinessTest.php` -> 3 passed / 12 assertions
  - `.\vendor\bin\pest.bat` -> 81 passed / 420 assertions
  - `php artisan vogueai:demo-check` -> 0 failed / 1 warning（既有 Telescope migration GitHub gate）

後續狀態：此項已於「後續大進度 7」完成，provider gate 已收斂到只剩 Gemini key warning；若提供 Gemini key，再做外部 Gemini smoke。

## 2026-06-09 後續大進度 5：AI Stylist Gemini 真實模型驗收入口完成

- 已在 AI Stylist 表單加入「生成模式」選項：預設 `展示模式`，可切換為 `真實模型`。
- `展示模式` 維持原本 rule-based / mock Gemini contract，不影響 demo-safe 預設。
- `真實模型` 會把本次 stylist request 傳入 `mock_mode=false`，由 `StylistTextGenerationService` 嘗試 Gemini text adapter。
- 已新增 Gemini REST adapter 嘗試流程：
  - 有 `GEMINI_API_KEY` 時，會呼叫 Gemini `generateContent` REST endpoint。
  - 沒有 `GEMINI_API_KEY` 時，不會讓流程壞掉，會記錄 `degraded / real_adapter_attempt / GEMINI_API_KEY_MISSING` 並保留 `rule_based_text` fallback。
  - Gemini 回傳 valid JSON 時，Stylist History 會記錄 `ready / real_adapter`。
- 已新增設定：
  - `GEMINI_API_KEY`
  - `GEMINI_API_BASE_URL`
  - `GEMINI_TEXT_MODEL=gemini-2.5-flash`
- AI Stylist history 的 Gemini 區塊現在會顯示 `fallback_active`、`error_code`、`endpoint`、`reasoning_notes` 等驗收資訊。
- 驗證：
  - `php -l app\Services\StylistTextGenerationService.php` -> passed
  - `php -l app\Http\Controllers\ClosetController.php` -> passed
  - `php -l resources\views\closet\stylist.blade.php` -> passed
  - `.\vendor\bin\pest.bat tests\Feature\AiStylistTest.php` -> 15 passed / 147 assertions
  - `.\vendor\bin\pest.bat` -> 78 passed / 408 assertions
  - `php artisan vogueai:demo-check` -> 0 failed / 1 warning（既有 Telescope migration GitHub gate）
  - 登入後頁面 smoke：`POST /closet/stylist` with `provider_mode=real` -> HTTP 200，頁面包含 `真實模型`、`gemini-stylist-text-v1`、`real_adapter_attempt`、`GEMINI_API_KEY_MISSING`、`fallback_active`

下一個大進度：執行 AI Stylist 真實模式人工頁面驗收；若你提供有效 `GEMINI_API_KEY`，再做真實外部 Gemini API smoke。

## 2026-06-09 後續大進度 4：AI Search 真實模型畫面驗收入口完成

- 已在 AI Search 表單加入「搜尋模式」選項：預設 `展示模式`，可切換為 `真實模型`。
- `展示模式` 仍沿用全域 demo-safe `AI_MOCK_MODE=true` 行為。
- `真實模型` 只影響本次搜尋 request，Laravel 會傳 `mock_mode=false` 給 `embedText()` 與 `searchSimilar()`，不需要修改 `.env`。
- 已新增 feature test，確認 URL `provider_mode=real` 時兩個 AI service call 都會收到 `mock_mode=false`。
- 此入口用於人工驗收 Qdrant 真實搜尋頁面：啟動 Qdrant + AI Service 後，在 `/closet/ai-search` 選 `真實模型` 搜尋衣物描述。
- 已執行登入後頁面驗收：`/closet/ai-search?q=white%20shirt&provider_mode=real&top_k=6` 回 HTTP 200，頁面包含 `真實模型`、`qdrant`、`clip-vit-base-patch32`、`qdrant_vector_similarity` 與衣物結果。
- Demo user Qdrant smoke：demo user id `22`，真實搜尋回資料庫內衣物 `白色上衣`，`search_provider=qdrant`，`fallback_active=false`。
- 驗證：
  - `.\vendor\bin\pest.bat tests\Feature\AiSearchTest.php` -> 8 passed / 44 assertions
  - `.\vendor\bin\pest.bat` -> 76 passed / 386 assertions
  - `ai_service\.venv\Scripts\python.exe -m pytest ai_service\tests` -> 39 passed / 1 warning
  - `php artisan vogueai:demo-check` -> 0 failed / 1 warning（既有 Telescope migration GitHub gate）

## 2026-06-09 後續大進度 3：Qdrant 真實搜尋接入完成

- 已把 AI Service `POST /ai/search/similar` 從固定 mock search 改成在 `mock_mode=false` 時呼叫 `qdrant_search_similar_clothing()`。
- 已修正 Qdrant client 1.18.x 搜尋 API：正式使用 `query_points(...)`，不再使用舊版不存在的 `search(...)`。
- 已修正真實搜尋 named vector 目標：CLIP text query 會搜尋衣物已儲存的 `clip_image` vector，避免查到空結果。
- 已保留 demo-safe 行為：`mock_mode=true` 仍回 mock/degraded；Qdrant 不可用時回 `degraded`，Laravel 可走 fallback。
- 已讓 Laravel `ClosetController` 接受 AI 回傳 `ready` 狀態，避免真實 provider 成功時被當成失敗。
- 已讓 AI Search 頁可吃下 `ready/qdrant` 搜尋結果並顯示原本 metadata。
- 已讓 stylist embedding 排序與 AI job 查詢納入 `ready` 狀態。
- 真實 HTTP smoke 已通過：`embed/image -> qdrant stored=true`、`embed/text -> 512D ready`、`search/similar -> qdrant ready`，隔離 user 搜回 `clothing_id=999004`。
- Laravel service smoke 已通過：`AiService::embedImage/embedText/searchSimilar(... mock_mode=false)` 搜回 `clothing_id=999005`，`fallback_active=false`。
- 驗證：
  - `ai_service\.venv\Scripts\python.exe -m pytest ai_service\tests` -> 39 passed / 2 warnings
  - `.\vendor\bin\pest.bat tests\Feature\AiSearchTest.php` -> 7 passed / 39 assertions
  - `.\vendor\bin\pest.bat` -> 75 passed / 381 assertions
  - `php artisan vogueai:demo-check` -> 0 failed / 1 warning（既有 Telescope migration GitHub gate）

下一個大進度：把 AI Search 的「畫面/人工驗收」與「真實 provider 啟動操作步驟」整理成可重複 checklist，之後再決定是否進入 Gemini Stylist 真實 provider 或 GitHub pre-upload gate。


## 2026-06-08 後續大進度 2：CLIP / BLIP HTTP 與 Laravel 局部真實流程完成

- 已完成 CLIP / BLIP 真實模型從 service function 往 AI Service HTTP endpoint 的接通。
- 已新增 `ai_service/utils/image_paths.py`，支援解析 absolute path、`public/...`、`storage/app/public/...`、`public/storage/...` 與 AI service 相對路徑。
- 已讓 CLIP / BLIP 開圖流程使用 `resolve_image_path()`，Laravel 傳入 `clothes/...` 或 `images/demo/...` 時可被真實模型讀取。
- 已補上 `ai_service/tests/test_image_paths.py`，驗證 public demo image 與 public disk storage path 解析。
- HTTP smoke 通過：`POST /ai/embed/text` + `mock_mode=false` -> `ready`、`real_adapter`、512D。
- HTTP smoke 通過：`POST /ai/embed/image` + `mock_mode=false` + `store_to_vector_db=false` -> `ready`、`real_adapter`、512D。
- HTTP smoke 通過：`POST /ai/attributes` + `mock_mode=false` -> BLIP `real_adapter_attempt=ready`，回傳 caption；屬性仍保留 hybrid/mock fallback。
- 已測 Laravel `App\Services\AiService` per-request `mock_mode=false`：text embedding 512D ready、image embedding 512D ready、BLIP caption ready。
- 額外修正 Qdrant point id：從無效的 `clothing_{id}` 改為 Qdrant 可接受的整數 `clothing_id`。
- 已修正 image embedding response metadata：Qdrant upsert 成功時 `provider=qdrant`、`collection=vogueai_clothing_embeddings`、`stored=true`、`fallback_active=false`。
- Qdrant upsert HTTP smoke 通過：`POST /ai/embed/image` + `store_to_vector_db=true` -> `ready`、512D、`stored=true`。
- Laravel 預設 `store_to_vector_db=true` smoke 通過：`AiService::embedImage(... mock_mode=false)` -> `provider=qdrant`、`stored=true`、point id 為整數。
- Demo-safe 全域設定仍維持 `AI_MOCK_MODE=true`，本階段只用 per-request `mock_mode=false` 做局部真實 adapter 測試。
- 驗證：`ai_service\.venv\Scripts\python.exe -m pytest ai_service\tests` -> 38 passed / 2 warnings。
- 下一個大進度：Qdrant 真實搜尋接入，讓 `/ai/search/similar` 從 mock search 進一步使用 `qdrant_search_similar_clothing()` 回傳真實向量搜尋結果。

## 2026-06-08 後續大進度 1：真實 Provider 環境啟動完成

- 已完成真實 Provider 環境啟動的本機可行版本，GitHub 上傳仍依使用者要求跳過。
- 已安裝重型 ML 依賴：`torch==2.12.0+cpu`、`transformers==5.10.2`，並保留 `pillow==12.2.0`、`qdrant-client==1.18.0`。
- 已新增 `AI_MODEL_CACHE_DIR` 設定，預設解析到 `ai_service/models/huggingface`，避免 Hugging Face 預設 cache 寫入 `C:\Users\User\.cache` 發生權限問題。
- 已將 `ai_service/models/huggingface/` 加入 `.gitignore`，模型 cache 不會進 Git。
- 已下載並 cache 真實模型：`openai/clip-vit-base-patch32` 與 `Salesforce/blip-image-captioning-base`。
- 已修正 CLIP adapter 對 `transformers` 5.x output 格式的支援，會正確抽出 `pooler_output` 作為 512D embedding。
- 真實模型 smoke test 通過：CLIP text embedding -> 512D ready；CLIP image embedding -> 512D ready；BLIP caption -> ready，demo 白色上衣可產生 caption。
- 已下載 Qdrant 官方 Windows binary `qdrant.exe` 到 `tools/qdrant/runtime`，版本 `1.18.2`；`tools/qdrant/` 已加入 `.gitignore`。
- 已新增 `start-qdrant.ps1`，人工或下一個 Codex 可用 `.\start-qdrant.ps1 -NoTelemetry` 開啟 Qdrant 視窗。
- 已建立並驗證 Qdrant collection：`vogueai_clothing_embeddings`。
- Qdrant collection schema 驗證通過：named vectors `clip_image` / `clip_text` 均為 512D、Cosine；payload indexes 包含 `user_id`、`clothing_id`、`category`、`color`、`season`、`occasion`、`style_tags`。
- 已新增 `VECTOR_STORE_TIMEOUT_SECONDS=10`，避免本機 Qdrant 初次連線過短 timeout。
- 因 Codex shell 會回收長駐子程序，Qdrant daemon 無法在本回合結束後保證常駐；已提供 `start-qdrant.ps1` 作為人工/下一回合啟動方式，collection 與模型 cache 已落地保留。
- 驗證：`ai_service\.venv\Scripts\python.exe -m pytest ai_service\tests` -> 36 passed / 2 warnings。
- 驗證：`.\vendor\bin\pest.bat` -> 74 passed / 373 assertions。
- 驗證：`php artisan vogueai:demo-check` -> 0 failed / 1 warning；warning 仍為 GitHub 前 Telescope migration confirmation。
- 下一個大進度：CLIP / BLIP 真實模型 Smoke Test 與 API flow 整合，也就是從直接 service smoke 進一步走 AI Service HTTP endpoint / Laravel 呼叫鏈。

## 2026-06-07 大步驟 5：真實模型 / Provider 本機前置完成

- 已依使用者要求跳過 GitHub 上傳，直接推進第 5 大步驟。
- 已在 `ai_service/.venv` 安裝輕量 provider 前置依賴：`qdrant-client==1.18.0`、`pillow==12.2.0`。
- 已修正 AI service 測試預期：目前狀態不再是「Qdrant client 未安裝」，而是「client 可用但 Qdrant daemon 未啟動，因此安全 degraded」。
- `ai_service\.venv\Scripts\python.exe -m pytest ai_service\tests` -> 36 passed / 1 warning。
- `php artisan vogueai:demo-check` -> 0 failed / 1 warning；warning 仍是 GitHub 前的 Telescope migration 確認，不影響 demo。
- AI Service `/health` HTTP 200：`mock_mode=true`、`pillow=available`、`qdrant=available`、`clip=mock`、`blip=mock`。
- Qdrant preflight 已用正確 header `X-Internal-AI-Token` 驗證：connection check 會 attempt，因本機 Qdrant daemon 未啟動而安全回 `QDRANT_CONNECTION_FAILED`。
- Qdrant collection ensure 已驗證安全 degraded：未建立 collection、未 verified，錯誤碼為 `QDRANT_COLLECTION_ENSURE_FAILED`。
- 已更新 `docs/model-integration-readiness.md`：補上 Step 5 執行狀態、修正 internal endpoint header、列出真實模型啟用前仍需完成的外部條件。
- 目前不能宣稱 CLIP / BLIP 真實模型已完成，因 `torch`、`transformers`、model cache 尚未安裝；這部分需要下一輪明確進入重型 ML 依賴與模型下載。
- 下一個大步驟：啟動真正 provider 環境，包含 Qdrant daemon、512D collection、`torch/transformers`、CLIP/BLIP smoke test；Gemini 需等 API key 再接。

## 2026-06-07 大步驟 4：模型串接前置準備完成

- 已完成第 4 大步驟：模型串接前置準備。
- 已新增 `docs/model-integration-readiness.md`，作為 CLIP / BLIP / Qdrant / Gemini / pose provider 正式串接前的 gate 文件。
- 已整理 provider matrix：CLIP image/text embedding、BLIP caption、Qdrant vector store、Gemini text generation、Pose / Magic Mirror。
- 已整理 Laravel `.env`、AI Service `.env`、optional ML dependencies、啟用順序、smoke commands、provider gates、fallback rules。
- 已明確保留 demo-safe 決策：目前 `AI_MOCK_MODE=true`，不啟用真實 provider。
- AI Service health 確認：mock mode true、Qdrant fallback active、Qdrant client missing、Pillow missing、外部 API keys missing，符合前置階段預期。
- AI Service full tests 通過：`ai_service\.venv\Scripts\python.exe -m pytest ai_service\tests` -> 36 passed。
- Demo readiness 通過：`php artisan vogueai:demo-check` -> 0 failed / 1 warning；warning 仍為 GitHub 前 Telescope confirmation。
- 下一個大步驟：真實模型 / Provider 接入，建議先從環境安裝與 CLIP / BLIP 本機 smoke test 開始，再進 Qdrant，最後才接 Gemini 與 pose provider。

## 2026-06-07 大步驟 3：Demo 穩定化與資料重整完成

- 使用者確認第 1 大步驟「全站人工畫面確認」與第 2 大步驟「核心功能使用流程確認」暫定完成。
- 已直接進入第 3 大步驟：Demo 穩定化與資料重整。
- `php artisan vogueai:demo-check` 結果：0 failed / 1 warning；warning 仍為 GitHub 前 Telescope migration confirmation，不影響 demo。
- `php artisan vogueai:demo-data seed` 已成功執行，demo 帳號為 `demo@vogueai.local` / `password`。
- Demo seed 可重複執行，結果穩定：3 clothes、4 embeddings、3 wear logs、2 stylist histories、1 outfit log。
- Demo 圖片資產確認存在：`public/images/demo/white-shirt.jpg`、`navy-blazer.jpg`、`red-dress.jpg`。
- Demo focused tests 通過：`.\vendor\bin\pest.bat tests\Feature\DemoDataTest.php tests\Feature\DemoReadinessTest.php` -> 4 tests / 26 assertions。
- Demo 帳號登入後 smoke check 通過：Dashboard、我的衣櫥、AI 搜尋、AI 穿搭顧問、Digital Twin 全部 HTTP 200。
- 下一個大步驟：模型串接前置準備，先盤點 CLIP / BLIP / Qdrant / Gemini / pose provider 的 adapter contract、環境需求與啟用條件。

## 2026-06-07 決策：先跳過 GitHub 上傳，改走後續產品完成路線

- 使用者已明確要求先跳過 GitHub 上傳。
- 本階段不 stage、不 commit、不 push、不開 PR。
- GitHub 前整理文件仍保留，之後若要上傳，可從 `docs/github-upload-review-checklist.md` 的 staging manifest 繼續。
- 目前後續大步驟改為產品完成與人工驗收路線：
  1. 全站人工畫面確認與問題修補。
  2. 核心功能使用流程確認。
  3. Demo 穩定化與資料重整。
  4. 模型串接前置準備。
  5. 真實模型 / Provider 接入。
  6. 進階功能 P5 / P6 補齊。
  7. 最終回歸測試與展示版封版。
  8. GitHub 上傳與部署準備，等使用者重新批准後再進行。

## 2026-06-07 大步驟：精準 Staging Manifest 草稿完成

- 已將 GitHub 前整理從「建議 commit 群組」推進到「精準 staging manifest」。
- 已在 `docs/github-upload-review-checklist.md` 列出 5 個 commit group 的 dry-run 檔案清單。
- Commit 1：AI service adapter contracts。
- Commit 2：Laravel closet / stylist workflows。
- Commit 3：demo readiness and GitHub gates。
- Commit 4：localized UI and manual polish。
- Commit 5：project docs and roadmap。
- Telescope duplicate migration deletion 已獨立列為 blocked item，使用者未明確確認前不 stage。
- 本階段仍未 stage、未 commit、未 push。

## 2026-06-07 大步驟：GitHub 前提交策略草稿完成

- 已將下一步推進到 GitHub 前整理計畫，但仍未 stage、未 commit、未 push。
- `php artisan vogueai:github-check` 仍為 2 blockers / 0 warnings，狀態符合預期。
- 已在 `docs/github-upload-review-checklist.md` 新增 draft commit plan，將 104 個 changed/untracked entries 拆成 5 個建議提交群組。
- 建議群組包含：AI service adapter contracts、Laravel closet/stylist workflows、demo readiness and GitHub gates、localized UI and manual polish、project docs and roadmap。
- 每個群組都已附上建議驗證指令，最後仍需全量 Laravel、全量 AI service、demo-check、github-check 與 git status 作為 pre-push gate。
- 下一個阻塞點仍是使用者確認 Telescope duplicate migration cleanup；未確認前不進入實際 Git 上傳流程。

## 2026-06-07 大步驟：GitHub 前預上傳稽核啟動

- 已進入下一個大步驟：GitHub 前變更整理與上傳準備。
- 本階段只做稽核與文件更新，未 stage、未 commit、未 push。
- `php artisan vogueai:github-check` 目前仍為 2 blockers / 0 warnings。
- Blocker 1：worktree 目前有 104 changed/untracked entries，需要後續按 review group 有意識整理。
- Blocker 2：Telescope duplicate migration deletion 需要使用者明確確認。
- 已重新比對 Telescope migration：`2026_04_22_161640_create_telescope_entries_table.php` 與已刪除的 `2026_04_22_161722_create_telescope_entries_table.php` 內容相同，兩者都建立 `telescope_entries`、`telescope_entries_tags`、`telescope_monitoring`。
- 已更新 `docs/github-upload-review-checklist.md`，記錄 2026-06-07 pre-upload audit、變更分組與 blocker 狀態。

## 2026-06-06 大步驟：GitHub 前全站功能驗收完成（仍先跳過 GitHub）

- 已完成登入後主要頁面 smoke check：Dashboard、Smart Closet Hub、我的衣櫥、新增衣物、AI 搜尋、AI 穿搭顧問、試穿、Runway Video、Digital Twin、帳號總覽、編輯帳號全部 HTTP 200。
- 已確認本機服務健康：Laravel `/` HTTP 200、`/login` HTTP 200、Vite `@vite/client` HTTP 200、AI Service `/health` HTTP 200。
- 已完成核心功能回歸：`SmartClosetTest`、`AiSearchTest`、`AiStylistTest`、`ProfileTest`、`AiJobsL1Test` 共 44 tests / 286 assertions 通過。
- 已完成全量 Laravel 回歸：`.\vendor\bin\pest.bat` -> 74 tests / 373 assertions 通過。
- 已完成全量 AI Service 回歸：`ai_service\.venv\Scripts\python.exe -m pytest ai_service\tests` -> 36 tests 通過。
- Demo readiness：`php artisan vogueai:demo-check` -> 0 failed / 1 warning；warning 仍為 Telescope migration deletion 需 GitHub 前確認。
- GitHub readiness：`php artisan vogueai:github-check` -> 2 blockers / 0 warnings；blockers 為 104 changed/untracked entries 與 Telescope migration deletion confirmation。
- 本階段仍依使用者要求跳過 GitHub：沒有 stage、沒有 commit、沒有 push。

## 2026-06-06 手動畫面確認修正：衣櫥列表縮圖固定大小

- 已修正「我的衣櫥」列表中每件衣物圖片看起來大小不一致的問題。
- 列表頁新增固定 280px 縮圖舞台，衣物卡片改為一致高度與一致按鈕位置。
- 列表縮圖使用固定裁切式呈現，讓實拍圖與 demo 佔位圖在卡片中視覺大小更一致。
- 衣物詳情頁仍保留完整圖片顯示，不影響查看原圖。
- 驗證：`php -l resources/views/closet/index.blade.php` 通過；`npm.cmd run build` 通過；Vite CSS 已載入固定縮圖規則。

## 2026-06-06 手動畫面確認修正：帳號總覽主題切換

- 已修正「帳號總覽」進入後停在夜間模式、無法切換日間的問題。
- 原因：帳號總覽使用獨立 Blade 頁，包含主題切換按鈕但缺少 `x-vogue-page` 內建的主題初始化腳本。
- 修正：將主題控制器補到共同側邊導覽元件，並加上防重複綁定保護，避免一般頁面按一次被切換兩次。
- 驗證：`php -l` 通過相關 Blade；`tests/Feature/ProfileTest.php` 通過 6 tests / 24 assertions；`npm.cmd run build` 通過。

## 2026-06-06 手動畫面確認修正：衣櫥圖片比例

- 已修正「我的衣櫥」衣物卡片圖片被拉成扁平橫條的問題。
- 衣物列表卡片改為桌機商品卡寬度，手機仍維持單欄滿版。
- 衣物圖片顯示改為 `object-fit: contain` 與置中，避免上衣、洋裝等圖片被裁切或壓扁。
- 同步套用到衣物詳情、AI 搜尋結果、AI 造型建議中的衣物圖片。
- 驗證：`php -l` 通過 closet show/search/stylist Blade；`npm.cmd run build` 通過。

## 最新乾淨進度：2026-06-06 人工畫面確認階段

- 已依使用者指示進入人工畫面確認階段，GitHub 上傳先跳過。
- 本機服務已確認可用：Laravel `/`、Laravel `/login`、Vite `@vite/client`、AI Service `/health` 皆回應 HTTP 200。
- 人工確認重點：中文介面是否舒服、主要頁面是否能開、按鈕與表單是否清楚、介紹型英文是否還有殘留、AI/mock/degraded 技術資訊是否只出現在需要除錯的位置。
- 已在 `docs/manual-acceptance-checklist.md` 最上方新增乾淨的 2026-06-06 人工畫面確認清單，供逐頁勾選。
- 已補上人工確認快速連結與問題紀錄表，方便使用者逐頁檢查並回報頁面、問題與狀態。
- 已完成人工確認前內容 QA 掃描：14 個主要頁面皆 HTTP 200，主要英文介紹關鍵字未命中，主要頁面圖片連結未發現 404。
- 已建立人工確認截圖包：`storage/logs/manual-acceptance-screenshots/`；並修正登入/註冊 layout 亂碼、`.env`/`.env.example` 的 `APP_NAME=VogueAI`、以及人工確認用 `DEBUGBAR_ENABLED=false`。
- 已新增一頁式截圖總覽：`storage/logs/manual-acceptance-screenshots/index.html`，方便使用者快速掃過所有主要頁面截圖。
- 已重新截圖確認首頁、登入、註冊皆 HTTP 200，分頁標題為 VogueAI，且未偵測 debugbar。
- GitHub gate 暫不處理、不 stage、不 commit、不 push；待人工畫面確認完成後再回到上傳前整理。

## 最新乾淨進度：2026-06-06 完整回歸驗證

- 完整 Laravel 測試已通過：`.\vendor\bin\pest.bat` -> `73 passed / 370 assertions`。
- 完整 AI service 測試已通過：`ai_service\.venv\Scripts\python.exe -m pytest ai_service\tests` -> `36 passed`。
- Demo readiness 已通過：`php artisan vogueai:demo-check` -> `0 failed / 1 warning`；warning 仍是 Telescope migration 刪除需確認。
- GitHub readiness 仍封鎖：`php artisan vogueai:github-check` -> `2 blockers / 0 warnings`；blockers 為 102 筆 changed/untracked entries 與 Telescope 重複 migration 刪除需明確確認。
- 本機服務已重新啟動並驗證：Laravel `/`、Laravel `/login`、Vite `@vite/client`、AI Service `/health` 皆回應 HTTP 200。
- 已用 demo 帳號完成登入後頁面巡檢：首頁、儀表板、Smart Closet Hub、我的衣櫥、新增衣物、AI 搜尋、AI 穿搭顧問、試穿、Runway、Digital Twin、帳號總覽、編輯帳號皆回應 HTTP 200，且未命中主要介紹型英文掃描。
- 管理員使用者頁用 demo 帳號回應 HTTP 403，符合非 admin 帳號的權限保護預期；管理員頁本身已完成 Blade 語法檢查。

## 最新乾淨進度：2026-06-05 介面介紹英文清理

- 已完成主要可見頁面的介紹型英文清理，改成繁體中文，包含首頁、登入、註冊、Dashboard、共用導覽、Smart Closet Hub、My Closet、衣物新增/詳情、AI Search、AI Stylist、Try-on、Workspace、Runway Video、Digital Twin、Feature Spec 頁面。
- 已擴大清理 Breeze 預設頁、帳號總覽/編輯頁、管理員使用者管理頁、密碼重設/確認/驗證頁，以及舊 `welcome.blade.php`，並移除這些頁面的英文 i18n 切換包殘留。
- 已同步更新 `WorkspaceController`、`FeatureController`、`ClosetController` 的使用者可見模組名稱、結果標題、說明文字與 fallback/degraded 訊息；技術識別字、路由、資料鍵與 provider metadata 保持原本英文，以免破壞程式契約。
- 已更新相關 Feature Test 預期文字，避免測試仍要求舊英文文案。
- 驗證結果：Laravel `/`、Laravel `/login`、Vite dev client、AI Service `/health` 皆回應 200；`php -l` 控制器、翻譯檔與重點 Blade 檔皆通過；重點 Laravel 測試 `60 passed / 314 assertions`。
- GitHub 仍未開放上傳：`php artisan vogueai:github-check` 目前仍有 2 個 blocker，包含 102 個 changed/untracked entries 與 Telescope 重複 migration 刪除需要使用者明確確認。

最後更新：2026-06-05 Asia/Taipei

這份文件是 VogueAI 專案的核心進度來源，整合原始 roadmap、目前已完成項目、驗收結果與下一階段目標。

## 目前狀態

專案已具備可操作的 MVP 主流程，並完成 P0 穩定化、Try-on L2 核心驗收、Smart Closet / AI Search L3 adapter contract，以及 GitHub 上傳前 5 組 review 中的前 4 組。

已可完整運作的核心項目：

- Laravel 12 + Blade + Vite + Tailwind 主站。
- Python FastAPI AI Service，採 mock-first / degraded-first 設計。
- SQLite 本機資料庫與 Laravel migrations。
- Auth、Dashboard、Smart Closet Hub、Workspace 入口。
- My Closet 衣物上傳、列表、詳情與使用者資料隔離。
- AI attributes mock/degraded。
- image/text embedding mock/degraded。
- AI Search + SQL LIKE fallback，已修正 mock similar search id 不符合目前使用者衣物時的 fallback。
- AI Stylist L1.5 / L2 rule-based 建議與 history。
- Digital Twin L1 profile job。
- Digital Twin L2 closet-based style analysis。
- Try-on L1 pose job。
- Try-on L2 pose quality / quality checks / improvement tips。
- Runway Video L1 storyboard mock/degraded。
- `start-all.ps1` 可處理 Windows Path/PATH、npm.cmd、AI venv 與 Laravel 啟動路徑。

## 最新驗收結果

- Laravel full regression：`.\vendor\bin\pest.bat`，73 passed / 370 assertions。
- AI service full regression：`ai_service\.venv\Scripts\python.exe -m pytest ai_service\tests`，36 passed。
- Demo readiness：`php artisan vogueai:demo-check`，0 failed / 1 warning。
- GitHub readiness：`php artisan vogueai:github-check` 仍正確阻擋，2 blockers / 0 warnings；`git status --short --untracked-files=all` 目前 73 changed/untracked entries、無 raw warning；目前尚未 stage / commit / push。
- Browser/manual：2026-06-05 final spot-check passed：Home、Login、Dashboard、Smart Closet Hub、My Closet、Clothing Detail、AI Search keyword fallback metadata、AI Stylist、Try-on、Runway Video、Digital Twin 全部 HTTP 200，無 local 4xx response、無 console error。
- L3 狀態：目前完成 adapter contract、fallback、UI metadata 與測試；真實 CLIP / BLIP / Qdrant / Gemini / pose provider 仍屬 GitHub 後正式模型接入階段。

## P0：穩定化與完整可跑

- [x] 修正 Laravel/Pest 測試基礎。
- [x] 將 Breeze/Auth/Profile/Example closure tests 改成 class-based Laravel Feature Tests。
- [x] 建立 AI service `.venv`。
- [x] 補齊 `ai_service/requirements.txt` 的測試依賴。
- [x] 新增 `ai_service/tests`。
- [x] 驗證 `/health`、attributes、embedding、search、pose endpoint。
- [x] 驗證 `npm.cmd run build`。
- [x] 修正 `start-all.ps1`。
- [x] 驗證 Laravel + Vite + FastAPI smoke test。
- [x] 驗證完整 demo flow：註冊、Smart Closet、上傳衣物、AI Search、AI Stylist、Digital Twin、Try-on、Runway Video。
- [x] Digital Twin POST routes 移入 auth group。
- [x] 新增 guest protection tests。
- [x] 清理臨時與備份檔。

保留的正式 artifacts：

- `ai_service/tests/`
- `ai_service/docs/`
- `docs/vogueai-core-progress.md`
- `tests/Feature/AiSearchTest.php`
- `tests/Feature/SmartClosetTest.php`

注意事項：

- `database/migrations/2026_04_22_161722_create_telescope_entries_table.php` 目前是 pre-existing tracked deletion，尚未處理；已比對其內容與保留中的 `database/migrations/2026_04_22_161640_create_telescope_entries_table.php` 行內容一致，兩者都建立 `telescope_entries`、`telescope_entries_tags`、`telescope_monitoring`。仍需使用者明確確認「保留 161640、刪除 161722」後，才可解除 GitHub blocker。

## P1：10-C Try-on L2

- [x] 在 `/ai/pose` mock 回傳加入 `pose_quality_score`。
- [x] 在 `/ai/pose` mock 回傳加入 `pose_quality_status`。
- [x] 檢查人物照是否全身入鏡。
- [x] 檢查左右肩、髖部與 keypoints confidence。
- [x] 回傳 quality checks、缺漏 keypoints、品質警示與改善提示。
- [x] Try-on 頁面顯示 Pose Quality。
- [x] Try-on 頁面顯示 Quality Status。
- [x] Try-on 頁面顯示 Quality Checks。
- [x] Try-on 頁面顯示 Fit Notes。
- [x] Try-on 頁面顯示 Improvement Tips。
- [x] 新增 Try-on L2 Feature Test。
- [x] 新增 AI pose route regression assertions。
- [x] 完成 Try-on L2 UI browser check。
- [x] 補 Try-on L2 驗收紀錄。
- [ ] Try-on 與 Magic Mirror 共用 pose analysis 流程。
- [ ] 規劃 YOLO Pose / MediaPipe 真實模型接入。

Try-on L2 驗收 checklist：

- [x] 使用者可建立 pose_analysis job。
- [x] `ai_jobs.result_json` 會保存 pose quality、quality checks、pose analysis。
- [x] 畫面可讀取既有 job 並顯示 86% 品質分數。
- [x] 畫面可顯示 pass/review 類型的品質檢查。
- [x] 測試不依賴 GD extension。
- [x] Laravel full test suite 通過。
- [x] AI service full pytest suite 通過。

## P2：Runway Video L2

建議下一步目標。

- [x] 將 Runway Video 從 storyboard 提升成可追蹤 job 流程。
- [x] 保留 degraded/mock 狀態並建立 L2 preview job contract。
- [x] 顯示 mock video preview 區塊。
- [x] 在 `result_json` 保存 `video_prompt`、scene timeline、provider metadata。
- [x] 規劃 Veo provider placeholder adapter metadata。
- [x] 顯示 degraded placeholder preview。
- [x] 新增 Runway Video L2 Feature Test。
- [x] 做一次 browser UI check。

## P3：AI Stylist + Digital Twin 串接

- [x] 將 Digital Twin L2 closet profile 串進 AI Stylist。
- [x] AI Stylist 推薦時讀取最近一筆 `digital_twin_style_analysis`。
- [x] Stylist History 的 `recommendation_json` 保存使用到的 Digital Twin profile。
- [x] AI Stylist 頁面顯示目前可用的 Digital Twin Profile。
- [x] 最近推薦紀錄顯示 Digital Twin Profile Used。
- [x] 新增 AI Stylist + Digital Twin integration Feature Test。
- [x] 完成 AI Stylist + Digital Twin browser UI check。
- [x] 讓 stylist history 保存使用者偏好與拒絕/喜歡回饋。
- [x] 新增 `feedback_status`、`feedback_reason`、`feedback_json`、`feedback_submitted_at`。
- [x] 新增 AI Stylist feedback route 與 controller action。
- [x] AI Stylist 頁面可提交「喜歡這套 / 不適合」。
- [x] 不適合時可保存拒絕原因。
- [x] 新增 feedback ownership protection test。
- [x] 完成 AI Stylist feedback browser UI check。
- [x] 加入場合、天氣、季節的更完整輸入。
- [x] 新增 `context_json` 保存完整推薦情境。
- [x] AI Stylist 表單新增季節、正式程度、心情 / 氛圍、避免事項。
- [x] 推薦邏輯會使用季節 context 強化 season matching。
- [x] 推薦邏輯會使用避免事項排除不想要的衣物。
- [x] 新增 expanded context Feature Test。
- [x] 完成 expanded context browser UI check。
- [x] 使用 `ai_embeddings` 強化相似衣物搜尋。
- [x] AI Stylist 讀取 `ai_embeddings` image vector，使用 local cosine similarity 補強候選排序。
- [x] `recommendation_json.embedding_signals` 保存 local_cosine mode、top matches、score、embedding id。
- [x] AI Stylist History 顯示 Embedding Signals 與單品 Embedding Score。
- [x] 新增 AI Stylist ai_embeddings ranking Feature Test。
- [x] 完成 AI Stylist ai_embeddings browser UI check。
- [x] 規劃 Gemini 文字生成 adapter。
- [x] 新增 `App\Services\StylistTextGenerationService`，以 mock/degraded contract 產生 title、summary、styling tips。
- [x] `recommendation_json.text_generation` 保存 provider、adapter、status、mode、model、fallback、prompt contract。
- [x] AI Stylist History 顯示 Gemini Text Adapter 狀態。
- [x] 新增 `docs/gemini-stylist-adapter-plan.md`。
- [x] 新增 Gemini text adapter Feature Test 與 browser UI check。

## P4：Smart Closet / AI Search L3

L3 完成定義：目前完成的是可展示、可測試、可安全 fallback 的 L3 產品流程與 adapter contract；真實 CLIP / BLIP / Qdrant / Gemini / pose provider 屬於後續正式模型接入階段，不是不接，而是要等 GitHub 前 review、環境依賴、模型下載與 daemon 驗證都完成後再切換。

- [ ] 接入真實 CLIP image/text embedding。
- [x] 建立 CLIP image/text embedding adapter readiness contract。
- [x] 建立 CLIP image/text embedding adapter methods，正式環境可產生 512D vector。
- [x] CLIP image/text embedding API 支援 `mock_mode=false` 嘗試正式 adapter，失敗安全 fallback。
- [x] AI Service embedding response 輸出 `embedding_provider` contract：target provider `clip`、active provider `mock_embedding_fallback`、adapter `clip-embedding-v1`。
- [x] AI Search metadata 顯示 Embedding Target、Embedding Adapter、Embedding Fallback。
- [x] 接入 BLIP 圖片描述 placeholder / fallback contract。
- [x] 建立 BLIP image caption adapter method，正式環境可產生圖片描述。
- [x] BLIP image caption API 支援 `mock_mode=false` 嘗試正式 adapter，失敗安全 fallback。
- [x] AI Service attributes response 輸出 `image_caption` contract：target provider `blip`、active provider `mock_caption_fallback`、adapter `blip-image-caption-v1`。
- [x] Clothing Detail UI 顯示 Image Caption Contract、Target、Adapter、Fallback、Model。
- [ ] 建立 Qdrant vector store。
- [x] 建立 Qdrant vector store placeholder / fallback contract。
- [x] 建立 Qdrant vector store readiness / health preflight metadata。
- [x] 建立 Qdrant vector store service skeleton 與 internal preflight endpoint。
- [x] Qdrant preflight 支援可選 `check_connection=true` 真連線檢查開關。
- [x] Qdrant collection schema contract 固定 target vector size、distance、named vectors 與 payload indexes。
- [x] Qdrant collection creation dry-run plan 固定 create/verify collection 規格與 activation guardrails。
- [x] Qdrant upsert/search dry-run plan 固定 point id、named vector、payload template 與 user filter。
- [x] Qdrant vector dimension guard 標示 mock 8D 不可寫入/查詢 512D target collection。
- [x] Qdrant collection ensure internal endpoint 可在正式環境手動建立/驗證 collection。
- [x] Qdrant upsert/search adapter methods 可在正式環境寫入/查詢 512D named vectors。
- [x] Laravel `AiService` 以 `AI_MOCK_MODE` 統一控制 `mock_mode`，可切換正式 adapter attempt。
- [x] Qdrant / CLIP / BLIP 環境範本與 `qdrant-client` dependency declaration。
- [x] AI Service mock embedding/search 會回傳 target provider `qdrant`、active provider `mock_sqlite_fallback`、adapter `qdrant-vector-store-v1`。
- [x] AI Service `/health` 顯示 `vector_store` readiness：target URL、collection、client package、fallback、next steps。
- [x] AI Search metadata 顯示 Target、Adapter、Fallback active。
- [x] 顯示 similarity score 與 metadata。
- [x] AI Search results 顯示 Search Metadata：rank、provider、model、match type、confidence、source。
- [x] keyword fallback 也會標示 `sql_like` / `keyword_fallback` / `fallback`。
- [x] 新增 AI Search similarity metadata Feature Test 與 browser UI check。
- [x] 新增 `wear_logs`。
- [x] 新增 `WearLog` model、`Clothing::wearLogs()` 關聯與 `POST /closet/{id}/wear`。
- [x] 衣物詳情頁新增 Wear Tracking，支援手動記錄穿著、顯示 wear count、last worn、recent wear logs。
- [x] 記錄穿著時同步更新 `clothes.wear_count` 與 `clothes.last_worn_at`。
- [x] 新增 wear_logs ownership Feature Test 與 browser UI check。
- [x] 新增 `outfit_logs`。
- [x] 新增 `OutfitLog` model、`StylistHistory::outfitLogs()` 關聯與 `POST /closet/stylist/{history}/outfit-log`。
- [x] AI Stylist History 新增 Outfit Log 區塊，可保存整套推薦穿搭、時間與 notes。
- [x] `outfit_logs` 保存 selected_items、item_ids、item_count、context_json、stylist_history_id 與 metadata。
- [x] 新增 outfit_logs ownership Feature Test 與 browser UI check。

## P5：其他功能 L1 / L2

- [ ] Trend / Chat Assistant L1。
- [ ] SmartTag / QuickSnap / Smart Storage L1。
- [ ] Community / Showcase / Blind Box / Travel Packer L1。
- [ ] Magic Mirror 共用 Try-on pose flow。
- [ ] 統一各功能的 fallback/degraded 顯示規則。

## P6：最終完整運作標準

- [ ] 每個功能都有可登入入口。
- [ ] 每個 L1 功能都有 mock/degraded flow。
- [ ] 關鍵 L2 功能有 Feature Test。
- [ ] AI Service 真實 provider adapter 可逐步替換 mock。
- [ ] fallback / degraded 狀態可被使用者理解。
- [x] `start-all.ps1` 可穩定啟動 demo。
- [x] README / docs / demo checklist 完整。
- [x] Demo 資料可建立、使用、清理。

## Demo 順序

1. 註冊 User。
2. 進 Dashboard。
3. 進 Smart Closet Hub。
4. 上傳衣物。
5. 驗證 AI attributes / embedding。
6. AI Search 找回衣物。
7. AI Stylist 產生穿搭建議。
8. Digital Twin 建立 closet-based profile。
9. Try-on 顯示 pose quality 與 keypoints。
10. Runway Video 顯示 storyboard / video placeholder。
11. 檢查 AI service `/health`。
12. 說明 fallback / degraded 狀態。

## 人工網頁驗收入口

- Laravel app：http://127.0.0.1:8000
- Login：http://127.0.0.1:8000/login
- Vite dev client：http://127.0.0.1:5173/@vite/client
- AI Service health：http://127.0.0.1:8001/health
- Demo user：`demo@vogueai.local`
- Demo password：`password`
- Demo data：已可用 `php artisan vogueai:demo-data seed` 建立，`php artisan vogueai:demo-data cleanup` 清理。
- 2026-06-03 人工驗收前檢查：Laravel `/` 與 `/login` HTTP 200、`/closet` 未登入 HTTP 302、Vite `@vite/client` HTTP 200、AI `/health` HTTP 200。
- 2026-06-03 使用者已完成 in-app browser 人工網頁驗收；下一步回到 GitHub 前 blocker 處理。
- 2026-06-03 人工網頁驗收後完整回歸：Laravel Pest 全測 72 passed / 361 assertions；AI service pytest 全測 33 passed。

## 人工可驗收功能清單

- [x] App 首頁可開啟：`http://127.0.0.1:8000/`。
- [x] Login 頁可開啟：`http://127.0.0.1:8000/login`。
- [x] 未登入進入 `/closet` 會導回登入。
- [x] Demo 帳號可用：`demo@vogueai.local` / `password`。
- [x] Demo seed 可建立固定衣櫃資料、embedding、wear logs、stylist history、outfit log。
- [x] Vite dev client 可回應：`http://127.0.0.1:5173/@vite/client`。
- [x] AI Service health 可回應：`http://127.0.0.1:8001/health`。
- [ ] 登入後檢查 Dashboard。
- [ ] 登入後檢查 Smart Closet Hub。
- [ ] 檢查 My Closet 衣物列表、詳情頁與使用者資料隔離。
- [ ] 檢查衣物詳情 Image Caption Contract / BLIP fallback metadata。
- [ ] 檢查 AI Search 結果與 Search Metadata / CLIP / Qdrant fallback metadata。
- [ ] 檢查 AI Stylist 推薦、history、Digital Twin profile、feedback、context、embedding signals。
- [ ] 檢查 Wear Tracking 可新增穿著紀錄並更新 wear count / last worn。
- [ ] 檢查 Outfit Log 可從 Stylist History 保存整套穿搭。
- [ ] 檢查 Try-on Pose Quality / Quality Checks / Fit Notes / Improvement Tips。
- [ ] 檢查 Workspace Runway Video storyboard / preview placeholder / provider metadata。
- [ ] 檢查 degraded / fallback 狀態文字不會讓使用者誤以為正式 AI provider 已上線。

## 後續完整清單

### GitHub 上傳前必做

- [x] Review group 1：AI Service Adapters And Contracts。
- [x] Review group 2：Laravel Closet, Stylist, Wear, Outfit Features。
- [x] Review group 3：Demo And GitHub Readiness Tooling。
- [x] Review group 4：Tests。
- [x] Review group 5：Project Docs。
- [ ] 使用者明確確認 Telescope duplicate migration cleanup，才能解除 `2026_04_22_161722_create_telescope_entries_table.php` deletion blocker。
- [x] 所有 review 完成後重跑完整驗證：Laravel Pest、AI pytest、demo-check、github-check、git status。
- [x] 最終人工瀏覽器 spot-check：Home、Login、Dashboard、Smart Closet Hub、My Closet、AI Search、AI Stylist、Wear Tracking、Outfit Log、Try-on、Runway Video、Digital Twin。
- [ ] 確認 git status 沒有 `.env`、大型模型檔、或不該上傳的本機 artifacts。
- [ ] 確認所有 changed/untracked entries 都是本次要上傳的範圍。
- [ ] 依 commit plan 分組 stage。
- [ ] 建立 commit。
- [ ] 取得使用者最後明確同意後，才 push / open PR。

### GitHub 後續產品升級

- [ ] 正式模型接入階段：將目前 L3 adapter contract 從 mock/degraded fallback 逐步切換到 real provider。
- [ ] 在合適環境安裝並驗證真實 ML dependencies：`torch`、`transformers`、`pillow`。
- [ ] 用真實圖片與文字跑 CLIP image/text embedding smoke test。
- [ ] 啟動 Qdrant daemon，建立/驗證 `vogueai_clothing_embeddings`，並跑 connection-enabled preflight。
- [ ] 僅在 512D CLIP vectors 確認後，才把 Qdrant 從 fallback 切成 active。
- [ ] 接入真實 BLIP caption generation。
- [ ] 接入真實 Gemini text generation provider。
- [ ] 接入真實 Magic Mirror / pose provider，取代目前 mock pose analysis。
- [ ] 繼續 P5：Trend / Chat Assistant、SmartTag / QuickSnap / Smart Storage、Community / Showcase / Blind Box / Travel Packer。

## 更新紀錄

### 2026-06-05

- 新增後續完整清單，將 GitHub 上傳前必做事項與 GitHub 後產品升級拆開追蹤。
- 完成 GitHub 上傳前第一組 review：AI Service Adapters And Contracts。
- 修正 BLIP health dependency 判斷：`blip` 現在需要 `torch`、`transformers`、`pillow` 都存在才顯示 `available`，避免缺 `pillow` 時 health 誤判。
- 修正 Qdrant readiness metadata：只有 `qdrant-client` 存在但尚未確認 daemon/collection 時，仍保持 `mock_sqlite_fallback` active；只有 connection 與 collection 都 ready 時才標示 active provider 為 `qdrant`。
- 新增 AI service regression tests，覆蓋 BLIP/pillow dependency gate、Qdrant client-only fallback、Qdrant connection-ready activation。
- 驗證 `ai_service\.venv\Scripts\python.exe -m py_compile ai_service\utils\dependencies.py ai_service\services\vector_store_service.py ai_service\tests\test_health.py ai_service\tests\test_vector_store_service.py`：通過。
- 驗證 `ai_service\.venv\Scripts\python.exe -m pytest ai_service\tests\test_health.py ai_service\tests\test_vector_store_service.py ai_service\tests\test_ai_routes.py`：19 passed。
- 驗證 `ai_service\.venv\Scripts\python.exe -m pytest ai_service\tests`：36 passed。
- GitHub 仍未 stage / commit / push；Telescope migration deletion 尚待使用者明確確認。
- 完成 GitHub 上傳前第二組 review：Laravel Closet, Stylist, Wear, Outfit Features。
- 確認 clothing、stylist history、wear log、outfit log、Digital Twin、Try-on、AI Search 相關入口都維持 current-user scoped query 或已由 feature tests 覆蓋跨使用者保護。
- 修正 Stylist / Workspace metadata panel 內可見的異常分隔字元：`繚` 改為 `·`，避免人工網頁驗收時顯示亂碼。
- 驗證 `php -l app\Http\Controllers\ClosetController.php`、`php -l app\Http\Controllers\WorkspaceController.php`、`php -l app\Models\WearLog.php`、`php -l app\Models\OutfitLog.php`、`php -l app\Services\StylistTextGenerationService.php`：通過。
- 驗證 `.\vendor\bin\pest.bat tests\Feature\SmartClosetTest.php tests\Feature\AiStylistTest.php tests\Feature\AiSearchTest.php tests\Feature\AiJobsL1Test.php`：38 passed / 262 assertions。
- 完成 GitHub 上傳前第三組 review：Demo And GitHub Readiness Tooling。
- 加強 demo data 安全性：`AiEmbedding::updateOrCreate()` 現在以 `user_id`、`clothing_id`、`embedding_type` 作為識別範圍；cleanup 測試新增非 demo 使用者與衣物，確認只刪除 `demo@vogueai.local` 範圍。
- 加強 `GithubReadinessService`：現在會解析 `git status --short` entries，將 `warning:` 行獨立列為 warning，不再算入 dirty worktree；保留 git status 前兩欄、使用 `git -C <repo>` 與 stdout/stderr pipes，避免 Windows cwd / trim 問題低估 tracked changes；可攔 staged Telescope deletion、staged/modified `.env`、`.gguf` 大型模型 artifact。
- 驗證 `php -l app\Services\DemoDataService.php`、`php -l app\Services\DemoReadinessService.php`、`php -l app\Services\GithubReadinessService.php`、`php -l routes\console.php`：通過。
- 驗證 `.\vendor\bin\pest.bat tests\Feature\DemoDataTest.php tests\Feature\DemoReadinessTest.php tests\Feature\GithubReadinessTest.php`：8 passed / 41 assertions。
- 驗證 `php artisan vogueai:demo-check`：0 failed / 1 warning；`php artisan vogueai:github-check`：2 blockers / 0 warnings，仍正確阻擋上傳。
- Post-review final verification：`.\vendor\bin\pest.bat` 73 passed / 370 assertions；`ai_service\.venv\Scripts\python.exe -m pytest ai_service\tests` 36 passed；`php artisan vogueai:demo-check` 0 failed / 1 warning；`php artisan vogueai:github-check` 2 blockers / 0 warnings；`git status --short --untracked-files=all` 73 changed/untracked entries、無 raw warning。
- `.gitignore` 新增 `.pytest_cache/`，避免本機 pytest cache 權限狀態污染 GitHub 前 raw `git status` 輸出。
- Final browser spot-check：以本機 Chrome headless 登入 `demo@vogueai.local`，驗證 Home、Login、Dashboard、Smart Closet Hub、My Closet、Clothing Detail、AI Search、AI Stylist、Try-on、Runway Video、Digital Twin 全部 HTTP 200；無 local 4xx response、無 console error。
- Browser spot-check 發現 demo seed 圖片 URL 404，已新增 `public/images/demo/white-shirt.jpg`、`public/images/demo/navy-blazer.jpg`、`public/images/demo/red-dress.jpg`；重跑後 404 清除。
- 最新 GitHub gate：`php artisan vogueai:github-check` 仍為 2 blockers / 0 warnings；dirty worktree 更新為 73 changed/untracked entries，新增 3 張 demo JPG 屬預期修正。
- 完成 GitHub 上傳前第四組 review：Tests。
- 確認 `tests/` 與 `ai_service/tests/` 未出現 `only`、`skip`、`todo`、`dd`、`dump` 或可見亂碼標記。
- 驗證高風險 Laravel test files 語法：`SmartClosetTest`、`AiStylistTest`、`AiJobsL1Test`、`GithubReadinessTest`、`DemoDataTest` 全部通過 `php -l`。
- 驗證 AI service test files：全部通過 `py_compile`。
- 驗證 `.\vendor\bin\pest.bat`：73 passed / 370 assertions。
- 驗證 `ai_service\.venv\Scripts\python.exe -m pytest ai_service\tests`：36 passed。
- 完成 GitHub 上傳前第五組 review：Project Docs。
- 更新核心進度檔頂部狀態與最新驗收結果，避免仍顯示舊的 62/311、72/361、33 passed 數字作為目前狀態。
- 確認 `docs/gemini-stylist-adapter-plan.md` 仍正確標示 Gemini 為 future provider，現況維持 mock/degraded contract。
- 確認 `docs/github-upload-review-checklist.md` 與核心進度檔都明確標示：GitHub upload 仍 blocked、Telescope deletion 需明確確認、L3 後續會正式接模型。

### 2026-06-03

- 完成 Qdrant vector store readiness / health preflight metadata。
- 完成 CLIP image/text embedding adapter methods。
- 完成 BLIP image caption adapter method。
- 完成 AI adapter orchestration：`/ai/attributes`、`/ai/embed/image`、`/ai/embed/text` 在 `mock_mode=false` 時會先嘗試正式 BLIP/CLIP adapter，失敗時保留現有 mock-first fallback。
- 確認 Laravel `AiService` 已使用 `AI_MOCK_MODE` 統一帶入 `mock_mode`，並新增測試鎖住 `AI_MOCK_MODE=false` 時 attributes、image embedding、text embedding、similar search 都會送出 `mock_mode=false`。
- 新增 `App\Services\DemoReadinessService` 與 `php artisan vogueai:demo-check`，上 GitHub 或本機 demo 前可檢查 Laravel env、AI service folder、venv、requirements、核心進度文件、AI service config、AI mock mode 與 Telescope migration 風險。
- `start-all.ps1` 啟動 Laravel/Vite/FastAPI 前會先跑 `php artisan vogueai:demo-check`；必要項 fail 會停止啟動，warning 不阻擋。可用 `.\start-all.ps1 -SkipDemoCheck` 手動略過。
- 新增 `App\Services\DemoDataService` 與 `php artisan vogueai:demo-data seed|cleanup`，可建立固定 demo user、3 件 demo clothes、image embeddings、wear logs、stylist history 與 outfit log；cleanup 只清除 `demo@vogueai.local` 相關資料。
- `php artisan vogueai:demo-check` 新增 Demo data command 檢查，確保 seed / cleanup 指令在展示前可用。
- 新增 `App\Services\GithubReadinessService` 與 `php artisan vogueai:github-check`，上 GitHub 前檢查 dirty worktree、Telescope migration deletion、`.env` 與大型模型檔；此指令只檢查，不會 stage / commit / push。
- `php artisan vogueai:demo-check` 新增 GitHub check command 檢查，確保上傳前安全閘門存在。
- 新增 `ai_service/services/adapter_orchestration_service.py`，集中管理正式 adapter attempt、mock fallback、`real_adapter_attempt` metadata 與 image embedding Qdrant upsert attempt。
- `clip_embed_text()` / `clip_embed_image()` 新增 model repository resolver：外部 `clip-vit-base-patch32` 會解析到 `openai/clip-vit-base-patch32`，避免 transformers 正式啟用時使用錯誤模型 ID。
- 新增 `ai_service/services/blip_caption_service.py`：提供 `blip_generate_caption()`，正式環境可透過 `torch`、`transformers`、`pillow` 與 `Salesforce/blip-image-captioning-base` 產生圖片描述。
- BLIP adapter 新增 dependency gate：缺 `torch`、`transformers` 或 `pillow` 時，安全回傳 `BLIP_DEPENDENCIES_NOT_INSTALLED` 與 degraded fallback。
- `image_caption` contract 新增 `model_repository=Salesforce/blip-image-captioning-base` 與 `adapter_methods.image_caption=blip_generate_caption`。
- 新增 `ai_service/services/clip_embedding_service.py`：提供 `clip_embed_image()` / `clip_embed_text()`，正式環境可透過 `torch`、`transformers`、`pillow` 與 `openai/clip-vit-base-patch32` 產生 512D normalized vectors。
- CLIP adapter 新增 dependency gate：缺 `torch`、`transformers` 或 image path 需要的 `pillow` 時，安全回傳 `CLIP_DEPENDENCIES_NOT_INSTALLED` 與 degraded fallback。
- CLIP adapter 新增 dimension validation：輸出必須符合 Qdrant target 512D，否則回 `CLIP_VECTOR_DIMENSION_MISMATCH`，避免錯誤向量流入 Qdrant。
- 新增 `ai_service/requirements-ml.txt`，將 `torch`、`transformers`、`pillow` 與輕量 demo dependencies 分離。
- `embedding_provider` contract 新增 `model_repository=openai/clip-vit-base-patch32` 與 adapter methods metadata。
- AI Service dependency health 的 `clip` 判斷同步納入 `PIL`，並新增 `pillow` dependency status。
- 新增 `ai_service/services/vector_store_service.py`，集中管理 Qdrant adapter skeleton、shared vector store contract 與 preflight response。
- 新增 internal endpoint `GET /ai/vector-store/preflight`，需要 `X-Internal-AI-Token`，回傳 vector store readiness、fallback_safe、collection_required 與 next steps。
- `GET /ai/vector-store/preflight?check_connection=true` 現在支援可選真連線檢查；預設不連線，沒有 `qdrant-client` 時會安全回傳 `connection_check=skipped` 與 `QDRANT_CLIENT_NOT_INSTALLED`。
- Qdrant contract 新增 collection schema：target vector size `512`、active mock vector size `8`、distance `Cosine`、named vectors `clip_image` / `clip_text`、payload indexes `user_id` / `clothing_id` / `category` / `color` / `season` / `occasion` / `style_tags`。
- Qdrant contract 新增 `collection_plan` dry-run：operation `create_or_verify_collection`、vectors_config、payload_index_plan、required_before_activation、activation_guardrails，明確禁止把 mock 8D embeddings upsert 到 512D target collection。
- Qdrant contract 新增 `upsert_plan` / `search_plan` dry-run：upsert 使用 `clip_image` named vector、point id `clothing_{clothing_id}`、payload template；search 使用 `clip_text` query vector，並要求 `user_id` filter。
- Qdrant contract 新增 `dimension_validation`：目前 mock vector `actual_vector_size=8`、target `expected_vector_size=512`，回傳 `VECTOR_DIMENSION_MISMATCH`、`qdrant_ready=false`、`fallback_required=true`。
- Qdrant contract 新增 `adapter_methods`，標示正式 adapter 入口與安全規則：collection creation 不會由 health check 觸發，必須走 internal endpoint。
- 新增 internal endpoint `POST /ai/vector-store/collection/ensure`，需要 `X-Internal-AI-Token`；`create_missing=true` 時才會在 `qdrant-client` 與 daemon 可用時呼叫 `recreate_collection` 並建立 payload indexes。
- 新增 `qdrant_ensure_collection()` service path：目前缺 `qdrant-client` 時安全回傳 `QDRANT_CLIENT_NOT_INSTALLED`、`fallback_safe=true`。
- 新增 `qdrant_upsert_clothing_embedding()`：只接受 512D `clip_image` vector，寫入 point id `clothing_{clothing_id}`、named vector 與 payload；8D mock vector 會先回 `VECTOR_DIMENSION_MISMATCH`，不碰 Qdrant。
- 新增 `qdrant_search_similar_clothing()`：只接受 512D `clip_text` query vector，固定套用 `user_id` filter，可加 category 等 filters，回傳 Qdrant matches；不符合維度或缺 client 時安全 degraded。
- `ai_service/.env.example` 新增 `EMBEDDING_*`、`VECTOR_STORE_*`、`IMAGE_CAPTION_*` 設定；根目錄 `.env.example` 新增 Laravel AI client 設定；`ai_service/requirements.txt` 宣告 `qdrant-client`。
- `/ai/embed/image` 的 `vector_db` 與 `/ai/search/similar` 的 `vector_store` 會同步回傳 `target_vector_size`、`active_vector_size`、`distance` 與 `collection_schema`。
- `/health`、`/ai/embed/image`、`/ai/search/similar` 目前共用同一份 Qdrant contract，避免 health 與 API 回傳不一致。
- AI Service `/health` 新增 `vector_store` block，顯示 target provider `qdrant`、active provider `mock_sqlite_fallback`、adapter `qdrant-vector-store-v1`、target URL、target collection、client package、fallback_active、api_key_configured、connection_check 與 next_steps。
- 新增 `VECTOR_STORE_URL` 與 `VECTOR_STORE_API_KEY` 設定，預設 URL 為 `http://127.0.0.1:6333`。
- `/ai/embed/image` 與 `/ai/search/similar` 的 vector store contract 也同步帶出 `target_url` 與 `connection_check`，讓 health 與 API 回傳一致。
- 更新 `ai_service/tests/test_health.py` 與 `ai_service/tests/test_ai_routes.py`，驗證 Qdrant readiness metadata。
- 更新 `ai_service/tests/test_security_validation.py`，驗證 vector store preflight 需要 internal token。
- `ai_service/README.md` 已補上 `/health.vector_store` 範例與 embedding vector_db readiness 欄位。
- 驗證 `ai_service/.venv/Scripts/python.exe -m py_compile ai_service/services/vector_store_service.py ai_service/services/mock_ai_service.py ai_service/tests/test_ai_routes.py`：通過。
- 驗證 `ai_service/.venv/Scripts/python.exe -m pytest ai_service/tests/test_ai_routes.py`：7 passed。
- 驗證 fake Qdrant client create path：`qdrant_ensure_collection(create_missing=True)` 會建立 named vectors 與 7 個 payload indexes。
- 驗證 fake Qdrant client upsert/search path：512D vector 會呼叫 `upsert` / `search`，8D mock vector 會在 client 呼叫前被 dimension guard 擋下。
- 驗證 fake CLIP adapter path：text/image 皆可產生 512D normalized vector，缺依賴時回 `CLIP_DEPENDENCIES_NOT_INSTALLED`，錯誤維度回 `CLIP_VECTOR_DIMENSION_MISMATCH`。
- 驗證 fake BLIP adapter path：可產生 caption，缺依賴時回 `BLIP_DEPENDENCIES_NOT_INSTALLED`，空 caption 回 `BLIP_EMPTY_CAPTION`。
- 驗證 adapter orchestration path：`mock_mode=false` 時 CLIP/BLIP ready 會回 `ready` / `hybrid`，缺依賴時保留 mock fallback 並輸出 `real_adapter_attempt`。
- 驗證 Laravel `AiService` mock mode switch：`AI_MOCK_MODE=false` 會送出 `mock_mode=false`，且單次 request 可覆蓋回 `mock_mode=true`。
- 驗證 `php artisan vogueai:demo-check`：0 failed / 1 warning，warning 為既有 Telescope migration deletion 需上 GitHub 前確認。
- 驗證 `php artisan vogueai:demo-check`：新增 Demo data command 檢查後仍為 0 failed / 1 warning。
- 驗證 `php artisan vogueai:github-check`：目前正確回 2 blockers，包含 dirty worktree 與既有 Telescope migration deletion；此狀態下不應上 GitHub。
- 完成 Telescope migration deletion 檢查：`2026_04_22_161722_create_telescope_entries_table.php` 與保留中的 `2026_04_22_161640_create_telescope_entries_table.php` 行內容一致，判斷為重複 Telescope migration 清理候選；仍需使用者明確確認後才可解除 GitHub blocker。
- 人工網頁驗收後重跑完整回歸：`.\vendor\bin\pest.bat` 通過，72 passed / 361 assertions。
- 人工網頁驗收後重跑完整回歸：`ai_service\.venv\Scripts\python.exe -m pytest ai_service\tests` 通過，33 passed。
- 新增 `docs/github-upload-review-checklist.md`，整理 GitHub 上傳前 gate 狀態、Telescope blocker、73 個 changed/untracked entries 的 review groups、建議 commit 順序與最終 pre-push 指令。
- 驗證 `vendor/bin/pest.bat tests/Feature/GithubReadinessTest.php tests/Feature/DemoReadinessTest.php`：5 passed / 15 assertions。
- 驗證 `vendor/bin/pest.bat tests/Feature/DemoDataTest.php tests/Feature/DemoReadinessTest.php`：4 passed / 24 assertions。
- 驗證 `vendor/bin/pest.bat tests/Feature/DemoReadinessTest.php`：2 passed / 7 assertions。
- 驗證 `vendor/bin/pest.bat tests/Feature/AiServiceMockModeTest.php`：2 passed / 6 assertions。
- 驗證 `ai_service/.venv/Scripts/python.exe -m pytest ai_service/tests/test_adapter_orchestration_service.py ai_service/tests/test_ai_routes.py`：14 passed。
- 驗證 `ai_service/.venv/Scripts/python.exe -m py_compile ai_service/services/adapter_orchestration_service.py ai_service/services/clip_embedding_service.py ai_service/routes/ai_routes.py ai_service/tests/test_adapter_orchestration_service.py ai_service/tests/test_ai_routes.py`：通過。
- 驗證 `ai_service/.venv/Scripts/python.exe -m pytest ai_service/tests/test_adapter_orchestration_service.py ai_service/tests/test_ai_routes.py ai_service/tests/test_clip_embedding_service.py ai_service/tests/test_blip_caption_service.py`：21 passed。
- 驗證 `ai_service/.venv/Scripts/python.exe -m py_compile ai_service/services/blip_caption_service.py ai_service/services/mock_ai_service.py ai_service/config.py ai_service/tests/test_blip_caption_service.py ai_service/tests/test_ai_routes.py`：通過。
- 驗證 `ai_service/.venv/Scripts/python.exe -m pytest ai_service/tests/test_blip_caption_service.py ai_service/tests/test_ai_routes.py`：11 passed。
- 驗證 `ai_service/.venv/Scripts/python.exe -m py_compile ai_service/services/clip_embedding_service.py ai_service/services/mock_ai_service.py ai_service/utils/dependencies.py ai_service/tests/test_clip_embedding_service.py ai_service/tests/test_ai_routes.py`：通過。
- 驗證 `ai_service/.venv/Scripts/python.exe -m pytest ai_service/tests/test_clip_embedding_service.py ai_service/tests/test_ai_routes.py ai_service/tests/test_health.py`：13 passed。
- 驗證 `ai_service/.venv/Scripts/python.exe -m py_compile ai_service/services/vector_store_service.py ai_service/services/mock_ai_service.py ai_service/routes/ai_routes.py ai_service/tests/test_ai_routes.py ai_service/tests/test_security_validation.py ai_service/tests/test_vector_store_service.py`：通過。
- 驗證 `ai_service/.venv/Scripts/python.exe -m pytest ai_service/tests/test_ai_routes.py ai_service/tests/test_security_validation.py ai_service/tests/test_vector_store_service.py`：16 passed。
- 驗證 `ai_service/.venv/Scripts/python.exe -m py_compile ai_service/services/vector_store_service.py ai_service/tests/test_vector_store_service.py ai_service/tests/test_ai_routes.py`：通過。
- 驗證 `ai_service/.venv/Scripts/python.exe -m pytest ai_service/tests/test_vector_store_service.py ai_service/tests/test_ai_routes.py`：12 passed。
- 驗證 `ai_service/.env.example`：已包含 `EMBEDDING_*`、`VECTOR_STORE_*`、`IMAGE_CAPTION_*`。
- 驗證 `.env.example`：已包含 Laravel AI client 設定。
- 驗證 `ai_service/requirements.txt`：已宣告 `qdrant-client`。
- 驗證 `ai_service/.venv/Scripts/python.exe -m pytest ai_service/tests`：33 passed。
- 驗證 `vendor/bin/pest.bat`：72 passed / 361 assertions。
- GitHub 尚未推送；正式 Qdrant daemon、collection 建立與 qdrant-client 安裝仍未接入，`check_connection=true` 與 `POST /ai/vector-store/collection/ensure?create_missing=true` 目前會在缺 client 時安全 degraded / skip。

- 完成 CLIP image/text embedding adapter readiness contract。
- AI Service 新增 `EMBEDDING_PROVIDER` 與 `EMBEDDING_MODEL` 設定，預設 target provider 為 `clip`、target model 為 `clip-vit-base-patch32`。
- `/ai/embed/image` 與 `/ai/embed/text` 回傳新增 `embedding_provider`，包含 target provider、active fallback provider、adapter、target model、active model、fallback_active、degraded reason。
- `/ai/search/similar` 回傳新增 query embedding contract metadata：`query_model` 保留 active mock model，`target_query_model` 指向 CLIP target model。
- AI Search metadata UI 新增 Embedding Target、Embedding Adapter、Embedding Fallback 顯示。
- 更新 `ai_service/tests/test_ai_routes.py` 與 `tests/Feature/AiSearchTest.php`，驗證 CLIP adapter readiness contract。
- `ai_service/models/clip/README.md` 與 `ai_service/README.md` 已同步補上 CLIP contract 說明。
- 驗證 `ai_service/.venv/Scripts/python.exe -m py_compile ai_service/config.py ai_service/services/mock_ai_service.py ai_service/tests/test_ai_routes.py`：通過。
- 驗證 `php -l app/Http/Controllers/ClosetController.php`、`php -l resources/views/closet/search.blade.php`、`php -l tests/Feature/AiSearchTest.php`：通過。
- 驗證 `ai_service/.venv/Scripts/python.exe -m pytest ai_service/tests/test_ai_routes.py`：5 passed。
- 驗證 `vendor/bin/pest.bat tests/Feature/AiSearchTest.php`：6 passed / 31 assertions。
- 驗證 `vendor/bin/pest.bat`：63 passed / 323 assertions。
- 驗證 `ai_service/.venv/Scripts/python.exe -m pytest ai_service/tests`：11 passed。
- 實際 HTTP UI check：啟動本機 Laravel server 與臨時 fake AI service、登入臨時測試使用者、開啟 AI Search，確認 Search Metadata / Embedding Target `clip` / Embedding Adapter `clip-embedding-v1` / Embedding Fallback active 皆出現；完成後已清理臨時資料與 `.codex-*` 檔案。
- GitHub 尚未推送；真實 CLIP 模型與大型依賴仍未接入，需另開環境安裝與模型下載檢查。

### 2026-06-02 02:45

- 完成 BLIP 圖片描述 placeholder / fallback contract。
- AI Service 新增 `IMAGE_CAPTION_PROVIDER` 與 `IMAGE_CAPTION_MODEL` 設定，預設 target provider 為 `blip`。
- `/ai/attributes` 回傳新增 `image_caption`，包含 caption、target provider、active fallback provider、adapter、target model、fallback_active、grounding metadata。
- Clothing Detail 頁面新增 Image Caption Contract 顯示，讓使用者能確認目前是 mock fallback，並保留未來接入真 BLIP adapter 的欄位。
- 更新 `ai_service/tests/test_ai_routes.py` 與 `tests/Feature/SmartClosetTest.php`，驗證 BLIP caption contract 與 UI 顯示。
- 驗證 `ai_service/.venv/Scripts/python.exe -m py_compile ai_service/config.py ai_service/services/mock_ai_service.py ai_service/tests/test_ai_routes.py`：通過。
- 驗證 `php -l app/Http/Controllers/ClosetController.php`、`php -l resources/views/closet/show.blade.php`、`php -l tests/Feature/SmartClosetTest.php`：通過。
- 驗證 `ai_service/.venv/Scripts/python.exe -m pytest ai_service/tests/test_ai_routes.py`：5 passed。
- 驗證 `vendor/bin/pest.bat tests/Feature/SmartClosetTest.php`：8 passed / 27 assertions。
- 驗證 `vendor/bin/pest.bat`：63 passed / 320 assertions。
- 驗證 `ai_service/.venv/Scripts/python.exe -m pytest ai_service/tests`：11 passed。
- 實際 HTTP UI check：啟動本機 Laravel server、登入臨時測試使用者、開啟 Clothing Detail，確認 Image Caption Contract / mock caption / BLIP adapter 欄位皆出現；完成後已清理臨時資料與 `.codex-blip-*` 檔案。
- GitHub 尚未推送；下一步仍需確認 worktree 變更範圍，特別是既有 Telescope migration deletion。

### 2026-06-02 02:00

- 完成 Qdrant vector store placeholder / fallback contract。
- AI Service 新增 `VECTOR_STORE_PROVIDER` 與 `VECTOR_STORE_COLLECTION` 設定，預設 target provider 為 `qdrant`。
- `/ai/embed/image` 的 `vector_db` 現在會標示 target provider、target collection、active fallback provider、fallback_active 與 degraded reason。
- `/ai/search/similar` 現在會回傳 `vector_store` contract，包含 `qdrant-vector-store-v1` adapter、target provider `qdrant`、active provider `mock_sqlite_fallback`。
- AI Search result metadata 現在會顯示 Target、Adapter 與 Fallback active。
- 更新 `ai_service/tests/test_ai_routes.py` 與 `tests/Feature/AiSearchTest.php`，驗證 Qdrant placeholder contract。
- 驗證 `ai_service/.venv/Scripts/python.exe -m py_compile ai_service/config.py ai_service/services/mock_ai_service.py ai_service/tests/test_ai_routes.py`：通過。
- 驗證 `vendor/bin/pest.bat tests/Feature/AiSearchTest.php`：6 passed / 28 assertions。
- 驗證 `vendor/bin/pest.bat`：62 passed / 314 assertions。
- 驗證 `ai_service/.venv/Scripts/python.exe -m pytest ai_service/tests`：11 passed。
- 本次 Headless Chrome Qdrant UI check 尚未執行：環境 escalation 因用量限制被系統拒絕；已清理臨時 demo 資料與 `.codex-qdrant-*` / `.codex-fake-ai-qdrant.cjs` 檔案。

### 2026-06-02 01:00

- 完成 P4 `outfit_logs`。
- 新增 migration：`2026_06_02_002000_create_outfit_logs_table.php`。
- 新增 `app/Models/OutfitLog.php`，並在 `StylistHistory` 加上 `outfitLogs()` 關聯。
- 新增 `POST /closet/stylist/{history}/outfit-log` 與 `ClosetController::storeStylistOutfitLog()`，只允許保存自己的 Stylist History。
- AI Stylist History 卡片新增 Outfit Log 區塊，可保存整套推薦穿搭、logged_at、name、notes。
- `outfit_logs` 保存 selected_items、item_ids、item_count、context_json、stylist_history_id 與 metadata，讓後續個人化學習可讀整套搭配。
- 驗證 `php artisan migrate --force`：`outfit_logs` migration applied。
- 新增 `test_user_can_save_stylist_history_as_outfit_log` 與 `test_user_cannot_save_another_users_stylist_history_as_outfit_log`。
- 驗證 `vendor/bin/pest.bat tests/Feature/AiStylistTest.php`：13 passed / 125 assertions。
- 驗證 `vendor/bin/pest.bat`：62 passed / 311 assertions。
- 驗證 `ai_service/.venv/Scripts/python.exe -m pytest ai_service/tests`：11 passed。
- 完成 outfit_logs Headless Chrome browser UI check。

### 2026-06-02

- 完成 P4 `wear_logs`。
- 新增 migration：`2026_06_02_001000_create_wear_logs_table.php`。
- 新增 `app/Models/WearLog.php`，並在 `Clothing` 加上 `wearLogs()` 關聯。
- 新增 `POST /closet/{id}/wear` 與 `ClosetController::storeWearLog()`，只允許記錄自己的衣物。
- 衣物詳情頁新增 Wear Tracking 區塊，可手動記錄 worn_at、context、notes，並顯示 wear count、last worn、recent wear logs。
- 記錄穿著後同步更新 `clothes.wear_count` 與 `clothes.last_worn_at`。
- 驗證 `php artisan migrate --force`：`wear_logs` migration applied。
- 新增 `test_user_can_record_wear_log_for_owned_clothing` 與 `test_user_cannot_record_wear_log_for_another_users_clothing`。
- 驗證 `vendor/bin/pest.bat tests/Feature/SmartClosetTest.php`：7 passed / 21 assertions。
- 驗證 `vendor/bin/pest.bat`：60 passed / 292 assertions。
- 驗證 `ai_service/.venv/Scripts/python.exe -m pytest ai_service/tests`：11 passed。
- 完成 wear_logs Headless Chrome browser UI check。

### 2026-06-01

- 完成 AI Search L3 similarity metadata 顯示。
- `mapSimilarSearchResults()` 現在會為向量結果加入 rank、provider、model、match type、confidence label、score percent、source。
- `keywordSearch()` 現在也會加入 fallback metadata，標示 `sql_like`、`keyword_fallback`、`keyword`、`fallback`。
- AI Search result card 新增 Search Metadata 區塊，顯示 Rank、Provider、Model、Match、Confidence、Source。
- 新增 `test_ai_search_displays_similarity_metadata_for_vector_results`。
- 驗證 `vendor/bin/pest.bat tests/Feature/AiSearchTest.php`：6 passed / 25 assertions。
- 驗證 `vendor/bin/pest.bat`：58 passed / 278 assertions。
- 驗證 `ai_service/.venv/Scripts/python.exe -m pytest ai_service/tests`：11 passed。
- 完成 AI Search metadata Headless Chrome browser UI check。

### 2026-05-31

- 完成 Gemini Stylist Text Adapter 規劃與 mock/degraded contract。
- 新增 `app/Services/StylistTextGenerationService.php`，把 AI Stylist 文案生成從 controller 抽出成可替換服務。
- 新增 `config/ai.php` 設定：`AI_TEXT_GENERATION_PROVIDER`、`GEMINI_TEXT_MODEL`。
- AI Stylist 產生推薦時會保存 `recommendation_json.text_generation`，包含 provider、adapter、status、mode、model、fallback、prompt contract。
- AI Stylist History UI 新增 Gemini Text Adapter 顯示區塊。
- 新增 `docs/gemini-stylist-adapter-plan.md`。
- 新增 `test_stylist_records_gemini_text_generation_adapter_plan`。
- 驗證 `vendor/bin/pest.bat tests/Feature/AiStylistTest.php`：11 passed / 106 assertions。
- 驗證 `vendor/bin/pest.bat`：57 passed / 267 assertions。
- 驗證 `ai_service/.venv/Scripts/python.exe -m pytest ai_service/tests`：11 passed。
- 完成 Gemini Text Adapter Headless Chrome browser UI check。

### 2026-05-30

- 完成 AI Stylist ai_embeddings ranking。
- AI Stylist 現在會讀取使用者衣物的 `ai_embeddings` image vector，以 local cosine similarity 產生 embedding 排序訊號。
- `recommendation_json.embedding_signals` 會保存 `local_cosine`、top matches、score、embedding id 與 provider。
- Stylist History UI 會顯示 Embedding Signals，單品卡會顯示 Embedding Score。
- 新增 `test_stylist_uses_ai_embeddings_to_rank_candidates`。
- 驗證 `vendor/bin/pest.bat tests/Feature/AiStylistTest.php`：10 passed / 89 assertions。
- 驗證 `vendor/bin/pest.bat`：56 passed / 250 assertions。
- 驗證 `ai_service/.venv/Scripts/python.exe -m pytest ai_service/tests`：11 passed。
- 完成 AI Stylist ai_embeddings Headless Chrome browser UI check。
- 完成 AI Stylist expanded context inputs。
- 新增 migration：`2026_05_30_002000_add_context_json_to_stylist_history_table.php`。
- `stylist_history` 新增 `context_json`，保存 occasion、weather、season_context、formality_level、mood_context、style_preference、avoid_notes。
- AI Stylist 表單新增季節、正式程度、心情 / 氛圍、避免事項。
- 推薦邏輯現在會使用 season context 參與 season matching，並用 avoid notes 排除不想要的衣物。
- 新增 `test_stylist_saves_expanded_context_inputs`。
- 驗證 `vendor/bin/pest.bat tests/Feature/AiStylistTest.php`：10 passed / 89 assertions。
- 驗證 `vendor/bin/pest.bat`：56 passed / 250 assertions。
- 完成 AI Stylist expanded context Headless Chrome browser UI check。
- 完成 AI Stylist feedback flow。
- 新增 migration：`2026_05_30_001000_add_feedback_fields_to_stylist_history_table.php`。
- `stylist_history` 新增 `feedback_status`、`feedback_reason`、`feedback_json`、`feedback_submitted_at`。
- 新增 `POST /closet/stylist/{history}/feedback`。
- AI Stylist 頁面可保存喜歡 / 不適合與拒絕原因。
- 新增 `test_user_can_save_stylist_feedback` 與 `test_user_cannot_update_another_users_stylist_feedback`。
- 驗證 `vendor/bin/pest.bat tests/Feature/AiStylistTest.php`：8 passed / 58 assertions。
- 驗證 `vendor/bin/pest.bat`：54 passed / 219 assertions。
- 完成 AI Stylist feedback Headless Chrome browser UI check，確認 rejected 與拒絕原因會顯示。
- 完成 AI Stylist + Digital Twin L2 closet profile 串接。
- AI Stylist 現在會讀取最近一筆 `digital_twin_style_analysis`，用 dominant category/color/style 補強候選衣物排序。
- `stylist_history.recommendation_json` 現在保存 `digital_twin_profile`。
- AI Stylist 頁面新增 Digital Twin Profile 與 Digital Twin Profile Used 顯示。
- 新增 `test_stylist_uses_latest_digital_twin_closet_profile`。
- 驗證 `php -l app/Http/Controllers/ClosetController.php`、`php -l resources/views/closet/stylist.blade.php`、`php -l tests/Feature/AiStylistTest.php`。
- 驗證 `vendor/bin/pest.bat tests/Feature/AiStylistTest.php`：6 passed / 37 assertions。
- 驗證 `vendor/bin/pest.bat`：52 passed / 198 assertions。
- 驗證 `ai_service/.venv/Scripts/python.exe -m pytest ai_service/tests`：11 passed。
- 完成 AI Stylist + Digital Twin Headless Chrome browser UI check。
- 完成 Runway Video L2 mock preview job。
- `runway_video` job 的 `result_json` 新增 `video_prompt`、`preview`、`provider`、`scene_timeline`、`export`、`next_steps`。
- Workspace Runway Video 頁面新增 Generation Status、Target Provider、Video Preview、Video Prompt 顯示。
- 更新 Runway Feature Test，驗證 L2 job contract 與頁面文字。
- 驗證 `php -l app/Http/Controllers/WorkspaceController.php`、`php -l resources/views/workspace/show.blade.php`、`php -l tests/Feature/AiJobsL1Test.php`。
- 驗證 `vendor/bin/pest.bat tests/Feature/AiJobsL1Test.php`：11 passed / 79 assertions。
- 驗證 `vendor/bin/pest.bat`：51 passed / 183 assertions。
- 驗證 `ai_service/.venv/Scripts/python.exe -m pytest ai_service/tests`：11 passed。
- 完成 Runway Video L2 Headless Chrome browser UI check。
- Browser UI check 驗證欄位：Generation Status、Video Preview、Video Prompt、Target Provider、`degraded_placeholder`、`9:16`、`veo`。
- Browser check demo user、clothing、ai_job 與臨時腳本已清理；Laravel/Vite 背景服務已停止。

### 2026-05-24

- 建立核心進度 tracker。
- 吸收 `VogueAI_progress_and_final_roadmap.md` 的目前進度與最終目標。
- 完成 P0 穩定化。
- 修正 Auth/Profile/Example 測試結構。
- 建立 AI service `.venv` 與 pytest suite。
- 修正 Digital Twin route auth 保護。
- 修正 `start-all.ps1`。
- 完成 Laravel + Vite + FastAPI smoke test。
- 修正 AI Search fallback，避免 mock similar search 固定 clothing id 導致目前使用者查不到剛上傳衣物。
- 新增 AI Search regression test。
- 完成 headless Chrome demo flow。
- 清理臨時與備份檔。
- 完成 Try-on L2 pose quality mock response。
- 更新 Try-on UI 顯示 Pose Quality、Quality Checks、Fit Notes、Improvement Tips。
- 新增 Try-on L2 Feature Test。
- 更新 AI pose route assertions。
- 完成 Try-on L2 browser UI check。
## 2026-06-17 FASHN Try-on Max Adapter 接入

- 已完成 Try-on 第一個外部 Provider：`AI_TRYON_PROVIDER=fashn` 時會改走 FASHN Direct API。
- 2026-06-17 使用者決定：FASHN 成本過高，暫停採用；目前不啟用、不填 API key、不做 live 生成測試。既有 adapter 僅保留為可選備援，不作為正式 provider。
- 已新增 FASHN 建立任務 mapping：
  - `POST {AI_TRYON_API_BASE_URL}/run`
  - `model_name = AI_TRYON_MODEL`
  - `inputs.model_image = person_image_url`
  - `inputs.product_image = clothing_image_url`
  - `inputs.prompt / resolution / generation_mode / output_format / return_base64` 由 `.env` 控制。
- 已保留 generic Try-on provider fallback：非 `fashn` provider 仍走原本 `{AI_TRYON_API_BASE_URL}/tryon/generate`。
- 已新增 Try-on status polling：
  - `php artisan vogueai:tryon-status {providerTaskId}`
  - FASHN `completed` 會正規化為 `status=success` 並輸出 `output_url`。
  - FASHN `failed` 會保留 `error_code` / `error_message`，方便 UI 後續做失敗重試與 fallback。
- 已更新 `vogueai:external-provider-smoke --only=tryon`：FASHN 建立任務回 `processing` 會視為 live provider 已接單。
- 已更新 `.env.example`，但目前本機 `.env` 尚未填入 Try-on live provider 設定；目前 smoke 結果仍是安全 warning，沒有外洩 API key。
- 驗證：
  - `php -l app\Services\ExternalModelProviderService.php` 通過。
  - `php -l routes\console.php` 通過。
  - `php -l tests\Feature\ExternalModelProviderTest.php` 通過。
  - `php -l tests\Feature\ExternalProviderSmokeTest.php` 通過。
  - `.\vendor\bin\pest.bat tests\Feature\ExternalModelProviderTest.php tests\Feature\ExternalProviderSmokeTest.php` 通過：14 tests / 71 assertions。
  - `php artisan vogueai:external-provider-smoke --only=tryon` 通過但 1 warning：本機 `.env` 尚未啟用 live FASHN 設定。

## 2026-06-17 Hugging Face IDM-VTON Try-on Demo Provider 接入

- 已改採免費 demo / research prototype 路線：`Laravel -> Python FastAPI AI Service -> gradio_client -> Hugging Face Space yisol/IDM-VTON`。
- 已新增 Python AI Service endpoint：
  - `POST /tryon/generate`
  - `GET /tryon/status/{task_id}`
- 已新增 `HuggingFaceIDMVTONProvider` 對應服務：
  - 建立本地 async task id：`local_hf_tryon_xxx`。
  - 背景呼叫 `gradio_client.Client("yisol/IDM-VTON")` 與 `api_name="/tryon"`。
  - 成功結果複製到 `ai_service/static/tryon/`，並回傳 `http://127.0.0.1:8001/static/tryon/{task_id}.png`。
  - Space 不可用、排隊過久、回傳空結果或 client 缺失時，不讓服務崩潰，改回 degraded/failed contract。
- 已掛載 FastAPI static files：`/static/tryon/...` 可供 Laravel/瀏覽器讀取。
- 已新增 `gradio_client` 至 `ai_service/requirements.txt`，並已安裝到本機 venv。
- 已更新 `.env.example` 與 `ai_service/.env.example`：
  - `AI_TRYON_PROVIDER=huggingface_idm_vton`
  - `AI_TRYON_MODEL=idm-vton`
  - `AI_TRYON_API_BASE_URL=http://127.0.0.1:8001`
  - `AI_TRYON_CREATE_ENDPOINT=/tryon/generate`
  - `AI_TRYON_STATUS_ENDPOINT=/tryon/status/{id}`
  - `AI_TRYON_MODE=async`
- 已更新本機 `.env` 與 `ai_service/.env`，目前 Hugging Face token 留空，使用公開 Space；AI Service `AI_MOCK_MODE=false`。
- 已修正 Laravel generic Try-on adapter：
  - `huggingface_idm_vton` 不要求 `AI_TRYON_API_KEY`。
  - Laravel 改用 `X-Internal-AI-Token` 呼叫本機 Python AI Service。
  - 保留 Python 回傳的 `processing/success/degraded/failed` 狀態，不再把所有 HTTP 200 硬包成 `ready`。
- 文件已補充限制：Hugging Face IDM-VTON 為學生專題 demo / research prototype，不是商用 SLA；公開 Space 可能休眠、排隊、限流或失敗，必要時可 Duplicate Space，但 GPU 可能產生 Hugging Face 費用。
- 驗證：
  - `ai_service\.venv\Scripts\python.exe -m py_compile ...` 通過。
  - `ai_service\.venv\Scripts\python.exe -m pytest ai_service\tests` 通過：43 tests。
  - `.\vendor\bin\pest.bat tests\Feature\ExternalModelProviderTest.php tests\Feature\ExternalProviderSmokeTest.php` 通過：15 tests / 77 assertions。
  - `AI_EXTERNAL_PROVIDER_CALLS=false .\vendor\bin\pest.bat` 通過：127 tests / 694 assertions；完整 Laravel regression 測試時刻意關閉外部呼叫，避免測試期間誤打 Hugging Face。
  - AI Service `/health`：`mock_mode=false`、`gradio_client=available`、`qdrant=available`。
  - `php artisan vogueai:external-provider-smoke --only=tryon` 通過：0 failed / 0 warnings。

## 2026-06-17 Hugging Face Token 與 Windows 背景啟動修正

- 使用者已將 Hugging Face Access Token 放入 `ai_service/.env` 的 `TRYON_API_TOKEN`；文件與進度只記錄已配置，不顯示 token 內容。
- 重新啟動 AI Service 後確認：
  - `/health` 回 200。
  - `mock_mode=false`。
  - `gradio_client=available`。
- 修正 IDM-VTON provider 圖片輸入：
  - Laravel 傳入 `http://localhost/...` 或 `http://127.0.0.1/...` 圖片 URL 時，AI Service 會轉成本機檔案路徑，再由 `gradio_client.handle_file()` 上傳給 Hugging Face Space。
  - 避免 Hugging Face Space 嘗試讀取使用者本機 localhost URL 而失敗。
- 新增 `ai_service/run_server.py`：
  - 用於 Windows hidden/background 啟動 AI Service。
  - 修正 `pythonw.exe` 沒有 stdout 時 Uvicorn logging 呼叫 `sys.stdout.isatty()` 造成服務立刻退出的問題。
  - 啟動失敗會寫入 `storage/logs/ai-service-idm-vton-python.log`。
- 驗證：
  - `ai_service\.venv\Scripts\python.exe -m py_compile ai_service\services\huggingface_idm_vton_service.py ai_service\tests\test_ai_routes.py` 通過。
  - `ai_service\.venv\Scripts\python.exe -m pytest ai_service\tests\test_ai_routes.py` 通過：17 tests。
  - `pythonw.exe run_server.py` 背景啟動成功，`/health` 回 200。
  - `php artisan vogueai:external-provider-smoke --only=tryon` 通過：0 failed / 0 warnings。

## 2026-06-18 Try-on 手動查詢結果流程

- 已完成 Try-on 真實 provider 任務回填流程：
  - 新增 `POST /ai-jobs/{job}/tryon-status`。
  - Try-on 頁面任務卡新增「真實試穿任務」區塊。
  - 顯示 provider task id、目前 provider status。
  - 新增「重新查詢結果」按鈕；按一次才查一次，不做自動輪詢，避免免費 Hugging Face Space 被不必要地重複呼叫。
  - 查詢成功後會把 `output_url` 寫回 `ai_jobs.result_json.tryon_output_url`，並在頁面直接顯示試穿圖片。
- 已完成 Laravel provider status polling：
  - `ExternalModelProviderService::pollTryOn()` 支援 `huggingface_idm_vton` 的 `GET /tryon/status/{id}`。
  - Hugging Face / Python AI Service 呼叫使用 `X-Internal-AI-Token`，不要求 Laravel 端保存 Hugging Face token。
- 已調整 Python AI Service IDM-VTON flow：
  - `POST /tryon/generate` 只建立本地 task，不立即呼叫 Hugging Face。
  - `GET /tryon/status/{task_id}` 才真正執行一次 Hugging Face IDM-VTON 產圖。
  - 這符合學生免費 demo 成本控管：建立任務與 smoke 不消耗 Space 執行，人工確認時才執行。
- 已修正 Windows 背景服務穩定性：
  - `ai_service/run_server.py` 改用 `http="h11"`，避開 Uvicorn/httptools 在 Windows 背景程序處理 POST body 時可能無 traceback 退出的問題。
  - 背景啟動 AI Service 必須用非 sandbox process，否則工具呼叫結束後背景程序可能被隔離清掉。
- 已更新本機 timeout：
  - `.env` / `.env.example`：`AI_TIMEOUT_SECONDS=180`，讓人工查詢 Hugging Face 產圖時有足夠等待時間。
- 驗證：
  - `php -l app\Http\Controllers\AiJobController.php` 通過。
  - `php -l app\Services\ExternalModelProviderService.php` 通過。
  - `php -l app\Http\Controllers\ClosetController.php` 通過。
  - `php -l tests\Feature\AiJobStatusTest.php` 通過。
  - `.\vendor\bin\pest.bat tests\Feature\AiJobStatusTest.php tests\Feature\AiJobsL1Test.php tests\Feature\ExternalModelProviderTest.php tests\Feature\ExternalProviderSmokeTest.php` 通過：31 tests / 205 assertions。
  - `AI_EXTERNAL_PROVIDER_CALLS=false .\vendor\bin\pest.bat` 通過：128 tests / 702 assertions。
  - AI Service 非 sandbox 背景啟動後 `/health` 回 200。
  - `php artisan vogueai:external-provider-smoke --only=tryon` 通過：0 failed / 0 warnings。
  - smoke 後 AI Service 仍存活：`127.0.0.1:8001` listening PID 24548。

## 2026-06-19 Try-on 人工驗收畫面整理

- 人工驗收確認最新 `POSE-0024` 已成功建立 Hugging Face provider task，目前真實試穿狀態為 `processing`；姿態分析的 `mock/degraded` 與真實試穿任務狀態已分開呈現。
- 修正試穿頁左右雙欄被歷史紀錄撐高、左側產生大片空白的排版問題，改為建立表單與任務紀錄上下排列。
- 任務主狀態優先顯示真實試穿 provider 狀態，避免把姿態檢查的 `degraded` 誤認為真實試穿失敗。
- 舊任務只保留摘要；完整姿態品質、檢查項目與建議集中顯示於最新任務，縮短頁面長度。
- 已將姿態檢查名稱、說明與照片改善建議的既有英文內容轉為中文顯示。
- 驗證：`php vendor/bin/phpunit --do-not-cache-result tests/Feature/AiJobsL1Test.php tests/Feature/AiJobStatusTest.php` 通過：16 tests / 128 assertions。
- 人工查詢發現本機 `gradio_client 2.5.0` 的 `Client` 驗證參數為 `token`，原先使用的 `hf_token` 會回傳 `HF_SPACE_ERROR`；已修正 provider 並新增參數相容性測試。
- 驗證：`ai_service\.venv\Scripts\python.exe -m pytest tests\test_ai_routes.py -q` 通過：18 tests；AI Service 重新啟動後 `/health` 回 200。
- 後續人工查詢發現衣物圖片可能以 `/storage/clothes/...` 相對網址傳入；已補上 Laravel `public/storage` 與 `storage/app/public` 本機路徑解析，避免 `handle_file` 誤判為不存在的絕對路徑。
- 驗證：AI Service Try-on route suite 通過：19 tests；重啟後 `/health` 回 200。
- 人工查詢發現 IDM-VTON 可能以 tuple / 巢狀結構回傳多個檔案；已讓輸出解析支援 tuple、list、dict 與具 `path/name` 屬性的檔案物件。
- Try-on 改為建立任務後由 AI Service 背景執行；網頁每 4 秒自動查詢本機狀態，成功後自動重新整理並顯示成果圖，不再要求使用者手動按查詢。
- 手動更新按鈕仍保留為備援；失敗卡改為顯示 provider 真實錯誤內容，不再一律誤導為 AI Service 未啟動。
- 驗證：AI Service route suite 20 tests 通過；Laravel Try-on suite 16 tests / 128 assertions 通過；重啟後 `/health` 回 200。
