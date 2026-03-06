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

## Dummy Endpoint Examples (All APIs)

Base URL:

```bash
http://127.0.0.1:8000
```

Set token after login/register:

```bash
TOKEN="paste_your_bearer_token_here"
```

### Auth

`POST /api/auth/register`

```bash
curl -X POST http://127.0.0.1:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name":"Aldo",
    "email":"aldo@example.com",
    "password":"Password123!",
    "password_confirmation":"Password123!"
  }'
```

`POST /api/auth/login`

```bash
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email":"aldo@example.com",
    "password":"Password123!"
  }'
```

`GET /api/auth/me`

```bash
curl http://127.0.0.1:8000/api/auth/me \
  -H "Authorization: Bearer $TOKEN"
```

`POST /api/auth/logout`

```bash
curl -X POST http://127.0.0.1:8000/api/auth/logout \
  -H "Authorization: Bearer $TOKEN"
```

### Projects

`GET /api/projects`

```bash
curl http://127.0.0.1:8000/api/projects \
  -H "Authorization: Bearer $TOKEN"
```

`POST /api/projects`

```bash
curl -X POST http://127.0.0.1:8000/api/projects \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name":"Sprint Board",
    "description":"Tasks for sprint 1"
  }'
```

`GET /api/projects/{project}`

```bash
curl http://127.0.0.1:8000/api/projects/1 \
  -H "Authorization: Bearer $TOKEN"
```

`PUT /api/projects/{project}`

```bash
curl -X PUT http://127.0.0.1:8000/api/projects/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name":"Sprint Board v2",
    "description":"Updated sprint board"
  }'
```

`DELETE /api/projects/{project}`

```bash
curl -X DELETE http://127.0.0.1:8000/api/projects/1 \
  -H "Authorization: Bearer $TOKEN"
```

### Tasks

`GET /api/statuses`

```bash
curl http://127.0.0.1:8000/api/statuses \
  -H "Authorization: Bearer $TOKEN"
```

`GET /api/projects/{project}/tasks`

```bash
curl http://127.0.0.1:8000/api/projects/1/tasks \
  -H "Authorization: Bearer $TOKEN"
```

`POST /api/projects/{project}/tasks`

```bash
curl -X POST http://127.0.0.1:8000/api/projects/1/tasks \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title":"Build login page",
    "description":"Implement login UI + validation",
    "status":"pending",
    "due_date":"2026-03-10"
  }'
```

`GET /api/projects/{project}/tasks/{task}`

```bash
curl http://127.0.0.1:8000/api/projects/1/tasks/1 \
  -H "Authorization: Bearer $TOKEN"
```

`PUT /api/projects/{project}/tasks/{task}`

```bash
curl -X PUT http://127.0.0.1:8000/api/projects/1/tasks/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title":"Build login page",
    "description":"Done backend integration",
    "status":"in_progress"
  }'
```

`DELETE /api/projects/{project}/tasks/{task}`

```bash
curl -X DELETE http://127.0.0.1:8000/api/projects/1/tasks/1 \
  -H "Authorization: Bearer $TOKEN"
```

### Jobs (Public, Throttled)

`GET /api/jobs`

```bash
curl "http://127.0.0.1:8000/api/jobs?search=laravel&location=remote&per_page=10"
```

`POST /api/jobs`

```bash
curl -X POST http://127.0.0.1:8000/api/jobs \
  -H "Content-Type: application/json" \
  -d '{
    "title":"Laravel Developer",
    "location":"Remote",
    "description":"Build and maintain Laravel APIs",
    "company_id":1
  }'
```

`POST /api/jobs/{id}/summarize`

```bash
curl -X POST http://127.0.0.1:8000/api/jobs/1/summarize
```

`GET /api/jobs/{id}/summary`

```bash
curl http://127.0.0.1:8000/api/jobs/1/summary
```

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
- `mysql-slave` (MySQL 8 read replica target, manual sync)
- `meilisearch`
- `redis`

Run:

```bash
docker compose up -d --build
```

## MySQL Master/Slave (Read/Write Split)

This project uses Laravel read/write splitting on the `mysql` connection:
- write host: `DB_HOST=db` (master)
- read host: `DB_SLAVE_HOST=mysql-slave` (slave)
- `sticky=true` is enabled

Important:
- `DB::connection()->getConfig('host')` is config fallback only.
- To verify real active server, query `@@hostname` from write/read PDO.

### Persistent MySQL Data on Windows (D: drive)

Compose mounts:
- `D:/docker-data/laravel-be/mysql-master:/var/lib/mysql`
- `D:/docker-data/laravel-be/mysql-slave:/var/lib/mysql`

Create folders once:

```powershell
mkdir D:\docker-data\laravel-be\mysql-master
mkdir D:\docker-data\laravel-be\mysql-slave
```

### Manual Verification (Real Host Check)

1. Check container hostnames in PowerShell:

```powershell
docker inspect -f "{{.Config.Hostname}}" laravel-db
docker inspect -f "{{.Config.Hostname}}" laravel-db-slave
```

2. Open tinker inside Docker app container:

```powershell
docker-compose exec app php artisan tinker
```

3. In Tinker, check write and read hosts:

```php
DB::connection('mysql')->getPdo()->query("select @@hostname")->fetchColumn();      // write PDO (master)
DB::connection('mysql')->getReadPdo()->query("select @@hostname")->fetchColumn();  // read PDO (slave)
```

If the returned hostname matches:
- `laravel-db` hostname => master
- `laravel-db-slave` hostname => slave

### Manual Master -> Slave Sync (No Binlog Replication)

Since replication is not configured, run manual sync after schema/data changes:

```powershell
.\scripts\sync-master-to-slave.ps1
```

Typical flow:
1. `docker-compose exec app php artisan migrate` (master only)
2. `.\scripts\sync-master-to-slave.ps1`

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
