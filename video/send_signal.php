<?php
include "../config/connection.php";

$call_id = $_POST['call_id'];
$sender_id = $_POST['sender_id'];
$type = $_POST['type']; // offer | answer | candidate
$data = $_POST['data'];

$stmt = $conn->prepare("INSERT INTO call_signaling (call_id, signal_type, payload) VALUES (?,?,?)");
$stmt->bind_param("iss", $call_id, $type, $data);
$stmt->execute();

echo json_encode(["success" => true]);
