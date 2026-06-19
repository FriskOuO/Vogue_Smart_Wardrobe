# VogueAI Smart Wardrobe 完整進度交接文件

更新日期：2026-06-19  
專案路徑：`C:/Users/User/Vogue_Smart_Wardrobe`  
本機模型來源：`C:/Users/User/smart_wardrobe/backend/models`

## 1. 專案目前定位

VogueAI Smart Wardrobe 目前已從單純展示版推進到「可本機真模型推論 + 外部 Try-on demo provider + Laravel 任務紀錄」的階段。核心功能包含衣櫥管理、衣物 AI 分析、CLIP + Qdrant 搜尋、Gemini 穿搭建議、YOLO 姿態檢查、Hugging Face IDM-VTON 真實試穿、Runway/Veo provider contract、Digital Twin workflow contract。

目前狀態不是最終商用版，但已可做較完整的人工展示與專題驗收。正式上架前仍需補 GPU runtime、Digital Twin 3D/avatar provider、外部模型 SLA/成本控管、部署環境與資安檢查。

## 2. 技術架構

- Laravel 12 + Blade + Vite + Tailwind：主要 Web App、使用者流程、資料庫、任務紀錄、provider adapters。
- Python FastAPI AI Service：本機 AI 模型推論與 Hugging Face Space wrapper。
- Qdrant：正式向量資料庫，用於 CLIP image/text embedding 搜尋。
- Gemini：穿搭顧問、聊天助理、文字理解。
- Hugging Face Space yisol/IDM-VTON：免費 demo / research prototype 的真實虛擬試穿 provider。
- SQLite / Laravel DB：目前本機開發資料庫與 ai_jobs 任務紀錄。

## 3. 本機模型整合狀態

| 能力 | Provider / 模型 | 狀態 | 接入位置 | 備註 |
| --- | --- | --- | --- | --- |
| 文字搜尋 embedding | fine-tuned CLIP `fashion_search_finetuned/model` | 已接入 | `ai_service/services/clip_embedding_service.py` | 512D vector，替換原 base CLIP repository |
| 圖片 embedding | fine-tuned CLIP `fashion_search_finetuned/model` | 已接入 | `/ai/embed/image` | 可 upsert 到 Qdrant |
| 衣物描述 | BLIP Large `blip-image-captioning-large` | 已接入 | `ai_service/services/blip_caption_service.py` | 替換 BLIP base |
| 進階衣物理解 | BLIP VQA `blip-vqa-base` | 已接入 | `ai_service/services/blip_vqa_service.py`, `/ai/vqa` | 問 garment type / color / pattern / material |
| 自動標籤 | ResNet50 multi-output classifier `fashion_multioutput_v4_smart_tuned.pth` | 已接入 | `ai_service/services/fashion_attribute_service.py` | category、color、season、occasion、style 等 |
| 姿態檢查 | YOLO11s Pose `yolo11s-pose.pt` | 已接入 | `ai_service/services/yolo_pose_service.py`, `/ai/pose` | `AI_MOCK_MODE=false` 時替換 mock |
| 虛擬試穿 | Hugging Face IDM-VTON | 已接入並成功人工驗收 | `ai_service/services/huggingface_idm_vton_service.py` | 免費 Space，非商用 SLA |
| 向量資料庫 | Qdrant | 已接入 | `ai_service/services/vector_store_service.py` | collection / upsert / search 已驗證 |

## 4. AI Service 重要設定

AI Service env 使用以下概念設定，實際 secret 不應寫入文件或 Git：

```env
AI_MOCK_MODE=false
LOCAL_MODEL_ROOT=C:/Users/User/smart_wardrobe/backend/models

EMBEDDING_PROVIDER=clip
EMBEDDING_MODEL=clip-vit-base-patch32
EMBEDDING_MODEL_REPOSITORY=C:/Users/User/smart_wardrobe/backend/models/fashion_search_finetuned/model

IMAGE_CAPTION_PROVIDER=blip
IMAGE_CAPTION_MODEL=Salesforce/blip-image-captioning-large
IMAGE_CAPTION_MODEL_REPOSITORY=C:/Users/User/smart_wardrobe/backend/models/blip-image-captioning-large

VQA_PROVIDER=blip_vqa
VQA_MODEL=Salesforce/blip-vqa-base
VQA_MODEL_REPOSITORY=C:/Users/User/smart_wardrobe/backend/models/blip-vqa-base

ATTRIBUTE_PROVIDER=fashion_multioutput
ATTRIBUTE_MODEL=fashion_multioutput_v4_smart_tuned
ATTRIBUTE_MODEL_REPOSITORY=C:/Users/User/smart_wardrobe/backend/models/fashion_multioutput_v4_smart_tuned/fashion_multioutput_v4_smart_tuned.pth

POSE_PROVIDER=yolo_pose
POSE_MODEL=yolo11s-pose
POSE_MODEL_REPOSITORY=C:/Users/User/smart_wardrobe/backend/models/yolo11s-pose/yolo11s-pose.pt

TRYON_PROVIDER=huggingface_idm_vton
TRYON_MODEL=idm-vton
TRYON_SPACE=yisol/IDM-VTON
TRYON_PUBLIC_BASE_URL=http://127.0.0.1:8001
TRYON_OUTPUT_DIR=static/tryon
```

