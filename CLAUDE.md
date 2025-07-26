# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

TRMNL BYOS Laravel is a self-hostable implementation of a TRMNL server built with Laravel. It manages e-ink display devices, generates screens dynamically, and can proxy the native TRMNL cloud service.

## Development Commands

### Essential Commands
- `composer dev` - Start all development services (server, queue, logs, vite)
- `composer test` or `vendor/bin/pest` - Run test suite
- `composer test-coverage` or `vendor/bin/pest --coverage` - Run tests with coverage
- `composer format` or `vendor/bin/pint` - Format PHP code
- `vendor/bin/phpstan analyse` - Run static analysis

### Laravel Artisan Commands
- `php artisan serve --host=0.0.0.0 --port=4567` - Start development server on local network
- `php artisan migrate --seed` - Run migrations with seeding
- `php artisan queue:listen --tries=1` - Start queue worker
- `php artisan pail --timeout=0` - Real-time log monitoring
- `php artisan trmnl:screen:generate` - Generate screen for devices
- `php artisan db:seed --class=ExampleRecipesSeeder` - Seed demo plugins

### Frontend Commands
- `npm run dev` - Start Vite development server
- `npm run build` - Build for production
- `npm i` - Install Node dependencies

## Architecture Overview

### Core Models and Relationships
- **Device**: Central model representing TRMNL e-ink devices with battery, WiFi, and firmware tracking
- **Plugin**: Content generators (recipes, markup, API integrations) that create screen content
- **Playlist/PlaylistItem**: Scheduling system for rotating content on devices
- **User**: Device ownership and auto-join capabilities

### Key Services
- **ImageGenerationService**: Converts HTML markup to device-compatible images (PNG/BMP) using Browsershot/Puppeteer
- **Queue Jobs**: Background processing for screen generation, firmware updates, and device log cleanup

### API Structure
**Device APIs (no auth required):**
- `/api/display` - Main endpoint devices call to get screen content
- `/api/setup` - Device registration/setup endpoint
- `/api/log` - Device log submission
- `/api/screens` - Direct screen content submission

**User APIs (require auth:sanctum):**
- `/api/devices` - User device management
- `/api/display/update` - Programmatic screen updates
- `/api/display/status` - Device status and configuration

### Frontend Architecture
- **Livewire/Volt**: Modern full-stack reactive components
- **Flux UI**: Component library for consistent interface
- **TailwindCSS**: Utility-first styling

## Key Features and Patterns

### Device Auto-Discovery
Users can enable "assign_new_devices" to automatically register devices that connect to the server.

### Cloud Proxy Mode
Devices can proxy content from the native TRMNL service while allowing local overrides for custom content.

### Screen Generation Pipeline
1. Plugin renders markup (Blade/Liquid templates)
2. GenerateScreenJob converts markup to image via Browsershot
3. Images stored as PNG/BMP based on firmware version
4. Device fetches image via `/api/display` endpoint

### Content Strategies
- **Markup**: Direct HTML/Blade content
- **Recipe**: Liquid templating with data sources
- **API**: External data integration
- **Mashup**: Combining multiple plugins

## Testing Framework

Uses **Pest PHP** testing framework with:
- Feature tests for API endpoints and web interface
- Unit tests for individual components
- Architecture tests to enforce code standards
- RefreshDatabase trait for test isolation

## Code Quality Tools

- **Laravel Pint**: Code formatting with custom rules in `pint.json`
- **PHPStan (Larastan)**: Static analysis at level 4
- **Pest Architecture**: Enforces no `die`, `dd`, or `dump` in app code

## Important File Locations

- API routes: `routes/api.php`
- Web routes: `routes/web.php` 
- Livewire components: `app/Livewire/`
- Models: `app/Models/`
- Jobs: `app/Jobs/`
- Services: `app/Services/`
- Blade views: `resources/views/`
- TRMNL templates: `resources/views/recipes/`, `resources/views/trmnl-layouts/`

## Environment Configuration

Key environment variables for development:
- `APP_ENV=local`
- `DB_CONNECTION=sqlite` (uses `database/database.sqlite`)
- `QUEUE_CONNECTION=sync` (or `database` for background processing)
- `REGISTRATION_ENABLED=1` (allow user registration)
- `TRMNL_PROXY_BASE_URL=https://trmnl.app` (for cloud proxy)

## Special Notes

- Screen images are generated as PNG by default, BMP for older firmware versions
- Device firmware version comparison logic exists for image format compatibility
- Queue worker must be running for cloud proxy and background jobs
- Uses Browsershot/Puppeteer for HTML-to-image conversion
- Supports both local Puppeteer and AWS Lambda (sidecar) for image generation