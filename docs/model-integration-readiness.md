# VogueAI Model Integration Readiness

Updated: 2026-06-09 Asia/Taipei

This document is the pre-integration gate for moving VogueAI from mock/degraded AI contracts toward real model and provider integrations. It does not approve GitHub upload and does not require changing the current demo-safe `AI_MOCK_MODE=true` setting.

## Provider Environment Activation Status

Updated: 2026-06-09 Asia/Taipei

- ML runtime is installed in `ai_service/.venv`: `torch==2.12.0+cpu`, `transformers==5.10.2`, `pillow==12.2.0`.
- Qdrant client is installed: `qdrant-client==1.18.0`.
- Hugging Face cache is configured inside the project through `AI_MODEL_CACHE_DIR`, defaulting to `ai_service/models/huggingface`.
- CLIP and BLIP model caches are prepared locally.
- CLIP text embedding smoke passed with a 512D vector.
- CLIP image embedding smoke passed with a 512D vector.
- BLIP caption smoke passed with a non-empty caption.
- Qdrant Windows binary is available at `tools/qdrant/runtime/qdrant.exe`, version `1.18.2`.
- Qdrant collection `vogueai_clothing_embeddings` is created and verified.
- Named vectors are `clip_image` and `clip_text`, both 512D with Cosine distance.
- Payload indexes are created for `user_id`, `clothing_id`, `category`, `color`, `season`, `occasion`, and `style_tags`.
- Use `.\start-qdrant.ps1 -NoTelemetry` to start Qdrant manually before connection-enabled smoke tests.
- Demo-safe mode remains active. Keep `AI_MOCK_MODE=true` until the next HTTP/Laravel integration gate passes.

## HTTP And Laravel Adapter Smoke Status

Updated: 2026-06-08 Asia/Taipei

- AI Service HTTP `/ai/embed/text` with `mock_mode=false` returns a real CLIP 512D text embedding.
- AI Service HTTP `/ai/embed/image` with `mock_mode=false` returns a real CLIP 512D image embedding.
- AI Service HTTP `/ai/attributes` with `mock_mode=false` returns a BLIP caption through `real_adapter_attempt=ready`; attribute extraction remains hybrid/mock fallback.
- Laravel `App\Services\AiService` can call the same endpoints with per-request `mock_mode=false` while global `AI_MOCK_MODE=true` remains unchanged.
- Qdrant point ids now use integer `clothing_id`, which is accepted by Qdrant; the clothing id is also preserved in payload.
- Image embedding with `store_to_vector_db=true` can upsert to Qdrant and returns `provider=qdrant`, `stored=true`, and `fallback_active=false`.
- Qdrant search gate passed on 2026-06-09: `/ai/search/similar` calls `qdrant_search_similar_clothing()` when `mock_mode=false` and a 512D CLIP query vector is provided.
- Real search uses Qdrant `query_points(...)` and searches stored clothing `clip_image` vectors with the CLIP query vector.
- Laravel AI Search now accepts `ready` responses and can render Qdrant vector results without falling back unnecessarily.
- AI Search page now has a per-query `provider_mode=real` entry point. It sends `mock_mode=false` to text embedding and similar search without changing global `AI_MOCK_MODE=true`.
- AI Stylist page now has a per-submit `provider_mode=real` entry point. It sends `mock_mode=false` to the Gemini stylist text adapter without changing global `AI_MOCK_MODE=true`.
- Gemini adapter attempt is implemented behind `GEMINI_API_KEY`; without a key it safely records `GEMINI_API_KEY_MISSING` and keeps `rule_based_text` fallback.
- `php artisan vogueai:provider-check` is now the consolidated pre-activation gate. With AI Service and Qdrant running, and after ensuring the collection, the current result is 0 failed / 1 warning.
- The remaining provider warning is `GEMINI_API_KEY` only. If a valid key is provided, run an external Gemini smoke test before treating AI Stylist text generation as fully ready.
- `php artisan vogueai:real-mode-check` is now the consolidated AI Search / AI Stylist real-mode acceptance gate. With AI Service and Qdrant running, and after ensuring the collection, the current result is 0 failed / 0 warnings.
- Full regression gate passed on 2026-06-09: Laravel 83 passed / 427 assertions, AI Service 39 passed / 1 warning, production build passed, demo readiness 0 failed / 1 warning.

