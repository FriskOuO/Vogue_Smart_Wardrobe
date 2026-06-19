import importlib
import importlib.util
from functools import lru_cache
from typing import Dict, Iterable, Optional

from config import VQA_MODEL, VQA_MODEL_REPOSITORY, VQA_PROVIDER
from services.model_runtime import model_device, move_inputs
from utils.image_paths import resolve_image_path


DEFAULT_FASHION_QUESTIONS = {
    "garment_type": "What type of garment is this?",
    "primary_color": "What is the primary color of this garment?",
    "pattern": "What pattern does this garment have?",
    "material": "What material does this garment appear to be made of?",
}


def vqa_dependencies_available() -> bool:
    return all(
        importlib.util.find_spec(package) is not None
        for package in ["torch", "transformers", "PIL"]
    )


@lru_cache(maxsize=1)
def blip_vqa_model_bundle(model_repository: Optional[str] = None):
    transformers = importlib.import_module("transformers")
    repository = model_repository or VQA_MODEL_REPOSITORY
    processor = transformers.BlipProcessor.from_pretrained(repository)
    model = transformers.BlipForQuestionAnswering.from_pretrained(repository)
    device = model_device()
    model.to(device)
    model.eval()

    return model, processor, device


def blip_answer_questions(
    image_path: str,
    questions: Optional[Dict[str, str]] = None,
    model_repository: Optional[str] = None,
) -> Dict[str, object]:
    if VQA_PROVIDER != "blip":
        return _degraded("VQA_PROVIDER_NOT_BLIP", "VQA_PROVIDER is not blip.")

    if not vqa_dependencies_available():
        return _degraded("BLIP_VQA_DEPENDENCIES_NOT_INSTALLED", "BLIP VQA dependencies are missing.")

    try:
        torch = importlib.import_module("torch")
        image_module = importlib.import_module("PIL.Image")
        image = image_module.open(resolve_image_path(image_path)).convert("RGB")
        model, processor, device = blip_vqa_model_bundle(model_repository)
        answers = {}

        for key, question in (questions or DEFAULT_FASHION_QUESTIONS).items():
            inputs = move_inputs(
                processor(images=image, text=question, return_tensors="pt"),
                device,
            )
            with torch.no_grad():
                output = model.generate(**inputs, max_new_tokens=20)
            answers[key] = processor.decode(output[0], skip_special_tokens=True).strip()

        return {
            "status": "ready",
            "mode": "real_adapter",
            "provider": "blip",
            "model": VQA_MODEL,
            "model_repository": model_repository or VQA_MODEL_REPOSITORY,
            "device": device,
            "answers": answers,
            "fallback_required": False,
            "error_code": None,
            "error_message": None,
        }
    except Exception as exc:
        return _degraded("BLIP_VQA_FAILED", str(exc))


def _degraded(error_code: str, error_message: str) -> Dict[str, object]:
    return {
        "status": "degraded",
        "mode": "mock",
        "provider": VQA_PROVIDER,
        "model": VQA_MODEL,
        "model_repository": VQA_MODEL_REPOSITORY,
        "answers": {},
        "fallback_required": True,
        "error_code": error_code,
        "error_message": error_message,
    }
