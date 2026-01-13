# Web Anime - Complete Setup Guide

This guide will help you set up the Web Anime streaming platform from scratch.

## Table of Contents
1. [Pre-Installation Requirements](#pre-installation-requirements)
2. [Initial Setup](#initial-setup)
3. [Database Configuration](#database-configuration)
4. [Running Migrations](#running-migrations)
5. [Seeding Sample Data](#seeding-sample-data)
6. [Admin User Creation](#admin-user-creation)
7. [Running the Application](#running-the-application)
8. [Testing the Application](#testing-the-application)

## Pre-Installation Requirements

Before you begin, ensure you have the following installed:

### Required Software
- **PHP 8.2 or higher** - Check: `php -v`
- **Composer** - Check: `composer --version`
- **Node.js & npm** - Check: `node -v` and `npm -v`
- **MySQL/MariaDB** - Check by connecting to your database server
- **Git** (optional but recommended)

### For Windows (XAMPP Users)
1. Ensure XAMPP is running (Apache and MySQL)
2. PHP should be accessible from command line
3. MySQL should be running on default port 3306

## Initial Setup

### Step 1: Navigate to Project Directory
```bash
cd c:\xampp\htdocs\Web\ Anime
# Or from your installation location
```

### Step 2: Install PHP Dependencies
```bash
composer install
```
This will install all Laravel and FilamentPHP dependencies.

### Step 3: Install JavaScript Dependencies
```bash
npm install
```
This will install Tailwind CSS, PostCSS, and other frontend dependencies.

### Step 4: Create Environment File
```bash
copy .env.example .env
```

### Step 5: Generate Application Key
```bash
php artisan key:generate
```

## Database Configuration

### Step 1: Create MySQL Database
Open phpMyAdmin or MySQL command line and create a new database:

```sql
CREATE DATABASE web_anime;
```

### Step 2: Update .env File
Edit `.env` and update the database section:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=web_anime
DB_USERNAME=root
DB_PASSWORD=
```

**Note**: If you have a password for MySQL, replace the password field.

### Step 3: Verify Database Connection
Test the connection:
```bash
php artisan db:show
```

You should see database information displayed.

## Running Migrations

### Run All Migrations
```bash
php artisan migrate
```

This will create the following tables:
- `users`
- `password_resets`
- `failed_jobs`
- `personal_access_tokens`
- `genres`
- `animes`
- `anime_genre`
- `episodes`
- `video_servers`

### If You Need to Reset (Fresh Install)
```bash
php artisan migrate:fresh
```

**Warning**: This will delete all data and recreate tables.

## Seeding Sample Data

### Option 1: Seed with Sample Anime
```bash
php artisan db:seed
```

This will create:
- 10 genres (Action, Adventure, Comedy, Drama, Fantasy, Horror, Sci-Fi, Romance, Slice of Life, Supernatural)
- 5 sample anime (Attack on Titan, Death Note, My Hero Academia, Demon Slayer, One Piece)
- 3 episodes per anime
- 3 video servers per episode

### Option 2: Seed Specific Seeder
```bash
php artisan db:seed --class=DatabaseSeeder
```

## Admin User Creation

### Step 1: Enter Tinker Shell
```bash
php artisan tinker
```

### Step 2: Create Admin User
```php
App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@example.com',
    'password' => Hash::make('password')
])
```

### Step 3: Exit Tinker
```php
exit
```

**Credentials**:
- Email: `admin@example.com`
- Password: `password`

## Running the Application

### Step 1: Build Frontend Assets
```bash
npm run build
```

Or for development with hot reload:
```bash
npm run dev
```

### Step 2: Start Laravel Development Server
Open a new terminal and run:
```bash
php artisan serve
```

The application will be available at `http://localhost:8000`

### Step 3: Create Storage Symlink
```bash
php artisan storage:link
```

This creates a symlink for public access to uploaded files.

## Testing the Application

### Frontend (Public)
1. Open `http://localhost:8000` in your browser
2. You should see the homepage with featured anime
3. Test the following features:
   - Click on anime to view details
   - Click "Watch Now" to view an episode
   - Test the video server switcher
   - Try the search functionality
   - Test filters by genre, status, and type

### Admin Panel
1. Open `http://localhost:8000/admin` in your browser
2. Log in with the credentials you created:
   - Email: `admin@example.com`
   - Password: `password`
3. You should see the admin dashboard
4. Test the following:
   - View Genres resource
   - View Animes resource (with grid layout)
   - View Episodes resource (with repeater fields for video servers)
   - View Video Servers resource
   - Try creating a new anime with image upload
   - Try adding genres to an anime

## Troubleshooting

### Issue: Composer install fails
**Solution**: Update Composer
```bash
composer self-update
composer install --ignore-platform-reqs
```

### Issue: PHP version not compatible
**Solution**: Check your PHP version
```bash
php -v
```
Ensure it's 8.2 or higher.

### Issue: Database connection error
**Solution**: Verify database credentials in .env and ensure MySQL is running.

### Issue: CSS not loading
**Solution**: Rebuild assets
```bash
npm install
npm run build
php artisan storage:link
```

### Issue: Admin panel blank or 404
**Solution**: Ensure FilamentPHP is installed
```bash
composer require filament/filament:"^3.0"
```

### Issue: Migrations don't find tables
**Solution**: Check that you ran migrations
```bash
php artisan migrate:status
```

### Issue: Storage link error
**Solution**: Remove and recreate the symlink
```bash
rm public/storage
php artisan storage:link
```

## Development Commands Reference

```bash
# Run migrations
php artisan migrate

# Fresh database
php artisan migrate:fresh

# Seed database
php artisan db:seed

# Create new model with migration
php artisan make:model ModelName -m

# Create new controller
php artisan make:controller ControllerName

# Create Livewire component
php artisan make:livewire ComponentName

# Tinker shell
php artisan tinker

# Serve application
php artisan serve

# Build frontend
npm run build

# Development mode with hot reload
npm run dev

# Cache configuration
php artisan config:cache

# Clear all cache
php artisan cache:clear
```

## Filament Admin Commands

```bash
# Publish Filament assets
php artisan filament:install

# Create new admin resource
php artisan make:filament-resource ResourceName

# Create admin user
php artisan tinker
# Then: App\Models\User::create([...])
```

## Next Steps

1. **Add More Anime**: Use the admin panel to add your anime collection
2. **Upload Posters**: Add poster images for each anime
3. **Add Episodes**: Create episodes for your anime
4. **Add Video Servers**: Link streaming servers for each episode
5. **Customize Theme**: Modify colors and styling in `tailwind.config.js`
6. **Add Authentication**: If you want user accounts and watchlists

## Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [FilamentPHP Documentation](https://filamentphp.com/docs)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [Livewire Documentation](https://livewire.laravel.com/docs)

## Support

If you encounter any issues:
1. Check the logs: `storage/logs/laravel.log`
2. Run `php artisan migrate:status` to check migration status
3. Verify database credentials in .env
4. Clear cache: `php artisan cache:clear`

## Production Deployment

When deploying to production:

1. Update .env for production settings
2. Run: `php artisan config:cache`
3. Run: `php artisan optimize`
4. Set proper file permissions
5. Use a production-grade web server (Nginx/Apache)
6. Configure SSL certificate
7. Set up proper error logging
8. Run: `npm run build` (not dev)

## Support and Questions

For detailed documentation, see README.md or visit the individual documentation links above.
