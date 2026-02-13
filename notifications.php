<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

require_once 'backend/config.php';
$conn = getDatabaseConnection();

$is_admin = ($_SESSION['role'] === 'admin');
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Notification Blueprints (Locked System Standards)
$blueprints = [
    'BLUEPRINT_MAINTENANCE' => [
        'name' => 'Scheduled Maintenance Window',
        'category' => 'INFO',
        'priority' => 'MEDIUM',
        'template' => 'NOTICE: System maintenance is scheduled for [MODULE] starting [DATE] [TIME] for approximately [DURATION]. Service may be intermittent.',
        'params' => ['MODULE' => 'text', 'DATE' => 'date', 'TIME' => 'time', 'DURATION' => 'text'],
        'impact' => 100, 
        'compliance' => 'ISO 27001'
    ],
    'BLUEPRINT_EMERGENCY' => [
        'name' => 'Emergency System Outage',
        'category' => 'CRITICAL',
        'priority' => 'URGENT',
        'template' => 'URGENT ALERT: Our team has detected a disruption in the [MODULE] service. Resolution is underway. Anticipated restoration: [EST_TIME].',
        'params' => ['MODULE' => 'text', 'EST_TIME' => 'time'],
        'impact' => 100,
        'compliance' => 'SLA_CORE'
    ],
    'BLUEPRINT_SECURITY' => [
        'name' => 'Security Patch Deployment',
        'category' => 'CRITICAL',
        'priority' => 'URGENT',
        'template' => 'SECURITY ACTION: A critical security patch [PATCH_ID] is being deployed. All users must [ACTION] by [DEADLINE] to maintain account integrity.',
        'params' => ['PATCH_ID' => 'text', 'ACTION' => 'text', 'DEADLINE' => 'datetime-local'],
        'impact' => 100,
        'compliance' => 'NIST_800'
    ]
];

// Fetch Lifecycle Timeline
$sql = "SELECT b.*, 
        (SELECT COUNT(*) FROM user_notifications WHERE broadcast_id = b.id) as read_count,
        (SELECT is_read FROM user_notifications WHERE broadcast_id = b.id AND user_id = $user_id LIMIT 1) as my_read_status
        FROM notification_broadcasts b
        LEFT JOIN user_notifications u ON b.id = u.broadcast_id AND u.user_id = $user_id
        WHERE b.status != 'ARCHIVED' 
        AND (u.is_read IS NULL OR u.is_read = 0)
        ";

