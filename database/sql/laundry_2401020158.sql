-- Christine Simbolon | 2401020158 | Paket 2 Tipe C
-- MySQL >= 8.0.16 atau MariaDB >= 10.2.1 (CHECK harus ditegakkan).
-- Jalankan pada basis data baru. Tidak ada DROP atau penghapusan data.
CREATE DATABASE IF NOT EXISTS `laundry_2401020158`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `laundry_2401020158`;

CREATE TABLE jenis_layanan (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama_layanan VARCHAR(100) NOT NULL,
    CONSTRAINT pk_jenis_layanan PRIMARY KEY (id),
    CONSTRAINT uq_nama_layanan UNIQUE (nama_layanan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pesanan_laundry (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    kode_pesanan CHAR(10) NOT NULL,
    nama_pelanggan VARCHAR(100) NOT NULL,
    nomor_telepon VARCHAR(20) NOT NULL,
    berat_kg SMALLINT UNSIGNED NOT NULL,
    jenis_layanan_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT pk_pesanan_laundry PRIMARY KEY (id),
    CONSTRAINT uq_kode_pesanan UNIQUE (kode_pesanan),
    CONSTRAINT uq_nomor_telepon UNIQUE (nomor_telepon),
    CONSTRAINT chk_berat_kg CHECK (berat_kg BETWEEN 1 AND 20),
    CONSTRAINT fk_pesanan_jenis_layanan FOREIGN KEY (jenis_layanan_id)
        REFERENCES jenis_layanan (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO jenis_layanan (id, nama_layanan) VALUES
    (1, 'Cuci Kering'),
    (2, 'Cuci Setrika');

INSERT INTO pesanan_laundry
    (kode_pesanan, nama_pelanggan, nomor_telepon, berat_kg, jenis_layanan_id, created_at, updated_at)
VALUES
    ('LD00000001', 'Ayu Lestari', '081200000001', 5, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('LD00000002', 'Rafi Akbar', '081200000002', 8, 2, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('LD00000003', 'Nina Putri', '081200000003', 3, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);
