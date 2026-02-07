<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Http\Request;
use App\Models\Complaint;

// Test data sesuai payload frontend
$testData = [
    'judul_laporan' => 'Laporan Kekerasan Verbal di Area Kampus',
    'kronologi' => 'Pada tanggal 15 Januari 2026, sekitar pukul 14.00 WIB, saya melihat terjadi kekerasan verbal antara mahasiswa di area kantin. Pelaku mengucapkan kata-kata kasar dan menyinggung pribadi korban secara berulang kali. Kejadian berlangsung sekitar 10 menit dan membuat korban menangis. Beberapa mahasiswa lain juga menjadi saksi namun tidak berani intervensi.',
    'kategori_kekerasan' => 1,
    'victim_type' => 'other',
    'victim_name' => 'Budi Santoso',
    'victim_relationship' => 'Teman',
    'urgency_level' => 'medium',
    'is_anonymous' => false,
    'incident_date' => '2026-01-15',
    'incident_location' => 'Kantin Gedung A Lantai 1'
];

echo "=== TEST FIELD MAPPING ===\n";

// Test validation rules
echo "1. Testing validation rules...\n";
$rules = Complaint::validationRules();
echo "✓ Validation rules loaded\n";

// Test field mapping
echo "2. Testing field mapping...\n";
$mappedData = Complaint::mapFrontendToBackend($testData);

echo "Frontend -> Backend mapping:\n";
foreach ($testData as $frontendField => $value) {
    $backendField = match($frontendField) {
        'judul_laporan' => 'title',
        'kronologi' => 'chronology', 
        'kategori_kekerasan' => 'violence_category_id',
        default => $frontendField
    };
    
    $mappedValue = $mappedData[$backendField] ?? 'NOT FOUND';
    echo "  {$frontendField} -> {$backendField}: " . ($mappedValue !== 'NOT FOUND' ? '✓' : '✗') . "\n";
}

echo "\n=== SAMPLE REQUEST VALIDATION ===\n";

// Simulate Request::validate()
echo "3. Simulating Request::validate()...\n";

$request = new Request();
foreach ($testData as $key => $value) {
    $request->merge([$key => $value]);
}

try {
    // This would normally be called in controller
    $validatedData = $request->validate(Complaint::validationRules(), Complaint::validationMessages());
    echo "✓ Validation passed\n";
    
    // Show mapped result
    $mappedData = Complaint::mapFrontendToBackend($validatedData);
    echo "\nMapped data ready for database:\n";
    foreach ($mappedData as $key => $value) {
        echo "  {$key}: {$value}\n";
    }
    
} catch (Exception $e) {
    echo "✗ Validation failed: " . $e->getMessage() . "\n";
}

echo "\n=== SAMPLE PAYLOAD EXAMPLE ===\n";

echo "4. Example frontend payload:\n";
echo json_encode($testData, JSON_PRETTY_PRINT) . "\n";

echo "\n5. Expected backend data after mapping:\n";
$expectedBackend = [
    'title' => 'Laporan Kekerasan Verbal di Area Kampus',
    'chronology' => 'Pada tanggal 15 Januari 2026, sekitar pukul 14.00 WIB, saya melihat terjadi kekerasan verbal antara mahasiswa di area kantin. Pelaku mengucapkan kata-kata kasar dan menyinggung pribadi korban secara berulang kali. Kejadian berlangsung sekitar 10 menit dan membuat korban menangis. Beberapa mahasiswa lain juga menjadi saksi namun tidak berani intervensi.',
    'violence_category_id' => 1,
    'victim_type' => 'other',
    'victim_name' => 'Budi Santoso',
    'victim_relationship' => 'Teman',
    'urgency_level' => 'medium',
    'is_anonymous' => false,
    'incident_date' => '2026-01-15',
    'incident_location' => 'Kantin Gedung A Lantai 1'
];

echo json_encode($expectedBackend, JSON_PRETTY_PRINT) . "\n";

echo "\n=== VALIDATION MESSAGES ===\n";
echo "6. Error messages (Indonesian):\n";
$messages = Complaint::validationMessages();
foreach (['judul_laporan.required', 'kronologi.required', 'kategori_kekerasan.exists'] as $key) {
    if (isset($messages[$key])) {
        echo "  {$key}: {$messages[$key]}\n";
    }
}

echo "\n=== TEST COMPLETE ===\n";
