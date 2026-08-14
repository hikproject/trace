# Oracle Production Report (Leoco)

Aplikasi web berbasis **PHP 8** yang menampilkan laporan produksi dari database **Oracle** dan
melakukan **trace pengiriman** (delivery) berdasarkan customer atau Leoco Part No.
Dilengkapi fitur export ke **Excel** (PhpSpreadsheet).

## Fitur

1. **Report Produksi** (`/`)
   - Cari berdasarkan Leoco Part No (`sfb05`) + rentang tanggal WO.
   - Menampilkan per WO: informasi part/customer, issuing material (dengan lot), FQC & DC In, dan Barang Out (DO).
   - Pagination dan export Excel.
2. **Trace Pengiriman** (`/trace`)
   - Cari berdasarkan **Customer** (nama/kode) atau **Leoco Part No**.
   - Menampilkan tabel datar: No DO, Tanggal Kirim, Lot (Kode Produksi/WO), Leoco PN, Nama Barang, Qty.
   - Pagination dan export Excel.
3. **Autocomplete dropdown** (Select2 + AJAX) untuk Part No dan Customer.
4. Semua aset frontend (jQuery, Bootstrap, Select2) **dibundel lokal** di `public/assets/vendor/`
   sehingga tidak bergantung koneksi internet.

## Kebutuhan Server

| Komponen | Keterangan |
|---|---|
| PHP | **8.0+** (fungsi `str_starts_with`, `mb_substr`) |
| Ekstensi PHP | `oci8` (koneksi Oracle), `mbstring` |
| Oracle Client | Oracle Instant Client (sesuai arsitektur PHP), terhubung ke server Oracle |
| Composer | Untuk instalasi dependency (`phpoffice/phpspreadsheet`) |

## Instalasi

```bash
# 1. Clone / salin folder proyek ke server
git clone <repo> /path/aplikasi
cd /path/aplikasi

# 2. Install dependency PHP
composer install --no-dev --optimize-autoloader
```

> Jika tidak ada akses internet di server, salin folder `vendor/` dari mesin development
> (sudah termasuk `phpoffice/phpspreadsheet`). Dependency hanya itu satu-satunya paket luar PHP.

## Konfigurasi Database

Buat file `.env` di root proyek (salin dari `.env.example` jika tersedia):

```ini
DB_HOST=10.0.0.5
DB_PORT=1521
DB_SID=LEOCO
DB_USER=oracle
DB_PASS=********
```

Keterangan:
- `DB_HOST` — host/alamat server Oracle.
- `DB_PORT` — port Oracle (default `1521`).
- `DB_SID` — SID/service name Oracle.
- `DB_USER` / `DB_PASS` — kredensial koneksi.
- Charset koneksi sudah ditetapkan `AL32UTF8` di `config.php`.

> **Catatan:** Jangan pernah menyimpan kredensial di file lain, dan jangan commit file `.env`
> ke repositori publik. File `config.php` otomatis membaca `.env`.

## Menjalankan

### Mode development (PHP built-in server)

```bash
php -S localhost:80 -t public
```

atau jalankan `start.bat` (Windows).

> PHP built-in server **single-threaded** — jika banyak user mengakses bersamaan dan query
> Oracle lambat, request bisa saling menunggu. Untuk pemakaian bersama di LAN, gunakan Apache
> (poin berikut).

### Deploy produksi (Apache)

1. Install Apache + PHP (modul `php8`) + `oci8`.
2. Arahkan **DocumentRoot** ke folder `public/`.
3. File `public/.htaccess` sudah disertakan dan otomatis me-rewrite semua request ke `index.php`
   (URL rewrite). Pastikan modul `mod_rewrite` aktif dan `AllowOverride All` pada konfigurasi.
