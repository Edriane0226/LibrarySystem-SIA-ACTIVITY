<?php
include "../config/connection.php";
session_start();

// Check if user is logged in
if (!isset($_SESSION['student_id']) && !isset($_SESSION['staff_id'])) {
    echo json_encode(["success" => false, "error" => "Not logged in"]);
    exit;
}

$caller_id = isset($_SESSION['student_id']) ? $_SESSION['student_id'] : $_SESSION['staff_id'];
$caller_type = isset($_SESSION['student_id']) ? 'student' : 'staff';
$receiver_id = $_POST['receiver_id'] ?? '';
$receiver_type = $_POST['receiver_type'] ?? '';

if (!$receiver_id || !$receiver_type) {
    echo json_encode(["success" => false, "error" => "Invalid receiver data"]);
    exit;
}

// Generate unique room ID
$room_id = uniqid("room_", true);

// Insert new call
$stmt = $conn->prepare("INSERT INTO video_calls (caller_id, caller_type, receiver_id, receiver_type, room_id, status) VALUES (?,?,?,?,?, 'pending')");
$stmt->bind_param("sssss", $caller_id, $caller_type, $receiver_id, $receiver_type, $room_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(["success" => true, "call_id" => $stmt->insert_id, "room_id" => $room_id]);
} else {
    echo json_encode(["success" => false, "error" => "Failed to create call"]);
}
