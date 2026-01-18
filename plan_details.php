<?php
session_start();
// A simple check for user session
if (!isset($_SESSION['user_id'])) { header('Location: login.html'); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plan Details - Constructa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="js/plans_data.js"></script> <!-- Shared Plans Data -->
    <style>
        :root {
            --primary: #294033;
            --primary-light: #3d5a49;
            --accent: #d4af37;
            --bg-color: #f8fafc;
            --text-main: #1e293b;
            --text-muted: #64748b;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        
        body { background: var(--bg-color); color: var(--text-main); min-height: 100vh; overflow-x: hidden; }

        /* 3D Background */
        #canvas-container {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            z-index: -1; background: #f8fafc; pointer-events: none;
        }
        
        /* Navbar (Simple) */
        nav {
            padding: 1.5rem 3rem;
            display: flex; justify-content: space-between; align-items: center;
            background: rgba(255,255,255,0.8); backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0,0,0,0.05); position: sticky; top:0; z-index:100;
        }
        .logo { font-size: 1.5rem; font-weight: 800; color: var(--primary); text-decoration: none; }
        .back-link { font-weight: 600; color: var(--text-muted); text-decoration: none; display: flex; align-items: center; gap: 0.5rem; transition: color 0.3s; }
        .back-link:hover { color: var(--primary); }

        /* Layout */
        .container { max-width: 1200px; margin: 3rem auto; padding: 0 2rem; display: grid; grid-template-columns: 1.5fr 1fr; gap: 3rem; }
        
        /* Left Column: Visuals */
        .gallery-section { display: flex; flex-direction: column; gap: 1.5rem; }
        .main-image-frame {
            width: 100%; height: 400px; border-radius: 24px; overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1); border: 4px solid white;
            position: relative;
        }
        .main-image { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s; }
        .main-image-frame:hover .main-image { transform: scale(1.05); }

        .thumbnails { display: flex; gap: 1rem; overflow-x: auto; padding-bottom: 1rem; }
        .thumb { 
            width: 100px; height: 80px; border-radius: 12px; cursor: pointer; 
            object-fit:cover; border: 2px solid transparent; transition: all 0.2s;
        }
        .thumb:hover, .thumb.active { border-color: var(--primary); transform: translateY(-2px); }

        /* Right Column: Details */
        .details-card {
            background: rgba(255,255,255,0.9); backdrop-filter: blur(20px);
            border-radius: 24px; padding: 2.5rem; box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border: 1px solid rgba(255,255,255,0.5);
            display: flex; flex-direction: column; height: fit-content;
        }

        .category-pill {
            display: inline-block; background: #e0f2fe; color: #0284c7; 
            padding: 0.4rem 1rem; border-radius: 20px; font-weight: 700; font-size: 0.8rem;
            text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 1rem; width: fit-content;
        }

        .plan-title { font-size: 2.5rem; font-weight: 800; color: var(--primary); line-height: 1.1; margin-bottom: 1.5rem; }
        
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem; }
        .stat-item { background: #f1f5f9; padding: 1rem; border-radius: 16px; }
        .stat-label { font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.3rem; }
        .stat-value { font-size: 1.2rem; font-weight: 700; color: var(--text-main); }

        .price-tag { 
            font-size: 2rem; font-weight: 800; color: var(--primary); 
            display: flex; align-items: baseline; gap: 0.5rem; margin-bottom: 0.5rem;
        }
        .price-label { font-size: 0.9rem; font-weight: 500; color: var(--text-muted); }

        .action-buttons { display: flex; flex-direction: column; gap: 1rem; margin-top: 2rem; }
        
        .btn-main {
            padding: 1rem; border-radius: 12px; border: none; cursor: pointer;
            font-size: 1rem; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 0.8rem;
            transition: all 0.3s;
        }
        .btn-est { 
            background: linear-gradient(135deg, #294033 0%, #3d5a49 100%); color: white;
            box-shadow: 0 4px 15px rgba(41, 64, 51, 0.3);
        }
        .btn-est:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(41, 64, 51, 0.4); }

        .btn-secondary { background: white; border: 1px solid #e2e8f0; color: var(--text-main); }
        .btn-secondary:hover { background: #f8fafc; border-color: #cbd5e1; }

        .love-btn {
            background: #fee2e2; color: #ef4444; border: 1px solid #fecaca;
        }
        .love-btn:hover { background: #ef4444; color: white; }

        /* Loader */
        #loader { position: fixed; inset:0; background:white; display:flex; justify-content:center; align-items:center; z-index:999; }
        
        @keyframes fadeUp { from {opacity:0; transform:translateY(20px);} to {opacity:1; transform:translateY(0);} }
        .fade-in { animation: fadeUp 0.6s ease-out forwards; }

    </style>
</head>
<body>
    <div id="loader"><i class="fas fa-circle-notch fa-spin" style="font-size:3rem; color:var(--primary);"></i></div>
    <div id="canvas-container"></div>

    <nav>
        <a href="homeowner.php" class="logo"><i class="fas fa-cube"></i> Constructa</a>
        <a href="saved_favorites.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Favorites</a>
    </nav>

    <div class="container fade-in">
        <!-- Visuals -->
        <div class="gallery-section">
            <div class="main-image-frame">
                <img id="mainImg" src="" alt="House Plan" class="main-image">
            </div>
            <div class="thumbnails" id="thumbContainer">
                <!-- Injected via JS -->
            </div>
            
            <div class="details-card" style="margin-top:1rem;">
                <h3 style="font-size:1.2rem; margin-bottom:1rem; color:var(--primary);">Engineer's Analysis</h3>
                <ul id="reasoningList" style="list-style:none; display:flex; flex-direction:column; gap:0.8rem;">
                    <!-- Injected -->
                </ul>
            </div>
        </div>

        <!-- Info -->
        <div class="details-card">
            <div class="category-pill" id="planStyle">Modern</div>
            <div style="display:flex; justify-content:space-between; align-items:start;">
                <h1 class="plan-title" id="planTitle">Loading...</h1>
                <button class="btn-main love-btn" style="width:50px; padding:0;" title="Remove from Favorites" onclick="toggleFav()">
                    <i class="fas fa-heart"></i>
                </button>
            </div>
            
            <p style="color:var(--text-muted); line-height:1.6; margin-bottom:2rem;">
                This engineer-verified architectural plan offers a perfect balance of aesthetics and structural integrity. 
                Designed for optimal space utilization and natural lighting.
            </p>

            <div class="price-tag">
                <span id="planCost">₹0</span> 
                <span class="price-label">Est. Construction Cost</span>
            </div>

            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-label"><i class="fas fa-vector-square"></i> Area</div>
                    <div class="stat-value"><span id="planArea">0</span> sqft</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label"><i class="fas fa-layer-group"></i> Floors</div>
                    <div class="stat-value" id="planFloors">G + 1</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label"><i class="fas fa-bed"></i> Bedrooms</div>
                    <div class="stat-value" id="planBeds">3 BHK</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label"><i class="fas fa-check-circle"></i> Vastu</div>
                    <div class="stat-value" id="planVastu">Compliant</div>
                </div>
            </div>

            <div class="action-buttons">
                <button class="btn-main btn-est" onclick="window.location.href='budget_calculator.php'">
                    <i class="fas fa-calculator"></i> Proceed to Estimate
                </button>
            </div>
        </div>
    </div>

    <script>
        // Use the shared data
        // Check URL Params
        const urlParams = new URLSearchParams(window.location.search);
        const planId = parseInt(urlParams.get('id'));

        document.addEventListener('DOMContentLoaded', () => {
             // Find Plan
             const plan = plans.find(p => p.id === planId);
             
             if(plan) {
                 renderPage(plan);
             } else {
                 document.querySelector('.container').innerHTML = '<h2 style="text-align:center; padding:5rem;">Plan Not Found</h2>';
             }
             
             // Remove loader
             setTimeout(() => document.getElementById('loader').style.display = 'none', 500);

             // Init 3D BG (Same logic)
             if(typeof initBackground3D === 'function') initBackground3D();
        });

        function renderPage(plan) {
            document.getElementById('planTitle').textContent = plan.title;
            document.getElementById('planStyle').textContent = plan.style + ' Design';
            document.getElementById('planCost').textContent = '₹' + (plan.baseCost / 100000).toFixed(1) + ' Lakh';
            document.getElementById('planArea').textContent = plan.area;
            document.getElementById('planFloors').textContent = plan.floors === 1 ? 'Ground Only' : `G + ${plan.floors - 1}`;
            document.getElementById('planBeds').textContent = (plan.beds || 3) + ' BHK';
            document.getElementById('planVastu').textContent = plan.style === 'Vastu' ? '100% Score' : 'Standard';

            document.getElementById('mainImg').src = plan.builtImage || plan.planImage;
            
            // Thumbnails
            const thumbContainer = document.getElementById('thumbContainer');
            if(plan.gallery && plan.gallery.length > 0) {
                plan.gallery.forEach((img, idx) => {
                    const t = document.createElement('img');
                    t.src = img;
                    t.className = `thumb ${idx===0 ? 'active' : ''}`;
                    t.onclick = () => {
                        document.getElementById('mainImg').src = img;
                        document.querySelectorAll('.thumb').forEach(el => el.classList.remove('active'));
                        t.classList.add('active');
                    };
                    thumbContainer.appendChild(t);
                });
            }

            // Reasoning
            const rList = document.getElementById('reasoningList');
            if(plan.reasoning) {
                plan.reasoning.forEach(r => {
                    rList.innerHTML += `<li style="display:flex; gap:0.5rem; align-items:center; color:var(--text-muted);"><i class="fas fa-check" style="color:#10b981;"></i> ${r}</li>`;
                });
            }
        }

        // Toggle Favorite (Stub)
        function toggleFav() {
            if(confirm('Remove this plan from your favorites?')) {
                // Call backend to remove
                alert('Plan removed.');
                window.location.href = 'saved_favorites.php';
            }
        }

        // 3D Background Logic
        const initBackground3D = () => {
            const container = document.getElementById('canvas-container');
            if (!container) return;

            const scene = new THREE.Scene();
            scene.background = new THREE.Color('#f8fafc');

            const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
            camera.position.z = 8;
            camera.position.y = 2;

            const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            container.appendChild(renderer.domElement);

            // Lighting
            const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
            scene.add(ambientLight);
            const mainLight = new THREE.DirectionalLight(0xffffff, 0.8);
            mainLight.position.set(10, 10, 10);
            scene.add(mainLight);
            const blueLight = new THREE.PointLight(0x3d5a49, 0.5);
            blueLight.position.set(-5, 5, 5);
            scene.add(blueLight);

            // Objects
            const cityGroup = new THREE.Group();
            scene.add(cityGroup);

            const buildingGeometry = new THREE.BoxGeometry(1, 1, 1);
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

            // Hero House (Central Floating Object)
            const houseGroup = new THREE.Group();
            const baseGeo = new THREE.BoxGeometry(2, 2, 2);
            const baseEdges = new THREE.EdgesGeometry(baseGeo);
            const baseLine = new THREE.LineSegments(baseEdges, new THREE.LineBasicMaterial({ color: 0x294033, linewidth: 2 }));
            houseGroup.add(baseLine);
            const roofGeo = new THREE.ConeGeometry(1.5, 1.2, 4);
            const roofEdges = new THREE.EdgesGeometry(roofGeo);
            const roofLine = new THREE.LineSegments(roofEdges, new THREE.LineBasicMaterial({ color: 0x3d5a49, linewidth: 2 }));
            roofLine.position.y = 1.6;
            roofLine.rotation.y = Math.PI / 4;
            houseGroup.add(roofLine);

            const floatGroup = new THREE.Group();
            floatGroup.add(houseGroup);
            floatGroup.position.set(0, 0, 2);
            scene.add(floatGroup);

            // Animation
            let mouseX = 0, mouseY = 0;
            document.addEventListener('mousemove', (event) => {
                mouseX = (event.clientX - window.innerWidth / 2) * 0.001;
                mouseY = (event.clientY - window.innerHeight / 2) * 0.001;
            });

            const animate = () => {
                requestAnimationFrame(animate);
                cityGroup.rotation.y += 0.001;
                floatGroup.rotation.y += 0.005;
                floatGroup.position.y = Math.sin(Date.now() * 0.001) * 0.5 + 0.5;
                
                // Interactive tilt
                cityGroup.rotation.x += 0.05 * (mouseY - cityGroup.rotation.x);
                cityGroup.rotation.y += 0.05 * (mouseX - cityGroup.rotation.y);

                renderer.render(scene, camera);
            };
            animate();

            window.addEventListener('resize', () => {
                camera.aspect = window.innerWidth / window.innerHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(window.innerWidth, window.innerHeight);
            });
        };
    </script>
</body>
</html>