注意：Hugging Face token 若有放，必須只放在本機 `.env`，不要寫入文件、不要提交 GitHub。

## 5. Laravel Provider 狀態

`php artisan vogueai:provider-matrix` 目前結果：

- PASS：Gemini 穿搭顧問。
- PASS：Gemini 聊天助理。
- PASS：Gemini 文字理解。
- PASS：CLIP 文字搜尋，本機 fine-tuned repository。
- PASS：CLIP 圖片向量，本機 fine-tuned repository。
- PASS：BLIP Large 衣物描述。
- PASS：BLIP VQA 進階衣物理解。
- PASS：多輸出分類衣物自動標籤。
- PASS：YOLO Pose 姿態檢查。
- PASS：Qdrant 正式向量資料庫。
- PASS：Hugging Face IDM-VTON 真實換裝模型。
- PASS：Runway / Veo 影片生成 provider contract 與設定。
- WARN：Digital Twin 3D / 多視角 / avatar provider contract 已存在，但尚未接正式 avatar provider endpoint / key。

## 6. 已完成的使用者功能

### 我的衣櫥

- 可登入後管理衣物。
- 可上傳衣物圖片。
- 可顯示衣物卡片與詳情。
- 圖片顯示已修正固定容器，不再因圖片比例不同導致卡片忽大忽小。
- 可重新分析衣物屬性。
- 可重新建立 embedding，供 AI Search 使用。
- 衣物 AI 分析目前會整合 multi-output classifier、BLIP Large caption、BLIP VQA。

### AI Search

- 已接入 fine-tuned CLIP text/image embedding。
- 已接 Qdrant collection / upsert / search。
- 搜尋結果可顯示相似度 metadata。
- AI 不可用時仍有 keyword fallback。
- 已通過 feature tests 與實際 Qdrant smoke。

### AI Stylist

- Gemini adapter 已接入。
- 可根據衣櫥資料、場合、天氣、正式程度、風格偏好產生穿搭建議。
- 可保存穿搭紀錄。
- 可回饋推薦結果。
- Gemini key 缺失時可安全 fallback，不會讓頁面崩潰。

### Try-on / 姿態

- YOLO Pose 已替換 mock 姿態檢查。
- 建立任務後會先做人物照片姿態品質檢查。
- Hugging Face IDM-VTON 已成功產生真實合成試穿圖。
- 試穿任務會保存 provider task id、status、output_url、error。
- 頁面會直接顯示成功輸出的合成圖。
- 最新 processing 任務具備自動查詢訊號，不需要連續手動刷新。
- 歷史失敗任務會保留錯誤，不會覆蓋成功紀錄。

### Runway / Veo

- Laravel external provider contract 已建立。
- Provider matrix 顯示設定通過。
- 任務紀錄、輪詢與失敗處理流程已具備基礎。
- 尚需再做實際影片生成額度與 API 回傳格式的端對端驗證。

### Digital Twin

- Digital Twin profile / closet analysis 工作流已存在。
- 可建立個人風格資料與衣櫥分析任務。
- Provider contract 已存在。
- 尚未接正式 3D / 多視角 / avatar provider，所以目前仍是唯一 provider warning。

## 7. 測試與驗證結果

本輪完成後的驗證：

```powershell
.\vendor\bin\pest.bat --no-coverage
# 128 passed, 705 assertions
```

```powershell
cd ai_service
.\.venv\Scripts\python.exe -m pytest tests -q
# 47 passed
```

```powershell
php artisan vogueai:manual-acceptance
# Summary: 0 failed, 1 warnings
```

```powershell
php artisan vogueai:provider-matrix
# Summary: 0 failed, 1 warnings
# warning: Digital Twin avatar provider 尚未接正式 endpoint / key
```

人工畫面驗收：

