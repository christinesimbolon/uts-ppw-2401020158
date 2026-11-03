<p>Semua kolom wajib diisi.</p>

<p>
    <label for="kode_pesanan">Kode pesanan (tepat 10 huruf atau angka)</label>
    <input
        type="text"
        id="kode_pesanan"
        name="kode_pesanan"
        value="{{ old('kode_pesanan', $pesanan->kode_pesanan ?? '') }}"
        minlength="10"
        maxlength="10"
        pattern="[A-Za-z0-9]{10}"
        required
    >
    @error('kode_pesanan')
        <span role="alert">{{ $message }}</span>
    @enderror
</p>

<p>
    <label for="nama_pelanggan">Nama pelanggan (3–100 karakter)</label>
    <input
        type="text"
        id="nama_pelanggan"
        name="nama_pelanggan"
        value="{{ old('nama_pelanggan', $pesanan->nama_pelanggan ?? '') }}"
        minlength="3"
        maxlength="100"
        required
    >
    @error('nama_pelanggan')
        <span role="alert">{{ $message }}</span>
    @enderror
</p>

<p>
    <label for="nomor_telepon">Nomor telepon (10–20 karakter)</label>
    <input
        type="tel"
        id="nomor_telepon"
        name="nomor_telepon"
        value="{{ old('nomor_telepon', $pesanan->nomor_telepon ?? '') }}"
        minlength="10"
        maxlength="20"
        required
    >
    @error('nomor_telepon')
        <span role="alert">{{ $message }}</span>
    @enderror
</p>

<p>
    <label for="berat_kg">Berat laundry (1–20 kg, bilangan bulat)</label>
    <input
        type="number"
        id="berat_kg"
        name="berat_kg"
        value="{{ old('berat_kg', $pesanan->berat_kg ?? '') }}"
        min="1"
        max="20"
        step="1"
        required
    >
    @error('berat_kg')
        <span role="alert">{{ $message }}</span>
    @enderror
</p>

<p>
    <label for="jenis_layanan_id">Jenis layanan</label>
    <select id="jenis_layanan_id" name="jenis_layanan_id" required>
        <option value="" @selected((string) old('jenis_layanan_id', $pesanan->jenis_layanan_id ?? '') === '')>Pilih jenis layanan</option>
        @forelse ($layanan as $item)
            <option value="{{ $item->id }}" @selected((string) old('jenis_layanan_id', $pesanan->jenis_layanan_id ?? '') === (string) $item->id)>{{ $item->nama_layanan }}</option>
        @empty
            <option value="" disabled>Belum ada jenis layanan tersedia</option>
        @endforelse
    </select>
    @error('jenis_layanan_id')
        <span role="alert">{{ $message }}</span>
    @enderror
</p>
