# School Management System

A scalable, maintainable, secure, and production-ready School Management System and Public School Website built with Laravel.

## Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js & NPM

## Installation

1. Clone the repository and navigate into the project directory:
   ```bash
   git clone <repository-url>
   cd school-management-system
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Install frontend dependencies:
   ```bash
   npm install
   ```

4. Configure your environment:
   ```bash
   cp .env.example .env
   ```
   *Update the `.env` file with your database credentials and other settings.*

5. Generate the application key:
   ```bash
   php artisan key:generate
   ```

6. Run database migrations:
   ```bash
   php artisan migrate
   ```

7. Build frontend assets:
   ```bash
   npm run build
   ```

8. Start the development server:
   ```bash
   php artisan serve
   ```

## Development Commands
- `npm run dev`: Start Vite development server
- `php artisan test`: Run the test suite
- `composer validate`: Validate composer.json
- `php artisan optimize:clear`: Clear all cached files

## Architecture
See [docs/architecture.md](docs/architecture.md) for detailed architecture documentation.
