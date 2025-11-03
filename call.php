<?php
session_start();
if (!isset($_SESSION['student_id']) && !isset($_SESSION['staff_id'])) {
    die("Not logged in");
}

$room_id = $_GET['room'] ?? '';
if (!$room_id) {
    die("No room ID provided");
}

$user_type = isset($_SESSION['student_id']) ? "student" : "staff";
$user_id = $user_type === "student" ? $_SESSION['student_id'] : $_SESSION['staff_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Call - Library System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #0a0a0a;
            color: white;
            overflow: hidden;
            height: 100vh;
            position: relative;
        }
        
        /* Background gradient overlay */
        .background-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, 
                rgba(102, 126, 234, 0.1) 0%, 
                rgba(118, 75, 162, 0.1) 50%,
                rgba(13, 110, 253, 0.1) 100%);
            pointer-events: none;
        }
        
        /* Main container */
        .call-container {
            position: relative;
            height: 100vh;
            display: flex;
            flex-direction: column;
            z-index: 1;
        }
        
        /* Header */
        .call-header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            padding: 20px;
            background: linear-gradient(180deg, rgba(0,0,0,0.8) 0%, transparent 100%);
            z-index: 100;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .call-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .participant-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
        }
        
        .call-details h3 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .call-status {
            font-size: 14px;
            opacity: 0.8;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #4CAF50;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .call-timer {
            font-size: 16px;
            font-weight: 500;
            padding: 8px 16px;
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }
        
        /* Video containers */
        .video-area {
            flex: 1;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        #remoteVideo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            background: #1a1a1a;
        }
        
        #localVideo {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 200px;
            height: 150px;
            border-radius: 12px;
            border: 2px solid rgba(255,255,255,0.2);
            object-fit: cover;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            background: #2a2a2a;
            z-index: 50;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        #localVideo:hover {
            transform: scale(1.05);
            border-color: rgba(255,255,255,0.4);
        }
        
        /* Controls bar */
        .controls-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 30px;
            background: linear-gradient(0deg, rgba(0,0,0,0.8) 0%, transparent 100%);
            display: flex;
            justify-content: center;
            gap: 20px;
            z-index: 100;
        }
        
        .control-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }
        
        .control-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .control-btn:hover::before {
            background: rgba(255,255,255,0.2);
        }
        
        .control-btn:active {
            transform: scale(0.95);
        }
        
        .mute-btn {
            background: rgba(108, 117, 125, 0.8);
            color: white;
        }
        
        .mute-btn.muted {
            background: rgba(220, 53, 69, 0.8);
        }
        
        .video-btn {
            background: rgba(108, 117, 125, 0.8);
            color: white;
        }
        
        .video-btn.disabled {
            background: rgba(220, 53, 69, 0.8);
        }
        
        .end-btn {
            background: rgba(220, 53, 69, 0.9);
            color: white;
            width: 70px;
            height: 70px;
            box-shadow: 0 4px 20px rgba(220, 53, 69, 0.4);
        }
        
        .end-btn:hover {
            background: rgba(220, 53, 69, 1);
            transform: scale(1.1);
            box-shadow: 0 6px 25px rgba(220, 53, 69, 0.6);
        }
        
        /* Connection status overlay */
        .connection-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.9);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 200;
            backdrop-filter: blur(5px);
        }
        
        .connection-overlay.hidden {
            display: none;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(255,255,255,0.1);
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .connection-text {
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .connection-subtext {
            font-size: 14px;
            opacity: 0.7;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            #localVideo {
                width: 120px;
                height: 90px;
                top: 15px;
                right: 15px;
            }
            
            .control-btn {
                width: 50px;
                height: 50px;
                font-size: 20px;
            }
            
            .end-btn {
                width: 60px;
                height: 60px;
            }
            
            .call-header {
                padding: 15px;
            }
            
            .controls-bar {
                padding: 20px;
                gap: 15px;
            }
        }
        
        /* Tooltip */
        .tooltip {
            position: relative;
        }
        
        .tooltip::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 80px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            z-index: 1000;
        }
        
        .tooltip:hover::after {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="background-overlay"></div>
    
    <div class="call-container">
        <!-- Header -->
        <div class="call-header">
            <div class="call-info">
                <div class="participant-avatar" id="participantAvatar"><?= strtoupper(substr($room_id, 0, 1)) ?></div>
                <div class="call-details">
                    <h3 id="participantName">Room <?= htmlspecialchars($room_id) ?></h3>
                    <div class="call-status">
                        <div class="status-dot"></div>
                        <span id="statusText">Establishing connection</span>
                    </div>
                </div>
            </div>
            <div class="call-timer" id="callTimer">00:00</div>
        </div>
        
        <!-- Video Area -->
        <div class="video-area">
            <video id="remoteVideo" autoplay playsinline></video>
            <video id="localVideo" autoplay playsinline muted></video>
        </div>
        
        <!-- Controls -->
        <div class="controls-bar">
            <button class="control-btn mute-btn tooltip" id="muteBtn" data-tooltip="Mute microphone">
                🎤
            </button>
            <button class="control-btn video-btn tooltip" id="videoBtn" data-tooltip="Turn off camera">
                📹
            </button>
            <button class="control-btn end-btn tooltip" id="endBtn" data-tooltip="End call">
                📞
            </button>
        </div>
    </div>
    
    <!-- Connection overlay -->
    <div class="connection-overlay" id="connectionOverlay">
        <div class="spinner"></div>
        <div class="connection-text">Connecting to call...</div>
        <div class="connection-subtext">Please wait while we establish the connection</div>
    </div>

<script>
const roomId = "<?= htmlspecialchars($room_id) ?>";
const userType = "<?= $user_type ?>";
const userId = "<?= $user_id ?>";

let localStream;
let pc = new RTCPeerConnection({
    iceServers: [
        { urls: 'stun:stun.l.google.com:19302' },
        { urls: 'stun:stun1.l.google.com:19302' }
    ]
});

const localVideo = document.getElementById("localVideo");
const remoteVideo = document.getElementById("remoteVideo");
const statusText = document.getElementById("statusText");
const connectionOverlay = document.getElementById("connectionOverlay");
const callTimer = document.getElementById("callTimer");
const muteBtn = document.getElementById("muteBtn");
const videoBtn = document.getElementById("videoBtn");
const endBtn = document.getElementById("endBtn");

let callStartTime = null;
let timerInterval = null;
let isMuted = false;
let isVideoOff = false;
let signalingInterval = null;
let statusCheckInterval = null;
let isConnected = false;

// Update call timer
function updateTimer() {
    if (!callStartTime) return;
    const elapsed = Math.floor((Date.now() - callStartTime) / 1000);
    const minutes = Math.floor(elapsed / 60).toString().padStart(2, '0');
    const seconds = (elapsed % 60).toString().padStart(2, '0');
    callTimer.textContent = `${minutes}:${seconds}`;
}

// Get camera + mic
console.log("Attempting to get user media...");
navigator.mediaDevices.getUserMedia({ video: true, audio: true })
.then(stream => {
    console.log("User media obtained successfully");
    localStream = stream;
    localVideo.srcObject = stream;
    stream.getTracks().forEach(track => pc.addTrack(track, stream));
    statusText.textContent = "Camera and microphone ready";
    
    // Hide connection overlay after getting media
    setTimeout(() => {
        connectionOverlay.classList.add('hidden');
    }, 1000);
    
    console.log("Local stream started");
})
.catch(err => {
    console.error("Error accessing media devices:", err);
    statusText.textContent = "Error accessing camera/microphone";
    connectionOverlay.innerHTML = `
        <div style="text-align: center;">
            <div style="font-size: 48px; margin-bottom: 20px;">❌</div>
            <div class="connection-text">Camera Access Required</div>
            <div class="connection-subtext">Please allow camera and microphone access to join the call</div>
            <button onclick="location.reload()" style="margin-top: 20px; padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer;">Try Again</button>
        </div>
    `;
});

// Remote stream
pc.ontrack = (event) => {
    remoteVideo.srcObject = event.streams[0];
    statusText.textContent = "Connected!";
    connectionOverlay.classList.add('hidden');
    isConnected = true;
    
    // Stop excessive polling once connected
    if (signalingInterval) {
        clearInterval(signalingInterval);
        signalingInterval = null;
    }
    
    // Start call timer
    if (!callStartTime) {
        callStartTime = Date.now();
        timerInterval = setInterval(updateTimer, 1000);
    }
    
    console.log("Remote stream received");
};

// Connection state monitoring
pc.onconnectionstatechange = () => {
    console.log("Connection state:", pc.connectionState);
    switch(pc.connectionState) {
        case 'connecting':
            statusText.textContent = "Connecting...";
            connectionOverlay.classList.remove('hidden');
            break;
        case 'connected':
            statusText.textContent = "Connected!";
            connectionOverlay.classList.add('hidden');
            isConnected = true;
            
            // Stop excessive polling once connected
            if (signalingInterval) {
                clearInterval(signalingInterval);
                signalingInterval = null;
            }
            
            if (!callStartTime) {
                callStartTime = Date.now();
                timerInterval = setInterval(updateTimer, 1000);
            }
            break;
        case 'disconnected':
            statusText.textContent = "Disconnected";
            break;
        case 'failed':
            statusText.textContent = "Connection failed";
            connectionOverlay.innerHTML = `
                <div style="text-align: center;">
                    <div style="font-size: 48px; margin-bottom: 20px;">⚠️</div>
                    <div class="connection-text">Connection Failed</div>
                    <div class="connection-subtext">Unable to establish connection. Please try again.</div>
                </div>
            `;
            break;
    }
};

// Control buttons
muteBtn.addEventListener('click', () => {
    if (localStream) {
        const audioTrack = localStream.getAudioTracks()[0];
        if (audioTrack) {
            audioTrack.enabled = !audioTrack.enabled;
            isMuted = !audioTrack.enabled;
            muteBtn.innerHTML = isMuted ? '🔇' : '🎤';
            muteBtn.classList.toggle('muted', isMuted);
            muteBtn.setAttribute('data-tooltip', isMuted ? 'Unmute microphone' : 'Mute microphone');
        }
    }
});

videoBtn.addEventListener('click', () => {
    if (localStream) {
        const videoTrack = localStream.getVideoTracks()[0];
        if (videoTrack) {
            videoTrack.enabled = !videoTrack.enabled;
            isVideoOff = !videoTrack.enabled;
            videoBtn.innerHTML = isVideoOff ? '📹' : '📸';
            videoBtn.classList.toggle('disabled', isVideoOff);
            videoBtn.setAttribute('data-tooltip', isVideoOff ? 'Turn on camera' : 'Turn off camera');
            localVideo.style.opacity = isVideoOff ? '0.3' : '1';
        }
    }
});

endBtn.addEventListener('click', endCall);

// Double-click local video to make it fullscreen
localVideo.addEventListener('dblclick', () => {
    if (localVideo.style.position === 'fixed') {
        // Return to corner
        localVideo.style.position = 'absolute';
        localVideo.style.width = '200px';
        localVideo.style.height = '150px';
        localVideo.style.top = '20px';
        localVideo.style.right = '20px';
        localVideo.style.zIndex = '50';
    } else {
        // Make fullscreen
        localVideo.style.position = 'fixed';
        localVideo.style.width = '100vw';
        localVideo.style.height = '100vh';
        localVideo.style.top = '0';
        localVideo.style.right = '0';
        localVideo.style.zIndex = '1000';
    }
});

// Exchange SDP + ICE via PHP polling
async function sendData(type, data) {
    try {
        console.log(`Sending ${type}:`, data);
        const response = await fetch("video/signal.php", {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded"},
            body: `room_id=${roomId}&user_id=${userId}&user_type=${userType}&${type}=${encodeURIComponent(JSON.stringify(data))}`
        });
        const result = await response.text();
        console.log(`Send ${type} response:`, result);
    } catch (err) {
        console.error("Error sending data:", err);
    }
}

async function getData() {
    try {
        let res = await fetch(`video/signal.php?room_id=${roomId}&user_id=${userId}&user_type=${userType}`);
        let msgs = await res.json();
        console.log("Received messages:", msgs);
        for (let m of msgs) {
            console.log("Processing message:", m);
            if (m.type === "offer" || m.type === "answer") {
                console.log(`Setting remote description for ${m.type}`);
                await pc.setRemoteDescription(new RTCSessionDescription(m));
            } else if (m.type === "candidate") {
                console.log("Adding ICE candidate");
                await pc.addIceCandidate(new RTCIceCandidate(m));
            }
        }
    } catch (err) {
        console.error("Error getting data:", err);
    }
}

pc.onicecandidate = event => {
    if (event.candidate) {
        console.log("Sending ICE candidate:", event.candidate);
        sendData("candidate", event.candidate);
    } else {
        console.log("ICE gathering complete");
    }
};

// Start call if staff (caller)
if (userType === "staff") {
    console.log("Starting as caller (staff)");
    setTimeout(() => {
        pc.createOffer().then(offer => {
            console.log("Created offer:", offer);
            pc.setLocalDescription(offer);
            sendData("offer", offer);
            statusText.textContent = "Calling...";
        }).catch(err => {
            console.error("Error creating offer:", err);
            statusText.textContent = "Error starting call";
        });
    }, 2000); // Wait 2 seconds for media to be ready
} else {
    console.log("Starting as receiver (student)");
    statusText.textContent = "Waiting for incoming call...";
    // Student waits for offer then replies
    let interval = setInterval(async () => {
        console.log("Checking for offers...");
        await getData();
        if (pc.remoteDescription && pc.remoteDescription.type === "offer" && !pc.localDescription) {
            console.log("Received offer, creating answer");
            let answer = await pc.createAnswer();
            await pc.setLocalDescription(answer);
            sendData("answer", answer);
            statusText.textContent = "Answering call...";
            clearInterval(interval);
        }
    }, 3000); // Reduced frequency to 3 seconds
}

// Test connection immediately on load
console.log("Testing connection to signaling server...");
fetch(`video/signal.php?room_id=${roomId}&user_id=${userId}&user_type=${userType}`)
.then(res => res.json())
.then(data => {
    console.log("Signaling server test successful:", data);
    statusText.textContent = "Signaling server connected";
})
.catch(err => {
    console.error("Signaling server test failed:", err);
    statusText.textContent = "Signaling server error";
    connectionOverlay.innerHTML = `
        <div style="text-align: center;">
            <div style="font-size: 48px; margin-bottom: 20px;">❌</div>
            <div class="connection-text">Connection Error</div>
            <div class="connection-subtext">Unable to connect to signaling server</div>
            <button onclick="location.reload()" style="margin-top: 20px; padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer;">Try Again</button>
        </div>
    `;
});

// Keep checking for ICE candidates & answers with smart polling
signalingInterval = setInterval(() => {
    if (!isConnected) {
        getData();
    }
}, 3000); // Reduced to 3 seconds and stops when connected

// Add connection timeout (30 seconds)
setTimeout(() => {
    if (pc.connectionState !== 'connected' && !connectionOverlay.classList.contains('hidden')) {
        console.log("Connection timeout reached");
        connectionOverlay.innerHTML = `
            <div style="text-align: center;">
                <div style="font-size: 48px; margin-bottom: 20px;">⏰</div>
                <div class="connection-text">Connection Timeout</div>
                <div class="connection-subtext">Unable to establish connection within 30 seconds</div>
                <button onclick="location.reload()" style="margin-top: 20px; padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer;">Try Again</button>
                <button onclick="endCall()" style="margin-top: 10px; margin-left: 10px; padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 8px; cursor: pointer;">End Call</button>
            </div>
        `;
    }
}, 30000);

function endCall() {
    // Clear all intervals
    if (signalingInterval) {
        clearInterval(signalingInterval);
        signalingInterval = null;
    }
    if (statusCheckInterval) {
        clearInterval(statusCheckInterval);
        statusCheckInterval = null;
    }
    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }
    
    fetch("video/update_call_status.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `call_id=${roomId}&status=ended`
    });
    
    if (localStream) {
        localStream.getTracks().forEach(track => track.stop());
    }
    pc.close();
    
    // Show ending overlay
    connectionOverlay.classList.remove('hidden');
    connectionOverlay.innerHTML = `
        <div style="text-align: center;">
            <div style="font-size: 48px; margin-bottom: 20px;">👋</div>
            <div class="connection-text">Call Ended</div>
            <div class="connection-subtext">Thank you for using our video call service</div>
        </div>
    `;
    
    setTimeout(() => {
        window.location.href = userType === "staff" ? "Staff/accInfoStaff.php" : "student/accInfo.php";
    }, 2000);
}

