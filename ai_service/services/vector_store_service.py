import importlib
import importlib.util
from typing import Dict, List, Optional

from config import (
    VECTOR_STORE_API_KEY,
    VECTOR_STORE_ACTIVE_VECTOR_SIZE,
    VECTOR_STORE_COLLECTION,
    VECTOR_STORE_DISTANCE,
    VECTOR_STORE_PROVIDER,
    VECTOR_STORE_TARGET_VECTOR_SIZE,
    VECTOR_STORE_TIMEOUT_SECONDS,
    VECTOR_STORE_URL,
)


def is_qdrant_client_available() -> bool:
    return importlib.util.find_spec("qdrant_client") is not None


def qdrant_collection_schema_contract() -> Dict[str, object]:
    return {
        "collection_name": VECTOR_STORE_COLLECTION,
        "distance": VECTOR_STORE_DISTANCE,
        "target_vector_size": VECTOR_STORE_TARGET_VECTOR_SIZE,
        "active_vector_size": VECTOR_STORE_ACTIVE_VECTOR_SIZE,
        "vector_name": "clip_image",
        "named_vectors": {
            "clip_image": {
                "size": VECTOR_STORE_TARGET_VECTOR_SIZE,
                "distance": VECTOR_STORE_DISTANCE,
                "source": "clip image embedding",
            },
            "clip_text": {
                "size": VECTOR_STORE_TARGET_VECTOR_SIZE,
                "distance": VECTOR_STORE_DISTANCE,
                "source": "clip text embedding",
            },
        },
        "payload_indexes": [
            "user_id",
            "clothing_id",
            "category",
            "color",
            "season",
            "occasion",
            "style_tags",
        ],
        "mock_active_dimension_note": (
            f"Current mock embeddings are {VECTOR_STORE_ACTIVE_VECTOR_SIZE}D; "
            f"target Qdrant collection is {VECTOR_STORE_TARGET_VECTOR_SIZE}D for real CLIP vectors."
        ),
    }


def qdrant_collection_creation_plan() -> Dict[str, object]:
    schema = qdrant_collection_schema_contract()

    return {
        "dry_run": True,
        "operation": "create_or_verify_collection",
        "collection_name": schema["collection_name"],
        "client_method": "recreate_collection",
        "vectors_config": {
            name: {
                "size": vector_config["size"],
                "distance": vector_config["distance"],
            }
            for name, vector_config in schema["named_vectors"].items()
        },
        "payload_index_plan": [
            {
                "field_name": field_name,
                "field_schema": "keyword" if field_name != "style_tags" else "keyword_array",
            }
            for field_name in schema["payload_indexes"]
        ],
        "required_before_activation": [
            "qdrant-client installed",
            "Qdrant daemon reachable at VECTOR_STORE_URL",
            f"collection {schema['collection_name']} exists",
            f"named vectors are {schema['target_vector_size']}D",
            f"distance metric is {schema['distance']}",
        ],
        "activation_guardrails": [
            "Do not upsert mock 8D embeddings into the 512D target collection.",
            "Keep SQLite ai_embeddings fallback active until CLIP embeddings are 512D.",
            "Run preflight with check_connection=true before disabling fallback.",
        ],
    }


def qdrant_adapter_methods_contract() -> Dict[str, object]:
    return {
        "connection_check": "qdrant_connection_check",
        "ensure_collection": "qdrant_ensure_collection(create_missing=True)",
        "upsert": "qdrant_upsert_clothing_embedding",
        "search": "qdrant_search_similar_clothing",
        "activation_mode": "manual_internal_endpoint",
        "safety": [
            "Collection creation is never triggered by health checks.",
            "Collection creation requires the internal token endpoint.",
            "Upsert/search reject non-512D vectors before touching Qdrant.",
            "Fallback remains active until real 512D CLIP embeddings are available.",
        ],
    }


def qdrant_upsert_plan() -> Dict[str, object]:
    schema = qdrant_collection_schema_contract()

    return {
        "dry_run": True,
        "operation": "upsert_clothing_embedding",
        "client_method": "upsert",
        "collection_name": schema["collection_name"],
        "point_id_template": "<clothing_id>",
        "vector_name": "clip_image",
        "vector_size": schema["target_vector_size"],
        "active_mock_vector_size": schema["active_vector_size"],
        "payload_template": {
            "user_id": "<int>",
            "clothing_id": "<int>",
            "category": "<string|null>",
            "color": "<string|null>",
            "season": "<array>",
            "occasion": "<array>",
            "style_tags": "<array>",
            "source": "vogueai_clothing",
        },
        "guardrails": [
            "Only upsert 512D CLIP image vectors into clip_image.",
            "Reject or fallback when embedding dimension does not match target_vector_size.",
            "Keep ai_embeddings SQLite row as local fallback and audit trail.",
        ],
    }


