@extends('layouts.app')

@section('title', 'Gangguan Database')

@section('content')
    <h1>Data sementara tidak tersedia</h1>
    <p>Terjadi gangguan saat mengakses database. Silakan coba kembali beberapa saat lagi.</p>
    <p>Jika gangguan berlanjut, hubungi pengelola aplikasi.</p>
    <p><a href="{{ route('pesanan-laundry.index') }}">Kembali ke daftar pesanan laundry</a></p>
@endsection
