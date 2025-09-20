#!/usr/bin/env php
<?php
// Direct Laravel setup for Windows without artisan issues

require_once __DIR__.'/vendor/autoload.php';

// Manually generate APP_KEY
function generateRandomKey($length = 32) {
    return base64_encode(random_bytes($length));
}

$envFile = __DIR__.'/.env';
$envContent = file_get_contents($envFile);

// Check if APP_KEY is empty and generate one
if (strpos($envContent, 'APP_KEY=') !== false && preg_match('/APP_KEY=\s*$/', $envContent)) {
    $newKey = 'base64:'.generateRandomKey();
    $envContent = preg_replace('/APP_KEY=\s*$/', "APP_KEY=$newKey", $envContent);
    file_put_contents($envFile, $envContent);
    echo "✅ Generated APP_KEY: $newKey\n";
} else {
    echo "ℹ️ APP_KEY already exists\n";
}

echo "✅ Laravel configuration setup complete!\n";
echo "📋 Next steps:\n";
echo "   1. Run: php artisan migrate:fresh --seed --seeder=TaskDemoSeeder\n";
echo "   2. Run: php artisan serve\n";
echo "   3. Run: npm run dev\n";