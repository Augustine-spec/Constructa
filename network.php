<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'engineer' && $_SESSION['role'] !== 'admin')) { 
    header('Location: login.html'); 
    exit(); 
}
$is_admin = ($_SESSION['role'] === 'admin');
require_once 'backend/config.php';
$conn = getDatabaseConnection();
$user_id = $_SESSION['user_id'];

// Fetch logged-in user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$current_user = $stmt->get_result()->fetch_assoc();

// Fetch trending engineers based on activity (posts, likes, comments, followers)
$trending_query = "
    SELECT 
        u.id,
        u.name,
        u.specialization,
        COUNT(DISTINCT ep.id) as post_count,
        COUNT(DISTINCT pl.id) as likes_received,
        COUNT(DISTINCT pc.id) as comments_received,
        COUNT(DISTINCT ef.follower_id) as follower_count,
        (COUNT(DISTINCT ep.id) * 3 + 
         COUNT(DISTINCT pl.id) * 2 + 
         COUNT(DISTINCT pc.id) * 2 + 
         COUNT(DISTINCT ef.follower_id) * 5) as activity_score
    FROM users u
    LEFT JOIN engineer_posts ep ON u.id = ep.user_id
    LEFT JOIN post_likes pl ON ep.id = pl.post_id
    LEFT JOIN post_comments pc ON ep.id = pc.post_id
    LEFT JOIN engineer_followers ef ON u.id = ef.following_id
    WHERE (u.role = 'engineer' OR (SELECT COUNT(*) FROM engineer_posts WHERE user_id = u.id) > 0)
    GROUP BY u.id, u.name, u.specialization
    HAVING activity_score > 0
    ORDER BY activity_score DESC
    LIMIT 3
";

