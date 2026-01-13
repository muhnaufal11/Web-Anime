# ⚡ Cara Clear Route Cache untuk Fuzzy Search di Docker

Karena Docker server berjalan di CasaOS Anda, jalankan command ini di **terminal server Anda** (bukan terminal lokal):

## Langkah 1: SSH ke Server atau Buka Terminal CasaOS

Masuk ke terminal server CasaOS Anda via SSH atau akses terminal di interface CasaOS.

## Langkah 2: Cari Nama Container Laravel

```bash
docker ps --format "table {{.Names}}\t{{.Image}}"
```

Lihat output dan cari container yang namenya kira-kira:
- `nipnime-app`
- `laravel`
- `app`
- atau nama lain yang menjalankan aplikasi

Catat nama containernya, misalnya: `nipnime-app`

## Langkah 3: Clear Route Cache

Jalankan command ini (ganti `nipnime-app` dengan nama container Anda):

```bash
docker exec nipnime-app php artisan route:clear
docker exec nipnime-app php artisan route:cache
docker exec nipnime-app php artisan view:clear
docker exec nipnime-app php artisan config:clear
docker exec nipnime-app php artisan config:cache
```

## Langkah 4: Verify Route Terdaftar

```bash
docker exec nipnime-app php artisan route:list | grep search.suggestions
```

Seharusnya output:

```
GET       /api/search/suggestions ......................... search.suggestions
```

## Langkah 5: Test API

Buka browser, akses:

```
https://nipnime.my.id/api/search/suggestions?q=naruto
```

(Ganti nipnime.my.id dengan domain Anda)

Seharusnya response JSON berisi array suggestions.

## Langkah 6: Test Autocomplete di Website

Refresh website, ketik di search box. Autocomplete dropdown seharusnya muncul sekarang.

---

**Jika masih error:**
1. Check browser console (F12) untuk JavaScript error
2. Check Docker logs: `docker logs nipnime-app`
3. Verify method `searchSuggestions()` exists: `docker exec nipnime-app php artisan tinker` lalu `grep -r "searchSuggestions" app/`

---

**Note:** Jangan ubah kode apapun, hanya clear cache dan restart jika perlu.
