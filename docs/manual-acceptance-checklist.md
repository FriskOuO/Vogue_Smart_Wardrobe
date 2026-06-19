# VogueAI Smart Wardrobe - Manual Acceptance Checklist

## 2026-06-10 Gemini API 外部 Smoke 通過

- [x] 已確認 Gemini key 被放在 `.env.example`，不是 `.env`。
- [x] 已把 `GEMINI_API_KEY` 安全搬到本機 `.env`。
- [x] 已清空 `.env.example` 的 `GEMINI_API_KEY`，避免 GitHub 外洩。
- [x] 已執行 `php artisan config:clear`。
- [x] `php artisan vogueai:gemini-smoke` 已通過：`ready / real_adapter`。
- [x] Gemini smoke 成功 endpoint：`/v1beta/models/gemini-2.5-flash:generateContent`。
- [x] AI Service + Qdrant 啟動後，`php artisan vogueai:provider-check` -> 0 failed / 0 warnings。
- [x] AI Service + Qdrant 啟動後，`php artisan vogueai:real-mode-check` -> 0 failed / 0 warnings。
- [ ] 人工打開 `/closet/stylist`，選「真實模型」產生建議。
- [ ] 確認最新 Stylist History 顯示 `ready / real_adapter`。
- [ ] 確認建議內容合理，且沒有捏造不在衣櫥中的衣物。

## 2026-06-10 Gemini API Smoke Gate

- [x] 新增 `php artisan vogueai:gemini-smoke`。
- [x] Smoke 指令不印出 API key。
- [x] Smoke 指令不寫入資料庫。
- [x] 缺少 `GEMINI_API_KEY` 時會安全失敗並顯示 `GEMINI_API_KEY_MISSING`。
- [x] fake Gemini structured response 測試可通過 `ready / real_adapter`。
- [x] 測試：`.\vendor\bin\pest.bat tests\Feature\GeminiSmokeTest.php tests\Feature\AiStylistTest.php tests\Feature\ProviderReadinessTest.php` -> 20 passed / 170 assertions。
- [x] `GeminiSmokeTest` 已納入 upload-scope 的 `demo-readiness-provider-gates` 分組，`needs-manual-review` 維持 0。
- [x] 在本機 `.env` 填入有效 `GEMINI_API_KEY`。
- [x] 執行 `php artisan config:clear`。
- [x] 執行 `php artisan vogueai:gemini-smoke`，確認 `Summary: Gemini API smoke passed.`。
- [ ] 到 `/closet/stylist` 選「真實模型」產生建議，確認 history 顯示 `ready / real_adapter`。

## 2026-06-09 Upload Scope Review Gate

- [x] 新增 `php artisan vogueai:upload-scope`。
- [x] Gate 只做 review，不 stage、不 commit、不 push、不開 PR。
- [x] 目前 worktree 統計為 115 changed/untracked entries。
- [x] 最新分組：AI service 26、Laravel backend 17、Views and UI 34、Tests 20、Docs 6、Config and scripts 4、Database migrations 5、Assets 3、Other 0。
- [x] 最新建議 commit groups：ai-service-adapter-contracts 26、laravel-closet-stylist-workflows 19、demo-readiness-provider-gates 21、localized-ui-and-manual-polish 42、project-docs-and-roadmap 6、telescope-duplicate-migration-cleanup 1、needs-manual-review 0。
- [x] 已確認 `.env` 沒有出現在 git status。
- [x] 已確認大型模型 artifact 沒有出現在 git status。
- [x] 測試：`.\vendor\bin\pest.bat tests\Feature\UploadScopeReviewTest.php tests\Feature\GithubReadinessTest.php` -> 7 passed / 29 assertions。
- [ ] 使用者確認 115 changed/untracked entries 都是本次上傳範圍。
- [ ] 使用者確認 Telescope duplicate migration cleanup：保留 `2026_04_22_161640_create_telescope_entries_table.php`，刪除 `2026_04_22_161722_create_telescope_entries_table.php`。

## 2026-06-09 GitHub 前人工整理 / 分組檢查

- [x] 更新 `docs/github-upload-review-checklist.md` 到 2026-06-09 最新狀態。
- [x] 目前 worktree 統計為 112 changed/untracked entries。
- [x] 已重新分組：AI service 26、Laravel backend 16、Views and UI 34、Tests 18、Docs 6、Config and scripts 4、Database migrations 5、Assets 3、Other 0。
- [x] 已確認 `php artisan vogueai:github-check` 仍正確阻擋上傳：2 blockers / 0 warnings。
- [x] 已確認 `.env` 沒有出現在 git status。
- [x] 已確認大型模型 artifact 沒有出現在 git status。
- [x] 已把 Provider readiness / Real-mode acceptance 新增檔案補入 dry-run staging manifest。
- [ ] 使用者確認 112 changed/untracked entries 都是本次上傳範圍。
- [ ] 使用者確認 Telescope duplicate migration cleanup：保留 `2026_04_22_161640_create_telescope_entries_table.php`，刪除 `2026_04_22_161722_create_telescope_entries_table.php`。
- [ ] 使用者重新明確批准 GitHub 上傳後，才可進入 stage / commit / push。

## 2026-06-09 完整 Regression Gate

- [x] Laravel 全量測試：`.\vendor\bin\pest.bat` -> 83 passed / 427 assertions。
- [x] AI Service 全量測試：`ai_service\.venv\Scripts\python.exe -m pytest ai_service\tests` -> 39 passed / 1 warning。
- [x] 前端 production build：`npm.cmd run build` -> passed。
- [x] Demo readiness：`php artisan vogueai:demo-check` -> 0 failed / 1 warning。
- [x] Real mode check（服務未啟動）：`php artisan vogueai:real-mode-check` -> 0 failed / 1 warning。
- [x] Provider check（服務未啟動）：`php artisan vogueai:provider-check` -> 0 failed / 3 warnings。
- [x] 啟動 AI Service + Qdrant 並 ensure collection 後，`provider-check` -> 0 failed / 1 warning。
- [x] 啟動 AI Service + Qdrant 並 ensure collection 後，`real-mode-check` -> 0 failed / 0 warnings。
- [x] GitHub readiness：`php artisan vogueai:github-check` -> 2 blockers / 0 warnings，仍正確阻擋上傳。
- [ ] GitHub 前人工確認 112 changed/untracked entries 的分組與 stage 計畫。
- [ ] GitHub 前人工確認 Telescope migration deletion 是否保留。

## 2026-06-09 AI Search / AI Stylist 真實模式驗收 Gate

