<?php
session_start();

// Redirect to login if not logged in
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
    <title>Budget Calculator - Constructa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <style>
        :root {
            --bg-color:#f6f7f2;
            --text-dark: #121212;
            --text-gray: #555555;
            --primary-green: #294033;
            --accent-green: #3d5a49;
            --card-bg: #ffffff;
            --input-bg: #f9f9f9;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* 3D Background Canvas */
        #canvas-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: -1;
            background: #f6f7f2;
            pointer-events: none;
        }

        /* Navbar */
        header {
            padding: 1.5rem 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1600px;
            margin: 0 auto;
            width: 100%;
            background: rgba(246, 247, 242, 0.9);
            backdrop-filter: blur(10px);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-green);
            text-decoration: none;
        }

        .logo i {
            font-size: 1.5rem;
        }

        nav {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        nav a {
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 500;
            font-size: 0.95rem;
            transition: color 0.2s;
        }

        nav a:hover {
            color: var(--primary-green);
        }

        /* Main Content */
        main {
            flex: 1;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            padding: 3rem;
            z-index: 2;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            animation: fadeInDown 0.8s ease-out;
        }

        .page-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #294033 0%, #3d5a49 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .page-subtitle {
            color: var(--text-gray);
            font-size: 1.1rem;
        }

        .content-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            border-radius: 24px;
            padding: 3rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            animation: fadeInUp 0.8s ease-out;
        }

        .calculator-section {
            margin-bottom: 2rem;
        }

        .calculator-section h3 {
            font-size: 1.5rem;
            color: var(--primary-green);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .calculator-section h3 i {
            font-size: 1.3rem;
        }

        .input-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }

        .form-input,
        .form-select {
            padding: 0.8rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            background-color: var(--input-bg);
            transition: border-color 0.2s;
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: var(--primary-green);
            background-color: white;
        }

        .btn-calculate {
            background: linear-gradient(135deg, #294033 0%, #3d5a49 100%);
            color: white;
            padding: 1rem 2.5rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 1.5rem;
            box-shadow: 0 10px 20px rgba(41, 64, 51, 0.2);
        }

        .btn-calculate:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(41, 64, 51, 0.3);
        }

        .result-section {
            margin-top: 3rem;
            padding: 2rem;
            background: linear-gradient(135deg, rgba(41, 64, 51, 0.05) 0%, rgba(61, 90, 73, 0.05) 100%);
            border-radius: 16px;
            border: 2px dashed var(--primary-green);
            display: none;
        }

        .result-section.active {
            display: block;
            animation: fadeInUp 0.5s ease-out;
        }

        .result-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .result-item:last-child {
            border-bottom: none;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-green);
        }

        .result-label {
            font-weight: 500;
        }

        .result-value {
            font-weight: 700;
            color: var(--accent-green);
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <!-- 3D Canvas Background -->
    <div id="canvas-container"></div>

    <!-- Navigation -->
    <header>
        <a href="homeowner.php" class="logo">
            <i class="far fa-building"></i>
            Constructa
        </a>
        <nav>
            <a href="homeowner.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="landingpage.html">Home</a>
            <a href="login.html">Logout</a>
        </nav>
    </header>

    <main>
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-calculator"></i> Budget Calculator</h1>
            <p class="page-subtitle">Estimate costs for materials, labor, and permits instantly</p>
        </div>

        <div class="content-card">
            <form id="budgetForm">
                <!-- Project Details -->
                <div class="calculator-section">
                    <h3><i class="fas fa-info-circle"></i> Project Details</h3>
                    <div class="input-row">
                        <div class="form-group">
                            <label class="form-label">Project Type</label>
                            <select class="form-select" id="projectType" required>
                                <option value="">Select Project Type</option>
                                <option value="newConstruction">New Construction</option>
                                <option value="renovation">Renovation</option>
                                <option value="extension">Extension</option>
                                <option value="commercial">Commercial</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Area (sq ft)</label>
                            <input type="number" class="form-input" id="area" placeholder="Enter area" required>
                        </div>
                    </div>
                </div>

                <!-- Materials -->
                <div class="calculator-section">
                    <h3><i class="fas fa-boxes"></i> Materials</h3>
                    <div class="input-row">
                        <div class="form-group">
                            <label class="form-label">Cement (bags)</label>
                            <input type="number" class="form-input" id="cement" placeholder="Number of bags">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Steel (tons)</label>
                            <input type="number" class="form-input" id="steel" placeholder="Amount in tons" step="0.1">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Bricks (units)</label>
                            <input type="number" class="form-input" id="bricks" placeholder="Number of bricks">
                        </div>
                    </div>
                </div>

                <!-- Labor -->
                <div class="calculator-section">
                    <h3><i class="fas fa-users"></i> Labor</h3>
                    <div class="input-row">
                        <div class="form-group">
                            <label class="form-label">Number of Workers</label>
                            <input type="number" class="form-input" id="workers" placeholder="Enter number">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Project Duration (days)</label>
                            <input type="number" class="form-input" id="duration" placeholder="Enter days">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Daily Wage per Worker ($)</label>
                            <input type="number" class="form-input" id="dailyWage" placeholder="Enter wage">
                        </div>
                    </div>
                </div>

                <!-- Additional Costs -->
                <div class="calculator-section">
                    <h3><i class="fas fa-file-invoice-dollar"></i> Additional Costs</h3>
                    <div class="input-row">
                        <div class="form-group">
                            <label class="form-label">Permits & Approvals ($)</label>
                            <input type="number" class="form-input" id="permits" placeholder="Enter amount">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Equipment Rental ($)</label>
                            <input type="number" class="form-input" id="equipment" placeholder="Enter amount">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Miscellaneous ($)</label>
                            <input type="number" class="form-input" id="misc" placeholder="Enter amount">
                        </div>
                    </div>
                </div>

                <button type="button" class="btn-calculate" onclick="calculateBudget()">
                    <i class="fas fa-calculator"></i> Calculate Total Budget
                </button>
            </form>

            <!-- Results Section -->
            <div class="result-section" id="resultSection">
                <h3 style="margin-bottom: 1.5rem; color: var(--primary-green);">
                    <i class="fas fa-chart-line"></i> Budget Breakdown
                </h3>
                <div class="result-item">
                    <span class="result-label">Materials Cost:</span>
                    <span class="result-value" id="materialsCost">$0</span>
                </div>
                <div class="result-item">
                    <span class="result-label">Labor Cost:</span>
                    <span class="result-value" id="laborCost">$0</span>
                </div>
                <div class="result-item">
                    <span class="result-label">Additional Costs:</span>
                    <span class="result-value" id="additionalCost">$0</span>
                </div>
                <div class="result-item">
                    <span class="result-label">Total Estimated Budget:</span>
                    <span class="result-value" id="totalBudget">$0</span>
                </div>
            </div>
        </div>
    </main>

    <script>
        function calculateBudget() {
            // Material costs (example prices)
            const cement = parseFloat(document.getElementById('cement').value) || 0;
            const steel = parseFloat(document.getElementById('steel').value) || 0;
            const bricks = parseFloat(document.getElementById('bricks').value) || 0;

            const cementCost = cement * 8; // $8 per bag
            const steelCost = steel * 800; // $800 per ton
            const bricksCost = bricks * 0.50; // $0.50 per brick
            const materialsCost = cementCost + steelCost + bricksCost;

            // Labor costs
            const workers = parseInt(document.getElementById('workers').value) || 0;
            const duration = parseInt(document.getElementById('duration').value) || 0;
            const dailyWage = parseFloat(document.getElementById('dailyWage').value) || 0;
            const laborCost = workers * duration * dailyWage;

            // Additional costs
            const permits = parseFloat(document.getElementById('permits').value) || 0;
            const equipment = parseFloat(document.getElementById('equipment').value) || 0;
            const misc = parseFloat(document.getElementById('misc').value) || 0;
            const additionalCost = permits + equipment + misc;

            // Total
            const totalBudget = materialsCost + laborCost + additionalCost;

            // Display results
            document.getElementById('materialsCost').textContent = `$${materialsCost.toLocaleString('en-US', { minimumFractionDigits: 2 })}`;
            document.getElementById('laborCost').textContent = `$${laborCost.toLocaleString('en-US', { minimumFractionDigits: 2 })}`;
            document.getElementById('additionalCost').textContent = `$${additionalCost.toLocaleString('en-US', { minimumFractionDigits: 2 })}`;
            document.getElementById('totalBudget').textContent = `$${totalBudget.toLocaleString('en-US', { minimumFractionDigits: 2 })}`;

            // Show results
            document.getElementById('resultSection').classList.add('active');
        }

        // 3D Background Animation
        const initBackground3D = () => {
            const container = document.getElementById('canvas-container');
            if (!container) return;

            const scene = new THREE.Scene();
            scene.background = new THREE.Color('#f6f7f2');

            const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
            camera.position.z = 8;
            camera.position.y = 2;

            const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            container.appendChild(renderer.domElement);

            const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
            scene.add(ambientLight);

            const mainLight = new THREE.DirectionalLight(0xffffff, 0.8);
            mainLight.position.set(10, 10, 10);
            scene.add(mainLight);

            const cityGroup = new THREE.Group();
            scene.add(cityGroup);

            const buildingMaterial = new THREE.MeshPhongMaterial({
                color: 0x294033,
                transparent: true,
                opacity: 0.1
            });

            const edgeMaterial = new THREE.LineBasicMaterial({
                color: 0x294033,
                transparent: true,
                opacity: 0.3
            });

            for (let x = -10; x < 10; x++) {
                for (let z = -10; z < 10; z++) {
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
                    building.position.set(x * 3, -2, z * 3);
                    cityGroup.add(building);
                }
            }

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

        if (typeof THREE !== 'undefined') {
            initBackground3D();
        }
    </script>
</body>

</html>
