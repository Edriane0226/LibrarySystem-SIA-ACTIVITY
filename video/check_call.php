<?php
include "../config/connection.php";
session_start();

// Check if user is logged in
if (!isset($_SESSION['student_id']) && !isset($_SESSION['staff_id'])) {
    echo json_encode(["incoming" => false]);
    exit;
}

$user_id = isset($_SESSION['student_id']) ? $_SESSION['student_id'] : $_SESSION['staff_id'];
$user_type = isset($_SESSION['student_id']) ? 'student' : 'staff';

// Look for a pending call
$stmt = $conn->prepare("SELECT * FROM video_calls WHERE receiver_id = ? AND receiver_type = ? AND status = 'pending' ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("ss", $user_id, $user_type);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        "incoming" => true,
        "call" => $row
    ]);
} else {
    echo json_encode(["incoming" => false]);
}
