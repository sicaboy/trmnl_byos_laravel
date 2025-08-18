# Laravel Integration Guide

Users.au provides seamless OAuth 2.0 integration with Laravel applications through the `users-au/laravel-client` package. This comprehensive guide covers installation, configuration, migration from existing authentication systems, and advanced usage patterns.

## Integration Approaches

This guide covers two main integration scenarios:

1. **New Application**: Installing Users.au OAuth in a fresh Laravel application
2. **Existing Application Migration**: Replacing Laravel's default authentication with Users.au OAuth (demonstrated with a real-world project)

We'll focus primarily on the migration scenario as it covers the most common use case and demonstrates how to maintain backward compatibility.

## Overview

The Laravel client package provides:

- 🔐 **OAuth 2.0 Authentication** - Secure authentication via Users.au
- 👤 **Automatic User Management** - Creates and updates user records automatically  
- 🔄 **Token Management** - Handles access and refresh tokens
- 🎨 **Profile Photo Support** - Optional profile photo synchronization
- 🛡️ **Middleware Protection** - Configurable middleware for route protection
- 📱 **Account Management** - Direct integration with Users.au account pages
- 🚪 **Single Sign-Out** - Coordinated logout across systems

## Requirements

- PHP >= 8.0
- Composer >= 2.0  
- Laravel 5.0+ (supports versions 5.x through 12.x)
- Users.au OAuth application credentials

## Quick Start

### 1. Install Package

```bash
composer require users-au/laravel-client
```

**For Laravel 12.x compatibility**, specifically use version 1.2.0:

```bash
composer require users-au/laravel-client:1.2.0
```

The package automatically installs required dependencies:
- `laravel/socialite` - OAuth integration
- `socialiteproviders/manager` - Extended provider support  
- `users-au/socialite-provider` - Users.au specific provider

### 2. Publish Assets

```bash
php artisan vendor:publish --provider="Usersau\UsersauLaravelClient\UsersauLaravelClientServiceProvider"
```

This publishes:
- `config/usersau.php` - Package configuration
- Database migrations for OAuth fields

### 3. Run Migrations

```bash
php artisan migrate
```

**For production environments:**
```bash
php artisan migrate --force
```

Adds three columns to your `users` table:
- `usersau_id` - Users.au unique identifier
- `usersau_access_token` - OAuth access token (encrypted)
- `usersau_refresh_token` - OAuth refresh token (encrypted)

### 4. Configure Environment

Add to your `.env` file:

```env
USERSAU_CLIENT_ID=your_client_id
USERSAU_CLIENT_SECRET=your_client_secret
USERSAU_REDIRECT_URI=https://yourapp.com/auth/usersau/callback
USERSAU_HOST=https://auth.yourcompany.com
```

**Important**: The callback URL must be `/auth/usersau/callback`, not `/oauth/callback`.

## Configuration

### User Model Setup

Update your User model to include OAuth fields:

```php
// app/Models/User.php

protected $fillable = [
    'name', 'email', 'password',
    'usersau_id',
    'usersau_access_token', 
    'usersau_refresh_token',
];

protected $hidden = [
    'password', 'remember_token',
    'usersau_id',
    'usersau_access_token',
    'usersau_refresh_token',
];
```

### Services Configuration

Add to `config/services.php`:

```php
'usersau' => [
    'client_id' => env('USERSAU_CLIENT_ID'),
    'client_secret' => env('USERSAU_CLIENT_SECRET'), 
    'redirect' => env('USERSAU_REDIRECT_URI'),
    'host' => env('USERSAU_HOST'),
],
```

### Package Configuration

Customize behavior in `config/usersau.php`:

```php
return [
    'after_login_url' => '/',              // Redirect after login
    'after_logout_url' => '/',             // Redirect after logout
    'after_register_url' => '/',           // Redirect after registration
    'user_model' => App\Models\User::class, // User model class
    'middleware' => ['web'],               // Route middleware
    'profile_photo_column' => null,        // Profile photo column (optional)
];
```

