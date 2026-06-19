from schemas import (
    AttributesRequest,
    ImageEmbeddingRequest,
    TextEmbeddingRequest,
    SimilarSearchRequest,
    PoseRequest,
)
from config import (
    EMBEDDING_MODEL,
    EMBEDDING_MODEL_REPOSITORY,
    EMBEDDING_PROVIDER,
    IMAGE_CAPTION_MODEL,
    IMAGE_CAPTION_MODEL_REPOSITORY,
    IMAGE_CAPTION_PROVIDER,
)
from services.vector_store_service import qdrant_vector_store_contract
from services.vector_store_service import qdrant_dimension_validation


def mock_attributes(payload: AttributesRequest) -> dict:
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
            "pattern": "素色",
        },
        "image_caption": mock_image_caption_contract(payload),
        "confidence": {
            "category": 0.70,
            "color": 0.70,
            "season": 0.60,
            "occasion": 0.60,
            "overall": 0.65,
        },
        "message": "目前為 mock mode，已回傳展示用衣物屬性",
    }


def mock_image_caption_contract(payload: AttributesRequest) -> dict:
    return {
        "target_provider": IMAGE_CAPTION_PROVIDER,
        "active_provider": "mock_caption_fallback",
        "adapter": "blip-image-caption-v1",
        "target_model": IMAGE_CAPTION_MODEL,
        "model_repository": IMAGE_CAPTION_MODEL_REPOSITORY,
        "active_model": "mock-blip-caption",
        "status": "planned",
        "mode": "mock",
        "fallback_active": True,
        "degraded_reason": "BLIP_ADAPTER_NOT_CONNECTED",
        "adapter_methods": {
            "image_caption": "blip_generate_caption",
        },
        "caption": "Mock caption: a clean wardrobe item photographed for style analysis.",
        "caption_locale": payload.locale,
        "visual_tags": ["wardrobe", "style-analysis", "mock-caption"],
        "grounding": {
            "image_path": payload.image_path,
            "image_url": payload.image_url,
            "clothing_id": payload.clothing_id,
        },
    }


def mock_image_embedding(payload: ImageEmbeddingRequest) -> dict:
    vector_store = mock_vector_store_contract(stored=True)
    embedding_provider = mock_embedding_provider_contract(
        embedding_type="image",
        target_model=payload.model or EMBEDDING_MODEL,
        active_model="mock-image-embedding",
    )
    embedding = [0.12, 0.08, -0.04, 0.31, 0.22, -0.18, 0.05, 0.11]
    dimension_validation = qdrant_dimension_validation(
        vector_length=len(embedding),
        vector_name="clip_image",
    )

    return {
        "schema_version": "v1",
        "request_id": payload.request_id,
        "status": "degraded",
        "mode": "mock",
        "degraded_reason": "MOCK_EMBEDDING_ENABLED",
        "embedding_type": "image",
        "model": "mock-image-embedding",
        "target_model": embedding_provider["target_model"],
        "embedding_provider": embedding_provider,
        "vector_dimension": 8,
        "clothing_id": payload.clothing_id,
        "embedding": embedding,
        "embedding_preview": [0.12, 0.08, -0.04, 0.31],
        "vector_db": {
            "provider": vector_store["active_provider"],
            "target_provider": vector_store["target_provider"],
            "collection": vector_store["fallback_collection"],
            "target_collection": vector_store["collection"],
            "target_url": vector_store["target_url"],
            "target_vector_size": vector_store["target_vector_size"],
            "active_vector_size": vector_store["active_vector_size"],
            "distance": vector_store["distance"],
            "collection_schema": vector_store["collection_schema"],
            "collection_plan": vector_store["collection_plan"],
            "upsert_plan": vector_store["upsert_plan"],
            "search_plan": vector_store["search_plan"],
            "adapter_methods": vector_store["adapter_methods"],
            "dimension_validation": dimension_validation,
            "point_id": f"clothing_{payload.clothing_id}",
            "stored": True,
            "fallback_active": vector_store["fallback_active"],
            "connection_check": vector_store["connection_check"],
            "degraded_reason": vector_store["degraded_reason"],
        },
        "message": "目前為 mock mode，已回傳展示用 image embedding",
    }


def mock_text_embedding(payload: TextEmbeddingRequest) -> dict:
    embedding_provider = mock_embedding_provider_contract(
        embedding_type="text",
        target_model=payload.model or EMBEDDING_MODEL,
        active_model="mock-text-embedding",
    )

    return {
        "schema_version": "v1",
        "request_id": payload.request_id,
        "status": "degraded",
        "mode": "mock",
        "degraded_reason": "MOCK_TEXT_EMBEDDING_ENABLED",
        "embedding_type": "text",
        "model": "mock-text-embedding",
        "target_model": embedding_provider["target_model"],
        "embedding_provider": embedding_provider,
        "vector_dimension": 8,
        "query": payload.query,
        "normalized_query": payload.query.strip(),
        "embedding": [0.20, -0.11, 0.07, 0.18, 0.03, -0.04, 0.09, 0.14],
        "embedding_preview": [0.20, -0.11, 0.07, 0.18],
        "message": "目前為 mock mode，已回傳展示用 text embedding",
    }


