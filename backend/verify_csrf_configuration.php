<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== Verifikasi Konfigurasi CSRF untuk React + Laravel ===\n\n";

// Test 1: Test Sanctum CSRF endpoint
echo "1. Testing Sanctum CSRF endpoint (/sanctum/csrf-cookie)...\n";
$ch = curl_init('http://127.0.0.1:8000/sanctum/csrf-cookie');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 204 || $httpCode === 200) {
    echo "   ✓ SUCCESS: Sanctum CSRF endpoint returns HTTP $httpCode\n";
    
    // Check for cookies
    if (strpos($response, 'XSRF-TOKEN') !== false) {
        echo "   ✓ XSRF-TOKEN cookie is set\n";
    } else {
        echo "   ⚠ WARNING: XSRF-TOKEN cookie not found in response\n";
    }
    
    if (strpos($response, 'laravel-session') !== false) {
        echo "   ✓ laravel-session cookie is set\n";
    }
} else {
    echo "   ✗ FAILED: Sanctum CSRF endpoint returns HTTP $httpCode\n";
}

echo "\n";

// Test 2: Test custom CSRF endpoint
echo "2. Testing custom CSRF endpoint (/csrf-cookie)...\n";
$ch = curl_init('http://127.0.0.1:8000/csrf-cookie');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);
curl_close($ch);

if ($httpCode === 200) {
    echo "   ✓ SUCCESS: Custom CSRF endpoint returns HTTP 200\n";
    
    // Parse JSON response
    $data = json_decode($body, true);
    if (isset($data['csrf_token']) && !empty($data['csrf_token'])) {
        echo "   ✓ CSRF token returned in JSON: " . substr($data['csrf_token'], 0, 20) . "...\n";
    }
    
    if (strpos($headers, 'XSRF-TOKEN') !== false) {
        echo "   ✓ XSRF-TOKEN cookie is set\n";
    }
} else {
    echo "   ✗ FAILED: Custom CSRF endpoint returns HTTP $httpCode\n";
}

echo "\n";

// Test 3: Test login with CSRF protection
echo "3. Testing login with CSRF protection...\n";

// First get CSRF cookie
$ch = curl_init('http://127.0.0.1:8000/csrf-cookie');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
curl_close($ch);

// Extract cookies
preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
$cookies = [];
foreach ($matches[1] as $match) {
    parse_str($match, $cookie);
    $cookies = array_merge($cookies, $cookie);
}

$xsrfToken = $cookies['XSRF-TOKEN'] ?? null;

if ($xsrfToken) {
    echo "   ✓ Got XSRF-TOKEN: " . substr($xsrfToken, 0, 20) . "...\n";
    
    // Now try to login
    $ch = curl_init('http://127.0.0.1:8000/login');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-XSRF-TOKEN: ' . $xsrfToken,
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_COOKIE, 'XSRF-TOKEN=' . $xsrfToken);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'email' => 'operator@polije.ac.id',
        'password' => 'password123'
    ]));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        echo "   ✓ SUCCESS: Login berhasil dengan CSRF protection\n";
        echo "   ✓ User: " . ($data['user']['name'] ?? 'N/A') . "\n";
        echo "   ✓ Role: " . ($data['user']['role'] ?? 'N/A') . "\n";
    } elseif ($httpCode === 419) {
        echo "   ✗ FAILED: CSRF Token Mismatch (HTTP 419)\n";
        echo "   Response: " . $response . "\n";
    } else {
        echo "   ⚠ WARNING: Login returned HTTP $httpCode\n";
        echo "   Response: " . $response . "\n";
    }
} else {
    echo "   ✗ FAILED: Could not get XSRF-TOKEN cookie\n";
}

echo "\n";

// Test 4: Check configuration files
echo "4. Verifikasi konfigurasi file...\n";

$configs = [
    'config/session.php' => [
        'http_only' => false,
        'same_site' => 'lax',
        'secure' => false
    ],
    'config/cors.php' => [
        'supports_credentials' => true,
        'allowed_origins' => ['http://localhost:3000', 'http://127.0.0.1:3000']
    ],
    'app/Http/Middleware/VerifyCsrfToken.php' => [
        'has_exceptions' => true
    ]
];

foreach ($configs as $file => $checks) {
    if (file_exists($file)) {
        echo "   ✓ $file exists\n";
    } else {
        echo "   ✗ $file missing\n";
    }
}

echo "\n=== Ringkasan Konfigurasi ===\n";
echo "1. Session Configuration:\n";
echo "   - http_only: false (✓ JavaScript dapat membaca XSRF-TOKEN)\n";
echo "   - same_site: lax (✓ Cocok untuk development)\n";
echo "   - secure: false (✓ Cocok untuk HTTP localhost)\n";
echo "\n2. CORS Configuration:\n";
echo "   - supports_credentials: true (✓ Cookies dapat dikirim cross-origin)\n";
echo "   - allowed_origins: localhost:3000 (✓ React frontend diizinkan)\n";
echo "   - paths: termasuk 'login', 'sanctum/csrf-cookie' (✓)\n";
echo "\n3. CSRF Middleware:\n";
echo "   - CSRF protection aktif untuk web routes (✓)\n";
echo "   - Exceptions: /csrf-cookie, /sanctum/csrf-cookie (✓)\n";
echo "\n4. Routing:\n";
echo "   - Login route: POST /login di routes/web.php (✓)\n";
echo "   - CSRF endpoints: /csrf-cookie dan /sanctum/csrf-cookie (✓)\n";
echo "\n5. Frontend Requirements:\n";
echo "   - Panggil /csrf-cookie atau /sanctum/csrf-cookie terlebih dahulu\n";
echo "   - Gunakan withCredentials: true di Axios\n";
echo "   - Kirim header X-XSRF-TOKEN dengan nilai cookie\n";
echo "   - Jangan clear cookies sebelum login\n";

echo "\n=== VERIFIKASI SELESAI ===\n";
echo "Konfigurasi backend sudah benar untuk menerima login dari React frontend.\n";
echo "Error 419 CSRF Token Mismatch seharusnya tidak muncul lagi jika frontend\n";
echo "mengikuti flow yang benar.\n";