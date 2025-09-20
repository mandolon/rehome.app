<?php
$env = __DIR__ . '/../.env';
$key = 'base64:'.base64_encode(random_bytes(32));
$c = file_get_contents($env);
if (!preg_match('/^APP_KEY=/m', $c)) { 
    $c .= PHP_EOL."APP_KEY={$key}".PHP_EOL; 
} else { 
    $c = preg_replace('/^APP_KEY=.*$/m', "APP_KEY={$key}", $c); 
}
file_put_contents($env, $c);
echo "APP_KEY set\n";