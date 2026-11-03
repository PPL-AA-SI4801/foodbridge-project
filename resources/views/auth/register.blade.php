@php
    $selectedRole = old('role') === 'penerima' ? 'penerima' : 'donor';

    $hasAccountErrors = $errors->hasAny(['role', 'nama', 'email', 'no_hp', 'password', 'password_confirmation']);
    $hasProfileErrors = $errors->hasAny(['nama_usaha', 'jenis_usaha', 'nama_lembaga', 'jenis_lembaga', 'alamat', 'jenis_dokumen', 'dokumen']);
    $startStep = $hasAccountErrors ? 1 : ($hasProfileErrors ? 2 : 1);

    $labelClass = 'block text-sm font-medium text-foreground';
    $inputClass = 'mt-1.5 block w-full rounded-md border border-input bg-white px-3.5 py-2.5 text-sm text-foreground shadow-none transition-[border-color,box-shadow] duration-150 ease-[cubic-bezier(0.23,1,0.32,1)] placeholder:text-muted-foreground focus:border-primary focus:ring-1 focus:ring-primary';
    $selectClass = 'mt-1.5 block w-full appearance-none rounded-md border border-input bg-white bg-none px-3.5 py-2.5 pr-10 text-sm text-foreground shadow-none transition-[border-color,box-shadow] duration-150 ease-[cubic-bezier(0.23,1,0.32,1)] focus:border-primary focus:ring-1 focus:ring-primary';
    $fileClass = 'mt-1.5 block w-full min-w-0 cursor-pointer rounded-md border border-input bg-white p-2 text-sm text-muted-foreground file:mr-3 file:cursor-pointer file:rounded-md file:border-0 file:bg-muted file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-foreground hover:file:bg-accent focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2';
@endphp

