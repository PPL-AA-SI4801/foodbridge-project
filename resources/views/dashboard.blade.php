<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="mb-6 rounded-md border border-[#86EFAC] bg-[#DCFCE7] px-4 py-3 text-sm font-medium text-[#166534]">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-md border border-[#FCA5A5] bg-[#FEE2E2] px-4 py-3 text-sm font-medium text-[#B91C1C]">
                {{ session('error') }}
            </div>
        @endif

        <section class="overflow-hidden rounded-2xl border border-border bg-card p-6 sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary">
                {{ auth()->user()->hasRole(\App\Enums\Role::Donor) ? 'Donor Hub' : 'Penerima / Yayasan' }}
            </p>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-foreground">
                {{ __('Selamat datang, :nama', ['nama' => auth()->user()->nama]) }}
            </h1>
            <p class="mt-2 text-sm text-muted-foreground">
                {{ __('Anda masuk sebagai :role.', ['role' => ucfirst(auth()->user()->role->value)]) }}
            </p>

            <div class="mt-6 flex flex-wrap gap-3">
                @if (auth()->user()->hasRole(\App\Enums\Role::Donor))
                    <a href="{{ route('donor.food-posts.index') }}" class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90">
                        {{ __('Posting Makanan') }}
                    </a>
                @else
                    <a href="{{ route('recipient.catalog.index') }}" class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90">
                        {{ __('Katalog Makanan') }}
                    </a>
                
                    <a href="{{ route('recipient.claims.index') }}" class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90">
                    Klaim Saya
                    </a>
                @endif

                <a href="{{ route('profile.edit') }}" class="inline-flex items-center justify-center rounded-md border border-border bg-card px-4 py-2 text-sm font-semibold text-foreground transition hover:bg-accent">
                    {{ __('Profil Saya') }}
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center rounded-md border border-border bg-card px-4 py-2 text-sm font-semibold text-foreground transition hover:bg-accent">
                        {{ __('Keluar') }}
                    </button>
                </form>
            </div>
        </section>
    </div>
</x-app-layout>