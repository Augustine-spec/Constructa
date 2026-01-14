<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'engineer') {
    header('Location: login.html');
    exit();
}
$username = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Engineer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Projects - Constructa Studio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/Flip.min.js"></script>
    <style>
        :root {
            --primary: #1a2e23;
            --primary-light: #2c4a3b;
            --accent: #d4af37;
            --bg-color: #e5e7eb;
            --glass-bg: rgba(255, 255, 255, 0.25);
            --glass-border: rgba(255, 255, 255, 0.4);
            --text-main: #2c3e50;
            --text-muted: #64748b;
            --slate: #334155;
            --concrete: #f2f2f2;
            --transition: all 0.5s cubic-bezier(0.23, 1, 0.32, 1);
            --card-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.1), 0 4px 10px -5px rgba(0, 0, 0, 0.05);
            --hover-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.25), 0 18px 36px -18px rgba(0, 0, 0, 0.3);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', 'Inter', sans-serif;
        }

        body {
            background: radial-gradient(circle at top left, #f8f9fa, #e9ecef);
            color: var(--text-main);
            min-height: 100vh;
            overflow-x: hidden;
            perspective: 2000px;
        }

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

        nav {
            background: var(--glass-bg);
            backdrop-filter: blur(30px);
            padding: 1rem 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--glass-border);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-logo {
            font-weight: 800;
            font-size: 1.6rem;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            letter-spacing: -0.5px;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .nav-btn {
            background: white;
            border: 1px solid rgba(0, 0, 0, 0.05);
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--text-main);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            transition: var(--transition);
        }

        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .main-container {
            max-width: 1500px;
            margin: 0 auto;
            padding: 2rem 3rem;
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 2.5rem;
        }

        .content-area {
            display: flex;
            flex-direction: column;
            gap: 2.5rem;
        }

        .summary-strip {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 1rem;
        }

        .summary-item {
            background: rgba(255, 255, 255, 0.45);
            backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 24px;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.07);
            transition: var(--transition);
        }

        .summary-item:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.85);
            box-shadow: 0 15px 45px rgba(31, 38, 135, 0.12);
        }

        .summary-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .summary-value {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--primary);
            display: flex;
            align-items: baseline;
            gap: 0.5rem;
        }

        .summary-value span { font-size: 1rem; color: var(--text-muted); }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            background: #d1fae5;
            color: #065f46;
            letter-spacing: 1px;
            position: relative;
        }

        .status-badge.active::after {
            content: '';
            position: absolute;
            width: 8px;
            height: 8px;
            background: #059669;
            border-radius: 50%;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            box-shadow: 0 0 10px #059669;
            animation: pulse-ring 1.5s infinite;
        }

        .status-badge.completed {
            background: #e2e8f0;
            color: #475569;
        }

        @keyframes pulse-ring {
            0% { transform: translateY(-50%) scale(0.8); opacity: 0.8; }
            50% { transform: translateY(-50%) scale(1.5); opacity: 0; }
            100% { transform: translateY(-50%) scale(0.8); opacity: 0.8; }
        }

        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 2.5rem;
        }

        .project-card {
            background: var(--glass-bg);
            backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border);
            border-radius: 28px;
            padding: 2rem;
            position: relative;
            transform-style: preserve-3d;
            transition: var(--transition);
            cursor: pointer;
            box-shadow: var(--card-shadow);
            overflow: visible;
        }

        .project-card:hover { box-shadow: var(--hover-shadow); }

        .card-inner { transform: translateZ(30px); display: flex; flex-direction: column; gap: 1.5rem; }

        .card-top { display: flex; justify-content: space-between; align-items: flex-start; }

        .project-3d-visual {
            width: 100px; height: 100px; background: rgba(41, 64, 51, 0.05);
            border-radius: 20px; position: relative; overflow: hidden;
            display: flex; align-items: center; justify-content: center;
        }

        .mini-building-container { width: 100%; height: 100%; }

        .card-main-info { flex: 1; padding-left: 1.5rem; }

        .project-name { font-size: 1.6rem; font-weight: 800; color: var(--primary); line-height: 1.2; margin-bottom: 0.25rem; }

        .client-type { font-size: 1rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; }

        .progress-3d {
            position: absolute; top: 2rem; right: 2rem; width: 80px; height: 80px;
            display: flex; align-items: center; justify-content: center;
        }

        .progress-ring-svg { transform: rotate(-90deg); width: 80px; height: 80px; }

        .progress-ring-bg { fill: none; stroke: rgba(0,0,0,0.05); stroke-width: 8; }

        .progress-ring-circle {
            fill: none; stroke: var(--primary); stroke-width: 8; stroke-linecap: round;
            stroke-dasharray: 226.19; stroke-dashoffset: 226.19;
            transition: stroke-dashoffset 1.5s cubic-bezier(0.23, 1, 0.32, 1);
        }

        .progress-text { position: absolute; font-size: 1rem; font-weight: 800; color: var(--primary); }

        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.2rem; margin: 0.5rem 0; }

        .info-pill {
            display: flex; align-items: center; gap: 0.75rem; background: rgba(255,255,255,0.25);
            padding: 0.8rem 1rem; border-radius: 16px; border: 1px solid rgba(255,255,255,0.4);
        }

        .info-pill i { font-size: 1rem; color: var(--primary-light); transition: transform 0.3s ease; }

        .project-card:hover .info-pill i { transform: scale(1.2) rotate(5deg); }

        .info-label { font-size: 0.85rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; display: block; }

        .info-data { font-size: 1.05rem; font-weight: 600; color: var(--text-main); }

        .card-actions { display: flex; gap: 1rem; margin-top: 0.5rem; }

        .btn-manage-new {
            flex: 1; padding: 1.2rem; background: var(--primary); color: white; border: none;
            border-radius: 18px; font-weight: 800; font-size: 0.9rem; text-transform: uppercase;
            letter-spacing: 1px; cursor: pointer; transition: var(--transition);
            display: flex; align-items: center; justify-content: center; gap: 0.75rem;
            box-shadow: 0 10px 20px -5px rgba(41, 64, 51, 0.4);
        }

        .btn-manage-new:hover { background: #111e17; transform: translateY(-3px) scale(1.02); box-shadow: 0 15px 30px -5px rgba(41, 64, 51, 0.5); }

        .filter-panel {
            background: var(--glass-bg); backdrop-filter: blur(30px); border: 1px solid var(--glass-border);
            border-radius: 28px; padding: 2.5rem; height: calc(100vh - 120px); position: sticky;
            top: 100px; display: flex; flex-direction: column; gap: 2rem; box-shadow: var(--card-shadow);
        }

        .filter-section-title { font-size: 0.9rem; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 1.2rem; display: flex; align-items: center; gap: 0.75rem; }

        .filter-options { display: flex; flex-direction: column; gap: 0.75rem; }

        .filter-opt {
            padding: 1rem 1.2rem; background: rgba(255,255,255,0.25); border: 1px solid rgba(255,255,255,0.4);
            border-radius: 16px; font-size: 0.9rem; font-weight: 600; color: var(--text-main);
            cursor: pointer; transition: var(--transition); display: flex; align-items: center; justify-content: space-between;
        }

        .filter-opt:hover, .filter-opt.active { background: white; border-color: var(--primary); transform: translateX(5px); color: var(--primary); }

        .filter-opt i { opacity: 0; transition: 0.3s; }

        .filter-opt.active i { opacity: 1; }

        body.zooming { overflow: hidden; }

        body.zooming .zoom-overlay { opacity: 1; pointer-events: all; }

        .search-container {
            margin-bottom: 0.5rem;
            position: relative;
        }
        .search-container input {
            width: 100%;
            padding: 1.2rem 1.2rem 1.2rem 3.5rem;
            background: rgba(255,255,255,0.35);
            border: 1px solid rgba(255,255,255,0.6);
            border-radius: 20px;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--primary);
            transition: var(--transition);
            backdrop-filter: blur(10px);
        }
        .search-container input:focus {
            outline: none;
            background: white;
            border-color: var(--primary);
            box-shadow: 0 10px 30px rgba(26, 46, 35, 0.1);
        }
        .search-container i {
            position: absolute;
            left: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            font-size: 1.1rem;
            opacity: 0.6;
        }

        .no-projects { text-align: center; padding: 4rem 2rem; background: var(--glass-bg); border-radius: 28px; border: 1px solid var(--glass-border); box-shadow: var(--card-shadow); grid-column: 1 / -1; }

        .no-projects i { font-size: 4rem; color: var(--primary); margin-bottom: 1.5rem; opacity: 0.5; }

        @media (max-width: 1100px) { .main-container { grid-template-columns: 1fr; } .filter-panel { display: none; } }
    </style>
