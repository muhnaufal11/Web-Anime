# 🚀 Panduan Deploy ke CasaOS

## Metode 1: Docker Compose (Direkomendasikan)

### Step 1: Upload File ke CasaOS

1. Compress folder project (tanpa `vendor` dan `node_modules`):
   ```bash
   # Di Windows, buat ZIP tanpa folder besar:
   # - vendor/
   # - node_modules/
   # - .git/
   ```

2. Upload ke CasaOS via:
   - File Manager CasaOS
   - SFTP/SCP
   - Samba Share

### Step 2: Buat docker-compose.yml

Buat file `docker-compose.yml` di folder project:

```yaml
version: '3.8'

services:
  app:
    image: webdevops/php-nginx:8.2-alpine
    container_name: nipnime-app
    restart: unless-stopped
    working_dir: /app
    volumes:
      - ./:/app
      - ./storage:/app/storage
      - ./public:/app/public
    ports:
      - "8080:80"
    environment:
      - WEB_DOCUMENT_ROOT=/app/public
      - PHP_MEMORY_LIMIT=256M
      - PHP_UPLOAD_MAX_FILESIZE=64M
      - PHP_POST_MAX_SIZE=64M
    depends_on:
      - mysql
    networks:
      - nipnime-network

  mysql:
    image: mysql:8.0
    container_name: nipnime-mysql
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: nipnime_secret_password
      MYSQL_DATABASE: web_anime
      MYSQL_USER: nipnime
      MYSQL_PASSWORD: nipnime_password
    volumes:
      - mysql_data:/var/lib/mysql
      - ./anime_db_backup.sql:/docker-entrypoint-initdb.d/init.sql
    ports:
      - "3307:3306"
    networks:
      - nipnime-network

  phpmyadmin:
    image: phpmyadmin:latest
    container_name: nipnime-phpmyadmin
    restart: unless-stopped
    environment:
      PMA_HOST: mysql
      PMA_PORT: 3306
      UPLOAD_LIMIT: 100M
    ports:
      - "8081:80"
    depends_on:
      - mysql
    networks:
      - nipnime-network

networks:
  nipnime-network:
    driver: bridge

volumes:
  mysql_data:
```

### Step 3: Setup di CasaOS

1. **SSH ke CasaOS server:**
   ```bash
   ssh user@YOUR_CASAOS_IP
   ```

2. **Pindah ke folder project:**
   ```bash
   cd /path/to/nipnime
   ```

3. **Install dependencies:**
   ```bash
   docker run --rm -v $(pwd):/app composer:latest install --no-dev --optimize-autoloader
   ```

4. **Setup .env:**
   ```bash
   cp .env.production .env
   nano .env
   ```
   
   Edit nilai berikut:
   ```
   APP_URL=http://YOUR_CASAOS_IP:8080
   DB_HOST=mysql
   DB_DATABASE=web_anime
   DB_USERNAME=nipnime
   DB_PASSWORD=nipnime_password
   ```

5. **Jalankan Docker Compose:**
   ```bash
   docker-compose up -d
   ```

6. **Setup Laravel:**
   ```bash
   # Masuk ke container
   docker exec -it nipnime-app bash
   
   # Di dalam container:
   php artisan key:generate
   php artisan storage:link
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   
   # Set permissions
   chown -R application:application storage bootstrap/cache
   chmod -R 775 storage bootstrap/cache
   ```

---

## Metode 2: Install Langsung di CasaOS (Tanpa Docker)

### Prasyarat di CasaOS:
- PHP 8.1+
- MySQL/MariaDB
- Nginx atau Apache
- Composer

### Step 1: Install Dependencies

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP & extensions
sudo apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath

# Install MySQL
sudo apt install -y mysql-server

# Install Nginx
sudo apt install -y nginx

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Step 2: Setup Database

```bash
# Login ke MySQL
sudo mysql

# Di MySQL prompt:
CREATE DATABASE web_anime;
CREATE USER 'nipnime'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON web_anime.* TO 'nipnime'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import database
mysql -u nipnime -p web_anime < anime_db_backup.sql
```

### Step 3: Upload & Setup Project

```bash
# Buat folder
sudo mkdir -p /var/www/nipnime
cd /var/www/nipnime

# Upload/copy files ke folder ini

# Install composer dependencies
composer install --no-dev --optimize-autoloader

# Setup .env
cp .env.production .env
nano .env
# Edit sesuai konfigurasi server

# Generate key & setup
php artisan key:generate
php artisan storage:link
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
sudo chown -R www-data:www-data /var/www/nipnime
sudo chmod -R 755 /var/www/nipnime
sudo chmod -R 775 /var/www/nipnime/storage
sudo chmod -R 775 /var/www/nipnime/bootstrap/cache
```

### Step 4: Konfigurasi Nginx

Buat file: `/etc/nginx/sites-available/nipnime`

```nginx
server {
    listen 80;
    server_name YOUR_CASAOS_IP;
    root /var/www/nipnime/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Increase upload size
    client_max_body_size 64M;
}
```

Aktifkan site:
```bash
sudo ln -s /etc/nginx/sites-available/nipnime /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

---

## 📁 File yang Perlu Di-Upload

### WAJIB:
- ✅ Semua folder dan file KECUALI yang di bawah
- ✅ `anime_db_backup.sql` (database export)
- ✅ `.env.production` (rename jadi `.env` di server)

### JANGAN Upload (bisa di-generate di server):
- ❌ `vendor/` (install ulang dengan composer)
- ❌ `node_modules/` (install ulang dengan npm jika perlu)
- ❌ `.git/` (tidak perlu di production)

---

## 🔧 Troubleshooting

### Error 500:
```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Fix permissions
sudo chmod -R 775 storage bootstrap/cache
```

### Database Connection Error:
```bash
# Test koneksi
php artisan tinker
>>> DB::connection()->getPdo();
```

### Storage/Images Tidak Muncul:
```bash
php artisan storage:link
```

### Permission Denied:
```bash
sudo chown -R www-data:www-data /var/www/nipnime
```

---

## 🌐 Akses Website

Setelah setup selesai:
- **Website:** `http://YOUR_CASAOS_IP:8080` (Docker) atau `http://YOUR_CASAOS_IP` (Native)
- **Admin Panel:** `http://YOUR_CASAOS_IP:8080/admin`
- **phpMyAdmin:** `http://YOUR_CASAOS_IP:8081` (Docker only)

---

## 📝 Checklist Deploy

- [ ] Upload project files
- [ ] Import database (`anime_db_backup.sql`)
- [ ] Setup `.env` dengan kredensial yang benar
- [ ] Install composer dependencies
- [ ] `php artisan key:generate`
- [ ] `php artisan storage:link`
- [ ] `php artisan migrate --force`
- [ ] Set permissions (storage, bootstrap/cache)
- [ ] Cache config, routes, views
- [ ] Test website bisa diakses
- [ ] Test admin panel login
- [ ] Test upload gambar
