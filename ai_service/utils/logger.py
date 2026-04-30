import logging
import time
from typing import Optional


logger = logging.getLogger("vogueai_ai_service")

if not logger.handlers:
    handler = logging.StreamHandler()
    formatter = logging.Formatter(
        "[%(levelname)s] %(asctime)s %(name)s - %(message)s"
    )
    handler.setFormatter(formatter)
    logger.addHandler(handler)

logger.setLevel(logging.INFO)


def log_ai_request(
    request_id: str,
    endpoint: str,
    status: str,
    mode: Optional[str] = None,
    user_id: Optional[int] = None,
    clothing_id: Optional[int] = None,
    error_code: Optional[str] = None,
    started_at: Optional[float] = None,
) -> None:
    latency_ms = None

    if started_at is not None:
        latency_ms = round((time.time() - started_at) * 1000, 2)

    logger.info(
        "request_id=%s endpoint=%s status=%s mode=%s user_id=%s clothing_id=%s error_code=%s latency_ms=%s",
        request_id,
        endpoint,
        status,
        mode,
        user_id,
        clothing_id,
        error_code,
        latency_ms,
    )