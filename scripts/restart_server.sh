#!/bin/bash
# Pindah ke folder project
cd /home/ubuntu/trello-laravel

# Ngusir Nginx/Apache bawaan biar Port 80 kosong (Jaga-jaga buat instance baru)
sudo systemctl stop nginx || true
sudo systemctl stop apache2 || true

# Matikan container lama (biar bener-bener clean)
sudo docker compose down

# Build dan jalankan ulang
sudo docker compose up -d --build --force-recreate

# KUNCI BIAR GAK ERROR AUTOLOAD:
# Tunggu kontainer stabil sebentar
sleep 5

# Install dependencies di dalam kontainer app (pake -T biar jalan di CI/CD)
sudo docker compose exec -T app composer install --no-dev --optimize-autoloader

# Setup izin storage & cache biar gak error 500
sudo docker compose exec -T app chmod -R 775 storage bootstrap/cache
sudo docker compose exec -T app chown -R www-data:www-data storage bootstrap/cache

# Generate key kalau belum ada (jaga-jaga instance baru)
sudo docker compose exec -T app php artisan key:generate --force
