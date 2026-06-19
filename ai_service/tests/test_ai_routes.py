from fastapi.testclient import TestClient
import gradio_client
import time

from config import AI_INTERNAL_TOKEN, EMBEDDING_MODEL_REPOSITORY, IMAGE_CAPTION_MODEL_REPOSITORY
from main import app
from routes import ai_routes
from services import huggingface_idm_vton_service
from services import vector_store_service


client = TestClient(app)


def auth_headers():
    return {
        "X-Internal-AI-Token": AI_INTERNAL_TOKEN
    }


def test_ai_attributes_degraded():
    payload = {
        "schema_version": "v1",
        "request_id": "test_attributes_001",
        "user_id": 1,
        "clothing_id": 1,
        "image_path": "clothes/1/test.jpg",
        "image_url": "http://127.0.0.1:8000/storage/clothes/1/test.jpg",
    }

    response = client.post(
        "/ai/attributes",
        json=payload,
        headers=auth_headers(),
    )

    assert response.status_code == 200

    data = response.json()

    assert data["status"] == "degraded"
    assert data["mode"] == "mock"
    assert data["clothing_id"] == 1
    assert "attributes" in data
    assert "confidence" in data
    assert data["image_caption"]["target_provider"] == "blip"
    assert data["image_caption"]["active_provider"] == "mock_caption_fallback"
    assert data["image_caption"]["adapter"] == "blip-image-caption-v1"
    assert data["image_caption"]["model_repository"] == IMAGE_CAPTION_MODEL_REPOSITORY
    assert data["image_caption"]["adapter_methods"]["image_caption"] == "blip_generate_caption"
    assert data["image_caption"]["fallback_active"] is True


def test_ai_attributes_real_adapter_fallback_when_adapter_unavailable():
    payload = {
        "schema_version": "v1",
        "request_id": "test_attributes_real_fallback_001",
        "user_id": 1,
        "clothing_id": 1,
        "image_path": "clothes/1/test.jpg",
        "image_url": "http://127.0.0.1:8000/storage/clothes/1/test.jpg",
        "mock_mode": False,
    }

    response = client.post(
        "/ai/attributes",
        json=payload,
        headers=auth_headers(),
    )

    assert response.status_code == 200

    data = response.json()

    assert data["status"] == "degraded"
    assert data["mode"] == "mock"
    assert data["image_caption"]["active_provider"] == "mock_caption_fallback"
    assert data["real_adapter_attempt"]["status"] == "degraded"
    assert data["real_adapter_attempt"]["error_code"] in [
        "BLIP_DEPENDENCIES_NOT_INSTALLED",
        "BLIP_CAPTION_FAILED",
        "FASHION_ATTRIBUTE_FAILED",
        "ATTRIBUTE_DEPENDENCIES_NOT_INSTALLED",
    ]


def test_ai_embed_image_degraded():
    payload = {
        "schema_version": "v1",
        "request_id": "test_embed_image_001",
        "user_id": 1,
        "clothing_id": 1,
        "image_path": "clothes/1/test.jpg",
        "image_url": "http://127.0.0.1:8000/storage/clothes/1/test.jpg",
    }

    response = client.post(
        "/ai/embed/image",
        json=payload,
        headers=auth_headers(),
    )

    assert response.status_code == 200

    data = response.json()

    assert data["status"] == "degraded"
    assert data["mode"] == "mock"
    assert data["embedding_type"] == "image"
    assert data["vector_dimension"] == 8
    assert "embedding" in data
    assert data["target_model"] == "clip-vit-base-patch32"
    assert data["embedding_provider"]["target_provider"] == "clip"
    assert data["embedding_provider"]["active_provider"] == "mock_embedding_fallback"
    assert data["embedding_provider"]["adapter"] == "clip-embedding-v1"
    assert data["embedding_provider"]["model_repository"] == EMBEDDING_MODEL_REPOSITORY
    assert data["embedding_provider"]["adapter_methods"]["image"] == "clip_embed_image"
    assert data["embedding_provider"]["fallback_active"] is True
    assert "vector_db" in data
    assert data["vector_db"]["target_provider"] == "qdrant"
    assert data["vector_db"]["target_url"] == "http://127.0.0.1:6333"
    assert data["vector_db"]["target_vector_size"] == 512
    assert data["vector_db"]["active_vector_size"] == 8
    assert data["vector_db"]["distance"] == "Cosine"
    assert data["vector_db"]["collection_schema"]["named_vectors"]["clip_image"]["size"] == 512
    assert data["vector_db"]["collection_plan"]["dry_run"] is True
    assert data["vector_db"]["collection_plan"]["vectors_config"]["clip_image"]["size"] == 512
    assert data["vector_db"]["adapter_methods"]["ensure_collection"] == "qdrant_ensure_collection(create_missing=True)"
    assert data["vector_db"]["upsert_plan"]["operation"] == "upsert_clothing_embedding"
    assert data["vector_db"]["upsert_plan"]["vector_name"] == "clip_image"
    assert data["vector_db"]["upsert_plan"]["payload_template"]["source"] == "vogueai_clothing"
    assert "Only upsert 512D CLIP image vectors into clip_image." in data["vector_db"]["upsert_plan"]["guardrails"]
    assert data["vector_db"]["dimension_validation"]["vector_name"] == "clip_image"
    assert data["vector_db"]["dimension_validation"]["expected_vector_size"] == 512
    assert data["vector_db"]["dimension_validation"]["actual_vector_size"] == 8
    assert data["vector_db"]["dimension_validation"]["qdrant_ready"] is False
    assert data["vector_db"]["dimension_validation"]["fallback_required"] is True
    assert data["vector_db"]["dimension_validation"]["error_code"] == "VECTOR_DIMENSION_MISMATCH"
    assert data["vector_db"]["provider"] == "mock_sqlite_fallback"
    assert data["vector_db"]["fallback_active"] is True


