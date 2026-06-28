# Class Sync — Student Monitoring System

A modern, offline-first Student Monitoring System built with **Laravel 13**, **Livewire 3**, and **Tailwind CSS**. Designed for single-computer deployments and multi-user LAN environments in schools — no internet required.

## Features

- **7-Step Installation Wizard** — First-time setup at `/setup`
- **Multi-Role Authentication** — Administrator, Registrar, Teacher, Guidance, Accounting, Cashier, Principal, Clinic, Security, Student, Parent
- **Student Management** — Profiles, photos, QR codes, RFID tags, guardians, documents
- **Attendance Module** — Manual entry, QR/RFID scanner, live monitoring dashboard
- **Analytics Dashboard** — Present, late, absent, excused stats with charts
- **Reports** — Daily, weekly, monthly, yearly exports (PDF, Excel, CSV)
- **Audit Logs** — Full activity tracking with IP and device info
- **Backup System** — Manual and scheduled database backups
- **REST API** — Ready for future mobile apps (Laravel Sanctum)
- **Desktop Ready** — Electron packaging for Windows desktop app
- **Dark Mode** — Light, dark, and system theme support

## Requirements

- PHP 8.3+
- Composer 2.x
- Node.js 18+ & NPM
- MySQL 8+ / MariaDB 10.6+ or SQLite
- Extensions: PDO, OpenSSL, MBString, FileInfo, ZIP, BCMath

## Quick Start (Development)

```bash
# Clone and install dependencies
composer install
npm install

# Copy environment file
cp .env.example .env

# Generate key and run migrations (or use setup wizard)
php artisan key:generate
php artisan migrate --seed

# Build frontend assets
npm run build

# Start development server
php artisan serve
```

Visit `http://localhost:8000/setup` for first-time installation, or `http://localhost:8000` if already configured.

**Default admin credentials** (after seeding): `admin` / `password`

## First-Time Setup Wizard

On first launch, the application redirects to `/setup`:

1. **Welcome** — Version and system requirements
2. **System Check** — PHP, extensions, writable directories
3. **Database** — MySQL, MariaDB, or SQLite configuration
4. **Application** — School name, timezone, locale, academic year
5. **Administrator** — Create system admin account
6. **Installation** — Migrate, seed, optimize with live logs
7. **Finish** — Redirect to dashboard

## LAN Deployment (Multi-User)

### Server Setup

1. Install on the school server PC
2. Complete the setup wizard with MySQL/MariaDB
3. Configure MySQL to accept LAN connections
4. Start Laravel: `php artisan serve --host=0.0.0.0 --port=8000`
5. Or use Apache/Nginx with the `public/` directory as document root

### Client PCs

Access from any device on the LAN:

```
http://<server-ip>:8000
```

All users share the same database on the server.

## Desktop Application (Electron)

Class Sync can run as a **native Windows app**. Electron starts PHP + Laravel in the background and opens the app in its own window.

### Run locally (development)

```bash
cd electron
npm install
npm rebuild electron-builder
npm start
```

PHP is auto-detected from Laragon, WAMP, or your system PATH.

### Build Windows installer

Bundle portable PHP first so the installer works on PCs without Laragon:

```powershell
cd electron
npm install
npm run build:desktop
```

The installer is created in `electron/dist/`. Install it, then open **Class Sync** from the Start Menu.

On first launch, complete the `/setup` wizard if the app is not configured yet.

## Docker (Optional)

```bash
docker compose up -d
```

See `docker-compose.yml` for MySQL, PHP, and Nginx services.

## API Documentation

Base URL: `/api/v1`

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/login` | Authenticate and get token |
| GET | `/api/v1/students` | List students |
| POST | `/api/v1/attendance/scan` | Record attendance by QR/RFID |
| GET | `/api/v1/attendance/stats` | Daily attendance statistics |

Include token: `Authorization: Bearer <token>`

## Project Structure

```
app/
├── Actions/          # Single-purpose action classes
├── DTOs/             # Data transfer objects
├── Enums/            # Type-safe enumerations
├── Http/             # Controllers, Middleware, Requests
├── Livewire/         # UI components
├── Models/           # Eloquent models
├── Policies/         # Authorization policies
├── Repositories/     # Data access layer
└── Services/         # Business logic layer
```

## Testing

```bash
php artisan test
```

## Security

- CSRF protection on all forms
- Role-based access control (Spatie Permission)
- Encrypted passwords (bcrypt)
- Activity audit logging
- Rate limiting on API routes
- Secure file upload validation

## License

MIT License — see [LICENSE](LICENSE)
