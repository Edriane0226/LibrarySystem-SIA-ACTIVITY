<?php
include "../config/connection.php";

$call_id = $_POST['call_id'];
$stmt = $conn->prepare("UPDATE video_calls SET status='ended', end_time=NOW() WHERE call_id=?");
$stmt->bind_param("i", $call_id);
$stmt->execute();

echo json_encode(["success" => true]);
