<?php
include "../config/connection.php";
session_start();

$room_id = $_REQUEST['room_id'] ?? '';
$user_id = $_REQUEST['user_id'] ?? '';
$user_type = $_REQUEST['user_type'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $offer = $_POST['offer'] ?? '';
    $answer = $_POST['answer'] ?? '';
    $candidate = $_POST['candidate'] ?? '';
    
    if ($offer) {
        $stmt = $conn->prepare("INSERT INTO call_signaling (room_id, user_id, user_type, signal_type, payload) VALUES (?,?,?,'offer',?)");
        $stmt->bind_param("ssss", $room_id, $user_id, $user_type, $offer);
        $stmt->execute();
    }
    
    if ($answer) {
        $stmt = $conn->prepare("INSERT INTO call_signaling (room_id, user_id, user_type, signal_type, payload) VALUES (?,?,?,'answer',?)");
        $stmt->bind_param("ssss", $room_id, $user_id, $user_type, $answer);
        $stmt->execute();
    }
    
    if ($candidate) {
        $stmt = $conn->prepare("INSERT INTO call_signaling (room_id, user_id, user_type, signal_type, payload) VALUES (?,?,?,'candidate',?)");
        $stmt->bind_param("ssss", $room_id, $user_id, $user_type, $candidate);
        $stmt->execute();
    }
    
    echo json_encode(["success" => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare("SELECT signal_type, payload FROM call_signaling WHERE room_id=? AND (user_id<>? OR user_type<>?) ORDER BY signaling_id ASC");
    $stmt->bind_param("sss", $room_id, $user_id, $user_type);
    $stmt->execute();
    $result = $stmt->get_result();
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $message = json_decode($row['payload'], true);
        $message['type'] = $row['signal_type'];
        $messages[] = $message;
    }
    echo json_encode($messages);
    exit;
}