- [x] 新增 `php artisan vogueai:real-mode-check`。
- [x] Gate 可檢查 AI Search / AI Stylist routes。
- [x] Gate 可檢查 AI Search `provider_mode=real` 與 `mock_mode=false` wiring。
- [x] Gate 可檢查 AI Search 真實模式 feature test 覆蓋。
- [x] Gate 可檢查 AI Stylist `provider_mode=real` 與 Gemini fallback wiring。
- [x] Gate 可檢查缺少 `GEMINI_API_KEY` 時的 fallback test。
- [x] Gate 可檢查 fake Gemini ready response test。
- [x] 未啟動 AI Service / Qdrant 時，結果為 0 failed / 1 warning。
- [x] 啟動 AI Service + Qdrant 並 ensure collection 後，結果為 0 failed / 0 warnings。
- [ ] 若已填 `GEMINI_API_KEY`，執行外部 Gemini smoke 並確認 AI Stylist history 為 ready / real_adapter。

指令：
```powershell
php artisan vogueai:real-mode-check
```

## 2026-06-09 真實 Provider 啟動前總驗收 Gate

- [x] 新增 `php artisan vogueai:provider-check`。
- [x] Gate 可檢查 CLIP / BLIP cache、Qdrant launcher、AI Search 真實模式入口、AI Stylist 真實模式入口。
- [x] Gate 可檢查 Qdrant runtime / Hugging Face cache 沒有進入 Git status。
- [x] 目前結果：0 failed / 3 warnings。
- [x] 啟動 AI Service 後重跑 provider-check，確認 AI service health warning 消失。
- [x] 啟動 Qdrant 後重跑 provider-check，確認 Qdrant preflight warning 消失。
- [x] 啟動 AI Service + Qdrant 並 ensure collection 後，`provider-check` 收斂到 0 failed / 1 warning。
- [ ] 填入有效 `GEMINI_API_KEY` 後重跑 provider-check，確認 Gemini config warning 消失。

指令：

```powershell
php artisan vogueai:provider-check
```

## 2026-06-09 AI Stylist Gemini 真實模型驗收入口

- [x] AI Stylist 表單新增「生成模式」。
- [x] 預設為 `展示模式`，不改變 rule-based demo 行為。
- [x] 選擇 `真實模型` 時，Laravel 會對 Stylist text generation 傳 `mock_mode=false`。
- [x] 無 `GEMINI_API_KEY` 時，系統會安全 degraded 並顯示 `GEMINI_API_KEY_MISSING`。
- [x] 測試已涵蓋 fake Gemini success：Gemini JSON 可寫入 `ready / real_adapter` Stylist History。
- [ ] 人工打開 `/closet/stylist`，確認表單可看到「生成模式」。
- [ ] 選 `展示模式` 產生建議，確認歷史紀錄仍是 rule_based / degraded。
- [ ] 選 `真實模型` 產生建議，確認 Gemini 區塊顯示 real_adapter_attempt 與 fallback/error 資訊。
- [ ] 若已填 `GEMINI_API_KEY`，確認新紀錄可變成 ready / real_adapter。

## 2026-06-09 AI Search 真實模型畫面驗收入口

- [x] AI Search 表單新增「搜尋模式」。
- [x] 預設為 `展示模式`，不改變 demo-safe 行為。
- [x] 選擇 `真實模型` 時，Laravel 本次搜尋會對 AI service 傳 `mock_mode=false`。
- [x] 啟動 Qdrant 與 AI Service 後，登入 demo 帳號並打開 `/closet/ai-search`。
- [x] 搜尋模式選 `真實模型`，輸入 `white shirt` 或 `白色上衣`，送出搜尋。
- [x] 確認結果卡可顯示衣物、分數與 metadata。
- [x] 確認 metadata 可看到 `qdrant`、`clip-vit-base-patch32`、`qdrant_vector_similarity`。
- [ ] 切回 `展示模式` 後搜尋仍可安全 fallback。

## 2026-06-09 後續大進度 3：Qdrant 真實搜尋人工驗收

- [x] AI Service `/ai/search/similar` 在 `mock_mode=false` 時改走 Qdrant。
- [x] Qdrant 搜尋使用 `query_points(...)`，符合 `qdrant-client==1.18.0`。
- [x] CLIP text query 搜尋衣物已儲存的 `clip_image` vector。
- [x] Laravel AI Search 接受 `ready` 狀態，不再把真實 provider 成功誤判成失敗。
- [x] Laravel service smoke：`embedImage`、`embedText`、`searchSimilar` 全部回 `ready`，搜尋結果回到隔離衣物 `999005`。
- [x] 自動驗證：AI service tests 39 passed；Laravel full tests 75 passed；demo-check 0 failed / 1 warning。
- [ ] 人工打開 AI Search 頁，輸入衣物描述，確認搜尋頁能正常顯示結果。
- [ ] 人工確認搜尋結果 metadata 顯示 `qdrant`、`clip-vit-base-patch32`、`qdrant_vector_similarity`。
- [ ] 人工確認 Qdrant 未啟動時，AI Search 仍可 fallback，不會讓畫面壞掉。

啟動真實 provider 人工驗收時，先開 Qdrant：

```powershell
.\start-qdrant.ps1 -NoTelemetry
```

再開 AI Service：

```powershell
cd ai_service
.\.venv\Scripts\python.exe -m uvicorn main:app --host 127.0.0.1 --port 8001
```

Laravel demo-safe 預設仍可維持 `AI_MOCK_MODE=true`；需要真實搜尋時用 per-request `mock_mode=false` 或後續專門切換設定。

## 2026-06-08 後續大進度 2：CLIP / BLIP HTTP 與 Laravel 局部真實流程

- [x] 新增 AI service 圖片路徑解析器。
- [x] CLIP / BLIP 支援 Laravel public disk 與 public demo image 路徑。
- [x] AI Service HTTP `/ai/embed/text` 真實 adapter：ready，512D。
- [x] AI Service HTTP `/ai/embed/image` 真實 adapter：ready，512D。
- [x] AI Service HTTP `/ai/attributes` 真實 BLIP caption：adapter attempt ready。
- [x] Laravel `AiService::embedText(... mock_mode=false)`：ready，512D。
- [x] Laravel `AiService::embedImage(... mock_mode=false)`：ready，512D。
- [x] Laravel `AiService::analyzeAttributes(... mock_mode=false)`：BLIP caption ready，屬性 hybrid fallback。
- [x] 修正 Qdrant point id 為整數 `clothing_id`。
- [x] HTTP image embedding + `store_to_vector_db=true` 可成功寫入 Qdrant。
- [x] Laravel image embedding 預設 `store_to_vector_db=true` 可成功寫入 Qdrant。
- [x] 全域 `AI_MOCK_MODE=true` 保持不變。
- [x] AI Service tests：38 passed / 2 warnings。

下一個大進度確認：

- [ ] `/ai/search/similar` 改接真實 `qdrant_search_similar_clothing()`。
- [ ] Laravel AI Search 使用真實 CLIP text embedding + Qdrant search。
- [ ] 保留 SQL keyword fallback，避免 Qdrant 無結果時空白。

