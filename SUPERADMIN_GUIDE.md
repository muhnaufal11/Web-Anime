# Superadmin Role & Admin Performance System

## 📋 Overview

Sistem baru yang memungkinkan kamu (superadmin) untuk:
- ✅ Mengelola role pengguna (User → Admin → Superadmin)
- ✅ Melacak kinerja admin per episode 
- ✅ Membayar admin Rp 500 per episode
- ✅ Mengelola status pembayaran (Pending → Approved → Paid)

---

## 🚀 Instalasi

Jalankan migrasi untuk membuat tabel baru:

```bash
php artisan migrate
```

Ini akan membuat:
- Field `role` di tabel users
- Tabel `admin_episode_logs` untuk tracking episode & pembayaran
- Field `created_by` di tabel episodes

---

## 👤 Role System

### 3 Role Tersedia:

| Role | Deskripsi | Akses Admin Panel |
|------|-----------|------------------|
| **User** | User biasa, penonton | ❌ Tidak |
| **Admin** | Admin pembuat episode, dapat bayaran per episode | ✅ Ya (terbatas) |
| **Superadmin** | Kamu - kontrol penuh sistem | ✅ Ya (full) |

---

## 🔧 Cara Menggunakan

### 1. Buat Superadmin (Dirimu Sendiri)

```bash
php make_superadmin.php nama@email.com
```

**Contoh:**
```bash
php make_superadmin.php saya@anime.com
```

### 2. Jadikan User Menjadi Admin

**Via CLI:**
```bash
php make_admin.php admin@anime.com
```

**Via Admin Panel:**
- Login ke `http://localhost/admin`
- Buka **User Management → Users**
- Edit user → Ubah role ke "Admin"
- Atau gunakan action **"Jadikan Admin"** pada table

### 3. Tambah Episode & Auto-Track Performance

Ketika admin membuat episode di panel admin:
- Episode otomatis tercatat dengan `created_by`
- **AdminEpisodeLog** otomatis dibuat
- Status: **Pending** (menunggu approval)
- Amount: **Rp 500** (default)

### 4. Manage Admin Performance (Superadmin Only)

**Menu:** Admin Panel → Superadmin → **Admin Performance**

Fitur:
- ✅ Lihat semua episode yang dibuat admin
- ✅ Lihat status pembayaran (Pending/Approved/Paid)
- ✅ Ubah amount jika perlu
- ✅ Set status → Approved → Paid
- ✅ Bulk action: Tandai Dibayar untuk banyak episode

---

## 📊 Dashboard Stats

Di halaman dashboard, superadmin akan lihat:
- **Pending Bayaran Admin**: Total Rp yang harus dibayar

Contoh: Jika 5 episode pending × Rp 500 = **Rp 2.500**

---

## 📝 Database Schema

### admin_episode_logs
```
- id (Primary Key)
- user_id (Foreign Key → users)
- episode_id (Foreign Key → episodes) 
- amount (Integer) - default 500
- status (pending|approved|paid)
- note (Text)
- created_at
- updated_at
```

**Unique Constraint:** `(user_id, episode_id)` - 1 admin per episode

### users
```
- role (string) - user|admin|superadmin
- is_admin (boolean) - sync dengan role
```

### episodes  
```
- created_by (Foreign Key → users, nullable)
```

---

## 🔐 Permissions

| Action | User | Admin | Superadmin |
|--------|------|-------|-----------|
| Buat episode | ❌ | ✅ | ✅ |
| Lihat admin log | ❌ | ❌ | ✅ |
| Ubah role | ❌ | ❌ | ✅ |
| Bayar admin | ❌ | ❌ | ✅ |
| Edit log | ❌ | ❌ | ✅ |

---

## 📈 Admin Performance Tracking

### Fitur Real-time:

1. **Per Admin View** (User Resource)
   - Total episode dibuat
   - Total bayaran

2. **Admin Performance Page**
   - Tabel detail per episode
   - Filter by status / admin
   - Bulk update status
   - Edit individual entries

3. **Dashboard Widget**
   - Card: "Pending Bayaran Admin"
   - Hanya visible untuk superadmin
   - Real-time total

---

## 💡 Contoh Workflow

### Scenario: Admin A membuat 3 episode

1. **Admin A** masuk ke panel → Buat 3 episode
2. **Sistem** otomatis buat 3 AdminEpisodeLog:
   - Status: pending
   - Amount: Rp 500 each = Rp 1.500 total
   
3. **Superadmin (Kamu)** lihat di Admin Performance:
   - Episode 1: Pending - Rp 500
   - Episode 2: Pending - Rp 500
   - Episode 3: Pending - Rp 500
   
4. **Superadmin** approve:
   - Bulk select ketiga
   - Action: "Tandai Dibayar"
   - Semua jadi "Paid"

---

## 🎯 Key Features

✨ **Automatic Tracking**
- Saat admin buat episode, log otomatis dibuat

✨ **Status Management**
- Pending → Approve → Paid workflow

✨ **Flexible Amount**
- Default Rp 500, bisa diedit per episode

✨ **Bulk Operations**
- Batch update status untuk efisiensi

✨ **Role-based Visibility**
- Admin tidak bisa lihat performance page
- Hanya superadmin yang punya akses

✨ **Dashboard Integration**
- Real-time stats di dashboard

---

## 🔍 Checking Current Setup

**Lihat semua user & role:**
```bash
php list_users.php
```

**Debug user access:**
```bash
php debug_admin.php
```

---

## 📞 Notes

- **Backward Compatible**: `is_admin` field tetap ada dan sync dengan `role`
- **Auto-migration**: Existing admins (is_admin=true) jadi role='admin'
- **Safe deletion**: Episode deletion otomatis delete logs-nya
- **Unique constraint**: Satu admin hanya punya 1 log per episode

---

## 🎓 Models & Relations

```
User
├── createdEpisodes() → HasMany Episode
└── adminEpisodeLogs() → HasMany AdminEpisodeLog

Episode
├── creator() → BelongsTo User (created_by)
└── adminEpisodeLogs() → HasMany AdminEpisodeLog

AdminEpisodeLog
├── user() → BelongsTo User
└── episode() → BelongsTo Episode
```

---

**Selesai! Kamu sekarang punya sistem manajemen admin yang lengkap. 🚀**
