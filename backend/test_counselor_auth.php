<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;

echo "=== Test Counselor Authentication & Role-Based Access ===\n\n";

// Test 1: User with role 'user' should have access
echo "Test 1: User with role 'user' accessing counselors\n";
try {
    // Mock user with role 'user'
    $user = new \stdClass();
    $user->id = 1;
    $user->role = 'user';
    
    $request = Request::create('/api/counselors', 'GET');
    $request->setUserResolver(function () use ($user) {
        return $user;
    });
    
    $controller = new \App\Http\Controllers\Api\CounselorController();
    $response = $controller->index($request);
    
    echo "✅ Status Code: " . $response->getStatusCode() . "\n";
    $data = json_decode($response->getContent(), true);
    
    if ($data['status'] === 'success') {
        echo "✅ User with role 'user' can access counselors\n";
        echo "   Found " . count($data['data']) . " counselors\n";
        if (!empty($data['data'])) {
            $counselor = $data['data'][0];
            echo "   Sample counselor: " . $counselor['name'] . " (ID: " . $counselor['id'] . ")\n";
            // Verify only id and name fields are returned
            $fields = array_keys($counselor);
            echo "   Returned fields: " . implode(', ', $fields) . "\n";
        }
    } else {
        echo "❌ Unexpected response: " . $data['message'] . "\n";
    }
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: User with role 'operator' should be denied
echo "Test 2: User with role 'operator' accessing counselors\n";
try {
    // Mock user with role 'operator'
    $operator = new \stdClass();
    $operator->id = 2;
    $operator->role = 'operator';
    
    $request2 = Request::create('/api/counselors', 'GET');
    $request2->setUserResolver(function () use ($operator) {
        return $operator;
    });
    
    $response2 = $controller->index($request2);
    
    echo "✅ Status Code: " . $response2->getStatusCode() . " (expected 403)\n";
    $data2 = json_decode($response2->getContent(), true);
    
    if ($response2->getStatusCode() === 403 && $data2['status'] === 'error') {
        echo "✅ Operator correctly denied access\n";
        echo "   Message: " . $data2['message'] . "\n";
    } else {
        echo "❌ Operator should be denied access\n";
    }
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: User with role 'konselor' should be denied
echo "Test 3: User with role 'konselor' accessing counselors\n";
try {
    // Mock user with role 'konselor'
    $konselor = new \stdClass();
    $konselor->id = 3;
    $konselor->role = 'konselor';
    
    $request3 = Request::create('/api/counselors', 'GET');
    $request3->setUserResolver(function () use ($konselor) {
        return $konselor;
    });
    
    $response3 = $controller->index($request3);
    
    echo "✅ Status Code: " . $response3->getStatusCode() . " (expected 403)\n";
    $data3 = json_decode($response3->getContent(), true);
    
    if ($response3->getStatusCode() === 403 && $data3['status'] === 'error') {
        echo "✅ Counselor correctly denied access\n";
        echo "   Message: " . $data3['message'] . "\n";
    } else {
        echo "❌ Counselor should be denied access\n";
    }
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Unauthenticated user should be denied
echo "Test 4: Unauthenticated user accessing counselors\n";
try {
    // Mock unauthenticated request (no user)
    $request4 = Request::create('/api/counselors', 'GET');
    $request4->setUserResolver(function () {
        return null;
    });
    
    $response4 = $controller->index($request4);
    
    echo "✅ Status Code: " . $response4->getStatusCode() . " (expected 403)\n";
    $data4 = json_decode($response4->getContent(), true);
    
    if ($response4->getStatusCode() === 403 && $data4['status'] === 'error') {
        echo "✅ Unauthenticated user correctly denied access\n";
        echo "   Message: " . $data4['message'] . "\n";
    } else {
        echo "❌ Unauthenticated user should be denied access\n";
    }
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 5: Verify counselors exist in database
echo "Test 5: Verify counselors exist in database\n";
try {
    $counselors = \App\Models\User::where('role', 'konselor')->get(['id', 'name']);
    echo "✅ Found " . $counselors->count() . " counselors in database\n";
    
    if ($counselors->count() > 0) {
        foreach ($counselors as $counselor) {
            echo "   - " . $counselor->name . " (ID: " . $counselor->id . ")\n";
        }
    } else {
        echo "ℹ️  No counselors found in database\n";
    }
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n✓ All authentication tests completed\n";
