from fastapi.testclient import TestClient

from config import AI_INTERNAL_TOKEN
from main import app


client = TestClient(app)


def auth_headers():
    return {
        "X-Internal-AI-Token": AI_INTERNAL_TOKEN
    }


def test_missing_internal_token_should_fail():
    payload = {
        "schema_version": "v1",
        "request_id": "test_no_token_001",
        "user_id": 1,
        "query": "白色上衣",
    }

    response = client.post("/ai/embed/text", json=payload)

    assert response.status_code == 401

    data = response.json()

    assert data["detail"]["status"] == "failed"
    assert data["detail"]["error"]["code"] == "AI_UNAUTHORIZED"


def test_wrong_internal_token_should_fail():
    payload = {
        "schema_version": "v1",
        "request_id": "test_wrong_token_001",
        "user_id": 1,
        "query": "白色上衣",
    }

    response = client.post(
        "/ai/embed/text",
        json=payload,
        headers={"X-Internal-AI-Token": "wrong_token"},
    )

    assert response.status_code == 401

    data = response.json()

    assert data["detail"]["status"] == "failed"
    assert data["detail"]["error"]["code"] == "AI_UNAUTHORIZED"


def test_empty_text_query_should_fail():
    payload = {
        "schema_version": "v1",
        "request_id": "test_empty_query_001",
        "user_id": 1,
        "query": "   ",
    }

    response = client.post(
        "/ai/embed/text",
        json=payload,
        headers=auth_headers(),
    )

    assert response.status_code == 422

    data = response.json()

    assert data["detail"]["status"] == "failed"
    assert data["detail"]["error"]["code"] == "AI_VALIDATION_ERROR"


def test_invalid_query_type_should_fail():
    payload = {
        "schema_version": "v1",
        "request_id": "test_invalid_query_type_001",
        "user_id": 1,
        "query_type": "audio",
        "query": "白色上衣",
        "embedding": [0.1, 0.2],
        "top_k": 5,
        "filters": {},
    }

    response = client.post(
        "/ai/search/similar",
        json=payload,
        headers=auth_headers(),
    )

    assert response.status_code == 422

    data = response.json()

    assert data["detail"]["status"] == "failed"
    assert data["detail"]["error"]["code"] == "AI_VALIDATION_ERROR"


def test_empty_embedding_should_fail():
    payload = {
        "schema_version": "v1",
        "request_id": "test_empty_embedding_001",
        "user_id": 1,
        "query_type": "text",
        "query": "白色上衣",
        "embedding": [],
        "top_k": 5,
        "filters": {},
    }

    response = client.post(
        "/ai/search/similar",
        json=payload,
        headers=auth_headers(),
    )

    assert response.status_code == 422

    data = response.json()

    assert data["detail"]["status"] == "failed"
    assert data["detail"]["error"]["code"] == "AI_VALIDATION_ERROR"


def test_vector_store_preflight_requires_internal_token():
    response = client.get("/ai/vector-store/preflight")

    assert response.status_code == 401

    data = response.json()

    assert data["detail"]["status"] == "failed"
    assert data["detail"]["error"]["code"] == "AI_UNAUTHORIZED"


def test_vector_store_collection_ensure_requires_internal_token():
    response = client.post("/ai/vector-store/collection/ensure?create_missing=true")

    assert response.status_code == 401

    data = response.json()

    assert data["detail"]["status"] == "failed"
    assert data["detail"]["error"]["code"] == "AI_UNAUTHORIZED"
