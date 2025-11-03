<?php
session_start();
include "../config/connection.php";

if (isset($_SESSION['staff_id'])) {
    $current_type = "staff";
    $current_id   = $_SESSION['staff_id'];
} elseif (isset($_SESSION['student_id'])) {
    $current_type = "student";
    $current_id   = $_SESSION['student_id'];
} else {
    exit("Not logged in");
}

$receiver_id   = $_GET['user'] ?? '';
$receiver_type = $_GET['type'] ?? '';

if (!$receiver_id || !$receiver_type) {
    exit("Invalid receiver");
}

$sql = "SELECT * FROM messages 
        WHERE (sender_id=? AND sender_type=? AND receiver_id=? AND receiver_type=?)
           OR (sender_id=? AND sender_type=? AND receiver_id=? AND receiver_type=?)
        ORDER BY created_at ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssss", 
    $current_id, $current_type, $receiver_id, $receiver_type,
    $receiver_id, $receiver_type, $current_id, $current_type
);
$stmt->execute();
$result = $stmt->get_result();

while($row = $result->fetch_assoc()):
    $message_class = ($row['sender_id'] == $current_id && $row['sender_type'] === $current_type) ? 'sent' : 'received';
    $message_text = htmlspecialchars($row['message']);
    $timestamp = date('M j, Y g:i A', strtotime($row['created_at']));
    $sender_initial = strtoupper(substr($row['sender_id'], 0, 1));
?>
    <div class="message <?= $message_class ?>">
        <div class="message-avatar"><?= $sender_initial ?></div>
        <div>
            <div class="message-content">
                <?= nl2br($message_text) ?>
            </div>
            <div class="message-time"><?= $timestamp ?></div>
        </div>
    </div>
<?php endwhile; ?>
