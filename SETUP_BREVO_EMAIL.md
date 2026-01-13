# 📧 Setup Brevo.com untuk Email OTP Profesional

## Kenapa Brevo?

✅ **GRATIS 300 emails/day** - Cukup untuk OTP sistem  
✅ **Custom Domain** - Email dari `admin@nipnime.my.id` bukan Gmail  
✅ **Professional** - Tidak masuk spam, deliverability tinggi  
✅ **Dashboard Lengkap** - Tracking open rate, click rate  
✅ **Easy Setup** - 10 menit saja  

---

## 🚀 Cara Setup Brevo (Step by Step)

### Step 1: Daftar Akun Brevo

1. Buka: https://app.brevo.com/account/register
2. Isi form registrasi:
   - Email kamu
   - Password
   - Company name: `NipNime` atau nama lain
3. Klik **Sign up**
4. Verifikasi email (cek inbox)

### Step 2: Lengkapi Profil

1. Setelah login, lengkapi informasi:
   - Company/Website: `nipnime.my.id`
   - Industry: `Entertainment` atau `Technology`
   - Country: `Indonesia`
2. Skip survey jika mau (klik Skip)

### Step 3: Generate SMTP Key

1. Klik menu: **SMTP & API** (di sidebar kiri)
2. Pilih tab: **SMTP**
3. Klik tombol: **Generate a new SMTP key**
4. Beri nama: `Laravel OTP System`
5. **COPY** SMTP key yang muncul (hanya muncul 1x!)
   ```
   xkeysib-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
   ```
6. Simpan di tempat aman (akan dipakai nanti)

### Step 4: Add Verified Sender

⚠️ **PENTING:** Agar email terkirim dari `admin@nipnime.my.id`

1. Klik menu: **Senders, Domains & Dedicated IPs**
2. Pilih tab: **Senders**
3. Klik: **Add a new sender**
4. Isi form:
   - Email: `admin@nipnime.my.id`
   - Sender name: `NipNime`
5. Klik **Add**
6. Brevo akan kirim email verifikasi ke `admin@nipnime.my.id`
7. **Cek inbox `admin@nipnime.my.id`** dan klik link verifikasi

✅ Setelah verified, email OTP akan terkirim dari `admin@nipnime.my.id`!

### Step 5: Update File .env

Tambahkan ke file `.env` kamu:

```env
# Provider Utama (yang sekarang)
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com  # atau provider utama kamu
MAIL_PORT=587
MAIL_USERNAME=admin@nipnime.my.id
MAIL_PASSWORD=password-utama-kamu
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=admin@nipnime.my.id
MAIL_FROM_NAME="NipNime"

# Brevo Backup (Failover pertama)
MAIL_BACKUP_HOST=smtp-relay.brevo.com
MAIL_BACKUP_PORT=587
MAIL_BACKUP_USERNAME=email-login-brevo-kamu@gmail.com
MAIL_BACKUP_PASSWORD=xkeysib-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
MAIL_BACKUP_ENCRYPTION=tls

# Gmail Backup (Opsional - failover terakhir)
MAIL_BACKUP2_HOST=smtp.gmail.com
MAIL_BACKUP2_PORT=587
MAIL_BACKUP2_USERNAME=your-gmail@gmail.com
MAIL_BACKUP2_PASSWORD=your-app-password
MAIL_BACKUP2_ENCRYPTION=tls
```

**Catatan:**
- `MAIL_BACKUP_USERNAME` = Email yang kamu pakai untuk login ke Brevo
- `MAIL_BACKUP_PASSWORD` = SMTP key dari Step 3
- `MAIL_FROM_ADDRESS` tetap `admin@nipnime.my.id` di semua provider!

### Step 6: Test Email

Test dengan cara:
```bash
php artisan tinker
```

Lalu jalankan:
```php
Mail::raw('Test email failover', function($msg) {
    $msg->to('email-test@example.com')
        ->subject('Test Brevo Backup');
});
```

Cek inbox email test kamu!

---

## 📊 Cara Cek Email Terkirim

1. Login ke Brevo dashboard
2. Menu: **Statistics** → **Email**
3. Lihat grafik email terkirim, delivered, opened
4. Klik **Logs** untuk detail setiap email

---

## ⚡ Cara Kerja Sistem Failover

```
Provider Utama (Hostinger/dll)
         ↓ (Gagal/Limit)
    Brevo.com (300/day)
         ↓ (Gagal/Limit)
    Gmail (500/day)
```

**Semua email tetap dari:** `admin@nipnime.my.id` ✅

---

## 🔥 Tips & Tricks

### Tingkatkan Deliverability

1. **Setup SPF Record:**
   - Login ke domain registrar (Hostinger/Niagahoster)
   - Tambah TXT record:
   ```
   v=spf1 include:spf.brevo.com include:_spf.google.com ~all
   ```

2. **Setup DKIM (Opsional):**
   - Di Brevo: Menu **Senders** → **Authenticate your domain**
   - Follow instruksi add DKIM record ke DNS

### Monitor Quota

Cek quota Brevo:
1. Dashboard → **Settings** → **Plan**
2. Lihat: `Daily email limit: 300`
3. Reset otomatis setiap hari jam 00:00 UTC

### Email Masuk Spam?

Jika email masuk spam:
1. Pastikan sender sudah verified ✅
2. Setup SPF & DKIM record
3. Jangan kirim terlalu cepat (pakai rate limit)
4. Tambahkan unsubscribe link (untuk email marketing)

---

## 🆘 Troubleshooting

### Error: "Sender not verified"
**Solusi:** Cek inbox `admin@nipnime.my.id` dan klik link verifikasi dari Brevo

### Error: "Daily quota exceeded"
**Solusi:** Sistem otomatis beralih ke Gmail backup. Tunggu besok atau upgrade Brevo plan.

### Error: "Authentication failed"
**Solusi:** 
- Pastikan SMTP key benar
- Regenerate SMTP key di dashboard Brevo
- Update `.env` dengan key baru

### Email tidak terkirim sama sekali
**Solusi:**
1. Cek log: `storage/logs/laravel.log`
2. Pastikan queue worker jalan: `php artisan queue:work`
3. Test koneksi SMTP manual dengan telnet

---

## 💰 Upgrade Plan (Opsional)

Jika 300 emails/day kurang:

| Plan | Price | Emails/day | Features |
|------|-------|------------|----------|
| **Free** | $0 | 300 | Basic |
| **Lite** | $25/month | 10,000 | No Brevo logo |
| **Premium** | $65/month | 20,000 | Advanced stats |

📌 Untuk OTP sistem, **Free plan sudah cukup!**

---

## ✅ Checklist Setup

- [ ] Daftar akun Brevo
- [ ] Generate SMTP key
- [ ] Add & verify sender `admin@nipnime.my.id`
- [ ] Update `.env` dengan Brevo credentials
- [ ] Test kirim email
- [ ] Setup SPF record (opsional tapi recommended)
- [ ] Monitor email di dashboard Brevo

---

## 📚 Resource

- Dashboard Brevo: https://app.brevo.com
- Documentation: https://developers.brevo.com
- Support: https://help.brevo.com
- Status page: https://status.brevo.com

---

**🎉 Done! Sekarang sistem OTP kamu production-ready dengan email profesional dari `admin@nipnime.my.id`!**
