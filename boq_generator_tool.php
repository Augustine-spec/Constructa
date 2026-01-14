<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'engineer') {
    header('Location: login.html');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOQ Generator & Cost Estimator - Constructa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <style>
        :root {
            --primary: #294033;
            --primary-light: #3d5a49;
            --secondary: #eab308;
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: transparent;
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* 3D Background Canvas */
        #canvas-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: -1;
            background: #f8fafc;
            pointer-events: none;
        }

        /* Navbar */
        nav {
            background: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .nav-logo {
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-link {
            text-decoration: none;
            color: var(--text-main);
            font-weight: 600;
            font-size: 0.95rem;
            transition: var(--transition);
            padding: 0.75rem 1.5rem;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .nav-link:hover {
            color: var(--primary);
            border-color: var(--primary);
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        /* Main Layout */
        .app-container {
            display: flex;
            flex: 1;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            height: calc(100vh - 70px);
        }

        /* Wizard Section (Left) */
        .wizard-section {
            flex: 2;
            padding: 3rem;
            position: relative;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        /* Live Preview Section (Right) */
        .preview-section {
            flex: 1;
            background: #ffffff;
            border-left: 1px solid var(--border-color);
            padding: 2.5rem;
            display: flex;
            flex-direction: column;
            box-shadow: -5px 0 20px rgba(0,0,0,0.02);
        }

        /* Progress Bar */
        .progress-header {
            margin-bottom: 3rem;
        }
        
        .progress-track {
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .progress-fill {
            height: 100%;
            background: var(--primary);
            width: 0%;
            transition: width 0.5s ease-in-out;
        }

        .step-indicator {
            font-size: 0.9rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        /* Step Content */
        .step-container {
            position: relative;
            flex: 1;
            display: flex;
        }

        .step {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            opacity: 0;
            transform: translateY(20px);
            pointer-events: none;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            display: none;
        }

        .step.active {
            opacity: 1;
            transform: translateY(0);
            pointer-events: all;
            display: block;
            position: relative;
        }

        .step-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .step-desc {
            font-size: 1.1rem;
            color: var(--text-muted);
            margin-bottom: 2.5rem;
        }

        /* Big Input Style */
        .big-input {
            width: 100%;
            font-size: 2.5rem;
            padding: 1rem;
            border: none;
            border-bottom: 3px solid var(--border-color);
            background: transparent;
            font-weight: 700;
            color: var(--primary);
            outline: none;
            transition: var(--transition);
        }

        .big-input:focus {
            border-bottom-color: var(--primary);
        }

        .big-input::placeholder {
            color: #cbd5e1;
        }

        /* Plus/Minus Counter */
        .counter-wrapper {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-top: 1rem;
        }

        .counter-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 2px solid var(--border-color);
            background: white;
            font-size: 1.5rem;
            color: var(--primary);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .counter-btn:hover {
            border-color: var(--primary);
            background: var(--primary);
            color: white;
        }

        .counter-display {
            font-size: 3rem;
            font-weight: 800;
            min-width: 80px;
            text-align: center;
        }

        /* Selection Cards */
        .options-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }

        .selection-card {
            background: white;
            border: 2px solid var(--border-color);
            border-radius: 16px;
            padding: 2rem;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .selection-card:hover {
            border-color: #cbd5e1;
            transform: translateY(-4px);
            box-shadow: var(--shadow);
        }

        .selection-card.selected {
            border-color: var(--primary);
            background: #f0fdf4;
            box-shadow: 0 0 0 2px rgba(41, 64, 51, 0.1);
        }

        .selection-card .icon {
            font-size: 2rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
            transition: var(--transition);
        }

        .selection-card.selected .icon {
            color: var(--primary);
        }

        .card-title {
            font-weight: 700;
            font-size: 1.2rem;
        }

        .card-subtitle {
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .check-mark {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transform: scale(0);
            transition: var(--transition);
        }

        .selection-card.selected .check-mark {
            opacity: 1;
            transform: scale(1);
        }

        /* Navigation Buttons */
        .wizard-nav {
            margin-top: auto;
            padding-top: 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn {
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-secondary {
            background: var(--bg-color);
            color: var(--text-muted);
        }

        .btn-secondary:hover {
            background: #e2e8f0;
            color: var(--text-main);
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(41, 64, 51, 0.3);
        }

        .btn-primary:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(41, 64, 51, 0.4);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }

        /* Preview Sidebar */
        .preview-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .preview-card {
            background: var(--bg-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .preview-label {
            font-size: 0.85rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .preview-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
        }

        .result-display {
            background: white;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .boq-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem;
            border-bottom: 1px solid var(--border-color);
        }

        .boq-item:last-child {
            border-bottom: none;
        }

        @media (max-width: 900px) {
            .app-container {
                flex-direction: column;
                height: auto;
            }
            
            .preview-section {
                border-left: none;
                border-top: 1px solid var(--border-color);
            }

            .options-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div id="canvas-container"></div>
    
    <!-- Navbar -->
    <nav>
        <a href="resources.php" class="nav-logo">
            <i class="fas fa-file-invoice-dollar"></i>
            Constructa
        </a>
        <a href="resources.php" class="nav-link">
            <i class="fas fa-home"></i>
            Back to Resources
        </a>
    </nav>

    <div class="app-container">
        <!-- Left Wizard -->
        <div class="wizard-section">
            <div class="progress-header">
                <div class="step-indicator">Step <span id="currentStepNum">1</span> of 5</div>
                <div class="progress-track">
                    <div class="progress-fill" id="progressBar"></div>
                </div>
            </div>

            <div class="step-container">
                <!-- STEP 1: Building Type -->
                <div class="step active" id="step1">
                    <h2 class="step-title">Select the building type.</h2>
                    <p class="step-desc">This affects material specifications and requirements.</p>
                    <div class="options-grid">
                        <div class="selection-card selected" onclick="selectCard('buildingType', 'residential', this)" data-value="residential">
                            <div class="check-mark"><i class="fas fa-check"></i></div>
                            <div class="icon"><i class="fas fa-home"></i></div>
                            <div class="card-title">Residential</div>
                            <div class="card-subtitle">Houses, apartments, and residential buildings</div>
                        </div>
                        <div class="selection-card" onclick="selectCard('buildingType', 'commercial', this)" data-value="commercial">
                            <div class="check-mark"><i class="fas fa-check"></i></div>
                            <div class="icon"><i class="fas fa-building"></i></div>
                            <div class="card-title">Commercial</div>
                            <div class="card-subtitle">Offices, shops, and commercial spaces</div>
                        </div>
                        <div class="selection-card" onclick="selectCard('buildingType', 'institutional', this)" data-value="institutional">
                            <div class="check-mark"><i class="fas fa-check"></i></div>
                            <div class="icon"><i class="fas fa-university"></i></div>
                            <div class="card-title">Institutional</div>
                            <div class="card-subtitle">Schools, hospitals, public buildings</div>
                        </div>
                    </div>
                    <input type="hidden" id="buildingType" value="residential">
                </div>

                <!-- STEP 2: Built-up Area -->
                <div class="step" id="step2">
                    <h2 class="step-title">Enter the total built-up area.</h2>
                    <p class="step-desc">This is the total floor area including all floors.</p>
                    <div class="form-group">
                        <input type="number" id="builtupArea" class="big-input" placeholder="e.g. 1000" step="0.1" min="1" />
                        <label style="display:block; margin-top:1rem; color:var(--text-muted);">Square Meters (m²)</label>
                    </div>
                </div>

                <!-- STEP 3: Number of Floors -->
                <div class="step" id="step3">
                    <h2 class="step-title">How many floors?</h2>
                    <p class="step-desc">Include the ground floor in your count.</p>
                    <div class="counter-wrapper">
                        <button type="button" class="counter-btn" onclick="adjustFloors(-1)"><i class="fas fa-minus"></i></button>
                        <div class="counter-display" id="floorDisplay">3</div>
                        <button type="button" class="counter-btn" onclick="adjustFloors(1)"><i class="fas fa-plus"></i></button>
                    </div>
                    <input type="hidden" id="floorCount" value="3">
                </div>

                <!-- STEP 4: Plinth Height -->
                <div class="step" id="step4">
                    <h2 class="step-title">Enter the plinth height.</h2>
                    <p class="step-desc">Standard floor-to-floor height.</p>
                    <div class="form-group">
                        <input type="number" id="plinthHeight" class="big-input" placeholder="e.g. 3.0" step="0.1" min="2" value="3.0" />
                        <label style="display:block; margin-top:1rem; color:var(--text-muted);">Meters (m)</label>
                    </div>
                </div>

                <!-- STEP 5: Results -->
                <div class="step" id="step5">
                    <h2 class="step-title" style="text-align: center;">BOQ Generated!</h2>
                    <p class="step-desc" style="text-align: center;">Based on IS 456:2000 and market rates.</p>
                    
                    <div id="boqResults" style="max-height: 400px; overflow-y: auto;">
                        <!-- BOQ items will be injected here -->
                    </div>

                    <div style="text-align: center; margin-top: 2rem;">
                        <button type="button" class="btn btn-primary" onclick="downloadPDF()">
                            <i class="fas fa-file-pdf"></i> Download BOQ PDF
                        </button>
                    </div>
                </div>
            </div>

            <div class="wizard-nav">
                <button type="button" class="btn btn-secondary" id="prevBtn" onclick="changeStep(-1)" disabled>
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <button type="button" class="btn btn-primary" id="nextBtn" onclick="changeStep(1)">
                    Next Step <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- Right Preview -->
        <div class="preview-section">
            <h3 class="preview-title"><i class="fas fa-chart-bar"></i> Live Preview</h3>
            
            <div class="preview-card">
                <div class="preview-label">3D Visualization</div>
                <canvas id="buildingCanvas" width="300" height="250" style="width: 100%; border-radius: 8px; background: white;"></canvas>
            </div>

            <div class="preview-label">Estimated Cost</div>
            <div class="preview-card" style="background: var(--primary); color: white;">
                <div class="preview-label" style="color: rgba(255,255,255,0.8);">Total Budget</div>
                <div style="font-size: 2rem; font-weight: 800;" id="totalCost">₹ 0</div>
            </div>

            <div class="preview-label">Parameters</div>
            <div class="result-display">
                <div class="preview-label">Building Type</div>
                <div class="preview-value" id="prevBuildingType">Residential</div>
            </div>

            <div class="result-display">
                <div class="preview-label">Built-up Area</div>
                <div class="preview-value" id="prevArea">-</div>
            </div>

            <div class="result-display">
                <div class="preview-label">Floors</div>
                <div class="preview-value" id="prevFloors">3</div>
            </div>
        </div>
    </div>

    <script>
        // State
        let currentStep = 1;
        const totalSteps = 5;
        let boqData = [];
        let grandTotal = 0;

        // Initialize
        updateProgress();
        draw3DBuilding();

        // Navigation
        function changeStep(direction) {
            if (direction === 1 && !validateStep(currentStep)) return;

            const currentEl = document.getElementById(`step${currentStep}`);
            currentEl.classList.remove('active');

            currentStep += direction;
            if (currentStep < 1) currentStep = 1;
            if (currentStep > totalSteps) currentStep = totalSteps;

            const nextEl = document.getElementById(`step${currentStep}`);
            nextEl.classList.add('active');

            updateProgress();
            updatePreview();

            // Generate BOQ on step 5
            if (currentStep === 5) {
                generateBOQ();
            }
        }

        function validateStep(step) {
            switch(step) {
                case 2:
                    const area = document.getElementById('builtupArea').value;
                    if (!area || parseFloat(area) <= 0) {
                        alert('Please enter a valid built-up area');
                        return false;
                    }
                    break;
                case 4:
                    const height = document.getElementById('plinthHeight').value;
                    if (!height || parseFloat(height) < 2) {
                        alert('Please enter a valid plinth height (minimum 2m)');
                        return false;
                    }
                    break;
            }
            return true;
        }

        function updateProgress() {
            const progress = ((currentStep - 1) / (totalSteps - 1)) * 100;
            document.getElementById('progressBar').style.width = progress + '%';
            document.getElementById('currentStepNum').textContent = currentStep;

            document.getElementById('prevBtn').disabled = currentStep === 1;
            
            if (currentStep === totalSteps) {
                document.getElementById('nextBtn').style.display = 'none';
            } else {
                document.getElementById('nextBtn').style.display = 'flex';
            }
        }

        function updatePreview() {
            const buildingType = document.getElementById('buildingType').value;
            const area = document.getElementById('builtupArea').value;
            const floors = document.getElementById('floorCount').value;

            const typeMap = {
                'residential': 'Residential',
                'commercial': 'Commercial',
                'institutional': 'Institutional'
            };

            document.getElementById('prevBuildingType').textContent = typeMap[buildingType] || 'Residential';
            document.getElementById('prevArea').textContent = area ? area + ' m²' : '-';
            document.getElementById('prevFloors').textContent = floors;

            // Update rough cost estimate
            if (area && parseFloat(area) > 0) {
                const costPerSqm = buildingType === 'commercial' ? 2500 : buildingType === 'institutional' ? 2800 : 2200;
                const roughCost = parseFloat(area) * costPerSqm * parseFloat(floors);
                document.getElementById('totalCost').textContent = '₹ ' + roughCost.toLocaleString('en-IN');
            }

            draw3DBuilding();
        }

        function selectCard(type, value, element) {
            const parent = element.parentElement;
            parent.querySelectorAll('.selection-card').forEach(card => {
                card.classList.remove('selected');
            });
            element.classList.add('selected');
            document.getElementById(type).value = value;
            updatePreview();
        }

        function adjustFloors(delta) {
            const input = document.getElementById('floorCount');
            const display = document.getElementById('floorDisplay');
            let value = parseInt(input.value) + delta;
            if (value < 1) value = 1;
            if (value > 20) value = 20;
            input.value = value;
            display.textContent = value;
            updatePreview();
        }

        // Add input listeners
        document.getElementById('builtupArea').addEventListener('input', updatePreview);
        document.getElementById('plinthHeight').addEventListener('input', updatePreview);

        function draw3DBuilding() {
            const canvas = document.getElementById('buildingCanvas');
            const ctx = canvas.getContext('2d');
            
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            const floors = parseInt(document.getElementById('floorCount').value) || 3;
            const area = parseFloat(document.getElementById('builtupArea').value) || 1000;
            
            // Calculate proportions
            const floorHeight = 30;
            const baseWidth = 180;
            const depth = 120;
            const offsetX = 60;
            const offsetY = 20;
            
            // Draw building (isometric view)
            for (let i = 0; i < floors; i++) {
                const y = offsetY + 200 - (i + 1) * floorHeight;
                
                // Front face
                ctx.fillStyle = i % 2 === 0 ? '#e8f5e9' : '#f0fdf4';
                ctx.strokeStyle = '#294033';
                ctx.lineWidth = 2;
                ctx.fillRect(offsetX, y, baseWidth, floorHeight);
                ctx.strokeRect(offsetX, y, baseWidth, floorHeight);
                
                // Windows
                const windowsPerFloor = 6;
                for (let w = 0; w < windowsPerFloor; w++) {
                    const wx = offsetX + 20 + w * 25;
                    const wy = y + 8;
                    ctx.fillStyle = '#b3d9ff';
                    ctx.fillRect(wx, wy, 15, 15);
                    ctx.strokeStyle = '#294033';
                    ctx.lineWidth = 1;
                    ctx.strokeRect(wx, wy, 15, 15);
                }
            }
            
            // Roof
            const roofY = offsetY + 200 - floors * floorHeight;
            ctx.fillStyle = '#8b4513';
            ctx.beginPath();
            ctx.moveTo(offsetX - 10, roofY);
            ctx.lineTo(offsetX + baseWidth / 2, roofY - 20);
            ctx.lineTo(offsetX + baseWidth + 10, roofY);
            ctx.closePath();
            ctx.fill();
            ctx.strokeStyle = '#294033';
            ctx.lineWidth = 2;
            ctx.stroke();
            
            // Labels
            ctx.font = 'bold 12px Inter';
            ctx.fillStyle = '#294033';
            ctx.textAlign = 'center';
            ctx.fillText(`${floors} Floor${floors > 1 ? 's' : ''}`, offsetX + baseWidth / 2, offsetY + 230);
        }

        // Initial visualization
        draw3DBuilding();

        // BOQ Generation (continued in next part)
        function generateBOQ() {
            const buildingType = document.getElementById('buildingType').value;
            const area = parseFloat(document.getElementById('builtupArea').value);
            const floors = parseInt(document.getElementById('floorCount').value);
            const plinthHeight = parseFloat(document.getElementById('plinthHeight').value);

            boqData = [];
            
            // Simplified BOQ generation (you can expand this)
            const excavationQty = area * 1.5;
            boqData.push({ item: 'Earthwork Excavation', unit: 'm³', quantity: excavationQty.toFixed(2), rate: 150, amount: excavationQty * 150 });
            
            const pccQty = area * 0.10;
            boqData.push({ item: 'PCC 1:4:8', unit: 'm³', quantity: pccQty.toFixed(2), rate: 4500, amount: pccQty * 4500 });
            
            const foundationQty = area * 0.30;
            boqData.push({ item: 'Foundation RCC M25', unit: 'm³', quantity: foundationQty.toFixed(2), rate: 7500, amount: foundationQty * 7500 });
            
            const slabQty = area * floors * 0.125;
            boqData.push({ item: 'RCC for Slabs M25', unit: 'm³', quantity: slabQty.toFixed(2), rate: 7800, amount: slabQty * 7800 });
            
            const steelQty = (foundationQty + slabQty) * 110;
            boqData.push({ item: 'Steel (Fe 415)', unit: 'kg', quantity: steelQty.toFixed(2), rate: 65, amount: steelQty * 65 });
            
            boqData.push({ item: 'Brick Masonry', unit: 'm³', quantity: (area * 0.5).toFixed(2), rate: 5200, amount: area * 0.5 * 5200 });
            boqData.push({ item: 'Plastering', unit: 'm²', quantity: (area * 2).toFixed(2), rate: 280, amount: area * 2 * 280 });
            boqData.push({ item: 'Flooring', unit: 'm²', quantity: area.toFixed(2), rate: 650, amount: area * 650 });
            boqData.push({ item: 'Electrical Work', unit: 'LS', quantity: '1.00', rate: area * 500, amount: area * 500 });
            boqData.push({ item: 'Plumbing Work', unit: 'LS', quantity: '1.00', rate: area * 400, amount: area * 400 });

            grandTotal = boqData.reduce((sum, item) => sum + item.amount, 0);
            const contingency = grandTotal * 0.05;
            const total = grandTotal + contingency;

            displayBOQ(contingency, total);
        }

        function displayBOQ(contingency, total) {
            let html = '';
            boqData.forEach((item, idx) => {
                html += `
                    <div class="result-display">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <strong>${idx + 1}. ${item.item}</strong>
                            <span style="color: var(--primary); font-weight: 700;">₹ ${item.amount.toLocaleString('en-IN')}</span>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-muted);">
                            ${item.quantity} ${item.unit} × ₹${item.rate.toLocaleString('en-IN')}
                        </div>
                    </div>
                `;
            });

            html += `
                <div class="result-display" style="background: #f0fdf4; border-color: var(--primary);">
                    <div style="display: flex; justify-content: space-between; font-size: 1.2rem;">
                        <strong>GRAND TOTAL</strong>
                        <strong style="color: var(--primary);">₹ ${total.toLocaleString('en-IN')}</strong>
                    </div>
                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.5rem;">
                        (Includes 5% contingency)
                    </div>
                </div>
            `;

            document.getElementById('boqResults').innerHTML = html;
            document.getElementById('totalCost').textContent = '₹ ' + total.toLocaleString('en-IN');
        }

        function downloadPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            doc.setFontSize(18);
            doc.setFont('helvetica', 'bold');
            doc.text('BILL OF QUANTITIES', 105, 20, { align: 'center' });

            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            doc.text(`Generated: ${new Date().toLocaleString('en-IN')}`, 20, 30);

            const tableData = boqData.map((item, idx) => [
                idx + 1,
                item.item,
                item.unit,
                item.quantity,
                '₹ ' + item.rate.toLocaleString('en-IN'),
                '₹ ' + item.amount.toLocaleString('en-IN')
            ]);

            doc.autoTable({
                head: [['S.No', 'Description', 'Unit', 'Qty', 'Rate', 'Amount']],
                body: tableData,
                startY: 40,
                theme: 'grid',
                headStyles: { fillColor: [41, 64, 51] }
            });

            const finalY = doc.lastAutoTable.finalY + 10;
            const contingency = grandTotal * 0.05;
            const total = grandTotal + contingency;

            doc.setFont('helvetica', 'bold');
            doc.text('GRAND TOTAL:', 130, finalY);
            doc.text(`₹ ${total.toLocaleString('en-IN')}`, 170, finalY);

            doc.save(`BOQ_${Date.now()}.pdf`);
        }

        // 3D Background (same as budget calculator)
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

            let mouseX = 0;
            let mouseY = 0;

            document.addEventListener('mousemove', (event) => {
                mouseX = (event.clientX - window.innerWidth / 2) * 0.001;
                mouseY = (event.clientY - window.innerHeight / 2) * 0.001;
            });

            let scrollY = 0;
            const wizardSection = document.querySelector('.wizard-section');
            if (wizardSection) {
                wizardSection.addEventListener('scroll', () => {
                     scrollY = wizardSection.scrollTop * 0.001;
                });
            }

            const animate = () => {
                requestAnimationFrame(animate);

                cityGroup.rotation.y += 0.001;
                floatGroup.rotation.y += 0.005;
                floatGroup.position.y = Math.sin(Date.now() * 0.001) * 0.5 + 0.5;

                cityGroup.rotation.x += 0.05 * (mouseY - cityGroup.rotation.x);
                cityGroup.rotation.y += 0.05 * (mouseX - cityGroup.rotation.y);

                camera.position.y = 2 - scrollY * 2;
                camera.position.z = 8 + scrollY * 5;

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