def test_ai_embed_image_real_adapter_fallback_when_adapter_unavailable():
    payload = {
        "schema_version": "v1",
        "request_id": "test_embed_image_real_fallback_001",
        "user_id": 1,
        "clothing_id": 1,
        "image_path": "clothes/1/test.jpg",
        "image_url": "http://127.0.0.1:8000/storage/clothes/1/test.jpg",
        "mock_mode": False,
    }

    response = client.post(
        "/ai/embed/image",
        json=payload,
        headers=auth_headers(),
    )

    assert response.status_code == 200

    data = response.json()

    assert data["status"] == "degraded"
    assert data["mode"] == "mock"
    assert data["embedding_provider"]["active_provider"] == "mock_embedding_fallback"
    assert data["real_adapter_attempt"]["status"] == "degraded"
    assert data["real_adapter_attempt"]["error_code"] in [
        "CLIP_DEPENDENCIES_NOT_INSTALLED",
        "CLIP_IMAGE_EMBEDDING_FAILED",
    ]


def test_ai_embed_text_degraded():
    payload = {
        "schema_version": "v1",
        "request_id": "test_embed_text_001",
        "user_id": 1,
        "query": "白色上衣",
    }

    response = client.post(
        "/ai/embed/text",
        json=payload,
        headers=auth_headers(),
    )

    assert response.status_code == 200

    data = response.json()

    assert data["status"] == "degraded"
    assert data["mode"] == "mock"
    assert data["embedding_type"] == "text"
    assert data["vector_dimension"] == 8
    assert data["normalized_query"] == "白色上衣"
    assert data["target_model"] == "clip-vit-base-patch32"
    assert data["embedding_provider"]["target_provider"] == "clip"
    assert data["embedding_provider"]["active_provider"] == "mock_embedding_fallback"
    assert data["embedding_provider"]["adapter"] == "clip-embedding-v1"
    assert data["embedding_provider"]["model_repository"] == EMBEDDING_MODEL_REPOSITORY
    assert data["embedding_provider"]["adapter_methods"]["text"] == "clip_embed_text"
    assert data["embedding_provider"]["fallback_active"] is True
    assert "embedding" in data


def test_ai_embed_text_real_adapter_fallback_when_adapter_unavailable():
    payload = {
        "schema_version": "v1",
        "request_id": "test_embed_text_real_fallback_001",
        "user_id": 1,
        "query": "白色上衣",
        "model": "missing-local-clip-model",
        "mock_mode": False,
    }

    response = client.post(
        "/ai/embed/text",
        json=payload,
        headers=auth_headers(),
    )

    assert response.status_code == 200

    data = response.json()

    assert data["status"] == "degraded"
    assert data["mode"] == "mock"
    assert data["embedding_provider"]["active_provider"] == "mock_embedding_fallback"
    assert data["real_adapter_attempt"]["status"] == "degraded"
    assert data["real_adapter_attempt"]["error_code"] in [
        "CLIP_DEPENDENCIES_NOT_INSTALLED",
        "CLIP_TEXT_EMBEDDING_FAILED",
    ]


