<?php
include "../config/connection.php";
session_start();

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: studentLogin.php");
    exit();
}

$student_id = $_SESSION['student_id'];
// Set generic user session variables for video call system
$_SESSION['user_id'] = $student_id;
$_SESSION['user_type'] = 'student';

// student info
$query = "SELECT * FROM students WHERE student_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student_data = $result->fetch_assoc();

if (!$student_data) {
    session_destroy();
    header("Location: studentLogin.php");
    exit();
}

// Get staff data
$staffs_result = $conn->query("SELECT staff_id, first_name, surname FROM staff");

// Get other students data (excluding current student)
$students_stmt = $conn->prepare("SELECT student_id, first_name, surname FROM students WHERE student_id != ?");
$students_stmt->bind_param("s", $student_id);
$students_stmt->execute();
$students_result = $students_stmt->get_result();

// Get borrowing history 
$history_query = "SELECT b.title, bb.barrowed_date, bb.status, bb.barrowID
                  FROM barrowed_books bb 
                  JOIN books b ON bb.ISBN = b.ISBN 
                  WHERE bb.barrower_id = ?
                  ORDER BY bb.barrowed_date DESC";
$history_stmt = $conn->prepare($history_query);
$history_stmt->bind_param("s", $student_id);
$history_stmt->execute();
$history_result = $history_stmt->get_result();

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: studentLogin.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Information - Library Management System</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height:100vh; color:#333;
        }
        .header {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            position: fixed; top:0; left:0; right:0; z-index:1000;
        }
        .headerCont {
            display:flex; justify-content:space-between; align-items:center;
            padding:15px 30px; max-width:1200px; margin:0 auto;
        }
        .logo { font-size:24px; font-weight:bold; color:blue; text-decoration:none; }
        .nav-links { display:flex; gap:30px; align-items:center; }
        .nav-links a {
            text-decoration:none; color:#333; font-weight:500;
            padding:10px 15px; border-radius:8px; transition:all 0.3s ease;
        }
        .nav-links a.active { background:blue; color:white; transform:translateY(-2px);
            box-shadow:0 4px 15px rgba(94,105,206,0.3);}
        .main-content {
            padding-top:100px; padding-bottom:50px; display:flex;
            flex-direction:column; align-items:center; gap:30px;
            padding-left:20px; padding-right:20px;
        }
        .info-card,.history-card {
            background:rgba(255,255,255,0.95);
            backdrop-filter:blur(10px);
            border-radius:20px; padding:30px;
            box-shadow:0 10px 40px rgba(0,0,0,0.1);
            border:1px solid rgba(255,255,255,0.2);
            width:100%; max-width:800px;
        }
        .card-title { color:blue; margin-bottom:25px; font-size:24px; text-align:center; }
        .info-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:20px; margin-bottom:25px; }
        .info-item { background:rgba(94,105,206,0.05); padding:20px; border-radius:15px; }
        .info-label { color:blue; font-weight:600; font-size:14px; margin-bottom:5px; }
        .info-value { color:#333; font-size:18px; font-weight:500; }
        .logout-btn {
            background: linear-gradient(135deg,#e74c3c 0%,#ec7063 100%);
            color:white; padding:12px 25px; border:none; border-radius:10px;
            font-size:16px; font-weight:bold; cursor:pointer;
            text-decoration:none; display:inline-block; transition:all 0.3s ease;
        }
        .logout-btn:hover { transform:translateY(-2px);
            box-shadow:0 6px 20px rgba(231,76,60,0.4);}
        .table-wrapper { background:white; border-radius:15px; overflow:hidden;
            box-shadow:0 5px 25px rgba(0,0,0,0.1); margin-bottom:20px;}
        .history-table { width:100%; border-collapse:collapse; }
        .history-table thead { background:blue; }
        .history-table thead th {
            color:white; padding:20px 15px; text-align:left;
            font-weight:600; font-size:14px; text-transform:uppercase;
        }
        .history-table tbody tr { border-bottom:1px solid #f0f0f0; transition:all 0.3s ease; }
        .history-table tbody tr:hover { background:rgba(94,105,206,0.05); }
        .history-table tbody td { padding:15px; color:#555; vertical-align:middle; }
        .status-badge { padding:6px 12px; border-radius:20px; font-weight:bold; font-size:12px;
            text-align:center; display:inline-block; min-width:80px;}
        .status-returned { background:linear-gradient(135deg,#27ae60 0%,#2ecc71 100%); color:white;}
        .status-borrowed { background:linear-gradient(135deg,#3498db 0%,#5dade2 100%); color:white;}
        .status-overdue { background:linear-gradient(135deg,#e74c3c 0%,#ec7063 100%); color:white;}
        .status-lost { background:linear-gradient(135deg,#7f8c8d 0%,#95a5a6 100%); color:white;}
        .refresh-btn {
            background:blue; color:white; padding:12px 25px;
            border:none; border-radius:10px; font-size:16px;
            font-weight:bold; cursor:pointer; transition:all 0.3s ease;
        }
        .refresh-btn:hover { transform:translateY(-2px);}
        .no-data { text-align:center; padding:40px 20px; color:#666; }
        /* Modern Messenger Chat Widget */
        .chat-widget {
            position: fixed; bottom: 20px; right: 20px;
            width: 350px; height: 500px; background: white;
            border-radius: 15px; box-shadow: 0 8px 32px rgba(0,0,0,0.15);
            font-size: 14px; z-index: 2000; overflow: hidden;
            display: flex; flex-direction: column;
            border: 1px solid #e1e5e9;
        }
        .chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; padding: 15px 20px;
            font-weight: 600; display: flex; align-items: center;
            justify-content: space-between; border-radius: 15px 15px 0 0;
        }
        .chat-header h3 { margin: 0; font-size: 16px; }
        .chat-status { font-size: 12px; opacity: 0.8; }
        .chat-controls {
            display: flex; gap: 10px; padding: 15px 20px;
            background: #f8f9fa; border-bottom: 1px solid #e1e5e9;
        }
        .user-type-select { flex: 1; }
        .user-type-select select {
            width: 100%; padding: 10px 15px; border: 1px solid #ddd;
            border-radius: 25px; font-size: 14px; background: white;
            outline: none; transition: all 0.3s ease; margin-bottom: 10px;
        }
        .user-type-select select:focus {
            border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .user-select-wrapper { flex: 1; }
        .user-select-wrapper select {
            width: 100%; padding: 10px 15px; border: 1px solid #ddd;
            border-radius: 25px; font-size: 14px; background: white;
            outline: none; transition: all 0.3s ease;
        }
        .user-select-wrapper select:focus {
            border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .call-btn {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white; border: none; border-radius: 25px;
            padding: 10px 20px; font-weight: 600; cursor: pointer;
            transition: all 0.3s ease; white-space: nowrap;
        }
        .call-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4); }
        .call-btn:disabled {
            background: #6c757d; cursor: not-allowed; transform: none;
            box-shadow: none;
        }
        #chat-box {
            flex: 1; overflow-y: auto; padding: 20px;
            background: #f8f9fa; display: flex; flex-direction: column;
            gap: 15px;
        }
        .message {
            display: flex; align-items: flex-end; gap: 8px;
            max-width: 85%; animation: messageSlide 0.3s ease;
        }
        .message.sent { align-self: flex-end; flex-direction: row-reverse; }
        .message.received { align-self: flex-start; }
        .message-avatar {
            width: 32px; height: 32px; border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: bold; font-size: 12px;
            flex-shrink: 0;
        }
        .message-content {
            background: white; padding: 12px 16px; border-radius: 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: relative; word-wrap: break-word;
        }
        .message.sent .message-content {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white; border-radius: 18px 18px 4px 18px;
        }
        .message.received .message-content {
            background: white; color: #333;
            border-radius: 18px 18px 18px 4px;
        }
        .message-time {
            font-size: 11px; opacity: 0.7; margin-top: 4px;
            text-align: right;
        }
        .message.sent .message-time { text-align: left; }
        .typing-indicator {
            display: none; align-items: center; gap: 8px;
            padding: 10px 15px; color: #666; font-style: italic;
        }
        .typing-dots {
            display: flex; gap: 3px;
        }
        .typing-dots span {
            width: 6px; height: 6px; background: #667eea;
            border-radius: 50%; animation: typing 1.4s infinite;
        }
        .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
        .typing-dots span:nth-child(3) { animation-delay: 0.4s; }
        .chat-footer {
            padding: 15px 20px; background: white;
            border-top: 1px solid #e1e5e9; border-radius: 0 0 15px 15px;
        }
        .message-input-wrapper {
            display: flex; gap: 10px; align-items: flex-end;
        }
        .message-input {
            flex: 1; padding: 12px 16px; border: 1px solid #ddd;
            border-radius: 25px; font-size: 14px; outline: none;
            resize: none; max-height: 100px; min-height: 40px;
            transition: all 0.3s ease; font-family: inherit;
        }
        .message-input:focus {
            border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .send-btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white; border: none; border-radius: 50%;
            width: 40px; height: 40px; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.3s ease; flex-shrink: 0;
        }
        .send-btn:hover { transform: scale(1.1); }
        .send-btn:disabled {
            background: #6c757d; cursor: not-allowed;
            transform: none;
        }
        .no-messages {
            text-align: center; color: #666; padding: 40px 20px;
            font-style: italic;
        }
        .online-indicator {
            width: 8px; height: 8px; background: #28a745;
            border-radius: 50%; margin-left: 8px;
        }
        @keyframes messageSlide {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-10px); }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="headerCont">
            <a href="#" class="logo">Library System</a>
            <nav class="nav-links">
                <a href="index.php">Home</a>
                <a href="accInfo.php" class="active">Account</a>
                <a href="bookList.php">Books</a>
                <a href="returnbooks.php">Return</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="info-card">
            <h1 class="card-title">Account Information</h1>
            <div class="info-grid">
                <div class="info-item"><div class="info-label">Student ID</div>
                    <div class="info-value"><?= htmlspecialchars($student_data['student_id']); ?></div></div>
                <div class="info-item"><div class="info-label">Surname</div>
                    <div class="info-value"><?= htmlspecialchars($student_data['surname']); ?></div></div>
                <div class="info-item"><div class="info-label">First Name</div>
                    <div class="info-value"><?= htmlspecialchars($student_data['first_name']); ?></div></div>
                <div class="info-item"><div class="info-label">Middle Initial</div>
                    <div class="info-value"><?= htmlspecialchars($student_data['initial'] ?? 'N/A'); ?></div></div>
            </div>
            <div style="text-align:center;">
                <a href="accInfo.php?logout=true" class="logout-btn">Log Out</a>
            </div>
        </div>

        <div class="history-card">
            <h1 class="card-title">Borrowing History</h1>
            <div class="table-wrapper">
                <table class="history-table">
                    <thead><tr><th>Book Title</th><th>Date Borrowed</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php if ($history_result->num_rows > 0): ?>
                            <?php while ($row = $history_result->fetch_assoc()): ?>
                                <?php
                                    $status_class = 'status-returned'; $display_status = $row['status'];
                                    switch($row['status']) {
                                        case 'Returned': case 'Available': $status_class='status-returned'; $display_status='Returned'; break;
                                        case 'Barrowed': $status_class='status-borrowed'; $display_status='Borrowed'; break;
                                        case 'Overdue': $status_class='status-overdue'; $display_status='Overdue'; break;
                                        case 'Lost': $status_class='status-lost'; $display_status='Lost'; break;
                                    }
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['title']); ?></td>
                                    <td><?= $row['barrowed_date'] ? date('M j, Y', strtotime($row['barrowed_date'])) : 'N/A'; ?></td>
                                    <td><span class="status-badge <?= $status_class; ?>"><?= $display_status; ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="no-data"><h3>No borrowing history found</h3><p>You haven't borrowed any books yet</p></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div style="text-align:center;">
                <button class="refresh-btn" onclick="location.reload()">Refresh</button>
            </div>
        </div>
    </main>

    <!-- Modern Messenger Chat Widget -->
    <div class="chat-widget">
        <div class="chat-header">
            <h3>💬 Messages</h3>
            <div class="chat-status" id="chatStatus">Select someone to start chatting</div>
        </div>
        
        <div class="chat-controls">
            <div class="user-type-select">
                <select id="userTypeSelect">
                    <option value="">Select user type...</option>
                    <option value="staff">Staff Members</option>
                    <option value="student">Other Students</option>
                </select>
            </div>
            <div class="user-select-wrapper">
                <select id="userSelect" disabled>
                    <option value="">First select user type</option>
                </select>
            </div>
            <button id="callBtn" class="call-btn" disabled>
                📞 Call
            </button>
        </div>
        
        <div id="chat-box">
            <div class="no-messages">
                <div style="font-size: 48px; margin-bottom: 15px;">💬</div>
                <p>Select someone to start messaging</p>
            </div>
        </div>
        
        <div class="typing-indicator" id="typingIndicator">
            <div class="message-avatar">U</div>
            <div class="typing-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
        
        <div class="chat-footer">
            <form id="chat-form" style="display:none;">
                <input type="hidden" name="receiver_id" id="receiver_id">
                <input type="hidden" name="receiver_type" id="receiver_type">
                <div class="message-input-wrapper">
                    <textarea name="message" id="chat-message" class="message-input" placeholder="Type a message..." required></textarea>
                    <button type="submit" class="send-btn" title="Send message">
                        ➤
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Staff and student data
    const staffData = <?= json_encode($staffs_result->fetch_all(MYSQLI_ASSOC)) ?>;
    const studentData = <?= json_encode($students_result->fetch_all(MYSQLI_ASSOC)) ?>;
    
    let currentUserId = null;
    let currentUserType = null;
    let typingTimeout = null;
    let isTyping = false;

    // Handle user type selection
    document.getElementById('userTypeSelect').addEventListener('change', function() {
        const userType = this.value;
        const userSelect = document.getElementById('userSelect');
        const callBtn = document.getElementById('callBtn');
        const chatForm = document.getElementById('chat-form');
        const chatStatus = document.getElementById('chatStatus');
        const chatBox = document.getElementById('chat-box');
        
        userSelect.innerHTML = '<option value="">Select user...</option>';
        userSelect.disabled = true;
        callBtn.disabled = true;
        chatForm.style.display = 'none';
        chatStatus.textContent = "Select someone to start chatting";
        
        if (userType === 'staff') {
            staffData.forEach(staff => {
                const option = document.createElement('option');
                option.value = staff.staff_id;
                option.textContent = staff.first_name + ' ' + staff.surname;
                userSelect.appendChild(option);
            });
            userSelect.disabled = false;
        } else if (userType === 'student') {
            studentData.forEach(student => {
                const option = document.createElement('option');
                option.value = student.student_id;
                option.textContent = student.first_name + ' ' + student.surname;
                userSelect.appendChild(option);
            });
            userSelect.disabled = false;
        }
    });

    // Handle user selection
    document.getElementById('userSelect').addEventListener('change', function() {
        const userId = this.value;
        const userType = document.getElementById('userTypeSelect').value;
        const callBtn = document.getElementById('callBtn');
        const chatForm = document.getElementById('chat-form');
        const chatStatus = document.getElementById('chatStatus');
        const chatBox = document.getElementById('chat-box');
        
        if (userId && userType) {
            currentUserId = userId;
            currentUserType = userType;
            document.getElementById("receiver_id").value = userId;
            document.getElementById("receiver_type").value = userType;
            chatForm.style.display = "block";
            callBtn.disabled = false;
            
            // Update status
            const selectedOption = this.options[this.selectedIndex];
            chatStatus.innerHTML = `Chatting with ${selectedOption.text} <span class="online-indicator"></span>`;
            
            loadMessages(userId, userType);
            if (window.chatInterval) clearInterval(window.chatInterval);
            window.chatInterval = setInterval(() => loadMessages(userId, userType), 3000);
        } else {
            currentUserId = null;
            currentUserType = null;
            document.getElementById("receiver_id").value = "";
            document.getElementById("receiver_type").value = "";
            chatForm.style.display = "none";
            callBtn.disabled = true;
            chatStatus.textContent = "Select someone to start chatting";
            chatBox.innerHTML = `
                <div class="no-messages">
                    <div style="font-size: 48px; margin-bottom: 15px;">💬</div>
                    <p>Select someone to start messaging</p>
                </div>
            `;
        }
    });

    function loadMessages(userId, userType) {
        fetch("../messaging/fetch_messages.php?user=" + encodeURIComponent(userId) + "&type=" + userType)
            .then(res => res.text())
            .then(data => {
                const chatDiv = document.getElementById("chat-box");
                chatDiv.innerHTML = data;
                chatDiv.scrollTop = chatDiv.scrollHeight;
            })
            .catch(err => console.error('Error loading messages:', err));
    }

    function formatMessage(message, isSent, timestamp) {
        const time = new Date(timestamp).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        const avatar = isSent ? 'U' : (currentUserType === 'staff' ? 'S' : 'U');
        const messageClass = isSent ? 'sent' : 'received';
        
        return `
            <div class="message ${messageClass}">
                <div class="message-avatar">${avatar}</div>
                <div>
                    <div class="message-content">${message}</div>
                    <div class="message-time">${time}</div>
                </div>
            </div>
        `;
    }

    function showTypingIndicator() {
        const typingIndicator = document.getElementById('typingIndicator');
        typingIndicator.style.display = 'flex';
        const chatBox = document.getElementById('chat-box');
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    function hideTypingIndicator() {
        const typingIndicator = document.getElementById('typingIndicator');
        typingIndicator.style.display = 'none';
    }

    // Auto-resize textarea
    document.getElementById('chat-message').addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 100) + 'px';
        
        // Show typing indicator
        if (!isTyping) {
            isTyping = true;
            showTypingIndicator();
        }
        
        // Clear existing timeout
        if (typingTimeout) {
            clearTimeout(typingTimeout);
        }
        
        // Hide typing indicator after 1 second of no typing
        typingTimeout = setTimeout(() => {
            isTyping = false;
            hideTypingIndicator();
        }, 1000);
    });

    // Send message on Enter (but allow Shift+Enter for new line)
    document.getElementById('chat-message').addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            document.getElementById('chat-form').dispatchEvent(new Event('submit'));
        }
    });

    document.getElementById("chat-form").addEventListener("submit", function(e) {
        e.preventDefault();
        const messageInput = document.getElementById("chat-message");
        const message = messageInput.value.trim();
        
        if (!message || !currentUserId || !currentUserType) return;
        
        // Hide typing indicator
        isTyping = false;
        hideTypingIndicator();
        
        // Send to server FIRST (before clearing input)
        const formData = new FormData(this);
        
        // Add message to chat immediately for better UX
        const chatBox = document.getElementById("chat-box");
        chatBox.innerHTML += formatMessage(message, true, new Date().toISOString());
        chatBox.scrollTop = chatBox.scrollHeight;
        
        // Clear input and reset height AFTER creating FormData
        messageInput.value = "";
        messageInput.style.height = 'auto';
        
        fetch("../messaging/send_message.php", { method: "POST", body: formData })
            .then(() => {
                // Reload messages to get server response
                loadMessages(currentUserId, currentUserType);
            })
            .catch(err => {
                console.error('Error sending message:', err);
                // Show error message
                chatBox.innerHTML += formatMessage('Failed to send message. Please try again.', true, new Date().toISOString());
            });
    });

    // Video call functionality
    document.getElementById("callBtn").addEventListener("click", () => {
        const userId = document.getElementById("userSelect").value;
        const userType = document.getElementById("userTypeSelect").value;
        
        if (!userId || !userType) { 
            alert("Please select a user to call!"); 
            return; 
        }

        console.log("Calling user:", userId, "Type:", userType);

        fetch("../video/create_call.php", {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded"},
            body: `receiver_id=${encodeURIComponent(userId)}&receiver_type=${encodeURIComponent(userType)}`
        })
        .then(r => r.json())
        .then(data => {
            console.log("Create call response:", data);
            if (data.success) {
                alert(`Calling ${userType}... Waiting for response.`);
                let callId = data.call_id;
                let roomId = data.room_id;

                // Poll call status for THIS call with optimized interval
                let poll = setInterval(() => {
                    fetch("../video/check_call_status.php?call_id=" + callId)
                    .then(r => r.json())
                    .then(statusData => {
                        console.log("Poll:", statusData);
                        if (statusData.status === "accepted") {
                            clearInterval(poll);
                            window.open("../call.php?room=" + roomId, '_blank', 'width=800,height=600');
                        } else if (statusData.status === "declined") {
                            clearInterval(poll);
                            alert("Call declined ❌");
                        } else if (statusData.status === "ended") {
                            clearInterval(poll);
                            alert("Call ended ❌");
                        }
                    })
                    .catch(err => {
                        console.error("Error checking call status:", err);
                    });
                }, 3000); // Increased to 3 seconds

                // Stop polling after 30 seconds
                setTimeout(() => {
                    clearInterval(poll);
                }, 30000);
            } else {
                alert("Failed to start call: " + (data.error || "Unknown error"));
            }
        })
        .catch(err => {
            console.error("Error starting call:", err);
            alert("Error starting call. Please try again.");
        });
    });

    // Video call functionality for students - check for incoming calls
    let callCheckInterval = setInterval(() => {
        fetch("../video/check_call.php")
        .then(res => res.json())
        .then(data => {
            if (data.incoming) {
                let call = data.call;
                // Clear the interval to prevent multiple prompts
                clearInterval(callCheckInterval);
                
                if (confirm(`Incoming call from ${call.caller_type} (ID: ${call.caller_id}). Accept?`)) {
                    fetch("../video/respond_call.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `call_id=${call.call_id}&action=accept`
                    }).then(() => {
                        window.open("../call.php?room=" + call.room_id, '_blank', 'width=800,height=600');
                    });
                } else {
                    fetch("../video/respond_call.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `call_id=${call.call_id}&action=decline`
                    });
                }
                
                // Restart the interval after handling the call
                setTimeout(() => {
                    callCheckInterval = setInterval(arguments.callee, 8000);
                }, 5000);
            }
        })
        .catch(err => {
            console.error("Error checking incoming calls:", err);
        });
    }, 8000); // Reduced frequency to 8 seconds
    </script>

</body>
</html>