// Check call status periodically with reduced frequency
statusCheckInterval = setInterval(() => {
    fetch(`video/check_call_status.php?call_id=${roomId}`)
    .then(res => res.json())
    .then(data => {
        if (data.status === "declined" || data.status === "ended") {
            if (data.status === "declined") {
                connectionOverlay.classList.remove('hidden');
                connectionOverlay.innerHTML = `
                    <div style="text-align: center;">
                        <div style="font-size: 48px; margin-bottom: 20px;">❌</div>
                        <div class="connection-text">Call Declined</div>
                        <div class="connection-subtext">The other participant declined the call</div>
                    </div>
                `;
                setTimeout(() => {
                    window.location.href = userType === "staff" ? "Staff/accInfoStaff.php" : "student/accInfo.php";
                }, 3000);
            } else {
                endCall();
            }
        }
    })
    .catch(err => console.error("Error checking call status:", err));
}, 5000); // Reduced frequency to 5 seconds

// Keyboard shortcuts
document.addEventListener('keydown', (e) => {
    if (e.key === 'm' || e.key === 'M') {
        muteBtn.click();
    } else if (e.key === 'v' || e.key === 'V') {
        videoBtn.click();
    } else if (e.key === 'Escape') {
        endCall();
    }
});

</script>
</body>
</html>
