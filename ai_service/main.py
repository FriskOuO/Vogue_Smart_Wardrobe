import os
from fastapi import FastAPI, Header, HTTPException
from pydantic import BaseModel
from typing import Optional, List, Dict, Any
from dotenv import load_dotenv

load_dotenv()

AI_INTERNAL_TOKEN = os.getenv("AI_INTERNAL_TOKEN", "change_this_internal_ai_token")
AI_MOCK_MODE = os.getenv("AI_MOCK_MODE", "true").lower() == "true"

app = FastAPI(
    title="VogueAI AI Service",
    description="Mock AI service for Laravel integration",
    version="0.1.0"
)


def check_internal_token(x_internal_ai_token: Optional[str]):
    if x_internal_ai_token != AI_INTERNAL_TOKEN:
        raise HTTPException(
            status_code=401,
            detail={
                "schema_version": "v1",
                "status": "failed",
                "error": {
                    "code": "AI_UNAUTHORIZED",
                    "message": "Internal token 錯誤"
                }
            }
        )


class AttributesRequest(BaseModel):
    schema_version: str
    request_id: str
    user_id: int
    clothing_id: int
    image_path: str
    image_url: Optional[str] = None
    locale: Optional[str] = "zh_TW"
    mock_mode: Optional[bool] = True


class ImageEmbeddingRequest(BaseModel):
    schema_version: str
    request_id: str
    user_id: int
    clothing_id: int
    image_path: str
    image_url: Optional[str] = None
    model: Optional[str] = "clip-vit-base-patch32"
    store_to_vector_db: Optional[bool] = True
    mock_mode: Optional[bool] = True


class TextEmbeddingRequest(BaseModel):
    schema_version: str
    request_id: str
    user_id: int
    query: str
    locale: Optional[str] = "zh_TW"
    model: Optional[str] = "clip-vit-base-patch32"
    mock_mode: Optional[bool] = True


class SimilarSearchRequest(BaseModel):
    schema_version: str
    request_id: str
    user_id: int
    query_type: str
    query: Optional[str] = None
    source_clothing_id: Optional[int] = None
    embedding: List[float]
    top_k: Optional[int] = 5
    filters: Optional[Dict[str, Any]] = {}
    fallback_enabled: Optional[bool] = True
    mock_mode: Optional[bool] = True


class PoseRequest(BaseModel):
    schema_version: str
    request_id: str
    user_id: int
    image_path: str
    image_url: Optional[str] = None
    task_type: Optional[str] = "magic_mirror"
    return_annotated_image: Optional[bool] = True
    mock_mode: Optional[bool] = True


@app.get("/health")
def health():
    return {
        "status": "ok",
        "service": "VogueAI AI Service",
        "mock_mode": AI_MOCK_MODE
    }


@app.post("/ai/attributes")
def analyze_attributes(
    payload: AttributesRequest,
    x_internal_ai_token: Optional[str] = Header(None)
):
    check_internal_token(x_internal_ai_token)

    return {
        "schema_version": "v1",
        "request_id": payload.request_id,
        "status": "degraded",
        "mode": "mock",
        "degraded_reason": "MOCK_MODE_ENABLED",
        "clothing_id": payload.clothing_id,
        "attributes": {
            "category": "上衣",
            "subcategory": "襯衫",
            "color": "白色",
            "secondary_colors": [],
            "season": ["春", "夏"],
            "occasion": ["日常"],
            "usage": ["休閒穿搭", "校園穿搭"],
            "style_tags": ["簡約", "基本款"],
            "material_guess": "未知",
            "pattern": "素色"
        },
        "confidence": {
            "category": 0.70,
            "color": 0.70,
            "season": 0.60,
            "occasion": 0.60,
            "overall": 0.65
        },
        "message": "目前為 mock mode，已回傳展示用衣物屬性"
    }


@app.post("/ai/embed/image")
def embed_image(
    payload: ImageEmbeddingRequest,
    x_internal_ai_token: Optional[str] = Header(None)
):
    check_internal_token(x_internal_ai_token)

    return {
        "schema_version": "v1",
        "request_id": payload.request_id,
        "status": "degraded",
        "mode": "mock",
        "degraded_reason": "MOCK_EMBEDDING_ENABLED",
        "embedding_type": "image",
        "model": "mock-image-embedding",
        "vector_dimension": 8,
        "clothing_id": payload.clothing_id,
        "embedding": [0.12, 0.08, -0.04, 0.31, 0.22, -0.18, 0.05, 0.11],
        "embedding_preview": [0.12, 0.08, -0.04, 0.31],
        "vector_db": {
            "provider": "sqlite_fallback",
            "collection": "ai_embeddings",
            "point_id": f"clothing_{payload.clothing_id}",
            "stored": True
        },
        "message": "目前為 mock mode，已回傳展示用 image embedding"
    }


