# VogueAI GitHub Upload Review Checklist

Updated: 2026-06-10 Asia/Taipei

This checklist tracks what must be reviewed before staging, committing, or pushing the current workspace. Do not upload to GitHub until every blocker is resolved and the user explicitly confirms the final upload step.

## Current Gate Status

- [x] Laravel full regression passed: `.\vendor\bin\pest.bat` -> 83 passed / 427 assertions.
- [x] AI service full regression passed: `ai_service\.venv\Scripts\python.exe -m pytest ai_service\tests` -> 39 passed / 1 warning.
- [x] Frontend production build passed: `npm.cmd run build`.
- [x] Provider startup gate with AI Service + Qdrant running passed with one external warning: `php artisan vogueai:provider-check` -> 0 failed / 1 warning (`GEMINI_API_KEY`).
- [x] Real-mode acceptance gate with AI Service + Qdrant running passed: `php artisan vogueai:real-mode-check` -> 0 failed / 0 warnings.
- [x] Upload scope review gate configured: `php artisan vogueai:upload-scope`.
- [x] Gemini external smoke passed: `php artisan vogueai:gemini-smoke` -> ready / real_adapter.
- [x] `GEMINI_API_KEY` was cleared from `.env.example`; the real key is kept only in local `.env`.
- [x] Authenticated page sweep passed on 2026-06-06/07: Dashboard, Smart Closet Hub, My Closet, Create Clothing, AI Search, AI Stylist, Try-on, Runway, Digital Twin, Account, and Profile all returned HTTP 200.
- [x] UI copy cleanup focused regression passed: `.\vendor\bin\pest.bat tests\Feature\AiSearchTest.php tests\Feature\AiStylistTest.php tests\Feature\AiJobsL1Test.php tests\Feature\SmartClosetTest.php tests\Feature\Auth\AuthenticationTest.php tests\Feature\Auth\RegistrationTest.php tests\Feature\Auth\PasswordResetTest.php tests\Feature\Auth\PasswordConfirmationTest.php tests\Feature\Auth\EmailVerificationTest.php tests\Feature\ProfileTest.php tests\Feature\ExampleTest.php` -> 60 passed / 314 assertions.
- [x] Demo readiness passed with warning: `php artisan vogueai:demo-check` -> 0 failed / 1 warning.
- [x] Local services restarted and smoke checked: Laravel `/`, Laravel `/login`, Vite `@vite/client`, and AI Service `/health` all returned HTTP 200.
- [x] Authenticated page sweep passed with demo account: Home, Dashboard, Smart Closet Hub, My Closet, Create Clothing, AI Search, AI Stylist, Try-on, Runway, Digital Twin, Account, and Profile all returned HTTP 200 with no major introductory-English scan hits.
- [x] Admin routes returned HTTP 403 for the non-admin demo account, matching the expected access guard.
- [ ] GitHub readiness passed: currently blocked by dirty worktree and Telescope migration deletion confirmation.
- [ ] User confirmed Telescope duplicate migration cleanup.
- [ ] User confirmed all changed/untracked files are intended for upload.
- [ ] Files staged intentionally.
- [ ] Commit created intentionally.
- [ ] Push/PR explicitly approved by user.

## Upload Blockers

1. Worktree is dirty.
   - Current GitHub gate reports 115 changed/untracked entries after Gemini smoke gate, provider integration gates, real-mode acceptance gates, upload-scope review gate, and the latest full regression.
   - `.gitignore` now ignores `.pytest_cache/`, so raw `git status` no longer emits the local pytest cache permission warning.
   - Required action: review scope, then stage intentionally.

2. Telescope migration deletion needs explicit confirmation.
   - Deleted file: `database/migrations/2026_04_22_161722_create_telescope_entries_table.php`.
   - Retained file: `database/migrations/2026_04_22_161640_create_telescope_entries_table.php`.
   - Finding rechecked on 2026-06-07: line contents match; both create `telescope_entries`, `telescope_entries_tags`, and `telescope_monitoring`.
   - Required user confirmation: "保留 161640，刪除 161722，作為重複 Telescope migration 清理。"

