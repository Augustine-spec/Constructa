<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'homeowner') { header('Location: login.html'); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Favorites - Constructa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <style>
        :root {
            --primary: #294033;
            --primary-light: #3d5a49;
            --accent: #eab308;
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        
        body {
            background-color: transparent; /* For 3D canvas */
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* 3D Background */
        #canvas-container {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            z-index: -1; background: #f8fafc; pointer-events: none;
        }

        /* Nav */
        header {
            padding: 1.5rem 3rem;
            display: flex; justify-content: space-between; align-items: center;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255,255,255,0.3);
            position: sticky; top: 0; z-index: 100;
        }
        .logo { font-size: 1.5rem; font-weight: 800; color: var(--primary); text-decoration: none; display:flex; align-items:center; gap:0.5rem; }
        .nav-link { color: var(--text-muted); text-decoration: none; font-weight: 500; transition: color 0.3s; }
        .nav-link:hover { color: var(--primary); }

        /* Main Content */
        main { flex: 1; max-width: 1400px; margin: 0 auto; width: 100%; padding: 3rem; }

        /* Animated Title */
        .page-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            width: 100%;
            margin-bottom: 4rem;
            position: relative;
        }

        .page-title { 
            font-size: 3.5rem; font-weight: 800; color: var(--primary); margin-bottom: 0.5rem; 
            letter-spacing: -1px; display: inline-block; perspective: 1000px;
        }
        
        .letter {
            display: inline-block;
            transform-origin: bottom center;
            animation: wave 1s ease-in-out forwards;
            opacity: 0;
            transform: rotateX(90deg) translateY(50px);
        }

        @keyframes wave {
            0% { opacity: 0; transform: rotateX(90deg) translateY(50px); }
            50% { transform: rotateX(-20deg) translateY(-10px); }
            100% { opacity: 1; transform: rotateX(0deg) translateY(0); }
        }

        /* Tabs */
        .tabs-container {
            display: flex; justify-content: center; gap: 2rem; margin-bottom: 3rem;
            position: relative;
        }
        .tab-btn {
            background: none; border: none; padding: 0.8rem 1.5rem;
            font-size: 1.1rem; font-weight: 600; color: var(--text-muted);
            cursor: pointer; position: relative; transition: all 0.3s;
            outline: none; /* Remove focus ring */
        }
        .tab-btn:focus, .tab-btn:active { outline: none; border: none; }
        .tab-btn.active { color: var(--primary); }
        .tab-btn::after {
            content: ''; position: absolute; bottom: -5px; left: 0; width: 0; height: 3px;
            background: var(--accent); transition: width 0.3s ease; border-radius: 2px;
        }
        .tab-btn.active::after { width: 100%; }
        .tab-btn:hover { color: var(--primary); }

        /* Grid */
        .favorites-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 2.5rem;
        }

        /* 3D Card */
        .card-perspective {
            perspective: 1200px;
        }
        
        .fav-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.6);
            border-radius: 24px;
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            transition: all 0.5s cubic-bezier(0.23, 1, 0.32, 1);
            transform-style: preserve-3d;
            position: relative;
            cursor: pointer;
            height: 100%; display: flex; flex-direction: column;
        }
        
        .fav-card:hover {
            transform: translateY(-10px) rotateX(2deg) rotateY(-2deg);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            background: rgba(255, 255, 255, 0.95);
        }

        /* Image Pop Effect */
        .img-wrapper {
            width: 100%; height: 220px;
            border-radius: 16px; overflow: hidden;
            margin-bottom: 1.5rem;
            transform: translateZ(20px); /* 3D Pop */
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            transition: transform 0.5s ease;
        }
        .fav-card:hover .img-wrapper { transform: translateZ(40px) scale(1.05); }
        .card-img { width: 100%; height: 100%; object-fit: cover; }
        
        .card-content { transform: translateZ(10px); flex: 1; display: flex; flex-direction: column; }
        
        .card-type {
            font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;
            color: var(--accent); font-weight: 700; margin-bottom: 0.5rem;
        }
        
        .card-title {
            font-size: 1.4rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.5rem;
            line-height: 1.2;
        }
        
        .card-desc {
            font-size: 0.95rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 1.5rem;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }

        .meta-tags { display: flex; gap: 0.8rem; flex-wrap: wrap; margin-bottom: 1.5rem; transform: translateZ(40px); } /* Extra pop for badges */
        .tag {
            background: #f1f5f9; padding: 0.3rem 0.8rem; border-radius: 20px;
            font-size: 0.8rem; font-weight: 600; color: var(--text-muted);
            display: flex; align-items: center; gap: 0.4rem;
        }
        
        /* Action Buttons */
        .card-actions {
            margin-top: auto;
            display: flex; gap: 1rem;
            transform: translateZ(10px);
        }
        .btn-view {
            flex: 1; padding: 0.8rem 1.2rem; border-radius: 12px; border: none;
            background: linear-gradient(135deg, #294033 0%, #3d5a49 100%);
            color: white; font-weight: 600; cursor: pointer;
            box-shadow: 0 4px 6px -1px rgba(41, 64, 51, 0.3), 0 2px 4px -1px rgba(41, 64, 51, 0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex; align-items: center; justify-content: center; gap: 0.6rem;
            position: relative; overflow: hidden;
        }
        
        .btn-view:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(41, 64, 51, 0.4), 0 4px 6px -2px rgba(41, 64, 51, 0.2);
            /* Gentle glow */
            filter: brightness(1.1);
        }

        .btn-view:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(41, 64, 51, 0.2) inset;
        }

        .btn-view i { transition: transform 0.3s ease; }
        .btn-view:hover i { transform: translateX(4px); }
        
        .btn-delete {
            width: 45px; height: 45px; border-radius: 12px; border: 1px solid #fee2e2;
            background: #fef2f2; color: #ef4444; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.3s;
        }
        .btn-delete:hover { background: #ef4444; color: white; transform: rotate(90deg); }

        /* Animations */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .stagger-enter { animation: fadeUp 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; opacity: 0; }
        
        .exit-anim {
            animation: fadeOutRotate 0.5s ease forwards;
        }
        @keyframes fadeOutRotate {
            to { opacity: 0; transform: scale(0.8) rotate(-5deg); }
        }

        /* Loading */
        .spinner { font-size: 2rem; color: var(--primary); animation: spin 1s linear infinite; }
        @keyframes spin { 100% { transform: rotate(360deg); } }

    </style>
</head>
<body>
    <div id="canvas-container"></div>
    
    <header>
        <a href="homeowner.php" class="logo"><i class="fas fa-cube"></i> Constructa</a>
        <nav>
            <a href="homeowner.php" class="nav-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </nav>
    </header>

    <main>
        <div class="page-header">
            <h1 class="page-title">Saved Favorites</h1>
            <p class="page-subtitle">Your curated collection of designs, materials, and professionals.</p>
        </div>

        <!-- Tabs Removed -->

        <div class="favorites-grid" id="favoritesContainer">
            <!-- Dynamic Content -->
            <div style="grid-column:1/-1; text-align:center; padding:5rem;">
                <i class="fas fa-circle-notch spinner"></i>
            </div>
        </div>
    </main>

    <script>
        // ----------------------------------------------------
        // 1. 3D BACKGROUND (Exact Match from Project Status)
        // ----------------------------------------------------
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
        
        if (typeof THREE !== 'undefined') initBackground3D();


        // ----------------------------------------------------
        // 2. FAVORITES LOGIC
        // ----------------------------------------------------
        document.addEventListener('DOMContentLoaded', () => {
            // Animate Title
            const title = document.querySelector('.page-title');
            if(title) {
                const text = title.textContent;
                title.textContent = '';
                [...text].forEach((char, i) => {
                    const span = document.createElement('span');
                    span.textContent = char === ' ' ? '\u00A0' : char;
                    span.className = 'letter';
                    span.style.animationDelay = `${i * 0.1}s`; // Staggered delay
                    title.appendChild(span);
                });
            }

            loadFavorites('all');
        });

        async function loadFavorites(type, btn = null) {
            if(btn) {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            }

            const container = document.getElementById('favoritesContainer');
            container.innerHTML = `<div style="grid-column:1/-1; text-align:center; padding:5rem;"><i class="fas fa-circle-notch spinner"></i></div>`;

            try {
                const response = await fetch(`backend/save_favorite.php?type=${type}`);
                const text = await response.text(); // Get raw text for debugging

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${text.substring(0, 100)}`);
                }
                
                let result;
                try {
                    result = JSON.parse(text);
                } catch(e) {
                    console.error("JSON Parse Error. Raw response:", text);
                    throw new Error("Invalid server response. Check console for details.");
                }

                if (result.status === 'success') {
                    renderFavorites(result.data);
                } else {
                    container.innerHTML = `<div style="grid-column:1/-1; text-align:center; color:#ef4444; font-weight:600;">${result.message}</div>`;
                }
            } catch (error) {
                console.error(error);
                container.innerHTML = `<div style="grid-column:1/-1; text-align:center; color:#ef4444; padding:2rem;">
                    <i class="fas fa-exclamation-triangle" style="font-size:2rem; margin-bottom:1rem;"></i><br>
                    ${error.message}<br>
                    <button onclick="loadFavorites('${type}')" style="margin-top:1rem; padding:0.5rem 1rem; background:var(--primary); color:white; border:none; border-radius:8px; cursor:pointer;">Retry</button>
                </div>`;
            }
        }

        function renderFavorites(items) {
            const container = document.getElementById('favoritesContainer');
            container.innerHTML = '';

            if (items.length === 0) {
                container.innerHTML = `
                    <div style="grid-column:1/-1; text-align:center; padding:5rem; opacity:0.6;">
                        <i class="far fa-folder-open" style="font-size:4rem; margin-bottom:1rem; color:var(--primary);"></i>
                        <p style="font-size:1.2rem;">Your collection is empty.</p>
                    </div>`;
                return;
            }

            items.forEach((item, index) => {
                // UI Config based on type
                let icon = 'fa-cube';
                let typeName = 'Item';
                let cta = 'View Details';
                let ctaIcon = 'fa-arrow-right';

                if(item.item_type === 'design') { icon='fa-drafting-compass'; typeName='Design Project'; cta='Explore Plan'; }
                if(item.item_type === 'material') { icon='fa-cubes'; typeName='Material'; cta='Purchase'; ctaIcon='fa-shopping-cart'; }
                if(item.item_type === 'professional') { icon='fa-user-tie'; typeName='Professional'; cta='Contact'; ctaIcon='fa-phone'; }

                // Image Handling
                let imageHtml = `<div class="img-wrapper" style="background:#e2e8f0; display:flex; align-items:center; justify-content:center; color:var(--text-muted);">
                                    <i class="fas ${icon}" style="font-size:3rem;"></i>
                                 </div>`;
                
                if(item.image_url && item.image_url.includes('uploads/')) {
                    imageHtml = `<div class="img-wrapper">
                                    <img src="${item.image_url}" class="card-img" alt="${item.title}">
                                 </div>`;
                }

                // Tags Generation
                let tagsHtml = '';
                if(item.meta_info) {
                    if(item.meta_info.area) tagsHtml += `<div class="tag"><i class="fas fa-ruler-combined"></i> ${item.meta_info.area} sqft</div>`;
                    if(item.meta_info.cost) tagsHtml += `<div class="tag"><i class="fas fa-wallet"></i> ₹${(item.meta_info.cost/100000).toFixed(1)}L</div>`;
                }

                // Action Button Logic
                const viewAction = `window.location.href='plan_details.php?id=${item.item_id}&type=${item.item_type}'`;
                
                // Define delay for animation
                const delay = index * 0.1;

                const card = `
                <div class="card-perspective stagger-enter" style="animation-delay: ${delay}s" id="fav-card-${item.id}">
                    <div class="fav-card">
                        ${imageHtml}
                        
                        <div class="card-content">
                            <div class="card-type">${typeName}</div>
                            <h3 class="card-title">${item.title}</h3>
                            <p class="card-desc">${item.description || 'No description available for this verified item.'}</p>
                            
                            <div class="meta-tags">
                                ${tagsHtml}
                            </div>

                            <div class="card-actions">
                                <button class="btn-view" onclick="${viewAction}">
                                    ${cta} <i class="fas ${ctaIcon}"></i>
                                </button>
                                <button class="btn-delete" onclick="removeFavoriteItem(${item.user_id}, '${item.item_id}', '${item.item_type}', ${item.id})" title="Remove">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                `;
                container.innerHTML += card;
            });
        }

        async function removeFavoriteItem(userId, itemId, itemType, dbId) {
            const cardWrapper = document.getElementById(`fav-card-${dbId}`);
            if(!cardWrapper) return;

            // Optimistic UI Removal start
            // Add exit animation class
            cardWrapper.classList.add('exit-anim');
            
            // Wait for animation, then delete from server
            setTimeout(async () => {
                try {
                    const payload = { 
                        item_id: itemId, 
                        item_type: itemType, 
                        title: 'remove_action' // Signal backend logic
                    };
                    
                    const response = await fetch('backend/save_favorite.php', {
                        method: 'POST',
                        body: JSON.stringify(payload)
                    });
                    
                    const res = await response.json();
                    if(res.status === 'removed') {
                        cardWrapper.remove();
                        // If empty, show empty state
                        if(document.querySelectorAll('.card-perspective').length === 0) {
                            renderFavorites([]);
                        }
                    } else {
                        // Revert if failed (rare)
                        cardWrapper.classList.remove('exit-anim');
                        alert('Could not remove item.');
                    }
                } catch(e) {
                    console.error(e);
                    cardWrapper.classList.remove('exit-anim');
                }
            }, 400); // 400ms match CSS animation
        }

    </script>
</body>
</html>
