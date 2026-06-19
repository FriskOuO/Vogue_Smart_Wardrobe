from services import blip_caption_service
from config import IMAGE_CAPTION_MODEL_REPOSITORY


class FakeNoGrad:
    def __enter__(self):
        return None

    def __exit__(self, exc_type, exc, traceback):
        return False


class FakeProcessor:
    def __init__(self, caption="a clean white shirt on a hanger"):
        self.caption = caption

    def __call__(self, image, return_tensors):
        return {
            "image": image,
            "return_tensors": return_tensors,
        }

    def batch_decode(self, output, skip_special_tokens):
        return [self.caption]


class FakeModel:
    def generate(self, **inputs):
        return [["fake-token-ids"]]


def test_blip_generate_caption_success_path(monkeypatch):
    monkeypatch.setattr(blip_caption_service, "is_package_available", lambda package: True)
    monkeypatch.setattr(blip_caption_service, "blip_open_image", lambda image_path: "fake-image")
    monkeypatch.setattr(blip_caption_service, "blip_model_bundle", lambda model_repository=None: (FakeModel(), FakeProcessor()))
    monkeypatch.setattr(blip_caption_service, "torch_no_grad", lambda: FakeNoGrad())

    result = blip_caption_service.blip_generate_caption(
        image_path="clothes/7/image.jpg",
        locale="zh_TW",
    )

    assert result["status"] == "ready"
    assert result["mode"] == "real_adapter"
    assert result["caption"] == "a clean white shirt on a hanger"
    assert result["caption_locale"] == "zh_TW"
    assert result["image_caption"]["active_provider"] == "blip"
    assert result["image_caption"]["model_repository"] == IMAGE_CAPTION_MODEL_REPOSITORY
    assert result["image_caption"]["adapter_methods"]["image_caption"] == "blip_generate_caption"
    assert result["grounding"]["image_path"] == "clothes/7/image.jpg"
    assert result["fallback_required"] is False
    assert result["error_code"] is None
    assert "shirt" in result["visual_tags"]


def test_blip_generate_caption_missing_dependencies_is_degraded(monkeypatch):
    monkeypatch.setattr(blip_caption_service, "is_package_available", lambda package: False)

    result = blip_caption_service.blip_generate_caption(
        image_path="clothes/7/image.jpg",
        locale="zh_TW",
    )

    assert result["status"] == "degraded"
    assert result["mode"] == "mock"
    assert result["caption"] is None
    assert result["fallback_required"] is True
    assert result["error_code"] == "BLIP_DEPENDENCIES_NOT_INSTALLED"
    assert result["image_caption"]["fallback_active"] is True


def test_blip_generate_caption_empty_caption_is_degraded(monkeypatch):
    monkeypatch.setattr(blip_caption_service, "is_package_available", lambda package: True)
    monkeypatch.setattr(blip_caption_service, "blip_open_image", lambda image_path: "fake-image")
    monkeypatch.setattr(blip_caption_service, "blip_model_bundle", lambda model_repository=None: (FakeModel(), FakeProcessor(caption="")))
    monkeypatch.setattr(blip_caption_service, "torch_no_grad", lambda: FakeNoGrad())

    result = blip_caption_service.blip_generate_caption(
        image_path="clothes/7/image.jpg",
        locale="zh_TW",
    )

    assert result["status"] == "degraded"
    assert result["caption"] == ""
    assert result["fallback_required"] is True
    assert result["error_code"] == "BLIP_EMPTY_CAPTION"
