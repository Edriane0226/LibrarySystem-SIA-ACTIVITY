<?php
    include "../config/connection.php";
    session_start();

    // Check if staff is logged in
    if (!isset($_SESSION['staff_id'])) {
        header("Location: staffLogin.php");
        exit();
    }

    // Initialize search parameters
    $search_query = "";
    $year_filter = "";
    $availability_filter = "";

    // Process search form
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $search_query = isset($_POST['search']) ? $_POST['search'] : '';
        $year_filter = isset($_POST['year']) ? $_POST['year'] : '';
        $availability_filter = isset($_POST['availability']) ? $_POST['availability'] : '';
    }

    // Build SQL query with filters
    $sql = "SELECT DISTINCT b.title, b.author, b.ISBN, b.publication_year, b.no_copies, b.shelf_loc, b.book_cover,
                   COALESCE(bb.status, 'Available') as status
            FROM books b 
            LEFT JOIN barrowed_books bb ON b.ISBN = bb.ISBN 
            WHERE 1=1";

    $params = [];
    $types = "";

    // Add search filter
    if (!empty($search_query)) {
        $sql .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.ISBN LIKE ?)";
        $search_param = "%$search_query%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "sss";
    }

    // Add year filter
    if (!empty($year_filter)) {
        $sql .= " AND YEAR(b.publication_year) = ?";
        $params[] = $year_filter;
        $types .= "i";
    }

    // Add availability filter
    if (!empty($availability_filter)) {
        if ($availability_filter == 'Available') {
            $sql .= " AND (bb.status IS NULL OR bb.status = 'Available' OR bb.status = 'Returned')";
        } elseif ($availability_filter == 'Unavailable') {
            $sql .= " AND bb.status IN ('Barrowed', 'Overdue', 'Lost')";
        }
    }

    $sql .= " ORDER BY b.title";

    // Execute query
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    // Get unique years for dropdown
    $years_query = "SELECT DISTINCT YEAR(publication_year) as year FROM books WHERE publication_year IS NOT NULL ORDER BY year DESC";
    $years_result = $conn->query($years_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Catalog - Library Management System</title>
    
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
            color: #5e69ce;
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

        .nav-links a:hover, .nav-links a.active {
            background: linear-gradient(135deg, #5e69ce 0%, #764ba2 100%);
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
            gap: 30px;
            padding-left: 20px;
            padding-right: 20px;
        }

        .search-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            width: 100%;
            max-width: 800px;
        }

        .search-card h1 {
            color: #5e69ce;
            margin-bottom: 25px;
            font-size: 28px;
            text-align: center;
            position: relative;
        }

        .search-card h1::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: linear-gradient(135deg, #5e69ce 0%, #764ba2 100%);
            border-radius: 2px;
        }

        .search-form {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 20px;
            margin-bottom: 20px;
        }

        .search-input {
            padding: 15px 20px;
            border: 2px solid #e1e8ed;
            border-radius: 15px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #5e69ce;
            box-shadow: 0 0 20px rgba(94, 105, 206, 0.2);
        }

        .search-btn {
            background: linear-gradient(135deg, #5e69ce 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 15px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(94, 105, 206, 0.4);
        }

        .filters-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            color: #5e69ce;
            font-weight: 600;
            font-size: 14px;
        }

        .filter-group select {
            padding: 12px;
            border: 2px solid #e1e8ed;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .filter-group select:focus {
            outline: none;
            border-color: #5e69ce;
            box-shadow: 0 0 15px rgba(94, 105, 206, 0.2);
        }

        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }

        .radio-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .radio-item input[type="radio"] {
            accent-color: #5e69ce;
        }

        /* Results Section */
        .results-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            width: 100%;
            max-width: 1200px;
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .results-header h2 {
            color: #5e69ce;
            font-size: 24px;
        }

        .results-count {
            background: linear-gradient(135deg, #5e69ce 0%, #764ba2 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }

        /* Table Styling */
        .table-wrapper {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
        }

        .books-table {
            width: 100%;
            border-collapse: collapse;
        }

        .books-table thead {
            background: linear-gradient(135deg, #5e69ce 0%, #764ba2 100%);
        }

        .books-table thead th {
            color: white;
            padding: 20px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .books-table tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s ease;
        }

        .books-table tbody tr:hover {
            background: rgba(94, 105, 206, 0.05);
        }

        .books-table tbody td {
            padding: 15px;
            color: #555;
            vertical-align: middle;
        }

        /* Book Cover */
        .book-cover {
            width: 50px;
            height: 70px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
        }

        .book-cover:hover {
            transform: scale(1.2);
        }

        .no-cover {
            width: 50px;
            height: 70px;
            background: linear-gradient(135deg, #e9ecef 0%, #f8f9fa 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #6c757d;
            border: 2px dashed #dee2e6;
            text-align: center;
            font-weight: bold;
        }

        /* Status Badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
            text-align: center;
            display: inline-block;
            min-width: 80px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .status-available {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
        }

        .status-borrowed {
            background: linear-gradient(135deg, #3498db 0%, #5dade2 100%);
            color: white;
        }

        .status-returned {
            background: linear-gradient(135deg, #27ae60 0%, #58d68d 100%);
            color: white;
        }

        .status-overdue {
            background: linear-gradient(135deg, #e74c3c 0%, #ec7063 100%);
            color: white;
        }

        .status-lost {
            background: linear-gradient(135deg, #7f8c8d 0%, #95a5a6 100%);
            color: white;
        }

        /* No Results */
        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .no-results .icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .search-form {
                grid-template-columns: 1fr;
            }
            
            .filters-row {
                grid-template-columns: 1fr 1fr;
            }
        }

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

            .search-card, .results-card {
                padding: 20px;
            }

            .filters-row {
                grid-template-columns: 1fr;
            }

            .radio-group {
                flex-direction: column;
                gap: 10px;
            }

            .results-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .books-table thead th,
            .books-table tbody td {
                padding: 10px 8px;
                font-size: 12px;
            }

            .book-cover, .no-cover {
                width: 40px;
                height: 56px;
            }
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

        .search-card, .results-card {
            animation: fadeInUp 0.6s ease-out;
        }

        .results-card {
            animation-delay: 0.1s;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="headerCont">
            <a href="#" class="logo">📚 Library System</a>
            <nav class="nav-links">
                <a href="addBooks.php">Add Books</a>
                <a href="accInfoStaff.php">Account</a>
                <a href="bookListStaff.php" class="active">Books</a>
                <a href="Schedules.php">Transactions</a>
                <a href="returnBook.php">Return Book</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Search Card -->
        <div class="search-card">
            <h1>🔍 Book Search & Filters</h1>
            
            <form method="POST" class="search-form">
                <input type="text" name="search" class="search-input" 
                       placeholder="Search by title, author, or ISBN..." 
                       value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="search-btn">🔍 Search</button>
            </form>

            <div class="filters-row">
                <div class="filter-group">
                    <label for="year">📅 Publication Year</label>
                    <select name="year" id="year">
                        <option value="">All Years</option>
                        <?php while ($year_row = $years_result->fetch_assoc()): ?>
                            <option value="<?php echo $year_row['year']; ?>" 
                                    <?php echo ($year_filter == $year_row['year']) ? 'selected' : ''; ?>>
                                <?php echo $year_row['year']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label>📊 Availability Status</label>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" name="availability" value="" id="any" 
                                   <?php echo ($availability_filter == '') ? 'checked' : ''; ?>>
                            <label for="any">Any</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" name="availability" value="Available" id="available"
                                   <?php echo ($availability_filter == 'Available') ? 'checked' : ''; ?>>
                            <label for="available">Available</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" name="availability" value="Unavailable" id="unavailable"
                                   <?php echo ($availability_filter == 'Unavailable') ? 'checked' : ''; ?>>
                            <label for="unavailable">Unavailable</label>
                        </div>
                    </div>
                </div>
            </div>
            </form>
        </div>

        <!-- Results Card -->
        <div class="results-card">
            <div class="results-header">
                <h2>📖 Book Catalog</h2>
                <div class="results-count">
                    <?php echo $result->num_rows; ?> books found
                </div>
            </div>

            <div class="table-wrapper">
                <table class="books-table">
                    <thead>
                        <tr>
                            <th>Cover</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>ISBN</th>
                            <th>Year</th>
                            <th>Copies</th>
                            <th>Location</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($book = $result->fetch_assoc()): ?>
                                <?php
                                    $status_class = 'status-available';
                                    $status = $book['status'];
                                    
                                    switch($status) {
                                        case 'Available':
                                        case 'Returned':
                                            $status_class = 'status-available';
                                            $display_status = 'Available';
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
                                            $status_class = 'status-available';
                                            $display_status = 'Available';
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($book['book_cover'])): ?>
                                            <img src="../uploads/<?php echo htmlspecialchars($book['book_cover']); ?>" 
                                                 alt="Book Cover" class="book-cover">
                                        <?php else: ?>
                                            <div class="no-cover">No Image</div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($book['title']); ?></td>
                                    <td><?php echo htmlspecialchars($book['author']); ?></td>
                                    <td><?php echo htmlspecialchars($book['ISBN']); ?></td>
                                    <td><?php echo htmlspecialchars($book['publication_year']); ?></td>
                                    <td><?php echo htmlspecialchars($book['no_copies']); ?></td>
                                    <td><?php echo htmlspecialchars($book['shelf_loc']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo $display_status; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="no-results">
                                    <div class="icon">📚</div>
                                    <h3>No books found</h3>
                                    <p>Try adjusting your search criteria or filters</p>
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
