<?php

namespace App\Http\Controllers;

use App\Repositories\PesananLaundryRepository;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PDOException;

class PesananLaundryController extends Controller
{
    public function __construct(private PesananLaundryRepository $repository) {}

    public function index(Request $request): View|Response
    {
        $this->sanitize($request, ['q']);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'jenis_layanan_id' => ['nullable', 'integer', 'min:1'],
        ], [
            'q.string' => 'Kata kunci harus berupa teks.',
            'q.max' => 'Kata kunci maksimal 100 karakter.',
            'jenis_layanan_id.integer' => 'Filter layanan tidak valid.',
            'jenis_layanan_id.min' => 'Filter layanan tidak valid.',
        ]);
        $q = $filters['q'] ?? '';
        $jenisLayananId = isset($filters['jenis_layanan_id']) ? (int) $filters['jenis_layanan_id'] : null;

        try {
            $layanan = $this->repository->layanan();
            $pesanan = $this->repository->daftar($q, $jenisLayananId);

            return view('pesanan-laundry.index', [
                'pesanan' => $pesanan, 'layanan' => $layanan, 'q' => $q,
                'jenisLayananId' => $jenisLayananId, 'total' => count($pesanan),
            ]);
        } catch (PDOException|QueryException $exception) {
            report($exception);

            return response()->view('errors.database', [], 503);
        }
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $data = $this->validatedData($request);
            $this->repository->simpan($data);

            return to_route('pesanan-laundry.index')->with('success', 'Pesanan laundry berhasil ditambahkan.');
        } catch (PDOException|QueryException $exception) {
            report($exception);

            return back()->withInput($request->except('_token', '_method'))
                ->with('error', 'Pesanan belum dapat disimpan. Periksa duplikat data atau coba kembali nanti.');
        }
    }

    public function edit(int $id): View|Response
    {
        try {
            $pesanan = $this->repository->cari($id);
            abort_if($pesanan === null, 404);

            return view('pesanan-laundry.edit', [
                'pesanan' => $pesanan, 'layanan' => $this->repository->layanan(),
            ]);
        } catch (PDOException|QueryException $exception) {
            report($exception);

            return response()->view('errors.database', [], 503);
        }
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        try {
            abort_if($this->repository->cari($id) === null, 404);
            $data = $this->validatedData($request, $id);
            abort_unless($this->repository->ubah($id, $data), 404);

            return to_route('pesanan-laundry.index')->with('success', 'Pesanan laundry berhasil diperbarui.');
        } catch (PDOException|QueryException $exception) {
            report($exception);

            return back()->withInput($request->except('_token', '_method'))
                ->with('error', 'Pesanan belum dapat diperbarui. Periksa duplikat data atau coba kembali nanti.');
        }
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        try {
            abort_if($this->repository->cari($id) === null, 404);
            abort_unless($this->repository->hapus($id), 404);

            return to_route('pesanan-laundry.index')->with('success', 'Pesanan laundry berhasil dihapus.');
        } catch (PDOException|QueryException $exception) {
            report($exception);

            return back()->withInput($request->except('_token', '_method'))
                ->with('error', 'Pesanan belum dapat dihapus. Silakan coba kembali nanti.');
        }
    }

    private function sanitize(Request $request, array $fields): void
    {
        foreach ($fields as $field) {
            if (is_string($request->input($field))) {
                $request->merge([$field => trim(strip_tags($request->input($field)))]);
            }
        }
    }

    private function validatedData(Request $request, ?int $id = null): array
    {
        $this->sanitize($request, ['kode_pesanan', 'nama_pelanggan', 'nomor_telepon']);
        $data = Validator::make($request->all(), [
            'kode_pesanan' => ['required', 'string', 'size:10', 'regex:/\A[A-Za-z0-9]{10}\z/'],
            'nama_pelanggan' => ['required', 'string', 'min:3', 'max:100'],
            'nomor_telepon' => ['required', 'string', 'min:10', 'max:20'],
            'berat_kg' => ['required', 'integer', 'between:1,20'],
            'jenis_layanan_id' => ['required', 'integer', 'min:1'],
        ], [
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'kode_pesanan.size' => 'Kode pesanan harus tepat 10 karakter.',
            'kode_pesanan.regex' => 'Kode pesanan harus terdiri dari 10 huruf atau angka.',
            'nama_pelanggan.min' => 'Nama pelanggan minimal 3 karakter.',
            'nama_pelanggan.max' => 'Nama pelanggan maksimal 100 karakter.',
            'nomor_telepon.min' => 'Nomor telepon minimal 10 karakter.',
            'nomor_telepon.max' => 'Nomor telepon maksimal 20 karakter.',
            'berat_kg.integer' => 'Berat harus berupa bilangan bulat.',
            'berat_kg.between' => 'Berat harus antara 1 dan 20 kg.',
            'jenis_layanan_id.integer' => 'Jenis layanan tidak valid.',
            'jenis_layanan_id.min' => 'Jenis layanan tidak valid.',
        ], [
            'kode_pesanan' => 'Kode pesanan', 'nama_pelanggan' => 'Nama pelanggan',
            'nomor_telepon' => 'Nomor telepon', 'berat_kg' => 'Berat', 'jenis_layanan_id' => 'Jenis layanan',
        ])->validate();

        $errors = [];
        if ($this->repository->kodeTerpakai($data['kode_pesanan'], $id)) {
            $errors['kode_pesanan'] = 'Kode pesanan sudah digunakan.';
        }
        if ($this->repository->teleponTerpakai($data['nomor_telepon'], $id)) {
            $errors['nomor_telepon'] = 'Nomor telepon sudah digunakan.';
        }
        if (! $this->repository->layananTersedia((int) $data['jenis_layanan_id'])) {
            $errors['jenis_layanan_id'] = 'Jenis layanan tidak tersedia.';
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $data;
    }
}
