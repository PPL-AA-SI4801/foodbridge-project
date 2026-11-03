<x-guest-layout>
    <div class="mb-8">
        <h1 class="text-2xl font-semibold tracking-tight text-foreground">{{ __('Selamat datang kembali') }}</h1>
        <p class="mt-1.5 text-sm text-muted-foreground">
            {{ __('Masuk untuk melanjutkan berbagi dan menyelamatkan makanan.') }}
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-5" :status="session('status')" />

    @if ($errors->has('account_status'))
        <div role="alert" class="mb-5 flex items-start gap-3 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <x-icon name="triangle-alert" class="mt-0.5 h-4 w-4 shrink-0" />
            <p class="font-medium">{{ $errors->first('account_status') }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-foreground">{{ __('Alamat Email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                    placeholder="nama@email.com" spellcheck="false"
                    class="mt-1.5 block w-full rounded-md border border-input bg-white px-3.5 py-2.5 text-sm text-foreground shadow-none transition-[border-color,box-shadow] duration-150 ease-[cubic-bezier(0.23,1,0.32,1)] placeholder:text-muted-foreground focus:border-primary focus:ring-1 focus:ring-primary" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-foreground">{{ __('Kata Sandi') }}</label>

            <div class="mt-1.5">
                <x-password-input id="password" name="password" autocomplete="current-password" required />
            </div>

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <button type="submit"
                class="group inline-flex w-full items-center justify-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-[background-color,transform] duration-150 ease-[cubic-bezier(0.23,1,0.32,1)] hover:bg-primary/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 active:scale-[0.98] motion-reduce:active:scale-100">
            {{ __('Masuk') }}
            <x-icon name="arrow-right" class="h-4 w-4 transition-transform duration-150 ease-[cubic-bezier(0.23,1,0.32,1)] group-hover:translate-x-0.5 motion-reduce:group-hover:translate-x-0" />
        </button>
    </form>

    <div class="mt-8">
        <div class="flex items-center gap-3">
            <span class="h-px flex-1 bg-border"></span>
            <span class="text-xs uppercase tracking-wide text-muted-foreground">{{ __('atau') }}</span>
            <span class="h-px flex-1 bg-border"></span>
        </div>

        <p class="mt-5 text-center text-sm text-muted-foreground">{{ __('Belum punya akun?') }}</p>

        <a href="{{ route('register') }}"
                class="mt-2 inline-flex w-full items-center justify-center rounded-md border border-input bg-white px-4 py-2.5 text-sm font-semibold text-foreground transition-[background-color,border-color,transform] duration-150 ease-[cubic-bezier(0.23,1,0.32,1)] hover:border-primary/30 hover:bg-accent focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 active:scale-[0.98] motion-reduce:active:scale-100">
            {{ __('Buat akun baru') }}
        </a>
    </div>
</x-guest-layout>
