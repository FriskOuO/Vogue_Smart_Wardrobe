import importlib
import importlib.util
from functools import lru_cache
from typing import Dict, Optional

from config import (
    IMAGE_CAPTION_MODEL,
    IMAGE_CAPTION_MODEL_REPOSITORY,
    IMAGE_CAPTION_PROVIDER,
)
from utils.image_paths import resolve_image_path
from services.model_runtime import model_device, move_inputs


def is_package_available(package_name: str) -> bool:
    return importlib.util.find_spec(package_name) is not None


def blip_dependencies_status() -> Dict[str, str]:
    return {
        "torch": "available" if is_package_available("torch") else "missing",
        "transformers": "available" if is_package_available("transformers") else "missing",
        "pillow": "available" if is_package_available("PIL") else "missing",
    }


def blip_dependencies_available() -> bool:
    return all(value == "available" for value in blip_dependencies_status().values())


def blip_caption_provider_contract(locale: str = "zh_TW") -> Dict[str, object]:
    dependencies_available = blip_dependencies_available()
    provider_enabled = IMAGE_CAPTION_PROVIDER == "blip"

    if not provider_enabled:
        status = "disabled"
        degraded_reason = "IMAGE_CAPTION_PROVIDER_NOT_BLIP"
    elif not dependencies_available:
        status = "planned"
        degraded_reason = "BLIP_DEPENDENCIES_NOT_INSTALLED"
    else:
        status = "available"
        degraded_reason = None

    return {
        "target_provider": IMAGE_CAPTION_PROVIDER,
        "active_provider": "blip" if status == "available" else "mock_caption_fallback",
        "adapter": "blip-image-caption-v1",
        "target_model": IMAGE_CAPTION_MODEL,
        "model_repository": IMAGE_CAPTION_MODEL_REPOSITORY,
        "dependencies": blip_dependencies_status(),
        "status": status,
        "mode": "real_adapter" if status == "available" else "mock",
        "fallback_active": status != "available",
        "degraded_reason": degraded_reason,
        "caption_locale": locale,
        "adapter_methods": {
            "image_caption": "blip_generate_caption",
        },
    }


@lru_cache(maxsize=1)
def blip_model_bundle(model_repository: Optional[str] = None):
    transformers = importlib.import_module("transformers")
    processor = transformers.BlipProcessor.from_pretrained(
        model_repository or IMAGE_CAPTION_MODEL_REPOSITORY
    )
    model = transformers.BlipForConditionalGeneration.from_pretrained(
        model_repository or IMAGE_CAPTION_MODEL_REPOSITORY
    )
    model.to(model_device())
    model.eval()

    return model, processor


def torch_no_grad():
    torch = importlib.import_module("torch")
    return torch.no_grad()


def blip_open_image(image_path: str):
    image_module = importlib.import_module("PIL.Image")
    return image_module.open(resolve_image_path(image_path)).convert("RGB")


def blip_degraded_response(
    error_code: str,
    error_message: str,
    locale: str = "zh_TW",
) -> Dict[str, object]:
    return {
        "schema_version": "v1",
        "status": "degraded",
        "mode": "mock",
        "image_caption": blip_caption_provider_contract(locale=locale),
        "caption": None,
        "caption_locale": locale,
        "visual_tags": [],
        "fallback_required": True,
        "error_code": error_code,
        "error_message": error_message,
    }


def blip_generate_caption(
    image_path: str,
    locale: str = "zh_TW",
    model_repository: Optional[str] = None,
) -> Dict[str, object]:
    if IMAGE_CAPTION_PROVIDER != "blip":
        return blip_degraded_response(
            "IMAGE_CAPTION_PROVIDER_NOT_BLIP",
            "IMAGE_CAPTION_PROVIDER is not blip.",
            locale=locale,
        )

    if not blip_dependencies_available():
        return blip_degraded_response(
            "BLIP_DEPENDENCIES_NOT_INSTALLED",
            "Install torch, transformers, and pillow before running BLIP captions.",
            locale=locale,
        )

    try:
        image = blip_open_image(image_path)
        model, processor = blip_model_bundle(model_repository)
        inputs = processor(image, return_tensors="pt")
        inputs = move_inputs(inputs, model_device())

        with torch_no_grad():
            output = model.generate(**inputs)

        captions = processor.batch_decode(output, skip_special_tokens=True)
        caption = captions[0].strip() if captions else ""

        return {
            "schema_version": "v1",
            "status": "ready" if caption else "degraded",
            "mode": "real_adapter",
            "image_caption": blip_caption_provider_contract(locale=locale),
            "caption": caption,
            "caption_locale": locale,
            "visual_tags": [tag for tag in caption.lower().replace(",", " ").split()[:8] if tag],
            "grounding": {
                "image_path": image_path,
            },
            "fallback_required": not bool(caption),
            "error_code": None if caption else "BLIP_EMPTY_CAPTION",
            "error_message": None if caption else "BLIP returned an empty caption.",
        }
    except Exception as exc:
        return blip_degraded_response(
            "BLIP_CAPTION_FAILED",
            str(exc),
            locale=locale,
        )
