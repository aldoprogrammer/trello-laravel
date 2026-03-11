#!/bin/bash
cd /home/ubuntu/trello-laravel

# 1. Ngusir pengganggu Port 80
sudo systemctl stop nginx || true
sudo systemctl stop apache2 || true

# 2. Formalitas .env (biar artisan gak mogok)
if [ ! -f .env ]; then
    touch .env
fi

# 3. Rebuild total
sudo docker compose down
sudo docker compose up -d --build --force-recreate

# 4. Tunggu mesin panas
sleep 5

# 5. Pasang urat saraf (Autoload & Vendor)
sudo docker compose exec -T app composer install --no-dev --optimize-autoloader

# 6. Kasih nyawa (APP_KEY & Cache)
sudo docker compose exec -T app php artisan key:generate --force
sudo docker compose exec -T app php artisan config:cache

# 7. Izin akses (Biar gak Error 500 pas nulis log/session)
sudo docker compose exec -T app chmod -R 775 storage bootstrap/cache
sudo docker compose exec -T app chown -R www-data:www-data storage bootstrap/cache

# 8. Migrasi (Biar DB Master-Slave lo sinkron skemanya)
sudo docker compose exec -T app php artisan migrate --force
