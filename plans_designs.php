<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'homeowner') {
    header('Location: login.html');
    exit();
}
?

>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plans & Designs - Constructa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <style>
        :root { --bg-color: #f6f7f2; --text-dark: #121212; --text-gray: #555555; --primary-green: #294033; --accent-green: #3d5a49; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-color); color: var(--text-dark); min-height: 100vh; display: flex; flex-direction: column; }
        #canvas-container { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: -1; background: #f6f7f2; }
        header { padding: 1.5rem 3rem; display: flex; justify-content: space-between; align-items: center; max-width: 1600px; margin: 0 auto; width: 100%; background: rgba(246, 247, 242, 0.9); backdrop-filter: blur(10px); position: sticky; top: 0; z-index: 100; }
        .logo { display: flex; align-items: center; gap: 0.5rem; font-size: 1.25rem; font-weight: 700; color: var(--primary-green); text-decoration: none; }
        nav { display: flex; gap: 2rem; align-items: center; }
        nav a { text-decoration: none; color: var(--text-dark); font-weight: 500; transition: color 0.2s; }
        nav a:hover { color: var(--primary-green); }
        main { flex: 1; max-width: 1400px; margin: 0 auto; width: 100%; padding: 3rem; z-index: 2; }
        .page-header { text-align: center; margin-bottom: 3rem; }
        .page-title { font-size: 3rem; font-weight: 700; margin-bottom: 0.5rem; background: linear-gradient(135deg, #294033 0%, #3d5a49 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .page-subtitle { color: var(--text-gray); font-size: 1.1rem; }
        
        .plans-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 2.5rem; }
        .plan-card { background: rgba(255, 255, 255, 0.95); border-radius: 24px; overflow: hidden; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); transition: all 0.4s; cursor: pointer; }
        .plan-card:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15); }
        .plan-image { width: 100%; height: 250px; background: linear-gradient(135deg, #3b82f6, #2563eb); display: flex; align-items: center; justify-content: center; color: white; font-size: 4rem; }
        .plan-content { padding: 2rem; }
        .plan-title { font-size: 1.8rem; font-weight: 700; margin-bottom: 0.8rem; color: var(--text-dark); }
        .plan-desc { color: var(--text-gray); line-height: 1.6; margin-bottom: 1.5rem; }
        .plan-specs { display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
        .spec-item { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: rgba(41, 64, 51, 0.05); border-radius: 8px; font-size: 0.9rem; }
        .btn-view { background: linear-gradient(135deg, #294033, #3d5a49); color: white; padding: 1rem 2rem; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; width: 100%; transition: all 0.3s; }
        .btn-view:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(41, 64, 51, 0.3); }
        
        @keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
        .plan-card { animation: fadeIn 0.6s ease-out; }
    </style>
</head>
<body>
    <div id="canvas-container"></div>
    <header>
        <a href="homeowner.php" class="logo"><i class="far fa-building"></i> Constructa</a>
        <nav>
            <a href="homeowner.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="landingpage.html">Home</a>
            <a href="login.html">Logout</a>
        </nav>
    </header>

    <main>
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-layer-group"></i> Plans & Designs</h1>
            <p class="page-subtitle">View 3D models, blueprints, and architectural drafts</p>
        </div>

        <div class="plans-grid">
            <!-- Plan 1 -->
            <div class="plan-card">
                <div class="plan-image" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                    <i class="fas fa-home"></i>
                </div>
                <div class="plan-content">
                    <h3 class="plan-title">Modern Villa</h3>
                    <p class="plan-desc">Luxurious 3-bedroom villa with contemporary design, spacious living areas, and panoramic views</p>
                    <div class="plan-specs">
                        <div class="spec-item"><i class="fas fa-bed"></i> 3 Bedrooms</div>
                        <div class="spec-item"><i class="fas fa-bath"></i> 2 Bathrooms</div>
                        <div class="spec-item"><i class="fas fa-ruler-combined"></i> 2500 sq ft</div>
                    </div>
                    <button class="btn-view"><i class="fas fa-eye"></i> View 3D Model</button>
                </div>
            </div>

            <!-- Plan 2 -->
            <div class="plan-card">
                <div class="plan-image" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <i class="fas fa-building"></i>
                </div>
                <div class="plan-content">
                    <h3 class="plan-title">Eco-Friendly Home</h3>
                    <p class="plan-desc">Sustainable design with solar panels, rainwater harvesting, and energy-efficient systems</p>
                    <div class="plan-specs">
                        <div class="spec-item"><i class="fas fa-bed"></i> 2 Bedrooms</div>
                        <div class="spec-item"><i class="fas fa-bath"></i> 2 Bathrooms</div>
                        <div class="spec-item"><i class="fas fa-ruler-combined"></i> 1800 sq ft</div>
                    </div>
                    <button class="btn-view"><i class="fas fa-eye"></i> View 3D Model</button>
                </div>
            </div>

            <!-- Plan 3 -->
            <div class="plan-card">
                <div class="plan-image" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                    <i class="fas fa-city"></i>
                </div>
                <div class="plan-content">
                    <h3 class="plan-title">Urban Apartment</h3>
                    <p class="plan-desc">Compact and efficient design perfect for city living with modern amenities</p>
                    <div class="plan-specs">
                        <div class="spec-item"><i class="fas fa-bed"></i> 2 Bedrooms</div>
                        <div class="spec-item"><i class="fas fa-bath"></i> 1 Bathroom</div>
                        <div class="spec-item"><i class="fas fa-ruler-combined"></i> 1200 sq ft</div>
                    </div>
                    <button class="btn-view"><i class="fas fa-eye"></i> View 3D Model</button>
                </div>
            </div>

            <!-- Plan 4 -->
            <div class="plan-card">
                <div class="plan-image" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                    <i class="fas fa-warehouse"></i>
                </div>
                <div class="plan-content">
                    <h3 class="plan-title">Family Mansion</h3>
                    <p class="plan-desc">Spacious multi-story home with pool, garden, and entertainment areas for large families</p>
                    <div class="plan-specs">
                        <div class="spec-item"><i class="fas fa-bed"></i> 5 Bedrooms</div>
                        <div class="spec-item"><i class="fas fa-bath"></i> 4 Bathrooms</div>
                        <div class="spec-item"><i class="fas fa-ruler-combined"></i> 4500 sq ft</div>
                    </div>
                    <button class="btn-view"><i class="fas fa-eye"></i> View 3D Model</button>
                </div>
            </div>

            <!-- Plan 5 -->
            <div class="plan-card">
                <div class="plan-image" style="background: linear-gradient(135deg, #ec4899, #db2777);">
                    <i class="fas fa-hotel"></i>
                </div>
                <div class="plan-content">
                    <h3 class="plan-title">Minimalist Studio</h3>
                    <p class="plan-desc">Sleek and modern studio apartment with open-plan design and smart storage solutions</p>
                    <div class="plan-specs">
                        <div class="spec-item"><i class="fas fa-bed"></i> Studio</div>
                        <div class="spec-item"><i class="fas fa-bath"></i> 1 Bathroom</div>
                        <div class="spec-item"><i class="fas fa-ruler-combined"></i> 800 sq ft</div>
                    </div>
                    <button class="btn-view"><i class="fas fa-eye"></i> View 3D Model</button>
                </div>
            </div>

            <!-- Plan 6 -->
            <div class="plan-card">
                <div class="plan-image" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                    <i class="fas fa-store"></i>
                </div>
                <div class="plan-content">
                    <h3 class="plan-title">Commercial Complex</h3>
                    <p class="plan-desc">Multi-purpose commercial building with retail spaces, offices, and parking facilities</p>
                    <div class="plan-specs">
                        <div class="spec-item"><i class="fas fa-warehouse"></i> 10 Units</div>
                        <div class="spec-item"><i class="fas fa-car"></i> Parking</div>
                        <div class="spec-item"><i class="fas fa-ruler-combined"></i> 8000 sq ft</div>
                    </div>
                    <button class="btn-view"><i class="fas fa-eye"></i> View 3D Model</button>
                </div>
            </div>
        </div>
    </main>

    <script>
        const initBackground3D = () => {
            const container = document.getElementById('canvas-container');
            if (!container) return;
            const scene = new THREE.Scene();
            scene.background = new THREE.Color('#f6f7f2');
            const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
            camera.position.z = 8;
            const renderer = new THREE.WebGLRenderer({ antialias: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            container.appendChild(renderer.domElement);
            const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
            scene.add(ambientLight);
            const cityGroup = new THREE.Group();
            scene.add(cityGroup);
            const material = new THREE.MeshPhongMaterial({ color: 0x294033, transparent: true, opacity: 0.1 });
            for (let x = -10; x < 10; x++) {
                for (let z = -10; z < 10; z++) {
                    const height = Math.random() * 2 + 0.5;
                    const geometry = new THREE.BoxGeometry(1, height, 1);
                    const mesh = new THREE.Mesh(geometry, material);
                    mesh.position.set(x * 3, height / 2 - 2, z * 3);
                    cityGroup.add(mesh);
                }
            }
            const animate = () => {
                requestAnimationFrame(animate);
                cityGroup.rotation.y += 0.001;
                renderer.render(scene, camera);
            };
            animate();
        };
        if (typeof THREE !== 'undefined') initBackground3D();
    </script>
</body>
</html>