</head>
<body>
    <div id="canvas-container"></div>
    
    <!-- Navbar -->
    <nav>
        <a href="engineer.php" class="nav-logo">
            <i class="far fa-building"></i>
            Constructa
        </a>
        <div class="nav-links">
            <a href="engineer.php" class="nav-btn">
                <i class="fas fa-home"></i> DASHBOARD
            </a>
            <a href="login.html" class="nav-btn">
                <i class="fas fa-sign-out-alt"></i> LOGOUT
            </a>
        </div>
    </nav>

    <?php
    require_once 'backend/config.php';
    $conn = getDatabaseConnection();
    $engineer_id = $_SESSION['user_id'];
    
    // Fetch accepted project requests
    $stmt = $conn->prepare("
        SELECT pr.*, u.name as homeowner_name, u.email as homeowner_email 
        FROM project_requests pr 
        JOIN users u ON pr.homeowner_id = u.id 
        WHERE pr.engineer_id = ? AND (pr.status = 'accepted' OR pr.status = 'completed')
        ORDER BY pr.updated_at DESC
    ");
    $stmt->bind_param("i", $engineer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    ?>

    <div class="main-container">
        <div class="content-area">
            <div class="summary-strip">
                <?php
                $total_projects = $result->num_rows;
                $ongoing = 0;
                $completed = 0;
                $projects_data = [];
                
                if ($total_projects > 0) {
                    $result->data_seek(0);
                    while($p = $result->fetch_assoc()) {
                        // Dynamically calculate progress based on construction stages (0 to 6)
                        $max_stages = 6; 
                        // Cap at 100% for completed projects (stage > 6)
                        $effective_stage = min($p['current_stage'], $max_stages);
                        $p['current_progress'] = round(($effective_stage / $max_stages) * 100);
                        
                        if ($p['status'] === 'completed' || $p['current_progress'] >= 100) {
                            $completed++;
                        } else {
                            $ongoing++;
                        }
                        $projects_data[] = $p;
                    }
                }
                ?>
                <div class="summary-item">
                    <span class="summary-label">Total Projects</span>
                    <div class="summary-value" id="stat-total">0</div>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Ongoing</span>
                    <div class="summary-value" id="stat-ongoing">0</div>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Completed</span>
                    <div class="summary-value" id="stat-completed">0</div>
                </div>
            </div>

            <div class="projects-grid">
                <?php if ($total_projects > 0): 
                    foreach($projects_data as $project):
                        $progress = $project['current_progress'];
                ?>
                    <div class="project-card" 
                         data-progress="<?php echo $progress; ?>" 
                         data-date="<?php echo $project['updated_at']; ?>"
                         data-status="<?php echo ($progress >= 100) ? 'completed' : 'active'; ?>">
                        <div class="card-inner">
                            <div class="card-top">
                                <div class="project-3d-visual">
                                    <div class="mini-building-container" id="mini-build-<?php echo $project['id']; ?>"></div>
                                </div>
                                <div class="card-main-info">
                                    <div class="client-type"><?php echo htmlspecialchars($project['project_type']); ?></div>
                                    <div class="project-name"><?php echo htmlspecialchars($project['project_title'] ?: "Residential Complex"); ?></div>
                                    <div class="status-badge <?php echo ($project['status'] === 'completed') ? 'completed' : 'active'; ?>">
                                        <?php echo ($project['status'] === 'completed') ? 'Completed' : 'Active'; ?>
                                    </div>
                                </div>
                                
                                <div class="progress-3d">
                                    <svg class="progress-ring-svg">
                                        <circle class="progress-ring-bg" cx="40" cy="40" r="36"></circle>
                                        <circle class="progress-ring-circle" cx="40" cy="40" r="36" data-pct="<?php echo $progress; ?>"></circle>
                                    </svg>
                                    <div class="progress-text"><?php echo $progress; ?>%</div>
                                </div>
                            </div>

                            <div class="info-grid">
                                <div class="info-pill">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <div>
                                        <span class="info-label">Location</span>
                                        <span class="info-data"><?php echo htmlspecialchars($project['location']); ?></span>
                                    </div>
                                </div>
                                <div class="info-pill">
                                    <i class="fas fa-calendar-check"></i>
                                    <div>
                                        <span class="info-label">Started</span>
                                        <span class="info-data"><?php echo date('M d, Y', strtotime($project['updated_at'])); ?></span>
                                    </div>
                                </div>
                                <div class="info-pill">
                                    <i class="fas fa-hourglass-half"></i>
                                    <div>
                                        <span class="info-label">Timeline</span>
                                        <span class="info-data"><?php echo htmlspecialchars($project['timeline']); ?></span>
                                    </div>
                                </div>
                                <div class="info-pill">
                                    <i class="fas fa-user-tie"></i>
                                    <div>
                                        <span class="info-label">Homeowner</span>
                                        <span class="info-data"><?php echo htmlspecialchars($project['homeowner_name']); ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="card-actions">
                                <button class="btn-manage-new" onclick='manageProject(this, <?php echo htmlspecialchars(json_encode($project), ENT_QUOTES, "UTF-8"); ?>)'>
                                    <i class="fas fa-expand-arrows-alt"></i> MANAGE PROJECT
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; else: ?>
                    <div class="no-projects">
                        <i class="fas fa-drafting-pencil"></i>
                        <h3>Studio Workspace is Empty</h3>
                        <p>Accept a project request to begin your structural journey.</p>
                        <a href="project_requests.php" class="nav-btn" style="margin-top: 2rem;">VIEW PENDING REQUESTS</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="filter-panel">
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="projectSearch" placeholder="Search by name, type or location..." onkeyup="handleSearch(event)">
            </div>

            <div>
                <h3 class="filter-section-title"><i class="fas fa-filter"></i> Studio Filters</h3>
                <div class="filter-options">
                    <div class="filter-opt active" onclick="filterProjects(event, 'all')">All Projects <i class="fas fa-check"></i></div>
                    <div class="filter-opt" onclick="filterProjects(event, 'active')">Ongoing <i class="fas fa-check"></i></div>
                    <div class="filter-opt" onclick="filterProjects(event, 'completed')">Completed <i class="fas fa-check"></i></div>
                </div>
            </div>

            <div>
                <h3 class="filter-section-title"><i class="fas fa-sort-amount-down"></i> Sort Metrics</h3>
                <div class="filter-options sort-options">
                    <div class="filter-opt sort-opt active" onclick="handleSort(event, 'progress')">Progress Depth <i class="fas fa-check"></i></div>
                    <div class="filter-opt sort-opt" onclick="handleSort(event, 'date')">Start Date <i class="fas fa-check"></i></div>
                    <div class="filter-opt sort-opt" onclick="handleSort(event, 'name')">Name Index <i class="fas fa-check"></i></div>
                </div>
            </div>

            <!-- Removed Secure Studio Mode button -->
        </div>
    </div>

    <div class="zoom-overlay"></div>

    <script>
        // Project Data for Counters (Accessing PHP variables via JS)
        const stats = {
            total: <?php echo $total_projects; ?>,
            ongoing: <?php echo $ongoing; ?>,
            completed: <?php echo $completed; ?>
        };

        // Initialize Stats Counters with GSAP
        function initCounters() {
            gsap.to("#stat-total", { innerText: stats.total, duration: 2, snap: { innerText: 1 } });
            gsap.to("#stat-ongoing", { innerText: stats.ongoing, duration: 2, snap: { innerText: 1 } });
            gsap.to("#stat-completed", { innerText: stats.completed, duration: 2, snap: { innerText: 1 } });
        }

        // 3D Card Tilt Effect
        function initTilt() {
            const cards = document.querySelectorAll('.project-card');
            cards.forEach(card => {
                card.addEventListener('mousemove', (e) => {
                    const rect = card.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    const centerX = rect.width / 2;
                    const centerY = rect.height / 2;
                    const rotateX = (y - centerY) / 15;
                    const rotateY = (centerX - x) / 15;

                    gsap.to(card, {
                        rotateX: rotateX,
                        rotateY: rotateY,
                        translateZ: 50,
                        duration: 0.5,
                        ease: "power2.out"
                    });
                });

                card.addEventListener('mouseleave', () => {
                    gsap.to(card, {
                        rotateX: 0,
                        rotateY: 0,
                        translateZ: 0,
                        duration: 0.8,
                        ease: "elastic.out(1, 0.5)"
                    });
                });
            });
        }

        // Animate Progress Rings
        function initProgressRings() {
            const circles = document.querySelectorAll('.progress-ring-circle');
            circles.forEach(circle => {
                const pct = circle.dataset.pct;
                const circumference = 2 * Math.PI * 36;
                const offset = circumference - (pct / 100) * circumference;
                
                gsap.to(circle, {
                    strokeDashoffset: offset,
                    duration: 2.5,
                    delay: 0.2,
                    ease: "power4.out"
                });
            });
        }

        // Mini 3D Buildings for each card
        function initMiniBuildings() {
            const containers = document.querySelectorAll('.mini-building-container');
            containers.forEach(container => {
                const projectCard = container.closest('.project-card');
                const progress = parseInt(projectCard.dataset.progress);
                
                const scene = new THREE.Scene();
                const camera = new THREE.PerspectiveCamera(75, 1, 0.1, 1000);
                camera.position.set(2.5, 2.5, 2.5);
                camera.lookAt(0, 0, 0);

                const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
                renderer.setSize(100, 100);
                renderer.setPixelRatio(window.devicePixelRatio);
                container.appendChild(renderer.domElement);

                const light = new THREE.DirectionalLight(0xffffff, 1.2);
                light.position.set(5, 5, 5);
                scene.add(light);
                scene.add(new THREE.AmbientLight(0xffffff, 0.6));

                // Building geometry grows with progress
                const height = (progress / 100) * 2 + 0.4;
                const geometry = new THREE.BoxGeometry(1.2, height, 1.2);
                const material = new THREE.MeshPhongMaterial({ 
                    color: 0x1a2e23, 
                    transparent: true, 
                    opacity: 0.9,
                    shininess: 80
                });
                const building = new THREE.Mesh(geometry, material);
                building.position.y = height / 2 - 1;
                scene.add(building);

                // Wireframe edges for that "architectural" look
                const edges = new THREE.EdgesGeometry(geometry);
                const line = new THREE.LineSegments(edges, new THREE.LineBasicMaterial({ color: 0xd4af37, opacity: 0.4, transparent: true }));
                line.position.copy(building.position);
                scene.add(line);

                function animateMini() {
                    requestAnimationFrame(animateMini);
                    building.rotation.y += 0.01;
                    line.rotation.y += 0.01;
                    renderer.render(scene, camera);
                }
                animateMini();
            });
        }

        // Management Transition (Zoom)
        function manageProject(btn, project) {
            document.body.classList.add('zooming');
            const card = btn.closest('.project-card');
            
            // Premium Camera Style Zoom
            gsap.to(card, {
                scale: 12,
                z: 2000,
                x: (window.innerWidth / 2 - btn.getBoundingClientRect().left) * 5,
                y: (window.innerHeight / 2 - btn.getBoundingClientRect().top) * 5,
                opacity: 0,
                duration: 1.5,
                ease: "power4.inOut",
                onComplete: () => {
                    setTimeout(() => {
                        window.location.href = `engineer_workspace.php?id=${project.id}`;
                    }, 200);
                }
            });

            gsap.to('.main-container', {
                opacity: 0,
                filter: 'blur(20px)',
                duration: 1.2,
                ease: "power2.in"
            });
        }

        // Global variables for filter/search state
        let currentStatus = 'all';
        let searchQuery = '';

        function handleSearch(e) {
            searchQuery = e.target.value.toLowerCase();
            applyFilters();
        }

        function filterProjects(e, status) {
            document.querySelectorAll('.filter-opt:not(.sort-opt)').forEach(opt => opt.classList.remove('active'));
            e.currentTarget.classList.add('active');
            currentStatus = status;
            applyFilters();
        }

        function applyFilters() {
            const cards = document.querySelectorAll('.project-card');
            cards.forEach(card => {
                const name = card.querySelector('.project-name').textContent.toLowerCase();
                const type = card.querySelector('.client-type').textContent.toLowerCase();
                const location = card.querySelector('.info-data').textContent.toLowerCase();
                const status = card.dataset.status || 'active'; // In real app, bind progress status

                const matchesSearch = name.includes(searchQuery) || type.includes(searchQuery) || location.includes(searchQuery);
                const matchesStatus = currentStatus === 'all' || 
                                     (currentStatus === 'active' && status === 'active') || 
                                     (currentStatus === 'completed' && status === 'completed');

                if (matchesSearch && matchesStatus) {
                    if (card.style.display === 'none') {
                        card.style.display = 'block';
                        gsap.fromTo(card, { opacity: 0, scale: 0.9 }, { opacity: 1, scale: 1, duration: 0.4, ease: "back.out(1.2)" });
                    }
                } else {
                    if (card.style.display !== 'none') {
                        gsap.to(card, { 
                            opacity: 0, 
                            scale: 0.8, 
                            duration: 0.3, 
                            onComplete: () => card.style.display = 'none' 
                        });
                    }
                }
            });
        }

        function handleSort(e, type) {
            document.querySelectorAll('.sort-opt').forEach(opt => opt.classList.remove('active'));
            e.currentTarget.classList.add('active');
            sortProjects(type);
        }

        // Sorting logic with layout transition
        function sortProjects(type) {
            const grid = document.querySelector('.projects-grid');
            const cards = Array.from(grid.querySelectorAll('.project-card'));
            
            // Record state for Flip
            const state = Flip.getState(cards);

            cards.sort((a, b) => {
                if(type === 'progress') return b.dataset.progress - a.dataset.progress;
                if(type === 'date') return new Date(b.dataset.date) - new Date(a.dataset.date);
                if(type === 'name') return a.querySelector('.project-name').textContent.localeCompare(b.querySelector('.project-name').textContent);
            });

            cards.forEach(card => grid.appendChild(card));

            Flip.from(state, {
                duration: 0.8,
                stagger: 0.05,
                ease: "power2.inOut",
                onEnter: elements => gsap.fromTo(elements, {opacity: 0, scale: 0}, {opacity: 1, scale: 1, duration: 0.6}),
                onLeave: elements => gsap.to(elements, {opacity: 0, scale: 0, duration: 0.6})
            });
        }

        const initBackground3D = () => {
            const container = document.getElementById('canvas-container');
            if (!container) return;
            const scene = new THREE.Scene();
            scene.background = new THREE.Color('#f6f7f2');
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

            const cityGroup = new THREE.Group();
            scene.add(cityGroup);
            const buildingMaterial = new THREE.MeshPhongMaterial({ color: 0x294033, transparent: true, opacity: 0.1, side: THREE.DoubleSide });
            const edgeMaterial = new THREE.LineBasicMaterial({ color: 0x294033, transparent: true, opacity: 0.3 });
            const gridSize = 40; // Greatly increased city size
            const spacing = 3;
            for (let x = -gridSize; x < gridSize; x++) {
                for (let z = -gridSize; z < gridSize; z++) {
                    // Reduce density slightly for performance with larger grid
                    if (Math.random() > 0.4) continue;
                    
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

            // Add fog to blend edges seamlessly into background
            scene.fog = new THREE.Fog(0xf6f7f2, 15, 60);

            let mouseX = 0;
            let mouseY = 0;
            document.addEventListener('mousemove', (event) => {
                mouseX = (event.clientX - window.innerWidth / 2) * 0.001;
                mouseY = (event.clientY - window.innerHeight / 2) * 0.001;
            });

            let scrollY = 0;
            window.addEventListener('scroll', () => {
                const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
                if (maxScroll > 0) {
                    scrollY = window.scrollY / maxScroll;
                }
            });

            const animate = () => {
                requestAnimationFrame(animate);
                cityGroup.rotation.y += 0.0005; // Slower rotation for massive city
                floatGroup.rotation.y += 0.005;
                floatGroup.position.y = Math.sin(Date.now() * 0.001) * 0.5 + 0.5;
                
                cityGroup.rotation.x += 0.05 * (mouseY - cityGroup.rotation.x);
                cityGroup.rotation.y += 0.05 * (mouseX - cityGroup.rotation.y);
                
                // Subtle Camera Parallax - Keeps city in view always
                camera.position.y = 2 + scrollY * 5; 
                camera.position.z = 8 + scrollY * 4; 
                camera.lookAt(0, scrollY * 2, 0);
                
                renderer.render(scene, camera);
            };
            animate();

            window.addEventListener('resize', () => {
                camera.aspect = window.innerWidth / window.innerHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(window.innerWidth, window.innerHeight);
            });
        };

        // Execution Entry
        document.addEventListener('DOMContentLoaded', () => {
            gsap.registerPlugin(Flip);
            initBackground3D();
            initCounters();
            initTilt();
            initProgressRings();
            initMiniBuildings();
        });
    </script>
</body>
</html>
