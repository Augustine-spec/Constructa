<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'homeowner') { header('Location: login.html'); exit(); }
require_once 'backend/config.php';

$conn = getDatabaseConnection();
$sql = "SELECT id, name, email, specialization, experience, profile_picture FROM users WHERE role = 'engineer' AND status = 'approved'";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Structural Engineering Directory | Constructa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <style>
        :root {
            --bg-color: #f6f7f2;
            --text-dark: #121212;
            --text-gray: #555555;
            --primary-green: #294033;
            --accent-green: #3d5a49;
            --card-bg: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: transparent;
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
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

        /* Fixed Navigation Buttons */
        .nav-fixed-container {
            position: fixed;
            top: 2rem;
            right: 2rem;
            z-index: 1000;
            display: flex;
            gap: 1rem;
        }

        .top-nav-btn {
            padding: 0.8rem 1.5rem;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 4px;
            text-decoration: none;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8rem;
            color: var(--text-dark);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .top-nav-btn:hover {
            background: var(--primary-green);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }

        /* Dashboard Layout */
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            padding: 6rem 3rem 2rem 3rem; /* Increased top padding since header is gone */
            display: flex;
            flex-direction: column;
            gap: 3rem;
            z-index: 2;
        }
/* ... existing styles ... */
        .welcome-section {
            text-align: center;
            margin-bottom: 1rem;
            animation: fadeInDown 0.8s ease-out;
        }

        .welcome-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--primary-green);
            background: linear-gradient(135deg, #294033 0%, #3d5a49 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .welcome-subtitle {
            color: var(--text-gray);
            font-size: 1.1rem;
        }

        /* Features Grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            perspective: 1000px;
        }

        @media (max-width: 1200px) {
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .features-grid {
                grid-template-columns: 1fr;
            }
            .nav-fixed-container {
                top: 1rem;
                right: 1rem;
                flex-direction: column;
                gap: 0.5rem;
            }
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border-radius: 24px;
            padding: 2.5rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(255, 255, 255, 0.5);
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transform-style: preserve-3d;
            animation: fadeInUp 0.8s ease-out backwards;
            min-height: 280px;
            justify-content: center;
        }

        .feature-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
            background: rgba(255, 255, 255, 0.95);
        }

        .icon-wrapper {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 0.5rem;
            transform: translateZ(20px);
        }

        /* Gradients for Icons */
        .gradient-1 { background: linear-gradient(135deg, #6366f1, #4f46e5); }
        .gradient-2 { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .gradient-3 { background: linear-gradient(135deg, #10b981, #059669); }
        .gradient-4 { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .gradient-5 { background: linear-gradient(135deg, #ec4899, #db2777); }
        .gradient-6 { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .gradient-7 { background: linear-gradient(135deg, #f43f5e, #e11d48); }
        .gradient-8 { background: linear-gradient(135deg, #06b6d4, #0891b2); }

        .feature-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-green);
            transform: translateZ(15px);
        }

        .feature-card p {
            color: var(--text-gray);
            line-height: 1.6;
            font-size: 0.95rem;
            transform: translateZ(10px);
        }

        .card-meta {
            margin-top: auto;
            border-top: 1px solid rgba(0,0,0,0.05);
            padding-top: 1rem;
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: var(--text-gray);
            font-weight: 500;
        }

        /* 3D Decorative Blob in Card */
        .card-bg-3d {
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, rgba(41, 64, 51, 0.05) 0%, rgba(255, 255, 255, 0) 70%);
            border-radius: 50%;
            z-index: 0;
            transition: all 0.5s ease;
        }

        .feature-card:hover .card-bg-3d {
            transform: scale(1.5);
            background: radial-gradient(circle, rgba(41, 64, 51, 0.1) 0%, rgba(255, 255, 255, 0) 70%);
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 10px; }
        ::-webkit-scrollbar-track { background: var(--bg-color); }
        ::-webkit-scrollbar-thumb { background: #ccc; border-radius: 5px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--primary-green); }

    </style>
</head>

<body>
    <!-- 3D Canvas Background -->
    <div id="canvas-container"></div>

    <!-- Navigation Buttons -->
    <div class="nav-fixed-container">
        <a href="homeowner.php" class="top-nav-btn">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <a href="material_market.php" class="top-nav-btn">
            <i class="fas fa-shopping-cart"></i> Market
        </a>
        <a href="login.html" class="top-nav-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>

    <main class="dashboard-container">
        <div class="welcome-section">
            <h1 class="welcome-title">Engineer Directory</h1>
            <p class="welcome-subtitle">Connect with vetted experts for your structural needs.</p>
        </div>

        <div class="features-grid">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php 
                $gradients = ['gradient-1', 'gradient-2', 'gradient-3', 'gradient-4', 'gradient-5', 'gradient-6', 'gradient-7', 'gradient-8'];
                $i = 0;
                while($row = $result->fetch_assoc()): 
                    $exp = $row['experience'] ?: 5;
                    $gradientClass = $gradients[$i % count($gradients)];
                    $i++;
                ?>
                    <!-- Engineer Card Style 1 -->
                    <div class="feature-card tilt-card" onclick="window.location.href='contact_engineer.php?id=<?php echo $row['id']; ?>'">
                        <div class="card-content">
                            <div class="icon-wrapper <?php echo $gradientClass; ?>">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                            <p>
                                <?php echo htmlspecialchars($row['specialization'] ?: 'Structural Engineer'); ?>
                            </p>
                            <div class="card-meta">
                                <span><?php echo $exp; ?> Yrs Exp</span>
                                <span>Verified</span>
                            </div>
                        </div>
                        <div class="card-bg-3d"></div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 4rem; color: var(--text-gray);">
                    <i class="fas fa-search" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <h3>No Engineers Found</h3>
                    <p>There are currently no active engineers in the directory.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // === 3D TILT EFFECT ===
            const cards = document.querySelectorAll('.tilt-card');
            cards.forEach(card => {
                card.addEventListener('mousemove', (e) => {
                    const rect = card.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    const centerX = rect.width / 2;
                    const centerY = rect.height / 2;
                    const rotateX = ((y - centerY) / centerY) * -15; // Increased tilt
                    const rotateY = ((x - centerX) / centerX) * 15; // Increased tilt
                    card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.03) translateY(-15px)`;
                });
                card.addEventListener('mouseleave', () => {
                    card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale(1) translateY(0)';
                });
            });

            // === 3D BACKGROUND (Cinematic Intro) ===
            const initBackground3D = () => {
                const container = document.getElementById('canvas-container');
                if (!container) return;
                const scene = new THREE.Scene();
                scene.background = new THREE.Color('#f6f7f2');
                const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
                
                // Initial Camera Position (Zoomed Out)
                camera.position.set(0, 20, 40);
                
                const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
                renderer.setSize(window.innerWidth, window.innerHeight);
                renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
                container.appendChild(renderer.domElement);
                
                const ambientLight = new THREE.AmbientLight(0xffffff, 0.7);
                scene.add(ambientLight);
                
                const mainLight = new THREE.DirectionalLight(0xffffff, 0.8);
                mainLight.position.set(10, 20, 10);
                scene.add(mainLight);

                // Grid of buildings
                const floorGroup = new THREE.Group();
                scene.add(floorGroup);
                
                const buildMat = new THREE.MeshPhongMaterial({ 
                    color: 0x294033, 
                    transparent: true, 
                    opacity: 0.1, 
                    side: THREE.DoubleSide 
                });
                const edgeMat = new THREE.LineBasicMaterial({ 
                    color: 0x294033, 
                    transparent: true, 
                    opacity: 0.2 
                });

                const gridSize = 8;
                const spacing = 4;
                const buildings = [];

                for (let x = -gridSize; x <= gridSize; x++) {
                    for (let z = -gridSize; z <= gridSize; z++) {
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
                        
                        // Set initial position (below ground for intro)
                        building.position.set(x * spacing, -20, z * spacing);
                        
                        floorGroup.add(building);
                        buildings.push({
                            obj: building,
                            targetY: -5,
                            delay: Math.random() * 1
                        });
                    }
                }

                // INTRO ANIMATION SEQUENCE
                const tl = gsap.timeline({ defaults: { ease: "power3.out" } });

                // 1. Camera swoop in
                tl.to(camera.position, { z: 10, y: 5, duration: 2.5, ease: "power2.inOut" });

                // 2. City rises from below
                buildings.forEach(b => {
                    gsap.to(b.obj.position, {
                        y: b.targetY,
                        duration: 1.5,
                        delay: 0.5 + b.delay,
                        ease: "back.out(1.2)"
                    });
                });

                // Parallax Mouse Effect
                let mouseX = 0, mouseY = 0;
                document.addEventListener('mousemove', (e) => {
                    mouseX = (e.clientX - window.innerWidth / 2) * 0.0005;
                    mouseY = (e.clientY - window.innerHeight / 2) * 0.0005;
                });

                const animate = () => {
                    requestAnimationFrame(animate);
                    
                    // City rotation
                    if(tl.progress() > 0.3) {
                        floorGroup.rotation.y += 0.001;
                        
                        // Mouse effect
                        floorGroup.rotation.x += 0.05 * (mouseY - floorGroup.rotation.x);
                        floorGroup.rotation.y += 0.05 * (mouseX - floorGroup.rotation.y);
                    }
                    
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
        });
    </script>
</body>
</html>
