import time
from typing import Optional

from fastapi import APIRouter, Header, HTTPException

from config import EMBEDDING_MODEL
from schemas import (
    AttributesRequest,
    ImageEmbeddingRequest,
    TextEmbeddingRequest,
    SimilarSearchRequest,
    PoseRequest,
    VqaRequest,
    TryOnGenerateRequest,
)

from services.adapter_orchestration_service import (
    attributes_response,
    image_embedding_response,
    text_embedding_response,
)
from services.mock_ai_service import (
    mock_embedding_provider_contract,
    mock_similar_search,
    mock_pose,
)
from services.blip_vqa_service import blip_answer_questions
from services.yolo_pose_service import analyze_yolo_pose
from services.vector_store_service import (
    qdrant_ensure_collection,
    qdrant_search_similar_clothing,
    qdrant_preflight_contract,
    qdrant_vector_store_contract,
)
from services.huggingface_idm_vton_service import (
    create_tryon_task,
    get_tryon_status,
)

from utils.security import check_internal_token
from utils.logger import log_ai_request

router = APIRouter()


@router.post("/ai/attributes")
def analyze_attributes(
    payload: AttributesRequest,
    x_internal_ai_token: Optional[str] = Header(None),
):
    check_internal_token(x_internal_ai_token)
    return attributes_response(payload)


@router.post("/ai/embed/image")
def embed_image(
    payload: ImageEmbeddingRequest,
    x_internal_ai_token: Optional[str] = Header(None),
):
    check_internal_token(x_internal_ai_token)
    return image_embedding_response(payload)


@router.post("/ai/embed/text")
def embed_text(
    payload: TextEmbeddingRequest,
    x_internal_ai_token: Optional[str] = Header(None),
):
    started_at = time.time()
    endpoint = "/ai/embed/text"

    check_internal_token(x_internal_ai_token)

    if not payload.query.strip():
        log_ai_request(
            request_id=payload.request_id,
            endpoint=endpoint,
            status="failed",
            mode="validation",
            user_id=payload.user_id,
            error_code="AI_VALIDATION_ERROR",
            started_at=started_at,
        )

        raise HTTPException(
            status_code=422,
            detail={
                "schema_version": "v1",
                "request_id": payload.request_id,
                "status": "failed",
                "error": {
                    "code": "AI_VALIDATION_ERROR",
                    "message": "query 不可為空",
                },
            },
        )

    result = text_embedding_response(payload)

    log_ai_request(
        request_id=payload.request_id,
        endpoint=endpoint,
        status=result.get("status", "unknown"),
        mode=result.get("mode"),
        user_id=payload.user_id,
        started_at=started_at,
    )

    return result

