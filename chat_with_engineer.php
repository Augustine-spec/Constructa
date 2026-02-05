<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'homeowner') {
    header('Location: login.html');
    exit();
}
require_once 'backend/config.php';

$engineer_id = isset($_GET['engineer_id']) ? intval($_GET['engineer_id']) : 0;
if ($engineer_id === 0) {
    header('Location: engineer_directory.php');
    exit();
}

$conn = getDatabaseConnection();
$stmt = $conn->prepare("SELECT name, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $engineer_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    header('Location: engineer_directory.php');
    exit();
}
$engineer = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat with <?php echo htmlspecialchars($engineer['name']); ?> - Constructa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <style>
        :root { 
            --bg-color: #f6f7f2; 
            --text-dark: #121212; 
            --text-gray: #555555; 
            --primary-green: #294033; 
            --chat-bg: #fafafa;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        
        body { 
            background-color: transparent; 
            min-height: 100vh; 
            display: flex; 
            flex-direction: column; 
        }

        /* 3D Background Canvas */
        #canvas-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: -1;
            background: #f6f7f2;
            pointer-events: none;
        }
        
        header { padding: 1.5rem 3rem; display: flex; justify-content: space-between; max-width: 1600px; margin: 0 auto; width: 100%; background: rgba(246, 247, 242, 0.9); position: sticky; top: 0; z-index: 100; border-bottom: 1px solid rgba(0,0,0,0.05); backdrop-filter: blur(10px); }
        .logo { display: flex; align-items: center; gap: 0.5rem; font-size: 1.25rem; font-weight: 700; color: #000000; text-decoration: none; }
        .nav-btn { padding: 0.8rem 1.5rem; background: white; border: 1px solid rgba(0,0,0,0.1); border-radius: 8px; text-decoration: none; color: var(--text-dark); font-weight: 600; transition: all 0.3s; }
        .nav-btn:hover { background: var(--primary-green); color: white; }

        main { flex: 1; max-width: 1000px; margin: 0 auto; width: 100%; padding: 2rem; display: flex; flex-direction: column; position: relative; z-index: 1; }
        
        .chat-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            height: 70vh;
            overflow: hidden;
            position: relative;
        }

        .chat-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            justify-content: space-between; /* Add this */
            gap: 1rem;
            background: white;
        }

        .header-left { display: flex; align-items: center; gap: 1rem; }

        .engineer-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: 700;
        }

        /* 3 Dots Menu */
        .menu-container { position: relative; }
        .menu-btn { background: none; border: none; font-size: 1.2rem; color: var(--text-gray); cursor: pointer; padding: 0.5rem; border-radius: 50%; transition: background 0.3s; }
        .menu-btn:hover { background: #f3f4f6; color: var(--primary-green); }
        .dropdown-menu {
            position: absolute; right: 0; top: 120%; width: 200px;
            background: white; border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            padding: 0.5rem; opacity: 0; visibility: hidden;
            transform: translateY(-10px); transition: all 0.2s;
            z-index: 50;
        }
        .dropdown-menu.show { opacity: 1; visibility: visible; transform: translateY(0); }
        .menu-item {
            padding: 0.8rem 1rem; display: flex; align-items: center; gap: 0.8rem;
            color: var(--text-dark); text-decoration: none; border-radius: 8px;
            cursor: pointer; font-size: 0.9rem; transition: background 0.2s;
        }
        .menu-item:hover { background: #f3f4f6; }

        .chat-messages {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
            background: var(--chat-bg);
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            transition: background 0.3s;
        }

        .message {
            display: flex;
            max-width: 70%;
        }

        .message.sent {
            align-self: flex-end;
            flex-direction: row-reverse;
        }

        .message.received {
            align-self: flex-start;
        }

        .message-content {
            padding: 1rem 1.5rem;
            border-radius: 18px;
            font-size: 0.95rem;
            line-height: 1.5;
            position: relative;
        }

        .message.sent .message-content {
            background: var(--primary-green);
            color: white;
            border-bottom-right-radius: 4px;
        }

        .message.received .message-content {
            background: white;
            border: 1px solid #eee;
            border-bottom-left-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }

        .message-time {
            font-size: 0.7rem;
            margin-top: 0.3rem;
            opacity: 0.7;
            text-align: right;
        }

        .chat-input-area {
            padding: 1.5rem;
            background: white;
            border-top: 1px solid #eee;
            display: flex;
            gap: 1rem;
        }

        .chat-input {
            flex: 1;
            padding: 1rem;
            border: 1px solid #ddd;
            border-radius: 12px;
            outline: none;
            transition: border-color 0.3s;
        }

        .chat-input:focus {
            border-color: var(--primary-green);
        }

        .send-btn {
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 0 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .send-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        /* Modal */
        .modal-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5); z-index: 1000;
            display: flex; align-items: center; justify-content: center;
            opacity: 0; visibility: hidden; transition: all 0.3s;
        }
        .modal-overlay.open { opacity: 1; visibility: visible; }
        
        .modal-card {
            background: white; width: 90%; max-width: 500px;
            border-radius: 24px; padding: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            transform: scale(0.9); transition: all 0.3s;
        }
        .modal-overlay.open .modal-card { transform: scale(1); }

        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .modal-title { font-size: 1.5rem; font-weight: 700; color: var(--primary-green); }
        .modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-gray); }

        .theme-section { margin-bottom: 2rem; }
        .theme-label { display: block; font-weight: 600; margin-bottom: 0.8rem; color: var(--text-dark); }
        
        .color-options { display: flex; gap: 1rem; flex-wrap: wrap; }
        .color-circle { 
            width: 40px; height: 40px; border-radius: 50%; cursor: pointer; 
            border: 3px solid transparent; transition: transform 0.2s;
        }
        .color-circle.active { border-color: #121212; transform: scale(1.1); }
        
        .bg-options { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
        .bg-card {
            height: 80px; border-radius: 12px; border: 2px solid #eee; cursor: pointer;
            background-size: cover; background-position: center;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden; position: relative;
        }
        .bg-card.active { border-color: var(--primary-green); }
        .bg-card i { color: #555; font-size: 1.5rem; }
        
        .icon-btn {
            background: #f3f4f6; border: none; width: 45px; height: 45px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
            color: var(--text-gray); cursor: pointer; transition: all 0.2s;
        }
        .icon-btn:hover, .icon-btn.active { background: var(--primary-green); color: white; transform: rotate(45deg); }

        .attachment-menu {
            position: absolute; bottom: 60px; left: 0; width: 220px;
            background: white; border-radius: 16px; padding: 0.5rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            display: flex; flex-direction: column; gap: 0.2rem;
            opacity: 0; visibility: hidden; transform: translateY(10px); transition: all 0.2s;
            z-index: 20;
        }
        .attachment-menu.show { opacity: 1; visibility: visible; transform: translateY(0); }

        .attach-item {
            padding: 0.8rem 1rem; display: flex; align-items: center; gap: 1rem;
            font-size: 0.95rem; font-weight: 500; color: var(--text-dark);
            border-radius: 8px; cursor: pointer; transition: background 0.2s;
        }
        .attach-item:hover { background: #f3f4f6; }

        .emoji-picker {
            position: absolute; bottom: 50px; right: 0; width: 280px;
            background: white; border-radius: 16px; padding: 1rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            display: grid; grid-template-columns: repeat(6, 1fr); gap: 0.5rem;
            opacity: 0; visibility: hidden; transform: translateY(10px); transition: all 0.2s;
            z-index: 20; max-height: 200px; overflow-y: auto;
        }
        .emoji-picker.show { opacity: 1; visibility: visible; transform: translateY(0); }
        .emoji-picker span { font-size: 1.5rem; cursor: pointer; padding: 5px; text-align: center; border-radius: 8px; transition: background 0.2s; }
        .emoji-picker span:hover { background: #f3f4f6; }

        #customBgInput { display: none; }
    </style>
    <script>
        function toggleAttachmentMenu() {
            document.getElementById('attachmentMenu').classList.toggle('show');
            document.querySelector('.icon-btn').classList.toggle('active');
            document.getElementById('emojiPicker').classList.remove('show'); // close emoji if open
        }

        function toggleEmojiPicker() {
            document.getElementById('emojiPicker').classList.toggle('show');
            document.getElementById('attachmentMenu').classList.remove('show'); // close attach if open
            document.querySelector('.icon-btn').classList.remove('active');
        }

        function insertEmoji(emoji) {
            const input = document.getElementById('messageInput');
            input.value += emoji;
            input.focus();
        }

        // Close dropdowns if clicked outside
            if (!e.target.closest('.attachment-wrapper') && !e.target.closest('.emoji-btn') && !e.target.closest('.emoji-picker')) {
                document.getElementById('attachmentMenu').classList.remove('show');
                document.getElementById('emojiPicker').classList.remove('show');
                const btn = document.querySelector('.icon-btn');
                if(btn) btn.classList.remove('active');
            }
        });
        
        // Immediate Theme Restore
        try {
            const storedColor = localStorage.getItem('constructa_chat_color');
            if(storedColor) document.documentElement.style.setProperty('--primary-green', storedColor);
        } catch(e) { console.error('Theme restore error:', e); }
    </script>
</head>
<body>
    <!-- 3D Canvas Background -->
    <div id="canvas-container"></div>

    <header>
        <a href="homeowner.php" class="logo"><i class="fas fa-home"></i> Constructa</a>
        <a href="engineer_directory.php" class="nav-btn"><i class="fas fa-arrow-left"></i> Back to Directory</a>
    </header>

    <main>
        <div class="chat-container">
            <div class="chat-header">
                <div class="header-left">
                    <div class="engineer-avatar">
                       <?php echo strtoupper(substr($engineer['name'], 0, 2)); ?>
                    </div>
                    <div>
                        <h2 style="font-size: 1.2rem;"><?php echo htmlspecialchars($engineer['name']); ?></h2>
                        <p style="font-size: 0.85rem; color: var(--text-gray);">Structural Engineer</p>
                    </div>
                </div>
                
                <!-- 3 Dots Menu -->
                <div class="menu-container">
                    <button class="menu-btn" onclick="toggleMenu()"><i class="fas fa-ellipsis-v"></i></button>
                    <div class="dropdown-menu" id="dropdownMenu">
                        <div class="menu-item" onclick="openThemeModal()">
                            <i class="fas fa-palette"></i> Chat Theme
                        </div>
                        <div class="menu-item" onclick="clearChat()">
                            <i class="fas fa-trash-alt"></i> Clear Chat
                        </div>
                    </div>
                </div>
            </div>

            <div class="chat-messages" id="chatArea">
                <!-- Messages will load here -->
            </div>

            <div class="chat-input-area">
                <!-- Attachment Menu -->
                <div class="attachment-wrapper" style="position: relative;">
                    <button class="icon-btn" onclick="toggleAttachmentMenu()">
                        <i class="fas fa-paperclip"></i>
                    </button>
                    <div class="attachment-menu" id="attachmentMenu">
                        <div class="attach-item" onclick="document.getElementById('photoInput').click()"><i class="fas fa-image" style="color: #2563eb;"></i> Photo</div>
                        <div class="attach-item" onclick="document.getElementById('videoInput').click()"><i class="fas fa-video" style="color: #db2777;"></i> Video</div>
                        <div class="attach-item" onclick="document.getElementById('docInput').click()"><i class="fas fa-file-alt" style="color: #7c3aed;"></i> Document</div>
                        <div class="attach-item" onclick="openCamera()"><i class="fas fa-camera" style="color: #ea580c;"></i> Camera</div>
                    </div>
                </div>

                <!-- Recording UI (Hidden by default) -->
                <div class="recording-ui" id="recordingUI" style="display: none; flex: 1; align-items: center; justify-content: space-between; padding: 0 1rem; background: #fff; border-radius: 12px; height: 50px;">
                     <div style="display: flex; align-items: center; gap: 10px; color: #ef4444;">
                         <i class="fas fa-circle fa-beat" style="font-size: 0.8rem;"></i>
                         <span id="recordingTime" style="font-weight: 600;">0:00</span>
                     </div>
                     <div style="display: flex; gap: 10px;">
                         <button onclick="cancelRecording()" style="background:none; border:none; color:#666; cursor:pointer;"><i class="fas fa-trash"></i></button>
                         <button onclick="stopRecording()" style="background: #294033; color: white; border:none; padding: 5px 15px; border-radius: 20px; cursor:pointer;"><i class="fas fa-paper-plane"></i></button>
                     </div>
                </div>

                <!-- Input with Emoji -->
                <div class="input-wrapper" id="inputWrapper" style="flex: 1; position: relative; display: flex; align-items: center;">
                    <input type="text" id="messageInput" class="chat-input" placeholder="Type a message..." onkeypress="handleKeyPress(event)" style="padding-right: 2.5rem; width: 100%;">
                    <button class="emoji-btn" onclick="toggleEmojiPicker()" style="position: absolute; right: 10px; background: none; border: none; cursor: pointer; color: #666; font-size: 1.2rem;">
                        <i class="far fa-smile"></i>
                    </button>
                    <!-- Simple Emoji Picker -->
                    <div class="emoji-picker" id="emojiPicker">
                        <span onclick="insertEmoji('😊')">😊</span>
                        <span onclick="insertEmoji('😂')">😂</span>
                        <span onclick="insertEmoji('😍')">😍</span>
                        <span onclick="insertEmoji('👍')">👍</span>
                        <span onclick="insertEmoji('🔥')">🔥</span>
                        <span onclick="insertEmoji('🎉')">🎉</span>
                        <span onclick="insertEmoji('🤔')">🤔</span>
                        <span onclick="insertEmoji('😎')">😎</span>
                        <span onclick="insertEmoji('😭')">😭</span>
                        <span onclick="insertEmoji('👋')">👋</span>
                        <span onclick="insertEmoji('🙌')">🙌</span>
                        <span onclick="insertEmoji('🙏')">🙏</span>
                        <span onclick="insertEmoji('👻')">👻</span>
                        <span onclick="insertEmoji('💩')">💩</span>
                        <span onclick="insertEmoji('😡')">😡</span>
                    </div>
                </div>

                <button class="send-btn" onclick="sendMessage()"><i class="fas fa-paper-plane"></i> Send</button>
            </div>
        </div>
    </main>

    <!-- Theme Modal -->
    <div class="modal-overlay" id="themeModal">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="modal-title">Customize Chat</h3>
                <button class="modal-close" onclick="closeThemeModal()">&times;</button>
            </div>
            
            <div class="theme-section">
                <label class="theme-label">Chat Color</label>
                <div class="color-options">
                    <div class="color-circle" style="background: #294033;" onclick="setChatColor('#294033', this)"></div>
                    <div class="color-circle" style="background: #6366f1;" onclick="setChatColor('#6366f1', this)"></div>
                    <div class="color-circle" style="background: #ec4899;" onclick="setChatColor('#ec4899', this)"></div>
                    <div class="color-circle" style="background: #f59e0b;" onclick="setChatColor('#f59e0b', this)"></div>
                    <div class="color-circle" style="background: #10b981;" onclick="setChatColor('#10b981', this)"></div>
                </div>
            </div>

            <div class="theme-section">
                <label class="theme-label">Background Wallpaper</label>
                <div class="bg-options">
                    <div class="bg-card" style="background: #fafafa;" onclick="setChatBg('color', '#fafafa', this)">
                        <span>Default</span>
                    </div>
                    <div class="bg-card" style="background: #eef2ff;" onclick="setChatBg('color', '#eef2ff', this)"></div>
                    <div class="bg-card" style="background: #fdf2f8;" onclick="setChatBg('color', '#fdf2f8', this)"></div>
                    
                    <label class="bg-card" style="border-style: dashed;">
                        <input type="file" id="customBgInput" accept="image/*" onchange="handleBgUpload(this)">
                        <div style="text-align: center; color: var(--primary-green);">
                            <i class="fas fa-cloud-upload-alt"></i><br>
                            <span style="font-size: 0.8rem; font-weight: 600;">Upload</span>
                        </div>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Camera Modal -->
    <div class="modal-overlay" id="cameraModal">
        <div class="modal-card" style="width: 100%; max-width: 600px; padding: 0; background: transparent; box-shadow: none;">
            <div style="position: relative; background: #000; border-radius: 24px; overflow: hidden;">
                <video id="cameraVideo" autoplay playsinline style="width: 100%; height: auto; display: block; max-height: 80vh;"></video>
                <canvas id="cameraCanvas" style="display: none;"></canvas>
                <div style="position: absolute; bottom: 20px; left: 0; right: 0; display: flex; justify-content: center; gap: 20px; padding-bottom: 20px;">
                    <button onclick="closeCameraModal()" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(5px); color: white; border: none; padding: 10px 25px; border-radius: 30px; font-weight: 600;">Cancel</button>
                    <button onclick="capturePhoto()" style="background: white; color: black; border: 4px solid rgba(255,255,255,0.5); width: 70px; height: 70px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; box-shadow: 0 4px 15px rgba(0,0,0,0.3);"><i class="fas fa-camera"></i></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div class="modal-overlay" id="imageModal" style="z-index: 2000;">
        <div class="modal-card" style="width: auto; max-width: 90vw; background: transparent; box-shadow: none; text-align: center;">
            <img id="previewImage" src="" style="max-width: 100%; max-height: 80vh; border-radius: 12px; box-shadow: 0 5px 30px rgba(0,0,0,0.5);">
            <div style="margin-top: 20px; display: flex; justify-content: center; gap: 20px;">
                <button onclick="closeImageModal()" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(5px); color: white; border: none; padding: 10px 25px; border-radius: 30px; font-weight: 600; cursor: pointer;">Close</button>
                <a id="downloadLink" href="" download style="background: white; color: black; text-decoration: none; padding: 10px 25px; border-radius: 30px; font-weight: 600; display: flex; align-items: center; gap: 8px;"><i class="fas fa-download"></i> Save Image</a>
            </div>
        </div>
    </div>

    <!-- Hidden Inputs for Uploads -->
    <input type="file" id="photoInput" accept="image/*" style="display:none;" onchange="handleFileUpload(this)">
    <input type="file" id="videoInput" accept="video/*" style="display:none;" onchange="handleFileUpload(this)">
    <input type="file" id="docInput" accept=".pdf,.doc,.docx,.txt" style="display:none;" onchange="handleFileUpload(this)">
    <!-- Camera input removed as we use modal now -->

    <script>
        const engineerId = <?php echo $engineer_id; ?>;
        const chatArea = document.getElementById('chatArea');
        let cameraStream = null;

        // === 3D BACKGROUND LOGIC ===
        const initBackground3D = () => {
            const container = document.getElementById('canvas-container');
            if (!container) return;

            const scene = new THREE.Scene();
            scene.background = new THREE.Color('#f6f7f2');
            scene.fog = new THREE.Fog('#f6f7f2', 10, 45);

            const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
            camera.position.z = 8;
            camera.position.y = 2;

            const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            container.appendChild(renderer.domElement);

            const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
            scene.add(ambientLight);
            const mainLight = new THREE.DirectionalLight(0xffffff, 0.8);
            mainLight.position.set(10, 10, 10);
            scene.add(mainLight);

            // City Grid
            const cityGroup = new THREE.Group();
            scene.add(cityGroup);

            const buildMat = new THREE.MeshPhongMaterial({ color: 0x294033, transparent: true, opacity: 0.1, side: THREE.DoubleSide });
            const edgeMat = new THREE.LineBasicMaterial({ color: 0x294033, transparent: true, opacity: 0.2 });

            const gridSize = 10;
            const spacing = 3;

            for (let x = -gridSize; x < gridSize; x++) {
                for (let z = -gridSize; z < gridSize; z++) {
                    const h = Math.random() * 2 + 0.5;
                    const geo = new THREE.BoxGeometry(1, h, 1);
                    const mesh = new THREE.Mesh(geo, buildMat);
                    mesh.position.y = h / 2;
                    const edges = new THREE.EdgesGeometry(geo);
                    const line = new THREE.LineSegments(edges, edgeMat);
                    line.position.y = h / 2;
                    
                    const building = new THREE.Group();
                    building.add(mesh);
                    building.add(line);
                    building.position.set(x * spacing, -2, z * spacing);
                    cityGroup.add(building);
                }
            }

            // Interactive
            let mouseX = 0, mouseY = 0;
            document.addEventListener('mousemove', (e) => {
                mouseX = (e.clientX - window.innerWidth / 2) * 0.001;
                mouseY = (e.clientY - window.innerHeight / 2) * 0.001;
            });

            const animate = () => {
                requestAnimationFrame(animate);
                cityGroup.rotation.y += 0.001;
                cityGroup.rotation.x += 0.05 * (mouseY - cityGroup.rotation.x);
                cityGroup.rotation.y += 0.05 * (mouseX - cityGroup.rotation.y);
                renderer.render(scene, camera);
            };
            animate();

            window.addEventListener('resize', () => {
                camera.aspect = window.innerWidth / window.innerHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(window.innerWidth, window.innerHeight);
            });
        };
        if (typeof THREE !== 'undefined') initBackground3D();

        // === MESSAGING LOGIC ===
        function loadMessages() {
            fetch(`backend/get_messages.php?user_id=${engineerId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        chatArea.innerHTML = '';
                        data.messages.forEach(msg => {
                            const div = document.createElement('div');
                            div.className = `message ${msg.type}`;
                            
                            let content = '';
                            if (msg.attachment_url) {
                                if (msg.attachment_type === 'image') {
                                    content += `<img src="${msg.attachment_url}" onclick="openImageModal('${msg.attachment_url}')" style="max-width:200px; border-radius:8px; display:block; margin-bottom:5px; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">`;
                                } else if (msg.attachment_type === 'video') {
                                    content += `<video controls src="${msg.attachment_url}" style="max-width:200px; border-radius:8px; display:block; margin-bottom:5px;"></video>`;
                                } else if (msg.attachment_type === 'audio') {
                                    content += `<audio controls src="${msg.attachment_url}" style="max-width:200px; display:block; margin-bottom:5px;"></audio>`;
                                } else {
                                    content += `<a href="${msg.attachment_url}" target="_blank" style="color:inherit; text-decoration:underline; display:block; margin-bottom:5px;"><i class="fas fa-file"></i> View Document</a>`;
                                }
                            }
                            if (msg.message) {
                                content += `<div>${msg.message}</div>`;
                            }
                            
                            // Unsend Button
                            let actionBtn = '';
                            if (msg.type === 'sent') {
                                actionBtn = `<button onclick="deleteMessage(${msg.id})" style="background:none; border:none; color: rgba(255,255,255,0.7); cursor:pointer; font-size:0.7rem; margin-left: 5px;" title="Unsend"><i class="fas fa-trash"></i></button>`;
                            }

                            div.innerHTML = `
                                <div class="message-content">
                                    ${content}
                                    <div style="display:flex; justify-content:space-between; align-items:center;">
                                        <div class="message-time">${msg.time}</div>
                                        ${actionBtn}
                                    </div>
                                </div>
                            `;
                            chatArea.appendChild(div);
                        });
                        chatArea.scrollTop = chatArea.scrollHeight;
                    }
                })
                .catch(err => console.error(err));
        }

        function sendMessage() {
            const input = document.getElementById('messageInput');
            const text = input.value.trim();
            if (!text) return;

            const formData = new FormData();
            formData.append('receiver_id', engineerId);
            formData.append('message_text', text);

            fetch('backend/send_message.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    input.value = '';
                    loadMessages(); 
                }
            });
        }
        
        function deleteMessage(id) {
            if(!confirm('Unsend this message?')) return;
            
            const formData = new FormData();
            formData.append('message_id', id);
            
            fetch('backend/delete_message.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    loadMessages();
                } else {
                    alert(data.message);
                }
            });
        }
        
        function handleFileUpload(input) {
            if (input.files && input.files[0]) {
                uploadFileObj(input.files[0]);
                input.value = ''; // Reset input to allow re-upload of same file
                toggleAttachmentMenu(); // Close menu
            }
        }
        
        // === VOICE RECORDING LOGIC ===
        let mediaRecorder;
        let audioChunks = [];
        let startTime;
        let recordingInterval;

        async function startRecording() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(stream);
                audioChunks = [];

                mediaRecorder.ondataavailable = event => {
                    audioChunks.push(event.data);
                };

                mediaRecorder.onstop = () => {
                   // Logic handled in stop/cancel functions
                };

                mediaRecorder.start();
                
                // UI Updates
                document.getElementById('inputWrapper').style.display = 'none';
                document.getElementById('defaultButtons').style.display = 'none';
                document.getElementById('recordingUI').style.display = 'flex';
                
                // Timer
                startTime = Date.now();
                updateTimer();
                recordingInterval = setInterval(updateTimer, 1000);

            } catch (err) {
                alert('Microphone access denied: ' + err.message);
            }
        }
        
        function updateTimer() {
            const diff = Math.floor((Date.now() - startTime) / 1000);
            const m = Math.floor(diff / 60);
            const s = diff % 60;
            document.getElementById('recordingTime').textContent = `${m}:${s.toString().padStart(2, '0')}`;
        }

        function stopRecording() {
            if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                mediaRecorder.stop();
                mediaRecorder.stream.getTracks().forEach(track => track.stop());
                clearInterval(recordingInterval);
                
                // Create Audio Blob and Upload
                // setTimeout ensures chunks are gathered
                setTimeout(() => {
                    const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                    const file = new File([audioBlob], "voice_msg_" + Date.now() + ".webm", { type: "audio/webm" });
                    uploadFileObj(file);
                    resetRecordingUI();
                }, 200);
            }
        }

        function cancelRecording() {
             if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                mediaRecorder.stop();
                mediaRecorder.stream.getTracks().forEach(track => track.stop());
             }
             clearInterval(recordingInterval);
             resetRecordingUI();
        }

        function resetRecordingUI() {
            document.getElementById('recordingUI').style.display = 'none';
            document.getElementById('inputWrapper').style.display = 'flex';
            document.getElementById('defaultButtons').style.display = 'flex';
        }
        
        // Update uploadFileObj to accept file type for audio
        function uploadFileObj(file) {
            const formData = new FormData();
            formData.append('receiver_id', engineerId);
            formData.append('attachment', file); // Filename is in file object if created correctly
            
            // Show loading state
            const tempDiv = document.createElement('div');
            tempDiv.className = 'message sent';
            tempDiv.innerHTML = `<div class="message-content"><i class="fas fa-spinner fa-spin"></i> Uploading...</div>`;
            chatArea.appendChild(tempDiv);
            chatArea.scrollTop = chatArea.scrollHeight;

            fetch('backend/send_message.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    loadMessages();
                } else {
                    alert('Upload failed: ' + data.message);
                    if(tempDiv) tempDiv.remove();
                }
            })
            .catch(err => {
                console.error(err);
                if(tempDiv) tempDiv.remove();
            });
        }


        // Camera Functions
        async function openCamera() {
            toggleAttachmentMenu(); // Close menu
            const modal = document.getElementById('cameraModal');
            const video = document.getElementById('cameraVideo');
            modal.classList.add('open');
            
            try {
                cameraStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                video.srcObject = cameraStream;
            } catch (err) {
                alert('Could not access camera: ' + err.message);
                closeCameraModal();
            }
        }

        function closeCameraModal() {
            document.getElementById('cameraModal').classList.remove('open');
            const video = document.getElementById('cameraVideo');
            if (cameraStream) {
                cameraStream.getTracks().forEach(track => track.stop());
                video.srcObject = null;
                cameraStream = null;
            }
        }

        function capturePhoto() {
            const video = document.getElementById('cameraVideo');
            const canvas = document.getElementById('cameraCanvas');
            
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            canvas.toBlob(blob => {
                const file = new File([blob], "camera_photo_" + Date.now() + ".jpg", { type: "image/jpeg" });
                uploadFileObj(file);
                closeCameraModal();
            }, 'image/jpeg', 0.9);
        }

        // Image Modal Functions
        function openImageModal(src) {
            document.getElementById('previewImage').src = src;
            document.getElementById('downloadLink').href = src;
            document.getElementById('imageModal').classList.add('open');
        }
        function closeImageModal() {
            document.getElementById('imageModal').classList.remove('open');
        }
        
        window.addEventListener('click', (e) => {
             if (e.target.id === 'imageModal') closeImageModal();
        });

        function handleKeyPress(e) {
            if (e.key === 'Enter') sendMessage();
        }

        loadMessages();
        setInterval(loadMessages, 3000);

        // === UI INTERACTIONS ===
        function toggleAttachmentMenu() {
            document.getElementById('attachmentMenu').classList.toggle('show');
            document.querySelector('.icon-btn').classList.toggle('active');
            document.getElementById('emojiPicker').classList.remove('show');
        }

        function toggleEmojiPicker() {
            document.getElementById('emojiPicker').classList.toggle('show');
            document.getElementById('attachmentMenu').classList.remove('show');
            document.querySelector('.icon-btn').classList.remove('active');
        }

        function insertEmoji(emoji) {
            const input = document.getElementById('messageInput');
            input.value += emoji;
            input.focus();
        }

        // Close dropdowns if clicked outside
        window.addEventListener('click', (e) => {
            if (!e.target.closest('.attachment-wrapper') && !e.target.closest('.emoji-btn') && !e.target.closest('.emoji-picker') && !e.target.closest('#cameraModal')) {
                document.getElementById('attachmentMenu').classList.remove('show');
                document.getElementById('emojiPicker').classList.remove('show');
                const btn = document.querySelector('.icon-btn');
                if(btn) btn.classList.remove('active');
            }
            // Close menu if outside
            if (!e.target.closest('.menu-container')) {
                document.getElementById('dropdownMenu').classList.remove('show');
            }
        });
        
        // === THEME customization ===
        function toggleMenu() {
            const menu = document.getElementById('dropdownMenu');
            menu.classList.toggle('show');
        }

        function openThemeModal() {
            document.getElementById('themeModal').classList.add('open');
            document.getElementById('dropdownMenu').classList.remove('show');
        }

        function closeThemeModal() {
            document.getElementById('themeModal').classList.remove('open');
        }
        
        function clearChat() {
            if (!engineerId) return;
            if (!confirm('Are you sure you want to clear the entire chat history with this engineer? This cannot be undone.')) return;
            
            const fd = new FormData();
            fd.append('contact_id', engineerId);
            
            fetch('backend/clear_chat.php', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        document.getElementById('chatArea').innerHTML = '';
                        document.getElementById('dropdownMenu').classList.remove('show');
                        loadMessages();
                    } else {
                        alert(data.message || 'Failed to clear chat');
                    }
                })
                .catch(err => alert('Error: ' + err));
        }

        function setChatColor(color, el) {
            document.documentElement.style.setProperty('--primary-green', color);
            try {
                localStorage.setItem('constructa_chat_color', color);
            } catch(e) { console.error(e); }
            
            document.querySelectorAll('.color-circle').forEach(c => c.classList.remove('active'));
            el.classList.add('active');
        }

        function setChatBg(type, value, el) {
            const area = document.getElementById('chatArea');
            if(type === 'color') {
                area.style.backgroundImage = 'none';
                area.style.backgroundColor = value;
            } else if (type === 'image') {
                area.style.backgroundImage = `url(${value})`;
            }
            
            try {
                localStorage.setItem('constructa_chat_bg', JSON.stringify({ type: type, value: value }));
            } catch (e) { console.error(e); }

            document.querySelectorAll('.bg-card').forEach(c => c.classList.remove('active'));
            if(el) el.classList.add('active');
        }

        function handleBgUpload(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const fd = new FormData();
                fd.append('bg_image', file);

                const label = input.closest('label');
                const originalText = label.innerHTML;
                label.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                fetch('backend/upload_theme_bg.php', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        setChatBg('image', data.url);
                        closeThemeModal();
                    } else {
                        alert(data.message || 'Upload failed');
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Upload failed');
                })
                .finally(() => {
                    label.innerHTML = originalText;
                    input.value = '';
                });
            }
        }

        // Initialize Settings on Load
        document.addEventListener('DOMContentLoaded', () => {
             // Theme handled by immediate script in head for color, but bg needs this
            const savedBg = localStorage.getItem('constructa_chat_bg');
            if(savedBg) {
                try {
                    const bg = JSON.parse(savedBg);
                    const area = document.getElementById('chatArea');
                    if(bg.type === 'color') {
                        area.style.backgroundImage = 'none';
                        area.style.backgroundColor = bg.value;
                    } else if(bg.type === 'image') {
                        area.style.backgroundImage = `url(${bg.value})`;
                        area.style.backgroundSize = 'cover';
                    }
                } catch(e) {}
            }
        });

    </script>
</body>
</html>
