<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'homeowner') {
    header('Location: login.html');
    exit();
}

require_once 'backend/config.php';
$conn = getDatabaseConnection();
$homeowner_id = $_SESSION['user_id'];

// Fetch the active project for this homeowner
// Assuming 'project_requests' table holds the project workflow state
$sql = "SELECT pr.*, u.name as engineer_name, u.email as engineer_email 
        FROM project_requests pr 
        LEFT JOIN users u ON pr.engineer_id = u.id 
        WHERE pr.homeowner_id = ? 
        ORDER BY pr.updated_at DESC LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $homeowner_id);
$stmt->execute();
$result = $stmt->get_result();
$project = $result->fetch_assoc();

// Define Stages
$stages = [
    1 => ['label' => 'Request Submitted', 'desc' => 'Your project request has been received and is under review.', 'duration' => '1-2 Days'],
    2 => ['label' => 'Engineer Assigned', 'desc' => 'A verified structural engineer has been assigned to your project.', 'duration' => '2-3 Days'],
    3 => ['label' => 'Site Survey', 'desc' => 'Engineer will visit the site for measurements and soil testing.', 'duration' => '1 Week'],
    4 => ['label' => 'Cost Estimation', 'desc' => 'Detailed BOQ and material cost estimation is being prepared.', 'duration' => '3-5 Days'],
    5 => ['label' => 'Approval & Permissions', 'desc' => 'Waiting for client approval and municipal permissions.', 'duration' => '2-4 Weeks'],
    6 => ['label' => 'Construction Execution', 'desc' => 'Actual construction and structural work in progress.', 'duration' => '3-6 Months'],
    7 => ['label' => 'Inspection & Handover', 'desc' => 'Final quality check and project handover.', 'duration' => '1-2 Weeks']
];

