<?php
    include "../config/connection.php";
    session_start();

    // Check if staff is logged in
    if (!isset($_SESSION['staff_id'])) {
        header("Location: staffLogin.php");
        exit();
    }

    // Query to get all transactions with borrower and book details
    $query = "SELECT bb.barrowID, bb.ISBN, bb.status, bb.barrower_id, bb.barrowed_date, 
                     b.title, b.author, s.first_name, s.surname
              FROM barrowed_books bb 
              JOIN books b ON bb.ISBN = b.ISBN 
              LEFT JOIN students s ON bb.barrower_id = s.student_id
              ORDER BY bb.barrowed_date DESC";
    
    $result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - Library Management System</title>
    
    <style>
        /* Modern Internal CSS - Complete Design System */
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
            padding: 0;
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
            color:blue;
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
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .nav-links a.active {
            background: blue;
            color: white;
            transform: translateY(-2px);
        }

        /* Main Content */
        .main-container {
            margin-top: 80px;
            padding: 30px 20px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        .page-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .page-title {
            color: white;
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .record-count {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
            font-weight: 500;
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }


        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .stat-borrowed .stat-number { color: #007bff; }
        .stat-overdue .stat-number { color: #dc3545; }
        .stat-returned .stat-number { color: #28a745; }
        .stat-lost .stat-number { color: #6c757d; }

        /* Controls */
        .controls-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .refresh-btn {
            background: blue;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }

        .refresh-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(102, 126, 234, 0.4);
        }

        .filter-container {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .filter-select {
            padding: 10px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            background: white;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        /* Table Container */
        .table-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            animation: slideInUp 0.8s ease-out;
        }

        /* Table Styling */
        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .transactions-table th {
            background: blue;
            color: white;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }

        .transactions-table td {
            padding: 12px;
            border-bottom: 1px solid #e1e5e9;
            font-size: 14px;
            vertical-align: middle;
        }

        .transactions-table tr:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
            transition: all 0.3s ease;
        }

        .transactions-table tr:last-child td {
            border-bottom: none;
        }

        /* Status Badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            display: inline-block;
            min-width: 80px;
        }

        .status-returned {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
        }

        .status-borrowed {
            background: linear-gradient(135deg, #d1ecf1, #bee5eb);
            color: #0c5460;
        }

        .status-overdue {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
        }

        .status-lost {
            background: linear-gradient(135deg, #e2e3e5, #d6d8db);
            color: #383d41;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #6c757d;
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state-text {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        .empty-state-subtext {
            font-size: 0.9rem;
            opacity: 0.7;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .headerCont {
                padding: 15px 20px;
            }
            
            .main-container {
                padding: 20px 15px;
            }
            
            .table-container {
                padding: 20px;
            }
            
            .controls-container {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-container {
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .nav-links {
                gap: 15px;
            }
            
            .nav-links a {
                padding: 6px 12px;
                font-size: 14px;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .transactions-table {
                font-size: 12px;
            }
            
            .transactions-table th,
            .transactions-table td {
                padding: 8px 6px;
            }
            
            .status-badge {
                font-size: 11px;
                padding: 4px 8px;
                min-width: 60px;
            }
        }

        @media (max-width: 480px) {
            .headerCont {
                padding: 10px 15px;
            }
            
            .logo {
                font-size: 20px;
            }
            
            .nav-links {
                gap: 10px;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .table-container {
                padding: 15px;
            }
            
            .transactions-table {
                font-size: 11px;
            }
            
            .transactions-table th,
            .transactions-table td {
                padding: 6px 4px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="headerCont">
            <a href="addBooks.php" class="logo">Library System</a>
            <nav class="nav-links">
                <a href="addBooks.php">Add Books</a>
                <a href="accInfoStaff.php">Account</a>
                <a href="bookListStaff.php">Books</a>
                <a href="Schedules.php" class="active">Transactions</a>
                <a href="returnBook.php">Return Book</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-container">
        <div class="page-header">
            <h1 class="page-title">Transaction History</h1>
            <p class="record-count"><?php echo $result->num_rows; ?> total records</p>
        </div>

        <!-- Statistics Cards -->
        <?php
            // Calculate statistics
            $stats = ['borrowed' => 0, 'overdue' => 0, 'returned' => 0, 'lost' => 0];
            $result_copy = $conn->query($query);
            while ($row = $result_copy->fetch_assoc()) {
                switch($row['status']) {
                    case 'Barrowed':
                        $stats['borrowed']++;
                        break;
                    case 'Overdue':
                        $stats['overdue']++;
                        break;
                    case 'Returned':
                    case 'Available':
                        $stats['returned']++;
                        break;
                    case 'Lost':
                        $stats['lost']++;
                        break;
                }
            }
        ?>
        
        <div class="stats-container">
            <div class="stat-card stat-borrowed">
                <br>
                <div class="stat-number"><?php echo $stats['borrowed']; ?></div>
                <div class="stat-label">Currently Borrowed</div>
            </div>
            <div class="stat-card stat-overdue">
                <br>
                <div class="stat-number"><?php echo $stats['overdue']; ?></div>
                <div class="stat-label">Overdue Books</div>
            </div>
            <div class="stat-card stat-returned">
                <br>
                <div class="stat-number"><?php echo $stats['returned']; ?></div>
                <div class="stat-label">Books Returned</div>
            </div>
            <div class="stat-card stat-lost">
                <br>
                <div class="stat-number"><?php echo $stats['lost']; ?></div>
                <div class="stat-label">Lost Books</div>
            </div>
        </div>
         <div class="table-container" style="margin-top: 30px;">
            <h3>Send SMS Notification</h3>
            <form action="../sms/sms.php" method="POST" style="margin-top: 10px;">
                <select name='student' class= "filter-select">
                    <option value="">Select Recipient</option>
                    <?php
                        $studentQuery = "SELECT first_name FROM students";
                        $studentResult = $conn->query($studentQuery);
                        if ($studentResult->num_rows > 0) {
                            while ($student = $studentResult->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($student['first_name']) . '">' . htmlspecialchars($student['first_name']) .'</option>';
                            }
                        } else {
                            echo '<option value="" disabled>No students found</option>';
                        }
                    ?>
                <label for="msg">Message:</label><br>
                <input name="msg" id="msg" type="text" 
                       style="width: 100%; padding: 10px; margin: 10px 0; border-radius: 8px; border: 1px solid #ccc;">
                <br>
                <input type="submit" value="Send SMS" 
                       style="background: blue; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer;">
            </form>
        </div>
        <br>
        <!-- Controls -->
        <div class="controls-container">
            <div class="filter-container">
                <select class="filter-select" id="statusFilter">
                    <option value="">All Statuses</option>
                    <option value="Barrowed">Borrowed</option>
                    <option value="Overdue">Overdue</option>
                    <option value="Returned">Returned</option>
                    <option value="Lost">Lost</option>
                </select>
                <input type="text" class="filter-select" id="searchInput" placeholder="Search by name, book, or ISBN...">
            </div>
            <button class="refresh-btn" onclick="location.reload()">
                Refresh
            </button>
        </div>

        
        <!-- Transactions Table -->
        <div class="table-container">
            <table class="transactions-table" id="transactionsTable">
                <thead>
                    <tr>
                        <th>📝 Student Name</th>
                        <th>🆔 Student ID</th>
                        <th>📖 Book Title</th>
                        <th>📋 ISBN</th>
                        <th>📅 Date Borrowed</th>
                        <th>📊 Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php
                                // Determine status class for CSS styling
                                $status_class = '';
                                $status = $row['status'];
                                
                                switch($status) {
                                    case 'Available':
                                    case 'Returned':
                                        $status_class = 'status-returned';
                                        $display_status = 'Returned';
                                        break;
                                    case 'Barrowed':
                                        $status_class = 'status-borrowed';
                                        $display_status = 'Borrowed';
                                        break;
                                    case 'Overdue':
                                        $status_class = 'status-overdue';
                                        $display_status = 'Overdue';
                                        break;
                                    case 'Lost':
                                        $status_class = 'status-lost';
                                        $display_status = 'Lost';
                                        break;
                                    default:
                                        $status_class = 'status-borrowed';
                                        $display_status = $status;
                                }

                                // Format borrower name
                                $borrower_name = 'Unknown Student';
                                if (!empty($row['first_name']) || !empty($row['surname'])) {
                                    $borrower_name = trim($row['first_name'] . ' ' . $row['surname']);
                                }
                            ?>
                            <tr data-status="<?php echo $status; ?>">
                                <td><?php echo htmlspecialchars($borrower_name); ?></td>
                                <td><?php echo htmlspecialchars($row['barrower_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td><?php echo htmlspecialchars($row['ISBN']); ?></td>
                                <td><?php echo $row['barrowed_date'] ? date('M d, Y', strtotime($row['barrowed_date'])) : 'N/A'; ?></td>
                                <td>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo $display_status; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="empty-state">
                                <div class="empty-state-text">No transactions found</div>
                                <div class="empty-state-subtext">Transaction history will appear here once books are borrowed</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
    </div>

    <script>
        // Filter functionality
        const statusFilter = document.getElementById('statusFilter');
        const searchInput = document.getElementById('searchInput');
        const table = document.getElementById('transactionsTable');
        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

        function filterTable() {
            const statusValue = statusFilter.value.toLowerCase();
            const searchValue = searchInput.value.toLowerCase();

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const status = row.getAttribute('data-status');
                const text = row.textContent.toLowerCase();
                
                const statusMatch = !statusValue || (status && status.toLowerCase() === statusValue);
                const textMatch = !searchValue || text.includes(searchValue);
                
                if (statusMatch && textMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        }

        statusFilter.addEventListener('change', filterTable);
        searchInput.addEventListener('input', filterTable);

        // Auto-refresh every 30 seconds
        setInterval(function() {
            // Add subtle indicator for auto-refresh
            const refreshBtn = document.querySelector('.refresh-btn');
            refreshBtn.style.opacity = '0.7';
            setTimeout(() => {
                refreshBtn.style.opacity = '1';
            }, 500);
        }, 30000);

        // Smooth scroll for long tables
        if (rows.length > 10) {
            table.style.maxHeight = '500px';
            table.style.overflowY = 'auto';
        }
    </script>
</body>
</html>