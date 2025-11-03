<?php
include "../config/connection.php";

$call_id = $_GET['call_id'];
$last_id = $_GET['last_id'] ?? 0;

$sql = "SELECT * FROM call_signaling WHERE call_id=? AND signaling_id>? ORDER BY signaling_id ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $call_id, $last_id);
$stmt->execute();
$result = $stmt->get_result();

$signals = [];
while ($row = $result->fetch_assoc()) {
    $signals[] = $row;
}

echo json_encode(["signals" => $signals]);