## Authentication Routes

The package automatically registers these routes:

| Route | Method | Name | Description |
|-------|--------|------|-------------|
| `/auth/usersau/redirect` | GET | `usersau.login` | Initiates OAuth flow |
| `/auth/usersau/callback` | GET | - | OAuth callback handler |
| `/auth/usersau/logout` | GET | `usersau.logout` | Logout and redirect |
| `/auth/usersau/register` | GET | `usersau.register` | Registration redirect |
| `/auth/usersau/account` | GET | `usersau.account` | Account management |

## Usage Examples

### Basic Authentication Links

```php
<!-- Login -->
<a href="{{ route('usersau.login') }}">Login with Users.au</a>

<!-- Register --> 
<a href="{{ route('usersau.register') }}">Create Account</a>

<!-- Logout -->
<form method="POST" action="{{ route('usersau.logout') }}">
    @csrf
    <button type="submit">Logout</button>
</form>

<!-- Account Management -->
@auth
<a href="{{ route('usersau.account') }}">Manage Account</a>
@endauth
```

## Migrating from Existing Laravel Authentication

This section demonstrates how to migrate an existing Laravel application from default authentication to Users.au OAuth while maintaining backward compatibility.

### Understanding the Migration Strategy

Instead of removing existing authentication routes (which would break existing UI components), we use **seamless redirection**:

1. Keep existing route names (`login`, `register`, `logout`)
2. Replace route handlers with redirects to Users.au OAuth routes
3. Preserve all existing UI components and links
4. Maintain fallback authentication methods where appropriate

### Original Laravel Authentication Structure

**Before migration** (`routes/auth.php`):
```php
<?php
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    // Original Livewire/Volt login
    Volt::route('login', 'auth.login')->name('login');
    
    // Original Livewire/Volt registration
    if (config('app.registration.enabled')) {
        Volt::route('register', 'auth.register')->name('register');
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

// Original Livewire logout action
Route::post('logout', App\Livewire\Actions\Logout::class)->name('logout');
```

### Migrated Authentication Structure

**After migration** (`routes/auth.php`):
```php
<?php
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    // MIGRATED: Redirect to Users.au OAuth login
    Route::get('login', function () {
        return redirect()->route('usersau.login');
    })->name('login');
    
    // MIGRATED: Redirect to Users.au OAuth registration
    if (config('app.registration.enabled')) {
        Route::get('register', function () {
            return redirect()->route('usersau.register');
        })->name('register');
    }
    
    // PRESERVED: Password reset for emergency access
    Volt::route('forgot-password', 'auth.forgot-password')
        ->name('password.request');
    Volt::route('reset-password/{token}', 'auth.reset-password')
        ->name('password.reset');
});

Route::middleware('auth')->group(function () {
    // PRESERVED: Email verification for compliance
    Volt::route('verify-email', 'auth.verify-email')
        ->name('verification.notice');
    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    // PRESERVED: Password confirmation for sensitive operations
    Volt::route('confirm-password', 'auth.confirm-password')
        ->name('password.confirm');
});

// MIGRATED: Redirect logout to Users.au (supports GET and POST)
Route::match(['get', 'post'], 'logout', function () {
    return redirect()->route('usersau.logout');
})->name('logout');
```

### What Gets Preserved vs. Migrated

| Component | Status | Reason |
|-----------|--------|---------|
| **Login route** | ✅ Migrated | Core authentication |
| **Register route** | ✅ Migrated | Core authentication |
| **Logout route** | ✅ Migrated | Single sign-out |
| **Password reset** | 🔄 Preserved | Emergency access |
| **Email verification** | 🔄 Preserved | Compliance requirements |
| **Password confirmation** | 🔄 Preserved | Sensitive operations |
| **Original Livewire components** | 🔄 Preserved | Fallback/reference |