## 2026-06-09 Current Pre-Upload Audit

Changed/untracked file grouping from `git status --short --untracked-files=all`:

- AI service: 26
- Laravel backend: 17
- Views and UI: 34
- Tests: 20
- Docs: 6
- Config and scripts: 4
- Database migrations: 5
- Assets: 3
- Other: 0

Current `php artisan vogueai:github-check` result:

- Worktree review: blocked by 115 changed/untracked entries.
- Telescope migration deletion: blocked until user explicitly confirms the duplicate cleanup.
- Local `.env`: pass, not listed in git status.
- Large model artifacts: pass, no large model artifact extensions detected.
- Local provider artifacts: Qdrant runtime and Hugging Face cache are ignored by provider gate and do not appear in git status.
- GitHub action state: no staging, no commit, no push performed.

Latest verification before upload planning:

- `.\vendor\bin\pest.bat` -> 83 passed / 427 assertions.
- `ai_service\.venv\Scripts\python.exe -m pytest ai_service\tests` -> 39 passed / 1 warning.
- `npm.cmd run build` -> passed.
- `php artisan vogueai:demo-check` -> 0 failed / 1 warning.
- With AI Service + Qdrant running, `php artisan vogueai:provider-check` -> 0 failed / 0 warnings.
- With AI Service + Qdrant running, `php artisan vogueai:real-mode-check` -> 0 failed / 0 warnings.
- `php artisan vogueai:gemini-smoke` -> ready / real_adapter.
- `php artisan vogueai:upload-scope` -> 115 changed/untracked entries, no `.env` or large model artifact risks, no manual-review group entries.

## 2026-06-07 Pre-Upload Audit

Changed/untracked file grouping from `git status --short`:

- AI service: 17
- Laravel backend: 19
- Views and UI: 29
- Tests: 16
- Docs: 5
- Config and scripts: 3
- Database migrations: 5
- Assets: 1
- Other: 0

Current `php artisan vogueai:github-check` result:

- Worktree review: blocked by 104 changed/untracked entries.
- Telescope migration deletion: blocked until user explicitly confirms the duplicate cleanup.
- Local `.env`: pass, not listed in git status.
- Large model artifacts: pass, no large model artifact extensions detected.
- GitHub action state: no staging, no commit, no push performed.

## Draft Commit Plan

This is a planning outline only. No files have been staged, committed, or pushed.

Recommended commit groups:

1. `ai-service-adapter-contracts`
   - Scope: FastAPI AI service config, routes, mock/degraded adapter orchestration, CLIP, BLIP, Qdrant real local services, AI service tests, AI service docs.
   - Verification before commit: `ai_service\.venv\Scripts\python.exe -m pytest ai_service\tests`.

2. `laravel-closet-stylist-workflows`
   - Scope: Closet, AI Search, AI Stylist, WearLog, OutfitLog, Digital Twin / Runway workflow controllers, models, migrations, and feature tests.
   - Verification before commit: `.\vendor\bin\pest.bat tests\Feature\SmartClosetTest.php tests\Feature\AiSearchTest.php tests\Feature\AiStylistTest.php tests\Feature\AiJobsL1Test.php`.

3. `demo-readiness-and-github-gates`
   - Scope: demo data service, demo readiness command, GitHub readiness command, provider readiness command, real-mode acceptance command, Qdrant launcher, demo assets, environment examples, `.gitignore`.
   - Verification before commit: `php artisan vogueai:demo-check`, `php artisan vogueai:provider-check`, `php artisan vogueai:real-mode-check`, and `php artisan vogueai:github-check`.

4. `localized-ui-and-manual-polish`
   - Scope: Blade UI Traditional Chinese cleanup, auth/account/admin pages, closet image sizing fixes, theme toggle fixes, manual acceptance updates.
   - Verification before commit: authenticated HTTP page sweep and `npm.cmd run build`.

