# Laravel Backend API (Current State)

Backend API built with Laravel 12 for two modules currently present in codebase:
- Trello-style project/task management (Sanctum protected)
- Job board search + AI summary endpoints (public, throttled)

## Tech Stack

- PHP 8.2+
- Laravel 12
- MySQL 8
- Laravel Sanctum (token auth)
- Laravel Scout + Meilisearch (job search)
- Redis/Cache support
- Pest + PHPUnit for testing
- Docker / Docker Compose
- GitHub Actions CI/CD
- Terraform (AWS EC2 + Security Group)
- Kubernetes manifest (MySQL deployment/service)

## Implemented Features

### 1) Auth + Trello-style API (Protected)

- `POST /api/auth/register`
- `POST /api/auth/login`
- `GET /api/auth/me` (auth:sanctum)
- `POST /api/auth/logout` (auth:sanctum)
- Project CRUD under `/api/projects`
- Task CRUD under `/api/projects/{project}/tasks`
- Task statuses endpoint: `GET /api/statuses`
- Policy-based authorization for user-owned resources
- Form Request validation + unified API error responses

### 2) Jobs API (Public + Rate Limited)

All under `throttle:60,1`:
- `GET /api/jobs` (supports `search`, `location`, `per_page`)
- `POST /api/jobs`
- `POST /api/jobs/{id}/summarize`
- `GET /api/jobs/{id}/summary`

Details:
- Search uses Scout (`Job::search`) and Meilisearch driver in normal env.
- Summary generation uses queued `SummarizeJob` with retry/backoff.
- AI abstraction via `AIServiceInterface` bound to `GeminiService`.
- Summary is cached (`job_summary_{id}`, TTL 1 hour).

### 3) Security + Middleware

Global custom middleware:
- `X-Frame-Options: DENY`
- `X-Content-Type-Options: nosniff`
- `X-XSS-Protection: 1; mode=block`

Configured in `bootstrap/app.php`.

## Local Setup

1. Install dependencies

```bash
composer install
```

2. Copy env and configure

```bash
cp .env.example .env
```

Set at minimum:

```env
APP_NAME=TrelloLite
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=trello_test_aldo
DB_USERNAME=root
DB_PASSWORD=

SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=masterKey

GEMINI_API_KEY=
```

3. App key + migrate

```bash
php artisan key:generate
php artisan migrate
```

4. Run app

```bash
php artisan serve
```

## Docker Compose

Available services in `docker-compose.yml`:
- `app` (Laravel/PHP-FPM)
- `db` (MySQL 8)
- `meilisearch`

Run:

```bash
docker compose up -d --build
```

## Testing

Run all tests:

```bash
./vendor/bin/pest
```

Current test coverage includes:
- Feature: `JobSearchTest`, `SecurityTest`
- Unit: `JobServiceTest`

Test environment notes:
- `phpunit.xml` uses SQLite in-memory
- `SCOUT_DRIVER=collection` in tests (prevents Meilisearch dependency)

## API Docs

- OpenAPI file: `docs/swagger.yaml`
- Web route for docs UI page: `/docs`
- Swagger file route: `/docs/swagger.yaml`

## CI/CD

GitHub Actions workflow: `.github/workflows/deploy.yml`

- `test` job (on push/PR to `main`)
  - Uses MySQL service
  - Runs migration + test suite
- `deploy` job (push to `main` only)
  - Runs on self-hosted Linux runner
  - Pulls latest main
  - `composer install --no-dev`
  - Migrates + caches config/routes/views

## Infra Artifacts

- Terraform: `terraform/main.tf`
  - EC2 instance (`t2.micro`)
  - Security group allowing inbound 80/tcp
- Kubernetes: `k8s/mysql-deployment.yml`
  - MySQL Deployment + Service

## Project Structure (Key Parts)

- `app/Http/Controllers` (Auth, Project, Task, Job)
- `app/Http/Requests` (validation, including `StoreJobRequest`)
- `app/Services` (`JobService`, `GeminiService`, others)
- `app/Jobs/SummarizeJob.php`
- `app/Http/Middleware/SecurityHeaders.php`
- `routes/api.php`
- `routes/web.php`
- `tests/Feature`, `tests/Unit`
- `docs/swagger.yaml`