def qdrant_search_plan() -> Dict[str, object]:
    schema = qdrant_collection_schema_contract()

    return {
        "dry_run": True,
        "operation": "search_similar_clothing",
        "client_method": "query_points",
        "collection_name": schema["collection_name"],
        "query_vector_name": "clip_image",
        "query_vector_size": schema["target_vector_size"],
        "active_mock_vector_size": schema["active_vector_size"],
        "limit_source": "top_k",
        "score_threshold": None,
        "filter_template": {
            "must": [
                {
                    "key": "user_id",
                    "match": {
                        "value": "<current_user_id>",
                    },
                },
            ],
            "optional_should": [
                "category",
                "color",
                "season",
                "occasion",
                "style_tags",
            ],
        },
        "guardrails": [
            "Search 512D CLIP query vectors against stored clip_image clothing vectors.",
            "Always filter by user_id before returning clothing matches.",
            "Fallback to SQL keyword search when Qdrant is unavailable or returns no owned clothes.",
        ],
    }


def qdrant_dimension_validation(vector_length: int, vector_name: str) -> Dict[str, object]:
    expected_size = VECTOR_STORE_TARGET_VECTOR_SIZE
    matches_target = vector_length == expected_size

    return {
        "vector_name": vector_name,
        "expected_vector_size": expected_size,
        "actual_vector_size": vector_length,
        "matches_target": matches_target,
        "qdrant_ready": matches_target,
        "fallback_required": not matches_target,
        "error_code": None if matches_target else "VECTOR_DIMENSION_MISMATCH",
        "message": (
            "Vector dimension matches Qdrant target collection."
            if matches_target
            else f"Vector is {vector_length}D but Qdrant target collection requires {expected_size}D."
        ),
    }


def qdrant_vector_store_contract(
    stored: bool = False,
    connection_ready: bool = False,
) -> Dict[str, object]:
    client_available = is_qdrant_client_available()
    fallback_active = (
        VECTOR_STORE_PROVIDER != "qdrant"
        or not client_available
        or not connection_ready
    )

    if VECTOR_STORE_PROVIDER != "qdrant":
        status = "disabled"
        degraded_reason = "VECTOR_STORE_PROVIDER_NOT_QDRANT"
    elif not client_available:
        status = "planned"
        degraded_reason = "QDRANT_CLIENT_NOT_INSTALLED"
    elif connection_ready:
        status = "ready"
        degraded_reason = None
    else:
        status = "client_available"
        degraded_reason = "QDRANT_CONNECTION_NOT_CHECKED"

    return {
        "target_provider": VECTOR_STORE_PROVIDER,
        "active_provider": "mock_sqlite_fallback" if fallback_active else VECTOR_STORE_PROVIDER,
        "adapter": "qdrant-vector-store-v1",
        "status": status,
        "client_package": "available" if client_available else "missing",
        "target_url": VECTOR_STORE_URL,
        "collection": VECTOR_STORE_COLLECTION,
        "target_collection": VECTOR_STORE_COLLECTION,
        "collection_schema": qdrant_collection_schema_contract(),
        "collection_plan": qdrant_collection_creation_plan(),
        "upsert_plan": qdrant_upsert_plan(),
        "search_plan": qdrant_search_plan(),
        "adapter_methods": qdrant_adapter_methods_contract(),
        "target_vector_size": VECTOR_STORE_TARGET_VECTOR_SIZE,
        "active_vector_size": VECTOR_STORE_ACTIVE_VECTOR_SIZE,
        "distance": VECTOR_STORE_DISTANCE,
        "dimension_validation": qdrant_dimension_validation(
            vector_length=VECTOR_STORE_ACTIVE_VECTOR_SIZE,
            vector_name="clip_image",
        ),
        "fallback_collection": "ai_embeddings",
        "stored": stored,
        "fallback_active": fallback_active,
        "api_key_configured": bool(VECTOR_STORE_API_KEY),
        "connection_check": "not_attempted",
        "degraded_reason": degraded_reason,
    }


def qdrant_client_instance():
    qdrant_module = importlib.import_module("qdrant_client")
    return qdrant_module.QdrantClient(
        url=VECTOR_STORE_URL,
        api_key=VECTOR_STORE_API_KEY or None,
        timeout=VECTOR_STORE_TIMEOUT_SECONDS,
    )


def qdrant_models_module():
    return importlib.import_module("qdrant_client.models")


