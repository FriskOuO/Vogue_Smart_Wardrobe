import importlib.util
import os
from pathlib import Path
from typing import Dict

from services.vector_store_service import qdrant_preflight_contract
from config import (
    ATTRIBUTE_MODEL_REPOSITORY,
    EMBEDDING_MODEL_REPOSITORY,
    IMAGE_CAPTION_MODEL_REPOSITORY,
    POSE_MODEL_REPOSITORY,
    VQA_MODEL_REPOSITORY,
)


def is_package_available(package_name: str) -> bool:
    return importlib.util.find_spec(package_name) is not None


def get_dependency_status() -> Dict[str, object]:
    """
    檢查 AI Service 常見依賴是否可用。

    狀態說明：
    - available：套件或 key 存在
    - missing：套件或 key 不存在
    - mock：目前使用 mock-first，尚未啟用真模型
    - disabled：目前功能刻意關閉
    """

    qdrant_available = is_package_available("qdrant_client")
    onnxruntime_available = is_package_available("onnxruntime")
    rembg_available = is_package_available("rembg")
    gradio_client_available = is_package_available("gradio_client")
    torch_available = is_package_available("torch")
    transformers_available = is_package_available("transformers")
    pillow_available = is_package_available("PIL")
    torchvision_available = is_package_available("torchvision")
    ultralytics_available = is_package_available("ultralytics")

    gemini_key = os.getenv("GEMINI_API_KEY")
    veo_key = os.getenv("VEO_API_KEY")
    brave_key = os.getenv("BRAVE_SEARCH_API_KEY")
    weather_key = os.getenv("WEATHER_API_KEY")

    return {
        "clip": "available" if torch_available and transformers_available and pillow_available else "mock",
        "blip": "available" if torch_available and transformers_available and pillow_available else "mock",
        "blip_vqa": "available" if torch_available and transformers_available and pillow_available else "mock",
        "fashion_attribute": "available" if torch_available and torchvision_available and pillow_available else "mock",
        "pillow": "available" if pillow_available else "missing",
        "torchvision": "available" if torchvision_available else "missing",
        "pose": "available" if torch_available and ultralytics_available else "mock",
        "ultralytics": "available" if ultralytics_available else "missing",
        "qdrant": "available" if qdrant_available else "missing",
        "onnxruntime": "available" if onnxruntime_available else "missing",
        "rembg": "available" if rembg_available else "missing",
        "gradio_client": "available" if gradio_client_available else "missing",
        "gemini_api_key": "available" if gemini_key else "missing",
        "veo_api_key": "available" if veo_key else "missing",
        "brave_search_api_key": "available" if brave_key else "missing",
        "weather_api_key": "available" if weather_key else "missing",
        "local_models": {
            "clip": "available" if Path(EMBEDDING_MODEL_REPOSITORY).exists() else "missing",
            "blip_caption": "available" if Path(IMAGE_CAPTION_MODEL_REPOSITORY).exists() else "missing",
            "blip_vqa": "available" if Path(VQA_MODEL_REPOSITORY).exists() else "missing",
            "fashion_attribute": "available" if Path(ATTRIBUTE_MODEL_REPOSITORY).exists() else "missing",
            "pose": "available" if Path(POSE_MODEL_REPOSITORY).exists() else "missing",
        },
    }


def get_vector_store_readiness() -> Dict[str, object]:
    """
    回傳 Qdrant/vector store 正式接入前的 readiness metadata。

    這裡只檢查本機設定與 client 套件是否存在，不主動連線外部服務，
    讓 mock/degraded 測試在沒有 Qdrant daemon 時仍然穩定。
    """

    preflight = qdrant_preflight_contract()
    vector_store = preflight["vector_store"]
    vector_store["next_steps"] = preflight["next_steps"]

    return vector_store


def get_degraded_reasons() -> Dict[str, str]:
    """
    回傳常見 degraded reason。
    後續 endpoint 可依照這裡的 key 回傳 degraded_reason。
    """

    qdrant_reason = (
        "QDRANT_CONNECTION_NOT_CHECKED"
        if is_package_available("qdrant_client")
        else "QDRANT_CLIENT_NOT_INSTALLED"
    )

    return {
        "qdrant": qdrant_reason,
        "onnxruntime": "ONNXRUNTIME_NOT_INSTALLED",
        "rembg": "REMBG_NOT_INSTALLED",
        "gemini_api_key": "GEMINI_API_KEY_MISSING",
        "veo_api_key": "VEO_API_KEY_MISSING",
        "brave_search_api_key": "BRAVE_SEARCH_API_KEY_MISSING",
        "weather_api_key": "WEATHER_API_KEY_MISSING",
    }
