import importlib
import importlib.util
import math
from functools import lru_cache
from typing import Dict, List, Optional

from config import (
    EMBEDDING_MODEL,
    EMBEDDING_MODEL_REPOSITORY,
    EMBEDDING_PROVIDER,
    VECTOR_STORE_TARGET_VECTOR_SIZE,
)
from utils.image_paths import resolve_image_path
from services.model_runtime import model_device, move_inputs


def is_package_available(package_name: str) -> bool:
    return importlib.util.find_spec(package_name) is not None


def clip_dependencies_status() -> Dict[str, str]:
    return {
        "torch": "available" if is_package_available("torch") else "missing",
        "transformers": "available" if is_package_available("transformers") else "missing",
        "pillow": "available" if is_package_available("PIL") else "missing",
    }


def clip_dependencies_available(embedding_type: str) -> bool:
    status = clip_dependencies_status()

    if embedding_type == "image":
        return all(value == "available" for value in status.values())

    return (
        status["torch"] == "available"
        and status["transformers"] == "available"
    )


def clip_embedding_provider_contract(embedding_type: str) -> Dict[str, object]:
    dependencies_available = clip_dependencies_available(embedding_type)
    provider_enabled = EMBEDDING_PROVIDER == "clip"

    if not provider_enabled:
        status = "disabled"
        degraded_reason = "EMBEDDING_PROVIDER_NOT_CLIP"
    elif not dependencies_available:
        status = "planned"
        degraded_reason = "CLIP_DEPENDENCIES_NOT_INSTALLED"
    else:
        status = "available"
        degraded_reason = None

    return {
        "target_provider": EMBEDDING_PROVIDER,
        "active_provider": "clip" if status == "available" else "mock_embedding_fallback",
        "adapter": "clip-embedding-v1",
        "embedding_type": embedding_type,
        "target_model": EMBEDDING_MODEL,
        "model_repository": EMBEDDING_MODEL_REPOSITORY,
        "target_vector_size": VECTOR_STORE_TARGET_VECTOR_SIZE,
        "dependencies": clip_dependencies_status(),
        "status": status,
        "mode": "real_adapter" if status == "available" else "mock",
        "fallback_active": status != "available",
        "degraded_reason": degraded_reason,
        "adapter_methods": {
            "image": "clip_embed_image",
            "text": "clip_embed_text",
        },
    }


@lru_cache(maxsize=2)
def clip_model_bundle(model_repository: Optional[str] = None):
    transformers = importlib.import_module("transformers")
    model = transformers.CLIPModel.from_pretrained(model_repository or EMBEDDING_MODEL_REPOSITORY)
    processor = transformers.CLIPProcessor.from_pretrained(model_repository or EMBEDDING_MODEL_REPOSITORY)
    model.to(model_device())
    model.eval()

    return model, processor


def resolve_model_repository(model: Optional[str] = None) -> str:
    if not model or model == EMBEDDING_MODEL:
        return EMBEDDING_MODEL_REPOSITORY

    return model


def torch_no_grad():
    torch = importlib.import_module("torch")
    return torch.no_grad()


def clip_open_image(image_path: str):
    image_module = importlib.import_module("PIL.Image")
    return image_module.open(resolve_image_path(image_path)).convert("RGB")


def normalize_embedding(raw_embedding) -> List[float]:
    if hasattr(raw_embedding, "pooler_output") and raw_embedding.pooler_output is not None:
        raw_embedding = raw_embedding.pooler_output
    elif isinstance(raw_embedding, dict) and raw_embedding.get("pooler_output") is not None:
        raw_embedding = raw_embedding["pooler_output"]

    if hasattr(raw_embedding, "detach"):
        raw_embedding = raw_embedding.detach()

    if hasattr(raw_embedding, "cpu"):
        raw_embedding = raw_embedding.cpu()

    if hasattr(raw_embedding, "tolist"):
        raw_embedding = raw_embedding.tolist()

    if raw_embedding and isinstance(raw_embedding[0], list):
        raw_embedding = raw_embedding[0]

    vector = [float(value) for value in raw_embedding]
    magnitude = math.sqrt(sum(value * value for value in vector))

    if magnitude == 0:
        return vector

    return [value / magnitude for value in vector]


def clip_dimension_validation(vector: List[float], vector_name: str) -> Dict[str, object]:
    matches_target = len(vector) == VECTOR_STORE_TARGET_VECTOR_SIZE

    return {
        "vector_name": vector_name,
        "expected_vector_size": VECTOR_STORE_TARGET_VECTOR_SIZE,
        "actual_vector_size": len(vector),
        "matches_target": matches_target,
        "qdrant_ready": matches_target,
        "fallback_required": not matches_target,
        "error_code": None if matches_target else "CLIP_VECTOR_DIMENSION_MISMATCH",
        "message": (
            "CLIP vector dimension matches Qdrant target collection."
            if matches_target
            else (
                f"CLIP vector is {len(vector)}D but Qdrant target collection "
                f"requires {VECTOR_STORE_TARGET_VECTOR_SIZE}D."
            )
        ),
    }


