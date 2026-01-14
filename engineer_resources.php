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
    <title>Engineer Resources - Constructa Command Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Orbitron:wght@400;500;700;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    <style>
        :root {
            --primary-dark: #0a1612;
            --secondary-dark: #1a2e23;
            --forest-green: #294033;
            --emerald: #10b981;
            --cyan: #06b6d4;
            --glass-bg: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.1);
            --glow-emerald: rgba(16, 185, 129, 0.4);
            --glow-cyan: rgba(6, 182, 212, 0.4);
            --text-primary: #e5e7eb;
            --text-secondary: #9ca3af;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--primary-dark);
            color: var(--text-primary);
            overflow-x: hidden;
            perspective: 2000px;
        }

        /* 3D Background Canvas */
        #canvas-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: -1;
            background: linear-gradient(135deg, #0a1612 0%, #1a2e23 50%, #0f1a15 100%);
        }

        /* Navigation - Consistent with Platform */
        nav {
            background: rgba(26, 46, 35, 0.4);
            backdrop-filter: blur(30px);
            padding: 1rem 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--glass-border);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
        }

        .nav-logo {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 800;
            font-size: 1.6rem;
            color: var(--emerald);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            letter-spacing: -0.5px;
            text-shadow: 0 0 20px var(--glow-emerald);
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .nav-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--text-primary);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .nav-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(16, 185, 129, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .nav-btn:hover::before {
            left: 100%;
        }

        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px var(--glow-emerald);
            background: var(--forest-green);
            border-color: var(--emerald);
        }

        /* Hero Section - 3D Command Center */
        .hero-section {
            position: relative;
            min-height: 70vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 6rem 3rem 4rem;
            overflow: hidden;
        }

        .floating-grid {
            position: absolute;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(16, 185, 129, 0.1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(16, 185, 129, 0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            transform: rotateX(60deg) translateZ(-200px);
            animation: gridFlow 20s linear infinite;
            opacity: 0.3;
        }

        @keyframes gridFlow {
            0% { transform: rotateX(60deg) translateZ(-200px) translateY(0); }
            100% { transform: rotateX(60deg) translateZ(-200px) translateY(50px); }
        }

        .hero-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 4.5rem;
            font-weight: 900;
            background: linear-gradient(135deg, #10b981 0%, #06b6d4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-align: center;
            margin-bottom: 1.5rem;
            text-shadow: 0 0 80px var(--glow-emerald);
            letter-spacing: -2px;
            animation: titlePulse 3s ease-in-out infinite;
        }

        @keyframes titlePulse {
            0%, 100% { filter: brightness(1); }
            50% { filter: brightness(1.2); }
        }

        .hero-subtitle {
            font-size: 1.3rem;
            color: var(--text-secondary);
            text-align: center;
            max-width: 800px;
            line-height: 1.8;
            margin-bottom: 3rem;
            font-weight: 300;
        }

        /* 3D Category Orbital Selector */
        .category-orbital {
            position: relative;
            width: 600px;
            height: 600px;
            margin: 4rem auto;
            perspective: 1500px;
        }

        .orbital-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, var(--forest-green), var(--secondary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: var(--emerald);
            box-shadow: 0 0 60px var(--glow-emerald), inset 0 0 30px rgba(0, 0, 0, 0.5);
            z-index: 10;
            animation: centerGlow 2s ease-in-out infinite;
        }

        @keyframes centerGlow {
            0%, 100% { box-shadow: 0 0 60px var(--glow-emerald), inset 0 0 30px rgba(0, 0, 0, 0.5); }
            50% { box-shadow: 0 0 100px var(--glow-emerald), inset 0 0 30px rgba(0, 0, 0, 0.5); }
        }

        .category-orbit {
            position: absolute;
            width: 100%;
            height: 100%;
            animation: orbit 30s linear infinite;
            transform-style: preserve-3d;
        }

        @keyframes orbit {
            0% { transform: rotateZ(0deg); }
            100% { transform: rotateZ(360deg); }
        }

        .category-node {
            position: absolute;
            width: 100px;
            height: 100px;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 2px solid var(--glass-border);
            border-radius: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }

        .category-node:hover {
            transform: scale(1.2) translateZ(50px);
            background: rgba(16, 185, 129, 0.1);
            border-color: var(--emerald);
            box-shadow: 0 20px 60px var(--glow-emerald);
        }

        .category-node i {
            font-size: 2rem;
            color: var(--emerald);
            margin-bottom: 0.5rem;
        }

        .category-node span {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-primary);
            text-align: center;
        }

        /* Position nodes in orbit */
        .category-node:nth-child(1) { top: 0; left: 50%; transform: translateX(-50%) translateY(-250px); }
        .category-node:nth-child(2) { top: 20%; right: 0; transform: translateX(200px); }
        .category-node:nth-child(3) { bottom: 20%; right: 0; transform: translateX(200px); }
        .category-node:nth-child(4) { bottom: 0; left: 50%; transform: translateX(-50%) translateY(250px); }
        .category-node:nth-child(5) { bottom: 20%; left: 0; transform: translateX(-200px); }
        .category-node:nth-child(6) { top: 20%; left: 0; transform: translateX(-200px); }

        /* Project Stage Timeline Tunnel */
        .stage-timeline {
            position: relative;
            margin: 6rem auto;
            max-width: 1400px;
            perspective: 2000px;
        }

        .timeline-tunnel {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 3rem 2rem;
            position: relative;
            transform: rotateX(10deg);
            transform-style: preserve-3d;
        }

        .timeline-line {
            position: absolute;
            top: 50%;
            left: 5%;
            right: 5%;
            height: 4px;
            background: linear-gradient(90deg, 
                var(--emerald) 0%, 
                var(--cyan) 50%, 
                var(--emerald) 100%);
            transform: translateY(-50%);
            box-shadow: 0 0 20px var(--glow-emerald);
            animation: lineFlow 3s linear infinite;
        }

        @keyframes lineFlow {
            0% { opacity: 0.5; }
            50% { opacity: 1; }
            100% { opacity: 0.5; }
        }

        .stage-node {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            transition: all 0.4s ease;
        }

        .stage-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 3px solid var(--glass-border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: var(--emerald);
            transition: all 0.4s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .stage-node:hover .stage-circle,
        .stage-node.active .stage-circle {
            transform: scale(1.3) translateZ(30px);
            background: linear-gradient(135deg, var(--emerald), var(--cyan));
            border-color: var(--emerald);
            box-shadow: 0 20px 60px var(--glow-emerald);
            animation: stagePulse 1s ease-in-out infinite;
        }

        @keyframes stagePulse {
            0%, 100% { box-shadow: 0 20px 60px var(--glow-emerald); }
            50% { box-shadow: 0 20px 80px var(--glow-cyan); }
        }

        .stage-label {
            margin-top: 1rem;
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }

        .stage-node:hover .stage-label,
        .stage-node.active .stage-label {
            color: var(--emerald);
            text-shadow: 0 0 10px var(--glow-emerald);
        }

        /* 3D Floating Resource Cards */
        .resources-grid {
            max-width: 1600px;
            margin: 4rem auto;
            padding: 0 3rem;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 3rem;
            perspective: 1500px;
        }

        .resource-card {
            position: relative;
            height: 420px;
            transform-style: preserve-3d;
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            animation: cardFloat 6s ease-in-out infinite;
        }

        @keyframes cardFloat {
            0%, 100% { transform: translateY(0) translateZ(0); }
            50% { transform: translateY(-20px) translateZ(20px); }
        }

        .resource-card:nth-child(2n) { animation-delay: -2s; }
        .resource-card:nth-child(3n) { animation-delay: -4s; }

        .resource-card:hover {
            transform: translateY(-30px) scale(1.05) rotateX(5deg) rotateY(5deg);
        }

        .resource-card.flipped {
            transform: rotateY(180deg);
        }

        .card-face {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            border-radius: 24px;
            padding: 2rem;
            background: var(--glass-bg);
            backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border);
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        .card-front {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2rem;
        }

        .card-icon-wrapper {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--forest-green), var(--secondary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            animation: iconPulse 3s ease-in-out infinite;
        }

        @keyframes iconPulse {
            0%, 100% {
                box-shadow: 0 0 40px var(--glow-emerald);
                transform: scale(1);
            }
            50% {
                box-shadow: 0 0 60px var(--glow-cyan);
                transform: scale(1.05);
            }
        }

        .card-icon {
            font-size: 3.5rem;
            color: var(--emerald);
            filter: drop-shadow(0 0 20px var(--glow-emerald));
        }

        .card-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            text-align: center;
            margin-bottom: 0.5rem;
        }

        .card-description {
            font-size: 0.95rem;
            color: var(--text-secondary);
            text-align: center;
            line-height: 1.6;
        }

        .card-back {
            transform: rotateY(180deg);
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .card-back-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--emerald);
            text-align: center;
            margin-bottom: 1rem;
        }

        .card-back-info {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 12px;
            border: 1px solid var(--glass-border);
        }

        .info-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            font-weight: 600;
            text-transform: uppercase;
        }

        .info-value {
            font-size: 0.9rem;
            color: var(--text-primary);
            font-weight: 700;
        }

        .risk-indicator {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            animation: riskGlow 2s ease-in-out infinite;
        }

        .risk-high {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid #ef4444;
        }

        .risk-medium {
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
            border: 1px solid #fbbf24;
        }

        .risk-low {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            border: 1px solid #10b981;
        }

        @keyframes riskGlow {
            0%, 100% { box-shadow: 0 0 10px currentColor; }
            50% { box-shadow: 0 0 20px currentColor; }
        }

        .card-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: auto;
        }

        .card-btn {
            flex: 1;
            padding: 0.9rem;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--emerald), var(--cyan));
            color: white;
            box-shadow: 0 10px 30px var(--glow-emerald);
        }

        .btn-primary:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 15px 40px var(--glow-cyan);
        }

        .btn-primary:active {
            transform: translateY(0) scale(0.98);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
            border: 1px solid var(--glass-border);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--emerald);
        }

        /* Download Progress Animation */
        .download-progress {
            position: relative;
            width: 40px;
            height: 40px;
            display: none;
        }

        .download-progress.active {
            display: inline-block;
        }

        .progress-ring {
            transform: rotate(-90deg);
        }

        .progress-ring-circle {
            stroke: var(--emerald);
            fill: none;
            stroke-width: 4;
            stroke-dasharray: 126;
            stroke-dashoffset: 126;
            animation: progressFill 2s ease-in-out forwards;
        }

        @keyframes progressFill {
            to { stroke-dashoffset: 0; }
        }

        /* Scroll-triggered animations class */
        .reveal {
            opacity: 0;
            transform: translateY(50px) translateZ(-50px);
        }

        .reveal.active {
            opacity: 1;
            transform: translateY(0) translateZ(0);
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--primary-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.5s ease;
        }

        .loading-overlay.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .loading-spinner {
            width: 80px;
            height: 80px;
            border: 4px solid rgba(16, 185, 129, 0.1);
            border-top-color: var(--emerald);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-title { font-size: 2.5rem; }
            .category-orbital { width: 400px; height: 400px; }
            .resources-grid { grid-template-columns: 1fr; }
            .timeline-tunnel { flex-direction: column; gap: 2rem; }
        }
    </style>
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <div id="canvas-container"></div>

    <!-- Navigation -->
    <nav>
        <a href="engineer.php" class="nav-logo">
            <i class="fas fa-cube"></i>
            CONSTRUCTA
        </a>
        <div class="nav-links">
            <a href="engineer.php" class="nav-btn">
                <i class="fas fa-home"></i> DASHBOARD
            </a>
            <a href="my_projects.php" class="nav-btn">
                <i class="fas fa-project-diagram"></i> PROJECTS
            </a>
            <a href="login.html" class="nav-btn">
                <i class="fas fa-sign-out-alt"></i> LOGOUT
            </a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="floating-grid"></div>
        <h1 class="hero-title">ENGINEER RESOURCES</h1>
        <p class="hero-subtitle">
            Advanced engineering command center providing instant access to building codes, 
            structural calculations, safety protocols, and professional engineering tools. 
            Empowering civil engineers with enterprise-grade resources.
        </p>
    </section>

    <!-- 3D Category Orbital Selector -->
    <div class="category-orbital">
        <div class="orbital-center">
            <i class="fas fa-hard-hat"></i>
        </div>
        <div class="category-orbit">
            <div class="category-node" data-category="codes">
                <i class="fas fa-book"></i>
                <span>Building Codes</span>
            </div>
            <div class="category-node" data-category="calculations">
                <i class="fas fa-calculator"></i>
                <span>Calculations</span>
            </div>
            <div class="category-node" data-category="safety">
                <i class="fas fa-shield-alt"></i>
                <span>Safety</span>
            </div>
            <div class="category-node" data-category="materials">
                <i class="fas fa-cubes"></i>
                <span>Materials</span>
            </div>
            <div class="category-node" data-category="tools">
                <i class="fas fa-tools"></i>
                <span>Tools</span>
            </div>
            <div class="category-node" data-category="standards">
                <i class="fas fa-certificate"></i>
                <span>Standards</span>
            </div>
        </div>
    </div>

    <!-- Project Stage Timeline Tunnel -->
    <div class="stage-timeline">
        <div class="timeline-tunnel">
            <div class="timeline-line"></div>
            <div class="stage-node active" data-stage="design">
                <div class="stage-circle">
                    <i class="fas fa-drafting-compass"></i>
                </div>
                <span class="stage-label">Design</span>
            </div>
            <div class="stage-node" data-stage="approval">
                <div class="stage-circle">
                    <i class="fas fa-stamp"></i>
                </div>
                <span class="stage-label">Approval</span>
            </div>
            <div class="stage-node" data-stage="estimation">
                <div class="stage-circle">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <span class="stage-label">Estimation</span>
            </div>
            <div class="stage-node" data-stage="execution">
                <div class="stage-circle">
                    <i class="fas fa-hammer"></i>
                </div>
                <span class="stage-label">Execution</span>
            </div>
            <div class="stage-node" data-stage="inspection">
                <div class="stage-circle">
                    <i class="fas fa-search"></i>
                </div>
                <span class="stage-label">Inspection</span>
            </div>
            <div class="stage-node" data-stage="handover">
                <div class="stage-circle">
                    <i class="fas fa-handshake"></i>
                </div>
                <span class="stage-label">Handover</span>
            </div>
        </div>
    </div>

    <!-- 3D Floating Resource Cards -->
    <div class="resources-grid" id="resourcesGrid">
        <!-- Cards will be dynamically generated -->
    </div>

    <script>
        // Resource Data
        const resources = [
            {
                id: 1,
                title: 'IS 456:2000 - RCC Design',
                description: 'Indian Standard for Plain and Reinforced Concrete Code of Practice',
                icon: 'fa-file-pdf',
                category: 'codes',
                stage: 'design',
                role: 'Structural Engineer',
                risk: 'high',
                type: 'PDF Document'
            },
            {
                id: 2,
                title: 'Beam Calculator Pro',
                description: 'Advanced structural analysis and beam design calculations',
                icon: 'fa-calculator',
                category: 'calculations',
                stage: 'design',
                role: 'Structural Engineer',
                risk: 'medium',
                type: 'Engineering Tool'
            },
            {
                id: 3,
                title: 'Safety Protocol Manual',
                description: 'Comprehensive site safety guidelines and emergency procedures',
                icon: 'fa-hard-hat',
                category: 'safety',
                stage: 'execution',
                role: 'Site Engineer',
                risk: 'high',
                type: 'PDF Document'
            },
            {
                id: 4,
                title: 'Material Grade Standards',
                description: 'IS specifications for steel, concrete, and construction materials',
                icon: 'fa-cubes',
                category: 'materials',
                stage: 'estimation',
                role: 'Structural Engineer',
                risk: 'medium',
                type: 'Reference Guide'
            },
            {
                id: 5,
                title: 'Load Calculation Tool',
                description: 'Dead load, live load, and wind load computation utility',
                icon: 'fa-weight-hanging',
                category: 'calculations',
                stage: 'design',
                role: 'Structural Engineer',
                risk: 'high',
                type: 'Engineering Tool'
            },
            {
                id: 6,
                title: 'NBC 2016 Guidelines',
                description: 'National Building Code of India - Latest Edition',
                icon: 'fa-landmark',
                category: 'codes',
                stage: 'approval',
                role: 'Structural Engineer',
                risk: 'high',
                type: 'PDF Document'
            },
            {
                id: 7,
                title: 'Quality Inspection Checklist',
                description: 'Comprehensive QA/QC checklist for construction phases',
                icon: 'fa-clipboard-check',
                category: 'tools',
                stage: 'inspection',
                role: 'Site Engineer',
                risk: 'medium',
                type: 'Checklist'
            },
            {
                id: 8,
                title: 'Foundation Design Standards',
                description: 'IS 2950 - Design and construction of foundation specifications',
                icon: 'fa-layer-group',
                category: 'standards',
                stage: 'design',
                role: 'Structural Engineer',
                risk: 'high',
                type: 'Standard Document'
            },
            {
                id: 9,
                title: 'BOQ Generator',
                description: 'Automated Bill of Quantities generation and cost estimation',
                icon: 'fa-file-invoice',
                category: 'tools',
                stage: 'estimation',
                role: 'Structural Engineer',
                risk: 'low',
                type: 'Engineering Tool'
            }
        ];

        // Initialize 3D Background
        function init3DBackground() {
            const container = document.getElementById('canvas-container');
            const scene = new THREE.Scene();
            const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
            camera.position.z = 30;

            const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            container.appendChild(renderer.domElement);

            // Ambient lighting
            const ambientLight = new THREE.AmbientLight(0x10b981, 0.3);
            scene.add(ambientLight);

            const pointLight = new THREE.PointLight(0x10b981, 1, 100);
            pointLight.position.set(10, 10, 10);
            scene.add(pointLight);

            // Create wireframe city
            const cityGroup = new THREE.Group();
            const buildingMaterial = new THREE.MeshBasicMaterial({ 
                color: 0x294033, 
                wireframe: true, 
                transparent: true, 
                opacity: 0.15 
            });

            for (let i = 0; i < 30; i++) {
                const height = Math.random() * 10 + 3;
                const geometry = new THREE.BoxGeometry(
                    Math.random() * 2 + 1,
                    height,
                    Math.random() * 2 + 1
                );
                const building = new THREE.Mesh(geometry, buildingMaterial);
                building.position.set(
                    (Math.random() - 0.5) * 60,
                    height / 2 - 5,
                    (Math.random() - 0.5) * 60
                );
                cityGroup.add(building);
            }
            scene.add(cityGroup);

            // Animation
            function animate() {
                requestAnimationFrame(animate);
                cityGroup.rotation.y += 0.0005;
                renderer.render(scene, camera);
            }
            animate();

            // Responsive
            window.addEventListener('resize', () => {
                camera.aspect = window.innerWidth / window.innerHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(window.innerWidth, window.innerHeight);
            });
        }

        // Generate Resource Cards
        function generateResourceCards() {
            const grid = document.getElementById('resourcesGrid');
            resources.forEach((resource, index) => {
                const card = document.createElement('div');
                card.className = 'resource-card reveal';
                card.style.animationDelay = `${index * 0.1}s`;
                card.dataset.category = resource.category;
                card.dataset.stage = resource.stage;
                
                card.innerHTML = `
                    <div class="card-face card-front">
                        <div class="card-icon-wrapper">
                            <i class="fas ${resource.icon} card-icon"></i>
                        </div>
                        <h3 class="card-title">${resource.title}</h3>
                        <p class="card-description">${resource.description}</p>
                    </div>
                    <div class="card-face card-back">
                        <h3 class="card-back-title">${resource.title}</h3>
                        <div class="card-back-info">
                            <div class="info-row">
                                <span class="info-label">Type</span>
                                <span class="info-value">${resource.type}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Stage</span>
                                <span class="info-value">${resource.stage}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Role</span>
                                <span class="info-value">${resource.role}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Risk Level</span>
                                <span class="risk-indicator risk-${resource.risk}">${resource.risk}</span>
                            </div>
                        </div>
                        <div class="card-actions">
                            <button class="card-btn btn-primary" onclick="viewResource(${resource.id})">
                                <i class="fas fa-eye"></i> VIEW
                            </button>
                            <button class="card-btn btn-secondary" onclick="downloadResource(${resource.id})">
                                <i class="fas fa-download"></i> DOWNLOAD
                            </button>
                        </div>
                    </div>
                `;
                
                card.addEventListener('click', () => {
                    card.classList.toggle('flipped');
                });
                
                grid.appendChild(card);
            });
        }

        // Category Filter
        document.querySelectorAll('.category-node').forEach(node => {
            node.addEventListener('click', function() {
                const category = this.dataset.category;
                filterResources('category', category);
                
                // Zoom animation
                gsap.to(this, {
                    scale: 1.5,
                    duration: 0.3,
                    yoyo: true,
                    repeat: 1
                });
            });
        });

        // Stage Filter
        document.querySelectorAll('.stage-node').forEach(node => {
            node.addEventListener('click', function() {
                document.querySelectorAll('.stage-node').forEach(n => n.classList.remove('active'));
                this.classList.add('active');
                
                const stage = this.dataset.stage;
                filterResources('stage', stage);
            });
        });

        function filterResources(filterType, filterValue) {
            const cards = document.querySelectorAll('.resource-card');
            cards.forEach((card, index) => {
                const matchesFilter = card.dataset[filterType] === filterValue;
                
                if (matchesFilter) {
                    gsap.to(card, {
                        opacity: 1,
                        scale: 1,
                        y: 0,
                        duration: 0.5,
                        delay: index * 0.05,
                        ease: "back.out(1.2)"
                    });
                } else {
                    gsap.to(card, {
                        opacity: 0.2,
                        scale: 0.8,
                        y: 50,
                        duration: 0.3
                    });
                }
            });
        }

        function viewResource(id) {
            const resource = resources.find(r => r.id === id);
            alert(`Opening resource: ${resource.title}`);
            // Implement resource viewer
        }

        function downloadResource(id) {
            const resource = resources.find(r => r.id === id);
            
            // Show progress animation
            const btn = event.target.closest('.card-btn');
            const originalHTML = btn.innerHTML;
            
            btn.innerHTML = `
                <svg class="download-progress active" viewBox="0 0 40 40">
                    <circle class="progress-ring-circle" cx="20" cy="20" r="18"></circle>
                </svg>
            `;
            
            setTimeout(() => {
                btn.innerHTML = '<i class="fas fa-check"></i> DONE';
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                }, 1500);
            }, 2000);
        }

        // Scroll-triggered animations
        gsap.registerPlugin(ScrollTrigger);

        function initScrollAnimations() {
            const reveals = document.querySelectorAll('.reveal');
            
            reveals.forEach(element => {
                gsap.to(element, {
                    scrollTrigger: {
                        trigger: element,
                        start: "top 80%",
                        end: "bottom 20%",
                        toggleClass: "active",
                        once: true
                    }
                });
            });
        }

        // Initialize
        window.addEventListener('DOMContentLoaded', () => {
            init3DBackground();
            generateResourceCards();
            
            setTimeout(() => {
                initScrollAnimations();
                document.getElementById('loadingOverlay').classList.add('hidden');
            }, 1000);
        });
    </script>
</body>
</html>
