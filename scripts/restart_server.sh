#!/bin/bash
cd /home/ubuntu/trello-laravel

# 1. Matikan sisa-sisa pengganggu
sudo systemctl stop nginx || true

# 2. AMBIL ENV DARI AWS SSM (DINAMIS!)
# Kita tarik value dari Parameter Store dan simpan jadi file .env
echo "Fetching secrets from AWS Parameter Store..."
aws ssm get-parameter --name "/trello/prod/env_file" --with-decryption --query "Parameter.Value" --output text > .env

# 3. KUNCI DOCKER: Paksa variabel Host agar sesuai dengan nama container
# Karena di .env AWS lo DB_HOST-nya mungkin masih RDS, kita pastiin
# Laravel tahu kalau Redis & Meilisearch ada di jaringan Docker
sed -i 's/REDIS_HOST=.*/REDIS_HOST=laravel-redis/' .env
sed -i 's/MEILISEARCH_HOST=.*/MEILISEARCH_HOST=http:\/\/laravel-meili:7700/' .env

# 4. Restart Docker
sudo docker compose down
sudo docker compose up -d --build --force-recreate

# 5. Tunggu Container Ready
sleep 15

# 6. Post-Deployment Hooks
sudo docker compose exec -T app composer install --no-dev --optimize-autoloader
sudo docker compose exec -T app php artisan key:generate --force
sudo docker compose exec -T app php artisan config:clear
sudo docker compose exec -T app php artisan config:cache

# 7. Permission & Migration
sudo docker compose exec -T app chmod -R 775 storage bootstrap/cache
sudo docker compose exec -T app chown -R www-data:www-data storage bootstrap/cache
sudo docker compose exec -T app php artisan migrate --force
