<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        註冊完成後，請先點擊信箱中的驗證連結。若沒有收到信件，可以重新寄送。
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
            新的驗證連結已寄出。
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button>重新寄送驗證信</x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                登出
            </button>
        </form>
    </div>
</x-guest-layout>
