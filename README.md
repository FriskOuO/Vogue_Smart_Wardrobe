# Vogue Smart Wardrobe

Vogue Smart Wardrobe 是一個以 Laravel + Blade + Vite + Tailwind 建置的智慧穿搭平台原型。  
目前專案採用「Laravel 主後端 + Python AI Service」架構，前端頁面優先完成高一致性的展示與操作流程，再分階段對接資料庫與 AI。

## 目前重點與分工

1. Laravel 主後端：頁面、登入、權限、資料庫、檔案上傳流程
2. Python FastAPI AI Service：屬性辨識、embedding、相似搜尋、姿態分析
3. 目前前端已先完成主要 Smart Closet 介面與模組工作台，便於後續後端接線

## 目前已完成

### 1) 統一 Vogue 視覺系統
1. 公開頁、登入註冊、Dashboard、Account、Profile Edit、Admin Users 視覺一致化
2. 支援中英文切換
3. 支援白晝 / 黑夜模式
4. 支援骨架屏與內容 reveal 動畫

### 2) 左側可伸縮導覽
1. 已改為左側可伸縮導覽列
2. 桌機：上方側欄按鈕可收合 / 展開左側欄
3. 手機：上方側欄按鈕開啟 / 關閉抽屜式側欄
4. README 模組區已改為「未完成暫放區」，方便組員辨識

### 3) Smart Closet 前端頁面（先行版）
1. Smart Closet Hub：`closet.hub`
2. My Closet 列表：`closet.index`
3. 新增衣物：`closet.create`
4. 儲存提交（示範模式）：`closet.store`
5. 衣物詳細：`closet.show`
6. AI Search：`closet.search`
7. AI Stylist：`closet.stylist`
8. Try-On / Pose：`closet.tryon`

### 4) README 模組未完成暫放區（Workspace）
以下模組已先做成統一工作台頁（展示用），供後端與 AI 後續對接：

1. AI Stylist
2. Virtual Try-On
3. Community
4. Blind Box
5. Runway Video
6. Chat Assistant
7. Showcase
8. Digital Twin
9. Travel Packer
10. Smart Storage
11. Quick Snap
12. Smart Tag
13. Magic Mirror
14. AI Bestie Call

對應路由：`workspace.show`（`/workspace/{module}`）

### 5) 帳號功能（一般使用者）
1. Account Overview（Read）
2. Profile Edit（Update 個資）
3. Password Update（Update 密碼）
4. Delete Account（Delete）
5. 表單送出可攜帶 locale，後端驗證訊息可配合語系

### 6) Admin / User 權限與管理
1. `users` 新增 `role` 欄位（`admin` / `user`）
2. 新增 `EnsureUserIsAdmin` middleware
3. `admin.users.*` 路由受 `auth + verified + admin` 保護
4. Admin 可管理使用者 CRUD（列表、建立、檢視、編輯、刪除，避免刪除自己）

## 測試帳號

由 `DatabaseSeeder` 建立：

1. Admin
   1. Email: `admin.dev@vogueai.local`
   2. Password: `Admin@123456`
   3. Role: `admin`
2. User
   1. Email: `demo.user@vogueai.local`
   2. Password: `User@123456`
   3. Role: `user`

## 技術棧

1. Backend: Laravel 12, PHP 8.2+
2. Frontend: Blade, Vite, TailwindCSS
3. Database: SQLite（開發環境）
4. Auth: Laravel Breeze（Session）+ 自訂帳號頁與角色權限
5. AI Service: Python FastAPI（Mock-first，可回傳 success / degraded / failed）

## 本機啟動

1. 安裝依賴

```bash
composer install
npm install
```

2. 設定環境

```bash
cp .env.example .env
php artisan key:generate
```

3. 遷移與種子

```bash
php artisan migrate --path=database/migrations/2026_04_24_090000_add_role_to_users_table.php --force
php artisan db:seed --force
```

4. 啟動

```bash
php artisan serve
npm run dev
```

## 重要注意事項

目前專案中存在重複的 Telescope migration：

1. `2026_04_22_161640_create_telescope_entries_table.php`
2. `2026_04_22_161722_create_telescope_entries_table.php`

在既有 SQLite 資料庫上執行完整 `php artisan migrate` 可能因重複建表失敗。  
若你只需套用特定變更，建議使用 `--path` 執行指定 migration。

## 後續建議

1. 將 Smart Closet / Workspace 頁面表單欄位對齊正式 API payload
2. 接入 `clothes`、`ai_embeddings`、`ai_jobs` 資料表與 Controller
3. 完成上傳後呼叫 AI 並寫回 DB 的流程
4. 補齊 Feature / Unit 測試與關鍵流程 E2E
