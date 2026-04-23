<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            帳號資訊
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-xl overflow-hidden">
                <div class="p-6 sm:p-8 space-y-6">
                    <div>
                        <p class="text-sm uppercase tracking-wider text-gray-500 dark:text-gray-400">Account Overview</p>
                        <h3 class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $user->name }}</h3>
                        <p class="mt-1 text-gray-600 dark:text-gray-300">{{ $user->email }}</p>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4 text-sm">
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                            <p class="text-gray-500 dark:text-gray-400">註冊時間</p>
                            <p class="mt-1 font-medium text-gray-800 dark:text-gray-100">{{ optional($user->created_at)->format('Y-m-d H:i') }}</p>
                        </div>
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                            <p class="text-gray-500 dark:text-gray-400">Email 驗證狀態</p>
                            <p class="mt-1 font-medium text-gray-800 dark:text-gray-100">
                                {{ $user->email_verified_at ? '已驗證' : '尚未驗證' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('profile.edit') }}" class="inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-300">
                            編輯資料（Update / Delete）
                        </a>
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-md border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700">
                            回 Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
