# WasteFlow - Laravel + React + Inertia.js Application

A modern waste management application built with Laravel, React, Inertia.js, and Tailwind CSS.

## Tech Stack

- **Backend**: Laravel 12
- **Frontend**: React 18 with Inertia.js v2
- **Styling**: Tailwind CSS v3 with Flowbite components
- **Data Tables**: TanStack Table (headless, type-safe, powerful)
- **Authentication & Authorization**: Spatie Laravel Permission
- **Database**: MySQL (default; Laravel Sail). SQLite supported for local dev and testing.

## Features

- ✅ Modern Laravel 12 setup
- ✅ React 18 with Inertia.js for SPA-like experience
- ✅ Tailwind CSS v3 with custom WasteFlow color theme
- ✅ Flowbite UI components
- ✅ TanStack Table for powerful data tables with sorting, filtering, and pagination
- ✅ Spatie Laravel Permission for roles and permissions
- ✅ Pre-configured user roles: Admin, Manager, Operator, Client
- ✅ Client management with data table (server-driven data)
- ✅ Comprehensive testing setup with Pest (Laravel) and Jest (React)
- ✅ Laravel Sail for Docker development environment
- ✅ Custom domain configuration (wasteflow.test)
- ✅ Responsive design with WasteFlow branding

## Current Capabilities (Code-Confirmed)

- ✅ Company, branch, and site management (CRUD)
- ✅ Service provider management (CRUD)
- ✅ Materials, waste streams, grades, classifications, facilities (CRUD)
- ✅ Order creation with tracking numbers
- ✅ Order workflow enforcement and status history
- ✅ Weight capture and waste stream recording
- ✅ Supporting document upload for orders
- ✅ Order PDF export and consolidated order PDF export
- ✅ Rebate tracker report and PDF export
- ✅ Waste management report and PDF export
- ✅ Dashboard data aggregation and environmental impact calculations
- ✅ Roles and permissions management UI
- ✅ User management UI for staff

## Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd wasteflow
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Set up environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Configure your database in `.env` (e.g. MySQL for Sail, or SQLite for minimal local setup).

5. **Run database migrations and seeders**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. **Build frontend assets**
   ```bash
   npm run build
   ```

7. **Start the development server**
   - **With Sail (recommended):** `vendor/bin/sail up -d` then `vendor/bin/sail open`
   - **Without Sail:** `php artisan serve` (and in another terminal `npm run dev` for Vite)

## Default Users

The following users are created by the seeder:

- **Admin**: admin@wasteflow.example.com / password
- **Manager**: manager@wasteflow.example.com / password  
- **Operator**: operator@wasteflow.example.com / password

## Development

- **Full stack (Sail):** `vendor/bin/sail up -d` and `vendor/bin/sail npm run dev` (or `vendor/bin/sail composer run dev` if configured)
- **Frontend only:** `vendor/bin/sail npm run dev` or `npm run dev` (Vite dev server)
- **Backend only:** `vendor/bin/sail artisan serve` or `php artisan serve`

If frontend changes don’t appear, run `vendor/bin/sail npm run build` or `npm run build`.

## Testing

### Backend Tests (Pest)

Tests use MySQL and the `testing` database (see `phpunit.xml`). **With Sail:** create the database once, then run tests:

```bash
# Create testing database (one-time, with Sail). From the host:
#   vendor/bin/sail exec mysql mysql -u sail -p -e "CREATE DATABASE IF NOT EXISTS testing;"
# (enter your DB password when prompted), or run the same SQL after `sail mysql`.

# Run all tests (run via Sail so the app uses the same MySQL host as your app)
vendor/bin/sail artisan test

# Run a specific test file
vendor/bin/sail artisan test tests/Feature/ClientControllerTest.php

# Run with filter
vendor/bin/sail artisan test --filter=ClientController

# With coverage
vendor/bin/sail artisan test --coverage
```

Without Sail, use `php artisan test` or `./vendor/bin/pest` from the project root.

### Frontend Tests (Jest)

```bash
npm test

# Watch mode
npm run test:watch

# Coverage
npm run test:coverage
```

## Color Theme

The application uses a custom color palette inspired by the WasteFlow website:

- **Primary**: Green tones (#3b82f6) for environmental/sustainability theme
- **Secondary**: Gray tones for professional contrast
- **Accent**: Yellow tones for highlights and call-to-actions

## Project Structure

```
resources/
├── js/
│   ├── Components/     # Reusable React components
│   ├── Layouts/        # Layout components
│   ├── Pages/          # Inertia.js pages
│   └── app.jsx         # Main application entry point
├── css/
│   └── app.css         # Tailwind CSS with custom theme
└── views/
    └── app.blade.php   # Main HTML layout
```

## Next Steps

- [ ] Implement separate client portal experience
- [ ] Add CSV/Excel export for reports and data tables
- [ ] Add email notifications and templates
- [ ] Improve audit trail and login activity tracking

## License

This project is proprietary software for WasteFlow.
