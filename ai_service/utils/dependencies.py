import importlib.util
import os
from typing import Dict


def is_package_available(package_name: str) -> bool:
    return importlib.util.find_spec(package_name) is not None


def get_dependency_status() -> Dict[str, str]:
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
    torch_available = is_package_available("torch")
    transformers_available = is_package_available("transformers")

    gemini_key = os.getenv("GEMINI_API_KEY")
    veo_key = os.getenv("VEO_API_KEY")
    brave_key = os.getenv("BRAVE_SEARCH_API_KEY")
    weather_key = os.getenv("WEATHER_API_KEY")

    return {
        "clip": "available" if torch_available and transformers_available else "mock",
        "blip": "available" if torch_available and transformers_available else "mock",
        "pose": "available" if torch_available else "mock",
        "qdrant": "available" if qdrant_available else "missing",
        "onnxruntime": "available" if onnxruntime_available else "missing",
        "rembg": "available" if rembg_available else "missing",
        "gemini_api_key": "available" if gemini_key else "missing",
        "veo_api_key": "available" if veo_key else "missing",
        "brave_search_api_key": "available" if brave_key else "missing",
        "weather_api_key": "available" if weather_key else "missing",
    }


def get_degraded_reasons() -> Dict[str, str]:
    """
    回傳常見缺依賴對應的 degraded reason。
    後續 endpoint 可依照這裡的 key 回傳 degraded_reason。
    """

    return {
        "qdrant": "QDRANT_CLIENT_NOT_INSTALLED",
        "onnxruntime": "ONNXRUNTIME_NOT_INSTALLED",
        "rembg": "REMBG_NOT_INSTALLED",
        "gemini_api_key": "GEMINI_API_KEY_MISSING",
        "veo_api_key": "VEO_API_KEY_MISSING",
        "brave_search_api_key": "BRAVE_SEARCH_API_KEY_MISSING",
        "weather_api_key": "WEATHER_API_KEY_MISSING",
    }