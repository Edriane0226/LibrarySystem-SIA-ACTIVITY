<?php
    include "../config/connection.php";
    session_start();

    // Check if student is logged in
    if (!isset($_SESSION['student_id'])) {
        header("Location: studentLogin.php");
        exit();
    }

    $success_message = "";
    $error_message = "";

    // Get available books for dropdown - books that are either not in barrowed_books table OR have status 'Available' or 'Returned'
    $books_query = "SELECT DISTINCT b.ISBN, b.title, b.author
                    FROM books b 
                    LEFT JOIN (
                        SELECT bb1.ISBN, bb1.status 
                        FROM barrowed_books bb1
                        WHERE bb1.barrowID = (
                            SELECT MAX(bb2.barrowID) 
                            FROM barrowed_books bb2 
                            WHERE bb2.ISBN = bb1.ISBN
                        )
                    ) latest_status ON b.ISBN = latest_status.ISBN
                    WHERE latest_status.ISBN IS NULL 
                       OR latest_status.status IN ('Available', 'Returned')
                    ORDER BY b.title";
    $books_result = $conn->query($books_query);

    // Handle book borrowing
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $selected_book = $_POST["book"];
        $duration = $_POST["duration"];
        $student_id = $_SESSION['student_id'];
        
        if (isset($_POST["agree"])) {
            // First, check the current status of the book in barrowed_books table
            $status_check = "SELECT bb.status, bb.barrower_id 
                            FROM barrowed_books bb 
                            WHERE bb.ISBN = ? 
                            ORDER BY bb.barrowID DESC 
                            LIMIT 1";
            $check_stmt = $conn->prepare($status_check);
            $check_stmt->bind_param("s", $selected_book);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $book_status = $check_result->fetch_assoc();
                
                // Check if book is currently unavailable
                if (in_array($book_status['status'], ['Barrowed', 'Overdue', 'Lost'])) {
                    $error_message = "Sorry, this book is currently unavailable. Status: " . $book_status['status'];
                } else {
                    // Book is available (status is 'Available' or 'Returned'), proceed with other checks
                    performBorrowingChecks($selected_book, $student_id, $conn, $success_message, $error_message, $books_query);
                }
            } else {
                // Book has never been borrowed before, it's available
                performBorrowingChecks($selected_book, $student_id, $conn, $success_message, $error_message, $books_query);
            }
        } else {
            $error_message = "Please agree to the library rules before borrowing a book.";
        }
    }

    // Function to perform borrowing checks and process
    function performBorrowingChecks($selected_book, $student_id, $conn, &$success_message, &$error_message, $books_query) {
        // Check if this student already has this book borrowed
        $student_check = "SELECT bb.barrowID FROM barrowed_books bb 
                         WHERE bb.ISBN = ? AND bb.barrower_id = ? AND bb.status = 'Barrowed'";
        $student_stmt = $conn->prepare($student_check);
        $student_stmt->bind_param("ss", $selected_book, $student_id);
        $student_stmt->execute();
        $student_result = $student_stmt->get_result();
        
        if ($student_result->num_rows > 0) {
            $error_message = "You have already borrowed this book. Please return it before borrowing again.";
        } else {
            // Check borrowing limit (max 3 books per student)
            $limit_check = "SELECT COUNT(*) as borrowed_count FROM barrowed_books 
                           WHERE barrower_id = ? AND status = 'Barrowed'";
            $limit_stmt = $conn->prepare($limit_check);
            $limit_stmt->bind_param("s", $student_id);
            $limit_stmt->execute();
            $limit_result = $limit_stmt->get_result();
            $limit_data = $limit_result->fetch_assoc();
            
            if ($limit_data['borrowed_count'] >= 3) {
                $error_message = "You have reached the maximum borrowing limit (3 books). Please return a book before borrowing a new one.";
            } else {
                // All checks passed, proceed with borrowing
                $insert_query = "INSERT INTO barrowed_books (ISBN, status, barrower_id, barrowed_date) 
                                VALUES (?, 'Barrowed', ?, CURDATE())";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bind_param("ss", $selected_book, $student_id);
                
                if ($insert_stmt->execute()) {
                    $success_message = "Book borrowed successfully! Please collect it from the library within 24 hours.";
                    
                    // Refresh the books dropdown to remove the borrowed book
                    global $books_result;
                    $books_result = $conn->query($books_query);
                } else {
                    $error_message = "Error borrowing book. Please try again or contact the librarian.";
                }
            }
        }
    }

    // Get student's current borrowed books count for display
    $student_books_query = "SELECT COUNT(*) as current_books FROM barrowed_books 
                           WHERE barrower_id = ? AND status = 'Barrowed'";
    $student_stmt = $conn->prepare($student_books_query);
    $student_stmt->bind_param("s", $_SESSION['student_id']);
    $student_stmt->execute();
    $student_result = $student_stmt->get_result();
    $student_data = $student_result->fetch_assoc();
    $current_borrowed = $student_data['current_books'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Library Management System</title>
    
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
            color: #333;
        }

        /* Header Styling */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .headerCont {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: blue;
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            padding: 10px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .nav-links a.active {
            background: blue;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(94, 105, 206, 0.3);
        }

        /* Main Content */
        .main-content {
            padding-top: 100px;
            padding-bottom: 50px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding-left: 20px;
            padding-right: 20px;
        }

        .borrow-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            width: 100%;
            max-width: 500px;
            animation: fadeInUp 0.6s ease-out;
        }

        .borrow-card h1 {
            color: blue;
            margin-bottom: 30px;
            font-size: 28px;
            text-align: center;
            position: relative;
        }

        .borrow-card h1::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: blue;
            border-radius: 2px;
        }

        /* Status Info */
        .status-info {
            background: rgba(102, 126, 234, 0.1);
            border: 1px solid blue;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 25px;
            text-align: center;
        }

        .status-info h3 {
            color: blue;
            margin-bottom: 5px;
            font-size: 16px;
        }

        .status-info p {
            color: #555;
            font-size: 14px;
        }

        .books-limit {
            font-weight: 600;
            color: blue;
        }

        .books-limit.warning {
            color: #f39c12;
        }

        .books-limit.danger {
            color: #e74c3c;
        }

        /* Messages */
        .message {
            padding: 15px;
            border-radius: 10px;
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
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            color: blue;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .form-select {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e8ed;
            border-radius: 15px;
            font-size: 16px;
            background: white;
            color: #333;
            transition: all 0.3s ease;
        }

        .form-select:focus {
            outline: none;
            border-color:blue;
            box-shadow: 0 0 20px rgba(94, 105, 206, 0.2);
        }

        /* Duration Radio Buttons */
        .duration-group {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            padding: 15px;
            background: rgba(94, 105, 206, 0.05);
            border-radius: 10px;
            border: 2px solid transparent;
            cursor: pointer;
        }

        .radio-option:hover {
            background: rgba(94, 105, 206, 0.1);
            border-color:blue;
        }

        .radio-option input[type="radio"] {
            margin-right: 10px;
            accent-color:blue;
            transform: scale(1.2);
        }

        .radio-option label {
            margin: 0;
            cursor: pointer;
            font-weight: 500;
        }

        /* Checkbox */
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 25px;
            padding: 15px;
            background: rgba(94, 105, 206, 0.05);
            border-radius: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            accent-color:blue;
            transform: scale(1.2);
        }

        .checkbox-group label {
            margin: 0;
            color: #555;
            font-weight: 500;
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
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(94, 105, 206, 0.4);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        /* No Books Available */
        .no-books {
            text-align: center;
            padding: 30px;
            color: #666;
        }

        .no-books h3 {
            color: #333;
            margin-bottom: 15px;
        }

        .no-books p {
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .browse-btn {
            background: blue;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .browse-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .headerCont {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }

            .nav-links {
                gap: 15px;
                flex-wrap: wrap;
                justify-content: center;
            }

            .main-content {
                padding-top: 150px;
                padding-left: 10px;
                padding-right: 10px;
            }

            .borrow-card {
                padding: 30px 20px;
            }

            .duration-group {
                gap: 10px;
            }
        }

        /* Animation */

    </style>
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="headerCont">
            <a href="#" class="logo">Library System</a>
            <nav class="nav-links">
                <a href="index.php" class="active"> Home</a>
                <a href="accInfo.php"> Account</a>
                <a href="bookList.php"> Books</a>
                <a href="returnbooks.php">Return</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="borrow-card">
            <h1> Borrow a Book</h1>
            
            <!-- Status Information -->
            <div class="status-info">
                <h3> Your Borrowing Status</h3>
                <p>Currently borrowed: 
                    <span class="books-limit <?php echo $current_borrowed >= 3 ? 'danger' : ($current_borrowed >= 2 ? 'warning' : ''); ?>">
                        <?php echo $current_borrowed; ?>/3 books
                    </span>
                </p>
            </div>
            
            <?php if (!empty($success_message)): ?>
                <div class="message success-message"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="message error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <?php if ($current_borrowed >= 3): ?>
                <div class="no-books">
                    <h3> Borrowing Limit Reached</h3>
                    <p>You have reached the maximum borrowing limit of 3 books. Please return a book before borrowing a new one.</p>
                    <a href="returnbooks.php" class="browse-btn"> Return Books</a>
                </div>
            <?php elseif ($books_result->num_rows == 0): ?>
                <div class="no-books">
                    <h3> No Books Available</h3>
                    <p>All books are currently borrowed by other students. Please check back later or browse our catalog for upcoming returns.</p>
                    <a href="bookList.php" class="browse-btn"> Browse Catalog</a>
                </div>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="book"> Select Book</label>
                        <select name="book" id="book" class="form-select" required>
                            <option value="" disabled selected>Choose a book to borrow...</option>
                            <?php while ($book_row = $books_result->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($book_row['ISBN']); ?>">
                                    <?php echo htmlspecialchars($book_row['title']); ?>
                                    <?php if (!empty($book_row['author'])): ?>
                                        - by <?php echo htmlspecialchars($book_row['author']); ?>
                                    <?php endif; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label> Borrowing Duration</label>
                        <div class="duration-group">
                            <div class="radio-option">
                                <input type="radio" name="duration" value="1week" id="1week" required>
                                <label for="1week"> 1 Week (7 days)</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" name="duration" value="2week" id="2week">
                                <label for="2week"> 2 Weeks (14 days)</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" name="duration" value="1month" id="1month">
                                <label for="1month"> 1 Month (30 days)</label>
                            </div>
                        </div>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" name="agree" id="agree" required>
                        <label for="agree"> I agree to follow all library rules and return the book on time</label>
                    </div>
                    
                    <button type="submit" class="submit-btn"> Borrow Book</button>
                </form>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>