def qdrant_connection_check() -> Dict[str, object]:
    if VECTOR_STORE_PROVIDER != "qdrant":
        return {
            "connection_check": "skipped",
            "connected": False,
            "collection_exists": False,
            "error_code": "VECTOR_STORE_PROVIDER_NOT_QDRANT",
            "error_message": "VECTOR_STORE_PROVIDER is not qdrant.",
        }

    if not is_qdrant_client_available():
        return {
            "connection_check": "skipped",
            "connected": False,
            "collection_exists": False,
            "error_code": "QDRANT_CLIENT_NOT_INSTALLED",
            "error_message": "Install qdrant-client before attempting connection preflight.",
        }

    try:
        qdrant_client = qdrant_client_instance()
        collections_response = qdrant_client.get_collections()
        collections = getattr(collections_response, "collections", [])
        collection_names = [getattr(collection, "name", "") for collection in collections]
        collection_exists = VECTOR_STORE_COLLECTION in collection_names

        return {
            "connection_check": "attempted",
            "connected": True,
            "collection_exists": collection_exists,
            "collection_names": collection_names,
            "error_code": None if collection_exists else "QDRANT_COLLECTION_MISSING",
            "error_message": None if collection_exists else f"Collection {VECTOR_STORE_COLLECTION} was not found.",
        }
    except Exception as exc:
        return {
            "connection_check": "attempted",
            "connected": False,
            "collection_exists": False,
            "error_code": "QDRANT_CONNECTION_FAILED",
            "error_message": str(exc),
        }


def qdrant_ensure_collection(create_missing: bool = False) -> Dict[str, object]:
    schema = qdrant_collection_schema_contract()
    plan = qdrant_collection_creation_plan()

    if VECTOR_STORE_PROVIDER != "qdrant":
        return {
            "schema_version": "v1",
            "status": "disabled",
            "operation": "ensure_collection",
            "collection_name": schema["collection_name"],
            "created": False,
            "verified": False,
            "fallback_safe": True,
            "error_code": "VECTOR_STORE_PROVIDER_NOT_QDRANT",
            "error_message": "VECTOR_STORE_PROVIDER is not qdrant.",
            "collection_plan": plan,
        }

    if not is_qdrant_client_available():
        return {
            "schema_version": "v1",
            "status": "degraded",
            "operation": "ensure_collection",
            "collection_name": schema["collection_name"],
            "created": False,
            "verified": False,
            "fallback_safe": True,
            "error_code": "QDRANT_CLIENT_NOT_INSTALLED",
            "error_message": "Install qdrant-client before ensuring the Qdrant collection.",
            "collection_plan": plan,
        }

    try:
        qdrant_client = qdrant_client_instance()
        collections_response = qdrant_client.get_collections()
        collections = getattr(collections_response, "collections", [])
        collection_names = [getattr(collection, "name", "") for collection in collections]

        if schema["collection_name"] in collection_names:
            return {
                "schema_version": "v1",
                "status": "ready",
                "operation": "ensure_collection",
                "collection_name": schema["collection_name"],
                "created": False,
                "verified": True,
                "fallback_safe": True,
                "error_code": None,
                "error_message": None,
                "collection_plan": plan,
            }

        if not create_missing:
            return {
                "schema_version": "v1",
                "status": "degraded",
                "operation": "ensure_collection",
                "collection_name": schema["collection_name"],
                "created": False,
                "verified": False,
                "fallback_safe": True,
                "error_code": "QDRANT_COLLECTION_MISSING",
                "error_message": f"Collection {schema['collection_name']} was not found.",
                "collection_plan": plan,
            }

        models = qdrant_models_module()
        distance = getattr(models.Distance, str(schema["distance"]).upper())
        vectors_config = {
            name: models.VectorParams(
                size=vector_config["size"],
                distance=distance,
            )
            for name, vector_config in schema["named_vectors"].items()
        }

        qdrant_client.recreate_collection(
            collection_name=schema["collection_name"],
            vectors_config=vectors_config,
        )

        payload_schema = getattr(models.PayloadSchemaType, "KEYWORD")
        for payload_index in plan["payload_index_plan"]:
            qdrant_client.create_payload_index(
                collection_name=schema["collection_name"],
                field_name=payload_index["field_name"],
                field_schema=payload_schema,
            )

        return {
            "schema_version": "v1",
            "status": "ready",
            "operation": "ensure_collection",
            "collection_name": schema["collection_name"],
            "created": True,
            "verified": True,
            "payload_indexes_created": plan["payload_index_plan"],
            "fallback_safe": True,
            "error_code": None,
            "error_message": None,
            "collection_plan": plan,
        }
    except Exception as exc:
        return {
            "schema_version": "v1",
            "status": "degraded",
            "operation": "ensure_collection",
            "collection_name": schema["collection_name"],
            "created": False,
            "verified": False,
            "fallback_safe": True,
            "error_code": "QDRANT_COLLECTION_ENSURE_FAILED",
            "error_message": str(exc),
            "collection_plan": plan,
        }