## Step 5 Execution Status

Updated: 2026-06-07 Asia/Taipei

- Local lightweight provider dependencies are now partially activated: `qdrant-client==1.18.0` and `pillow==12.2.0` are installed in `ai_service/.venv`.
- Optional heavy ML dependencies remain intentionally unavailable: `torch` and `transformers` are still missing, so CLIP and BLIP stay in mock/degraded mode.
- AI Service HTTP health passed with `mock_mode=true`, `dependencies.pillow=available`, `dependencies.qdrant=available`, `dependencies.clip=mock`, and `dependencies.blip=mock`.
- Qdrant preflight with `check_connection=true` now attempts a real connection and safely degrades with `QDRANT_CONNECTION_FAILED` because no local Qdrant daemon is running.
- Qdrant collection ensure safely degrades with `QDRANT_COLLECTION_ENSURE_FAILED`; no collection was created or verified.
- Internal endpoint header confirmed: use `X-Internal-AI-Token`, not `X-AI-Internal-Token`.
- Demo-safe mode remains active. Do not switch `AI_MOCK_MODE=false` until Qdrant daemon, model dependencies, model cache, and provider credentials are ready.

## Current Decision

- GitHub upload remains skipped.
- Demo mode remains the safe default.
- Real providers should be enabled one at a time, behind explicit environment changes and smoke tests.
- Fallback behavior must remain available until each provider is proven stable with real data.

## Provider Matrix

| Provider | Current Status | Target Adapter | Required Environment | Activation Gate |
| --- | --- | --- | --- | --- |
| CLIP image embedding | Contract ready, fallback active | `clip-embedding-v1` | `torch`, `transformers`, `pillow`, model cache for `openai/clip-vit-base-patch32` | 512D image vector returned and accepted by Qdrant dimension guard |
| CLIP text embedding | Contract ready, fallback active | `clip-embedding-v1` | `torch`, `transformers`, model cache for `openai/clip-vit-base-patch32` | 512D text vector returned and search request remains user-scoped |
| BLIP caption | Contract ready, fallback active | `blip-image-caption-v1` | `torch`, `transformers`, `pillow`, model cache for `Salesforce/blip-image-captioning-base` | non-empty caption returned for demo clothing image |
| Qdrant vector store | Local real gate passed, fallback retained | `qdrant-vector-store-v1` | Qdrant daemon at `VECTOR_STORE_URL`, collection `vogueai_clothing_embeddings`, 512D named vectors | connection-enabled preflight ready and collection exists |
| Gemini text generation | Adapter implemented, external key pending | `gemini-stylist-text-v1` | provider API key and approved model name | structured JSON output matches persisted recommendation contract |
| Pose / Magic Mirror | Mock pose contract ready | future pose provider adapter | pose runtime or external provider credentials | pose quality score, status, checks, and keypoints returned safely |

## Environment Checklist

### Laravel `.env`

```env
AI_SERVICE_URL=http://127.0.0.1:8001
AI_INTERNAL_TOKEN=change_this_internal_ai_token
AI_TIMEOUT_SECONDS=30
AI_MOCK_MODE=true
AI_TEXT_GENERATION_PROVIDER=gemini
GEMINI_TEXT_MODEL=gemini-2.5-flash
GEMINI_API_KEY=
GEMINI_API_BASE_URL=https://generativelanguage.googleapis.com
```

Real adapter attempt starts only after changing:

```env
AI_MOCK_MODE=false
```

Keep `AI_MOCK_MODE=true` for demo, manual QA, and GitHub readiness until real providers pass the gates below.

