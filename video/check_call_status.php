<?php
include "../config/connection.php";

$call_id = $_GET['call_id'] ?? '';

if ($call_id) {
    $stmt = $conn->prepare("SELECT status FROM video_calls WHERE call_id = ?");
    $stmt->bind_param("i", $call_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        echo json_encode(["status" => $row['status']]);
    } else {
        echo json_encode(["status" => "not_found"]);
    }
} else {
    echo json_encode(["status" => "missing_call_id"]);
}