def qdrant_upsert_clothing_embedding(
    clothing_id: int,
    user_id: int,
    vector: List[float],
    payload: Optional[Dict[str, object]] = None,
    vector_name: str = "clip_image",
) -> Dict[str, object]:
    plan = qdrant_upsert_plan()
    dimension_validation = qdrant_dimension_validation(
        vector_length=len(vector),
        vector_name=vector_name,
    )

    if not dimension_validation["matches_target"]:
        return {
            "schema_version": "v1",
            "status": "degraded",
            "operation": "upsert_clothing_embedding",
            "collection_name": VECTOR_STORE_COLLECTION,
            "point_id": clothing_id,
            "stored": False,
            "fallback_safe": True,
            "dimension_validation": dimension_validation,
            "error_code": "VECTOR_DIMENSION_MISMATCH",
            "error_message": dimension_validation["message"],
            "upsert_plan": plan,
        }

    if VECTOR_STORE_PROVIDER != "qdrant":
        return {
            "schema_version": "v1",
            "status": "disabled",
            "operation": "upsert_clothing_embedding",
            "collection_name": VECTOR_STORE_COLLECTION,
            "point_id": clothing_id,
            "stored": False,
            "fallback_safe": True,
            "dimension_validation": dimension_validation,
            "error_code": "VECTOR_STORE_PROVIDER_NOT_QDRANT",
            "error_message": "VECTOR_STORE_PROVIDER is not qdrant.",
            "upsert_plan": plan,
        }

    if not is_qdrant_client_available():
        return {
            "schema_version": "v1",
            "status": "degraded",
            "operation": "upsert_clothing_embedding",
            "collection_name": VECTOR_STORE_COLLECTION,
            "point_id": clothing_id,
            "stored": False,
            "fallback_safe": True,
            "dimension_validation": dimension_validation,
            "error_code": "QDRANT_CLIENT_NOT_INSTALLED",
            "error_message": "Install qdrant-client before upserting Qdrant vectors.",
            "upsert_plan": plan,
        }

    try:
        qdrant_client = qdrant_client_instance()
        models = qdrant_models_module()
        point_payload = {
            **(payload or {}),
            "user_id": user_id,
            "clothing_id": clothing_id,
            "source": "vogueai_clothing",
        }
        point = models.PointStruct(
            id=clothing_id,
            vector={vector_name: vector},
            payload=point_payload,
        )

        qdrant_client.upsert(
            collection_name=VECTOR_STORE_COLLECTION,
            points=[point],
        )

        return {
            "schema_version": "v1",
            "status": "ready",
            "operation": "upsert_clothing_embedding",
            "collection_name": VECTOR_STORE_COLLECTION,
            "point_id": clothing_id,
            "vector_name": vector_name,
            "stored": True,
            "fallback_safe": True,
            "dimension_validation": dimension_validation,
            "payload": point_payload,
            "error_code": None,
            "error_message": None,
            "upsert_plan": plan,
        }
    except Exception as exc:
        return {
            "schema_version": "v1",
            "status": "degraded",
            "operation": "upsert_clothing_embedding",
            "collection_name": VECTOR_STORE_COLLECTION,
            "point_id": clothing_id,
            "stored": False,
            "fallback_safe": True,
            "dimension_validation": dimension_validation,
            "error_code": "QDRANT_UPSERT_FAILED",
            "error_message": str(exc),
            "upsert_plan": plan,
        }


