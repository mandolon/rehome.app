# PowerShell Development Environment Setup Script
# PreConstruct Platform - Windows Setup

param(
    [switch]$SkipDependencyCheck,
    [string]$DatabasePassword = "yourpassword"
)

Write-Host "🚀 PreConstruct Development Environment Setup" -ForegroundColor Cyan
Write-Host "=" * 50

# Function to check if a command exists
function Test-CommandExists {
    param([string]$Command)
    try {
        Get-Command $Command -ErrorAction Stop | Out-Null
        return $true
    }
    catch {
        return $false
    }
}

# Function to write colored output
function Write-Status {
    param([string]$Message, [string]$Status = "Info")
    switch ($Status) {
        "Success" { Write-Host "✅ $Message" -ForegroundColor Green }
        "Warning" { Write-Host "⚠️  $Message" -ForegroundColor Yellow }
        "Error"   { Write-Host "❌ $Message" -ForegroundColor Red }
        "Info"    { Write-Host "ℹ️  $Message" -ForegroundColor Blue }
        default   { Write-Host $Message }
    }
}

# Check required dependencies
Write-Host "`n🔍 Checking Dependencies..." -ForegroundColor Yellow

$dependencies = @{
    "php" = "PHP 8.2+"
    "composer" = "Composer"
    "psql" = "PostgreSQL"
    "redis-cli" = "Redis"
    "node" = "Node.js"
    "npm" = "npm"
}

$missingDeps = @()

foreach ($dep in $dependencies.Keys) {
    if (Test-CommandExists $dep) {
        Write-Status "$($dependencies[$dep]) found" "Success"
    } else {
        Write-Status "$($dependencies[$dep]) not found in PATH" "Error"
        $missingDeps += $dep
    }
}

# If dependencies are missing, provide guidance
if ($missingDeps.Count -gt 0 -and -not $SkipDependencyCheck) {
    Write-Host "`n❌ Missing Dependencies Detected!" -ForegroundColor Red
    Write-Host "The following tools are required but not found in PATH:" -ForegroundColor Yellow
    foreach ($dep in $missingDeps) {
        Write-Host "  - $($dependencies[$dep])" -ForegroundColor Red
    }
    
    Write-Host "`n📖 Setup Instructions:" -ForegroundColor Cyan
    Write-Host "1. Install missing dependencies using Chocolatey (run as Administrator):"
    Write-Host "   choco install php composer postgresql redis-64 -y" -ForegroundColor Green
    Write-Host "   refreshenv" -ForegroundColor Green
    Write-Host "`n2. Or follow the detailed manual installation guide:"
    Write-Host "   See docs/windows-setup.md for complete instructions" -ForegroundColor Green
    Write-Host "`n3. After installation, restart your terminal and run this script again."
    
    Write-Host "`nTo bypass this check (if tools are installed but not in PATH), use:" -ForegroundColor Yellow
    Write-Host "   .\scripts\setup-dev.ps1 -SkipDependencyCheck" -ForegroundColor Green
    
    exit 1
}

Write-Host "`n⚙️  Setting up development environment..." -ForegroundColor Yellow

# Check if .env exists, if not copy from example
Write-Host "`n🔧 Configuring Environment File..."
if (-not (Test-Path ".env")) {
    if (Test-Path ".env.example") {
        Copy-Item ".env.example" ".env" -Force
        Write-Status ".env file created from .env.example" "Success"
    } else {
        Write-Status ".env.example file not found!" "Error"
        exit 1
    }
} else {
    Write-Status ".env file already exists" "Info"
}

# Generate application key if needed
Write-Host "`n🔑 Checking Application Key..."
$envContent = Get-Content ".env" -Raw
if ($envContent -notmatch "APP_KEY=base64:") {
    if (Test-CommandExists "php") {
        Write-Status "Generating application key..." "Info"
        & php artisan key:generate
        Write-Status "Application key generated" "Success"
    } else {
        Write-Status "Cannot generate app key - PHP not available" "Error"
    }
} else {
    Write-Status "Application key already set" "Success"
}

# Update .env with database and cache configuration
Write-Host "`n🗃️  Configuring Database and Cache Settings..."

$envUpdates = @{
    "DB_CONNECTION" = "pgsql"
    "DB_HOST" = "127.0.0.1"
    "DB_PORT" = "5432"
    "DB_DATABASE" = "preconstruct"
    "DB_USERNAME" = "preconstruct_user"
    "DB_PASSWORD" = $DatabasePassword
    "CACHE_DRIVER" = "redis"
    "SESSION_DRIVER" = "redis"
    "QUEUE_CONNECTION" = "redis"
    "REDIS_HOST" = "127.0.0.1"
    "REDIS_PASSWORD" = "null"
    "REDIS_PORT" = "6379"
    "VECTOR_STORE" = "pgvector"
    "POSTGRES_VECTOR_SCHEMA" = "public"
}

$envContent = Get-Content ".env" -Raw

foreach ($key in $envUpdates.Keys) {
    $value = $envUpdates[$key]
    $pattern = "^$key=.*"
    $replacement = "$key=$value"
    
    if ($envContent -match $pattern) {
        $envContent = $envContent -replace $pattern, $replacement
        Write-Status "Updated $key" "Info"
    } else {
        $envContent += "`n$replacement"
        Write-Status "Added $key" "Info"
    }
}

