from schemas import AttributesRequest, ImageEmbeddingRequest, TextEmbeddingRequest
from services.blip_caption_service import blip_generate_caption
from services.blip_vqa_service import blip_answer_questions
from services.clip_embedding_service import clip_embed_image, clip_embed_text
from services.fashion_attribute_service import predict_fashion_attributes
from services.mock_ai_service import (
    mock_attributes,
    mock_image_embedding,
    mock_text_embedding,
)
from services.vector_store_service import (
    qdrant_dimension_validation,
    qdrant_upsert_clothing_embedding,
    qdrant_vector_store_contract,
)


def attach_real_adapter_attempt(fallback_response: dict, adapter_result: dict) -> dict:
    fallback_response["real_adapter_attempt"] = {
        "status": adapter_result.get("status"),
        "mode": adapter_result.get("mode"),
        "error_code": adapter_result.get("error_code"),
        "error_message": adapter_result.get("error_message"),
        "fallback_required": adapter_result.get("fallback_required", True),
    }

    return fallback_response


def attributes_response(payload: AttributesRequest) -> dict:
    fallback_response = mock_attributes(payload)

    if payload.mock_mode:
        return fallback_response

    classifier_result = predict_fashion_attributes(payload.image_path)
    caption_result = blip_generate_caption(
        image_path=payload.image_path,
        locale=payload.locale or "zh_TW",
    )
    vqa_result = blip_answer_questions(payload.image_path)

    if classifier_result.get("status") == "ready":
        translated = classifier_result["translated"]
        confidence = classifier_result["confidence"]
        vqa_answers = vqa_result.get("answers", {})
        usage = translated.get("usage", "未知")
        fallback_response["status"] = "success"
        fallback_response["mode"] = "real_adapter"
        fallback_response["degraded_reason"] = None
        fallback_response["attributes"] = {
            "category": translated.get("master"),
            "subcategory": translated.get("sub"),
            "color": translated.get("colour"),
            "secondary_colors": [],
            "season": [translated.get("season")] if translated.get("season") else [],
            "occasion": [usage] if usage else [],
            "usage": [usage] if usage else [],
            "style_tags": [translated.get("gender")] if translated.get("gender") else [],
            "material_guess": vqa_answers.get("material") or "未知",
            "pattern": vqa_answers.get("pattern") or "未知",
        }
        fallback_response["confidence"] = {
            "category": confidence.get("master"),
            "subcategory": confidence.get("sub"),
            "color": confidence.get("colour"),
            "season": confidence.get("season"),
            "occasion": confidence.get("usage"),
            "overall": sum(confidence.values()) / len(confidence) if confidence else None,
        }
        fallback_response["attribute_classifier"] = classifier_result

    if caption_result.get("status") == "ready":
        if classifier_result.get("status") != "ready":
            fallback_response["mode"] = "hybrid"
        fallback_response["image_caption"] = {
            **caption_result["image_caption"],
            "caption": caption_result["caption"],
            "caption_locale": caption_result["caption_locale"],
            "visual_tags": caption_result["visual_tags"],
            "grounding": {
                **caption_result.get("grounding", {}),
                "image_url": payload.image_url,
                "clothing_id": payload.clothing_id,
            },
        }

    fallback_response["visual_qa"] = vqa_result
    fallback_response["real_adapter_attempt"] = {
        "status": fallback_response["status"],
        "mode": fallback_response["mode"],
        "adapters": {
            "classifier": classifier_result,
            "caption": caption_result,
            "vqa": vqa_result,
        },
        "fallback_required": classifier_result.get("status") != "ready",
        "error_code": None if classifier_result.get("status") == "ready" else classifier_result.get("error_code"),
        "error_message": None if classifier_result.get("status") == "ready" else classifier_result.get("error_message"),
    }
    fallback_response["message"] = (
        "已使用本機多輸出分類、BLIP Large 與 BLIP VQA 完成衣物分析。"
        if classifier_result.get("status") == "ready"
        else "本機分類模型不可用，已保留降級衣物分析結果。"
    )

    return fallback_response


