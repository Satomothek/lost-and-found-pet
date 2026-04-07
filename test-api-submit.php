<?php
/**
 * Simulate Form Submission to API
 */
require_once '../config/database.php';
require_once '../lib/functions.php';
require_once '../lib/auth.php';

// Simulate login
$_SESSION['user_id'] = 1; // Force login for testing
$currentUser = getCurrentUser();

echo "<h1>🧪 API Form Submission Test</h1>";
echo "<p><strong>Simulating user:</strong> ID: {$currentUser['id']} | Name: {$currentUser['name']}</p>";

// Simulate POST data like the form would send
$_POST = [
    'type' => 'lost',
    'pet_name' => 'API Test ' . time(),
    'species' => 'Anjing',
    'location' => 'Jakarta API Test',
    'description' => 'Test submission via API simulation',
    'date' => date('Y-m-d')
];

// Simulate file upload
$_FILES = [
    'image' => [
        'name' => 'test-image.jpg',
        'type' => 'image/jpeg',
        'tmp_name' => '../public/uploads/test-image.jpg', // This file might not exist, but that's ok for test
        'error' => UPLOAD_ERR_OK,
        'size' => 1024
    ]
];

// Include the API logic
echo "<h2>📤 Simulating API Call</h2>";

// Replicate the API logic from reports.php
$method = 'POST';
$action = 'create';

if ($method === 'POST' && $action === 'create') {
    $type = sanitizeInput($_POST['type'] ?? '');
    $petName = sanitizeInput($_POST['pet_name'] ?? '');
    $species = sanitizeInput($_POST['species'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $reportDate = sanitizeInput($_POST['date'] ?? '');

    echo "<h3>📋 Input Data:</h3>";
    echo "<ul>";
    echo "<li>Type: $type</li>";
    echo "<li>Pet Name: $petName</li>";
    echo "<li>Species: $species</li>";
    echo "<li>Location: $location</li>";
    echo "<li>Description: $description</li>";
    echo "<li>Report Date: $reportDate</li>";
    echo "</ul>";

    // Validate
    if (!$type || !$species || !$location || !$description || !$reportDate) {
        echo "<p style='color: red;'>❌ Validation failed: Missing required fields</p>";
        echo "<p>Missing: " . (!$type ? 'type ' : '') . (!$species ? 'species ' : '') . (!$location ? 'location ' : '') . (!$description ? 'description ' : '') . (!$reportDate ? 'date' : '') . "</p>";
    } else {
        echo "<p style='color: green;'>✅ Validation passed</p>";

        if (!in_array($type, ['lost', 'found'])) {
            echo "<p style='color: red;'>❌ Invalid type: $type</p>";
        } else {
            echo "<p style='color: green;'>✅ Type validation passed</p>";

            $today = date('Y-m-d');
            $minDate = date('Y-m-d', strtotime('-7 days'));

            if ($reportDate < $minDate || $reportDate > $today) {
                echo "<p style='color: red;'>❌ Date validation failed: $reportDate (min: $minDate, max: $today)</p>";
            } else {
                echo "<p style='color: green;'>✅ Date validation passed</p>";

                // Handle image upload
                $imageUrl = 'https://via.placeholder.com/600x400?text=Pet+Image';
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    echo "<p>📸 Processing image upload...</p>";
                    $upload = uploadImage($_FILES['image'], '../public/uploads/');
                    if ($upload['success']) {
                        $imageUrl = 'public/uploads/' . $upload['filename'];
                        echo "<p style='color: green;'>✅ Image uploaded: $imageUrl</p>";
                    } else {
                        echo "<p style='color: orange;'>⚠️ Image upload failed: {$upload['error']}, using placeholder</p>";
                    }
                }

                // Insert report
                echo "<h3>💾 Attempting Database Insert</h3>";
                $query = "INSERT INTO pet_reports (user_id, type, pet_name, species, location, description, image_url, event_date)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

                $result = executeQuery($connection, $query, [
                    $currentUser['id'],
                    $type,
                    $petName,
                    $species,
                    $location,
                    $description,
                    $imageUrl,
                    $reportDate
                ]);

                if ($result['success']) {
                    echo "<p style='color: green;'>✅ <strong>INSERT SUCCESS!</strong> Report ID: {$result['insert_id']}</p>";

                    // Verify the data
                    $verifyQuery = "SELECT * FROM pet_reports WHERE id = ?";
                    $verifyResult = fetchOne($connection, $verifyQuery, [$result['insert_id']]);

                    if ($verifyResult) {
                        echo "<h4>📊 Data tersimpan di database:</h4>";
                        echo "<table border='1' cellpadding='5'>";
                        foreach ($verifyResult as $key => $value) {
                            echo "<tr><td><strong>$key</strong></td><td>" . htmlspecialchars($value) . "</td></tr>";
                        }
                        echo "</table>";
                    } else {
                        echo "<p style='color: orange;'>⚠️ Insert berhasil tapi verifikasi gagal</p>";
                    }
                } else {
                    echo "<p style='color: red;'>❌ <strong>INSERT FAILED:</strong> {$result['error']}</p>";
                }
            }
        }
    }
}

$connection->close();
?>