$trending_engineers = [];
$stmt = $conn->prepare($trending_query);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $trending_engineers[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'specialty' => $row['specialization'] ?? 'General Engineering',
        'score' => $row['activity_score'],
        'post_count' => $row['post_count'],
        'follower_count' => $row['follower_count']
    ];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Engineer Network | Constructa Community</title>
    
    <!-- Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Outfit:wght@200;300;400;500;600;700;800&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/TextPlugin.min.js"></script>
    
    <style>
        :root {
            --primary-accent: #294033;
            --secondary-accent: #3d5a49;
            --bg-light: #f6f7f2;
            --glass-panel: rgba(255, 255, 255, 0.92);
            --glass-border: rgba(41, 64, 51, 0.1);
            --card-radius: 20px;
            --transition-smooth: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            background-color: var(--bg-light);
            color: #1a202c;
            font-family: 'Outfit', sans-serif;
            margin: 0;
            overflow-x: hidden;
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

        .bg-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, rgba(246, 247, 242, 0) 0%, rgba(246, 247, 242, 0.3) 50%, rgba(246, 247, 242, 0) 100%),
                        radial-gradient(circle at 50% 50%, transparent 0%, rgba(246, 247, 242, 0.4) 100%);
            z-index: -1;
        }



        /* Navbar */
        nav {
            padding: 1.2rem 4rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
        }

        .nav-brand {
            font-weight: 800;
            font-size: 1.6rem;
            font-family: 'Space Grotesk', sans-serif;
            background: linear-gradient(90deg, var(--primary-accent), var(--secondary-accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
            display: flex;
            gap: 2px;
        }

        .nav-btn {
            background: white;
            border: 1px solid rgba(0, 0, 0, 0.08);
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            color: #1a202c;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition-smooth);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(41, 64, 51, 0.15);
            background: var(--primary-accent);
            color: white !important;
            border-color: var(--primary-accent);
        }

        .nav-btn i { font-size: 1rem; }

        .nav-links { display: flex; gap: 1rem; align-items: center; }

        /* Main Layout */
        .network-container {
            max-width: 1400px;
            margin: 100px auto 0;
            padding: 40px;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 40px;
        }

        /* Community Header */
        .community-hero {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 0;
            position: relative;
        }

        .hero-title {
            font-size: 4.5rem;
            font-weight: 800;
            font-family: 'Space Grotesk', sans-serif;
            margin-bottom: 15px;
            letter-spacing: -2px;
            color: #1a202c;
            display: flex;
            justify-content: center;
            gap: 6px;
        }

        .hero-title span, .nav-brand span {
            display: inline-block;
            opacity: 0;
            transform: translateY(20px);
        }

        .hero-subtitle {
            font-family: 'JetBrains Mono', monospace;
            color: var(--primary-accent);
            font-size: 1.1rem;
            height: 24px;
        }

        /* Search Bar */
        .search-engine-wrap {
            margin: 40px auto;
            max-width: 800px;
            position: relative;
        }

        .search-input-box {
            width: 100%;
            background: var(--glass-panel);
            border: 1px solid var(--glass-border);
            border-radius: 50px;
            padding: 18px 30px;
            padding-left: 60px;
            color: #1a202c;
            font-size: 1.1rem;
            transition: 0.4s;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        .search-input-box:focus {
            outline: none;
            border-color: var(--primary-accent);
            box-shadow: var(--neon-glow);
        }

        .search-icon {
            position: absolute;
            left: 25px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-accent);
            font-size: 1.2rem;
        }

        /* Premium Post Composer */
        .composer-card {
            background: var(--glass-panel);
            border: 1px solid var(--glass-border);
            border-radius: var(--card-radius);
            padding: 30px;
            margin-bottom: 40px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.05);
            transition: 0.3s;
            backdrop-filter: blur(25px);
        }

        .composer-card:hover { border-color: rgba(14, 165, 233, 0.2); }

        .composer-header {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .user-avatar-small {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: linear-gradient(45deg, var(--primary-accent), var(--secondary-accent));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #ffffff;
            font-size: 1.2rem;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .composer-input {
            flex: 1;
            background: rgba(0, 0, 0, 0.02);
            border: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: 12px;
            padding: 15px;
            color: #1a202c;
            min-height: 80px;
            resize: none;
            transition: 0.3s;
        }

        .composer-input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--primary-accent);
        }

        .composer-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        .attach-btns { display: flex; gap: 15px; }
        .attach-btn {
            color: rgba(0, 0, 0, 0.5);
            font-size: 0.9rem;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            border-radius: 50px;
            background: rgba(0, 0, 0, 0.02);
        }

        .attach-btn:hover {
            color: var(--primary-accent);
            background: rgba(14, 165, 233, 0.1);
        }

        .btn-post {
            background: linear-gradient(90deg, var(--primary-accent), var(--secondary-accent));
            color: #fff;
            border: none;
            padding: 10px 30px;
            border-radius: 50px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.85rem;
            transition: 0.4s;
            box-shadow: 0 5px 15px rgba(14, 165, 233, 0.3);
        }

        .btn-post:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(14, 165, 233, 0.5);
        }

        /* Feed Post Card */
        .post-card {
            background: var(--glass-panel);
            border: 1px solid var(--glass-border);
            border-radius: var(--card-radius);
            padding: 25px;
            margin-bottom: 30px;
            opacity: 0;
            transform: translateY(30px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.03);
            backdrop-filter: blur(25px);
        }

        .post-author {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .author-info { display: flex; gap: 12px; align-items: center; }
        .author-name { font-weight: 700; font-size: 1.1rem; color: #1a202c; }
        .author-meta { font-size: 0.75rem; color: rgba(0, 0, 0, 0.4); }
        .verified-badge { color: var(--primary-accent); font-size: 0.8rem; margin-left: 5px; }

        .post-content {
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 20px;
            color: #2d3748;
        }

        .post-media {
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 20px;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .post-media img {
            width: 100%;
            transition: 0.8s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        .post-media:hover img { transform: scale(1.03); }

        .interaction-bar {
            display: flex;
            gap: 25px;
            padding-top: 20px;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        .interact-btn {
            background: none;
            border: none;
            color: rgba(0, 0, 0, 0.4);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: 0.3s;
        }

        .interact-btn:hover { color: var(--primary-accent); }
        .interact-btn.liked { color: #f43f5e; }

        /* Discovery Sidebar */
        .sidebar {
            position: sticky;
            top: 120px;
            height: fit-content;
        }

        .sidebar-widget {
            background: var(--glass-panel);
            border: 1px solid var(--glass-border);
            border-radius: var(--card-radius);
            padding: 25px;
            margin-bottom: 30px;
        }

        .widget-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .trending-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
        }

        .trending-user { display: flex; gap: 10px; align-items: center; }
        .trend-avatar { width: 35px; height: 35px; border-radius: 8px; background: rgba(255,255,255,0.1); }
        .trend-name { font-size: 0.9rem; font-weight: 600; color: #1a202c; }
        .trend-spec { font-size: 0.7rem; color: rgba(0, 0, 0, 0.4); }

        .btn-follow {
            background: rgba(14, 165, 233, 0.1);
            color: var(--primary-accent);
            border: 1px solid rgba(14, 165, 233, 0.3);
            padding: 5px 15px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            transition: 0.3s;
        }

        .btn-follow:hover {
            background: var(--primary-accent);
            color: #fff;
        }

    </style>
</head>
<body>

<div id="canvas-container"></div>
<div class="bg-overlay"></div>

<nav>
    <a href="engineer.php" class="nav-brand" id="nav-logo">
        <span>C</span><span>O</span><span>N</span><span>S</span><span>T</span><span>R</span><span>U</span><span>C</span><span>T</span><span>A</span>
    </a>
    <div class="nav-links">
        <?php if($is_admin): ?>
            <a href="admin_dashboard.php" class="nav-btn">
                <i class="fas fa-arrow-left"></i> Admin Dashboard
            </a>
        <?php else: ?>
            <a href="engineer.php" class="nav-btn">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
            <a href="team_management.php" class="nav-btn">
                <i class="fas fa-user-circle"></i> Profile
            </a>
        <?php endif; ?>
        <a href="logout.php" class="nav-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</nav>

<main class="network-container">
    
    <!-- Professional Community Header (Span across) -->
    <section class="community-hero">

        <h1 class="hero-title" id="main-heading">
            <span>C</span><span>O</span><span>N</span><span>S</span><span>T</span><span>R</span><span>U</span><span>C</span><span>T</span><span>A</span>
        </h1>
        <div class="hero-subtitle" id="typing-text"></div>
        
        <div class="search-engine-wrap">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="search-input-box" placeholder="Search by Specialization, Project Type, or Expert Name...">
        </div>
    </section>

    <!-- Main Feed -->
    <section class="feed-column">
        
        <?php if($is_admin): ?>
        <div style="background: linear-gradient(135deg, #ef4444, #b91c1c); color: white; padding: 20px; border-radius: var(--card-radius); margin-bottom: 30px; display: flex; align-items: center; gap: 15px; box-shadow: 0 10px 25px rgba(239, 68, 68, 0.25);">
            <div style="background: rgba(255,255,255,0.2); width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                <i class="fas fa-user-shield"></i>
            </div>
            <div>
                <h3 style="margin: 0; font-size: 1.2rem; font-weight: 700;">Admin Moderation View</h3>
                <p style="margin: 5px 0 0; opacity: 0.9; font-size: 0.9rem;">You are viewing the network as an administrator. You can moderate content but cannot post.</p>
            </div>
        </div>
        <?php else: ?>
        <!-- Post Composer -->
        <div class="composer-card">
            <div class="composer-header">
                <div class="user-avatar-small"><?php echo substr($current_user['name'] ?? 'U', 0, 1); ?></div>
                <textarea class="composer-input" placeholder="Share a project update or technical insight..."></textarea>
            </div>
            <div class="composer-actions">
                <div class="attach-btns">
                    <div class="attach-btn" id="btn-media"><i class="fas fa-image"></i> Media</div>
                    <div class="attach-btn" id="btn-blueprint"><i class="fas fa-file-pdf"></i> Blueprint</div>
                    <div class="attach-btn" id="btn-category"><i class="fas fa-tags"></i> Category</div>
                </div>
                
                <!-- Hidden Inputs -->
                <input type="file" id="media-upload" accept="image/*" style="display: none;">
                <input type="file" id="blueprint-upload" accept=".pdf" style="display: none;">
                <select id="category-select" style="display: none;">
                    <option value="">Select Category</option>
                    <option value="Structural">Structural</option>
                    <option value="Architectural">Architectural</option>
                    <option value="MEP">MEP</option>
                    <option value="Sustainability">Sustainability</option>
                    <option value="BIM">BIM</option>
                </select>

                <!-- Selection Status -->
                <div id="selection-status" style="display: none; padding: 10px; background: rgba(0,0,0,0.02); border-radius: 8px; margin-top: 10px; font-size: 0.85rem; color: var(--primary-accent);">
                    <div id="media-status" style="display: none;"><i class="fas fa-check"></i> Image selected</div>
                    <div id="blueprint-status" style="display: none;"><i class="fas fa-check"></i> Blueprint selected</div>
                    <div id="category-status" style="display: none;"></div>
                </div>
                <button class="btn-post">PUBLISH POST</button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Feed List -->
        <div id="feed-container">
            <!-- Posts will be loaded dynamically -->
        </div>
    </section>

    <!-- Sidebar Discovery -->
    <aside class="sidebar">
        <div class="sidebar-widget">
            <h3 class="widget-title"><i class="fas fa-fire" style="color: #f59e0b;"></i> Trending Experts</h3>
            <?php if (count($trending_engineers) > 0): ?>
                <?php foreach($trending_engineers as $eng): ?>
                <div class="trending-item">
                    <div class="trending-user">
                        <div class="trend-avatar" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; cursor: pointer;" onclick="window.location.href='team_management.php?engineer_id=<?php echo $eng['id']; ?>'">
                            <?php echo strtoupper(substr($eng['name'], 0, 1)); ?>
                        </div>
                        <div>
                            <div class="trend-name">
                                <a href="team_management.php?engineer_id=<?php echo $eng['id']; ?>" style="color: inherit; text-decoration: none; transition: 0.2s;" onmouseover="this.style.color='var(--primary-accent)'" onmouseout="this.style.color='inherit'">
                                    <?php echo htmlspecialchars($eng['name']); ?>
                                </a>
                            </div>
                            <div class="trend-spec"><?php echo htmlspecialchars($eng['specialty']); ?></div>
                            <div class="trend-spec" style="color: #f59e0b; font-weight: 600;">
                                <i class="fas fa-chart-line"></i> <?php echo $eng['score']; ?> pts
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 20px; color: rgba(0, 0, 0, 0.4);">
                    <i class="fas fa-users" style="font-size: 2rem; margin-bottom: 10px; opacity: 0.3;"></i>
                    <p style="font-size: 0.9rem; margin: 0;">No trending engineers yet.<br>Be the first to share!</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="sidebar-widget">
            <h3 class="widget-title"><i class="fas fa-network-wired" style="color: var(--primary-accent);"></i> Trust Score Elite</h3>
            <div class="p-4 rounded-4 bg-white bg-opacity-5 border border-white border-opacity-10 text-center">
                <div class="fs-1 fw-bold text-white mb-2">94.2</div>
                <div class="small opacity-50 text-uppercase letter-spacing-2">Network Credibility Avg.</div>
                <div class="mt-3 progress" style="height: 4px; background: rgba(255,255,255,0.05);">
                    <div class="progress-bar bg-info" style="width: 94%;"></div>
                </div>
            </div>
        </div>
    </aside>

</main>

<script>
    const IS_ADMIN = <?php echo json_encode($is_admin); ?>;
    // 3D Background System
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

        let mouseX = 0;
        let mouseY = 0;
        document.addEventListener('mousemove', (event) => {
            mouseX = (event.clientX - window.innerWidth / 2) * 0.001;
            mouseY = (event.clientY - window.innerHeight / 2) * 0.001;
        });

        let scrollY = 0;
        window.addEventListener('scroll', () => {
            scrollY = window.pageYOffset / (document.documentElement.scrollHeight - window.innerHeight);
        });

        const animate = () => {
            requestAnimationFrame(animate);
            cityGroup.rotation.y += 0.001;
            cityGroup.rotation.x += 0.05 * (mouseY - cityGroup.rotation.x);
            cityGroup.rotation.y += 0.05 * (mouseX - cityGroup.rotation.y);
            camera.position.y = 2 - scrollY * 2;
            camera.position.z = 8 + scrollY * 5;
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

    gsap.registerPlugin(TextPlugin, ScrollTrigger);

    // Sequential Letter Animation
    const animateLetters = (selector) => {
        gsap.to(`${selector} span`, {
            opacity: 1,
            y: 0,
            stagger: 0.08,
            duration: 0.6,
            ease: "back.out(1.7)",
            scrollTrigger: {
                trigger: selector,
                start: "top 90%"
            }
        });
    };

    animateLetters("#nav-logo");
    animateLetters("#main-heading");

    // Typing Effect Animation
    const keywords = ["Connect", "Share", "Collaborate", "Innovate"];
    let mainTimeline = gsap.timeline({repeat: -1});

    keywords.forEach(word => {
        let textTimeline = gsap.timeline({repeat: 1, yoyo: true, repeatDelay: 1});
        textTimeline.to("#typing-text", {duration: 1, text: word});
        mainTimeline.add(textTimeline);
    });

    // Reveal Posts on Scroll
    gsap.utils.toArray(".post-card").forEach((post, i) => {
        gsap.to(post, {
            scrollTrigger: {
                trigger: post,
                start: "top 90%",
                toggleActions: "play none none none"
            },
            opacity: 1,
            y: 0,
            duration: 0.8,
            ease: "back.out(1.7)",
            delay: i * 0.1
        });
    });

    // --- Dynamic Feed System ---

    const postBtn = document.querySelector('.btn-post');
    const composerInput = document.querySelector('.composer-input');
    
    // Attachments Logic
    const mediaBtn = document.getElementById('btn-media');
    const blueprintBtn = document.getElementById('btn-blueprint');
    const categoryBtn = document.getElementById('btn-category');
    
    const mediaInput = document.getElementById('media-upload');
    const blueprintInput = document.getElementById('blueprint-upload');
    const categorySelect = document.getElementById('category-select');
    
    const selectionStatus = document.getElementById('selection-status');
    const mediaStatus = document.getElementById('media-status');
    const blueprintStatus = document.getElementById('blueprint-status');
    const categoryStatus = document.getElementById('category-status');

    let selectedCategory = '';

    // Trigger Inputs
    if(mediaBtn) mediaBtn.addEventListener('click', () => mediaInput.click());
    if(blueprintBtn) blueprintBtn.addEventListener('click', () => blueprintInput.click());
    
    // Category Popover/Dropdown simulation
    if(categoryBtn) {
        categoryBtn.addEventListener('click', (e) => {
            // Simple prompt for now, or could be replaced with a custom modal
            // For better UI, let's just trigger the native select focus if possible or toggle a visible dropdown
            // Since styling a select is tricky, let's create a temporary simple dropdown
            
            const existingDropdown = document.querySelector('.category-dropdown-custom');
            if(existingDropdown) {
                existingDropdown.remove();
                return;
            }

            const dropdown = document.createElement('div');
            dropdown.className = 'category-dropdown-custom';
            dropdown.style.cssText = `
                position: absolute;
                background: white;
                border: 1px solid rgba(0,0,0,0.1);
                border-radius: 12px;
                padding: 10px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                z-index: 100;
                margin-top: 10px;
                min-width: 150px;
            `;
            
            const categories = ['Structural', 'Architectural', 'MEP', 'Sustainability', 'BIM', 'Project Management'];
            categories.forEach(cat => {
                const item = document.createElement('div');
                item.innerText = cat;
                item.style.cssText = `
                    padding: 8px 12px;
                    cursor: pointer;
                    border-radius: 6px;
                    font-size: 0.9rem;
                    transition: 0.2s;
                `;
                item.onmouseover = () => item.style.background = '#f7f7f7';
                item.onmouseout = () => item.style.background = 'transparent';
                item.onclick = () => {
                    selectedCategory = cat;
                    updateStatus();
                    dropdown.remove();
                };
                dropdown.appendChild(item);
            });

            categoryBtn.parentElement.appendChild(dropdown);
        });
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('#btn-category') && !e.target.closest('.category-dropdown-custom')) {
            const dropdown = document.querySelector('.category-dropdown-custom');
            if (dropdown) dropdown.remove();
        }
    });

    // Handle File Selections
    if(mediaInput) mediaInput.addEventListener('change', updateStatus);
    if(blueprintInput) blueprintInput.addEventListener('change', updateStatus);

    function updateStatus() {
        let hasContent = false;
        
        if (mediaInput && mediaInput.files.length > 0) {
            if(mediaStatus) {
                mediaStatus.style.display = 'block';
                mediaStatus.innerHTML = `<i class="fas fa-image"></i> ${mediaInput.files[0].name}`;
            }
            if(mediaBtn) mediaBtn.style.color = 'var(--primary-accent)';
            hasContent = true;
        } else {
            if(mediaStatus) mediaStatus.style.display = 'none';
            if(mediaBtn) mediaBtn.style.color = '';
        }

        if (blueprintInput && blueprintInput.files.length > 0) {
            if(blueprintStatus) {
                blueprintStatus.style.display = 'block';
                blueprintStatus.innerHTML = `<i class="fas fa-file-pdf"></i> ${blueprintInput.files[0].name}`;
            }
            if(blueprintBtn) blueprintBtn.style.color = 'var(--primary-accent)';
            hasContent = true;
        } else {
            if(blueprintStatus) blueprintStatus.style.display = 'none';
            if(blueprintBtn) blueprintBtn.style.color = '';
        }

        if (selectedCategory) {
            if(categoryStatus) {
                categoryStatus.style.display = 'block';
                categoryStatus.innerHTML = `<i class="fas fa-tag"></i> ${selectedCategory}`;
            }
            if(categoryBtn) categoryBtn.style.color = 'var(--primary-accent)';
            hasContent = true;
        } else {
            if(categoryStatus) categoryStatus.style.display = 'none';
            if(categoryBtn) categoryBtn.style.color = '';
        }

        if(selectionStatus) selectionStatus.style.display = hasContent ? 'block' : 'none';
    }

    async function fetchPosts() {
        const feedContainer = document.getElementById('feed-container');
        try {
            const res = await fetch('backend/get_network_posts.php');
            const data = await res.json();
            
            if(data.success && data.posts.length > 0) {
                feedContainer.innerHTML = '';
                data.posts.forEach(post => {
                    const isLiked = post.is_liked ? 'liked' : '';
                    const heartIcon = post.is_liked ? 'fas' : 'far';
                    
                    let commentsHtml = '';
                    if(post.comments && post.comments.length > 0) {
                        commentsHtml = '<div class="comments-section" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(0,0,0,0.05);">';
                        post.comments.forEach(comment => {
                            commentsHtml += `
                                <div class="comment-item" style="margin-bottom: 8px; font-size: 0.9rem;">
                                    <strong>${comment.commenter_name}:</strong> ${comment.comment}
                                </div>
                            `;
                        });
                        commentsHtml += '</div>';
                    }

                    const deleteBtn = IS_ADMIN ? `
                        <button onclick="deletePost(${post.id})" style="position: absolute; top: 20px; right: 20px; background: none; border: none; color: #ef4444; cursor: pointer; font-size: 1.1rem; opacity: 0.7; transition: 0.2s; z-index: 5;" title="Delete Post">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    ` : '';

                    const postHtml = `
                        <div class="post-card" id="post-${post.id}" style="opacity: 1; transform: translateY(0); position: relative;">
                            ${deleteBtn}
                            <div class="post-author">
                                <div class="author-info">
                                    <div class="user-avatar-small" style="background: ${getRandomColor()}; color: white; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; cursor: pointer;" onclick="window.location.href='team_management.php?engineer_id=${post.user_id}'">
                                        ${post.avatar_letter}
                                    </div>
                                    <div>
                                        <div class="author-name">
                                            <a href="team_management.php?engineer_id=${post.user_id}" style="color: inherit; text-decoration: none; transition: 0.2s;" onmouseover="this.style.color='var(--primary-accent)'" onmouseout="this.style.color='inherit'">
                                                ${post.name}
                                            </a>
                                            ${post.user_id % 3 === 0 ? '<i class="fas fa-check-circle verified-badge"></i>' : ''}
                                        </div>
                                        <div class="author-meta">${post.category || 'Engineer'} • ${post.created_at}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="post-content">${post.content}</div>
                            ${post.media_url ? renderMedia(post.media_url, post.media_type) : ''}
                            
                            <div class="interaction-bar">
                                <button class="interact-btn ${isLiked}" onclick="toggleLike(${post.id})">
                                    <i class="${heartIcon} fa-heart"></i> <span id="like-count-${post.id}">${post.like_count}</span>
                                </button>
                                <button class="interact-btn" onclick="toggleComments(${post.id})">
                                    <i class="far fa-comment-alt"></i> <span id="comment-count-${post.id}">${post.comment_count}</span>
                                </button>
                            </div>

                            <!-- Comments Section (Hidden by default or shown if comments exist) -->
                            <div id="comments-container-${post.id}" style="display: none;">
                                ${commentsHtml}
                                <div class="comment-input-area" style="margin-top: 15px; display: flex; gap: 10px; position: relative;">
                                    <button class="emoji-btn" onclick="toggleEmojiPicker(${post.id})" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #555;">
                                        <i class="far fa-smile"></i>
                                    </button>
                                    <input type="text" id="comment-input-${post.id}" placeholder="Write a comment..." 
                                           style="flex: 1; padding: 8px 12px; border: 1px solid rgba(0,0,0,0.1); border-radius: 20px; outline: none;">
                                    <button onclick="submitComment(${post.id})" 
                                            style="background: var(--primary-accent); color: white; border: none; padding: 8px 15px; border-radius: 20px; cursor: pointer;">
                                        Post
                                    </button>
                                    
                                    <!-- Emoji Picker Container -->
                                    <div id="emoji-picker-${post.id}" class="emoji-picker" style="display: none; position: absolute; bottom: 50px; left: 0; background: white; border: 1px solid #ccc; border-radius: 8px; padding: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 250px; flex-wrap: wrap; gap: 5px; z-index: 10;">
                                        ${getCommonEmojis().map(emoji => `<span onclick="insertEmoji(${post.id}, '${emoji}')" style="cursor: pointer; font-size: 1.2rem; padding: 2px;">${emoji}</span>`).join('')}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    feedContainer.insertAdjacentHTML('beforeend', postHtml);
                });
            }
        } catch (e) {
            console.error("Feed error:", e);
        }
    }

    function getCommonEmojis() {
        return ['👍', '❤️', '😂', '😮', '😢', '😡', '👏', '🔥', '🎉', '💯', '✨', '🏗️', '🏠', '👷', '🔨', '📐', '🔧', '🤝', '✅', '👀'];
    }

    function toggleEmojiPicker(postId) {
        const picker = document.getElementById(`emoji-picker-${postId}`);
        // Close all other pickers
        document.querySelectorAll('.emoji-picker').forEach(p => {
            if (p.id !== `emoji-picker-${postId}`) p.style.display = 'none';
        });
        
        picker.style.display = picker.style.display === 'none' ? 'flex' : 'none';
    }

    function insertEmoji(postId, emoji) {
        const input = document.getElementById(`comment-input-${postId}`);
        input.value += emoji;
        input.focus();
    }

    // Close emoji picker when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.emoji-btn') && !event.target.closest('.emoji-picker')) {
            document.querySelectorAll('.emoji-picker').forEach(p => p.style.display = 'none');
        }
    });

    async function toggleLike(postId) {
        try {
            const res = await fetch('backend/toggle_like.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `post_id=${postId}`
            });
            const data = await res.json();
            if(data.success) {
                const btn = document.querySelector(`#post-${postId} .interact-btn:first-child`);
                const icon = btn.querySelector('i');
                const countSpan = document.getElementById(`like-count-${postId}`);
                
                if(data.action === 'liked') {
                    btn.classList.add('liked');
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    countSpan.innerText = parseInt(countSpan.innerText) + 1;
                } else {
                    btn.classList.remove('liked');
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                    countSpan.innerText = Math.max(0, parseInt(countSpan.innerText) - 1);
                }
            }
        } catch(e) { console.error(e); }
    }

    function toggleComments(postId) {
        const container = document.getElementById(`comments-container-${postId}`);
        if(container.style.display === 'none') {
            container.style.display = 'block';
            // Focus input
            setTimeout(() => document.getElementById(`comment-input-${postId}`).focus(), 100);
        } else {
            container.style.display = 'none';
        }
    }

    async function submitComment(postId) {
        const input = document.getElementById(`comment-input-${postId}`);
        const text = input.value.trim();
        if(!text) return;

        try {
            const formData = new FormData();
            formData.append('post_id', postId);
            formData.append('comment', text);

            const res = await fetch('backend/add_comment.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            
            if(data.success) {
                // Add comment to DOM
                const container = document.getElementById(`comments-container-${postId}`);
                const commentHtml = `
                    <div class="comment-item" style="margin-bottom: 8px; font-size: 0.9rem;">
                        <strong>You:</strong> ${text}
                    </div>
                `;
                // Insert before input area
                const inputArea = container.querySelector('.comment-input-area');
                if(container.querySelector('.comments-section')) {
                     container.querySelector('.comments-section').insertAdjacentHTML('beforeend', commentHtml);
                } else {
                    // Create section if not exists
                    const newSection = document.createElement('div');
                    newSection.className = 'comments-section';
                    newSection.style.cssText = "margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(0,0,0,0.05);";
                    newSection.innerHTML = commentHtml;
                    container.insertBefore(newSection, inputArea);
                }
                
                input.value = '';
                
                // Update count
                const countSpan = document.getElementById(`comment-count-${postId}`);
                countSpan.innerText = parseInt(countSpan.innerText) + 1;
            }
        } catch(e) { console.error(e); }
    }

    function renderMedia(url, type) {
        if(type === 'image') {
            return `<div class="post-media"><img src="${url}"></div>`;
        } else if (type === 'document' || type === 'blueprint') {
             return `<div class="post-media" style="background: #f0fdf4; padding: 20px; display: flex; align-items: center; gap: 15px; border: 1px solid #bbf7d0;">
                        <i class="fas fa-file-pdf" style="font-size: 2rem; color: #16a34a;"></i>
                        <div>
                            <div style="font-weight: 600; color: #15803d;">Attached Blueprint</div>
                            <a href="${url}" target="_blank" style="color: #16a34a; text-decoration: none; font-size: 0.9rem;">View Document</a>
                        </div>
                    </div>`;
        }
        return '';
    }

    function getRandomColor() {
        const colors = ['#0ea5e9', '#6366f1', '#10b981', '#f59e0b', '#8b5cf6'];
        return colors[Math.floor(Math.random() * colors.length)];
    }

    if(postBtn) {
        postBtn.addEventListener('click', async () => {
            const content = composerInput.value.trim();
            // Allow post if there is content OR media
            if(!content && mediaInput.files.length === 0 && blueprintInput.files.length === 0) return;

            postBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> PUBLISHING...';
            postBtn.disabled = true;

            const formData = new FormData();
            formData.append('content', content);
            if (selectedCategory) formData.append('category', selectedCategory);
            if (mediaInput.files.length > 0) formData.append('media', mediaInput.files[0]);
            if (blueprintInput.files.length > 0) formData.append('blueprint', blueprintInput.files[0]);

            try {
                const res = await fetch('backend/process_network_post.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if(data.success) {
                    // Reset Form
                    composerInput.value = '';
                    mediaInput.value = '';
                    blueprintInput.value = '';
                    selectedCategory = '';
                    updateStatus();
                    
                    await fetchPosts();
                    gsap.from(".post-card:first-child", {
                        scale: 0.9,
                        opacity: 0,
                        duration: 0.5,
                        ease: "back.out(1.7)"
                    });
                } else {
                    alert('Failed to post: ' + (data.message || 'Unknown error'));
                }
            } catch (e) {
                console.error(e);
                alert('Error posting. See console.');
            }

            postBtn.innerHTML = 'PUBLISH POST';
            postBtn.disabled = false;
        });
    }

    async function deletePost(postId) {
        if(!confirm('Are you sure you want to delete this post as Admin? This action cannot be undone.')) return;

        try {
            const formData = new FormData();
            formData.append('post_id', postId);

            const res = await fetch('backend/delete_network_post.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            
            if(data.success) {
                const card = document.getElementById(`post-${postId}`);
                gsap.to(card, {
                    opacity: 0, 
                    y: -20, 
                    duration: 0.3, 
                    onComplete: () => {
                        card.remove();
                    }
                });
            } else {
                alert('Failed to delete: ' + (data.message || 'Unknown error'));
            }
        } catch(e) {
            console.error(e);
            alert('Error deleting post.');
        }
    }

    // Initial load
    fetchPosts();

    // Search Interaction
    const searchInput = document.querySelector('.search-input-box');
    searchInput.addEventListener('input', (e) => {
        const val = e.target.value.toLowerCase();
        document.querySelectorAll('.post-card').forEach(card => {
            const content = card.innerText.toLowerCase();
            card.style.display = content.includes(val) ? 'block' : 'none';
        });
    });


</script>

</body>
</html>