def mock_similar_search(payload: SimilarSearchRequest) -> dict:
    vector_store = mock_vector_store_contract(stored=False)
    embedding_provider = mock_embedding_provider_contract(
        embedding_type=payload.query_type,
        target_model=EMBEDDING_MODEL,
        active_model=f"mock-{payload.query_type}-embedding",
    )
    query_vector_name = "clip_text" if payload.query_type == "text" else "clip_image"
    dimension_validation = qdrant_dimension_validation(
        vector_length=len(payload.embedding),
        vector_name=query_vector_name,
    )
    vector_store["dimension_validation"] = dimension_validation

    # [ASSUMPTION] 目前 mock 回傳 clothing_id 1 與 8。
    # 若本機資料庫沒有這些 ID，Laravel 會查不到結果。
    # 後續可改成由 Laravel 傳入已存在衣物 ID，或接入真正 Qdrant。
    return {
        "schema_version": "v1",
        "request_id": payload.request_id,
        "status": "degraded",
        "mode": "mock",
        "degraded_reason": "MOCK_SEARCH_ENABLED",
        "query_type": payload.query_type,
        "search_provider": vector_store["active_provider"],
        "target_search_provider": vector_store["target_provider"],
        "query_model": embedding_provider["active_model"],
        "target_query_model": embedding_provider["target_model"],
        "embedding_provider": embedding_provider,
        "vector_store": vector_store,
        "dimension_validation": dimension_validation,
        "top_k": payload.top_k,
        "results": [
            {
                "rank": 1,
                "clothing_id": 1,
                "score": 0.75,
                "vector_provider": vector_store["active_provider"],
                "target_vector_provider": vector_store["target_provider"],
                "model": "mock-text-embedding",
                "match_type": "vector_similarity_fallback",
                "reason": "目前為 mock mode，回傳展示用相似衣物結果",
                "metadata": {
                    "category": "上衣",
                    "color": "白色",
                    "season": ["春", "夏"],
                    "occasion": ["日常"],
                },
            },
            {
                "rank": 2,
                "clothing_id": 8,
                "score": 0.68,
                "vector_provider": vector_store["active_provider"],
                "target_vector_provider": vector_store["target_provider"],
                "model": "mock-text-embedding",
                "match_type": "vector_similarity_fallback",
                "reason": "目前為 mock mode，依模擬相似度排序",
                "metadata": {
                    "category": "外套",
                    "color": "米色",
                    "season": ["秋", "冬"],
                    "occasion": ["通勤"],
                },
            },
        ],
        "message": "目前為 mock mode，已回傳展示用相似搜尋結果",
    }


def mock_vector_store_contract(stored: bool) -> dict:
    return qdrant_vector_store_contract(stored=stored)


def mock_embedding_provider_contract(
    embedding_type: str,
    target_model: str,
    active_model: str,
) -> dict:
    return {
        "target_provider": EMBEDDING_PROVIDER,
        "active_provider": "mock_embedding_fallback",
        "adapter": "clip-embedding-v1",
        "embedding_type": embedding_type,
        "target_model": target_model,
        "model_repository": EMBEDDING_MODEL_REPOSITORY,
        "active_model": active_model,
        "status": "planned",
        "mode": "mock",
        "fallback_active": True,
        "degraded_reason": "CLIP_ADAPTER_NOT_CONNECTED",
        "adapter_methods": {
            "image": "clip_embed_image",
            "text": "clip_embed_text",
        },
    }


def mock_pose(payload: PoseRequest) -> dict:
    pose_quality_score = 0.86
    pose_quality_status = "usable"
    quality_checks = {
        "full_body_visible": {
            "passed": True,
            "confidence": 0.82,
            "message": "Full-body framing is usable for Try-on L2.",
        },
        "shoulders_detected": {
            "passed": True,
            "confidence": 0.70,
            "required_keypoints": ["left_shoulder", "right_shoulder"],
            "message": "Both shoulder keypoints are available.",
        },
        "hips_detected": {
            "passed": True,
            "confidence": 0.65,
            "required_keypoints": ["left_hip", "right_hip"],
            "message": "Both hip keypoints are available.",
        },
        "keypoint_confidence": {
            "passed": True,
            "min_confidence": 0.65,
            "average_confidence": 0.68,
            "message": "Keypoint confidence is sufficient for mock L2 analysis.",
        },
    }

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
            "height": 1440,
        },
        "keypoints_format": "coco_17",
        "keypoints": [
            {"name": "nose", "x": 540, "y": 180, "confidence": 0.70},
            {"name": "left_shoulder", "x": 410, "y": 390, "confidence": 0.70},
            {"name": "right_shoulder", "x": 670, "y": 398, "confidence": 0.70},
            {"name": "left_hip", "x": 440, "y": 760, "confidence": 0.65},
            {"name": "right_hip", "x": 650, "y": 755, "confidence": 0.65},
        ],
        "pose_quality_score": pose_quality_score,
        "pose_quality_status": pose_quality_status,
        "quality_checks": quality_checks,
        "pose_analysis": {
            "full_body_visible": True,
            "pose_quality_score": pose_quality_score,
            "pose_quality_status": pose_quality_status,
            "missing_keypoints": [],
            "quality_warnings": [],
            "improvement_tips": [
                "Use a straight full-body photo with both shoulders and hips visible.",
                "Keep arms slightly away from the torso for cleaner garment fitting.",
            ],
            "shoulder_balance": "unknown",
            "shoulder_tilt_degree": None,
            "posture_notes": ["目前使用展示模式，姿態分析僅供流程展示"],
            "fit_notes": ["可用於 Try-on / Magic Mirror 介面測試"],
        },
        "annotated_image_url": None,
        "message": "目前為 mock mode，已回傳展示用 pose keypoints",
    }
