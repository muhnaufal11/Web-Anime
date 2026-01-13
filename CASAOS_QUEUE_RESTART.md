# 🐳 Restart Queue Worker di CasaOS

## ⚠️ PENTING: Setelah Update Kode OTP Failover, HARUS Restart Queue!

---

## 🚀 Cara 1: Via CasaOS UI (Termudah)

### Step 1: Buka CasaOS Dashboard
```
http://your-casaos-ip:8089
```

### Step 2: Buka App/Container yang berjalankan Laravel
- Cari container: `web`, `app`, `nipnime`, atau nama sesuai docker-compose

### Step 3: Restart Container
1. Klik container → **Manage**
2. Klik **Restart** atau **Stop** lalu **Start**
3. Tunggu container restart (~10 detik)

### Step 4: Verifikasi
1. Klik container → **Logs**
2. Lihat apakah queue worker sudah jalan:
   ```
   Starting queue worker
   Processing: App\Jobs\SendOtpEmailJob
   ```

---

## 🚀 Cara 2: Via Terminal SSH (Lebih Control)

### Step 1: SSH ke CasaOS Server
```bash
ssh root@your-casaos-ip
```

### Step 2: Lihat List Container
```bash
docker ps
```

Output:
```
CONTAINER ID   IMAGE          NAMES
abc123def456   app:latest     nipnime_web_1
xyz789qwe012   mysql:8.0      nipnime_db_1
```

### Step 3: Restart Container
```bash
docker restart nipnime_web_1
```

Atau restart semua container:
```bash
docker-compose -f /path/to/docker-compose.yml restart
```

### Step 4: Monitor Logs
```bash
docker logs -f nipnime_web_1
```

Harus muncul:
```
[2026-01-01 14:30:00] Starting queue worker...
[2026-01-01 14:30:01] Listening on: default
[2026-01-01 14:30:02] Processing: App\Jobs\SendOtpEmailJob
```

---

## 🚀 Cara 3: Via Docker Compose (Rekomendasi)

### Step 1: SSH ke Server
```bash
ssh root@your-casaos-ip
```

### Step 2: Masuk ke Folder Project
```bash
cd /path/to/your/project
# Biasanya: /casaos/data/apps/... atau /root/...
```

### Step 3: Restart Service Tertentu
```bash
# Restart web container saja
docker-compose restart web

# Atau restart semua
docker-compose restart

# Atau full rebuild
docker-compose down && docker-compose up -d
```

### Step 4: Monitor
```bash
docker-compose logs -f web
```

---

## 🎯 Yang Harus Di-Restart

Container yang menjalankan **queue worker**. Biasanya:

- `web` - Jika queue jalan di app container
- `app` - Jika ada container terpisah
- `worker` - Jika ada container khusus queue
- `laravel` - Jika custom naming

**Cek di docker-compose.yml:**
```yaml
services:
  web:
    image: app:latest
    command: php artisan queue:work  # ← Queue jalan di sini
    
  # atau
  
  worker:
    image: app:latest
    command: php artisan queue:work  # ← Queue jalan di sini
```

---

## ✅ Verifikasi Failover Bekerja di CasaOS

### Cara 1: Via CasaOS UI
1. Buka container → **Logs**
2. Cari text: `BACKUP mailer` atau `SendOtpEmailJob`
3. Harus muncul setelah kirim OTP

### Cara 2: Via SSH Terminal
```bash
docker logs -f nipnime_web_1 | grep "Email sent"
```

Output (jika failover bekerja):
```
✅ Email sent using BACKUP mailer: smtp_backup for user@example.com
```

### Cara 3: Test Real
1. Daftar user baru di app
2. Lihat logs container:
   ```bash
   docker logs -f nipnime_web_1
   ```
3. Harus muncul salah satu:
   - `✅ Email sent using primary mailer` (utama berhasil)
   - `✅ Email sent using BACKUP mailer: smtp_backup` (failover bekerja!)

---

## 🔧 Troubleshooting CasaOS

### Container tidak mau restart?

**Force stop & start:**
```bash
docker stop nipnime_web_1
docker start nipnime_web_1
```

### Queue masih tidak jalan?

**Check container status:**
```bash
docker ps -a | grep nipnime
```

Jika `Exited`, cek logs:
```bash
docker logs nipnime_web_1
```

**Jika error, rebuild:**
```bash
cd /path/to/project
docker-compose build --no-cache
docker-compose up -d
```

### Tidak tahu folder project di mana?

**Cari folder project:**
```bash
find / -name "docker-compose.yml" 2>/dev/null
```

Output:
```
/casaos/data/apps/nipnime/docker-compose.yml
```

---

## 📋 Checklist Restart Queue di CasaOS

- [ ] SSH ke CasaOS server (atau buka CasaOS UI)
- [ ] Identifikasi container yang jalan queue (`docker ps`)
- [ ] Restart container (`docker restart <container-name>`)
- [ ] Monitor logs untuk melihat queue worker start
- [ ] Test kirim OTP di app
- [ ] Lihat logs, pastikan muncul `SendOtpEmailJob` atau `BACKUP mailer`
- [ ] Verifikasi OTP terkirim ke email

---

## 🎯 Jika Pakai Custom Queue Container

Jika ada container **terpisah** untuk queue, format docker-compose:

```yaml
version: '3.8'
services:
  web:
    image: app:latest
    command: php artisan serve --host=0.0.0.0 --port=8000
    ports:
      - "8000:8000"

  worker:
    image: app:latest
    command: php artisan queue:work
    depends_on:
      - web
```

Restart worker saja:
```bash
docker-compose restart worker
```

---

## 💡 Pro Tips untuk CasaOS

### 1. Auto-Restart on Failure
Edit docker-compose.yml:
```yaml
web:
  restart_policy:
    condition: on-failure
    max_attempts: 3
    delay: 5s
```

### 2. Monitor Real-time di CasaOS UI
1. Buka CasaOS Dashboard
2. Pilih container
3. Tab **Logs** → Real-time monitoring

### 3. SSH Shortcut
Simpan SSH config `~/.ssh/config`:
```
Host casaos
  HostName your-casaos-ip
  User root
  Port 22
```

Lalu cukup:
```bash
ssh casaos
docker logs -f nipnime_web_1
```

---

## ⚡ Quick Command Reference

```bash
# SSH ke CasaOS
ssh root@your-casaos-ip

# List containers
docker ps

# Restart container
docker restart <container-name>

# See logs
docker logs -f <container-name>

# Restart via compose
cd /path/to/project
docker-compose restart

# See compose logs
docker-compose logs -f

# Hard restart (rebuild)
docker-compose down && docker-compose up -d
```

---

## 🆘 Emergency Queue Error?

**Reset queue di CasaOS:**
```bash
# Enter container
docker exec -it nipnime_web_1 bash

# Inside container:
php artisan queue:restart
php artisan queue:flush
php artisan cache:clear
php artisan config:clear

# Exit container
exit

# Restart container
docker restart nipnime_web_1
```

---

✅ **Sekarang queue di CasaOS siap dengan failover system!**

**Jangan lupa restart container setelah update kode!** ⚠️
