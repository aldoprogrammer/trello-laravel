#!/bin/bash
cd /home/ubuntu/trello-laravel

# 1. Pastikan Nginx host mati
sudo systemctl stop nginx || true

# 2. Setup .env dari example (Ini wajib biar struktur DB_HOST=db ke-load)
if [ -f .env.example ]; then
    cp .env.example .env
else
    touch .env
fi

# 3. Restart Docker
sudo docker compose down
sudo docker compose up -d --build --force-recreate

# 4. Tunggu MySQL booting (MySQL 8.0 agak lama startup-nya)
sleep 15

# 5. Jalankan perintah di dalam container
sudo docker compose exec -T app composer install --no-dev --optimize-autoloader
sudo docker compose exec -T app php artisan key:generate --force

# 6. Clear cache biar Laravel baca DB_HOST=db dari .env yang baru di-copy
sudo docker compose exec -T app php artisan config:clear
sudo docker compose exec -T app php artisan config:cache

# 7. Permission & Migration
sudo docker compose exec -T app chmod -R 775 storage bootstrap/cache
sudo docker compose exec -T app chown -R www-data:www-data storage bootstrap/cache
sudo docker compose exec -T app php artisan migrate --force