def test_ai_search_similar_degraded():
    payload = {
        "schema_version": "v1",
        "request_id": "test_search_similar_001",
        "user_id": 1,
        "query_type": "text",
        "query": "白色上衣",
        "embedding": [0.20, -0.11, 0.07, 0.18, 0.03, -0.04, 0.09, 0.14],
        "top_k": 5,
        "filters": {},
    }

    response = client.post(
        "/ai/search/similar",
        json=payload,
        headers=auth_headers(),
    )

    assert response.status_code == 200

    data = response.json()

    assert data["status"] == "degraded"
    assert data["mode"] == "mock"
    assert data["query_type"] == "text"
    assert data["search_provider"] == "mock_sqlite_fallback"
    assert data["target_search_provider"] == "qdrant"
    assert data["query_model"] == "mock-text-embedding"
    assert data["target_query_model"] == "clip-vit-base-patch32"
    assert data["embedding_provider"]["target_provider"] == "clip"
    assert data["embedding_provider"]["adapter"] == "clip-embedding-v1"
    assert data["embedding_provider"]["fallback_active"] is True
    assert data["vector_store"]["adapter"] == "qdrant-vector-store-v1"
    assert data["vector_store"]["target_url"] == "http://127.0.0.1:6333"
    assert data["vector_store"]["target_vector_size"] == 512
    assert data["vector_store"]["active_vector_size"] == 8
    assert data["vector_store"]["distance"] == "Cosine"
    assert data["vector_store"]["collection_schema"]["named_vectors"]["clip_text"]["size"] == 512
    assert data["vector_store"]["collection_plan"]["operation"] == "create_or_verify_collection"
    assert data["vector_store"]["collection_plan"]["dry_run"] is True
    assert data["vector_store"]["search_plan"]["operation"] == "search_similar_clothing"
    assert data["vector_store"]["search_plan"]["query_vector_name"] == "clip_image"
    assert data["vector_store"]["search_plan"]["filter_template"]["must"][0]["key"] == "user_id"
    assert data["dimension_validation"]["vector_name"] == "clip_text"
    assert data["dimension_validation"]["expected_vector_size"] == 512
    assert data["dimension_validation"]["actual_vector_size"] == 8
    assert data["dimension_validation"]["qdrant_ready"] is False
    assert data["vector_store"]["dimension_validation"]["vector_name"] == "clip_text"
    assert data["vector_store"]["dimension_validation"]["fallback_required"] is True
    assert data["vector_store"]["connection_check"] == "not_attempted"
    assert data["vector_store"]["fallback_active"] is True
    assert "results" in data
    assert len(data["results"]) >= 1
    assert data["results"][0]["target_vector_provider"] == "qdrant"
    assert data["results"][0]["match_type"] == "vector_similarity_fallback"


def test_ai_search_similar_uses_qdrant_when_mock_mode_is_disabled(monkeypatch):
    def fake_qdrant_search_similar_clothing(
        user_id,
        query_vector,
        top_k,
        filters,
        vector_name,
    ):
        return {
            "schema_version": "v1",
            "status": "ready",
            "operation": "search_similar_clothing",
            "collection_name": "vogueai_clothing_embeddings",
            "query_vector_name": vector_name,
            "matches": [
                {
                    "id": 12,
                    "score": 0.94,
                    "payload": {
                        "user_id": user_id,
                        "clothing_id": 12,
                        "category": "shirt",
                        "color": "white",
                    },
                },
            ],
            "dimension_validation": {
                "vector_name": vector_name,
                "expected_vector_size": 512,
                "actual_vector_size": len(query_vector),
                "matches_target": True,
                "qdrant_ready": True,
                "fallback_required": False,
                "error_code": None,
                "message": "Vector dimension matches Qdrant target collection.",
            },
            "error_code": None,
            "error_message": None,
        }

    monkeypatch.setattr(
        ai_routes,
        "qdrant_search_similar_clothing",
        fake_qdrant_search_similar_clothing,
    )

    payload = {
        "schema_version": "v1",
        "request_id": "test_search_similar_real_qdrant_001",
        "user_id": 7,
        "query_type": "text",
        "query": "white shirt",
        "embedding": [0.02] * 512,
        "top_k": 3,
        "filters": {
            "category": "shirt",
        },
        "mock_mode": False,
    }

    response = client.post(
        "/ai/search/similar",
        json=payload,
        headers=auth_headers(),
    )

    assert response.status_code == 200

    data = response.json()

    assert data["status"] == "ready"
    assert data["mode"] == "real_adapter"
    assert data["search_provider"] == "qdrant"
    assert data["target_search_provider"] == "qdrant"
    assert data["query_model"] == "clip-vit-base-patch32"
    assert data["embedding_provider"]["active_provider"] == "clip"
    assert data["embedding_provider"]["fallback_active"] is False
    assert data["vector_store"]["active_provider"] == "qdrant"
    assert data["vector_store"]["fallback_active"] is False
    assert data["dimension_validation"]["actual_vector_size"] == 512
    assert data["results"][0]["clothing_id"] == 12
    assert data["results"][0]["score"] == 0.94
    assert data["results"][0]["vector_provider"] == "qdrant"
    assert data["results"][0]["match_type"] == "qdrant_vector_similarity"


