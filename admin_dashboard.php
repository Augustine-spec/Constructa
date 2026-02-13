<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.html');
    exit();
}
$username = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Admin';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Constructa</title>
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
            --input-bg: #f9f9f9;
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

        /* Navbar */
        header {
            padding: 1.5rem 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1600px;
            margin: 0 auto;
            width: 100%;
            background: rgba(246, 247, 242, 0.9);
            backdrop-filter: blur(10px);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-green);
            text-decoration: none;
        }

        .logo i {
            font-size: 1.5rem;
        }

        nav {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .top-nav-btn {
            padding: 0.8rem 1.5rem;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.1);
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
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .top-nav-btn:hover {
            background: var(--primary-green);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        }

        /* Dashboard Layout */
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            padding: 2rem 3rem;
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
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15), 0 0 1px rgba(0, 0, 0, 0.05);
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
        .gradient-admin-1 {
            background: linear-gradient(135deg, #ef4444, #b91c1c);
        }

        .gradient-admin-2 {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .gradient-admin-3 {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }

        .gradient-admin-4 {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .gradient-admin-5 {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        }

        .gradient-admin-6 {
            background: linear-gradient(135deg, #0f766e, #115e59);
        }

        .gradient-admin-7 {
            background: linear-gradient(135deg, #be123c, #9f1239);
        }

        .gradient-admin-8 {
            background: linear-gradient(135deg, #b45309, #92400e);
        }

        .feature-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
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
            from {
                opacity: 0;
                transform: translateY(-30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .features-grid {
                grid-template-columns: 1fr;
            }

            .welcome-title {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body>
    <!-- 3D Canvas Background -->
    <div id="canvas-container"></div>

    <!-- Navigation -->
    <header>
        <a href="landingpage.html" class="logo">
            <i class="far fa-building"></i>
            Constructa
        </a>
        <nav>
            <a href="landingpage.html" class="top-nav-btn">
                <i class="fas fa-home"></i> Home
            </a>
            <a href="backend/logout.php" class="top-nav-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </header>

    <main class="dashboard-container">
        <div class="welcome-section">
            <h1 class="welcome-title">Admin Dashboard</h1>
            <p class="welcome-subtitle">System Overview and Management Controls</p>
        </div>

        <div class="features-grid">
            <!-- Card 1: User Management -->
            <div class="feature-card tilt-card" onclick="window.location.href='admin_user_management.php'">
                <div class="card-content">
                    <div class="icon-wrapper gradient-admin-1">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <h3>User Management</h3>
                    <p>Manage homeowners, verify engineers, and handle account permissions.</p>
                </div>
                <div class="card-bg-3d"></div>
            </div>

            <!-- Card 2: Content Management -->
            <div class="feature-card tilt-card" onclick="window.location.href='content.php'">
                <div class="card-content">
                    <div class="icon-wrapper gradient-admin-3">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <h3>Content & Plans</h3>
                    <p>Upload new house plans, designs, and update architecture resources.</p>
                </div>
                <div class="card-bg-3d"></div>
            </div>

            <!-- Card 3: Material Procurement -->
            <div class="feature-card tilt-card" onclick="window.location.href='material_procurement.php'">
                <div class="card-content">
                    <div class="icon-wrapper gradient-admin-2">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <h3>Material Procurement</h3>
                    <p>Track material orders, monitor delivery statuses, and manage onsite inventory.</p>
                </div>
                <div class="card-bg-3d"></div>
            </div>

            <!-- Card 4: Feedback -->
            <div class="feature-card tilt-card" onclick="window.location.href='admin_feedback_queue.php'">
                <div class="card-content">
                    <div class="icon-wrapper gradient-admin-4">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h3>Feedback Queue</h3>
                    <p>Monitor operational quality scores and validate technical review sessions.</p>
                </div>
                <div class="card-bg-3d"></div>
            </div>

            <!-- Card 5: Analytics -->
            <div class="feature-card tilt-card" onclick="window.location.href='platform_analytics.php'">
                <div class="card-content">
                    <div class="icon-wrapper gradient-admin-5">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Platform Analytics</h3>
                    <p>View site traffic, user registrations, and project statistics.</p>
                </div>
                <div class="card-bg-3d"></div>
            </div>



            <!-- Card 7: Notification Hub -->
            <div class="feature-card tilt-card" onclick="window.location.href='notifications.php'">
                <div class="card-content">
                    <div class="icon-wrapper gradient-admin-7">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <h3>Notification Hub</h3>
                    <p>Policy-driven communication and audience-targeted broadcasting.</p>
                </div>
                <div class="card-bg-3d"></div>
            </div>

            <!-- Card 8: Template Management -->
            <div class="feature-card tilt-card" onclick="window.location.href='admin_manage_templates.php'">
                <div class="card-content">
                    <div class="icon-wrapper gradient-admin-8">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <h3>Template Management</h3>
                    <p>Add, update, enable, or remove house plan templates and specifications.</p>
                </div>
                <div class="card-bg-3d"></div>
            </div>

            <!-- Card 9: Professional Network -->
            <div class="feature-card tilt-card" onclick="window.location.href='network.php'">
                <div class="card-content">
                    <div class="icon-wrapper gradient-admin-3">
                        <i class="fas fa-network-wired"></i>
                    </div>
                    <h3>Professional Network</h3>
                    <p>Moderate discussions, view community posts, and handle user reports.</p>
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
                    const rotateX = ((y - centerY) / centerY) * -10;
                    const rotateY = ((x - centerX) / centerX) * 10;
                    card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.02) translateY(-10px)`;
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
                // Add soft fog for atmospheric perspective (fades distant objects)
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

                // Admin specific background elements? Maybe more structured grid.
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

                // Hero Object
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

                // Animation
                let mouseX = 0;
                let mouseY = 0;
                document.addEventListener('mousemove', (event) => {
                    mouseX = (event.clientX - window.innerWidth / 2) * 0.001;
                    mouseY = (event.clientY - window.innerHeight / 2) * 0.001;
                });

                let scrollY = 0;
                const animate = () => {
                    requestAnimationFrame(animate);
                    cityGroup.rotation.y += 0.001;
                    floatGroup.rotation.y += 0.005;
                    floatGroup.position.y = Math.sin(Date.now() * 0.001) * 0.5 + 0.5;
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
        });
    </script>
</body>

</html>