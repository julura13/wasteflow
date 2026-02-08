# WasteFlow - Laravel + React + Inertia.js Application

A modern waste management application built with Laravel, React, Inertia.js, and Tailwind CSS.

## Tech Stack

- **Backend**: Laravel 12
- **Frontend**: React 18 with Inertia.js
- **Styling**: Tailwind CSS v4 with Flowbite components
- **Data Tables**: TanStack Table (headless, type-safe, powerful)
- **Authentication & Authorization**: Spatie Laravel Permission
- **Database**: SQLite (development)

## Features

- ✅ Modern Laravel 12 setup
- ✅ React 18 with Inertia.js for SPA-like experience
- ✅ Tailwind CSS v4 with custom WasteFlow color theme
- ✅ Flowbite UI components
- ✅ TanStack Table for powerful data tables with sorting, filtering, and pagination
- ✅ Spatie Laravel Permission for roles and permissions
- ✅ Pre-configured user roles: Admin, Manager, Operator, Client
- ✅ Sample client management with data table
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
   ```bash
   php artisan serve
   ```

## Default Users

The following users are created by the seeder:

- **Admin**: admin@wasteflow.example.com / password
- **Manager**: manager@wasteflow.example.com / password  
- **Operator**: operator@wasteflow.example.com / password

## Development

- **Frontend development**: `npm run dev` (starts Vite dev server)
- **Backend development**: `php artisan serve` (starts Laravel dev server)

## Testing

### Backend Tests (Pest)
```bash
# Run all tests
./vendor/bin/pest

# Run specific test file
./vendor/bin/pest tests/Unit/ClientTest.php

# Run with coverage
./vendor/bin/pest --coverage
```

### Frontend Tests (Jest)
```bash
# Run all tests
npm test

# Run tests in watch mode
npm run test:watch

# Run tests with coverage
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
