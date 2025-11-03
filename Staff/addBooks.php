<?php
include "../config/connection.php";
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php';

session_start();
if (!isset($_SESSION['staff_id'])) {
    header("Location: staffLogin.php");
    exit();
}

if (isset($_GET['approve'])) {
    $student_id = $_GET['approve'];

    // Get student info
    $res = $conn->query("SELECT * FROM students WHERE student_id='$student_id'");
    $student = $res->fetch_assoc();

    if ($student) {
        $token = bin2hex(random_bytes(16));
        $conn->query("UPDATE students SET status='approved', verify_token='$token' WHERE student_id='$student_id'");

        $first_name = $student['first_name'];
        $surname = $student['surname'];
        $email = $student['email'];
        $phone = $student['phone'];

        // Send email + SMS
        sendApprovalEmail($first_name, $surname, $email, $token);
        sendApprovalSMS($first_name, $surname, $phone);

        echo "<script>alert('Student approved. Email and SMS sent successfully!'); window.location='approveRequests.php';</script>";
    }
}

// Fetch all pending students
$result = $conn->query("SELECT * FROM students WHERE status='pending'");

function sendApprovalEmail($first_name, $surname, $email, $token) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'edriane.bangonon26@gmail.com';
        $mail->Password = 'vyjhyogubmahnhcd';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('edriane.bangonon26@gmail.com', 'Library Admin');
        $mail->addAddress($email);

        $autoLoginLink = "http://localhost/LibrarySystem/student/autoLogin.php?token=" . $token;

        $mail->isHTML(true);
        $mail->Subject = "Library Account Approved";
        $mail->Body = "
            <p>Dear $first_name $surname,</p>
            <p>Your library account has been approved! You can now log in using this link:</p>
            <p><a href='$autoLoginLink'>$autoLoginLink</a></p>
            <br><p>- Library System</p>
        ";

        $mail->send();
    } catch (Exception $e) {
        error_log("Email Error: " . $mail->ErrorInfo);
    }
}

function sendApprovalSMS($first_name, $surname, $phone) {
    if (empty($phone)) return;

    $message = "Hello $first_name $surname, your library registration has been approved. You may now log in to the Library System. You can access your account sa email na among gi send";
    sendsmsx($phone, $message);
}

function sendsmsx($pnum, $smsgs) {
    $ch = curl_init();
    $url = "http://192.168.1.251/default/en_US/send.html?";
    $user = "admin";
    $pass = "285952";

    // (Optional) detect carrier — can be expanded later if needed
    $line = "1"; // default to PLDT line

    $fields = array('u' => $user, 'p' => $pass, 'l' => $line, 'n' => $pnum, 'm' => $smsgs);
    $postvars = http_build_query($fields);

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERPWD, "$user:$pass");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    if (!$response) {
        error_log("SMS failed to send to $pnum");
    } else {
        error_log("SMS sent successfully to $pnum");
    }
    curl_close($ch);
}
?>