### Benefits of This Approach

1. **Zero UI Changes**: All existing `route('login')` calls work unchanged
2. **Backward Compatibility**: Existing bookmarks and links continue working
3. **Gradual Migration**: Can be rolled back easily if needed
4. **Fallback Options**: Emergency access methods remain available
5. **Developer Friendly**: No need to update view files

### Handling Edge Cases

**Mixed Authentication Requirements**:
Some applications need both OAuth and local authentication. You can support this by:

```php
// Example: Admin backdoor with local auth
Route::get('admin/login', function() {
    return view('auth.admin-login'); // Local form
})->name('admin.login');

// Regular users use OAuth
Route::get('login', function() {
    return redirect()->route('usersau.login');
})->name('login');
```

**API Authentication**:
OAuth primarily handles web authentication. For API access:

```php
// API routes can still use Sanctum tokens
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/api/user', function (Request $request) {
        return $request->user();
    });
});
```

### Custom Event Handling

Listen for authentication events:

```php
// In a service provider

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;

Event::listen(Login::class, function ($event) {
    $user = $event->user;
    
    // Custom logic after user login
    activity()
        ->performedOn($user)
        ->log('User logged in via Users.au');
});
```

## Complete OAuth Flow Documentation

### Overview

The Users.au OAuth integration follows the Authorization Code flow with PKCE (Proof Key for Code Exchange) for enhanced security. Understanding this flow is crucial for debugging and customization.

### Detailed Step-by-Step Process

#### 1. Authentication Initiation
**User Action**: Clicks login link in your application
**Technical Flow**:
```
GET /login
→ 302 Redirect to /auth/usersau/redirect (due to route migration)
→ Package constructs OAuth authorization URL
→ 302 Redirect to Users.au authorization endpoint
```

**Authorization URL Structure**:
```
https://auth.yourcompany.com/oauth/authorize?
  client_id=9f7bd182-96e1-47cb-969c-f22972b60556&
  response_type=code&
  scope=openid+profile+email&
  state=random_csrf_token&
  redirect_uri=https://yourapp.com/auth/usersau/callback
```

#### 2. User Authentication at Users.au
**User Experience**: 
- If not logged in: Sees Users.au login form
- If logged in: May see consent screen (first time only)
- If already authorized: Immediate redirect back

**Technical Process**:
- Users.au validates user credentials
- Checks application authorization status
- Generates authorization code
- Validates redirect URI matches registered value

#### 3. Authorization Grant Callback
**Technical Flow**:
```
Users.au redirects to:
GET /auth/usersau/callback?code=abc123&state=xyz789

Package receives callback and:
1. Validates state parameter (CSRF protection)
2. Extracts authorization code
3. Prepares for token exchange
```

#### 4. Token Exchange (Server-to-Server)
**Behind the scenes** (invisible to user):
```php
// Package makes this request automatically
POST https://auth.yourcompany.com/oauth/token
Content-Type: application/x-www-form-urlencoded

grant_type=authorization_code&
code=abc123&
redirect_uri=https://yourapp.com/auth/usersau/callback&
client_id=9f7bd182-96e1-47cb-969c-f22972b60556&
client_secret=your_secret
```

**Response**:
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "refresh_token": "def50200...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "scope": "openid profile email"
}
```

#### 5. User Profile Retrieval
**Package automatically fetches**:
```php
GET https://auth.yourcompany.com/api/user
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...
```

**User Data Response**:
```json
{
  "id": "usersau_12345",
  "name": "John Doe",
  "email": "john@example.com",
  "avatar": "https://auth.yourcompany.com/avatars/john.jpg",
  "email_verified_at": "2024-01-15T10:30:00Z"
}
```

#### 6. Local User Management
**Database Operations**:
```php
// Package logic (simplified)
$existingUser = User::where('usersau_id', $userData['id'])->first();

