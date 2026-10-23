@php($selectedRole = old('role') === 'penerima' ? 'penerima' : 'donor')

<x-guest-layout>
    <div x-data="{ role: '{{ $selectedRole }}' }">
        <h1 class="text-lg font-semibold text-gray-900">{{ __('Register') }}</h1>
        <p class="mt-1 text-sm text-gray-600">
            {{ __('Choose a role and upload one legality document. An administrator reviews it before you can sign in.') }}
        </p>

        <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" class="mt-6">
            @csrf

            <!-- Role -->
            <fieldset>
                <legend class="block font-medium text-sm text-gray-700">{{ __('I am registering as') }}</legend>

                <div class="mt-2 grid grid-cols-2 gap-3">
                    <label class="flex cursor-pointer items-start gap-2 rounded-md border border-gray-300 p-3">
                        <input type="radio" name="role" value="donor" x-model="role"
                                @checked($selectedRole === 'donor')
                                class="mt-0.5 border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <span>
                            <span class="block text-sm font-medium text-gray-900">{{ __('Donor') }}</span>
                            <span class="block text-xs text-gray-500">{{ __('Business') }}</span>
                        </span>
                    </label>

                    <label class="flex cursor-pointer items-start gap-2 rounded-md border border-gray-300 p-3">
                        <input type="radio" name="role" value="penerima" x-model="role"
                                @checked($selectedRole === 'penerima')
                                class="mt-0.5 border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <span>
                            <span class="block text-sm font-medium text-gray-900">{{ __('Penerima') }}</span>
                            <span class="block text-xs text-gray-500">{{ __('Institution') }}</span>
                        </span>
                    </label>
                </div>

                <x-input-error :messages="$errors->get('role')" class="mt-2" />
            </fieldset>

            <!-- Name -->
            <div class="mt-4">
                <x-input-label for="nama" :value="__('Name')" />
                <x-text-input id="nama" class="block mt-1 w-full" type="text" name="nama"
                                :value="old('nama')" required autofocus autocomplete="name" />
                <x-input-error :messages="$errors->get('nama')" class="mt-2" />
            </div>

            <!-- Email Address -->
            <div class="mt-4">
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email"
                                :value="old('email')" required autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Phone Number -->
            <div class="mt-4">
                <x-input-label for="no_hp" :value="__('Phone number')" />
                <x-text-input id="no_hp" class="block mt-1 w-full" type="tel" name="no_hp"
                                :value="old('no_hp')" required autocomplete="tel" />
                <x-input-error :messages="$errors->get('no_hp')" class="mt-2" />
            </div>

            <!-- Password -->
            <div class="mt-4">
                <x-input-label for="password" :value="__('Password')" />
                <x-text-input id="password" class="block mt-1 w-full" type="password" name="password"
                                required autocomplete="new-password" />
                <p class="mt-1 text-xs text-gray-500">{{ __('At least 8 characters.') }}</p>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Confirm Password -->
            <div class="mt-4">
                <x-input-label for="password_confirmation" :value="__('Confirm password')" />
                <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password"
                                name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <!-- Donor profile -->
            <div x-show="role === 'donor'" x-cloak class="mt-4">
                <p class="text-sm font-medium text-gray-900">{{ __('Business profile') }}</p>

                <div class="mt-3">
                    <x-input-label for="nama_usaha" :value="__('Business name')" />
                    <x-text-input id="nama_usaha" class="block mt-1 w-full" type="text" name="nama_usaha"
                                    :value="old('nama_usaha')" x-bind:disabled="role !== 'donor'"
                                    x-bind:required="role === 'donor'" />
                    <x-input-error :messages="$errors->get('nama_usaha')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="jenis_usaha" :value="__('Business type')" />
                    <x-text-input id="jenis_usaha" class="block mt-1 w-full" type="text" name="jenis_usaha"
                                    :value="old('jenis_usaha')" x-bind:disabled="role !== 'donor'"
                                    x-bind:required="role === 'donor'" />
                    <x-input-error :messages="$errors->get('jenis_usaha')" class="mt-2" />
                </div>
            </div>

            <!-- Penerima profile -->
            <div x-show="role === 'penerima'" x-cloak class="mt-4">
                <p class="text-sm font-medium text-gray-900">{{ __('Institution profile') }}</p>

                <div class="mt-3">
                    <x-input-label for="nama_lembaga" :value="__('Institution name')" />
                    <x-text-input id="nama_lembaga" class="block mt-1 w-full" type="text" name="nama_lembaga"
                                    :value="old('nama_lembaga')" x-bind:disabled="role !== 'penerima'"
                                    x-bind:required="role === 'penerima'" />
                    <x-input-error :messages="$errors->get('nama_lembaga')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="jenis_lembaga" :value="__('Institution type')" />
                    <x-text-input id="jenis_lembaga" class="block mt-1 w-full" type="text" name="jenis_lembaga"
                                    :value="old('jenis_lembaga')" x-bind:disabled="role !== 'penerima'"
                                    x-bind:required="role === 'penerima'" />
                    <x-input-error :messages="$errors->get('jenis_lembaga')" class="mt-2" />
                </div>
            </div>

            <!-- Address -->
            <div class="mt-4">
                <x-input-label for="alamat" :value="__('Address')" />
                <x-text-input id="alamat" class="block mt-1 w-full" type="text" name="alamat"
                                :value="old('alamat')" required autocomplete="street-address" />
                <x-input-error :messages="$errors->get('alamat')" class="mt-2" />
            </div>

            <!-- Legality document -->
            <div class="mt-6 border-t border-gray-200 pt-4">
                <p class="text-sm font-medium text-gray-900">{{ __('Legality document') }}</p>

                <div class="mt-3">
                    <x-input-label for="jenis_dokumen" :value="__('Document type')" />
                    <select id="jenis_dokumen" name="jenis_dokumen" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="" disabled @selected(old('jenis_dokumen') === null)>{{ __('Select a document type') }}</option>
                        @foreach ([
                            'nib' => 'NIB',
                            'akta_pendirian' => 'Deed of establishment',
                            'izin_operasional' => 'Operating permit',
                            'lainnya' => 'Other',
                        ] as $value => $label)
                            <option value="{{ $value }}" @selected(old('jenis_dokumen') === $value)>{{ __($label) }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('jenis_dokumen')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="dokumen" :value="__('Document file')" />
                    <input id="dokumen" name="dokumen" type="file" accept=".pdf,.jpg,.jpeg,.png" required
                            class="mt-1 block w-full text-sm text-gray-600">
                    <p class="mt-1 text-xs text-gray-500">{{ __('PDF, JPG, or PNG. Maximum 2 MB.') }}</p>
                    <x-input-error :messages="$errors->get('dokumen')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end">
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        href="{{ route('login') }}">
                    {{ __('Already registered?') }}
                </a>

                <x-primary-button class="ms-4">
                    {{ __('Register') }}
                </x-primary-button>
            </div>
        </form>
    </div>
</x-guest-layout>
