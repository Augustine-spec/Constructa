<?php
session_start();
if (!isset($_SESSION['user_id'])) { 
    header('Location: login.html'); 
    exit(); 
}

require_once 'backend/config.php';
$conn = getDatabaseConnection();

// 1. Determine Target User (Self or Other)
$viewer_id = $_SESSION['user_id'];
$target_id = isset($_GET['engineer_id']) ? intval($_GET['engineer_id']) : $viewer_id;

// 2. Fetch User Profile
$stmt = $conn->prepare("SELECT id, name, email, specialization, bio, experience, created_at, role, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $target_id);
$stmt->execute();
$user_res = $stmt->get_result();

if ($user_res->num_rows === 0) {
    echo "User not found.";
    exit();
}

$user = $user_res->fetch_assoc();

// 3. Fetch Real Stats
$stats_query = "
    SELECT 
        COUNT(DISTINCT ep.id) as post_count,
        COUNT(DISTINCT pl.id) as likes_received,
        COUNT(DISTINCT pc.id) as comments_received,
        COUNT(DISTINCT ef.follower_id) as follower_count
    FROM users u
    LEFT JOIN engineer_posts ep ON u.id = ep.user_id
    LEFT JOIN post_likes pl ON ep.id = pl.post_id
    LEFT JOIN post_comments pc ON ep.id = pc.post_id
    LEFT JOIN engineer_followers ef ON u.id = ef.following_id
    WHERE u.id = ?
    GROUP BY u.id
";
$stat_stmt = $conn->prepare($stats_query);
$stat_stmt->bind_param("i", $target_id);
$stat_stmt->execute();
$stats_res = $stat_stmt->get_result();
$stats = $stats_res->fetch_assoc() ?? ['post_count'=>0, 'likes_received'=>0, 'comments_received'=>0, 'follower_count'=>0];

$activity_score = ($stats['post_count'] * 3) + ($stats['likes_received'] * 2) + ($stats['comments_received'] * 2) + ($stats['follower_count'] * 5);

// 4. Fetch Portfolio Items
$portfolio_query = "SELECT media_url, content, created_at FROM engineer_posts WHERE user_id = ? AND media_type = 'image' ORDER BY created_at DESC LIMIT 4";
$port_stmt = $conn->prepare($portfolio_query);
$port_stmt->bind_param("i", $target_id);
$port_stmt->execute();
$portfolio_items = $port_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 5. Fetch Achievements
$achievements_query = "SELECT * FROM engineer_achievements WHERE user_id = ? ORDER BY created_at DESC";
$badges_stmt = $conn->prepare($achievements_query);
$badges_stmt->bind_param("i", $target_id);
$badges_stmt->execute();
$badges = $badges_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$name = htmlspecialchars($user['name']);
$headline = htmlspecialchars($user['specialization'] ?: 'Engineer');
$joined_date = date('F Y', strtotime($user['created_at']));
$profile_pic = htmlspecialchars($user['profile_picture'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $name; ?> | Profile</title>
    
    <!-- Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Outfit:wght@200;300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    
    <style>
        :root {
            --primary-accent: #294033;
            --secondary-accent: #3d5a49;
            --bg-light: #f6f7f2;
            --glass-bg: rgba(255, 255, 255, 0.92);
            --glass-border: rgba(41, 64, 51, 0.15);
            --card-radius: 24px;
        }

        body {
            background-color: var(--bg-light);
            color: #1a202c;
            font-family: 'Outfit', sans-serif;
            overflow-x: hidden;
            margin: 0;
        }

        /* 3D Background Canvas */
        #canvas-container {
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            z-index: -1; background: #f6f7f2; pointer-events: none;
        }
        .bg-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(to right, rgba(246, 247, 242, 0) 0%, rgba(246, 247, 242, 0.5) 50%, rgba(246, 247, 242, 0) 100%);
            z-index: -1;
        }

        /* Nav */
        nav {
            padding: 1.2rem 4rem;
            display: flex; justify-content: space-between; align-items: center;
            position: fixed; top: 0; width: 100%; z-index: 1000;
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0,0,0,0.06);
        }
        .nav-brand {
            font-weight: 800; font-size: 1.6rem;
            font-family: 'Space Grotesk', sans-serif;
            background: linear-gradient(90deg, var(--primary-accent), var(--secondary-accent));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            text-decoration: none;
        }
        .nav-links a {
            padding: 0.75rem 1.5rem; border-radius: 8px;
            font-weight: 600; color: #1a202c; text-decoration: none;
            transition: 0.3s; margin-left: 10px; border: 1px solid rgba(0,0,0,0.1);
            background: white;
        }
        .nav-links a:hover { background: var(--primary-accent); color: white; }

        /* Layout */
        .profile-wrapper {
            max-width: 1440px; margin: 120px auto 0; padding: 0 40px 100px;
            display: grid; grid-template-columns: 400px 1fr; gap: 40px;
        }

        /* Identity Side */
        .identity-side { position: sticky; top: 120px; height: fit-content; }
        .glass-card {
            background: var(--glass-bg); backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border); border-radius: var(--card-radius);
            padding: 40px; box-shadow: 0 30px 60px rgba(0,0,0,0.1);
            position: relative; overflow: hidden;
        }

        .profile-hex-container {
            width: 150px; height: 165px; margin: 0 auto 20px;
            clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
            background: linear-gradient(135deg, var(--primary-accent), var(--secondary-accent));
            padding: 4px; display: flex; align-items: center; justify-content: center;
            position: relative; overflow: hidden; transition: 0.3s;
        }
        .profile-hex-container:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }

        .profile-avatar-char {
            width: 100%; height: 100%;
            background: white; clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
            display: flex; align-items: center; justify-content: center;
            font-size: 4rem; font-weight: 800; color: var(--primary-accent);
        }
        .profile-avatar-img {
            width: 100%; height: 100%; object-fit: cover;
            clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
        }

        .eng-name { font-size: 2rem; font-weight: 800; text-align: center; margin-bottom: 5px; }
        .eng-headline { text-align: center; color: var(--primary-accent); font-weight: 600; font-size: 0.9rem; text-transform: uppercase; margin-bottom: 25px; }
        .stats-strip {
            display: grid; grid-template-columns: 1fr; gap: 15px; margin-top: 30px; border-top: 1px solid rgba(0,0,0,0.1); padding-top: 20px; text-align: center;
        }
        .stat-num { font-size: 1.5rem; font-weight: 800; color: var(--primary-accent); }
        .stat-txt { font-size: 0.7rem; opacity: 0.6; font-weight: 700; }

        /* Hub Sections */
        .hub-section { margin-bottom: 60px; }
        .section-tag { font-size: 0.75rem; color: var(--primary-accent); font-weight: 700; text-transform: uppercase; letter-spacing: 2px; display: block; margin-bottom: 10px; }
        .hub-title { font-size: 2.2rem; font-weight: 800; margin-bottom: 20px; }

        /* Achievements */
        .achievement-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;
        }
        .achievement-card {
            background: white; border-radius: 18px; padding: 25px;
            display: flex; align-items: flex-start; gap: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.05);
            transition: 0.3s;
        }
        .achievement-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.08); }
        .badge-icon {
            width: 50px; height: 50px; border-radius: 12px;
            background: #f0fdf4; color: var(--secondary-accent);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; flex-shrink: 0;
        }
        .badge-info h4 { margin: 0 0 5px 0; font-size: 1.1rem; font-weight: 700; }
        .badge-info p { margin: 0; font-size: 0.9rem; opacity: 0.7; line-height: 1.4; }

        /* Authority Score */
        .authority-score-card {
            background: white; border-radius: 20px; padding: 30px;
            display: flex; align-items: center; gap: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }
        .score-val { font-size: 2.5rem; font-weight: 800; color: var(--primary-accent); }
        
        /* Portfolio */
        .portfolio-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .project-card {
            height: 250px; border-radius: 15px; overflow: hidden; position: relative;
            background: #eee;
        }
        .project-card img { width: 100%; height: 100%; object-fit: cover; transition: 0.3s; }
        .project-card:hover img { transform: scale(1.05); }
        .project-overlay {
            position: absolute; bottom: 0; left: 0; width: 100%; padding: 20px;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent); color: white;
        }
        
        /* Modals */
        .modal-custom {
            display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%;
            background-color: rgba(10, 20, 15, 0.7); backdrop-filter: blur(15px);
            align-items: center; justify-content: center;
        }
        .modal-panel {
            background: rgba(255, 255, 255, 0.95); width: 600px; border-radius: 30px; padding: 0;
            position: relative; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden;
            animation: modalPop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        @keyframes modalPop { from { transform: scale(0.9) translateY(20px); opacity: 0; } to { transform: scale(1) translateY(0); opacity: 1; } }
        .panel-header {
            padding: 30px 40px; background: linear-gradient(135deg, #f0fdf4 0%, #fff 100%);
            border-bottom: 1px solid rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center;
        }
        .panel-body { padding: 40px; }
        .btn-premium-action {
            width: 100%; padding: 15px; border-radius: 15px; border: none;
            background: var(--primary-accent); color: white; font-weight: 700;
            letter-spacing: 1px; text-transform: uppercase; margin-top: 20px; transition: 0.3s;
        }
        .btn-premium-action:hover { background: var(--secondary-accent); transform: translateY(-2px); }

        /* Profile Modal Specifics */
        .custom-tabs { display: flex; background: #f1f5f9; padding: 5px; border-radius: 15px; margin-bottom: 30px; }
        .tab-btn { flex: 1; padding: 12px; border: none; background: transparent; border-radius: 12px; font-weight: 700; color: #64748b; cursor: pointer; transition: 0.3s; }
        .tab-btn.active { background: white; color: var(--primary-accent); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .drop-zone { border: 2px dashed #cbd5e1; border-radius: 20px; height: 250px; display: flex; flex-direction: column; align-items: center; justify-content: center; transition: 0.3s; cursor: pointer; background: #f8fafc; position: relative; overflow: hidden; }
        .drop-zone:hover, .drop-zone.drag-over { border-color: var(--primary-accent); background: #f0fdf4; }
        #preview-image { position: absolute; top:0; left:0; width: 100%; height: 100%; object-fit: contain; padding: 10px; background: white; display: none; }
        .avatar-grid-premium { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
        .avatar-item { aspect-ratio: 1; border-radius: 18px; cursor: pointer; border: 2px solid transparent; padding: 5px; transition: 0.2s; background: #f8fafc; }
        .avatar-item:hover { transform: translateY(-3px); background: white; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .avatar-item.selected { border-color: var(--primary-accent); background: #f0fdf4; }
        .avatar-item img { width: 100%; height: 100%; border-radius: 12px; }

        /* Badge Modal Specs */
        .icon-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px; }
        .icon-option { 
            height: 60px; border-radius: 15px; background: #f8fafc; 
            display: flex; align-items: center; justify-content: center; 
            font-size: 1.5rem; color: #64748b; cursor: pointer; transition: 0.3s; border: 2px solid transparent;
        }
        .icon-option:hover, .icon-option.selected { background: #f0fdf4; color: var(--primary-accent); border-color: var(--primary-accent); }
    </style>
</head>
<body>

<div id="canvas-container"></div>
<div class="bg-overlay"></div>

<nav>
    <a href="engineer.php" class="nav-brand">CONSTRUCTA</a>
    <div class="nav-links">
        <a href="network.php"><i class="fas fa-arrow-left"></i> Network</a>
        <?php if($viewer_id === $target_id): ?>
            <a href="engineer.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="engineer.php">Dashboard</a>
        <?php endif; ?>
    </div>
</nav>

<main class="profile-wrapper">
    <!-- Identity Side -->
    <aside class="identity-side">
        <div class="glass-card">
            <div class="profile-hex-container" <?php if($viewer_id === $target_id) echo 'onclick="openProfileModal()" style="cursor: pointer;"'; ?>>
                <?php if (!empty($profile_pic)): ?>
                    <img src="<?php echo $profile_pic; ?>" class="profile-avatar-img" alt="Profile">
                <?php else: ?>
                    <div class="profile-avatar-char">
                        <?php echo strtoupper(substr($name, 0, 1)); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <h1 class="eng-name"><?php echo $name; ?></h1>
            <p class="eng-headline"><?php echo $headline; ?></p>
            
            <div style="text-align:center; font-size: 0.9rem; opacity: 0.7;">
                <i class="fas fa-calendar-alt"></i> Joined <?php echo $joined_date; ?>
            </div>

            <div class="stats-strip">
                <div class="stat-box">
                    <span class="stat-num"><?php echo $stats['post_count']; ?></span>
                    <span class="stat-txt">POSTS</span>
                </div>
            </div>
            

        </div>
    </aside>

    <!-- Content Hub -->
    <div class="content-hub">
        
        <!-- Authority Section -->
        <section class="hub-section">
            <span class="section-tag">Platform Impact</span>
            <h2 class="hub-title">Authority Score</h2>
            <div class="authority-score-card">
                <div class="score-val"><?php echo $activity_score; ?></div>
                <div>
                    <h4 class="mb-1 fw-bold">Community Activity Points</h4>
                    <p class="mb-0 opacity-75 small">Calculated based on posts, likes, comments, and follower growth on the Constructa Network.</p>
                </div>
            </div>
        </section>

        <!-- Achievements Section (Replaces About) -->
        <section class="hub-section">
            <span class="section-tag">Recognitions</span>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 class="hub-title" style="margin-bottom: 0;">Achievements</h2>
                <?php if($viewer_id === $target_id): ?>
                    <button class="btn btn-sm btn-outline-dark rounded-pill px-3 fw-bold" onclick="openBadgeModal()">
                        <i class="fas fa-plus me-1"></i> Add Badge
                    </button>
                <?php endif; ?>
            </div>
            
            <div class="achievement-grid">
                <?php foreach($badges as $badge): ?>
                <div class="achievement-card">
                    <div class="badge-icon"><i class="<?php echo htmlspecialchars($badge['badge_icon']); ?>"></i></div>
                    <div class="badge-info">
                        <h4><?php echo htmlspecialchars($badge['badge_title']); ?></h4>
                        <p><?php echo htmlspecialchars($badge['badge_description']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if(empty($badges)): ?>
                    <div class="text-center p-4 border rounded-4 w-100" style="grid-column: 1/-1; background: #fff;">
                        <i class="fas fa-award fa-2x mb-3 opacity-25"></i>
                        <p class="mb-0 text-muted opacity-75">No achievements listed yet used to showcase milestones.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Portfolio (Recent Image Posts) -->
        <section class="hub-section">
            <span class="section-tag">Recent Work</span>
            <h2 class="hub-title">Portfolio Highlights</h2>
            <?php if (count($portfolio_items) > 0): ?>
                <div class="portfolio-grid">
                    <?php foreach($portfolio_items as $item): ?>
                    <div class="project-card">
                        <img src="<?php echo htmlspecialchars($item['media_url']); ?>" alt="Project Image">
                        <div class="project-overlay">
                            <?php if(!empty($item['content'])): ?>
                                <small><?php echo htmlspecialchars(substr($item['content'], 0, 50)) . '...'; ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="p-5 text-center bg-white rounded-4 border">
                    <i class="fas fa-images fa-2x mb-3 opacity-25"></i>
                    <p class="mb-0 fw-bold opacity-50">No portfolio images uploaded yet.</p>
                    <small>Share images in the Network feed to see them here.</small>
                </div>
            <?php endif; ?>
        </section>

    </div>
</main>

<!-- Profile Picture Modal -->
<?php if($viewer_id === $target_id): ?>
<div id="profileModal" class="modal-custom">
    <div class="modal-panel">
        <div class="panel-header">
            <div class="panel-title"><span>Visual Identity</span><h3>Update Profile Picture</h3></div>
            <button onclick="closeProfileModal()" class="btn-close"></button>
        </div>
        <div class="panel-body">
            <div class="custom-tabs">
                <button class="tab-btn active" onclick="switchTab('upload')"><i class="fas fa-cloud-upload-alt me-2"></i> Upload</button>
                <button class="tab-btn" onclick="switchTab('avatar')"><i class="fas fa-robot me-2"></i> Generate</button>
            </div>
            <div id="view-upload" class="tab-view">
                <div class="drop-zone" id="dropArea" onclick="document.getElementById('fileInput').click()">
                    <i class="far fa-image dz-icon"></i>
                    <span class="dz-text">Drag & drop or Click to Browse</span>
                    <input type="file" id="fileInput" hidden accept="image/*">
                    <img id="preview-image" src="">
                </div>
                <button class="btn-premium-action" onclick="saveUpload()">Confirm Upload</button>
            </div>
            <div id="view-avatar" class="tab-view" style="display:none;">
                <div class="avatar-grid-premium" id="avatarGrid"></div>
                <button class="btn-premium-action" onclick="saveAvatar()">Apply Avatar</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Badge Modal -->
<div id="badgeModal" class="modal-custom">
    <div class="modal-panel" style="width: 500px;">
        <div class="panel-header">
            <div class="panel-title"><span>Recognition</span><h3>Add New Achievement</h3></div>
            <button onclick="closeBadgeModal()" class="btn-close"></button>
        </div>
        <div class="panel-body">
            <div class="mb-3">
                <label class="form-label fw-bold small text-uppercase">Title</label>
                <input type="text" id="badgeTitle" class="form-control form-control-lg" placeholder="e.g. Lead Architect">
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold small text-uppercase">Description</label>
                <textarea id="badgeDesc" class="form-control" rows="2" placeholder="Brief description of the achievement"></textarea>
            </div>
            <div class="mb-4">
                <label class="form-label fw-bold small text-uppercase">Select Icon</label>
                <div class="icon-grid">
                    <div class="icon-option selected" onclick="selectIcon('fas fa-trophy', this)"><i class="fas fa-trophy"></i></div>
                    <div class="icon-option" onclick="selectIcon('fas fa-medal', this)"><i class="fas fa-medal"></i></div>
                    <div class="icon-option" onclick="selectIcon('fas fa-certificate', this)"><i class="fas fa-certificate"></i></div>
                    <div class="icon-option" onclick="selectIcon('fas fa-star', this)"><i class="fas fa-star"></i></div>
                    <div class="icon-option" onclick="selectIcon('fas fa-rocket', this)"><i class="fas fa-rocket"></i></div>
                    <div class="icon-option" onclick="selectIcon('fas fa-code', this)"><i class="fas fa-code"></i></div>
                    <div class="icon-option" onclick="selectIcon('fas fa-check-circle', this)"><i class="fas fa-check-circle"></i></div>
                    <div class="icon-option" onclick="selectIcon('fas fa-crown', this)"><i class="fas fa-crown"></i></div>
                </div>
            </div>
            <button class="btn-premium-action mt-0" onclick="submitBadge()">Add Badge</button>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
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

    gsap.from(".identity-side", { x: -50, opacity: 0, duration: 1, ease: "power3.out" });
    gsap.from(".content-hub", { y: 50, opacity: 0, duration: 1, delay: 0.2, ease: "power3.out" });

    // Profile Modal
    let selectedAvatarUrl = '';
    function openProfileModal() { document.getElementById('profileModal').style.display = 'flex'; generateAvatars(); }
    function closeProfileModal() { document.getElementById('profileModal').style.display = 'none'; }
    window.switchTab = function(tabName) {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-view').forEach(v => v.style.display = 'none');
        if(tabName === 'upload') {
            document.querySelector('button[onclick="switchTab(\'upload\')"]').classList.add('active');
            document.getElementById('view-upload').style.display = 'block';
        } else {
            document.querySelector('button[onclick="switchTab(\'avatar\')"]').classList.add('active');
            document.getElementById('view-avatar').style.display = 'block';
            generateAvatars();
        }
    }
    // (Existing Drop/Avatar Code Omitted for Brevity but kept logic)
    const dropArea = document.getElementById('dropArea');
    if(dropArea) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => dropArea.addEventListener(eventName, e => {e.preventDefault(); e.stopPropagation()}, false));
        dropArea.addEventListener('drop', e => handleFiles(e.dataTransfer.files), false);
        document.getElementById('fileInput').addEventListener('change', function() { handleFiles(this.files); });
        function handleFiles(files) {
            if(files.length > 0 && files[0].type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = e => { const img = document.getElementById('preview-image'); img.src = e.target.result; img.style.display = 'block'; }
                reader.readAsDataURL(files[0]);
                window.currentUploadFile = files[0];
            }
        }
    }
    function generateAvatars() {
        const styles = ['personas', 'bottts', 'adventurer', 'lorelei'];
        const grid = document.getElementById('avatarGrid'); grid.innerHTML = '';
        for(let i=0; i<8; i++) {
            const seed = Math.random().toString(36).substring(7);
            const style = styles[i % styles.length];
            const url = `https://api.dicebear.com/9.x/${style}/svg?seed=${seed}&backgroundColor=eef2ff,dbeafe`;
            const div = document.createElement('div'); div.className = 'avatar-item'; div.innerHTML = `<img src="${url}">`;
            div.onclick = function() { document.querySelectorAll('.avatar-item').forEach(el => el.classList.remove('selected')); this.classList.add('selected'); selectedAvatarUrl = url; };
            grid.appendChild(div);
        }
    }
    async function saveUpload() {
        const file = document.getElementById('fileInput').files[0] || window.currentUploadFile;
        if(!file) return alert('Select image first');
        const formData = new FormData(); formData.append('file', file); formData.append('type', 'upload');
        submitUpdate(formData);
    }
    async function saveAvatar() {
        if(!selectedAvatarUrl) return alert('Select avatar first');
        const formData = new FormData(); formData.append('avatar_url', selectedAvatarUrl); formData.append('type', 'avatar');
        submitUpdate(formData);
    }
    async function submitUpdate(formData) { /* ... same logic ... */ 
        try { const res = await fetch('backend/update_profile_picture.php', { method: 'POST', body: formData }); const data = await res.json(); if(data.success) location.reload(); else alert(data.message); } catch(e) { console.error(e); }
    }

    // Badge Modal Logic
    let selectedBadgeIcon = 'fas fa-trophy';
    function openBadgeModal() { document.getElementById('badgeModal').style.display = 'flex'; }
    function closeBadgeModal() { document.getElementById('badgeModal').style.display = 'none'; }
    window.selectIcon = function(iconClass, el) {
        selectedBadgeIcon = iconClass;
        document.querySelectorAll('.icon-option').forEach(e => e.classList.remove('selected'));
        el.classList.add('selected');
    }
    async function submitBadge() {
        const title = document.getElementById('badgeTitle').value;
        const desc = document.getElementById('badgeDesc').value;
        if(!title) return alert('Title is required');
        
        const formData = new FormData();
        formData.append('title', title);
        formData.append('description', desc);
        formData.append('icon', selectedBadgeIcon);
        
        try {
            const res = await fetch('backend/add_badge.php', { method: 'POST', body: formData });
            const data = await res.json();
            if(data.success) location.reload();
            else alert(data.message);
        } catch(e) { console.error(e); }
    }

    // Animate Score
    const scoreVal = document.querySelector('.score-val');
    if(scoreVal) {
        const finalVal = parseInt(scoreVal.innerText) || 0;
        // Reset to 0 initially
        scoreVal.innerText = "0";
        
        gsap.to(scoreVal, {
            innerText: finalVal,
            duration: 2.5,
            snap: { innerText: 1 },
            ease: "power2.out"
        });
    }

    window.onclick = function(e) {
        if(e.target == document.getElementById('profileModal')) closeProfileModal();
        if(e.target == document.getElementById('badgeModal')) closeBadgeModal();
    }
</script>

</body>
</html>
