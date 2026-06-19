# CLIP Model

用途：

- image embedding
- text embedding
- 以圖搜圖
- 以文搜圖
- AI Stylist 候選衣物召回

[ASSUMPTION] 對外 model 名稱保留 `clip-vit-base-patch32`，實際 Hugging Face repository 預設使用 `openai/clip-vit-base-patch32`。

目前 API 已保留 `clip-embedding-v1` adapter contract：

- `target_provider`: `clip`
- `active_provider`: `mock_embedding_fallback`
- `target_model`: `clip-vit-base-patch32`
- `model_repository`: `openai/clip-vit-base-patch32`
- `fallback_active`: `true`
- `degraded_reason`: `CLIP_ADAPTER_NOT_CONNECTED`

目前已新增正式 adapter method：

- `clip_embed_image(image_path)`
- `clip_embed_text(query)`

這兩個 method 會在 `torch`、`transformers`、`pillow` 可用時嘗試產生 512D vector，並用 dimension validation 確認可安全接到 Qdrant `clip_image` / `clip_text` named vectors。大型依賴放在 `ai_service/requirements-ml.txt`，主 demo flow 仍維持 mock-first fallback。

真實 CLIP 接入前，不應把大型模型檔或 cache 放入 Git。

模型檔不放入 GitHub。
