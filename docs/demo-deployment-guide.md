# VogueAI Smart Wardrobe - Demo Deployment Guide

## 1. 文件目的

本文件用於整理 VogueAI Smart Wardrobe 的本機展示部署流程。

目前專案採用：

```text
Laravel 12 + Blade + Vite + Tailwind
Python FastAPI AI Service
SQLite
Mock-first / degraded AI 策略
```

展示時需要同時啟動三個服務：

```text
1. Laravel Web Server
2. Vite Frontend Dev Server
3. Python FastAPI AI Service
```

---

## 2. 專案目錄

預設專案位置：

```powershell
C:\Users\User\Vogue_Smart_Wardrobe
```

進入專案：

```powershell
cd C:\Users\User\Vogue_Smart_Wardrobe
```

---

## 3. 展示前檢查

### 3.1 必要檔案

```text
[ ] .env 存在
[ ] ai_service/.env 存在
[ ] database/database.sqlite 存在
[ ] storage link 已建立
[ ] vendor/ 存在
[ ] node_modules/ 存在
[ ] ai_service/.venv 存在
```

---

### 3.2 Laravel 環境檢查

```powershell
php -v
composer -V
php artisan --version
```

預期：

```text
PHP 可正常顯示版本
Composer 可正常顯示版本
Laravel artisan 可正常執行
```

---

### 3.3 Node / Vite 環境檢查

```powershell
node -v
npm -v
```

預期：

```text
Node 可正常顯示版本
npm 可正常顯示版本
```

---

### 3.4 Python AI Service 環境檢查

```powershell
cd ai_service
.venv\Scripts\activate
python --version
pip list
```

回到專案根目錄：

```powershell
cd ..
```

---

## 4. 初次安裝流程

若是第一次下載專案，請先執行以下步驟。

### 4.1 安裝 PHP 套件

```powershell
composer install
```

---

### 4.2 安裝 Node 套件

```powershell
npm install
```

---

### 4.3 建立 Laravel .env

```powershell
copy .env.example .env
php artisan key:generate
```

---

### 4.4 建立 SQLite DB

```powershell
New-Item database\database.sqlite -ItemType File -Force
```

---

### 4.5 建立 Storage Link

```powershell
php artisan storage:link
```

---

### 4.6 建立 Python venv

```powershell
cd ai_service
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
cd ..
```

---

## 5. .env 設定

### 5.1 Laravel .env 重要設定

`.env` 建議至少確認：

```env
APP_NAME=VogueAI
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=sqlite

AI_SERVICE_URL=http://127.0.0.1:8001
AI_INTERNAL_TOKEN=change_this_internal_ai_token
AI_TIMEOUT_SECONDS=30
AI_MOCK_MODE=true
```

---

### 5.2 Python AI Service .env

`ai_service/.env` 建議至少確認：

```env
APP_NAME=VogueAI-AI-Service
APP_ENV=local
HOST=127.0.0.1
PORT=8001

AI_INTERNAL_TOKEN=change_this_internal_ai_token
AI_MOCK_MODE=true

GEMINI_API_KEY=
VEO_API_KEY=
BRAVE_SEARCH_API_KEY=
WEATHER_API_KEY=
```

注意：

```text
Laravel .env 的 AI_INTERNAL_TOKEN 必須與 ai_service/.env 一致。
```

---

## 6. Migration 注意事項

目前專案曾出現重複 Telescope migration，因此不建議直接盲目執行：

```powershell
php artisan migrate
```

建議先檢查 migration 狀態：

```powershell
php artisan migrate:status
```

若要套用特定 migration，使用：

```powershell
php artisan migrate --path=database/migrations/指定檔案.php
```

---

## 7. 測試帳號

由 Seeder 建立：

```text
Admin
Email: admin.dev@vogueai.local
Password: Admin@123456
Role: admin

User
Email: demo.user@vogueai.local
Password: User@123456
Role: user
```

若資料庫是全新狀態，可執行：

```powershell
php artisan db:seed --force
```

---

## 8. 三個服務啟動方式

展示時請開三個 PowerShell 終端機。

---

### 8.1 終端機 1：Laravel

```powershell
cd C:\Users\User\Vogue_Smart_Wardrobe
php artisan serve
```

預期看到：

```text
Server running on [http://127.0.0.1:8000]
```

Laravel 網址：

```text
http://127.0.0.1:8000
```

---

### 8.2 終端機 2：Vite

```powershell
cd C:\Users\User\Vogue_Smart_Wardrobe
npm run dev
```

預期看到：

```text
VITE ready
Local: http://127.0.0.1:5173
```

---

### 8.3 終端機 3：Python AI Service

```powershell
cd C:\Users\User\Vogue_Smart_Wardrobe\ai_service
.venv\Scripts\activate
uvicorn main:app --host 127.0.0.1 --port 8001 --reload
```

預期看到：

```text
Uvicorn running on http://127.0.0.1:8001
```

AI Service Health：

```text
http://127.0.0.1:8001/health
```

---

## 9. 展示路線

### 9.1 登入

```text
http://127.0.0.1:8000/login
```

建議使用：

```text
demo.user@vogueai.local
User@123456
```

---

### 9.2 Smart Closet

```text
http://127.0.0.1:8000/smart-closet
http://127.0.0.1:8000/closet
http://127.0.0.1:8000/closet/create
```

展示重點：

```text
1. 新增衣物
2. 上傳圖片
3. AI attributes 分析
4. image embedding 寫入
5. 衣物詳細頁顯示 AI 結果
```

---

### 9.3 AI Search

```text
http://127.0.0.1:8000/closet/ai-search
```