<x-guest-layout width="lg">
    <div x-data="{
            step: {{ $startStep }},
            role: '{{ $selectedRole }}',
            checkPasswords() {
                const password = this.$refs.stepOne.querySelector('#password');
                const confirmation = this.$refs.stepOne.querySelector('#password_confirmation');

                if (password && confirmation) {
                    confirmation.setCustomValidity(
                        confirmation.value === password.value ? '' : @js(__('Kata sandi tidak cocok.')),
                    );
                }
            },
            advance() {
                this.checkPasswords();

                const fields = this.$refs.stepOne.querySelectorAll('input, select, textarea');

                for (const field of fields) {
                    if (!field.checkValidity()) {
                        field.reportValidity();
                        return;
                    }
                }

                this.step = 2;
                this.$nextTick(() => {
                    const next = [...this.$refs.stepTwo.querySelectorAll('input, select, textarea')]
                        .find((field) => field.offsetParent !== null);

                    next?.focus();
                });
            },
            retreat() {
                this.step = 1;
                this.$nextTick(() => this.$refs.stepOne.querySelector('#nama')?.focus());
            }
        }">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-foreground">{{ __('Buat akun') }}</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ __('Bergabung untuk berbagi dan menyelamatkan makanan.') }}
                </p>
            </div>

            <!-- Step indicator -->
            <ol class="flex shrink-0 items-center gap-2 text-xs sm:mt-1" aria-label="{{ __('Langkah pendaftaran') }}">
                <li class="flex items-center gap-1.5" :aria-current="step === 1 ? 'step' : null">
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full font-semibold transition-colors duration-150"
                            :class="step >= 1 ? 'bg-primary text-primary-foreground' : 'border border-input bg-white text-muted-foreground'">
                        1
                    </span>
                    <span class="font-medium transition-colors duration-150"
                            :class="step >= 1 ? 'text-foreground' : 'text-muted-foreground'">
                        {{ __('Akun') }}
                    </span>
                </li>

                <li class="h-px w-6 transition-colors duration-150" :class="step === 2 ? 'bg-primary' : 'bg-border'" aria-hidden="true"></li>

                <li class="flex items-center gap-1.5" :aria-current="step === 2 ? 'step' : null">
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full font-semibold transition-colors duration-150"
                            :class="step === 2 ? 'bg-primary text-primary-foreground' : 'border border-input bg-white text-muted-foreground'">
                        2
                    </span>
                    <span class="font-medium transition-colors duration-150"
                            :class="step === 2 ? 'text-foreground' : 'text-muted-foreground'">
                        {{ __('Profil') }}
                    </span>
                </li>
            </ol>
        </div>

        <noscript>
            <div class="mb-6 flex items-start gap-3 rounded-md border border-border bg-muted px-4 py-3 text-sm text-muted-foreground">
                <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0" />
                <p>{{ __('Aktifkan JavaScript untuk menggunakan formulir pendaftaran bertahap.') }}</p>
            </div>
        </noscript>

        <p class="sr-only" aria-live="polite" x-text="step === 1 ? @js(__('Langkah 1 dari 2: data akun')) : @js(__('Langkah 2 dari 2: profil dan dokumen'))"></p>

        <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" autocomplete="off"
                @submit="if (step === 1) { $event.preventDefault(); advance(); }">
            @csrf

            <!-- Step 1: Account -->
            <div x-show="step === 1" x-ref="stepOne">
                <!-- Role -->
                <fieldset>
                    <legend class="{{ $labelClass }}">{{ __('Saya mendaftar sebagai') }}</legend>

                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <label class="relative flex cursor-pointer items-start gap-3 rounded-md border p-3.5 transition-[border-color,background-color,box-shadow] duration-150 ease-[cubic-bezier(0.23,1,0.32,1)] has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-primary has-[:focus-visible]:ring-offset-2"
                                :class="role === 'donor' ? 'border-primary bg-primary/5 ring-1 ring-primary' : 'border-input bg-white hover:border-primary/40'">
                            <input type="radio" name="role" value="donor" x-model="role"
                                    @checked($selectedRole === 'donor') required class="sr-only">

                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md transition-colors duration-150"
                                    :class="role === 'donor' ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'">
                                <x-icon name="store" class="h-4 w-4" />
                            </span>

                            <span class="pr-5">
                                <span class="block text-sm font-semibold text-foreground">{{ __('Donor') }}</span>
                                <span class="mt-0.5 block text-xs text-muted-foreground">{{ __('Usaha makanan atau restoran') }}</span>
                            </span>

                            <span class="absolute right-3 top-3 flex h-4 w-4 items-center justify-center rounded-full bg-primary text-primary-foreground transition-opacity duration-150"
                                    :class="role === 'donor' ? 'opacity-100' : 'opacity-0'" aria-hidden="true">
                                <x-icon name="check" class="h-2.5 w-2.5" />
                            </span>
                        </label>

                        <label class="relative flex cursor-pointer items-start gap-3 rounded-md border p-3.5 transition-[border-color,background-color,box-shadow] duration-150 ease-[cubic-bezier(0.23,1,0.32,1)] has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-primary has-[:focus-visible]:ring-offset-2"
                                :class="role === 'penerima' ? 'border-primary bg-primary/5 ring-1 ring-primary' : 'border-input bg-white hover:border-primary/40'">
                            <input type="radio" name="role" value="penerima" x-model="role"
                                    @checked($selectedRole === 'penerima') required class="sr-only">

                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md transition-colors duration-150"
                                    :class="role === 'penerima' ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'">
                                <x-icon name="building-2" class="h-4 w-4" />
                            </span>

                            <span class="pr-5">
                                <span class="block text-sm font-semibold text-foreground">{{ __('Penerima') }}</span>
                                <span class="mt-0.5 block text-xs text-muted-foreground">{{ __('Yayasan atau komunitas') }}</span>
                            </span>

                            <span class="absolute right-3 top-3 flex h-4 w-4 items-center justify-center rounded-full bg-primary text-primary-foreground transition-opacity duration-150"
                                    :class="role === 'penerima' ? 'opacity-100' : 'opacity-0'" aria-hidden="true">
                                <x-icon name="check" class="h-2.5 w-2.5" />
                            </span>
                        </label>
                    </div>

                    <x-input-error :messages="$errors->get('role')" class="mt-2" />
                </fieldset>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <!-- Name -->
                    <div class="sm:col-span-2">
                        <label for="nama" class="{{ $labelClass }}">{{ __('Nama lengkap') }}</label>
                        <input id="nama" type="text" name="nama" value="{{ old('nama') }}" required
                                @if ($startStep === 1) autofocus @endif autocomplete="name"
                                placeholder="{{ __('Nama Anda') }}" class="{{ $inputClass }}" />
                        <x-input-error :messages="$errors->get('nama')" class="mt-2" />
                    </div>

                    <!-- Email Address -->
                    <div>
                        <label for="email" class="{{ $labelClass }}">{{ __('Alamat Email') }}</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required
                                autocomplete="username" spellcheck="false" placeholder="nama@email.com"
                                class="{{ $inputClass }}" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <!-- Phone Number -->
                    <div>
                        <label for="no_hp" class="{{ $labelClass }}">{{ __('Nomor HP') }}</label>
                        <input id="no_hp" type="tel" name="no_hp" value="{{ old('no_hp') }}" required maxlength="20"
                                autocomplete="tel" placeholder="08xx xxxx xxxx" class="{{ $inputClass }}" />
                        <x-input-error :messages="$errors->get('no_hp')" class="mt-2" />
                    </div>

                    <!-- Password -->
                    <div>
                        <div class="flex items-baseline justify-between gap-3">
                            <label for="password" class="{{ $labelClass }}">{{ __('Kata Sandi') }}</label>
                            <span class="text-xs text-muted-foreground">{{ __('Minimal 8 karakter') }}</span>
                        </div>
                        <div class="mt-1.5">
                            <x-password-input id="password" name="password" autocomplete="new-password" minlength="8" required />
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="password_confirmation" class="{{ $labelClass }}">{{ __('Konfirmasi Kata Sandi') }}</label>
                        <div class="mt-1.5">
                            <x-password-input id="password_confirmation" name="password_confirmation" autocomplete="new-password" minlength="8" required />
                        </div>
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>
                </div>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <a href="{{ route('login') }}"
                            class="order-2 text-sm text-muted-foreground transition-colors duration-150 hover:text-foreground focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 sm:order-1">
                        {{ __('Sudah punya akun?') }}
                        <span class="font-medium text-primary">{{ __('Masuk') }}</span>
                    </a>

                    <x-button type="button" size="md" class="order-1 sm:order-2 self-start" @click="advance()">
                        {{ __('Lanjut') }}
                    </x-button>
                </div>
            </div>

            <!-- Step 2: Profile -->
            <div x-show="step === 2" x-ref="stepTwo" x-cloak>
                <p class="text-sm text-muted-foreground">{{ __('Lengkapi profil dan unggah satu dokumen legalitas.') }}</p>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <!-- Donor profile -->
                    <div x-show="role === 'donor'" x-cloak class="grid gap-4 sm:col-span-2 sm:grid-cols-2">
                        <div>
                            <label for="nama_usaha" class="{{ $labelClass }}">{{ __('Nama usaha') }}</label>
                            <input id="nama_usaha" type="text" name="nama_usaha" value="{{ old('nama_usaha') }}"
                                    x-bind:disabled="role !== 'donor'" x-bind:required="role === 'donor'"
                                    placeholder="{{ __('Contoh: Dapur Berkah') }}" class="{{ $inputClass }}" />
                            <x-input-error :messages="$errors->get('nama_usaha')" class="mt-2" />
                        </div>

                        <div>
                            <label for="jenis_usaha" class="{{ $labelClass }}">{{ __('Jenis usaha') }}</label>
                            <input id="jenis_usaha" type="text" name="jenis_usaha" value="{{ old('jenis_usaha') }}"
                                    x-bind:disabled="role !== 'donor'" x-bind:required="role === 'donor'"
                                    placeholder="{{ __('Contoh: Restoran') }}" class="{{ $inputClass }}" />
                            <x-input-error :messages="$errors->get('jenis_usaha')" class="mt-2" />
                        </div>
                    </div>

                    <!-- Penerima profile -->
                    <div x-show="role === 'penerima'" x-cloak class="grid gap-4 sm:col-span-2 sm:grid-cols-2">
                        <div>
                            <label for="nama_lembaga" class="{{ $labelClass }}">{{ __('Nama lembaga') }}</label>
                            <input id="nama_lembaga" type="text" name="nama_lembaga" value="{{ old('nama_lembaga') }}"
                                    x-bind:disabled="role !== 'penerima'" x-bind:required="role === 'penerima'"
                                    placeholder="{{ __('Contoh: Panti Asuhan Harapan') }}" class="{{ $inputClass }}" />
                            <x-input-error :messages="$errors->get('nama_lembaga')" class="mt-2" />
                        </div>

                        <div>
                            <label for="jenis_lembaga" class="{{ $labelClass }}">{{ __('Jenis lembaga') }}</label>
                            <input id="jenis_lembaga" type="text" name="jenis_lembaga" value="{{ old('jenis_lembaga') }}"
                                    x-bind:disabled="role !== 'penerima'" x-bind:required="role === 'penerima'"
                                    placeholder="{{ __('Contoh: Yayasan') }}" class="{{ $inputClass }}" />
                            <x-input-error :messages="$errors->get('jenis_lembaga')" class="mt-2" />
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="sm:col-span-2">
                        <label for="alamat" class="{{ $labelClass }}">{{ __('Alamat') }}</label>
                        <input id="alamat" type="text" name="alamat" value="{{ old('alamat') }}" required
                                autocomplete="street-address" placeholder="{{ __('Jl. Contoh No. 1, Kota') }}"
                                class="{{ $inputClass }}" />
                        <x-input-error :messages="$errors->get('alamat')" class="mt-2" />
                    </div>

                    <div class="border-t border-border sm:col-span-2" aria-hidden="true"></div>

                    <!-- Legality document -->
                    <div>
                        <label for="jenis_dokumen" class="{{ $labelClass }}">{{ __('Jenis dokumen') }}</label>
                        <div class="relative">
                            <select id="jenis_dokumen" name="jenis_dokumen" required class="{{ $selectClass }}">
                                <option value="" disabled @selected(old('jenis_dokumen') === null)>{{ __('Pilih jenis dokumen') }}</option>
                                @foreach ([
                                    'nib' => 'NIB',
                                    'akta_pendirian' => 'Akta pendirian',
                                    'izin_operasional' => 'Izin operasional',
                                    'lainnya' => 'Lainnya',
                                ] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('jenis_dokumen') === $value)>{{ __($label) }}</option>
                                @endforeach
                            </select>
                            <x-icon name="chevron-down" class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        </div>
                        <x-input-error :messages="$errors->get('jenis_dokumen')" class="mt-2" />
                    </div>

                    <div>
                        <label for="dokumen" class="{{ $labelClass }}">{{ __('Berkas dokumen') }}</label>
                        <input id="dokumen" name="dokumen" type="file" accept=".pdf,.jpg,.jpeg,.png" required
                                class="{{ $fileClass }}" />
                        <p class="mt-1.5 text-xs text-muted-foreground">{{ __('PDF, JPG, atau PNG. Maks. 2 MB.') }}</p>
                        <x-input-error :messages="$errors->get('dokumen')" class="mt-2" />
                    </div>
                </div>

                <div class="mt-4 flex items-start gap-2.5 rounded-md bg-muted px-3.5 py-2.5 text-xs text-muted-foreground">
                    <x-icon name="shield-check" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-primary" />
                    <p>{{ __('Akun aktif setelah dokumen diverifikasi administrator.') }}</p>
                </div>

                <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <x-button type="button" size="md" class="btn-block sm:w-auto btn-secondary" @click="retreat()">
                        {{ __('Kembali') }}
                    </x-button>

                    <x-button type="submit" size="md" class="btn-block sm:w-auto">
                        {{ __('Daftar') }}
                    </x-button>
                </div>
            </div>
        </form>
    </div>

    <script>
        // Prevent the browser from restoring a stale two-step form state
        // (e.g. step 2 / a previously selected role) from the back/forward cache.
        window.addEventListener('beforeunload', function () {});
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
</x-guest-layout>
