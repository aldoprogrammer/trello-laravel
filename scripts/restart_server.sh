#!/bin/bash
cd /home/ubuntu/trello-laravel

# Matikan sisa nginx host
sudo systemctl stop nginx || true

# Ambil ENV dari SSM
echo "Pulling environment from AWS SSM..."
/usr/local/bin/aws ssm get-parameter --name "/trello/prod/env_file" --with-decryption --query "Parameter.Value" --output text --region us-east-1 > .env

# Ownership fix agar Docker lancar baca file
sudo chown ubuntu:ubuntu .env
sudo chmod 600 .env

# Docker restart
sudo docker compose down
sudo docker compose up -d --build --force-recreate

# Warming up MySQL
sleep 20

# Laravel Setup
sudo docker compose exec -T app composer install --no-dev --optimize-autoloader
sudo docker compose exec -T app php artisan key:generate --force
sudo docker compose exec -T app php artisan config:clear
sudo docker compose exec -T app php artisan config:cache

# Permissions
sudo docker compose exec -T app chmod -R 775 storage bootstrap/cache
sudo docker compose exec -T app chown -R www-data:www-data storage bootstrap/cache

# Migration
sudo docker compose exec -T app php artisan migrate --force