if ($existingUser) {
    // Update existing user
    $existingUser->update([
        'name' => $userData['name'],
        'email' => $userData['email'],
        'usersau_access_token' => encrypt($accessToken),
        'usersau_refresh_token' => encrypt($refreshToken),
    ]);
    $user = $existingUser;
} else {
    // Create new user
    $user = User::create([
        'name' => $userData['name'],
        'email' => $userData['email'],
        'usersau_id' => $userData['id'],
        'usersau_access_token' => encrypt($accessToken),
        'usersau_refresh_token' => encrypt($refreshToken),
    ]);
}
```

#### 7. Laravel Authentication & Redirect
**Final Steps**:
```php
// Package authenticates user in Laravel
Auth::login($user);

// Redirects to configured URL
return redirect()->to(config('usersau.after_login_url', '/'));
```

### Database State Changes

**Session Table**:
```sql
INSERT INTO sessions (
    id, user_id, ip_address, user_agent, payload, last_activity
) VALUES (
    'session_id', 123, '192.168.1.1', 'Mozilla/5.0...', 
    'serialized_session_data', 1642248600
);
```

**Users Table Updates**:
```sql
-- For new users
INSERT INTO users (name, email, usersau_id, usersau_access_token, usersau_refresh_token)
VALUES ('John Doe', 'john@example.com', 'usersau_12345', 'encrypted_token', 'encrypted_refresh');

-- For existing users  
UPDATE users 
SET usersau_access_token = 'new_encrypted_token',
    usersau_refresh_token = 'new_encrypted_refresh',
    updated_at = NOW()
WHERE usersau_id = 'usersau_12345';
```

### Error Handling Scenarios

#### Invalid State Parameter
**Cause**: CSRF token mismatch or session issues
**Response**: 
```
InvalidStateException: The state parameter is invalid.
```
**Debug**: Check session configuration and cookie settings

#### User Cancellation
**Flow**: User clicks "Cancel" on Users.au authorization screen
**Callback**: 
```
GET /auth/usersau/callback?error=access_denied&error_description=User+cancelled
```
**Package Handling**: Shows user-friendly error message

#### Token Exchange Failure
**Causes**: Invalid client credentials, expired authorization code
**Response**: Network exception with detailed error
**Debug**: Verify `USERSAU_CLIENT_ID`, `USERSAU_CLIENT_SECRET`, and `USERSAU_HOST`

#### Network Connectivity
**Symptoms**: Timeout during token exchange or user profile fetch
**Handling**: Package logs detailed error information
**Resolution**: Check firewall rules and DNS resolution

### Performance Considerations

**Token Storage**: Access tokens are encrypted before database storage
**Session Impact**: OAuth adds ~2-3 additional HTTP requests per login
**Caching**: User profile data can be cached to reduce API calls
**Token Refresh**: Refresh tokens allow seamless token renewal

### Security Features

**State Parameter**: Prevents CSRF attacks during OAuth flow
**Token Encryption**: All OAuth tokens encrypted in database
**Secure Cookies**: Session cookies marked as secure and httponly
**Scope Limitation**: Only requests necessary user data (openid, profile, email)

## Advanced Configuration

### Custom Middleware

Protect routes with Users.au authentication:

```php
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/profile', [ProfileController::class, 'show']);
});
```

### Profile Photo Sync

Enable profile photo synchronization:

```php
// config/usersau.php
'profile_photo_column' => 'profile_photo_url',

// Migration
Schema::table('users', function (Blueprint $table) {
    $table->string('profile_photo_url')->nullable();
});
```

### Custom User Model

Use a custom user model:

```php
// config/usersau.php
'user_model' => App\Models\CustomUser::class,
```

## Docker Integration

For Docker deployments, add environment variables:

```yaml
# docker-compose.yml
services:
  app:
    environment:
      - USERSAU_CLIENT_ID=${USERSAU_CLIENT_ID}
      - USERSAU_CLIENT_SECRET=${USERSAU_CLIENT_SECRET}
      - USERSAU_REDIRECT_URI=${USERSAU_REDIRECT_URI}
      - USERSAU_HOST=${USERSAU_HOST}