def test_vector_store_preflight_degraded():
    response = client.get(
        "/ai/vector-store/preflight",
        headers=auth_headers(),
    )

    assert response.status_code == 200

    data = response.json()

    assert data["status"] == "degraded"
    assert data["mode"] == "preflight"
    assert data["vector_store"]["target_provider"] == "qdrant"
    assert data["vector_store"]["active_provider"] == "mock_sqlite_fallback"
    assert data["vector_store"]["adapter"] == "qdrant-vector-store-v1"
    assert data["vector_store"]["target_url"] == "http://127.0.0.1:6333"
    assert data["vector_store"]["target_collection"] == "vogueai_clothing_embeddings"
    assert data["vector_store"]["target_vector_size"] == 512
    assert data["vector_store"]["active_vector_size"] == 8
    assert data["vector_store"]["distance"] == "Cosine"
    assert data["vector_store"]["collection_schema"]["collection_name"] == "vogueai_clothing_embeddings"
    assert data["vector_store"]["collection_schema"]["named_vectors"]["clip_image"]["size"] == 512
    assert data["vector_store"]["collection_schema"]["named_vectors"]["clip_text"]["distance"] == "Cosine"
    assert data["collection_plan"]["dry_run"] is True
    assert data["collection_plan"]["vectors_config"]["clip_image"]["distance"] == "Cosine"
    assert data["collection_plan"]["vectors_config"]["clip_text"]["size"] == 512
    assert data["collection_plan"]["payload_index_plan"][0]["field_name"] == "user_id"
    assert "Do not upsert mock 8D embeddings into the 512D target collection." in data["collection_plan"]["activation_guardrails"]
    assert data["upsert_plan"]["client_method"] == "upsert"
    assert data["upsert_plan"]["point_id_template"] == "<clothing_id>"
    assert data["search_plan"]["client_method"] == "query_points"
    assert data["search_plan"]["filter_template"]["must"][0]["match"]["value"] == "<current_user_id>"
    assert data["adapter_methods"]["activation_mode"] == "manual_internal_endpoint"
    assert "Collection creation is never triggered by health checks." in data["adapter_methods"]["safety"]
    assert data["dimension_validation"]["expected_vector_size"] == 512
    assert data["dimension_validation"]["actual_vector_size"] == 8
    assert data["dimension_validation"]["fallback_required"] is True
    assert data["vector_store"]["fallback_collection"] == "ai_embeddings"
    assert data["vector_store"]["fallback_active"] is True
    assert data["vector_store"]["connection_check"] == "not_attempted"
    assert data["readiness"]["fallback_safe"] is True
    assert data["readiness"]["collection_required"] == "vogueai_clothing_embeddings"
    assert data["readiness"]["connected"] is False
    assert data["readiness"]["collection_exists"] is False
    assert data["connection"]["connection_check"] == "not_attempted"
    assert "next_steps" in data