## 2026-06-08 後續大進度 1：真實 Provider 環境啟動

- [x] 安裝 `torch==2.12.0+cpu`。
- [x] 安裝 `transformers==5.10.2`。
- [x] 設定專案內 Hugging Face cache：`ai_service/models/huggingface`。
- [x] 將模型 cache 加入 `.gitignore`。
- [x] 準備 CLIP model cache：`openai/clip-vit-base-patch32`。
- [x] 準備 BLIP model cache：`Salesforce/blip-image-captioning-base`。
- [x] CLIP text embedding smoke：ready，512D。
- [x] CLIP image embedding smoke：ready，512D。
- [x] BLIP caption smoke：ready。
- [x] 下載 Qdrant Windows binary：`tools/qdrant/runtime/qdrant.exe`，版本 `1.18.2`。
- [x] 將 `tools/qdrant/` 加入 `.gitignore`。
- [x] 新增 `start-qdrant.ps1`。
- [x] 建立 / 驗證 Qdrant collection：`vogueai_clothing_embeddings`。
- [x] 驗證 named vectors：`clip_image` / `clip_text` 均為 512D Cosine。
- [x] 驗證 payload indexes：`user_id`、`clothing_id`、`category`、`color`、`season`、`occasion`、`style_tags`。
- [x] AI Service tests：36 passed / 2 warnings。
- [x] Laravel full tests：74 passed / 373 assertions。
- [x] Demo readiness：0 failed / 1 warning。

後續人工啟動方式：

```powershell
.\start-qdrant.ps1 -NoTelemetry
```

下一個大進度確認：

- [ ] 走 AI Service HTTP endpoint 測 CLIP / BLIP 真實 adapter。
- [ ] 走 Laravel 呼叫鏈測 `AI_MOCK_MODE=false` 的局部流程。
- [ ] 把真實 CLIP embedding 寫入 Qdrant，測 AI Search 真實向量搜尋。

## 2026-06-07 大步驟 5：真實模型 / Provider 本機前置

- [x] 跳過 GitHub 上傳，直接推進 Provider 接入前置。
- [x] 安裝 `qdrant-client==1.18.0`。
- [x] 安裝 `pillow==12.2.0`。
- [x] 確認 `torch`、`transformers` 尚未安裝，因此 CLIP / BLIP 仍維持 mock/degraded。
- [x] AI Service tests：36 passed / 1 warning。
- [x] Demo readiness：0 failed / 1 warning。
- [x] AI Service `/health` HTTP 200。
- [x] `/health` 顯示 `pillow=available`、`qdrant=available`、`clip=mock`、`blip=mock`。
- [x] Qdrant preflight 使用 `X-Internal-AI-Token` 驗證通過 endpoint 授權。
- [x] Qdrant preflight 在 daemon 未啟動時安全 degraded：`QDRANT_CONNECTION_FAILED`。
- [x] Qdrant collection ensure 在 daemon 未啟動時安全 degraded：`QDRANT_COLLECTION_ENSURE_FAILED`。
- [x] 已更新 `docs/model-integration-readiness.md` 與 `docs/vogueai-core-progress.md`。

待下一個大步驟確認：

- [ ] 啟動 Qdrant daemon。
- [ ] 建立 / 驗證 512D named-vector collection。
- [ ] 安裝 `torch`、`transformers`。
- [ ] 準備 CLIP / BLIP model cache。
- [ ] 執行 CLIP image/text embedding 真實 smoke test。
- [ ] 執行 BLIP caption 真實 smoke test。
- [ ] 有 Gemini API key 後再接 text generation provider。

## 2026-06-07 大步驟 4：模型串接前置準備

- [x] 新增 `docs/model-integration-readiness.md`。
- [x] CLIP / BLIP / Qdrant / Gemini / pose provider 前置 gate 已整理。
- [x] 環境變數、optional ML dependencies、啟用順序、smoke commands 已整理。
- [x] fallback rules 已整理，demo-safe `AI_MOCK_MODE=true` 維持不變。
- [x] AI Service health 確認仍在 mock/degraded 安全狀態。
- [x] AI Service tests：36 passed。
- [x] Demo readiness：0 failed / 1 warning。

## 2026-06-07 大步驟 3：Demo 穩定化

- [x] 第 1 大步驟「全站人工畫面確認」暫定完成。
- [x] 第 2 大步驟「核心功能使用流程確認」暫定完成。
- [x] Demo readiness：0 failed / 1 warning；warning 為 GitHub 前 Telescope confirmation。
- [x] Demo seed 成功：`demo@vogueai.local` / `password`。
- [x] Demo seed 可重複執行，資料量穩定：3 clothes、4 embeddings、3 wear logs、2 stylist histories、1 outfit log。
- [x] Demo 圖片資產存在：white shirt、navy blazer、red dress。
- [x] Demo focused tests：4 tests / 26 assertions 通過。
- [x] Demo 帳號核心頁：Dashboard、我的衣櫥、AI 搜尋、AI 穿搭顧問、Digital Twin 全部 HTTP 200。

## 2026-06-06 大步驟驗收：全站功能檢查

- [x] 登入後主要頁面 smoke check：11 個主要頁面全部 HTTP 200。
- [x] 本機服務健康：Laravel、登入頁、Vite、AI Service health 全部 HTTP 200。
- [x] 核心功能測試：44 tests / 286 assertions 通過。
- [x] Laravel 全量測試：74 tests / 373 assertions 通過。
- [x] AI Service 全量測試：36 tests 通過。
- [x] Demo readiness：0 failed / 1 warning，warning 為 GitHub 前 Telescope migration confirmation。
- [x] GitHub readiness：仍有 2 blockers，依使用者要求本階段不處理上傳。
- [x] GitHub 動作：本階段未 stage、未 commit、未 push。

## 2026-06-06 手動回報修正：衣櫥縮圖大小

- [x] 衣櫥列表衣物圖片大小不一致：已改為固定 280px 縮圖舞台。
- [x] 衣物卡片高度與按鈕位置：已固定，列表視覺更整齊。
- [x] 詳情頁：仍保留完整圖片顯示。
- [x] 驗證：closet index Blade 語法通過，前端 build 通過，Vite CSS 已載入新規則。

## 2026-06-06 手動回報修正：帳號總覽主題

- [x] 帳號總覽進入後固定夜間模式：已補上共同主題控制器。
- [x] 主題切換按鈕：已加入防重複綁定，避免按一下被切換兩次。
- [x] 驗證：Profile feature tests 通過，前端 build 通過。

## 2026-06-06 手動回報修正

- [x] 我的衣櫥衣物圖片扁平：已改為完整置中顯示，並限制桌機卡片寬度。
- [x] 同步檢查衣物詳情、AI 搜尋結果、AI 造型建議圖片樣式。
- [x] 驗證：closet Blade 語法通過，前端 build 通過。

