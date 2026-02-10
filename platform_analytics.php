<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.html');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Constructa | Platform Analytics AI</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Three.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    
    <style>
        :root {
            --bg-color: #f8f9fa;
            --text-main: #1f2937;
            --text-muted: #6b7280;
            --primary: #4f46e5;   /* Indigo */
            --accent: #8b5cf6;    /* Violet */
            --glass-surface: rgba(255, 255, 255, 0.65);
            --glass-border: rgba(255, 255, 255, 0.4);
            --shadow-premium: 0 20px 40px -10px rgba(0, 0, 0, 0.1), 0 0 2px rgba(0,0,0,0.05);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Outfit', sans-serif;
            background: #f3f4f6; /* Fallback */
            color: var(--text-main);
            overflow: hidden; /* App-like feel */
            height: 100vh;
            width: 100vw;
        }

        /* 3D Background Container */
        #analytics-canvas {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            z-index: 0;
            background: radial-gradient(circle at 50% 50%, #ffffff 0%, #f0f2f5 100%);
        }

        /* UI Layer */
        #ui-layer {
            position: relative; z-index: 10;
            height: 100vh; width: 100vw;
            display: flex; flex-direction: column;
            pointer-events: none; /* Let clicks pass to 3D where needed, verify specific elements have pointer-events: auto */
        }

        /* Nav */
        nav {
            padding: 1.5rem 3rem;
            display: flex; justify-content: space-between; align-items: center;
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,255,255,0.3);
            pointer-events: auto;
        }
        .logo { font-family: 'Space Grotesk', sans-serif; font-weight: 700; font-size: 1.5rem; color: #111827; letter-spacing: -0.5px; display: flex; align-items: center; gap: 0.5rem; text-decoration: none; }
        .nav-btn {
            padding: 0.6rem 1.2rem; border-radius: 8px; font-weight: 600; font-size: 0.9rem;
            color: #4b5563; text-decoration: none; transition: all 0.2s;
            border: 1px solid transparent;
        }
        .nav-btn:hover { background: rgba(0,0,0,0.05); color: #111827; }
        .nav-btn.primary { background: #111827; color: white; box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
        .nav-btn.primary:hover { background: #374151; transform: translateY(-1px); }

        /* Main Dashboard Content */
        .dashboard-grid {
            flex: 1;
            padding: 3rem;
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 2rem;
            pointer-events: none;
        }

        /* Floating Panel (Left) */
        .glass-panel {
            background: var(--glass-surface);
            backdrop-filter: blur(25px) saturate(180%);
            border-radius: 24px;
            border: 1px solid var(--glass-border);
            box-shadow: var(--shadow-premium);
            padding: 2rem;
            display: flex; flex-direction: column; gap: 2rem;
            pointer-events: auto;
            transform: perspective(1000px) rotateY(2deg);
            transition: transform 0.3s ease;
        }
        .glass-panel:hover {
            transform: perspective(1000px) rotateY(0deg) translateZ(10px);
        }

        .metric-group { display: flex; flex-direction: column; gap: 0.5rem; }
        .metric-label { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-weight: 600; }
        .metric-value { 
            font-family: 'Space Grotesk', sans-serif; 
            font-size: 3rem; font-weight: 700; 
            background: linear-gradient(135deg, #111827 0%, #4f46e5 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            line-height: 1;
        }
        .metric-delta { font-size: 0.9rem; color: #10b981; font-weight: 500; display: flex; align-items: center; gap: 0.3rem; }
        .metric-delta.down { color: #ef4444; }

        .pulse-indicator {
            display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #10b981;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulse-green 2s infinite;
            margin-right: 6px;
        }
        @keyframes pulse-green {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        /* 3D Main Viewport Area (Right) */
        .viewport-area {
            position: relative;
            /* Allow generic clicks to pass through to 3D canvas behind, 
               but maybe we want specific UI overlays here too */
            pointer-events: none;
            display: flex; flex-direction: column; justify-content: flex-end; align-items: flex-end;
            padding-bottom: 2rem;
        }

        /* Floating HUD Elements */
        .hud-card {
            pointer-events: auto;
            background: rgba(255,255,255,0.8);
            backdrop-filter: blur(15px);
            padding: 1rem 1.5rem;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            margin-top: 1rem;
            display: flex; align-items: center; gap: 1rem;
            border: 1px solid rgba(255,255,255,0.5);
            animation: slideInRight 0.5s ease-out backwards;
        }
        .hud-card:nth-child(2) { animation-delay: 0.1s; }
        .hud-card:nth-child(3) { animation-delay: 0.2s; }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .hud-icon {
            width: 40px; height: 40px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 1.1rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        /* Title Overlay */
        .page-title {
            position: absolute; top: 0; left: 0;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 4rem; font-weight: 700;
            color: rgba(0,0,0,0.03); /* Extremely subtle architectural watermark */
            pointer-events: none;
            user-select: none;
            z-index: -1;
            line-height: 0.8;
        }
    </style>
</head>
<body>

    <!-- 3D Scene Layer -->
    <div id="analytics-canvas"></div>

    <!-- UI Overlay Layer -->
    <div id="ui-layer">
        <nav>
            <a href="admin_dashboard.php" class="logo">
                <i class="fas fa-layer-group" style="color:#4f46e5;"></i> Constructa<span style="opacity:0.5; font-weight:400;">Analytics</span>
            </a>
            <div style="display:flex; gap:1rem;">
                <a href="admin_dashboard.php" class="nav-btn">Back to Dashboard</a>
                <a href="#" class="nav-btn primary">Export Report</a>
            </div>
        </nav>

        <div class="dashboard-grid">
            <!-- Left Panel: Key Metrics -->
            <div class="glass-panel">
                <div class="metric-group">
                    <div class="metric-label"><span class="pulse-indicator"></span>Live Traffic</div>
                    <div class="metric-value" id="counter-traffic">0</div>
                    <div class="metric-delta"><i class="fas fa-arrow-up"></i> 12% vs last hour</div>
                </div>

                <div style="height: 1px; background: rgba(0,0,0,0.05); margin: 1rem 0;"></div>

                <div class="metric-group">
                    <div class="metric-label">Active Projects</div>
                    <div class="metric-value" id="counter-projects">0</div>
                    <div class="metric-delta"><i class="fas fa-arrow-up"></i> 3 new today</div>
                </div>

                <div style="height: 1px; background: rgba(0,0,0,0.05); margin: 1rem 0;"></div>

                <div class="metric-group">
                    <div class="metric-label">User Growth</div>
                    <div class="metric-value" id="counter-users">0</div>
                    <div class="metric-delta down"><i class="fas fa-arrow-down"></i> 0.4% churn</div>
                </div>

                <div style="margin-top: auto;">
                    <button style="width:100%; padding:1rem; background:linear-gradient(135deg, #4f46e5, #8b5cf6); color:white; border:none; border-radius:12px; font-weight:600; cursor:pointer; box-shadow:0 10px 20px -5px rgba(79, 70, 229, 0.4); display:flex; justify-content:center; align-items:center; gap:0.5rem; transition:transform 0.2s;">
                        <i class="fas fa-robot"></i> Ask AI Assistant
                    </button>
                    <p style="text-align:center; font-size:0.75rem; color:var(--text-muted); margin-top:1rem;">
                        Last updated: Just now
                    </p>
                </div>
            </div>

            <!-- Right Area: HUD elements relative to 3D graph -->
            <div class="viewport-area">
                <div class="page-title">PLATFORM<br>INTELLIGENCE</div>
                
                <div class="hud-card">
                    <div class="hud-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                        <i class="fas fa-server"></i>
                    </div>
                    <div>
                        <div style="font-size:0.8rem; font-weight:600; color:#6b7280;">Server Load</div>
                        <div style="font-size:1.1rem; font-weight:700; color:#1f2937;">24% Optimal</div>
                    </div>
                </div>

                <div class="hud-card">
                    <div class="hud-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                        <i class="fas fa-database"></i>
                    </div>
                    <div>
                        <div style="font-size:0.8rem; font-weight:600; color:#6b7280;">Storage</div>
                        <div style="font-size:1.1rem; font-weight:700; color:#1f2937;">1.2 TB / 5 TB</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // === 1. COUNTER ANIMATIONS ===
        // Premium number counting effect using GSAP
        const animateValue = (id, start, end, duration) => {
            const obj = { val: start };
            gsap.to(obj, {
                val: end,
                duration: duration,
                ease: "power2.out",
                onUpdate: () => {
                    document.getElementById(id).innerText = Math.floor(obj.val).toLocaleString();
                }
            });
        };

        window.onload = () => {
            animateValue("counter-traffic", 0, 1482, 2.5);
            animateValue("counter-projects", 0, 86, 3);
            animateValue("counter-users", 0, 12403, 3.5);
            
            // Init 3D Scene
            init3DAnalytics();
        };

        // === 2. THREE.JS 3D VISUALIZATION ===
        function init3DAnalytics() {
            const container = document.getElementById('analytics-canvas');
            const scene = new THREE.Scene();
            // scene.background = new THREE.Color('#f3f4f6'); // Let CSS handle gradient background
            
            const camera = new THREE.PerspectiveCamera(45, window.innerWidth / window.innerHeight, 0.1, 1000);
            camera.position.set(20, 15, 20); // Isometric-ish view
            camera.lookAt(0, 2, 0);

            const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            container.appendChild(renderer.domElement);

            // -- LIGHTING --
            // Soft studio lighting
            const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
            scene.add(ambientLight);

            const dirLight = new THREE.DirectionalLight(0xffffff, 0.8);
            dirLight.position.set(10, 20, 10);
            dirLight.castShadow = true;
            dirLight.shadow.mapSize.width = 1024;
            dirLight.shadow.mapSize.height = 1024;
            scene.add(dirLight);

            const purpleLight = new THREE.PointLight(0x8b5cf6, 0.8, 50);
            purpleLight.position.set(-5, 5, -5);
            scene.add(purpleLight);

            const cyanLight = new THREE.PointLight(0x06b6d4, 0.8, 50);
            cyanLight.position.set(5, 5, 5);
            scene.add(cyanLight);

            // -- OBJECTS --

            // 1. The Base Grid (Architectural / Floor Plan feel)
            const gridHelper = new THREE.GridHelper(40, 40, 0xccd1d9, 0xe5e7eb);
            scene.add(gridHelper);

            // 2. 3D Data Bars (Rising Up)
            const groupBars = new THREE.Group();
            scene.add(groupBars);

            const barGeometry = new THREE.BoxGeometry(0.8, 1, 0.8);
            // Glass-like material for bars
            const barMaterial = new THREE.MeshPhysicalMaterial({ 
                color: 0x4f46e5, 
                transparent: true, 
                opacity: 0.8,
                roughness: 0.1,
                metalness: 0.1,
                transmission: 0.2,
                clearcoat: 1.0
            });

            // Create a 10x10 dataset visualization
            const bars = [];
            for(let x = -5; x < 5; x++) {
                for(let z = -5; z < 5; z++) {
                    // Perlin noise substitute logic for height
                    const dist = Math.sqrt(x*x + z*z);
                    const baseHeight = Math.max(0.2, Math.sin(x * 0.5) * Math.cos(z * 0.5) * 5 + 3);
                    
                    const mesh = new THREE.Mesh(barGeometry, barMaterial);
                    mesh.position.set(x * 1.5, baseHeight / 2, z * 1.5);
                    mesh.scale.y = baseHeight;
                    
                    // Store initial height for animation
                    mesh.userData = { 
                        initialScale: baseHeight, 
                        offset: Math.random() * 100 
                    };
                    
                    groupBars.add(mesh);
                    bars.push(mesh);
                }
            }

            // 3. Floating "Holographic" Line Graph
            const linePoints = [];
            for (let i = -10; i <= 10; i++) {
                linePoints.push(new THREE.Vector3(i * 1.5, 8 + Math.sin(i)*2, 0));
            }
            const lineCurve = new THREE.CatmullRomCurve3(linePoints);
            const lineGeo = new THREE.TubeGeometry(lineCurve, 64, 0.15, 8, false);
            const lineMat = new THREE.MeshStandardMaterial({ 
                color: 0x10b981, 
                emissive: 0x10b981,
                emissiveIntensity: 0.5,
                roughness: 0.4 
            });
            const tubeMesh = new THREE.Mesh(lineGeo, lineMat);
            scene.add(tubeMesh);

            // 4. Floating Data Nodes (Particles)
            const particlesGeo = new THREE.BufferGeometry();
            const particlesCount = 100;
            const posArray = new Float32Array(particlesCount * 3);
            
            for(let i = 0; i < particlesCount * 3; i++) {
                posArray[i] = (Math.random() - 0.5) * 30; // Spread mostly around center
                if(i % 3 === 1) posArray[i] = Math.random() * 10 + 2; // Y range 2-12
            }
            
            particlesGeo.setAttribute('position', new THREE.BufferAttribute(posArray, 3));
            const particlesMat = new THREE.PointsMaterial({
                size: 0.15,
                color: 0x8b5cf6, // Violet nodules
                transparent: true,
                opacity: 0.8
            });
            const particlesMesh = new THREE.Points(particlesGeo, particlesMat);
            scene.add(particlesMesh);


            // -- ANIMATION LOOP --
            let time = 0;
            const animate = () => {
                requestAnimationFrame(animate);
                time += 0.01;

                // Rotate entire assembly slowly
                groupBars.rotation.y = time * 0.05;
                tubeMesh.rotation.y = time * 0.05;
                particlesMesh.rotation.y = time * 0.03;

                // Animate Bars (Breathing effect)
                bars.forEach(bar => {
                    const t = time + bar.userData.offset;
                    const newHeight = bar.userData.initialScale + Math.sin(t) * 1.5;
                    // Ensure positive height
                    const h = Math.max(0.1, newHeight);
                    bar.scale.y = h;
                    bar.position.y = h/2;

                    // Color shift based on height
                    const intensity = h / 8; // normalize somewhat
                    bar.material.color.setHSL(0.66, 0.7, 0.3 + intensity * 0.4); // Blue-ish range
                });

                // Floating Line undulation based on vertex manipulation would be expensive here, 
                // so we just float the whole object
                tubeMesh.position.y = Math.sin(time) * 0.5;

                renderer.render(scene, camera);
            };

            animate();

            // -- INTERACTION --
            let mouseX = 0;
            let mouseY = 0;
            
            document.addEventListener('mousemove', (event) => {
                mouseX = (event.clientX - window.innerWidth / 2) * 0.0005;
                mouseY = (event.clientY - window.innerHeight / 2) * 0.0005;
            });

            // Camera subtle parallax
            const parallaxAnimate = () => {
                requestAnimationFrame(parallaxAnimate);
                camera.position.x += (20 + mouseX * 10 - camera.position.x) * 0.05;
                camera.position.y += (15 + mouseY * 10 - camera.position.y) * 0.05;
                camera.lookAt(0, 2, 0);
            }
            parallaxAnimate();

            // Resize handle
            window.addEventListener('resize', () => {
                camera.aspect = window.innerWidth / window.innerHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(window.innerWidth, window.innerHeight);
            });
        }
    </script>
</body>
</html>