4. Konfigurasi virtual host, contoh:

   ```apache
   <VirtualHost *:80>
       DocumentRoot "D:/data wo/public"
       ServerName report.leoco.local

       <Directory "D:/data wo/public">
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

5. Pastikan ekstensi `oci8` aktif (`extension=php_oci8.dll` di `php.ini`) dan `allow_url_fopen=On`.

### Deploy produksi (IIS + PHP)

1. Pasang PHP via Web Platform Installer, aktifkan ekstensi `oci8`.
2. Install modul **URL Rewrite** untuk IIS.
3. Buat site baru, Physical Path diarahkan ke folder `public/`.
4. Tambahkan handler `.php` → `php-cgi.exe`.
5. File `public/web.config` sudah disertakan — semua request selain file statis
   di-rewrite ke `index.php` sehingga URL bersih (`/`, `/trace`, `/api/*`, `/export-*`) berfungsi.

### Deploy produksi (aaPanel / Nginx)

> **Penting:** Nginx **mengabaikan file `.htaccess`** (itu milik Apache). Karena itu untuk
> aaPanel yang memakai engine **Nginx**, URL rewrite harus ditambahkan di konfigurasi situs,
> bukan lewat `.htaccess`.

1. Pastikan **run directory** (site root) situs diarahkan ke folder `public/`.
2. Buka aaPanel → **Website** → pilih situs → **设置 (Settings)** → **配置文件 (Config file)**.
3. Di dalam blok `server { }`, pastikan ada rule berikut (tambahkan jika belum ada):

   ```nginx
   location / {
       try_files $uri $uri/ /index.php?$query_string;
   }
   ```

   Jika blok `location /` sudah ada, cukup tambahkan baris `try_files` tersebut.
4. **Save** lalu **Reload Nginx** dari panel.
5. Uji: `/`, `/trace`, `/api/*`, `/export-*` — semuanya harus 200 (lakukan hard-refresh/Ctrl+F5).

Catatan: aset statis `/assets/...` tetap dilayani langsung oleh Nginx (try_files mencocokkan
file asli sebelum jatuh ke `index.php`).

## Struktur Proyek

```
├── app/                    # Kelas PHP (namespace App\)
│   ├── Database.php        # Koneksi Oracle (singleton)
│   ├── Part.php            # Pencarian Part No (Select2)
│   ├── Report.php          # Query laporan produksi
│   └── Trace.php           # Query trace pengiriman + pencarian customer
├── public/                 # Document root
│   ├── index.php           # Entry point & routing (/, /trace, /api/*, /export-*)
│   ├── .htaccess           # URL rewrite Apache → index.php
│   ├── web.config          # URL rewrite IIS → index.php
│   └── assets/             # style.css + vendor lokal
│       └── vendor/
│           ├── jquery/
│           ├── bootstrap/
│           └── select2/
├── views/                  # Template HTML/PHP
│   ├── header.php          # Head + navbar tab (Report / Trace)
│   ├── form.php            # Form report produksi
│   ├── report.php          # Hasil report + expand WO + pagination
│   ├── trace_form.php      # Form trace pengiriman
│   ├── trace_view.php      # Tabel hasil trace + pagination
│   └── footer.php          # Script jQuery/Bootstrap/Select2 + init
├── config.php              # Baca .env & definisikan konstanta DB
├── .env                    # Kredensial database (tidak di-commit)
├── .htaccess               # (Opsional) bila DocumentRoot = root proyek; me-redirect ke public/
├── composer.json           # Dependency: phpoffice/phpspreadsheet
└── query.text              # Referensi query lama (untuk pengembangan)
```

## Route Aplikasi

| Route | Fungsi |
|---|---|
| `/` | Halaman Report Produksi (form + hasil) |
| `/trace` | Halaman Trace Pengiriman (form + hasil) |
| `/api/parts?q=...` | JSON autocomplete Part No (untuk Select2) |
| `/api/customers?q=...` | JSON autocomplete Customer (untuk Select2) |
| `/api/wos?q=...` | JSON autocomplete WO No (untuk Select2) |
| `/export-excel?...` | Download Excel hasil report produksi |
| `/export-trace?...` | Download Excel hasil trace pengiriman |

## Troubleshooting

| Gejala | Penyebab / Solusi |
|---|---|
| `Oracle connection failed` | Kredensial/SID salah, Oracle client tidak terpasang, atau `oci8` tidak aktif. Cek `php -m` |
| Dropdown / klik WO tidak berfungsi | Sudah dibundel lokal; pastikan folder `public/assets/vendor/` ikut ter-copy ke server |
| Halaman `/` terbuka tapi `/trace`, `/api/*` 404 | Server memakai **Nginx** yang mengabaikan `.htaccess` — tambahkan rule `try_files` di konfigurasi situs (lihat "Deploy aaPanel / Nginx") |
| Halaman lambat saat ramai | `php -S` single-threaded — pindah ke Apache (lihat bagian deploy) |
| Export Excel error | Pastikan `vendor/` terpasang (`composer install`) |
