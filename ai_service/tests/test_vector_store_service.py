from types import SimpleNamespace

from services import vector_store_service


class FakeDistance:
    COSINE = "Cosine"


class FakePayloadSchemaType:
    KEYWORD = "keyword"


class FakeVectorParams:
    def __init__(self, size, distance):
        self.size = size
        self.distance = distance


class FakePointStruct:
    def __init__(self, id, vector, payload):
        self.id = id
        self.vector = vector
        self.payload = payload


class FakeMatchValue:
    def __init__(self, value):
        self.value = value


class FakeFieldCondition:
    def __init__(self, key, match):
        self.key = key
        self.match = match


class FakeFilter:
    def __init__(self, must):
        self.must = must


class FakeModels:
    Distance = FakeDistance
    PayloadSchemaType = FakePayloadSchemaType
    VectorParams = FakeVectorParams
    PointStruct = FakePointStruct
    MatchValue = FakeMatchValue
    FieldCondition = FakeFieldCondition
    Filter = FakeFilter


class FakeQdrantClient:
    def __init__(self):
        self.recreated_collection = None
        self.payload_indexes = []
        self.upserted = None
        self.search_request = None

    def get_collections(self):
        return SimpleNamespace(collections=[])

    def recreate_collection(self, collection_name, vectors_config):
        self.recreated_collection = {
            "collection_name": collection_name,
            "vectors_config": vectors_config,
        }

    def create_payload_index(self, collection_name, field_name, field_schema):
        self.payload_indexes.append(
            {
                "collection_name": collection_name,
                "field_name": field_name,
                "field_schema": field_schema,
            }
        )

    def upsert(self, collection_name, points):
        self.upserted = {
            "collection_name": collection_name,
            "points": points,
        }

    def query_points(self, collection_name, query, using, query_filter, limit, with_payload):
        self.search_request = {
            "collection_name": collection_name,
            "query": query,
            "using": using,
            "query_filter": query_filter,
            "limit": limit,
            "with_payload": with_payload,
        }

        return SimpleNamespace(
            points=[
                SimpleNamespace(
                    id=9,
                    score=0.91,
                    payload={
                        "user_id": 7,
                        "clothing_id": 9,
                        "category": "shirt",
                    },
                )
            ]
        )


def test_qdrant_ensure_collection_create_path(monkeypatch):
    fake_client = FakeQdrantClient()

    monkeypatch.setattr(vector_store_service, "is_qdrant_client_available", lambda: True)
    monkeypatch.setattr(vector_store_service, "qdrant_client_instance", lambda: fake_client)
    monkeypatch.setattr(vector_store_service, "qdrant_models_module", lambda: FakeModels)

    result = vector_store_service.qdrant_ensure_collection(create_missing=True)

    assert result["status"] == "ready"
    assert result["created"] is True
    assert result["verified"] is True
    assert result["collection_name"] == "vogueai_clothing_embeddings"
    assert fake_client.recreated_collection["collection_name"] == "vogueai_clothing_embeddings"
    assert fake_client.recreated_collection["vectors_config"]["clip_image"].size == 512
    assert fake_client.recreated_collection["vectors_config"]["clip_text"].distance == "Cosine"
    assert len(fake_client.payload_indexes) == 7
    assert fake_client.payload_indexes[0]["field_name"] == "user_id"
    assert result["payload_indexes_created"][0]["field_name"] == "user_id"


def test_qdrant_contract_keeps_fallback_active_until_connection_ready(monkeypatch):
    monkeypatch.setattr(vector_store_service, "is_qdrant_client_available", lambda: True)

    result = vector_store_service.qdrant_vector_store_contract()

    assert result["status"] == "client_available"
    assert result["active_provider"] == "mock_sqlite_fallback"
    assert result["fallback_active"] is True
    assert result["degraded_reason"] == "QDRANT_CONNECTION_NOT_CHECKED"