def clip_degraded_response(
    embedding_type: str,
    vector_name: str,
    error_code: str,
    error_message: str,
) -> Dict[str, object]:
    return {
        "schema_version": "v1",
        "status": "degraded",
        "mode": "mock",
        "embedding_type": embedding_type,
        "model": None,
        "target_model": EMBEDDING_MODEL,
        "embedding_provider": clip_embedding_provider_contract(embedding_type),
        "vector_dimension": 0,
        "embedding": [],
        "embedding_preview": [],
        "dimension_validation": clip_dimension_validation([], vector_name),
        "fallback_required": True,
        "error_code": error_code,
        "error_message": error_message,
    }


def clip_embed_text(query: str, model: Optional[str] = None) -> Dict[str, object]:
    embedding_type = "text"
    vector_name = "clip_text"
    normalized_query = query.strip()

    if EMBEDDING_PROVIDER != "clip":
        return clip_degraded_response(
            embedding_type,
            vector_name,
            "EMBEDDING_PROVIDER_NOT_CLIP",
            "EMBEDDING_PROVIDER is not clip.",
        )

    if not normalized_query:
        return clip_degraded_response(
            embedding_type,
            vector_name,
            "CLIP_TEXT_QUERY_EMPTY",
            "query cannot be empty.",
        )

    if not clip_dependencies_available(embedding_type):
        return clip_degraded_response(
            embedding_type,
            vector_name,
            "CLIP_DEPENDENCIES_NOT_INSTALLED",
            "Install torch and transformers before running CLIP text embeddings.",
        )

    try:
        resolved_model = resolve_model_repository(model)
        clip_model, processor = clip_model_bundle(resolved_model)
        inputs = processor(
            text=[normalized_query],
            return_tensors="pt",
            padding=True,
            truncation=True,
        )
        inputs = move_inputs(inputs, model_device())

        with torch_no_grad():
            raw_embedding = clip_model.get_text_features(**inputs)

        embedding = normalize_embedding(raw_embedding)
        dimension_validation = clip_dimension_validation(embedding, vector_name)

        return {
            "schema_version": "v1",
            "status": "ready" if dimension_validation["matches_target"] else "degraded",
            "mode": "real_adapter",
            "embedding_type": embedding_type,
            "model": resolved_model,
            "target_model": EMBEDDING_MODEL,
            "embedding_provider": clip_embedding_provider_contract(embedding_type),
            "vector_dimension": len(embedding),
            "query": query,
            "normalized_query": normalized_query,
            "embedding": embedding,
            "embedding_preview": embedding[:4],
            "dimension_validation": dimension_validation,
            "fallback_required": not dimension_validation["matches_target"],
            "error_code": dimension_validation["error_code"],
            "error_message": None if dimension_validation["matches_target"] else dimension_validation["message"],
        }
    except Exception as exc:
        return clip_degraded_response(
            embedding_type,
            vector_name,
            "CLIP_TEXT_EMBEDDING_FAILED",
            str(exc),
        )


def clip_embed_image(image_path: str, model: Optional[str] = None) -> Dict[str, object]:
    embedding_type = "image"
    vector_name = "clip_image"

    if EMBEDDING_PROVIDER != "clip":
        return clip_degraded_response(
            embedding_type,
            vector_name,
            "EMBEDDING_PROVIDER_NOT_CLIP",
            "EMBEDDING_PROVIDER is not clip.",
        )

    if not clip_dependencies_available(embedding_type):
        return clip_degraded_response(
            embedding_type,
            vector_name,
            "CLIP_DEPENDENCIES_NOT_INSTALLED",
            "Install torch, transformers, and pillow before running CLIP image embeddings.",
        )

    try:
        image = clip_open_image(image_path)
        resolved_model = resolve_model_repository(model)
        clip_model, processor = clip_model_bundle(resolved_model)
        inputs = processor(
            images=image,
            return_tensors="pt",
        )
        inputs = move_inputs(inputs, model_device())

        with torch_no_grad():
            raw_embedding = clip_model.get_image_features(**inputs)

        embedding = normalize_embedding(raw_embedding)
        dimension_validation = clip_dimension_validation(embedding, vector_name)

        return {
            "schema_version": "v1",
            "status": "ready" if dimension_validation["matches_target"] else "degraded",
            "mode": "real_adapter",
            "embedding_type": embedding_type,
            "model": resolved_model,
            "target_model": EMBEDDING_MODEL,
            "embedding_provider": clip_embedding_provider_contract(embedding_type),
            "vector_dimension": len(embedding),
            "image_path": image_path,
            "embedding": embedding,
            "embedding_preview": embedding[:4],
            "dimension_validation": dimension_validation,
            "fallback_required": not dimension_validation["matches_target"],
            "error_code": dimension_validation["error_code"],
            "error_message": None if dimension_validation["matches_target"] else dimension_validation["message"],
        }
    except Exception as exc:
        return clip_degraded_response(
            embedding_type,
            vector_name,
            "CLIP_IMAGE_EMBEDDING_FAILED",
            str(exc),
        )
