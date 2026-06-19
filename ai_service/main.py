from fastapi import FastAPI
from fastapi.staticfiles import StaticFiles

from config import APP_NAME, AI_MOCK_MODE, BASE_DIR
from routes.ai_routes import router as ai_router
from utils.dependencies import get_dependency_status, get_vector_store_readiness

app = FastAPI(
    title=APP_NAME,
    description="Mock-first AI service for VogueAI Smart Wardrobe Laravel integration",
    version="0.2.0",
)

static_dir = BASE_DIR / "static"
static_dir.mkdir(parents=True, exist_ok=True)
app.mount("/static", StaticFiles(directory=str(static_dir)), name="static")
app.include_router(ai_router)


@app.get("/health")
def health():
    return {
        "status": "ok",
        "service": APP_NAME,
        "mock_mode": AI_MOCK_MODE,
        "version": "0.2.0",
        "dependencies": get_dependency_status(),
        "vector_store": get_vector_store_readiness(),
    }