5. `project-docs-and-roadmap`
   - Scope: core progress tracker, GitHub checklist, Gemini adapter plan, README updates.
   - Verification before commit: confirm docs match current blocker state and no upload approval is implied.

Final pre-push gate after all commits:

- `.\vendor\bin\pest.bat`
- `ai_service\.venv\Scripts\python.exe -m pytest ai_service\tests`
- `php artisan vogueai:demo-check`
- `php artisan vogueai:github-check`
- `git status --short`

## Exact Staging Manifest Draft

This is a dry-run manifest only. Do not stage these until the user confirms the GitHub blockers and upload intent.

### Commit 1: `ai-service-adapter-contracts`

- `ai_service/.env.example`
- `ai_service/README.md`
- `ai_service/config.py`
- `ai_service/main.py`
- `ai_service/models/blip/README.md`
- `ai_service/models/clip/README.md`
- `ai_service/requirements.txt`
- `ai_service/requirements-ml.txt`
- `ai_service/routes/ai_routes.py`
- `ai_service/services/adapter_orchestration_service.py`
- `ai_service/services/blip_caption_service.py`
- `ai_service/services/clip_embedding_service.py`
- `ai_service/services/mock_ai_service.py`
- `ai_service/services/vector_store_service.py`
- `ai_service/utils/dependencies.py`
- `ai_service/docs/backend-ai-progress.md`
- `ai_service/tests/`
- `ai_service/utils/image_paths.py`

### Commit 2: `laravel-closet-stylist-workflows`

- `app/Http/Controllers/ClosetController.php`
- `app/Http/Controllers/FeatureController.php`
- `app/Http/Controllers/WorkspaceController.php`
- `app/Models/Clothing.php`
- `app/Models/OutfitLog.php`
- `app/Models/StylistHistory.php`
- `app/Models/WearLog.php`
- `app/Services/StylistTextGenerationService.php`
- `config/ai.php`
- `database/migrations/2026_05_30_001000_add_feedback_fields_to_stylist_history_table.php`
- `database/migrations/2026_05_30_002000_add_context_json_to_stylist_history_table.php`
- `database/migrations/2026_06_02_001000_create_wear_logs_table.php`
- `database/migrations/2026_06_02_002000_create_outfit_logs_table.php`
- `routes/web.php`
- `tests/Feature/AiJobsL1Test.php`
- `tests/Feature/AiSearchTest.php`
- `tests/Feature/AiServiceMockModeTest.php`
- `tests/Feature/AiStylistTest.php`
- `tests/Feature/SmartClosetTest.php`

### Commit 3: `demo-readiness-and-github-gates`

- `.env.example`
- `.gitignore`
- `app/Services/DemoDataService.php`
- `app/Services/DemoReadinessService.php`
- `app/Services/GithubReadinessService.php`
- `app/Services/ProviderReadinessService.php`
- `app/Services/RealModeAcceptanceService.php`
- `app/Services/UploadScopeReviewService.php`
- `public/images/demo/`
- `routes/console.php`
- `start-all.ps1`
- `start-qdrant.ps1`
- `tests/Feature/DemoDataTest.php`
- `tests/Feature/DemoReadinessTest.php`
- `tests/Feature/GithubReadinessTest.php`
- `tests/Feature/ProviderReadinessTest.php`
- `tests/Feature/RealModeAcceptanceTest.php`
- `tests/Feature/UploadScopeReviewTest.php`
- `tests/Feature/GeminiSmokeTest.php`

### Commit 4: `localized-ui-and-manual-polish`