def qdrant_search_similar_clothing(
    user_id: int,
    query_vector: List[float],
    top_k: int = 5,
    filters: Optional[Dict[str, object]] = None,
    vector_name: str = "clip_image",
) -> Dict[str, object]:
    plan = qdrant_search_plan()
    dimension_validation = qdrant_dimension_validation(
        vector_length=len(query_vector),
        vector_name=vector_name,
    )

    if not dimension_validation["matches_target"]:
        return {
            "schema_version": "v1",
            "status": "degraded",
            "operation": "search_similar_clothing",
            "collection_name": VECTOR_STORE_COLLECTION,
            "matches": [],
            "fallback_safe": True,
            "dimension_validation": dimension_validation,
            "error_code": "VECTOR_DIMENSION_MISMATCH",
            "error_message": dimension_validation["message"],
            "search_plan": plan,
        }

    if VECTOR_STORE_PROVIDER != "qdrant":
        return {
            "schema_version": "v1",
            "status": "disabled",
            "operation": "search_similar_clothing",
            "collection_name": VECTOR_STORE_COLLECTION,
            "matches": [],
            "fallback_safe": True,
            "dimension_validation": dimension_validation,
            "error_code": "VECTOR_STORE_PROVIDER_NOT_QDRANT",
            "error_message": "VECTOR_STORE_PROVIDER is not qdrant.",
            "search_plan": plan,
        }

    if not is_qdrant_client_available():
        return {
            "schema_version": "v1",
            "status": "degraded",
            "operation": "search_similar_clothing",
            "collection_name": VECTOR_STORE_COLLECTION,
            "matches": [],
            "fallback_safe": True,
            "dimension_validation": dimension_validation,
            "error_code": "QDRANT_CLIENT_NOT_INSTALLED",
            "error_message": "Install qdrant-client before searching Qdrant vectors.",
            "search_plan": plan,
        }

    try:
        qdrant_client = qdrant_client_instance()
        models = qdrant_models_module()
        must_conditions = [
            models.FieldCondition(
                key="user_id",
                match=models.MatchValue(value=user_id),
            )
        ]

        for key, value in (filters or {}).items():
            if value not in (None, "", []):
                must_conditions.append(
                    models.FieldCondition(
                        key=key,
                        match=models.MatchValue(value=value),
                    )
                )

        query_filter = models.Filter(must=must_conditions)
        query_response = qdrant_client.query_points(
            collection_name=VECTOR_STORE_COLLECTION,
            query=query_vector,
            using=vector_name,
            query_filter=query_filter,
            limit=top_k,
            with_payload=True,
        )
        raw_matches = getattr(query_response, "points", query_response)

        matches = [
            {
                "id": getattr(match, "id", None),
                "score": getattr(match, "score", None),
                "payload": getattr(match, "payload", {}),
            }
            for match in raw_matches
        ]

        return {
            "schema_version": "v1",
            "status": "ready",
            "operation": "search_similar_clothing",
            "collection_name": VECTOR_STORE_COLLECTION,
            "query_vector_name": vector_name,
            "matches": matches,
            "fallback_safe": True,
            "dimension_validation": dimension_validation,
            "applied_filter": {
                "user_id": user_id,
                **(filters or {}),
            },
            "error_code": None,
            "error_message": None,
            "search_plan": plan,
        }
    except Exception as exc:
        return {
            "schema_version": "v1",
            "status": "degraded",
            "operation": "search_similar_clothing",
            "collection_name": VECTOR_STORE_COLLECTION,
            "matches": [],
            "fallback_safe": True,
            "dimension_validation": dimension_validation,
            "error_code": "QDRANT_SEARCH_FAILED",
            "error_message": str(exc),
            "search_plan": plan,
        }


def qdrant_preflight_contract(check_connection: bool = False) -> Dict[str, object]:
    connection = qdrant_connection_check() if check_connection else {
        "connection_check": "not_attempted",
        "connected": False,
        "collection_exists": False,
        "error_code": None,
        "error_message": None,
    }
    connection_ready = bool(connection["connected"] and connection["collection_exists"])
    vector_store = qdrant_vector_store_contract(
        stored=False,
        connection_ready=connection_ready,
    )

    vector_store["connection_check"] = connection["connection_check"]
    vector_store["connected"] = connection["connected"]
    vector_store["collection_exists"] = connection["collection_exists"]

    return {
        "schema_version": "v1",
        "status": "ready" if connection_ready else "degraded",
        "mode": "preflight",
        "vector_store": vector_store,
        "readiness": {
            "can_attempt_connection": (
                vector_store["target_provider"] == "qdrant"
                and vector_store["client_package"] == "available"
            ),
            "collection_required": vector_store["target_collection"],
            "connection_check": vector_store["connection_check"],
            "connected": connection["connected"],
            "collection_exists": connection["collection_exists"],
            "fallback_safe": True,
        },
        "connection": connection,
        "dimension_validation": vector_store["dimension_validation"],
        "collection_plan": vector_store["collection_plan"],
        "upsert_plan": vector_store["upsert_plan"],
        "search_plan": vector_store["search_plan"],
        "adapter_methods": vector_store["adapter_methods"],
        "next_steps": [
            "Install qdrant-client in ai_service/.venv.",
            "Run or configure Qdrant at VECTOR_STORE_URL.",
            f"Create or verify collection {vector_store['target_collection']}.",
            "Run a connection-enabled preflight before switching off mock fallback.",
        ],
    }