if (!$is_admin) {
    $sql .= " AND (b.target_type = 'GLOBAL' OR (b.target_type = 'ROLE' AND b.target_value = '$role') OR (b.target_type = 'INDIVIDUAL' AND b.target_value = '$user_id')) ";
}
$sql .= " ORDER BY b.sent_at DESC";
$timeline_res = $conn->query($sql);
$timeline = $timeline_res ? $timeline_res->fetch_all(MYSQLI_ASSOC) : [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Communication Hub - Constructa</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>

    <style>
        :root {
            --primary: #294033;
            --accent: #3b82f6;
            --bg-color: #f8fafc;
            --card-bg: rgba(255, 255, 255, 0.85);
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: rgba(0, 0, 0, 0.08);
            --shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Outfit', sans-serif;
            background: transparent;
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        #canvas-container {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            z-index: -1; background: var(--bg-color); pointer-events: none;
        }

        header {
            padding: 2.5rem 4rem 1.5rem 4rem; /* Increased top padding */
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            position: sticky; 
            top: 0; 
            z-index: 100;
        }

        .logo { 
            font-size: 1.5rem; font-weight: 800; color: var(--primary); text-decoration: none; 
            display:flex; align-items:center; gap:0.5rem; 
            perspective: 1000px;
            margin-top: 0.5rem; /* Additional breathing room */
        }
        .logo span { display: inline-block; }
        .logo .letter {
            display: inline-block;
            transform-origin: bottom center;
            opacity: 0;
            transform: rotateX(90deg) translateY(50px);
            animation: letterWave 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
        }

        @keyframes letterWave {
            0% { opacity: 0; transform: rotateX(90deg) translateY(50px); }
            100% { opacity: 1; transform: rotateX(0deg) translateY(0); }
        }

        .nav-container { display: flex; gap: 1rem; align-items: center; }
        .top-nav-btn {
            padding: 0.7rem 1.4rem;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 4px;
            text-decoration: none;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.75rem;
            color: var(--text-main);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.04);
        }

        .top-nav-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.12);
        }

        .nav-link { color: var(--text-muted); text-decoration: none; font-weight: 500; display:flex; align-items:center; gap:0.5rem; }

        main { flex: 1; display: grid; grid-template-columns: 350px 1fr; max-width: 1400px; margin: 2rem auto; width: 100%; gap: 2rem; padding: 0 2rem; align-items: start; }

        /* Left Timeline */
        .timeline-panel {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            border: 1px solid var(--border);
            padding: 1.5rem;
            display: flex; flex-direction: column; gap: 1.5rem;
            box-shadow: var(--shadow);
            max-height: 80vh; overflow-y: auto;
        }

        .timeline-item {
            padding: 1rem;
            border-radius: 16px;
            background: rgba(255,255,255,0.4);
            border: 1px solid var(--border);
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
        }
        .timeline-item:hover { transform: translateY(-3px) scale(1.02); background: white; border-color: var(--accent); }
        .timeline-item.active { border-left: 4px solid var(--accent); }

        .time-label { font-size: 0.7rem; color: var(--text-muted); font-family: 'JetBrains Mono'; margin-bottom: 0.25rem; }
        .item-title { font-size: 0.9rem; font-weight: 700; margin-bottom: 0.5rem; }
        
        .progress-mini { height: 4px; background: #e2e8f0; border-radius: 2px; overflow: hidden; }
        .progress-bar { height: 100%; background: var(--accent); transition: width 0.5s; width: 0%; }

        /* Orchestration Center */
        .orchestration-panel {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: var(--shadow);
        }

        .section-header { margin-bottom: 2rem; }
        .section-header h2 { font-size: 1.75rem; font-weight: 800; color: var(--primary); }
        .section-header p { color: var(--text-muted); font-size: 1rem; }

        .blueprint-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 2.5rem; }
        .blueprint-card {
            background: white; border: 1px solid var(--border); border-radius: 16px;
            padding: 1.5rem; cursor: pointer; transition: 0.3s;
        }
        .blueprint-card:hover { transform: translateY(-5px); border-color: var(--accent); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        .blueprint-card.selected { background: rgba(59, 130, 246, 0.05); border-color: var(--accent); }
        .blueprint-card h4 { font-size: 1rem; font-weight: 700; margin-bottom: 0.25rem; }
        .blueprint-card span { font-size: 0.7rem; font-family: 'JetBrains Mono'; color: var(--text-muted); }

        .field-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; }
        .field-group { display: flex; flex-direction: column; gap: 0.5rem; }
        .field-label { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); }
        .field-input {
            padding: 0.8rem 1.2rem; border-radius: 12px; border: 1px solid var(--border);
            background: white; font-family: inherit; font-size: 0.95rem; outline: none; transition: 0.3s;
        }
        .field-input:focus { border-color: var(--accent); box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }

        .preview-area {
            margin-top: 2.5rem; padding: 2rem; border-radius: 20px; background: rgba(0,0,0,0.02);
            border: 1px dashed var(--border);
        }
        .preview-text {
            font-family: 'JetBrains Mono', monospace; font-size: 0.95rem; line-height: 1.6;
            color: var(--text-main);
        }
        .preview-text strong { color: var(--accent); font-weight: 700; }

        .action-bar { margin-top: 2.5rem; display: flex; justify-content: space-between; align-items: center; }
        .btn-send {
            background: var(--primary); color: white; border: none; padding: 1rem 2.5rem;
            border-radius: 14px; font-weight: 700; cursor: pointer; transition: 0.3s;
            display: flex; align-items: center; gap: 0.75rem;
        }
        .btn-send:hover:not(:disabled) { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(41, 64, 51, 0.2); }
        .btn-send:disabled { opacity: 0.5; cursor: not-allowed; }

        .impact-indicator { font-family: 'JetBrains Mono'; color: var(--text-muted); font-size: 0.8rem; }
        .impact-indicator b { color: var(--accent); font-size: 1.1rem; }

        /* User Message Bubble */
        .msg-bubble {
            background: white; border: 1px solid var(--border); border-radius: 24px;
            padding: 2.5rem; box-shadow: var(--shadow); margin-bottom: 2rem;
            animation: slideUp 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
        }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

        .btn-read {
            padding: 0.6rem 1.2rem;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 4px;
            text-decoration: none;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.7rem;
            color: var(--text-main);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.04);
            cursor: pointer;
        }

        .btn-read:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.12);
        }

        .read-status-indicator {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 0;
        }

        .read-dim { opacity: 0.6; filter: grayscale(0.5); }

    </style>
