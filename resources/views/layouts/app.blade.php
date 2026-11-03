<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sistem Pesanan Laundry')</title>
</head>
<body>
    <header>
        <p>UTS Laravel — Tipe C</p>
        <dl>
            <dt>Nama</dt>
            <dd>Christine Simbolon</dd>
            <dt>NIM</dt>
            <dd>2401020158</dd>
            <dt>Tipe</dt>
            <dd>C</dd>
        </dl>
        <nav aria-label="Navigasi utama">
            <a href="{{ route('pesanan-laundry.index') }}">Daftar pesanan laundry</a>
        </nav>
        <p>URL halaman: <a href="{{ url()->full() }}">{{ url()->full() }}</a></p>
    </header>

    <main>
        @if (session('success'))
            <p role="status">{{ session('success') }}</p>
        @endif

        @if (session('error'))
            <p role="alert">{{ session('error') }}</p>
        @endif

        @if (isset($errors) && $errors->any())
            <section role="alert" aria-labelledby="ringkasan-kesalahan">
                <h2 id="ringkasan-kesalahan">Periksa kembali data yang diisi</h2>
                <ul>
                    @forelse ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @empty
                    @endforelse
                </ul>
            </section>
        @endif

        @yield('content')
    </main>

    <footer>
        <p>Christine Simbolon — NIM 2401020158 — Tipe C — Sistem Pesanan Laundry</p>
    </footer>
</body>
</html>
