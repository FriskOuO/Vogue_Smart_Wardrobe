<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>VogueAI | 編輯帳號</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="vogue-home-body antialiased">
    <div class="vogue-theme-layer vogue-theme-layer-dark" aria-hidden="true"></div>
    <div class="vogue-theme-layer vogue-theme-layer-light" aria-hidden="true"></div>

    <x-vogue-auth-nav />

    <main class="vogue-shell pb-16 md:pb-24 space-y-6">
        <section class="vogue-highlight reveal is-visible">
            <div>
                <p class="vogue-eyebrow">編輯帳號</p>
                <h2>帳號設定</h2>
                <p>集中管理個人資料、密碼與刪除帳號。</p>
            </div>
            <ul class="vogue-points">
                <li>更新姓名與電子郵件</li>
                <li>修改登入密碼</li>
                <li>需要時可進入刪除帳號確認流程</li>
            </ul>
        </section>

        <section class="vogue-section">
            <div class="grid gap-5 md:grid-cols-2">
                <article class="vogue-card reveal is-visible">
                    @include('profile.partials.update-profile-information-form')
                </article>

                <article class="vogue-card reveal is-visible">
                    @include('profile.partials.update-password-form')
                </article>
            </div>
        </section>

        <section id="danger-zone" class="vogue-section">
            <article class="vogue-card reveal is-visible">
                @include('profile.partials.delete-user-form')
            </article>
        </section>
    </main>
</body>
</html>
