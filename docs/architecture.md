# System Architecture

## Overview
The School Management System is designed to be a scalable, modular, and multi-tenant (multi-school) application. It serves as both the internal management tool for school administrators, teachers, parents, and students, as well as the public-facing website for the school(s).

## Core Principles
- **SOLID Principles**: Adherence to SOLID principles for maintainability.
- **Clean Architecture**: Separation of concerns using dedicated layers (Controllers, Actions, Services, Repositories/Queries).
- **Fat Models, Skinny Controllers**: Business logic is abstracted away from controllers into Services, Actions, or specialized model methods.
- **Security-First**: Strict use of Form Requests for validation, Policies/Gates for authorization, and parameterized queries via Eloquent to prevent SQL injection.

## Directory Structure
The application structure strictly follows domain-driven modularity where applicable, utilizing specific directories under `app/`:

- `Actions/`: Single-responsibility classes that perform one specific task (e.g., `RegisterStudentAction`, `ProcessPaymentAction`).
- `Enums/`: PHP 8.1+ Enums representing fixed sets of values (e.g., `UserRole`, `PaymentStatus`).
- `Services/`: Classes containing complex business logic that might orchestrate multiple Actions or interact with external APIs.
- `Policies/`: Authorization classes defining user permissions.
- `Support/`: Helper classes, traits, and shared utilities.

## Future SaaS Support (Multi-Tenancy)
The system is built with a multi-tenant mindset. 
- A `school_id` will exist on most major tables.
- Global scopes will be applied to automatically filter queries by the authenticated user's active school.

## Authentication & Authorization
- **Authentication**: Standard Laravel Session/Sanctum based authentication.
- **Authorization**: Role-based access control (RBAC) managed via spatie/laravel-permission or custom Policies.

### Roles
1. **Super Admin**: System-wide control.
2. **School Admin**: School-specific control.
3. **Teacher**: Academic management.
4. **Student**: Learning dashboard.
5. **Parent**: Child tracking and fees.
6. **Staff**: Specific functional roles (e.g., Accountant, Librarian).

## REST API and Mobile App Support
Controllers will be designed to potentially serve both web views and JSON responses (using API resources), or a separate API route group will be maintained mirroring the core business logic from `Actions` and `Services`.
