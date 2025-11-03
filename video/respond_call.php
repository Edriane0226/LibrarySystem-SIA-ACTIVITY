<?php
include "../config/connection.php";
session_start();

// Check if user is logged in
if (!isset($_SESSION['student_id']) && !isset($_SESSION['staff_id'])) {
    echo json_encode(["success" => false, "error" => "Not logged in"]);
    exit;
}

$call_id = $_POST['call_id'] ?? '';
$action = $_POST['action'] ?? ''; // "accept" or "decline"

if (!in_array($action, ['accept','decline'])) {
    echo json_encode(["success" => false, "error" => "Invalid action"]);
    exit;
}

$status = $action === 'accept' ? "accepted" : "declined";
$stmt = $conn->prepare("UPDATE video_calls SET status = ? WHERE call_id = ?");
$stmt->bind_param("si", $status, $call_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(["success" => true, "status" => $status]);
} else {
    echo json_encode(["success" => false, "error" => "Failed to update call status"]);
}
