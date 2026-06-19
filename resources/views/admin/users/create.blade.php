<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>VogueAI | 建立使用者</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="vogue-home-body antialiased">
    <div class="vogue-theme-layer vogue-theme-layer-dark" aria-hidden="true"></div>
    <div class="vogue-theme-layer vogue-theme-layer-light" aria-hidden="true"></div>

    <x-vogue-auth-nav />

    <main class="vogue-shell pb-16 md:pb-24 space-y-6">
        <section class="vogue-highlight reveal is-visible">
            <div>
                <p class="vogue-eyebrow">管理員</p>
                <h2>建立使用者</h2>
                <p>新增帳號並指定角色。</p>
            </div>
            <ul class="vogue-points">
                <li>建立新的登入帳號</li>
                <li>設定管理員或一般使用者角色</li>
                <li>送出後回到使用者清單</li>
            </ul>
        </section>

        <section class="vogue-card reveal is-visible max-w-4xl">
            <form method="POST" action="{{ route('admin.users.store') }}" class="grid gap-5 md:grid-cols-2">
                @csrf

                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-semibold text-slate-200">{{ __('admin.name') }}</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required class="mt-2 w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-inherit placeholder:text-slate-400 focus:border-amber-300/50 focus:outline-none">
                    @error('name') <p class="mt-2 text-sm text-red-300">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="email" class="block text-sm font-semibold text-slate-200">{{ __('admin.email') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required class="mt-2 w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-inherit placeholder:text-slate-400 focus:border-amber-300/50 focus:outline-none">
                    @error('email') <p class="mt-2 text-sm text-red-300">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-slate-200">{{ __('admin.password') }}</label>
                    <input id="password" name="password" type="password" required class="mt-2 w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-inherit focus:border-amber-300/50 focus:outline-none">
                    @error('password') <p class="mt-2 text-sm text-red-300">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-slate-200">{{ __('admin.confirm_password') }}</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required class="mt-2 w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-inherit focus:border-amber-300/50 focus:outline-none">
                </div>

                <div class="md:col-span-2">
                    <label for="role" class="block text-sm font-semibold text-slate-200">{{ __('admin.role') }}</label>
                    <select id="role" name="role" class="mt-2 w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-inherit focus:border-amber-300/50 focus:outline-none">
                        <option value="user" @selected(old('role', 'user') === 'user')>{{ __('admin.user') }}</option>
                        <option value="admin" @selected(old('role') === 'admin')>{{ __('admin.admin') }}</option>
                    </select>
                    @error('role') <p class="mt-2 text-sm text-red-300">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2 flex flex-wrap gap-3 pt-2">
                    <button class="vogue-btn vogue-btn-solid">{{ __('admin.create') }}</button>
                    <a href="{{ route('admin.users.index') }}" class="vogue-btn vogue-btn-soft">{{ __('admin.cancel') }}</a>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
