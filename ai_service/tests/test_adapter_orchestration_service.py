from schemas import AttributesRequest, ImageEmbeddingRequest, TextEmbeddingRequest
from services import adapter_orchestration_service


def test_attributes_response_uses_ready_blip_caption(monkeypatch):
    def fake_blip_generate_caption(image_path, locale):
        return {
            "status": "ready",
            "mode": "real_adapter",
            "image_caption": {
                "target_provider": "blip",
                "active_provider": "blip",
                "adapter": "blip-image-caption-v1",
                "target_model": "Salesforce/blip-image-captioning-base",
                "model_repository": "Salesforce/blip-image-captioning-base",
                "fallback_active": False,
            },
            "caption": "a crisp white shirt",
            "caption_locale": locale,
            "visual_tags": ["shirt", "white"],
            "grounding": {
                "image_path": image_path,
            },
        }

    monkeypatch.setattr(
        adapter_orchestration_service,
        "blip_generate_caption",
        fake_blip_generate_caption,
    )

    payload = AttributesRequest(
        schema_version="v1",
        request_id="test",
        user_id=1,
        clothing_id=9,
        image_path="clothes/9/image.jpg",
        image_url="http://example.test/image.jpg",
        mock_mode=False,
    )

    result = adapter_orchestration_service.attributes_response(payload)

    assert result["mode"] == "hybrid"
    assert result["image_caption"]["active_provider"] == "blip"
    assert result["image_caption"]["caption"] == "a crisp white shirt"
    assert result["image_caption"]["grounding"]["clothing_id"] == 9
    assert result["real_adapter_attempt"]["status"] == "degraded"
    assert result["real_adapter_attempt"]["fallback_required"] is True


def test_image_embedding_response_uses_ready_clip_embedding(monkeypatch):
    embedding = [0.01] * 512

    def fake_clip_embed_image(image_path, model):
        return {
            "status": "ready",
            "mode": "real_adapter",
            "model": "openai/clip-vit-base-patch32",
            "target_model": "clip-vit-base-patch32",
            "embedding_provider": {
                "target_provider": "clip",
                "active_provider": "clip",
                "adapter": "clip-embedding-v1",
                "fallback_active": False,
            },
            "vector_dimension": 512,
            "embedding": embedding,
            "embedding_preview": embedding[:4],
            "dimension_validation": {
                "vector_name": "clip_image",
                "expected_vector_size": 512,
                "actual_vector_size": 512,
                "qdrant_ready": True,
                "fallback_required": False,
                "error_code": None,
            },
        }

    def fake_upsert(**kwargs):
        return {
            "status": "ready",
            "stored": True,
            "point_id": kwargs["clothing_id"],
        }

    monkeypatch.setattr(adapter_orchestration_service, "clip_embed_image", fake_clip_embed_image)
    monkeypatch.setattr(adapter_orchestration_service, "qdrant_upsert_clothing_embedding", fake_upsert)

    payload = ImageEmbeddingRequest(
        schema_version="v1",
        request_id="test",
        user_id=1,
        clothing_id=9,
        image_path="clothes/9/image.jpg",
        image_url="http://example.test/image.jpg",
        mock_mode=False,
    )

    result = adapter_orchestration_service.image_embedding_response(payload)

    assert result["status"] == "ready"
    assert result["mode"] == "real_adapter"
    assert result["vector_dimension"] == 512
    assert result["vector_db"]["provider"] == "qdrant"
    assert result["vector_db"]["collection"] == "vogueai_clothing_embeddings"
    assert result["vector_db"]["stored"] is True
    assert result["vector_db"]["fallback_active"] is False
    assert result["vector_db"]["point_id"] == 9
    assert result["vector_db"]["upsert_result"]["point_id"] == 9
    assert result["real_adapter_attempt"]["status"] == "ready"


def test_text_embedding_response_uses_ready_clip_embedding(monkeypatch):
    embedding = [0.02] * 512

    def fake_clip_embed_text(query, model):
        return {
            "status": "ready",
            "mode": "real_adapter",
            "model": "openai/clip-vit-base-patch32",
            "target_model": "clip-vit-base-patch32",
            "embedding_provider": {
                "target_provider": "clip",
                "active_provider": "clip",
                "adapter": "clip-embedding-v1",
                "fallback_active": False,
            },
            "vector_dimension": 512,
            "normalized_query": query.strip(),
            "embedding": embedding,
            "embedding_preview": embedding[:4],
            "dimension_validation": {
                "vector_name": "clip_text",
                "expected_vector_size": 512,
                "actual_vector_size": 512,
                "qdrant_ready": True,
                "fallback_required": False,
                "error_code": None,
            },
        }

    monkeypatch.setattr(adapter_orchestration_service, "clip_embed_text", fake_clip_embed_text)

    payload = TextEmbeddingRequest(
        schema_version="v1",
        request_id="test",
        user_id=1,
        query=" white shirt ",
        mock_mode=False,
    )

    result = adapter_orchestration_service.text_embedding_response(payload)

    assert result["status"] == "ready"
    assert result["mode"] == "real_adapter"
    assert result["vector_dimension"] == 512
    assert result["normalized_query"] == "white shirt"
    assert result["real_adapter_attempt"]["status"] == "ready"
