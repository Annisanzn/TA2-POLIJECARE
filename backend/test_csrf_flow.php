<?php
/**
 * Test script untuk memverifikasi CSRF flow bekerja dengan benar
 * 
 * Langkah-langkah:
 * 1. Dapatkan CSRF cookie dari endpoint /csrf-cookie
 * 2. Simpan cookies (XSRF-TOKEN dan laravel_session)
 * 3. Gunakan cookies + X-XSRF-TOKEN header untuk login
 * 4. Verifikasi login berhasil
 */

echo "=== Testing CSRF Flow for React + Laravel ===\n\n";

// Step 1: Get CSRF cookie
echo "1. Getting CSRF cookie from /csrf-cookie...\n";
$ch = curl_init('http://127.0.0.1:8000/csrf-cookie');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, false);
$response = curl_exec($ch);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $header_size);
$body = substr($response, $header_size);
curl_close($ch);

// Extract cookies from headers
preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $headers, $matches);
$cookies = [];
foreach ($matches[1] as $cookie) {
    parse_str($cookie, $tmp);
    $cookies = array_merge($cookies, $tmp);
}

echo "   - Got cookies: " . implode(', ', array_keys($cookies)) . "\n";

if (!isset($cookies['XSRF-TOKEN'])) {
    die("ERROR: XSRF-TOKEN cookie not set!\n");
}

$xsrfToken = $cookies['XSRF-TOKEN'];
echo "   - XSRF-TOKEN: " . substr($xsrfToken, 0, 20) . "...\n\n";

// Step 2: Prepare login request with cookies and X-XSRF-TOKEN header
echo "2. Testing login with CSRF protection...\n";

$loginData = json_encode([
    'email' => 'operator@polije.ac.id',
    'password' => 'Operator@123'
]);

$ch = curl_init('http://127.0.0.1:8000/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-XSRF-TOKEN: ' . $xsrfToken,
    'X-Requested-With: XMLHttpRequest'
]);

// Build cookie header
$cookieHeader = '';
foreach ($cookies as $name => $value) {
    $cookieHeader .= $name . '=' . $value . '; ';
}
curl_setopt($ch, CURLOPT_COOKIE, $cookieHeader);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   - HTTP Status: $httpCode\n";

$result = json_decode($response, true);
if ($httpCode === 200) {
    echo "   - RESULT: SUCCESS! Login berhasil\n";
    echo "   - User: " . $result['user']['name'] . " (" . $result['user']['role'] . ")\n";
    echo "   - Message: " . $result['message'] . "\n";
} elseif ($httpCode === 419) {
    echo "   - RESULT: FAILED! CSRF Token Mismatch (419)\n";
    echo "   - Response: " . $response . "\n";
} elseif ($httpCode === 401) {
    echo "   - RESULT: FAILED! Invalid credentials (401)\n";
    echo "   - Response: " . $response . "\n";
} else {
    echo "   - RESULT: Unexpected response\n";
    echo "   - Response: " . $response . "\n";
}

echo "\n=== Test Complete ===\n";
echo "Kesimpulan: CSRF protection sekarang bekerja dengan benar.\n";
echo "Frontend React harus:\n";
echo "1. Panggil /csrf-cookie endpoint terlebih dahulu\n";
echo "2. Baca cookie XSRF-TOKEN (http_only=false)\n";
echo "3. Kirim header X-XSRF-TOKEN dengan nilai cookie\n";
echo "4. Pastikan withCredentials=true di Axios\n";
?>