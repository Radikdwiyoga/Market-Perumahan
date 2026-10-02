# Marketplace Perumahan

Platform marketplace multi-vendor khusus lingkungan perumahan yang memungkinkan warga membeli dan menjual produk/jasa dari pedagang (seller) di dalam atau sekitar lingkungan perumahan.

## Fitur

**Pembeli**
- Jelajahi marketplace, pencarian produk, detail produk & toko
- Keranjang belanja dan daftar favorit
- Checkout dan pemesanan multi-seller
- Upload bukti pembayaran, konfirmasi pesanan diterima
- Ulasan produk dan komplain pesanan
- Notifikasi realtime (SSE), chat dengan seller

**Seller**
- Pendaftaran & verifikasi seller oleh admin
- Kelola produk (dengan deskripsi berbantuan AI), toko, ongkir, dan pengaturan pembayaran (rekening/QRIS)
- Kelola pesanan: terima, proses, kirim, verifikasi QR pickup
- Verifikasi pembayaran masuk, laporan penjualan & ekspor

**Admin**
- Dashboard, audit log, dan manajemen pengguna (aktif/nonaktif, verifikasi/tolak seller)
- Kelola kategori, pantau pesanan, verifikasi/tolak pembayaran
- Tangani komplain dan laporan transaksi & ekspor

**API**
- REST API berbasis Laravel Sanctum (token) untuk aplikasi mobile/klien lain: auth, produk, kategori, toko, keranjang, favorit, pesanan, pengiriman, pembayaran, dan endpoint seller

## Teknologi

- PHP 8.3, Laravel 13 (Blade + controllers)
- Laravel Sanctum untuk autentikasi API
- Tailwind CSS v4 + Vite
- SQLite untuk pengembangan lokal (MySQL untuk produksi, lihat `.env.example`)
- PHPUnit untuk pengujian, Laravel Pint untuk formatting

## Instalasi

```bash
git clone <url-repo> Market-Perumahan
cd Market-Perumahan
composer setup
```

Perintah `composer setup` akan menjalankan: `composer install`, menyalin `.env`, `key:generate`, migrasi, `npm install`, dan `npm run build`.

Atau manual:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

Isi data contoh:

```bash
php artisan db:seed
```

## Menjalankan Aplikasi

```bash
composer run dev
```

Perintah ini menjalankan server Laravel, queue worker, log viewer (Pail), dan Vite secara bersamaan. Aplikasi dapat diakses di URL yang ditampilkan (default `http://localhost:8000`, sesuaikan `APP_URL`).

Alternatif terpisah:

```bash
php artisan serve
npm run dev
```

## Pengujian

```bash
php artisan test --compact
```

Format kode sebelum commit:

```bash
vendor/bin/pint --dirty
```

## Struktur Direktori

- `app/Http/Controllers` — controller web (pembeli, seller, admin)
- `app/Http/Controllers/Api` — controller REST API
- `app/Models` — model Eloquent (User, Product, Order, Payment, Shipment, dll.)
- `resources/views` — Blade templates per area (marketplace, seller, admin, auth, dst.)
- `routes/web.php` — rute web; `routes/api.php` — rute API
- `database/migrations` & `database/seeders` — skema dan data contoh
- `PRD.txt` — dokumen kebutuhan produk

## Catatan Produksi

- Atur `DB_CONNECTION=mysql` beserta kredensialnya di `.env` (contoh pada `.env.example`).
- Set `QUEUE_CONNECTION` sesuai worker yang tersedia; pada hosting tanpa queue worker gunakan `sync`.
- Jalankan `npm run build` untuk aset produksi.
