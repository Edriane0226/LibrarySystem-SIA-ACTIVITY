<?php
    include "../config/connection.php";
    session_start();

    // Check if student is logged in
    if (!isset($_SESSION['student_id'])) {
        header("Location: studentLogin.php");
        exit();
    }

    $student_id = $_SESSION['student_id'];
    $borrowed_books = null;
    $success_message = "";
    $error_message = "";

    // Get all borrowed books for this student
    $query = "SELECT bb.barrowID, bb.ISBN, bb.status, bb.barrowed_date, b.title, b.book_cover, b.author
              FROM barrowed_books bb 
              JOIN books b ON bb.ISBN = b.ISBN 
              WHERE bb.barrower_id = ? AND bb.status = 'Barrowed'
              ORDER BY bb.barrowed_date DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $borrowed_books = $stmt->get_result();

    // Handle return request
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['return_book'])) {
        $barrow_id = $_POST['barrow_id'];
        
        $update_query = "UPDATE barrowed_books SET status = 'Returned' WHERE barrowID = ? AND barrower_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("is", $barrow_id, $student_id);
        
        if ($update_stmt->execute()) {
            $success_message = "Return request submitted successfully! Please bring the book to the library for staff verification.";
            // Refresh the borrowed books list
            $stmt->execute();
            $borrowed_books = $stmt->get_result();
        } else {
            $error_message = "Error submitting return request. Please try again.";
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Books - Library Management System</title>
    
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
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            padding-left: 20px;
            padding-right: 20px;
        }

        .return-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            width: 100%;
            max-width: 1000px;
            margin-bottom: 30px;
        }

        .return-card h1 {
            color: blue;
            margin-bottom: 30px;
            font-size: 28px;
            text-align: center;
            position: relative;
        }

        .return-card h1::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 2px;
        }

        .return-subtitle {
            text-align: center;
            color: #666;
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.6;
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

        /* Table Styling */
        .table-container {
            overflow-x: auto;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .books-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 15px;
            overflow: hidden;
        }

        .books-table thead {
            background: blue;
            color: white;
        }

        .books-table th {
            padding: 20px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 16px;
        }

        .books-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        .books-table tbody tr {
            transition: all 0.3s ease;
        }

        .books-table tbody tr:hover {
            background: rgba(102, 126, 234, 0.05);
            transform: translateX(5px);
        }

        .books-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Book Cover */
        .book-cover-img {
            width: 50px;
            height: 70px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
        }

        .book-cover-img:hover {
            transform: scale(1.1);
        }

        .no-cover {
            width: 50px;
            height: 70px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 10px;
            text-align: center;
            border: 1px solid #dee2e6;
        }

        /* Button Styling */
        .return-btn {
            background: red;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .return-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
        }

        .return-btn:active {
            transform: translateY(0);
        }

        .refresh-btn {
            width: 100%;
            max-width: 200px;
            background: blue;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 15px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 0 auto;
            display: block;
        }

        .refresh-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(94, 105, 206, 0.4);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 40px;
            color: #666;
        }

        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 15px;
            color: #333;
        }

        .empty-state p {
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .browse-btn {
            background: blue;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .browse-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        /* ISBN Styling */
        .isbn-code {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            padding: 4px 8px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            font-weight: 600;
        }

        /* Date Styling */
        .date-text {
            color: #666;
            font-weight: 500;
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

            .return-card {
                padding: 30px 20px;
            }

            .return-card h1 {
                font-size: 24px;
            }

            .books-table th,
            .books-table td {
                padding: 10px 8px;
                font-size: 14px;
            }

            .book-cover-img,
            .no-cover {
                width: 40px;
                height: 55px;
            }

            .return-btn {
                padding: 8px 16px;
                font-size: 12px;
            }
        }

        @media (max-width: 480px) {
            .return-card {
                padding: 20px 15px;
            }

            .return-card h1 {
                font-size: 20px;
            }

            .return-subtitle {
                font-size: 14px;
            }

            .books-table {
                font-size: 12px;
            }

            .books-table th,
            .books-table td {
                padding: 8px 5px;
            }

            .book-cover-img,
            .no-cover {
                width: 35px;
                height: 45px;
            }
        }



        .return-card {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Loading Animation for Buttons */
        .return-btn.loading {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
            cursor: not-allowed;
            pointer-events: none;
        }

        .return-btn.loading::after {
            content: '';
            width: 16px;
            height: 16px;
            border: 2px solid #ffffff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 8px;
            display: inline-block;
        }


    </style>
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="headerCont">
            <a href="#" class="logo">Library System</a>
            <nav class="nav-links">
                <a href="index.php"> Home</a>
                <a href="accInfo.php"> Account</a>
                <a href="bookList.php"> Books</a>
                <a href="returnbooks.php" class="active"> Return</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="return-card">
            <h1> Return Books</h1>
            <p class="return-subtitle">
                Select the book you want to return. After submitting your request, please bring the physical book to the library for staff verification.
            </p>
            
            <?php if (!empty($success_message)): ?>
                <div class="message success-message">
                     <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="message error-message">
                     <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php if ($borrowed_books->num_rows > 0): ?>
                <div class="table-container">
                    <table class="books-table">
                        <thead>
                            <tr>
                                <th> Cover</th>
                                <th> Title</th>
                                <th> Author</th>
                                <th> ISBN</th>
                                <th> Date Borrowed</th>
                                <th> Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($book = $borrowed_books->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($book['book_cover'])): ?>
                                            <img src="../Staff/uploads/<?php echo htmlspecialchars($book['book_cover']); ?>" 
                                                 alt="Book Cover" class="book-cover-img">
                                        <?php else: ?>
                                            <div class="no-cover">No Image</div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-weight: 600; color: #333;">
                                        <?php echo htmlspecialchars($book['title']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($book['author']); ?></td>
                                    <td>
                                        <span class="isbn-code"><?php echo htmlspecialchars($book['ISBN']); ?></span>
                                    </td>
                                    <td>
                                        <span class="date-text">
                                            <?php echo $book['barrowed_date'] ? date('M d, Y', strtotime($book['barrowed_date'])) : 'N/A'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="barrow_id" value="<?php echo $book['barrowID']; ?>">
                                            <button type="submit" name="return_book" class="return-btn"
                                                    onclick="return confirm(' Are you sure you want to return this book? Please make sure you have the physical book ready to bring to the library.')">
                                                 Return Book
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3> No Books Currently Borrowed</h3>
                    <p>You don't have any books currently borrowed. Ready to explore our collection?</p>
                    <a href="bookList.php" class="browse-btn">🔍 Browse Books</a>
                </div>
            <?php endif; ?>
            
            <button class="refresh-btn" onclick="location.reload()">
                Refresh List
            </button>
        </div>
    </main>

    <script>
        // Add loading state to return buttons
        document.querySelectorAll('.return-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                if (confirm('⚠️ Are you sure you want to return this book? Please make sure you have the physical book ready to bring to the library.')) {
                    this.classList.add('loading');
                    this.innerHTML = 'Processing...';
                } else {
                    e.preventDefault();
                }
            });
        });

        // Auto-refresh every 30 seconds
        setTimeout(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>