def test_vector_store_preflight_connection_check_degrades_without_daemon(monkeypatch):
    monkeypatch.setattr(vector_store_service, "is_qdrant_client_available", lambda: True)
    monkeypatch.setattr(
        vector_store_service,
        "qdrant_client_instance",
        lambda: (_ for _ in ()).throw(RuntimeError("forced qdrant connection failure")),
    )

    response = client.get(
        "/ai/vector-store/preflight?check_connection=true",
        headers=auth_headers(),
    )

    assert response.status_code == 200

    data = response.json()

    assert data["status"] == "degraded"
    assert data["vector_store"]["target_provider"] == "qdrant"
    assert data["vector_store"]["connection_check"] == "attempted"
    assert data["readiness"]["can_attempt_connection"] is True
    assert data["readiness"]["connected"] is False
    assert data["readiness"]["collection_exists"] is False
    assert data["connection"]["connection_check"] == "attempted"
    assert data["connection"]["connected"] is False
    assert data["connection"]["collection_exists"] is False
    assert data["connection"]["error_code"] == "QDRANT_CONNECTION_FAILED"


def test_vector_store_collection_ensure_degrades_without_daemon(monkeypatch):
    monkeypatch.setattr(vector_store_service, "is_qdrant_client_available", lambda: True)
    monkeypatch.setattr(
        vector_store_service,
        "qdrant_client_instance",
        lambda: (_ for _ in ()).throw(RuntimeError("forced qdrant connection failure")),
    )

    response = client.post(
        "/ai/vector-store/collection/ensure?create_missing=true",
        headers=auth_headers(),
    )

    assert response.status_code == 200

    data = response.json()

    assert data["status"] == "degraded"
    assert data["operation"] == "ensure_collection"
    assert data["collection_name"] == "vogueai_clothing_embeddings"
    assert data["created"] is False
    assert data["verified"] is False
    assert data["fallback_safe"] is True
    assert data["error_code"] == "QDRANT_COLLECTION_ENSURE_FAILED"
    assert data["collection_plan"]["operation"] == "create_or_verify_collection"


def test_ai_pose_degraded():
    payload = {
        "schema_version": "v1",
        "request_id": "test_pose_001",
        "user_id": 1,
        "image_path": "tryon/1/full_body.jpg",
        "image_url": "http://127.0.0.1:8000/storage/tryon/1/full_body.jpg",
        "task_type": "magic_mirror",
    }

    response = client.post(
        "/ai/pose",
        json=payload,
        headers=auth_headers(),
    )

    assert response.status_code == 200

    data = response.json()

    assert data["status"] == "degraded"
    assert data["mode"] == "mock"
    assert data["pose_model"] == "mock-pose"
    assert "keypoints" in data
    assert "pose_analysis" in data
    assert data["pose_quality_score"] == 0.86
    assert data["pose_quality_status"] == "usable"
    assert data["quality_checks"]["full_body_visible"]["passed"] is True
    assert data["quality_checks"]["shoulders_detected"]["passed"] is True
    assert data["quality_checks"]["hips_detected"]["passed"] is True
    assert data["pose_analysis"]["pose_quality_score"] == data["pose_quality_score"]


def test_tryon_generate_degrades_in_mock_mode():
    payload = {
        "schema_version": "v1",
        "request_id": "test_tryon_mock_001",
        "user_id": 1,
        "model": "idm-vton",
        "person_image_url": "https://example.com/person.jpg",
        "clothing_image_url": "https://example.com/clothing.jpg",
        "pose_analysis": {
            "pose_quality_score": 0.82,
        },
        "mock_mode": True,
    }

    response = client.post(
        "/tryon/generate",
        json=payload,
        headers=auth_headers(),
    )

    assert response.status_code == 200

    data = response.json()

    assert data["status"] == "degraded"
    assert data["mode"] == "mock"
    assert data["provider"] == "huggingface_idm_vton"
    assert data["error_code"] == "HF_IDM_VTON_MOCK_MODE"
    assert data["output_url"] is None


def test_tryon_generate_requires_person_image_url():
    payload = {
        "schema_version": "v1",
        "request_id": "test_tryon_validation_001",
        "user_id": 1,
        "model": "idm-vton",
        "clothing_image_url": "https://example.com/clothing.jpg",
        "pose_analysis": {},
    }

    response = client.post(
        "/tryon/generate",
        json=payload,
        headers=auth_headers(),
    )

    assert response.status_code == 422


def test_tryon_generate_requires_clothing_image_url():
    payload = {
        "schema_version": "v1",
        "request_id": "test_tryon_validation_002",
        "user_id": 1,
        "model": "idm-vton",
        "person_image_url": "https://example.com/person.jpg",
        "pose_analysis": {},
    }

    response = client.post(
        "/tryon/generate",
        json=payload,
        headers=auth_headers(),
    )

    assert response.status_code == 422


