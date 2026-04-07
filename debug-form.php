<?php
/**
 * Debug Form Submission & Database
 */
require_once 'config/database.php';
require_once 'lib/functions.php';
require_once 'lib/auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<h1>❌ Not Logged In</h1>";
    echo "<p>You need to login first to test form submission.</p>";
    echo "<a href='pages/login.php'>Login Here</a>";
    exit;
}

$currentUser = getCurrentUser();

echo "<h1>🔍 Form Submission Debug</h1>";
echo "<p><strong>Current User:</strong> ID: {$currentUser['id']} | Name: {$currentUser['name']}</p>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";

// Check database connection
if ($connection->connect_error) {
    echo "<h2>❌ Database Connection Error</h2>";
    echo "<p>" . $connection->connect_error . "</p>";
} else {
    echo "<h2>✅ Database Connected</h2>";
    echo "<p>Connected to: " . $connection->host_info . "</p>";
}

// Test table structure
echo "<h2>📋 Table Structure Check</h2>";
$result = $connection->query("DESCRIBE pet_reports");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $highlight = ($row['Field'] === 'event_date') ? ' style="background-color: #e8f5e8;"' : '';
        echo "<tr{$highlight}>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ Cannot read table structure: " . $connection->error . "</p>";
}

// Show recent reports
echo "<h2>📊 Recent Reports (Last 10)</h2>";
$query = "SELECT id, user_id, type, pet_name, species, location, event_date, created_at, status
          FROM pet_reports
          ORDER BY created_at DESC
          LIMIT 10";
$result = $connection->query($query);

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>User</th><th>Type</th><th>Pet Name</th><th>Species</th><th>Location</th><th>Event Date</th><th>Created At</th><th>Status</th></tr>";

    while ($row = $result->fetch_assoc()) {
        $isRecent = (strtotime($row['created_at']) > strtotime('-5 minutes'));
        $highlight = $isRecent ? ' style="background-color: #fff3cd;"' : '';
        echo "<tr{$highlight}>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['user_id']}</td>";
        echo "<td>{$row['type']}</td>";
        echo "<td>{$row['pet_name']}</td>";
        echo "<td>{$row['species']}</td>";
        echo "<td>{$row['location']}</td>";
        echo "<td>{$row['event_date']}</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "<td>{$row['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p><em>🟡 Highlighted rows = created in last 5 minutes</em></p>";
} else {
    echo "<p>No reports found or query error: " . $connection->error . "</p>";
}

// Test INSERT simulation
echo "<h2>🧪 Test INSERT Simulation</h2>";
$testData = [
    'user_id' => $currentUser['id'],
    'type' => 'lost',
    'pet_name' => 'Test Debug ' . time(),
    'species' => 'Kucing',
    'location' => 'Jakarta Debug',
    'description' => 'Test insert dari debug page',
    'image_url' => 'debug.jpg',
    'event_date' => date('Y-m-d')
];

$query = "INSERT INTO pet_reports (user_id, type, pet_name, species, location, description, image_url, event_date)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $connection->prepare($query);
if (!$stmt) {
    echo "<p style='color: red;'>❌ Prepare failed: " . $connection->error . "</p>";
} else {
    $stmt->bind_param('ssssssss',
        $testData['user_id'],
        $testData['type'],
        $testData['pet_name'],
        $testData['species'],
        $testData['location'],
        $testData['description'],
        $testData['image_url'],
        $testData['event_date']
    );

    if ($stmt->execute()) {
        $insertId = $stmt->insert_id;
        echo "<p style='color: green;'>✅ Test INSERT berhasil! ID: <strong>$insertId</strong></p>";

        // Verify the inserted data
        $verifyQuery = "SELECT * FROM pet_reports WHERE id = $insertId";
        $verifyResult = $connection->query($verifyQuery);
        if ($verifyResult && $verifyResult->num_rows > 0) {
            $data = $verifyResult->fetch_assoc();
            echo "<p><strong>Data yang tersimpan:</strong></p>";
            echo "<pre>" . print_r($data, true) . "</pre>";
        }
    } else {
        echo "<p style='color: red;'>❌ Execute failed: " . $stmt->error . "</p>";
    }
    $stmt->close();
}

// Check PHP error logs
echo "<h2>📝 PHP Error Check</h2>";
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    $lines = file($error_log);
    $recent_errors = array_slice($lines, -10); // Last 10 lines
    echo "<p><strong>Recent PHP errors (last 10 lines):</strong></p>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border: 1px solid #dee2e6; max-height: 200px; overflow-y: auto;'>";
    foreach ($recent_errors as $error) {
        echo htmlspecialchars($error);
    }
    echo "</pre>";
} else {
    echo "<p>No error log found or accessible.</p>";
}

$connection->close();
?>