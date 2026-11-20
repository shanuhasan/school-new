# School Management System

A multi-school (multi-tenant) School Management System built with **Laravel 12**. It covers academics, students, staff, attendance, timetables, homework, and examinations. Access is role-based, and each school's data is kept separate from every other school's.

---

## Tech Stack

| Layer          | Technology                                                   |
| -------------- | ------------------------------------------------------------ |
| Backend        | PHP 8.2+, Laravel 12                                         |
| Auth           | Laravel Breeze (session-based, email verification)           |
| Authorization  | spatie/laravel-permission (roles and permissions) + Policies |
| Images         | intervention/image                                           |
| Frontend       | Blade, AdminHMD admin theme, Tailwind CSS, Alpine.js, Vite   |
| Database       | SQLite by default; MySQL supported (e.g. via XAMPP)          |
| Testing        | PHPUnit 11                                                   |

---

## Features / Modules

| Module                    | What it does                                                                                       |
| ------------------------- | -------------------------------------------------------------------------------------------------- |
| **Authentication**        | Login, logout, password reset, email verification, remember-me, login throttling, inactive-user blocking, last-login tracking |
| **Roles & Permissions**   | 10 roles with granular permissions (e.g. `students.create`, `marks.enter`, `results.publish`); Super Admin bypass |
| **Multi-School Tenancy**  | `School`, `SchoolSetting`, school switcher, `EnsureTenantContext` middleware, `BelongsToSchool` trait, cross-school access prevention |
| **Academic Setup**        | Academic sessions, classes, sections, subjects, class–subject mapping                              |
| **Students & Guardians**  | Student profiles, guardians, class enrollments, student document upload/download                   |
| **Teachers & Staff**      | Departments, designations, teacher and staff profiles                                              |
| **Attendance**            | Mark attendance per class/section, plus daily, monthly, and per-student attendance reports         |
| **Timetable**             | Build class timetables; teachers see their own timetable                                           |
| **Homework**              | Assign homework with attachments; students submit; teachers evaluate                               |
| **Examination & Results** | Exam types, grade scales, exams with subjects, marks entry, result processing and publishing       |

### Roles

`Super Admin`, `School Admin`, `Principal`, `Teacher`, `Accountant`, `Librarian`, `Transport Manager`, `Staff`, `Student`, `Parent`

---

## Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Academic/        # Attendance, Timetable, Homework, Exams, Marks, Results
│   │   ├── Auth/            # Breeze authentication controllers
│   │   ├── Report/          # Attendance reports
│   │   ├── Staff/           # Teacher's own timetable
│   │   └── *.php            # Sessions, Classes, Sections, Subjects, Students, Guardians, Teachers, Staff…
│   └── Middleware/
│       └── EnsureTenantContext.php
├── Models/                  # 25 Eloquent models
│   └── Traits/BelongsToSchool.php
├── Policies/                # Authorization policy for each module
└── Services/TenantManager.php   # Holds the active school for the request
database/
├── migrations/
└── seeders/                 # Roles/permissions, demo users, dummy data
resources/views/             # Blade views (academic, students, staff, guardians, reports…)
routes/web.php               # All module routes (grouped by academic / staff / reports)
tests/Feature/               # Feature tests for each module + tenant isolation
docs/
├── architecture.md          # Architecture and design principles
└── project_phases.md        # Phase-by-phase development log
```

---

## Installation

### Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js & npm
- (Optional) MySQL via XAMPP

### Steps

```bash
# 1. Clone and enter the project
git clone <repository-url>
cd school-management-system

# 2. Install dependencies
composer install
npm install

# 3. Environment
cp .env.example .env
php artisan key:generate
```

**4. Configure the database** in `.env`.

SQLite (default):
```env
DB_CONNECTION=sqlite
```

MySQL (XAMPP):
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school_management
DB_USERNAME=root
DB_PASSWORD=
```

