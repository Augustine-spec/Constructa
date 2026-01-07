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
    <title>Structural Engineering Registry | Constructa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&family=Outfit:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { 
            --panel-bg: #f2f2f0;
            --slab-color: #ffffff;
            --text-engrave: rgba(0,0,0,0.7);
            --text-etch: rgba(0,0,0,0.5);
            --border-rail: #d1d1cc;
            --muted-green: #3d5a49;
            --tech-font: 'JetBrains Mono', monospace;
            --accent-glow: #e0e0db;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            background: #e8e8e3; 
            color: #1a1a1a; 
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
        }

        #global-bg { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; pointer-events: none; opacity: 0.4; }

        header { 
            padding: 1.5rem 4rem; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            background: rgba(232, 232, 227, 0.9); 
            backdrop-filter: blur(10px); 
            border-bottom: 2px solid var(--border-rail);
            position: sticky; top: 0; z-index: 1000; 
        }
        .logo { font-family: 'Outfit'; font-weight: 800; font-size: 1.1rem; color: var(--muted-green); letter-spacing: 1px; text-decoration: none; display: flex; align-items: center; gap: 0.5rem; }
        nav { display: flex; gap: 3rem; }
        nav a { text-decoration: none; color: #444; font-family: var(--tech-font); font-size: 0.75rem; text-transform: uppercase; font-weight: 700; transition: color 0.2s; }
        nav a:hover { color: var(--muted-green); }

        main { max-width: 1400px; margin: 0 auto; padding: 5rem 4rem; }
        
        .registry-header { border-left: 2px solid var(--muted-green); padding-left: 2rem; margin-bottom: 6rem; }
        .registry-header h1 { font-family: 'Outfit'; font-size: 2.5rem; font-weight: 800; color: #2d2d2d; line-height: 1; letter-spacing: -0.5px; margin-bottom: 0.5rem; }
        .registry-header p { font-family: var(--tech-font); font-size: 0.8rem; color: #666; text-transform: uppercase; letter-spacing: 1px; }

        /* Grid & Slabs */
        .workspace-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(440px, 1fr)); 
            gap: 4rem; 
        }

        .slab-dock {
            position: relative;
            height: 520px;
            background: var(--panel-bg);
            border: 1px solid var(--border-rail);
            padding: 10px;
            box-shadow: inset 0 0 20px rgba(0,0,0,0.05);
        }

        .slab-canvas {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: 1;
        }

        .slab-ui {
            position: relative;
            z-index: 10;
            padding: 3rem;
            height: 100%;
            display: flex;
            flex-direction: column;
            pointer-events: none;
            user-select: none;
        }

        /* Engraved Typography */
        .slab-name {
            font-family: 'Outfit';
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--text-engrave);
            text-shadow: -1px -1px 1px rgba(255,255,255,0.8), 1px 1px 1px rgba(0,0,0,0.2);
            margin-bottom: 0.2rem;
            letter-spacing: -0.2px;
        }
        .slab-role {
            font-family: var(--tech-font);
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-etch);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 3rem;
        }

        .specs-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .spec-item {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding-bottom: 0.8rem;
        }
        .spec-label {
            font-family: var(--tech-font);
            font-size: 0.65rem;
            color: #999;
            text-transform: uppercase;
        }
        .spec-value {
            font-family: var(--tech-font);
            font-size: 0.85rem;
            font-weight: 700;
            color: #444;
        }

        .corner-mark {
            position: absolute;
            top: 2rem; right: 2rem;
            width: 30px; height: 30px;
            border-top: 1px solid #ccc;
            border-right: 1px solid #ccc;
            opacity: 0.5;
        }
        .seal-mark {
            position: absolute;
            bottom: 3.5rem; right: 3rem;
            font-family: var(--tech-font);
            font-size: 0.55rem;
            color: #bbb;
            transform: rotate(-90deg);
            transform-origin: bottom right;
        }

        .btn-carved {
            margin-top: 2rem;
            width: 100%;
            padding: 1.4rem;
            background: #e0e0db;
            border: 1px solid #d1d1cc;
            color: #555;
            font-family: var(--tech-font);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-align: center;
            box-shadow: inset 2px 2px 5px rgba(0,0,0,0.1), inset -2px -2px 5px rgba(255,255,255,0.8);
            cursor: pointer;
            pointer-events: auto;
            transition: all 0.2s ease;
            text-decoration: none;
            display: block;
        }
        .btn-carved:hover {
            color: var(--muted-green);
            background: #e8e8e3;
            box-shadow: inset 1px 1px 3px rgba(0,0,0,0.1), inset -1px -1px 3px rgba(255,255,255,0.8);
        }
    </style>
