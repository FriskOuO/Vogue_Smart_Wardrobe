import os
from dotenv import load_dotenv

load_dotenv()

APP_NAME = os.getenv("APP_NAME", "VogueAI-AI-Service")
APP_ENV = os.getenv("APP_ENV", "local")
HOST = os.getenv("HOST", "127.0.0.1")
PORT = int(os.getenv("PORT", "8001"))

AI_INTERNAL_TOKEN = os.getenv("AI_INTERNAL_TOKEN", "change_this_internal_ai_token")
AI_MOCK_MODE = os.getenv("AI_MOCK_MODE", "true").lower() == "true"