```

## API Reference

### AuthController Methods

#### `redirect()`
Initiates OAuth flow by redirecting to Users.au.

**Returns:** `RedirectResponse`

#### `callback()`
Handles OAuth callback and user authentication.

**Process:**
1. Validates OAuth state parameter
2. Exchanges authorization code for tokens
3. Retrieves user data from Users.au
4. Creates or updates local user record
5. Authenticates user in Laravel
6. Redirects to configured URL

**Returns:** `RedirectResponse`

#### `logout()`
Logs out user locally and redirects to Users.au logout.

**Returns:** `RedirectResponse`

#### `account()`
Redirects authenticated users to Users.au account management.

**Returns:** `RedirectResponse`

#### `register()`
Redirects to Users.au registration page.

**Returns:** `RedirectResponse`

## Troubleshooting

### Common Issues

**Invalid State Exception**
- Cause: Session configuration issues
- Solution: Verify session driver and cookie settings

**Client Exception**
- Cause: Invalid OAuth credentials
- Solution: Check `USERSAU_CLIENT_ID`, `USERSAU_CLIENT_SECRET`, and `USERSAU_HOST`

**404 on Callback**
- Cause: Incorrect callback URL
- Solution: Ensure callback URL is `/auth/usersau/callback`

**Migration Errors**
- Cause: User table doesn't exist or conflicting columns
- Solution: Ensure users table exists before running package migrations

### Debug Configuration

For development, enable debug mode:

```env
APP_DEBUG=true
LOG_LEVEL=debug
```

### Logging

The package logs OAuth activities. Review logs for troubleshooting:

```bash
tail -f storage/logs/laravel.log
```

## Security Best Practices

1. **Environment Variables** - Never commit OAuth credentials to version control
2. **HTTPS** - Always use HTTPS for callback URLs in production
3. **Token Security** - OAuth tokens are automatically hidden from serialization
4. **Redirect URI Validation** - Ensure exact match in Users.au application settings
5. **Session Security** - Use secure session configuration in production

## Testing

### Unit Testing

Mock the OAuth flow for testing:

```php
// tests/Feature/AuthTest.php

public function test_oauth_login()
{
    $this->mock(SocialiteProvider::class)
        ->shouldReceive('redirect')
        ->andReturn(redirect('/auth/usersau/redirect'));
        
    $response = $this->get('/login');
    $response->assertRedirect('/auth/usersau/redirect');
}
```

### Integration Testing

Test with a Users.au development instance:

```env
# .env.testing
USERSAU_HOST=https://auth-dev.yourcompany.com
USERSAU_CLIENT_ID=dev_client_id
USERSAU_CLIENT_SECRET=dev_client_secret
```

## Migration Guide

### From Version 1.x

1. Update composer requirements
2. Republish package assets:
   ```bash
   php artisan vendor:publish --provider="Usersau\UsersauLaravelClient\UsersauLaravelClientServiceProvider" --force
   ```
3. Run new migrations:
   ```bash
   php artisan migrate
   ```
4. Update environment variables if needed

## Support

For technical support:

- **GitHub Issues**: [users-au/laravel-client/issues](https://github.com/users-au/laravel-client/issues)
- **Documentation**: [docs.users.au](https://docs.users.au)
- **Email Support**: support@users.au

## Examples

Complete integration examples are available in the package repository:

- Basic Laravel integration
- Multi-tenant applications  
- API-only applications
- Single-page applications (SPA)

## Changelog

See [CHANGELOG.md](https://github.com/users-au/laravel-client/blob/main/CHANGELOG.md) for version history and breaking changes.

## License

The Laravel client package is open-source software licensed under the [MIT License](https://opensource.org/licenses/MIT).