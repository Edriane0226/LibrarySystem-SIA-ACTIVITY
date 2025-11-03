<?php
    include "../config/connection.php";
    session_start();

    // Check if staff is logged in
    if (!isset($_SESSION['staff_id'])) {
        header("Location: staffLogin.php");
        exit();
    }

    $search_isbn = "";
    $book_details = null;
    $success_message = "";
    $error_message = "";

    // Handle search by ISBN or Student ID
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search'])) {
        $search_value = trim($_POST['search_value']);
        $search_type = $_POST['search_type'];
        
        // Build query based on search type
        if ($search_type == 'isbn') {
            $query = "SELECT bb.barrowID, bb.ISBN, bb.status, bb.barrower_id, bb.barrowed_date, 
                             b.title, b.book_cover, b.author, s.first_name, s.surname
                      FROM barrowed_books bb 
                      JOIN books b ON bb.ISBN = b.ISBN 
                      LEFT JOIN students s ON bb.barrower_id = s.student_id
                      WHERE bb.ISBN = ? AND bb.status IN ('Barrowed', 'Overdue', 'Lost')";
        } else if ($search_type == 'student_id') { // Fixed: was missing this condition
            $query = "SELECT bb.barrowID, bb.ISBN, bb.status, bb.barrower_id, bb.barrowed_date, 
                             b.title, b.book_cover, b.author, s.first_name, s.surname
                      FROM barrowed_books bb 
                      JOIN books b ON bb.ISBN = b.ISBN 
                      LEFT JOIN students s ON bb.barrower_id = s.student_id
                      WHERE bb.barrower_id = ? AND bb.status IN ('Barrowed', 'Overdue', 'Lost')";
        }
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $search_value);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $book_details = $result->fetch_all(MYSQLI_ASSOC);
            $search_isbn = $search_value;
            $success_message = "Found " . $result->num_rows . " borrowed book(s).";
        } else {
            // Let's debug what's in the database
            $debug_query = "SELECT bb.barrowID, bb.ISBN, bb.status, bb.barrower_id, bb.barrowed_date, 
                           b.title, s.first_name, s.surname 
                           FROM barrowed_books bb 
                           LEFT JOIN books b ON bb.ISBN = b.ISBN 
                           LEFT JOIN students s ON bb.barrower_id = s.student_id";
            $debug_result = $conn->query($debug_query);
            
            $debug_info = "";
            if ($debug_result && $debug_result->num_rows > 0) {
                $debug_info = "<br><strong>Debug Info:</strong> Found " . $debug_result->num_rows . " total records:<br>";
                while ($row = $debug_result->fetch_assoc()) {
                    $debug_info .= "- ISBN: " . $row['ISBN'] . ", Status: " . $row['status'] . ", Student ID: " . $row['barrower_id'] . "<br>";
                }
            } else {
                $debug_info = "<br><strong>Debug Info:</strong> No records found in barrowed_books table.";
            }
            
            $error_message = "No borrowed books found with " . ($search_type == 'isbn' ? 'ISBN: ' : 'Student ID: ') . htmlspecialchars($search_value) . $debug_info;
        }
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $barrow_id = $_POST['barrow_id'];
    $new_status = $_POST['Status'];

    $update_query = "UPDATE barrowed_books SET status = ? WHERE barrowID = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("si", $new_status, $barrow_id);

    if ($update_stmt->execute()) {
        $success_message = "Book status updated successfully!";

        if (strtolower($new_status) == 'returned') {
            $query = "SELECT barrower_id FROM barrowed_books WHERE barrowID = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $barrow_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $barrower_id = $row['barrower_id'];
                include "../mailer/mailer.php";
            }
        }

        // Clear details after update
        $book_details = null;
        $search_isbn = "";
    } else {
        $error_message = "Error updating book status.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Book - Library Management System</title>
    
    <!-- Barcode Scanner Library -->
    <script src="https://unpkg.com/@zxing/library@latest/umd/index.min.js"></script>
    
    <style>
        /* Keep all the existing CSS styles... */
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
        }

        .nav-links a.active {
            background: blue;
            color: white;
        }

        /* Main Content */
        .main-container {
            margin-top: 80px;
            padding: 30px 20px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        .page-title {
            text-align: center;
            color: white;
            font-size: 2.5rem;
            margin-bottom: 30px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        /* Search Container */
        .search-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }


        .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: end;
        }

        .form-group {
            flex: 1;
            min-width: 200px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
        }

        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        .search-btn {
            background: blue;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
            height: fit-content;
        }

        .search-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(102, 126, 234, 0.4);
        }

        /* Barcode Scanner Styles */
        .barcode-scanner-container {
            display: flex;
            gap: 10px;
            align-items: end;
        }

        .scanner-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            height: fit-content;
        }

        .scanner-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
        }

        .clear-btn {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            height: fit-content;
        }

        .clear-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);
        }

        .scanner-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .scanner-content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            position: relative;
        }

        .scanner-video {
            width: 100%;
            max-width: 400px;
            height: 300px;
            border: 2px solid #ddd;
            border-radius: 10px;
            margin: 20px 0;
            background: #f8f9fa;
        }

        .scanner-close {
            position: absolute;
            top: 15px;
            right: 20px;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .scanner-close:hover {
            color: #333;
        }

        .scanner-result {
            margin-top: 15px;
            padding: 10px;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            display: none;
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
            animation: slideInDown 0.5s ease-out;
        }

        @keyframes slideInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Results Container */
        .results-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            animation: slideInUp 0.8s ease-out;
        }

        .section-title {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }

        /* Table Styling */
        .books-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .books-table th {
            background: blue;
            color: white;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }

        .books-table td {
            padding: 12px;
            border-bottom: 1px solid #e1e5e9;
            font-size: 14px;
            vertical-align: middle;
        }

        .books-table tr:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
            transition: all 0.3s ease;
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

        .status-borrowed {
            background: linear-gradient(135deg, #d1ecf1, #bee5eb);
            color: #0c5460;
        }

        .status-overdue {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
        }

        .status-lost {
            background: linear-gradient(135deg, #e9ecef, #dee2e6);
            color: #495057;
        }

        /* Action Form */
        .action-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: center;
        }

        .action-form select {
            padding: 8px 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            transition: all 0.3s ease;
        }

        .action-form select:focus {
            outline: none;
            border-color: #667eea;
        }

        .update-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .update-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
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
                <a href="Schedules.php">Transactions</a>
                <a href="returnBook.php" class="active">Return Book</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-container">
        <h1 class="page-title">Return Book Management</h1>
        
        <!-- Alert Messages -->
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                 <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error">
                 <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Search Form -->
        <div class="search-container">
            <form method="POST" class="search-form">
                <div class="form-group">
                    <label for="search_type">Search Method</label>
                    <select name="search_type" id="search_type">
                        <option value="isbn">Search by ISBN</option>
                        <option value="student_id">Search by Student ID</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="search_value">Enter Value</label>
                    <div class="barcode-scanner-container">
                        <input type="text" name="search_value" id="search_value" 
                               placeholder="Scan barcode or enter ISBN/Student ID manually" 
                               value="<?php echo htmlspecialchars($search_isbn); ?>" 
                               autocomplete="off" autofocus required>
                        <button type="button" id="scanBarcodeBtn" class="scanner-btn">
                            📷 Camera Scan
                        </button>
                        <button type="button" id="clearBtn" class="clear-btn" onclick="clearSearchValue()">
                            ✖ Clear
                        </button>
                    </div>
                    <small class="form-text text-muted">
                        <i class="fas fa-barcode"></i> Use camera scanner, hardware scanner, or type manually
                    </small>
                </div>
                
                <button type="submit" name="search" class="search-btn">
                     Search Books
                </button>
            </form>
        </div>

        <!-- Camera Barcode Scanner Modal -->
        <div id="scannerModal" class="scanner-modal">
            <div class="scanner-content">
                <button class="scanner-close" id="closeScannerBtn">&times;</button>
                <h3>Camera Barcode Scanner</h3>
                <p>Position the barcode within the camera view</p>
                <video id="scannerVideo" class="scanner-video" autoplay playsinline></video>
                <div id="scannerResult" class="scanner-result">
                    <strong>Scanned: </strong><span id="scannedCode"></span>
                </div>
                <div style="margin-top: 15px;">
                    <button type="button" id="stopScannerBtn" class="scanner-btn" style="background: #dc3545;">
                        Stop Scanner
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Results Section -->
        <div class="results-container">
            <h2 class="section-title">Borrowed Books Details</h2>

            <table class="books-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Student ID</th>
                        <th>Book Title</th>
                        <th>Author</th>
                        <th>ISBN</th>
                        <th>Date Borrowed</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($book_details && count($book_details) > 0): ?>
                        <?php foreach ($book_details as $book): ?>
                            <?php
                                // Format student name
                                $student_name = 'Unknown Student';
                                if (!empty($book['first_name']) || !empty($book['surname'])) {
                                    $student_name = trim($book['first_name'] . ' ' . $book['surname']);
                                }
                                
                                // Determine status class
                                $status_class = 'status-badge ';
                                switch($book['status']) {
                                    case 'Barrowed':
                                        $status_class .= 'status-borrowed';
                                        break;
                                    case 'Overdue':
                                        $status_class .= 'status-overdue';
                                        break;
                                    case 'Lost':
                                        $status_class .= 'status-lost';
                                        break;
                                }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student_name); ?></td>
                                <td><?php echo htmlspecialchars($book['barrower_id']); ?></td>
                                <td><?php echo htmlspecialchars($book['title']); ?></td>
                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                <td><?php echo htmlspecialchars($book['ISBN']); ?></td>
                                <td><?php echo $book['barrowed_date'] ? date('M d, Y', strtotime($book['barrowed_date'])) : 'N/A'; ?></td>
                                <td>
                                    <span class="<?php echo $status_class; ?>">
                                        <?php echo htmlspecialchars($book['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" class="action-form">
                                        <input type="hidden" name="barrow_id" value="<?php echo $book['barrowID']; ?>">
                                        <select name="Status" required>
                                            <option value="">Select Status</option>
                                            <option value="Returned">Returned</option>
                                            <option value="Overdue">Overdue</option>
                                            <option value="Lost">Lost</option>
                                        </select>
                                        <button type="submit" name="update_status" class="update-btn">
                                            Update
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: #6c757d; font-style: italic; padding: 30px;">
                                Search for borrowed books using ISBN or Student ID to see details
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Combined Hardware and Camera Barcode Scanner Support
        let scanBuffer = '';
        let scanTimeout;
        let codeReader = null;
        let scanning = false;
        
        const searchInput = document.getElementById('search_value');
        const scannerModal = document.getElementById('scannerModal');
        const scannerVideo = document.getElementById('scannerVideo');
        const scanBarcodeBtn = document.getElementById('scanBarcodeBtn');
        const closeScannerBtn = document.getElementById('closeScannerBtn');
        const stopScannerBtn = document.getElementById('stopScannerBtn');
        const scannerResult = document.getElementById('scannerResult');
        const scannedCode = document.getElementById('scannedCode');

        // Auto-focus search input
        searchInput.focus();
        
        // Search type change handling
        document.getElementById('search_type').addEventListener('change', function() {
            if (this.value === 'isbn') {
                searchInput.placeholder = 'Scan barcode or enter ISBN manually';
            } else {
                searchInput.placeholder = 'Enter Student ID';
            }
            searchInput.focus();
        });

        // Clear search input function
        function clearSearchValue() {
            searchInput.value = '';
            searchInput.focus();
        }

        // Check if scanned code looks like an ISBN
        function isISBN(code) {
            // Remove hyphens and spaces
            const cleanCode = code.replace(/[-\s]/g, '');
            // Check if it's 10 or 13 digits
            return /^\d{10}$/.test(cleanCode) || /^\d{13}$/.test(cleanCode);
        }

        // Initialize camera barcode reader
        function initBarcodeReader() {
            if (typeof ZXing !== 'undefined') {
                codeReader = new ZXing.BrowserMultiFormatReader();
                console.log('ZXing code reader initialized');
            } else {
                console.error('ZXing library not loaded');
                alert('Camera barcode scanner library not loaded. Please refresh the page.');
            }
        }

        // Start camera scanning
        async function startCameraScanning() {
            if (!codeReader) {
                initBarcodeReader();
                if (!codeReader) return;
            }

            try {
                scanning = true;
                scannerModal.style.display = 'flex';
                
                // Get available video devices
                const videoInputDevices = await codeReader.listVideoInputDevices();
                
                if (videoInputDevices.length === 0) {
                    throw new Error('No camera devices found');
                }

                // Use the first available camera (usually back camera on mobile)
                const selectedDeviceId = videoInputDevices[0].deviceId;
                
                console.log('Starting camera barcode scanning...');
                
                // Start decoding from video device
                codeReader.decodeFromVideoDevice(selectedDeviceId, scannerVideo, (result, err) => {
                    if (result) {
                        console.log('Camera barcode detected:', result.text);
                        
                        // Update the search input with scanned code
                        searchInput.value = result.text;
                        
                        // Show result in modal
                        scannedCode.textContent = result.text;
                        scannerResult.style.display = 'block';
                        
                        // Set search type to ISBN if it looks like an ISBN
                        if (isISBN(result.text)) {
                            document.getElementById('search_type').value = 'isbn';
                            document.getElementById('search_type').dispatchEvent(new Event('change'));
                        }
                          
                    
                        // Auto-close modal after 2 seconds
                        setTimeout(() => {
                            stopCameraScanning();
                        }, 2000);

                    }
                    
                    if (err && !(err instanceof ZXing.NotFoundException)) {
                        console.error('Camera scanning error:', err);
                    }
                });
                
            } catch (err) {
                console.error('Error starting camera:', err);
                alert('Error accessing camera: ' + err.message + '\nPlease ensure camera permissions are granted.');
                stopCameraScanning();
            }
        }

        // Stop camera scanning
        function stopCameraScanning() {
            if (codeReader && scanning) {
                codeReader.reset();
                scanning = false;
            }
            scannerModal.style.display = 'none';
            scannerResult.style.display = 'none';
            searchInput.focus();
        }

        // Handle hardware barcode scanner input
        function handleHardwareBarcodeScan(scannedData) {
            // Clean the scanned data
            const cleanData = scannedData.trim();
            
            if (cleanData.length > 0) {
                console.log('Hardware barcode scanned:', cleanData);
                
                // Set the value in the search input
                searchInput.value = cleanData;
                
                // Auto-detect if it's an ISBN and set search type
                if (isISBN(cleanData)) {
                    document.getElementById('search_type').value = 'isbn';
                    document.getElementById('search_type').dispatchEvent(new Event('change'));
                }
                
                // Focus on search input
                searchInput.focus();
                
                // Show confirmation and auto-submit option
                const confirmMsg = `Hardware scanner detected: ${cleanData}\n\nPress OK to search immediately, or Cancel to edit first.`;
                if (confirm(confirmMsg)) {
                    document.querySelector('.search-form').submit();
                }
            }
        }

        // Camera scanner event listeners
        scanBarcodeBtn.addEventListener('click', startCameraScanning);
        closeScannerBtn.addEventListener('click', stopCameraScanning);
        stopScannerBtn.addEventListener('click', stopCameraScanning);

        // Close camera modal when clicking outside
        scannerModal.addEventListener('click', function(e) {
            if (e.target === scannerModal) {
                stopCameraScanning();
            }
        });

        // Hardware scanner: Listen for rapid keystrokes (typical of barcode scanners)
        document.addEventListener('keypress', function(e) {
            // Only process if the search input is focused or if it's likely a scanner
            if (document.activeElement === searchInput || scanBuffer.length > 0) {
                
                // Clear timeout if a new key is pressed
                clearTimeout(scanTimeout);
                
                // Add character to buffer if it's printable and not Enter
                if (e.key && e.key.length === 1 && e.key !== '\r' && e.key !== '\n') {
                    scanBuffer += e.key;
                    
                    // Set timeout to detect end of scanning (scanners are very fast)
                    scanTimeout = setTimeout(() => {
                        // If we have accumulated enough characters, it's likely a scan
                        if (scanBuffer.length >= 8) {
                            handleHardwareBarcodeScan(scanBuffer);
                        }
                        scanBuffer = '';
                    }, 50); // 50ms timeout - scanners are much faster than human typing
                }
            }
        });

        // Hardware scanner: Handle Enter key specifically (many scanners end with Enter)
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && scanBuffer.length > 0) {
                clearTimeout(scanTimeout);
                e.preventDefault();
                
                // If we have a substantial buffer, treat it as a scan
                if (scanBuffer.length >= 8) {
                    handleHardwareBarcodeScan(scanBuffer);
                }
                scanBuffer = '';
            }
            
            // Keyboard shortcut to focus and clear search input (F2)
            if (e.key === 'F2') {
                e.preventDefault();
                clearSearchValue();
            }

            // Keyboard shortcut for camera scanner (Alt + C)
            if (e.altKey && e.key.toLowerCase() === 'c') {
                e.preventDefault();
                if (!scanning) {
                    startCameraScanning();
                } else {
                    stopCameraScanning();
                }
            }
        });

        // Hardware scanner: Alternative detection - Monitor input changes for rapid entry
        let lastInputTime = 0;
        let inputBuffer = '';

        searchInput.addEventListener('input', function(e) {
            const currentTime = Date.now();
            const value = e.target.value;
            
            // If typing very fast (less than 100ms between characters), likely a scanner
            if (currentTime - lastInputTime < 100 && value.length > inputBuffer.length) {
                inputBuffer = value;
                
                // If we reach a reasonable barcode length and it's all digits/mixed
                if (value.length >= 10 && (value.match(/^\d+$/) || value.match(/^[A-Z0-9]+$/))) {
                    // Auto-set to ISBN search if it looks like an ISBN
                    if (isISBN(value)) {
                        document.getElementById('search_type').value = 'isbn';
                    }
                }
            } else {
                inputBuffer = value;
            }
            
            lastInputTime = currentTime;
        });

        // Initialize camera reader and focus search input when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initBarcodeReader();
            searchInput.focus();
        });

        // Re-focus on search input when clicking anywhere on the page (helps with scanner focus)
        document.addEventListener('click', function(e) {
            // Don't interfere with button clicks or modal
            if (!e.target.matches('button') && 
                !e.target.matches('input[type="submit"]') && 
                !e.target.closest('.scanner-modal')) {
                setTimeout(() => searchInput.focus(), 100);
            }
        });
    </script>
</body>
</html>