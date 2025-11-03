<?php
session_start();
include "../config/connection.php";

// ✅ Determine logged in user
if (isset($_SESSION['student_id'])) {
    $sender_type = "student";
    $sender_id   = $_SESSION['student_id'];
} elseif (isset($_SESSION['staff_id'])) {
    $sender_type = "staff";
    $sender_id   = $_SESSION['staff_id'];
} else {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $receiver_type = $_POST['receiver_type']; 
    $receiver_id   = $_POST['receiver_id'];   
    $message       = $_POST['message'];

    $sql = "INSERT INTO messages (sender_type, sender_id, receiver_type, receiver_id, message)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $sender_type, $sender_id, $receiver_type, $receiver_id, $message);

    if ($stmt->execute()) {
        header("Location: chat.php?user=" . urlencode($receiver_id) . "&type=" . urlencode($receiver_type));
        exit();
    } else {
        echo "Error sending message.";
    }
}
?>
