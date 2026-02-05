<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'engineer') { header('Location: login.html'); exit(); }
$username = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Engineer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Messages - Constructa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script>
        // Immediate Theme Restore to prevent FOUC
        try {
            const storedColor = localStorage.getItem('constructa_chat_color');
            if(storedColor) document.documentElement.style.setProperty('--primary-green', storedColor);
        } catch(e) { console.error('Theme restore error:', e); }
    </script>
    <style>
        :root { --bg-color: #f6f7f2; --text-dark: #121212; --text-gray: #555555; --primary-green: #294033; --chat-bg: #fafafa; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        
        body { background: transparent; min-height: 100vh; display: flex; flex-direction: column; }
        
        /* 3D Background Canvas */
        #canvas-container {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            z-index: -1; background: #f6f7f2; pointer-events: none;
        }

        header { padding: 1.5rem 3rem; display: flex; justify-content: space-between; max-width: 1600px; margin: 0 auto; width: 100%; background: rgba(246, 247, 242, 0.9); position: sticky; top: 0; z-index: 100; backdrop-filter: blur(10px); border-bottom: 1px solid rgba(0,0,0,0.05); }
        .logo { display: flex; align-items: center; gap: 0.5rem; font-size: 1.25rem; font-weight: 700; color: #000000; text-decoration: none; }
        nav { display: flex; gap: 1.5rem; align-items: center; }
        .nav-btn {
            background: white;
            border: 1px solid #e2e8f0;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-weight: 800;
            font-size: 0.85rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--text-dark);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.2s ease;
        }
        .nav-btn:hover {
            background: #fff;
            border-color: var(--text-dark);
            color: var(--text-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .nav-btn i { font-size: 1rem; }
        
        main { flex: 1; max-width: 1400px; margin: 0 auto; width: 100%; padding: 3rem; position: relative; z-index: 1; }
        
        .page-header { text-align: center; margin-bottom: 2rem; }
        .page-title { font-size: 3rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--primary-green); }
        
        .messages-container { display: grid; grid-template-columns: 350px 1fr; gap: 2rem; height: 75vh; }
        
        /* Contacts List */
        .contacts-list { 
            background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);
            border-radius: 20px; padding: 1.5rem; overflow-y: auto; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid rgba(255,255,255,0.5);
        }
        .contact-item { 
            padding: 1rem; border-radius: 12px; cursor: pointer; margin-bottom: 0.5rem; 
            transition: all 0.3s; display: flex; align-items: center; gap: 1rem; 
            border: 1px solid transparent;
        }
        .contact-item:hover { background: #f3f4f6; }
        .contact-item.active { background: white; border-color: var(--primary-green); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        
        .contact-avatar { 
            width: 45px; height: 45px; border-radius: 50%; 
            background: linear-gradient(135deg, #10b981, #059669); 
            display: flex; align-items: center; justify-content: center; 
            color: white; font-weight: 700; 
        }
        .contact-info h4 { font-size: 0.95rem; margin-bottom: 0.2rem; }
        .contact-info p { font-size: 0.8rem; color: var(--text-gray); }

        /* Chat Area */
        .chat-area { 
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);
            border-radius: 20px; display: flex; flex-direction: column; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid rgba(255,255,255,0.5);
            overflow: hidden; position: relative;
        }
        
        .chat-header { 
            padding: 1.5rem; border-bottom: 1px solid #eee; display: flex; 
            justify-content: space-between; align-items: center; background: white; 
        }
        
        .chat-messages { 
            flex: 1; padding: 2rem; overflow-y: auto; 
            background: var(--chat-bg); background-size: cover; background-position: center;
            display: flex; flex-direction: column; gap: 1rem; transition: background 0.3s;
        }
        
        .message { display: flex; max-width: 70%; }
        .message.sent { align-self: flex-end; flex-direction: row-reverse; }
        .message.received { align-self: flex-start; }
        
        .message-bubble { 
            padding: 1rem 1.5rem; border-radius: 18px; font-size: 0.95rem; line-height: 1.5; position: relative; 
        }
        .message.sent .message-bubble { background: var(--primary-green); color: white; border-bottom-right-radius: 4px; }
        .message.received .message-bubble { background: white; border: 1px solid #eee; border-bottom-left-radius: 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
        
        .message-time { font-size: 0.7rem; margin-top: 0.3rem; opacity: 0.7; text-align: right; }

        /* Input Area */
        .chat-input-area { 
            padding: 1.5rem; background: white; border-top: 1px solid #eee; 
            display: flex; gap: 1rem; align-items: center; 
        }
        .chat-input { 
            flex: 1; padding: 1rem; border: 1px solid #ddd; border-radius: 12px; 
            outline: none; transition: border-color 0.3s; 
        }
        .chat-input:focus { border-color: var(--primary-green); }
        
        .btn-send { 
            background: var(--primary-green); color: white; border: none; 
            padding: 0 1.5rem; border-radius: 12px; font-weight: 600; cursor: pointer; height: 50px;
        }

        /* Attachments & Emoji */
        .icon-btn {
            background: #f3f4f6; border: none; width: 45px; height: 45px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
            color: var(--text-gray); cursor: pointer; transition: all 0.2s;
        }
        .icon-btn:hover, .icon-btn.active { background: var(--primary-green); color: white; transform: rotate(45deg); }

        .attachment-menu {
            position: absolute; bottom: 85px; left: 1.5rem; width: 220px;
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
            position: absolute; bottom: 85px; right: 80px; width: 280px;
            background: white; border-radius: 16px; padding: 1rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            display: grid; grid-template-columns: repeat(6, 1fr); gap: 0.5rem;
            opacity: 0; visibility: hidden; transform: translateY(10px); transition: all 0.2s;
            z-index: 20; max-height: 200px; overflow-y: auto;
        }
        .emoji-picker.show { opacity: 1; visibility: visible; transform: translateY(0); }
        .emoji-picker span { font-size: 1.5rem; cursor: pointer; padding: 5px; text-align: center; border-radius: 8px; }
        .emoji-picker span:hover { background: #f3f4f6; }

        /* Menu & Modal */
        .menu-container { position: relative; }
        .menu-btn { background: none; border: none; font-size: 1.2rem; color: var(--text-gray); cursor: pointer; padding: 0.5rem; border-radius: 50%; }
        .dropdown-menu {
            position: absolute; right: 0; top: 120%; width: 200px;
            background: white; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            padding: 0.5rem; opacity: 0; visibility: hidden; transform: translateY(-10px); transition: all 0.2s; z-index: 50;
        }
        .dropdown-menu.show { opacity: 1; visibility: visible; transform: translateY(0); }
        .menu-item { padding: 0.8rem 1rem; display: flex; align-items: center; gap: 0.8rem; cursor: pointer; border-radius: 8px; }
        .menu-item:hover { background: #f3f4f6; }

        .modal-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000;
            display: flex; align-items: center; justify-content: center; opacity: 0; visibility: hidden; transition: all 0.3s;
        }
        .modal-overlay.open { opacity: 1; visibility: visible; }
        .modal-card { background: white; width: 90%; max-width: 500px; border-radius: 24px; padding: 2rem; transform: scale(0.9); transition: all 0.3s; }
        .modal-overlay.open .modal-card { transform: scale(1); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .color-options { display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 2rem; }
        .color-circle { width: 40px; height: 40px; border-radius: 50%; cursor: pointer; border: 3px solid transparent; }
        .color-circle.active { border-color: #121212; transform: scale(1.1); }
        .bg-options { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
        .bg-card { height: 80px; border-radius: 12px; border: 2px solid #eee; cursor: pointer; background-size: cover; background-position: center; display: flex; align-items: center; justify-content: center; }
        .bg-card.active { border-color: var(--primary-green); }
        
        #customBgInput { display: none; }
    </style>
</head>
<body>
    <div id="canvas-container"></div>
    <header>
        <a href="engineer.php" class="logo"><i class="far fa-building"></i> Constructa</a>
        <nav>
            <a href="engineer.php" class="nav-btn">
                <i class="fas fa-home"></i> HOME
            </a>
            <a href="login.html" class="nav-btn">
                <i class="fas fa-sign-out-alt"></i> LOGOUT
            </a>
        </nav>
    </header>
    <main>
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-comments"></i> Client Messages</h1>
            <p style="color: var(--text-gray); font-size: 1.1rem;">Direct communication channel with your current clients</p>
        </div>

        <div class="messages-container">
            <!-- Contacts List -->
            <div class="contacts-list" id="contactsList">
                <div style="text-align: center; color: #999; padding: 2rem;">Loading contacts...</div>
            </div>

            <!-- Chat Area -->
            <div class="chat-area" id="chatContainer" style="display: none;">
                <div class="chat-header">
                    <div>
                        <h3 id="chatUserName">Select a client</h3>
                        <p style="color: var(--text-gray); font-size: 0.9rem;" id="chatUserRole">...</p>
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
                
                <div class="chat-messages" id="chatMessages">
                    <!-- Messages go here -->
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
                        <input type="text" id="msgInput" class="chat-input" placeholder="Type a message..." onkeypress="handleKeyPress(event)" style="padding-right: 2.5rem; width: 100%;">
                        <button class="emoji-btn" onclick="toggleEmojiPicker()" style="position: absolute; right: 10px; background: none; border: none; cursor: pointer; color: #666; font-size: 1.2rem;">
                            <i class="far fa-smile"></i>
                        </button>
                        
                        <div class="emoji-picker" id="emojiPicker">
                            <span onclick="insertEmoji('😊')">😊</span><span onclick="insertEmoji('😂')">😂</span><span onclick="insertEmoji('😍')">😍</span>
                            <span onclick="insertEmoji('👍')">👍</span><span onclick="insertEmoji('🔥')">🔥</span><span onclick="insertEmoji('🎉')">🎉</span>
                            <span onclick="insertEmoji('🤔')">🤔</span><span onclick="insertEmoji('😎')">😎</span><span onclick="insertEmoji('😭')">😭</span>
                            <span onclick="insertEmoji('👋')">👋</span><span onclick="insertEmoji('🙌')">🙌</span><span onclick="insertEmoji('🙏')">🙏</span>
                        </div>
                    </div>
                
                    <div id="defaultButtons" style="display: flex; gap: 0.5rem;">
                        <button class="icon-btn" onclick="startRecording()" style="background: #f3f4f6;"><i class="fas fa-microphone"></i></button>
                        <button class="btn-send" onclick="sendMsg()"><i class="fas fa-paper-plane"></i> Send</button>
                    </div>
                </div>
            </div>
            
            <!-- Empty State -->
            <div class="chat-area" id="emptyState" style="display: flex; align-items: center; justify-content: center; color: #999;">
                <p>Select a client on the left to start chatting</p>
            </div>
        </div>
    </main>
    
    <!-- Theme Modal -->
    <div class="modal-overlay" id="themeModal">
        <div class="modal-card">
            <div class="modal-header">
                <h3 style="font-size: 1.5rem; color: var(--primary-green);">Customize Chat</h3>
                <button onclick="closeThemeModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>
            <div style="margin-bottom: 2rem;">
                <label style="display:block; font-weight:600; margin-bottom:0.8rem;">Chat Color</label>
                <div class="color-options">
                    <div class="color-circle" style="background: #294033;" onclick="setChatColor('#294033', this)"></div>
                    <div class="color-circle" style="background: #6366f1;" onclick="setChatColor('#6366f1', this)"></div>
                    <div class="color-circle" style="background: #ec4899;" onclick="setChatColor('#ec4899', this)"></div>
                    <div class="color-circle" style="background: #f59e0b;" onclick="setChatColor('#f59e0b', this)"></div>
                </div>
            </div>
            <div>
                <label style="display:block; font-weight:600; margin-bottom:0.8rem;">Background</label>
                <div class="bg-options">
                    <div class="bg-card" style="background: #fafafa;" onclick="setChatBg('color', '#fafafa', this)">Default</div>
                    <div class="bg-card" style="background: #eef2ff;" onclick="setChatBg('color', '#eef2ff', this)"></div>
                    <label class="bg-card" style="border-style: dashed;">
                        <input type="file" id="customBgInput" accept="image/*" onchange="handleBgUpload(this)">
                        <div style="text-align: center; color: var(--primary-green); font-size:0.8rem;"><i class="fas fa-cloud-upload-alt"></i><br>Upload</div>
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

    <!-- Hidden Inputs -->
    <input type="file" id="photoInput" accept="image/*" style="display:none;" onchange="handleFileUpload(this)">
    <input type="file" id="videoInput" accept="video/*" style="display:none;" onchange="handleFileUpload(this)">
    <input type="file" id="docInput" accept=".pdf,.doc,.docx,.txt" style="display:none;" onchange="handleFileUpload(this)">

    <script>
        let currentUserId = 0;
        let cameraStream = null;
        const chatArea = document.getElementById('chatMessages');

        // === 3D BG ===
        const initBackground3D = () => {
            const container = document.getElementById('canvas-container');
            const scene = new THREE.Scene(); scene.background = new THREE.Color('#f6f7f2'); scene.fog = new THREE.Fog('#f6f7f2', 10, 45);
            const camera = new THREE.PerspectiveCamera(60, window.innerWidth/window.innerHeight, 0.1, 1000); camera.position.set(0,2,8);
            const renderer = new THREE.WebGLRenderer({antialias:true, alpha:true}); renderer.setSize(window.innerWidth, window.innerHeight);
            container.appendChild(renderer.domElement);
            
            const cityGroup = new THREE.Group(); scene.add(cityGroup);
            const mat = new THREE.MeshPhongMaterial({color:0x294033, transparent:true, opacity:0.1});
            const lineMat = new THREE.LineBasicMaterial({color:0x294033, transparent:true, opacity:0.2});
            
            for(let x=-10; x<10; x++) { for(let z=-10; z<10; z++) {
                const h = Math.random()*2+0.5;
                const mesh = new THREE.Mesh(new THREE.BoxGeometry(1,h,1), mat); mesh.position.y=h/2;
                const line = new THREE.LineSegments(new THREE.EdgesGeometry(new THREE.BoxGeometry(1,h,1)), lineMat); line.position.y=h/2;
                const b = new THREE.Group(); b.add(mesh, line); b.position.set(x*3, -2, z*3); cityGroup.add(b);
            }}
            
            scene.add(new THREE.AmbientLight(0xffffff,0.6));
            const dl = new THREE.DirectionalLight(0xffffff,0.8); dl.position.set(10,10,10); scene.add(dl);
            
            let mx=0, my=0; document.addEventListener('mousemove', e=>{ mx=(e.clientX-window.innerWidth/2)*0.001; my=(e.clientY-window.innerHeight/2)*0.001; });
            const animate = () => { requestAnimationFrame(animate); cityGroup.rotation.y+=0.001; cityGroup.rotation.x+=0.05*(my-cityGroup.rotation.x); cityGroup.rotation.y+=0.05*(mx-cityGroup.rotation.y); renderer.render(scene, camera); };
            animate();
            window.addEventListener('resize', ()=>{ camera.aspect=window.innerWidth/window.innerHeight; camera.updateProjectionMatrix(); renderer.setSize(window.innerWidth, window.innerHeight); });
        };
        if(typeof THREE !== 'undefined') initBackground3D();

        // === CORE LOGIC ===
        function loadContacts() {
            fetch('backend/get_chat_contacts.php')
                .then(res => res.json())
                .then(data => {
                    const list = document.getElementById('contactsList');
                    list.innerHTML = '';
                    if (data.status === 'success' && data.contacts.length > 0) {
                        data.contacts.forEach(contact => {
                            const activeClass = contact.id == currentUserId ? 'active' : '';
                            const div = document.createElement('div');
                            div.className = `contact-item ${activeClass}`;
                            div.onclick = () => selectUser(contact.id, contact.name, contact.role);
                            div.innerHTML = `
                                <div class="contact-avatar">${contact.avatar}</div>
                                <div class="contact-info">
                                    <h4>${contact.name}</h4>
                                    <p>${contact.last_message ? contact.last_message.substring(0, 30) : 'No messages'}</p>
                                </div>
                            `;
                            list.appendChild(div);
                        });
                    } else {
                        list.innerHTML = '<div style="padding:1rem; text-align:center;">No active chats</div>';
                    }
                });
        }

        function selectUser(id, name, role) {
            currentUserId = id;
            localStorage.setItem('constructa_last_chat_user', JSON.stringify({id, name, role}));
            
            document.getElementById('chatContainer').style.display = 'flex';
            document.getElementById('emptyState').style.display = 'none';
            document.getElementById('chatUserName').textContent = name;
            document.getElementById('chatUserRole').textContent = role || 'Homeowner';
            loadContacts();
            loadMessages();
        }

        function loadMessages() {
            if (!currentUserId) return;
            fetch(`backend/get_messages.php?user_id=${currentUserId}`)
                .then(res => res.json())
                .then(data => {
                    const area = document.getElementById('chatMessages');
                    area.innerHTML = '';
                    if (data.status === 'success') {
                        data.messages.forEach(msg => {
                            let content = '';
                            if (msg.attachment_url) {
                                if (msg.attachment_type === 'image') content += `<img src="${msg.attachment_url}" onclick="openImageModal('${msg.attachment_url}')" style="max-width:200px; border-radius:8px; display:block; margin-bottom:5px; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">`;
                                else if (msg.attachment_type === 'video') content += `<video controls src="${msg.attachment_url}" style="max-width:200px; border-radius:8px; display:block; margin-bottom:5px;"></video>`;
                                else if (msg.attachment_type === 'audio') content += `<audio controls src="${msg.attachment_url}" style="max-width:200px; display:block; margin-bottom:5px;"></audio>`;
                                else content += `<a href="${msg.attachment_url}" target="_blank" style="color:inherit; text-decoration:underline; display:block; margin-bottom:5px;"><i class="fas fa-file"></i> View Document</a>`;
                            }
                            if (msg.message) content += `<div>${msg.message}</div>`;
                            
                            // Unsend Button
                            let actionBtn = '';
                            if (msg.type === 'sent') {
                                actionBtn = `<button onclick="deleteMessage(${msg.id})" style="background:none; border:none; color: rgba(255,255,255,0.7); cursor:pointer; font-size:0.7rem; margin-left: 5px;" title="Unsend"><i class="fas fa-trash"></i></button>`;
                            }
                            
                            const div = document.createElement('div');
                            div.className = `message ${msg.type}`;
                            div.innerHTML = `<div class="message-bubble">${content}<div style="display:flex; justify-content:space-between; align-items:center;"><div class="message-time">${msg.time}</div>${actionBtn}</div></div>`;
                            area.appendChild(div);
                        });
                        area.scrollTop = area.scrollHeight;
                    }
                });
        }

        function sendMsg() {
            const input = document.getElementById('msgInput');
            const text = input.value.trim();
            if(!text || !currentUserId) return;

            const fd = new FormData();
            fd.append('receiver_id', currentUserId);
            fd.append('message_text', text);

            fetch('backend/send_message.php', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        input.value = '';
                        loadMessages();
                        loadContacts();
                    }
                });
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

        function deleteMessage(id) {
            if(!confirm('Unsend this message?')) return;
            const fd = new FormData(); fd.append('message_id', id);
            fetch('backend/delete_message.php', { method: 'POST', body: fd })
            .then(res => res.json()).then(d => { if(d.status==='success') loadMessages(); else alert(d.message); });
        }
        
        function handleFileUpload(input) {
            if (input.files[0] && currentUserId) {
                uploadFileObj(input.files[0]);
                input.value = '';
                toggleAttachmentMenu();
            }
        }
        
        function uploadFileObj(file) {
            const fd = new FormData();
            fd.append('receiver_id', currentUserId);
            fd.append('attachment', file);
            
            const tempDiv = document.createElement('div'); tempDiv.className = 'message sent';
            tempDiv.innerHTML = `<div class="message-bubble"><i class="fas fa-spinner fa-spin"></i> Uploading...</div>`;
            document.getElementById('chatMessages').appendChild(tempDiv);

            fetch('backend/send_message.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') loadMessages();
                else { alert('Upload failed'); tempDiv.remove(); }
            });
        }
        
        // Camera Functions Setup
        async function openCamera() {
            toggleAttachmentMenu();
            const modal = document.getElementById('cameraModal');
            const video = document.getElementById('cameraVideo');
            modal.classList.add('open');
            try {
                cameraStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                video.srcObject = cameraStream;
            } catch (err) { alert('Camera access denied: ' + err.message); closeCameraModal(); }
        }
        function closeCameraModal() {
            document.getElementById('cameraModal').classList.remove('open');
            if(cameraStream) { cameraStream.getTracks().forEach(t=>t.stop()); document.getElementById('cameraVideo').srcObject=null; cameraStream=null; }
        }
        function capturePhoto() {
            const v = document.getElementById('cameraVideo');
            const c = document.getElementById('cameraCanvas');
            c.width=v.videoWidth; c.height=v.videoHeight;
            c.getContext('2d').drawImage(v,0,0);
            c.toBlob(b => {
                const f = new File([b], "cam_capture_"+Date.now()+".jpg", {type:"image/jpeg"});
                uploadFileObj(f); closeCameraModal();
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

        function handleKeyPress(e) { if (e.key === 'Enter') sendMsg(); }

        // === UI HELPERS ===
        function toggleAttachmentMenu() {
            document.getElementById('attachmentMenu').classList.toggle('show');
            document.querySelector('.icon-btn').classList.toggle('active');
            document.getElementById('emojiPicker').classList.remove('show');
        }
        function toggleEmojiPicker() {
            document.getElementById('emojiPicker').classList.toggle('show');
            document.getElementById('attachmentMenu').classList.remove('show');
        }
        function insertEmoji(e) { 
            const i = document.getElementById('msgInput'); i.value+=e; i.focus(); 
        }
        
        window.onclick = (e) => {
            if(!e.target.closest('.attachment-wrapper') && !e.target.closest('.emoji-btn') && !e.target.closest('.emoji-picker') && !e.target.closest('#cameraModal')) {
                document.getElementById('attachmentMenu').classList.remove('show');
                document.getElementById('emojiPicker').classList.remove('show');
            }
            if (!e.target.closest('.menu-container')) document.getElementById('dropdownMenu').classList.remove('show');
        };

        // Theme Logic
        function toggleMenu() { document.getElementById('dropdownMenu').classList.toggle('show'); }
        function openThemeModal() { document.getElementById('themeModal').classList.add('open'); document.getElementById('dropdownMenu').classList.remove('show'); }
        function closeThemeModal() { document.getElementById('themeModal').classList.remove('open'); }

        function clearChat() {
            if (!currentUserId) return;
            if (!confirm('Are you sure you want to clear the entire chat history with this user? This cannot be undone.')) return;
            
            const fd = new FormData();
            fd.append('contact_id', currentUserId);
            
            fetch('backend/clear_chat.php', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        document.getElementById('chatMessages').innerHTML = ''; // Clear UI immediately
                        document.getElementById('dropdownMenu').classList.remove('show'); // Close menu
                        loadMessages(); // Reload to be sure (optional, but good practice)
                    } else {
                        alert(data.message || 'Failed to clear chat');
                    }
                })
                .catch(err => alert('Error: ' + err));
        }
        
        function setChatColor(c, el) {
            document.documentElement.style.setProperty('--primary-green', c);
            try {
                localStorage.setItem('constructa_chat_color', c);
            } catch(e) {
                console.error('Failed to save color:', e);
                alert('Failed to save theme setting. Storage might be full.');
            }
            document.querySelectorAll('.color-circle').forEach(x => x.classList.remove('active')); 
            if(el) el.classList.add('active');
        }
        function setChatBg(t, v, el) {
            const area = document.getElementById('chatMessages');
            if(t==='color') { 
                area.style.backgroundImage='none'; 
                area.style.backgroundColor=v; 
            } else { 
                area.style.backgroundImage=`url(${v})`; 
            }
            
            // Now v is just a short URL string or color code, so no quota issues
            localStorage.setItem('constructa_chat_bg', JSON.stringify({type:t, value:v}));
            
            document.querySelectorAll('.bg-card').forEach(x => x.classList.remove('active')); 
            if(el) el.classList.add('active');
        }

        function handleBgUpload(input) {
            if(input.files[0]) {
                const file = input.files[0];
                const fd = new FormData();
                fd.append('bg_image', file);

                // Show loading state
                const label = input.closest('label');
                const originalText = label.innerHTML;
                label.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                fetch('backend/upload_theme_bg.php', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Pass the returned server URL to setChatBg
                        setChatBg('image', data.url);
                        closeThemeModal();
                    } else {
                        alert(data.message || 'Upload failed');
                    }
                })
                .catch(err => {
                    console.error('Upload Error:', err);
                    alert('Upload failed');
                })
                .finally(() => {
                    label.innerHTML = originalText;
                    input.value = ''; // Reset input
                });
            }
        }
        
        // Helper to match color in modal
        function updateThemeUI(color) {
            const circles = document.querySelectorAll('.color-circle');
            circles.forEach(c => {
                c.classList.remove('active');
                // Convert both to hex to compare or just check simple match
                // For now, we just check if the background style matches roughly
                if (c.style.backgroundColor === color || 
                    rgbToHex(c.style.backgroundColor) === color) {
                   c.classList.add('active');
                }
            });
        }
        
        function rgbToHex(rgb) {
            if (!rgb) return '';
            if (rgb.startsWith('#')) return rgb;
            const sep = rgb.indexOf(",") > -1 ? "," : " ";
            const rgbArr = rgb.substr(4).split(")")[0].split(sep);
            let r = (+rgbArr[0]).toString(16), g = (+rgbArr[1]).toString(16), b = (+rgbArr[2]).toString(16);
            if (r.length == 1) r = "0" + r; if (g.length == 1) g = "0" + g; if (b.length == 1) b = "0" + b;
            return "#" + r + g + b;
        }

        // Init
        document.addEventListener('DOMContentLoaded', () => {
             // 1. Restore Theme IMMEDIATELLY
            const c = localStorage.getItem('constructa_chat_color'); 
            if(c) {
                document.documentElement.style.setProperty('--primary-green', c);
                // Try to update UI if possible (requires hex conversion usually)
            }
            
            const b = localStorage.getItem('constructa_chat_bg');
            if(b) {
                try {
                    const j = JSON.parse(b);
                    const area = document.getElementById('chatMessages');
                    if(j.type==='color') { 
                        area.style.backgroundImage='none'; 
                        area.style.backgroundColor=j.value; 
                    } else { 
                        area.style.backgroundImage=`url(${j.value})`; 
                        area.style.backgroundSize='cover'; 
                        area.style.backgroundPosition = 'center';
                    }
                } catch(e) { console.error('Theme restore error', e); }
            }

             loadContacts();
             setInterval(() => { if(currentUserId) loadMessages(); }, 3000);
            
            // Restore active chat if any
            const lastChat = localStorage.getItem('constructa_last_chat_user');
            if(lastChat) {
                try {
                    const u = JSON.parse(lastChat);
                    selectUser(u.id, u.name, u.role);
                } catch(e) {}
            }
        });

    </script>
</body>
</html>
