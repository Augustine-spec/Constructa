<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Material Procurement & Tracking - Constructa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <style>
        :root {
            --primary: #294033;
            --primary-light: #3d5a49;
            --accent: #eab308;
            --bg-color: #f8fafc;
            --card-glass: rgba(255, 255, 255, 0.7);
            --text-main: #1e293b;
            --text-muted: #64748b;
            --success: #10b981;
            --info: #3b82f6;
            --warning: #f59e0b;
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
        
        .top-nav-btn {
            padding: 0.6rem 1.2rem;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 8px;
            text-decoration: none;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
            color: var(--text-main);
            font-weight: 600;
            transition: all 0.3s;
            display: flex; align-items: center; gap: 0.5rem;
        }
        .top-nav-btn:hover { background: var(--primary); color: white; transform: translateY(-2px); }

        /* Main Content */
        main { flex: 1; max-width: 1400px; margin: 0 auto; width: 100%; padding: 3rem; }

        .dashboard-header {
            margin-bottom: 3rem;
            text-align: center;
        }
        .page-title { 
            font-size: 3rem; font-weight: 800; color: var(--primary); margin-bottom: 0.5rem; 
            letter-spacing: -1px;
        }
        .page-subtitle { font-size: 1.1rem; color: var(--text-muted); }

        /* Project Card */
        .project-summary-card {
            background: var(--card-glass);
            backdrop-filter: blur(12px);
            border: 1px solid white;
            border-radius: 24px;
            padding: 2rem;
            margin-bottom: 3rem;
            box-shadow: 0 10px 30px -5px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transform: translateZ(0);
        }

        .project-info h2 { font-size: 1.8rem; margin-bottom: 0.5rem; color: var(--primary); }
        .stat-group { display: flex; gap: 2rem; }
        .stat-item { text-align: center; }
        .stat-value { font-size: 1.5rem; font-weight: 800; color: var(--primary); }
        .stat-label { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); }

        /* Material Wall */
        .material-wall {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }

        .material-track-card {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.5);
            border-radius: 20px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .material-track-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 20px 40px -5px rgba(0,0,0,0.08);
        }

        .m-header {
            display: flex; justify-content: space-between; align-items: start;
            margin-bottom: 1rem;
        }
        .m-icon {
            width: 50px; height: 50px; background: #e0e7ff; color: #4338ca;
            border-radius: 12px; display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; margin-right: 1rem;
        }
        .m-title { font-size: 1.2rem; font-weight: 700; color: var(--primary); margin-bottom: 0.2rem; }
        .m-qty { font-size: 0.9rem; color: var(--text-muted); font-weight: 600; }
        
        .status-badge {
            padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        .status-requested { background: #fef3c7; color: #d97706; }
        .status-approved { background: #dbeafe; color: #2563eb; }
        .status-site { background: #dcfce7; color: #166534; }

        /* Journey Timeline */
        .journey-timeline {
            margin-top: 1.5rem;
            display: flex;
            justify-content: space-between;
            position: relative;
        }
        .journey-line {
            position: absolute; top: 10px; left: 0; width: 100%; height: 2px;
            background: #e2e8f0; z-index: 0;
        }
        .journey-progress {
            position: absolute; top: 10px; left: 0; width: 0%; height: 2px;
            background: var(--success); z-index: 0; transition: width 1s ease;
        }

        .j-step {
            position: relative; z-index: 1;
            display: flex; flex-direction: column; align-items: center; gap: 0.5rem;
            width: 20px;
        }
        .j-dot {
            width: 20px; height: 20px; border-radius: 50%; background: #ffffff; border: 2px solid #cbd5e1;
            transition: all 0.3s;
        }
        .j-step.completed .j-dot { background: var(--success); border-color: var(--success); }
        .j-step.current .j-dot { background: white; border-color: var(--accent); box-shadow: 0 0 0 4px rgba(234, 179, 8, 0.2); }
        
        .j-label {
            font-size: 0.65rem; color: var(--text-muted); white-space: nowrap; font-weight: 600;
            position: absolute; top: 25px; opacity: 0; transition: opacity 0.3s;
        }
        .j-step:hover .j-label, .j-step.current .j-label { opacity: 1; }

        .card-actions {
            margin-top: 2rem;
            border-top: 1px solid #f1f5f9;
            padding-top: 1rem;
            display: flex; justify-content: space-between; align-items: center;
        }
        .action-btn {
            background: none; border: none; color: var(--text-muted); cursor: pointer;
            font-size: 0.9rem; font-weight: 500; display: flex; align-items: center; gap: 0.5rem;
            transition: color 0.2s;
        }
        .action-btn:hover { color: var(--primary); }

        /* Loading */
        .loading-state { text-align: center; padding: 4rem; grid-column: 1/-1; }
        .spinner { font-size: 2rem; color: var(--primary); animation: spin 1s linear infinite; }
        @keyframes spin { 100% { transform: rotate(360deg); } }

    </style>
</head>
<body>
    <div id="canvas-container"></div>
    
    <header>
        <a href="landingpage.html" class="logo"><i class="fas fa-cube"></i> Constructa</a>
        <nav style="display:flex; gap:1rem;">
            <a href="material_market.php" class="top-nav-btn"><i class="fas fa-arrow-left"></i> Market</a>
            <a href="homeowner.php" class="top-nav-btn"><i class="fas fa-th-large"></i> Dashboard</a>
        </nav>
    </header>

    <main>
        <div class="dashboard-header">
            <h1 class="page-title">Material Procurement</h1>
            <p class="page-subtitle">Track the journey of your construction materials from request to site.</p>
        </div>

        <div id="projects-container">
            <div class="loading-state"><i class="fas fa-circle-notch spinner"></i> Loading procurement data...</div>
        </div>
    </main>

    <script>
        // ----------------------------------------------------
        // 1. 3D BACKGROUND (Exact Match from Material Market)
        // ----------------------------------------------------
        const initBackground3D = () => {
            const container = document.getElementById('canvas-container');
            if (!container) return;

            const scene = new THREE.Scene();
            scene.background = new THREE.Color('#f6f7f2'); // Updated color

            const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
            camera.position.z = 8;
            camera.position.y = 2;

            const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            container.innerHTML = ''; // Clear existing
            container.appendChild(renderer.domElement);

            const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
            scene.add(ambientLight);

            const mainLight = new THREE.DirectionalLight(0xffffff, 0.8);
            mainLight.position.set(10, 10, 10);
            scene.add(mainLight);

            const cityGroup = new THREE.Group();
            scene.add(cityGroup);

            const buildingGeometry = new THREE.BoxGeometry(1, 1, 1);
            const buildingMaterial = new THREE.MeshPhongMaterial({
                color: 0x294033,
                transparent: true,
                opacity: 0.1,
                side: THREE.DoubleSide
            });
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

            // Animation Loop
            let mouseX = 0;
            let mouseY = 0;
            document.addEventListener('mousemove', (event) => {
                mouseX = (event.clientX - window.innerWidth / 2) * 0.001;
                mouseY = (event.clientY - window.innerHeight / 2) * 0.001;
            });

            const animate = () => {
                requestAnimationFrame(animate);
                cityGroup.rotation.y += 0.001;
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
        initBackground3D();


        // ----------------------------------------------------
        // 2. DASHBOARD LOGIC
        // ----------------------------------------------------
        const stages = ['Requested', 'Engineer Approved', 'Vendor Packed', 'In Transit', 'At Site', 'Verified'];

        async function loadDashboard() {
            try {
                const response = await fetch('backend/fetch_material_tracking.php');
                const result = await response.json();

                const container = document.getElementById('projects-container');
                container.innerHTML = '';

                if (result.status === 'success' && result.data.length > 0) {
                    result.data.forEach(project => {
                        renderProject(project, container);
                    });
                } else {
                    container.innerHTML = `
                        <div class="loading-state">
                            <i class="fas fa-clipboard-list" style="font-size:3rem; margin-bottom:1rem; color:var(--text-muted);"></i>
                            <p>No material requests found.</p>
                            <a href="material_market.php" style="color:var(--primary); font-weight:600; margin-top:1rem; display:inline-block;">Go to Market</a>
                        </div>`;
                }
            } catch (error) {
                console.error(error);
                document.getElementById('projects-container').innerHTML = `<p style="text-align:center; color:red;">Error loading data.</p>`;
            }
        }

        function renderProject(project, container) {
            // Summary Card
            const html = `
                <div class="project-summary-card">
                    <div class="project-info">
                        <h2><i class="fas fa-hard-hat" style="margin-right:0.5rem;"></i> ${project.name}</h2>
                        <div style="display:flex; gap:0.5rem; align-items:center;">
                            <span class="status-badge status-site">Active Site</span>
                            <span style="font-size:0.9rem; color:var(--text-muted);">Last updated: Just now</span>
                        </div>
                    </div>
                    <div class="stat-group">
                        <div class="stat-item">
                            <div class="stat-value">${project.total_orders}</div>
                            <div class="stat-label">Orders</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">₹${(project.total_spend/100000).toFixed(1)}L</div>
                            <div class="stat-label">Spend</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value" style="color:var(--warning);">${project.stages_summary['Requested'] || 0}</div>
                            <div class="stat-label">Pending</div>
                        </div>
                    </div>
                </div>

                <div class="material-wall">
                    ${project.materials.map(item => renderMaterialCard(item)).join('')}
                </div>
            `;
            container.innerHTML += html;
        }

        function renderMaterialCard(item) {
            // Icons based on name text (simple heuristic)
            let icon = 'fa-box';
            if(item.name.includes('Cement')) icon = 'fa-cubes';
            if(item.name.includes('Steel')) icon = 'fa-bars';
            if(item.name.includes('Brick')) icon = 'fa-border-all';
            if(item.name.includes('Wire')) icon = 'fa-bolt';

            let statusClass = 'status-requested';
            if(item.delivery_stage === 'At Site' || item.delivery_stage === 'Verified') statusClass = 'status-site';
            if(item.delivery_stage === 'Engineer Approved') statusClass = 'status-approved';

            return `
                <div class="material-track-card">
                    <div class="m-header">
                        <div style="display:flex;">
                            <div class="m-icon"><i class="fas ${icon}"></i></div>
                            <div>
                                <div class="m-title">${item.name}</div>
                                <div class="m-qty">${item.quantity} ${item.unit || 'units'}</div>
                            </div>
                        </div>
                        <span class="status-badge ${statusClass}">${item.delivery_stage}</span>
                    </div>

                    <div class="card-actions" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee; display:flex; justify-content:space-between;">
                        <div style="font-size:0.8rem; color:#94a3b8;">
                            <i class="far fa-clock"></i> Ordered ${new Date(item.order_date).toLocaleDateString()}
                        </div>
                    </div>
                </div>
            `;
        }

        function viewDetails(id) {
            alert("Detailed journey log for Order #" + id + " would open here.");
        }

        // Initialize
        loadDashboard();

    </script>
</body>
</html>
