from typing import Optional

from fastapi import HTTPException

from config import AI_INTERNAL_TOKEN


def check_internal_token(x_internal_ai_token: Optional[str]):
    if x_internal_ai_token != AI_INTERNAL_TOKEN:
        raise HTTPException(
            status_code=401,
            detail={
                "schema_version": "v1",
                "status": "failed",
                "error": {
                    "code": "AI_UNAUTHORIZED",
                    "message": "Internal token 錯誤"
                }
            }
        )