展示重點：

```text
1. 搜尋「白色上衣」
2. 正常模式會呼叫 AI Service
3. 可展示 search mode
4. 可展示 fallback 設計
```

---

### 9.4 Try-on L1

```text
http://127.0.0.1:8000/closet/try-on
```

展示重點：

```text
1. 選擇衣物
2. 上傳人物照片
3. 建立 Try-on L1 任務
4. 顯示 mock pose / degraded keypoints
5. 顯示 posture notes / fit notes
```

---

### 9.5 Runway Video L1

```text
http://127.0.0.1:8000/workspace/runway-video
```

展示重點：

```text
1. 選擇衣物
2. 輸入影片風格
3. 建立 Runway Storyboard
4. 顯示 prompt
5. 顯示 4 個 storyboard scenes
```

---

### 9.6 Digital Twin L1

```text
http://127.0.0.1:8000/workspace/digital-twin
```

展示重點：

```text
1. 輸入身高
2. 輸入風格偏好
3. 輸入常見場合
4. 建立 Digital Twin Profile
5. 顯示 avatar placeholder / style summary / style tags
```

---

### 9.7 AI Service Health

```text
http://127.0.0.1:8001/health
```

展示重點：

```text
1. status=ok
2. mock_mode=true
3. dependencies 顯示 qdrant / clip / blip / pose 狀態
4. 說明缺依賴時會採 degraded 策略
```

---

## 10. 展示前測試指令

### 10.1 Laravel Feature Tests

```powershell
php artisan test tests/Feature/SmartClosetTest.php
php artisan test tests/Feature/AiSearchTest.php
php artisan test tests/Feature/AiJobsL1Test.php
```

目前預期：

```text
SmartClosetTest：5 passed
AiSearchTest：4 passed
AiJobsL1Test：5 passed
```

---

### 10.2 AI Service pytest

```powershell
cd ai_service
python -m pytest
```

---

## 11. 常見錯誤排除

### 11.1 Could not open input file: artisan

原因：

```text
目前不在 Laravel 專案根目錄。
```

修法：

```powershell
cd C:\Users\User\Vogue_Smart_Wardrobe
php artisan --version
```

---

### 11.2 Vite manifest not found

原因：

```text
Blade 使用 @vite，但沒有啟動 Vite 或沒有 build。
```

開發展示修法：

```powershell
npm run dev
```

若是 production build：

```powershell
npm run build
```

測試環境修法：

```php
$this->withoutVite();
```

---

### 11.3 AI Service connection refused

原因：

```text
Python AI Service 沒開，或 port 不是 8001。
```

修法：

```powershell
cd ai_service
.venv\Scripts\activate
uvicorn main:app --host 127.0.0.1 --port 8001 --reload
```

確認：

```text
http://127.0.0.1:8001/health
```

---

### 11.4 table telescope_entries already exists

原因：

```text
存在重複 Telescope migration。
```

修法：

```text
只保留一個 create_telescope_entries_table migration，
另一個移到 database/migrations_backup。
```

---

### 11.5 no such table: clothes / ai_jobs / ai_embeddings

原因：

```text
核心 migration 尚未執行。
```

修法：

```powershell
php artisan migrate --path=database/migrations/你的_create_clothes_table.php
php artisan migrate --path=database/migrations/你的_create_ai_embeddings_table.php
php artisan migrate --path=database/migrations/你的_create_ai_jobs_table.php
```

---

### 11.6 storage 圖片無法顯示

原因：

```text
public/storage link 未建立。
```

修法：

```powershell
php artisan storage:link
```

---

### 11.7 port already in use

查詢：

```powershell
netstat -ano | findstr :8000
netstat -ano | findstr :8001
netstat -ano | findstr :5173
```

結束指定 PID：

```powershell
taskkill /PID 你的PID /F
```

---

## 12. Demo 前最小檢查清單

```text
[ ] Laravel 可開 http://127.0.0.1:8000
[ ] Vite 已啟動
[ ] AI Service 可開 http://127.0.0.1:8001/health
[ ] 可登入 User 帳號
[ ] 可新增衣物
[ ] closet.show 可顯示 AI 分析
[ ] AI Search 可搜尋
[ ] AI Search fallback 可運作
[ ] Try-on L1 可建立任務
[ ] Runway Video L1 可建立 storyboard
[ ] Digital Twin L1 可建立 profile
[ ] README / docs 已更新
[ ] git status 沒有敏感檔 staged
```

---

## 13. 建議展示講法

```text
我們的系統採用 Laravel 主後端加 Python AI Service 的架構。Laravel 負責登入、頁面、權限、資料庫與圖片上傳；Python FastAPI 負責 AI 分析、embedding、相似搜尋與姿態分析。由於專題功能範圍較大，我們採用 Mock-first 與 degraded 策略，先確保每個功能都有完整資料流與任務狀態，再逐步替換成真實模型。
```

```text
目前 Smart Closet 已經能完成衣物上傳、AI 屬性分析、image embedding 寫入與以文搜圖。Try-on、Runway Video、Digital Twin 則已完成 L1 展示版，可以建立 ai_jobs 並顯示 mock / degraded 結果。這代表系統不是只有前端畫面，而是已經具備後端資料紀錄與 AI Service 串接基礎。
```

---

## 14. 下一步

下一步可進入：

```text
第 9 項：4 週里程碑與驗收整理
```

或視展示需求補強：

```text
1. start-all.ps1 一鍵啟動腳本
2. Laravel → AI Service integration smoke test
3. Admin CRUD Feature Test
4. Try-on L2 任務狀態與重新執行
```