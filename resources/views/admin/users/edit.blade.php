<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>VogueAI | 編輯使用者</title>
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
                <h2>編輯使用者 #{{ $user->id }}</h2>
                <p>更新姓名、電子郵件、角色與密碼。</p>
            </div>
            <ul class="vogue-points">
                <li>密碼欄位可留空，代表不變更</li>
                <li>角色可切換為管理員或一般使用者</li>
                <li>刪除前會再次確認</li>
            </ul>
        </section>

        @if (session('status'))
            <div class="vogue-card reveal is-visible border-amber-300/40 text-amber-100">{{ __(session('status')) }}</div>
        @endif

        <section class="vogue-card reveal is-visible max-w-4xl">
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="grid gap-5 md:grid-cols-2">
                @csrf
                @method('PUT')

                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-semibold text-slate-200">{{ __('admin.name') }}</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required class="mt-2 w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-inherit placeholder:text-slate-400 focus:border-amber-300/50 focus:outline-none">
                    @error('name') <p class="mt-2 text-sm text-red-300">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="email" class="block text-sm font-semibold text-slate-200">{{ __('admin.email') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required class="mt-2 w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-inherit placeholder:text-slate-400 focus:border-amber-300/50 focus:outline-none">
                    @error('email') <p class="mt-2 text-sm text-red-300">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-slate-200">{{ __('admin.new_password_optional') }}</label>
                    <input id="password" name="password" type="password" class="mt-2 w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-inherit focus:border-amber-300/50 focus:outline-none">
                    @error('password') <p class="mt-2 text-sm text-red-300">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-slate-200">{{ __('admin.confirm_password') }}</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="mt-2 w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-inherit focus:border-amber-300/50 focus:outline-none">
                </div>

                <div class="md:col-span-2">
                    <label for="role" class="block text-sm font-semibold text-slate-200">{{ __('admin.role') }}</label>
                    <select id="role" name="role" class="mt-2 w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-inherit focus:border-amber-300/50 focus:outline-none">
                        <option value="user" @selected(old('role', $user->role) === 'user')>{{ __('admin.user') }}</option>
                        <option value="admin" @selected(old('role', $user->role) === 'admin')>{{ __('admin.admin') }}</option>
                    </select>
                    @error('role') <p class="mt-2 text-sm text-red-300">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2 flex flex-wrap gap-3 pt-2">
                    <button class="vogue-btn vogue-btn-solid">{{ __('admin.save') }}</button>
                    <a href="{{ route('admin.users.show', $user) }}" class="vogue-btn vogue-btn-soft">{{ __('admin.view') }}</a>
                    <a href="{{ route('admin.users.index') }}" class="vogue-btn vogue-btn-outline">{{ __('admin.back') }}</a>
                </div>
            </form>
        </section>

        @if (auth()->id() !== $user->id)
            <section class="vogue-card reveal is-visible">
                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('{{ __('admin.delete_this_user') }}')">
                    @csrf
                    @method('DELETE')
                    <button class="vogue-btn vogue-btn-outline text-red-200 border-red-300/30">{{ __('admin.delete') }}</button>
                </form>
            </section>
        @endif
    </main>
</body>
</html>
