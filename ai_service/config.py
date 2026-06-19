import os
from pathlib import Path
from dotenv import load_dotenv

load_dotenv()

BASE_DIR = Path(__file__).resolve().parent
LOCAL_MODEL_ROOT = Path(os.getenv("LOCAL_MODEL_ROOT", BASE_DIR / "models"))

APP_NAME = os.getenv("APP_NAME", "VogueAI-AI-Service")
APP_ENV = os.getenv("APP_ENV", "local")
HOST = os.getenv("HOST", "127.0.0.1")
PORT = int(os.getenv("PORT", "8001"))

AI_INTERNAL_TOKEN = os.getenv("AI_INTERNAL_TOKEN", "change_this_internal_ai_token")
AI_MOCK_MODE = os.getenv("AI_MOCK_MODE", "true").lower() == "true"
EMBEDDING_PROVIDER = os.getenv("EMBEDDING_PROVIDER", "clip")
EMBEDDING_MODEL = os.getenv("EMBEDDING_MODEL", "clip-vit-base-patch32")
EMBEDDING_MODEL_REPOSITORY = os.getenv("EMBEDDING_MODEL_REPOSITORY", "openai/clip-vit-base-patch32")
VECTOR_STORE_PROVIDER = os.getenv("VECTOR_STORE_PROVIDER", "qdrant")
VECTOR_STORE_COLLECTION = os.getenv("VECTOR_STORE_COLLECTION", "vogueai_clothing_embeddings")
VECTOR_STORE_URL = os.getenv("VECTOR_STORE_URL", "http://127.0.0.1:6333")
VECTOR_STORE_API_KEY = os.getenv("VECTOR_STORE_API_KEY")
VECTOR_STORE_TARGET_VECTOR_SIZE = int(os.getenv("VECTOR_STORE_TARGET_VECTOR_SIZE", "512"))
VECTOR_STORE_ACTIVE_VECTOR_SIZE = int(os.getenv("VECTOR_STORE_ACTIVE_VECTOR_SIZE", "8"))
VECTOR_STORE_DISTANCE = os.getenv("VECTOR_STORE_DISTANCE", "Cosine")
VECTOR_STORE_TIMEOUT_SECONDS = float(os.getenv("VECTOR_STORE_TIMEOUT_SECONDS", "10"))
IMAGE_CAPTION_PROVIDER = os.getenv("IMAGE_CAPTION_PROVIDER", "blip")
IMAGE_CAPTION_MODEL = os.getenv("IMAGE_CAPTION_MODEL", "Salesforce/blip-image-captioning-base")
IMAGE_CAPTION_MODEL_REPOSITORY = os.getenv(
    "IMAGE_CAPTION_MODEL_REPOSITORY",
    "Salesforce/blip-image-captioning-base",
)
VQA_PROVIDER = os.getenv("VQA_PROVIDER", "blip")
VQA_MODEL = os.getenv("VQA_MODEL", "Salesforce/blip-vqa-base")
VQA_MODEL_REPOSITORY = os.getenv("VQA_MODEL_REPOSITORY", VQA_MODEL)
ATTRIBUTE_PROVIDER = os.getenv("ATTRIBUTE_PROVIDER", "fashion_multioutput")
ATTRIBUTE_MODEL = os.getenv("ATTRIBUTE_MODEL", "fashion_multioutput_v4_smart_tuned")
ATTRIBUTE_MODEL_REPOSITORY = os.getenv(
    "ATTRIBUTE_MODEL_REPOSITORY",
    str(LOCAL_MODEL_ROOT / "fashion_multioutput_v4_smart_tuned.pth"),
)
POSE_PROVIDER = os.getenv("POSE_PROVIDER", "yolo")
POSE_MODEL = os.getenv("POSE_MODEL", "yolo11s-pose")
POSE_MODEL_REPOSITORY = os.getenv(
    "POSE_MODEL_REPOSITORY",
    str(LOCAL_MODEL_ROOT / "yolo11s-pose.pt"),
)
MODEL_DEVICE = os.getenv("MODEL_DEVICE", "auto")
TRYON_PROVIDER = os.getenv("TRYON_PROVIDER", "huggingface_idm_vton")
TRYON_MODEL = os.getenv("TRYON_MODEL", "idm-vton")
TRYON_SPACE = os.getenv("TRYON_SPACE", "yisol/IDM-VTON")
TRYON_API_TOKEN = os.getenv("TRYON_API_TOKEN", os.getenv("AI_TRYON_API_KEY", ""))
TRYON_PUBLIC_BASE_URL = os.getenv("TRYON_PUBLIC_BASE_URL", f"http://{HOST}:{PORT}")
TRYON_OUTPUT_DIR = os.getenv("TRYON_OUTPUT_DIR", "static/tryon")
TRYON_TIMEOUT_SECONDS = float(os.getenv("TRYON_TIMEOUT_SECONDS", "180"))
_AI_MODEL_CACHE_DIR = os.getenv("AI_MODEL_CACHE_DIR")
AI_MODEL_CACHE_DIR = str(
    Path(_AI_MODEL_CACHE_DIR)
    if _AI_MODEL_CACHE_DIR and Path(_AI_MODEL_CACHE_DIR).is_absolute()
    else BASE_DIR / (_AI_MODEL_CACHE_DIR or "models/huggingface")
)

os.makedirs(AI_MODEL_CACHE_DIR, exist_ok=True)
os.environ.setdefault("HF_HOME", AI_MODEL_CACHE_DIR)
os.environ.setdefault("HUGGINGFACE_HUB_CACHE", str(Path(AI_MODEL_CACHE_DIR) / "hub"))
os.environ.setdefault("TRANSFORMERS_CACHE", str(Path(AI_MODEL_CACHE_DIR) / "transformers"))
os.environ.setdefault("YOLO_CONFIG_DIR", str(BASE_DIR / "models" / "ultralytics"))
os.environ.setdefault("ULTRALYTICS_SKIP_REQUIREMENTS_CHECKS", "1")
os.environ.setdefault("MPLCONFIGDIR", str(BASE_DIR / "models" / "matplotlib"))
os.makedirs(os.environ["YOLO_CONFIG_DIR"], exist_ok=True)
os.makedirs(os.environ["MPLCONFIGDIR"], exist_ok=True)
