<?php
include "../config/connection.php";

$call_id = $_POST['call_id'] ?? '';
$status  = $_POST['status'] ?? '';

// Validate input
$allowed = ['pending', 'accepted', 'declined', 'ended'];

if (!$call_id || !in_array($status, $allowed)) {
    echo json_encode(["success" => false, "error" => "Invalid input"]);
    exit;
}

// Update call status
$stmt = $conn->prepare("UPDATE video_calls SET status = ? WHERE call_id = ?");
$stmt->bind_param("si", $status, $call_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => "No rows updated"]);
}