@app.post("/ai/embed/text")
def embed_text(
    payload: TextEmbeddingRequest,
    x_internal_ai_token: Optional[str] = Header(None)
):
    check_internal_token(x_internal_ai_token)

    if not payload.query.strip():
        raise HTTPException(
            status_code=422,
            detail={
                "schema_version": "v1",
                "request_id": payload.request_id,
                "status": "failed",
                "error": {
                    "code": "AI_VALIDATION_ERROR",
                    "message": "query 不可為空"
                }
            }
        )

    return {
        "schema_version": "v1",
        "request_id": payload.request_id,
        "status": "degraded",
        "mode": "mock",
        "degraded_reason": "MOCK_TEXT_EMBEDDING_ENABLED",
        "embedding_type": "text",
        "model": "mock-text-embedding",
        "vector_dimension": 8,
        "query": payload.query,
        "normalized_query": payload.query.strip(),
        "embedding": [0.20, -0.11, 0.07, 0.18, 0.03, -0.04, 0.09, 0.14],
        "embedding_preview": [0.20, -0.11, 0.07, 0.18],
        "message": "目前為 mock mode，已回傳展示用 text embedding"
    }


@app.post("/ai/search/similar")
def search_similar(
    payload: SimilarSearchRequest,
    x_internal_ai_token: Optional[str] = Header(None)
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
                    "message": "query_type 必須是 text 或 image"
                }
            }
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
                    "message": "embedding 不可為空"
                }
            }
        )

    return {
        "schema_version": "v1",
        "request_id": payload.request_id,
        "status": "degraded",
        "mode": "mock",
        "degraded_reason": "MOCK_SEARCH_ENABLED",
        "query_type": payload.query_type,
        "search_provider": "mock",
        "top_k": payload.top_k,
        "results": [
            {
                "rank": 1,
                "clothing_id": 21,
                "score": 0.75,
                "reason": "目前為 mock mode，回傳展示用相似衣物結果",
                "metadata": {
                    "category": "上衣",
                    "color": "白色",
                    "season": ["春", "夏"],
                    "occasion": ["日常"]
                }
            },
            {
                "rank": 2,
                "clothing_id": 18,
                "score": 0.68,
                "reason": "目前為 mock mode，依模擬相似度排序",
                "metadata": {
                    "category": "外套",
                    "color": "米色",
                    "season": ["秋", "冬"],
                    "occasion": ["通勤"]
                }
            }
        ],
        "message": "目前為 mock mode，已回傳展示用相似搜尋結果"
    }


@app.post("/ai/pose")
def analyze_pose(
    payload: PoseRequest,
    x_internal_ai_token: Optional[str] = Header(None)
):
    check_internal_token(x_internal_ai_token)

    return {
        "schema_version": "v1",
        "request_id": payload.request_id,
        "status": "degraded",
        "mode": "mock",
        "degraded_reason": "MOCK_POSE_ENABLED",
        "pose_model": "mock-pose",
        "person_count": 1,
        "image_size": {
            "width": 1080,
            "height": 1440
        },
        "keypoints_format": "coco_17",
        "keypoints": [
            {"name": "nose", "x": 540, "y": 180, "confidence": 0.70},
            {"name": "left_shoulder", "x": 410, "y": 390, "confidence": 0.70},
            {"name": "right_shoulder", "x": 670, "y": 398, "confidence": 0.70},
            {"name": "left_hip", "x": 440, "y": 760, "confidence": 0.65},
            {"name": "right_hip", "x": 650, "y": 755, "confidence": 0.65}
        ],
        "pose_analysis": {
            "full_body_visible": True,
            "shoulder_balance": "unknown",
            "shoulder_tilt_degree": None,
            "posture_notes": ["目前使用展示模式，姿態分析僅供流程展示"],
            "fit_notes": ["可用於 Try-on / Magic Mirror 介面測試"]
        },
        "annotated_image_url": None,
        "message": "目前為 mock mode，已回傳展示用 pose keypoints"
    }
