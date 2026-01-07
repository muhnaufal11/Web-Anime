#!/bin/bash

# Fix Fuzzy Search Feature - Production Steps

# 1. Clear routes cache
php artisan route:clear
php artisan route:cache

# 2. Clear view cache
php artisan view:clear

# 3. Clear config cache
php artisan config:clear
php artisan config:cache

# 4. Verify route exists
php artisan route:list | grep search.suggestions

# 5. Test API
# curl -X GET "https://nipnime.my.id/api/search/suggestions?q=naruto" 

echo "✓ All caches cleared!"
echo "✓ Autocomplete should work now"
