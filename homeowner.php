<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'homeowner') {
    header('Location: login.html');
    exit();
}
$username = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Homeowner';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homeowner Dashboard - Constructa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
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
            padding: 6rem 3rem 2rem 3rem; /* Increased top padding */
            display: flex;
            flex-direction: column;
            gap: 3rem;
            z-index: 2;
        }

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

    <!-- Navigation -->
    <div class="nav-fixed-container">
        <a href="landingpage.html" class="top-nav-btn">
            <i class="fas fa-home"></i> Home
        </a>
        <a href="login.html" class="top-nav-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>

    <main class="dashboard-container">
        <div class="welcome-section">
            <h1 class="welcome-title">Homeowner Dashboard</h1>
            <p class="welcome-subtitle">Welcome back, <?php echo htmlspecialchars($username); ?>! Let's build your dream together.</p>
        </div>

        <div class="features-grid">
            <!-- Card 1: AI Architect Studio -->
            <div class="feature-card tilt-card" onclick="window.location.href='plans_designs.php'">
                <div class="card-content">
                    <div class="icon-wrapper gradient-1">
                        <i class="fas fa-magic"></i>
                    </div>
                    <h3>Plans & Designs</h3>
                    <p>Gather requirements and generate architectural blueprints and 3D models.</p>
                </div>
                <div class="card-bg-3d"></div>
            </div>

            <!-- Card 2: Budget Calculator -->
            <div class="feature-card tilt-card" onclick="window.location.href='budget_calculator.php'">
                <div class="card-content">
                    <div class="icon-wrapper gradient-2">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <h3>Budget Planner</h3>
                    <p>Calculate construction costs and manage your budget with our wizard.</p>
                </div>
                <div class="card-bg-3d"></div>
            </div>

            <!-- Card 3: Material Market -->
            <div class="feature-card tilt-card" onclick="window.location.href='material_market.php'">
                <div class="card-content">
                    <div class="icon-wrapper gradient-3">
                        <i class="fas fa-store"></i>
                    </div>
                    <h3>Material Market</h3>
                    <p>Browse and select premium construction materials for your project.</p>
                </div>
                <div class="card-bg-3d"></div>
            </div>

            <!-- Card 4: Engineer Directory -->
            <div class="feature-card tilt-card" onclick="window.location.href='engineer_directory.php'">
                <div class="card-content">
                    <div class="icon-wrapper gradient-4">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h3>Engineer Directory</h3>
                    <p>Connect with vetted structural and civil engineers for your project.</p>
                </div>
                <div class="card-bg-3d"></div>
            </div>

            <!-- Card 5: Project Status -->
            <div class="feature-card tilt-card" onclick="window.location.href='#'">
                <div class="card-content">
                    <div class="icon-wrapper gradient-5">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <h3>Project Status</h3>
                    <p>Check the live status of your active construction projects and estimates.</p>
                </div>
                <div class="card-bg-3d"></div>
            </div>

            <!-- Card 6: Saved Favorites -->
            <div class="feature-card tilt-card" onclick="window.location.href='saved_favorites.php'">
                <div class="card-content">
                    <div class="icon-wrapper gradient-6">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3>Saved Favorites</h3>
                    <p>Access your saved house plans, materials, and engineer profiles.</p>
                </div>
                <div class="card-bg-3d"></div>
            </div>

            <!-- Card 7: Resource Library -->
            <div class="feature-card tilt-card" onclick="window.location.href='resources.php'">
                <div class="card-content">
                    <div class="icon-wrapper gradient-7">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <h3>Resource Library</h3>
                    <p>Examine guides, compliance documents, and construction best practices.</p>
                </div>
                <div class="card-bg-3d"></div>
            </div>

            <!-- Card 8: Feedback -->
            <div class="feature-card tilt-card" onclick="window.location.href='feedback.php'">
                <div class="card-content">
                    <div class="icon-wrapper gradient-8">
                        <i class="fas fa-comment-dots"></i>
                    </div>
                    <h3>Feedback & Support</h3>
                    <p>Share your experience or get assistance from our support team.</p>
                </div>
                <div class="card-bg-3d"></div>
            </div>

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

            // === 3D BACKGROUND ===
            const initBackground3D = () => {
                const container = document.getElementById('canvas-container');
                if (!container) return;
                const scene = new THREE.Scene();
                scene.background = new THREE.Color('#f6f7f2');
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

                // Reusable wireframe building elements
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
                        building.position.set(x * spacing, -5, z * spacing);
                        floorGroup.add(building);
                    }
                }

                // Primary Hero Asset (Floating Wireframe House)
                const heroGroup = new THREE.Group();
                const floorGeo = new THREE.BoxGeometry(4, 0.2, 4);
                const floorLine = new THREE.LineSegments(new THREE.EdgesGeometry(floorGeo), new THREE.LineBasicMaterial({color: 0x294033, opacity: 0.8}));
                heroGroup.add(floorLine);

                const wallGeo = new THREE.BoxGeometry(3.5, 2.5, 3.5);
                const wallLines = new THREE.LineSegments(new THREE.EdgesGeometry(wallGeo), new THREE.LineBasicMaterial({color: 0x294033}));
                wallLines.position.y = 1.35;
                heroGroup.add(wallLines);

                const roofGeo = new THREE.ConeGeometry(3, 2, 4);
                const roofLines = new THREE.LineSegments(new THREE.EdgesGeometry(roofGeo), new THREE.LineBasicMaterial({color: 0x3d5a49}));
                roofLines.position.y = 3.6;
                roofLines.rotation.y = Math.PI / 4;
                heroGroup.add(roofLines);

                heroGroup.position.set(0, 0, 0);
                scene.add(heroGroup);

                // Parallax Mouse Effect
                let mouseX = 0, mouseY = 0;
                document.addEventListener('mousemove', (e) => {
                    mouseX = (e.clientX - window.innerWidth / 2) * 0.0005;
                    mouseY = (e.clientY - window.innerHeight / 2) * 0.0005;
                });

                const animate = () => {
                    requestAnimationFrame(animate);
                    
                    const time = Date.now() * 0.001;
                    
                    // Floating rotation
                    heroGroup.rotation.y += 0.005;
                    heroGroup.position.y = Math.sin(time) * 0.5;
                    
                    // Grid movement
                    floorGroup.rotation.y += 0.001;
                    
                    // Mouse effect
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
            };
            if (typeof THREE !== 'undefined') initBackground3D();
        });
    </script>
</body>

</html>
