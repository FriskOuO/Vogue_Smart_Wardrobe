import importlib
import importlib.util
from functools import lru_cache
from pathlib import Path
from typing import Dict

from config import ATTRIBUTE_MODEL, ATTRIBUTE_MODEL_REPOSITORY, ATTRIBUTE_PROVIDER
from services.model_runtime import model_device
from utils.image_paths import resolve_image_path


CLASSES = {
    "master": ["Accessories", "Apparel", "Footwear", "Free Items", "Personal Care", "Sporting Goods"],
    "sub": ["Accessories", "Apparel Set", "Bags", "Bath and Body", "Beauty Accessories", "Belts", "Bottomwear", "Cufflinks", "Dress", "Eyes", "Eyewear", "Flip Flops", "Fragrance", "Free Gifts", "Gloves", "Hair", "Headwear", "Innerwear", "Jewellery", "Lips", "Loungewear and Nightwear", "Makeup", "Mufflers", "Nails", "Perfumes", "Sandal", "Saree", "Scarves", "Shoe Accessories", "Shoes", "Skin", "Skin Care", "Socks", "Sports Accessories", "Sports Equipment", "Stoles", "Ties", "Topwear", "Umbrellas", "Vouchers", "Wallets", "Watches", "Water Bottle", "Wristbands"],
    "gender": ["Boys", "Girls", "Men", "Unisex", "Women"],
    "season": ["Fall", "Spring", "Summer", "Winter"],
    "usage": ["Casual", "Ethnic", "Formal", "Party", "Smart Casual", "Sports", "Travel"],
    "colour": ["Beige", "Black", "Blue", "Bronze", "Brown", "Burgundy", "Charcoal", "Coffee Brown", "Copper", "Cream", "Fluorescent Green", "Gold", "Green", "Grey", "Grey Melange", "Khaki", "Lavender", "Lime Green", "Magenta", "Maroon", "Mauve", "Metallic", "Multi", "Mushroom Brown", "Mustard", "Navy Blue", "Nude", "Off White", "Olive", "Orange", "Peach", "Pink", "Purple", "Red", "Rose", "Rust", "Sea Green", "Silver", "Skin", "Steel", "Tan", "Taupe", "Teal", "Turquoise Blue", "White", "Yellow"],
}

ZH = {
    "Apparel": "服飾", "Accessories": "配件", "Footwear": "鞋類", "Topwear": "上衣",
    "Bottomwear": "下身", "Dress": "洋裝", "Bags": "包款", "Shoes": "鞋款",
    "Spring": "春", "Summer": "夏", "Fall": "秋", "Winter": "冬",
    "Casual": "休閒", "Formal": "正式", "Party": "派對", "Sports": "運動",
    "Travel": "旅行", "Smart Casual": "都會休閒", "Ethnic": "民族風",
    "Black": "黑色", "White": "白色", "Blue": "藍色", "Red": "紅色",
    "Green": "綠色", "Grey": "灰色", "Brown": "棕色", "Pink": "粉色",
    "Purple": "紫色", "Yellow": "黃色", "Orange": "橘色", "Navy Blue": "深藍色",
    "Beige": "米色", "Cream": "奶油色", "Off White": "米白色", "Multi": "多色",
    "Men": "男裝", "Women": "女裝", "Unisex": "中性", "Boys": "男童", "Girls": "女童",
}


def attribute_dependencies_available() -> bool:
    return all(
        importlib.util.find_spec(package) is not None
        for package in ["torch", "torchvision", "PIL"]
    )


def _build_model():
    torch_nn = importlib.import_module("torch.nn")
    models = importlib.import_module("torchvision.models")

    class MultiOutputModel(torch_nn.Module):
        def __init__(self):
            super().__init__()
            resnet = models.resnet50(weights=None)
            self.backbone = torch_nn.Sequential(*list(resnet.children())[:-2])
            self.pool = torch_nn.AdaptiveAvgPool2d((1, 1))
            for key, labels in CLASSES.items():
                setattr(self, f"fc_{key}", torch_nn.Linear(2048, len(labels)))

        def forward(self, image):
            features = self.pool(self.backbone(image)).flatten(1)
            return {key: getattr(self, f"fc_{key}")(features) for key in CLASSES}

    return MultiOutputModel()


@lru_cache(maxsize=1)
def fashion_attribute_model_bundle(model_repository: str = ATTRIBUTE_MODEL_REPOSITORY):
    torch = importlib.import_module("torch")
    path = Path(model_repository)
    if not path.is_file():
        raise FileNotFoundError(f"Fashion attribute model not found: {path}")

    model = _build_model()
    state_dict = torch.load(path, map_location="cpu", weights_only=True)
    model.load_state_dict(state_dict)
    device = model_device()
    model.to(device)
    model.eval()

    return model, device


def predict_fashion_attributes(image_path: str) -> Dict[str, object]:
    if ATTRIBUTE_PROVIDER != "fashion_multioutput":
        return _degraded("ATTRIBUTE_PROVIDER_DISABLED", "ATTRIBUTE_PROVIDER is not fashion_multioutput.")
    if not attribute_dependencies_available():
        return _degraded("ATTRIBUTE_DEPENDENCIES_NOT_INSTALLED", "Fashion attribute dependencies are missing.")

    try:
        torch = importlib.import_module("torch")
        image_module = importlib.import_module("PIL.Image")
        transforms = importlib.import_module("torchvision.transforms")
        image = image_module.open(resolve_image_path(image_path)).convert("RGB")
        transform = transforms.Compose([
            transforms.Resize((224, 224)),
            transforms.ToTensor(),
            transforms.Normalize(mean=[0.485, 0.456, 0.406], std=[0.229, 0.224, 0.225]),
        ])
        model, device = fashion_attribute_model_bundle()

        with torch.no_grad():
            outputs = model(transform(image).unsqueeze(0).to(device))

        predictions = {}
        confidences = {}
        for key, logits in outputs.items():
            probabilities = torch.softmax(logits, dim=1)[0]
            index = int(probabilities.argmax().item())
            predictions[key] = CLASSES[key][index]
            confidences[key] = float(probabilities[index].item())

        return {
            "status": "ready",
            "mode": "real_adapter",
            "provider": ATTRIBUTE_PROVIDER,
            "model": ATTRIBUTE_MODEL,
            "model_repository": ATTRIBUTE_MODEL_REPOSITORY,
            "device": device,
            "predictions": predictions,
            "translated": {key: ZH.get(value, value) for key, value in predictions.items()},
            "confidence": confidences,
            "fallback_required": False,
            "error_code": None,
            "error_message": None,
        }
    except Exception as exc:
        return _degraded("FASHION_ATTRIBUTE_FAILED", str(exc))


def _degraded(error_code: str, error_message: str) -> Dict[str, object]:
    return {
        "status": "degraded",
        "mode": "mock",
        "provider": ATTRIBUTE_PROVIDER,
        "model": ATTRIBUTE_MODEL,
        "model_repository": ATTRIBUTE_MODEL_REPOSITORY,
        "predictions": {},
        "translated": {},
        "confidence": {},
        "fallback_required": True,
        "error_code": error_code,
        "error_message": error_message,
    }
