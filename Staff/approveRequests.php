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

    // Generate new auto-login token
    $token = bin2hex(random_bytes(16));
    $conn->query("UPDATE students SET status='approved', verify_token='$token' WHERE student_id='$student_id'");

    // Send approval email
    sendApprovalEmail($student['student_id'], $student['first_name'], $student['surname'], $token);
    sendApprovalSMS($student['first_name'], $student['surname'], $student['phone'], $token);

    echo "<script>alert('Student approved and email sent! also texted that nig');</script>";
}

$result = $conn->query("SELECT * FROM students WHERE status='pending'");

function sendApprovalEmail($student_id, $first_name, $surname, $token) {
    $mail = new PHPMailer(true);
    $emailquery = "SELECT email FROM students WHERE student_id='$student_id'";
    $result = $GLOBALS['conn']->query($emailquery);
    $emailrow = $result->fetch_assoc();
    $student_email = $emailrow['email'];
    
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'edriane.bangonon26@gmail.com';
        $mail->Password = 'vyjhyogubmahnhcd';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('edriane.bangonon26@gmail.com', 'Library Admin');
        $mail->addAddress($student_email);

        

        $hostname = gethostname(); // Get the hostname of the server
        $localip = gethostbyname($hostname); // Resolve the hostname to its IP address

        // echo "Local IP Address: " . $localip;


        $autoLoginLink = "http://$localip/LibrarySystem/student/autoLogin.php?token=" . $token;

        $mail->isHTML(true);
        $mail->Subject = "Your Library Account Has Been Approved";
        $mail->Body = "
            <p>Dear $first_name $surname,</p>
            <p>Your account has been approved! Click the link below to access your account:</p>
            <p><a href='$autoLoginLink'>$autoLoginLink</a></p>
            <br>
            <p>Welcome to the Library System!</p>
        ";

        $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
    }
}
function sendApprovalSMS($first_name, $surname, $phone, $token) {
    if (empty($phone)) return;

      $hostname = gethostname(); // Get the hostname of the server
      $localip = gethostbyname($hostname); // Resolve the hostname to its IP address

      $loginLink = "http://$localip/LibrarySystem/student/autoLogin.php?token=" . $token;

    $message = "Hello $first_name $surname, your library registration has been approved. You may now log in to this link: $loginLink";

    sendsmsx($phone, $message);
}

function sendsmsx($phone, $message) {
    $ch = curl_init();
    $url = "http://192.168.1.251/default/en_US/send.html?";
    $user = "admin";
    $pass = "285952";

    // (Optional) detect carrier — can be expanded later if needed
    $line = "1"; // default to PLDT line

    $fields = array('u' => $user, 'p' => $pass, 'l' => $line, 'n' => $phone, 'm' => $message);
    $postvars = http_build_query($fields);

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERPWD, "$user:$pass");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    if (!$response) {
        error_log("SMS failed to send to $phone");
    } else {
        error_log("SMS sent successfully to $phone");
    }
    curl_close($ch);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Requests - Library Management System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: rgba(255, 255, 255, 0.95);
            color: #333;
            min-height: 100vh;
        }

        /* Header */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: fixed; top: 0; left: 0; right: 0;
            z-index: 1000;
        }
        .headerCont {
            display: flex; justify-content: space-between; align-items: center;
            padding: 15px 30px; max-width: 1200px; margin: 0 auto;
        }
        .logo { font-size: 24px; font-weight: bold; color: blue; text-decoration: none; }
        .nav-links { display: flex; gap: 30px; align-items: center; }
        .nav-links a {
            text-decoration: none; color: #333; font-weight: 500;
            padding: 10px 15px; border-radius: 8px; transition: all 0.3s ease;
        }
        .nav-links a.active { background: blue; color: white; }

        /* Main */
        .main-content {
            padding-top: 100px; display: flex;
            justify-content: center; align-items: flex-start;
            gap: 30px; flex-wrap: wrap; min-height: 100vh;
            padding-left: 20px; padding-right: 20px;
        }
        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px; padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            min-width: 600px;
        }
        .card h1 {
            color: blue; margin-bottom: 25px; font-size: 28px;
            text-align: center;
        }

        /* Table */
        .table-wrapper { background: white; border-radius: 15px;
            overflow: hidden; box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1); }
        .table { width: 100%; border-collapse: collapse; }
        .table thead { background: blue; }
        .table thead th {
            color: white; padding: 20px 15px; text-align: left;
            font-weight: 600; font-size: 14px; text-transform: uppercase;
        }
        .table tbody tr { border-bottom: 1px solid #f0f0f0; transition: background 0.3s ease; }
        .table tbody tr:hover { background: rgba(94, 105, 206, 0.05); }
        .table tbody td { padding: 15px; color: #555; vertical-align: middle; }

        /* Button */
        .approve-btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white; padding: 8px 18px; border: none;
            border-radius: 20px; text-decoration: none;
            font-weight: bold; transition: 0.3s ease; cursor: pointer;
        }
        .approve-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="headerCont">
            <a href="#" class="logo">Library System</a>
            <nav class="nav-links">
                <a href="addBooks.php">Add Books</a>
                <a href="accInfoStaff.php">Account</a>
                <a href="bookListStaff.php">Books</a>
                <a href="Schedules.php">Transactions</a>
                <a href="returnBook.php">Return Book</a>
                <a href="approveRequests.php" class="active">Approve Requests</a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="card">
            <h1>Pending Student Approvals</h1>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['student_id']); ?></td>
                                    <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['surname']); ?></td>
                                    <td>
                                        <a class="approve-btn" href="?approve=<?= $row['student_id'] ?>">Approve</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align:center; padding: 40px; color:#666;">
                                    No pending student approvals found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>