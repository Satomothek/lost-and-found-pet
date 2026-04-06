<?php
$conn = new mysqli('localhost', 'root', '', 'petfounds_db');
if ($conn->connect_error) {
    echo 'DBERR:' . $conn->connect_error;
    exit(1);
}
$res = $conn->query("SELECT id,user_id,type,pet_name,species,location,image_url,created_at FROM pet_reports ORDER BY created_at DESC LIMIT 10");
if (!$res) {
    echo 'QUERYERR:' . $conn->error;
    exit(1);
}
while ($row = $res->fetch_assoc()) {
    echo implode(' | ', array_values($row)) . PHP_EOL;
}
$conn->close();
