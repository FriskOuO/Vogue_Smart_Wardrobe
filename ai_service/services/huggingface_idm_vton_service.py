import shutil
import time
import uuid
from concurrent.futures import ThreadPoolExecutor
from pathlib import Path
from typing import Any, Dict, Optional
from urllib.parse import urlparse, unquote

from config import (
    AI_MOCK_MODE,
    BASE_DIR,
    TRYON_API_TOKEN,
    TRYON_MODEL,
    TRYON_OUTPUT_DIR,
    TRYON_PROVIDER,
    TRYON_PUBLIC_BASE_URL,
    TRYON_SPACE,
)
from schemas import TryOnGenerateRequest


TASKS: Dict[str, Dict[str, Any]] = {}
TASK_EXECUTOR = ThreadPoolExecutor(max_workers=2, thread_name_prefix="idm-vton")


def _base_contract(
    request_id: str,
    provider_task_id: Optional[str],
    status: str,
    mode: str,
) -> Dict[str, Any]:
    return {
        "schema_version": "v1",
        "request_id": request_id,
        "status": status,
        "mode": mode,
        "provider": TRYON_PROVIDER,
        "model": TRYON_MODEL,
        "provider_task_id": provider_task_id,
        "output_url": None,
    }


def _degraded_response(
    request_id: str,
    error_code: str = "HF_IDM_VTON_UNAVAILABLE",
    message: str = "Hugging Face IDM-VTON Space is unavailable. Fallback to pose analysis result.",
) -> Dict[str, Any]:
    return {
        **_base_contract(
            request_id=request_id,
            provider_task_id=None,
            status="degraded",
            mode="mock",
        ),
        "error_code": error_code,
        "message": message,
    }


def create_tryon_task(payload: TryOnGenerateRequest) -> Dict[str, Any]:
    if AI_MOCK_MODE or payload.mock_mode:
        return _degraded_response(
            request_id=payload.request_id,
            error_code="HF_IDM_VTON_MOCK_MODE",
            message="AI Service mock mode is enabled. Fallback to pose analysis result.",
        )

    provider_task_id = f"local_hf_tryon_{uuid.uuid4().hex[:12]}"
    response = {
        **_base_contract(
            request_id=payload.request_id,
            provider_task_id=provider_task_id,
            status="processing",
            mode="huggingface_space",
        ),
        "message": "IDM-VTON try-on job created",
    }
    TASKS[provider_task_id] = {
        **response,
        "_payload": payload.model_dump(),
    }
    TASK_EXECUTOR.submit(_run_tryon_task, provider_task_id, payload)

    return response


def get_tryon_status(provider_task_id: str) -> Dict[str, Any]:
    task = TASKS.get(provider_task_id)

    if task is None:
        return {
            **_base_contract(
                request_id="unknown",
                provider_task_id=provider_task_id,
                status="failed",
                mode="huggingface_space",
            ),
            "error_code": "TRYON_TASK_NOT_FOUND",
            "error_message": "Try-on task id was not found in the local AI Service task store.",
        }

    return _public_task(task)


def _run_tryon_task(provider_task_id: str, payload: TryOnGenerateRequest) -> None:
    started_at = time.time()

    try:
        result = _call_huggingface_idm_vton(payload)
        output_url = _copy_result_to_static(result, provider_task_id)

        TASKS[provider_task_id] = {
            **_base_contract(
                request_id=payload.request_id,
                provider_task_id=provider_task_id,
                status="success",
                mode="huggingface_space",
            ),
            "output_url": output_url,
            "raw_output": result if isinstance(result, list) else [str(result)],
            "latency_ms": round((time.time() - started_at) * 1000, 2),
            "message": "IDM-VTON try-on image generated successfully",
        }
    except Exception as exc:
        TASKS[provider_task_id] = {
            **_base_contract(
                request_id=payload.request_id,
                provider_task_id=provider_task_id,
                status="failed",
                mode="huggingface_space",
            ),
            "error_code": "HF_SPACE_ERROR",
            "error_message": str(exc) or "The Hugging Face Space returned an error or timed out.",
            "latency_ms": round((time.time() - started_at) * 1000, 2),
        }


