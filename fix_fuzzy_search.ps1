# Fix Fuzzy Search Feature - Production Steps (Windows)

# 1. Clear routes cache
php artisan route:clear
php artisan route:cache

# 2. Clear view cache
php artisan view:clear

# 3. Clear config cache
php artisan config:clear
php artisan config:cache

# 4. Verify route exists
php artisan route:list | Select-String "search.suggestions"

# 5. Test API
# Invoke-WebRequest -Uri "https://nipnime.my.id/api/search/suggestions?q=naruto"

Write-Host "✓ All caches cleared!"
Write-Host "✓ Autocomplete should work now"