## 2026-06-06 人工畫面確認清單（GitHub 先跳過）

目前本機服務狀態：

- [x] Laravel 首頁：`http://127.0.0.1:8000/` -> HTTP 200
- [x] Laravel 登入頁：`http://127.0.0.1:8000/login` -> HTTP 200
- [x] Vite dev client：`http://127.0.0.1:5173/@vite/client` -> HTTP 200
- [x] AI Service health：`http://127.0.0.1:8001/health` -> HTTP 200
- [x] GitHub 上傳本階段先跳過，不 stage、不 commit、不 push。
- [x] 2026-06-06 內容 QA 掃描：首頁、登入、註冊、Dashboard、Smart Closet Hub、我的衣櫥、新增衣物、AI 搜尋、AI 穿搭顧問、試穿、Runway、Digital Twin、帳號總覽、編輯帳號皆 HTTP 200。
- [x] 2026-06-06 內容 QA 掃描：主要英文介紹關鍵字未命中。
- [x] 2026-06-06 內容 QA 掃描：主要頁面圖片連結未發現 404。
- [x] 2026-06-06 截圖包已建立：`storage/logs/manual-acceptance-screenshots/`。
- [x] 2026-06-06 截圖總覽頁已建立：`storage/logs/manual-acceptance-screenshots/index.html`。
- [x] 2026-06-06 修正登入/註冊底層 layout 亂碼字串。
- [x] 2026-06-06 本地人工確認已關閉 debugbar：`DEBUGBAR_ENABLED=false`。
- [x] 2026-06-06 重新截圖確認首頁、登入、註冊皆 HTTP 200，分頁標題為 VogueAI，且未偵測 debugbar。

登入資訊：

- Email：`demo@vogueai.local`
- Password：`password`

快速連結：

- 截圖總覽：`storage/logs/manual-acceptance-screenshots/index.html`
- 首頁：`http://127.0.0.1:8000/`
- 登入：`http://127.0.0.1:8000/login`
- 註冊：`http://127.0.0.1:8000/register`
- Dashboard：`http://127.0.0.1:8000/dashboard`
- Smart Closet Hub：`http://127.0.0.1:8000/smart-closet`
- 我的衣櫥：`http://127.0.0.1:8000/closet`
- 新增衣物：`http://127.0.0.1:8000/closet/create`
- AI 搜尋：`http://127.0.0.1:8000/closet/ai-search`
- AI 穿搭顧問：`http://127.0.0.1:8000/closet/stylist`
- 試穿：`http://127.0.0.1:8000/closet/try-on`
- Runway Video：`http://127.0.0.1:8000/workspace/runway-video`
- Digital Twin：`http://127.0.0.1:8000/workspace/digital-twin`
- 帳號總覽：`http://127.0.0.1:8000/account`
- 編輯帳號：`http://127.0.0.1:8000/profile`

人工確認問題紀錄：

| 頁面 | 問題 | 狀態 |
| --- | --- | --- |
| 待填 | 待填 | 待修 / 已修 / 不處理 |

請依序人工確認：

- [ ] 首頁：第一眼舒服、中文自然、沒有多餘英文介紹。
- [ ] 登入 / 註冊：表單、按鈕、錯誤訊息都可讀。
- [ ] Dashboard：主要入口清楚，功能卡片不擁擠。
- [ ] Smart Closet Hub：功能入口清楚，文案舒服。
- [ ] 我的衣櫥：衣物列表、圖片、按鈕、空狀態都正常。
- [ ] 新增衣物：上傳表單欄位清楚，送出按鈕可理解。
- [ ] 衣物詳情：AI 操作、影像描述、穿著紀錄區塊可讀。
- [ ] AI 搜尋：搜尋欄、結果卡、相似度資訊可讀；技術 metadata 不影響主要閱讀。
- [ ] AI 穿搭顧問：推薦流程、歷史紀錄、回饋、保存穿搭可理解。
- [ ] 試穿：頁面可開，姿態品質資訊與提示可讀。
- [ ] Runway Video：任務建立區塊、故事板、預覽資訊可讀。
- [ ] Digital Twin：個人風格卡與衣櫥分析資訊可讀。
- [ ] 帳號總覽 / 編輯帳號：帳號資料、密碼、刪除區皆中文化。

人工確認完成條件：

- [ ] 沒有明顯英文介紹殘留。
- [ ] 沒有頁面打不開。
- [ ] 沒有按鈕文字或欄位名稱看不懂。
- [ ] 沒有排版明顯破掉、文字重疊或太擠。
- [ ] 使用者確認可進入 GitHub 前整理階段。

## 1. 文件目的

本文件用於 VogueAI Smart Wardrobe 展示前的手動驗收。

目前專案已完成：

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

本 checklist 目標是確保：

```text
1. Demo 前功能可正常操作
2. Laravel 與 Python AI Service 可正常串接
3. AI Service 不可用時可降級
4. ai_jobs 任務紀錄可正常建立
5. 頁面不會因 mock / degraded 狀態而壞掉
```

---

## 2. Demo 啟動前檢查

### 2.1 環境檢查

```text
[ ] 已進入專案根目錄
[ ] .env 存在
[ ] database/database.sqlite 存在
[ ] storage link 已建立
[ ] composer install 已完成
[ ] npm install 已完成
[ ] ai_service/.venv 已建立
[ ] ai_service/requirements.txt 已安裝
```

---

### 2.2 三個服務啟動

Laravel：

```powershell
php artisan serve
```

檢查：

```text
[ ] Laravel 可開啟 http://127.0.0.1:8000
```

Vite：

```powershell
npm run dev
```

檢查：

```text
[ ] Vite 正常啟動
[ ] 頁面 CSS / Tailwind 正常
```

Python AI Service：

```powershell
cd ai_service
uvicorn main:app --host 127.0.0.1 --port 8001 --reload
```

檢查：

```text
[ ] AI Service 可開啟 http://127.0.0.1:8001/health
[ ] /health 回傳 status=ok
[ ] /health 顯示 dependencies
```

---

## 3. 帳號與權限驗收

### 3.1 登入功能

```text
[ ] 可進入登入頁
[ ] Admin 帳號可登入
[ ] User 帳號可登入
[ ] 登入後可進入 Dashboard
```

測試帳號：

```text
Admin
Email: admin.dev@vogueai.local
Password: Admin@123456

User
Email: demo.user@vogueai.local
Password: User@123456
```

---

### 3.2 Admin 權限

```text
[ ] Admin 可進入 Admin Users
[ ] Admin 可查看使用者列表
[ ] Admin 可搜尋使用者
[ ] User 不可進入 Admin Users
[ ] Admin 不可刪除自己
```

---

## 4. Smart Closet 驗收

