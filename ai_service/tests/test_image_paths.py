from pathlib import Path

from utils.image_paths import PROJECT_ROOT, resolve_image_path


def test_resolve_public_image_path():
    resolved = resolve_image_path("images/demo/white-shirt.jpg")

    assert resolved == str(PROJECT_ROOT / "public" / "images/demo/white-shirt.jpg")


def test_resolve_public_disk_storage_path(tmp_path, monkeypatch):
    storage_file = PROJECT_ROOT / "storage" / "app" / "public" / "clothes" / "path-test.jpg"
    storage_file.parent.mkdir(parents=True, exist_ok=True)
    storage_file.write_bytes(b"fake-image")

    try:
        resolved = resolve_image_path("clothes/path-test.jpg")

        assert resolved == str(storage_file)
    finally:
        storage_file.unlink(missing_ok=True)
