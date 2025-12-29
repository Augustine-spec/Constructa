<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'homeowner') {
    header('Location: login.html');
    exit();
}
$username = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Material Market - Constructa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-color); color: var(--text-dark); min-height: 100vh; display: flex; flex-direction: column; }
        #canvas-container { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: -1; background: #f6f7f2; pointer-events: none; }
        header { padding: 1.5rem 3rem; display: flex; justify-content: space-between; align-items: center; max-width: 1600px; margin: 0 auto; width: 100%; background: rgba(246, 247, 242, 0.9); backdrop-filter: blur(10px); position: sticky; top: 0; z-index: 100; }
        .logo { display: flex; align-items: center; gap: 0.5rem; font-size: 1.25rem; font-weight: 700; color: var(--primary-green); text-decoration: none; }
        .logo i { font-size: 1.5rem; }
        nav { display: flex; gap: 2rem; align-items: center; }
        nav a { text-decoration: none; color: var(--text-dark); font-weight: 500; font-size: 0.95rem; transition: color 0.2s; }
        nav a:hover { color: var(--primary-green); }
        main { flex: 1; max-width: 1400px; margin: 0 auto; width: 100%; padding: 3rem; z-index: 2; }
        .page-header { text-align: center; margin-bottom: 3rem; animation: fadeInDown 0.8s ease-out; }
        .page-title { font-size: 3rem; font-weight: 700; margin-bottom: 0.5rem; background: linear-gradient(135deg, #294033 0%, #3d5a49 100%); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; }
        .page-subtitle { color: var(--text-gray); font-size: 1.1rem; }
        
        .filter-section { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border-radius: 16px; padding: 2rem; margin-bottom: 2rem; display: flex; gap: 1rem; flex-wrap: wrap; }
        .filter-btn { padding: 0.8rem 1.5rem; border-radius: 8px; border: 2px solid var(--primary-green); background: transparent; color: var(--primary-green); font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .filter-btn.active, .filter-btn:hover { background: var(--primary-green); color: white; }
        
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 2rem; }
        .product-card { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border-radius: 20px; padding: 2rem; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05); transition: all 0.4s; cursor: pointer; animation: fadeInUp 0.8s ease-out; }
        .product-card:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1); }
        .product-icon { width: 70px; height: 70px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: white; margin-bottom: 1.5rem; }
        .product-name { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--text-dark); }
        .product-desc { color: var(--text-gray); margin-bottom: 1rem; line-height: 1.6; }
        .product-price { font-size: 1.8rem; font-weight: 700; color: var(--primary-green); margin-bottom: 1rem; }
        .product-unit { font-size: 0.9rem; color: var(--text-gray); }
        .btn-add { width: 100%; background: linear-gradient(135deg, #294033 0%, #3d5a49 100%); color: white; padding: 1rem; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s; margin-top: 1rem; }
        .btn-add:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(41, 64, 51, 0.3); }
        
        .gradient-orange { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .gradient-blue { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .gradient-green { background: linear-gradient(135deg, #10b981, #059669); }
        .gradient-red { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .gradient-purple { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .gradient-pink { background: linear-gradient(135deg, #ec4899, #db2777); }
        
        @keyframes fadeInDown { from { opacity: 0; transform: translateY(-30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
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
            <h1 class="page-title"><i class="fas fa-shopping-basket"></i> Material Market</h1>
            <p class="page-subtitle">Order high-quality construction materials at best rates</p>
        </div>

        <div class="filter-section">
            <button class="filter-btn active" onclick="filterProducts('all')">All Materials</button>
            <button class="filter-btn" onclick="filterProducts('cement')">Cement & Concrete</button>
            <button class="filter-btn" onclick="filterProducts('steel')">Steel & Metal</button>
            <button class="filter-btn" onclick="filterProducts('bricks')">Bricks & Blocks</button>
            <button class="filter-btn" onclick="filterProducts('paint')">Paint & Finish</button>
        </div>

        <div class="products-grid" id="productsGrid">
            <!-- Product 1 -->
            <div class="product-card" data-category="cement">
                <div class="product-icon gradient-orange"><i class="fas fa-box"></i></div>
                <h3 class="product-name">Premium Cement</h3>
                <p class="product-desc">High-strength Portland cement, ideal for all construction purposes</p>
                <div class="product-price">$8.50 <span class="product-unit">/ bag</span></div>
                <button class="btn-add"><i class="fas fa-cart-plus"></i> Add to Cart</button>
            </div>

            <!-- Product 2 -->
            <div class="product-card" data-category="steel">
                <div class="product-icon gradient-blue"><i class="fas fa-bars"></i></div>
                <h3 class="product-name">Steel Rebar</h3>
                <p class="product-desc">High-grade reinforcement steel bars for structural strength</p>
                <div class="product-price">$850 <span class="product-unit">/ ton</span></div>
                <button class="btn-add"><i class="fas fa-cart-plus"></i> Add to Cart</button>
            </div>

            <!-- Product 3 -->
            <div class="product-card" data-category="bricks">
                <div class="product-icon gradient-red"><i class="fas fa-th-large"></i></div>
                <h3 class="product-name">Red Clay Bricks</h3>
                <p class="product-desc">Traditional red bricks with excellent durability and finish</p>
                <div class="product-price">$0.55 <span class="product-unit">/ piece</span></div>
                <button class="btn-add"><i class="fas fa-cart-plus"></i> Add to Cart</button>
            </div>

            <!-- Product 4 -->
            <div class="product-card" data-category="cement">
                <div class="product-icon gradient-green"><i class="fas fa-cubes"></i></div>
                <h3 class="product-name">Ready-Mix Concrete</h3>
                <p class="product-desc">Pre-mixed concrete for quick and reliable construction</p>
                <div class="product-price">$120 <span class="product-unit">/ cubic meter</span></div>
                <button class="btn-add"><i class="fas fa-cart-plus"></i> Add to Cart</button>
            </div>

            <!-- Product 5 -->
            <div class="product-card" data-category="paint">
                <div class="product-icon gradient-purple"><i class="fas fa-paint-roller"></i></div>
                <h3 class="product-name">Exterior Paint</h3>
                <p class="product-desc">Weather-resistant premium paint for outdoor surfaces</p>
                <div class="product-price">$45 <span class="product-unit">/ gallon</span></div>
                <button class="btn-add"><i class="fas fa-cart-plus"></i> Add to Cart</button>
            </div>

            <!-- Product 6 -->
            <div class="product-card" data-category="steel">
                <div class="product-icon gradient-pink"><i class="fas fa-ruler-combined"></i></div>
                <h3 class="product-name">Steel Beams</h3>
                <p class="product-desc">I-beams and H-beams for structural support</p>
                <div class="product-price">$950 <span class="product-unit">/ ton</span></div>
                <button class="btn-add"><i class="fas fa-cart-plus"></i> Add to Cart</button>
            </div>
        </div>
    </main>

    <script>
        function filterProducts(category) {
            const cards = document.querySelectorAll( '.product-card');
            const buttons = document.querySelectorAll('.filter-btn');
            
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            cards.forEach(card => {
                if (category === 'all' || card.dataset.category === category) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // 3D Background
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
            const edgeMaterial = new THREE.LineBasicMaterial({ color: 0x294033, transparent: true, opacity: 0.3 });
            for (let x = -10; x < 10; x++) {
                for (let z = -10; z < 10; z++) {
                    const height = Math.random() * 2 + 0.5;
                    const geometry = new THREE.BoxGeometry(1, height, 1);
                    const mesh = new THREE.Mesh(geometry, material);
                    mesh.position.set(x * 3, height / 2 - 2, z * 3);
                    const edges = new THREE.EdgesGeometry(geometry);
                    const line = new THREE.LineSegments(edges, edgeMaterial);
                    line.position.copy(mesh.position);
                    cityGroup.add(mesh);
                    cityGroup.add(line);
                }
            }
            const animate = () => {
                requestAnimationFrame(animate);
                cityGroup.rotation.y += 0.001;
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
    </script>
</body>
</html>
