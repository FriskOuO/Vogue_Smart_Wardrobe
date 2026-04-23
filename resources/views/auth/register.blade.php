<x-guest-layout>
    <div class="mb-5">
        <h1 class="text-2xl font-semibold text-slate-100" data-i18n="register_title">建立 VogueAI 帳號</h1>
        <p class="mt-1 text-sm text-slate-300/85" data-i18n="register_desc">完成註冊後即可使用智慧衣櫥、AI 穿搭與社群功能。</p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <input type="hidden" name="locale" class="js-locale-input" value="{{ app()->getLocale() }}">

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" data-i18n="field_name" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" data-i18n="field_email" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" data-i18n="field_password" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" data-i18n="field_password_confirm" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-5">
            <a class="text-sm text-slate-300 hover:text-white" href="{{ route('login') }}" data-i18n="auth_to_login">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button>
                <span data-i18n="auth_register_button">{{ __('Register') }}</span>
            </x-primary-button>
        </div>

        <div class="mt-4 text-center">
            <a href="{{ url('/') }}" class="text-xs text-slate-300/80 hover:text-white" data-i18n="back_home">返回首頁</a>
        </div>
    </form>
</x-guest-layout>
