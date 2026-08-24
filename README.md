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

## Prerequisites

- **Git**
- **Docker** — [Docker Desktop](https://www.docker.com/products/docker-desktop/) on macOS/Windows, or Docker Engine + the Compose plugin on Linux
- **Make**

<details>
<summary><strong>macOS</strong></summary>

- Install Docker Desktop and make sure it's running before you continue.
- `make` ships with the Xcode Command Line Tools — if you don't have it: `xcode-select --install`.

</details>

<details>
<summary><strong>Linux</strong></summary>

- Install Docker Engine and the Compose plugin for your distro ([docs.docker.com/engine/install](https://docs.docker.com/engine/install/)).
- Add your user to the `docker` group so you don't need `sudo` for every command, then log out and back in: `sudo usermod -aG docker $USER`.
- `make` is usually preinstalled; if not, `sudo apt install make` (Debian/Ubuntu) or your distro's equivalent.

</details>

<details>
<summary><strong>Windows</strong></summary>

- Install Docker Desktop with the **WSL2 backend** enabled.
- Install a WSL2 distro if you don't already have one: `wsl --install` (Ubuntu is the default).
- Clone the repo and run every command below **inside the WSL2 terminal**, not PowerShell/cmd — Docker Desktop's WSL2 integration handles the rest, and `make` is available by default in the Ubuntu WSL image (or `sudo apt install make` if not).

</details>

## Quick Start

Once the prerequisites above are installed:

```bash
git clone <repository-url>
cd wasteflow
make init
```

`make init` will:
1. Copy `.env.example` → `.env` (if it doesn't already exist) and install PHP dependencies via a throwaway container — no local PHP/Composer needed
2. Ask a few questions: app name, local port, and whether to seed extra demo data (sample companies/branches/sites, service providers, and orders)
3. Start the Docker containers (app, MySQL, Meilisearch)
4. Generate the app key, install frontend dependencies, run migrations, and seed the database
5. Start the Vite dev server

When it finishes, the app is running — see **Default Users** below to log in.

Other commands (see `Makefile` for the full list):

| Command | What it does |
|---|---|
| `make up` / `make down` | Start / stop the containers |
| `make fresh` | Wipe and re-seed the database |
| `make dev` | Run the Vite dev server only (containers must already be up) |
| `make test` | Run the backend test suite |
| `make logs` | Tail container logs |

## Manual Installation

If you'd rather not use Docker/Sail, or want to install dependencies yourself:

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
   - **With Sail:** `vendor/bin/sail up -d` then `vendor/bin/sail open`
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

- **Primary**: Blue tones (#3b82f6) for a clean, professional theme
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