### 4.1 Closet Hub

網址：

```text
/ smart-closet
```

或：

```text
http://127.0.0.1:8000/smart-closet
```

檢查：

```text
[ ] Smart Closet Hub 可正常開啟
[ ] 顯示衣物總數
[ ] 顯示待分析數量
[ ] 顯示 Mock / Degraded 數量
[ ] 顯示本週新增
```

---

### 4.2 新增衣物

網址：

```text
/closet/create
```

操作：

```text
1. 上傳衣物圖片
2. 輸入衣物名稱
3. 輸入備註
4. 送出表單
```

檢查：

```text
[ ] 圖片可成功上傳
[ ] clothes 建立成功
[ ] 頁面 redirect 到 closet.show
[ ] 顯示成功訊息
[ ] 圖片可正常顯示
```

---

### 4.3 AI Attributes

新增衣物後檢查：

```text
[ ] Laravel 呼叫 /ai/attributes
[ ] clothes.ai_status = degraded 或 success
[ ] category 有資料
[ ] color 有資料
[ ] season 有資料
[ ] occasion 有資料
[ ] usage 有資料
[ ] style_tags 有資料
[ ] closet.show 顯示 AI 分析結果
```

---

### 4.4 Image Embedding

新增衣物後用 tinker 檢查：

```powershell
php artisan tinker
```

```php
\App\Models\AiEmbedding::latest()->first();
```

檢查：

```text
[ ] ai_embeddings 有新增或更新資料
[ ] embedding_type = image
[ ] status = degraded 或 success
[ ] mode = mock 或 model
[ ] vector_dimension 有資料
[ ] vector_provider 有資料
```

---

### 4.5 Closet Index / Show

網址：

```text
/closet
```

檢查：

```text
[ ] closet.index 可正常開啟
[ ] 可看到資料庫中的衣物
[ ] 可搜尋衣物名稱
[ ] 可搜尋顏色
[ ] 點擊衣物可進入 closet.show
[ ] closet.show 可顯示圖片
[ ] closet.show 可顯示 AI 分析資料
```

---

### 4.6 重新分析 / 重新產生 embedding

在衣物詳細頁操作：

```text
[ ] 點擊「重新分析 AI Attributes」
[ ] 頁面顯示成功或失敗訊息
[ ] clothes.ai_status 有更新
[ ] 點擊「重新產生 Image Embedding」
[ ] 頁面顯示成功或失敗訊息
[ ] ai_embeddings 有更新
[ ] 同一件衣物不應重複新增多筆 image embedding
```

---

## 5. AI Search 驗收

網址：

```text
/closet/ai-search
```

---

### 5.1 正常模式

確認 Python AI Service 有開。

操作：

```text
1. 輸入「白色上衣」
2. 按下 Text Search
```

檢查：

```text
[ ] 頁面不報錯
[ ] 呼叫 /ai/embed/text
[ ] 呼叫 /ai/search/similar
[ ] Search mode 顯示 mock 或 ai_search
[ ] 有搜尋結果時顯示衣物卡片
[ ] 卡片顯示 similarity score
[ ] 可點擊查看衣物
```

---

### 5.2 Fallback 模式

關閉 Python AI Service：

```text
Ctrl + C
```

重新搜尋：

```text
白色
```

檢查：

```text
[ ] 頁面不報錯
[ ] 顯示 keyword_fallback
[ ] 顯示 AI text embedding 暫時不可用相關訊息
[ ] 可用 name / category / color 搜尋資料庫
[ ] 搜尋結果仍可顯示衣物卡片
```

測完後重新啟動 Python AI Service。

---

## 6. Try-on L1 驗收

網址：

```text
/closet/try-on
```

操作：

```text
1. 選擇一件衣物
2. 上傳人物照片
3. 按「建立 Try-on L1 任務」
```

檢查：

```text
[ ] 頁面可正常開啟
[ ] 衣物下拉選單有資料
[ ] 可上傳人物照片
[ ] 送出後建立 ai_jobs
[ ] job_type = pose_analysis
[ ] status = degraded 或 success
[ ] mode = mock
[ ] result_json 有 pose_analysis
[ ] result_json 有 keypoints
[ ] 頁面顯示 posture notes
[ ] 頁面顯示 fit notes
[ ] 頁面顯示 keypoints 數量
```

Tinker 檢查：

```php
\App\Models\AiJob::where('job_type', 'pose_analysis')->latest()->first();
```

---

## 7. Runway Video L1 驗收

網址：

```text
/workspace/runway-video
```

操作：

```text
1. 選擇一件衣物
2. 影片風格輸入 vogue luxury runway
3. 鏡頭節奏輸入 slow cinematic camera movement
4. 按「建立 Runway Storyboard」
```

檢查：

```text
[ ] 頁面可正常開啟
[ ] 衣物下拉選單有資料
[ ] 可送出表單
[ ] 建立 ai_jobs
[ ] job_type = runway_video
[ ] status = degraded
[ ] mode = mock
[ ] result_json 有 prompt
[ ] result_json 有 scenes
[ ] scenes 數量為 4
[ ] 頁面顯示 storyboard scenes
[ ] 頁面顯示 prompt
```

Tinker 檢查：

```php
\App\Models\AiJob::where('job_type', 'runway_video')->latest()->first();
```

---

## 8. Digital Twin L1 驗收

網址：

```text
/workspace/digital-twin
```

操作：

```text
1. 身高輸入 170
2. 風格偏好輸入 簡約都會風
3. 常見場合輸入 校園日常
4. 補充說明輸入 喜歡寬鬆版型
5. 按「建立 Digital Twin Profile」
```

檢查：

```text
[ ] 頁面可正常開啟
[ ] 可送出表單
[ ] 建立 ai_jobs
[ ] job_type = digital_twin
[ ] status = degraded
[ ] mode = mock
[ ] result_json 有 profile
[ ] result_json 有 style_summary
[ ] result_json 有 style_tags
[ ] 頁面顯示 Avatar Placeholder
[ ] 頁面顯示身高
[ ] 頁面顯示風格偏好
[ ] 頁面顯示常見場合
[ ] 頁面顯示 Style Summary
```

Tinker 檢查：

```php
\App\Models\AiJob::where('job_type', 'digital_twin')->latest()->first();
```

---

## 9. Python AI Service 驗收

### 9.1 Health

網址：

```text
http://127.0.0.1:8001/health
```

檢查：

```text
[ ] status = ok
[ ] service 有顯示
[ ] mock_mode 有顯示
[ ] version 有顯示
[ ] dependencies 有顯示
[ ] qdrant 狀態有顯示
[ ] clip / blip / pose 狀態有顯示
```

---

### 9.2 Laravel tinker smoke test

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

檢查：

```text
[ ] attributes status = degraded
[ ] image embedding status = degraded
[ ] text embedding status = degraded
[ ] similar search status = degraded
[ ] pose status = degraded
[ ] 五個 endpoint 都有 request_id
```