</head>
<body>

<div id="canvas-container"></div>

<header>
    <?php
    $dashboard_url = 'homeowner.php';
    if ($role === 'admin') $dashboard_url = 'admin_dashboard.php';
    else if ($role === 'engineer') $dashboard_url = 'engineer.php';
    ?>
    <a href="<?php echo $dashboard_url; ?>" class="logo" id="logoText">
        <i data-lucide="shield-check"></i>
        <span>Constructa</span>
    </a>
    <div class="nav-container">
        <a href="<?php echo $dashboard_url; ?>" class="top-nav-btn">
            <i data-lucide="layout-dashboard"></i> Dashboard
        </a>
        <a href="backend/logout.php" class="top-nav-btn">
            <i data-lucide="log-out"></i> Logout
        </a>
    </div>
</header>

<main>
    <!-- Left: Lifecycle Timeline -->
    <aside class="timeline-panel">
        <h3 style="font-size: 0.75rem; font-weight: 800; color: var(--text-muted); letter-spacing: 2px;">BROADCAST_HISTORY</h3>
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <?php foreach($timeline as $event): ?>
            <div class="timeline-item <?php echo ($event['status'] === 'SENT') ? 'active' : ''; ?>" id="time-<?php echo $event['id']; ?>">
                <p class="time-label"><?php echo date('H:i', strtotime($event['sent_at'])); ?> | ROI: <?php echo $event['id']; ?></p>
                <p class="item-title"><?php echo $event['title']; ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </aside>

    <!-- Right: Orchestrator / User Feed -->
    <section>
        <?php if ($is_admin): ?>
        <div class="orchestration-panel">
            <div class="section-header">
                <h2>Broadcast Orchestrator</h2>
                <p>Authorize system-standard communications for the platform nodes.</p>
            </div>

            <div class="blueprint-grid">
                <?php foreach($blueprints as $id => $bp): ?>
                <div class="blueprint-card" id="bp-<?php echo $id; ?>" onclick="selectBlueprint('<?php echo $id; ?>', <?php echo htmlspecialchars(json_encode($bp)); ?>)">
                    <h4><?php echo $bp['name']; ?></h4>
                    <span><?php echo $bp['compliance']; ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <div id="param-section" style="opacity: 0.2; pointer-events: none; transition: 0.5s;">
                <h3 style="font-size: 0.9rem; font-weight: 800; margin-bottom: 1.5rem; text-transform: uppercase;">1. Configure Parameters</h3>
                <div class="field-grid" id="param-inputs">
                    <!-- Dynamic fields -->
                </div>

                <div class="preview-area">
                    <p style="font-size: 0.65rem; font-weight: 800; color: var(--text-muted); margin-bottom: 1rem; letter-spacing: 1px;">ORCHESTRATION_PAYLOAD_PREVIEW</p>
                    <div id="payload-preview" class="preview-text">Please select a blueprint to begin...</div>
                </div>

                <div class="action-bar">
                    <div class="impact-indicator">Impact Estimation: <b id="reach-count">0</b> active nodes</div>
                    <button class="btn-send" id="btn-send" disabled onclick="executeOrchestration()">
                        <i data-lucide="zap"></i> EXECUTE UNIVERSE BROADCAST
                    </button>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="user-feed">
            <h2 style="font-size: 2rem; font-weight: 800; margin-bottom: 2rem;">System Governance Feed</h2>
            <?php foreach($timeline as $n): ?>
            <div class="msg-bubble unread-entry" id="msg-<?php echo $n['id']; ?>">
                <div style="display: flex; justify-content: space-between; margin-bottom: 1.5rem;">
                    <span style="font-family: 'JetBrains Mono'; font-size: 0.75rem; color: var(--accent); font-weight: 700;">
                        <span class="unread-dot"></span>
                        [ <?php echo $n['category']; ?> ]
                    </span>
                    <span style="color: var(--text-muted); font-size: 0.8rem;"><?php echo date('M d, Y | H:i', strtotime($n['sent_at'])); ?></span>
                </div>
                <h3 style="font-size: 1.4rem; font-weight: 700; margin-bottom: 1rem;"><?php echo $n['title']; ?></h3>
                <p style="font-size: 1.1rem; line-height: 1.6; color: var(--text-main); margin-bottom: 2rem;"><?php echo $n['content']; ?></p>
                
                <div id="read-container-<?php echo $n['id']; ?>">
                    <button class="btn-read" onclick="markRead(<?php echo $n['id']; ?>, event)" id="read-btn-<?php echo $n['id']; ?>">
                        <i data-lucide="check"></i> MARK AS READ
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>
</main>

