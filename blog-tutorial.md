# How to Replace Laravel's Default Authentication with Users.au Single Sign-On: A Complete Migration Guide

Single Sign-On (SSO) has become essential for modern web applications. It provides users with a seamless authentication experience while giving developers a secure, standardized way to handle user authentication. In this comprehensive tutorial, we'll walk through completely replacing Laravel's default authentication system with Users.au OAuth authentication using the `users-au/laravel-client` package.

## What is Users.au?

Users.au is an Australian-focused identity provider that offers OAuth 2.0 authentication services. It allows users to authenticate across multiple applications using a single set of credentials, making it perfect for Australian businesses and organizations that want to provide a unified login experience.

## Project Context: TRMNL BYOS Laravel

In this tutorial, we'll be working with a real-world Laravel application - the TRMNL BYOS (Bring Your Own Server) Laravel project. This is a self-hostable implementation of a TRMNL server built with Laravel that manages e-ink display devices. We'll show you exactly how we migrated this existing application from Laravel's default authentication to Users.au OAuth.

The TRMNL BYOS project includes:
- Device management for e-ink displays
- Screen content generation
- User authentication and authorization
- Livewire components for real-time updates
- Docker deployment support

## Prerequisites

Before we begin, make sure you have:

- A Laravel application (this tutorial works with Laravel 5.x through 12.x)
- PHP 8.0 or higher
- Composer installed
- A Users.au OAuth application (we'll cover how to set this up)

## Step 1: Setting Up Your Users.au OAuth Application

First, you'll need to register your application with Users.au:

1. Visit your Users.au instance (e.g., `https://auth.yourcompany.com`)
2. Create a new OAuth application
3. Set your redirect URI to: `http://localhost:4567/auth/usersau/callback` (for development)
4. Note down your Client ID and Client Secret

## Step 2: Installing the Laravel Client Package

Install the Users.au Laravel client package via Composer:

```bash
composer require users-au/laravel-client
```

**What happens during installation:**

The package will install several dependencies:
- `laravel/socialite` - Laravel's OAuth integration package
- `socialiteproviders/manager` - Extended Socialite provider manager
- `users-au/socialite-provider` - Users.au specific Socialite provider

During our installation, we encountered a compatibility issue with Laravel 12.x (the TRMNL project uses Laravel 12.1). The initial attempts failed because the package originally supported Laravel 5.x-10.x. However, version 1.2.0 of the package includes Laravel 11.x and 12.x support.

If you encounter similar issues, specifically install version 1.2.0:

```bash
composer require users-au/laravel-client:1.2.0
```

The installation will also automatically discover and register the service provider, so no manual registration is needed in modern Laravel versions.

## Step 3: Publishing Package Assets

Publish the package configuration and migrations:

```bash
php artisan vendor:publish --provider="Usersau\UsersauLaravelClient\UsersauLaravelClientServiceProvider"
```

**What gets published:**

```
Copying directory [vendor/users-au/laravel-client/config] to [config] ✓
Copying directory [vendor/users-au/laravel-client/database/migrations] to [database/migrations] ✓
```

This creates:
- `config/usersau.php` - Package configuration file
- Migration files in `database/migrations/` for adding OAuth fields to your users table

**Important Note About Database Configuration:**

In our TRMNL project, we encountered a database connection issue during migration because the application was configured for production with MariaDB, but we were running in a local development environment. If you encounter similar issues:

1. For local development, you might want to use SQLite by updating your `.env`:
   ```env
   DB_CONNECTION=sqlite
   DB_DATABASE=database/database.sqlite
   ```

2. For Docker environments, ensure your database containers are running
3. If the migration fails due to production mode, use the `--force` flag (but be careful!)

## Step 4: Running Migrations

Run the migrations to add the required OAuth fields to your users table:

```bash
php artisan migrate
```

**Note:** In our case, since we were transitioning from an existing production environment configuration, we needed to run:

```bash
php artisan migrate --force
```

**What the migration adds:**

The migration adds three new columns to your existing `users` table:
- `usersau_id` - Stores the user's unique ID from the Users.au system
- `usersau_access_token` - Stores the OAuth access token (encrypted)
- `usersau_refresh_token` - Stores the OAuth refresh token (encrypted)

These columns allow your application to maintain the connection between local user records and Users.au accounts.

## Step 5: Updating Your User Model

This is a critical step that requires careful modification of your existing User model. In the TRMNL project, the original User model looked like this:

**Before modification:**
```php
<?php
// app/Models/User.php

class User extends Authenticatable
{
    protected $fillable = [
        'name',
        'email',
        'password',
        'assign_new_devices',
        'assign_new_device_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
    
    // ... other existing methods
}
```

**After modification (what we need to add):**
```php
<?php
// app/Models/User.php

class User extends Authenticatable
{
    protected $fillable = [
        'name',
        'email',
        'password',
        'assign_new_devices',
        'assign_new_device_id',
        // Add these three new fields for Users.au OAuth
        'usersau_id',
        'usersau_access_token', 
        'usersau_refresh_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        // Add these three fields to hidden array for security
        'usersau_id',
        'usersau_access_token',
        'usersau_refresh_token', 
    ];
    
    // ... keep all your existing methods unchanged
}
```

**Why these changes are necessary:**

1. **`$fillable` array**: Adding the OAuth fields allows mass assignment when the package creates or updates user records during the OAuth flow
2. **`$hidden` array**: Adding the OAuth fields here ensures they're never exposed in JSON responses or when the model is serialized, maintaining security
3. **Preserve existing fields**: Keep all your application-specific fields (like `assign_new_devices` in our TRMNL example) unchanged

## Step 6: Configuring Services

The `config/services.php` file needs to be updated to include Users.au OAuth configuration. In the TRMNL project, this file already contained other service configurations.

**Before modification:**
```php
<?php
// config/services.php

return [
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'trmnl' => [
        'proxy_base_url' => env('TRMNL_PROXY_BASE_URL', 'https://trmnl.app'),
        'proxy_refresh_minutes' => env('TRMNL_PROXY_REFRESH_MINUTES', 15),
        // ... other TRMNL-specific config
    ],

    'webhook' => [
        'notifications' => [
            'url' => env('WEBHOOK_NOTIFICATION_URL', null),
            'topic' => env('WEBHOOK_NOTIFICATION_TOPIC', 'null'),
        ],
    ],
];
```

**After modification (add Users.au config):**
```php
<?php
// config/services.php

return [
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'trmnl' => [
        'proxy_base_url' => env('TRMNL_PROXY_BASE_URL', 'https://trmnl.app'),
        'proxy_refresh_minutes' => env('TRMNL_PROXY_REFRESH_MINUTES', 15),
        // ... other TRMNL-specific config
    ],

    'webhook' => [
        'notifications' => [
            'url' => env('WEBHOOK_NOTIFICATION_URL', null),
            'topic' => env('WEBHOOK_NOTIFICATION_TOPIC', 'null'),
        ],
    ],

    // Add this new section for Users.au OAuth
    'usersau' => [
        'client_id' => env('USERSAU_CLIENT_ID'),
        'client_secret' => env('USERSAU_CLIENT_SECRET'),
        'redirect' => env('USERSAU_REDIRECT_URI'),
        'host' => env('USERSAU_HOST'),
    ],
];
```

**Key points:**
- Add the Users.au configuration block at the end of the array
- Keep all existing service configurations unchanged
- The package will automatically read these values during the OAuth flow

## Step 7: Setting Environment Variables

Add your Users.au credentials to your `.env` file:

```env
# Users.au OAuth Configuration
USERSAU_CLIENT_ID=your_client_id_here
USERSAU_CLIENT_SECRET=your_client_secret_here
USERSAU_REDIRECT_URI=http://localhost:4567/auth/usersau/callback
USERSAU_HOST=https://auth.yourcompany.com
```

Also update your `.env.example` file so other developers know what variables to set.

## Step 8: The Critical Step - Replacing Laravel's Default Authentication Routes

This is where we completely replace Laravel's default authentication system with Users.au OAuth. This step is **crucial** and requires careful attention to maintain backward compatibility with existing UI components.

### Understanding the Existing Authentication Structure

The TRMNL project originally used Laravel's default authentication with Livewire/Volt components. Here's what the original `routes/auth.php` looked like:

**Original authentication routes:**
```php
<?php
// routes/auth.php (BEFORE modification)

use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    // Original Laravel/Volt login page
    Volt::route('login', 'auth.login')
        ->name('login');

    // Original Laravel/Volt registration page
    if (config('app.registration.enabled')) {
        Volt::route('register', 'auth.register')
            ->name('register');
    }

    Volt::route('forgot-password', 'auth.forgot-password')
        ->name('password.request');

    Volt::route('reset-password/{token}', 'auth.reset-password')
        ->name('password.reset');
});

Route::middleware('auth')->group(function () {
    Volt::route('verify-email', 'auth.verify-email')
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Volt::route('confirm-password', 'auth.confirm-password')
        ->name('password.confirm');
});

// Original logout using Livewire action
Route::post('logout', App\Livewire\Actions\Logout::class)
    ->name('logout');
```

### Our Strategy: Seamless Redirection

Instead of removing the existing routes entirely (which would break existing UI components), we replace them with **redirects** to the Users.au OAuth routes. This ensures that:

1. All existing UI components continue to work
2. Any hardcoded links in views still function
3. The transition is completely seamless for users
4. We maintain backward compatibility

### The New Authentication Routes

Here's what we changed the `routes/auth.php` file to:

**Modified authentication routes:**
```php
<?php
// routes/auth.php (AFTER modification)

use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    // CHANGED: Redirect to Users.au OAuth login instead of showing Laravel login form
    Route::get('login', function () {
        return redirect()->route('usersau.login');
    })->name('login');

    // CHANGED: Redirect to Users.au OAuth registration instead of showing Laravel registration form
    if (config('app.registration.enabled')) {
        Route::get('register', function () {
            return redirect()->route('usersau.register');
        })->name('register');
    }

    // KEPT: Password reset routes remain for emergency access
    Volt::route('forgot-password', 'auth.forgot-password')
        ->name('password.request');

    Volt::route('reset-password/{token}', 'auth.reset-password')
        ->name('password.reset');
});

Route::middleware('auth')->group(function () {
    // KEPT: Email verification remains unchanged
    Volt::route('verify-email', 'auth.verify-email')
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    // KEPT: Password confirmation remains for sensitive operations
    Volt::route('confirm-password', 'auth.confirm-password')
        ->name('password.confirm');
});

// CHANGED: Redirect logout to Users.au OAuth logout (supports both GET and POST)
Route::match(['get', 'post'], 'logout', function () {
    return redirect()->route('usersau.logout');
})->name('logout');
```

### What These Changes Accomplish

1. **Login Flow**: `/login` → redirects to → `/auth/usersau/redirect` (Users.au OAuth)
2. **Registration Flow**: `/register` → redirects to → `/auth/usersau/register` (Users.au registration)  
3. **Logout Flow**: `/logout` → redirects to → `/auth/usersau/logout` (Users.au logout)

### Why We Keep Some Original Routes

**Password Reset**: We kept the password reset functionality because:
- It provides a fallback authentication method
- Some users might need emergency access
- It doesn't interfere with the OAuth flow

**Email Verification**: We kept email verification because:
- It might still be needed for certain compliance requirements
- It doesn't conflict with OAuth authentication

**Password Confirmation**: We kept password confirmation for:
- Sensitive operations that require additional security
- Compatibility with Laravel's built-in security features

### Preserving the Original Livewire Components

The beauty of this approach is that we **don't delete** the original Livewire/Volt authentication components:

- `resources/views/livewire/auth/login.blade.php` - Still exists but isn't accessed
- `resources/views/livewire/auth/register.blade.php` - Still exists but isn't accessed
- `app/Livewire/Actions/Logout.php` - Still exists but isn't used

These remain as a fallback or for future reference, but are effectively bypassed by our route redirects.

### How Existing UI Components Continue to Work

All existing UI components that use named routes continue to work seamlessly:

**Welcome page navigation** (`resources/views/welcome.blade.php`):
```php
<!-- These existing links continue to work perfectly -->
<a href="{{ route('login') }}">Log in</a>  <!-- Now redirects to Users.au -->
<a href="{{ route('register') }}">Register</a>  <!-- Now redirects to Users.au -->
```

**Header navigation** (`resources/views/components/layouts/app/header.blade.php`):
```php
<!-- Existing logout form continues to work -->
<form method="POST" action="{{ route('logout') }}">  <!-- Now redirects to Users.au -->
    @csrf
    <button type="submit">Log Out</button>
</form>
```

This means **zero changes** are required to existing view files - everything just works!

## Step 9: Using the Authentication Routes

The package automatically registers these authentication routes:

| Route | Purpose |
|-------|---------|
| `/auth/usersau/redirect` | Initiates OAuth login flow |
| `/auth/usersau/callback` | Handles OAuth callback |
| `/auth/usersau/logout` | Logs out and redirects to Users.au |
| `/auth/usersau/register` | Redirects to Users.au registration |
| `/auth/usersau/account` | Redirects to Users.au account management |

## Step 10: Adding Login Links to Your Views

Update your views to use Users.au authentication:

```php
<!-- In your Blade templates -->

<!-- Login link -->
<a href="{{ route('usersau.login') }}" class="btn btn-primary">
    Login with Users.au
</a>

<!-- Register link -->
<a href="{{ route('usersau.register') }}" class="btn btn-success">
    Create Account
</a>

<!-- Logout link (for authenticated users) -->
@auth
<form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit" class="btn btn-secondary">
        Logout
    </button>
</form>
@endauth

<!-- Account management link -->
@auth
<a href="{{ route('usersau.account') }}" class="btn btn-info">
    Manage Account
</a>
@endauth
```

## Step 11: Configuring Docker for Users.au OAuth

The TRMNL project includes Docker support for both development and production deployments. We need to update both Docker Compose configurations to support the new Users.au environment variables.

### Development Docker Configuration

**Original `docker-compose.yml` (excerpt):**
```yaml
services:
    trmnl-app:
        build:
            context: .
            dockerfile: Dockerfile
        ports:
            - "4567:8080"
        environment:
            - PHP_OPCACHE_ENABLE=1
            - TRMNL_PROXY_REFRESH_MINUTES=15
            - DB_DATABASE=trmnl
            - APP_URL=${APP_URL:-http://localhost:4567}
            - APP_ENV=${APP_ENV:-local}
            - APP_NAME=${APP_NAME:-Trmnl}
```

**Updated `docker-compose.yml` (with Users.au support):**
```yaml
services:
    trmnl-app:
        build:
            context: .
            dockerfile: Dockerfile
        ports:
            - "4567:8080"
        environment:
            - PHP_OPCACHE_ENABLE=1
            - TRMNL_PROXY_REFRESH_MINUTES=15
            - DB_DATABASE=trmnl
            - APP_URL=${APP_URL:-http://localhost:4567}
            - APP_ENV=${APP_ENV:-local}
            - APP_NAME=${APP_NAME:-Trmnl}
            # Users.au OAuth Configuration
            - USERSAU_CLIENT_ID=${USERSAU_CLIENT_ID:-}
            - USERSAU_CLIENT_SECRET=${USERSAU_CLIENT_SECRET:-}
            - USERSAU_REDIRECT_URI=${USERSAU_REDIRECT_URI:-${APP_URL}/auth/usersau/callback}
            - USERSAU_HOST=${USERSAU_HOST:-}
```

### Production Docker Configuration

**Original `docker/prod/docker-compose.yml`:**
```yaml
services:
    app:
        image: ghcr.io/usetrmnl/byos_laravel:latest
        ports:
            - "4567:8080"
        environment:
            - PHP_OPCACHE_ENABLE=1
            - TRMNL_PROXY_REFRESH_MINUTES=15
            - DB_DATABASE=database/storage/database.sqlite
```

**Updated `docker/prod/docker-compose.yml`:**
```yaml
services:
    app:
        image: ghcr.io/usetrmnl/byos_laravel:latest
        ports:
            - "4567:8080"
        environment:
            - PHP_OPCACHE_ENABLE=1
            - TRMNL_PROXY_REFRESH_MINUTES=15
            - DB_DATABASE=database/storage/database.sqlite
            # Users.au OAuth Configuration (uncomment and configure for production)
            #- USERSAU_CLIENT_ID=your_production_client_id
            #- USERSAU_CLIENT_SECRET=your_production_client_secret
            #- USERSAU_REDIRECT_URI=https://yourproductiondomain.com/auth/usersau/callback
            #- USERSAU_HOST=https://auth.yourcompany.com
```

### Key Docker Configuration Points

1. **Development**: Uses environment variable passthrough (`${VARIABLE:-default}`) so values from your host `.env` file are used
2. **Production**: Variables are commented out for security - uncomment and set real values when deploying
3. **Callback URL**: The redirect URI automatically uses the `APP_URL` environment variable with the correct path
4. **Fallback Values**: All Users.au variables have empty fallbacks so the container will start even if they're not set

### Real-World Environment Variables

Here are the actual environment variables we configured for the TRMNL project:

```env
# Users.au OAuth Configuration (REAL EXAMPLE)
USERSAU_CLIENT_ID=9f7bd182-96e1-47cb-969c-f22972b60556
USERSAU_CLIENT_SECRET=JbP5472mVhSdZErGLKrSXw7lcdUBwjIERROYJKbp
USERSAU_REDIRECT_URI=http://localhost:4567/auth/usersau/callback
USERSAU_HOST=https://auth.slj.me
```

### Important Notes About Callback URLs

We initially encountered a 404 error because we set the callback URL incorrectly:

**❌ Wrong callback URL:**
```env
USERSAU_REDIRECT_URI=http://localhost:4567/oauth/callback  # This doesn't exist!
```

**✅ Correct callback URL:**
```env
USERSAU_REDIRECT_URI=http://localhost:4567/auth/usersau/callback  # This is the actual route
```

The Users.au Laravel client package automatically registers the route at `/auth/usersau/callback`, not `/oauth/callback`.

## The Complete Authentication Flow: What Actually Happens

Understanding the complete flow helps with debugging and customization. Here's exactly what happens when a user authenticates with our newly integrated Users.au OAuth system:

### Step-by-Step Authentication Process

#### 1. User Initiates Login
**What the user sees:** Clicks "Login" on your application
**What happens behind the scenes:**
- User clicks a link like `<a href="{{ route('login') }}">Login</a>`
- Due to our route modification, this redirects to `route('usersau.login')`
- Laravel internally redirects to `/auth/usersau/redirect`

#### 2. OAuth Authorization Request
**What the user sees:** Redirected to Users.au login page
**What happens behind the scenes:**
- The Users.au Laravel client constructs an OAuth authorization URL
- URL includes: client ID, response type, scope, state, and redirect URI
- User is redirected to something like: `https://auth.slj.me/oauth/authorize?client_id=9f7bd182-96e1-47cb-969c-f22972b60556&response_type=code&scope=openid+profile+email&state=randomstatestring&redirect_uri=http://localhost:4567/auth/usersau/callback`

#### 3. User Authentication at Users.au
**What the user sees:** Users.au login form (if not already logged in)
**What happens behind the scenes:**
- User enters credentials on Users.au
- Users.au validates the credentials
- Users.au checks if the user has authorized your application
- If authorization is needed, Users.au shows consent screen

#### 4. Authorization Grant
**What the user sees:** Redirected back to your application
**What happens behind the scenes:**
- Users.au generates an authorization code
- Users.au redirects to: `http://localhost:4567/auth/usersau/callback?code=abc123&state=randomstatestring`
- The Laravel package receives this callback

#### 5. Token Exchange
**What the user sees:** Loading/processing (brief moment)
**What happens behind the scenes:**
- The package validates the state parameter (CSRF protection)
- Package makes a server-to-server request to Users.au token endpoint
- Exchanges authorization code for access token and refresh token
- Receives user profile information from Users.au

#### 6. User Record Management
**What the user sees:** Still processing
**What happens behind the scenes:**
- Package looks for existing user with matching `usersau_id`
- If user exists: Updates user record with latest info and tokens
- If user doesn't exist: Creates new user record with Users.au data
- Example user creation:
```php
User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'usersau_id' => 'usersau_12345',
    'usersau_access_token' => 'encrypted_access_token',
    'usersau_refresh_token' => 'encrypted_refresh_token',
]);
```

#### 7. Laravel Authentication
**What the user sees:** Logged into your application
**What happens behind the scenes:**
- Package calls `Auth::login($user)` to authenticate the user in Laravel
- Laravel session is established
- User is redirected to configured post-login URL (default: `/`)

### Real-World Example from TRMNL Project

When we tested this with the TRMNL project, here's what we observed:

**1. Initial click on welcome page:**
```
GET /login
→ 302 Redirect to /auth/usersau/redirect
```

**2. OAuth initiation:**
```
GET /auth/usersau/redirect
→ 302 Redirect to https://auth.slj.me/oauth/authorize?client_id=9f7bd182-96e1-47cb-969c-f22972b60556&...
```

**3. Users.au authentication:**
```
User authenticates on auth.slj.me
→ 302 Redirect to http://localhost:4567/auth/usersau/callback?code=def123&state=xyz789
```

**4. Callback processing:**
```
GET /auth/usersau/callback?code=def123&state=xyz789
→ Package processes OAuth callback
→ User record created/updated in database
→ 302 Redirect to /dashboard (or configured after-login URL)
```

### Database Changes During Authentication

Here's what gets stored in your database during the authentication process:

**New user record:**
```sql
INSERT INTO users (
    name, 
    email, 
    usersau_id, 
    usersau_access_token, 
    usersau_refresh_token,
    created_at,
    updated_at
) VALUES (
    'John Doe',
    'john@slj.me', 
    'usersau_12345',
    'encrypted_access_token_here',
    'encrypted_refresh_token_here',
    '2024-01-15 10:30:00',
    '2024-01-15 10:30:00'
);
```

**Laravel session:**
```
Session Key: 'login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d'
Session Value: [user_id]
```

### Error Handling and Edge Cases

**Invalid State Parameter:**
- If state doesn't match, user sees "Invalid state" error
- Usually caused by session issues or CSRF token problems

**User Cancels Authorization:**
- If user clicks "Cancel" on Users.au, they're redirected back with error parameter
- Package handles this gracefully and shows appropriate error message

**Network Issues:**
- If token exchange fails, user sees OAuth error
- Package logs detailed error information for debugging

**Existing Email Conflict:**
- If Users.au email matches existing user without `usersau_id`, package can handle this based on configuration
- Default behavior: Links the Users.au account to existing user

### Post-Authentication User Experience

After successful authentication, the user experience in the TRMNL application is seamless:

**1. User sees dashboard:** Redirected to `/dashboard` (configured in `config/usersau.php`)
**2. User data available:** All Laravel user authentication features work normally
**3. Session persistence:** User remains logged in according to Laravel session configuration
**4. Logout process:** When user clicks logout, they're redirected to Users.au logout for complete SSO logout

This creates a completely seamless experience where users don't even realize they're using OAuth - it just feels like normal application authentication, but with the added benefit of single sign-on across all applications in your Users.au ecosystem.

## Customizing the Integration

### Custom User Handling

You can listen for authentication events to perform custom actions:

```php
// In a service provider

Event::listen(\Illuminate\Auth\Events\Login::class, function ($event) {
    $user = $event->user;
    
    // Log login activity
    Log::info('User logged in via Users.au', [
        'user_id' => $user->id,
        'usersau_id' => $user->usersau_id,
    ]);
    
    // Update last login timestamp
    $user->update(['last_login_at' => now()]);
});
```

### Configuration Options

Customize the package behavior in `config/usersau.php`:

```php
return [
    'after_login_url' => '/dashboard',        // Where to redirect after login
    'after_logout_url' => '/',               // Where to redirect after logout  
    'after_register_url' => '/onboarding',   // Where to redirect after registration
    'user_model' => App\Models\User::class,  // Your user model
    'middleware' => ['web'],                 // Middleware for auth routes
    'profile_photo_column' => null,          // Column for profile photos (optional)
];
```

## Troubleshooting

### Common Issues

**"Invalid State Exception"**
- Usually caused by session configuration issues
- Ensure your session driver is properly configured
- Check that cookies are enabled in your browser

**"Client Exception during OAuth"**
- Verify your `USERSAU_CLIENT_ID` and `USERSAU_CLIENT_SECRET`
- Ensure your `USERSAU_REDIRECT_URI` matches exactly what's configured in Users.au
- Check that `USERSAU_HOST` is correct and accessible

**404 on Callback URL**
- Make sure the callback URL is `/auth/usersau/callback` not `/oauth/callback`
- Ensure the package routes are being registered properly

### Debug Mode

Enable debug mode for detailed error messages:

```env
APP_DEBUG=true
LOG_LEVEL=debug
```

## Security Considerations

1. **Never commit credentials** - Keep your OAuth credentials in environment variables
2. **Use HTTPS in production** - Always use HTTPS for your callback URLs in production
3. **Validate redirect URIs** - Ensure your Users.au application has the correct redirect URIs configured
4. **Keep tokens secure** - The OAuth tokens are automatically hidden from serialization

## Conclusion

Integrating Users.au OAuth into your Laravel application provides a secure, user-friendly authentication experience. The `users-au/laravel-client` package handles all the OAuth complexity, allowing you to focus on building your application features.

With this setup, your users can seamlessly authenticate across multiple applications in your organization's ecosystem, while you benefit from centralized user management and enhanced security.

## Further Reading

- [Users.au Documentation](https://docs.users.au)
- [Laravel Socialite Documentation](https://laravel.com/docs/socialite)
- [OAuth 2.0 RFC](https://tools.ietf.org/html/rfc6749)
- [users-au/laravel-client GitHub Repository](https://github.com/users-au/laravel-client)

---

*This tutorial was written for Laravel developers looking to implement Users.au single sign-on authentication. For more advanced configurations and enterprise features, consult the official Users.au documentation.*