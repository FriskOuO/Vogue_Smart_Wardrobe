from services import clip_embedding_service
from config import EMBEDDING_MODEL_REPOSITORY


class FakeNoGrad:
    def __enter__(self):
        return None

    def __exit__(self, exc_type, exc, traceback):
        return False


class FakeProcessor:
    def __call__(self, **kwargs):
        return {
            "fake_inputs": kwargs,
        }


class FakeModel:
    def __init__(self):
        self.text_inputs = None
        self.image_inputs = None

    def get_text_features(self, **inputs):
        self.text_inputs = inputs
        return [[1.0] * 512]

    def get_image_features(self, **inputs):
        self.image_inputs = inputs
        return [[2.0] * 512]


def test_clip_embed_text_success_path(monkeypatch):
    fake_model = FakeModel()

    monkeypatch.setattr(clip_embedding_service, "is_package_available", lambda package: True)
    monkeypatch.setattr(clip_embedding_service, "clip_model_bundle", lambda model=None: (fake_model, FakeProcessor()))
    monkeypatch.setattr(clip_embedding_service, "torch_no_grad", lambda: FakeNoGrad())

    result = clip_embedding_service.clip_embed_text(" white shirt ")

    assert result["status"] == "ready"
    assert result["mode"] == "real_adapter"
    assert result["embedding_type"] == "text"
    assert result["model"] == EMBEDDING_MODEL_REPOSITORY
    assert result["normalized_query"] == "white shirt"
    assert result["vector_dimension"] == 512
    assert result["dimension_validation"]["qdrant_ready"] is True
    assert result["fallback_required"] is False
    assert result["embedding_provider"]["active_provider"] == "clip"
    assert len(result["embedding"]) == 512
    assert len(result["embedding_preview"]) == 4


def test_clip_embed_image_success_path(monkeypatch):
    fake_model = FakeModel()

    monkeypatch.setattr(clip_embedding_service, "is_package_available", lambda package: True)
    monkeypatch.setattr(clip_embedding_service, "clip_model_bundle", lambda model=None: (fake_model, FakeProcessor()))
    monkeypatch.setattr(clip_embedding_service, "clip_open_image", lambda image_path: "fake-image")
    monkeypatch.setattr(clip_embedding_service, "torch_no_grad", lambda: FakeNoGrad())

    result = clip_embedding_service.clip_embed_image("clothes/7/image.jpg")

    assert result["status"] == "ready"
    assert result["mode"] == "real_adapter"
    assert result["embedding_type"] == "image"
    assert result["image_path"] == "clothes/7/image.jpg"
    assert result["vector_dimension"] == 512
    assert result["dimension_validation"]["vector_name"] == "clip_image"
    assert result["dimension_validation"]["qdrant_ready"] is True
    assert result["fallback_required"] is False
    assert result["embedding_provider"]["active_provider"] == "clip"
    assert len(result["embedding"]) == 512


def test_clip_embed_text_missing_dependencies_is_degraded(monkeypatch):
    monkeypatch.setattr(clip_embedding_service, "is_package_available", lambda package: False)

    result = clip_embedding_service.clip_embed_text("white shirt")

    assert result["status"] == "degraded"
    assert result["embedding_type"] == "text"
    assert result["vector_dimension"] == 0
    assert result["embedding"] == []
    assert result["fallback_required"] is True
    assert result["error_code"] == "CLIP_DEPENDENCIES_NOT_INSTALLED"
    assert result["embedding_provider"]["fallback_active"] is True


def test_clip_dimension_validation_rejects_wrong_size():
    result = clip_embedding_service.clip_dimension_validation(
        vector=[0.1] * 8,
        vector_name="clip_text",
    )

    assert result["expected_vector_size"] == 512
    assert result["actual_vector_size"] == 8
    assert result["qdrant_ready"] is False
    assert result["fallback_required"] is True
    assert result["error_code"] == "CLIP_VECTOR_DIMENSION_MISMATCH"
