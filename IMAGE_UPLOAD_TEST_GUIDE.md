# Image Upload & Display - Quick Test Guide

## How to Test the Fixes

### Admin Panel - Upload Image
1. Go to `http://localhost/admin` (or your admin URL)
2. Click on **Anime** in the navigation
3. Click **Edit** on any anime (e.g., "Undead Unluck: Winter-hen")
4. Scroll to **"Upload poster anime (JPG/PNG)"** section
5. Click to upload an image or drag & drop a JPG/PNG file
6. You should see:
   - ✅ Loading indicator while uploading (fixed: was missing before)
   - ✅ Image preview after upload (fixed: now properly stored)
7. Click **Save**
8. Return to anime list - image should display in the Poster column

### Admin Panel - View Anime List
1. Go to Anime list in admin
2. You should see poster thumbnails in the left column
3. Images should display without error

### Public Website - View Images

#### Homepage (`/`)
- Featured anime section (top) should display poster image as background
- "Episode Terbaru" section should show poster thumbnails for latest episodes
- "Sedang Trending" section should show small poster previews

#### Search Page (`/search`)
- All anime thumbnails should display in the grid

#### Anime Detail Page (`/anime/{slug}`)
- Large poster image should display on the left side
- Background image should be visible behind the content

#### Watch Page (`/watch/{episode-id}`)
- Anime poster should display in the sidebar

### What to Verify

**✅ Upload Loading**
- [ ] Loading indicator shows during image upload
- [ ] Indicator disappears when upload completes
- [ ] No errors in browser console

**✅ Admin Display**
- [ ] Images appear in admin anime table
- [ ] Images are clearly visible (60px size)
- [ ] No broken image icons

**✅ Public Display**
- [ ] All images load on homepage
- [ ] Images load on search page
- [ ] Images load on detail page
- [ ] Images load on watch page
- [ ] Gray background shows briefly while loading (better UX)

**✅ Fallback**
- [ ] If image is missing, placeholder appears instead
- [ ] No broken image errors
- [ ] Page layout doesn't break

### Image Locations

Files are stored in: `storage/app/public/posters/`

Accessible at: `http://localhost/storage/posters/{filename}`

Example: If you upload a file for "Attack on Titan", it will be saved as:
- File: `storage/app/public/posters/attack-on-titan.jpg`
- URL: `/storage/posters/attack-on-titan.jpg`

### Troubleshooting

If images still don't appear:

1. **Check storage symlink exists:**
   ```
   public/storage -> ../storage/app/public
   ```

2. **Verify directories exist:**
   - `storage/app/public/posters/` 

3. **Clear all caches:**
   ```bash
   php artisan config:clear
   php artisan view:clear
   php artisan cache:clear
   ```

4. **Check file permissions:**
   - `storage/` directory should be writable
   - Windows: Usually OK by default
   - Linux: Run `chmod 775 storage/`

5. **Check database values:**
   - Run `php artisan tinker`
   - Then: `Anime::first()->poster_image` should show a valid path like `posters/filename.jpg`

### Before & After Comparison

| Issue | Before | After |
|-------|--------|-------|
| Upload indicator | Missing/Broken | ✅ Shows progress |
| File storage location | Wrong directory | ✅ `/storage/app/public/posters/` |
| Admin image display | Images not showing | ✅ Displays in table |
| Public image paths | Invalid file names | ✅ Clean slug-based names |
| Missing images | Page breaks | ✅ Fallback placeholder shows |
| Database paths | Livewire temp paths | ✅ Valid poster file paths |
