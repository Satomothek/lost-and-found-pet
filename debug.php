<?php
/**
 * Debug Database & Query Test
 */
require_once 'config/database.php';
require_once 'lib/functions.php';

// Test kolom pet_reports
$query = "DESCRIBE pet_reports";
$result = $connection->query($query);

echo "<h1>Struktur Tabel pet_reports</h1>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . $row['Default'] . "</td>";
    echo "<td>" . $row['Extra'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Test INSERT query dengan dummy data
echo "<h2>Testing INSERT Query</h2>";

$testQuery = "INSERT INTO pet_reports 
(user_id, type, pet_name, species, location, description, image_url, event_date) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $connection->prepare($testQuery);

if (!$stmt) {
    echo "<p style='color: red;'><strong>Error Prepare:</strong> " . $connection->error . "</p>";
} else {
    $user_id = 1;
    $type = 'lost';
    $pet_name = 'Test Kucing Debug ' . time();
    $species = 'Kucing';
    $location = 'Jakarta';
    $description = 'Kucing hilang test - debug';
    $image_url = 'test.jpg';
    $event_date = date('Y-m-d'); // Hari ini
    
    // Note: type 's' untuk semua karena $user_id akan diconvert ke string
    $stmt->bind_param('ssssssss', $user_id, $type, $pet_name, $species, $location, $description, $image_url, $event_date);
    
    if (!$stmt->execute()) {
        echo "<p style='color: red;'><strong>Error Execute:</strong> " . $stmt->error . "</p>";
    } else {
        $insertId = $stmt->insert_id;
        echo "<p style='color: green;'><strong>✓ Insert Berhasil!</strong> Inserted ID: <strong>" . $insertId . "</strong></p>";
        
        // Verifikasi data dengan 3 detik delay untuk memastikan commit
        sleep(1);
        
        $verifyQuery = "SELECT * FROM pet_reports WHERE id = " . intval($insertId);
        $verifyResult = $connection->query($verifyQuery);
        
        if ($verifyResult && $verifyResult->num_rows > 0) {
            $data = $verifyResult->fetch_assoc();
            echo "<p style='color: green;'><strong>✓ Data Tersimpan di Database:</strong></p>";
            echo "<table border='1' cellpadding='5' style='margin-top: 10px;'>";
            foreach ($data as $key => $value) {
                echo "<tr><td><strong>$key</strong></td><td>" . htmlspecialchars($value) . "</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: red;'><strong>✗ Data tidak ditemukan setelah insert</strong></p>";
            echo "<p>Debug: Query executed, last error: " . $connection->error . "</p>";
        }
    }
    
    $stmt->close();
}

// Tampilkan recent 5 laporan
echo "<h2>Recent 5 Laporan di Database</h2>";
$recentQuery = "SELECT id, user_id, type, pet_name, species, location, event_date, created_at 
                FROM pet_reports 
                ORDER BY created_at DESC 
                LIMIT 5";
$recentResult = $connection->query($recentQuery);

if ($recentResult && $recentResult->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>User</th><th>Type</th><th>Pet Name</th><th>Species</th><th>Location</th><th>Event Date</th><th>Created At</th></tr>";
    
    while ($row = $recentResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . $row['type'] . "</td>";
        echo "<td>" . $row['pet_name'] . "</td>";
        echo "<td>" . $row['species'] . "</td>";
        echo "<td>" . $row['location'] . "</td>";
        echo "<td>" . $row['event_date'] . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Tidak ada laporan</p>";
}

$connection->close();
?>