Set-Content ".env" $envContent
Write-Status "Environment configuration updated" "Success"

# Install Composer dependencies
Write-Host "`n📦 Installing PHP Dependencies..."
if (Test-CommandExists "composer") {
    & composer install --no-interaction --prefer-dist --optimize-autoloader
    if ($LASTEXITCODE -eq 0) {
        Write-Status "Composer dependencies installed" "Success"
    } else {
        Write-Status "Composer install failed" "Error"
    }
} else {
    Write-Status "Skipping composer install - Composer not available" "Warning"
}

# Check database connection and run migrations
Write-Host "`n🗄️  Database Setup..."
if (Test-CommandExists "php") {
    Write-Status "Checking database connection..." "Info"
    
    # Test database connection
    $migrateStatus = & php artisan migrate:status 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Status "Database connection successful" "Success"
        Write-Host "Migration status:" -ForegroundColor Blue
        Write-Host $migrateStatus -ForegroundColor Gray
        
        # Ask user if they want to run migrations
        $runMigrations = Read-Host "`nRun database migrations? (y/N)"
        if ($runMigrations -eq "y" -or $runMigrations -eq "Y") {
            & php artisan migrate
            if ($LASTEXITCODE -eq 0) {
                Write-Status "Database migrations completed" "Success"
            } else {
                Write-Status "Database migrations failed" "Error"
            }
        }
    } else {
        Write-Status "Database connection failed" "Error"
        Write-Host "Error details:" -ForegroundColor Red
        Write-Host $migrateStatus -ForegroundColor Red
        Write-Host "`n💡 Make sure PostgreSQL is running and database 'preconstruct' exists" -ForegroundColor Yellow
        Write-Host "See docs/windows-setup.md for database setup instructions" -ForegroundColor Yellow
    }
} else {
    Write-Status "Skipping database setup - PHP not available" "Warning"
}

# Install Node.js dependencies
Write-Host "`n🎨 Installing Node.js Dependencies..."
if (Test-CommandExists "npm") {
    & npm install
    if ($LASTEXITCODE -eq 0) {
        Write-Status "Node.js dependencies installed" "Success"
    } else {
        Write-Status "npm install failed" "Error"
    }
} else {
    Write-Status "Skipping npm install - npm not available" "Warning"
}

# Start development servers
Write-Host "`n🚀 Starting Development Servers..." -ForegroundColor Yellow

$startServers = Read-Host "Start Laravel and Vite development servers? (Y/n)"
if ($startServers -ne "n" -and $startServers -ne "N") {
    
    # Start Laravel server in background
    if (Test-CommandExists "php") {
        Write-Status "Starting Laravel server..." "Info"
        $laravelJob = Start-Process powershell -ArgumentList "-NoExit", "-Command", "php artisan serve" -PassThru
        Start-Sleep 2
        Write-Status "Laravel server started (PID: $($laravelJob.Id))" "Success"
    }
    
    # Start Vite dev server in background
    if (Test-CommandExists "npm") {
        Write-Status "Starting Vite dev server..." "Info"
        $viteJob = Start-Process powershell -ArgumentList "-NoExit", "-Command", "npm run dev" -PassThru
        Start-Sleep 2
        Write-Status "Vite dev server started (PID: $($viteJob.Id))" "Success"
    }
    
    Write-Host "`n🎉 Development Environment Ready!" -ForegroundColor Green
    Write-Host "=" * 50
    Write-Host "🌐 Application URLs:" -ForegroundColor Cyan
    Write-Host "   Laravel:  http://127.0.0.1:8000" -ForegroundColor Green
    Write-Host "   Vite:     http://127.0.0.1:5173" -ForegroundColor Green
    Write-Host "   API Health: http://127.0.0.1:8000/api/health" -ForegroundColor Green
    Write-Host "`n📝 Notes:" -ForegroundColor Yellow
    Write-Host "   - Two new PowerShell windows opened for the servers"
    Write-Host "   - Close those windows to stop the servers"
    Write-Host "   - Check server logs in their respective windows"
    Write-Host "`n🛠️  Troubleshooting:" -ForegroundColor Yellow
    Write-Host "   - If ports are in use, servers will try alternative ports"
    Write-Host "   - Check docs/windows-setup.md for detailed setup help"
    Write-Host "   - Verify database connection if migrations failed"
    
} else {
    Write-Host "`n✅ Setup Complete!" -ForegroundColor Green
    Write-Host "Run these commands manually to start development:" -ForegroundColor Yellow
    Write-Host "   php artisan serve" -ForegroundColor Green
    Write-Host "   npm run dev" -ForegroundColor Green
}

Write-Host "`n🎯 Next Steps:" -ForegroundColor Cyan
Write-Host "1. Visit http://127.0.0.1:8000 to access the application"
Write-Host "2. Check API health at http://127.0.0.1:8000/api/health"
Write-Host "3. Review logs if you encounter any issues"
Write-Host "4. See docs/windows-setup.md for additional configuration"

Write-Host "`n🏁 Setup script completed!" -ForegroundColor Green