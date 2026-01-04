<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

$username = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Builder';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Material Market | Constructa</title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Three.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>

    <style>
        :root {
            /* Light Theme - Constructa Standard */
            --bg-color: #f6f7f2;
            --bg-card: rgba(255, 255, 255, 0.8);
            --bg-card-hover: #ffffff;
            --primary: #294033; /* Constructa Green */
            --primary-light: #3d5a49;
            --accent: #d97706; /* Construction Yellow/Orange for highlights */
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --glass-border: 1px solid rgba(255, 255, 255, 0.6);
            --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* 3D Canvas Background */
        #canvas-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: -1;
            background: var(--bg-color);
        }

        /* Navbar */
        nav {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        /* ACTIONS */
        .cart-icon-wrapper {
            position: relative;
            cursor: pointer;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 50%;
            transition: all 0.2s;
            color: var(--primary);
            box-shadow: var(--shadow-sm);
        }

        .cart-icon-wrapper:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary);
        }

        .cart-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: var(--accent);
            color: white;
            font-weight: 700;
            font-size: 0.75rem;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transform: scale(0);
            transition: all 0.3s;
        }

        .cart-badge.visible {
            opacity: 1;
            transform: scale(1);
        }

        /* Layout */
        .marketplace-layout {
            display: grid;
            grid-template-columns: 1fr 350px;
            max-width: 1600px;
            margin: 0 auto;
            padding: 2rem;
            gap: 2rem;
        }

        @media (max-width: 1024px) {
            .marketplace-layout { grid-template-columns: 1fr; }
            .budget-panel-wrapper { display: none; } /* Simplified mobile for demo */
        }

        /* Typography */
        .market-title {
            font-size: 2.5rem;
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--primary);
        }

        .market-subtitle { color: var(--text-muted); font-size: 1.1rem; }

        /* Filters */
        .filters-container {
            display: flex;
            gap: 0.8rem;
            flex-wrap: wrap;
            align-items: center;
            background: white;
            padding: 1rem;
            border-radius: 16px;
            border: 1px solid var(--border);
            margin: 2rem 0;
            box-shadow: var(--shadow-sm);
        }

        .category-pill {
            padding: 0.6rem 1.25rem;
            border-radius: 50px;
            background: #f1f5f9;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .category-pill:hover {
            background: #e2e8f0;
            color: var(--primary);
        }

        .category-pill.active {
            background: var(--primary);
            color: white;
        }

        /* Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 1.5rem;
        }

        .product-card {
            background: var(--bg-card);
            border-radius: 16px;
            border: 1px solid white; /* Glassy highlight */
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            position: relative;
            transition: transform 0.4s ease, box-shadow 0.4s ease;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            backdrop-filter: blur(5px);
        }

        .product-card:hover {
            transform: translateY(-8px);
            background: var(--bg-card-hover);
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
            border-color: rgba(41, 64, 51, 0.1);
        }

        .card-image-wrapper {
            height: 160px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.5rem;
        }
        
        .product-img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 8px 12px rgba(0,0,0,0.15));
            transition: transform 0.3s;
        }

        .product-card:hover .product-img {
            transform: scale(1.08);
        }

        .product-category {
            font-size: 0.75rem;
            color: var(--accent);
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .product-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--primary);
        }

        .product-desc {
            font-size: 0.85rem;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 1rem;
            border-top: 1px solid #f1f5f9;
        }

        .price {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--primary);
        }

        .unit { font-size: 0.8rem; color: var(--text-muted); font-weight: 500; }

        .add-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: none;
            background: var(--primary);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            box-shadow: 0 4px 6px rgba(41, 64, 51, 0.2);
        }

        .add-btn:hover {
            background: var(--primary-light);
            transform: scale(1.1);
        }

        /* Budget Panel */
        .budget-panel-wrapper {
            position: sticky;
            top: 100px;
            height: fit-content;
        }

        .budget-panel {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
        }

        .panel-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .progress-track {
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .progress-bar {
            height: 100%;
            background: var(--primary);
            width: 0%;
            transition: width 1s ease;
        }

        .budget-display {
            text-align: center;
            margin-bottom: 2rem;
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px dashed #cbd5e1;
        }

        .budget-amount {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary);
            font-family: 'Space Grotesk', sans-serif;
        }

        .cart-items-preview {
            max-height: 300px;
            overflow-y: auto;
            border-top: 1px solid var(--border);
            padding-top: 1rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .cart-item {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .cart-item-img {
            width: 40px;
            height: 40px;
            object-fit: contain;
            background: #f1f5f9;
            border-radius: 6px;
            padding: 4px;
        }
        
        .cart-desc { flex: 1; }
        .cart-desc-title { font-weight: 600; font-size: 0.9rem; color: var(--primary); }
        .cart-desc-price { font-size: 0.8rem; color: var(--text-muted); }

        .checkout-btn {
            width: 100%;
            background: var(--primary);
            color: white;
            padding: 1rem;
            border-radius: 10px;
            border: none;
            font-weight: 700;
            margin-top: 1.5rem;
            cursor: pointer;
            transition: 0.2s;
        }

        .checkout-btn:hover {
            background: var(--primary-light);
            box-shadow: 0 4px 12px rgba(41, 64, 51, 0.25);
        }

        .flying-img {
            position: fixed;
            z-index: 9999;
            pointer-events: none;
            border-radius: 50%;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transition: all 0.8s cubic-bezier(0.19, 1, 0.22, 1);
        }
        
        /* Recommended */
        .rec-card {
            margin-top: 1rem;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 0.8rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            cursor: pointer;
        }
        .rec-card:hover { background: #dbeafe; }
        .rec-icon {
            color: #3b82f6;
            background: white;
            width: 32px; height: 32px;
            border-radius: 6px;
            display: flex; align-items: center; justify-content: center;
        }

    </style>
</head>
<body>
    <div id="canvas-container"></div>

    <nav>
        <a href="homeowner.php" class="logo">
            <i class="fas fa-cube"></i> Constructa
        </a>
        <div style="display:flex; gap:1.5rem; align-items:center;">
            <a href="homeowner.php" style="color:var(--text-muted); text-decoration:none; font-weight:500; font-size:0.95rem;">Back to Dashboard</a>
            <div class="cart-icon-wrapper" id="cartIcon">
                <i class="fas fa-shopping-cart"></i>
                <div class="cart-badge" id="cartCount">0</div>
            </div>
        </div>
    </nav>

    <div class="marketplace-layout">
        <!-- Main Application -->
        <div class="main-content">
            <div style="margin-bottom: 2rem;">
                <h1 class="market-title">Construction Materials</h1>
                <p class="market-subtitle">Sourced from top-tier suppliers for your dream project.</p>
            </div>

            <!-- Filters -->
            <div class="filters-container">
                <div class="category-pill active" onclick="filter('all', this)">All Materials</div>
                <div class="category-pill" onclick="filter('cement', this)">Cement</div>
                <div class="category-pill" onclick="filter('concrete', this)">Concrete</div>
                <div class="category-pill" onclick="filter('steel', this)">Steel</div>
                <div class="category-pill" onclick="filter('bricks', this)">Bricks</div>
                <div class="category-pill" onclick="filter('wood', this)">Wood & Timber</div>
                <div class="category-pill" onclick="filter('paint', this)">Paints</div>
            </div>

            <!-- Products Grid -->
            <div class="products-grid" id="productGrid">
                <!-- Items injected via JS -->
            </div>
        </div>

        <!-- Right Panel: Live Budget & Cart -->
        <div class="budget-panel-wrapper">
            <div class="budget-panel">
                <div class="panel-title"><i class="fas fa-calculator"></i> Live Budget Tracker</div>
                
                <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem; font-size:0.9rem; color:var(--text-muted);">
                    <span>Spent vs. Limit</span>
                    <span id="percentText">0%</span>
                </div>
                <div class="progress-track">
                    <div class="progress-bar" id="progressBar"></div>
                </div>

                <div class="budget-display">
                    <div class="budget-amount" id="totalAmount">₹0</div>
                    <div style="font-size:0.8rem; color:var(--text-muted);">Total Estimated Cost</div>
                </div>

                <div class="panel-title" style="margin-bottom:1rem; font-size:1rem;"><i class="fas fa-shopping-bag"></i> Cart Summary</div>
                <div class="cart-items-preview" id="cartList">
                    <div style="text-align:center; padding:1.5rem; color:var(--text-muted); font-size:0.9rem;">Your cart is empty</div>
                </div>

                <button class="checkout-btn">
                    Proceed to Checkout <i class="fas fa-arrow-right"></i>
                </button>
                
                <div style="margin-top:1.5rem;">
                    <h4 style="font-size:0.85rem; color:var(--text-muted); margin-bottom:0.5rem; text-transform:uppercase;">You might need</h4>
                    <div class="rec-card">
                        <div class="rec-icon"><i class="fas fa-hard-hat"></i></div>
                        <div>
                            <div style="font-weight:600; font-size:0.9rem;">Safety Kit</div>
                            <div style="font-size:0.75rem; color:var(--text-muted);">Essential for every site</div>
                        </div>
                        <i class="fas fa-plus" style="margin-left:auto; color:#94a3b8;"></i>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        // --- DATA ---
        const products = [
            { id: 1, name: 'UltraTech Cement', category: 'cement', price: 380, unit: 'bag', img: 'https://cdn-icons-png.flaticon.com/512/911/911388.png', desc: 'Premium Portland Pozzolana Cement' },
            { id: 2, name: 'Red Clay Bricks', category: 'bricks', price: 12, unit: 'pc', img: 'https://cdn-icons-png.flaticon.com/512/7594/7594380.png', desc: 'High strength, standard red bricks' },
            { id: 3, name: 'TMT Steel Bars', category: 'steel', price: 65, unit: 'kg', img: 'https://cdn-icons-png.flaticon.com/512/2558/2558162.png', desc: 'Fe-550 Grade TMT Bars for foundation' },
            { id: 4, name: 'Ready Mix Concrete', category: 'concrete', price: 4500, unit: 'm³', img: 'https://cdn-icons-png.flaticon.com/512/3405/3405523.png', desc: 'Pre-mixed concrete delivered to site' },
            { id: 5, name: 'Teak Wood Logs', category: 'wood', price: 2500, unit: 'cft', img: 'https://cdn-icons-png.flaticon.com/512/2829/2829924.png', desc: 'Grade A Burma Teak for frames' },
            { id: 6, name: 'Asian Paints Apex', category: 'paint', price: 4200, unit: 'bucket', img: 'https://cdn-icons-png.flaticon.com/512/2954/2954898.png', desc: 'Weather proof exterior emulsion (20L)' },
        ];

        // --- STATE ---
        let cart = [];
        let total = 0;

        // --- RENDER ---
        function renderProducts(filterCat = 'all') {
            const grid = document.getElementById('productGrid');
            grid.innerHTML = '';
            
            const filtered = filterCat === 'all' 
                ? products 
                : products.filter(p => p.category === filterCat);

            filtered.forEach(p => {
                const card = document.createElement('div');
                card.className = 'product-card';
                
                card.innerHTML = `
                    <div class="product-category">${p.category}</div>
                    <div class="card-image-wrapper">
                        <img src="${p.img}" alt="${p.name}" class="product-img" id="img-${p.id}">
                    </div>
                    <div class="product-title">${p.name}</div>
                    <div class="product-desc">${p.desc}</div>
                    <div class="product-meta">
                        <div>
                            <div class="price">₹${p.price}</div>
                            <div class="unit">per ${p.unit}</div>
                        </div>
                        <button class="add-btn" onclick="addToCart(${p.id})"><i class="fas fa-plus"></i></button>
                    </div>
                `;
                grid.appendChild(card);
            });
        }

        function filter(cat, btn) {
            document.querySelectorAll('.category-pill').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            renderProducts(cat);
        }

        function addToCart(id) {
            const p = products.find(i => i.id === id);
            cart.push(p);
            total += p.price;
            animateToCart(id);
            updateCartUI();
        }

        function updateCartUI() {
            // Money
            document.getElementById('totalAmount').innerText = '₹' + total.toLocaleString('en-IN');
            
            // Progress
            const budget = 200000; // Mock limit
            const pct = Math.min((total / budget) * 100, 100);
            document.getElementById('progressBar').style.width = pct + '%';
            document.getElementById('percentText').innerText = Math.round(pct) + '%';

            // Badge
            const badge = document.getElementById('cartCount');
            badge.innerText = cart.length;
            badge.classList.add('visible');

            // Cart List
            const list = document.getElementById('cartList');
            if (cart.length === 0) {
                list.innerHTML = '<div style="text-align:center;">Empty</div>';
            } else {
                list.innerHTML = '';
                // Count items
                const counts = {};
                cart.forEach(x => { counts[x.id] = (counts[x.id] || 0) + 1; });
                const unique = [...new Set(cart.map(x => x.id))];

                unique.forEach(uid => {
                    const item = products.find(x => x.id === uid);
                    const qty = counts[uid];
                    const div = document.createElement('div');
                    div.className = 'cart-item';
                    div.innerHTML = `
                        <img src="${item.img}" class="cart-item-img">
                        <div class="cart-desc">
                            <div class="cart-desc-title">${item.name}</div>
                            <div class="cart-desc-price">₹${item.price} x ${qty}</div>
                        </div>
                    `;
                    list.appendChild(div);
                });
            }
        }

        function animateToCart(id) {
            const img = document.getElementById(`img-${id}`);
            if(!img) return;
            const flyer = img.cloneNode(true);
            flyer.classList.add('flying-img');
            
            const rect = img.getBoundingClientRect();
            flyer.style.left = rect.left + 'px';
            flyer.style.top = rect.top + 'px';
            flyer.style.width = rect.width + 'px';
            flyer.style.height = rect.height + 'px';
            
            document.body.appendChild(flyer);
            
            const cartRect = document.getElementById('cartIcon').getBoundingClientRect();
            
            requestAnimationFrame(() => {
                flyer.style.left = (cartRect.left + 5) + 'px';
                flyer.style.top = (cartRect.top + 5) + 'px';
                flyer.style.width = '20px';
                flyer.style.height = '20px';
                flyer.style.opacity = '0';
            });
            setTimeout(() => flyer.remove(), 800);
        }

        renderProducts();

        // --- 3D WHITE BACKGROUND CONSISTENT WITH CALCULATOR ---
        const initBackground3D = () => {
            const container = document.getElementById('canvas-container');
            if (!container) return;

            const scene = new THREE.Scene();
            // Original Light Theme Background Color
            scene.background = new THREE.Color('#f6f7f2');

            // Camera interaction
            const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
            camera.position.z = 8;
            camera.position.y = 2;

            const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            container.appendChild(renderer.domElement);

            // Light Theme Lighting
            const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
            scene.add(ambientLight);

            const mainLight = new THREE.DirectionalLight(0xffffff, 0.8);
            mainLight.position.set(10, 10, 10);
            scene.add(mainLight);

            // Constructa Green Accent Light
            const greenLight = new THREE.PointLight(0x294033, 0.5);
            greenLight.position.set(-5, 5, 5);
            scene.add(greenLight);

            // City Group
            const cityGroup = new THREE.Group();
            scene.add(cityGroup);

            // Standard Wireframe / Glassy Green Materials
            const buildingMaterial = new THREE.MeshPhongMaterial({
                color: 0x294033,
                transparent: true,
                opacity: 0.05, // Very subtle on light bg
                side: THREE.DoubleSide
            });
            const edgeMaterial = new THREE.LineBasicMaterial({ color: 0x294033, transparent: true, opacity: 0.2 });

            const gridSize = 12;
            const spacing = 3;

            for (let x = -gridSize; x < gridSize; x++) {
                for (let z = -gridSize; z < gridSize; z++) {
                    const height = Math.random() * 2 + 0.5;
                    const loopGroup = new THREE.Group();

                    const geometry = new THREE.BoxGeometry(1, height, 1);
                    const mesh = new THREE.Mesh(geometry, buildingMaterial);
                    mesh.position.y = height / 2;

                    const edges = new THREE.EdgesGeometry(geometry);
                    const line = new THREE.LineSegments(edges, edgeMaterial);
                    line.position.y = height / 2;

                    loopGroup.add(mesh);
                    loopGroup.add(line);
                    loopGroup.position.set(x * spacing, -2, z * spacing);
                    cityGroup.add(loopGroup);
                }
            }

            // Central Isometric House (Hero for this page)
            const houseGroup = new THREE.Group();
            // Simple structure
            const baseGeo = new THREE.BoxGeometry(2, 2, 2);
            const baseEdges = new THREE.EdgesGeometry(baseGeo);
            const baseLine = new THREE.LineSegments(baseEdges, new THREE.LineBasicMaterial({ color: 0x294033, linewidth: 2 }));
            houseGroup.add(baseLine);
            const roofGeo = new THREE.ConeGeometry(1.5, 1, 4);
            const roofEdges = new THREE.EdgesGeometry(roofGeo);
            const roofLine = new THREE.LineSegments(roofEdges, new THREE.LineBasicMaterial({ color: 0x294033, linewidth: 2 }));
            roofLine.position.y = 1.5; 
            roofLine.rotation.y = Math.PI / 4;
            houseGroup.add(roofLine);
            
            const floatGroup = new THREE.Group();
            floatGroup.add(houseGroup);
            floatGroup.position.set(0, 0, 3);
            scene.add(floatGroup);

            // Animation
            let mouseX = 0; 
            let mouseY = 0;
            document.addEventListener('mousemove', (e) => {
                mouseX = (e.clientX - window.innerWidth / 2) * 0.0005;
                mouseY = (e.clientY - window.innerHeight / 2) * 0.0005;
            });

            const animate = () => {
                requestAnimationFrame(animate);
                
                cityGroup.rotation.y += 0.001;
                floatGroup.position.y = Math.sin(Date.now() * 0.001) * 0.3 + 0.5;
                floatGroup.rotation.y += 0.005;

                // Interactive tilt
                cityGroup.rotation.x += (mouseY - cityGroup.rotation.x) * 0.05;
                cityGroup.rotation.y += (mouseX - cityGroup.rotation.y) * 0.05;

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