@router.post("/ai/search/similar")
def search_similar(
    payload: SimilarSearchRequest,
    x_internal_ai_token: Optional[str] = Header(None),
):
    check_internal_token(x_internal_ai_token)

    if payload.query_type not in ["text", "image"]:
        raise HTTPException(
            status_code=422,
            detail={
                "schema_version": "v1",
                "request_id": payload.request_id,
                "status": "failed",
                "error": {
                    "code": "AI_VALIDATION_ERROR",
                    "message": "query_type 必須是 text 或 image",
                },
            },
        )

    if not payload.embedding:
        raise HTTPException(
            status_code=422,
            detail={
                "schema_version": "v1",
                "request_id": payload.request_id,
                "status": "failed",
                "error": {
                    "code": "AI_VALIDATION_ERROR",
                    "message": "embedding 不可為空",
                },
            },
        )

    if payload.mock_mode:
        return mock_similar_search(payload)

    query_vector_name = "clip_image"
    search_result = qdrant_search_similar_clothing(
        user_id=payload.user_id,
        query_vector=payload.embedding,
        top_k=payload.top_k or 5,
        filters=payload.filters or {},
        vector_name=query_vector_name,
    )
    qdrant_ready = search_result.get("status") == "ready"
    vector_store = qdrant_vector_store_contract(
        stored=False,
        connection_ready=qdrant_ready,
    )
    dimension_validation = search_result.get("dimension_validation")
    if dimension_validation:
        vector_store["dimension_validation"] = dimension_validation

    embedding_provider = mock_embedding_provider_contract(
        embedding_type=payload.query_type,
        target_model=EMBEDDING_MODEL,
        active_model=EMBEDDING_MODEL if qdrant_ready else f"mock-{payload.query_type}-embedding",
    )
    if qdrant_ready:
        embedding_provider["active_provider"] = "clip"
        embedding_provider["fallback_active"] = False
        embedding_provider["status"] = "ready"

    results = []
    for rank, match in enumerate(search_result.get("matches", []), start=1):
        metadata = match.get("payload") or {}
        clothing_id = metadata.get("clothing_id") or match.get("id")
        results.append(
            {
                "rank": rank,
                "clothing_id": clothing_id,
                "score": match.get("score"),
                "vector_provider": vector_store["active_provider"],
                "target_vector_provider": vector_store["target_provider"],
                "model": EMBEDDING_MODEL,
                "match_type": "qdrant_vector_similarity",
                "reason": "Qdrant 真實向量搜尋結果",
                "metadata": metadata,
            }
        )

    return {
        "schema_version": "v1",
        "request_id": payload.request_id,
        "status": "ready" if qdrant_ready else "degraded",
        "mode": "real_adapter" if qdrant_ready else "qdrant_fallback",
        "degraded_reason": None if qdrant_ready else search_result.get("error_code"),
        "query_type": payload.query_type,
        "search_provider": vector_store["active_provider"],
        "target_search_provider": vector_store["target_provider"],
        "query_model": EMBEDDING_MODEL if qdrant_ready else embedding_provider["active_model"],
        "target_query_model": EMBEDDING_MODEL,
        "embedding_provider": embedding_provider,
        "vector_store": vector_store,
        "dimension_validation": dimension_validation,
        "top_k": payload.top_k,
        "results": results,
        "qdrant_search_attempt": search_result,
        "message": (
            "Qdrant 真實向量搜尋已完成"
            if qdrant_ready
            else "Qdrant 真實搜尋不可用，Laravel 可改走 fallback"
        ),
    }


@router.get("/ai/vector-store/preflight")
def vector_store_preflight(
    check_connection: bool = False,
    x_internal_ai_token: Optional[str] = Header(None),
):
    check_internal_token(x_internal_ai_token)
    return qdrant_preflight_contract(check_connection=check_connection)


@router.post("/ai/vector-store/collection/ensure")
def vector_store_collection_ensure(
    create_missing: bool = False,
    x_internal_ai_token: Optional[str] = Header(None),
):
    check_internal_token(x_internal_ai_token)
    return qdrant_ensure_collection(create_missing=create_missing)


@router.post("/ai/pose")
def analyze_pose(
    payload: PoseRequest,
    x_internal_ai_token: Optional[str] = Header(None),
):
    check_internal_token(x_internal_ai_token)
    return mock_pose(payload) if payload.mock_mode else analyze_yolo_pose(payload)


@router.post("/ai/vqa")
def visual_question_answering(
    payload: VqaRequest,
    x_internal_ai_token: Optional[str] = Header(None),
):
    check_internal_token(x_internal_ai_token)
    if payload.mock_mode:
        return {
            "schema_version": "v1",
            "request_id": payload.request_id,
            "status": "degraded",
            "mode": "mock",
            "answers": {},
            "error_code": "MOCK_MODE_ENABLED",
        }

    return {
        "schema_version": "v1",
        "request_id": payload.request_id,
        **blip_answer_questions(payload.image_path, payload.questions),
    }


@router.post("/tryon/generate")
def generate_tryon(
    payload: TryOnGenerateRequest,
    x_internal_ai_token: Optional[str] = Header(None),
):
    check_internal_token(x_internal_ai_token)
    return create_tryon_task(payload)


@router.get("/tryon/status/{task_id}")
def tryon_status(
    task_id: str,
    x_internal_ai_token: Optional[str] = Header(None),
):
    check_internal_token(x_internal_ai_token)
    return get_tryon_status(task_id)
