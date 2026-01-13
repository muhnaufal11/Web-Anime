# Web Anime - Anime Streaming Platform

A complete anime streaming website built with Laravel 11, FilamentPHP v3, and Tailwind CSS. Inspired by AnimeSail, this platform allows guests to browse and watch anime without authentication, while admins manage content through a powerful admin panel.

## Features

### 🌍 Public Features
- **Homepage**: Featured anime showcase, latest episodes, and popular series
- **Anime Directory**: Browse, search, and filter anime by genre, status, and type
- **Anime Details**: Comprehensive anime information with genre tags and episode list
- **Video Player**: Stream episodes with multiple server options
- **Server Switcher**: Switch between different video servers seamlessly using Livewire
- **Responsive Design**: Mobile, tablet, and desktop optimized
- **SEO-Friendly**: Clean URLs using slugs

### 🔐 Admin Panel (FilamentPHP v3)
- **Anime Management**: Create, edit, and delete anime with image uploads
- **Episode Management**: Add episodes with detailed information
- **Video Server Management**: Link multiple streaming servers per episode
- **Genre Management**: Organize anime by genres
- **Automatic Slug Generation**: URLs are generated automatically from titles
- **Grid Layouts**: Beautiful grid-based resource management
- **Repeater Fields**: Manage multiple video servers for each episode

## Tech Stack

- **Backend**: Laravel 11
- **Admin Panel**: FilamentPHP v3
- **Frontend**: Blade Templates + Tailwind CSS
- **Real-time Updates**: Livewire 3
- **Database**: MySQL/MariaDB
- **Build Tool**: Vite

## Project Structure

```
Web Anime/
├── app/
│   ├── Http/Controllers/
│   │   ├── HomeController.php
│   │   ├── DetailController.php
│   │   └── WatchController.php
│   ├── Livewire/
│   │   └── VideoPlayer.php
│   ├── Models/
│   │   ├── Anime.php
│   │   ├── Genre.php
│   │   ├── Episode.php
│   │   └── VideoServer.php
│   └── Filament/Resources/
│       ├── AnimeResource.php
│       ├── GenreResource.php
│       ├── EpisodeResource.php
│       └── VideoServerResource.php
├── database/
│   ├── migrations/
│   │   ├── 2025_12_26_100000_create_genres_table.php
│   │   ├── 2025_12_26_100001_create_animes_table.php
│   │   ├── 2025_12_26_100002_create_anime_genre_table.php
│   │   ├── 2025_12_26_100003_create_episodes_table.php
│   │   └── 2025_12_26_100004_create_video_servers_table.php
│   └── seeders/
│       └── DatabaseSeeder.php
├── resources/
│   ├── css/
│   │   └── app.css (Tailwind imports)
│   ├── views/
│   │   ├── layouts/
│   │   │   └── app.blade.php
│   │   ├── home.blade.php
│   │   ├── detail.blade.php
│   │   ├── watch.blade.php
│   │   ├── search.blade.php
│   │   └── livewire/
│   │       └── video-player.blade.php
│   └── js/
│       ├── app.js
│       └── bootstrap.js
├── routes/
│   └── web.php (Public routes)
└── config/
    └── app.php (Configuration files)
```

## Database Schema

### Genres Table
```sql
- id (Primary Key)
- name (Unique String)
- slug (Unique String)
- timestamps
```

### Animes Table
```sql
- id (Primary Key)
- title (Unique String)
- slug (Unique String)
- synopsis (Long Text)
- poster_image (String, nullable)
- type (Enum: TV, Movie, ONA)
- status (Enum: Ongoing, Completed)
- release_year (Integer, nullable)
- rating (Float, nullable)
- featured (Boolean)
- timestamps
```

### Anime_Genre Table (Many-to-Many Pivot)
```sql
- id (Primary Key)
- anime_id (Foreign Key → Animes)
- genre_id (Foreign Key → Genres)
- timestamps
- unique(anime_id, genre_id)
```

### Episodes Table
```sql
- id (Primary Key)
- anime_id (Foreign Key → Animes)
- episode_number (Integer)
- title (String)
- slug (Unique String)
- description (Long Text, nullable)
- timestamps
- unique(anime_id, episode_number)
```

### VideoServers Table
```sql
- id (Primary Key)
- episode_id (Foreign Key → Episodes)
- server_name (String)
- embed_url (Long Text)
- is_active (Boolean)
- timestamps
```

## Model Relationships

- **Anime** has many **Genres** (Many-to-Many)
- **Anime** has many **Episodes** (One-to-Many)
- **Episode** belongs to **Anime** (One-to-Many)
- **Episode** has many **VideoServers** (One-to-Many)
- **VideoServer** belongs to **Episode** (One-to-Many)
- **Genre** has many **Animes** (Many-to-Many)

## Installation & Setup

### Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js & npm
- MySQL/MariaDB

### Steps

1. **Clone/Navigate to the project directory**
```bash
cd c:/xampp/htdocs/Web\ Anime
```

2. **Install PHP dependencies**
```bash
composer install
```

3. **Install JavaScript dependencies**
```bash
npm install
```

