<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        輸入電子郵件後，我們會寄出密碼重設連結。
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div>
            <x-input-label for="email" value="電子郵件" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>寄出重設連結</x-primary-button>
        </div>
    </form>
</x-guest-layout>
