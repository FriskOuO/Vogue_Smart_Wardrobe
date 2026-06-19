from fastapi.testclient import TestClient

from main import app
from utils import dependencies


client = TestClient(app)


def test_health_ok():
    response = client.get("/health")

    assert response.status_code == 200

    data = response.json()

    assert data["status"] == "ok"
    assert "service" in data
    assert "mock_mode" in data
    assert "version" in data
    assert "dependencies" in data
    assert "vector_store" in data
    assert data["vector_store"]["target_provider"] == "qdrant"
    assert data["vector_store"]["adapter"] == "qdrant-vector-store-v1"
    assert data["vector_store"]["target_collection"] == "vogueai_clothing_embeddings"
    assert data["vector_store"]["target_url"] == "http://127.0.0.1:6333"
    assert data["vector_store"]["connection_check"] == "not_attempted"
    assert data["vector_store"]["fallback_collection"] == "ai_embeddings"
    assert isinstance(data["vector_store"]["fallback_active"], bool)
    assert "next_steps" in data["vector_store"]


def test_dependency_status_requires_pillow_for_blip(monkeypatch):
    available_packages = {"torch", "transformers"}

    monkeypatch.setattr(
        dependencies,
        "is_package_available",
        lambda package: package in available_packages,
    )

    status = dependencies.get_dependency_status()

    assert status["blip"] == "mock"
    assert status["pillow"] == "missing"
