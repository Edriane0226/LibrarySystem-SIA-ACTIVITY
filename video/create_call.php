<?php
include "../config/connection.php";
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['student_id']) && !isset($_SESSION['staff_id'])) {
    echo json_encode(["success" => false, "error" => "Not logged in", "debug" => "No session found"]);
    exit;
}

// Determine caller info
$caller_id = isset($_SESSION['student_id']) ? $_SESSION['student_id'] : $_SESSION['staff_id'];
$caller_type = isset($_SESSION['student_id']) ? 'student' : 'staff';
$receiver_id = $_POST['receiver_id'] ?? '';
$receiver_type = $_POST['receiver_type'] ?? '';

// Debug information
$debug_info = [
    "caller_id" => $caller_id,
    "caller_type" => $caller_type,
    "receiver_id" => $receiver_id,
    "receiver_type" => $receiver_type,
    "session_data" => $_SESSION
];

if (!$receiver_id || !$receiver_type) {
    echo json_encode(["success" => false, "error" => "Missing receiver data", "debug" => $debug_info]);
    exit;
}

// Check database connection
if ($conn->connect_error) {
    echo json_encode(["success" => false, "error" => "Database connection failed", "debug" => $conn->connect_error]);
    exit;
}

// Unique room ID (used by WebRTC peer connection)
$room_id = uniqid("room_", true);

// Insert into DB
$stmt = $conn->prepare("INSERT INTO video_calls (caller_id, caller_type, receiver_id, receiver_type, room_id, status) 
                        VALUES (?, ?, ?, ?, ?, 'pending')");

if (!$stmt) {
    echo json_encode(["success" => false, "error" => "Prepare statement failed", "debug" => $conn->error]);
    exit;
}

$stmt->bind_param("sssss", $caller_id, $caller_type, $receiver_id, $receiver_type, $room_id);
$execute_result = $stmt->execute();

if (!$execute_result) {
    echo json_encode(["success" => false, "error" => "Execute failed", "debug" => $stmt->error]);
    exit;
}

if ($stmt->affected_rows > 0) {
    echo json_encode(["success" => true, "call_id" => $stmt->insert_id, "room_id" => $room_id, "debug" => $debug_info]);
} else {
    echo json_encode(["success" => false, "error" => "Database insert failed", "debug" => $debug_info]);
}
