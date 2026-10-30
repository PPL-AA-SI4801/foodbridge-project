<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profil Saya') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-md border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('profile.update') }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <h3 class="text-lg font-medium text-gray-900">{{ __('Akun') }}</h3>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <x-input-label for="nama" :value="__('Nama')" />
                                    <x-text-input id="nama" name="nama" type="text" class="mt-1 block w-full" :value="old('nama', $user->nama)" required />
                                    <x-input-error :messages="$errors->get('nama')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="email" :value="__('Email')" />
                                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required />
                                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="no_hp" :value="__('Nomor HP')" />
                                    <x-text-input id="no_hp" name="no_hp" type="tel" class="mt-1 block w-full" :value="old('no_hp', $user->no_hp)" required />
                                    <x-input-error :messages="$errors->get('no_hp')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-medium text-gray-900">{{ __('Kata sandi (opsional)') }}</h3>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <x-input-label for="current_password" :value="__('Kata sandi saat ini')" />
                                    <x-text-input id="current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
                                    <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="password" :value="__('Kata sandi baru')" />
                                    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="password_confirmation" :value="__('Konfirmasi kata sandi baru')" />
                                    <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-medium text-gray-900">{{ __('Profil organisasi') }}</h3>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                @if ($user->role === \App\Enums\Role::Donor)
                                    <div>
                                        <x-input-label for="nama_usaha" :value="__('Nama usaha')" />
                                        <x-text-input id="nama_usaha" name="nama_usaha" type="text" class="mt-1 block w-full" :value="old('nama_usaha', $profile->nama_usaha)" required />
                                        <x-input-error :messages="$errors->get('nama_usaha')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="jenis_usaha" :value="__('Jenis usaha')" />
                                        <x-text-input id="jenis_usaha" name="jenis_usaha" type="text" class="mt-1 block w-full" :value="old('jenis_usaha', $profile->jenis_usaha)" required />
                                        <x-input-error :messages="$errors->get('jenis_usaha')" class="mt-2" />
                                    </div>
                                @else
                                    <div>
                                        <x-input-label for="nama_lembaga" :value="__('Nama lembaga')" />
                                        <x-text-input id="nama_lembaga" name="nama_lembaga" type="text" class="mt-1 block w-full" :value="old('nama_lembaga', $profile->nama_lembaga)" required />
                                        <x-input-error :messages="$errors->get('nama_lembaga')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="jenis_lembaga" :value="__('Jenis lembaga')" />
                                        <x-text-input id="jenis_lembaga" name="jenis_lembaga" type="text" class="mt-1 block w-full" :value="old('jenis_lembaga', $profile->jenis_lembaga)" required />
                                        <x-input-error :messages="$errors->get('jenis_lembaga')" class="mt-2" />
                                    </div>
                                @endif

                                <div class="sm:col-span-2">
                                    <x-input-label for="alamat" :value="__('Alamat')" />
                                    <x-text-input id="alamat" name="alamat" type="text" class="mt-1 block w-full" :value="old('alamat', $profile->alamat)" required />
                                    <x-input-error :messages="$errors->get('alamat')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-medium text-gray-900">{{ __('Lokasi') }}</h3>
                            <p class="mt-1 text-sm text-gray-600">{{ __('Cari alamat, gunakan lokasi saya, atau klik peta untuk menentukan titik.') }}</p>

                            <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                                <input type="text" id="address-search" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="{{ __('Cari alamat...') }}" />
                                <button type="button" id="search-address" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('Cari') }}</button>
                                <button type="button" id="detect-location" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('Lokasi saya') }}</button>
                            </div>

                            <div id="profile-map" class="mt-4 h-72 w-full rounded-md border border-gray-300"></div>

                            <input type="hidden" id="lat" name="lat" value="{{ old('lat', $profile->lat) }}" />
                            <input type="hidden" id="lng" name="lng" value="{{ old('lng', $profile->lng) }}" />

                            <x-input-error :messages="$errors->get('lat')" class="mt-2" />
                            <x-input-error :messages="$errors->get('lng')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end">
                            <x-primary-button>
                                {{ __('Simpan') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const latInput = document.getElementById('lat');
            const lngInput = document.getElementById('lng');
            const initialLat = latInput.value ? parseFloat(latInput.value) : null;
            const initialLng = lngInput.value ? parseFloat(lngInput.value) : null;
            const defaultLat = -2.5489;
            const defaultLng = 118.0149;

            const map = L.map('profile-map').setView(
                [initialLat ?? defaultLat, initialLng ?? defaultLng],
                initialLat ? 13 : 5
            );

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            let marker = null;

            function setCoords(lat, lng) {
                latInput.value = lat;
                lngInput.value = lng;
                if (marker) {
                    marker.setLatLng([lat, lng]);
                } else {
                    marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                    marker.on('dragend', function (e) {
                        const pos = e.target.getLatLng();
                        latInput.value = pos.lat;
                        lngInput.value = pos.lng;
                    });
                }
            }

            if (initialLat !== null && initialLng !== null) {
                setCoords(initialLat, initialLng);
            }

            map.on('click', function (e) {
                setCoords(e.latlng.lat, e.latlng.lng);
            });

            document.getElementById('search-address').addEventListener('click', async function () {
                const query = document.getElementById('address-search').value.trim();
                if (!query) return;
                try {
                    const response = await fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(query));
                    const data = await response.json();
                    if (data && data.length) {
                        const result = data[0];
                        const lat = parseFloat(result.lat);
                        const lng = parseFloat(result.lon);
                        setCoords(lat, lng);
                        map.setView([lat, lng], 15);
                    }
                } catch (e) {
                    // ignore; user can still set coordinates manually via the map
                }
            });

            document.getElementById('detect-location').addEventListener('click', function () {
                if (!navigator.geolocation) return;
                navigator.geolocation.getCurrentPosition(function (position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    setCoords(lat, lng);
                    map.setView([lat, lng], 15);
                });
            });
        });
    </script>
</x-app-layout>