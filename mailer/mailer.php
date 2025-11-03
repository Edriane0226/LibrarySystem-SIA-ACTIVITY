<?php
include "../config/connection.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

$barrow = (int)$barrower_id;

$result = $conn->query("SELECT first_name, surname, email FROM students WHERE student_id = $barrow");

if ($result && $result->num_rows > 0) {

    while ($row = $result->fetch_assoc()) {

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'edriane.bangonon26@gmail.com'; 
            $mail->Password = 'vyjhyogubmahnhcd';   
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('edriane.bangonon26@gmail.com', 'Admin');

            $mail->addAddress($row['email'], $row['first_name'] . ' ' . $row['surname']);

            $mail->isHTML(true);
            $mail->Subject = 'Book Returned Notice';
            $mail->Body    = '
                <p>Maayong Adlaw <b>' . htmlspecialchars($row['first_name']) . '</b>,</p>
                <p>Nagapahibalo ko saimo na imong libro na gi hulam niaging tuig kay nauli na.</p>
                <p>Kana Lang,<br>Admin</p>
            ';

            $mail->send();
            echo "Email sent to " . htmlspecialchars($row['email']) . "<br>";

        } catch (Exception $e) {
            echo "Message could not be sent to " . htmlspecialchars($row['email']) . ". Error: {$mail->ErrorInfo}<br>";
        }
    }

    }
$conn->close();
?>