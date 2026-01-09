<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'homeowner') { header('Location: login.html'); exit(); }
require_once 'backend/config.php';

$questions = [
    ['id' => 1, 'text' => 'Structural Integrity', 'sub' => 'Validation Accuracy', 'desc' => 'Assess the precision of load-bearing calculations and adherence to safety margins.'],
    ['id' => 2, 'text' => 'Technical Documentation', 'sub' => 'Clarity Index', 'desc' => 'Evaluate the fidelity, detailing, and standard compliance of blueprints.'],
    ['id' => 3, 'text' => 'Professional Conduct', 'sub' => 'Consultation Standard', 'desc' => 'Rating of engineering ethics, communication timeliness, and transparency.'],
    ['id' => 4, 'text' => 'Operational Efficiency', 'sub' => 'Turnaround Time', 'desc' => 'Speed of delivery relative to the complexity of the structural project.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Station 01 | Constructa</title>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&family=Outfit:wght@300;500;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --concrete: #e6e6e2;
            --concrete-dark: #d1d1cc;
            --glass-border: rgba(255, 255, 255, 0.4);
            --glass-bg: rgba(242, 242, 240, 0.65);
            --text-main: #1a1a1a;
            --text-sub: #555555;
            --accent-green: #294033;
            --accent-green-dim: #3d5a49;
            --danger: #d9534f;
            --warning: #f0ad4e;
            --success: #3d5a49;
            --bg-color: #f6f7f2;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background-color: transparent;
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            overflow: hidden;
            width: 100vw;
            height: 100vh;
            user-select: none;
        }

        /* --- 3D Canvas --- */
        #canvas-container {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: 0;
            pointer-events: none; /* Let clicks pass to UI */
        }

        /* --- UI Overlay --- */
        #ui-layer {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: 10;
            display: flex;
            justify-content: center;
            align-items: center;
            perspective: 2000px; /* Deep perspective for 3D UI */
            pointer-events: none; /* Inner elements will re-enable pointer-events */
        }

        /* --- Main Feedback Panel (Glass/Concrete Hybrid) --- */
        .review-panel {
            width: 900px;
            max-width: 95%;
            /* height: 600px; */
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 4px; /* Engineered look, small radius */
            box-shadow: 
                0 20px 50px rgba(0,0,0,0.1),
                0 0 0 1px rgba(255,255,255,0.5) inset;
            pointer-events: auto;
            transform-style: preserve-3d;
            padding: 4rem 5rem;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.1s ease-out; /* Smooth tilt */
        }

        /* Decorative "Hardware" on Panel */
        .panel-hardware {
            position: absolute;
            width: 10px; height: 10px;
            background: #bbb;
            border-radius: 50%;
            box-shadow: inset 1px 1px 2px rgba(0,0,0,0.2);
        }
        .ph-tl { top: 15px; left: 15px; }
        .ph-tr { top: 15px; right: 15px; }
        .ph-bl { bottom: 15px; left: 15px; }
        .ph-br { bottom: 15px; right: 15px; }

        .panel-header {
            border-bottom: 2px solid rgba(0,0,0,0.05);
            padding-bottom: 2rem;
            margin-bottom: 3rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .step-indicator {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.75rem;
            color: var(--text-sub);
            letter-spacing: 2px;
            text-transform: uppercase;
            display: block;
            margin-bottom: 0.5rem;
        }

        .main-title {
            font-family: 'Outfit', sans-serif;
            font-size: 3rem;
            font-weight: 300; /* Light weight for futuristic look */
            line-height: 1;
            color: var(--text-main);
        }
        .main-title strong {
            font-weight: 700;
            display: block;
        }

        .sub-desc {
            font-size: 1rem;
            color: var(--text-sub);
            max-width: 400px;
            line-height: 1.5;
            margin-top: 1rem;
            font-weight: 500;
        }

        /* --- 3D Rating Blocks --- */
        .rating-stage {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin: 2rem 0 4rem 0;
            transform-style: preserve-3d;
        }

        .rating-block {
            position: relative;
            width: 100px;
            height: 120px;
            background: linear-gradient(135deg, #f0f0f0 0%, #e0e0e0 100%);
            border: 1px solid #fff;
            border-radius: 2px;
            cursor: pointer;
            transform-style: preserve-3d;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            box-shadow: 
                5px 5px 15px rgba(0,0,0,0.1),
                inset 1px 1px 2px rgba(255,255,255,0.8);
        }

        /* "Thickness" Extrusion Effect via Pseudo-elements */
        .rating-block::before {
            content: '';
            position: absolute;
            top: 2px; left: 100%;
            width: 10px; height: 100%;
            background: #ccc;
            transform: skewY(45deg);
            transform-origin: top left;
        }
        .rating-block::after {
            content: '';
            position: absolute;
            top: 100%; left: 2px;
            width: 100%; height: 10px;
            background: #bfbfbf;
            transform: skewX(45deg);
            transform-origin: top left;
        }

        .rating-block:hover {
            transform: translateZ(20px) translateY(-5px);
            background: #fff;
            box-shadow: 
                15px 15px 30px rgba(0,0,0,0.15),
                inset 0 0 0 2px var(--accent-green);
        }

        .rating-block.selected {
            background: var(--accent-green);
            color: white;
            border-color: var(--accent-green);
            transform: translateZ(10px) translateY(2px); /* Pressed in logic */
            box-shadow: 
                2px 2px 5px rgba(0,0,0,0.2),
                inset 0 0 20px rgba(0,0,0,0.2);
        }

        /* Fix extrusion color when selected */
        .rating-block.selected::before { background: var(--accent-green-dim); }
        .rating-block.selected::after { background: var(--accent-green-dim); }

        .rb-num {
            font-family: 'Outfit', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.2rem;
            /* Engraved effect */
            text-shadow: 1px 1px 0 rgba(255,255,255,0.5);
        }
        .rating-block.selected .rb-num { text-shadow: none; }

        .rb-label {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.6rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.7;
        }

        /* --- Footer / Confidence Meter --- */
        .panel-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid rgba(0,0,0,0.05);
            padding-top: 2rem;
        }

        .confidence-meter {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .meter-track {
            width: 200px;
            height: 6px;
            background: #ddd;
            border-radius: 3px;
            overflow: hidden;
            position: relative;
        }
        
        .meter-fill {
            position: absolute;
            top: 0; left: 0;
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, var(--danger), var(--warning), var(--success));
            background-size: 200% 100%;
            transition: width 0.5s ease-out;
        }

        .meter-label {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.7rem;
            color: var(--text-sub);
        }

        .nav-controls {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        /* --- Navigation Buttons --- */
        .btn-nav {
            background: transparent;
            border: 2px solid var(--text-main);
            color: var(--text-main);
            padding: 0.8rem 2rem;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .btn-nav:hover:not(:disabled) {
            background: var(--text-main);
            color: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn-nav:disabled {
            opacity: 0.3;
            cursor: not-allowed;
            border-color: #ccc;
            color: #ccc;
        }

        /* --- Step Sections --- */
        .step-section {
            display: none;
            opacity: 0;
            /* Transition handled by GSAP */
        }
        .step-section.active {
            display: block;
            opacity: 1;
        }

        /* --- Final Comment Area --- */
        .final-area {
            width: 100%;
        }
        .glass-input {
            width: 100%;
            height: 150px;
            background: rgba(255,255,255,0.5);
            border: 1px solid #ccc;
            padding: 1rem;
            font-family: 'Inter', sans-serif;
            font-size: 1.1rem;
            border-radius: 4px;
            resize: none;
            outline: none;
            transition: all 0.3s;
        }
        .glass-input:focus {
            background: rgba(255,255,255,0.8);
            border-color: var(--accent-green);
            box-shadow: 0 0 15px rgba(61, 90, 73, 0.1);
        }

        /* Loading Overlay */
        #loader {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: var(--bg-color);
            z-index: 100;
            display: flex; justify-content: center; align-items: center;
            font-family: 'JetBrains Mono';
            letter-spacing: 3px;
        }

        .top-nav-btn {
            position: fixed;
            top: 2rem;
            right: 2rem;
            z-index: 1000;
            padding: 0.8rem 1.5rem;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 4px;
            text-decoration: none;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8rem;
            color: var(--text-main);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .top-nav-btn:hover {
            background: var(--text-main);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

    </style>
</head>
<body>
    
    <div id="loader">INITIALIZING_REVIEW_STATION...</div>

    <a href="homeowner.php" class="top-nav-btn">
        <i class="fas fa-th-large"></i> Dashboard
    </a>

    <!-- 3D Background -->
    <div id="canvas-container"></div>

    <!-- UI Overlay -->
    <div id="ui-layer">
        <div class="review-panel tilt-element">
            <div class="panel-hardware ph-tl"></div>
            <div class="panel-hardware ph-tr"></div>
            <div class="panel-hardware ph-bl"></div>
            <div class="panel-hardware ph-br"></div>

            <?php foreach($questions as $index => $q): ?>
                <div class="step-section" id="step-<?php echo $index; ?>" data-index="<?php echo $index; ?>">
                    <div class="panel-header">
                        <div>
                            <span class="step-indicator">Metric 0<?php echo $index + 1; ?> // 05</span>
                            <h1 class="main-title"><?php echo htmlspecialchars($q['text']); ?> <strong><?php echo htmlspecialchars($q['sub']); ?></strong></h1>
                        </div>
                        <div class="sub-desc"><?php echo htmlspecialchars($q['desc']); ?></div>
                    </div>

                    <div class="rating-stage">
                        <?php 
                        $labels = ['Marginal', 'Sufficient', 'Compliant', 'Exemplary', 'Superior'];
                        for($i=1; $i<=5; $i++): ?>
                            <div class="rating-block" data-val="<?php echo $i; ?>" onclick="handleRating(<?php echo $index; ?>, <?php echo $i; ?>, this)">
                                <span class="rb-num"><?php echo $i; ?></span>
                                <span class="rb-label"><?php echo $labels[$i-1]; ?></span>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Final Step: Comment -->
            <div class="step-section" id="step-4" data-index="4">
                <div class="panel-header">
                    <div>
                        <span class="step-indicator">Metric 05 // 05</span>
                        <h1 class="main-title">Consolidated <strong>Review</strong></h1>
                    </div>
                    <div class="sub-desc">Please provide any final technical logs or qualitative data for the engineering team.</div>
                </div>
                <div class="rating-stage" style="display:block;">
                    <textarea class="glass-input" id="final-comment" placeholder="Input technical observations..."></textarea>
                </div>
            </div>

            <!-- Success Step -->
            <div class="step-section" id="step-success" data-index="5">
                <div class="panel-header" style="text-align:center; display:block; border:none; margin-bottom:1rem;">
                    <i class="fas fa-check-circle" style="font-size: 4rem; color: var(--accent-green); margin-bottom: 2rem;"></i>
                    <h1 class="main-title">Log <strong>Serialized</strong></h1>
                    <p class="sub-desc" style="margin: 1rem auto;">Validation metrics have been pushed to the central grid.</p>
                </div>
                <div style="text-align: center;">
                    <button class="btn-nav" onclick="window.location.href='homeowner.php'">Return to Dashboard</button>
                </div>
            </div>

            <div class="panel-footer">
                <div class="confidence-meter">
                    <span class="meter-label">STRUCTURAL_CONFIDENCE</span>
                    <div class="meter-track">
                        <div class="meter-fill" id="conf-fill"></div>
                    </div>
                    <span class="meter-label" id="conf-text">PENDING...</span>
                </div>
                <div class="nav-controls">
                    <button class="btn-nav" id="btn-back" onclick="prevStep()">Back</button>
                    <button class="btn-nav" id="btn-next" onclick="nextStep()" disabled>Next Step</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>

    <script>
        // --- DATA & STATE ---
        const totalSteps = 5; // 0-3 ratings, 4 comment
        let currentStep = 0;
        const responses = {};

        // --- 3D BACKGROUND (Standardized Wireframe City) ---
        // This is the EXACT logic used in homeowner.php and landingpage.html
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

        // --- UI LOGIC ---
        
        // Tilt Effect for Panel using CSS
        const panel = document.querySelector('.review-panel');
        document.addEventListener('mousemove', (e) => {
            const x = (e.clientX / window.innerWidth - 0.5) * 2; // -1 to 1
            const y = (e.clientY / window.innerHeight - 0.5) * 2;
            
            gsap.to(panel, {
                rotateY: x * 5, // Max 5 deg tilt
                rotateX: -y * 5,
                duration: 0.5
            });
        });

        // Initialize
        if (typeof THREE !== 'undefined') initBackground3D();
        
        gsap.to('#loader', { opacity: 0, duration: 1, onComplete: () => {
            document.getElementById('loader').style.display = 'none';
        }});
        document.getElementById('step-0').classList.add('active');

        // Rating Handler
        window.handleRating = (stepIndex, value, el) => {
            // UI Update
            const parent = el.parentElement;
            parent.querySelectorAll('.rating-block').forEach(b => b.classList.remove('selected'));
            el.classList.add('selected');
            
            // Data Update
            responses[stepIndex] = { question_id: stepIndex + 1, score: value };
            
            document.getElementById('btn-next').disabled = false;
            
            // Confidence Meter
            const fill = (value / 5) * 100;
            const track = document.getElementById('conf-fill');
            track.style.width = fill + '%';
            
            // Color logic
            if(value < 3) track.style.background = 'var(--danger)';
            else if(value === 3) track.style.background = 'var(--warning)';
            else track.style.background = 'var(--success)';

            document.getElementById('conf-text').innerText = 'METRIC_VALIDATED';
        };

        // Navigation
        window.nextStep = () => {
            if (currentStep < 4) {
                // Animate Out
                gsap.to(`#step-${currentStep}`, { 
                    opacity: 0, 
                    x: -50, 
                    duration: 0.4, 
                    onComplete: () => {
                        document.getElementById(`step-${currentStep}`).classList.remove('active');
                        currentStep++;
                        
                        // Setup Next
                        const next = document.getElementById(`step-${currentStep}`);
                        next.classList.add('active');
                        gsap.fromTo(next, 
                            { opacity: 0, x: 50 }, 
                            { opacity: 1, x: 0, duration: 0.4 }
                        );

                        // Reset button for next step (unless it's the comment step which is always valid)
                        const btnNext = document.getElementById('btn-next');
                        if (currentStep !== 4) {
                            if (!responses[currentStep]) {
                                btnNext.disabled = true;
                                document.getElementById('conf-fill').style.width = '0%';
                                document.getElementById('conf-text').innerText = 'AWAITING_INPUT...';
                            } else {
                                // Restore previous state if editing
                                document.getElementById('btn-next').disabled = false;
                            }
                        } else {
                            btnNext.innerText = "SUBMIT REVIEW";
                            btnNext.disabled = false;
                            btnNext.onclick = submitFinal;
                        }
                        
                        // Slightly shift the header title with GSAP for effect
                        gsap.fromTo(next.querySelector('.main-title'), { x: 20, opacity: 0 }, { x: 0, opacity: 1, duration: 0.5, delay: 0.1 });
                    }
                });
            }
        };

        window.prevStep = () => {
            if (currentStep > 0) {
                 // Animate Out
                 gsap.to(`#step-${currentStep}`, { 
                    opacity: 0, 
                    x: 50, 
                    duration: 0.4, 
                    onComplete: () => {
                        document.getElementById(`step-${currentStep}`).classList.remove('active');
                        currentStep--;
                        
                        // Setup Prev
                        const prev = document.getElementById(`step-${currentStep}`);
                        prev.classList.add('active');
                        gsap.fromTo(prev, 
                            { opacity: 0, x: -50 }, 
                            { opacity: 1, x: 0, duration: 0.4 }
                        );

                        // Button logic
                        const btnNext = document.getElementById('btn-next');
                        btnNext.innerText = "NEXT STEP";
                        btnNext.onclick = window.nextStep;
                        
                        // Re-enable if data exists
                        if (responses[currentStep]) {
                             btnNext.disabled = false;
                        }
                    }
                });
            }
        };

        window.submitFinal = async () => {
            const btn = document.getElementById('btn-next');
            btn.innerText = "PROCESSING...";
            btn.disabled = true;

            const comment = document.getElementById('final-comment').value;
            // Format for backend
            const payload = [];
            for (let i = 0; i < 4; i++) {
                let item = responses[i];
                if (i === 0) item.comment = comment; // Attach comment to first item as per legacy logic or new logic
                payload.push(item);
            }

            try {
                const res = await fetch('backend/process_feedback.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ responses: payload })
                });
                const data = await res.json();
                
                if (data.success) {
                    // Success View
                    document.getElementById(`step-4`).classList.remove('active');
                    const success = document.getElementById(`step-success`);
                    success.classList.add('active');
                    gsap.fromTo(success, { scale: 0.8, opacity: 0 }, { scale: 1, opacity: 1, duration: 0.5, ease: "back.out" });
                    
                    document.querySelector('.panel-footer').style.display = 'none';
                } else {
                    alert('Submission failed: ' + data.message);
                    btn.disabled = false;
                }
            } catch (e) {
                console.error(e);
                alert('Network error.');
                btn.disabled = false;
            }
        };
    </script>
</body>
</html>
