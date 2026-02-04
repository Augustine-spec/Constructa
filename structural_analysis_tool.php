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
    <title>Structural Analysis Calculator - Constructa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
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
            --shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15), 0 0 1px rgba(0, 0, 0, 0.05);
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
            background: #f6f7f2;
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

        /* Selection Cards */
        .options-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
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

        .result-label {
            font-size: 0.85rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }

        .result-value {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .result-formula {
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            color: var(--text-muted);
            background: var(--bg-color);
            padding: 0.5rem;
            border-radius: 6px;
            margin-top: 0.5rem;
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
            <i class="fas fa-calculator"></i>
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
                <div class="step-indicator">Step <span id="currentStepNum">1</span> of 6</div>
                <div class="progress-track">
                    <div class="progress-fill" id="progressBar"></div>
                </div>
            </div>

            <div class="step-container">
                <!-- STEP 1: Beam Span -->
                <div class="step active" id="step1">
                    <h2 class="step-title">Enter the beam span length.</h2>
                    <p class="step-desc">This is the distance between supports. <span style="font-weight:600; color:#64748b;">(Max: 50m)</span></p>
                    <div class="form-group">
                        <input type="number" id="spanLength" class="big-input" placeholder="e.g. 6.0" step="0.1" min="0.1" max="50" />
                        <label style="display:block; margin-top:1rem; color:var(--text-muted);">Meters (m)</label>
                        <div id="span-error" style="display:none; color:#ef4444; font-size:0.9rem; font-weight:600; margin-top:0.5rem;">
                            <i class="fas fa-exclamation-circle"></i> <span></span>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: Support Condition -->
                <div class="step" id="step2">
                    <h2 class="step-title">Select the support condition.</h2>
                    <p class="step-desc">How is the beam supported?</p>
                    <div class="options-grid">
                        <div class="selection-card selected" onclick="selectCard('support', 'simply', this)" data-value="simply">
                            <div class="check-mark"><i class="fas fa-check"></i></div>
                            <div class="icon"><i class="fas fa-grip-lines"></i></div>
                            <div class="card-title">Simply Supported</div>
                            <div class="card-subtitle">Beam supported at both ends, free to rotate</div>
                        </div>
                        <div class="selection-card" onclick="selectCard('support', 'cantilever', this)" data-value="cantilever">
                            <div class="check-mark"><i class="fas fa-check"></i></div>
                            <div class="icon"><i class="fas fa-grip-vertical"></i></div>
                            <div class="card-title">Cantilever</div>
                            <div class="card-subtitle">Fixed at one end, free at the other</div>
                        </div>
                    </div>
                    <input type="hidden" id="supportType" value="simply">
                </div>

                <!-- STEP 3: Load Type -->
                <div class="step" id="step3">
                    <h2 class="step-title">Choose the type of load.</h2>
                    <p class="step-desc">How is the load distributed on the beam?</p>
                    <div class="options-grid">
                        <div class="selection-card selected" onclick="selectCard('loadType', 'udl', this)" data-value="udl">
                            <div class="check-mark"><i class="fas fa-check"></i></div>
                            <div class="icon"><i class="fas fa-arrows-alt-h"></i></div>
                            <div class="card-title">UDL</div>
                            <div class="card-subtitle">Uniformly Distributed Load across the span</div>
                        </div>
                        <div class="selection-card" onclick="selectCard('loadType', 'point', this)" data-value="point">
                            <div class="check-mark"><i class="fas fa-check"></i></div>
                            <div class="icon"><i class="fas fa-arrow-down"></i></div>
                            <div class="card-title">Point Load</div>
                            <div class="card-subtitle">Concentrated load at center of span</div>
                        </div>
                    </div>
                    <input type="hidden" id="loadType" value="udl">
                </div>

                <!-- STEP 4: Load Magnitude -->
                <div class="step" id="step4">
                    <h2 class="step-title">Enter the load magnitude.</h2>
                    <p class="step-desc" id="loadDesc">Enter the uniformly distributed load per meter. <span style="font-weight:600; color:#64748b;">(Max: 1000 kN/m)</span></p>
                    <div class="form-group">
                        <input type="number" id="loadMagnitude" class="big-input" placeholder="e.g. 10" step="0.1" min="0.1" max="1000" />
                        <label style="display:block; margin-top:1rem; color:var(--text-muted);" id="loadUnit">kN/m (for UDL)</label>
                        <div id="load-error" style="display:none; color:#ef4444; font-size:0.9rem; font-weight:600; margin-top:0.5rem;">
                            <i class="fas fa-exclamation-circle"></i> <span></span>
                        </div>
                    </div>
                </div>

                <!-- STEP 5: Material Properties -->
                <div class="step" id="step5">
                    <h2 class="step-title">Specify material properties.</h2>
                    <p class="step-desc">Enter Young's Modulus and Moment of Inertia.</p>
                    <div class="form-group" style="margin-bottom: 2rem;">
                        <label style="display:block; margin-bottom:0.5rem; font-weight:600; color:var(--text-main);">Young's Modulus (E)</label>
                        <input type="number" id="youngsModulus" class="big-input" value="25000000" step="1000" style="font-size: 1.5rem;" />
                        <label style="display:block; margin-top:0.5rem; color:var(--text-muted); font-size: 0.9rem;">kN/m² (default: 25×10⁶ for M25 concrete)</label>
                        <div id="youngs-error" style="display:none; color:#ef4444; font-size:0.9rem; font-weight:600; margin-top:0.5rem;">
                            <i class="fas fa-exclamation-circle"></i> <span></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label style="display:block; margin-bottom:0.5rem; font-weight:600; color:var(--text-main);">Moment of Inertia (I)</label>
                        <input type="number" id="inertia" class="big-input" placeholder="e.g. 0.003375" step="0.0001" style="font-size: 1.5rem;" />
                        <label style="display:block; margin-top:0.5rem; color:var(--text-muted); font-size: 0.9rem;">m⁴ (e.g., for 300×450mm beam: I = 0.003375 m⁴)</label>
                        <div id="inertia-error" style="display:none; color:#ef4444; font-size:0.9rem; font-weight:600; margin-top:0.5rem;">
                            <i class="fas fa-exclamation-circle"></i> <span></span>
                        </div>
                    </div>
                </div>

                <!-- STEP 6: Results -->
                <div class="step" id="step6">
                    <h2 class="step-title" style="text-align: center;">Analysis Complete!</h2>
                    <p class="step-desc" style="text-align: center;">Based on IS 456:2000 and standard structural mechanics principles.</p>
                    
                    <div id="resultsDisplay">
                        <!-- Results will be injected by JavaScript -->
                    </div>

                    <div style="text-align: center; margin-top: 2rem;">
                        <button type="button" class="btn btn-primary" onclick="downloadPDF()">
                            <i class="fas fa-file-pdf"></i> Download Report
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
            <h3 class="preview-title"><i class="fas fa-chart-line"></i> Live Preview</h3>
            
            <div class="preview-card">
                <div class="preview-label">3D Visualization</div>
                <canvas id="beamCanvas" width="300" height="200" style="width: 100%; border-radius: 8px; background: white;"></canvas>
            </div>

            <div class="preview-label">Parameters</div>
            <div class="result-display">
                <div class="preview-label">Span Length</div>
                <div class="preview-value" id="prevSpan">-</div>
            </div>

            <div class="result-display">
                <div class="preview-label">Support Type</div>
                <div class="preview-value" id="prevSupport">Simply Supported</div>
            </div>

            <div class="result-display">
                <div class="preview-label">Load Type</div>
                <div class="preview-value" id="prevLoad">UDL</div>
            </div>

            <div class="result-display">
                <div class="preview-label">Load Magnitude</div>
                <div class="preview-value" id="prevLoadMag">-</div>
            </div>

            <div class="result-display" id="resultsPreview" style="display:none;">
                <div class="preview-label">Status</div>
                <div class="preview-value" style="color: #10b981;">✓ Ready to Calculate</div>
            </div>
        </div>
    </div>

    <script>
        // State
        let currentStep = 1;
        const totalSteps = 6;
        let calculationData = {};

        // DOM Elements
        const progressBar = document.getElementById('progressBar');
        const currentStepNum = document.getElementById('currentStepNum');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');

        // Initialize
        updateProgress();
        setupValidation();

        function setupValidation() {
            // Helper to block invalid keys
            const blockInvalidKeys = (e) => {
                // Allow: backspace, delete, tab, escape, enter
                if ([46, 8, 9, 27, 13].indexOf(e.keyCode) !== -1 ||
                    // Allow: Ctrl+A
                    (e.keyCode === 65 && e.ctrlKey === true) ||
                    // Allow: home, end, left, right
                    (e.keyCode >= 35 && e.keyCode <= 39)) {
                    return;
                }
                
                // Allow decimal point (190 or 110) ONLY if not already present
                if (e.keyCode === 190 || e.keyCode === 110) {
                    if (e.target.value.includes('.')) {
                        e.preventDefault();
                    }
                    return;
                }

                // Ensure that it is a number and stop the keypress
                if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                    e.preventDefault();
                }
            };

            // Helper to setup validation for a specific field
            const setupLiveValidation = (id, errorId, min, max, label) => {
                const input = document.getElementById(id);
                const errorDiv = document.getElementById(errorId);
                if(!input) return;

                input.addEventListener('keydown', blockInvalidKeys);
                
                input.addEventListener('input', (e) => {
                    let valStr = e.target.value.replace(/[^0-9.]/g, ''); // Allow dots for decimals
                    // Prevent multiple dots
                    if ((valStr.match(/\./g) || []).length > 1) {
                         valStr = valStr.substring(0, valStr.lastIndexOf('.'));
                    }
                    
                    if(valStr !== e.target.value) e.target.value = valStr;
                    
                    let value = parseFloat(valStr);
                    input.classList.remove('valid', 'invalid');
                    input.style.borderColor = '#e2e8f0'; // Reset border

                    if (valStr.length > 0 && !isNaN(value)) {
                        if (value > max) {
                            // Don't auto-cap, just show error
                            input.classList.add('invalid');
                            input.style.borderColor = '#ef4444';
                            if (errorDiv) {
                                errorDiv.style.display = 'block';
                                errorDiv.querySelector('span').textContent = `Maximum ${label} is ${max}`;
                            }
                        } else if (value < min) {
                            // Don't auto-correct min immediately while typing
                            // But show visual warning
                            input.classList.add('invalid');
                            input.style.borderColor = '#ef4444';
                            if (errorDiv) {
                                errorDiv.style.display = 'block';
                                errorDiv.querySelector('span').textContent = `Minimum ${label} is ${min}`;
                            }
                        } else {
                            input.classList.add('valid');
                            input.style.borderColor = '#10b981';
                            if (errorDiv) errorDiv.style.display = 'none';
                        }
                    } else {
                        if (errorDiv) errorDiv.style.display = 'none';
                    }
                    
                    updatePreview();
                });
            };

            setupLiveValidation('spanLength', 'span-error', 0.1, 50, 'span');
            setupLiveValidation('loadMagnitude', 'load-error', 0.1, 1000, 'load');
            setupLiveValidation('youngsModulus', 'youngs-error', 1000, 200000000, 'Young\'s Modulus');
            setupLiveValidation('inertia', 'inertia-error', 0.000001, 1, 'Inertia');
        }

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

            // Calculate on step 6
            if (currentStep === 6) {
                performCalculation();
            }
        }

        function validateStep(step) {
            switch(step) {
                case 1:
                    const span = document.getElementById('spanLength').value;
                    if (!span || parseFloat(span) <= 0) {
                        alert('Please enter a valid beam span');
                        return false;
                    }
                    break;
                case 4:
                    const load = document.getElementById('loadMagnitude').value;
                    if (!load || parseFloat(load) <= 0) {
                        alert('Please enter a valid load magnitude');
                        return false;
                    }
                    break;
                case 5:
                    const E = document.getElementById('youngsModulus').value;
                    const I = document.getElementById('inertia').value;
                    if (!E || parseFloat(E) <= 0) {
                        alert('Please enter a valid Young\'s Modulus');
                        return false;
                    }
                    if (!I || parseFloat(I) <= 0) {
                        alert('Please enter a valid Moment of Inertia');
                        return false;
                    }
                    break;
            }
            return true;
        }

        function updateProgress() {
            const progress = ((currentStep - 1) / (totalSteps - 1)) * 100;
            progressBar.style.width = progress + '%';
            currentStepNum.textContent = currentStep;

            prevBtn.disabled = currentStep === 1;
            
            if (currentStep === totalSteps) {
                nextBtn.style.display = 'none';
            } else {
                nextBtn.style.display = 'flex';
            }
        }

        function updatePreview() {
            const span = document.getElementById('spanLength').value;
            const support = document.getElementById('supportType').value;
            const loadType = document.getElementById('loadType').value;
            const loadMag = document.getElementById('loadMagnitude').value;

            document.getElementById('prevSpan').textContent = span ? span + ' m' : '-';
            document.getElementById('prevSupport').textContent = support === 'simply' ? 'Simply Supported' : 'Cantilever';
            document.getElementById('prevLoad').textContent = loadType === 'udl' ? 'UDL' : 'Point Load';
            
            const loadUnit = loadType === 'udl' ? ' kN/m' : ' kN';
            document.getElementById('prevLoadMag').textContent = loadMag ? loadMag + loadUnit : '-';

            // Update 3D visualization
            draw3DBeam();
        }

        function draw3DBeam() {
            const canvas = document.getElementById('beamCanvas');
            const ctx = canvas.getContext('2d');
            
            // Clear canvas
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            const span = parseFloat(document.getElementById('spanLength').value) || 6;
            const support = document.getElementById('supportType').value;
            const loadType = document.getElementById('loadType').value;
            const loadMag = parseFloat(document.getElementById('loadMagnitude').value) || 0;
            
            const padding = 40;
            const beamY = 120;
            const beamStartX = padding;
            const beamEndX = canvas.width - padding;
            const beamLength = beamEndX - beamStartX;
            
            // Draw beam
            ctx.strokeStyle = '#294033';
            ctx.lineWidth = 8;
            ctx.lineCap = 'round';
            ctx.beginPath();
            ctx.moveTo(beamStartX, beamY);
            ctx.lineTo(beamEndX, beamY);
            ctx.stroke();
            
            // Draw supports
            if (support === 'simply') {
                // Left support (pin)
                drawPinSupport(ctx, beamStartX, beamY);
                // Right support (roller)
                drawRollerSupport(ctx, beamEndX, beamY);
            } else {
                // Fixed support (cantilever)
                drawFixedSupport(ctx, beamStartX, beamY);
            }
            
            // Draw load
            if (loadMag > 0) {
                if (loadType === 'udl') {
                    drawUDL(ctx, beamStartX, beamEndX, beamY, loadMag);
                } else {
                    drawPointLoad(ctx, (beamStartX + beamEndX) / 2, beamY, loadMag);
                }
            }
            
            // Draw dimension
            ctx.strokeStyle = '#64748b';
            ctx.lineWidth = 1;
            ctx.setLineDash([5, 3]);
            ctx.beginPath();
            ctx.moveTo(beamStartX, beamY + 40);
            ctx.lineTo(beamEndX, beamY + 40);
            ctx.stroke();
            ctx.setLineDash([]);
            
            // Dimension arrows
            drawArrow(ctx, beamStartX, beamY + 40, beamStartX + 10, beamY + 40, '#64748b');
            drawArrow(ctx, beamEndX, beamY + 40, beamEndX - 10, beamY + 40, '#64748b');
            
            // Dimension text
            ctx.fillStyle = '#294033';
            ctx.font = 'bold 14px Inter';
            ctx.textAlign = 'center';
            ctx.fillText(`L = ${span} m`, (beamStartX + beamEndX) / 2, beamY + 55);
            
            // Load label
            if (loadMag > 0) {
                ctx.font = '12px Inter';
                ctx.fillStyle = '#dc2626';
                const loadUnit = loadType === 'udl' ? ' kN/m' : ' kN';
                ctx.fillText(`w = ${loadMag}${loadUnit}`, (beamStartX + beamEndX) / 2, 25);
            }
        }
        
        function drawPinSupport(ctx, x, y) {
            // Triangle
            ctx.fillStyle = '#294033';
            ctx.beginPath();
            ctx.moveTo(x, y);
            ctx.lineTo(x - 10, y + 15);
            ctx.lineTo(x + 10, y + 15);
            ctx.closePath();
            ctx.fill();
            
            // Ground line
            ctx.strokeStyle = '#294033';
            ctx.lineWidth = 2;
            for (let i = 0; i < 5; i++) {
                ctx.beginPath();
                ctx.moveTo(x - 15 + i * 7, y + 15);
                ctx.lineTo(x - 18 + i * 7, y + 20);
                ctx.stroke();
            }
        }
        
        function drawRollerSupport(ctx, x, y) {
            // Circle (roller)
            ctx.fillStyle = '#294033';
            ctx.beginPath();
            ctx.arc(x, y + 10, 6, 0, Math.PI * 2);
            ctx.fill();
            
            // Ground line
            ctx.strokeStyle = '#294033';
            ctx.lineWidth = 2;
            for (let i = 0; i < 5; i++) {
                ctx.beginPath();
                ctx.moveTo(x - 15 + i * 7, y + 16);
                ctx.lineTo(x - 18 + i * 7, y + 21);
                ctx.stroke();
            }
        }
        
        function drawFixedSupport(ctx, x, y) {
            // Wall
            ctx.fillStyle = '#294033';
            ctx.fillRect(x - 15, y - 30, 10, 60);
            
            // Hatch pattern
            ctx.strokeStyle = '#ffffff';
            ctx.lineWidth = 2;
            for (let i = 0; i < 6; i++) {
                ctx.beginPath();
                ctx.moveTo(x - 15, y - 25 + i * 10);
                ctx.lineTo(x - 5, y - 25 + i * 10);
                ctx.stroke();
            }
        }
        
        function drawUDL(ctx, startX, endX, beamY, magnitude) {
            // Draw distributed load arrows
            const numArrows = 8;
            const spacing = (endX - startX) / numArrows;
            const arrowLength = Math.min(30, magnitude * 2);
            
            ctx.strokeStyle = '#dc2626';
            ctx.fillStyle = '#dc2626';
            ctx.lineWidth = 2;
            
            for (let i = 0; i < numArrows; i++) {
                const x = startX + spacing / 2 + i * spacing;
                drawArrow(ctx, x, beamY - arrowLength, x, beamY - 5, '#dc2626');
            }
        }
        
        function drawPointLoad(ctx, x, beamY, magnitude) {
            const arrowLength = Math.min(40, magnitude * 3);
            ctx.strokeStyle = '#dc2626';
            ctx.fillStyle = '#dc2626';
            ctx.lineWidth = 3;
            
            drawArrow(ctx, x, beamY - arrowLength, x, beamY - 5, '#dc2626');
            
            // Load label
            ctx.fillStyle = '#dc2626';
            ctx.font = 'bold 12px Inter';
            ctx.textAlign = 'center';
            ctx.fillText('P', x, beamY - arrowLength - 5);
        }
        
        function drawArrow(ctx, fromX, fromY, toX, toY, color) {
            const headLength = 8;
            const angle = Math.atan2(toY - fromY, toX - fromX);
            
            ctx.strokeStyle = color;
            ctx.fillStyle = color;
            ctx.lineWidth = 2;
            
            // Line
            ctx.beginPath();
            ctx.moveTo(fromX, fromY);
            ctx.lineTo(toX, toY);
            ctx.stroke();
            
            // Arrow head
            ctx.beginPath();
            ctx.moveTo(toX, toY);
            ctx.lineTo(toX - headLength * Math.cos(angle - Math.PI / 6), toY - headLength * Math.sin(angle - Math.PI / 6));
            ctx.lineTo(toX - headLength * Math.cos(angle + Math.PI / 6), toY - headLength * Math.sin(angle + Math.PI / 6));
            ctx.closePath();
            ctx.fill();
        }

        // Add real-time input listeners with validation
        document.getElementById('spanLength').addEventListener('input', function() {
            let value = parseFloat(this.value) || 0;
            const errorDiv = document.getElementById('span-error');
            
            // Remove previous validation classes
            this.classList.remove('valid', 'invalid');
            
            if (value > 0) {
                // Check maximum
                if (value > 50) {
                    this.value = 50;
                    value = 50;
                    this.classList.add('invalid');
                    this.style.borderColor = '#ef4444';
                    if (errorDiv) {
                        errorDiv.style.display = 'block';
                        errorDiv.querySelector('span').textContent = 'Maximum span is 50m (auto-capped)';
                    }
                    setTimeout(() => {
                        this.classList.remove('invalid');
                        this.classList.add('valid');
                        this.style.borderColor = '#10b981';
                        if (errorDiv) errorDiv.style.display = 'none';
                    }, 1500);
                }
                // Check minimum
                else if (value < 0.1) {
                    this.classList.add('invalid');
                    this.style.borderColor = '#ef4444';
                    if (errorDiv) {
                        errorDiv.style.display = 'block';
                        errorDiv.querySelector('span').textContent = 'Minimum span is 0.1m';
                    }
                }
                // Valid range
                else {
                    this.classList.add('valid');
                    this.style.borderColor = '#10b981';
                    if (errorDiv) errorDiv.style.display = 'none';
                }
            } else {
                this.style.borderColor = '';
                if (errorDiv) errorDiv.style.display = 'none';
            }
            
            updatePreview();
        });
        
        document.getElementById('loadMagnitude').addEventListener('input', function() {
            let value = parseFloat(this.value) || 0;
            const errorDiv = document.getElementById('load-error');
            
            // Remove previous validation classes
            this.classList.remove('valid', 'invalid');
            
            if (value > 0) {
                // Check maximum
                if (value > 1000) {
                    this.value = 1000;
                    value = 1000;
                    this.classList.add('invalid');
                    this.style.borderColor = '#ef4444';
                    if (errorDiv) {
                        errorDiv.style.display = 'block';
                        errorDiv.querySelector('span').textContent = 'Maximum load is 1000 kN/m (auto-capped)';
                    }
                    setTimeout(() => {
                        this.classList.remove('invalid');
                        this.classList.add('valid');
                        this.style.borderColor = '#10b981';
                        if (errorDiv) errorDiv.style.display = 'none';
                    }, 1500);
                }
                // Check minimum
                else if (value < 0.1) {
                    this.classList.add('invalid');
                    this.style.borderColor = '#ef4444';
                    if (errorDiv) {
                        errorDiv.style.display = 'block';
                        errorDiv.querySelector('span').textContent = 'Minimum load is 0.1 kN/m';
                    }
                }
                // Valid range
                else {
                    this.classList.add('valid');
                    this.style.borderColor = '#10b981';
                    if (errorDiv) errorDiv.style.display = 'none';
                }
            } else {
                this.style.borderColor = '';
                if (errorDiv) errorDiv.style.display = 'none';
            }
            
            updatePreview();
        });

        // Initial draw
        draw3DBeam();

        function selectCard(type, value, element) {
            const parent = element.parentElement;
            parent.querySelectorAll('.selection-card').forEach(card => {
                card.classList.remove('selected');
            });
            element.classList.add('selected');
            document.getElementById(type === 'support' ? 'supportType' : 'loadType').value = value;

            // Update load description
            if (type === 'loadType') {
                const loadDesc = document.getElementById('loadDesc');
                const loadUnit = document.getElementById('loadUnit');
                if (value === 'udl') {
                    loadDesc.textContent = 'Enter the uniformly distributed load per meter.';
                    loadUnit.textContent = 'kN/m (for UDL)';
                } else {
                    loadDesc.textContent = 'Enter the concentrated point load.';
                    loadUnit.textContent = 'kN (for Point Load)';
                }
            }

            updatePreview();
        }

        function performCalculation() {
            const L = parseFloat(document.getElementById('spanLength').value);
            const support = document.getElementById('supportType').value;
            const loadType = document.getElementById('loadType').value;
            const w = parseFloat(document.getElementById('loadMagnitude').value);
            const E = parseFloat(document.getElementById('youngsModulus').value);
            const I = parseFloat(document.getElementById('inertia').value);

            let M_max, V_max, delta_max;
            let M_formula, V_formula, delta_formula;

            if (support === 'simply' && loadType === 'udl') {
                M_max = (w * L * L) / 8;
                V_max = (w * L) / 2;
                delta_max = (5 * w * Math.pow(L * 1000, 4)) / (384 * E * I);
                
                M_formula = `M = (w × L²) / 8 = (${w} × ${L}²) / 8`;
                V_formula = `V = (w × L) / 2 = (${w} × ${L}) / 2`;
                delta_formula = `δ = (5 × w × L⁴) / (384 × E × I)`;
                
            } else if (support === 'simply' && loadType === 'point') {
                M_max = (w * L) / 4;
                V_max = w / 2;
                delta_max = (w * Math.pow(L * 1000, 3)) / (48 * E * I);
                
                M_formula = `M = (P × L) / 4 = (${w} × ${L}) / 4`;
                V_formula = `V = P / 2 = ${w} / 2`;
                delta_formula = `δ = (P × L³) / (48 × E × I)`;
                
            } else if (support === 'cantilever' && loadType === 'udl') {
                M_max = (w * L * L) / 2;
                V_max = w * L;
                delta_max = (w * Math.pow(L * 1000, 4)) / (8 * E * I);
                
                M_formula = `M = (w × L²) / 2 = (${w} × ${L}²) / 2`;
                V_formula = `V = w × L = ${w} × ${L}`;
                delta_formula = `δ = (w × L⁴) / (8 × E × I)`;
                
            } else if (support === 'cantilever' && loadType === 'point') {
                M_max = w * L;
                V_max = w;
                delta_max = (w * Math.pow(L * 1000, 3)) / (3 * E * I);
                
                M_formula = `M = P × L = ${w} × ${L}`;
                V_formula = `V = P = ${w}`;
                delta_formula = `δ = (P × L³) / (3 × E × I)`;
            }

            calculationData = {
                inputs: {
                    span: L,
                    support: support === 'simply' ? 'Simply Supported' : 'Cantilever',
                    loadType: loadType === 'udl' ? 'UDL' : 'Point Load',
                    load: w,
                    youngs: E,
                    inertia: I
                },
                results: {
                    moment: M_max,
                    shear: V_max,
                    deflection: delta_max
                },
                formulas: {
                    moment: M_formula,
                    shear: V_formula,
                    deflection: delta_formula
                }
            };

            displayResults();
        }

        function displayResults() {
            const resultsDiv = document.getElementById('resultsDisplay');
            resultsDiv.innerHTML = `
                <div class="result-display">
                    <div class="result-label">Maximum Bending Moment</div>
                    <div class="result-value">${calculationData.results.moment.toFixed(2)} kN·m</div>
                    <div class="result-formula">${calculationData.formulas.moment}</div>
                </div>

                <div class="result-display">
                    <div class="result-label">Maximum Shear Force</div>
                    <div class="result-value">${calculationData.results.shear.toFixed(2)} kN</div>
                    <div class="result-formula">${calculationData.formulas.shear}</div>
                </div>

                <div class="result-display">
                    <div class="result-label">Maximum Deflection</div>
                    <div class="result-value">${calculationData.results.deflection.toFixed(2)} mm</div>
                    <div class="result-formula">${calculationData.formulas.deflection}</div>
                </div>

                <div style="background: #f0fdf4; border: 2px solid #10b981; border-radius: 12px; padding: 1.5rem; margin-top: 1rem;">
                    <strong style="color: var(--primary);">Standard Reference:</strong>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.5rem;">
                        Calculations performed as per IS 456:2000 and standard structural mechanics principles.
                    </p>
                </div>
            `;
        }

        function downloadPDF() {
            if (!calculationData.results) {
                alert('Please perform calculations first');
                return;
            }

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            const margin = 20;
            let yPos = 20;

            doc.setFontSize(20);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(41, 64, 51);
            doc.text('STRUCTURAL ANALYSIS REPORT', margin, yPos);
            yPos += 15;

            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            doc.setTextColor(100, 116, 139);
            doc.text(`Generated: ${new Date().toLocaleString('en-IN')}`, margin, yPos);
            yPos += 15;

            doc.setFontSize(14);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(41, 64, 51);
            doc.text('INPUT PARAMETERS', margin, yPos);
            yPos += 10;

            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            doc.setTextColor(44, 62, 80);
            doc.text(`Span Length: ${calculationData.inputs.span} m`, margin + 5, yPos);
            yPos += 7;
            doc.text(`Support: ${calculationData.inputs.support}`, margin + 5, yPos);
            yPos += 7;
            doc.text(`Load Type: ${calculationData.inputs.loadType}`, margin + 5, yPos);
            yPos += 7;
            doc.text(`Load: ${calculationData.inputs.load} ${calculationData.inputs.loadType === 'UDL' ? 'kN/m' : 'kN'}`, margin + 5, yPos);
            yPos += 7;
            doc.text(`Young's Modulus: ${calculationData.inputs.youngs.toExponential(2)} kN/m²`, margin + 5, yPos);
            yPos += 7;
            doc.text(`Moment of Inertia: ${calculationData.inputs.inertia} m⁴`, margin + 5, yPos);
            yPos += 15;

            doc.setFontSize(14);
            doc.setFont('helvetica', 'bold');
            doc.text('RESULTS', margin, yPos);
            yPos += 10;

            doc.setFontSize(11);
            doc.setFont('helvetica', 'bold');
            doc.text('Maximum Bending Moment', margin + 5, yPos);
            yPos += 7;
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(10);
            doc.text(`Formula: ${calculationData.formulas.moment}`, margin + 10, yPos);
            yPos += 7;
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(12);
            doc.text(`Result: ${calculationData.results.moment.toFixed(2)} kN·m`, margin + 10, yPos);
            yPos += 12;

            doc.setFontSize(11);
            doc.text('Maximum Shear Force', margin + 5, yPos);
            yPos += 7;
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(10);
            doc.text(`Formula: ${calculationData.formulas.shear}`, margin + 10, yPos);
            yPos += 7;
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(12);
            doc.text(`Result: ${calculationData.results.shear.toFixed(2)} kN`, margin + 10, yPos);
            yPos += 12;

            doc.setFontSize(11);
            doc.text('Maximum Deflection', margin + 5, yPos);
            yPos += 7;
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(10);
            doc.text(`Formula: ${calculationData.formulas.deflection}`, margin + 10, yPos);
            yPos += 7;
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(12);
            doc.text(`Result: ${calculationData.results.deflection.toFixed(2)} mm`, margin + 10, yPos);

            doc.save(`Structural_Analysis_${Date.now()}.pdf`);
        }

    </script>

    <!-- === 3D BACKGROUND ANIMATION === -->
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
