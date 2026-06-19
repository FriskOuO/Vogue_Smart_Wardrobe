<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>VogueAI | 使用者資料</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="vogue-home-body antialiased">
    <div class="vogue-theme-layer vogue-theme-layer-dark" aria-hidden="true"></div>
    <div class="vogue-theme-layer vogue-theme-layer-light" aria-hidden="true"></div>

    <x-vogue-auth-nav />

    <main class="vogue-shell pb-16 md:pb-24 space-y-6">
        <section class="vogue-highlight reveal is-visible">
            <div>
                <p class="vogue-eyebrow">使用者資料</p>
                <h2>{{ $user->name }}</h2>
                <p>{{ $user->email }}</p>
            </div>
            <ul class="vogue-points">
                <li>查看角色與加入時間</li>
                <li>可直接前往編輯頁</li>
                <li>刪除操作需要再次確認</li>
            </ul>
        </section>

        <section class="vogue-grid">
            <article class="vogue-card reveal is-visible">
                <h3>{{ __('admin.name') }}</h3>
                <p>{{ $user->name }}</p>
            </article>
            <article class="vogue-card reveal is-visible">
                <h3>{{ __('admin.email') }}</h3>
                <p>{{ $user->email }}</p>
            </article>
            <article class="vogue-card reveal is-visible">
                <h3>{{ __('admin.role') }}</h3>
                <p>{{ __('admin.' . $user->role) }}</p>
            </article>
            <article class="vogue-card reveal is-visible">
                <h3>{{ __('admin.joined') }}</h3>
                <p>{{ optional($user->created_at)->format('Y-m-d H:i') }}</p>
            </article>
        </section>

        <section class="vogue-card reveal is-visible">
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.users.index') }}" class="vogue-btn vogue-btn-soft">{{ __('admin.back') }}</a>
                <a href="{{ route('admin.users.edit', $user) }}" class="vogue-btn vogue-btn-solid">{{ __('admin.edit') }}</a>
                @if (auth()->id() !== $user->id)
                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('{{ __('admin.delete_this_user') }}')">
                        @csrf
                        @method('DELETE')
                        <button class="vogue-btn vogue-btn-outline text-red-200 border-red-300/30">{{ __('admin.delete') }}</button>
                    </form>
                @endif
            </div>
        </section>
    </main>
</body>
</html>
