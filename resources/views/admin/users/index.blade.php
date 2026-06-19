<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>VogueAI | 使用者管理</title>
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
                <h2>使用者管理</h2>
                <p>建立、查看、更新與刪除系統使用者。</p>
            </div>
            <ul class="vogue-points">
                <li>以姓名或電子郵件快速搜尋</li>
                <li>查看角色與帳號狀態</li>
                <li>集中處理管理員操作</li>
            </ul>
        </section>

        @if (session('status'))
            <div class="vogue-card reveal is-visible border-amber-300/40 text-amber-100">
                {{ __(session('status')) }}
            </div>
        @endif

        <section class="vogue-card reveal is-visible">
            <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap gap-3 items-center">
                <input type="text" name="q" value="{{ $query }}" placeholder="{{ __('admin.search_placeholder') }}" class="w-full md:max-w-md rounded-full border border-white/10 bg-white/5 px-4 py-3 text-sm text-inherit placeholder:text-slate-400 focus:border-amber-300/50 focus:outline-none focus:ring-0">
                <button class="vogue-btn vogue-btn-outline">{{ __('admin.search') }}</button>
                <a href="{{ route('admin.users.index') }}" class="vogue-btn vogue-btn-soft">重設</a>
                <a href="{{ route('admin.users.create') }}" class="vogue-btn vogue-btn-solid">建立使用者</a>
            </form>
        </section>

        <section class="vogue-card reveal is-visible overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-white/10">
                    <thead>
                        <tr class="text-left text-xs text-slate-400">
                            <th class="px-4 py-3">{{ __('admin.id') }}</th>
                            <th class="px-4 py-3">{{ __('admin.name') }}</th>
                            <th class="px-4 py-3">{{ __('admin.email') }}</th>
                            <th class="px-4 py-3">{{ __('admin.role') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('admin.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse ($users as $user)
                            <tr class="align-top">
                                <td class="px-4 py-4 text-sm">{{ $user->id }}</td>
                                <td class="px-4 py-4 text-sm">{{ $user->name }}</td>
                                <td class="px-4 py-4 text-sm">{{ $user->email }}</td>
                                <td class="px-4 py-4 text-sm">{{ __('admin.' . $user->role) }}</td>
                                <td class="px-4 py-4 text-right text-sm">
                                    <div class="inline-flex flex-wrap items-center justify-end gap-2">
                                        <a href="{{ route('admin.users.show', $user) }}" class="vogue-btn vogue-btn-soft">{{ __('admin.view') }}</a>
                                        <a href="{{ route('admin.users.edit', $user) }}" class="vogue-btn vogue-btn-solid">{{ __('admin.edit') }}</a>
                                        @if (auth()->id() !== $user->id)
                                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('{{ __('admin.delete_this_user') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="vogue-btn vogue-btn-outline text-red-200 border-red-300/30">{{ __('admin.delete') }}</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-300">{{ __('admin.no_users') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-5">
                {{ $users->links() }}
            </div>
        </section>
    </main>
</body>
</html>