- `lang/en/admin.php`
- `lang/en/profile.php`
- `lang/zh_TW/admin.php`
- `lang/zh_TW/profile.php`
- `lang/zh_TW/validation.php`
- `resources/css/app.css`
- `resources/views/admin/users/create.blade.php`
- `resources/views/admin/users/edit.blade.php`
- `resources/views/admin/users/index.blade.php`
- `resources/views/admin/users/show.blade.php`
- `resources/views/auth/confirm-password.blade.php`
- `resources/views/auth/forgot-password.blade.php`
- `resources/views/auth/login.blade.php`
- `resources/views/auth/register.blade.php`
- `resources/views/auth/reset-password.blade.php`
- `resources/views/auth/verify-email.blade.php`
- `resources/views/closet/create.blade.php`
- `resources/views/closet/hub.blade.php`
- `resources/views/closet/index.blade.php`
- `resources/views/closet/search.blade.php`
- `resources/views/closet/show.blade.php`
- `resources/views/closet/stylist.blade.php`
- `resources/views/closet/tryon.blade.php`
- `resources/views/components/vogue-auth-nav.blade.php`
- `resources/views/components/vogue-page.blade.php`
- `resources/views/dashboard.blade.php`
- `resources/views/features/show.blade.php`
- `resources/views/home.blade.php`
- `resources/views/layouts/guest.blade.php`
- `resources/views/layouts/navigation.blade.php`
- `resources/views/profile/edit.blade.php`
- `resources/views/profile/show.blade.php`
- `resources/views/welcome.blade.php`
- `resources/views/workspace/show.blade.php`
- `tests/Feature/Auth/`
- `tests/Feature/ExampleTest.php`
- `tests/Feature/ProfileTest.php`

### Commit 5: `project-docs-and-roadmap`

- `README.md`
- `docs/gemini-stylist-adapter-plan.md`
- `docs/github-upload-review-checklist.md`
- `docs/manual-acceptance-checklist.md`
- `docs/model-integration-readiness.md`
- `docs/vogueai-core-progress.md`

### Blocked Until Explicit User Confirmation

- `database/migrations/2026_04_22_161722_create_telescope_entries_table.php` deletion

Required confirmation remains:

`保留 161640，刪除 161722，作為重複 Telescope migration 清理。`

## Remaining Work From Here

### Required Before GitHub Upload

- [x] Review group 1: AI Service Adapters And Contracts.
- [x] Review group 2: Laravel Closet, Stylist, Wear, Outfit Features.
- [x] Review group 3: Demo And GitHub Readiness Tooling.
- [x] Review group 4: Tests.
- [x] Review group 5: Project Docs.
- [x] UI visible introduction copy cleanup completed on 2026-06-05.
- [x] Main visible pages and controller copy now use Traditional Chinese, while technical keys/provider names remain unchanged for contracts.
- [x] Expanded cleanup also covers Breeze password pages, account pages, admin user pages, and legacy welcome page.
- [x] Service smoke checks after cleanup: Laravel `/`, Laravel `/login`, Vite `@vite/client`, and AI Service `/health` all returned HTTP 200.
- [ ] Resolve Telescope duplicate migration blocker after explicit user confirmation.
- [x] Rerun full verification after all reviews:
  - `.\vendor\bin\pest.bat`
  - `ai_service\.venv\Scripts\python.exe -m pytest ai_service\tests`
  - `php artisan vogueai:demo-check`
  - `php artisan vogueai:github-check`
  - `git status --short --untracked-files=all`
- [x] Restart local services and perform final manual browser spot-check:
  - Home / Login / Dashboard / Smart Closet Hub.
  - My Closet list/detail and Image Caption Contract.
  - AI Search metadata and fallback labels.
  - AI Stylist history, feedback, context, embedding signals, outfit log.
  - Wear Tracking.
  - Try-on Pose Quality.
  - Workspace Runway Video and Digital Twin.
- [ ] Confirm no local secrets or large model artifacts are in git status.
- [ ] Confirm the full changed/untracked file list is intended for upload.
- [ ] Stage files intentionally by review group or final commit plan.
- [ ] Create commits intentionally.
- [ ] Ask user for final explicit approval to push or open PR.
- [ ] Push/open PR only after approval.

### After GitHub Upload / Future Product Work

