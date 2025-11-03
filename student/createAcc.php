<?php
include "../config/connection.php";
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // Make sure PHPMailer is installed via Composer

$success_message = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $_POST["firstname"];
    $surname = $_POST["surname"];
    $initial = isset($_POST["initial"]) ? $_POST["initial"] : null;
    $email = $_POST["email"];
    $student_id = $_POST["studentID"];
    $password = $_POST["password"];
    $phone = $_POST["phone"];
    $confirm_password = $_POST["conPassword"];

    if ($password !== $confirm_password) {
        $error_message = "Passwords do not match!";
    } else {
        // Check if student ID already exists
        $check_query = "SELECT student_id FROM students WHERE student_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("s", $student_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error_message = "Student ID already exists!";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Generate token
            $token = bin2hex(random_bytes(16));

            // Insert with status = pending
            $insert_query = "INSERT INTO students (student_id, first_name, surname, initial, email, password, phone, status, verify_token) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("ssssssss", $student_id, $first_name, $surname, $initial, $email, $hashed_password, $phone, $token);

            if ($insert_stmt->execute()) {
                // Send notification email to student
                sendPendingEmail($student_id, $first_name, $surname);

                $success_message = "Account request sent! Please wait for staff approval.";
            } else {
                $error_message = "Error creating account. Please try again.";
            }
        }
    }
}

// Function: Send "waiting for approval" mail
function sendPendingEmail($student_id, $first_name, $surname) {
    $mail = new PHPMailer(true);
    $emailQuery = "SELECT email FROM students WHERE student_id = ?";
    global $conn;
    $stmt = $conn->prepare($emailQuery);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        return; // No email found
    }
    $row = $result->fetch_assoc();
    $mail_address = $row['email'];
    try {
        // SMTP settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Change if using another SMTP
        $mail->SMTPAuth = true;
        $mail->Username = 'edriane.bangonon26@gmail.com'; // your sender email
        $mail->Password = 'vyjhyogubmahnhcd'; // app password, not Gmail password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('edriane.bangonon26@gmail.com', 'Library Admin');
        $mail->addAddress($mail_address);

        $mail->isHTML(true);
        $mail->Subject = 'Library Registration Request Received';
        $mail->Body = "
            <p>Dear $first_name $surname,</p>
            <p>Your registration request has been received and is pending staff approval.</p>
            <p>You will receive another email once your account has been approved.</p>
            <br>
            <p>Thank you,<br>Library Management Team</p>
        ";

        $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Library Management System</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated Background */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><radialGradient id="a" cx="50%" cy="50%"><stop offset="0%" style="stop-color:%23ffffff;stop-opacity:0.1"/><stop offset="100%" style="stop-color:%23ffffff;stop-opacity:0"/></radialGradient></defs><g fill="url(%23a)"><circle cx="200" cy="200" r="150"/><circle cx="800" cy="300" r="100"/><circle cx="300" cy="700" r="120"/><circle cx="700" cy="800" r="80"/></g></svg>') no-repeat center center;
            background-size: cover;
            pointer-events: none;
        }


        .signup-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            padding: 40px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 10;
        }


        .signup-title {
            text-align: center;
            color:blue;
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
            position: relative;
        }

        .signup-subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
        }

        /* Messages */
        .message {
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 500;
            text-align: center;
        }

        .success-message {
            background: rgba(39, 174, 96, 0.1);
            border: 1px solid #27ae60;
            color: #27ae60;
        }

        .error-message {
            background: rgba(231, 76, 60, 0.1);
            border: 1px solid #e74c3c;
            color: #e74c3c;
        }

        /* Form Styling */
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            color:blue;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e8ed;
            border-radius: 15px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }

        .form-input:focus {
            outline: none;
            border-color:blue;
            box-shadow: 0 0 20px rgba(94, 105, 206, 0.2);
            background: white;
            transform: translateY(-2px);
        }

        .form-input::placeholder {
            color: #aaa;
            font-style: italic;
        }

        /* Submit Button */
        .submit-btn {
            width: 100%;
            background: blue;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 15px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(94, 105, 206, 0.4);
        }

        .submit-btn:active {
            transform: translateY(-1px);
        }

        /* Login Link */
        .login-link {
            text-align: center;
            margin-top: 15px;
        }

        .login-link a {
            color: blue;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
        }


        .login-link a:hover::after {
            width: 100%;
        }

        .login-link a:hover {
            color: #764ba2;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .signup-container {
                padding: 30px 20px;
                margin: 10px;
            }

            .signup-title {
                font-size: 28px;
            }

            .form-input {
                padding: 12px 15px;
                font-size: 14px;
            }

            .submit-btn {
                padding: 12px 25px;
                font-size: 16px;
            }
        }

        /* Form Icons */
        .form-group.has-icon {
            position: relative;
        }

        .form-group.has-icon::before {
            content: attr(data-icon);
            position: absolute;
            left: 15px;
            top: 38px;
            color: #5e69ce;
            font-size: 16px;
            z-index: 5;
        }

        .form-group.has-icon .form-input {
            padding-left: 45px;
        }
    </style>
</head>

<body>
    <div class="signup-container">
        <h1 class="signup-title"> Create Account</h1>
        <p class="signup-subtitle">Join our library community today!</p>
        
        <?php if (!empty($success_message)): ?>
            <div class="message success-message"> <?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="message error-message"> <?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group has-icon" data-icon="">
                <label for="firstname">First Name</label>
                <input type="text" name="firstname" id="firstname" class="form-input" 
                       placeholder="Enter your first name" required>
            </div>

            <div class="form-group has-icon" data-icon="">
                <label for="surname">Surname</label>
                <input type="text" name="surname" id="surname" class="form-input" 
                       placeholder="Enter your surname" required>
            </div>

            <div class="form-group has-icon" data-icon="">
                <label for="initial">Middle Initial (Optional)</label>
                <input type="text" name="initial" id="initial" class="form-input" 
                       maxlength="2" placeholder="e.g. M">
            </div>

            <div class="form-group has-icon" data-icon="">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-input" 
                       placeholder="Enter your email address" required>
            </div>

            <div class="form-group has-icon" data-icon="">
                <label for="phone">Phone Number (Optional)</label>
                <input type="text" name="phone" id="phone" class="form-input" 
                       placeholder="Enter your phone number">

            <div class="form-group has-icon" data-icon="">
                <label for="studentID">Student ID</label>
                <input type="text" name="studentID" id="studentID" class="form-input" 
                       placeholder="Enter your student ID" required>
            </div>

            <div class="form-group has-icon" data-icon="">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-input" 
                       placeholder="Create a secure password" required>
            </div>

            <div class="form-group has-icon" data-icon="">
                <label for="conPassword">Confirm Password</label>
                <input type="password" name="conPassword" id="conPassword" class="form-input" 
                       placeholder="Confirm your password" required>
            </div>

            <button type="submit" class="submit-btn"> Create Account</button>
        </form>

        <div class="login-link">
            <a href="studentLogin.php">Already have an account? Sign in here</a>
        </div>
    </div>
</body>
</html>