# 🔧 Cara Restart Queue Worker

## ⚠️ PENTING: Setelah Update Kode, HARUS Restart Queue!

Setiap kali update kode yang berkaitan dengan:
- Email/Mail
- Jobs
- Queue
- AuthController

**WAJIB restart queue worker!**

---

## 🚀 Cara Restart Queue Worker

### Di Server Production (Docker/CasaOS):

1. **Cari container yang menjalankan queue:**
   ```bash
   docker ps
   ```

2. **Restart container queue worker:**
   ```bash
   docker restart <container-name>
   ```
   
   Atau jika queue jalan di container yang sama dengan app:
   ```bash
   docker restart <app-container-name>
   ```

3. **Verifikasi queue worker jalan:**
   ```bash
   docker logs <container-name> -f
   ```
   
   Harus muncul: `Processing: App\Jobs\SendOtpEmailJob`

---

### Di Local Development (XAMPP):

1. **Stop queue worker yang sedang jalan:**
   - Tekan `Ctrl + C` di terminal yang menjalankan `php artisan queue:work`

2. **Jalankan ulang:**
   ```bash
   php artisan queue:work
   ```

3. **Atau gunakan queue:restart (lebih aman):**
   ```bash
   php artisan queue:restart
   ```
   
   Lalu jalankan lagi:
   ```bash
   php artisan queue:work
   ```

---

## ✅ Verifikasi Failover Bekerja

### Test 1: Cek Log
```bash
tail -f storage/logs/laravel.log
```

Ketika OTP dikirim, harus muncul salah satu:
- `✅ Email sent using primary mailer` (jika utama berhasil)
- `✅ Email sent using BACKUP mailer: smtp_backup` (jika failover)
- `❌ Failed to send email with smtp` (jika utama gagal, coba backup)

### Test 2: Cek Queue
```bash
php artisan queue:work --verbose
```

Harus muncul:
```
[2026-01-01 14:30:00] Processing: App\Jobs\SendOtpEmailJob
[2026-01-01 14:30:02] Processed:  App\Jobs\SendOtpEmailJob
```

### Test 3: Monitor Real-time
```bash
# Terminal 1: Monitor log
tail -f storage/logs/laravel.log

# Terminal 2: Monitor queue
php artisan queue:work --verbose

# Terminal 3: Test register
# Buka browser, daftar user baru
```

---

## 🐛 Troubleshooting

### Queue tidak jalan?

**Cek apakah ada job stuck:**
```bash
php artisan queue:failed
```

**Clear job yang gagal:**
```bash
php artisan queue:flush
```

**Retry job yang gagal:**
```bash
php artisan queue:retry all
```

### Email masih gagal semua provider?

1. **Cek config mail sudah benar:**
   ```bash
   php artisan config:cache
   php artisan config:clear
   ```

2. **Test koneksi SMTP manual:**
   ```bash
   php artisan tinker
   ```
   ```php
   Mail::mailer('smtp_backup')->raw('Test', function($msg) {
       $msg->to('test@example.com')->subject('Test');
   });
   ```

3. **Cek kredensial Brevo sudah benar:**
   - Login ke https://app.brevo.com
   - Menu: SMTP & API
   - Pastikan SMTP key aktif
   - Pastikan sender verified

### Log tidak muncul?

1. **Set log level ke debug di .env:**
   ```env
   LOG_LEVEL=debug
   ```

2. **Clear cache:**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

3. **Restart queue:**
   ```bash
   php artisan queue:restart
   ```

---

## 📊 Monitor Sistem Failover

### Cara 1: Via Log File
```bash
grep "BACKUP mailer" storage/logs/laravel.log
```

Output:
```
[2026-01-01 14:25:01] ✅ Email sent using BACKUP mailer: smtp_backup for user@example.com
```

### Cara 2: Via Brevo Dashboard
1. Login: https://app.brevo.com
2. Menu: Statistics → Email
3. Lihat grafik email sent
4. Jika ada spike, berarti failover bekerja!

### Cara 3: Via Database
Check jobs table:
```bash
php artisan tinker
```
```php
DB::table('jobs')->count(); // Harus 0 jika semua processed
DB::table('failed_jobs')->get(); // Cek job yang gagal
```

---

## 🎯 Best Practice

### 1. Setup Supervisor (Production)

Agar queue worker auto-restart:

**Install Supervisor:**
```bash
sudo apt-get install supervisor
```

**Config file: `/etc/supervisor/conf.d/laravel-worker.conf`**
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/worker.log
stopwaitsecs=3600
```

**Start Supervisor:**
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

### 2. Setup Horizon (Opsional)

Laravel Horizon untuk monitoring queue:
```bash
composer require laravel/horizon
php artisan horizon:install
php artisan horizon
```

Dashboard: `http://yourdomain.com/horizon`

### 3. Monitoring Cron

Setup cron job untuk auto-restart jika mati:
```bash
crontab -e
```

Tambahkan:
```cron
* * * * * cd /path/to/project && php artisan queue:work --stop-when-empty
```

---

## 📝 Checklist Setelah Update Kode

- [ ] Commit & push code ke repository
- [ ] Pull code di server production
- [ ] Run `php artisan config:cache`
- [ ] **Restart queue worker** (PENTING!)
- [ ] Test kirim OTP
- [ ] Monitor log untuk error
- [ ] Verifikasi email terkirim
- [ ] Cek Brevo dashboard (jika ada failover)

---

## 🆘 Emergency: Queue Error Parah?

**Hard reset queue:**
```bash
# Stop queue
php artisan queue:restart

# Clear semua job
php artisan queue:flush

# Clear failed jobs
php artisan queue:failed:flush

# Clear cache
php artisan cache:clear
php artisan config:clear

# Start queue lagi
php artisan queue:work
```

**Test ulang:**
```bash
php artisan tinker
```
```php
App\Jobs\SendOtpEmailJob::dispatch('test@example.com', new App\Mail\OtpVerificationMail((object)['name'=>'Test','email'=>'test@example.com'], 123456));
```

---

✅ **Done! Sekarang sistem failover sudah bekerja dengan benar!**
