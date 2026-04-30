from pydantic import BaseModel
from typing import Optional, List, Dict, Any


class AttributesRequest(BaseModel):
    schema_version: str
    request_id: str
    user_id: int
    clothing_id: int
    image_path: str
    image_url: Optional[str] = None
    locale: Optional[str] = "zh_TW"
    mock_mode: Optional[bool] = True


class ImageEmbeddingRequest(BaseModel):
    schema_version: str
    request_id: str
    user_id: int
    clothing_id: int
    image_path: str
    image_url: Optional[str] = None
    model: Optional[str] = "clip-vit-base-patch32"
    store_to_vector_db: Optional[bool] = True
    mock_mode: Optional[bool] = True


class TextEmbeddingRequest(BaseModel):
    schema_version: str
    request_id: str
    user_id: int
    query: str
    locale: Optional[str] = "zh_TW"
    model: Optional[str] = "clip-vit-base-patch32"
    mock_mode: Optional[bool] = True


class SimilarSearchRequest(BaseModel):
    schema_version: str
    request_id: str
    user_id: int
    query_type: str
    query: Optional[str] = None
    source_clothing_id: Optional[int] = None
    embedding: List[float]
    top_k: Optional[int] = 5
    filters: Optional[Dict[str, Any]] = {}
    fallback_enabled: Optional[bool] = True
    mock_mode: Optional[bool] = True


class PoseRequest(BaseModel):
    schema_version: str
    request_id: str
    user_id: int
    image_path: str
    image_url: Optional[str] = None
    task_type: Optional[str] = "magic_mirror"
    return_annotated_image: Optional[bool] = True
    mock_mode: Optional[bool] = True