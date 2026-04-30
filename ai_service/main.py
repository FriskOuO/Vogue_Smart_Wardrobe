from fastapi import FastAPI

from config import APP_NAME, AI_MOCK_MODE
from routes.ai_routes import router as ai_router
from utils.dependencies import get_dependency_status

app = FastAPI(
    title=APP_NAME,
    description="Mock-first AI service for VogueAI Smart Wardrobe Laravel integration",
    version="0.2.0",
)

app.include_router(ai_router)


@app.get("/health")
def health():
    return {
        "status": "ok",
        "service": APP_NAME,
        "mock_mode": AI_MOCK_MODE,
        "version": "0.2.0",
        "dependencies": get_dependency_status(),
    }