<?php
include "../config/connection.php";
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: staffLogin.php");
    exit();
}

$staff_id = $_SESSION['staff_id'];
// Set generic user session variables for video call system
$_SESSION['user_id'] = $staff_id;
$_SESSION['user_type'] = 'staff';

$query = "SELECT * FROM staff WHERE staff_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
$staff_data = $result->fetch_assoc();

if (!$staff_data) {
    session_destroy();
    header("Location: staffLogin.php");
    exit();
}

$students_result = $conn->query("SELECT student_id, first_name, surname FROM students");

$history_query = "SELECT b.title, b.author, bb.status, bb.barrowID, b.ISBN, b.book_cover
                FROM barrowed_books bb 
                JOIN books b ON bb.ISBN = b.ISBN 
                ORDER BY bb.barrowID DESC";
$history_stmt = $conn->prepare($history_query);
$history_stmt->execute();
$history_result = $history_stmt->get_result();

$student_query = "SELECT barrower_id FROM barrowed_books ORDER BY barrowID DESC LIMIT 1";
$student_result = $conn->query($student_query);
$student_id = $student_result && $student_result->num_rows > 0 ? $student_result->fetch_assoc()['barrower_id'] : '';

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Account - Library Management System</title>
         <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: rgba(255, 255, 255, 0.95);
                min-height: 100vh;
                color: #333;
            }
            .header {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
                position: fixed; top: 0; left: 0; right: 0;
                z-index: 1000; padding: 0;
            }
            .headerCont {
                display: flex; justify-content: space-between; align-items: center;
                padding: 15px 30px; max-width: 1200px; margin: 0 auto;
            }
            .logo { font-size: 24px; font-weight: bold; color:blue; text-decoration: none; }
            .nav-links { display: flex; gap: 30px; align-items: center; }
            .nav-links a {
                text-decoration: none; color: #333; font-weight: 500;
                padding: 10px 15px; border-radius: 8px; transition: all 0.3s ease;
            }
            .nav-links a.active { background: blue; color: white; }
            .main-content {
                padding-top: 100px; padding-bottom: 50px; display: flex;
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
                min-width: 300px; max-width: 600px;
            }
            .card h1 {
                color:blue; margin-bottom: 25px; font-size: 28px;
                text-align: center;
            }
            .info-item { display: flex; align-items: center; margin-bottom: 20px;
                padding: 15px; background: rgba(94, 105, 206, 0.05); border-radius: 10px; }
            .info-label { font-weight: bold; color:black; width: 120px; }
            .info-value { color: #333; font-size: 16px; }
            .logout-btn {
                background: red; color: white; padding: 12px 30px;
                border: none; border-radius: 25px; font-size: 16px;
                font-weight: bold; text-decoration: none;
                display: inline-block; margin-top: 25px;
                transition: all 0.3s ease; cursor: pointer;
                box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
            }
            .table-container { flex: 2; min-width: 600px; }
            .table-wrapper { background: white; border-radius: 15px;
                overflow: hidden; box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1); }
            .table { width: 100%; border-collapse: collapse; }
            .table thead { background: blue; }
            .table thead th {
                color: white; padding: 20px 15px; text-align: left;
                font-weight: 600; font-size: 14px; text-transform: uppercase;
            }
            .table tbody tr { border-bottom: 1px solid #f0f0f0; }
            .table tbody tr:hover { background: rgba(94, 105, 206, 0.05); }
            .table tbody td { padding: 15px; color: #555; vertical-align: middle; }
            .status-badge { padding: 6px 12px; border-radius: 20px; font-weight: bold;
                font-size: 12px; text-align: center; display: inline-block; min-width: 80px; }
            .status-available { background: green; color: white; }
            .status-borrowed { background: skyblue; }
            .status-returned { background: green; color: white; }
            .status-overdue { background: red; color: white; }
            .status-lost { background: black; color: white; }
            .refresh-btn {
                background: blue; color: white; padding: 12px 30px;
                border: none; border-radius: 25px; font-size: 16px;
                font-weight: bold; margin: 20px auto; display: block;
                cursor: pointer; transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(94, 105, 206, 0.3);
            }

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
                <a href="addBooks.php">Add Books</a>
                <a href="accInfoStaff.php" class="active">Account</a>
                <a href="bookListStaff.php">Books</a>
                <a href="Schedules.php">Transactions</a>
                <a href="returnBook.php">Return Book</a>
                <a href="approveRequests.php">Approve Requests</a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <!-- Account Info -->
        <div class="card account-info">
            <h1>Account Information</h1>
            <div class="info-item"><span class="info-label">Staff ID:</span>
                <span class="info-value"><?= htmlspecialchars($staff_data['staff_id']); ?></span></div>
            <div class="info-item"><span class="info-label">Surname:</span>
                <span class="info-value"><?= htmlspecialchars($staff_data['surname']); ?></span></div>
            <div class="info-item"><span class="info-label">First Name:</span>
                <span class="info-value"><?= htmlspecialchars($staff_data['first_name']); ?></span></div>
            <div class="info-item"><span class="info-label">Middle Initial:</span>
                <span class="info-value"><?= htmlspecialchars($staff_data['initial']); ?></span></div>
            
            
            <a href="accInfoStaff.php?logout=true" class="logout-btn">Log Out</a>
        </div>

        <!-- Book Status -->
        <div class="card table-container">
            <h1>Book Status Overview</h1>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Book Title</th><th>Author</th><th>ISBN</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php if ($history_result->num_rows > 0): ?>
                            <?php while ($row = $history_result->fetch_assoc()): ?>
                                <?php
                                    $status_class = 'status-available';
                                    switch($row['status']) {
                                        case 'Available': $status_class = 'status-available'; break;
                                        case 'Barrowed': $status_class = 'status-borrowed'; break;
                                        case 'Returned': $status_class = 'status-returned'; break;
                                        case 'Overdue': $status_class = 'status-overdue'; break;
                                        case 'Lost': $status_class = 'status-lost'; break;
                                    }
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['title']); ?></td>
                                    <td><?= htmlspecialchars($row['author']); ?></td>
                                    <td><?= htmlspecialchars($row['ISBN']); ?></td>
                                    <td><span class="status-badge <?= $status_class; ?>"><?= htmlspecialchars($row['status']); ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align: center; padding: 40px; color: #666;">No book records found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <button class="refresh-btn" onclick="location.reload()">Refresh</button>
        </div>
    </main>

    <!-- Modern Messenger Chat Widget -->
    <div class="chat-widget">
        <div class="chat-header">
            <h3>💬 Messages</h3>
            <div class="chat-status" id="chatStatus">Select a student to start chatting</div>
        </div>
        
        <div class="chat-controls">
            <div class="user-select-wrapper">
                <select id="studentSelect">
                    <option value="">Select a student...</option>
                    <?php if ($students_result && $students_result->num_rows > 0): ?>
                        <?php while ($student = $students_result->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($student['student_id']); ?>">
                                <?= htmlspecialchars($student['first_name'] . ' ' . $student['surname']); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>
            <button id="callBtn" class="call-btn" disabled>
                📞 Call
            </button>
        </div>
        
        <div id="chat-box">
            <div class="no-messages">
                <div style="font-size: 48px; margin-bottom: 15px;">💬</div>
                <p>Select a student to start messaging</p>
            </div>
        </div>
        
        <div class="typing-indicator" id="typingIndicator">
            <div class="message-avatar">S</div>
            <div class="typing-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
        
        <div class="chat-footer">
            <form id="chat-form" style="display:none;">
                <input type="hidden" name="receiver_id" id="receiver_id">
                <input type="hidden" name="receiver_type" value="student">
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
    let currentStudentId = null;
    let typingTimeout = null;
    let isTyping = false;

    // Chat handling
    document.getElementById('studentSelect').addEventListener('change', function() {
        const studentId = this.value;
        const callBtn = document.getElementById('callBtn');
        const chatForm = document.getElementById('chat-form');
        const chatStatus = document.getElementById('chatStatus');
        const chatBox = document.getElementById('chat-box');
        
        if (studentId) {
            currentStudentId = studentId;
            document.getElementById("receiver_id").value = studentId;
            chatForm.style.display = "block";
            callBtn.disabled = false;
            
            // Update status
            const selectedOption = this.options[this.selectedIndex];
            chatStatus.innerHTML = `Chatting with ${selectedOption.text} <span class="online-indicator"></span>`;
            
            loadMessages(studentId);
            if (window.chatInterval) clearInterval(window.chatInterval);
            window.chatInterval = setInterval(() => loadMessages(studentId), 3000);
        } else {
            currentStudentId = null;
            document.getElementById("receiver_id").value = "";
            chatForm.style.display = "none";
            callBtn.disabled = true;
            chatStatus.textContent = "Select a student to start chatting";
            chatBox.innerHTML = `
                <div class="no-messages">
                    <div style="font-size: 48px; margin-bottom: 15px;">💬</div>
                    <p>Select a student to start messaging</p>
                </div>
            `;
        }
    });

    function loadMessages(studentId) {
        fetch("../messaging/fetch_messages.php?user=" + encodeURIComponent(studentId) + "&type=student")
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
        const avatar = isSent ? 'S' : 'U';
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
        
        if (!message || !currentStudentId) return;
        
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
                loadMessages(currentStudentId);
            })
            .catch(err => {
                console.error('Error sending message:', err);
                // Show error message
                chatBox.innerHTML += formatMessage('Failed to send message. Please try again.', true, new Date().toISOString());
            });
    });

    // --- Video Call Section ---
    const userType = "staff";
    const userId = <?= json_encode($_SESSION['staff_id']); ?>;

    document.getElementById("callBtn").addEventListener("click", () => {
        let receiverId = document.getElementById("receiver_id").value;
        let receiverType = "student"; // Always student for staff calling
        
        console.log("Call button clicked. Receiver ID:", receiverId, "Type:", receiverType);
        
        if (!receiverId) { 
            alert("Please select a student to call!"); 
            return; 
        }

        console.log("Calling student:", receiverId);

        fetch("../video/create_call.php", {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded"},
            body: `receiver_id=${encodeURIComponent(receiverId)}&receiver_type=${encodeURIComponent(receiverType)}`
        })
        .then(r => {
            console.log("Response status:", r.status);
            return r.json();
        })
        .then(data => {
            console.log("Create call response:", data);
            if (data.success) {
                alert("Calling student... Waiting for response.");
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
                console.error("Call creation failed:", data);
                alert("Failed to start call: " + (data.error || "Unknown error"));
            }
        })
        .catch(err => {
            console.error("Error starting call:", err);
            alert("Error starting call. Please check console for details and try again.");
        });
    });

    // Incoming calls check with better management
    let staffCallCheckInterval = setInterval(() => {
        fetch("../video/check_call.php")
        .then(r => r.json())
        .then(data => {
            if (data.incoming) {
                let call = data.call;
                // Clear the interval to prevent multiple prompts
                clearInterval(staffCallCheckInterval);
                
                if (confirm(`Incoming call from ${call.caller_type} (ID: ${call.caller_id}). Accept?`)) {
                    fetch("../video/respond_call.php", {
                        method: "POST",
                        headers: {"Content-Type": "application/x-www-form-urlencoded"},
                        body: `call_id=${call.call_id}&action=accept`
                    }).then(() => {
                        window.open("../call.php?room=" + call.room_id, '_blank', 'width=800,height=600');
                    });
                } else {
                    fetch("../video/respond_call.php", {
                        method: "POST",
                        headers: {"Content-Type": "application/x-www-form-urlencoded"},
                        body: `call_id=${call.call_id}&action=decline`
                    });
                }
                
                // Restart the interval after handling the call
                setTimeout(() => {
                    staffCallCheckInterval = setInterval(arguments.callee, 8000);
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
