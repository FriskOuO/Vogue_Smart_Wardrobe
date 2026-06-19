from pathlib import Path

from config import BASE_DIR


PROJECT_ROOT = BASE_DIR.parent


def resolve_image_path(image_path: str) -> str:
    path = Path(image_path)

    if path.is_absolute() and path.exists():
        return str(path)

    candidates = [
        PROJECT_ROOT / image_path,
        PROJECT_ROOT / "public" / image_path,
        PROJECT_ROOT / "storage" / "app" / "public" / image_path,
        PROJECT_ROOT / "public" / "storage" / image_path,
        BASE_DIR / image_path,
    ]

    for candidate in candidates:
        if candidate.exists():
            return str(candidate)

    return image_path
