#!/bin/bash
set -euo pipefail
cd /home/ubuntu/trello-laravel

# docker compose v2 (plugin) or legacy docker-compose v1
if docker compose version >/dev/null 2>&1; then
  DC=(sudo docker compose)
else
  DC=(sudo docker-compose)
fi

# Stop host nginx (port 80 is used by nginx-gateway container)
sudo systemctl stop nginx 2>/dev/null || true

DEPLOY_ENV="dev"
if [ -f /home/ubuntu/.deploy_env ]; then
  DEPLOY_ENV=$(cat /home/ubuntu/.deploy_env | tr -d '[:space:]')
fi

echo "Pulling environment from AWS SSM (env=${DEPLOY_ENV})..."
AWS_REGION="${AWS_REGION:-ap-southeast-1}"
aws ssm get-parameter --name "/trello/${DEPLOY_ENV}/env_file" --with-decryption --query "Parameter.Value" --output text --region "$AWS_REGION" > .env

sudo chown ubuntu:ubuntu .env
sudo chmod 600 .env

echo "Starting Docker stack..."
"${DC[@]}" down
"${DC[@]}" up -d --build --force-recreate

# Wait for DB/Redis (tune sleep if using RDS — often faster than a long fixed sleep)
sleep 15

"${DC[@]}" exec -T app composer install --no-dev --optimize-autoloader --no-interaction
# APP_KEY must come from SSM .env; do not regenerate every deploy (invalidates sessions/tokens).
if ! grep -qE '^APP_KEY=base64:.+' .env 2>/dev/null; then
  echo "WARNING: APP_KEY missing in .env from SSM — generating once."
  "${DC[@]}" exec -T app php artisan key:generate --force
fi

"${DC[@]}" exec -T app php artisan config:clear
"${DC[@]}" exec -T app php artisan config:cache
"${DC[@]}" exec -T app php artisan route:cache 2>/dev/null || true
"${DC[@]}" exec -T app php artisan view:cache 2>/dev/null || true

"${DC[@]}" exec -T app chmod -R 775 storage bootstrap/cache
"${DC[@]}" exec -T app chown -R www-data:www-data storage bootstrap/cache

# Auto-create database if it doesn't exist (ensures dev/prod DB separation from scratch)
DB_NAME=$(grep '^DB_DATABASE=' .env | head -1 | cut -d= -f2 | tr -d '\r')
DB_HOST_VAL=$(grep '^DB_HOST=' .env | head -1 | cut -d= -f2 | tr -d '\r')
DB_PORT_VAL=$(grep '^DB_PORT=' .env | head -1 | cut -d= -f2 | tr -d '\r')
DB_USER_VAL=$(grep '^DB_USERNAME=' .env | head -1 | cut -d= -f2 | tr -d '\r')
DB_PASS_VAL=$(grep '^DB_PASSWORD=' .env | head -1 | cut -d= -f2 | tr -d '\r')

if [ -n "$DB_NAME" ] && [ -n "$DB_HOST_VAL" ]; then
  echo "Ensuring database '${DB_NAME}' exists on ${DB_HOST_VAL}..."
  "${DC[@]}" exec -T app php -r "
    try {
      \$pdo = new PDO('mysql:host=${DB_HOST_VAL};port=${DB_PORT_VAL:-3306}', '${DB_USER_VAL}', '${DB_PASS_VAL}');
      \$pdo->exec('CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\`');
      echo \"Database '${DB_NAME}' ready.\n\";
    } catch (\Exception \$e) {
      echo \"DB create warning: \" . \$e->getMessage() . \"\n\";
    }
  "
fi

"${DC[@]}" exec -T app php artisan migrate --force

echo "Restart worker to pick up new code..."
"${DC[@]}" restart worker 2>/dev/null || true

echo "Deploy hook finished."
