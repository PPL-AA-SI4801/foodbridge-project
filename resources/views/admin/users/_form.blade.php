@php
    $user = $user ?? null;
    $isCreate = $user === null;
    $currentRole = old('role', $user?->role?->value ?? 'donor');
    $currentLat = old('lat', $user?->donorProfile?->lat ?? $user?->recipientProfile?->lat ?? -6.200000);
    $currentLng = old('lng', $user?->donorProfile?->lng ?? $user?->recipientProfile?->lng ?? 106.816666);

    $inputClasses = 'mt-1.5 block w-full rounded-lg border border-input bg-white px-3 py-2 text-sm text-foreground shadow-xs transition placeholder:text-muted-foreground/70 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20';
    $labelClasses = 'block text-sm font-medium text-foreground';
    $errorClasses = 'mt-1 text-xs text-destructive';
@endphp

<form
    method="POST"
    action="{{ $action }}"
    class="space-y-6"
    x-data="{ role: '{{ $currentRole }}' }"
>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="rounded-xl border border-border bg-card p-5">
        <h2 class="text-base font-semibold">Informasi Akun</h2>
        <p class="mt-1 text-sm text-muted-foreground">Data dasar untuk masuk ke FoodBridge.</p>

        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="nama" class="{{ $labelClasses }}">Nama Lengkap</label>
                <input id="nama" name="nama" type="text" value="{{ old('nama', $user?->nama) }}" required class="{{ $inputClasses }}" />
                @error('nama')
                    <p class="{{ $errorClasses }}">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="{{ $labelClasses }}">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user?->email) }}" required class="{{ $inputClasses }}" />
                @error('email')
                    <p class="{{ $errorClasses }}">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="no_hp" class="{{ $labelClasses }}">Nomor HP</label>
                <input id="no_hp" name="no_hp" type="text" value="{{ old('no_hp', $user?->no_hp) }}" required class="{{ $inputClasses }}" />
                @error('no_hp')
                    <p class="{{ $errorClasses }}">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="role" class="{{ $labelClasses }}">Peran</label>
                @if ($isCreate)
                    <select id="role" name="role" x-model="role" required class="{{ $inputClasses }}">
                        <option value="donor">Donor</option>
                        <option value="penerima">Penerima</option>
                        <option value="administrator">Administrator</option>
                    </select>
                @else
                    <div class="mt-1.5 flex items-center gap-2 rounded-lg border border-border bg-muted px-3 py-2 text-sm">
                        <span class="font-medium">
                            {{ $user->role === \App\Enums\Role::Donor ? 'Donor' : ($user->role === \App\Enums\Role::Penerima ? 'Penerima' : 'Administrator') }}
                        </span>
                        <span class="text-xs text-muted-foreground">Peran tidak dapat diubah.</span>
                    </div>
                    <input type="hidden" name="role" value="{{ $user->role->value }}" />
                @endif
                @error('role')
                    <p class="{{ $errorClasses }}">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="{{ $labelClasses }}">
                    {{ $isCreate ? 'Password' : 'Password Baru (opsional)' }}
                </label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="new-password"
                    @if ($isCreate) required @endif
                    class="{{ $inputClasses }}"
                />
                @if (! $isCreate)
                    <p class="mt-1 text-xs text-muted-foreground">Biarkan kosong jika tidak ingin mengubah password.</p>
                @endif
                @error('password')
                    <p class="{{ $errorClasses }}">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="{{ $labelClasses }}">Konfirmasi Password</label>
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    @if ($isCreate) required @endif
                    class="{{ $inputClasses }}"
                />
            </div>
        </div>

        @if (! $isCreate)
            <div class="mt-5 flex items-center gap-3 rounded-lg border border-border bg-muted/50 px-4 py-3">
                <span class="text-sm text-muted-foreground">Status akun saat ini:</span>
                <x-status-badge :status="$user->status_akun" />
                <span class="text-xs text-muted-foreground">Status hanya dapat diubah melalui verifikasi atau pemblokiran.</span>
            </div>
        @endif
    </div>

    <fieldset x-show="role === 'donor'" x-bind:disabled="role !== 'donor'" x-cloak class="min-w-0 rounded-xl border border-border bg-card p-5">
        <legend class="sr-only">Profil Donor</legend>
        <h2 class="text-base font-semibold">Profil Donor</h2>
        <p class="mt-1 text-sm text-muted-foreground">Informasi usaha yang menyediakan surplus makanan.</p>

        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="nama_usaha" class="{{ $labelClasses }}">Nama Usaha</label>
                <input id="nama_usaha" name="nama_usaha" type="text" value="{{ old('nama_usaha', $user?->donorProfile?->nama_usaha) }}" class="{{ $inputClasses }}" />
                @error('nama_usaha')
                    <p class="{{ $errorClasses }}">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="jenis_usaha" class="{{ $labelClasses }}">Jenis Usaha</label>
                <input id="jenis_usaha" name="jenis_usaha" type="text" value="{{ old('jenis_usaha', $user?->donorProfile?->jenis_usaha) }}" placeholder="Contoh: Restoran, Katering" class="{{ $inputClasses }}" />
                @error('jenis_usaha')
                    <p class="{{ $errorClasses }}">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="donor_alamat" class="{{ $labelClasses }}">Alamat</label>
                <input id="donor_alamat" name="alamat" type="text" value="{{ old('alamat', $user?->donorProfile?->alamat) }}" class="{{ $inputClasses }}" />
                @error('alamat')
                    <p class="{{ $errorClasses }}">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="donor_lat" class="{{ $labelClasses }}">Latitude</label>
                <input id="donor_lat" name="lat" type="number" step="0.0000001" value="{{ $currentLat }}" class="{{ $inputClasses }}" />
                @error('lat')
                    <p class="{{ $errorClasses }}">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="donor_lng" class="{{ $labelClasses }}">Longitude</label>
                <input id="donor_lng" name="lng" type="number" step="0.0000001" value="{{ $currentLng }}" class="{{ $inputClasses }}" />
                @error('lng')
                    <p class="{{ $errorClasses }}">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </fieldset>

    <fieldset x-show="role === 'penerima'" x-bind:disabled="role !== 'penerima'" x-cloak class="min-w-0 rounded-xl border border-border bg-card p-5">
        <legend class="sr-only">Profil Penerima</legend>
        <h2 class="text-base font-semibold">Profil Penerima</h2>
        <p class="mt-1 text-sm text-muted-foreground">Informasi lembaga atau yayasan yang menerima bantuan makanan.</p>

        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="nama_lembaga" class="{{ $labelClasses }}">Nama Lembaga</label>
                <input id="nama_lembaga" name="nama_lembaga" type="text" value="{{ old('nama_lembaga', $user?->recipientProfile?->nama_lembaga) }}" class="{{ $inputClasses }}" />
                @error('nama_lembaga')
                    <p class="{{ $errorClasses }}">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="jenis_lembaga" class="{{ $labelClasses }}">Jenis Lembaga</label>
                <input id="jenis_lembaga" name="jenis_lembaga" type="text" value="{{ old('jenis_lembaga', $user?->recipientProfile?->jenis_lembaga) }}" placeholder="Contoh: Panti asuhan, Yayasan" class="{{ $inputClasses }}" />
                @error('jenis_lembaga')
                    <p class="{{ $errorClasses }}">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="recipient_alamat" class="{{ $labelClasses }}">Alamat</label>
                <input id="recipient_alamat" name="alamat" type="text" value="{{ old('alamat', $user?->recipientProfile?->alamat) }}" class="{{ $inputClasses }}" />
                @error('alamat')
                    <p class="{{ $errorClasses }}">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="recipient_lat" class="{{ $labelClasses }}">Latitude</label>
                <input id="recipient_lat" name="lat" type="number" step="0.0000001" value="{{ $currentLat }}" class="{{ $inputClasses }}" />
                @error('lat')
                    <p class="{{ $errorClasses }}">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="recipient_lng" class="{{ $labelClasses }}">Longitude</label>
                <input id="recipient_lng" name="lng" type="number" step="0.0000001" value="{{ $currentLng }}" class="{{ $inputClasses }}" />
                @error('lng')
                    <p class="{{ $errorClasses }}">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </fieldset>

    <div class="flex flex-wrap items-center gap-3">
        <button
            type="submit"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/30"
        >
            <x-icon name="check" class="h-4 w-4" />
            {{ $submitLabel }}
        </button>

        <a
            href="{{ route('admin.users.index') }}"
            class="inline-flex items-center justify-center rounded-lg border border-input bg-card px-4 py-2 text-sm font-medium transition-colors hover:bg-accent"
        >
            Batal
        </a>
    </div>
</form>
