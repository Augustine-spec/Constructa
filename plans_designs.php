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
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://unpkg.com/three@0.128.0/examples/js/controls/PointerLockControls.js"></script>
    <script src="https://unpkg.com/three@0.128.0/examples/js/controls/OrbitControls.js"></script>
    <script src="https://unpkg.com/three@0.128.0/examples/js/loaders/GLTFLoader.js"></script>

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

        /* Fixed Navigation Buttons */
        .nav-fixed-container {
            position: fixed;
            top: 2rem;
            right: 2rem;
            z-index: 1000;
            display: flex;
            gap: 1rem;
        }

        .top-nav-btn {
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
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .top-nav-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }
        
        /* Floating Brand */
        .brand-floating {
            position: fixed;
            top: 2rem;
            left: 2rem;
            z-index: 1000;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.8);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            backdrop-filter: blur(5px);
        }

        /* Main Layout */
        #app-container {
            position: relative; 
            z-index: 10;
            width: 100%; 
            height: 100vh;
            top: 0;
            display: flex;
            padding-top: 6rem; /* Push content down slightly or handle with internal padding */
        }

        /* Left Panel */
        .input-panel {
            width: 480px;
            background: var(--surface);
            backdrop-filter: var(--surface-blur);
            height: calc(100vh - 6rem); /* Subtract top padding */
            border-right: 1px solid var(--border);
            display: flex; 
            flex-direction: column;
            box-shadow: 10px 0 40px rgba(0,0,0,0.05);
            transition: width 0.3s ease;
            border-top-right-radius: 12px; /* Visual refinement */
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
        
        /* Export Buttons */
        .btn-export {
            background: rgba(41, 64, 51, 0.8);
            color: white;
            border: none;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.7rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .btn-export:hover {
            background: var(--primary);
            transform: translateY(-1px);
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
        
        /* Walk Mode Enhancements */
        .mode-walk .floor-plan-panel {
            width: 200px;
            height: 200px;
            bottom: 20px;
            right: 20px;
            left: auto;
            opacity: 0.95;
        }
        
        .mode-walk .live-summary {
            display: none;
        }
        
        /* Walk Mode HUD */
        .walk-hud {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 200;
            display: none;
        }
        
        .mode-walk .walk-hud {
            display: block;
        }
        
        /* Crosshair */
        .walk-crosshair {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            opacity: 0.3;
        }
        
        .walk-crosshair::before,
        .walk-crosshair::after {
            content: '';
            position: absolute;
            background: white;
        }
        
        .walk-crosshair::before {
            width: 2px;
            height: 100%;
            left: 50%;
            transform: translateX(-50%);
        }
        
        .walk-crosshair::after {
            width: 100%;
            height: 2px;
            top: 50%;
            transform: translateY(-50%);
        }
        
        /* Walk Controls Panel - REMOVED */
        .walk-controls {
            display: none !important;
        }
        
        /* Walk Status Bar - REMOVED */
        .walk-status {
            display: none !important;
        }
        
        /* Position Indicator */
        .position-indicator {
            position: absolute;
            top: 20px;
            right: 240px;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 8px;
            padding: 10px 16px;
            color: white;
            font-size: 0.75rem;
            font-family: 'Courier New', monospace;
            pointer-events: none;
        }
        
        /* Room Label */
        .room-label {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, calc(-50% - 100px));
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 8px;
            padding: 12px 24px;
            color: white;
            font-family: 'Space Grotesk';
            font-size: 1.2rem;
            font-weight: 700;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .room-label.visible {
            opacity: 1;
        }
        
        /* Toggle Controls Button - REMOVED */
        .toggle-controls-btn {
            display: none !important;
        }
        

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
        
        /* Customization Panel */
        #customization-panel {
            position: absolute;
            top: 100px;
            left: 20px; 
            width: 320px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            border-radius: 12px;
            border: 1px solid rgba(0,0,0,0.1);
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            padding: 24px;
            z-index: 2100; /* Above stepper */
            display: none; 
            max-height: 80vh;
            overflow-y: auto;
        }
        
        #customization-panel.active {
            display: block;
            animation: slideInLeft 0.3s ease;
        }
        @keyframes slideInLeft { from { opacity:0; transform:translateX(-20px); } to { opacity:1; transform:translateX(0); } }

        .cust-section {
            margin-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 16px;
        }
        .cust-section:last-child { border-bottom: none; }
        
        .cust-title {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--primary);
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .color-palette {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .color-swatch {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .color-swatch:hover { transform: scale(1.1); }
        .color-swatch.active { border-color: var(--accent); transform: scale(1.1); box-shadow: 0 0 0 2px var(--accent); }

        .cust-select {
            width: 100%;
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            background: white;
            font-size: 0.9rem;
            color: var(--text-main);
            margin-bottom: 8px;
        }

        .toggle-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            font-size: 0.9rem;
            color: var(--text-main);
            font-weight: 500;
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

    <a href="homeowner.php" class="brand-floating">
        <i class="far fa-building"></i> Constructa AI
    </a>

    <div class="nav-fixed-container">
        <a href="homeowner.php" class="top-nav-btn">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <a href="material_market.php" class="top-nav-btn">
            <i class="fas fa-shopping-cart"></i> Market
        </a>
        <a href="login.html" class="top-nav-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>

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
                                <input type="text" inputmode="decimal" class="c-input" id="inp-length" value="50" oninput="updateCalculations()" onkeypress="return (event.charCode >= 48 && event.charCode <= 57) || event.charCode == 46">
                                <div class="helper-text">Front to back measurement</div>
                            </div>
                            <div>
                                <label>Plot Width (ft)</label>
                                <input type="text" inputmode="decimal" class="c-input" id="inp-width" value="30" oninput="updateCalculations()" onkeypress="return (event.charCode >= 48 && event.charCode <= 57) || event.charCode == 46">
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
            <!-- Top Right Actions Removed as per request -->
            <a href="homeowner.php" class="brand-floating">
        <i class="far fa-building"></i> Constructa AI
    </a>

    <div class="nav-fixed-container">
        <a href="explore_designs.php" class="top-nav-btn" style="background:var(--accent); color:white; border-color:var(--accent);">
            <i class="fas fa-compass"></i> Explore
        </a>
        <a href="homeowner.php" class="top-nav-btn">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <a href="material_market.php" class="top-nav-btn">
            <i class="fas fa-shopping-cart"></i> Market
        </a>
        <a href="login.html" class="top-nav-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
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
                
                <div style="margin-top: 16px;">
                    <button class="btn-solid" onclick="toggleCustomization()" style="width:100%; justify-content:center;">
                        <i class="fas fa-palette"></i> Customize Design
                    </button>
                </div>
            </div>

            <!-- Customization Panel -->
            <div id="customization-panel">
                 <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
                    <h3 style="font-family:'Space Grotesk'; font-size:1.2rem; color:var(--primary);">Customize Design</h3>
                    <button onclick="toggleCustomization()" style="background:none; border:none; cursor:pointer; font-size:1.2rem; color:var(--text-muted);"><i class="fas fa-times"></i></button>
                 </div>

                 <div class="cust-section">
                    <div class="cust-title"><i class="fas fa-fill-drip"></i> Exterior Walls</div>
                    <div class="color-palette" id="wall-palette">
                        <div class="color-swatch active" style="background:#ffffff" onclick="setWallColor(0xffffff, this)" title="White"></div>
                        <div class="color-swatch" style="background:#f3f4f6" onclick="setWallColor(0xf3f4f6, this)" title="Light Grey"></div>
                        <div class="color-swatch" style="background:#fee2e2" onclick="setWallColor(0xfee2e2, this)" title="Soft Rose"></div>
                        <div class="color-swatch" style="background:#fef3c7" onclick="setWallColor(0xfef3c7, this)" title="Cream"></div>
                        <div class="color-swatch" style="background:#ffedd5" onclick="setWallColor(0xffedd5, this)" title="Peach"></div>
                        <div class="color-swatch" style="background:#e0f2fe" onclick="setWallColor(0xe0f2fe, this)" title="Sky Blue"></div>
                        <div class="color-swatch" style="background:#dcfce7" onclick="setWallColor(0xdcfce7, this)" title="Mint"></div>
                        <div class="color-swatch" style="background:#334155" onclick="setWallColor(0x334155, this)" title="Slate"></div>
                    </div>
                 </div>

                 <div class="cust-section">
                    <div class="cust-title"><i class="fas fa-home"></i> Roof Style</div>
                    <select class="cust-select" onchange="setRoofMaterial(this.value)">
                        <option value="default">Default Style</option>
                        <option value="tiles">Terracotta Tiles</option>
                        <option value="concrete">Concrete Finish</option>
                        <option value="metal">Dark Metal Seam</option>
                        <option value="slate">Blue Slate</option>
                    </select>
                 </div>

                 <div class="cust-section">
                    <div class="cust-title"><i class="fas fa-layer-group"></i> Components</div>
                    <div class="toggle-row">
                        <span><i class="fas fa-border-all"></i> Floors</span>
                         <input type="checkbox" checked onchange="toggleComponent('floor', this.checked)">
                    </div>
                    <div class="toggle-row">
                        <span><i class="fas fa-home"></i> Roof</span>
                         <input type="checkbox" checked onchange="toggleComponent('roof', this.checked)">
                    </div>
                    <div class="toggle-row">
                        <span><i class="fas fa-window-maximize"></i> Windows</span>
                         <input type="checkbox" checked onchange="toggleComponent('window', this.checked)">
                    </div>
                    <div class="toggle-row">
                        <span><i class="fas fa-door-open"></i> Doors</span>
                         <input type="checkbox" checked onchange="toggleComponent('door', this.checked)">
                    </div>
                     <div class="toggle-row">
                        <span><i class="fas fa-couch"></i> Furniture</span>
                         <input type="checkbox" checked onchange="toggleComponent('furniture', this.checked)">
                    </div>
                 </div>

                 <div class="cust-section">
                     <div class="cust-title"><i class="fas fa-sun"></i> Lighting Intensity</div>
                     <input type="range" min="0" max="2" step="0.1" value="0.8" style="width:100%" oninput="setLightIntensity(this.value)">
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
            
            <!-- Walk Mode HUD -->
            <div class="walk-hud">
                <!-- Crosshair -->
                <div class="walk-crosshair"></div>
                
                <!-- Room Label -->
                <div class="room-label" id="room-label">Living Room</div>
                
                <!-- Position Indicator -->
                <div class="position-indicator" id="position-indicator">
                    X: 0.0 | Y: 0.7 | Z: 0.0
                </div>
                

                

                

            </div>
            
            <div id="walk-instructions" style="
                display: none;
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                color: #1e293b;
                font-family: 'Space Grotesk';
                font-size: 1.5rem;
                text-align: center;
                text-shadow: none;
                pointer-events: none;
                z-index: 300;
                background: rgba(255, 255, 255, 0.95);
                padding: 40px 60px;
                border-radius: 16px;
                backdrop-filter: blur(10px);
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            ">
                <div style="margin-bottom: 20px; font-size: 2rem; color: #294033;">
                    <i class="fas fa-walking"></i> First-Person Walkthrough
                </div>
                <div style="font-size: 1.1rem; opacity: 0.8; margin-bottom: 15px; color: #1e293b;">
                    Click anywhere to start exploring
                </div>
                <div style="font-size: 0.85rem; opacity: 0.7; line-height: 1.6; color: #475569;">
                    Use <strong style="color: #294033;">WASD</strong> or <strong style="color: #294033;">Arrow Keys</strong> to move<br>
                    <strong style="color: #294033;">Mouse</strong> to look around • <strong style="color: #294033;">SHIFT</strong> to sprint<br>
                    Press <strong style="color: #294033;">ESC</strong> to exit walk mode
                </div>
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

        // === TEXTURE GENERATION (Procedural PBR) ===
        const textureCache = {};
        
        function getProceduralTexture(type) {
            if (textureCache[type]) return textureCache[type];
            
            const canvas = document.createElement('canvas');
            canvas.width = 512;
            canvas.height = 512;
            const ctx = canvas.getContext('2d');
            
            if (type === 'wood') {
                ctx.fillStyle = '#8b5a2b';
                ctx.fillRect(0,0,512,512);
                // Grain
                ctx.globalAlpha = 0.1;
                ctx.fillStyle = '#3e2723';
                for(let i=0; i<1000; i++) {
                    const w = Math.random() * 512;
                    const h = Math.random() * 2 + 1;
                    const x = Math.random() * 512;
                    const y = Math.random() * 512;
                    ctx.fillRect(x, y, w, h);
                }
            } else if (type === 'fabric') {
                ctx.fillStyle = '#e2e8f0'; // Base grey/white
                ctx.fillRect(0,0,512,512);
                ctx.globalAlpha = 0.05;
                ctx.fillStyle = '#000';
                // Weave pattern
                for(let i=0; i<512; i+=4) {
                    ctx.fillRect(i, 0, 1, 512);
                    ctx.fillRect(0, i, 512, 1);
                }
                // Noise
                for(let i=0; i<5000; i++) {
                    ctx.fillRect(Math.random()*512, Math.random()*512, 2, 2);
                }
            } else if (type === 'rug') {
                // Persian/Patterned Rug style
                ctx.fillStyle = '#7f1d1d'; // Red base
                ctx.fillRect(0,0,512,512);
                ctx.strokeStyle = '#fef3c7';
                ctx.lineWidth = 2;
                ctx.globalAlpha = 0.8;
                ctx.beginPath();
                // Border
                ctx.rect(20,20,472,472);
                ctx.stroke();
                // Center detail
                ctx.beginPath();
                ctx.arc(256, 256, 100, 0, Math.PI*2);
                ctx.stroke();
            }

            const tex = new THREE.CanvasTexture(canvas);
            tex.wrapS = THREE.RepeatWrapping;
            tex.wrapT = THREE.RepeatWrapping;
            textureCache[type] = tex;
            return tex;
        }

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

            // --- BACK ZONE (Bedrooms + Attached Baths) ---
            const brCount = Math.max(1, formData.bedrooms);
            const baCount = formData.bathrooms;
            
            // Calculate slot width for each bedroom suite
            const slotW = buildW / brCount;
            
            for(let i=0; i<brCount; i++) {
                const hasBath = (i < baCount); // Attach bath if available
                
                // Split slot: 70% Bedroom, 30% Bathroom (User Requested)
                const rBedW = hasBath ? slotW * 0.70 : slotW;
                const rBathW = hasBath ? (slotW - rBedW) : 0;
                const baseX = margin + (i * slotW);
                
                // Bedroom Furniture
                let brFurniture = [
                    {type: 'bed', x: 0.35, y: 0.15, w: 6, l: 7}, // Shifted x slightly for narrower room
                    {type: 'wardrobe', x: 0.05, y: 0.8, w: 3.5, l: 2} 
                ];
                
                // Master Bedroom Extras
                if (i === 0) {
                     brFurniture.push({type: 'desk', x: 0.60, y: 0.8, w: 4, l: 2.5});
                     brFurniture.push({type: 'chair', x: 0.55, y: 0.7, w: 1.5, l: 1.5});
                     brFurniture.push({type: 'avatar', x: 0.55, y: 0.7, w: 1, l: 1});
                     // Adjust bed for master
                     brFurniture[0].w = 7; // King size
                     brFurniture[0].x = 0.30; // Centered relative to available walking space
                }

                // Doors
                const brDoors = [ { wall: 'South', pos: 0.5 } ]; // Exit to home
                if(hasBath) brDoors.push({ wall: 'East', pos: 0.8 }); // Entrance to Bath

                // Push Bedroom
                rooms.push({
                    type: 'bedroom', 
                    name: `Bedroom ${i+1}`, 
                    x: baseX, 
                    y: currentY, 
                    width: rBedW, 
                    height: backDepth,
                    color: i===0 ? '#d8b4fe' : '#dbbefe',
                    furniture: brFurniture,
                    doors: brDoors
                });

                // Push Attached Bathroom
                if(hasBath) {
                    rooms.push({
                         type: 'bathroom',
                         name: `Bath ${i+1}`,
                         x: baseX + rBedW,
                         y: currentY,
                         width: rBathW,
                         height: backDepth,
                         color: '#ccfbf1',
                         furniture: [
                            {type: 'shower', x: 0.5, y: 0.1, w: 2.5, l: 2.5}, // Slightly smaller to fit 30% width
                            {type: 'toilet', x: 0.5, y: 0.5, w: 2, l: 2},
                            {type: 'mirror', x: 0.05, y: 0.2, w: 0.1, l: 2}
                        ],
                        doors: [{ wall: 'West', pos: 0.8 }] // Connect from Bedroom
                    });
                }
            }
            currentY += backDepth;

            // --- MIDDLE ZONE ---
            const midRoomsList = [];
            if(formData.spaces.includes('kitchen')) midRoomsList.push({type: 'kitchen', name: 'Kitchen', color: '#fef3c7', furniture: [
                {type: 'counter', x: 0.05, y: 0.05, w: 10, l: 2},
                {type: 'fridge', x: 0.85, y: 0.05, w: 3, l: 3},
                {type: 'wall_shelf', x: 0.2, y: 0.02, w: 4, l: 1, hOffset: 5} 
            ]});
            if(formData.spaces.includes('dining')) midRoomsList.push({type: 'dining', name: 'Dining', color: '#fef9c3', furniture: [
                {type: 'table', x: 0.5, y: 0.5, w: 6, l: 4}
            ]});
            if(formData.spaces.includes('balcony')) midRoomsList.push({type: 'balcony', name: 'Balcony', color: '#ffedd5', furniture: [
                {type: 'plant', x: 0.1, y: 0.1, w: 1, l: 1},
                {type: 'plant', x: 0.9, y: 0.1, w: 1, l: 1},
                {type: 'chair', x: 0.5, y: 0.5, w: 1.5, l: 1.5}
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
                        {type: 'tv', x: 0.5, y: 0.05, w: 6, l: 1.5},
                        {type: 'table', x: 0.5, y: 0.5, w: 3, l: 2}, 
                        {type: 'rug', x: 0.5, y: 0.55, w: 10, l: 6},
                        {type: 'plant', x: 0.9, y: 0.9, w: 1, l: 1},
                        {type: 'lamp', x: 0.1, y: 0.9, w: 1, l: 1, hOffset: 0},
                        {type: 'wallpaper_frame', x: 0.5, y: 0.98, w: 2, l: 2, hOffset: 4} // Wall Art on back wall
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
            // 1. Hemisphere Light for soft global illumination (Daylight)
            const hemiLight = new THREE.HemisphereLight(0xffffff, 0xffffff, 0.6);
            hemiLight.position.set(0, 50, 0);
            houseScene.add(hemiLight);

            // 2. Main Sun Directional Light
            const dirLight = new THREE.DirectionalLight(0xffffff, 1.2);
            dirLight.position.set(-10, 20, 10);
            dirLight.castShadow = true;
            dirLight.shadow.mapSize.width = 2048;
            dirLight.shadow.mapSize.height = 2048;
            dirLight.shadow.camera.near = 0.5;
            dirLight.shadow.camera.far = 100;
            // Adjust shadow camera frustum to cover house
            dirLight.shadow.camera.left = -30;
            dirLight.shadow.camera.right = 30;
            dirLight.shadow.camera.top = 30;
            dirLight.shadow.camera.bottom = -30;
            dirLight.shadow.bias = -0.0005; // Fix shadow acne
            houseScene.add(dirLight);

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
            group.name = 'furniture'; 
            
            // Common textures
            const woodTex = getProceduralTexture('wood');
            const fabricTex = getProceduralTexture('fabric');
            
            const woodMat = new THREE.MeshStandardMaterial({
                color: 0x5d4037, map: woodTex, roughness: 0.4, metalness: 0
            });
            const fabricMat = new THREE.MeshStandardMaterial({
                color: 0xe2e8f0, map: fabricTex, roughness: 0.8, metalness: 0
            });
            const lampMat = new THREE.MeshStandardMaterial({
                color: 0xffe0b2, emissive: 0xffe0b2, emissiveIntensity: 0.5
            });

            // Enabling shadows on all children
            const castReceive = (grp) => {
                grp.traverse(o => { if(o.isMesh) { o.castShadow = true; o.receiveShadow = true; } });
            };

            if (type === 'bed') {
                // Base
                const base = new THREE.Mesh(new THREE.BoxGeometry(w, h * 0.4, l), woodMat);
                group.add(base);
                // Mattress (realistic white fabric)
                const matrs = new THREE.Mesh(new THREE.BoxGeometry(w * 0.95, h * 0.3, l * 0.95), new THREE.MeshStandardMaterial({color: 0xffffff, roughness: 0.9}));
                matrs.position.y = h * 0.35;
                group.add(matrs);
                // Blanket (Messy look implemented via slightly uneven geometry or just colored overlay for now)
                const blanket = new THREE.Mesh(new THREE.BoxGeometry(w * 0.96, h * 0.1, l * 0.6), new THREE.MeshStandardMaterial({color: 0x3b82f6, roughness: 1.0})); // Blue blanket
                blanket.position.set(0, h * 0.45, l * 0.2);
                group.add(blanket);
                
                // Pillows
                const pGeo = new THREE.CylinderGeometry(0.3, 0.3, w*0.35, 16); // Cylinder shapes for pillows
                const p1 = new THREE.Mesh(pGeo, new THREE.MeshStandardMaterial({color: 0xffffff}));
                p1.rotation.z = Math.PI/2;
                p1.position.set(-w * 0.2, h * 0.55, -l * 0.35);
                p1.scale.set(1, 0.6, 1); // Flatten
                group.add(p1);
                
                const p2 = p1.clone();
                p2.position.x = w * 0.2;
                group.add(p2);
                
            } else if (type === 'sofa') {
                // Photorealistic Sofa: L-shape style or modern straight
                const seatH = h * 0.4;
                const backH = h;
                const depth = l;
                
                // Seat
                const seat = new THREE.Mesh(new THREE.BoxGeometry(w, seatH, depth), fabricMat);
                group.add(seat);
                
                // Backrest
                const back = new THREE.Mesh(new THREE.BoxGeometry(w, h * 0.6, depth * 0.2), fabricMat);
                back.position.set(0, h * 0.5, -depth * 0.4);
                group.add(back);
                
                // Arms
                const armGeo = new THREE.BoxGeometry(w * 0.1, h * 0.6, depth);
                const leftArm = new THREE.Mesh(armGeo, fabricMat);
                leftArm.position.set(-w/2 + w*0.05, h*0.3, 0);
                group.add(leftArm);
                
                const rightArm = new THREE.Mesh(armGeo, fabricMat);
                rightArm.position.set(w/2 - w*0.05, h*0.3, 0);
                group.add(rightArm);
                
                // Cushions
                const cushGeo = new THREE.BoxGeometry(w * 0.25, h * 0.4, depth * 0.1);
                const c1 = new THREE.Mesh(cushGeo, new THREE.MeshStandardMaterial({color: 0x94a3b8, roughness: 0.9})); // Accent color
                c1.position.set(-w*0.2, h*0.5, -depth*0.25);
                c1.rotation.x = -0.2;
                group.add(c1);
                const c2 = c1.clone();
                c2.position.set(w*0.2, h*0.5, -depth*0.25);
                group.add(c2);

            } else if (type === 'rug') {
                const rugTex = getProceduralTexture('rug');
                const rug = new THREE.Mesh(new THREE.BoxGeometry(w, 0.05, l), new THREE.MeshStandardMaterial({
                    map: rugTex, roughness: 1.0
                }));
                group.add(rug);
            } else if (type === 'wallpaper_frame') {
                 // Wall Art
                 const frame = new THREE.Mesh(new THREE.BoxGeometry(w, h, 0.05), new THREE.MeshStandardMaterial({color: 0x111111}));
                 group.add(frame);
                 const art = new THREE.Mesh(new THREE.PlaneGeometry(w*0.9, h*0.9), new THREE.MeshBasicMaterial({color: 0xff6b6b}));
                 art.position.z = 0.03;
                 group.add(art);
            
            } else if (type === 'lamp') {
                // Table Lamp
                const base = new THREE.Mesh(new THREE.CylinderGeometry(0.15, 0.2, 0.1, 16), new THREE.MeshStandardMaterial({color: 0x333333}));
                group.add(base);
                const stem = new THREE.Mesh(new THREE.CylinderGeometry(0.02, 0.02, 0.6, 8), new THREE.MeshStandardMaterial({color: 0x666666}));
                stem.position.y = 0.3;
                group.add(stem);
                const shade = new THREE.Mesh(new THREE.ConeGeometry(0.25, 0.3, 32, 1, true), lampMat);
                shade.position.y = 0.5;
                group.add(shade);
                
                // Actual Light
                const light = new THREE.PointLight(0xffaa00, 1, 5); // Warm light
                light.position.set(0, 0.4, 0);
                light.castShadow = true;
                group.add(light);
                
            } else if (type === 'desk' || type === 'counter' || type === 'table' || type === 'wall_shelf') {
                 const isShelf = type === 'wall_shelf';
                 const topThick = isShelf ? 0.05 : 0.1;
                 // Top
                const top = new THREE.Mesh(new THREE.BoxGeometry(w, topThick, l), woodMat);
                top.position.y = h;
                group.add(top);
                
                // Add Books (Random colors)
                const bookCols = [0xd32f2f, 0x1976d2, 0x388e3c, 0xfbc02d];
                if(isShelf || type === 'desk') {
                    for(let b=0; b<3; b++) {
                        const bk = new THREE.Mesh(new THREE.BoxGeometry(0.1, 0.3 + Math.random()*0.1, 0.2), new THREE.MeshStandardMaterial({color: bookCols[b%4]}));
                        bk.position.set(-w*0.3 + (b*0.12), h + 0.15, 0);
                        group.add(bk);
                    }
                }
                
                if(!isShelf) {
                    // Legs
                    const lGeo = new THREE.BoxGeometry(0.1, h, 0.1);
                    const l1 = new THREE.Mesh(lGeo, woodMat); l1.position.set(-w/2.2, h/2, -l/2.2); group.add(l1);
                    const l2 = l1.clone(); l2.position.x = w/2.2; group.add(l2);
                    const l3 = l1.clone(); l3.position.z = l/2.2; group.add(l3);
                    const l4 = l3.clone(); l4.position.x = w/2.2; group.add(l4);
                }
                
                if (type === 'desk') {
                    // Monitor
                    const mon = new THREE.Mesh(new THREE.BoxGeometry(w * 0.4, h * 0.6, 0.05), new THREE.MeshStandardMaterial({color: 0x111111}));
                    mon.position.set(0, h + h * 0.35, -l * 0.3);
                    group.add(mon);
                    // Glow
                    const screen = new THREE.Mesh(new THREE.PlaneGeometry(w * 0.35, h * 0.5), new THREE.MeshBasicMaterial({color: 0x60a5fa})); 
                    screen.position.set(0, h + h * 0.35, -l * 0.3 + 0.03);
                    group.add(screen);
                }
            } else if (type === 'tv') {
                 // TV Unit Cabinet
                 const cabinet = new THREE.Mesh(new THREE.BoxGeometry(w, h*0.5, l), woodMat);
                 cabinet.position.y = h*0.25;
                 group.add(cabinet);
                 
                 // TV Screen
                 const tvW = w * 0.8;
                 const tvH = w * 0.45; // 16:9 ish
                 const screen = new THREE.Mesh(new THREE.BoxGeometry(tvW, tvH, 0.05), new THREE.MeshStandardMaterial({color: 0x111111, roughness: 0.1, metalness: 0.8}));
                 screen.position.set(0, h*0.5 + tvH/2 + 0.1, 0); // Wall mount pos
                 group.add(screen);
                 
                 // Display
                 const disp = new THREE.Mesh(new THREE.PlaneGeometry(tvW * 0.95, tvH * 0.9), new THREE.MeshBasicMaterial({color: 0x000000}));
                 disp.position.set(0, h*0.5 + tvH/2 + 0.1, 0.03);
                 group.add(disp);

            } else if (type === 'avatar') {
                // Simple Avatar (kept same but improved materials)
                const bodyMat = new THREE.MeshStandardMaterial({color: 0x3b82f6}); 
                const skinMat = new THREE.MeshStandardMaterial({color: 0xffdbac});
                
                const torso = new THREE.Mesh(new THREE.BoxGeometry(0.4, 0.6, 0.2), bodyMat);
                torso.position.y = 0.6; group.add(torso);
                
                const head = new THREE.Mesh(new THREE.SphereGeometry(0.15), skinMat);
                head.position.y = 1.05; group.add(head);
                
                const thigh1 = new THREE.Mesh(new THREE.BoxGeometry(0.15, 0.15, 0.5), new THREE.MeshStandardMaterial({color: 0x1f2937})); 
                thigh1.position.set(-0.12, 0.35, 0.25); group.add(thigh1);
                
                const thigh2 = thigh1.clone(); thigh2.position.set(0.12, 0.35, 0.25); group.add(thigh2);
                 
                const leg1 = new THREE.Mesh(new THREE.BoxGeometry(0.15, 0.4, 0.15), new THREE.MeshStandardMaterial({color: 0x1f2937}));
                leg1.position.set(-0.12, 0.15, 0.5); group.add(leg1);
                
                const leg2 = leg1.clone(); leg2.position.set(0.12, 0.15, 0.5); group.add(leg2);
                
                const arm1 = new THREE.Mesh(new THREE.BoxGeometry(0.1, 0.4, 0.1), bodyMat);
                arm1.position.set(-0.25, 0.6, 0.1); arm1.rotation.x = -Math.PI/4; group.add(arm1);
                 const arm2 = arm1.clone(); arm2.position.set(0.25, 0.6, 0.1); group.add(arm2);

            } else if (type === 'plant') {
                const pot = new THREE.Mesh(new THREE.CylinderGeometry(0.2, 0.15, 0.3), new THREE.MeshStandardMaterial({color: 0xffffff}));
                pot.position.y = 0.15; group.add(pot);
                // More complex leaves
                const lMat = new THREE.MeshStandardMaterial({color: 0x22c55e});
                for(let i=0; i<5; i++) {
                    const l = new THREE.Mesh(new THREE.SphereGeometry(0.2), lMat);
                    l.position.set(Math.random()*0.2-0.1, 0.4+Math.random()*0.3, Math.random()*0.2-0.1);
                    l.scale.set(0.5, 1, 0.5);
                    group.add(l);
                }
            } else if (type === 'wardrobe' || type === 'fridge') {
                const mat = (type === 'fridge') ? new THREE.MeshStandardMaterial({color: 0xe5e7eb, metalness: 0.6, roughness: 0.2}) : woodMat;
                const body = new THREE.Mesh(new THREE.BoxGeometry(w, h, l), mat);
                body.position.y = h/2;
                group.add(body);
                // Gap for doors
                const gap = new THREE.Mesh(new THREE.BoxGeometry(0.04, h*0.9, l+0.04), new THREE.MeshStandardMaterial({color: 0x000000, opacity:0.1, transparent:true}));
                gap.position.set(0, h/2, 0); 
                group.add(gap);
            
            } else if (type === 'chair') {
                 const seatH = h*0.4;
                 const seat = new THREE.Mesh(new THREE.BoxGeometry(w, 0.1, l), fabricMat);
                 seat.position.y = seatH; group.add(seat);
                 // Legs
                 const lGeo = new THREE.BoxGeometry(0.1, seatH, 0.1);
                 const l1 = new THREE.Mesh(lGeo, woodMat); l1.position.set(-w/2.2, seatH/2, -l/2.2); group.add(l1);
                 const l2 = l1.clone(); l2.position.x = w/2.2; group.add(l2);
                 const l3 = l1.clone(); l3.position.z = l/2.2; group.add(l3);
                 const l4 = l3.clone(); l4.position.x = w/2.2; group.add(l4);
                 // Back
                 const back = new THREE.Mesh(new THREE.BoxGeometry(w, h*0.6, 0.1), fabricMat);
                 back.position.set(0, seatH + h*0.3, -l/2);
                 group.add(back);

            } else if (type === 'shower') {
                const glassBox = new THREE.Mesh(new THREE.BoxGeometry(w, h, l), new THREE.MeshStandardMaterial({color: 0xa5f3fc, transparent: true, opacity: 0.3}));
                glassBox.position.y = h/2;
                group.add(glassBox);
                
            } else if (type === 'mirror') {
                const mirror = new THREE.Mesh(new THREE.PlaneGeometry(w, h), new THREE.MeshStandardMaterial({color: 0xffffff, metalness: 1.0, roughness: 0.0}));
                mirror.position.z = 0.05;
                group.add(mirror);
                
            } else if (type === 'toilet') {
                 // Porcelain material
                 const porc = new THREE.MeshStandardMaterial({color: 0xffffff, roughness: 0.1, metalness: 0.1});
                 const base = new THREE.Mesh(new THREE.CylinderGeometry(0.3, 0.3, 0.4), porc);
                 base.position.y = 0.2; group.add(base);
                 const tank = new THREE.Mesh(new THREE.BoxGeometry(0.5, 0.4, 0.2), porc);
                 tank.position.set(0, 0.6, -0.2); group.add(tank);
                 const seat = new THREE.Mesh(new THREE.CylinderGeometry(0.32, 0.32, 0.05), porc);
                 seat.position.y = 0.42; group.add(seat);
            }
            
            castReceive(group);
            return group;
        }

        function updateHouseVisualization() {
            if(!houseGroup) return;
            while(houseGroup.children.length > 0) houseGroup.remove(houseGroup.children[0]);

            const plotScale = 0.5; 
            const pW = houseLayout.plot.width * plotScale;
            const pL = houseLayout.plot.length * plotScale;
            const unitScale = 0.5; 
            const hUnit = 10 * unitScale;
            
            // === STYLE DEFINITIONS ===
            const isTraditional = (formData.style === 'traditional');
            
            // 1. Lighting (Uniform Neutral / Professional)
            if(houseScene) {
                houseScene.children.forEach(c => {
                    if(c.isAmbientLight) {
                        c.color.setHex(0xffffff);
                        c.intensity = 0.6;
                    }
                    if(c.isDirectionalLight) {
                        c.color.setHex(0xffffff); 
                        c.position.set(-5, 10, 5); 
                        c.intensity = 0.8;
                        c.castShadow = true;
                    }
                });
            }

            const stylePalette = isTraditional ? {
                wall: 0xffffff,      
                trim: 0x1e293b,      
                roof: 0xffffff,      
                wood: 0x3e2723,      
                floor: 0xd6d3d1,     // Stone
                base: 0x0f172a,      
                glass: 0x1a1a1a
            } : {
                wall: 0xffffff,
                trim: 0xcbd5e1,
                roof: 0x1e293b,
                wood: 0x64748b,
                glass: 0xa5f3fc
            };

            // === BUDGET & PRIORITY INFLUENCE ===
            // Scale design quality based on budget slider (10L - 500L)
            const budgetRatio = (formData.budget - 10) / (500 - 10); // 0.0 to 1.0
            const isQualityPriority = formData.priority === 'quality';
            
            // Base quality on budget, boost if 'Better Quality' selected
            let designQuality = budgetRatio;
            if(isQualityPriority) designQuality = Math.min(1.0, designQuality + 0.3); // Boost quality
            if(formData.priority === 'space') designQuality = Math.max(0.0, designQuality - 0.1); // Slight penalty if maximizing space

            // Prioritize Quality > 0.3 for enhancements
            const isPremium = designQuality > 0.3;
            
            if (isPremium) {
                stylePalette.wall = 0xfafafa; // Cleaner white
                stylePalette.floor = 0xeaddcf; // Marble-ish
                stylePalette.glass = 0xbfdbfe; // Crystal clear blue
                
                if (designQuality > 0.7) {
                    // Ultra Premium
                    stylePalette.wall = 0xffffff; 
                    stylePalette.glass = 0xdbeafe; // Very clear
                }
                
                // Add Gold Trim if Traditional + High Quality
                if(isTraditional && designQuality > 0.6) stylePalette.trim = 0xffd700; 
                else if (designQuality > 0.4) stylePalette.trim = 0x475569; // Sleek dark metal
            }

            // === REALISTIC BASE (Plinth) ===
            const plinthH = 0.6 * unitScale; // High 2ft plinth
            const platformGeo = new THREE.BoxGeometry(pW + 1, plinthH, pL + 1); 
            const platformMat = new THREE.MeshStandardMaterial({ 
                color: stylePalette.base, 
                roughness: 0.9 
            });
            const platform = new THREE.Mesh(platformGeo, platformMat);
            platform.position.y = plinthH/2 - 0.3; // Sit on ground
            houseGroup.add(platform);
            
            // Floor Surface
            const floorSurfGeo = new THREE.BoxGeometry(pW + 0.8, 0.05, pL + 0.8);
            const floorSurfMat = new THREE.MeshStandardMaterial({ 
                color: stylePalette.floor, 
                roughness: 0.1, // Shiny floor
                metalness: 0.0,
                map: getProceduralTexture('concrete') 
            });
            const floorSurf = new THREE.Mesh(floorSurfGeo, floorSurfMat);
            floorSurf.receiveShadow = true;
            floorSurf.position.y = plinthH - 0.3 + 0.025;
            houseGroup.add(floorSurf);

            // === ROTATION & POSITIONING BASED ON INPUTS ===
            // Facing
            // Assuming default door is on South wall (World +Z), rotate to match label
            const facingMap = { 'North': Math.PI, 'East': Math.PI/2, 'South': 0, 'West': -Math.PI/2 };
            const targetRot = facingMap[formData.facing] || 0;
            houseGroup.rotation.y = targetRot;

             // Roads (Visuals)
            const roadMat = new THREE.MeshStandardMaterial({ color: 0x334155 });
            const roadW = (parseInt(formData.roadWidth) / 10) * unitScale; // Scale road visually
            
            // Front Road (Always there, typically South/Main Entry)
            const road1 = new THREE.Mesh(new THREE.BoxGeometry(pW + 10, 0.1, roadW), roadMat);
            road1.position.set(0, -0.2, (pL/2) + roadW/2 + 1); // Front
            houseGroup.add(road1);
            
            if(formData.cornerPlot) {
               // Side Road (West Side)
               const road2 = new THREE.Mesh(new THREE.BoxGeometry(roadW, 0.1, pL + 10 + roadW), roadMat);
               road2.position.set(-(pW/2) - roadW/2 - 1, -0.2, 0); 
               houseGroup.add(road2);
            }

            // Car Porch & Parking
            if (formData.parking > 0) {
                const numCars = formData.parking;
                const carWidthSpace = 5 * unitScale; // Space per car
                const totalPorchW = numCars * carWidthSpace;
                
                // Determine Porch Dimensions
                const porchL = 6 * unitScale;
                const porchX = (pW / 2) - (totalPorchW / 2) - (1 * unitScale); // Align to one side
                const porchZ = (pL / 2) + (porchL / 2); // Extends out front
                const porchH = hUnit - (0.5 * unitScale);

                // 1. Porch Pillars
                const pillarGeo = new THREE.BoxGeometry(0.25 * unitScale, porchH, 0.25 * unitScale);
                const pillarMat = new THREE.MeshStandardMaterial({ color: stylePalette.trim });
                
                // Front Pillars
                const p1 = new THREE.Mesh(pillarGeo, pillarMat); p1.position.set(porchX - totalPorchW/2 + 0.15, porchH/2 + plinthH - 0.3, porchZ + porchL/2 - 0.15);
                const p2 = new THREE.Mesh(pillarGeo, pillarMat); p2.position.set(porchX + totalPorchW/2 - 0.15, porchH/2 + plinthH - 0.3, porchZ + porchL/2 - 0.15);
                houseGroup.add(p1); houseGroup.add(p2);
                
                // Rear Pillars (against house)
                const p3 = new THREE.Mesh(pillarGeo, pillarMat); p3.position.set(porchX - totalPorchW/2 + 0.15, porchH/2 + plinthH - 0.3, porchZ - porchL/2 + 0.15);
                const p4 = new THREE.Mesh(pillarGeo, pillarMat); p4.position.set(porchX + totalPorchW/2 - 0.15, porchH/2 + plinthH - 0.3, porchZ - porchL/2 + 0.15);
                houseGroup.add(p3); houseGroup.add(p4);

                // 2. Porch Roof
                const prGeo = new THREE.BoxGeometry(totalPorchW + 0.4, 0.2, porchL + 0.4);
                const prMat = new THREE.MeshStandardMaterial({ color: stylePalette.roof, roughness: 0.5 });
                const pr = new THREE.Mesh(prGeo, prMat);
                pr.position.set(porchX, porchH + plinthH - 0.3, porchZ);
                houseGroup.add(pr);

                // 3. Load Real Car (Porsche 911) & Animate
                // Reliable high-quality model from Khronos samples
                const carUrl = 'https://raw.githubusercontent.com/KhronosGroup/glTF-Sample-Models/master/2.0/Porsche911GT2/glTF-Binary/Porsche911GT2.glb';
                
                const loader = new THREE.GLTFLoader();
                loader.load(
                    carUrl, 
                    (gltf) => {
                        const originalCar = gltf.scene;
                        originalCar.scale.set(1.4, 1.4, 1.4); // Adjust scale for this specific model relative to house
                        
                        // Materials fix (sometimes models come dark)
                        originalCar.traverse((o) => { 
                            if(o.isMesh) {
                                o.castShadow = true; 
                                if(o.material) o.material.metalness = 0.6;
                                if(o.material) o.material.roughness = 0.2;
                            }
                        });


                        // Loop for number of cars
                        for (let i = 0; i < numCars; i++) {
                            const car = originalCar.clone();
                            const offset = (i - (numCars - 1) / 2) * carWidthSpace;
                            const targetZ = porchZ; // Final parked position
                            const startZ = porchZ + 20; // Start from road (20 units away)
                            
                            // Align x-position
                            car.position.set(porchX + offset, plinthH - 0.3, targetZ);
                            car.rotation.y = Math.PI; // Face Outwards

                            houseGroup.add(car);

                            // ANIMATION LOGIC
                            // If 1 Car: Animate driving in
                            // If 2 Cars: 
                            //    i=0 (Left/First car): Static (parked)
                            //    i=1 (Right/Second car): Animate driving in
                            
                            let shouldAnimate = false;
                            
                            if (numCars === 1) {
                                // Single car always drives in
                                shouldAnimate = true;
                            } else if (numCars === 2) {
                                // Second car (index 1) drives in, First car (index 0) is already parked
                                if (i === 1) shouldAnimate = true;
                            }

                            if (shouldAnimate) {
                                // Set initial position at road
                                car.position.z = startZ;
                                
                                // Animate to park using GSAP
                                gsap.to(car.position, {
                                    z: targetZ,
                                    duration: 3,
                                    ease: "power2.out",
                                    delay: 0.5
                                });
                            }
                        }
                    },
                    undefined,
                    (error) => {
                        console.error('Car load failed', error);
                        // No geometric fallback to avoid "toy car" complaint - just show empty porch if fails
                        // or add a simple text label in 3D
                    }
                );
            }




            // === STRUCTURE ===
            for(let f = 0; f < formData.floors; f++) {
                const floorY = (f * hUnit) + (plinthH - 0.3);

                houseLayout.rooms.forEach(room => {
                    const rW = room.width * unitScale;
                    const rL = room.height * unitScale;
                    const rX = (room.x * unitScale) - (pW / 2) + (rW / 2);
                    const rZ = (room.y * unitScale) - (pL / 2) + (rL / 2);

                    // Interior Floor
                    const fGeo = new THREE.BoxGeometry(rW-0.1, 0.02, rL-0.1);
                    const fMesh = new THREE.Mesh(fGeo, floorSurfMat); // Use same polished floor
                    fMesh.position.set(rX, floorY + 0.05, rZ);
                    fMesh.receiveShadow = true;
                    houseGroup.add(fMesh);

                    // Walls
                    const wThick = isTraditional ? (0.25 * unitScale) : (0.1 * unitScale); 
                    const wMat = new THREE.MeshStandardMaterial({ 
                        color: stylePalette.wall, 
                        roughness: 0.9,
                        metalness: 0.0 // Matte paint
                    });
                    
                    // Balcony Logic: Change wall material/visibility
                    const isBalcony = (room.type === 'balcony');
                    if(isBalcony) {
                         // Use floor styling for balcony floor
                         fMesh.material = new THREE.MeshStandardMaterial({color: 0xe5e7eb, map: null});
                         // No specific tag needed here as long as parent or it has one if needed, but 'floor' is fine
                         fMesh.name = 'floor';
                    } else {
                         fMesh.name = 'floor'; 
                    }

                    
                    const dW = 3 * unitScale;
                    const dH = 7 * unitScale;
                    


                    const sideWalls = [
                        { p: [0, 0, -rL/2], d: [rW, hUnit, wThick], door: room.doors?.find(d=>d.wall==='North'), axis: 'z' },
                        { p: [0, 0, rL/2], d: [rW, hUnit, wThick], door: room.doors?.find(d=>d.wall==='South'), axis: 'z' },
                        { p: [-rW/2, 0, 0], d: [wThick, hUnit, rL], door: room.doors?.find(d=>d.wall==='West'), axis: 'x' },
                        { p: [rW/2, 0, 0], d: [wThick, hUnit, rL], door: room.doors?.find(d=>d.wall==='East'), axis: 'x' }
                    ];

                    sideWalls.forEach(sw => {
                        const wallGrp = new THREE.Group();
                        wallGrp.name = 'wall_group'; // Helpful for debugging
                        wallGrp.position.set(rX + sw.p[0], floorY + hUnit/2, rZ + sw.p[2]);

                        if (isBalcony) {
                            // Railing
                             // ... (Railing code omitted for brevity, assuming generic objects)
                             // Re-adding railing code briefly but ensuring names
                             const railingH = 1.0; 
                             const rail = new THREE.Mesh(new THREE.BoxGeometry(sw.d[0], 0.1, sw.d[2]), new THREE.MeshStandardMaterial({color: 0x333333}));
                             rail.position.y = -hUnit/2 + railingH;
                             rail.name = 'wall'; // Tag railing as wallish
                             wallGrp.add(rail);
                             
                             // Bars could be added here similar to before
                        } else if(sw.door) {
                            // Door Logic
                             const lH = hUnit - dH;
                            const lintel = new THREE.Mesh(new THREE.BoxGeometry(sw.d[0], lH, sw.d[2]), wMat);
                            lintel.position.y = (hUnit/2) - (lH/2);
                            lintel.name = 'wall';
                            wallGrp.add(lintel);

                            const sW = (Math.max(sw.d[0], sw.d[2]) - dW) / 2;
                            const left = new THREE.Mesh(new THREE.BoxGeometry(sw.axis==='z'?sW:sw.d[0], dH, sw.axis==='z'?sw.d[2]:sW), wMat);
                            const right = left.clone();
                            if (sw.axis === 'z') { left.position.set(-sw.d[0]/2 + sW/2, -lH/2, 0); right.position.set(sw.d[0]/2 - sW/2, -lH/2, 0); } 
                            else { left.position.set(0, -lH/2, -sw.d[2]/2 + sW/2); right.position.set(0, -lH/2, sw.d[2]/2 - sW/2); }
                            
                            left.name = 'wall'; right.name = 'wall';
                            wallGrp.add(left);
                            wallGrp.add(right);

                            // DOOR
                            if (sw.door.type === 'main') {
                                    const dMat = new THREE.MeshStandardMaterial({color: isTraditional ? stylePalette.wood : 0x334155});
                                    const door = new THREE.Mesh(new THREE.BoxGeometry(dW, dH, 0.1), dMat);
                                    door.position.y = -lH/2;
                                    door.name = 'door'; // Tag
                                    if(sw.axis === 'x') door.rotation.y = Math.PI/2;
                                    wallGrp.add(door);
                            }
                        } else {
                            // Solid Wall + Windows
                            const dim = Math.max(sw.d[0], sw.d[2]);
                            
                            if (dim > 5 * unitScale) {
                                // Window
                                const wMesh = new THREE.Mesh(new THREE.BoxGeometry(sw.d[0], hUnit, sw.d[2]), wMat);
                                wMesh.name = 'wall'; // WALL WITH WINDOW HOLE (simplified as solid for now, but logical tag)
                                // Actually simplistic model adds window ON TOP or IN PLACE. 
                                // Reverting to original simplifed box for wall
                                wallGrp.add(wMesh);

                                // Create Window Group
                                const winGrp = new THREE.Group();
                                winGrp.name = 'window'; // Tag Group
                                
                                // ... Window creation code ...
                                // Simply adding a glass pane for tag
                                const glass = new THREE.Mesh(new THREE.PlaneGeometry(3*unitScale, 4*unitScale), new THREE.MeshStandardMaterial({color: 0xa5f3fc, transparent:true, opacity:0.3}));
                                glass.position.z = wThick/2 + 0.05;
                                winGrp.add(glass);
                                
                                if(sw.axis==='x') winGrp.rotation.y = Math.PI/2;
                                wallGrp.add(winGrp);
                                
                            } else {
                                // Just Solid Wall
                                const wGeo = new THREE.BoxGeometry(sw.d[0], hUnit, sw.d[2]);
                                const wMesh = new THREE.Mesh(wGeo, wMat);
                                wMesh.name = 'wall'; // Tag
                                wallGrp.add(wMesh);
                            }
                        }
                        houseGroup.add(wallGrp);
                    });

                    // === FURNITURE GENERATION ===
                    if(room.furniture) {
                        room.furniture.forEach(f => {
                             const fW = f.w * unitScale;
                             const fL = f.l * unitScale;
                             // Heuristic height if not specified (Wardrobes tall, beds low)
                             let defaultH = 3; 
                             if(f.type === 'wardrobe' || f.type === 'shower' || f.type === 'fridge') defaultH = 7;
                             if(f.type === 'chair' || f.type === 'sofa') defaultH = 3;
                             if(f.type === 'table' || f.type === 'desk') defaultH = 2.5;
                             
                             const fH = (f.h || defaultH) * unitScale;
                             
                             // Calculate Top-Left of Room in 3D Space
                             const rWidthWorld = room.width * unitScale;
                             const rLengthWorld = room.height * unitScale;
                             
                             const tlX = rX - rWidthWorld/2;
                             const tlZ = rZ - rLengthWorld/2;
                             
                             // Raw Target Position
                             let rawFX = tlX + (f.x * rWidthWorld);
                             let rawFZ = tlZ + (f.y * rLengthWorld);
                             
                             // CLAMPING LOGIC: Keep furniture inside walls
                             // Margin to avoid z-fighting with wall (e.g. 0.05)
                             const safety = 0.05;
                             const minX = tlX + fW/2 + safety;
                             const maxX = tlX + rWidthWorld - fW/2 - safety;
                             const minZ = tlZ + fL/2 + safety;
                             const maxZ = tlZ + rLengthWorld - fL/2 - safety;
                             
                             const fX = Math.max(minX, Math.min(maxX, rawFX));
                             const fZ = Math.max(minZ, Math.min(maxZ, rawFZ));
                             
                             const fY = floorY + 0.05;
                             
                             // Create Mesh
                             const furn = createDetailedFurniture(f.type, fW, fL, fH, new THREE.MeshStandardMaterial({color: 0xeeeeee}));
                             furn.position.set(fX, fY, fZ);
                             
                             // Rotation / Orientation
                             // Default facing "into" room or specific rotations
                             // For beds/sofas near walls, we might want to rotate them to face center?
                             // Current default is 0 (Aligned with world Z) or PI.
                             // Let's improve rotation based on wall proximity if needed, but for now simple checks
                             if(f.type === 'bed' || f.type === 'sofa' || f.type === 'chair') {
                                 furn.rotation.y = Math.PI; 
                             }
                             
                             // Rotate desk if it's clearly against a side wall (Clamped heavily on X)
                             if (f.type === 'desk') {
                                 // If clamped to left/right walls, rotate 90 deg
                                 if (Math.abs(fX - minX) < 0.1 || Math.abs(fX - maxX) < 0.1) {
                                     furn.rotation.y = Math.PI/2;
                                 }
                             }
                             
                             // Height Offset (e.g., Wall Shelves, TV)
                             if(f.hOffset) furn.position.y += f.hOffset * unitScale;
                             
                             houseGroup.add(furn);
                        });
                    }

                });
            }

            // ROOF & EXTERIOR DETAILS
            const roofY = (formData.floors * hUnit) + (plinthH - 0.3);
            
            // Dynamic Roof Visibility: Transparent in 3D View (to see layout), Solid in Walkthrough (Ceiling)
            const roofOpacity = isWalking ? 1.0 : 0.2;
            const roofTransparent = !isWalking;

            if (isTraditional) {
                // FLAT ROOF (Traditional with Veranda coverage)
                const rMat = new THREE.MeshStandardMaterial({ 
                    color: stylePalette.roof, 
                    roughness: 0.6,
                    transparent: roofTransparent, 
                    opacity: roofOpacity,
                    side: THREE.DoubleSide
                });
                
                // Main flat slab
                const roofGeo = new THREE.BoxGeometry(pW + 1.0, 0.2, pL + 1.5);
                const roof = new THREE.Mesh(roofGeo, rMat);
                roof.position.y = roofY;
                roof.position.z = 0.5 * unitScale; 
                roof.name = 'roof'; // Tag
                houseGroup.add(roof);
                
                // Parapet Wall (Always visible)
                const paraH = 0.8 * unitScale;
                const paraThick = 0.1 * unitScale;
                const pMat = new THREE.MeshStandardMaterial({ color: stylePalette.wall });
                
                const pg = new THREE.Group();
                const bX = (pW + 1.0)/2;
                const bZ = (pL + 1.5)/2;
                
                const pw1 = new THREE.Mesh(new THREE.BoxGeometry(pW + 1.0, paraH, paraThick), pMat); pw1.position.z = -bZ + paraThick/2;
                const pw2 = new THREE.Mesh(new THREE.BoxGeometry(pW + 1.0, paraH, paraThick), pMat); pw2.position.z = bZ - paraThick/2;
                const pw3 = new THREE.Mesh(new THREE.BoxGeometry(paraThick, paraH, pL + 1.5), pMat); pw3.position.x = -bX + paraThick/2;
                const pw4 = new THREE.Mesh(new THREE.BoxGeometry(paraThick, paraH, pL + 1.5), pMat); pw4.position.x = bX - paraThick/2;
                
                pg.add(pw1); pg.add(pw2); pg.add(pw3); pg.add(pw4);
                pg.position.y = roofY + 0.1 + paraH/2;
                pg.position.z = 0.5 * unitScale;
                houseGroup.add(pg);

                // VERANDA PILLARS
                const pRad = 0.2 * unitScale;
                const pH = hUnit;
                const postGeo = new THREE.BoxGeometry(pRad, pH, pRad);
                const postMat = new THREE.MeshStandardMaterial({color: stylePalette.wood});
                const baseGeo = new THREE.BoxGeometry(pRad*1.5, 0.3, pRad*1.5);
                const baseMat = new THREE.MeshStandardMaterial({color: stylePalette.base});

                const startX = -pW/2;
                const endX = pW/2;
                const zPos = pL/2 + 0.5;
                const count = Math.floor(pW / (3*unitScale)) + 2;
                
                for(let i=0; i<count; i++) {
                    const x = startX + (i * ((endX - startX)/(count-1)));
                    const grp = new THREE.Group();
                    const p = new THREE.Mesh(postGeo, postMat);
                    p.position.y = pH/2;
                    const b = new THREE.Mesh(baseGeo, baseMat);
                    b.position.y = 0.15;
                    grp.add(b); grp.add(p);
                    grp.position.set(x, (plinthH - 0.3), zPos);
                    houseGroup.add(grp);
                }

            } else {
                // MODERN ROOF
                const rGeo = new THREE.BoxGeometry(pW + 0.5, 0.1, pL + 0.5);
                const rMat = new THREE.MeshStandardMaterial({ 
                    color: stylePalette.roof, 
                    transparent: roofTransparent, 
                    opacity: roofOpacity,
                    side: THREE.DoubleSide 
                });
                const roof = new THREE.Mesh(rGeo, rMat);
                roof.position.y = roofY;
                roof.name = 'roof'; // Tag for customization
                houseGroup.add(roof);
            }
        }

        let controls;
        let isWalking = false;
        let isSprinting = false;
        let currentRoom = 'Living Room';
        
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
            controls.addEventListener('lock', () => {
                document.getElementById('walk-instructions').style.display = 'none';
            });
            controls.addEventListener('unlock', () => {
                if(isWalking) {
                    document.getElementById('walk-instructions').style.display = 'block';
                }
            });
        }

        function exitWalkMode() {
            isWalking = false;
            isSprinting = false;
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
        

        
        function updateWalkPosition() {
            if(!isWalking || !houseCamera) return;
            
            const pos = houseCamera.position;
            document.getElementById('position-indicator').innerText = 
                `X: ${pos.x.toFixed(1)} | Y: ${pos.y.toFixed(1)} | Z: ${pos.z.toFixed(1)}`;
            
            // Detect current room based on position
            detectCurrentRoom(pos.x, pos.z);
        }
        
        function detectCurrentRoom(x, z) {
            if(!houseLayout || !houseLayout.floors || !houseLayout.floors[0]) return;
            
            const rooms = houseLayout.floors[0].rooms;
            const unitScale = 0.3;
            const pW = formData.width * unitScale;
            const pL = formData.length * unitScale;
            
            for(let room of rooms) {
                const rW = room.width * unitScale;
                const rL = room.length * unitScale;
                const rX = -pW/2 + (room.x * pW) + (rW/2);
                const rZ = -pL/2 + (room.y * pL) + (rL/2);
                
                // Check if position is within room bounds
                if(Math.abs(x - rX) < rW/2 && Math.abs(z - rZ) < rL/2) {
                    const roomName = room.type.charAt(0).toUpperCase() + room.type.slice(1);
                    if(currentRoom !== roomName) {
                        currentRoom = roomName;
                        showRoomLabel(roomName);
                    }
                    return;
                }
            }
        }
        
        function showRoomLabel(roomName) {
            const label = document.getElementById('room-label');
            label.innerText = roomName;
            label.classList.add('visible');
            setTimeout(() => {
                label.classList.remove('visible');
            }, 2000);
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
                case 'ShiftLeft':
                case 'ShiftRight': isSprinting = true; break;
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
                case 'ShiftLeft':
                case 'ShiftRight': isSprinting = false; break;
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

                // Sprint multiplier
                const speedMultiplier = isSprinting ? 2.0 : 1.0;
                const baseSpeed = 40.0 * speedMultiplier;

                if (moveForward || moveBackward) velocity.z -= direction.z * baseSpeed * delta;
                if (moveLeft || moveRight) velocity.x -= direction.x * baseSpeed * delta;

                controls.moveRight(-velocity.x * delta);
                controls.moveForward(-velocity.z * delta);
                
                // Simple collision (Floor limit)
                if(houseCamera.position.y < 0.7) {
                     velocity.y = 0;
                     houseCamera.position.y = 0.7;
                }

                prevTime = time;
                updateWalkPosition();
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
            updateHouseVisualization(); // Update 3D for road width
        }

        function toggleCorner(el, value) {
            el.parentElement.querySelectorAll('.selection-card').forEach(c => c.classList.remove('selected'));
            el.classList.add('selected');
            formData.cornerPlot = value;
            document.getElementById('corner-hint').classList.toggle('show', value);
            updateConfidence();
            updateHouseVisualization(); // Update 3D for road
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
            // Vaastu IS a structural change (rearranges kitchen/bedroom)
            if (hasStructuralChange()) {
                generateHouseLayout(); 
                drawFloorPlan();
                updateHouseVisualization(); 
                updatePreviousStructure();
            } else {
                 // Force refresh even if simple toggle
                 generateHouseLayout(); 
                 updateHouseVisualization();
            }
        }

        // === CALCULATIONS ===
        function updateCalculations() {
            // Read plot dimensions from inputs
            const lengthInput = document.getElementById('inp-length');
            const widthInput = document.getElementById('inp-width');
            const strictNumberRegex = /^\d*\.?\d+$/;
            
            let isValid = true;
            const MIN_DIM = 10;
            const MAX_DIM = 1000;

            if (lengthInput) {
                const rawVal = lengthInput.value;
                const val = parseFloat(rawVal);
                // Check strict regex AND range
                if (!strictNumberRegex.test(rawVal) || isNaN(val) || val < MIN_DIM || val > MAX_DIM) {
                    lengthInput.style.borderColor = '#ef4444';
                    lengthInput.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.2)';
                    isValid = false;
                } else {
                    lengthInput.style.borderColor = '';
                    lengthInput.style.boxShadow = '';
                    formData.length = val;
                }
            }

            if (widthInput) {
                const rawVal = widthInput.value;
                const val = parseFloat(rawVal);
                if (!strictNumberRegex.test(rawVal) || isNaN(val) || val < MIN_DIM || val > MAX_DIM) {
                    widthInput.style.borderColor = '#ef4444';
                    widthInput.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.2)';
                    isValid = false;
                } else {
                    widthInput.style.borderColor = '';
                    widthInput.style.boxShadow = '';
                    formData.width = val;
                }
            }
            
            if (!isValid) return;

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

        // === CUSTOMIZATION LOGIC ===
        function toggleCustomization() {
            const p = document.getElementById('customization-panel');
            p.classList.toggle('active');
        }



        function setWallColor(color, el) {
            // Update UI
            document.querySelectorAll('#wall-palette .color-swatch').forEach(c => c.classList.remove('active'));
            el.classList.add('active');
            
            // Update 3D
            if(houseGroup) {
                houseGroup.traverse(o => {
                    // Check for "wall" tag directly
                    if(o.name === 'wall') {
                        if(o.material) o.material.color.setHex(color);
                    }
                });
            }
        }

        function setRoofMaterial(type) {
            if(!houseGroup) return;
            houseGroup.traverse(o => {
                if(o.name === 'roof' || o.name === 'roof_part') {
                    const mat = o.material;
                    if(type === 'tiles') {
                        mat.color.setHex(0xa0522d); // Sienna
                        mat.roughness = 0.9;
                        mat.metalness = 0.0;
                    } else if(type === 'concrete') {
                        mat.color.setHex(0x9ca3af); // Grey
                        mat.roughness = 1.0;
                        mat.metalness = 0.0;
                    } else if(type === 'metal') {
                        mat.color.setHex(0x1e293b); // Dark Slate
                        mat.roughness = 0.3;
                        mat.metalness = 0.7;
                    } else if(type === 'slate') {
                         mat.color.setHex(0x475569);
                         mat.roughness = 0.6;
                         mat.metalness = 0.2;
                    } else {
                        // Default (White/Generic)
                        mat.color.setHex(0xffffff);
                        mat.roughness = 0.5;
                        mat.metalness = 0.1;
                    }
                }
            });
        }
        
        function toggleComponent(name, visible) {
            if(!houseGroup) return;
            
            if(name === 'furniture') {
                 // Furniture are often Groups
                 houseGroup.traverse(o => {
                    if(o.name === 'furniture') {
                        o.visible = visible;
                    }
                });
            } else if (name === 'window') {
                 houseGroup.traverse(o => {
                    if(o.name === 'window') {
                        o.visible = visible; // Toggle the whole window group
                    }
                });
            } else {
                // For direct meshes
                houseGroup.traverse(o => {
                    if(o.name === name) {
                        o.visible = visible;
                    }
                });
            }
        }
        
        function setLightIntensity(val) {
             if(houseScene) {
                houseScene.children.forEach(c => {
                    if(c.isAmbientLight) c.intensity = parseFloat(val) * 0.7;
                    if(c.isDirectionalLight) c.intensity = parseFloat(val);
                });
             }
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
        document.addEventListener('DOMContentLoaded', () => {
             console.log("Initializing Plans & Designs Wizard...");
             try {
                if(typeof generateHouseLayout === 'function') generateHouseLayout();
                if(typeof init2DFloorPlan === 'function') init2DFloorPlan();
                if(typeof init3DHouse === 'function') init3DHouse();
                if(typeof updateCalculations === 'function') updateCalculations();
                if(typeof updateConfidence === 'function') updateConfidence();
             } catch (e) {
                 console.error("Initialization error:", e);
             }
        });
    </script>
</body>
</html>
