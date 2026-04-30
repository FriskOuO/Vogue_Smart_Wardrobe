from schemas import (
    AttributesRequest,
    ImageEmbeddingRequest,
    TextEmbeddingRequest,
    SimilarSearchRequest,
    PoseRequest,
)


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
        "confidence": {
            "category": 0.70,
            "color": 0.70,
            "season": 0.60,
            "occasion": 0.60,
            "overall": 0.65,
        },
        "message": "目前為 mock mode，已回傳展示用衣物屬性",
    }


def mock_image_embedding(payload: ImageEmbeddingRequest) -> dict:
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
            "stored": True,
        },
        "message": "目前為 mock mode，已回傳展示用 image embedding",
    }


def mock_text_embedding(payload: TextEmbeddingRequest) -> dict:
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
        "message": "目前為 mock mode，已回傳展示用 text embedding",
    }


def mock_similar_search(payload: SimilarSearchRequest) -> dict:
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
        "search_provider": "mock",
        "top_k": payload.top_k,
        "results": [
            {
                "rank": 1,
                "clothing_id": 1,
                "score": 0.75,
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


def mock_pose(payload: PoseRequest) -> dict:
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
        "pose_analysis": {
            "full_body_visible": True,
            "shoulder_balance": "unknown",
            "shoulder_tilt_degree": None,
            "posture_notes": ["目前使用展示模式，姿態分析僅供流程展示"],
            "fit_notes": ["可用於 Try-on / Magic Mirror 介面測試"],
        },
        "annotated_image_url": None,
        "message": "目前為 mock mode，已回傳展示用 pose keypoints",
    }