<script>
    lucide.createIcons();

    // 3D Background Logic (Matches Admin Dashboard)
    const initBG = () => {
        // Logo Animation
        const logo = document.getElementById('logoText');
        const span = logo.querySelector('span');
        const text = span.textContent;
        span.textContent = '';
        [...text].forEach((char, i) => {
            const letter = document.createElement('span');
            letter.className = 'letter';
            letter.textContent = char;
            letter.style.animationDelay = `${i * 0.1}s`;
            span.appendChild(letter);
        });

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

        const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
        scene.add(ambientLight);
        const mainLight = new THREE.DirectionalLight(0xffffff, 0.8);
        mainLight.position.set(10, 10, 10);
        scene.add(mainLight);
        const blueLight = new THREE.PointLight(0x3d5a49, 0.5);
        blueLight.position.set(-5, 5, 5);
        scene.add(blueLight);

        const cityGroup = new THREE.Group();
        scene.add(cityGroup);
        const buildingMaterial = new THREE.MeshPhongMaterial({ color: 0x294033, transparent: true, opacity: 0.1, side: THREE.DoubleSide });
        const edgeMaterial = new THREE.LineBasicMaterial({ color: 0x294033, transparent: true, opacity: 0.3 });
        const gridSize = 10;
        const spacing = 3;

        for (let x = -gridSize; x < gridSize; x++) {
            for (let z = -gridSize; z < gridSize; z++) {
                const height = Math.random() * 2 + 0.5;
                const building = new THREE.Group();
                const geometry = new THREE.BoxGeometry(1, height, 1);
                const mesh = new THREE.Mesh(geometry, buildingMaterial);
                mesh.position.y = height / 2;
                const edges = new THREE.EdgesGeometry(geometry);
                const line = new THREE.LineSegments(edges, edgeMaterial);
                line.position.y = height / 2;
                building.add(mesh);
                building.add(line);
                building.position.set(x * spacing, -2, z * spacing);
                cityGroup.add(building);
            }
        }

        const houseGroup = new THREE.Group();
        const baseGeo = new THREE.BoxGeometry(2, 2, 2);
        const baseLine = new THREE.LineSegments(new THREE.EdgesGeometry(baseGeo), new THREE.LineBasicMaterial({ color: 0x294033, linewidth: 2 }));
        houseGroup.add(baseLine);
        const roofGeo = new THREE.ConeGeometry(1.5, 1.2, 4);
        const roofLine = new THREE.LineSegments(new THREE.EdgesGeometry(roofGeo), new THREE.LineBasicMaterial({ color: 0x3d5a49, linewidth: 2 }));
        roofLine.position.y = 1.6;
        roofLine.rotation.y = Math.PI / 4;
        houseGroup.add(roofLine);

        const floatGroup = new THREE.Group();
        floatGroup.add(houseGroup);
        floatGroup.position.set(0, 0, 2);
        scene.add(floatGroup);

        let mouseX = 0, mouseY = 0;
        document.addEventListener('mousemove', (event) => {
            mouseX = (event.clientX - window.innerWidth / 2) * 0.001;
            mouseY = (event.clientY - window.innerHeight / 2) * 0.001;
        });

        const animate = () => {
            requestAnimationFrame(animate);
            cityGroup.rotation.y += 0.001;
            floatGroup.rotation.y += 0.005;
            floatGroup.position.y = Math.sin(Date.now() * 0.001) * 0.5 + 0.5;
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
    initBG();

    let activeBP = null;
    let activeID = null;

    function selectBlueprint(id, data) {
        activeBP = data;
        activeID = id;

        document.querySelectorAll('.blueprint-card').forEach(c => c.classList.remove('selected'));
        document.getElementById(`bp-${id}`).classList.add('selected');

        const ps = document.getElementById('param-section');
        ps.style.opacity = '1';
        ps.style.pointerEvents = 'auto'; // FIX: corrected property name

        const grid = document.getElementById('param-inputs');
        grid.innerHTML = '';

        Object.keys(data.params).forEach(p => {
            const div = document.createElement('div');
            div.className = 'field-group';
            const type = data.params[p];
            let extra = '';
            if (type === 'date' || type === 'datetime-local') {
                const today = new Date().toISOString().split('T')[0];
                extra = `min="${today}"`;
            }
            if (type === 'time' || type === 'datetime-local') {
                // High-end Composite Tempus Picker
                const showDate = type === 'datetime-local';
                const today = new Date().toISOString().split('T')[0];
                
                div.innerHTML = `
                    <label class="field-label">${p}</label>
                    <div class="composite-time" style="display:flex; gap:5px; flex-wrap:wrap;">
                        ${showDate ? `<input type="date" class="field-input bp-param time-d" data-param="${p}" min="${today}" oninput="up()" style="flex:1 1 100%; margin-bottom:5px;">` : ''}
                        <select class="field-input bp-param time-h" data-param="${p}" oninput="up()" style="flex:1;">
                            ${Array.from({length: 12}, (_, i) => `<option value="${i + 1}">${String(i + 1).padStart(2, '0')}</option>`).join('')}
                        </select>
                        <select class="field-input bp-param time-m" data-param="${p}" oninput="up()" style="flex:1;">
                            ${Array.from({length: 60}, (_, i) => `<option value="${i}">${String(i).padStart(2, '0')}</option>`).join('')}
                        </select>
                        <select class="field-input bp-param time-p" data-param="${p}" oninput="up()" style="flex:auto; min-width:60px;">
                            <option value="AM">AM</option>
                            <option value="PM">PM</option>
                        </select>
                    </div>
                `;
            } else {
                div.innerHTML = `<label class="field-label">${p}</label><input type="${type}" class="field-input bp-param" data-param="${p}" ${extra} oninput="up()">`;
            }
            grid.appendChild(div);
        });

        // Add Scope selector
        const scopeDiv = document.createElement('div');
        scopeDiv.className = 'field-group';
        scopeDiv.style.gridColumn = 'span 2';
        scopeDiv.innerHTML = `
            <label class="field-label">AUDIENCE_SCOPE</label>
            <select class="field-input" id="t-type" onchange="up()">
                <option value="GLOBAL">GLOBAL_SERVICE_NODES</option>
                <option value="ROLE">ROLE_CLUSTER</option>
                <option value="INDIVIDUAL">INDIVIDUAL_NODE</option>
            </select>
            <input type="text" id="t-val" class="field-input" style="margin-top:10px; display:none;" placeholder="Enter target ID/Role...">
        `;
        grid.appendChild(scopeDiv);
        document.getElementById('t-type').addEventListener('change', function() {
            document.getElementById('t-val').style.display = this.value === 'GLOBAL' ? 'none' : 'block';
        });

        up();
    }

    function up() {
        if (!activeBP) return;
        let msg = activeBP.template;
        
        // Comprehensive Live Validation
        const allInputs = document.querySelectorAll('.field-input');
        allInputs.forEach(inp => {
            let v = inp.value;
            const paramKey = inp.getAttribute('data-param');
            if(!paramKey) return;
            
            let clean = v;

            // 1. Strict Digit-Only (PATCH_ID)
            if (paramKey === 'PATCH_ID') {
                clean = v.replace(/[^0-9]/g, '');
            } 
            // 2. Strict Alphabetic-Only (MODULE, ACTION) - No digits, no arithmetic
            else if (paramKey === 'MODULE' || paramKey.includes('ACTION')) {
                clean = v.replace(/[0-9+\-*/=%<>^&|()[\]{}\\]/g, '');
            }
            // 3. Alphanumeric (Other text fields) - Allow digits, block arithmetic
            else if (inp.type === 'text') {
                clean = v.replace(/[+\-*/=%<>^&|()[\]{}\\]/g, '');
            }
            // 4. Numeric Integrity (Positive only)
            else if (inp.type === 'number') {
                clean = v.replace(/[^0-9.]/g, '');
            }

            if (v !== clean) {
                inp.value = clean;
                inp.classList.add('shake');
                setTimeout(() => inp.classList.remove('shake'), 400);
            }

            // 5. Temporal Integrity (No past dates)
            if ((inp.type === 'date' || inp.classList.contains('time-d')) && inp.value) {
                const selected = new Date(inp.value);
                const now = new Date();
                now.setHours(0,0,0,0);
                if (selected < now) {
                    inp.value = '';
                    inp.style.borderColor = 'var(--critical)';
                    setTimeout(() => inp.style.borderColor = 'var(--border)', 1000);
                }
            }
        });

        // 4. Rebuild Preview from BP Params
        let ready = true;
        
        // Group composite time values
        const composites = {};
        
        const params = document.querySelectorAll('.bp-param');
        params.forEach(inp => {
            const paramKey = inp.getAttribute('data-param');
            
            // Handle composite time and datetime picking
            if (inp.classList.contains('time-h') || inp.classList.contains('time-m') || inp.classList.contains('time-p') || inp.classList.contains('time-d')) {
                if (!composites[paramKey]) composites[paramKey] = {h: '12', m: '00', p: 'AM', d: ''};
                if (inp.classList.contains('time-h')) composites[paramKey].h = inp.value;
                if (inp.classList.contains('time-m')) composites[paramKey].m = inp.value.padStart(2, '0');
                if (inp.classList.contains('time-p')) composites[paramKey].p = inp.value;
                if (inp.classList.contains('time-d')) composites[paramKey].d = inp.value;
                return; 
            }

            let v = inp.value;
            msg = msg.split(`[${paramKey}]`).join(`<strong>${v || '['+paramKey+']'}</strong>`);
            if (!v) ready = false;
        });

        // Inject Composite Time and Datetime values
        Object.keys(composites).forEach(k => {
            const timeStr = `${composites[k].h}:${composites[k].m} ${composites[k].p}`;
            const finalVal = composites[k].d ? `${composites[k].d} at ${timeStr}` : timeStr;
            msg = msg.split(`[${k}]`).join(`<strong>${finalVal}</strong>`);
            if (composites[k].d === '' && msg.includes(k)) ready = false; // Check date if it's a datetime
        });

        document.getElementById('payload-preview').innerHTML = msg;
        document.getElementById('btn-send').disabled = !ready;

        // Animate reach
        anime({
            targets: { val: 0 },
            val: Math.floor(Math.random() * 50) + 120,
            round: 1,
            easing: 'easeOutExpo',
            update: (a) => document.getElementById('reach-count').innerText = a.animatables[0].target.val
        });
    }

    async function executeOrchestration() {
        const btn = document.getElementById('btn-send');
        btn.innerText = "AUTHENTICATING...";
        btn.disabled = true;

        const p = {
            category: activeBP.category,
            priority: activeBP.priority,
            target_type: document.getElementById('t-type').value,
            target_value: document.getElementById('t-val').value,
            title: activeBP.name,
            content: document.getElementById('payload-preview').innerText
        };

        const res = await fetch('backend/send_notification.php', {
            method: 'POST',
            body: JSON.stringify(p)
        });
        const d = await res.json();
        if(d.success) {
            btn.innerText = "BROADCAST SUCCESSFUL";
            setTimeout(() => location.reload(), 1500);
        }
    }

    async function markRead(id, event) {
        if(event) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        const btn = document.getElementById(`read-btn-${id}`);
        if(btn) {
            btn.innerHTML = '<span style="opacity:0.5">...</span>';
            btn.disabled = true;
        }

        try {
            const res = await fetch('backend/notification_actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'read', id: id })
            });
            const d = await res.json();
            
            if(d.success) {
                // Replace button with status indicator in Main feed
                const container = document.getElementById(`read-container-${id}`);
                if(container) {
                    container.innerHTML = `<div class="read-status-indicator"><i data-lucide="check-circle-2"></i> MARKED AS READ</div>`;
                }

                if(window.lucide) lucide.createIcons();
                
                // Sync dashboard bell icon unread count
                updateBellBadge();
            }
        } catch (err) {
            console.error("Mark as read failed:", err);
            if(btn) {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="check"></i> MARK AS READ';
                if(window.lucide) lucide.createIcons();
            }
        }
    }

    function updateBellBadge() {
        const bellBadge = document.getElementById('notif-badge') || (window.parent && window.parent.document.getElementById('notif-badge'));
        if(bellBadge) {
            let current = parseInt(bellBadge.innerText) || 0;
            if(current > 0) {
                current--;
                bellBadge.innerText = current;
                if(current === 0) bellBadge.style.display = 'none';
            }
        }
    }
</script>

</body>
</html>
