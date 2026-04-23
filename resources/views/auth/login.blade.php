<x-guest-layout>
    <div class="mb-5">
        <h1 class="text-2xl font-semibold text-slate-100" data-i18n="login_title">登入你的時尚中樞</h1>
        <p class="mt-1 text-sm text-slate-300/85" data-i18n="login_desc">登入後可管理衣櫥、AI 穿搭與個人帳號資料。</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <input type="hidden" name="locale" class="js-locale-input" value="{{ app()->getLocale() }}">

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" data-i18n="field_email" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" data-i18n="field_password" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" name="remember">
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400" data-i18n="auth_remember">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-between mt-5">
            <a class="text-sm text-slate-300 hover:text-white" href="{{ route('register') }}" data-i18n="auth_to_register">
                還沒有帳號？立即註冊
            </a>

            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('password.request') }}" data-i18n="auth_forgot_password">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button>
                <span data-i18n="auth_login_button">{{ __('Log in') }}</span>
            </x-primary-button>
        </div>

        <div class="mt-4 text-center">
            <a href="{{ url('/') }}" class="text-xs text-slate-300/80 hover:text-white" data-i18n="back_home">返回首頁</a>
        </div>
    </form>
</x-guest-layout>
