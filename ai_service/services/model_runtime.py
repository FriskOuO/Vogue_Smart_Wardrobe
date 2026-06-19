import importlib

from config import MODEL_DEVICE


def torch_module():
    return importlib.import_module("torch")


def model_device() -> str:
    torch = torch_module()

    if MODEL_DEVICE != "auto":
        return MODEL_DEVICE

    return "cuda" if torch.cuda.is_available() else "cpu"


def move_inputs(inputs, device: str):
    return inputs.to(device) if hasattr(inputs, "to") else inputs