```bash
# 5. Migrate and seed roles, permissions, and demo users
php artisan migrate --seed

# (Optional) Load extra dummy data
php artisan db:seed --class=DummyDataSeeder

# 6. Link storage for uploaded files (documents, homework attachments)
php artisan storage:link

# 7. Build assets and run
npm run build
php artisan serve
```

Open http://127.0.0.1:8000

> **Shortcut:** `composer setup` runs install, env, key, migrate and the asset build in one step. You still need to seed and run `storage:link` yourself.

---

## Demo Login Accounts

`DummyUserSeeder` creates one user per role. Every demo user has the password **`password`** and is attached to *Demo International School* (`DEMO-01`).

| Role              | Email                          |
| ----------------- | ------------------------------ |
| Super Admin       | `superadmin@example.com`       |
| School Admin      | `schooladmin@example.com`      |
| Principal         | `principal@example.com`        |
| Teacher           | `teacher@example.com`          |
| Accountant        | `accountant@example.com`       |
| Librarian         | `librarian@example.com`        |
| Transport Manager | `transportmanager@example.com` |
| Staff             | `staff@example.com`            |
| Student           | `student@example.com`          |
| Parent            | `parent@example.com`           |

> ⚠️ These accounts are for local development only. Do not seed them in production.

---

## Development Commands

| Command                     | Purpose                                                       |
| --------------------------- | ------------------------------------------------------------- |
| `composer dev`              | Runs the server, queue worker, log viewer (Pail), and Vite together |
| `npm run dev`               | Vite dev server with hot reload                               |
| `npm run build`             | Production asset build                                        |
| `php artisan test`          | Run the full test suite                                       |
| `composer test`             | Clear config and run tests                                    |
| `./vendor/bin/pint`         | Format code (PSR-12)                                          |
| `php artisan optimize:clear`| Clear all caches                                              |
| `php artisan migrate:fresh --seed` | Rebuild the database with demo data                    |

---

## Testing

Each module has its own feature test in `tests/Feature/`:

- `AuthorizationTest`: role and permission checks
- `TenantIsolationTest`: School A cannot reach School B's data
- `AcademicManagementTest`, `StudentManagementTest`, `TeacherManagementTest`, `StaffManagementTest`
- `AttendanceManagementTest`, `TimetableManagementTest`, `HomeworkManagementTest`, `ExaminationManagementTest`
- `Auth/*`: login, registration, password reset, email verification

```bash
php artisan test
```

---

## Architecture Principles

- **Skinny controllers.** Validation lives in Form Requests, authorization in Policies, and complex logic in Services and Actions.
- **Server-side authorization.** Every sensitive action is checked with a policy or permission. Hiding a button in the UI is not treated as protection.
- **Tenant isolation.** School-owned tables carry `school_id` with foreign keys and indexes, and queries are scoped to the active school.
- **UI consistency.** Every view follows the AdminHMD theme structure (panels, panel headers, page headings).

See [docs/architecture.md](docs/architecture.md) for more detail and [docs/project_phases.md](docs/project_phases.md) for the development history.

---

## Roadmap

| Phase | Module                       | Status |
| ----- | ---------------------------- | ------ |
| 0     | Foundation                   | ✅ Done |
| 1     | Authentication               | ✅ Done |
| 2     | Roles & Permissions          | ✅ Done |
| 3     | Multi-School Tenancy         | ✅ Done |
| 4     | Academic Management          | ✅ Done |
| 5     | Students & Guardians         | ✅ Done |
| 6     | Teachers & Staff             | ✅ Done |
| 7     | Attendance                   | ✅ Done |
| 8     | Timetable                    | ✅ Done |
| 9     | Homework                     | ✅ Done |
| 10    | Examination & Results        | ✅ Done |
| —     | Fees & Online Payments       | 🔜 Planned |
| —     | Library, Transport           | 🔜 Planned |
| —     | Notifications, Public Website, REST API / Mobile App, SaaS Subscriptions | 🔜 Planned |

---

## License

MIT
