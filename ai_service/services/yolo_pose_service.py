import importlib
import importlib.util
import math
from functools import lru_cache
from typing import Dict

from config import POSE_MODEL, POSE_MODEL_REPOSITORY, POSE_PROVIDER
from services.model_runtime import model_device
from utils.image_paths import resolve_image_path


COCO_KEYPOINTS = [
    "nose", "left_eye", "right_eye", "left_ear", "right_ear",
    "left_shoulder", "right_shoulder", "left_elbow", "right_elbow",
    "left_wrist", "right_wrist", "left_hip", "right_hip",
    "left_knee", "right_knee", "left_ankle", "right_ankle",
]


@lru_cache(maxsize=1)
def yolo_pose_model_bundle(model_repository: str = POSE_MODEL_REPOSITORY):
    ultralytics = importlib.import_module("ultralytics")
    return ultralytics.YOLO(model_repository)


def analyze_yolo_pose(payload) -> Dict[str, object]:
    if POSE_PROVIDER != "yolo":
        return _degraded(payload, "POSE_PROVIDER_NOT_YOLO", "POSE_PROVIDER is not yolo.")
    if importlib.util.find_spec("ultralytics") is None:
        return _degraded(payload, "YOLO_DEPENDENCY_NOT_INSTALLED", "Install ultralytics before using YOLO Pose.")

    try:
        image_path = str(resolve_image_path(payload.image_path))
        model = yolo_pose_model_bundle()
        results = model.predict(source=image_path, device=model_device(), verbose=False)
        result = results[0]
        person_count = 0 if result.keypoints is None else len(result.keypoints)

        if person_count == 0:
            return _degraded(payload, "YOLO_NO_PERSON_DETECTED", "No person was detected in the image.")

        xy = result.keypoints.xy[0].cpu().tolist()
        confidence_tensor = result.keypoints.conf
        confidence = confidence_tensor[0].cpu().tolist() if confidence_tensor is not None else [0.0] * 17
        keypoints = [
            {"name": name, "x": round(float(xy[index][0]), 2), "y": round(float(xy[index][1]), 2), "confidence": round(float(confidence[index]), 4)}
            for index, name in enumerate(COCO_KEYPOINTS)
        ]
        by_name = {item["name"]: item for item in keypoints}
        required = ["left_shoulder", "right_shoulder", "left_hip", "right_hip"]
        required_confidence = [by_name[name]["confidence"] for name in required]
        full_body_confidence = min(by_name["left_ankle"]["confidence"], by_name["right_ankle"]["confidence"])
        average_confidence = sum(required_confidence) / len(required_confidence)
        full_body_visible = full_body_confidence >= 0.25
        usable = min(required_confidence) >= 0.35
        score = max(0.0, min(1.0, average_confidence * 0.8 + (0.2 if full_body_visible else 0.0)))
        left_shoulder = by_name["left_shoulder"]
        right_shoulder = by_name["right_shoulder"]
        shoulder_tilt = math.degrees(math.atan2(
            right_shoulder["y"] - left_shoulder["y"],
            right_shoulder["x"] - left_shoulder["x"],
        ))
        quality_status = "usable" if usable else "needs_improvement"
        missing = [name for name in required if by_name[name]["confidence"] < 0.35]

        return {
            "schema_version": "v1",
            "request_id": payload.request_id,
            "status": "success" if usable else "degraded",
            "mode": "real_adapter",
            "pose_model": POSE_MODEL,
            "pose_provider": POSE_PROVIDER,
            "model_repository": POSE_MODEL_REPOSITORY,
            "device": model_device(),
            "person_count": person_count,
            "image_size": {"width": int(result.orig_shape[1]), "height": int(result.orig_shape[0])},
            "keypoints_format": "coco_17",
            "keypoints": keypoints,
            "pose_quality_score": round(score, 4),
            "pose_quality_status": quality_status,
            "quality_checks": {
                "full_body_visible": {"passed": full_body_visible, "confidence": round(full_body_confidence, 4), "message": "Full-body visibility checked by YOLO Pose."},
                "shoulders_detected": {"passed": min(required_confidence[:2]) >= 0.35, "confidence": round(min(required_confidence[:2]), 4), "message": "Shoulder keypoints checked by YOLO Pose."},
                "hips_detected": {"passed": min(required_confidence[2:]) >= 0.35, "confidence": round(min(required_confidence[2:]), 4), "message": "Hip keypoints checked by YOLO Pose."},
                "keypoint_confidence": {"passed": usable, "average_confidence": round(average_confidence, 4), "message": "Required keypoint confidence checked by YOLO Pose."},
            },
            "pose_analysis": {
                "full_body_visible": full_body_visible,
                "pose_quality_score": round(score, 4),
                "pose_quality_status": quality_status,
                "missing_keypoints": missing,
                "quality_warnings": [] if usable else ["部分必要姿態關鍵點不夠清楚"],
                "improvement_tips": [] if usable else ["請使用正面全身照片，並確保肩膀與髖部清楚可見。"],
                "shoulder_balance": "balanced" if abs(shoulder_tilt) <= 5 else "tilted",
                "shoulder_tilt_degree": round(shoulder_tilt, 2),
                "posture_notes": ["已使用 YOLO Pose 完成真實姿態分析"],
                "fit_notes": ["姿態資料可供虛擬試穿使用"],
            },
            "annotated_image_url": None,
            "message": "YOLO Pose analysis completed.",
        }
    except Exception as exc:
        return _degraded(payload, "YOLO_POSE_FAILED", str(exc))


def _degraded(payload, error_code: str, error_message: str) -> Dict[str, object]:
    return {
        "schema_version": "v1",
        "request_id": payload.request_id,
        "status": "degraded",
        "mode": "mock",
        "pose_model": POSE_MODEL,
        "pose_provider": POSE_PROVIDER,
        "person_count": 0,
        "keypoints": [],
        "pose_analysis": None,
        "fallback_required": True,
        "error_code": error_code,
        "error_message": error_message,
    }
