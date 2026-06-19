<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>VogueAI | 帳號總覽</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="vogue-home-body antialiased">
    <div class="vogue-theme-layer vogue-theme-layer-dark" aria-hidden="true"></div>
    <div class="vogue-theme-layer vogue-theme-layer-light" aria-hidden="true"></div>

    <x-vogue-auth-nav />

    <main class="vogue-shell pb-16 md:pb-24 space-y-6">
        <section class="vogue-highlight reveal is-visible">
            <div>
                <p class="vogue-eyebrow">帳號總覽</p>
                <h2>{{ $user->name }}</h2>
                <p>{{ $user->email }}</p>
            </div>
            <ul class="vogue-points">
                <li>查看帳號資料與驗證狀態</li>
                <li>可前往編輯頁更新姓名、信箱與密碼</li>
                <li>危險操作集中在刪除區，避免誤觸</li>
            </ul>
        </section>

        <section class="vogue-section">
            <div class="vogue-grid">
                <article class="vogue-card reveal is-visible">
                    <h3>加入時間</h3>
                    <p>{{ optional($user->created_at)->format('Y-m-d H:i') }}</p>
                </article>
                <article class="vogue-card reveal is-visible">
                    <h3>電子郵件驗證</h3>
                    <p>{{ $user->email_verified_at ? __('profile.verified') : __('profile.not_verified') }}</p>
                </article>
                <article class="vogue-card reveal is-visible">
                    <h3>快捷操作</h3>
                    <div class="flex flex-wrap gap-2 pt-2">
                        <a href="{{ route('profile.edit') }}" class="vogue-btn vogue-btn-solid">編輯帳號</a>
                        <a href="{{ route('dashboard') }}" class="vogue-btn vogue-btn-soft">回到儀表板</a>
                    </div>
                </article>
            </div>
        </section>
    </main>
</body>
</html>
