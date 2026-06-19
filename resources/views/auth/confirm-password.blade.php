<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        這是安全區域。請先確認密碼再繼續。
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div>
            <x-input-label for="password" value="密碼" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-end mt-4">
            <x-primary-button>確認</x-primary-button>
        </div>
    </form>
</x-guest-layout>
