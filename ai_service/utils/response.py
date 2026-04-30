from typing import Any, Dict, Optional


def success_response(
    request_id: str,
    data: Optional[Dict[str, Any]] = None,
    message: str = "AI 任務完成",
    mode: str = "model",
    schema_version: str = "v1",
) -> Dict[str, Any]:
    return {
        "schema_version": schema_version,
        "request_id": request_id,
        "status": "success",
        "mode": mode,
        "data": data or {},
        "message": message,
    }


def degraded_response(
    request_id: str,
    data: Optional[Dict[str, Any]] = None,
    message: str = "目前使用降級模式回傳展示用結果",
    mode: str = "mock",
    degraded_reason: str = "MOCK_MODE_ENABLED",
    schema_version: str = "v1",
) -> Dict[str, Any]:
    return {
        "schema_version": schema_version,
        "request_id": request_id,
        "status": "degraded",
        "mode": mode,
        "degraded_reason": degraded_reason,
        "data": data or {},
        "message": message,
    }


def failed_response(
    request_id: str,
    error_code: str,
    message: str,
    details: Optional[Dict[str, Any]] = None,
    schema_version: str = "v1",
) -> Dict[str, Any]:
    return {
        "schema_version": schema_version,
        "request_id": request_id,
        "status": "failed",
        "error": {
            "code": error_code,
            "message": message,
            "details": details or {},
        },
    }