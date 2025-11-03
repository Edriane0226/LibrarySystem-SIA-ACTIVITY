<?php
    include "../config/connection.php";
    session_start();

    $user_data = null;
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $staff_first_name = $_POST['user'];
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT * FROM staff WHERE first_name = ? AND password = ?");
        $stmt->bind_param("ss", $staff_first_name, $password);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
    }

    if ($user_data) {
        $_SESSION['staff_id'] = $user_data['staff_id'];
        unset($_SESSION['student_id']); // Ensure only one role is set
        header("Location: accInfoStaff.php");
        exit();
    } elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
        $error_message = "Invalid Staff ID or Password";
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login - Library Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
            position: relative;
            overflow: hidden;
        }

        /* Animated Background */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="25" cy="25" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="3" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="1.5" fill="rgba(255,255,255,0.1)"/><circle cx="10" cy="90" r="2.5" fill="rgba(255,255,255,0.1)"/><circle cx="90" cy="30" r="1" fill="rgba(255,255,255,0.1)"/></svg>') repeat;
        }


        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            padding: 50px 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            width: 100%;
            max-width: 450px;
            position: relative;
        }


        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-header h1 {
            color:blue;
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
            position: relative;
        }

        .login-header p {
            color: #666;
            font-size: 16px;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color:blue;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-group input {
            width: 100%;
            padding: 15px 20px 15px 50px;
            border: 2px solid #e1e8ed;
            border-radius: 15px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }

        .form-group input:focus {
            outline: none;
            border-color: #5e69ce;
            background: white;
            box-shadow: 0 0 20px rgba(94, 105, 206, 0.2);
            transform: translateY(-2px);
        }


        .login-btn {
            width: 100%;
            background: blue;
            color: white;
            padding: 15px;
            border: none;
            border-radius: 15px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
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
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }


        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(94, 105, 206, 0.4);
            background: linear-gradient(135deg, blue 0%, white 100%);
        }

        .error-message {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
            animation: shake 0.5s ease-in-out;
        }

        .links-section {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e1e8ed;
        }

        .links-section a {
            color: #5e69ce;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-block;
            margin: 5px 15px;
        }

        .links-section a:hover {
            color: #764ba2;
            transform: translateY(-1px);
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            .login-container {
                margin: 20px;
                padding: 30px 25px;
            }

            .login-header h1 {
                font-size: 28px;
            }

            .form-group input {
                padding: 12px 15px 12px 45px;
            }

            .login-btn {
                padding: 12px;
                font-size: 16px;
            }
        }

        /* Loading Animation */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .loading .login-btn {
            background: #ccc;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Staff Portal</h1>
            <p>Library Management System</p>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm">
            <div class="form-group">
                <label for="user"> First Name</label>
                <div class="input-wrapper">
                    <span class="input-icon"></span>
                    <input type="text" id="user" name="user" required 
                           placeholder="Enter your first name">
                </div>
            </div>

            <div class="form-group">
                <label for="password"> Password</label>
                <div class="input-wrapper">
                    <span class="input-icon"></span>
                    <input type="password" id="password" name="password" required 
                           placeholder="Enter your password">
                </div>
            </div>

            <button type="submit" class="login-btn">
                 Sign In to Dashboard
            </button>
        </form>

        <div class="links-section">
            <a href="../student/studentLogin.php">Student Login</a>
        </div>
    </div>

    <script>
        // Add loading state on form submission
        document.getElementById('loginForm').addEventListener('submit', function() {
            this.classList.add('loading');
        });

        // Add enter key support
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('loginForm').submit();
            }
        });
    </script>
</body>
</html>