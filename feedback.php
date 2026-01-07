<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'homeowner') { header('Location: login.html'); exit(); }
require_once 'backend/config.php';

$conn = getDatabaseConnection();
$username = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'User';

// Institutional Engineering Questions
$questions = [
    ['id' => 1, 'text' => 'Structural Integrity Validation Accuracy', 'sub' => 'Assess the precision of load-bearing calculations and safety margin adherence.'],
    ['id' => 2, 'text' => 'Technical Documentation Clarity', 'sub' => 'Evaluate the fidelity and detailing of structural blueprints and specifications.'],
    ['id' => 3, 'text' => 'Consultation Professionalism Index', 'sub' => 'Adherence to engineering ethics and regulatory communication standards.'],
    ['id' => 4, 'text' => 'Operational Efficiency', 'sub' => 'Evaluation of turnaround time relative to project complexity.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technical Review Session | Constructa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&family=Outfit:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { 
            --bg: #f2f2f0;
            --muted-green: #3d5a49;
            --graphite: #2d2d2d;
            --border: rgba(0,0,0,0.1);
            --mono: 'JetBrains Mono', monospace;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: var(--bg); color: var(--graphite); font-family: 'Inter', sans-serif; overflow: hidden; height: 100vh; }

        #bg-canvas { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; pointer-events: none; opacity: 0.4; }

        header { 
            padding: 1.5rem 4rem; 
            display: flex; justify-content: space-between; align-items: center; 
            background: rgba(242, 242, 240, 0.9); backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border);
            position: fixed; top: 0; width: 100%; z-index: 1000;
        }
        .logo { font-family: 'Outfit'; font-weight: 800; font-size: 1.1rem; color: var(--muted-green); text-decoration: none; letter-spacing: 1px; }

        main { 
            height: 100vh; display: flex; align-items: center; justify-content: center;
            perspective: 1000px;
        }

        .review-container { 
            width: 100%; max-width: 800px; 
            position: relative; 
            padding: 2rem;
        }

        .step-card {
            background: #fff;
            padding: 5rem;
            border: 1px solid var(--border);
            box-shadow: 0 40px 100px rgba(0,0,0,0.05);
            display: none;
            opacity: 0;
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 100%;
        }

        .step-card.active { display: block; }

        .tag { font-family: var(--mono); font-size: 0.7rem; color: #999; margin-bottom: 1rem; display: block; }
        .q-text { font-family: 'Outfit'; font-size: 2.5rem; font-weight: 800; line-height: 1.1; margin-bottom: 1rem; }
        .q-sub { font-size: 1rem; color: #666; margin-bottom: 3rem; line-height: 1.6; }

        .rating-matrix {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1rem;
            margin-bottom: 3rem;
        }
        .rate-btn {
            padding: 1.5rem;
            border: 1px solid var(--border);
            background: var(--bg);
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }
        .rate-btn:hover, .rate-btn.selected {
            background: var(--muted-green);
            color: #fff;
            border-color: var(--muted-green);
            transform: scale(1.05);
        }
        .rate-num { font-family: var(--mono); font-size: 1.5rem; font-weight: 700; display: block; }
        .rate-lbl { font-size: 0.7rem; text-transform: uppercase; margin-top: 0.5rem; opacity: 0.7; }

        .final-input {
            width: 100%;
            padding: 2rem;
            font-family: inherit;
            font-size: 1.1rem;
            border: 1px solid var(--border);
            background: var(--bg);
            resize: none;
            height: 200px;
            margin-bottom: 2rem;
        }

        .action-tray { display: flex; justify-content: space-between; align-items: center; }
        .btn-nav {
            padding: 1.2rem 3rem;
            background: var(--graphite);
            color: #fff;
            border: none;
            font-family: var(--mono);
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-nav:hover:not(:disabled) { background: var(--muted-green); }
        .btn-nav:disabled { opacity: 0.2; cursor: not-allowed; }

        .progress-bar {
            position: fixed; bottom: 0; left: 0; height: 4px;
            background: var(--muted-green);
            transition: width 0.6s cubic-bezier(0.23, 1, 0.32, 1);
        }

        #confirmation {
            display: none;
            text-align: center;
        }
        #confirmation h2 { font-family: 'Outfit'; font-size: 4rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div id="bg-canvas"></div>

    <header>
        <a href="homeowner.php" class="logo">REVIEW_STATION_01</a>
    </header>

    <main>
        <div class="review-container">
            <?php foreach($questions as $index => $q): ?>
                <div class="step-card" data-step="<?php echo $index; ?>" id="step-<?php echo $index; ?>">
                    <span class="tag">METRIC_EVAL_<?php echo str_pad($q['id'], 2, '0', STR_PAD_LEFT); ?></span>
                    <h2 class="q-text"><?php echo $q['text']; ?></h2>
                    <p class="q-sub"><?php echo $q['sub']; ?></p>
                    
                    <div class="rating-matrix">
                        <?php 
                        $labels = ['Marginal', 'Sufficient', 'Compliant', 'Exemplary', 'Superior'];
                        for($i=1; $i<=5; $i++): ?>
                            <div class="rate-btn" onclick="selectRating(<?php echo $index; ?>, <?php echo $i; ?>)">
                                <span class="rate-num"><?php echo $i; ?></span>
                                <span class="rate-lbl"><?php echo $labels[$i-1]; ?></span>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <div class="action-tray">
                        <button class="btn-nav" onclick="prevStep()" <?php echo $index === 0 ? 'disabled' : ''; ?>>Back</button>
                        <button class="btn-nav" id="next-<?php echo $index; ?>" onclick="nextStep()" disabled>Next_Step</button>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Final Qualitative Remarks -->
            <div class="step-card" data-step="4" id="step-4">
                <span class="tag">ADDITIONAL_VAL_REMARKS</span>
                <h2 class="q-text">Consolidated Review</h2>
                <p class="q-sub">Please provide any final professional remarks or technical clarifications regarding the consultation performance.</p>
                
                <textarea class="final-input" id="final-comment" placeholder="Input professional qualitative data..."></textarea>

                <div class="action-tray">
                    <button class="btn-nav" onclick="prevStep()">Back</button>
                    <button class="btn-nav" id="submit-btn" onclick="submitReview()">Finalize_Session</button>
                </div>
            </div>

            <!-- Confirmation -->
            <div id="confirmation" class="step-card">
                <div id="success-3d-container" style="height: 300px; margin-bottom: 2rem;"></div>
                <span class="tag">SESSION_COMPLETED</span>
                <h2 class="q-text">Technical Review Logged</h2>
                <p class="q-sub">All data points have been serialized and pushed to the administrative queue for engineering validation.</p>
                <div class="action-tray" style="justify-content: center;">
                    <a href="homeowner.php" class="btn-nav" style="text-decoration:none;">Initialize Dashboard</a>
                </div>
            </div>
        </div>
    </main>

    <div class="progress-bar" id="p-bar" style="width: 0%;"></div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="js/architectural_bg.js"></script>
    <script>
        if(window.initArchitecturalBackground) initArchitecturalBackground('bg-canvas');

        let currentStep = 0;
        const totalSteps = 5;
        const responses = [];

        function showStep(s) {
            gsap.to('.step-card.active', { opacity: 0, x: -50, duration: 0.4, onComplete: () => {
                document.querySelectorAll('.step-card').forEach(c => c.classList.remove('active'));
                const next = document.getElementById('step-' + s);
                next.classList.add('active');
                gsap.fromTo(next, { opacity: 0, x: 50 }, { opacity: 1, x: -250, transform: 'translate(0, -50%)', left: '50%', duration: 0.6, ease: 'power2.out' });
                
                // Align step centering (since translate is active)
                next.style.transform = 'translate(-50%, -50%)';
            }});

            document.getElementById('p-bar').style.width = ((s + 1) / totalSteps) * 100 + '%';
            
            // Background Re-alignment simulation
            if (window.camera) {
                gsap.to(window.camera.position, { x: s * 0.5, z: 8 - s * 0.2, duration: 2 });
            }
        }

        // Initial Show
        const first = document.getElementById('step-0');
        first.classList.add('active');
        gsap.to(first, { opacity: 1, duration: 0.8 });
        document.getElementById('p-bar').style.width = (1 / totalSteps) * 100 + '%';

        function selectRating(s, val) {
            const btns = document.querySelectorAll(`#step-${s} .rate-btn`);
            btns.forEach(b => b.classList.remove('selected'));
            btns[val-1].classList.add('selected');
            
            responses[s] = { question_id: s + 1, score: val };
            document.getElementById('next-' + s).disabled = false;
        }

        function nextStep() {
            if(currentStep < totalSteps - 1) {
                currentStep++;
                showStep(currentStep);
            }
        }

        function prevStep() {
            if(currentStep > 0) {
                currentStep--;
                showStep(currentStep);
            }
        }

        async function submitReview() {
            const btn = document.getElementById('submit-btn');
            btn.disabled = true;
            btn.innerText = 'SERIALIZING...';

            const finalComment = document.getElementById('final-comment').value;
            if (responses[0]) responses[0].comment = finalComment;

            try {
                const res = await fetch('backend/process_feedback.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ responses: responses })
                });
                const data = await res.json();
                if(data.success) {
                    // Corrected transition: Fade out the active step, not the container
                    gsap.to('.step-card.active', { opacity: 0, x: -50, duration: 0.5, onComplete: () => {
                        document.querySelectorAll('.step-card').forEach(c => c.classList.remove('active'));
                        const conf = document.getElementById('confirmation');
                        conf.classList.add('active');
                        conf.style.display = 'block';
                        // Ensure it's centered
                        conf.style.transform = 'translate(-50%, -50%)';
                        gsap.fromTo(conf, { opacity: 0, scale: 0.9 }, { opacity: 1, scale: 1, duration: 0.8, ease: 'back.out(1.7)' });
                        document.getElementById('p-bar').style.width = '100%';
                        initSuccess3D();
                    }});
                } else {
                    alert('Log Error: ' + data.message);
                    btn.disabled = false;
                }
            } catch (e) {
                console.error(e);
                btn.disabled = false;
            }
        }

        function initSuccess3D() {
            const container = document.getElementById('success-3d-container');
            const scene = new THREE.Scene();
            const camera = new THREE.PerspectiveCamera(45, container.offsetWidth / container.offsetHeight, 0.1, 100);
            camera.position.z = 8;

            const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            renderer.setSize(container.offsetWidth, container.offsetHeight);
            container.appendChild(renderer.domElement);

            const group = new THREE.Group();
            scene.add(group);

            // Construct structural success icon (Shield/Checkmark hybrid)
            const box = new THREE.Mesh(
                new THREE.BoxGeometry(2, 2, 2),
                new THREE.MeshStandardMaterial({ color: 0x3d5a49, wireframe: true, transparent: true, opacity: 0.4 })
            );
            group.add(box);

            const innerBox = new THREE.Mesh(
                new THREE.BoxGeometry(1.2, 1.2, 1.2),
                new THREE.MeshStandardMaterial({ color: 0x3d5a49, metalness: 0.8, roughness: 0.2 })
            );
            group.add(innerBox);

            scene.add(new THREE.AmbientLight(0xffffff, 0.8));
            const pointLight = new THREE.PointLight(0x3d5a49, 1.5, 10);
            pointLight.position.set(2, 2, 2);
            scene.add(pointLight);

            function animate() {
                requestAnimationFrame(animate);
                group.rotation.y += 0.01;
                group.rotation.x += 0.005;
                innerBox.scale.setScalar(1 + Math.sin(Date.now() * 0.005) * 0.1);
                renderer.render(scene, camera);
            }
            animate();
        }
    </script>
</body>
</html>
