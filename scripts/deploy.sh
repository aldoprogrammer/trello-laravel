#!/bin/bash
cd /home/ubuntu/trello-laravel

# 1. Tarik config .env dari SSM (Dinamis & Aman)
# Pastikan di SSM isinya MEILISEARCH_HOST=http://laravel-meili:7700
aws ssm get-parameter --name "/trello/prod/env_file" --with-decryption --query "Parameter.Value" --output text > .env

# 2. Build & Up Container
sudo docker-compose up -d --build

# 3. Jalankan command Laravel di dalam container (app adalah nama service di docker-compose)
sudo docker-compose exec -T app composer install --no-dev --optimize-autoloader
sudo docker-compose exec -T app php artisan migrate --force
sudo docker-compose exec -T app php artisan config:cache
sudo docker-compose exec -T app php artisan route:cache

# 4. Restart Worker biar antrean job pake kodingan baru
sudo docker-compose restart worker
