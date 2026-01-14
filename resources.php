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
    <title>Engineer Resources - Constructa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        :root {
            --stone-white: #f6f7f2;
            --concrete-gray: #d4d6d0;
            --text-dark: #2c3e50;
            --text-gray: #64748b;
            --primary-green: #294033;
            --accent-green: #3d5a49;
            --card-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            --card-hover-shadow: 0 16px 48px rgba(0, 0, 0, 0.12);
            --transition-smooth: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--stone-white);
            color: var(--text-dark);
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* 3D Background - Same as Landing Page */
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

        /* Navigation - Clean & Professional */
        nav {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 1.5rem 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
        }

        .nav-logo {
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--primary-green);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            letter-spacing: -0.5px;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .nav-btn {
            background: white;
            border: 1px solid rgba(0, 0, 0, 0.08);
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-dark);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition-smooth);
        }

        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(41, 64, 51, 0.15);
            background: var(--primary-green);
            color: white;
            border-color: var(--primary-green);
        }

        /* Hero Section - Minimal & Clean */
        .hero-section {
            max-width: 1000px;
            margin: 0 auto;
            padding: 5rem 3rem 4rem;
            text-align: center;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 1rem;
            letter-spacing: -1px;
        }

        .hero-subtitle {
            font-size: 1.2rem;
            color: var(--text-gray);
            font-weight: 400;
            max-width: 700px;
            margin: 0 auto 3rem;
            line-height: 1.8;
        }

        /* Building Floors - Section Structure */
        .floor-section {
            max-width: 1400px;
            margin: 0 auto;
            padding: 4rem 3rem;
            opacity: 0;
            transform: translateY(40px);
            transition: opacity 1s ease, transform 1s ease;
        }

        .floor-section.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .floor-header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 3rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid rgba(41, 64, 51, 0.1);
        }

        .floor-number {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-green), var(--accent-green));
            color: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 800;
            box-shadow: 0 4px 12px rgba(41, 64, 51, 0.2);
        }

        .floor-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin: 0;
        }

        .floor-description {
            font-size: 1rem;
            color: var(--text-gray);
            margin-top: 0.25rem;
        }

        /* Resource Cards - Architectural Panels */
        .resources-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 2.5rem;
        }

        .resource-card {
            background: white;
            border-radius: 12px;
            padding: 2.5rem;
            position: relative;
            transition: var(--transition-smooth);
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(0, 0, 0, 0.04);
            cursor: pointer;
            overflow: hidden;
        }

        .resource-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, var(--primary-green), var(--accent-green));
            transform: scaleY(0);
            transform-origin: top;
            transition: transform 0.4s ease;
        }

        .resource-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--card-hover-shadow);
        }

        .resource-card:hover::before {
            transform: scaleY(1);
        }

        .resource-icon-wrapper {
            width: 80px;
            height: 80px;
            background: rgba(41, 64, 51, 0.06);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            transition: var(--transition-smooth);
        }

        .resource-card:hover .resource-icon-wrapper {
            background: linear-gradient(135deg, var(--primary-green), var(--accent-green));
        }

        .resource-icon {
            font-size: 2.2rem;
            color: var(--primary-green);
            transition: var(--transition-smooth);
        }

        .resource-card:hover .resource-icon {
            color: white;
            transform: scale(1.1);
        }

        .resource-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.75rem;
            line-height: 1.3;
        }

        .resource-description {
            font-size: 0.95rem;
            color: var(--text-gray);
            line-height: 1.7;
            margin-bottom: 1.5rem;
        }

        .resource-meta {
            display: flex;
            gap: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
            font-size: 0.85rem;
            color: var(--text-gray);
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .meta-item i {
            color: var(--primary-green);
        }

        .resource-actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }

        .primary-actions {
            display: flex;
            gap: 0.75rem;
            opacity: 0.65;
            transition: var(--transition-smooth);
        }

        .resource-card:hover .primary-actions {
            opacity: 1;
        }

        .secondary-actions {
            display: flex;
            gap: 0.75rem;
            opacity: 0;
            transform: translateY(-10px);
            transition: var(--transition-smooth);
        }

        .resource-card:hover .secondary-actions {
            opacity: 1;
            transform: translateY(0);
        }

        .resource-btn {
            flex: 1;
            padding: 0.85rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: var(--transition-smooth);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        /* Open Tool - Primary Action (Most Prominent) */
        .btn-open-tool {
            background: var(--primary-green);
            color: white;
            box-shadow: 0 2px 8px rgba(41, 64, 51, 0.2);
        }

        .btn-open-tool:hover {
            background: var(--accent-green);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(41, 64, 51, 0.35);
        }

        /* Guide - Secondary Primary Action (Outlined) */
        .btn-guide {
            background: transparent;
            color: var(--primary-green);
            border: 1.5px solid var(--primary-green);
        }

        .btn-guide:hover {
            background: rgba(41, 64, 51, 0.08);
            border-color: var(--accent-green);
            color: var(--accent-green);
        }

        /* View - Quick Reference Action (Outlined, lighter) */
        .btn-view {
            background: transparent;
            color: var(--text-dark);
            border: 1px solid rgba(0, 0, 0, 0.15);
        }

        .btn-view:hover {
            background: var(--primary-green);
            color: white;
            border-color: var(--primary-green);
        }

        /* Download - Documentation Action (Text link style) */
        .btn-download {
            background: rgba(41, 64, 51, 0.05);
            color: var(--primary-green);
            border: 1px solid rgba(41, 64, 51, 0.15);
            font-size: 0.85rem;
        }

        .btn-download:hover {
            background: var(--primary-green);
            color: white;
            border-color: var(--primary-green);
            transform: translateY(-1px);
        }

        /* Professional Badges */
        .resource-badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            background: rgba(41, 64, 51, 0.08);
            color: var(--primary-green);
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1rem;
        }

        /* Scroll Progress Indicator */
        .progress-bar {
            position: fixed;
            top: 0;
            left: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-green), var(--accent-green));
            z-index: 9999;
            transform-origin: left;
            transform: scaleX(0);
        }

        /* Footer Attribution */
        .resources-footer {
            max-width: 1400px;
            margin: 4rem auto 0;
            padding: 3rem;
            text-align: center;
            color: var(--text-gray);
            border-top: 1px solid rgba(0, 0, 0, 0.06);
        }

        .resources-footer p {
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .resources-grid {
                grid-template-columns: 1fr;
            }
            
            .floor-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        /* Loading State */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--stone-white);
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
            width: 50px;
            height: 50px;
            border: 3px solid rgba(41, 64, 51, 0.1);
            border-top-color: var(--primary-green);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <div class="progress-bar" id="progressBar"></div>
    <div id="canvas-container"></div>

    <!-- Navigation -->
    <nav>
        <a href="engineer.php" class="nav-logo">
            <i class="far fa-building"></i>
            Constructa
        </a>
        <div class="nav-links">
            <a href="engineer.php" class="nav-btn">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="my_projects.php" class="nav-btn">
                <i class="fas fa-project-diagram"></i> Projects
            </a>
            <a href="login.html" class="nav-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <h1 class="hero-title">Engineer Resources</h1>
        <p class="hero-subtitle">
            Professional engineering references, building codes, design standards, and technical resources 
            for civil and structural engineering practice.
        </p>
    </section>

    <!-- Level 1: Codes & Standards -->
    <section class="floor-section" data-floor="1">
        <div class="floor-header">
            <div class="floor-number">01</div>
            <div>
                <h2 class="floor-title">Codes & Standards</h2>
                <p class="floor-description">Regulatory frameworks and building codes</p>
            </div>
        </div>
        <div class="resources-grid">
            <div class="resource-card">
                <span class="resource-badge">Indian Standard</span>
                <div class="resource-icon-wrapper">
                    <i class="fas fa-book resource-icon"></i>
                </div>
                <h3 class="resource-title">IS 456:2000 - Plain and Reinforced Concrete</h3>
                <p class="resource-description">
                    Code of practice for plain and reinforced concrete structures, covering design, construction, 
                    and quality control requirements.
                </p>
                <div class="resource-meta">
                    <span class="meta-item">
                        <i class="fas fa-file-pdf"></i>
                        PDF Document
                    </span>
                    <span class="meta-item">
                        <i class="fas fa-download"></i>
                        2.4k downloads
                    </span>
                </div>
                <div class="resource-actions">
                    <button class="resource-btn btn-primary" onclick="viewResource(1)">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <button class="resource-btn btn-secondary" onclick="downloadResource(1)">
                        <i class="fas fa-download"></i> Download
                    </button>
                </div>
            </div>

            <div class="resource-card">
                <span class="resource-badge">National Code</span>
                <div class="resource-icon-wrapper">
                    <i class="fas fa-landmark resource-icon"></i>
                </div>
                <h3 class="resource-title">NBC 2016 - National Building Code of India</h3>
                <p class="resource-description">
                    Comprehensive building regulations covering safety, construction practices, 
                    fire protection, and accessibility standards.
                </p>
                <div class="resource-meta">
                    <span class="meta-item">
                        <i class="fas fa-file-pdf"></i>
                        PDF Document
                    </span>
                    <span class="meta-item">
                        <i class="fas fa-download"></i>
                        3.1k downloads
                    </span>
                </div>
                <div class="resource-actions">
                    <button class="resource-btn btn-primary" onclick="viewResource(2)">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <button class="resource-btn btn-secondary" onclick="downloadResource(2)">
                        <i class="fas fa-download"></i> Download
                    </button>
                </div>
            </div>

            <div class="resource-card">
                <span class="resource-badge">Foundation Standards</span>
                <div class="resource-icon-wrapper">
                    <i class="fas fa-layer-group resource-icon"></i>
                </div>
                <h3 class="resource-title">IS 2950 - Foundation Design & Construction</h3>
                <p class="resource-description">
                    Standards for design and construction of raft, pile, and isolated foundations 
                    for different soil conditions.
                </p>
                <div class="resource-meta">
                    <span class="meta-item">
                        <i class="fas fa-file-pdf"></i>
                        PDF Document
                    </span>
                    <span class="meta-item">
                        <i class="fas fa-download"></i>
                        1.8k downloads
                    </span>
                </div>
                <div class="resource-actions">
                    <button class="resource-btn btn-primary" onclick="viewResource(3)">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <button class="resource-btn btn-secondary" onclick="downloadResource(3)">
                        <i class="fas fa-download"></i> Download
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Level 2: Design Manuals -->
    <section class="floor-section" data-floor="2">
        <div class="floor-header">
            <div class="floor-number">02</div>
            <div>
                <h2 class="floor-title">Design Manuals</h2>
                <p class="floor-description">Structural analysis and design guidelines</p>
            </div>
        </div>
        <div class="resources-grid">
            <div class="resource-card">
                <span class="resource-badge">Seismic Design</span>
                <div class="resource-icon-wrapper">
                    <i class="fas fa-wave-square resource-icon"></i>
                </div>
                <h3 class="resource-title">IS 1893 - Earthquake Resistant Design</h3>
                <p class="resource-description">
                    Criteria for earthquake-resistant design of structures including seismic zone factors 
                    and response spectrum method.
                </p>
                <div class="resource-meta">
                    <span class="meta-item">
                        <i class="fas fa-file-pdf"></i>
                        PDF Document
                    </span>
                    <span class="meta-item">
                        <i class="fas fa-download"></i>
                        2.7k downloads
                    </span>
                </div>
                <div class="resource-actions">
                    <button class="resource-btn btn-primary" onclick="viewResource(4)">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <button class="resource-btn btn-secondary" onclick="downloadResource(4)">
                        <i class="fas fa-download"></i> Download
                    </button>
                </div>
            </div>

            <div class="resource-card">
                <span class="resource-badge">Steel Structures</span>
                <div class="resource-icon-wrapper">
                    <i class="fas fa-building resource-icon"></i>
                </div>
                <h3 class="resource-title">IS 800 - Steel Structure Design Code</h3>
                <p class="resource-description">
                    General construction in steel covering limit state design, connections, 
                    and fabrication requirements.
                </p>
                <div class="resource-meta">
                    <span class="meta-item">
                        <i class="fas fa-file-pdf"></i>
                        PDF Document
                    </span>
                    <span class="meta-item">
                        <i class="fas fa-download"></i>
                        1.9k downloads
                    </span>
                </div>
                <div class="resource-actions">
                    <button class="resource-btn btn-primary" onclick="viewResource(5)">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <button class="resource-btn btn-secondary" onclick="downloadResource(5)">
                        <i class="fas fa-download"></i> Download
                    </button>
                </div>
            </div>

            <div class="resource-card">
                <span class="resource-badge">Wind Loads</span>
                <div class="resource-icon-wrapper">
                    <i class="fas fa-wind resource-icon"></i>
                </div>
                <h3 class="resource-title">IS 875 - Design Loads for Buildings</h3>
                <p class="resource-description">
                    Dead loads, imposed loads, wind loads, snow loads, and special loads 
                    for structural design calculations.
                </p>
                <div class="resource-meta">
                    <span class="meta-item">
                        <i class="fas fa-file-pdf"></i>
                        PDF Document
                    </span>
                    <span class="meta-item">
                        <i class="fas fa-download"></i>
                        2.2k downloads
                    </span>
                </div>
                <div class="resource-actions">
                    <button class="resource-btn btn-primary" onclick="viewResource(6)">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <button class="resource-btn btn-secondary" onclick="downloadResource(6)">
                        <i class="fas fa-download"></i> Download
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Level 3: Calculations & Tools -->
    <section class="floor-section" data-floor="3">
        <div class="floor-header">
            <div class="floor-number">03</div>
            <div>
                <h2 class="floor-title">Calculations & Tools</h2>
                <p class="floor-description">Engineering calculation aids and software utilities</p>
            </div>
        </div>
        <div class="resources-grid">
            <div class="resource-card">
                <span class="resource-badge">Engineering Tool</span>
                <div class="resource-icon-wrapper">
                    <i class="fas fa-calculator resource-icon"></i>
                </div>
                <h3 class="resource-title">Structural Analysis Calculator</h3>
                <p class="resource-description">
                    Beam deflection, moment distribution, shear force, and bending moment 
                    calculation utility for standard loading conditions.
                </p>
                <div class="resource-meta">
                    <span class="meta-item">
                        <i class="fas fa-desktop"></i>
                        Web Application
                    </span>
                    <span class="meta-item">
                        <i class="fas fa-users"></i>
                        1.5k users
                    </span>
                </div>
                <div class="resource-actions">
                    <button class="resource-btn btn-view" onclick="openTool(1)">
                        <i class="fas fa-external-link-alt"></i> Open Tool
                    </button>
                </div>
            </div>

            <div class="resource-card">
                <span class="resource-badge">Cost Estimation</span>
                <div class="resource-icon-wrapper">
                    <i class="fas fa-file-invoice-dollar resource-icon"></i>
                </div>
                <h3 class="resource-title">BOQ Generator & Cost Estimator</h3>
                <p class="resource-description">
                    Automated bill of quantities generation with current market rates, 
                    labor analysis, and material cost estimation.
                </p>
                <div class="resource-meta">
                    <span class="meta-item">
                        <i class="fas fa-desktop"></i>
                        Web Application
                    </span>
                    <span class="meta-item">
                        <i class="fas fa-users"></i>
                        2.1k users
                    </span>
                </div>
                <div class="resource-actions">
                    <button class="resource-btn btn-view" onclick="openTool(2)">
                        <i class="fas fa-external-link-alt"></i> Open Tool
                    </button>
                </div>
            </div>

            <div class="resource-card">
                <span class="resource-badge">Reference Tables</span>
                <div class="resource-icon-wrapper">
                    <i class="fas fa-table resource-icon"></i>
                </div>
                <h3 class="resource-title">Load & Material Properties Tables</h3>
                <p class="resource-description">
                    Comprehensive reference tables for material properties, safe bearing capacity, 
                    and standard load assumptions.
                </p>
                <div class="resource-meta">
                    <span class="meta-item">
                        <i class="fas fa-file-pdf"></i>
                        PDF Document
                    </span>
                    <span class="meta-item">
                        <i class="fas fa-download"></i>
                        3.4k downloads
                    </span>
                </div>
                <div class="resource-actions">
                    <button class="resource-btn btn-primary" onclick="viewResource(7)">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <button class="resource-btn btn-secondary" onclick="downloadResource(7)">
                        <i class="fas fa-download"></i> Download
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Level 4: Safety & Compliance -->
    <section class="floor-section" data-floor="4">
        <div class="floor-header">
            <div class="floor-number">04</div>
            <div>
                <h2 class="floor-title">Safety & Compliance</h2>
                <p class="floor-description">Site safety protocols and quality assurance</p>
            </div>
        </div>
        <div class="resources-grid">
            <div class="resource-card">
                <span class="resource-badge">Safety Manual</span>
                <div class="resource-icon-wrapper">
                    <i class="fas fa-hard-hat resource-icon"></i>
                </div>
                <h3 class="resource-title">Construction Site Safety Guidelines</h3>
                <p class="resource-description">
                    Comprehensive safety protocols, PPE requirements, emergency procedures, 
                    and hazard identification for construction sites.
                </p>
                <div class="resource-meta">
                    <span class="meta-item">
                        <i class="fas fa-file-pdf"></i>
                        PDF Document
                    </span>
                    <span class="meta-item">
                        <i class="fas fa-download"></i>
                        2.8k downloads
                    </span>
                </div>
                <div class="resource-actions">
                    <button class="resource-btn btn-primary" onclick="viewResource(8)">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <button class="resource-btn btn-secondary" onclick="downloadResource(8)">
                        <i class="fas fa-download"></i> Download
                    </button>
                </div>
            </div>

            <div class="resource-card">
                <span class="resource-badge">Quality Control</span>
                <div class="resource-icon-wrapper">
                    <i class="fas fa-clipboard-check resource-icon"></i>
                </div>
                <h3 class="resource-title">QA/QC Inspection Checklists</h3>
                <p class="resource-description">
                    Stage-wise quality assurance and quality control checklists for concrete, 
                    steel, masonry, and finishing works.
                </p>
                <div class="resource-meta">
                    <span class="meta-item">
                        <i class="fas fa-file-pdf"></i>
                        PDF Document
                    </span>
                    <span class="meta-item">
                        <i class="fas fa-download"></i>
                        1.6k downloads
                    </span>
                </div>
                <div class="resource-actions">
                    <button class="resource-btn btn-primary" onclick="viewResource(9)">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <button class="resource-btn btn-secondary" onclick="downloadResource(9)">
                        <i class="fas fa-download"></i> Download
                    </button>
                </div>
            </div>

            <div class="resource-card">
                <span class="resource-badge">Material Testing</span>
                <div class="resource-icon-wrapper">
                    <i class="fas fa-vial resource-icon"></i>
                </div>
                <h3 class="resource-title">Material Testing Procedures & Standards</h3>
                <p class="resource-description">
                    Testing methods for concrete, steel, aggregates, soil, and other construction 
                    materials as per IS standards.
                </p>
                <div class="resource-meta">
                    <span class="meta-item">
                        <i class="fas fa-file-pdf"></i>
                        PDF Document
                    </span>
                    <span class="meta-item">
                        <i class="fas fa-download"></i>
                        1.4k downloads
                    </span>
                </div>
                <div class="resource-actions">
                    <button class="resource-btn btn-primary" onclick="viewResource(10)">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <button class="resource-btn btn-secondary" onclick="downloadResource(10)">
                        <i class="fas fa-download"></i> Download
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Level 5: Sustainability -->
    <section class="floor-section" data-floor="5">
        <div class="floor-header">
            <div class="floor-number">05</div>
            <div>
                <h2 class="floor-title">Sustainability & Green Building</h2>
                <p class="floor-description">Environmental standards and sustainable practices</p>
            </div>
        </div>
        <div class="resources-grid">
            <div class="resource-card">
                <span class="resource-badge">Green Certification</span>
                <div class="resource-icon-wrapper">
                    <i class="fas fa-leaf resource-icon"></i>
                </div>
                <h3 class="resource-title">LEED & GRIHA Certification Guidelines</h3>
                <p class="resource-description">
                    Green building certification requirements, sustainable design strategies, 
                    and environmental performance standards.
                </p>
                <div class="resource-meta">
                    <span class="meta-item">
                        <i class="fas fa-file-pdf"></i>
                        PDF Document
                    </span>
                    <span class="meta-item">
                        <i class="fas fa-download"></i>
                        1.2k downloads
                    </span>
                </div>
                <div class="resource-actions">
                    <button class="resource-btn btn-primary" onclick="viewResource(11)">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <button class="resource-btn btn-secondary" onclick="downloadResource(11)">
                        <i class="fas fa-download"></i> Download
                    </button>
                </div>
            </div>

            <div class="resource-card">
                <span class="resource-badge">Energy Efficiency</span>
                <div class="resource-icon-wrapper">
                    <i class="fas fa-solar-panel resource-icon"></i>
                </div>
                <h3 class="resource-title">Energy Conservation Building Code (ECBC)</h3>
                <p class="resource-description">
                    Energy efficiency standards for commercial buildings, HVAC design, 
                    lighting, and renewable energy integration.
                </p>
                <div class="resource-meta">
                    <span class="meta-item">
                        <i class="fas fa-file-pdf"></i>
                        PDF Document
                    </span>
                    <span class="meta-item">
                        <i class="fas fa-download"></i>
                        980 downloads
                    </span>
                </div>
                <div class="resource-actions">
                    <button class="resource-btn btn-primary" onclick="viewResource(12)">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <button class="resource-btn btn-secondary" onclick="downloadResource(12)">
                        <i class="fas fa-download"></i> Download
                    </button>
                </div>
            </div>

            <div class="resource-card">
                <span class="resource-badge">Waste Management</span>
                <div class="resource-icon-wrapper">
                    <i class="fas fa-recycle resource-icon"></i>
                </div>
                <h3 class="resource-title">Construction Waste Management Plan</h3>
                <p class="resource-description">
                    Guidelines for reducing, reusing, and recycling construction waste, 
                    sustainable material sourcing, and disposal practices.
                </p>
                <div class="resource-meta">
                    <span class="meta-item">
                        <i class="fas fa-file-pdf"></i>
                        PDF Document
                    </span>
                    <span class="meta-item">
                        <i class="fas fa-download"></i>
                        756 downloads
                    </span>
                </div>
                <div class="resource-actions">
                    <button class="resource-btn btn-primary" onclick="viewResource(13)">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <button class="resource-btn btn-secondary" onclick="downloadResource(13)">
                        <i class="fas fa-download"></i> Download
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="resources-footer">
        <p>All resources are provided for educational and professional reference purposes.</p>
        <p style="color: var(--primary-green); font-weight: 600;">
            © 2026 Constructa Engineering Platform
        </p>
    </footer>

    <script>
        // Initialize 3D Background (Same as Landing Page)
        function init3DBackground() {
            const container = document.getElementById('canvas-container');
            const scene = new THREE.Scene();
            scene.background = new THREE.Color('#f6f7f2');
            
            const camera = new THREE.PerspectiveCamera(
                60,
                window.innerWidth / window.innerHeight,
                0.1,
                1000
            );
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

            // Wireframe City
            const cityGroup = new THREE.Group();
            const buildingMaterial = new THREE.MeshPhongMaterial({
                color: 0x294033,
                transparent: true,
                opacity: 0.1,
                side: THREE.DoubleSide
            });

            const edgeMaterial = new THREE.LineBasicMaterial({
                color: 0x294033,
                transparent: true,
                opacity: 0.3
            });

            for (let i = 0; i < 25; i++) {
                const height = Math.random() * 8 + 2;
                const width = Math.random() * 1.5 + 0.8;
                const depth = Math.random() * 1.5 + 0.8;

                const geometry = new THREE.BoxGeometry(width, height, depth);
                const building = new THREE.Mesh(geometry, buildingMaterial);

                building.position.set(
                    (Math.random() - 0.5) * 40,
                    height / 2 - 3,
                    (Math.random() - 0.5) * 40
                );

                const edges = new THREE.EdgesGeometry(geometry);
                const lineSegments = new THREE.LineSegments(edges, edgeMaterial);
                lineSegments.position.copy(building.position);

                cityGroup.add(building);
                cityGroup.add(lineSegments);
            }

            scene.add(cityGroup);

            // Animation
            function animate() {
                requestAnimationFrame(animate);
                cityGroup.rotation.y += 0.0003;
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

        // Scroll Progress Bar
        function updateScrollProgress() {
            const progressBar = document.getElementById('progressBar');
            const scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
            const scrolled = window.scrollY;
            const progress = (scrolled / scrollHeight);
            progressBar.style.transform = `scaleX(${progress})`;
        }

        // Floor Section Reveal on Scroll
        function revealSections() {
            const sections = document.querySelectorAll('.floor-section');
            sections.forEach(section => {
                const rect = section.getBoundingClientRect();
                const isVisible = rect.top < window.innerHeight * 0.8;
                if (isVisible) {
                    section.classList.add('visible');
                }
            });
        }

        // ============================================
        // ENGINEERING RESOURCE CONTENT DATABASE
        // ============================================
        
        const engineeringResources = {
            1: {
                title: "IS 456:2000 - Plain and Reinforced Concrete",
                domain: "Structural Engineering",
                stage: "Design Phase",
                version: "IS 456:2000 (Reaffirmed 2021)",
                formulas: [
                    {
                        name: "Ultimate Moment Capacity (Singly Reinforced)",
                        formula: "M<sub>u</sub> = 0.87 f<sub>y</sub> A<sub>st</sub> d [1 - (f<sub>y</sub> A<sub>st</sub>)/(f<sub>ck</sub> b d)]",
                        symbols: {
                            "M<sub>u</sub>": "Ultimate moment of resistance (kN·m)",
                            "f<sub>y</sub>": "Characteristic strength of steel (N/mm²)",
                            "A<sub>st</sub>": "Area of tension reinforcement (mm²)",
                            "d": "Effective depth of section (mm)",
                            "f<sub>ck</sub>": "Characteristic compressive strength of concrete (N/mm²)",
                            "b": "Width of beam (mm)"
                        },
                        explanation: "This formula calculates the ultimate moment capacity of a singly reinforced rectangular beam based on limit state method."
                    },
                    {
                        name: "Maximum Spacing of Reinforcement",
                        formula: "S<sub>max</sub> = min(3d, 300mm)",
                        symbols: {
                            "S<sub>max</sub>": "Maximum spacing between bars (mm)",
                            "d": "Effective depth (mm)"
                        },
                        explanation: "Maximum permissible spacing to control crack width and ensure load distribution."
                    }
                ],
                workedExample: {
                    problem: "Calculate the ultimate moment capacity of a beam with b=300mm, d=450mm, M25 concrete, Fe415 steel, and A<sub>st</sub>=942mm² (3 bars of 20mm dia)",
                    solution: `Given: b = 300mm, d = 450mm, f<sub>ck</sub> = 25 N/mm², f<sub>y</sub> = 415 N/mm², A<sub>st</sub> = 942mm²\\n\\n
M<sub>u</sub> = 0.87 × 415 × 942 × 450 [1 - (415 × 942)/(25 × 300 × 450)]\\n
M<sub>u</sub> = 152,523,450 [1 - 0.116]\\n
M<sub>u</sub> = 134,830,730 N·mm\\n
M<sub>u</sub> = 134.83 kN·m\\n\\n
Therefore, the ultimate moment capacity is 134.83 kN·m`
                },
                standardCode: "IS 456:2000",
                relatedCodes: ["IS 875 (Loads)", "IS 13920 (Seismic)", "SP 16 (Design Aids)"]
            },
            2: {
                title: "NBC 2016 - National Building Code of India",
                domain: "General Construction",
                stage: "Approval & Design",
                version: "NBC 2016",
                formulas: [
                    {
                        name: "Minimum Room Height",
                        formula: "h<sub>min</sub> = 2.75m (habitable), 2.40m (kitchen/bath)",
                        symbols: {
                            "h<sub>min</sub>": "Minimum clear height (m)"
                        },
                        explanation: "Minimum clear height requirements for different room types as per NBC 2016."
                    },
                    {
                        name: "Ventilation Area Requirement",
                        formula: "A<sub>vent</sub> ≥ A<sub>floor</sub> / 10",
                        symbols: {
                            "A<sub>vent</sub>": "Minimum ventilation opening area (m²)",
                            "A<sub>floor</sub>": "Floor area of room (m²)"
                        },
                        explanation: "Minimum ventilation opening should be 1/10th of floor area for habitable rooms."
                    }
                ],
                workedExample: {
                    problem: "Calculate minimum ventilation required for a room of 4m × 5m",
                    solution: `Given: Room dimensions = 4m × 5m\\n
A<sub>floor</sub> = 4 × 5 = 20 m²\\n
A<sub>vent</sub> = 20 / 10 = 2 m²\\n\\n
Therefore, minimum ventilation opening = 2 m²`
                },
                standardCode: "NBC 2016",
                relatedCodes: ["Fire Safety Code", "Town Planning Regulations"]
            },
            3: {
                title: "IS 2950 - Foundation Design & Construction",
                domain: "Geotechnical & Structural",
                stage: "Design Phase",
                version: "IS 2950:1981 (Parts 1-3)",
                formulas: [
                    {
                        name: "Safe Bearing Capacity (Rankine's Formula)",
                        formula: "q<sub>safe</sub> = γ D<sub>f</sub> N<sub>q</sub> + 0.5 γ B N<sub>γ</sub> + c N<sub>c</sub>",
                        symbols: {
                            "q<sub>safe</sub>": "Safe bearing capacity (kN/m²)",
                            "γ": "Unit weight of soil (kN/m³)",
                            "D<sub>f</sub>": "Depth of foundation (m)",
                            "N<sub>q</sub>, N<sub>γ</sub>, N<sub>c</sub>": "Bearing capacity factors",
                            "B": "Width of foundation (m)",
                            "c": "Cohesion of soil (kN/m²)"
                        },
                        explanation: "General bearing capacity formula for shallow foundations accounting for depth, width, and soil cohesion."
                    }
                ],
                workedExample: {
                    problem: "Calculate safe bearing capacity for medium dense sand: γ=18kN/m³, φ=30°, D<sub>f</sub>=1.5m, B=2m, FOS=3",
                    solution: `Given: γ = 18 kN/m³, φ = 30°, D<sub>f</sub> = 1.5m, B = 2m\\n
For φ = 30°: N<sub>q</sub> = 18.4, N<sub>γ</sub> = 15.7, N<sub>c</sub> = 30.1\\n\\n
q<sub>ult</sub> = 18 × 1.5 × 18.4 + 0.5 × 18 × 2 × 15.7 + 0\\n
q<sub>ult</sub> = 497.6 + 282.6 = 780.2 kN/m²\\n\\n
q<sub>safe</sub> = 780.2 / 3 = 260 kN/m²`
                },
                standardCode: "IS 2950:1981",
                relatedCodes: ["IS 6403 (Soil Testing)", "IS 456 (RCC Design)"]
            },
            4: {
                title: "IS 1893 - Earthquake Resistant Design",
                domain: "Structural (Seismic)",
                stage: "Design Phase",
                version: "IS 1893 (Part 1): 2016",
                formulas: [
                    {
                        name: "Design Seismic Base Shear",
                        formula: "V<sub>B</sub> = A<sub>h</sub> × W",
                        symbols: {
                            "V<sub>B</sub>": "Design seismic base shear (kN)",
                            "A<sub>h</sub>": "Design horizontal acceleration spectrum value",
                            "W": "Seismic weight of building (kN)"
                        },
                        explanation: "Total horizontal seismic force acting at the base of the structure."
                    },
                    {
                        name: "Design Horizontal Acceleration Spectrum",
                        formula: "A<sub>h</sub> = (Z/2) × (I/R) × (S<sub>a</sub>/g)",
                        symbols: {
                            "Z": "Zone factor (0.10 to 0.36 for Zones II to V)",
                            "I": "Importance factor (1.0 to 1.5)",
                            "R": "Response reduction factor (3 to 5)",
                            "S<sub>a</sub>/g": "Average response acceleration coefficient"
                        },
                        explanation: "Design horizontal seismic coefficient considering zone, importance, and structural system."
                    }
                ],
                workedExample: {
                    problem: "Calculate base shear for a building in Zone IV: W=5000kN, I=1.0, R=5, S<sub>a</sub>/g=2.5",
                    solution: `Given: Z = 0.24 (Zone IV), I = 1.0, R = 5, S<sub>a</sub>/g = 2.5, W = 5000kN\\n\\n
A<sub>h</sub> = (0.24/2) × (1.0/5) × 2.5 = 0.06\\n
V<sub>B</sub> = 0.06 × 5000 = 300 kN\\n\\n
Therefore, design base shear = 300 kN`
                },
                standardCode: "IS 1893:2016",
                relatedCodes: ["IS 13920 (Ductile Detailing)", "IS 4326 (Earthquake Effects)"]
            },
            5: {
                title: "IS 800 - Steel Structure Design Code",
                domain: "Structural (Steel)",
                stage: "Design Phase",
                version: "IS 800:2007",
                formulas: [
                    {
                        name: "Design Strength of Tension Member",
                        formula: "T<sub>d</sub> = min(A<sub>g</sub>f<sub>y</sub>/γ<sub>m0</sub>, 0.9A<sub>n</sub>f<sub>u</sub>/γ<sub>m1</sub>)",
                        symbols: {
                            "T<sub>d</sub>": "Design tensile strength (kN)",
                            "A<sub>g</sub>": "Gross area of cross-section (mm²)",
                            "A<sub>n</sub>": "Net effective area (mm²)",
                            "f<sub>y</sub>": "Yield stress (N/mm²)",
                            "f<sub>u</sub>": "Ultimate stress (N/mm²)",
                            "γ<sub>m0</sub>, γ<sub>m1</sub>": "Partial safety factors (1.10, 1.25)"
                        },
                        explanation: "Design tensile strength is minimum of yielding of gross section and rupture of net section."
                    }
                ],
                workedExample: {
                    problem: "Calculate design strength for ISA 100×100×10mm: A<sub>g</sub>=1903mm², A<sub>n</sub>=1750mm², f<sub>y</sub>=250N/mm², f<sub>u</sub>=410N/mm²",
                    solution: `Given: A<sub>g</sub> = 1903mm², A<sub>n</sub> = 1750mm², f<sub>y</sub> = 250 N/mm², f<sub>u</sub> = 410 N/mm²\\n\\n
T<sub>dg</sub> = (1903 × 250) / 1.10 = 432,500 N = 432.5 kN\\n
T<sub>dn</sub> = (0.9 × 1750 × 410) / 1.25 = 517,860 N = 517.9 kN\\n\\n
T<sub>d</sub> = min(432.5, 517.9) = 432.5 kN`
                },
                standardCode: "IS 800:2007",
                relatedCodes: ["IS 807 (Structural Steel Sections)", "IS 2062 (Steel Grades)"]
            },
            6: {
                title: "IS 875 - Design Loads for Buildings",
                domain: "Structural (Loads)",
                stage: "Design Phase",
                version: "IS 875 (Parts 1-5):1987",
                formulas: [
                    {
                        name: "Dead Load (Part 1)",
                        formula: "DL = Σ(Volume × Unit Weight)",
                        symbols: {
                            "DL": "Total dead load (kN)",
                            "Volume": "Volume of material (m³)",
                            "Unit Weight": "Unit weight (kN/m³): RCC=25, Brick=20, Steel=78.5"
                        },
                        explanation: "Dead load is the weight of all permanent construction."
                    },
                    {
                        name: "Live Load on Floors (Part 2)",
                        formula: "LL = Imposed load per m²",
                        symbols: {
                            "LL": "Live load intensity (kN/m²): Residential=2.0, Office=3.0, Assembly=4.0"
                        },
                        explanation: "Imposed load depends on occupancy classification."
                    },
                    {
                        name: "Wind Pressure (Part 3)",
                        formula: "p<sub>z</sub> = 0.6 V<sub>z</sub>²",
                        symbols: {
                            "p<sub>z</sub>": "Wind pressure at height z (N/m²)",
                            "V<sub>z</sub>": "Design wind speed at height z (m/s)"
                        },
                        explanation: "Design wind pressure varies with height and basic wind speed."
                    }
                ],
                workedExample: {
                    problem: "Calculate dead load of RCC slab: 5m × 4m × 0.15m thick",
                    solution: `Given: Length = 5m, Width = 4m, Thickness = 0.15m, γ<sub>RCC</sub> = 25 kN/m³\\n\\n
Volume = 5 × 4 × 0.15 = 3.0 m³\\n
DL = 3.0 × 25 = 75 kN\\n\\n
Therefore, dead load of slab = 75 kN`
                },
                standardCode: "IS 875:1987",
                relatedCodes: ["IS 456 (RCC)", "IS 800 (Steel)", "IS 1893 (Seismic)"]
            },
            7: {
                title: "Load & Material Properties Tables",
                domain: "General Structural",
                stage: "Design & Estimation",
                version: "Consolidated Reference Tables",
                formulas: [
                    {
                        name: "Concrete Grades",
                        formula: "f<sub>ck</sub> values: M15=15, M20=20, M25=25, M30=30, M35=35 N/mm²",
                        symbols: {
                            "f<sub>ck</sub>": "Characteristic compressive strength at 28 days (N/mm²)"
                        },
                        explanation: "Standard concrete grades used in Indian construction practice."
                    },
                    {
                        name: "Steel Grades",
                        formula: "f<sub>y</sub> values: Fe250=250, Fe415=415, Fe500=500, Fe550=550 N/mm²",
                        symbols: {
                            "f<sub>y</sub>": "Yield strength of reinforcement steel (N/mm²)"
                        },
                        explanation: "Standard reinforcement steel grades as per IS 1786."
                    }
                ],
                workedExample: {
                    problem: "Select appropriate materials for a 3-storey residential building beam",
                    solution: `Recommended: M25 concrete + Fe415 steel\\n\\n
Reasoning:\\n
- M25 provides adequate strength for residential loads\\n
- Fe415 is economical and widely available\\n
- Suitable for moderate exposure conditions\\n
- Meets IS 456 requirements for deflection control`
                },
                standardCode: "Multiple IS Codes",
                relatedCodes: ["IS 456", "IS 1786", "IS 383"]
            },
            8: {
                title: "Construction Site Safety Guidelines",
                domain: "Site Safety",
                stage: "Execution Phase",
                version: "IS 3764:1992 + BOCW Act 1996",
                formulas: [
                    {
                        name: "Safe Working Load (Scaffolding)",
                        formula: "SWL = Load capacity / Safety Factor (min 4)",
                        symbols: {
                            "SWL": "Safe working load (kg)",
                            "Safety Factor": "Minimum 4 for scaffolding systems"
                        },
                        explanation: "Maximum safe load that can be placed on scaffolding."
                    },
                    {
                        name: "Excavation Slope",
                        formula: "Slope = 1:1 (cohesive soil), 1:1.5 (loose soil)",
                        symbols: {
                            "Slope": "Side slope ratio (vertical:horizontal)"
                        },
                        explanation: "Required slope to prevent collapse during excavation."
                    }
                ],
                workedExample: {
                    problem: "Calculate SWL for scaffold with 2000kg capacity",
                    solution: `Given: Scaffold capacity = 2000 kg, SF = 4\\n\\n
SWL = 2000 / 4 = 500 kg\\n\\n
Therefore, maximum safe working load = 500 kg`
                },
                standardCode: "IS 3764:1992",
                relatedCodes: ["BOCW Act 1996", "IS 14489 (PPE)"]
            },
            9: {
                title: "QA/QC Inspection Checklists",
                domain: "Quality Control",
                stage: "Execution & Inspection",
                version: "IS 456:2000 Quality Standards",
                formulas: [
                    {
                        name: "Concrete Cube Sampling",
                        formula: "Samples = 1 per 50m³ (minimum 1 per day)",
                        symbols: {
                            "Samples": "Number of cube sets to be cast"
                        },
                        explanation: "Minimum sampling frequency for concrete quality testing."
                    },
                    {
                        name: "Slump Test Acceptance",
                        formula: "Slump = 25-150mm (based on mix design)",
                        symbols: {
                            "Slump": "Concrete workability measurement (mm)"
                        },
                        explanation: "Acceptable slump range for different applications."
                    }
                ],
                workedExample: {
                    problem: "Determine sampling for 200m³ concrete pour",
                    solution: `Given: Concrete volume = 200m³\\n\\n
Samples = 200 / 50 = 4 sets minimum\\n
Each set = 3 cubes\\n
Total cubes = 4 × 3 = 12 cubes\\n\\n
Testing: 7-day and 28-day compressive strength`
                },
                standardCode: "IS 456:2000",
                relatedCodes: ["IS 516 (Cube Testing)", "IS 1199 (Concrete Sampling)"]
            },
            10: {
                title: "Material Testing Procedures & Standards",
                domain: "Laboratory Testing",
                stage: "Quality Verification",
                version: "Multiple IS Testing Standards",
                formulas: [
                    {
                        name: "Compressive Strength of Concrete",
                        formula: "f<sub>ck</sub> = Load / Area",
                        symbols: {
                            "f<sub>ck</sub>": "Compressive strength (N/mm²)",
                            "Load": "Maximum load at failure (N)",
                            "Area": "Cross-sectional area = 150×150 mm² (standard cube)"
                        },
                        explanation: "Concrete cube test as per IS 516."
                    },
                    {
                        name: "Steel Bar Tensile Strength",
                        formula: "f<sub>u</sub> = P<sub>max</sub> / A<sub>0</sub>",
                        symbols: {
                            "f<sub>u</sub>": "Ultimate tensile strength (N/mm²)",
                            "P<sub>max</sub>": "Maximum load (N)",
                            "A<sub>0</sub>": "Original cross-sectional area (mm²)"
                        },
                        explanation: "Tensile test for steel reinforcement as per IS 1608."
                    }
                ],
                workedExample: {
                    problem: "Calculate concrete strength: Cube failure load = 562.5kN, size = 150mm",
                    solution: `Given: Load = 562,500 N, Area = 150 × 150 = 22,500 mm²\\n\\n
f<sub>ck</sub> = 562,500 / 22,500 = 25 N/mm²\\n\\n
Grade achieved: M25 concrete`
                },
                standardCode: "IS 516:1959",
                relatedCodes: ["IS 1608 (Steel Testing)", "IS 2720 (Soil Testing)"]
            },
            11: {
                title: "LEED & GRIHA Certification Guidelines",
                domain: "Green Building",
                stage: "Design & Documentation",
                version: "LEED v4 + GRIHA 2019",
                formulas: [
                    {
                        name: "Energy Performance Index (EPI)",
                        formula: "EPI = Annual Energy Consumption / Built-up Area",
                        symbols: {
                            "EPI": "Energy performance index (kWh/m²/year)",
                            "Energy": "Total annual energy consumption (kWh/year)",
                            "Area": "Total built-up area (m²)"
                        },
                        explanation: "Lower EPI indicates better energy efficiency."
                    },
                    {
                        name: "Water Use Reduction Target",
                        formula: "Reduction% = (Baseline - Actual) / Baseline × 100",
                        symbols: {
                            "Baseline": "Standard water consumption",
                            "Actual": "Designed water consumption"
                        },
                        explanation: "Minimum 20% reduction required for LEED points."
                    }
                ],
                workedExample: {
                    problem: "Calculate EPI: Building area=5000m², Annual consumption=500,000kWh",
                    solution: `Given: Energy = 500,000 kWh/year, Area = 5000 m²\\n\\n
EPI = 500,000 / 5000 = 100 kWh/m²/year\\n\\n
GRIHA Rating: < 50 = 5 star, 50-100 = 4 star, >100 = 3 star\\n
This building achieves 4-star rating`
                },
                standardCode: "LEED v4, GRIHA 2019",
                relatedCodes: ["ECBC", "NBC Green Building Code"]
            },
            12: {
                title: "Energy Conservation Building Code (ECBC)",
                domain: "Building Services",
                stage: "Design & Compliance",
                version: "ECBC 2017",
                formulas: [
                    {
                        name: "Building Envelope Performance Index (BEPI)",
                        formula: "BEPI = Σ(U<sub>i</sub> × A<sub>i</sub>) / A<sub>total</sub>",
                        symbols: {
                            "BEPI": "Building envelope performance (W/m²·K)",
                            "U<sub>i</sub>": "U-value of component i (W/m²·K)",
                            "A<sub>i</sub>": "Area of component i (m²)",
                            "A<sub>total</sub>": "Total envelope area (m²)"
                        },
                        explanation: "Overall thermal performance of building envelope."
                    },
                    {
                        name: "Window-to-Wall Ratio (WWR)",
                        formula: "WWR = A<sub>window</sub> / A<sub>wall</sub> × 100",
                        symbols: {
                            "WWR": "Window-to-wall ratio (%)",
                            "A<sub>window</sub>": "Total window area (m²)",
                            "A<sub>wall</sub>": "Total wall area (m²)"
                        },
                        explanation: "Maximum WWR limits: 40% (ECBC), 30% (ECBC+), 25% (Super ECBC)."
                    }
                ],
                workedExample: {
                    problem: "Check WWR compliance: Wall=200m², Windows=70m²",
                    solution: `Given: A<sub>wall</sub> = 200m², A<sub>window</sub> = 70m²\\n\\n
WWR = (70 / 200) × 100 = 35%\\n\\n
Compliance:\\n
✓ ECBC (40%) - Compliant\\n
✗ ECBC+ (30%) - Non-compliant\\n
Recommendation: Reduce window area to 60m² for ECBC+`
                },
                standardCode: "ECBC 2017",
                relatedCodes: ["NBC", "ASHRAE 90.1"]
            },
            13: {
                title: "Construction Waste Management Plan",
                domain: "Environmental Management",
                stage: "Execution Phase",
                version: "Construction & Demolition Waste Rules 2016",
                formulas: [
                    {
                        name: "Waste Generation Estimate",
                        formula: "Waste = Built-up Area × 40-60 kg/m²",
                        symbols: {
                            "Waste": "Total C&D waste generated (kg)",
                            "Factor": "40-60 kg/m² for typical construction"
                        },
                        explanation: "Estimated waste generation for new construction."
                    },
                    {
                        name: "Diversion Rate",
                        formula: "Diversion% = (Recycled + Reused) / Total Waste × 100",
                        symbols: {
                            "Diversion%": "Waste diversion percentage",
                            "Target": "Minimum 75% for LEED certification"
                        },
                        explanation: "Percentage of waste diverted from landfill."
                    }
                ],
                workedExample: {
                    problem: "Calculate waste for 10,000m² building project",
                    solution: `Given: Built-up area = 10,000m², Factor = 50 kg/m²\\n\\n
Total Waste = 10,000 × 50 = 500,000 kg = 500 tonnes\\n\\n
75% Diversion Target = 375 tonnes\\n
Breakdown: Recycling=300t, Reuse=75t, Landfill=125t`
                },
                standardCode: "C&D Waste Rules 2016",
                relatedCodes: ["Solid Waste Management Rules", "Environment Protection Act"]
            }
        };

        // ============================================
        // 1. VIEW - QUICK REFERENCE (READ-ONLY PREVIEW)
        // ============================================
        
        function viewResource(id) {
            const resource = engineeringResources[id];
            if (!resource) {
                alert('Resource not found');
                return;
            }
            
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.75);
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow-y: auto;
                padding: 2rem 0;
            `;
            
            // Generate formula HTML
            let formulasHTML = '';
            resource.formulas.forEach((formula, idx) => {
                let symbolsHTML = '';
                for (const [symbol, meaning] of Object.entries(formula.symbols)) {
                    symbolsHTML += `<div style="margin: 0.5rem 0; padding-left: 1rem;">
                        <span style="font-family: 'Courier New', monospace; font-weight: 700; color: #294033;">${symbol}</span>
                        <span style="color: #64748b;"> = ${meaning}</span>
                    </div>`;
                }
                
                formulasHTML += `
                    <div style="background: ${idx % 2 === 0 ? '#f6f7f2' : '#e8f5e9'}; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid #294033;">
                        <h4 style="color: #294033; margin-bottom: 0.75rem; font-size: 1.1rem;">${formula.name}</h4>
                        <div style="background: white; padding: 1rem; border-radius: 6px; margin: 1rem 0; font-family: 'Courier New', monospace; font-size: 1.05rem; color: #2c3e50;">
                            ${formula.formula}
                        </div>
                        <div style="margin: 1rem 0;">
                            <strong style="color: #2c3e50; font-size: 0.9rem;">Where:</strong>
                            ${symbolsHTML}
                        </div>
                        <p style="color: #64748b; font-size: 0.9rem; margin-top: 0.75rem; font-style: italic;">
                            ${formula.explanation}
                        </p>
                    </div>
                `;
            });
            
            modal.innerHTML = `
                <div style="
                    background: white;
                    border-radius: 12px;
                    padding: 3rem;
                    max-width: 900px;
                    width: 90%;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
                    margin: auto;
                ">
                    <div style="border-bottom: 3px solid #294033; padding-bottom: 1.5rem; margin-bottom: 2rem;">
                        <h2 style="color: #294033; margin-bottom: 0.5rem; font-size: 2rem; line-height: 1.3;">
                            ${resource.title}
                        </h2>
                        <div style="display: flex; gap: 1.5rem; flex-wrap: wrap; margin-top: 1rem;">
                            <span style="background: #e8f5e9; color: #294033; padding: 0.4rem 0.8rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600;">
                                📐 ${resource.domain}
                            </span>
                            <span style="background: #fff8e1; color: #d97706; padding: 0.4rem 0.8rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600;">
                                🏗️ ${resource.stage}
                            </span>
                            <span style="background: #e3f2fd; color: #06b6d4; padding: 0.4rem 0.8rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600;">
                                📚 ${resource.version}
                            </span>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 2rem;">
                        <h3 style="color: #294033; margin-bottom: 1rem; font-size: 1.4rem; display: flex; align-items: center; gap: 0.5rem;">
                            📐 Key Formulas & Definitions
                        </h3>
                        ${formulasHTML}
                    </div>
                    
                    <div style="background: #fffbeb; padding: 2rem; border-radius: 8px; border-left: 4px solid #fbbf24; margin-bottom: 2rem;">
                        <h3 style="color: #294033; margin-bottom: 1rem; font-size: 1.4rem;">
                            📝 Worked Example
                        </h3>
                        <div style="background: white; padding: 1.5rem; border-radius: 6px; margin-bottom: 1rem;">
                            <strong style="color: #2c3e50; font-size: 1rem;">Problem:</strong>
                            <p style="color: #64748b; margin: 0.75rem 0; line-height: 1.7;">${resource.workedExample.problem}</p>
                        </div>
                        <div style="background: white; padding: 1.5rem; border-radius: 6px;">
                            <strong style="color: #294033; font-size: 1rem;">Solution:</strong>
                            <pre style="color: #2c3e50; margin: 0.75rem 0; line-height: 1.8; white-space: pre-wrap; font-family: 'Courier New', monospace; font-size: 0.9rem;">${resource.workedExample.solution}</pre>
                        </div>
                    </div>
                    
                    <div style="background: #e3f2fd; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
                        <h4 style="color: #294033; margin-bottom: 0.75rem; font-size: 1.1rem;">📚 Reference Standards</h4>
                        <div style="color: #2c3e50; line-height: 2;">
                            <strong>Primary Code:</strong> ${resource.standardCode}<br>
                            <strong>Related Codes:</strong> ${resource.relatedCodes.join(', ')}
                        </div>
                    </div>
                    
                    <div style="background: #fef2f2; padding: 1rem; border-radius: 6px; margin-bottom: 2rem; border: 1px solid #fca5a5;">
                        <p style="color: #991b1b; font-size: 0.85rem; margin: 0;">
                            <strong>⚠️ Disclaimer:</strong> This is a reference guide. All calculations must be verified by a licensed professional engineer. 
                            Site-specific conditions, local regulations, and safety factors must be considered.
                        </p>
                    </div>
                    
                    <button onclick="this.closest('div').parentElement.remove()" style="
                        background: #294033;
                        color: white;
                        border: none;
                        padding: 1rem 2rem;
                        border-radius: 8px;
                        font-weight: 700;
                        cursor: pointer;
                        width: 100%;
                        font-size: 1rem;
                    ">
                        Close Reference
                    </button>
                </div>
            `;
            
            document.body.appendChild(modal);
            modal.addEventListener('click', (e) => {
                if (e.target === modal) modal.remove();
            });
        }

        // ============================================
        // 2. DOWNLOAD - PDF DOCUMENTATION
        // ============================================
        
        function downloadResource(id) {
            const resource = engineeringResources[id];
            if (!resource) {
                alert('Resource not found');
                return;
            }
            
            // Show download notification
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 100px;
                right: 20px;
                background: white;
                border: 2px solid #294033;
                border-radius: 12px;
                padding: 1.5rem 2rem;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
                z-index: 9999;
                max-width: 400px;
            `;
            
            const fileName = `${resource.title.replace(/[^a-z0-9]/gi, '_')}_v${new Date().getFullYear()}.pdf`;
            
            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <i class="fas fa-file-pdf" style="color: #dc2626; font-size: 2rem;"></i>
                    <div>
                        <strong style="color: #294033; display: block; margin-bottom: 0.25rem; font-size: 1rem;">
                            Generating PDF Documentation
                        </strong>
                        <span style="color: #64748b; font-size: 0.85rem; display: block;">
                            ${fileName}
                        </span>
                        <div style="background: #e5e7eb; height: 4px; border-radius: 2px; margin-top: 0.5rem; overflow: hidden;">
                            <div class="progress-bar-fill" style="background: #294033; height: 100%; width: 0%; transition: width 2s ease;"></div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Animate progress bar
            setTimeout(() => {
                const progressBar = notification.querySelector('.progress-bar-fill');
                progressBar.style.width = '100%';
            }, 100);
            
            // Generate actual PDF using jsPDF
            setTimeout(() => {
                try {
                    const { jsPDF } = window.jspdf;
                    const doc = new jsPDF();
                    
                    let yPos = 20;
                    const pageWidth = doc.internal.pageSize.getWidth();
                    const pageHeight = doc.internal.pageSize.getHeight();
                    const margin = 20;
                    const contentWidth = pageWidth - (2 * margin);
                    
                    // Helper function to add new page if needed
                    function checkPageBreak(requiredSpace = 30) {
                        if (yPos + requiredSpace > pageHeight - 20) {
                            doc.addPage();
                            yPos = 20;
                            return true;
                        }
                        return false;
                    }
                    
                    // COVER PAGE
                    doc.setFontSize(28);
                    doc.setFont('helvetica', 'bold');
                    doc.setTextColor(41, 64, 51);
                    doc.text('CONSTRUCTA', pageWidth / 2, 40, { align: 'center' });
                    
                    doc.setFontSize(20);
                    doc.setTextColor(100, 116, 139);
                    doc.text('Engineering Resources', pageWidth / 2, 55, { align: 'center' });
                    
                    yPos = 80;
                    doc.setFontSize(16);
                    doc.setFont('helvetica', 'bold');
                    doc.setTextColor(41, 64, 51);
                    const titleLines = doc.splitTextToSize(resource.title, contentWidth);
                    titleLines.forEach(line => {
                        doc.text(line, pageWidth / 2, yPos, { align: 'center' });
                        yPos += 8;
                    });
                    
                    yPos += 10;
                    doc.setFontSize(12);
                    doc.setFont('helvetica', 'normal');
                    doc.setTextColor(100, 116, 139);
                    doc.text(`Version: ${resource.version}`, pageWidth / 2, yPos, { align: 'center' });
                    yPos += 10;
                    doc.text(`Generated: ${new Date().toLocaleDateString('en-IN')}`, pageWidth / 2, yPos, { align: 'center' });
                    
                    // NEW PAGE - FORMULAS
                    doc.addPage();
                    yPos = 20;
                    
                    doc.setFontSize(18);
                    doc.setFont('helvetica', 'bold');
                    doc.setTextColor(41, 64, 51);
                    doc.text('Key Formulas & Definitions', margin, yPos);
                    yPos += 15;
                    
                    resource.formulas.forEach((formula, idx) => {
                        checkPageBreak(60);
                        
                        doc.setFontSize(12);
                        doc.setFont('helvetica', 'bold');
                        doc.setTextColor(41, 64, 51);
                        doc.text(`${idx + 1}. ${formula.name}`, margin, yPos);
                        yPos += 8;
                        
                        doc.setFillColor(246, 247, 242);
                        doc.roundedRect(margin, yPos, contentWidth, 15, 2, 2, 'F');
                        doc.setFontSize(10);
                        doc.setFont('courier', 'normal');
                        doc.setTextColor(44, 62, 80);
                        
                        const cleanFormula = formula.formula.replace(/<[^>]*>/g, '');
                        const formulaLines = doc.splitTextToSize(cleanFormula, contentWidth - 10);
                        let formulaY = yPos + 5;
                        formulaLines.forEach(line => {
                            doc.text(line, margin + 5, formulaY);
                            formulaY += 5;
                        });
                        yPos += 18;
                        
                        doc.setFontSize(9);
                        doc.setFont('helvetica', 'normal');
                        doc.setTextColor(100, 116, 139);
                        doc.text('Where:', margin, yPos);
                        yPos += 5;
                        
                        for (const [symbol, meaning] of Object.entries(formula.symbols)) {
                            checkPageBreak(10);
                            const cleanSymbol = symbol.replace(/<[^>]*>/g, '');
                            const text = `  ${cleanSymbol} = ${meaning}`;
                            const textLines = doc.splitTextToSize(text, contentWidth - 10);
                            textLines.forEach(line => {
                                doc.text(line, margin + 5, yPos);
                                yPos += 5;
                            });
                        }
                        
                        yPos += 2;
                        doc.setFont('helvetica', 'italic');
                        const explLines = doc.splitTextToSize(formula.explanation, contentWidth - 10);
                        explLines.forEach(line => {
                            checkPageBreak(6);
                            doc.text(line, margin + 5, yPos);
                            yPos += 5;
                        });
                        
                        yPos += 10;
                    });
                    
                    // WORKED EXAMPLE
                    checkPageBreak(80);
                    if (yPos > 80) {
                        doc.addPage();
                        yPos = 20;
                    }
                    
                    doc.setFontSize(18);
                    doc.setFont('helvetica', 'bold');
                    doc.setTextColor(41, 64, 51);
                    doc.text('Worked Example', margin, yPos);
                    yPos += 15;
                    
                    doc.setFontSize(11);
                    doc.setFont('helvetica', 'bold');
                    doc.text('Problem:', margin + 5, yPos);
                    yPos += 7;
                    
                    doc.setFont('helvetica', 'normal');
                    doc.setTextColor(100, 116, 139);
                    const cleanProblem = resource.workedExample.problem.replace(/<[^>]*>/g, '');
                    const problemLines = doc.splitTextToSize(cleanProblem, contentWidth - 10);
                    problemLines.forEach(line => {
                        doc.text(line, margin + 5, yPos);
                        yPos += 5;
                    });
                    yPos += 10;
                    
                    checkPageBreak(40);
                    doc.setFont('helvetica', 'bold');
                    doc.setTextColor(41, 64, 51);
                    doc.text('Solution:', margin + 5, yPos);
                    yPos += 7;
                    
                    doc.setFont('courier', 'normal');
                    doc.setFontSize(9);
                    doc.setTextColor(44, 62, 80);
                    const cleanSolution = resource.workedExample.solution.replace(/<[^>]*>/g, '').replace(/\\n/g, '\n');
                    const solutionLines = doc.splitTextToSize(cleanSolution, contentWidth - 10);
                    solutionLines.forEach(line => {
                        if (yPos > pageHeight - 30) {
                            doc.addPage();
                            yPos = 20;
                        }
                        doc.text(line, margin + 5, yPos);
                        yPos += 5;
                    });
                    
                    // REFERENCE STANDARDS
                    doc.addPage();
                    yPos = 20;
                    
                    doc.setFontSize(18);
                    doc.setFont('helvetica', 'bold');
                    doc.setTextColor(41, 64, 51);
                    doc.text('Reference Standards', margin, yPos);
                    yPos += 15;
                    
                    doc.setFontSize(11);
                    doc.setFont('helvetica', 'bold');
                    doc.setTextColor(44, 62, 80);
                    doc.text('Primary Code:', margin + 5, yPos);
                    yPos += 7;
                    doc.setFont('helvetica', 'normal');
                    doc.text(resource.standardCode, margin + 5, yPos);
                    yPos += 10;
                    
                    doc.setFont('helvetica', 'bold');
                    doc.text('Related Codes:', margin + 5, yPos);
                    yPos += 7;
                    doc.setFont('helvetica', 'normal');
                    const relatedText = resource.relatedCodes.join(', ');
                    const relatedLines = doc.splitTextToSize(relatedText, contentWidth - 10);
                    relatedLines.forEach(line => {
                        doc.text(line, margin + 5, yPos);
                        yPos += 5;
                    });
                    
                    // DISCLAIMER
                    yPos += 10;
                    doc.setFillColor(254, 242, 242);
                    doc.setDrawColor(252, 165, 165);
                    doc.roundedRect(margin, yPos, contentWidth, 30, 2, 2, 'FD');
                    
                    doc.setFontSize(10);
                    doc.setFont('helvetica', 'bold');
                    doc.setTextColor(153, 27, 27);
                    doc.text('PROFESSIONAL DISCLAIMER', margin + 5, yPos + 8);
                    
                    doc.setFont('helvetica', 'normal');
                    doc.setFontSize(8);
                    const disclaimer = 'This document is a reference guide. All calculations must be verified by a licensed professional engineer. Site-specific conditions, local regulations, and safety factors must be considered.';
                    const disclaimerLines = doc.splitTextToSize(disclaimer, contentWidth - 10);
                    let disclaimerY = yPos + 15;
                    disclaimerLines.forEach(line => {
                        doc.text(line, margin + 5, disclaimerY);
                        disclaimerY += 4;
                    });
                    
                    // Footer on every page
                    const totalPages = doc.internal.getNumberOfPages();
                    for (let i = 1; i <= totalPages; i++) {
                        doc.setPage(i);
                        doc.setFontSize(8);
                        doc.setFont('helvetica', 'normal');
                        doc.setTextColor(100, 116, 139);
                        doc.text(`Page ${i} of ${totalPages}`, pageWidth / 2, pageHeight - 10, { align: 'center' });
                    }
                    
                    // Save PDF
                    doc.save(fileName);
                    
                    // Update notification
                    notification.innerHTML = `
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <i class="fas fa-check-circle" style="color: #10b981; font-size: 2rem;"></i>
                            <div>
                                <strong style="color: #294033; display: block; margin-bottom: 0.25rem; font-size: 1rem;">
                                    PDF Downloaded Successfully
                                </strong>
                                <span style="color: #64748b; font-size: 0.85rem; display: block;">
                                    ${fileName} saved to Downloads
                                </span>
                            </div>
                        </div>
                    `;
                    
                    setTimeout(() => notification.remove(), 4000);
                    
                } catch (error) {
                    console.error('PDF Generation Error:', error);
                    notification.innerHTML = `
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <i class="fas fa-exclamation-circle" style="color: #dc2626; font-size: 2rem;"></i>
                            <div>
                                <strong style="color: #dc2626; display: block;">
                                    PDF Generation Failed
                                </strong>
                                <span style="color: #64748b; font-size: 0.85rem;">
                                    Please try again
                                </span>
                            </div>
                        </div>
                    `;
                    setTimeout(() => notification.remove(), 3000);
                }
            }, 2000);
        }

        // Generate PDF metadata
        function generatePDFContent(resource, id) {
            return {
                fileName: `${resource.title.replace(/[^a-z0-9]/gi, '_')}_v${new Date().getFullYear()}.pdf`,
                title: resource.title,
                version: resource.version,
                generatedDate: new Date().toLocaleDateString('en-IN'),
                pages: 8 + resource.formulas.length * 2,
                formulas: resource.formulas.length,
                sections: [
                    'Cover Page',
                    'Document Information',
                    'Purpose & Scope',
                    'Engineering Assumptions',
                    'Formulas & Definitions',
                    'Worked Examples',
                    'Reference Standards',
                    'Professional Disclaimer',
                    'Appendices'
                ],
                footer: 'Constructa Engineering Platform | © 2026 | For Professional Use Only'
            };
        }

        // Tool Navigation Function
        function openTool(toolId) {
            // Navigate to actual working engineering tools
            const toolPages = {
                1: 'structural_analysis_tool.php',
                2: 'boq_generator_tool.php'
            };
            
            if (toolPages[toolId]) {
                window.location.href = toolPages[toolId];
            } else {
                alert('Tool not yet implemented');
            }
        }

        // Initialize
        window.addEventListener('DOMContentLoaded', () => {
            init3DBackground();
            
            setTimeout(() => {
                document.getElementById('loadingOverlay').classList.add('hidden');
                revealSections();
            }, 800);
        });

        window.addEventListener('scroll', () => {
            updateScrollProgress();
            revealSections();
        });

        // Initial reveal
        revealSections();
    </script>
</body>
</html>
