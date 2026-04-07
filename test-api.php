<?php
/**
 * Test API Response
 */
require_once 'lib/auth.php';
require_once 'config/database.php';
require_once 'lib/functions.php';

// Check login
requireLogin();
$currentUser = getCurrentUser();

echo "<h1>Testing API GET Response</h1>";
echo "<p><strong>Current User:</strong> ID: " . $currentUser['id'] . " | Name: " . $currentUser['name'] . "</p>";

// Test GET reports API format
$query = "SELECT pr.*, users.name as author, users.avatar_url as authorImg,
          (SELECT COUNT(*) FROM likes WHERE report_id = pr.id) as likes,
          (SELECT COUNT(*) FROM likes WHERE report_id = pr.id AND user_id = ?) as isLiked
          FROM pet_reports pr
          JOIN users ON pr.user_id = users.id
          WHERE pr.status = 'active'
          ORDER BY pr.created_at DESC 
          LIMIT 3";

$params = [$currentUser['id']];
$reports = fetchAll($connection, $query, $params);

if ($reports === false) {
    echo "<p style='color: red;'><strong>Error fetching reports</strong></p>";
} else {
    echo "<h2>Sample Reports (Last 3):</h2>";
    
    if (empty($reports)) {
        echo "<p>No reports found</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>";
        echo "<th>ID</th>";
        echo "<th>Type</th>";
        echo "<th>Pet Name</th>";
        echo "<th>Species</th>";
        echo "<th>Event Date</th>";
        echo "<th>Created At</th>";
        echo "<th>Location</th>";
        echo "<th>Author</th>";
        echo "</tr>";
        
        foreach ($reports as $report) {
            echo "<tr>";
            echo "<td>" . $report['id'] . "</td>";
            echo "<td>" . $report['type'] . "</td>";
            echo "<td>" . $report['pet_name'] . "</td>";
            echo "<td>" . $report['species'] . "</td>";
            echo "<td>" . (!empty($report['event_date']) ? $report['event_date'] : '<em>NULL</em>') . "</td>";
            echo "<td>" . $report['created_at'] . "</td>";
            echo "<td>" . $report['location'] . "</td>";
            echo "<td>" . $report['author'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Test date formatting
        echo "<h3>Date Formatting Test:</h3>";
        foreach ($reports as $report) {
            $dateToFormat = !empty($report['event_date']) ? $report['event_date'] : $report['created_at'];
            try {
                $dateObj = new DateTime($dateToFormat);
                $formatted = $dateObj->format('d/m/Y');
                echo "<p>ID {$report['id']}: event_date={$report['event_date']}, formatted={$formatted}</p>";
            } catch (Exception $e) {
                echo "<p style='color: red;'>ID {$report['id']}: Error - " . $e->getMessage() . "</p>";
            }
        }
    }
}

$connection->close();
?>
