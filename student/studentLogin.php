<?php
include "../config/connection.php";
    session_start();

    $user_data = null;
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $student_id = $_POST['user'];
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ? AND password = ?");
        $stmt->bind_param("ss", $student_id, $password);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
    }

    if ($user_data) {
        $_SESSION['student_id'] = $user_data['student_id'];
        unset($_SESSION['staff_id']);
        header("Location: accInfo.php");
        exit();
    } elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
        $error_message = "Invalid Student ID or Password";
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - Library Management System</title>
    
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
            color: #333;
        }

        /* Login Container */
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            width: 100%;
            max-width: 450px;
        }

        /* Header */
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .student-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            display: block;
        }

        .login-title {
            font-size: 28px;
            font-weight: bold;
            color: blue;
            margin-bottom: 10px;
        }

        .login-subtitle {
            color: #666;
            font-size: 16px;
            font-weight: 500;
        }

        /* Error Message */
        .error-message {
            background: rgba(231, 76, 60, 0.1);
            border: 1px solid #e74c3c;
            color: #e74c3c;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: 500;
            text-align: center;
        }

        /* Form Styling */
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .form-group {
            position: relative;
        }

        .form-group label {
            display: block;
            color: blue;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e8ed;
            border-radius: 15px;
            font-size: 16px;
            background: white;
            color: #333;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #5e69ce;
            box-shadow: 0 0 20px rgba(94, 105, 206, 0.2);
        }

        .form-group input:valid {
            border-color: #27ae60;
        }

        /* Input Icons */
        .form-group::after {
            content: '';
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-10%);
            font-size: 18px;
            color: #6c757d;
            pointer-events: none;
        }


        /* Form Row */
        .form-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 5px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: rgba(94, 105, 206, 0.05);
            border-radius: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            accent-color: #5e69ce;
            transform: scale(1.2);
        }

        .checkbox-group label {
            margin: 0;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }

        .forgot-link {
            color: #5e69ce;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .forgot-link:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        /* Login Button */
        .login-btn {
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
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }

        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .login-btn:hover::before {
            left: 100%;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(94, 105, 206, 0.4);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        /* Create Account Link */
        .create-account {
            text-align: center;
            margin-top: 25px;
        }

        .create-account a {
            color: #5e69ce;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
        }

        .create-account a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -2px;
            left: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            transition: all 0.3s ease;
        }

        .create-account a:hover::after {
            width: 100%;
            left: 0;
        }

        .create-account a:hover {
            color: #764ba2;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .login-container {
                padding: 30px 20px;
                margin: 20px 0;
            }
            
            .login-title {
                font-size: 24px;
            }
            
            .student-icon {
                font-size: 3rem;
            }
            
            .form-row {
                flex-direction: column;
                align-items: flex-start;
            }

            .checkbox-group {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 25px 15px;
            }
            
            .login-title {
                font-size: 22px;
            }
            
            .form-group input {
                padding: 12px 16px;
                font-size: 16px;
            }
            
            .login-btn {
                padding: 14px 25px;
                font-size: 16px;
            }
        }

        /* Loading State */
        .login-btn.loading {
            background: linear-gradient(135deg, #6c757d, #495057);
            cursor: not-allowed;
            pointer-events: none;
        }

        .login-btn.loading::after {
            content: '';
            width: 20px;
            height: 20px;
            border: 2px solid #ffffff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 10px;
            display: inline-block;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-container {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <br>
            <h1 class="login-title">Student Portal</h1>
            <p class="login-subtitle">Access your library account</p>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="login-form" id="loginForm">
            <div class="form-group user-input">
                <label for="user">Student ID</label>
                <input type="text" name="user" id="user" placeholder="Enter your student ID" required>
            </div>

            <div class="form-group password-input">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" placeholder="Enter your password" required>
            </div>

            <div class="form-row">
                <div class="checkbox-group">
                    <input type="checkbox" name="keepPass" id="keepPass">
                    <label for="keepPass">Stay logged in</label>
                </div>
                <a href="#" class="forgot-link">Forgot Password?</a>
            </div>

            <button type="submit" class="login-btn" id="loginBtn">
                Login to Account
            </button>
        </form>

        <div class="create-account">
            <a href="createAcc.php">Don't have an account? Create one here</a>
        </div>
    </div>

    <script>
        // Form submission enhancement
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const loginBtn = document.getElementById('loginBtn');
            loginBtn.textContent = 'Logging in...';
            loginBtn.classList.add('loading');
        });

        // Auto-focus on student ID field
        document.getElementById('user').focus();

        // Enter key handling
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('loginForm').submit();
            }
        });

        // Input validation
        const inputs = document.querySelectorAll('input[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.length > 0) {
                    this.style.borderColor = '#27ae60';
                } else {
                    this.style.borderColor = '#e1e8ed';
                }
            });
        });
    </script>
</body>
</html>