- [ ] Formal model integration phase: promote the current L3 adapter contracts from mock/degraded fallback to real providers after environment validation.
- [ ] Install and validate real ML dependencies in a suitable environment: `torch`, `transformers`, `pillow`.
- [ ] Run real CLIP image/text embedding smoke tests with local images/text.
- [ ] Run Qdrant daemon, create/verify `vogueai_clothing_embeddings`, and test connection-enabled preflight.
- [ ] Switch Qdrant from fallback to active only after 512D CLIP vectors are confirmed.
- [ ] Connect real BLIP caption generation and validate image captions.
- [ ] Connect real Gemini text generation provider.
- [ ] Add real Magic Mirror / pose provider integration beyond current mock pose analysis.
- [ ] Continue P5 features: Trend / Chat Assistant, SmartTag / QuickSnap / Smart Storage, Community / Showcase / Blind Box / Travel Packer.

## Review Groups

### 1. AI Service Adapters And Contracts

Review status: completed on 2026-06-05. One degraded-safety fix was applied.

- `ai_service/config.py`
- `ai_service/main.py`
- `ai_service/routes/ai_routes.py`
- `ai_service/services/mock_ai_service.py`
- `ai_service/utils/dependencies.py`
- `ai_service/services/adapter_orchestration_service.py`
- `ai_service/services/blip_caption_service.py`
- `ai_service/services/clip_embedding_service.py`
- `ai_service/services/vector_store_service.py`
- `ai_service/requirements.txt`
- `ai_service/requirements-ml.txt`
- `ai_service/.env.example`
- `ai_service/README.md`
- `ai_service/models/blip/README.md`
- `ai_service/models/clip/README.md`
- `ai_service/docs/backend-ai-progress.md`

Review focus:
- CLIP/BLIP/Qdrant remain mock-first and degraded-safe.
- Missing heavy ML dependencies do not break demo.
- Qdrant 512D guard prevents mock 8D vectors from being written.
- Internal vector-store endpoints require internal token.

Review result:
- BLIP health dependency status now requires `pillow`, matching the adapter's real dependency gate.
- Qdrant metadata now keeps `mock_sqlite_fallback` active when only `qdrant-client` exists but daemon/collection readiness has not been confirmed.
- Qdrant preflight only marks active provider as `qdrant` when connection and collection are both ready.
- Focused checks passed: py_compile for updated AI files, and `pytest ai_service\tests\test_health.py ai_service\tests\test_vector_store_service.py ai_service\tests\test_ai_routes.py` -> 19 passed.
- Full AI service suite passed after review: `ai_service\.venv\Scripts\python.exe -m pytest ai_service\tests` -> 36 passed.

### 2. Laravel Closet, Stylist, Wear, Outfit Features

Review status: completed on 2026-06-05. One UI text cleanup was applied.

- `app/Http/Controllers/ClosetController.php`
- `app/Http/Controllers/WorkspaceController.php`
- `app/Models/Clothing.php`
- `app/Models/StylistHistory.php`
- `app/Models/WearLog.php`
- `app/Models/OutfitLog.php`
- `app/Services/StylistTextGenerationService.php`
- `database/migrations/2026_05_30_001000_add_feedback_fields_to_stylist_history_table.php`
- `database/migrations/2026_05_30_002000_add_context_json_to_stylist_history_table.php`
- `database/migrations/2026_06_02_001000_create_wear_logs_table.php`
- `database/migrations/2026_06_02_002000_create_outfit_logs_table.php`
- `resources/views/closet/search.blade.php`
- `resources/views/closet/show.blade.php`
- `resources/views/closet/stylist.blade.php`
- `resources/views/closet/tryon.blade.php`
- `resources/views/workspace/show.blade.php`
- `routes/web.php`
- `config/ai.php`

Review focus:
- Ownership checks prevent cross-user access.
- Wear/outfit logging saves only user-owned context.
- Stylist history feedback/context/embedding signals render correctly.
- UI clearly labels fallback/degraded metadata.

