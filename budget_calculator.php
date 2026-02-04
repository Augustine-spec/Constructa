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
    <title>Construction Estimator Pro - Constructa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <style>
        :root {
            --primary: #294033;
            --primary-light: #3d5a49;
            --secondary: #eab308; /* Gold/Yellow for accents */
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
            color: var(--text-muted);
            font-weight: 500;
            font-size: 0.95rem;
            transition: var(--transition);
        }

        .nav-link:hover {
            color: var(--primary);
        }

        /* Main Layout */
        .app-container {
            display: flex;
            flex: 1;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            height: calc(100vh - 70px); /* Adjust for navbar */
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
            display: flex; /* Helps center content if needed */
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
            display: none; /* Changed from just opacity to display none for flow */
        }

        .step.active {
            opacity: 1;
            transform: translateY(0);
            pointer-events: all;
            display: block;
            position: relative; /* Make it take space */
        }

        .step.exit-left {
            opacity: 0;
            transform: translateX(-50px);
        }

        .step.exit-right {
           /* unused for now, simpler one-way flow */
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

        /* Step 1: Input Styles */
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

        /* Step 2: Plus/Minus Counter */
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

        /* Step 3, 4: Grid Cards */
        .options-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }
        
        @media (max-width: 900px) {
            .options-grid {
                 grid-template-columns: 1fr;
            }
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
            background: #f0fdf4; /* Light green tint */
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

        .card-price {
            margin-top: auto;
            padding-top: 1rem;
            font-weight: 600;
            color: var(--primary);
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

        /* Step 5: Feature Tiles (Smaller) */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
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

        /* Preview Sidebar Styles */
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

        .preview-amount {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
        }

        .preview-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            opacity: 0.8;
        }

        .preview-item {
            display: flex;
            justify-content: space-between;
            font-size: 0.95rem;
        }
        
        .preview-item span:first-child {
            color: var(--text-muted);
        }
        
        .preview-item span:last-child {
            font-weight: 600;
        }

        /* Final Breakdown Chart (CSS Bars) */
        .breakdown-bars {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            margin-top: 2rem;
        }

        .bar-group {
            width: 100%;
        }

        .bar-label {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            margin-bottom: 0.4rem;
        }

        .bar-track {
            height: 10px;
            background: #e2e8f0;
            border-radius: 5px;
            overflow: hidden;
        }

        .bar-value {
            height: 100%;
            border-radius: 5px;
            animation: growBar 1s ease-out forwards;
            width: 0; 
        }

        .bar-base { background: #3b82f6; }
        .bar-loc { background: #8b5cf6; }
        .bar-floor { background: #f59e0b; }
        .bar-extra { background: #10b981; }
        .bar-cont { background: #ef4444; }

        @keyframes growBar {
            from { width: 0; }
        }

        .final-total-display {
            text-align: center;
            margin: 2rem 0;
            padding: 2rem;
            background: var(--primary);
            color: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(41, 64, 51, 0.25);
        }
        
        .error-toast {
            position: fixed;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            background: #ef4444;
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            box-shadow: 0 10px 20px rgba(239, 68, 68, 0.3);
            display: none;
            z-index: 100;
            animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes popIn {
            from { transform: translateX(-50%) translateY(20px) scale(0.8); opacity: 0; }
            to { transform: translateX(-50%) translateY(0) scale(1); opacity: 1; }
        }

    </style>
</head>

<body>
    <div id="canvas-container"></div>
    <!-- Navbar -->
    <nav>
        <a href="homeowner.php" class="nav-logo">
            <i class="far fa-building"></i>
            Constructa
        </a>
        <a href="homeowner.php" class="nav-link">Exit Estimator</a>
    </nav>

    <div class="app-container">
        <!-- Toast -->
        <div id="errorToast" class="error-toast">
            <i class="fas fa-exclamation-circle"></i> <span id="errorMsg">Please fill in this field</span>
        </div>

        <!-- Left Wizard -->
        <div class="wizard-section">
            <div class="progress-header">
                <div class="step-indicator">Step <span id="currentStepNum">1</span> of 6</div>
                <div class="progress-track">
                    <div class="progress-fill" id="progressBar"></div>
                </div>
            </div>

            <form id="wizardForm">
                <!-- STEP 1: Plot Size -->
                <div class="step active" id="step1">
                    <h2 class="step-title">Let's start with your plot.</h2>
                    <p class="step-desc">Enter the total area of your plot in square feet.</p>
                    <div class="form-group">
                        <input type="text" inputmode="decimal" id="plotSize" class="big-input" placeholder="e.g. 1200" onkeypress="return (event.charCode >= 48 && event.charCode <= 57) || event.charCode == 46" />
                        <label style="display:block; margin-top:1rem; color:var(--text-muted);">Square Feet (sq. ft)</label>
                    </div>
                </div>

                <!-- STEP 2: Floors -->
                <div class="step" id="step2">
                    <h2 class="step-title">How many floors?</h2>
                    <p class="step-desc">Include the ground floor in your count.</p>
                    <div class="counter-wrapper">
                        <button type="button" class="counter-btn" onclick="adjustFloors(-1)"><i class="fas fa-minus"></i></button>
                        <div class="counter-display" id="floorDisplay">1</div>
                        <button type="button" class="counter-btn" onclick="adjustFloors(1)"><i class="fas fa-plus"></i></button>
                    </div>
                    <input type="hidden" id="floors" value="1">
                </div>

                <!-- STEP 3: Quality -->
                <div class="step" id="step3">
                    <h2 class="step-title">Choose construction quality.</h2>
                    <p class="step-desc">This determines the materials and finish.</p>
                    <div class="options-grid">
                        <div class="selection-card" onclick="selectCard('quality', '1600', this)" data-value="1600">
                            <div class="check-mark"><i class="fas fa-check"></i></div>
                            <div class="icon"><i class="fas fa-layer-group"></i></div>
                            <div class="card-title">Basic</div>
                            <div class="card-subtitle">Essential standard materials. Economical choice.</div>
                            <div class="card-price">₹1,600 / sq.ft</div>
                        </div>
                        <div class="selection-card selected" onclick="selectCard('quality', '2200', this)" data-value="2200">
                            <div class="check-mark"><i class="fas fa-check"></i></div>
                            <div class="icon"><i class="fas fa-star"></i></div>
                            <div class="card-title">Standard</div>
                            <div class="card-subtitle">High-quality branded materials. Best value.</div>
                            <div class="card-price">₹2,200 / sq.ft</div>
                        </div>
                        <div class="selection-card" onclick="selectCard('quality', '3000', this)" data-value="3000">
                            <div class="check-mark"><i class="fas fa-check"></i></div>
                            <div class="icon"><i class="fas fa-crown"></i></div>
                            <div class="card-title">Premium</div>
                            <div class="card-subtitle">Luxury finishes, superior fittings & design.</div>
                            <div class="card-price">₹3,000 / sq.ft</div>
                        </div>
                    </div>
                    <input type="hidden" id="quality" value="2200">
                </div>

                <!-- STEP 4: Location -->
                <div class="step" id="step4">
                    <h2 class="step-title">Where is the site located?</h2>
                    <p class="step-desc">Location affects labor and transportation costs.</p>
                    <div class="options-grid">
                        <div class="selection-card" onclick="selectCard('location', '0.9', this)" data-value="0.9">
                            <div class="check-mark"><i class="fas fa-check"></i></div>
                            <div class="icon"><i class="fas fa-tractor"></i></div>
                            <div class="card-title">Rural</div>
                            <div class="card-subtitle">Outer areas with easier access but less availability.</div>
                            <div class="card-price">0.9x Factor</div>
                        </div>
                        <div class="selection-card selected" onclick="selectCard('location', '1.0', this)" data-value="1.0">
                            <div class="check-mark"><i class="fas fa-check"></i></div>
                            <div class="icon"><i class="fas fa-city"></i></div>
                            <div class="card-title">Semi-Urban</div>
                            <div class="card-subtitle">Developing areas or town outskirts. Standard rates.</div>
                            <div class="card-price">1.0x Factor</div>
                        </div>
                        <div class="selection-card" onclick="selectCard('location', '1.1', this)" data-value="1.1">
                            <div class="check-mark"><i class="fas fa-check"></i></div>
                            <div class="icon"><i class="fas fa-building"></i></div>
                            <div class="card-title">Urban / Metro</div>
                            <div class="card-subtitle">City centers, higher labor and transport costs.</div>
                            <div class="card-price">1.1x Factor</div>
                        </div>
                    </div>
                    <input type="hidden" id="location" value="1.0">
                </div>

                <!-- STEP 5: Features -->
                <div class="step" id="step5">
                    <h2 class="step-title">Add optional features.</h2>
                    <p class="step-desc">Select any additional requirements.</p>
                    <div class="features-grid">
                        <div class="selection-card feature-tile" onclick="toggleFeature(this, 150000)">
                            <div class="check-mark"><i class="fas fa-check"></i></div>
                            <div class="icon"><i class="fas fa-car"></i></div>
                            <div class="card-title">Parking</div>
                            <div class="card-subtitle">Covered parking space</div>
                            <div class="card-price">+ ₹1.5L</div>
                        </div>
                        <div class="selection-card feature-tile" onclick="toggleFeature(this, 200000)">
                            <div class="check-mark"><i class="fas fa-check"></i></div>
                            <div class="icon"><i class="fas fa-utensils"></i></div>
                            <div class="card-title">Modular Kitchen</div>
                            <div class="card-subtitle">Modern fittings</div>
                            <div class="card-price">+ ₹2.0L</div>
                        </div>
                        <div class="selection-card feature-tile" onclick="toggleFeature(this, 120, true)">
                            <div class="check-mark"><i class="fas fa-check"></i></div>
                            <div class="icon"><i class="fas fa-cloud"></i></div>
                            <div class="card-title">False Ceiling</div>
                            <div class="card-subtitle">Per sq.ft design</div>
                            <div class="card-price">+ ₹120/sft</div>
                        </div>
                        <div class="selection-card feature-tile" onclick="toggleFeature(this, 80000)">
                            <div class="check-mark"><i class="fas fa-check"></i></div>
                            <div class="icon"><i class="fas fa-solar-panel"></i></div>
                            <div class="card-title">Solar Panels</div>
                            <div class="card-subtitle">1kW Rooftop system</div>
                            <div class="card-price">+ ₹80k</div>
                        </div>
                        <div class="selection-card feature-tile" onclick="toggleFeature(this, 700000, false, true)">
                            <div class="check-mark"><i class="fas fa-check"></i></div>
                            <div class="icon"><i class="fas fa-elevator"></i></div>
                            <div class="card-title">Lift</div>
                            <div class="card-subtitle">Home elevator (Floor > 2)</div>
                            <div class="card-price">+ ₹7.0L</div>
                        </div>
                    </div>
                </div>

                <!-- STEP 6: Summary -->
                <div class="step" id="step6">
                    <h2 class="step-title" style="text-align: center;">Your Estimate is Ready!</h2>
                    <p class="step-desc" style="text-align: center;">Based on current market rates in India.</p>
                    
                    <div class="final-total-display">
                        <div style="font-size: 1rem; opacity: 0.8; margin-bottom: 0.5rem;">Total Estimated Budget</div>
                        <div id="finalAmountDisplay" style="font-size: 3.5rem; font-weight: 800;">₹0</div>
                        <div style="font-size: 0.9rem; margin-top: 1rem; opacity: 0.7;">Includes materials, labor, and 10% contingency</div>
                    </div>

                    <div class="breakdown-bars">
                        <!-- Bars injected by JS -->
                    </div>
                    
                    <div style="text-align: center; margin-top: 3rem;">
                        <button type="button" class="btn btn-secondary" onclick="restartWizard()">
                           <i class="fas fa-redo"></i> Start New Estimate
                        </button>
                    </div>
                </div>

            </form>

            <div class="wizard-nav" id="wizardNav">
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
            <h3 class="preview-title"><i class="fas fa-calculator"></i> Live Preview</h3>
            
            <div class="preview-card">
                <div class="preview-label">Estimated Cost</div>
                <div class="preview-amount" id="liveTotal">₹0</div>
            </div>

            <div class="preview-label">Current Selections</div>
            <ul class="preview-list">
                <li class="preview-item">
                    <span>Area</span>
                    <span id="prevArea">-</span>
                </li>
                <li class="preview-item">
                    <span>Floors</span>
                    <span id="prevFloors">1</span>
                </li>
                <li class="preview-item">
                    <span>Quality</span>
                    <span id="prevQuality">Standard</span>
                </li>
                <li class="preview-item">
                    <span>Location</span>
                    <span id="prevLocation">Semi-Urban</span>
                </li>
                <li class="preview-item">
                    <span>Features</span>
                    <span id="prevFeatures">None</span>
                </li>
            </ul>
        </div>
    </div>

    <script>
        // State
        let currentStep = 1;
        const totalSteps = 6;
        let features = [];
        
        // DOM Elements
        const steps = document.querySelectorAll('.step');
        const progressBar = document.getElementById('progressBar');
        const currentStepNum = document.getElementById('currentStepNum');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const toast = document.getElementById('errorToast');
        
        // Initial Calculation
        updateProgress();
        calculateLive();

        // ------------------
        // WIZARD NAVIGATION
        // ------------------
        function changeStep(direction) {
            // Validate before going 'next'
            if (direction === 1 && !validateStep(currentStep)) return;

            // Animate Out
            const currentEl = document.getElementById(`step${currentStep}`);
            
            // Just simple hide/show with crossfade logic
            currentEl.classList.remove('active');
            
            // Timeout to allow transition (pseudo) or just structural update
            // For simplicity and robustness without complex JS animations:
            currentStep += direction;
            
            // Bounds check
            if (currentStep < 1) currentStep = 1;
            if (currentStep > totalSteps) currentStep = totalSteps;

            const nextEl = document.getElementById(`step${currentStep}`);
            nextEl.classList.add('active');

            updateProgress();
            
            // Specially handle final step
            if (currentStep === 6) {
                document.getElementById('wizardNav').style.display = 'none'; // Hide nav
                renderFinalBreakdown();
            } else {
                document.getElementById('wizardNav').style.display = 'flex';
            }
        }

        function validateStep(step) {
            let valid = true;
            let msg = '';

            if (step === 1) {
                const plot = parseFloat(document.getElementById('plotSize').value);
                if (!plot || plot < 100 || plot > 50000) {
                    valid = false;
                    msg = 'Please enter a valid plot size (100 - 50,000 sq.ft)';
                }
            }
            if (step === 2) {
                // Floors always valid as controlled by buttons (min 1)
            }
            
            if (!valid) showToast(msg);
            return valid;
        }

        function updateProgress() {
            // Update bar
            const percent = ((currentStep - 1) / (totalSteps - 1)) * 100;
            progressBar.style.width = `${percent}%`;
            currentStepNum.innerText = currentStep;

            // Update Buttons
            prevBtn.disabled = currentStep === 1;
            nextBtn.innerHTML = currentStep === 5 ? 'Finish Calculation <i class="fas fa-check"></i>' : 'Next Step <i class="fas fa-arrow-right"></i>';
        }

        // ------------------
        // INPUT HANDLERS
        // ------------------
        
        // Step 1: Plot
        document.getElementById('plotSize').addEventListener('input', calculateLive);

        // Step 2: Floors
        function adjustFloors(diff) {
            const input = document.getElementById('floors');
            const display = document.getElementById('floorDisplay');
            let val = parseInt(input.value) + diff;
            if (val < 1) val = 1;
            if (val > 20) val = 20;
            input.value = val;
            display.innerText = val;
            
            // Check Lift validity immediately if needed, but we do it in calcLive
            calculateLive();
        }

        // Step 3 & 4: Cards
        function selectCard(inputId, value, element) {
            document.getElementById(inputId).value = value;
            
            // UI Toggle
            const siblings = element.parentElement.children;
            for (let sib of siblings) sib.classList.remove('selected');
            element.classList.add('selected');
            
            calculateLive();
        }

        // Step 5: Features
        function toggleFeature(element, cost, isPerSqFt = false, isLift = false) {
            element.classList.toggle('selected');
            
            // We'll reconstruct the features array based on DOM state to be simpler
            // But let's verify Lift logic
            if (isLift && element.classList.contains('selected')) {
                const floors = parseInt(document.getElementById('floors').value);
                if (floors <= 2) {
                    showToast("Lift is recommended for > 2 floors only.");
                    // We allow selection but warn? Or prevent?
                    // User requirement: "Lift (only if floors > 2)"
                    // Let's unselect it if floors <= 2
                    // element.classList.remove('selected');
                    // return;
                    // Actually let's just warn.
                }
            }
            calculateLive();
        }

        function restartWizard() {
            location.reload();
        }

        function showToast(msg) {
            const t = document.getElementById('errorMsg');
            t.innerText = msg;
            toast.style.display = 'block';
            setTimeout(() => { toast.style.display = 'none'; }, 3000);
        }

        // ------------------
        // CALCULATIONS
        // ------------------
        function formatMoney(amount) {
            if (amount >= 10000000) return '₹' + (amount / 10000000).toFixed(2) + ' Cr';
            if (amount >= 100000) return '₹' + (amount / 100000).toFixed(2) + ' L';
            return '₹' + amount.toLocaleString('en-IN');
        }

        function calculateLive() {
            // Gather inputs
            const plotInput = document.getElementById('plotSize');
            const rawValue = plotInput.value;
            let plotSize = parseFloat(rawValue);
            
            // Live Step 1 Validation
            const MIN_AREA = 100;
            const MAX_AREA = 50000;
            let isValid = true;

            // Strict number check: digits only (optional decimal)
            // This prevents "255-1" or "10e2" or "-50"
            const strictNumberRegex = /^\d*\.?\d+$/;

            if (rawValue !== '') {
                 if (!strictNumberRegex.test(rawValue) || isNaN(plotSize) || plotSize < MIN_AREA || plotSize > MAX_AREA) {
                     plotInput.style.borderColor = '#ef4444';
                     plotInput.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.2)';
                     isValid = false;
                 } else {
                     plotInput.style.borderColor = '';
                     plotInput.style.boxShadow = '';
                 }
            } else {
                plotInput.style.borderColor = '';
                plotInput.style.boxShadow = '';
                plotSize = 0; // Treat empty as 0 for calc
            }

            if (!isValid) return; // Stop updates if invalid

            // Ensure plotSize is valid number for math
            if (isNaN(plotSize)) plotSize = 0;

            const floors = parseInt(document.getElementById('floors').value);
            const qualityRate = parseFloat(document.getElementById('quality').value);
            const locationFactor = parseFloat(document.getElementById('location').value);
            
            // Calculate
            const builtUpArea = plotSize * floors;
            
            // 1. Base (Area * Floors * Rate) Wait, usually Area * Floors is BuiltUp. 
            // So Base = BuiltUp * Rate.
            const rawBaseCost = builtUpArea * qualityRate;
            
            // 2. Location Adjustment
            // Note: If factor is 1.1, cost is Base * 1.1. 
            // The split is Base vs Location Adjustment.
            // LocAdj = (Base * LocFactor) - Base
            const locAdjAmount = (rawBaseCost * locationFactor) - rawBaseCost;
            const locAdjustedCost = rawBaseCost * locationFactor;

            // 3. Floor Adjustment (7% per floor > G)
            let extraFloors = floors > 1 ? floors - 1 : 0;
            // "Add 7% cost for every additional floor" - usually means 7% of base cost
            // Let's use locAdjustedCost as the basis for the 7% increase?
            // "Add 7% cost" is vague. Usually 7% of the cost of THAT floor.
            // But simplifying: Total Cost += (LocAdjCost * 0.07 * ExtraFloors)? 
            // Or is it Rate increases by 7%?
            // Let's assume cumulative surcharge on the total project value isn't right.
            // Requirement: "Add 7% cost for every additional floor"
            // Let's assume it means the cost of construction increases by 7% overall? No.
            // Let's assume it adds a surcharge.
            const floorAdjAmount = locAdjustedCost * (extraFloors * 0.07);

            // 4. Extras
            let extraCost = 0;
            let featureNames = [];
            const selectedFeatures = document.querySelectorAll('.feature-tile.selected');
            
            selectedFeatures.forEach(tile => {
                const title = tile.querySelector('.card-title').innerText;
                featureNames.push(title);
                
                // Logic based on titles/hardcoded in toggle
                // Let's re-parse cost from onclick attribute or just hardcode map here for safety
                if (title.includes('Parking')) extraCost += 150000;
                if (title.includes('Kitchen')) extraCost += 200000;
                if (title.includes('Solar')) extraCost += 80000;
                if (title.includes('Lift')) {
                     if (floors > 2) extraCost += 700000; 
                     // If floors <= 2, we ignore cost even if selected? 
                     // "Lift (only if floors > 2)". 
                     // We'll calculate it only if floors > 2.
                }
                if (title.includes('Ceiling')) extraCost += (builtUpArea * 120);
            });

            const subTotal = locAdjustedCost + floorAdjAmount + extraCost;
            const contingency = subTotal * 0.10;
            const total = subTotal + contingency;

            // Update Preview Sidebar
            document.getElementById('liveTotal').innerText = formatMoney(total);
            document.getElementById('prevArea').innerText = plotSize ? `${builtUpArea.toLocaleString()} sq.ft` : '-';
            document.getElementById('prevFloors').innerText = floors;
            
            // Quality Text
            let qText = 'Standard';
            if (qualityRate == 1600) qText = 'Basic';
            if (qualityRate == 3000) qText = 'Premium';
            document.getElementById('prevQuality').innerText = qText;

            // Location Text
            let lText = 'Semi-Urban';
            if (locationFactor == 0.9) lText = 'Rural';
            if (locationFactor == 1.1) lText = 'Urban';
            document.getElementById('prevLocation').innerText = lText;

            // Features Text
            document.getElementById('prevFeatures').innerText = featureNames.length > 0 ? featureNames.join(', ') : 'None';

            // Store global values for final page
            window.calcResults = {
                base: rawBaseCost,
                loc: locAdjAmount,
                floor: floorAdjAmount,
                extra: extraCost,
                cont: contingency,
                total: total
            };
        }

        function renderFinalBreakdown() {
            const r = window.calcResults;
            const maxVal = r.total; // For bar scaling

            // Check if NaN
            if (!r.total) { r.total = 0; }

            // Animate Number
            animateValue(document.getElementById('finalAmountDisplay'), 0, r.total, 1500);

            // Render Bars
            const container = document.querySelector('.breakdown-bars');
            container.innerHTML = `
                ${createBar('Base Construction', r.base, maxVal, 'bar-base')}
                ${createBar('Location Adjustment', r.loc, maxVal, 'bar-loc')}
                ${createBar('Floor Rise Cost', r.floor, maxVal, 'bar-floor')}
                ${createBar('Extra Features', r.extra, maxVal, 'bar-extra')}
                ${createBar('Contingency (10%)', r.cont, maxVal, 'bar-cont')}
            `;
        }

        function createBar(label, value, total, colorClass) {
            // Handle negative location adjustment visually? width must be positive
            const safeVal = Math.abs(value);
            const percent = (safeVal / total) * 100 * 1.2; // slight scale up
            const width = Math.min(percent, 100);
            
            // Format
            const displayVal = (value >= 0 ? '₹' : '-₹') + Math.abs(Math.round(value)).toLocaleString('en-IN');
            
            return `
                <div class="bar-group">
                    <div class="bar-label">
                        <span>${label}</span>
                        <span>${displayVal}</span>
                    </div>
                    <div class="bar-track">
                        <div class="bar-value ${colorClass}" style="width: ${width}%"></div>
                    </div>
                </div>
            `;
        }

        function animateValue(obj, start, end, duration) {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                // Ease out quart
                const ease = 1 - Math.pow(1 - progress, 4);
                
                const currentVal = Math.floor(progress * (end - start) + start);
                
                // Format
                if (currentVal >= 10000000) {
                     obj.innerHTML = '₹' + (currentVal / 10000000).toFixed(2) + ' <span style="font-size:1.5rem">Cr</span>';
                } else if (currentVal >= 100000) {
                     obj.innerHTML = '₹' + (currentVal / 100000).toFixed(2) + ' <span style="font-size:1.5rem">Lakh</span>';
                } else {
                     obj.innerHTML = '₹' + currentVal.toLocaleString('en-IN');
                }

                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        }

    </script>
    <script src="js/architectural_bg.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
             if(typeof initArchitecturalBackground === 'function') {
                 initArchitecturalBackground('canvas-container');
             }
        });
    </script>
</body>

</html>