---

## 10. 降級狀態驗收

### 10.1 Degraded 顯示

```text
[ ] Smart Closet 可顯示 degraded/mock
[ ] AI Search 可顯示 mock 或 keyword_fallback
[ ] Try-on 可顯示 degraded/mock
[ ] Runway Video 可顯示 degraded/mock
[ ] Digital Twin 可顯示 degraded/mock
```

---

### 10.2 Failed 狀態

關閉 Python AI Service 後測試：

```text
[ ] 新增衣物不應造成整站爆掉
[ ] AI Search 會 fallback
[ ] Try-on 任務失敗時可記錄錯誤
[ ] 頁面可顯示錯誤訊息
```

---

## 11. GitHub 上傳前檢查

執行：

```powershell
git status
```

確認不要上傳：

```text
[ ] .env 沒有被加入
[ ] ai_service/.env 沒有被加入
[ ] ai_service/.venv 沒有被加入
[ ] database/database.sqlite 沒有被加入
[ ] vendor/ 沒有被加入
[ ] node_modules/ 沒有被加入
[ ] storage/ 沒有被加入
[ ] public/storage 沒有被加入
[ ] __pycache__/ 沒有被加入
```

可以上傳：

```text
[ ] README.md
[ ] docs/*.md
[ ] app/Http/Controllers/*.php
[ ] app/Models/*.php
[ ] app/Services/AiService.php
[ ] config/ai.php
[ ] database/migrations/*.php
[ ] resources/views/**/*.blade.php
[ ] ai_service/*.py
[ ] ai_service/routes/*.py
[ ] ai_service/services/*.py
[ ] ai_service/utils/*.py
[ ] ai_service/models/**/README.md
[ ] ai_service/README.md
[ ] ai_service/.env.example
```

---

## 12. 展示前最小驗收順序

若時間不足，至少依照以下順序驗收：

```text
[ ] 1. Laravel 首頁 / Dashboard 可開
[ ] 2. 登入成功
[ ] 3. Smart Closet 新增衣物成功
[ ] 4. 衣物詳細頁顯示 AI 分析結果
[ ] 5. AI Search 正常模式可搜尋
[ ] 6. AI Search fallback 可搜尋
[ ] 7. Try-on L1 可建立任務
[ ] 8. Runway Video L1 可建立 storyboard
[ ] 9. Digital Twin L1 可建立 profile
[ ] 10. /health 正常
```

---

## 13. 驗收結果紀錄

| 日期 | 驗收人 | 項目 | 結果 | 備註 |
|---|---|---|---|---|
|  |  | Smart Closet |  |  |
|  |  | AI Search |  |  |
|  |  | Try-on L1 |  |  |
|  |  | Runway Video L1 |  |  |
|  |  | Digital Twin L1 |  |  |
|  |  | AI Service |  |  |



【專案上下文】

畢業專題：VogueAI Smart Wardrobe。

前端目前改為 Laravel 12 + Blade + Vite + Tailwind，由組員負責。前端已完成：
- 登入 / 註冊
- Dashboard
- Account 管理
- Admin / User 角色權限
- users.role 欄位
- EnsureUserIsAdmin middleware
- i18n 中英切換
- dark mode
- Admin Users CRUD，含搜尋
- Smart Closet 主要頁面
- Workspace 模組入口頁
- 左側可伸縮導覽
- 功能切換入口

開發 DB 使用 SQLite。

我負責：
- 後端資料設計
- AI 模型 / AI Service
- Laravel 與 Python AI Service 串接
- API 契約
- 上傳流程
- DB schema / migration 對齊
- fallback / degraded 策略
- 測試與部署文件
- 讓每個功能逐步從 L1 展示版升級成可實際應用的功能

目前架構：
Laravel 主後端 + Python FastAPI AI Service。

Laravel 負責：
- 頁面
- 登入 / 權限
- Controller
- DB
- 檔案上傳
- 呼叫 AI Service
- 將 AI 結果寫回 DB
- 顯示任務狀態
- 處理 failed / degraded / fallback

Python FastAPI AI Service 負責：
- attributes 衣物屬性辨識
- image embedding
- text embedding
- similar search
- pose analysis
- health / dependencies check
- 未來接 CLIP / BLIP / YOLO Pose / Qdrant / 外部生成 API

目前採用 Mock-first / Degraded 策略：
先確保功能可以操作、資料可以寫入、任務可以建立、頁面可以展示，再逐步換成真實模型。

所有假設請用 [ASSUMPTION] 標註。
遇到缺資料時，請先列出缺資料清單，然後用合理假設產出草稿，不要直接停下來。

---

【非常重要：所有功能都要有主要入口與實際應用路徑】

之後不管新增或修改任何功能，都必須注意：

1. 功能不能只是一個孤立頁面。
2. 功能要能從主要入口進入。
3. 功能要能在 Dashboard / SmartCloset Hub / Workspace / 側邊欄 / 功能切換中被看見。
4. 功能要有清楚的 Route name。
5. 功能要有可操作流程。
6. 功能要能寫入 DB 或 ai_jobs，不能只停留在靜態 UI。
7. 功能即使還是 L1，也要有實際應用價值，例如：
   - 建立資料
   - 顯示結果
   - 儲存紀錄
   - 可重新執行
   - 可 fallback
   - 可展示給老師看
8. 若目前是真模型尚未完成，請明確標示為 L1 / mock / degraded，不要假裝已經是真 AI。
9. 每次補功能時，都要同步考慮：
   - Dashboard 是否要加入口或統計
   - SmartCloset Hub 是否要加入口
   - Workspace 是否要加入口
   - 左側導覽 / 功能切換是否要加入口
   - README / docs 是否要更新
   - 測試或驗收 checklist 是否要補

---

【系統必須包含並逐步實際應用的主要入口與功能】

請你之後協助開發時，要確保以下功能都有實際可用入口與逐步可應用流程：

主要入口與管理功能：

1. 儀表板 Dashboard
   - 顯示系統總覽
   - 顯示衣物數量
   - 顯示 AI 任務狀態
   - 顯示最近新增衣物
   - 顯示快速入口
   - 後續可顯示推薦、趨勢、任務提醒

2. My Closet
   - 衣物列表
   - 搜尋 / 篩選
   - 查看衣物詳細
   - 管理衣物資料
   - 顯示 AI 分析結果

3. 帳號總覽 Account Overview
   - 使用者資料
   - 個資修改
   - 密碼更新
   - 帳號刪除
   - 後續可顯示風格偏好、Digital Twin profile

4. 使用者管理 Admin Users
   - Admin 可管理使用者
   - 搜尋使用者
   - 建立 / 查看 / 編輯 / 刪除
   - 權限 role 管理