def _public_task(task: Dict[str, Any]) -> Dict[str, Any]:
    return {
        key: value
        for key, value in task.items()
        if not key.startswith("_")
    }


def _call_huggingface_idm_vton(payload: TryOnGenerateRequest) -> Any:
    try:
        from gradio_client import Client, handle_file
    except Exception as exc:
        raise RuntimeError("gradio_client is not installed. Install ai_service/requirements.txt.") from exc

    client_kwargs: Dict[str, Any] = {}
    if TRYON_API_TOKEN:
        client_kwargs["token"] = TRYON_API_TOKEN

    client = Client(TRYON_SPACE, **client_kwargs)

    return client.predict(
        dict={
            "background": handle_file(_gradio_file_input(payload.person_image_url)),
            "layers": [],
            "composite": None,
        },
        garm_img=handle_file(_gradio_file_input(payload.clothing_image_url)),
        garment_des="a clothing item",
        is_checked=True,
        is_checked_crop=False,
        denoise_steps=30,
        seed=42,
        api_name="/tryon",
    )


def _copy_result_to_static(result: Any, provider_task_id: str) -> str:
    source = _extract_result_path(result)
    if source is None or not source.exists():
        raise RuntimeError("Hugging Face IDM-VTON returned no local image output.")

    output_dir = _output_dir()
    output_dir.mkdir(parents=True, exist_ok=True)
    destination = output_dir / f"{provider_task_id}.png"
    shutil.copyfile(source, destination)

    return f"{TRYON_PUBLIC_BASE_URL.rstrip('/')}/static/tryon/{destination.name}"


def _gradio_file_input(image_reference: str) -> str:
    local_path = _local_path_from_reference(image_reference)

    if local_path is not None and local_path.exists():
        return str(local_path)

    return image_reference


def _local_path_from_reference(image_reference: str) -> Optional[Path]:
    parsed = urlparse(image_reference)

    if parsed.scheme in ["", "file"]:
        path = Path(unquote(parsed.path if parsed.scheme == "file" else image_reference))
        if path.exists():
            return path

        if parsed.scheme == "":
            relative = unquote(parsed.path).lstrip("/")
            project_root = BASE_DIR.parent
            candidates = [
                project_root / "public" / relative,
                project_root / "public" / "storage" / relative.removeprefix("storage/"),
                project_root / "storage" / "app" / "public" / relative.removeprefix("storage/"),
            ]

            for candidate in candidates:
                if candidate.exists() and candidate.is_file():
                    return candidate

        return None

    if parsed.scheme not in ["http", "https"]:
        return None

    if parsed.hostname not in ["127.0.0.1", "localhost"]:
        return None

    relative = unquote(parsed.path).lstrip("/")
    project_root = BASE_DIR.parent
    candidates = [
        project_root / "public" / relative,
        project_root / "public" / "storage" / relative.removeprefix("storage/"),
        project_root / "storage" / "app" / "public" / relative.removeprefix("storage/"),
    ]

    for candidate in candidates:
        if candidate.exists() and candidate.is_file():
            return candidate

    return None


def _extract_result_path(result: Any) -> Optional[Path]:
    for candidate in _result_candidates(result):
        path = Path(candidate)
        if path.exists() and path.is_file():
            return path

    return None


def _result_candidates(result: Any):
    if isinstance(result, str):
        yield result
        return

    if isinstance(result, dict):
        direct_path = result.get("path") or result.get("name")
        if isinstance(direct_path, str):
            yield direct_path
        for value in result.values():
            if value is not direct_path:
                yield from _result_candidates(value)
        return

    if isinstance(result, (list, tuple)):
        for value in result:
            yield from _result_candidates(value)
        return

    for attribute in ("path", "name"):
        value = getattr(result, attribute, None)
        if isinstance(value, str):
            yield value


def _output_dir() -> Path:
    path = Path(TRYON_OUTPUT_DIR)
    if path.is_absolute():
        return path

    return BASE_DIR / path