Review result:
- Ownership-sensitive routes use current-user scoped queries for clothing, stylist history, wear logs, outfit logs, Digital Twin jobs, Try-on, and AI Search.
- Wear tracking updates `wear_count` and `last_worn_at` only for owned clothing.
- Outfit logs are created only from the signed-in user's Stylist History and preserve selected item ids/context metadata.
- Stylist feedback/context/embedding/Gemini mock metadata remains persisted as structured JSON.
- Cleaned visible mojibake separators in stylist/workspace metadata panels (`繚` -> `·`) so manual web verification does not show broken text.
- Syntax checks passed for `ClosetController`, `WorkspaceController`, `WearLog`, `OutfitLog`, and `StylistTextGenerationService`.
- Focused Laravel feature tests passed: `.\vendor\bin\pest.bat tests\Feature\SmartClosetTest.php tests\Feature\AiStylistTest.php tests\Feature\AiSearchTest.php tests\Feature\AiJobsL1Test.php` -> 38 passed / 262 assertions.

### 3. Demo And GitHub Readiness Tooling

Review status: completed on 2026-06-05. Demo cleanup and GitHub status parsing were hardened.

- `app/Services/DemoDataService.php`
- `app/Services/DemoReadinessService.php`
- `app/Services/GithubReadinessService.php`
- `routes/console.php`
- `start-all.ps1`
- `.env.example`
- `README.md`
- `public/images/demo/white-shirt.jpg`
- `public/images/demo/navy-blazer.jpg`
- `public/images/demo/red-dress.jpg`

Review focus:
- Demo seed/cleanup only affects `demo@vogueai.local`.
- `start-all.ps1` runs readiness checks before starting services.
- GitHub readiness blocks `.env`, large model files, dirty worktree, and unconfirmed Telescope deletion.

Review result:
- Demo embedding upsert now scopes by `user_id`, `clothing_id`, and `embedding_type`.
- Demo cleanup test now creates a non-demo user/clothing record and verifies cleanup preserves it.
- GitHub readiness now parses `git status --short` entries instead of treating warning text as changed files.
- GitHub readiness now reports `git status` warnings as warnings, while excluding them from dirty worktree counts.
- GitHub readiness now preserves the leading two-character git status column and runs `git -C <repo>` through stdout/stderr pipes, preventing Windows cwd and trimming issues from undercounting tracked changes.
- GitHub readiness now detects staged Telescope deletion, staged/modified `.env`, and `.gguf` model artifacts.
- `.gitignore` now ignores `.pytest_cache/` so local pytest cache permissions do not pollute the final raw git status output.
- Demo wardrobe image assets were added under `public/images/demo/` after browser spot-check found 404s for the seeded demo clothing URLs.
- Syntax checks passed for `DemoDataService`, `DemoReadinessService`, `GithubReadinessService`, and `routes/console.php`.
- Focused readiness tests passed: `.\vendor\bin\pest.bat tests\Feature\DemoDataTest.php tests\Feature\DemoReadinessTest.php tests\Feature\GithubReadinessTest.php` -> 8 passed / 41 assertions.
- Browser spot-check passed after the demo image fix: Home, Login, Dashboard, Smart Closet Hub, My Closet, Clothing Detail, AI Search keyword fallback metadata, AI Stylist, Try-on, Runway Video, and Digital Twin all returned HTTP 200 with no local 4xx responses and no console errors.
- Historical 2026-06-05 gate checks passed as expected: `php artisan vogueai:demo-check` -> 0 failed / 1 warning; `php artisan vogueai:github-check` -> 2 blockers / 0 warnings; `git status --short --untracked-files=all` -> 73 changed/untracked entries with no raw warning.
- Current 2026-06-10 gate checks are listed in the Current Pre-Upload Audit above; dirty worktree is now 115 changed/untracked entries.

### 4. Tests

Review status: completed on 2026-06-05. Full Laravel and AI service suites passed.