5. 功能切換 / 左側導覽 / Workspace
   - 所有主要模組都要能從這裡進入
   - 不要讓功能只存在 route 卻沒有入口
   - 新增功能時要提醒我是否需要補導覽連結

---

【Smart Closet 相關功能】

6. SmartCloset Hub
   - 作為智慧衣櫥主入口
   - 顯示衣物統計
   - 顯示 AI 狀態統計
   - 顯示快速功能入口：
     - My Closet
     - 上傳衣物
     - AI Search
     - AI Stylist
     - Try-On / Pose
     - SmartTag
     - QuickSnap
     - Smart Storage
   - 後續可顯示最近任務、推薦穿搭、待補資料

7. 上傳衣物 Upload Clothing
   - 可上傳衣物圖片
   - 建立 clothes
   - 呼叫 /ai/attributes
   - 寫回類別、顏色、季節、場合、用途、style tags
   - 呼叫 /ai/embed/image
   - 寫入 ai_embeddings
   - AI 失敗時仍保留衣物並標記 failed / degraded
   - 可重新分析與 reembed

8. AI 搜尋 AI Search
   - 可輸入文字搜尋衣物
   - 呼叫 /ai/embed/text
   - 呼叫 /ai/search/similar
   - 顯示 similarity score
   - AI Service 關閉時 fallback 到 SQL LIKE
   - 後續接 Qdrant 與真 CLIP embedding

9. AI Stylist
   - 不能只停留在頁面展示
   - L1：輸入場合 / 天氣 / 風格，產生 mock 穿搭建議
   - L2：從 clothes 真實挑衣物組合
   - L3：根據穿搭紀錄、Digital Twin、接受 / 拒絕紀錄做個人化推薦
   - 建議建立 stylist_history
   - 要能從 SmartCloset Hub、Dashboard 或 Workspace 進入

10. Try-On / 姿態
   - 目前 L1 已完成：
     - 選衣物
     - 上傳人物圖片
     - 建立 pose_analysis ai_job
     - 呼叫 /ai/pose
     - 顯示 mock keypoints、posture notes、fit notes
   - L2：接 YOLO Pose / MediaPipe，真實分析 keypoints
   - L3：接 virtual try-on API 或模型，產生換裝圖
   - 要保留 failed / degraded / retry 流程

---

【平台與互動功能】

11. Community
   - 不能只停留在 Workspace 展示
   - L1：穿搭貼文展示 / 建立貼文
   - L2：comments / likes / follows
   - L3：趨勢互動與推薦
   - 可建立 community_posts、comments、likes、follows
   - 要能從 Workspace 或 Dashboard 進入

12. Showcase
   - 商家展示 / 穿搭展示入口
   - L1：展示商家或精選穿搭卡片
   - L2：一鍵匯入衣物到衣櫥
   - L3：與推薦、趨勢、商家資料串接
   - 可與 Smart Closet 的上傳 / 匯入流程串接

13. Blind Box
   - 穿搭盲盒
   - L1：使用者選擇場合後產生 mock 穿搭盲盒
   - L2：從 clothes 隨機但合理組合
   - L3：根據風格偏好與歷史紀錄個人化產生
   - 建議寫入 ai_jobs 或 blind_box_history

14. Runway Video
   - 目前 L1 已完成：
     - 選衣物
     - 輸入影片風格
     - 建立 runway_video ai_job
     - 產生 prompt + 4 個 storyboard scenes
   - L2：queue / processing / success / failed / retry
   - L3：接 Veo / RunwayML / Pika 影片生成 API
   - 要能從 Workspace、Dashboard 或 Showcase 入口進入

15. Chat Assistant
   - L1：mock fashion Q&A
   - L2：讀取 clothes / style profile 給穿搭建議
   - L3：接 Gemini / RAG / trend memory
   - 要保留對話紀錄或至少有 chat_logs / ai_jobs
   - 不能只是靜態輸入框

16. Digital Twin
   - 目前 L1 已完成：
     - 輸入身高
     - 風格偏好
     - 常見場合
     - 建立 digital_twin ai_job
     - 顯示 avatar placeholder / style summary / style tags
   - L2：從 clothes 統計使用者真實風格
   - L3：接 3D avatar / 多視角生成 / image generation
   - 後續要與 AI Stylist、Try-On、Runway Video 串接

17. Travel Packer
   - 旅行打包助手
   - L1：輸入目的地 / 天數 / 場合，產生 mock packing list
   - L2：從 clothes 挑選衣物
   - L3：結合天氣 API、行程、穿搭推薦
   - 建議建立 travel_packing_jobs 或寫入 ai_jobs

18. Smart Storage
   - 智慧收納
   - L1：建立收納箱 / QR code 概念展示
   - L2：衣物綁定 storage box
   - L3：掃 QR code 查看箱內衣物
   - 建議資料表：
     - storage_boxes
     - storage_items
   - 要能跟 My Closet 串接

19. QuickSnap
   - 快速拍照入庫
   - L1：快速上傳照片
   - L2：自動建立 clothes 草稿
   - L3：自動分類、去背、標籤分析
   - 可重用 Upload Clothing + /ai/attributes 流程

20. SmartTag
   - 標籤 / 吊牌 / 發票掃描
   - L1：上傳吊牌或輸入商品資訊
   - L2：解析品牌、價格、材質
   - L3：OCR + 搜尋補圖 + 自動建立衣物
   - 可與 Brave Search / OCR / clothes 串接

21. Magic Mirror
   - 魔鏡 / 姿態與穿搭分析
   - L1：上傳人物照，回傳 mock 姿態與穿搭建議
   - L2：接 YOLO Pose / MediaPipe
   - L3：即時鏡頭、體態分析、天氣建議
   - 可共用 /ai/pose 與 Try-On 的 pose_analysis 流程

22. AI Bestie Call
   - AI 好友視訊 / 語音穿搭建議
   - L1：展示通話介面與 mock 建議
   - L2：語音輸入 / Web Speech API / chat response
   - L3：即時視覺分析 + 語音對話
   - 可與 Chat Assistant、Magic Mirror、BLIP 串接

---

【目前已完成內容】

1. 架構決策
已採用：
Laravel 主後端 + Python FastAPI AI Service。

2. Laravel ⇄ AI Service API 契約
已建立：
docs/ai-api-contract.md

已定義端點：
- GET /health
- POST /ai/attributes
- POST /ai/embed/image
- POST /ai/embed/text
- POST /ai/search/similar
- POST /ai/pose

3. DB Schema / migrations
已建立核心資料表：
- clothes
- ai_embeddings
- ai_jobs

已建立核心 Model：
- App\Models\Clothing
- App\Models\AiEmbedding
- App\Models\AiJob

4. Laravel AiService
已建立：
app/Services/AiService.php

