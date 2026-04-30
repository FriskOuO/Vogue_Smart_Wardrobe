# AI Models Directory

此資料夾用於放置 VogueAI Smart Wardrobe 後續會使用的本機 AI 模型檔。

## 目前策略

目前專案採用 Mock-first 策略，因此不需要先放入真實模型檔。

Laravel 與 Python AI Service 會先完成：

- 衣物屬性辨識流程
- image embedding 流程
- text embedding 流程
- similar search 流程
- pose 流程

後續再逐步替換成真實模型。

## 建議資料夾結構

```text
models/
├─ clip/
│  └─ README.md
├─ blip/
│  └─ README.md
└─ yolo_pose/
   └─ README.md