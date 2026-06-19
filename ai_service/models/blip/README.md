# BLIP Model

用途：

- 圖片描述
- 衣物照片語意理解
- 後續 Smart Tag / AI Bestie 視覺理解

[ASSUMPTION] 對外 caption model 預設使用 `Salesforce/blip-image-captioning-base`。

目前 API 已保留 `blip-image-caption-v1` adapter contract：

- `target_provider`: `blip`
- `active_provider`: `mock_caption_fallback`
- `target_model`: `Salesforce/blip-image-captioning-base`
- `model_repository`: `Salesforce/blip-image-captioning-base`
- `fallback_active`: `true`
- `adapter_methods.image_caption`: `blip_generate_caption`

目前已新增正式 adapter method：

- `blip_generate_caption(image_path)`

此 method 會在 `torch`、`transformers`、`pillow` 可用時嘗試產生圖片描述。大型依賴放在 `ai_service/requirements-ml.txt`，主 demo flow 仍維持 mock-first fallback。

模型檔不放入 GitHub。
