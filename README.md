# Windows Explorer (Laravel)

Minimal setup to run locally.
## Requirements
- PHP 8.2+
- Composer
- A database (e.g., MySQL) configured in `.env`
## Setup
```powershell
composer install
copy .env.example .env
php artisan key:generate
```
Edit `.env` for your database, then run:
```powershell
php artisan migrate
php artisan db:seed
```
## Run
```powershell
php artisan serve
```
Open `http://127.0.0.1:8000`