- Try-on 最新成功任務：POSE-0029。
- 狀態：SUCCESS。
- Provider Task：local_hf_tryon_cc45081cc891。
- 頁面已顯示合成試穿成果圖。

## 8. 啟動方式

常用三服務：

1. Laravel Web：
```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

2. Vite：
```powershell
npm run dev -- --host 127.0.0.1
```

3. AI Service：
```powershell
cd ai_service
.\.venv\Scripts\python.exe -m uvicorn main:app --host 127.0.0.1 --port 8001
```

4. Qdrant：
```powershell
.\start-qdrant.ps1
```

或使用專案既有 `start-all.ps1` 依本機設定啟動。

## 9. 人工檢查清單

### 衣櫥

- 登入後進入 `http://127.0.0.1:8000/closet`。
- 確認衣物圖片比例正常，不再被壓扁。
- 開一件衣物詳情，確認 AI 狀態、分類、顏色、描述可讀。
- 點重新分析或重新 embedding，確認成功或顯示可理解錯誤。

### AI Search

- 到 `/closet/ai-search`。
- 搜尋中文或英文，例如「黑色上衣」、「white shirt」。
- 確認結果有排序、相似度 metadata、fallback 狀態可讀。
- 若 Qdrant 未啟動，確認頁面不崩潰並回到 keyword fallback。

### AI Stylist

- 到 `/closet/stylist`。
- 輸入場合、天氣、正式程度、風格偏好。
- 確認 Gemini 建議可產生、可保存、可回饋。
- 若 Gemini 不可用，確認 fallback 提示清楚。

### Try-on

- 到 `/closet/try-on`。
- 選擇一件有圖片的衣物。
- 上傳正面全身人物照片。
- 建立任務後等待處理。
- 確認頁面顯示 YOLO 姿態品質與 Hugging Face 試穿任務狀態。
- 成功後確認合成圖直接顯示在任務紀錄中。
- 若失敗，確認錯誤碼與錯誤訊息可讀，且可建立新任務。

### Runway / Veo

- 到 Runway / 展示牆相關頁面。
- 建立影片任務。
- 確認任務紀錄、狀態、失敗處理可讀。
- 真實影片生成仍需再確認外部 provider quota 與實際回傳影片 URL。

### Digital Twin

- 到 `/workspace/digital-twin`。
- 建立 profile。
- 執行衣櫥分析。
- 確認資料能被 AI Stylist 使用。
- 真 3D/avatar provider 尚未完成，不要宣稱已能正式生成 avatar。

## 10. 已知限制與風險

- IDM-VTON 使用 Hugging Face public Space，可能排隊、睡眠、限流或失敗；適合學生專題 demo / research prototype，不是正式商用 SLA。
- 目前 Python venv 是 CPU 版 PyTorch；本機有 RTX 5060，但尚未安裝 CUDA 版 PyTorch。模型能跑，但 BLIP Large / VQA / CLIP / YOLO 首次載入和推論會較慢。
- fine-tuned CLIP 模型可載入並回傳 512D 向量，但搜尋品質仍需更多人工 query set 驗證。
- multi-output classifier 已接入，但標籤準確度仍需用真衣櫥圖片抽樣 QA。
- Runway / Veo contract pass 不等於已完成大量真影片生成驗收，仍需 provider quota 與真回傳 URL 測試。
- Digital Twin 仍缺正式 3D/avatar provider。
- `.env`、API key、模型權重、`ai_service/static/tryon` 輸出圖不應直接提交 GitHub。

## 11. 下一步建議

1. 安裝 CUDA 版 PyTorch，讓 RTX 5060 參與推論。
2. 建立 20 到 50 筆衣物圖片 QA set，檢查 multi-output classifier、BLIP Large、BLIP VQA 的標籤品質。
3. 建立 20 筆搜尋 query set，驗證 fine-tuned CLIP 搜尋品質，必要時再微調或調整 rerank。
4. 補 Try-on 任務隊列與更穩定的 background worker，避免 Web request 等待 Hugging Face。
5. 將成功的 IDM-VTON output 做本地保存與可重看，不要每次展示都重新消耗 provider。
6. 接正式 Digital Twin avatar provider，或先把 Digital Twin 明確定位為 profile / closet analysis。
7. 對 Runway / Veo 做一次真影片生成端對端驗收。
8. 上 GitHub 前跑 `php artisan vogueai:production-check`、`php artisan vogueai:upload-scope-review`、`php artisan vogueai:github-readiness`，確認沒有 `.env`、token、模型權重、輸出圖或大檔被列入。

