# Trello Lite Backend (Laravel)

Backend legacy service untuk Trello Lite dengan Laravel + Sanctum, fokus pada autentikasi token dan CRUD Projects/Tasks milik user.

## Stack

-   PHP 8.2+
-   Laravel 12.x
-   MySQL
-   Laravel Sanctum untuk token auth

## Fitur

-   Register, login, logout, me (Sanctum bearer token)
-   Authorization per-user (policies Project & Task)
-   CRUD Projects
-   CRUD Tasks (title, description, status: pending|in_progress|done, optional due_date)
-   Strong validation (Form Requests)
-   Konsisten JSON response (success, message, data/errors)
-   Swagger spec di `docs/swagger.yaml`

## Persiapan

1. Salin env:

```
cp .env.example .env
```

2. Sesuaikan kredensial MySQL di `.env`:

```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=trello_lite
DB_USERNAME=trello_user
DB_PASSWORD=trello_password
```

3. Install dependencies:

```
composer install
```

4. Generate key:

```
php artisan key:generate
```

5. Migrasi database:

```
php artisan migrate
```

## Menjalankan

```
php artisan serve
```

API base URL: `http://localhost:8000`

Auth flow:

-   `POST /api/auth/register` -> ambil `token`
-   `POST /api/auth/login` -> ambil `token`
-   Sertakan header: `Authorization: Bearer {token}`

Endpoints utama:

-   Projects: `GET/POST /api/projects`, `GET/PUT/DELETE /api/projects/{project}`
-   Tasks: `GET/POST /api/projects/{project}/tasks`, `GET/PUT/DELETE /api/projects/{project}/tasks/{task}`

## Dokumentasi API

File Swagger: `docs/swagger.yaml`
Import ke Swagger UI/Postman untuk eksplorasi skema request/response dan error (401/403/404/422).

## Dummy Payloads (contoh)

-   Register: `POST /api/auth/register`

```json
{
    "name": "Aldo Lata Soba",
    "email": "aldo@example.com",
    "password": "@Aldo1234",
    "password_confirmation": "@Aldo1234"
}
```

-   Login: `POST /api/auth/login`

```json
{
    "email": "aldo@example.com",
    "password": "@Aldo1234"
}
```

-   Create Project: `POST /api/projects`

```json
{
    "name": "Sprint Board",
    "description": "Tasks for sprint 1"
}
```

-   Update Project: `PUT /api/projects/{project}`

```json
{
    "name": "Sprint Board v2",
    "description": "Updated description"
}
```

-   Create Task: `POST /api/projects/{project}/tasks`

```json
{
    "title": "Write landing copy",
    "description": "Hero, value props, CTA",
    "status": "pending",
    "due_date": "2025-12-31"
}
```

-   Update Task: `PUT /api/projects/{project}/tasks/{task}`

```json
{
    "title": "Write landing copy v2",
    "description": "Refine hero text",
    "status": "in_progress",
    "due_date": "2026-01-05"
}
```
-   List Task Statuses: `GET /api/statuses`
    -   Headers: `Authorization: Bearer {token}`

```json
{
    "success": true,
    "message": "Task statuses",
    "data": [
        { "value": "pending", "label": "Pending" },
        { "value": "in_progress", "label": "In progress" },
        { "value": "done", "label": "Done" }
    ]
}
```

-   Authorization header (semua endpoint terproteksi):

```
Authorization: Bearer {token}
```

## Testing

-   Jalankan seluruh test:

```
php artisan test
```

## Struktur Singkat

-   `app/Models` (User, Project, Task)
-   `app/Http/Controllers` (Auth, Project, Task)
-   `app/Http/Requests` (AuthRegister/Login, ProjectStore/Update, TaskStore/Update)
-   `app/Services` (AuthService, ProjectService, TaskService)
-   `app/Policies` (ProjectPolicy, TaskPolicy)
-   `routes/api.php`
-   `docs/swagger.yaml`