def test_tryon_generate_success_can_be_polled(monkeypatch, tmp_path):
    source = tmp_path / "result.png"
    source.write_bytes(b"fake-png")

    monkeypatch.setattr(huggingface_idm_vton_service, "AI_MOCK_MODE", False)
    monkeypatch.setattr(
        huggingface_idm_vton_service,
        "_call_huggingface_idm_vton",
        lambda payload: [str(source)],
    )
    monkeypatch.setattr(
        huggingface_idm_vton_service,
        "_output_dir",
        lambda: tmp_path / "static" / "tryon",
    )

    payload = {
        "schema_version": "v1",
        "request_id": "test_tryon_success_001",
        "user_id": 1,
        "model": "idm-vton",
        "person_image_url": "https://example.com/person.jpg",
        "clothing_image_url": "https://example.com/clothing.jpg",
        "pose_analysis": {},
        "mock_mode": False,
    }

    response = client.post(
        "/tryon/generate",
        json=payload,
        headers=auth_headers(),
    )

    assert response.status_code == 200

    created = response.json()

    assert created["status"] == "processing"
    assert created["mode"] == "huggingface_space"
    assert created["provider_task_id"].startswith("local_hf_tryon_")

    task_id = created["provider_task_id"]
    status_response = None
    for _ in range(20):
        status_response = client.get(
            f"/tryon/status/{task_id}",
            headers=auth_headers(),
        )
        if status_response.json()["status"] == "success":
            break
        time.sleep(0.01)

    assert status_response is not None
    assert status_response.status_code == 200

    data = status_response.json()

    assert data["status"] == "success"
    assert data["mode"] == "huggingface_space"
    assert data["provider"] == "huggingface_idm_vton"
    assert data["output_url"].endswith(f"/static/tryon/{task_id}.png")


def test_tryon_provider_maps_localhost_image_url_to_local_file():
    mapped = huggingface_idm_vton_service._gradio_file_input(
        "http://127.0.0.1:8000/images/demo/white-shirt.jpg"
    )

    assert mapped.endswith("public\\images\\demo\\white-shirt.jpg") or mapped.endswith("public/images/demo/white-shirt.jpg")


def test_tryon_provider_maps_storage_relative_url_to_laravel_file(monkeypatch, tmp_path):
    ai_service_dir = tmp_path / "ai_service"
    clothing_file = tmp_path / "storage" / "app" / "public" / "clothes" / "item.jpg"
    clothing_file.parent.mkdir(parents=True)
    clothing_file.write_bytes(b"fake-image")
    monkeypatch.setattr(huggingface_idm_vton_service, "BASE_DIR", ai_service_dir)

    mapped = huggingface_idm_vton_service._gradio_file_input(
        "/storage/clothes/item.jpg"
    )

    assert mapped == str(clothing_file)


def test_tryon_provider_extracts_first_image_from_nested_tuple(tmp_path):
    output = tmp_path / "result.png"
    output.write_bytes(b"fake-image")

    extracted = huggingface_idm_vton_service._extract_result_path(
        ([{"path": str(output)}], None)
    )

    assert extracted == output


def test_tryon_provider_passes_hugging_face_token_with_supported_keyword(monkeypatch):
    captured = {}

    class FakeClient:
        def __init__(self, space, **kwargs):
            captured["space"] = space
            captured["kwargs"] = kwargs

        def predict(self, **kwargs):
            captured["predict"] = kwargs
            return ["result.png"]

    monkeypatch.setattr(gradio_client, "Client", FakeClient)
    monkeypatch.setattr(gradio_client, "handle_file", lambda value: value)
    monkeypatch.setattr(huggingface_idm_vton_service, "TRYON_API_TOKEN", "hf_test_token")

    payload = huggingface_idm_vton_service.TryOnGenerateRequest(
        request_id="test_tryon_token_001",
        user_id=1,
        model="idm-vton",
        person_image_url="https://example.com/person.jpg",
        clothing_image_url="https://example.com/clothing.jpg",
        pose_analysis={},
    )

    huggingface_idm_vton_service._call_huggingface_idm_vton(payload)

    assert captured["kwargs"] == {"token": "hf_test_token"}
