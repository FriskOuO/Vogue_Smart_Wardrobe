# Gemini Stylist Text Adapter Plan

Last updated: 2026-06-10 Asia/Taipei

## Goal

Prepare AI Stylist text generation for a Gemini provider without making the demo depend on an external API key.

The current implementation keeps demo mode safe by default. When the AI Stylist form is submitted with `provider_mode=real`, Laravel sends `mock_mode=false` to `StylistTextGenerationService` and attempts the Gemini adapter.

If `GEMINI_API_KEY` is missing, the flow records `degraded / real_adapter_attempt / GEMINI_API_KEY_MISSING` and keeps `rule_based_text` fallback. If Gemini returns valid structured JSON, the Stylist History can be recorded as `ready / real_adapter`.

## Current Contract

Laravel service:

- `App\Services\StylistTextGenerationService`

Stored metadata:

- `provider`: configured through `AI_TEXT_GENERATION_PROVIDER`, default `gemini`
- `adapter`: `gemini-stylist-text-v1`
- `status`: `planned`, `degraded`, or `ready`
- `mode`: `mock`, `real_adapter_attempt`, or `real_adapter`
- `model`: configured through `GEMINI_TEXT_MODEL`, default `gemini-2.5-flash`
- `fallback`: `rule_based_text`
- `fallback_active`: boolean
- `degraded_reason`: `GEMINI_TEXT_ADAPTER_NOT_CONNECTED`, `GEMINI_API_KEY_MISSING`, `GEMINI_HTTP_ERROR`, `GEMINI_RESPONSE_PARSE_FAILED`, or `GEMINI_CLIENT_ERROR`

Prompt inputs:

- `context`
- `selected_items`
- `digital_twin_profile`
- `embedding_signals`
- `feedback_history`

Expected structured outputs:

- `title`
- `summary`
- `styling_tips`
- `reasoning_notes`

## Guardrails

- Do not invent closet items outside `selected_items`.
- Keep text concise and safe for a demo screen.
- Return structured JSON so Laravel can persist the result without parsing prose.
- Fall back to local rule-based text if provider credentials, quota, network, or schema validation fail.

## Completed

1. Added a real Gemini REST attempt behind `StylistTextGenerationService`.
2. Added `provider_mode=real` on the AI Stylist form.
3. Added missing-key degraded fallback.
4. Added tests for missing key and fake Gemini success.
5. Added `php artisan vogueai:gemini-smoke` as a safe external smoke gate.
6. External Gemini smoke passed with `ready / real_adapter` using `gemini-2.5-flash`.
7. Cleared `GEMINI_API_KEY` from `.env.example` and kept the key in local `.env` only.

## Remaining

1. Confirm AI Stylist `provider_mode=real` creates `ready / real_adapter` history from the browser.
2. Confirm generated styling copy uses only selected closet items.
3. Persist provider latency and request id if needed.

## Smoke Command

`php artisan vogueai:gemini-smoke` runs a fixed local payload through `StylistTextGenerationService` with `mock_mode=false`.

- It does not print the API key.
- It does not write to the database.
- Without `GEMINI_API_KEY`, it should fail safely with `GEMINI_API_KEY_MISSING`.
- With a valid key and structured Gemini response, it should pass as `ready / real_adapter`.