已封裝方法：
- analyzeAttributes()
- embedImage()
- embedText()
- searchSimilar()
- analyzePose()

5. Smart Closet 主流程
已完成：
Blade form
→ Laravel Controller 驗證
→ storage 存圖
→ 建立 clothes
→ 呼叫 /ai/attributes
→ AI 分析結果寫回 clothes
→ 呼叫 /ai/embed/image
→ image embedding 寫入 ai_embeddings
→ redirect closet.show
→ 顯示 AI 分析結果

6. AI Search
已完成：
使用者輸入文字
→ Laravel 呼叫 /ai/embed/text
→ 取得 text embedding
→ 呼叫 /ai/search/similar
→ 回傳 topK clothing_id
→ Laravel 查 clothes
→ 顯示搜尋結果
→ AI Service 關閉時 fallback 到 SQL LIKE keywordSearch

7. Python AI Service 工程化
已完成：
ai_service/
- main.py
- config.py
- schemas.py
- routes/ai_routes.py
- services/mock_ai_service.py
- utils/security.py
- utils/response.py
- utils/logger.py
- utils/dependencies.py
- models/README.md
- models/clip/README.md
- models/blip/README.md
- models/yolo_pose/README.md
- ai_service/README.md

8. Try-on L1
已完成：
closet.tryon
→ 選擇衣物
→ 上傳人物圖片
→ 建立 ai_jobs
→ 呼叫 /ai/pose
→ 寫回 ai_jobs.result_json
→ 頁面顯示 mock / degraded pose 結果

9. Runway Video L1
已完成：
workspace/runway-video
→ 選擇衣物
→ 輸入影片風格與鏡頭節奏
→ 建立 ai_jobs
→ 產生 mock storyboard
→ 寫回 ai_jobs.result_json
→ 頁面顯示 prompt + 4 個 scenes

10. Digital Twin L1
已完成：
workspace/digital-twin
→ 輸入身高、風格偏好、常見場合
→ 建立 ai_jobs
→ 產生 mock profile
→ 寫回 ai_jobs.result_json
→ 顯示 avatar placeholder / style summary / style tags

11. 測試
已建立文件：
- docs/backend-ai-test-plan.md
- docs/manual-acceptance-checklist.md
- docs/test-execution-record.md

已建立 AI Service pytest：
- ai_service/tests/test_health.py
- ai_service/tests/test_ai_routes.py
- ai_service/tests/test_security_validation.py

已建立 Laravel Feature Tests：
- tests/Feature/SmartClosetTest.php
- tests/Feature/AiSearchTest.php
- tests/Feature/AiJobsL1Test.php

目前 Laravel Feature Test 結果：
- SmartClosetTest：5 passed / 7 assertions
- AiSearchTest：4 passed / 9 assertions
- AiJobsL1Test：5 passed / 21 assertions
合計：14 tests passed / 37 assertions

12. 部署與展示
已建立：
- docs/demo-deployment-guide.md
- start-all.ps1

13. 4 週里程碑
已建立：
docs/four-week-milestone-and-acceptance.md

---

【目前功能完整性判斷】

已比較接近真實後端功能：
- Dashboard / 基礎入口
- My Closet
- Account Overview
- Admin Users
- Smart Closet
- clothes DB
- 圖片上傳
- AI attributes 寫回
- image embedding 寫入
- AI Search fallback
- ai_jobs 任務紀錄
- Laravel AiService
- Python AI Service mock endpoints
- 測試與部署文件

目前仍是 L1 / mock / degraded：
- AI Stylist
- Try-on
- Community
- Showcase
- Blind Box
- Runway Video
- Chat Assistant
- Digital Twin
- Travel Packer
- Smart Storage
- QuickSnap
- SmartTag
- Magic Mirror
- AI Bestie Call
- 真實 CLIP / BLIP / Qdrant
- 真實 YOLO Pose
- 真實影片生成
- 真實 3D avatar

請回答時不要把 L1 包裝成已完成真實 AI 模型，要誠實標明目前是 L1 / mock / degraded。
但也不要只說「還沒做」，要協助我把每個功能設計成可逐步完成、可展示、可驗收、可寫入資料的實際應用流程。

---

【後續補強優先順序】

優先順序 1：AI Stylist L1 / L2
原因：
AI Stylist 最能把目前已完成的 Smart Closet、Digital Twin、AI Search 串起來。

優先順序 2：Try-on L2
原因：
Try-on L1 已完成，延伸最自然。

優先順序 3：Runway Video L2
原因：
L1 storyboard 已完成，可以補任務流程完整性。

優先順序 4：Digital Twin L2
原因：
可以從現有衣櫥資料做風格分析。

優先順序 5：Trend / Chat L1
原因：
提升平台感，展示上加分。

優先順序 6：SmartTag / QuickSnap / Smart Storage
原因：
這三個最容易和 Smart Closet 串接，能讓「衣物進入系統」更完整。

優先順序 7：Community / Showcase / Blind Box / Travel Packer / Magic Mirror / AI Bestie Call
原因：
這些是展示完整平台感與亮點的功能，可逐步 L1 化，再慢慢補 L2。

---

【回答格式要求】

當我貼錯誤 log 時，請用：

定位 →
原因 →
修法 →
驗證 →

當我要新增功能時，請用：

目前狀態 →
目標 →
入口與路由 →
檔案修改清單 →
完整程式碼 →
測試指令 →
驗收方式 →
下一步 →

當我要整理文件時，請直接給我可以貼進 md 的完整內容。

當我要改 Laravel 程式時，請注意：
- 不要破壞現有前端組員做好的 Blade / Tailwind 架構
- 優先最小修改
- route name 要明確
- Controller 要處理 auth user 資料隔離
- 如果會寫入 DB，要說明 migration / model / fillable / casts 是否需要改
- 如果會呼叫 AI Service，要有 failed / degraded fallback
- 如果是測試，記得加 $this->withoutVite()
- 新功能必須有入口，不能只有 route
- 新功能應優先接到 Dashboard / SmartCloset Hub / Workspace / 功能切換其中至少一個入口

當我要改 Python AI Service 時，請注意：
- 現在 ai_service 已拆分成 routes / services / utils / config / schemas
- 不要再把所有東西塞回 main.py
- API response 要維持 schema_version / request_id / status / mode
- 缺依賴要 degraded，不要讓整個服務啟動失敗
- Internal token 要檢查
- 要補 pytest 或至少說明測試方式

---

【目前下一步】

我現在準備進入後續專題收尾與功能完整性補強。

請你根據以上上下文，協助我做下一步時：
1. 先判斷目前是在哪一個大步驟
2. 說明這一步要解決什麼問題
3. 確認功能入口在哪裡
4. 給我最小可行修改方案
5. 如果是文件，請給完整 md
6. 如果是程式，請給完整檔案或明確插入位置
7. 最後給測試指令與驗收條件