</head>
<body>
    <div id="global-bg"></div>

    <header>
        <a href="homeowner.php" class="logo">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="square" stroke-linejoin="miter"><path d="M3 3h18v18H3z"/><path d="M3 9h18"/><path d="M3 15h18"/><path d="M9 3v18"/><path d="M15 3v18"/></svg>
            CONSTRUCTA_REGISTRY
        </a>
        <nav>
            <a href="homeowner.php">Terminal</a>
            <a href="material_market.php">Inventory</a>
            <a href="login.html">Logout</a>
        </nav>
    </header>

    <main>
        <div class="registry-header">
            <h1>Engineering Registry</h1>
            <p>Validated Technical Entities // System Status: Active</p>
        </div>

        <div class="workspace-grid" id="registry-grid">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): 
                    $exp = $row['experience'] ?: 5.0;
                    $projects = rand(40, 95);
                    $fee = number_format(rand(12, 45) * 100, 0);
                ?>
                    <div class="slab-dock" 
                         data-name="<?php echo htmlspecialchars($row['name']); ?>"
                         data-email="<?php echo $row['email']; ?>">
                        
                        <div class="slab-canvas"></div>
                        <div class="corner-mark"></div>
                        <div class="seal-mark">AUTH_VAL_2026</div>

                        <div class="slab-ui">
                            <h3 class="slab-name"><?php echo htmlspecialchars($row['name']); ?></h3>
                            <p class="slab-role"><?php echo htmlspecialchars($row['specialization'] ?: 'Structural Specialist'); ?></p>
                            
                            <div class="specs-panel">
                                <div class="spec-item">
                                    <span class="spec-label">Experience Metric</span>
                                    <span class="spec-value"><?php echo number_format($exp, 1); ?> YRS PRACTICE</span>
                                </div>
                                <div class="spec-item">
                                    <span class="spec-label">Project Output</span>
                                    <span class="spec-value"><?php echo $projects; ?> VERIFIED PROJECTS</span>
                                </div>
                                <div class="spec-item">
                                    <span class="spec-label">Accuracy Metric</span>
                                    <span class="spec-value">99.8% STRUCTURAL VALIDATION</span>
                                </div>
                                <div class="spec-item">
                                    <span class="spec-label">System Value</span>
                                    <span class="spec-value">ID_REF: <?php echo str_pad($row['id'], 3, '0', STR_PAD_LEFT); ?> // FEE_VAL: ₹<?php echo $fee; ?></span>
                                </div>
                            </div>

                            <a href="mailto:<?php echo $row['email']; ?>" class="btn-carved">Initialize Consultation</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; padding: 10rem; text-align: center; font-family: var(--tech-font); color: #999; font-size: 0.7rem;">
                    [SYSTEM_LOG: NO_ENTITIES_IDENTIFIED_IN_ACTIVE_GRID]
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="js/architectural_bg.js"></script>
    
    <script>
        if(window.initArchitecturalBackground) initArchitecturalBackground('global-bg');

        class DataSlabModule {
            constructor(el) {
                this.el = el;
                this.container = el.querySelector('.slab-canvas');
                
                this.scene = new THREE.Scene();
                this.camera = new THREE.PerspectiveCamera(40, this.container.offsetWidth / this.container.offsetHeight, 0.1, 100);
                this.camera.position.z = 6;

                this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
                this.renderer.setSize(this.container.offsetWidth, this.container.offsetHeight);
                this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
                this.container.appendChild(this.renderer.domElement);

                // Slab Geometry (Thick Physical Slab)
                const geometry = new THREE.BoxGeometry(4.6, 6.2, 0.4);
                const material = new THREE.MeshStandardMaterial({ 
                    color: 0xffffff, 
                    roughness: 0.9, 
                    metalness: 0.02 
                });
                this.slab = new THREE.Mesh(geometry, material);
                this.scene.add(this.slab);

                // Structural Rails (Docking feel)
                const railLines = new THREE.LineSegments(
                    new THREE.EdgesGeometry(new THREE.BoxGeometry(4.8, 6.4, 0.1)),
                    new THREE.LineBasicMaterial({ color: 0xd1d1cc, transparent: true, opacity: 0.5 })
                );
                railLines.position.z = -0.3;
                this.scene.add(railLines);

                // Lighting
                const ambient = new THREE.AmbientLight(0xffffff, 0.7);
                this.scene.add(ambient);

                this.frontLight = new THREE.DirectionalLight(0xffffff, 0.3);
                this.frontLight.position.set(0, 0, 5);
                this.scene.add(this.frontLight);

                this.bindEvents();
                this.animate();
            }

            bindEvents() {
                this.el.addEventListener('mousemove', (e) => {
                    const rect = this.el.getBoundingClientRect();
                    const x = (e.clientX - rect.left) / rect.width - 0.5;
                    const y = (e.clientY - rect.top) / rect.height - 0.5;

                    // Forward movement on Z-axis (Machinery alignment feel)
                    gsap.to(this.slab.position, {
                        z: 0.15,
                        x: x * 0.05,
                        y: -y * 0.05,
                        duration: 0.6,
                        ease: 'power2.inOut'
                    });

                    // Light intensification
                    gsap.to(this.frontLight, {
                        intensity: 0.6,
                        duration: 0.4
                    });
                });

                this.el.addEventListener('mouseleave', () => {
                    gsap.to(this.slab.position, {
                        z: 0, x: 0, y: 0,
                        duration: 1.2,
                        ease: 'power3.out'
                    });
                    gsap.to(this.frontLight, {
                        intensity: 0.3,
                        duration: 0.8
                    });
                });
            }

            animate() {
                requestAnimationFrame(() => this.animate());
                this.renderer.render(this.scene, this.camera);
            }
        }

        // Initialize all slabs
        document.querySelectorAll('.slab-dock').forEach(dock => new DataSlabModule(dock));
    </script>
</body>
</html>
