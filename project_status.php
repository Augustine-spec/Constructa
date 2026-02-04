<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'homeowner') {
    header('Location: login.html');
    exit();
}

require_once 'backend/config.php';
$conn = getDatabaseConnection();
$homeowner_id = $_SESSION['user_id'];

// Fetch all project requests for this homeowner
$sql = "SELECT pr.*, u.name as engineer_name 
        FROM project_requests pr 
        LEFT JOIN users u ON pr.engineer_id = u.id 
        WHERE pr.homeowner_id = ? 
        ORDER BY pr.updated_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $homeowner_id);
$stmt->execute();
$result = $stmt->get_result();
$projects = [];
while ($row = $result->fetch_assoc()) {
    $projects[] = $row;
}

// Stats
$total_projects = count($projects);
$active_projects = 0;
foreach ($projects as $p) {
    if ($p['status'] !== 'completed' && $p['status'] !== 'rejected') {
        $active_projects++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Projects - Constructa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <style>
        :root {
            --primary: #294033;
            --primary-light: #3d5a49;
            --accent: #d4af37;
            --bg-color: #f6f7f2;
            --card-bg: rgba(255, 255, 255, 0.9);
            --text-main: #1e293b;
            --text-muted: #64748b;
            --glass-border: rgba(255, 255, 255, 0.6);
            --shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15), 0 0 1px rgba(0, 0, 0, 0.05);
            --nav-height: 70px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }

        body {
            background-color: #f6f7f2;
            color: var(--text-main);
            min-height: 100vh;
            overflow-x: hidden;
        }

        #canvas-container {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            z-index: -1; background: #f6f7f2; pointer-events: none;
        }

        /* Navbar */
        nav {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            padding: 0 3rem;
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            position: sticky; top: 0; z-index: 100;
            height: var(--nav-height);
        }
        .nav-logo { font-weight: 800; font-size: 1.5rem; color: var(--primary); text-decoration: none; display: flex; align-items: center; gap: 0.5rem; }
        .nav-links { display: flex; gap: 1rem; }
        .nav-btn:hover { background: var(--primary); color: white; transform: translateY(-2px); }

        /* Premium Top Nav Style */
        .top-nav-btn {
            padding: 0.8rem 1.5rem;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 4px;
            text-decoration: none;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8rem;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .top-nav-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        }

        /* Main Content */
        .page-header {
            max-width: 1200px;
            margin: 3rem auto 2rem;
            padding: 0 2rem;
            text-align: center;
        }
        .page-title {
            font-size: 2.5rem; color: var(--primary); margin-bottom: 0.5rem; font-weight: 800;
        }
        .page-subtitle {
            color: var(--text-muted); font-size: 1.1rem;
        }
        
        .btn-new-project {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--primary);
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            margin-top: 1.5rem;
            box-shadow: 0 4px 15px rgba(41, 64, 51, 0.3);
            transition: all 0.3s ease;
        }
        .btn-new-project:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(41, 64, 51, 0.4);
        }

        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto 4rem;
            padding: 0 2rem;
        }

        .project-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2rem;
            cursor: pointer;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .project-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border-color: var(--primary);
        }

        .card-header {
            display: flex; justify-content: space-between; align-items: flex-start;
        }
        .project-icon {
            width: 50px; height: 50px;
            background: rgba(26, 46, 35, 0.05);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; color: var(--primary);
        }
        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-active { background: #dcfce7; color: #166534; }
        .status-pending { background: #e0f2fe; color: #075985; }
        .status-completed { background: #f3f4f6; color: #374151; }
        .status-rejected { background: #fee2e2; color: #991b1b; }

        .card-title-group h3 {
            font-size: 1.4rem; color: var(--text-main); font-weight: 700; margin-bottom: 0.2rem;
        }
        .card-title-group p {
            font-size: 0.9rem; color: var(--text-muted);
        }

        .info-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 0.8rem 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            font-size: 0.95rem;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: var(--text-muted); display: flex; align-items: center; gap: 0.5rem; }
        .info-value { font-weight: 600; color: var(--text-main); }

        .progress-section {
            margin-top: auto;
        }
        .progress-info {
            display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.85rem; font-weight: 600; color: var(--primary);
        }
        .progress-bar-bg {
            width: 100%; height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden;
        }
        .progress-bar-fill {
            height: 100%; background: var(--primary); transition: width 1s ease;
        }

        .view-msg {
            margin-top: 1rem;
            text-align: center;
            font-size: 0.9rem;
            color: var(--primary);
            font-weight: 600;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s;
        }
        .project-card:hover .view-msg {
            opacity: 1; transform: translateY(0);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            grid-column: 1 / -1;
            background: rgba(255,255,255,0.8);
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }
        .empty-icon {
            font-size: 3rem; color: var(--text-muted); margin-bottom: 1.5rem; opacity: 0.5;
        }

    </style>
</head>
<body>
    <div id="canvas-container"></div>

    <nav>
        <a href="homeowner.php" class="nav-logo"><i class="fas fa-home"></i> Constructa</a>
        <div class="nav-links">
            <a href="homeowner.php" class="top-nav-btn">Dashboard</a>
            <a href="login.html" class="top-nav-btn">Logout</a>
        </div>
    </nav>

    <main>
        <div class="page-header">
            <h1 class="page-title">My Projects</h1>
            <p class="page-subtitle">Track the progress and details of your construction projects.</p>
            <a href="engineer_directory.php" class="btn-new-project">
                <i class="fas fa-plus-circle"></i> Request New Project
            </a>
        </div>

        <div class="projects-grid">
            <?php if (count($projects) > 0): ?>
                <?php foreach ($projects as $proj): 
                    $status_class = 'status-pending';
                    if ($proj['status'] === 'accepted') $status_class = 'status-active';
                    if ($proj['status'] === 'completed') $status_class = 'status-completed';
                    if ($proj['status'] === 'rejected') $status_class = 'status-rejected';
                    
                    // Logic: Calculate progress based on current_stage
                    // Stages: 1-7, where 7 is completion
                    $stage = isset($proj['current_stage']) ? (int)$proj['current_stage'] : 1;
                    
                    // If current_stage is 7 or higher, it's 100% complete
                    if ($stage >= 7) {
                        $progress = 100;
                        $stage_label = "Completed";
                        $status_class = 'status-completed'; // Force completed status
                    } elseif ($proj['status'] === 'pending') {
                        $progress = 0;
                        $stage_label = "Request Sent";
                        $stage = 0;
                    } elseif ($proj['status'] === 'rejected') {
                        $progress = 0;
                        $stage_label = "Rejected";
                        $stage = 0;
                    } else {
                        // Active project: calculate progress (stages 1-6 map to 0-85%, stage 7 is 100%)
                        $progress = min(100, round(($stage / 7) * 100));
                        $stage_label = "Stage " . $stage . " of 7";
                    }
                ?>
                <div class="project-card" onclick="window.location.href='project_tracking.php?project_id=<?php echo $proj['id']; ?>'">
                    <div class="card-header">
                        <div class="project-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <span class="status-badge <?php echo $status_class; ?>"><?php echo ucfirst($proj['status']); ?></span>
                    </div>
                    
                    <div class="card-title-group">
                        <h3><?php echo htmlspecialchars($proj['project_title'] ?: 'Untitled Project'); ?></h3>
                        <p><?php echo htmlspecialchars($proj['project_type']); ?> • <?php echo htmlspecialchars($proj['location']); ?></p>
                    </div>

                    <div class="info-group">
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-user-hard-hat"></i> Engineer</span>
                            <span class="info-value"><?php echo htmlspecialchars($proj['engineer_name'] ?: 'Pending Assignment'); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-ruler-combined"></i> Size</span>
                            <span class="info-value"><?php echo htmlspecialchars($proj['project_size'] ?: 'N/A'); ?> sq.ft</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-calendar-alt"></i> Updated</span>
                            <span class="info-value"><?php echo date('M d, Y', strtotime($proj['updated_at'])); ?></span>
                        </div>
                    </div>

                    <div class="progress-section">
                        <div class="progress-info">
                            <span><?php echo $stage_label; ?></span>
                            <span><?php echo $progress; ?>%</span>
                        </div>
                        <div class="progress-bar-bg">
                            <div class="progress-bar-fill" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                    </div>

                    <div class="view-msg">Click to view full details <i class="fas fa-arrow-right"></i></div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-folder-open"></i></div>
                    <h2>No Projects Found</h2>
                    <p style="margin-top: 0.5rem; color: var(--text-muted);">You haven't started any projects yet.</p>
                    <a href="homeowner.php" class="nav-btn" style="margin-top: 1.5rem; display: inline-block;">Start Your Journey</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // 3D Background Logic (Premium Sync)
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.getElementById('canvas-container');
            if (container && typeof THREE !== 'undefined') {
                const scene = new THREE.Scene();
                scene.background = new THREE.Color('#f6f7f2');
                scene.fog = new THREE.Fog('#f6f7f2', 10, 45);

                const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
                camera.position.z = 10;
                camera.position.y = 2;

                const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
                renderer.setSize(window.innerWidth, window.innerHeight);
                renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
                container.appendChild(renderer.domElement);

                const ambientLight = new THREE.AmbientLight(0xffffff, 0.7);
                scene.add(ambientLight);

                const mainLight = new THREE.DirectionalLight(0xffffff, 0.8);
                mainLight.position.set(10, 20, 10);
                scene.add(mainLight);

                const floorGroup = new THREE.Group();
                scene.add(floorGroup);
                
                const buildMat = new THREE.MeshPhongMaterial({ color: 0x1a2e23, transparent: true, opacity: 0.05, side: THREE.DoubleSide });
                const edgeMat = new THREE.LineBasicMaterial({ color: 0x1a2e23, transparent: true, opacity: 0.15 });

                const gridSize = 8;
                const spacing = 4;
                for (let x = -gridSize; x <= gridSize; x++) {
                    for (let z = -gridSize; z <= gridSize; z++) {
                        if (Math.abs(x) < 2 && Math.abs(z) < 2) continue;
                        const h = Math.random() * 4 + 1;
                        const geo = new THREE.BoxGeometry(1.5, h, 1.5);
                        const mesh = new THREE.Mesh(geo, buildMat);
                        mesh.position.y = h / 2;
                        const edges = new THREE.EdgesGeometry(geo);
                        const line = new THREE.LineSegments(edges, edgeMat);
                        line.position.y = h / 2;
                        const building = new THREE.Group();
                        building.add(mesh);
                        building.add(line);
                        building.position.set(x * spacing, -5, z * spacing);
                        floorGroup.add(building);
                    }
                }

                const heroGroup = new THREE.Group();
                const floorGeo = new THREE.BoxGeometry(4, 0.2, 4);
                const floorLine = new THREE.LineSegments(new THREE.EdgesGeometry(floorGeo), new THREE.LineBasicMaterial({color: 0x1a2e23, opacity: 0.8}));
                heroGroup.add(floorLine);
                const wallGeo = new THREE.BoxGeometry(3.5, 2.5, 3.5);
                const wallLines = new THREE.LineSegments(new THREE.EdgesGeometry(wallGeo), new THREE.LineBasicMaterial({color: 0x1a2e23}));
                wallLines.position.y = 1.35;
                heroGroup.add(wallLines);
                const roofGeo = new THREE.ConeGeometry(3, 2, 4);
                const roofLines = new THREE.LineSegments(new THREE.EdgesGeometry(roofGeo), new THREE.LineBasicMaterial({color: 0xd4af37}));
                roofLines.position.y = 3.6;
                roofLines.rotation.y = Math.PI / 4;
                heroGroup.add(roofLines);
                heroGroup.position.set(0, 0, 0);
                scene.add(heroGroup);

                let mouseX = 0, mouseY = 0;
                document.addEventListener('mousemove', (e) => {
                    mouseX = (e.clientX - window.innerWidth / 2) * 0.0005;
                    mouseY = (e.clientY - window.innerHeight / 2) * 0.0005;
                });

                const animate = () => {
                    requestAnimationFrame(animate);
                    const time = Date.now() * 0.001;
                    heroGroup.rotation.y += 0.005;
                    heroGroup.position.y = Math.sin(time) * 0.5;
                    floorGroup.rotation.y += 0.002;
                    floorGroup.rotation.x += 0.05 * (mouseY - floorGroup.rotation.x);
                    floorGroup.rotation.y += 0.05 * (mouseX - floorGroup.rotation.y);
                    renderer.render(scene, camera);
                };
                animate();

                window.addEventListener('resize', () => {
                    camera.aspect = window.innerWidth / window.innerHeight;
                    camera.updateProjectionMatrix();
                    renderer.setSize(window.innerWidth, window.innerHeight);
                });
            }
        });
    </script>
</body>
</html>
