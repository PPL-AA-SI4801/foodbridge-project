@extends('layouts.app')

@section('title', 'Klaim Masuk — FoodBridge')

@section('content')
    <section class="page-head">
        <h1>Klaim Masuk</h1>
        <p class="page-sub">
            Kelola dan tinjau pengajuan klaim makanan berlebih dari lembaga penerima bantuan yang telah terverifikasi.
        </p>
    </section>

    <section class="stats" aria-label="Ringkasan klaim">
        <article class="stat-card">
            <div>
                <p class="stat-label">Menunggu Tinjauan</p>
                <p class="stat-value">{{ $stats['waiting'] }}</p>
            </div>
            <span class="stat-icon stat-icon--orange">
                <x-icon name="clock" :size="18" />
            </span>
        </article>

        <article class="stat-card">
            <div>
                <p class="stat-label">Klaim Disetujui</p>
                <p class="stat-value">{{ $stats['approved'] }}</p>
            </div>
            <span class="stat-icon stat-icon--green">
                <x-icon name="check-circle" :size="18" />
            </span>
        </article>

        <article class="stat-card">
            <div>
                <p class="stat-label">Total Riwayat Klaim</p>
                <p class="stat-value">{{ $stats['total'] }}</p>
            </div>
            <span class="stat-icon stat-icon--gray">
                <x-icon name="file-text" :size="18" />
            </span>
        </article>
    </section>

    <form class="filters" action="{{ route('claims.index') }}" method="GET" role="search">
        <label class="filter-search">
            <x-icon name="search" :size="15" />
            <input
                type="search"
                name="q"
                value="{{ $filters['q'] }}"
                placeholder="Cari makanan / penerima..."
                aria-label="Cari makanan atau penerima"
            >
        </label>

        <label class="filter">
            <span class="filter-label">STATUS:</span>
            <span class="select-wrap">
                <select name="status" aria-label="Filter status">
                    <option value="">Semua</option>
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-icon name="chevron-down" :size="12" class="select-chevron" />
            </span>
        </label>

        <label class="filter">
            <span class="filter-label">METODE:</span>
            <span class="select-wrap">
                <select name="method" aria-label="Filter metode">
                    <option value="">Semua</option>
                    @foreach ($methodOptions as $value => $label)
                        <option value="{{ $value }}" @selected($filters['method'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-icon name="chevron-down" :size="12" class="select-chevron" />
            </span>
        </label>

        <button type="submit" class="btn-primary">Terapkan Filter</button>
    </form>

    <section class="table-card">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th scope="col">NO</th>
                        <th scope="col">MAKANAN</th>
                        <th scope="col">PORSI</th>
                        <th scope="col">PENERIMA</th>
                        <th scope="col">METODE</th>
                        <th scope="col">TGL KLAIM</th>
                        <th scope="col">STATUS</th>
                        <th scope="col">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($claims as $claim)
                        <tr>
                            <td class="col-no">{{ $loop->iteration }}</td>

                            <td>
                                <div class="food">
                                    <span class="food-icon">
                                        <x-icon :name="$claim->food_icon" :size="15" />
                                    </span>
                                    <span class="food-name">{{ $claim->food_name }}</span>
                                </div>
                            </td>

                            <td>
                                <span @class([
                                    'pill',
                                    'pill--green' => $claim->status === \App\Models\Claim::STATUS_MENUNGGU,
                                    'pill--gray' => $claim->status !== \App\Models\Claim::STATUS_MENUNGGU,
                                ])>
                                    {{ $claim->portions }} Porsi
                                </span>
                            </td>

                            <td>
                                <div class="recipient">
                                    <span class="recipient-name">{{ $claim->recipient_name }}</span>
                                    <span class="recipient-city">{{ $claim->recipient_city }}</span>
                                </div>
                            </td>

                            <td>
                                <span class="method">
                                    <x-icon
                                        :name="$claim->method === \App\Models\Claim::METHOD_KURIR_ONLINE ? 'motor' : 'walk'"
                                        :size="14"
                                    />
                                    {{ $claim->method_label }}
                                </span>
                            </td>

                            <td class="date">{{ $claim->claim_date->format('d/m/Y') }}</td>

                            <td>
                                <x-status-badge :status="$claim->status" />
                            </td>

                            <td>
                                <button type="button" @class([
                                    'btn-action',
                                    'btn-action--primary' => $claim->status === \App\Models\Claim::STATUS_MENUNGGU,
                                    'btn-action--ghost' => $claim->status !== \App\Models\Claim::STATUS_MENUNGGU,
                                ])>
                                    {{ $claim->status === \App\Models\Claim::STATUS_MENUNGGU ? 'Tinjau' : 'Detail' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="empty-state">
                                Tidak ada klaim yang cocok dengan filter saat ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-bar">
            <p class="pagination-info">
                Menampilkan {{ $pagination['shown'] }} klaim dari total {{ $pagination['total'] }} pengajuan bulan ini
            </p>

            <nav class="pager" aria-label="Navigasi halaman">
                <button type="button" class="pager-btn" disabled aria-label="Halaman sebelumnya">
                    <x-icon name="arrow-left" :size="14" />
                </button>

                <span class="pager-label">Halaman {{ $pagination['current'] }} dari {{ $pagination['last'] }}</span>

                <a
                    class="pager-btn pager-btn--active"
                    href="{{ route('claims.index', array_merge(request()->query(), ['page' => 2])) }}"
                    aria-label="Halaman berikutnya"
                >
                    <x-icon name="arrow-right" :size="14" />
                </a>
            </nav>
        </div>
    </section>
@endsection
