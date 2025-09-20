# Windows Development Environment Setup

This guide helps you set up the complete development environment for the PreConstruct platform on Windows.

## Quick Install with Chocolatey (Recommended)

If you have [Chocolatey](https://chocolatey.org/install) installed, run this command in an **Administrator** PowerShell:

```powershell
choco install php composer postgresql redis-64 -y
refreshenv
```

**Important**: Ensure PHP version is 8.2 or higher after installation.

## Manual Installation Steps

### 1. PHP (8.2+)

**Option A: Chocolatey**
```powershell
choco install php -y
```

**Option B: Manual**
1. Download PHP 8.2+ from [windows.php.net](https://windows.php.net/download/)
2. Extract to `C:\php`
3. Add `C:\php` to your system PATH
4. Copy `php.ini-development` to `php.ini`

### 2. Composer

**Option A: Chocolatey**
```powershell
choco install composer -y
```

**Option B: Manual**
1. Download from [getcomposer.org](https://getcomposer.org/download/)
2. Run the installer
3. Verify with `composer --version`

### 3. PostgreSQL with pgvector

**Option A: Chocolatey**
```powershell
choco install postgresql -y
```

**Option B: Manual**
1. Download PostgreSQL 12+ from [postgresql.org](https://www.postgresql.org/download/windows/)
2. Run the installer (remember your postgres password)
3. Install pgvector extension (see below)

#### PostgreSQL Setup Steps

1. **Open SQL Shell (psql)** or use full path: `"C:\Program Files\PostgreSQL\16\bin\psql.exe"`

2. **Create database and user:**
   ```sql
   CREATE DATABASE preconstruct;
   CREATE USER preconstruct_user WITH PASSWORD 'yourpassword';
   GRANT ALL PRIVILEGES ON DATABASE preconstruct TO preconstruct_user;
   ```

3. **Connect to the new database:**
   ```sql
   \c preconstruct
   ```

4. **Enable pgvector extension:**
   ```sql
   CREATE EXTENSION IF NOT EXISTS vector;
   ```

#### If pgvector Extension Fails

If you get an error like "extension 'vector' is not available":

1. **Install pgvector via StackBuilder:**
   - Find "Application Stack Builder" in your Start menu (installed with PostgreSQL)
   - Select your PostgreSQL version/instance
   - Navigate to "Spatial Extensions" → "pgvector"
   - Install the extension
   
2. **Alternative: Manual pgvector installation:**
   - Download pgvector from [GitHub](https://github.com/pgvector/pgvector/releases)
   - Follow Windows compilation instructions or use pre-compiled binaries
   
3. **Retry the extension creation:**
   ```sql
   \c preconstruct
   CREATE EXTENSION IF NOT EXISTS vector;
   ```

### 4. Redis

**Option A: Chocolatey**
```powershell
choco install redis-64 -y
```

**Option B: Manual**
1. Download Redis for Windows from [GitHub](https://github.com/microsoftarchive/redis/releases)
2. Extract and run `redis-server.exe`
3. For service installation, run as Administrator:
   ```powershell
   redis-server --service-install
   redis-server --service-start
   ```

#### Redis Verification
```powershell
redis-cli PING
# Should return: PONG
```

## PHP Extension Configuration

### Enable PostgreSQL Extensions

1. **Find your php.ini file:**
   ```powershell
   php --ini
   ```

2. **Edit php.ini and uncomment these lines:**
   ```ini
   extension=pdo_pgsql
   extension=pgsql
   ```

3. **Restart your terminal and verify:**
   ```powershell
   php -m | findstr /i pgsql
   ```
   Should show: `pdo_pgsql` and `pgsql`

### Required PHP Extensions

Ensure these extensions are enabled in php.ini:
```ini
extension=curl
extension=fileinfo
extension=gd
extension=mbstring
extension=openssl
extension=pdo_pgsql
extension=pgsql
extension=tokenizer
extension=xml
extension=zip
```

## Environment Configuration

### .env Configuration for PostgreSQL + Redis

Update your `.env` file with these database and cache settings:

```env
# Database Configuration
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=preconstruct
DB_USERNAME=preconstruct_user
DB_PASSWORD=yourpassword

# Cache & Session Configuration
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Vector Database Configuration
VECTOR_STORE=pgvector
POSTGRES_VECTOR_SCHEMA=public
```

## Final Verification

After installation, verify all tools are working:

```powershell
# Check versions
php -v
composer -V
psql --version
redis-cli --version
node -v
npm -v

# Test database connection
php artisan migrate:status

# Test Redis connection
redis-cli PING
```

## Automated Setup Script

After installing the base tools, you can use our automated setup script:

```powershell
.\scripts\setup-dev.ps1
```

This script will:
- Configure your `.env` file
- Install PHP and Node dependencies
- Run database migrations
- Start both Laravel and Vite development servers

## Development URLs

Once everything is running:
- **Laravel Application**: http://127.0.0.1:8000
- **Vite Dev Server**: http://127.0.0.1:5173

## Troubleshooting

### Common Issues

1. **"Class 'PDO' not found"**
   - Enable `extension=pdo` in php.ini
   
2. **"could not find driver"**
   - Enable `extension=pdo_pgsql` in php.ini
   
3. **Redis connection refused**
   - Start Redis service: `redis-server --service-start`
   
4. **Port 8000 already in use**
   - Use a different port: `php artisan serve --port=8001`

### Getting Help

If you encounter issues:
1. Check that all PATH variables are set correctly
2. Restart your terminal after installation
3. Verify PHP extensions with `php -m`
4. Check PostgreSQL service is running in Services.msc
5. Test Redis with `redis-cli PING`

## Next Steps

After setup is complete:
1. Run the development servers with `.\scripts\setup-dev.ps1`
2. Visit http://127.0.0.1:8000 to access the application
3. Check the API health at http://127.0.0.1:8000/api/health