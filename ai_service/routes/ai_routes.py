import time
from typing import Optional

from fastapi import APIRouter, Header, HTTPException

from schemas import (
    AttributesRequest,
    ImageEmbeddingRequest,
    TextEmbeddingRequest,
    SimilarSearchRequest,
    PoseRequest,
)

from services.mock_ai_service import (
    mock_attributes,
    mock_image_embedding,
    mock_text_embedding,
    mock_similar_search,
    mock_pose,
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
    return mock_attributes(payload)


@router.post("/ai/embed/image")
def embed_image(
    payload: ImageEmbeddingRequest,
    x_internal_ai_token: Optional[str] = Header(None),
):
    check_internal_token(x_internal_ai_token)
    return mock_image_embedding(payload)


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

    result = mock_text_embedding(payload)

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

    return mock_similar_search(payload)


@router.post("/ai/pose")
def analyze_pose(
    payload: PoseRequest,
    x_internal_ai_token: Optional[str] = Header(None),
):
    check_internal_token(x_internal_ai_token)
    return mock_pose(payload)