def test_qdrant_preflight_marks_qdrant_active_when_collection_ready(monkeypatch):
    fake_client = FakeQdrantClient()
    fake_client.get_collections = lambda: SimpleNamespace(
        collections=[SimpleNamespace(name="vogueai_clothing_embeddings")]
    )

    monkeypatch.setattr(vector_store_service, "is_qdrant_client_available", lambda: True)
    monkeypatch.setattr(vector_store_service, "qdrant_client_instance", lambda: fake_client)

    result = vector_store_service.qdrant_preflight_contract(check_connection=True)

    assert result["status"] == "ready"
    assert result["vector_store"]["status"] == "ready"
    assert result["vector_store"]["active_provider"] == "qdrant"
    assert result["vector_store"]["fallback_active"] is False
    assert result["readiness"]["connected"] is True
    assert result["readiness"]["collection_exists"] is True


def test_qdrant_upsert_clothing_embedding_success_path(monkeypatch):
    fake_client = FakeQdrantClient()

    monkeypatch.setattr(vector_store_service, "is_qdrant_client_available", lambda: True)
    monkeypatch.setattr(vector_store_service, "qdrant_client_instance", lambda: fake_client)
    monkeypatch.setattr(vector_store_service, "qdrant_models_module", lambda: FakeModels)

    vector = [0.01] * 512
    result = vector_store_service.qdrant_upsert_clothing_embedding(
        clothing_id=9,
        user_id=7,
        vector=vector,
        payload={
            "category": "shirt",
            "style_tags": ["minimal"],
        },
    )

    assert result["status"] == "ready"
    assert result["stored"] is True
    assert result["point_id"] == 9
    assert result["dimension_validation"]["qdrant_ready"] is True
    assert fake_client.upserted["collection_name"] == "vogueai_clothing_embeddings"
    point = fake_client.upserted["points"][0]
    assert point.id == 9
    assert point.vector["clip_image"] == vector
    assert point.payload["user_id"] == 7
    assert point.payload["clothing_id"] == 9
    assert point.payload["source"] == "vogueai_clothing"


def test_qdrant_search_similar_clothing_success_path(monkeypatch):
    fake_client = FakeQdrantClient()

    monkeypatch.setattr(vector_store_service, "is_qdrant_client_available", lambda: True)
    monkeypatch.setattr(vector_store_service, "qdrant_client_instance", lambda: fake_client)
    monkeypatch.setattr(vector_store_service, "qdrant_models_module", lambda: FakeModels)

    vector = [0.02] * 512
    result = vector_store_service.qdrant_search_similar_clothing(
        user_id=7,
        query_vector=vector,
        top_k=3,
        filters={
            "category": "shirt",
        },
    )

    assert result["status"] == "ready"
    assert result["query_vector_name"] == "clip_image"
    assert result["dimension_validation"]["qdrant_ready"] is True
    assert fake_client.search_request["collection_name"] == "vogueai_clothing_embeddings"
    assert fake_client.search_request["query"] == vector
    assert fake_client.search_request["using"] == "clip_image"
    assert fake_client.search_request["limit"] == 3
    assert fake_client.search_request["with_payload"] is True
    assert fake_client.search_request["query_filter"].must[0].key == "user_id"
    assert fake_client.search_request["query_filter"].must[0].match.value == 7
    assert fake_client.search_request["query_filter"].must[1].key == "category"
    assert result["matches"][0]["payload"]["clothing_id"] == 9


def test_qdrant_upsert_rejects_mock_dimension_before_client(monkeypatch):
    fake_client = FakeQdrantClient()

    monkeypatch.setattr(vector_store_service, "is_qdrant_client_available", lambda: True)
    monkeypatch.setattr(vector_store_service, "qdrant_client_instance", lambda: fake_client)

    result = vector_store_service.qdrant_upsert_clothing_embedding(
        clothing_id=9,
        user_id=7,
        vector=[0.01] * 8,
    )

    assert result["status"] == "degraded"
    assert result["stored"] is False
    assert result["error_code"] == "VECTOR_DIMENSION_MISMATCH"
    assert result["dimension_validation"]["actual_vector_size"] == 8
    assert fake_client.upserted is None
