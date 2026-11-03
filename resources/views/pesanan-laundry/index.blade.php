@extends('layouts.app')

@section('title', 'Sistem Pesanan Laundry')

@section('content')
    <h1>Sistem Pesanan Laundry</h1>

    <section aria-labelledby="tambah-pesanan">
        <h2 id="tambah-pesanan">Tambah pesanan laundry</h2>
        <form action="{{ route('pesanan-laundry.store') }}" method="POST">
            @csrf
            @include('pesanan-laundry._form', ['pesanan' => null])
            <button type="submit">Simpan pesanan</button>
        </form>
    </section>

    <section aria-labelledby="cari-pesanan">
        <h2 id="cari-pesanan">Cari dan filter pesanan</h2>
        <form action="{{ route('pesanan-laundry.index') }}" method="GET">
            <p>
                <label for="q">Cari berdasarkan kode pesanan, nama pelanggan, atau nomor telepon</label>
                <input type="search" id="q" name="q" value="{{ $q }}">
            </p>
            <p>
                <label for="filter_jenis_layanan_id">Filter jenis layanan</label>
                <select id="filter_jenis_layanan_id" name="jenis_layanan_id">
                    <option value="" @selected($jenisLayananId === null)>Semua jenis layanan</option>
                    @forelse ($layanan as $item)
                        <option value="{{ $item->id }}" @selected($jenisLayananId === (int) $item->id)>{{ $item->nama_layanan }}</option>
                    @empty
                        <option value="" disabled>Belum ada jenis layanan tersedia</option>
                    @endforelse
                </select>
            </p>
            <button type="submit">Cari dan terapkan filter</button>
            <a href="{{ route('pesanan-laundry.index') }}">Reset pencarian dan filter</a>
        </form>
    </section>

    <section aria-labelledby="daftar-pesanan">
        <h2 id="daftar-pesanan">Daftar pesanan laundry</h2>
        <p>Total pesanan: {{ $total }}</p>
        <p>Kategori berat: Besar untuk berat minimal 10 kg, Reguler untuk berat di bawah 10 kg.</p>
        <p>Tombol hapus langsung menghapus pesanan tanpa konfirmasi.</p>
        <table>
            <caption>Data pesanan sesuai pencarian dan filter</caption>
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Kode pesanan</th>
                    <th scope="col">Nama pelanggan</th>
                    <th scope="col">Nomor telepon</th>
                    <th scope="col">Berat (kg)</th>
                    <th scope="col">Jenis layanan</th>
                    <th scope="col">Kategori berat</th>
                    <th scope="col">Tindakan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pesanan as $row)
                    <tr>
                        <td>{{ $row->id }}</td>
                        <td>{{ $row->kode_pesanan }}</td>
                        <td>{{ $row->nama_pelanggan }}</td>
                        <td>{{ $row->nomor_telepon }}</td>
                        <td>{{ $row->berat_kg }}</td>
                        <td>{{ $row->nama_layanan }}</td>
                        <td>
                            @if (statusBerat((int) $row->berat_kg) === 'Besar')
                                Besar
                            @else
                                Reguler
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('pesanan-laundry.edit', ['id' => $row->id]) }}">Edit pesanan {{ $row->kode_pesanan }}</a>
                            <form action="{{ route('pesanan-laundry.destroy', ['id' => $row->id]) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit">Hapus pesanan {{ $row->kode_pesanan }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">Tidak ada pesanan laundry yang dapat ditampilkan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection
