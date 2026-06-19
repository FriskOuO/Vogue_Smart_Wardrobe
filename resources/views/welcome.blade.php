<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'VogueAI') }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="vogue-home-body antialiased">
    <div class="vogue-theme-layer vogue-theme-layer-dark" aria-hidden="true"></div>
    <div class="vogue-theme-layer vogue-theme-layer-light" aria-hidden="true"></div>

    <main class="vogue-shell min-h-screen flex items-center">
        <section class="vogue-highlight w-full">
            <div>
                <p class="vogue-eyebrow">VogueAI</p>
                <h1>智慧衣櫥工作台</h1>
                <p>從衣物整理、AI 搜尋、穿搭建議到試穿流程，集中在同一個舒服的介面。</p>
            </div>
            <div class="flex flex-wrap gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="vogue-btn vogue-btn-solid">進入儀表板</a>
                @else
                    <a href="{{ route('login') }}" class="vogue-btn vogue-btn-solid">登入</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="vogue-btn vogue-btn-soft">註冊</a>
                    @endif
                @endauth
            </div>
        </section>
    </main>
</body>
</html>
