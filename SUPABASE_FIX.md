# Perbaikan Masalah Supabase Prepared Statement

## Masalah yang Diperbaiki

Error yang terjadi:
- `SQLSTATE[26000]: Invalid sql statement name: 7 ERROR: prepared statement "pdo_stmt_00000001" does not exist`
- `SQLSTATE[08P01]: bind message supplies 1 parameters, but prepared statement "pdo_stmt_00000002" requires 2`

Masalah ini terjadi karena Supabase connection pooler (port 6543) tidak mendukung prepared statements dengan baik.

## Solusi yang Diterapkan

1. **Custom PostgreSQL Connection Class** (`app/Database/PostgresConnection.php`)
   - Memastikan `PDO::ATTR_EMULATE_PREPARES => true` selalu digunakan
   - Menangani read dan write connections

2. **Middleware untuk Error Handling** (`app/Http/Middleware/HandleDatabaseConnectionErrors.php`)
   - Otomatis mendeteksi dan menangani prepared statement errors
   - Melakukan reconnection otomatis dengan retry mechanism

3. **Konfigurasi Database** (`config/database.php`)
   - Default connection diubah ke `pgsql`
   - Search path diubah ke `public` (sesuai Supabase)
   - PDO options dikonfigurasi untuk Supabase

## ⚠️ PENTING: Konfigurasi Environment Variables

### Gunakan DIRECT CONNECTION (Port 5432), BUKAN Pooler (Port 6543)

Di Laravel Cloud, pastikan environment variables Anda menggunakan **direct connection**:

```env
DB_CONNECTION=pgsql
DB_HOST=<your-supabase-host>.supabase.co
DB_PORT=5432  # ← PENTING: Gunakan 5432, BUKAN 6543
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=<your-password>
```

**ATAU** jika menggunakan connection string:

```env
DB_URL=postgresql://postgres:<password>@<host>.supabase.co:5432/postgres?sslmode=require
```

### ❌ JANGAN Gunakan Connection Pooler

Jangan gunakan port **6543** (connection pooler) karena tidak mendukung prepared statements:

```env
# ❌ SALAH - Jangan gunakan ini
DB_PORT=6543
```

## Verifikasi Konfigurasi

Setelah deploy, test koneksi dengan endpoint:
- `GET /api/test-db` - Test koneksi database
- `GET /api/health` - Health check

## Clear Cache Setelah Deploy

Setelah mengubah environment variables, jalankan:

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

## Troubleshooting

Jika masih mengalami error:

1. **Pastikan port 5432 digunakan** (bukan 6543)
2. **Pastikan SSL mode require** sudah di-set
3. **Check logs** di `storage/logs/laravel.log`
4. **Test connection** dengan endpoint `/api/test-db`

## Catatan Teknis

- Custom `PostgresConnection` class memastikan emulated prepares selalu aktif
- Middleware otomatis menangani prepared statement errors dengan reconnection
- Semua PDO connections (read dan write) dikonfigurasi dengan emulated prepares

