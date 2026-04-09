<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 12">
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/TailwindCSS-4.x-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white" alt="Tailwind CSS 4">
  <img src="https://img.shields.io/badge/Vite-7.x-646CFF?style=for-the-badge&logo=vite&logoColor=white" alt="Vite">
  <img src="https://img.shields.io/badge/License-MIT-green?style=for-the-badge" alt="MIT License">
</p>

<h1 align="center">Mojawad — منصة التلاوات القرآنية</h1>

<p align="center">
  A modern web platform for browsing, streaming, and downloading Quran recitations (Tilawat) by renowned Qaris.
</p>

---

## Features

- Qari Profiles — Browse detailed pages for each Qari with bio and recitation list
- Tilawa Streaming — Stream Quran recitations directly in the browser
- Downloads — Download audio files with rate-limiting (30 requests/minute)
- Likes — Authenticated users can like their favourite Tilawat
- Library (Saved) — Save Tilawat to a personal library for quick access
- Live Search — AJAX-powered instant search across Qaris and Tilawat
- Role-Based Access — Admin and Creator roles via `spatie/laravel-permission`
- Admin Panel — Full CRUD for Qaris, Tilawat, and User role management
- Authentication — Full auth system (register, login, password reset)

---

## Tech Stack

| Layer       | Technology                          |
|-------------|-------------------------------------|
| Backend     | Laravel 12 (PHP 8.2+)               |
| Frontend    | Blade + Tailwind CSS 4 + Vite 7     |
| Database    | SQLite (default) / MySQL            |
| Auth        | Laravel Breeze / built-in Auth      |
| Roles       | `spatie/laravel-permission`         |
| Queue       | Database driver                     |
| Storage     | Local disk (audio & cover images)   |
| Testing     | Pest PHP 3                          |

---

## Getting Started

### Prerequisites

- PHP >= 8.2
- Composer
- Node.js >= 18 & npm
- SQLite (default) or MySQL/MariaDB

### Installation

1. Clone the repository

```bash
git clone https://github.com/HossamSoliuman/mojawad.git
cd mojawad
```

2. One-command setup

```bash
composer run setup
```

Or do it step-by-step:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

3. Link storage

```bash
php artisan storage:link
```

4. (Optional) Seed the database

```bash
php artisan db:seed
```

---

## Running Locally

Start all services concurrently:

```bash
composer run dev
```

Or run them separately:

```bash
php artisan serve
npm run dev
php artisan queue:listen --tries=1
```

The app will be available at http://localhost:8000.

---

## Environment Configuration

Copy `.env.example` to `.env` and update the relevant values:

```dotenv
APP_NAME=Mojawad
APP_URL=http://localhost

DB_CONNECTION=sqlite

MAIL_MAILER=log
```

---

## Database

| Model          | Table           | Description                                 |
|----------------|-----------------|---------------------------------------------|
| `User`         | `users`         | Registered users                            |
| `Qari`         | `qaris`         | Quran reciters with slug and profile image  |
| `Tilawa`       | `tilawat`       | Individual recitation audio entries         |
| `Like`         | `likes`         | User ↔ Tilawa likes (pivot)                 |
| `SavedTilawa`  | `saved_tilawat` | User personal library                       |
| `DownloadLog`  | `download_logs` | Tracks per-user download history            |

---

## Roles & Permissions

Roles are managed via `spatie/laravel-permission`:

| Role      | Capabilities                                               |
|-----------|------------------------------------------------------------|
| `admin`   | Full access — Qaris, Tilawat, and User management          |
| `creator` | Manage Qaris and Tilawat; no user role management          |
| (guest) | Browse, search, stream, and download (rate-limited)        |

Assign a role via Tinker:

```bash
php artisan tinker
>>> \App\Models\User::find(1)->assignRole('admin');
```

---

## Routes Overview

| Method | URI                          | Description                        |
|--------|------------------------------|------------------------------------|
| GET    | `/`                          | Home page                          |
| GET    | `/qaris`                     | All Qaris listing                  |
| GET    | `/qaris/{slug}`              | Qari profile page                  |
| GET    | `/tilawa/{slug}`             | Tilawa detail & audio player       |
| GET    | `/tilawa/{slug}/download`    | Download audio (throttled)         |
| GET    | `/library`                   | Saved Tilawat (auth required)      |
| GET    | `/profile`                   | User profile (auth required)       |
| GET    | `/api/search`                | Live search endpoint               |
| POST   | `/api/like/{tilawa}`         | Toggle like (auth required)        |
| POST   | `/api/save/{tilawa}`         | Toggle save (auth required)        |
| GET    | `/admin`                     | Admin dashboard                    |
| *      | `/admin/qaris/*`             | Qari CRUD (admin/creator)          |
| *      | `/admin/tilawat/*`           | Tilawa CRUD (admin/creator)        |
| *      | `/admin/users/*`             | User role management (admin only)  |

---

## Testing

```bash
composer run test
```

Tests are written with Pest PHP and located in the tests directory.

---

## Project Structure

```text
mojawad/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── Admin/
│   │       ├── Api/
│   │       └── ...
│   ├── Models/
│   └── Policies/
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/
├── public/
│   └── storage/
├── resources/
│   ├── views/
│   └── css/ js/
├── routes/
│   ├── web.php
│   └── auth.php
└── storage/
    └── app/public/
```

---

## Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Commit your changes: `git commit -m 'Add some feature'`
4. Push to the branch: `git push origin feature/your-feature`
5. Open a Pull Request

---

## License

This project is open-sourced under the [MIT License](https://opensource.org/licenses/MIT).