- `ai_service/tests/`
- `tests/Feature/AiJobsL1Test.php`
- `tests/Feature/AiSearchTest.php`
- `tests/Feature/AiServiceMockModeTest.php`
- `tests/Feature/AiStylistTest.php`
- `tests/Feature/DemoDataTest.php`
- `tests/Feature/DemoReadinessTest.php`
- `tests/Feature/GithubReadinessTest.php`
- `tests/Feature/SmartClosetTest.php`
- `tests/Feature/Auth/*.php`
- `tests/Feature/ExampleTest.php`
- `tests/Feature/ProfileTest.php`

Review focus:
- Full Laravel/Python suites currently pass.
- Auth/Profile/Example tests were converted from closure style to class style.
- Regression tests cover ownership, adapter metadata, degraded fallback, and readiness gates.

Review result:
- No `only`, `skip`, `todo`, `dd`, `dump`, or visible mojibake markers were found in `tests/` or `ai_service/tests/`.
- PHP syntax checks passed for representative high-risk test files: `SmartClosetTest`, `AiStylistTest`, `AiJobsL1Test`, `GithubReadinessTest`, and `DemoDataTest`.
- Python test files passed `py_compile`.
- Historical 2026-06-05 full Laravel suite passed: `.\vendor\bin\pest.bat` -> 73 passed / 370 assertions.
- Historical 2026-06-05 full AI service suite passed: `ai_service\.venv\Scripts\python.exe -m pytest ai_service\tests` -> 36 passed.
- Current 2026-06-09 full regression passed: Laravel 83 passed / 427 assertions, AI Service 39 passed / 1 warning, frontend build passed.

### 5. Project Docs

Review status: completed on 2026-06-05. Current status, L3 definition, and upload blockers are aligned.

- `docs/vogueai-core-progress.md`
- `docs/gemini-stylist-adapter-plan.md`
- `docs/github-upload-review-checklist.md`

Review focus:
- Core progress doc matches the latest verification state.
- Manual browser verification checklist is present.
- GitHub upload still marked blocked until explicit confirmation.

Review result:
- Core progress top-level date and latest verification summary now reflect the current state: Laravel 83 passed / 427 assertions, AI service 39 passed / 1 warning, frontend build passed, demo-check 0 failed / 1 warning, provider-check with services running 0 failed / 0 warnings, real-mode-check with services running 0 failed / 0 warnings, gemini-smoke passed with ready / real_adapter, upload-scope 115 changed/untracked entries with no `.env` or large model artifact risks, github-check 2 blockers / 0 warnings, and raw git status 115 changed/untracked entries with no warning.
- L3 is explicitly defined as adapter contract / fallback / test completion, while real CLIP / BLIP / Qdrant / Gemini / pose provider remains future model integration.
- Gemini adapter plan remains consistent with current behavior: future provider, current mock/degraded contract.
- Upload checklist and core progress both keep GitHub upload blocked until Telescope deletion and worktree scope are explicitly confirmed.

## Suggested Commit Order

1. `ai-service-adapter-contracts`
   - CLIP/BLIP/Qdrant services, AI routes/config/docs, Qdrant launcher, Python tests.

2. `laravel-closet-stylist-workflows`
   - Wear logs, outfit logs, stylist context/feedback, views/routes/models, Laravel feature tests.

3. `demo-readiness-provider-gates`
   - Demo data/readiness services, GitHub gate, provider gate, real-mode gate, `start-all.ps1`, README/env docs, related tests.

4. `localized-ui-and-manual-polish`
   - Blade UI cleanup, localized copy, image sizing/theme polish, auth/account/admin pages.

5. `project-docs-and-roadmap`
   - Core progress tracker, Gemini adapter plan, model integration readiness, upload checklist.

6. `telescope-duplicate-migration-cleanup`
   - Only after explicit user confirmation.

## Final Pre-Push Commands

Run these immediately before any GitHub upload:

```powershell
.\vendor\bin\pest.bat
ai_service\.venv\Scripts\python.exe -m pytest ai_service\tests
php artisan vogueai:demo-check
php artisan vogueai:github-check
git status --short --untracked-files=all
```