4. **Create environment file**
```bash
copy .env.example .env
```

5. **Generate application key**
```bash
php artisan key:generate
```

6. **Update database configuration in .env**
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=web_anime
DB_USERNAME=root
DB_PASSWORD=
```

7. **Run migrations**
```bash
php artisan migrate
```

8. **Seed the database (optional - creates sample data)**
```bash
php artisan db:seed
```

9. **Build CSS and JavaScript**
```bash
npm run build
# or for development with hot reload
npm run dev
```

10. **Start the development server**
```bash
php artisan serve
```

11. **Create admin user**
```bash
php artisan tinker
# Then in Tinker:
App\Models\User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => Hash::make('password')])
```

12. **Access the application**
- Frontend: http://localhost:8000
- Admin Panel: http://localhost:8000/admin

## Routes

### Public Routes (No Authentication Required)
- `GET /` - Homepage
- `GET /search` - Search and filter anime
- `GET /anime/{slug}` - Anime detail page
- `GET /watch/{slug}` - Watch episode page

### Admin Routes (Filament Protected)
- `GET /admin` - Admin dashboard
- `GET /admin/animes` - Manage anime
- `GET /admin/genres` - Manage genres
- `GET /admin/episodes` - Manage episodes
- `GET /admin/video-servers` - Manage video servers

## Key Features Explained

### 1. Video Player with Server Switching
The `VideoPlayer` Livewire component allows users to switch between different video servers without page refresh:
- Located in `app/Livewire/VideoPlayer.php`
- Renders in `resources/views/livewire/video-player.blade.php`
- Supports iframe and direct URL embeds

### 2. Admin Panel Management
The Filament admin panel provides:
- **Anime Resource**: Full CRUD with image upload, genre selection, and featured toggle
- **Episode Resource**: Episode management with repeater field for video servers
- **Genre Resource**: Simple genre management
- **VideoServer Resource**: Direct video server management

### 3. Search and Filter
Homepage and search page feature:
- Full-text search in anime titles and synopsis
- Filter by genre, type (TV/Movie/ONA), and status (Ongoing/Completed)
- Pagination of results

### 4. Responsive Design
All pages are fully responsive using Tailwind CSS:
- Mobile-first approach
- Adaptive layouts for tablets and desktops
- Touch-friendly interface

## Configuration Files

### Tailwind Configuration
File: `tailwind.config.js`
- Configured for Blade templates
- Custom color schemes for anime theme
- Utility classes for consistency

### PostCSS Configuration
File: `postcss.config.js`
- Tailwind CSS processing
- Autoprefixer for browser compatibility

### Vite Configuration
File: `vite.config.js`
- Laravel Vite plugin setup
- CSS and JS entry points
- Hot module replacement

## Example Data Seeding

The DatabaseSeeder includes:
- 10 anime genres
- 5 sample anime (Attack on Titan, Death Note, My Hero Academia, Demon Slayer, One Piece)
- 3 episodes per anime
- 3 video servers per episode

Run `php artisan db:seed` to populate sample data.

## Customization

### Add More Video Servers
Edit in the admin panel:
1. Go to Admin → Episodes
2. Edit an episode
3. Click "Add Video Server"
4. Enter server name and embed URL
5. Save

### Manage Featured Anime
In the Anime admin resource, toggle the "Featured on Homepage" checkbox to show anime on the homepage.

### Upload Poster Images
When creating/editing anime, upload images in the "Description & Media" section. Images are stored in `storage/app/public/posters/`.

### Add New Genres
Use the Genre admin resource to add new genres. They'll automatically appear in search filters.

## Security Notes

- All public routes are accessible without authentication
- Admin panel (`/admin`) is protected by Filament's default authentication
- Video embedding should be from trusted sources only
- Always validate and sanitize user inputs
- Use environment variables for sensitive configuration

## Performance Tips

1. **Cache Popular Anime**: Use Redis to cache frequently accessed anime data
2. **Lazy Load Images**: Implement lazy loading for poster images
3. **Database Indexing**: Indices are created on foreign keys and slugs
4. **Pagination**: Search results are paginated (12 per page)

## Browser Compatibility

- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Troubleshooting

### Migrations failing?
```bash
php artisan migrate:fresh
php artisan migrate
```

### Admin panel not accessible?
Make sure you've created an admin user and installed FilamentPHP dependencies.

### CSS not loading?
```bash
npm install
npm run build
php artisan storage:link
```

### Video player not working?
Ensure embed URLs are valid and the browser allows iframe embedding.

## Future Enhancements

- [ ] User authentication and watchlist
- [ ] Comment system for episodes
- [ ] Rating system for anime
- [ ] Download episodes
- [ ] Subtitle management
- [ ] Advanced analytics
- [ ] Mobile app
- [ ] API for third-party integrations

## License

This project is open source and available under the MIT license.

## Support

For issues and questions, please open an issue in the project repository.

## Credits

Built with:
- [Laravel](https://laravel.com)
- [FilamentPHP](https://filamentphp.com)
- [Tailwind CSS](https://tailwindcss.com)
- [Livewire](https://livewire.laravel.com)


## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
