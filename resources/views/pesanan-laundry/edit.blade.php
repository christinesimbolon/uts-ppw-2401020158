@extends('layouts.app')

@section('title', 'Edit Pesanan Laundry')

@section('content')
    <h1>Edit Pesanan Laundry</h1>
    <p>Kode pesanan saat ini: {{ $pesanan->kode_pesanan }}</p>

    <form action="{{ route('pesanan-laundry.update', ['id' => $pesanan->id]) }}" method="POST">
        @csrf
        @method('PUT')
        @include('pesanan-laundry._form', ['pesanan' => $pesanan])
        <button type="submit">Simpan perubahan</button>
        <a href="{{ route('pesanan-laundry.index') }}">Batal dan kembali ke daftar pesanan</a>
    </form>
@endsection
