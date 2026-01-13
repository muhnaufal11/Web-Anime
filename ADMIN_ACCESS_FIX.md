# 🔒 ADMIN ACCESS FIX

## Problem
User mendapat 403 FORBIDDEN saat akses /admin

## Root Cause
Session masih menyimpan `is_admin = false` dari sebelum user di-set sebagai admin.

## Solution

### Quick Fix (Recommended)
1. **Logout** dari website
   - Klik avatar di navbar
   - Klik "🚪 LOGOUT"

2. **Login ulang** dengan:
   - Email: `naufalrabbani146@gmail.com`
   - Password: password yang kamu gunakan

3. **Akses admin:**
   - Go to: `http://localhost/admin`
   - Seharusnya sudah bisa masuk ✅

### Alternative Fix
Jika masih 403, coba:

1. **Hard refresh:**
   - Tekan `Ctrl + Shift + Delete`
   - Clear cookies & cache
   - Close browser completely

2. **Reopen browser & login ulang**

3. **Test admin access:**
   ```
   http://localhost/admin
   ```

### Verify Admin Status
Jalankan ini untuk cek status admin:
```bash
php list_users.php
```

Should show:
```
✅ ADMIN | naufal | naufalrabbani146@gmail.com
```

### Make Another User Admin
```bash
php quick_make_admin.php email@example.com
```

## Status
- ✅ Database: is_admin column added
- ✅ User model: canAccessPanel() implemented
- ✅ User naufal: is_admin = true
- ⏳ Session: Need fresh login

## Next Steps
1. Logout
2. Login ulang
3. Access /admin
4. Should work! 🎉
