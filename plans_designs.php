<?php
session_start();
// Ensure user is logged in
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
    <title>AI Architect Studio | Constructa</title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://unpkg.com/three@0.128.0/examples/js/controls/PointerLockControls.js"></script>
    <script src="https://unpkg.com/three@0.128.0/examples/js/controls/OrbitControls.js"></script>

    <style>
        :root {
            --primary: #294033;
            --primary-light: #3d5a49;
            --accent: #d97706;
            --surface: rgba(255, 255, 255, 0.95);
            --surface-blur: blur(12px);
            --border: rgba(0,0,0,0.1);
            --text-main: #1e293b;
            --text-muted: #64748b;
            --success: #10b981;
            --warning: #f59e0b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        
        body { 
            background-color: #f6f7f2; 
            color: var(--text-main); 
            height: 100vh; 
            overflow: hidden; 
        }

        /* 3D Background Canvas */
        #bg-canvas {
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%;
            z-index: 0; 
            pointer-events: none; 
            background: #f6f7f2;
        }

        /* Nav */
        nav {
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 72px;
            background: rgba(255,255,255,0.8); 
            backdrop-filter: var(--surface-blur);
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            padding: 0 40px; 
            border-bottom: 1px solid var(--border); 
            z-index: 1000;
        }
        .brand { 
            font-family: 'Space Grotesk', sans-serif; 
            font-size: 1.4rem; 
            font-weight: 700; 
            color: var(--primary); 
            text-decoration: none; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
        }
        .back-link { 
            text-decoration: none; 
            color: var(--text-muted); 
            font-size: 0.9rem; 
            font-weight: 500; 
            transition:0.3s; 
        }
        .back-link:hover { color: var(--primary); }

        /* Main Layout */
        #app-container {
            position: relative; 
            z-index: 10;
            width: 100%; 
            height: calc(100vh - 72px); 
            top: 72px;
            display: flex;
        }

        /* Left Panel */
        .input-panel {
            width: 480px;
            background: var(--surface);
            backdrop-filter: var(--surface-blur);
            height: 100%;
            border-right: 1px solid var(--border);
            display: flex; 
            flex-direction: column;
            box-shadow: 10px 0 40px rgba(0,0,0,0.05);
            transition: width 0.3s ease;
        }

        /* Progress Header */
        .stepper-header {
            padding: 20px 32px;
            border-bottom: 1px solid var(--border);
            background: rgba(255,255,255,0.8);
        }
        .progress-indicator {
            display: flex; 
            gap: 4px; 
            margin-top: 12px;
        }
        .progress-bar {
            flex: 1; 
            height: 4px; 
            background: #e2e8f0; 
            border-radius: 2px; 
            overflow: hidden;
        }
        .progress-fill {
            height: 100%; 
            background: var(--primary); 
            width: 0%; 
            transition: width 0.4s ease;
        }

        .scrollable-form {
            flex: 1; 
            overflow-y: auto; 
            padding: 32px;
        }

        /* Wizard Steps */
        .wizard-step {
            display: none;
            animation: slideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .wizard-step.active { display: block; }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        h2 { 
            font-family: 'Space Grotesk'; 
            font-size: 1.6rem; 
            color: var(--primary); 
            margin-bottom: 8px; 
        }
        p.subtitle { 
            color: var(--text-muted); 
            margin-bottom: 24px; 
            font-size: 0.9rem; 
            line-height: 1.4; 
            border-left: 3px solid var(--accent); 
            padding-left: 12px; 
        }

        .form-section { 
            margin-bottom: 24px; 
            padding-bottom: 24px; 
            border-bottom: 1px dashed #e2e8f0; 
        }
        .form-section:last-child { border-bottom: none; }
        
        .section-label { 
            font-size: 0.8rem; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
            font-weight: 700; 
            color: var(--accent); 
            margin-bottom: 12px; 
            display: flex; 
            align-items: center; 
            gap: 8px;
        }

        /* Input Styles */
        .input-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        
        .c-input {
            width: 100%; 
            padding: 12px 14px; 
            border: 1px solid #cbd5e1; 
            border-radius: 6px;
            background: white; 
            font-size: 0.95rem; 
            color: var(--text-main);
            transition: 0.2s;
        }
        .c-input:focus { 
            border-color: var(--primary); 
            outline: none; 
            box-shadow: 0 0 0 3px rgba(41, 64, 51, 0.1); 
        }
        
        label { 
            font-size: 0.8rem; 
            font-weight: 600; 
            color: var(--text-muted); 
            display: block; 
            margin-bottom: 6px; 
        }

        .selection-card {
            border: 1px solid #cbd5e1; 
            border-radius: 8px; 
            padding: 12px;
            cursor: pointer; 
            transition: 0.2s; 
            background: white;
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            gap: 6px;
            text-align: center; 
            height: 100%; 
            min-height: 80px; 
            position: relative;
        }
        .selection-card:hover { 
            border-color: var(--primary-light); 
            background: #f8fafc; 
        }
        .selection-card.selected { 
            border-color: var(--primary); 
            background: #f0fdf4; 
            color: var(--primary); 
            font-weight: 600; 
            box-shadow: 0 4px 12px rgba(41, 64, 51, 0.1); 
        }
        .selection-card.selected::after {
            content: '✓'; 
            position: absolute; 
            top: 4px; 
            right: 6px; 
            font-size: 0.8rem;
        }
        .selection-card i { 
            font-size: 1.2rem; 
            color: var(--text-muted); 
        }
        .selection-card.selected i { color: var(--primary); }

        /* Slider */
        input[type="range"] {
            -webkit-appearance: none;
            width: 100%;
            height: 8px;
            border-radius: 5px;
            background: #e2e8f0;
            outline: none;
        }
        input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--primary);
            cursor: pointer;
        }
        input[type="range"]::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--primary);
            cursor: pointer;
        }

        /* Counter */
        .counter-box {
            display: flex; 
            align-items: center; 
            justify-content: space-between;
            padding: 8px 12px; 
            border: 1px solid #cbd5e1; 
            border-radius: 6px; 
            background: white; 
            margin-bottom: 10px;
        }
        .btn-count {
            width: 28px; 
            height: 28px; 
            border-radius: 4px; 
            border: none; 
            background: #f1f5f9;
            cursor: pointer; 
            color: var(--text-main); 
            font-weight: 700; 
            display: flex; 
            align-items: center; 
            justify-content: center;
        }
        .btn-count:hover { background: #e2e8f0; }

        /* Navigation Footer */
        .wizard-footer {
            padding: 20px 32px; 
            border-top: 1px solid var(--border);
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            background: rgba(255,255,255,0.9);
        }
        .btn-hollow {
            padding: 10px 24px; 
            border: 1px solid #cbd5e1; 
            background: transparent;
            border-radius: 6px; 
            cursor: pointer; 
            color: var(--text-muted); 
            font-weight: 600; 
            font-size: 0.9rem;
        }
        .btn-hollow:hover { 
            border-color: var(--text-main); 
            color: var(--text-main); 
        }
        
        .btn-solid {
            padding: 12px 32px; 
            background: var(--primary); 
            border: none;
            border-radius: 6px; 
            cursor: pointer; 
            color: white; 
            font-weight: 600;
            display: flex; 
            align-items: center; 
            gap: 8px; 
            font-size: 0.95rem;
            box-shadow: 0 4px 12px rgba(41, 64, 51, 0.2);
            transition: 0.3s;
        }
        .btn-solid:hover { 
            background: var(--primary-light); 
            transform: translateY(-1px); 
        }

        /* Right Panel - 3D Viz + Summary */
        .viz-panel {
            flex: 1; 
            position: relative;
            display: flex; 
            flex-direction: column;
        }

        /* Live Summary Panel */
        .live-summary {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 320px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            padding: 20px;
            z-index: 100;
        }

        .summary-header {
            font-family: 'Space Grotesk';
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--primary);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }
        .summary-item:last-child { border-bottom: none; }
        .summary-item.updated {
            background: #fef3c7;
            padding-left: 8px;
            border-radius: 4px;
        }
        .summary-label { color: var(--text-muted); }
        .summary-value { font-weight: 600; color: var(--text-main); }

        /* Confidence Meter */
        .confidence-meter {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 2px solid #e2e8f0;
        }
        .meter-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            margin-bottom: 8px;
        }
        .meter-bar {
            height: 24px;
            background: #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
        }
        .meter-fill {
            height: 100%;
            background: linear-gradient(90deg, #f59e0b 0%, #10b981 50%, #059669 100%);
            width: 0%;
            transition: width 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 8px;
        }
        .meter-text {
            font-size: 0.7rem;
            font-weight: 700;
            color: white;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }

        /* 2D Floor Plan Panel */
        .floor-plan-panel {
            position: absolute;
            bottom: 20px;
            left: 20px;
            width: 420px;
            height: 480px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            padding: 16px;
            z-index: 100;
            display: flex;
            flex-direction: column;
        }

        .plan-header {
            font-family: 'Space Grotesk';
            font-weight: 700;
            font-size: 1rem;
            color: var(--primary);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .plan-canvas-container {
            flex: 1;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }

        #floor-plan-canvas {
            width: 100%;
            height: 100%;
            display: block;
        }

        .plan-legend {
            margin-top: 12px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            font-size: 0.7rem;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 2px;
            border: 1px solid #cbd5e1;
        }

        /* 3D Visualization Container */
        #viz-3d {
            width: 100%;
            height: 100%;
            position: relative;
        }

        /* Helper Text */
        .helper-text {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 4px;
            font-style: italic;
        }

        /* Context Hints */
        .context-hint {
            background: #fef3c7;
            border-left: 3px solid #f59e0b;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 0.8rem;
            color: #92400e;
            margin-top: 8px;
            display: none;
        }
        .context-hint.show { display: block; animation: slideIn 0.3s ease; }

        /* Auto-calculated fields */
        .auto-calc {
            background: #f0fdf4;
            border: 1px solid #86efac;
            padding: 12px;
            border-radius: 6px;
            margin-top: 8px;
        }
        .auto-calc-label {
            font-size: 0.75rem;
            color: #15803d;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .auto-calc-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: #166534;
        }

        /* Final Review Step */
        .review-grid {
            display: grid;
            gap: 16px;
        }
        .review-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
        }
        .review-title {
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 12px;
            font-size: 0.9rem;
        }
        .review-detail {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 0.85rem;
        }

        /* Loading Animation */
        .generating {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 20px;
        }
        .generating.active { display: flex; }
        .gen-text {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            font-family: 'Space Grotesk';
        }
        .gen-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255,255,255,0.2);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* View Switcher */
        .view-switcher-container {
            position: absolute;
            top: 100px;
            left: 50%;
            transform: translateX(-50%) translateY(-150px);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            z-index: 2000;
            transition: all 0.7s cubic-bezier(0.34, 1.56, 0.64, 1);
            opacity: 0;
            pointer-events: none;
        }
        .view-switcher-container.visible {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
            pointer-events: all;
        }
        .mode-2d .view-switcher-container {
            z-index: 4000; /* Ensure it stays above the 2D floor plan panel */
            top: 40px;
        }
        .switcher-label {
            font-size: 0.9rem;
            color: #64748b;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        .view-switcher {
            display: flex;
            gap: 20px;
            align-items: center;
            background: white;
            padding: 10px 30px;
            border-radius: 100px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        .view-btn {
            background: transparent;
            border: none;
            color: #94a3b8;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.3s;
            border-radius: 50%;
        }
        .view-btn.active {
            background: #4fc3f7; /* Blue circular style from image */
            color: white;
            transform: scale(1.15);
            box-shadow: 0 8px 15px rgba(79, 195, 247, 0.3);
        }
        .view-btn i { font-size: 1.4rem; }
        
        /* Top Right Actions */
        .top-right-actions {
            position: fixed;
            top: 25px;
            right: 40px;
            z-index: 3000;
            display: flex;
            gap: 15px;
            opacity: 0;
            pointer-events: none;
            transition: 0.5s;
        }
        .generated-state .top-right-actions { opacity: 1; pointer-events: all; }
        .btn-pdf {
            background: #10b981;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.2);
        }
        .btn-estimate-budget {
            background: #d97706;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(217, 119, 6, 0.2);
        }

        /* Mode Visibility */
        .mode-3d #viz-3d, .mode-walk #viz-3d { opacity: 1; pointer-events: all; }
        
        /* Mini Floor Plan in 3D/Walk */
        .floor-plan-panel { 
            display: flex; 
            width: 260px; 
            height: 280px; 
            bottom: 20px; 
            left: 20px; 
            z-index: 50; 
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid var(--border);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .generated-state .floor-plan-panel { display: flex; opacity: 0.8; }
        .generated-state .floor-plan-panel:hover { opacity: 1; }
        

        /* Generated State */
        .generated-state .input-panel { display: none; }
        .generated-state .viz-panel { width: 100vw; height: 100vh; position: fixed; top: 0; left: 0; padding: 0; }
        .generated-state .live-summary { top: 80px; }
        .generated-state .view-switcher { display: flex; }
        
        .back-to-input {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            display: none;
        }
        .generated-state .back-to-input { display: flex; }

        @keyframes pulse-update {
            0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
            50% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
        }
        .updating {
            animation: pulse-update 0.6s ease-out;
        }
    </style>
</head>
<body>

    <!-- 3D Background -->
    <div id="bg-canvas"></div>

    <!-- Loading Animation -->
    <div class="generating" id="generating">
        <div class="gen-spinner"></div>
        <div class="gen-text">Generating Conceptual Design...</div>
    </div>

    <nav>
        <a href="#" class="brand"><i class="fas fa-drafting-compass"></i>Constructa <span style="font-weight:400; font-size:1rem; opacity:0.7;">| AI Architect Studio</span></a>
        <a href="homeowner.php" class="back-link">Exit to Dashboard</a>
    </nav>

    <div id="app-container">
        
        <!-- Left Input Panel -->
        <aside class="input-panel">
            <div class="stepper-header">
                <div style="display:flex; justify-content:space-between; color:var(--text-muted); font-size:0.85rem; font-weight:600;">
                    <span id="step-label">1. PLOT DETAILS</span>
                    <span id="step-count">1/5</span>
                </div>
                <div class="progress-indicator">
                    <div class="progress-bar"><div class="progress-fill" id="p1" style="width:100%"></div></div>
                    <div class="progress-bar"><div class="progress-fill" id="p2"></div></div>
                    <div class="progress-bar"><div class="progress-fill" id="p3"></div></div>
                    <div class="progress-bar"><div class="progress-fill" id="p4"></div></div>
                    <div class="progress-bar"><div class="progress-fill" id="p5"></div></div>
                </div>
            </div>

            <div class="scrollable-form" id="wizard-form">
                
                <!-- STEP 1: PLOT DETAILS -->
                <div class="wizard-step active" id="step-1">
                    <h2>Plot Details</h2>
                    <p class="subtitle">Foundation data for your dream home</p>
                    
                    <div class="form-section">
                        <div class="section-label"><i class="fas fa-ruler-combined"></i> Plot Dimensions</div>
                        <div class="input-grid">
                            <div>
                                <label>Plot Length (ft)</label>
                                <input type="number" class="c-input" id="inp-length" value="50" oninput="updateCalculations()">
                                <div class="helper-text">Front to back measurement</div>
                            </div>
                            <div>
                                <label>Plot Width (ft)</label>
                                <input type="number" class="c-input" id="inp-width" value="30" oninput="updateCalculations()">
                                <div class="helper-text">Side to side measurement</div>
                            </div>
                        </div>
                        <div class="auto-calc">
                            <div class="auto-calc-label">Total Plot Area</div>
                            <div class="auto-calc-value"><span id="plot-area">1500</span> sq.ft</div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-label"><i class="fas fa-compass"></i> Plot Facing</div>
                        <div class="input-grid" style="grid-template-columns: repeat(4, 1fr);">
                            <div class="selection-card selected" onclick="selectFacing(this, 'North')">
                                <i class="fas fa-arrow-up"></i>
                                <span>North</span>
                            </div>
                            <div class="selection-card" onclick="selectFacing(this, 'South')">
                                <i class="fas fa-arrow-down"></i>
                                <span>South</span>
                            </div>
                            <div class="selection-card" onclick="selectFacing(this, 'East')">
                                <i class="fas fa-arrow-right"></i>
                                <span>East</span>
                            </div>
                            <div class="selection-card" onclick="selectFacing(this, 'West')">
                                <i class="fas fa-arrow-left"></i>
                                <span>West</span>
                            </div>
                        </div>
                        <div class="helper-text">Direction your main entrance faces</div>
                    </div>
                    
                    <div class="form-section">
                        <div class="section-label"><i class="fas fa-road"></i> Road Access</div>
                        <label>Road Width (ft)</label>
                        <input type="range" min="10" max="60" value="30" id="road-width" oninput="updateRoadWidth()">
                        <div style="text-align:center; font-weight:600; margin-top:8px; color:var(--primary);">
                            <span id="road-width-val">30</span> ft
                        </div>
                        <div class="helper-text">Wider roads allow better access and ventilation</div>
                    </div>

                    <div class="form-section">
                        <div class="section-label"><i class="fas fa-map-marker-alt"></i> Corner Plot</div>
                        <div class="input-grid" style="grid-template-columns: 1fr 1fr;">
                            <div class="selection-card selected" onclick="toggleCorner(this, false)">
                                <i class="fas fa-times"></i>
                                <span>No</span>
                            </div>
                            <div class="selection-card" onclick="toggleCorner(this, true)">
                                <i class="fas fa-check"></i>
                                <span>Yes</span>
                            </div>
                        </div>
                        <div class="context-hint" id="corner-hint">
                            <i class="fas fa-lightbulb"></i> Corner plots offer better ventilation and multiple entry options!
                        </div>
                    </div>
                </div>

                <!-- STEP 2: FLOOR & ROOMS -->
                <div class="wizard-step" id="step-2">
                    <h2>Floor & Room Requirements</h2>
                    <p class="subtitle">Define your space needs</p>

                    <div class="form-section">
                        <div class="section-label"><i class="fas fa-building"></i> Number of Floors</div>
                        <div class="input-grid" style="grid-template-columns: repeat(4, 1fr);">
                            <div class="selection-card selected" onclick="selectFloors(this, 1)">
                                <span style="font-size:1.5rem; font-weight:700;">1</span>
                                <span style="font-size:0.75rem;">Floor</span>
                            </div>
                            <div class="selection-card" onclick="selectFloors(this, 2)">
                                <span style="font-size:1.5rem; font-weight:700;">2</span>
                                <span style="font-size:0.75rem;">Floors</span>
                            </div>
                            <div class="selection-card" onclick="selectFloors(this, 3)">
                                <span style="font-size:1.5rem; font-weight:700;">3</span>
                                <span style="font-size:0.75rem;">Floors</span>
                            </div>
                            <div class="selection-card" onclick="selectFloors(this, 4)">
                                <span style="font-size:1.5rem; font-weight:700;">4+</span>
                                <span style="font-size:0.75rem;">Floors</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-label"><i class="fas fa-bed"></i> Bedrooms & Bathrooms</div>
                        <div class="counter-box">
                            <span>Bedrooms</span>
                            <div style="display:flex; gap:10px;">
                                <button class="btn-count" onclick="adjustCount('bedrooms',-1)">-</button>
                                <span id="val-bedrooms" style="min-width:20px; text-align:center;">3</span>
                                <button class="btn-count" onclick="adjustCount('bedrooms',1)">+</button>
                            </div>
                        </div>
                        <div class="counter-box">
                            <span>Bathrooms</span>
                            <div style="display:flex; gap:10px;">
                                <button class="btn-count" onclick="adjustCount('bathrooms',-1)">-</button>
                                <span id="val-bathrooms" style="min-width:20px; text-align:center;">2</span>
                                <button class="btn-count" onclick="adjustCount('bathrooms',1)">+</button>
                            </div>
                        </div>
                        <div class="context-hint" id="bathroom-hint">
                            <i class="fas fa-info-circle"></i> Recommended: 1 bathroom per 2 bedrooms
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-label"><i class="fas fa-couch"></i> Additional Spaces</div>
                        <div class="input-grid">
                            <div class="selection-card selected" onclick="toggleSpace(this, 'living')">
                                <i class="fas fa-couch"></i>
                                <span>Living Room</span>
                            </div>
                            <div class="selection-card selected" onclick="toggleSpace(this, 'kitchen')">
                                <i class="fas fa-utensils"></i>
                                <span>Kitchen</span>
                            </div>
                            <div class="selection-card" onclick="toggleSpace(this, 'dining')">
                                <i class="fas fa-chair"></i>
                                <span>Dining</span>
                            </div>
                            <div class="selection-card" onclick="toggleSpace(this, 'balcony')">
                                <i class="fas fa-tree"></i>
                                <span>Balcony</span>
                            </div>
                        </div>
                    </div>

                    <div class="auto-calc">
                        <div class="auto-calc-label">Estimated Built-up Area</div>
                        <div class="auto-calc-value"><span id="builtup-area">1200</span> sq.ft</div>
                        <div class="helper-text" style="color:#15803d; margin-top:4px;">Based on your room requirements</div>
                    </div>
                </div>

                <!-- STEP 3: BUDGET & PRIORITY -->
                <div class="wizard-step" id="step-3">
                    <h2>Budget & Priority</h2>
                    <p class="subtitle">Set your investment and preferences</p>
                    
                    <div class="form-section">
                        <div class="section-label"><i class="fas fa-rupee-sign"></i> Construction Budget</div>
                        <label>Budget Range (₹ Lakhs)</label>
                        <input type="range" min="10" max="500" step="5" value="50" id="budget-slider" oninput="updateBudget()">
                        <div style="text-align:center; font-weight:700; font-size:1.8rem; color:var(--primary); margin-top:16px;">
                            ₹ <span id="budget-val">50</span> Lakhs
                        </div>
                        <div style="display:flex; justify-content:space-between; font-size:0.75rem; color:var(--text-muted); margin-top:8px;">
                            <span>₹10L</span>
                            <span>₹500L</span>
                        </div>
                        <div class="helper-text">Includes construction, materials, and labor</div>
                    </div>

                    <div class="form-section">
                        <div class="section-label"><i class="fas fa-star"></i> Priority</div>
                        <div class="input-grid">
                            <div class="selection-card selected" onclick="selectPriority(this, 'space')">
                                <i class="fas fa-expand-arrows-alt"></i>
                                <span>Maximize Space</span>
                            </div>
                            <div class="selection-card" onclick="selectPriority(this, 'quality')">
                                <i class="fas fa-gem"></i>
                                <span>Better Quality</span>
                            </div>
                        </div>
                        <div class="helper-text">This affects material selection and space optimization</div>
                    </div>

                    <div class="auto-calc">
                        <div class="auto-calc-label">Estimated Cost per Sq.Ft</div>
                        <div class="auto-calc-value">₹ <span id="cost-per-sqft">1667</span></div>
                    </div>
                </div>

                <!-- STEP 4: DESIGN PREFERENCES -->
                <div class="wizard-step" id="step-4">
                    <h2>Design Preferences</h2>
                    <p class="subtitle">Personalize your home's character</p>

                    <div class="form-section">
                        <div class="section-label"><i class="fas fa-car"></i> Parking</div>
                        <div class="input-grid" style="grid-template-columns: repeat(3, 1fr);">
                            <div class="selection-card" onclick="selectParking(this, 0)">
                                <i class="fas fa-ban"></i>
                                <span>None</span>
                            </div>
                            <div class="selection-card selected" onclick="selectParking(this, 1)">
                                <i class="fas fa-car"></i>
                                <span>1 Car</span>
                            </div>
                            <div class="selection-card" onclick="selectParking(this, 2)">
                                <i class="fas fa-car-side"></i>
                                <span>2 Cars</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-label"><i class="fas fa-palette"></i> Design Style</div>
                        <div class="input-grid">
                            <div class="selection-card selected" onclick="selectStyle(this, 'modern')">
                                <i class="fas fa-cube"></i>
                                <span>Modern</span>
                            </div>
                            <div class="selection-card" onclick="selectStyle(this, 'traditional')">
                                <i class="fas fa-gopuram"></i>
                                <span>Traditional</span>
                            </div>
                        </div>
                        <div class="helper-text">Affects roof shape, colors, and overall aesthetics</div>
                    </div>

                    <div class="form-section">
                        <div class="section-label"><i class="fas fa-om"></i> Vaastu Compliance</div>
                        <div class="input-grid">
                            <div class="selection-card" onclick="toggleVaastu(this, true)">
                                <i class="fas fa-check"></i>
                                <span>Yes, Follow Vaastu</span>
                            </div>
                            <div class="selection-card selected" onclick="toggleVaastu(this, false)">
                                <i class="fas fa-times"></i>
                                <span>No Preference</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 5: REVIEW -->
                <div class="wizard-step" id="step-5">
                    <h2>Review & Confirm</h2>
                    <p class="subtitle">Verify your design inputs before generation</p>

                    <div class="review-grid">
                        <div class="review-card">
                            <div class="review-title">Plot Information</div>
                            <div class="review-detail"><span>Dimensions:</span><span id="rev-dims">50 x 30 ft</span></div>
                            <div class="review-detail"><span>Total Area:</span><span id="rev-plot-area">1500 sq.ft</span></div>
                            <div class="review-detail"><span>Facing:</span><span id="rev-facing">North</span></div>
                            <div class="review-detail"><span>Corner Plot:</span><span id="rev-corner">No</span></div>
                        </div>

                        <div class="review-card">
                            <div class="review-title">Structure</div>
                            <div class="review-detail"><span>Floors:</span><span id="rev-floors">1</span></div>
                            <div class="review-detail"><span>Bedrooms:</span><span id="rev-bedrooms">3</span></div>
                            <div class="review-detail"><span>Bathrooms:</span><span id="rev-bathrooms">2</span></div>
                            <div class="review-detail"><span>Built-up Area:</span><span id="rev-builtup">1200 sq.ft</span></div>
                        </div>

                        <div class="review-card">
                            <div class="review-title">Budget & Design</div>
                            <div class="review-detail"><span>Budget:</span><span id="rev-budget">₹50 Lakhs</span></div>
                            <div class="review-detail"><span>Priority:</span><span id="rev-priority">Maximize Space</span></div>
                            <div class="review-detail"><span>Style:</span><span id="rev-style">Modern</span></div>
                            <div class="review-detail"><span>Parking:</span><span id="rev-parking">1 Car</span></div>
                        </div>
                    </div>

                    <div style="margin-top:24px; padding:16px; background:#fef3c7; border-radius:8px; border-left:4px solid #f59e0b;">
                        <div style="font-weight:700; color:#92400e; margin-bottom:8px;">
                            <i class="fas fa-info-circle"></i> Ready to Generate
                        </div>
                        <div style="font-size:0.85rem; color:#92400e;">
                            Click "Generate Plan" to create your conceptual design. This will produce an abstract 3D visualization and floor plan based on your inputs.
                        </div>
                    </div>
                </div>

            </div>

            <div class="wizard-footer">
                <button class="btn-hollow" id="btn-back" onclick="changeStep(-1)">Back</button>
                <button class="btn-solid" id="btn-next" onclick="changeStep(1)">
                    Next Step <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </aside>

        <!-- Right Panel - 3D Viz + Summary -->
        <main class="viz-panel mode-3d" id="viz-container">
            <button class="btn-hollow back-to-input" onclick="exitGeneratedState()"><i class="fas fa-chevron-left"></i> Adjust Requirements</button>
            
            <!-- View Switcher (Circular Style) -->
            <div class="view-switcher-container" id="main-switcher">
                <div class="switcher-label">Select your view mode</div>
                <div class="view-switcher">
                    <button class="view-btn active" id="btn-view-3d" onclick="switchView('3d')">3D</button>
                    <button class="view-btn" id="btn-view-walk" onclick="switchView('walk')"><i class="fas fa-walking"></i></button>
                </div>
            </div>

            <!-- Top Right Actions -->
            <div class="top-right-actions">
                <button class="btn-pdf" onclick="downloadPlan()"><i class="fas fa-file-pdf"></i> Download PDF</button>
                <button class="btn-estimate-budget" onclick="location.href='budget_calculator.php'"><i class="fas fa-calculator"></i> Estimate Budget</button>
            </div>

            <!-- Live Summary -->
            <div class="live-summary">
                <div class="summary-header">
                    <i class="fas fa-chart-line"></i> Live Summary
                </div>
                <div class="summary-item">
                    <span class="summary-label">Plot Area</span>
                    <span class="summary-value" id="sum-plot">1500 sq.ft</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Built-up Area</span>
                    <span class="summary-value" id="sum-builtup">1200 sq.ft</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Floors</span>
                    <span class="summary-value" id="sum-floors">1</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Bedrooms</span>
                    <span class="summary-value" id="sum-bedrooms">3</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Budget</span>
                    <span class="summary-value" id="sum-budget">₹50L</span>
                </div>

                <div class="confidence-meter">
                    <div class="meter-label">Plan Confidence</div>
                    <div class="meter-bar">
                        <div class="meter-fill" id="confidence-fill" style="width:60%">
                            <span class="meter-text" id="confidence-text">GOOD</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2D Floor Plan -->
            <div class="floor-plan-panel">
                <div class="plan-header">
                    <span><i class="fas fa-drafting-compass"></i> Live Floor Plan</span>
                    <div style="display: flex; gap: 8px;">
                        <button class="btn-export" onclick="exportPlan('png')" title="Export as PNG"><i class="fas fa-image"></i> PNG</button>
                        <button class="btn-export" onclick="exportPlan('jpg')" title="Export as JPG"><i class="fas fa-file-image"></i> JPG</button>
                    </div>
                </div>
                <div class="plan-canvas-container">
                    <canvas id="floor-plan-canvas"></canvas>
                </div>
                <div class="plan-legend">
                    <div class="legend-item">
                        <div class="legend-color" style="background:#93c5fd;"></div>
                        <span>Bedroom</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background:#a5f3fc;"></div>
                        <span>Bathroom</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background:#fcd34d;"></div>
                        <span>Kitchen</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background:#86efac;"></div>
                        <span>Living</span>
                    </div>
                </div>
            </div>

            <!-- 3D Visualization -->
            <div id="viz-3d"></div>
            
            <div id="walk-instructions" style="
                display: none;
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                color: white;
                font-family: 'Space Grotesk';
                font-size: 1.5rem;
                text-align: center;
                text-shadow: 0 2px 4px rgba(0,0,0,0.5);
                pointer-events: none;
                z-index: 300;
            ">
                Click to Start Walk<br>
                <span style="font-size: 1rem; opacity: 0.8;">WASD to Move | Mouse to Look | ESC to Exit</span>
            </div>
        </main>

    </div>

    <script>
        // === DATA STATE ===
        let currentStep = 1;
        const totalSteps = 5;
        let formData = {
            length: 50,
            width: 30,
            facing: 'North',
            roadWidth: 30,
            cornerPlot: false,
            floors: 1,
            bedrooms: 3,
            bathrooms: 2,
            spaces: ['living', 'kitchen'],
            budget: 50,
            priority: 'space',
            parking: 1,
            style: 'modern',
            vaastu: false
        };

        // === UNIFIED SPATIAL DATA MODEL ===
        let houseLayout = {
            plot: { width: 50, length: 50 },
            rooms: []
        };

        function generateHouseLayout() {
            const plotW = formData.width;
            const plotL = formData.length;
            const rooms = [];
            const margin = 2; // Wall offset
            const buildW = plotW - margin * 2;
            const buildL = plotL - margin * 2;
            
            const backDepth = buildL * 0.35;
            const middleDepth = buildL * 0.25;
            const frontDepth = buildL * 0.4;
            
            let currentY = margin;

            // --- BACK ZONE ---
            const brCount = formData.bedrooms;
            const baCount = formData.bathrooms;
            const backRoomsList = [];
            for(let i=0; i<brCount; i++) {
                let brFurniture = [
                    {type: 'bed', x: 0.1, y: 0.1, w: 6, l: 7},
                    {type: 'wardrobe', x: 0.7, y: 0.05, w: 4, l: 2}
                ];
                // Add Computer Setup to first bedroom
                if (i === 0) {
                    brFurniture.push({type: 'desk', x: 0.1, y: 0.65, w: 5, l: 3});
                    brFurniture.push({type: 'chair', x: 0.25, y: 0.55, w: 1.5, l: 1.5});
                }
                backRoomsList.push({type: 'bedroom', name: `Bedroom ${i+1}`, furniture: brFurniture});
            }
            for(let i=0; i<baCount; i++) backRoomsList.push({type: 'bathroom', name: `Bath ${i+1}`, furniture: [
                {type: 'toilet', x: 0.7, y: 0.2, w: 2, l: 2},
                {type: 'shower', x: 0.1, y: 0.1, w: 3, l: 3}
            ]});

            if(backRoomsList.length > 0) {
                const rw = buildW / backRoomsList.length;
                backRoomsList.forEach((r, i) => {
                    rooms.push({
                        ...r, x: margin + (i * rw), y: currentY, width: rw, height: backDepth,
                        color: r.type === 'bedroom' ? '#dbbefe' : '#cffafe',
                        doors: [{ wall: 'South', pos: 0.5 }]
                    });
                });
            }
            currentY += backDepth;

            // --- MIDDLE ZONE ---
            const midRoomsList = [];
            if(formData.spaces.includes('kitchen')) midRoomsList.push({type: 'kitchen', name: 'Kitchen', color: '#fef3c7', furniture: [
                {type: 'counter', x: 0.05, y: 0.05, w: 10, l: 2},
                {type: 'fridge', x: 0.85, y: 0.05, w: 3, l: 3}
            ]});
            if(formData.spaces.includes('dining')) midRoomsList.push({type: 'dining', name: 'Dining', color: '#fef9c3', furniture: [
                {type: 'table', x: 0.5, y: 0.5, w: 6, l: 4}
            ]});

            if(midRoomsList.length > 0) {
                const rw = buildW / midRoomsList.length;
                midRoomsList.forEach((r, i) => {
                    rooms.push({
                        ...r, x: margin + (i * rw), y: currentY, width: rw, height: middleDepth,
                        doors: [{ wall: 'North', pos: 0.5 }, { wall: 'South', pos: 0.5 }]
                    });
                });
            }
            currentY += middleDepth;

            // --- FRONT ZONE ---
            if(formData.spaces.includes('living')) {
                rooms.push({
                    type: 'living', name: 'Living Room', color: '#e0f2fe',
                    x: margin, y: currentY, width: buildW, height: frontDepth,
                    furniture: [
                        {type: 'sofa', x: 0.2, y: 0.6, w: 8, l: 3},
                        {type: 'tv', x: 0.5, y: 0.05, w: 4, l: 1}
                    ],
                    doors: [{ wall: 'North', pos: 0.5 }, { wall: 'South', pos: 0.2, type: 'main' }]
                });
            }

            houseLayout = { plot: { width: plotW, length: plotL }, rooms };
        }

        // Track previous values to detect structural changes
        let previousStructure = {
            length: 50,
            width: 30,
            floors: 1,
            bedrooms: 3,
            bathrooms: 2,
            spaces: ['living', 'kitchen']
        };

        // === 3D BACKGROUND (From Landing Page) ===
        function init3DBackground() {
            const container = document.getElementById('bg-canvas');
            const scene = new THREE.Scene();
            const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
            camera.position.set(0, 2, 8);

            const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            container.appendChild(renderer.domElement);

            // Grid
            const gridHelper = new THREE.GridHelper(100, 100, 0xccd5ae, 0xe2e8f0);
            gridHelper.position.y = -2;
            scene.add(gridHelper);

            // City buildings
            const cityGroup = new THREE.Group();
            const buildingMaterial = new THREE.MeshBasicMaterial({ color: 0xf6f7f2, transparent: true, opacity: 0.05 });
            const edgeMaterial = new THREE.LineBasicMaterial({ color: 0x294033, transparent: true, opacity: 0.15 });

            for (let x = -5; x <= 5; x++) {
                for (let z = -5; z <= 5; z++) {
                    if (Math.abs(x) < 2 && Math.abs(z) < 2) continue;
                    const height = Math.random() * 3 + 1;
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
            scene.add(cityGroup);

            let mouseX = 0, mouseY = 0;
            document.addEventListener('mousemove', (e) => {
                mouseX = (e.clientX - window.innerWidth / 2) * 0.001;
                mouseY = (e.clientY - window.innerHeight / 2) * 0.001;
            });

            function animate() {
                requestAnimationFrame(animate);
                cityGroup.rotation.y += 0.001;
                cityGroup.rotation.x += 0.05 * (mouseY - cityGroup.rotation.x);
                cityGroup.rotation.y += 0.05 * (mouseX - cityGroup.rotation.y);
                renderer.render(scene, camera);
            }
            animate();

            window.addEventListener('resize', () => {
                camera.aspect = window.innerWidth / window.innerHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(window.innerWidth, window.innerHeight);
            });
        }
        init3DBackground();

        // === 2D FLOOR PLAN DRAWING ===
        let floorPlanCanvas, floorPlanCtx;
        
        function init2DFloorPlan() {
            floorPlanCanvas = document.getElementById('floor-plan-canvas');
            const container = floorPlanCanvas.parentElement;
            
            // High-DPI Support for clarity
            const dpr = window.devicePixelRatio || 2;
            floorPlanCanvas.width = container.clientWidth * dpr;
            floorPlanCanvas.height = container.clientHeight * dpr;
            floorPlanCanvas.style.width = container.clientWidth + 'px';
            floorPlanCanvas.style.height = container.clientHeight + 'px';
            
            floorPlanCtx = floorPlanCanvas.getContext('2d');
            floorPlanCtx.scale(dpr, dpr);
            
            drawFloorPlan();
            
            window.addEventListener('resize', () => {
                const newDpr = window.devicePixelRatio || 2;
                floorPlanCanvas.width = container.clientWidth * newDpr;
                floorPlanCanvas.height = container.clientHeight * newDpr;
                floorPlanCanvas.style.width = container.clientWidth + 'px';
                floorPlanCanvas.style.height = container.clientHeight + 'px';
                floorPlanCtx = floorPlanCanvas.getContext('2d');
                floorPlanCtx.scale(newDpr, newDpr);
                drawFloorPlan();
            });
        }

        function drawFloorPlan() {
            if (!floorPlanCtx) return;
            
            const ctx = floorPlanCtx;
            // Use CSS logical dimensions for drawing since we already applied ctx.scale()
            const width = parseFloat(floorPlanCanvas.style.width);
            const height = parseFloat(floorPlanCanvas.style.height);
            
            // Clear canvas
            ctx.clearRect(0, 0, width, height);

            // Set drawing quality
            ctx.imageSmoothingEnabled = true;
            ctx.imageSmoothingQuality = 'high';

            // Draw Technical Grid
            ctx.beginPath();
            ctx.strokeStyle = 'rgba(255, 255, 255, 0.1)';
            ctx.lineWidth = 1;
            const gridSize = 20;
            for(let x = 0; x <= width; x += gridSize) { ctx.moveTo(x, 0); ctx.lineTo(x, height); }
            for(let y = 0; y <= height; y += gridSize) { ctx.moveTo(0, y); ctx.lineTo(width, y); }
            ctx.stroke();

            // Calculate scale and offset (consistent for all drawing)
            const padding = 40;
            const scaleX = (width - padding) / houseLayout.plot.width;
            const scaleY = (height - padding) / houseLayout.plot.length;
            const scale = Math.min(scaleX, scaleY);
            
            const plotPixelW = houseLayout.plot.width * scale;
            const plotPixelL = houseLayout.plot.length * scale;
            const offX = (width - plotPixelW) / 2;
            const offY = (height - plotPixelL) / 2;
            
            // Draw Plot Boundary
            ctx.strokeStyle = '#294033';
            ctx.lineWidth = 3;
            ctx.strokeRect(offX, offY, plotPixelW, plotPixelL);
            
            // Draw Plot Dimensions
            ctx.fillStyle = '#64748b';
            ctx.font = '10px Inter';
            ctx.textAlign = 'center';
            ctx.fillText(`${formData.width}'`, offX + plotPixelW / 2, offY - 10);
            ctx.save();
            ctx.translate(offX - 10, offY + plotPixelL / 2);
            ctx.rotate(-Math.PI / 2);
            ctx.fillText(`${formData.length}'`, 0, 0);
            ctx.restore();
            
            // Draw Rooms
            houseLayout.rooms.forEach(room => {
                const rx = offX + room.x * scale;
                const ry = offY + room.y * scale;
                const rw = room.width * scale;
                const rh = room.height * scale;
                
                ctx.fillStyle = room.color;
                ctx.fillRect(rx, ry, rw, rh);
                ctx.strokeStyle = '#294033';
                ctx.lineWidth = 1;
                ctx.strokeRect(rx, ry, rw, rh);
                // Label (Smart Hide if too small)
                if (rw > 50 && rh > 40) {
                    ctx.fillStyle = '#1e293b';
                    ctx.font = 'bold 8.5px Inter'; // Slightly smaller font for better fit
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillText(room.name, rx + rw/2, ry + rh/2 - 6);
                    
                    ctx.font = '7.5px Inter';
                    ctx.fillStyle = 'rgba(30, 41, 59, 0.6)';
                    ctx.fillText(`${Math.round(room.width * room.height)} sq.ft`, rx + rw/2, ry + rh/2 + 6);
                } else if (rw > 30) {
                    // Small room: show icon or abbreviated text
                    ctx.fillStyle = '#1e293b';
                    ctx.font = 'bold 8px Inter';
                    ctx.textAlign = 'center';
                    ctx.fillText(room.name.charAt(0), rx + rw/2, ry + rh/2);
                }

                // Draw Furniture Icons
                if(room.furniture) {
                    ctx.fillStyle = 'rgba(0,0,0,0.06)';
                    room.furniture.forEach(f => {
                        const fx = rx + f.x * rw;
                        const fy = ry + f.y * rh;
                        const fw = f.w * scale;
                        const fl = f.l * scale;
                        ctx.fillRect(fx, fy, fw, fl);
                    });
                }

                // Draw Doors
                if(room.doors) {
                    ctx.strokeStyle = '#294033';
                    ctx.lineWidth = 2;
                    room.doors.forEach(d => {
                        const doorSize = 3 * scale;
                        let dx, dy;
                        if(d.wall === 'South') { dx = rx + rw * d.pos; dy = ry + rh; ctx.beginPath(); ctx.arc(dx, dy, doorSize, Math.PI, 1.5*Math.PI); ctx.stroke(); }
                        if(d.wall === 'North') { dx = rx + rw * d.pos; dy = ry; ctx.beginPath(); ctx.arc(dx, dy, doorSize, 0, 0.5*Math.PI); ctx.stroke(); }
                    });
                }
            });
            
            // Draw scale indicator
            ctx.fillStyle = '#64748b';
            ctx.font = '9px Inter';
            ctx.textAlign = 'left';
            ctx.fillText(`Scale: 1:${Math.round(100/scale)}`, offX, height - 10);
            
            floorPlanCanvas.classList.add('updating');
            setTimeout(() => floorPlanCanvas.classList.remove('updating'), 600);
        }

        // === 3D HOUSE VISUALIZATION ===
        let houseScene, houseCamera, houseRenderer, houseGroup, orbitControls;
        
        function init3DHouse() {
            try {
                const container = document.getElementById('viz-3d');
            houseScene = new THREE.Scene();
            // TRANSPARENT BACKGROUND - Let the animated city background show through!
            houseScene.background = null;
            
            houseCamera = new THREE.PerspectiveCamera(50, container.clientWidth / container.clientHeight, 0.1, 1000);
            houseCamera.position.set(8, 6, 8);
            houseCamera.lookAt(0, 0, 0);

            houseRenderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            houseRenderer.setClearColor(0x000000, 0); // Fully transparent
            houseRenderer.setSize(container.clientWidth, container.clientHeight);
            container.appendChild(houseRenderer.domElement);

            // Lights
            const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
            houseScene.add(ambientLight);
            const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
            directionalLight.position.set(5, 10, 5);
            houseScene.add(directionalLight);

            // House Group
            houseGroup = new THREE.Group();
            houseScene.add(houseGroup);
            
            // Orbit Controls
            if(typeof THREE.OrbitControls !== 'undefined') {
                orbitControls = new THREE.OrbitControls(houseCamera, houseRenderer.domElement);
                orbitControls.enableDamping = true;
                orbitControls.dampingFactor = 0.05;
                orbitControls.autoRotate = true;
                orbitControls.autoRotateSpeed = 2.0;
            } else {
                console.warn('OrbitControls not loaded');
            }

            updateHouseVisualization();
            animateHouse();

            window.addEventListener('resize', () => {
                houseCamera.aspect = container.clientWidth / container.clientHeight;
                houseCamera.updateProjectionMatrix();
                houseRenderer.setSize(container.clientWidth, container.clientHeight);
            });
        } catch (err) {
            console.error("3D House Error:", err);
            document.getElementById('viz-3d').innerHTML = `<div style="color:red; opacity:0.7; padding:20px;">3D View Unavailable</div>`;
        }
        }
        
        function createDetailedFurniture(type, w, l, h, mat) {
            const group = new THREE.Group();
            
            if (type === 'bed') {
                // Base
                const base = new THREE.Mesh(new THREE.BoxGeometry(w, h * 0.4, l), mat);
                group.add(base);
                // Mattress
                const matrs = new THREE.Mesh(new THREE.BoxGeometry(w * 0.95, h * 0.3, l * 0.95), new THREE.MeshStandardMaterial({color: 0xeeeeee}));
                matrs.position.y = h * 0.35;
                group.add(matrs);
                // Pillows
                const p1 = new THREE.Mesh(new THREE.BoxGeometry(w * 0.35, h * 0.2, l * 0.2), new THREE.MeshStandardMaterial({color: 0xffffff}));
                p1.position.set(-w * 0.2, h * 0.5, -l * 0.35);
                group.add(p1);
                const p2 = p1.clone();
                p2.position.x = w * 0.2;
                group.add(p2);
            } else if (type === 'sofa') {
                // Base
                const base = new THREE.Mesh(new THREE.BoxGeometry(w, h * 0.6, l), mat);
                group.add(base);
                // Backrest
                const back = new THREE.Mesh(new THREE.BoxGeometry(w, h * 0.8, l * 0.2), mat);
                back.position.set(0, h * 0.4, -l * 0.4);
                group.add(back);
            } else if (type === 'desk' || type === 'counter') {
                // Top
                const top = new THREE.Mesh(new THREE.BoxGeometry(w, 0.1 * 0.2, l), mat);
                top.position.y = h;
                group.add(top);
                // Legs
                const lGeo = new THREE.BoxGeometry(0.1 * 0.2, h, 0.1 * 0.2);
                const l1 = new THREE.Mesh(lGeo, mat); l1.position.set(-w/2.2, h/2, -l/2.2); group.add(l1);
                const l2 = l1.clone(); l2.position.x = w/2.2; group.add(l2);
                const l3 = l1.clone(); l3.position.z = l/2.2; group.add(l3);
                const l4 = l3.clone(); l4.position.x = w/2.2; group.add(l4);
                
                if (type === 'desk') {
                    // PC / Monitor
                    const mon = new THREE.Mesh(new THREE.BoxGeometry(w * 0.4, h * 0.6, 0.05), new THREE.MeshStandardMaterial({color: 0x111111}));
                    mon.position.set(0, h + h * 0.35, -l * 0.3);
                    group.add(mon);
                }
            } else if (type === 'chair') {
                // Ergonomic Office Chair
                const seat = new THREE.Mesh(new THREE.BoxGeometry(w, 0.1, l), mat);
                seat.position.y = h * 0.4;
                group.add(seat);
                const back = new THREE.Mesh(new THREE.BoxGeometry(w, h * 0.6, 0.1), mat);
                back.position.set(0, h * 0.7, -l * 0.4);
                group.add(back);
                const leg = new THREE.Mesh(new THREE.BoxGeometry(0.1, h * 0.4, 0.1), new THREE.MeshStandardMaterial({color: 0x222222}));
                leg.position.y = h * 0.2;
                group.add(leg);
            } else if (type === 'avatar') {
                // Human Figure (User's request)
                const body = new THREE.Mesh(new THREE.CylinderGeometry(0.2 * 0.5, 0.2 * 0.5, 1.2 * 0.5), new THREE.MeshStandardMaterial({color: 0x3498db}));
                body.position.y = 0.3;
                group.add(body);
                const head = new THREE.Mesh(new THREE.SphereGeometry(0.15 * 0.5), new THREE.MeshStandardMaterial({color: 0xffdbac}));
                head.position.y = 0.7;
                group.add(head);
            } else {
                const box = new THREE.Mesh(new THREE.BoxGeometry(w, h, l), mat);
                box.position.y = h/2;
                group.add(box);
            }
            return group;
        }

        function updateHouseVisualization() {
            if(!houseGroup) return;
            while(houseGroup.children.length > 0) houseGroup.remove(houseGroup.children[0]);

            const plotScale = 0.5; // Increased scale
            const pW = houseLayout.plot.width * plotScale;
            const pL = houseLayout.plot.length * plotScale;
            const unitScale = 0.5; // Increased scale
            const hUnit = 10 * unitScale;
            
            // Platform
            const platformGeo = new THREE.BoxGeometry(pW, 0.1, pL);
            const platformMat = new THREE.MeshStandardMaterial({ color: 0x294033, transparent: true, opacity: 0.2 });
            const platform = new THREE.Mesh(platformGeo, platformMat);
            platform.position.y = -0.05;
            houseGroup.add(platform);

            for(let f = 0; f < formData.floors; f++) {
                const floorY = f * hUnit;
                houseLayout.rooms.forEach(room => {
                    const rW = room.width * unitScale;
                    const rL = room.height * unitScale;
                    const rX = (room.x * unitScale) - (pW / 2) + (rW / 2);
                    const rZ = (room.y * unitScale) - (pL / 2) + (rL / 2);

                    // Floor
                    const fGeo = new THREE.BoxGeometry(rW, 0.05, rL);
                    const fMat = new THREE.MeshStandardMaterial({ color: room.color });
                    const fMesh = new THREE.Mesh(fGeo, fMat);
                    fMesh.position.set(rX, floorY, rZ);
                    houseGroup.add(fMesh);

                    // Ceiling
                    const cGeo = new THREE.BoxGeometry(rW, 0.05, rL);
                    const cMat = new THREE.MeshStandardMaterial({ color: 0xffffff, transparent: !isWalking, opacity: isWalking ? 1 : 0.1 });
                    const cMesh = new THREE.Mesh(cGeo, cMat);
                    cMesh.position.set(rX, floorY + hUnit, rZ);
                    houseGroup.add(cMesh);

                    // Furniture
                    if(room.furniture) {
                        room.furniture.forEach(fur => {
                            const fw = fur.w * unitScale;
                            const fl = fur.l * unitScale;
                            const fh = (fur.type === 'wardrobe' ? 7 : (fur.type === 'desk' ? 2.5 : 1.5)) * unitScale;
                            const fx = rX - (rW/2) + (fur.x * rW) + (fw/2);
                            const fz = rZ - (rL/2) + (fur.y * rL) + (fl/2);
                            
                            const furMat = new THREE.MeshStandardMaterial({ color: (fur.type==='bed' || fur.type==='sofa') ? 0x8d6e63 : 0x5a4a42 });
                            const furMesh = createDetailedFurniture(fur.type, fw, fl, fh, furMat);
                            furMesh.position.set(fx, floorY, fz);
                            houseGroup.add(furMesh);
                        });
                        
                        // Add an Avatar to the Living Room and Office-style Bedrooms
                        if (f === 0) {
                            if (room.type === 'living' || room.furniture.some(f => f.type === 'desk')) {
                                const avatar = createDetailedFurniture('avatar', 0, 0, 0, null);
                                avatar.position.set(rX + (room.type==='living'?0.5:1), floorY, rZ + (room.type==='living'?0.5:1));
                                houseGroup.add(avatar);
                            }
                        }
                    }

                    // Walls
                    const wThick = 0.1 * unitScale;
                    const wMat = new THREE.MeshStandardMaterial({ color: 0xffffff });
                    const dW = 3 * unitScale;
                    const dH = 7 * unitScale;
                    
                    const sideWalls = [
                        { p: [0, 0, -rL/2], d: [rW, hUnit, wThick], door: room.doors?.find(d=>d.wall==='North') },
                        { p: [0, 0, rL/2], d: [rW, hUnit, wThick], door: room.doors?.find(d=>d.wall==='South') },
                        { p: [-rW/2, 0, 0], d: [wThick, hUnit, rL], door: room.doors?.find(d=>d.wall==='West') },
                        { p: [rW/2, 0, 0], d: [wThick, hUnit, rL], door: room.doors?.find(d=>d.wall==='East') }
                    ];

                    sideWalls.forEach(sw => {
                        const wallGrp = new THREE.Group();
                        wallGrp.position.set(rX + sw.p[0], floorY + hUnit/2, rZ + sw.p[2]);
                        if(sw.door) {
                            const lH = hUnit - dH;
                            const lGeo = new THREE.BoxGeometry(sw.d[0], lH, sw.d[2]);
                            const lMesh = new THREE.Mesh(lGeo, wMat);
                            lMesh.position.y = (hUnit/2) - (lH/2);
                            wallGrp.add(lMesh);

                            const sW = (sw.d[0] - dW) / 2;
                            if(sW > 0) {
                                const sGeo = new THREE.BoxGeometry(sW, dH, sw.d[2]);
                                const left = new THREE.Mesh(sGeo, wMat);
                                left.position.set(-sw.d[0]/2 + sW/2, -lH/2, 0);
                                wallGrp.add(left);
                                const right = new THREE.Mesh(sGeo, wMat);
                                right.position.set(sw.d[0]/2 - sW/2, -lH/2, 0);
                                wallGrp.add(right);
                            }
                        } else {
                            const wGeo = new THREE.BoxGeometry(sw.d[0], hUnit, sw.d[2]);
                            const wMesh = new THREE.Mesh(wGeo, wMat);
                            wallGrp.add(wMesh);
                        }
                        houseGroup.add(wallGrp);
                    });
                });
            }

            // Roof
            const roofY = formData.floors * hUnit;
            const rGeo = new THREE.BoxGeometry(pW + 0.5, 0.1, pL + 0.5);
            const rMat = new THREE.MeshStandardMaterial({ color: 0x1e293b, transparent: !isWalking, opacity: isWalking ? 1 : 0.2 });
            const roof = new THREE.Mesh(rGeo, rMat);
            roof.position.y = roofY;
            if(!isWalking) houseGroup.add(roof);
        }

        let controls;
        let isWalking = false;
        
        function switchView(mode) {
            const container = document.getElementById('viz-container');
            container.className = 'viz-panel mode-' + mode;
            
            document.querySelectorAll('.view-btn').forEach(btn => btn.classList.remove('active'));
            document.getElementById('btn-view-' + mode).classList.add('active');

            if(mode === 'walk') {
                enterWalkMode();
            } else {
                exitWalkMode();
            }
            
            // Re-render house to adjust ceiling transparency
            updateHouseVisualization();
        }

        function enterWalkMode() {
            if(!houseGroup) return;
            isWalking = true;
            document.getElementById('walk-instructions').style.display = 'block';
            orbitControls.enabled = false;
            controls = new THREE.PointerLockControls(houseCamera, document.body);
            houseCamera.position.set(0, 0.7, 2); 
            houseCamera.lookAt(0, 0.7, 0);
            document.addEventListener('click', onWalkClick);
            controls.addEventListener('lock', () => document.getElementById('walk-instructions').style.display = 'none');
            controls.addEventListener('unlock', () => {
                if(isWalking) document.getElementById('walk-instructions').style.display = 'block';
            });
        }

        function exitWalkMode() {
            isWalking = false;
            if(controls) controls.unlock();
            document.getElementById('walk-instructions').style.display = 'none';
            houseCamera.position.set(8, 6, 8);
            houseCamera.lookAt(0, 0, 0);
            if(orbitControls) orbitControls.enabled = true;
            document.removeEventListener('click', onWalkClick);
        }

        function onWalkClick() {
            if(isWalking && controls) controls.lock();
        }

        let time = 0;
        const velocity = new THREE.Vector3();
        const direction = new THREE.Vector3();
        let prevTime = performance.now();
        let moveForward = false;
        let moveBackward = false;
        let moveLeft = false;
        let moveRight = false;

        document.addEventListener('keydown', (event) => {
            switch (event.code) {
                case 'ArrowUp':
                case 'KeyW': moveForward = true; break;
                case 'ArrowLeft':
                case 'KeyA': moveLeft = true; break;
                case 'ArrowDown':
                case 'KeyS': moveBackward = true; break;
                case 'ArrowRight':
                case 'KeyD': moveRight = true; break;
                case 'Escape': 
                    if(isWalking) {
                        switchView('3d');
                    }
                    break;
            }
        });
        document.addEventListener('keyup', (event) => {
            switch (event.code) {
                case 'ArrowUp':
                case 'KeyW': moveForward = false; break;
                case 'ArrowLeft':
                case 'KeyA': moveLeft = false; break;
                case 'ArrowDown':
                case 'KeyS': moveBackward = false; break;
                case 'ArrowRight':
                case 'KeyD': moveRight = false; break;
            }
        });

        function animateHouse() {
            requestAnimationFrame(animateHouse);
            
            if (isWalking && controls && controls.isLocked) {
                const time = performance.now();
                const delta = (time - prevTime) / 1000;
                
                velocity.x -= velocity.x * 10.0 * delta;
                velocity.z -= velocity.z * 10.0 * delta;
                velocity.y -= 9.8 * 100.0 * delta; // Gravity

                direction.z = Number(moveForward) - Number(moveBackward);
                direction.x = Number(moveRight) - Number(moveLeft);
                direction.normalize();

                if (moveForward || moveBackward) velocity.z -= direction.z * 40.0 * delta; // Speed
                if (moveLeft || moveRight) velocity.x -= direction.x * 40.0 * delta;

                controls.moveRight(-velocity.x * delta);
                controls.moveForward(-velocity.z * delta);
                
                // Simple collision (Floor limit)
                if(houseCamera.position.y < 0.7) {
                     velocity.y = 0;
                     houseCamera.position.y = 0.7;
                }

                prevTime = time;
                houseRenderer.render(houseScene, houseCamera);
            } else {
                // Orbit/Idle Animation
                time += 0.01;
                if(orbitControls) orbitControls.update();
                
                if(!isWalking) {
                   // houseGroup.rotation.y += 0.003; // Handled by orbitControls.autoRotate
                   houseGroup.position.y = Math.sin(time) * 0.1 - 0.1; // Float
                }
                houseRenderer.render(houseScene, houseCamera);
            }
        }

        updateCalculations();
        updateConfidence();
        function hasStructuralChange() {
            return formData.length !== previousStructure.length ||
                   formData.width !== previousStructure.width ||
                   formData.floors !== previousStructure.floors ||
                   formData.bedrooms !== previousStructure.bedrooms ||
                   formData.bathrooms !== previousStructure.bathrooms ||
                   JSON.stringify(formData.spaces) !== JSON.stringify(previousStructure.spaces);
        }

        function updatePreviousStructure() {
            previousStructure = {
                length: formData.length,
                width: formData.width,
                floors: formData.floors,
                bedrooms: formData.bedrooms,
                bathrooms: formData.bathrooms,
                spaces: [...formData.spaces]
            };
        }

        // === FORM INTERACTIONS ===
        function selectFacing(el, value) {
            document.querySelectorAll('#step-1 .selection-card').forEach(c => {
                if(c.parentElement === el.parentElement) c.classList.remove('selected');
            });
            el.classList.add('selected');
            formData.facing = value;
            updateSummary();
            // Facing is NOT a structural change - no plan update
        }

        function updateRoadWidth() {
            const val = document.getElementById('road-width').value;
            document.getElementById('road-width-val').innerText = val;
            formData.roadWidth = val;
            // Road width is NOT a structural change - no plan update
        }

        function toggleCorner(el, value) {
            el.parentElement.querySelectorAll('.selection-card').forEach(c => c.classList.remove('selected'));
            el.classList.add('selected');
            formData.cornerPlot = value;
            document.getElementById('corner-hint').classList.toggle('show', value);
            updateConfidence();
            // Corner plot is NOT a structural change - no plan update
        }

        function selectFloors(el, value) {
            el.parentElement.querySelectorAll('.selection-card').forEach(c => c.classList.remove('selected'));
            el.classList.add('selected');
            formData.floors = value;
            updateCalculations();
            updateHouseVisualization();
            // Floors IS a structural change
            if (hasStructuralChange()) {
                drawFloorPlan();
                updatePreviousStructure();
            }
        }

        function adjustCount(type, delta) {
            const el = document.getElementById('val-' + type);
            let val = parseInt(el.innerText) + delta;
            if(val < 0) val = 0;
            if(type === 'bedrooms' && val > 10) val = 10;
            if(type === 'bathrooms' && val > 8) val = 8;
            el.innerText = val;
            formData[type] = val;
            
            // Show bathroom hint
            if(type === 'bedrooms' || type === 'bathrooms') {
                const ratio = formData.bathrooms / formData.bedrooms;
                document.getElementById('bathroom-hint').classList.toggle('show', ratio < 0.4);
            }
            
            updateCalculations();
            updateHouseVisualization();
            // Room count IS a structural change
            if (hasStructuralChange()) {
                drawFloorPlan();
                updatePreviousStructure();
            }
        }

        function toggleSpace(el, space) {
            el.classList.toggle('selected');
            const idx = formData.spaces.indexOf(space);
            if(idx > -1) formData.spaces.splice(idx, 1);
            else formData.spaces.push(space);
            updateCalculations();
            // Spaces IS a structural change
            if (hasStructuralChange()) {
                drawFloorPlan();
                updatePreviousStructure();
            }
        }

        function updateBudget() {
            const val = document.getElementById('budget-slider').value;
            document.getElementById('budget-val').innerText = val;
            formData.budget = parseInt(val);
            updateCalculations();
            updateHouseVisualization();
            // Budget is NOT a structural change - no plan update
        }

        function selectPriority(el, value) {
            el.parentElement.querySelectorAll('.selection-card').forEach(c => c.classList.remove('selected'));
            el.classList.add('selected');
            formData.priority = value;
            // Priority is NOT a structural change - no plan update
        }

        function selectParking(el, value) {
            el.parentElement.querySelectorAll('.selection-card').forEach(c => c.classList.remove('selected'));
            el.classList.add('selected');
            formData.parking = value;
            updateHouseVisualization();
            // Parking is NOT a structural change - no plan update
        }

        function selectStyle(el, value) {
            el.parentElement.querySelectorAll('.selection-card').forEach(c => c.classList.remove('selected'));
            el.classList.add('selected');
            formData.style = value;
            updateHouseVisualization();
            // Style is NOT a structural change - no plan update
        }

        function toggleVaastu(el, value) {
            el.parentElement.querySelectorAll('.selection-card').forEach(c => c.classList.remove('selected'));
            el.classList.add('selected');
            formData.vaastu = value;
            // Vaastu is NOT a structural change - no plan update
        }

        // === CALCULATIONS ===
        function updateCalculations() {
            // Read plot dimensions from inputs
            const lengthInput = document.getElementById('inp-length');
            const widthInput = document.getElementById('inp-width');
            
            if (lengthInput) formData.length = parseFloat(lengthInput.value) || 50;
            if (widthInput) formData.width = parseFloat(widthInput.value) || 30;
            
            // DYNAMIC Plot area calculation (NOT FIXED!)
            const plotArea = formData.length * formData.width;
            document.getElementById('plot-area').innerText = plotArea;
            
            // Highlight updated value
            const plotSummary = document.querySelector('#sum-plot').parentElement;
            plotSummary.classList.add('updated');
            setTimeout(() => plotSummary.classList.remove('updated'), 1000);
            
            document.getElementById('sum-plot').innerText = plotArea + ' sq.ft';
            
            // Built-up area (70% of plot for single floor, 50% for multi-floor)
            const coverage = formData.floors === 1 ? 0.7 : 0.5;
            const baseArea = plotArea * coverage;
            const roomArea = (formData.bedrooms * 150) + (formData.bathrooms * 50) + (formData.spaces.length * 100);
            const builtupArea = Math.min(baseArea * formData.floors, roomArea * formData.floors);
            
            document.getElementById('builtup-area').innerText = Math.round(builtupArea);
            
            // Highlight updated value
            const builtupSummary = document.querySelector('#sum-builtup').parentElement;
            builtupSummary.classList.add('updated');
            setTimeout(() => builtupSummary.classList.remove('updated'), 1000);
            
            document.getElementById('sum-builtup').innerText = Math.round(builtupArea) + ' sq.ft';
            
            // Cost per sqft
            const costPerSqft = Math.round((formData.budget * 100000) / builtupArea);
            document.getElementById('cost-per-sqft').innerText = costPerSqft;
            
            updateSummary();
            updateConfidence();
            
            // Plot dimensions ARE structural changes
            if (hasStructuralChange()) {
                generateHouseLayout(); // Gen Unified Data
                drawFloorPlan();
                updateHouseVisualization(); // Sync 3D
                updatePreviousStructure();
            }
        }

        function updateSummary() {
            document.getElementById('sum-floors').innerText = formData.floors;
            document.getElementById('sum-bedrooms').innerText = formData.bedrooms;
            document.getElementById('sum-budget').innerText = '₹' + formData.budget + 'L';
        }

        function updateConfidence() {
            let score = 40;
            
            // Good plot size
            const plotArea = formData.length * formData.width;
            if(plotArea >= 1000) score += 15;
            
            // Adequate rooms
            if(formData.bedrooms >= 2 && formData.bedrooms <= 4) score += 10;
            if(formData.bathrooms >= formData.bedrooms * 0.5) score += 10;
            
            // Budget adequacy
            const builtupArea = parseInt(document.getElementById('builtup-area').innerText);
            const costPerSqft = (formData.budget * 100000) / builtupArea;
            if(costPerSqft >= 1500 && costPerSqft <= 3000) score += 15;
            
            // Corner plot bonus
            if(formData.cornerPlot) score += 10;
            
            const fill = document.getElementById('confidence-fill');
            const text = document.getElementById('confidence-text');
            
            fill.style.width = score + '%';
            
            if(score < 50) {
                text.innerText = 'BASIC';
                fill.style.background = '#f59e0b';
            } else if(score < 75) {
                text.innerText = 'GOOD';
                fill.style.background = 'linear-gradient(90deg, #f59e0b, #10b981)';
            } else {
                text.innerText = 'OPTIMIZED';
                fill.style.background = '#10b981';
            }
        }

        // === WIZARD NAVIGATION ===
        function changeStep(dir) {
            const next = currentStep + dir;
            if(next > totalSteps) {
                generatePlan();
                return;
            }
            if(next < 1) return;

            document.getElementById('step-' + currentStep).classList.remove('active');
            document.getElementById('step-' + next).classList.add('active');
            
            const btnBack = document.getElementById('btn-back');
            const btnNext = document.getElementById('btn-next');
            
            btnBack.style.opacity = next === 1 ? '0.5' : '1';
            
            if(next === totalSteps) {
                btnNext.innerHTML = 'Generate Plan <i class="fas fa-magic"></i>';
                btnNext.style.background = 'var(--accent)';
                updateReview();
            } else {
                btnNext.innerHTML = 'Next Step <i class="fas fa-arrow-right"></i>';
                btnNext.style.background = 'var(--primary)';
            }
            
            for(let i = 1; i <= totalSteps; i++) {
                const fill = document.getElementById('p' + i);
                fill.style.width = i <= next ? '100%' : '0%';
            }

            document.getElementById('step-count').innerText = `${next}/${totalSteps}`;
            const titles = ["PLOT DETAILS", "FLOOR & ROOMS", "BUDGET & PRIORITY", "DESIGN PREFERENCES", "REVIEW"];
            document.getElementById('step-label').innerText = next + ". " + titles[next-1];

            currentStep = next;
        }

        function updateReview() {
            document.getElementById('rev-dims').innerText = formData.length + ' x ' + formData.width + ' ft';
            document.getElementById('rev-plot-area').innerText = (formData.length * formData.width) + ' sq.ft';
            document.getElementById('rev-facing').innerText = formData.facing;
            document.getElementById('rev-corner').innerText = formData.cornerPlot ? 'Yes' : 'No';
            document.getElementById('rev-floors').innerText = formData.floors;
            document.getElementById('rev-bedrooms').innerText = formData.bedrooms;
            document.getElementById('rev-bathrooms').innerText = formData.bathrooms;
            document.getElementById('rev-builtup').innerText = document.getElementById('builtup-area').innerText + ' sq.ft';
            document.getElementById('rev-budget').innerText = '₹' + formData.budget + ' Lakhs';
            document.getElementById('rev-priority').innerText = formData.priority === 'space' ? 'Maximize Space' : 'Better Quality';
            document.getElementById('rev-style').innerText = formData.style === 'modern' ? 'Modern' : 'Traditional';
            document.getElementById('rev-parking').innerText = formData.parking === 0 ? 'None' : formData.parking + ' Car(s)';
        }

        function generatePlan() {
            const gen = document.getElementById('generating');
            gen.classList.add('active');
            
            // Re-generate layout to final quality
            generateHouseLayout();
            updateCalculations();
            
            setTimeout(() => {
                gen.classList.remove('active');
                document.getElementById('app-container').classList.add('generated-state');
                
                // Centering fix: Update camera and renderer for 100vw
                houseCamera.aspect = window.innerWidth / (window.innerHeight - 72);
                houseCamera.updateProjectionMatrix();
                houseRenderer.setSize(window.innerWidth, window.innerHeight - 72);
                
                switchView('3d');
                document.getElementById('main-switcher').classList.add('visible');
            }, 2500);
        }

        function exitGeneratedState() {
            document.getElementById('app-container').classList.remove('generated-state');
            document.getElementById('main-switcher').classList.remove('visible');
            setTimeout(() => {
                houseCamera.aspect = (window.innerWidth - 480) / (window.innerHeight - 72);
                houseCamera.updateProjectionMatrix();
                houseRenderer.setSize(window.innerWidth - 480, window.innerHeight - 72);
            }, 100);
        }

        function downloadPlan() {
            // Since 2D mode is removed, download the floor plan from the mini-map
            exportPlan('png');
        }

        function exportPlan(format) {
            const canvas = document.getElementById('floor-plan-canvas');
            const link = document.createElement('a');
            
            if (format === 'png') {
                link.download = 'Constructa_Floor_Plan.png';
                link.href = canvas.toDataURL('image/png', 1.0);
            } else if (format === 'jpg') {
                // Need a temporary white background for JPG
                const tempCanvas = document.createElement('canvas');
                tempCanvas.width = canvas.width;
                tempCanvas.height = canvas.height;
                const tempCtx = tempCanvas.getContext('2d');
                tempCtx.fillStyle = '#ffffff';
                tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
                tempCtx.drawImage(canvas, 0, 0);
                
                link.download = 'Constructa_Floor_Plan.jpg';
                link.href = tempCanvas.toDataURL('image/jpeg', 0.9);
            }
            
            link.click();
        }



        function proceedToBudget() {
            window.location.href = 'budget_calculator.php';
        }

        // Consolidate Global Initialization
        generateHouseLayout();
        init2DFloorPlan();
        init3DHouse();
        updateCalculations();
        updateConfidence();
    </script>
</body>
</html>