### AI Service `.env`

```env
AI_MOCK_MODE=true
EMBEDDING_PROVIDER=clip
EMBEDDING_MODEL=clip-vit-base-patch32
EMBEDDING_MODEL_REPOSITORY=openai/clip-vit-base-patch32
VECTOR_STORE_PROVIDER=qdrant
VECTOR_STORE_COLLECTION=vogueai_clothing_embeddings
VECTOR_STORE_URL=http://127.0.0.1:6333
VECTOR_STORE_TARGET_VECTOR_SIZE=512
VECTOR_STORE_ACTIVE_VECTOR_SIZE=8
VECTOR_STORE_DISTANCE=Cosine
IMAGE_CAPTION_PROVIDER=blip
IMAGE_CAPTION_MODEL=Salesforce/blip-image-captioning-base
IMAGE_CAPTION_MODEL_REPOSITORY=Salesforce/blip-image-captioning-base
```

Optional real ML dependencies are intentionally isolated:

```powershell
ai_service\.venv\Scripts\python.exe -m pip install -r ai_service\requirements-ml.txt
```

## Activation Order

1. Install optional ML dependencies in the AI service virtual environment.
2. Run AI service tests while still in mock mode.
3. Verify `/health` reports dependency availability without breaking fallback.
4. Start Qdrant locally and run connection-enabled preflight.
5. Ensure the Qdrant collection with 512D named vectors exists.
6. Enable `AI_MOCK_MODE=false` only for a focused smoke test.
7. Test CLIP image embedding on a demo image.
8. Test CLIP text embedding on a short query.
9. Test BLIP caption on a demo image.
10. Test AI Search with real adapter attempt and confirm it falls back safely if Qdrant is not ready.
11. Test AI Stylist with Gemini only after text generation credentials and structured output are ready.
12. Leave demo mode on unless all focused gates pass.

## Smoke Commands

### Baseline

```powershell
php artisan vogueai:demo-check
php artisan vogueai:provider-check
.\vendor\bin\pest.bat
ai_service\.venv\Scripts\python.exe -m pytest ai_service\tests
```

### AI Service Health

```powershell
Invoke-WebRequest -UseBasicParsing http://127.0.0.1:8001/health
```

Expected before real providers:

- `mock_mode=true`
- CLIP / BLIP dependencies may be `missing`
- vector store fallback remains active

### Qdrant Preflight

Use the configured internal token.

```powershell
Invoke-WebRequest -UseBasicParsing `
  -Headers @{ "X-Internal-AI-Token" = "change_this_internal_ai_token" } `
  "http://127.0.0.1:8001/ai/vector-store/preflight?check_connection=true"
```

Pass condition:

- `status=ready`
- `active_provider=qdrant`
- `fallback_active=false`
- collection exists

### Collection Ensure

```powershell
Invoke-WebRequest -UseBasicParsing `
  -Method Post `
  -Headers @{ "X-Internal-AI-Token" = "change_this_internal_ai_token" } `
  "http://127.0.0.1:8001/ai/vector-store/collection/ensure?create_missing=true"