$current_stage_id = $project ? ($project['current_stage'] ?? 1) : 0;
// Fallback if no project found
if (!$project) {
    $current_stage_id = 0;
    $project = ['project_title' => 'No Active Project', 'engineer_name' => 'N/A', 'updated_at' => date('Y-m-d')];
}
$current_stage_info = $stages[$current_stage_id] ?? $stages[1];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Plans - Constructa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <!-- Shared & Wizard CSS -->
    <style>
        :root {
            --primary: #294033;
            --primary-light: #3d5a49;
            --accent: #d4af37;
            --bg-color: #f6f7f2;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --success: #10b981;
            --nav-height: 70px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }

        body {
            background-color: transparent;
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        #canvas-container {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            z-index: -1; background: #f6f7f2; pointer-events: none;
        }

        /* Navbar */
        nav {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            padding: 1rem 3rem;
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            position: sticky; top: 0; z-index: 100;
            height: var(--nav-height);
        }
        .nav-logo { font-weight: 800; font-size: 1.5rem; color: var(--primary); text-decoration: none; display: flex; align-items: center; gap: 0.5rem; }
        .nav-btn {
            background: white; border: 1px solid rgba(0,0,0,0.1); padding: 0.6rem 1.2rem;
            border-radius: 8px; font-weight: 700; font-size: 0.85rem; color: var(--text-main);
        }
        /* Favorite Button Style */
        .btn-favorite {
            position: absolute; top: 1rem; right: 1rem;
            width: 40px; height: 40px; border-radius: 50%;
            background: white; border: 1px solid rgba(0,0,0,0.1);
            color: #ccc; font-size: 1.2rem; cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            display: flex; align-items: center; justify-content: center; z-index: 100;
        }
        .btn-favorite:hover { background: #fee2e2; color: #ef4444; transform: scale(1.1); }
        .btn-favorite i.fas { color: #ef4444; } /* Filled heart is red */
            text-decoration: none; transition: all 0.3s;
        }
        .nav-btn:hover { background: var(--primary); color: white; transform: translateY(-2px); }

        /* === WIZARD STYLES (From budget_calculator) === */
        .app-container {
            display: flex;
            flex: 1;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            height: calc(100vh - var(--nav-height));
        }

        .wizard-section {
            flex: 2;
            padding: 3rem;
            position: relative;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .preview-section {
            flex: 1;
            background: #ffffff;
            border-left: 1px solid var(--border-color);
            padding: 2.5rem;
            display: flex;
            flex-direction: column;
            box-shadow: -5px 0 20px rgba(0,0,0,0.02);
        }

        .step {
            display: none;
            animation: fadeIn 0.5s ease-out;
        }
        .step.active { display: block; }

        .step-title { font-size: 2rem; font-weight: 700; color: var(--primary); margin-bottom: 0.5rem; }
        .step-desc { font-size: 1.1rem; color: var(--text-muted); margin-bottom: 2.5rem; }

        .big-input {
            width: 100%; font-size: 2.5rem; padding: 1rem; border: none;
            border-bottom: 3px solid var(--border-color); background: transparent;
            font-weight: 700; color: var(--primary); outline: none; transition: all 0.3s;
        }
        .big-input:focus { border-bottom-color: var(--primary); }

        .options-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem;
        }

        .selection-card {
            background: white; border: 2px solid var(--border-color); border-radius: 16px;
            padding: 2rem; cursor: pointer; transition: all 0.3s; position: relative;
            display: flex; flex-direction: column; gap: 0.5rem;
        }
        .selection-card:hover { transform: translateY(-4px); border-color: #cbd5e1; }
        .selection-card.selected { border-color: var(--primary); background: #f0fdf4; }
        
        .selection-card .icon { font-size: 2rem; color: var(--text-muted); margin-bottom: 1rem; }
        .selection-card.selected .icon { color: var(--primary); }
        .check-mark {
            position: absolute; top: 1rem; right: 1rem; width: 24px; height: 24px;
            border-radius: 50%; background: var(--primary); color: white;
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transform: scale(0); transition: all 0.3s;
        }
        .selection-card.selected .check-mark { opacity: 1; transform: scale(1); }

        .wizard-nav {
            margin-top: auto; padding-top: 3rem; display: flex; justify-content: space-between;
        }
        .btn-primary {
            background: var(--primary); color: white; padding: 1rem 2rem; border-radius: 12px;
            border: none; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;
        }
        .btn-secondary {
            background: var(--bg-color); color: var(--text-muted); padding: 1rem 2rem; border-radius: 12px;
            border: none; font-weight: 600; cursor: pointer;
        }

        /* === PLAN FINDER SPECIFIC === */
        .plan-results-grid {
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 2rem;
            padding-bottom: 2rem;
        }
        .plan-card {
            background: white; border-radius: 16px; overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid var(--border-color);
            transition: transform 0.3s; display: flex; flex-direction: column;
        }
        .plan-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        
        .plan-visuals {
            display: grid; grid-template-columns: 1fr 1fr; height: 200px;
        }
        .plan-img {
            width: 100%; height: 100%; object-fit: cover;
            border-bottom: 1px solid var(--border-color);
        }
        .blueprint-style {
            background-color: #003366;
            background-image: 
                linear-gradient(rgba(255,255,255,0.1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 20px 20px;
            position: relative;
            display: flex; align-items: center; justify-content: center;
        }
        .blueprint-text {
            color: rgba(255,255,255,0.8); font-family: 'JetBrains Mono', monospace; 
            font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;
            border: 2px solid rgba(255,255,255,0.5); padding: 0.5rem;
        }

        .plan-details { padding: 1.5rem; flex: 1; display: flex; flex-direction: column; }
        .plan-title { font-size: 1.25rem; font-weight: 700; color: var(--primary); margin-bottom: 0.5rem; }
        .plan-meta { display: flex; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap; }
        .meta-tag { 
            background: #f1f5f9; padding: 0.3rem 0.8rem; border-radius: 20px; 
            font-size: 0.85rem; color: var(--text-muted); font-weight: 600;
        }
        
        .plan-actions { display: flex; gap: 1rem; margin-top: auto; }
        .btn-small {
            flex: 1; padding: 0.8rem; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; text-align: center;
        }
        .btn-outline { background: white; border: 1px solid var(--border-color); color: var(--text-main); }
        .btn-action { background: var(--primary); color: white; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* === STATUS DASHBOARD CSS (Preserved & Adjusted) === */
        .status-container {
            max-width: 1200px; margin: 3rem auto; padding: 0 2rem;
        }
        .focus-card {
            background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(20px);
            border: 1px solid white; border-radius: 24px;
            padding: 2.5rem; display: grid; grid-template-columns: 1.5fr 1fr;
            gap: 2rem; box-shadow: 0 10px 40px rgba(0,0,0,0.05);
            margin-bottom: 3rem;
        }
        .timeline { position: relative; padding-left: 2rem; border-left: 3px solid rgba(0,0,0,0.05); }
        .timeline-stage { position: relative; margin-bottom: 2rem; padding-left: 2.5rem; }
        .timeline-marker {
            position: absolute; left: -2.6rem; top: 0rem; width: 40px; height: 40px;
            background: white; border: 3px solid rgba(0,0,0,0.1); border-radius: 50%;
            display: flex; align-items: center; justify-content: center; z-index: 2;
        }
        .stage-card {
            background: white; border-radius: 16px; padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        }
    </style>
</head>
<body>
    <div id="canvas-container"></div>

    <nav>
        <a href="homeowner.php" class="nav-logo"><i class="fas fa-home"></i> Constructa</a>
        <?php if (!$project): ?>
            <div><span style="color:var(--text-muted); font-size:0.9rem;">Start Your Journey</span></div>
        <?php else: ?>
            <a href="homeowner.php" class="nav-btn"><i class="fas fa-arrow-left"></i> Dashboard</a>
        <?php endif; ?>
    </nav>

    <!-- RECOMMENDED PLANS WIZARD (Primary View) -->
    <div id="view-plans" class="app-container">
        <!-- Toast -->
        <div id="toast" style="position:fixed; bottom:2rem; left:50%; transform:translateX(-50%); background:var(--primary); color:white; padding:1rem 2rem; border-radius:50px; box-shadow:0 10px 20px rgba(0,0,0,0.2); display:none; z-index:1000; animation:popIn 0.3s ease-out;">
            <i class="fas fa-check-circle"></i> <span id="toast-msg">Action Completed</span>
        </div>

        <!-- Wizard Steps -->
        <div class="wizard-section">
            <div class="step active" id="step1">
                <h2 class="step-title">Find Your Perfect Plan</h2>
                <p class="step-desc">Enter your plot details to see engineer-approved CAD plans.</p>
                
                <div style="margin-bottom:2rem;">
                    <label style="display:block; font-weight:600; margin-bottom:0.5rem; color:var(--text-muted);">Plot Area (sq. ft)</label>
                    <input type="number" id="plotArea" class="big-input" placeholder="e.g. 1200" oninput="updatePreview()">
                </div>

                <div style="margin-bottom:2rem;">
                    <label style="display:block; font-weight:600; margin-bottom:1rem; color:var(--text-muted);">Number of Floors</label>
                    <div class="options-grid">
                        <div class="selection-card" onclick="selectOption('floors', 1, this)">
                            <div class="check-mark"><i class="fas fa-check"></i></div>
                            <div class="icon"><i class="fas fa-square"></i></div>
                            <div class="card-title">Ground Only</div>
                        </div>
                        <div class="selection-card" onclick="selectOption('floors', 2, this)">
                            <div class="check-mark"><i class="fas fa-check"></i></div>
                            <div class="icon"><i class="fas fa-layer-group"></i></div>
                            <div class="card-title">G + 1</div>
                        </div>
                        <div class="selection-card" onclick="selectOption('floors', 3, this)">
                            <div class="check-mark"><i class="fas fa-check"></i></div>
                            <div class="icon"><i class="fas fa-building"></i></div>
                            <div class="card-title">G + 2</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Preferences -->
            <div class="step" id="step2">
                <h2 class="step-title">Layout & Vision</h2>
                <p class="step-desc">Choose a style and describe your dream home for our AI.</p>
                
                <label style="display:block; font-weight:600; margin-bottom:1rem; color:var(--text-muted);">Architectural Style</label>
                <div class="options-grid">
                    <div class="selection-card" onclick="selectOption('style', 'Modern', this)">
                        <div class="check-mark"><i class="fas fa-check"></i></div>
                        <div class="icon"><i class="fas fa-cube"></i></div>
                        <div class="card-title">Modern Box</div>
                    </div>
                    <div class="selection-card" onclick="selectOption('style', 'Traditional', this)">
                        <div class="check-mark"><i class="fas fa-check"></i></div>
                        <div class="icon"><i class="fas fa-home"></i></div>
                        <div class="card-title">Traditional</div>
                    </div>
                    <div class="selection-card" onclick="selectOption('style', 'Vastu', this)">
                        <div class="check-mark"><i class="fas fa-check"></i></div>
                        <div class="icon"><i class="fas fa-compass"></i></div>
                        <div class="card-title">Vastu Compliant</div>
                    </div>
                </div>

                <div style="margin-top:2.5rem;">
                    <label style="display:block; font-weight:600; margin-bottom:0.8rem; color:var(--text-muted);">AI Design Prompt <span style="font-weight:400; font-size:0.85rem;">(Optional)</span></label>
                    <textarea id="aiPrompt" style="width:100%; padding:1rem; border:2px solid var(--border-color); border-radius:12px; font-family:'Outfit',sans-serif; font-size:1rem; min-height:100px; resize:vertical; outline:none; transition:border-color 0.3s;" placeholder="e.g. I want a large balcony facing north, a double-height living room with a skylight, and an open kitchen..."></textarea>
                    <p style="font-size:0.8rem; color:var(--text-muted); margin-top:0.5rem;"><i class="fas fa-magic"></i> Constructa AI will use this to refine suggestions.</p>
                </div>
            </div>

            <!-- Step 3: Results -->
            <div class="step" id="step3">
                <div id="ai-loader" style="display:none; text-align:center; padding:4rem 2rem;">
                     <div class="spinner" style="width:60px; height:60px; border:5px solid #e2e8f0; border-top-color:var(--primary); border-radius:50%; animation:spin 1s linear infinite; margin:0 auto 2rem;"></div>
                     <h2 style="color:var(--primary); font-size:1.5rem; margin-bottom:0.5rem;" id="ai-status-text">Analyzing Plot Dimensions...</h2>
                     <p style="color:var(--text-muted);">Generating optimal layouts based on your vision.</p>
                </div>

                <div id="results-content" style="display:none;">
                    <h2 class="step-title">Your Personalized Design</h2>
                    <p class="step-desc">Based on your plot (approx <span id="res-area"></span> sqft) and AI analysis.</p>
                    
                    <div class="plan-results-grid" id="planResults">
                        <!-- Plans Injected Here -->
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="wizard-nav">
                <button class="btn-secondary" id="prevBtn" onclick="prevStep()" disabled>Back</button>
                <button class="btn-primary" id="nextBtn" onclick="nextStep()">Next Step <i class="fas fa-arrow-right"></i></button>
            </div>
        </div>

        <style>
            @keyframes spin { 100% { transform:rotate(360deg); } }
        </style>
        
        <!-- Live Preview Sidebar -->
        <div class="preview-section">
            <h3 style="font-size:1.1rem; font-weight:700; margin-bottom:1.5rem; color:var(--text-muted); text-transform:uppercase;">Selection Summary</h3>
            <div style="display:flex; flex-direction:column; gap:1.5rem;">
                <div>
                    <div style="font-size:0.85rem; color:var(--text-muted);">Plot Area</div>
                    <div style="font-size:1.5rem; font-weight:700; color:var(--primary);"><span id="prev-area">0</span> sq.ft</div>
                </div>
                <div>
                    <div style="font-size:0.85rem; color:var(--text-muted);">Configuration</div>
                    <div style="font-size:1.1rem; font-weight:600;"><span id="prev-floors">Not Selected</span></div>
                </div>
                <div>
                    <div style="font-size:0.85rem; color:var(--text-muted);">Style</div>
                    <div style="font-size:1.1rem; font-weight:600;"><span id="prev-style">Any</span></div>
                </div>
            </div>

            <!-- Live 3D Preview Container -->
            <div style="margin-top:2rem; padding-top:2rem; border-top:1px solid var(--border-color);">
                <h4 style="font-size:0.9rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; margin-bottom:1rem;">Conceptual 3D Preview</h4>
                <div id="live-3d-preview" style="width:100%; height:300px; background:#f1f5f9; border-radius:12px; overflow:hidden; position:relative;"></div>
                <p style="font-size:0.75rem; color:var(--text-muted); margin-top:0.8rem; line-height:1.4;">
                    <i class="fas fa-info-circle"></i> Disclaimer: This is a visualization only. Final structural design may vary based on engineering requirements and approvals.
                </p>
            </div>
        </div>
    </div>

    <!-- Plan Viewer Modal (2D Layout) -->
    <div id="planModal" style="position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.95); z-index:200; display:none; flex-direction:column;">
        <div style="padding:1.5rem; display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.05); border-bottom:1px solid rgba(255,255,255,0.1);">
            <div>
                <h3 style="color:white; font-family:'Outfit', sans-serif; font-size:1.2rem; margin-bottom:0.2rem;" id="planModalTitle">Floor Plan Layout</h3>
                <p style="color:rgba(255,255,255,0.6); font-size:0.85rem;">Engineer Approved 2D Design</p>
            </div>
            <button onclick="closeModal('planModal')" style="background:rgba(255,255,255,0.1); border:none; color:white; width:40px; height:40px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:background 0.3s;"><i class="fas fa-times"></i></button>
        </div>
        <div style="flex:1; display:flex; justify-content:center; align-items:center; overflow:hidden; position:relative; padding:2rem;">
            <!-- Plan Container -->
            <div id="planViewerContainer" style="width:100%; max-width:900px; height:100%; max-height:80vh; display:flex; align-items:center; justify-content:center;">
                <!-- Image will be injected here -->
            </div>
        </div>
        <div style="padding:1.5rem; text-align:center; background:rgba(255,255,255,0.02);">
             <p style="color:rgba(255,255,255,0.4); font-size:0.75rem; margin-bottom:0.5rem;">Disclaimer: This plan is for reference only. Final design requires engineer validation.</p>
             <button class="btn-primary" style="display:inline-block; background:white; color:var(--primary);" onclick="alert('Download feature coming soon...')"><i class="fas fa-file-pdf"></i> Download Reference PDF</button>
        </div>
    </div>

    <!-- Image Viewer Modal (Built Visual) -->
    <div id="imageModal" style="position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.95); z-index:200; display:none; flex-direction:column;">
        <div style="padding:1.5rem; display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.05); border-bottom:1px solid rgba(255,255,255,0.1);">
            <div>
                <h3 style="color:white; font-family:'Outfit', sans-serif; font-size:1.2rem; margin-bottom:0.2rem;" id="imageModalTitle">Reference Visualization</h3>
                <p style="color:rgba(255,255,255,0.6); font-size:0.85rem;">Front Elevation & Look Validation</p>
            </div>
            <button onclick="closeModal('imageModal')" style="background:rgba(255,255,255,0.1); border:none; color:white; width:40px; height:40px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:background 0.3s;"><i class="fas fa-times"></i></button>
        </div>
        <div style="flex:1; display:flex; justify-content:center; align-items:center; padding:2rem;">
            <div id="imageViewerContainer" style="width:100%; max-width:1000px; height:auto; aspect-ratio:16/9; background:#e2e8f0; border-radius:12px; overflow:hidden; position:relative; box-shadow:0 20px 50px rgba(0,0,0,0.5);">
                <!-- Image will be injected here -->
            </div>
        </div>
    </div>

    <script>
        // 1. 3D Background Logic (Matches budget_calculator.php exact style)
        const init3D = () => {
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

            // Hero House
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
            let targetRotationX = 0, targetRotationY = 0;
            document.addEventListener('mousemove', (event) => {
                mouseX = (event.clientX - window.innerWidth / 2) * 0.001;
                mouseY = (event.clientY - window.innerHeight / 2) * 0.001;
            });

            const animate = () => {
                requestAnimationFrame(animate);
                cityGroup.rotation.y += 0.001;
                floatGroup.rotation.y += 0.005;
                floatGroup.position.y = Math.sin(Date.now() * 0.001) * 0.5 + 0.5;
                
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
        document.addEventListener('DOMContentLoaded', init3D);

        // 2. Wizard Logic
        let currentStep = 1;
        const selections = { area: '', floors: 0, style: '' };

        function updatePreview() {
            const area = document.getElementById('plotArea').value;
            selections.area = area;
            document.getElementById('prev-area').textContent = area || '0';
            if(typeof updateHouseModel === 'function') updateHouseModel();
        }

        function selectOption(key, value, el) {
            selections[key] = value;
            const siblings = el.parentElement.children;
            for(let sib of siblings) sib.classList.remove('selected');
            el.classList.add('selected');
            if(key === 'floors') document.getElementById('prev-floors').textContent = value === 1 ? 'Ground Only' : `G + ${value-1}`;
            if(key === 'style') document.getElementById('prev-style').textContent = value;
            
            if(typeof updateHouseModel === 'function') updateHouseModel();
        }

        function nextStep() {
            if (currentStep === 1 && !selections.area) { alert("Please enter a plot area."); return; }
            
            document.getElementById(`step${currentStep}`).classList.remove('active');
            currentStep++;
            document.getElementById(`step${currentStep}`).classList.add('active');
            document.getElementById('prevBtn').disabled = false;

            // Trigger AI Generation on Step 3
            if (currentStep === 3) {
                document.getElementById('nextBtn').style.display = 'none'; // Hide 'Next'
                document.getElementById('prevBtn').disabled = true; // Lock nav
                startAIGeneration();
            }
        }
        
        function startAIGeneration() {
            const loader = document.getElementById('ai-loader');
            const content = document.getElementById('results-content');
            const statusText = document.getElementById('ai-status-text');
            
            loader.style.display = 'block';
            content.style.display = 'none';
            
            // Sequence of fake statuses
            const statuses = [
                "Analyzing Plot Dimensions...",
                "Applying Vastu Guidelines...",
                "Optimizing Floor Space...",
                "Rendering Elevations..."
            ];
            
            let i = 0;
            const interval = setInterval(() => {
                if(i < statuses.length) {
                    statusText.innerText = statuses[i];
                    i++;
                } else {
                    clearInterval(interval);
                    loader.style.display = 'none';
                    content.style.display = 'block';
                    document.getElementById('prevBtn').disabled = false;
                    renderPlans();
                }
            }, 800); // 800ms per status = ~3.2s total wait
        }

        function prevStep() {
            if (currentStep === 1) return;
            document.getElementById(`step${currentStep}`).classList.remove('active');
            currentStep--;
            document.getElementById(`step${currentStep}`).classList.add('active');
            if (currentStep === 1) document.getElementById('prevBtn').disabled = true;
            document.getElementById('nextBtn').style.display = 'flex';
        }

    </script>
    <style>
        /* --- AI Design Module Styles --- */
        .ai-card {
            background: white;
            border: 1px solid rgba(0,0,0,0.08);
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 2rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
        }
        
        .ai-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.1);
        }

        /* Split View Comparison */
        .compare-view {
            position: relative;
            height: 100%;
            min-height: 400px;
            overflow: hidden;
            background: #f1f5f9;
        }
        .compare-img {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            object-fit: cover;
        }
        .compare-overlay {
            position: absolute;
            top: 0; left: 0; width: 50%; height: 100%;
            overflow: hidden;
            border-right: 2px solid white;
            box-shadow: 5px 0 20px rgba(0,0,0,0.2);
            transition: width 0.1s ease-out;
            z-index: 10;
            background: white; /* Fallback */
        }
        .compare-valid-badge {
            position: absolute;
            top: 1rem; left: 1rem;
            background: rgba(16, 185, 129, 0.9);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            backdrop-filter: blur(4px);
            z-index: 20;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .compare-slider {
            position: absolute;
            bottom: 1rem; left: 50%; transform: translateX(-50%);
            width: 80%;
            z-index: 30;
            appearance: none;
            background: rgba(255,255,255,0.3);
            height: 4px;
            border-radius: 2px;
            outline: none;
        }
        .compare-slider::-webkit-slider-thumb {
            appearance: none;
            width: 50px; height: 30px;
            background: white;
            border-radius: 15px;
            border: 1px solid #ccc;
            cursor: ew-resize;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23555'%3E%3Cpath d='M8 17V7l-5 5 5 5zm8-10v10l5-5-5-5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: center;
            background-size: 20px;
        }

        /* Card Content */
        .ai-content {
            padding: 2rem;
            display: flex;
            flex-direction: column;
        }

        .ai-header {
            margin-bottom: 1.5rem;
        }
        .ai-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.3rem;
        }
        .ai-match-score {
            display: inline-block;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        /* Reasoning Grid */
        .reasoning-grid {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }
        .reasoning-chip {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-size: 0.75rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .reasoning-chip i { color: var(--primary); }

        /* Live Customization */
        .custom-panel {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.2rem;
            margin-bottom: 1.5rem;
        }
        .control-group { margin-bottom: 1rem; }
        .control-group:last-child { margin-bottom: 0; }
        .control-label {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 0.4rem;
        }
        .control-slider {
            width: 100%;
            accent-color: var(--primary);
            height: 4px;
        }

        /* Metrics */
        .metrics-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .metric-item div:first-child { font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; }
        .metric-item div:last-child { font-size: 1.2rem; font-weight: 700; color: var(--text-dark); }
        .cost-val { color: var(--success) !important; }

        /* Timeline */
        .timeline-box { margin-bottom: 1.5rem; }
        .timeline-bar {
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 0.5rem;
            display: flex;
        }
        .t-seg { height: 100%; }
        .t-seg.design { width: 15%; background: #94a3b8; }
        .t-seg.approval { width: 25%; background: #f59e0b; }
        .t-seg.build { width: 60%; background: #10b981; }

        /* Actions */
        .action-row {
            margin-top: auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }


        @media (max-width: 900px) {
            .ai-card { grid-template-columns: 1fr; }
            .compare-view { height: 300px; min-height: auto; }
        }
    </style>
    <script>
        // ... (Existing JS) ...

        // 3. Render Plan Cards (Expanded Data with Gallery)
        const plans = [
            { id: 1, title: 'Modern Compact Villa', area: '1200', baseCost: 2500000, floors: 2, beds: 3, style: 'Modern', 
              planImage: 'uploads/plans/modern_villa.png', // Fallback
              builtImage: 'uploads/plans/modern_villa.png',
              reasoning: ['Optimal for 30x40 Plot', 'High Rental Yield', 'Vastu Neutral'],
              gallery: [
                  'uploads/plans/modern_villa.png',
                  'uploads/plans/modern_villa_side.png',
                  'uploads/plans/modern_villa_living.png',
                  'uploads/plans/modern_villa_bedroom.png',
                  'uploads/plans/modern_villa_kitchen.png'
              ]
            },
            { id: 2, title: 'Traditional Courtyard Home', area: '1500', baseCost: 3200000, floors: 1, beds: 3, style: 'Traditional', 
              planImage: 'uploads/plans/traditional_house.png', 
              builtImage: 'uploads/plans/traditional_house.png',
              reasoning: ['Perfect for South Façade', 'Elderly Friendly', 'Eco-Materials'],
              gallery: [
                  'uploads/plans/traditional_house.png',
                  'uploads/plans/traditional_house_courtyard.png',
                  'uploads/plans/traditional_house_hall.png',
                  'uploads/plans/traditional_house_kitchen.png'
              ]
            },
            { id: 3, title: 'Urban Duplex', area: '1000', baseCost: 2800000, floors: 2, beds: 2, style: 'Modern', 
              planImage: 'uploads/plans/urban_duplex.png', 
              builtImage: 'uploads/plans/urban_duplex.png',
               reasoning: ['Compact City Design', 'Smart Storage', 'Low Maintenance'],
               gallery: [
                  'uploads/plans/urban_duplex.png',
                  'uploads/plans/urban_duplex_street.png',
                  'uploads/plans/urban_duplex_living.png',
                  'uploads/plans/urban_duplex_bedroom.png'
               ]
            },
            { id: 4, title: 'Vastu Compliant 3BHK', area: '1800', baseCost: 4000000, floors: 2, beds: 3, style: 'Vastu', 
              planImage: 'uploads/plans/vastu_mansion.png', 
              builtImage: 'uploads/plans/vastu_mansion.png',
              reasoning: ['100% Vastu Score', 'Max Natural Light', 'Resale Value High'],
              gallery: [
                   'uploads/plans/vastu_mansion.png',
                   'uploads/plans/vastu_mansion_rear.png',
                   'uploads/plans/vastu_mansion_foyer.png',
                   'uploads/plans/vastu_mansion_theater.png'
              ]
            },
            { id: 5, title: 'Modern Ground Villa', area: '1200', baseCost: 2000000, floors: 1, beds: 2, style: 'Modern', 
              planImage: 'uploads/plans/modern_villa.png', 
              builtImage: 'uploads/plans/modern_villa.png',
              reasoning: ['Single Floor Comfort', 'Open Plan Living', 'Budget Friendly'],
              gallery: [
                  'uploads/plans/modern_villa.png',
                  'uploads/plans/modern_villa_side.png',
                  'uploads/plans/modern_villa_living.png',
                  'uploads/plans/modern_villa_kitchen.png'
              ]
            },
            { id: 6, title: 'Grand Vastu Mansion', area: '2400', baseCost: 6000000, floors: 2, beds: 4, style: 'Vastu', 
              planImage: 'uploads/plans/vastu_mansion.png', 
              builtImage: 'uploads/plans/vastu_mansion.png',
              reasoning: ['Luxury Living', 'Large Garden Space', 'Premium Assessment'],
              gallery: [
                   'uploads/plans/vastu_mansion.png',
                   'uploads/plans/vastu_mansion_rear.png',
                   'uploads/plans/vastu_mansion_foyer.png',
                   'uploads/plans/vastu_mansion_theater.png'
              ]
            },
            { id: 7, title: 'Traditional G+2 Joint Family', area: '1500', baseCost: 5500000, floors: 3, beds: 5, style: 'Traditional', 
              planImage: 'uploads/plans/traditional_house.png', 
              builtImage: 'uploads/plans/traditional_house.png',
               reasoning: ['Multi-Gen Living', 'Separate Floor Units', 'Terrace Garden'],
               gallery: [
                  'uploads/plans/traditional_house.png',
                  'uploads/plans/traditional_house_courtyard.png',
                  'uploads/plans/traditional_house_hall.png'
               ]
            },
            { id: 8, title: 'Traditional Starter Home', area: '1200', baseCost: 2200000, floors: 1, beds: 2, style: 'Traditional', 
              planImage: 'uploads/plans/traditional_house.png', 
              builtImage: 'uploads/plans/traditional_house.png',
               reasoning: ['Starter Choice', 'Porch Included', 'Low Cost'],
               gallery: [
                  'uploads/plans/traditional_house.png',
                  'uploads/plans/traditional_house_courtyard.png',
                  'uploads/plans/traditional_house_kitchen.png'
               ]
            },
            { id: 9, title: 'Vastu Duplex 1200', area: '1200', baseCost: 2800000, floors: 2, beds: 3, style: 'Vastu', 
              planImage: 'uploads/plans/vastu_mansion.png', 
              builtImage: 'uploads/plans/vastu_mansion.png',
              reasoning: ['N-E Entrance', 'Double Height Hall', 'Pooja Room'],
              gallery: [
                   'uploads/plans/vastu_mansion.png',
                   'uploads/plans/vastu_mansion_foyer.png',
                   'uploads/plans/vastu_mansion_theater.png'
              ]
            },
            { id: 10, title: 'Compact Modern G', area: '1000', baseCost: 1800000, floors: 1, beds: 2, style: 'Modern', 
              planImage: 'uploads/plans/urban_duplex.png', 
              builtImage: 'uploads/plans/urban_duplex.png',
              reasoning: ['Cube Design', 'Minimalist', 'Efficient'],
              gallery: [
                  'uploads/plans/urban_duplex.png',
                  'uploads/plans/urban_duplex_living.png',
                  'uploads/plans/urban_duplex_bedroom.png'
              ]
            },
            { id: 11, title: 'Spacious Vastu Ground', area: '1500', baseCost: 2500000, floors: 1, beds: 3, style: 'Vastu', 
              planImage: 'uploads/plans/vastu_mansion.png', 
              builtImage: 'uploads/plans/vastu_mansion.png',
              reasoning: ['Brahmasthan Center', 'East Facet', 'Ventilated'],
              gallery: [
                   'uploads/plans/vastu_mansion.png',
                   'uploads/plans/vastu_mansion_rear.png',
                   'uploads/plans/vastu_mansion_foyer.png'
              ]
            },
            { id: 12, title: 'Traditional Villa G+1', area: '1800', baseCost: 4500000, floors: 2, beds: 4, style: 'Traditional', 
              planImage: 'uploads/plans/traditional_house.png', 
              builtImage: 'uploads/plans/traditional_house.png',
              reasoning: ['Classic Aesthetic', 'Wood Finishes', 'Large Living'],
              gallery: [
                  'uploads/plans/traditional_house.png',
                  'uploads/plans/traditional_house_courtyard.png',
                  'uploads/plans/traditional_house_hall.png'
              ]
            },
            { id: 13, title: 'Luxury Modern G+2', area: '2400', baseCost: 7500000, floors: 3, beds: 5, style: 'Modern', 
              planImage: 'uploads/plans/urban_duplex.png', 
              builtImage: 'uploads/plans/urban_duplex.png',
              reasoning: ['Penthouse Suite', 'Infinity Pool', 'Glass Walls'],
              gallery: [
                  'uploads/plans/urban_duplex.png',
                  'uploads/plans/urban_duplex_street.png',
                  'uploads/plans/urban_duplex_living.png'
              ]
            }
        ];


        function renderPlans() {
            const container = document.getElementById('planResults');
            const userArea = parseInt(selections.area);
            const userFloors = parseInt(selections.floors);
            const userStyle = selections.style;

            document.getElementById('res-area').textContent = selections.area;
            container.innerHTML = '';

            if (!userArea) {
                container.innerHTML = `<div style="grid-column:1/-1; text-align:center; padding:2rem; color:var(--text-muted);">Please enter a plot area in Step 1 to see recommendations.</div>`;
                return;
            }

            // Round 1: Strict Filter (Area ±20%, Exact Floors, Exact Style)
            let filtered = plans.filter(p => {
                const areaVariance = userArea * 0.20; 
                const areaMatch = (parseInt(p.area) >= userArea - areaVariance) && (parseInt(p.area) <= userArea + areaVariance);
                const floorsMatch = userFloors ? (p.floors === userFloors) : true;
                const styleMatch = userStyle ? (p.style === userStyle) : true;
                return areaMatch && floorsMatch && styleMatch;
            });

            let isRelaxed = false;

            // Round 2: Relaxed Filter (If no exact matches)
            // Strategy: Relax Area to ±30% AND Match (Floors OR Style)
            if (filtered.length === 0) {
                isRelaxed = true;
                filtered = plans.filter(p => {
                    const areaVariance = userArea * 0.30; 
                    const areaMatch = (parseInt(p.area) >= userArea - areaVariance) && (parseInt(p.area) <= userArea + areaVariance);
                    const floorsMatch = userFloors ? (p.floors === userFloors) : false;
                    const styleMatch = userStyle ? (p.style === userStyle) : false;
                    return areaMatch && (floorsMatch || styleMatch);
                });
            }

            // Fallback: Show empty state if still 0
            if (filtered.length === 0) {
                container.innerHTML = `
                    <div style="grid-column:1/-1; text-align:center; padding:3rem; background:white; border-radius:16px; border:1px dashed var(--border-color);">
                        <i class="fas fa-search-minus" style="font-size:3rem; color:var(--text-muted); margin-bottom:1rem; opacity:0.5;"></i>
                        <h3 style="color:var(--text-muted); font-size:1.1rem; margin-bottom:0.5rem;">No matches found</h3>
                        <p style="color:#94a3b8; font-size:0.9rem;">We couldn't find any plans close to your requirements even with broader search criteria.</p>
                    </div>
                `;
                return;
            }

            // Show "Closest Match" warning if relaxed
            if (isRelaxed) {
                container.innerHTML += `
                    <div style="grid-column:1/-1; padding:1rem; background:#fff7ed; border:1px solid #fed7aa; border-radius:8px; color:#9a3412; font-size:0.9rem; margin-bottom:1rem; display:flex; align-items:center; gap:0.5rem;">
                        <i class="fas fa-exclamation-circle"></i> 
                        <div><strong>Exact match not found.</strong> Showing available plans that are closest to your area and preferences.</div>
                    </div>
                `;
            }

// NO SLICE! Show ALL matches.
            filtered.forEach(plan => {
                const efficiency = Math.floor(Math.random() * (98 - 85) + 85);
                const timeline = Math.floor(plan.floors * 3 + 4); 

                const card = `
                    <div class="ai-card" id="ai-card-${plan.id}">
                        <!-- Left: Single Visual (Cartoon) -->
                        <div class="compare-view" id="compare-${plan.id}" style="border-right:1px solid #e2e8f0; position:relative;">
                            <div class="compare-valid-badge"><i class="fas fa-certificate"></i> Engineer Verified</div>
                            
                            <!-- Favorite Button -->
                            <button class="btn-favorite" id="fav-btn-${plan.id}" onclick="toggleFavorite(${plan.id}, this)" style="z-index:99; display:flex !important;">
                                <i class="far fa-heart"></i>
                            </button>

                            <!-- Main Cartoon Image -->
                            <img src="${plan.builtImage}" class="compare-img" style="width:100%; height:100%; object-fit:cover;">
                        </div>

                        <!-- Right: Intelligent Controls -->
                        <div class="ai-content">
                            <div class="ai-header">
                                <div class="ai-match-score"><i class="fas fa-magic"></i> AI Match: ${efficiency}%</div>
                                <div class="ai-title">${plan.title}</div>
                                <div class="reasoning-grid">
                                    ${plan.reasoning ? plan.reasoning.map(r => `<div class="reasoning-chip"><i class="fas fa-check-circle"></i> ${r}</div>`).join('') : ''}
                                </div>
                            </div>

                            <!-- Live Customization -->
                            <div class="custom-panel">
                                <div class="control-group">
                                    <div class="control-label"><span>Built-up Area</span> <span id="val-area-${plan.id}">${plan.area} sqft</span></div>
                                    <input type="range" class="control-slider" min="${parseInt(plan.area)-500}" max="${parseInt(plan.area)+500}" step="50" value="${plan.area}" 
                                        oninput="updateMetrics('${plan.id}', ${plan.baseCost}, this.value, null)">
                                </div>
                                <div class="control-group">
                                    <div class="control-label"><span>Finishing Grade</span> <span id="val-grade-${plan.id}">Premium</span></div>
                                    <input type="range" class="control-slider" min="1" max="3" step="1" value="2" 
                                        oninput="updateMetrics('${plan.id}', ${plan.baseCost}, null, this.value)">
                                </div>
                            </div>

                            <!-- Dynamic Metrics -->
                            <div class="metrics-row">
                                <div class="metric-item">
                                    <div>Est. Cost</div>
                                    <div class="cost-val" id="cost-${plan.id}">₹${(plan.baseCost/100000).toFixed(2)} Lakhs</div>
                                </div>
                                <div class="metric-item">
                                    <div>Timeline</div>
                                    <div id="time-${plan.id}">${timeline} Months</div>
                                </div>
                            </div>

                            <!-- Readiness Timeline -->
                            <div class="timeline-box">
                                <div style="display:flex; justify-content:space-between; font-size:0.7rem; color:var(--text-muted);">
                                    <span>Design</span>
                                    <span>Permits</span>
                                    <span>Construction</span>
                                </div>
                                <div class="timeline-bar">
                                    <div class="t-seg design"></div>
                                    <div class="t-seg approval"></div>
                                    <div class="t-seg build"></div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="action-row">
                                <button class="btn-primary" style="background:#334155; border:none;" onclick="openGalleryViewer('${plan.id}')">
                                    <i class="fas fa-images"></i> View Gallery
                                </button>
                                <button class="btn-outline" style="border:1px solid #e2e8f0; color:var(--text-muted); pointer-events:none; font-size:0.8rem;">
                                    Only Matches Shown
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                container.innerHTML += card;
                
                // Check if favorite (simple client-side check if we had loaded favorites beforehand, 
                // but for now we start empty or could fetch. Let's assume empty for simplicity or fetch async).
            });
            
            // Fetch favorites to update icons
            checkFavorites();
        }

        function openGalleryViewer(planId) {
            const plan = plans.find(p => p.id == planId);
            if(!plan) return;
            
            document.getElementById('imageModalTitle').innerText = plan.title + " - Gallery";
            const container = document.getElementById('imageViewerContainer');
            
            let html = `<div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:10px; height:100%; overflow-y:auto; padding-right:5px;">`;
            
            if(plan.gallery && plan.gallery.length > 0) {
                 plan.gallery.forEach(url => {
                     if(url.includes('uploads/')) {
                         html += `<div style="height:250px; border-radius:8px; overflow:hidden;"><img src="${url}" style="width:100%; height:100%; object-fit:contain; background:#f0f0f0;"></div>`;
                     }
                 });
            }
            
            html += `</div>`;
            container.innerHTML = html;
            
            // Adjust container styles for gallery
            container.style.aspectRatio = 'auto';
            container.style.height = '80vh';
            container.style.background = 'transparent';
            container.style.boxShadow = 'none';
            
            document.getElementById('imageModal').style.display = 'flex';
        }

        async function toggleFavorite(planId, btn) {
             const plan = plans.find(p => p.id == planId);
             if(!plan) return;

             const icon = btn.querySelector('i');
             const isFav = icon.classList.contains('fas'); // currently filled
             
             if(isFav) {
                 icon.classList.remove('fas');
                 icon.classList.add('far');
                 icon.style.color = ''; // Reset to CSS default (#ccc)
             } else {
                 icon.classList.remove('far');
                 icon.classList.add('fas');
                 icon.style.color = '#ef4444';
             }

             // Prepare Payload with Fallbacks
             const payload = {
                 item_id: plan.id,
                 item_type: 'design',
                 title: plan.title || 'Untitled Plan',
                 description: (plan.reasoning && plan.reasoning.length > 0) ? plan.reasoning[0] : '',
                 image_url: plan.builtImage || '',
                 meta_info: { 
                     area: plan.area || 0, 
                     cost: plan.baseCost || 0 
                 }
             };

             try {
                 const response = await fetch('backend/save_favorite.php', {
                     method: 'POST',
                     headers: { 'Content-Type': 'application/json' },
                     body: JSON.stringify(payload)
                 });
             } catch(e) { console.error("Toggle Favorite Fetch Error:", e); }
        }
        
        async function checkFavorites() {
            try {
                const response = await fetch('backend/save_favorite.php?type=design');
                const result = await response.json();
                if(result.status === 'success') {
                    result.data.forEach(fav => {
                        const btn = document.getElementById(`fav-btn-${fav.item_id}`);
                        if(btn) {
                            const icon = btn.querySelector('i');
                            icon.classList.remove('far');
                            icon.classList.add('fas');
                            icon.style.color = '#ef4444';
                        }
                    });
                }
            } catch(e) { console.error(e); }
        }

        // Helper: Split View Logic
        function updateCompare(slider, id) {
            const overlay = document.querySelector(`#compare-${id} .compare-overlay`);
            overlay.style.width = slider.value + "%";
        }

        // Helper: Dynamic Metrics Calc
        function updateMetrics(id, baseCost, newArea, newGrade) {
            // Get current vals if not provided
            const areaEl = document.getElementById(`val-area-${id}`);
            const currentArea = newArea || parseInt(areaEl.innerText);
            if(newArea) areaEl.innerText = newArea + ' sqft';

            const gradeEl = document.getElementById(`val-grade-${id}`);
            let gradeMult = 1;
            if(newGrade) {
                const grades = ['Standard', 'Premium', 'Luxury'];
                gradeEl.innerText = grades[newGrade-1];
                gradeMult = 1 + ((newGrade-1) * 0.25); // 1.0, 1.25, 1.5
            } else {
                 // simplify: assume grade 2 if just area changing, or read from DOM? 
                 // For demo speed, just assume linear area scale
            }

            // Calc Cost
            // Cost scales linearly with area diff from original plan.area? 
            // Simplified: (Cost / OriginalArea) * NewArea * GradeMult
            // We need original area. Let's assume passed baseCost is for original Area.
            // We can hack it: just update display based on simple factor
            
            // Re-calc cost (Mock logic)
            // let unitCost = baseCost / 1200; // rough
            let unitCost = 2000; 
            let total = currentArea * unitCost * gradeMult;
            
            document.getElementById(`cost-${id}`).innerText = '₹' + (total/100000).toFixed(2) + ' Lakhs';
            
            // Update timeline slightly
            let months = Math.floor((currentArea / 500) + 4);
            document.getElementById(`time-${id}`).innerText = months + ' Months';
        }

        // 4. View Handlers (Functional)
        function openPlanViewer(title, imgSrc) {
            document.getElementById('planModalTitle').innerText = title;
            // Set image source
            const container = document.getElementById('planViewerContainer');
            container.innerHTML = `<img src="${imgSrc}" style="max-width:100%; max-height:100%; object-fit:contain; box-shadow:0 10px 30px rgba(0,0,0,0.5);">`;
            document.getElementById('planModal').style.display = 'flex';
        }

        function openImageViewer(title, imgSrc) {
            document.getElementById('imageModalTitle').innerText = title;
            // Set image source
            const container = document.getElementById('imageViewerContainer');
            container.innerHTML = `<img src="${imgSrc}" style="width:100%; height:100%; object-fit:cover;">`;
            document.getElementById('imageModal').style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // 5. Live 3D Preview Logic
        let previewScene, previewCamera, previewRenderer, houseMeshGroup;
        
        function initLivePreview3D() {
            const container = document.getElementById('live-3d-preview');
            if (!container) return;

            // Scene Setup
            previewScene = new THREE.Scene();
            previewScene.background = new THREE.Color('#f1f5f9'); // Light gray for sidebar

            // Camera
            previewCamera = new THREE.PerspectiveCamera(50, container.clientWidth / 300, 0.1, 100);
            previewCamera.position.set(20, 15, 20);
            previewCamera.lookAt(0, 0, 0);

            // Renderer
            previewRenderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            previewRenderer.setSize(container.clientWidth, 300); // Fixed height
            previewRenderer.shadowMap.enabled = true;
            container.appendChild(previewRenderer.domElement);

            // Lighting
            const ambient = new THREE.AmbientLight(0xffffff, 0.6);
            previewScene.add(ambient);
            const dirLight = new THREE.DirectionalLight(0xffffff, 0.8);
            dirLight.position.set(10, 20, 10);
            dirLight.castShadow = true;
            previewScene.add(dirLight);

            // Ground Plane
            const planeGeo = new THREE.PlaneGeometry(50, 50);
            const planeMat = new THREE.MeshLambertMaterial({ color: 0xffffff });
            const plane = new THREE.Mesh(planeGeo, planeMat);
            plane.rotation.x = -Math.PI / 2;
            plane.receiveShadow = true;
            previewScene.add(plane);

            // House Group
            houseMeshGroup = new THREE.Group();
            previewScene.add(houseMeshGroup);
            
            // Initial render
            updateHouseModel();

            // Animation Loop
            function animatePreview() {
                requestAnimationFrame(animatePreview);
                if(houseMeshGroup) {
                    houseMeshGroup.rotation.y += 0.005; // Gentle rotation
                }
                previewRenderer.render(previewScene, previewCamera);
            }
            animatePreview();

            // Resize handle
            window.addEventListener('resize', () => {
                if(!container) return;
                const width = container.clientWidth;
                previewCamera.aspect = width / 300;
                previewCamera.updateProjectionMatrix();
                previewRenderer.setSize(width, 300);
            });
        }

        // Dynamic Update Function
        function updateHouseModel() {
            if (!houseMeshGroup) return;

            // Clear previous meshes
            while(houseMeshGroup.children.length > 0){ 
                houseMeshGroup.remove(houseMeshGroup.children[0]); 
            }

            const area = parseInt(selections.area) || 1200;
            const floors = parseInt(selections.floors) || 1;
            const style = selections.style || 'Modern';

            // Scale factor based on area (roughly)
            // 1000 sqft -> scale 1
            const baseScale = Math.sqrt(area / 1000) * 4; 

            // Materials
            const wallColor = style === 'Traditional' ? 0xeddbb0 : 0xffffff;
            const roofColor = style === 'Traditional' ? 0x8b4513 : 0x333333;
            
            const wallMat = new THREE.MeshLambertMaterial({ color: wallColor });
            const roofMat = new THREE.MeshLambertMaterial({ color: roofColor });
            const glassMat = new THREE.MeshPhongMaterial({ color: 0x88ccff, transparent: true, opacity: 0.6 });

            // Build floors
            for(let i=0; i<floors; i++) {
                // Floor block
                const floorHeight = 3;
                const geo = new THREE.BoxGeometry(baseScale, floorHeight, baseScale * 0.8);
                const mesh = new THREE.Mesh(geo, wallMat);
                mesh.position.y = (i * floorHeight) + (floorHeight/2);
                mesh.castShadow = true;
                houseMeshGroup.add(mesh);

                // Windows (Simple representations)
                const winGeo = new THREE.BoxGeometry(0.2, 1.5, 1.5);
                const win1 = new THREE.Mesh(winGeo, glassMat);
                win1.position.set(baseScale/2, mesh.position.y, 1);
                houseMeshGroup.add(win1);
            }

            // Roof
            const topY = floors * 3;
            let roofMesh;
            
            if (style === 'Traditional' || style === 'Vastu') {
                // Sloped Roof
                const roofGeo = new THREE.ConeGeometry(baseScale * 0.8, 2.5, 4);
                roofMesh = new THREE.Mesh(roofGeo, roofMat);
                roofMesh.position.y = topY + 1.25;
                roofMesh.rotation.y = Math.PI/4;
            } else {
                // Flat/Modern Roof
                const roofGeo = new THREE.BoxGeometry(baseScale + 0.5, 0.5, (baseScale * 0.8) + 0.5);
                roofMesh = new THREE.Mesh(roofGeo, roofMat);
                roofMesh.position.y = topY + 0.25;
            }
            roofMesh.castShadow = true;
            houseMeshGroup.add(roofMesh);
        }

        // Initialize Preview when Loaded
        document.addEventListener('DOMContentLoaded', () => {
             // ... existing init3D call ...
             setTimeout(initLivePreview3D, 500); // Small delay to ensure container exists
        });

    </script>
</body>
</html>