def image_embedding_response(payload: ImageEmbeddingRequest) -> dict:
    fallback_response = mock_image_embedding(payload)

    if payload.mock_mode:
        return fallback_response

    adapter_result = clip_embed_image(
        image_path=payload.image_path,
        model=payload.model,
    )

    if adapter_result.get("status") != "ready":
        return attach_real_adapter_attempt(fallback_response, adapter_result)

    upsert_result = None
    stored = False
    if payload.store_to_vector_db:
        upsert_result = qdrant_upsert_clothing_embedding(
            clothing_id=payload.clothing_id,
            user_id=payload.user_id,
            vector=adapter_result["embedding"],
            payload={
                "image_path": payload.image_path,
                "image_url": payload.image_url,
            },
            vector_name="clip_image",
        )
        stored = upsert_result.get("stored", False)

    vector_store = qdrant_vector_store_contract(
        stored=stored,
        connection_ready=stored,
    )
    dimension_validation = qdrant_dimension_validation(
        vector_length=len(adapter_result["embedding"]),
        vector_name="clip_image",
    )
    vector_store["dimension_validation"] = dimension_validation

    return {
        "schema_version": "v1",
        "request_id": payload.request_id,
        "status": "ready",
        "mode": "real_adapter",
        "embedding_type": "image",
        "model": adapter_result["model"],
        "target_model": adapter_result["target_model"],
        "embedding_provider": adapter_result["embedding_provider"],
        "vector_dimension": adapter_result["vector_dimension"],
        "clothing_id": payload.clothing_id,
        "embedding": adapter_result["embedding"],
        "embedding_preview": adapter_result["embedding_preview"],
        "dimension_validation": adapter_result["dimension_validation"],
        "vector_db": {
            "provider": vector_store["active_provider"],
            "target_provider": vector_store["target_provider"],
            "collection": (
                vector_store["collection"]
                if stored
                else vector_store["fallback_collection"]
            ),
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
            "point_id": (
                upsert_result.get("point_id")
                if upsert_result
                else payload.clothing_id
            ),
            "stored": stored,
            "upsert_result": upsert_result,
            "fallback_active": vector_store["fallback_active"],
            "connection_check": vector_store["connection_check"],
            "degraded_reason": vector_store["degraded_reason"],
        },
        "real_adapter_attempt": {
            "status": "ready",
            "mode": "real_adapter",
            "adapter": "clip-embedding-v1",
            "fallback_required": False,
        },
        "message": "已使用 CLIP adapter 產生 image embedding。",
    }


def text_embedding_response(payload: TextEmbeddingRequest) -> dict:
    fallback_response = mock_text_embedding(payload)

    if payload.mock_mode:
        return fallback_response

    adapter_result = clip_embed_text(
        query=payload.query,
        model=payload.model,
    )

    if adapter_result.get("status") != "ready":
        return attach_real_adapter_attempt(fallback_response, adapter_result)

    return {
        "schema_version": "v1",
        "request_id": payload.request_id,
        "status": "ready",
        "mode": "real_adapter",
        "embedding_type": "text",
        "model": adapter_result["model"],
        "target_model": adapter_result["target_model"],
        "embedding_provider": adapter_result["embedding_provider"],
        "vector_dimension": adapter_result["vector_dimension"],
        "query": payload.query,
        "normalized_query": adapter_result["normalized_query"],
        "embedding": adapter_result["embedding"],
        "embedding_preview": adapter_result["embedding_preview"],
        "dimension_validation": adapter_result["dimension_validation"],
        "real_adapter_attempt": {
            "status": "ready",
            "mode": "real_adapter",
            "adapter": "clip-embedding-v1",
            "fallback_required": False,
        },
        "message": "已使用 CLIP adapter 產生 text embedding。",
    }