```

Pass condition:

- named vectors include `clip_image` and `clip_text`
- both vectors are 512D
- distance is `Cosine`

## Provider Gates

### CLIP

Pass when:

- `torch` and `transformers` are available.
- `pillow` is available for image embedding.
- `clip_embed_image()` returns 512 dimensions for `public/images/demo/white-shirt.jpg`.
- `clip_embed_text()` returns 512 dimensions for a query such as `白色上衣`.
- Qdrant rejects non-512D vectors before touching the store.

Keep fallback if:

- dependencies are missing,
- model cache is unavailable,
- vector dimension is not 512,
- Qdrant is not ready.

### BLIP

Pass when:

- `torch`, `transformers`, and `pillow` are available.
- `blip_generate_caption()` returns a non-empty caption for a demo image.
- response includes `image_caption.adapter=blip-image-caption-v1`.

Keep fallback if:

- dependencies are missing,
- caption is empty,
- model load fails.

### Qdrant

Pass when:

- daemon is reachable at `VECTOR_STORE_URL`.
- collection `vogueai_clothing_embeddings` exists.
- named vectors are `clip_image` and `clip_text`.
- both named vectors use 512 dimensions and cosine distance.
- user-scoped payload filters are preserved during search.

Keep fallback if:

- daemon is unreachable,
- collection is missing,
- vector dimensions do not match,
- search returns no owned clothing.

### Gemini

Pass when:

- credentials are configured outside Git.
- the provider returns structured JSON.
- output includes `title`, `summary`, `styling_tips`, and optional `reasoning_notes`.
- the recommendation does not invent closet items outside `selected_items`.

Keep fallback if:

- provider key is missing,
- response is prose-only,
- JSON schema validation fails,
- external request times out.

### Pose Provider

Pass when:

- response includes `pose_quality_score`, `pose_quality_status`, `quality_checks`, and keypoints.
- low-quality image input returns a safe review status instead of crashing.
- Try-on UI remains usable when provider fails.

Keep fallback if:

- provider runtime is missing,
- image quality is insufficient,
- keypoints are incomplete,
- provider response is slow or malformed.

## Do Not Proceed If

- `.env` or provider keys appear in `git status`.
- large model files appear in `git status`.
- Qdrant is active while CLIP still returns 8D mock vectors.
- `AI_MOCK_MODE=false` breaks demo pages.
- fallback metadata disappears from UI.

## Completion Criteria For Step 4

- Provider matrix documented.
- Environment variables documented.
- Activation order documented.
- Smoke commands documented.
- Provider gates documented.
- Fallback rules documented.
- Core progress tracker updated.

## Completion Criteria For Step 5 Local Pre-Activation

- `qdrant-client` and `pillow` installed in the AI service venv.
- AI Service full test suite passes after the dependency status changed.
- `/health` confirms qdrant and pillow availability while CLIP/BLIP remain safely mocked.
- Qdrant preflight and collection ensure attempt real provider paths and degrade safely when the daemon is unavailable.
- Core progress tracker and manual checklist updated.

## Completion Criteria For Provider Gate Startup Validation

- Qdrant starts locally and responds through the configured `VECTOR_STORE_URL`.
- AI Service starts locally and `/health` responds with `status=ok`.
- Qdrant collection ensure returns `ready`.
- `php artisan vogueai:provider-check` returns 0 failed / 1 warning.
- The only remaining warning is empty `GEMINI_API_KEY`.
- Demo readiness remains 0 failed / 1 warning.

## Completion Criteria For Real Mode Acceptance Gate

- `php artisan vogueai:real-mode-check` is configured.
- AI Search real mode route, UI selector, and per-query `mock_mode=false` wiring are checked.
- AI Search real mode feature coverage is checked.
- AI Stylist real mode route, UI selector, missing-key fallback, and ready Gemini response coverage are checked.
- Without AI Service and Qdrant running, the gate returns 0 failed / 1 warning.
- With AI Service and Qdrant running, and after ensuring the collection, the gate returns 0 failed / 0 warnings.

## Completion Criteria For Full Regression Gate

- Laravel full Pest suite passes.
- AI Service full pytest suite passes.
- Frontend production build passes.
- Demo readiness remains 0 failed / 1 warning or better.
- Provider check reaches 0 failed / 1 warning when AI Service and Qdrant are running.
- Real mode check reaches 0 failed / 0 warnings when AI Service and Qdrant are running.
- GitHub readiness still blocks upload until the dirty worktree and Telescope migration deletion are intentionally reviewed.

Remaining external requirements before full real provider activation:

- Configure real Gemini credentials if text generation is enabled.
- Run an external Gemini smoke test and verify `ready / real_adapter` AI Stylist history.
- Run focused manual AI Search and AI Stylist real-mode acceptance before changing demo mode.
