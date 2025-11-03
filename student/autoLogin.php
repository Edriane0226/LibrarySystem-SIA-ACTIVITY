<?php
include "../config/connection.php";
session_start();

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $stmt = $conn->prepare("SELECT * FROM students WHERE verify_token = ? AND status = 'approved'");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        $_SESSION['student_id'] = $student['student_id'];
        header("Location: accinfo.php");
        exit();
    } else {
        echo "Invalid or expired link.";
    }
} else {
    echo "Token missing.";
}
?>
