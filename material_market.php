<?php
// material_market.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic Material Market - Constructa</title>
    <!-- Fonts & Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Three.js Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>
    
    <style>
        :root {
            --bg-color: #f6f7f2;
            --text-dark: #121212;
            --text-gray: #555555;
            --primary-green: #294033;
            --accent-green: #3d5a49;
            --card-bg: #ffffff;
            --input-bg: #f9f9f9;
            --badge-bg: #dcfce7;
            --badge-text: #166534;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: transparent;
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* 3D Background Canvas */
        #bg-canvas-container {
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
            background: rgba(246, 247, 242, 0.8);
            backdrop-filter: blur(12px);
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid rgba(0,0,0,0.05);
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
        .logo i { font-size: 1.5rem; }

        nav { display: flex; gap: 2rem; align-items: center; }

        nav a {
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 500;
            font-size: 0.95rem;
            transition: color 0.2s;
        }
        nav a:hover { color: var(--primary-green); }

        .btn-start {
            background-color: var(--primary-green);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: background-color 0.2s;
        }
        .btn-start:hover { background-color: var(--accent-green); }

        /* Layout */
        .layout-container {
            display: flex;
            max-width: 1600px;
            margin: 0 auto;
            width: 100%;
            padding: 2rem;
            gap: 2rem;
        }

        /* Sidebar */
        aside {
            width: 250px;
            background: rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 1.5rem;
            height: calc(100vh - 120px);
            position: sticky;
            top: 100px;
            overflow-y: auto;
            border: 1px solid rgba(255,255,255,0.6);
        }

        aside h3 {
            font-size: 1.1rem;
            margin-bottom: 1rem;
            color: var(--primary-green);
            border-bottom: 2px solid var(--primary-green);
            padding-bottom: 0.5rem;
        }

        .category-list {
            list-style: none;
        }

        .category-list li {
            margin-bottom: 0.5rem;
        }

        .category-list button {
            display: block;
            width: 100%;
            text-align: left;
            padding: 0.7rem 1rem;
            border: none;
            background: transparent;
            color: var(--text-gray);
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            font-family: 'Inter', sans-serif;
        }

        .category-list button:hover {
            background-color: rgba(255,255,255,0.8);
            color: var(--primary-green);
        }

        .category-list button.active {
            background-color: white;
            color: var(--primary-green);
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            font-weight: 700;
            border-left: 4px solid var(--primary-green);
        }

        /* Main Content */
        main {
            flex: 1;
        }

        .page-header { margin-bottom: 2rem; }
        .page-header h1 { font-size: 2.5rem; color: var(--primary-green); margin-bottom: 0.5rem; }
        .page-header p { color: var(--text-gray); }

        /* Category Sections */
        .category-section {
            display: none; /* Hidden by default */
            animation: fadeIn 0.4s ease-out;
        }
        
        .category-section.active {
            display: block; /* Shown when active */
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            color: var(--text-dark);
            border-bottom: 1px solid #e5e5e5;
            padding-bottom: 0.5rem;
        }

        .market-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }

        /* Material Card */
        .material-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(255,255,255,0.6);
        }

        .material-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.08);
            cursor: pointer;
        }

        .product-viewer {
            width: 100%;
            height: 200px;
            background: rgba(0,0,0,0.02);
            border-radius: 12px;
            margin-bottom: 1rem;
            position: relative;
            cursor: pointer;
        }
        .viewer-hint {
            position: absolute;
            bottom: 8px;
            right: 8px;
            font-size: 0.7rem;
            color: #aaa;
            background: rgba(255,255,255,0.8);
            padding: 2px 6px;
            border-radius: 4px;
            pointer-events: none;
        }

        .material-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }

        .spec {
            font-size: 0.9rem;
            color: var(--text-gray);
            margin-bottom: 1rem;
        }

        .price-row {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding-top: 1rem;
            border-top: 1px solid #f0f0f0;
        }

        .price { font-size: 1.4rem; font-weight: 700; color: var(--primary-green); }
        .unit { font-size: 0.85rem; color: #888; }

        /* Detail Modal */
        #product-3d-modal {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            perspective: 1200px;
            background: rgba(0,0,0,0.4);
            backdrop-filter: blur(8px);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        #product-3d-modal.open {
            display: flex;
            opacity: 1;
        }

        .modal-content-3d {
            width: 90vw;
            max-width: 1100px;
            height: 85vh;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.3);
            display: flex;
            overflow: hidden;
            transform-style: preserve-3d;
            transform: rotateY(90deg) scale(0.8);
            transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
        }

        #product-3d-modal.open .modal-content-3d {
            transform: rotateY(0) scale(1);
        }

        .modal-layout {
            display: flex;
            width: 100%;
            height: 100%;
        }

        /* 3D Stage (Left) */
        .modal-3d-stage {
            flex: 1.5;
            background: #f0f2f5; /* Light studio grey */
            position: relative;
            overflow: hidden;
        }

        #modal-3d-canvas {
            width: 100%;
            height: 100%;
            cursor: grab;
        }
        #modal-3d-canvas:active { cursor: grabbing; }

        .view-toggles {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(255,255,255,0.9);
            padding: 0.5rem;
            border-radius: 50px;
            display: flex;
            gap: 0.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .toggle-btn {
            border: none;
            background: transparent;
            padding: 0.6rem 1.2rem;
            border-radius: 40px;
            cursor: pointer;
            font-weight: 600;
            color: var(--text-gray);
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .toggle-btn.active {
            background: var(--text-dark);
            color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        /* Hotspots */
        .hotspot-container {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            pointer-events: none;
        }

        .hotspot {
            position: absolute;
            width: 20px;
            height: 20px;
            background: var(--primary-green);
            border: 2px solid white;
            border-radius: 50%;
            cursor: pointer;
            pointer-events: auto;
            box-shadow: 0 0 0 rgba(41, 64, 51, 0.4);
            animation: pulse-green 2s infinite;
            display: flex; /* For centering content if needed */
        }

        @keyframes pulse-green {
            0% { box-shadow: 0 0 0 0 rgba(41, 64, 51, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(41, 64, 51, 0); }
            100% { box-shadow: 0 0 0 0 rgba(41, 64, 51, 0); }
        }

        .hotspot-label {
            position: absolute;
            left: 30px;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.95);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            width: max-content;
            max-width: 200px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-dark);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            opacity: 0;
            transition: opacity 0.2s, transform 0.2s;
            pointer-events: none;
        }

        .hotspot:hover .hotspot-label {
            opacity: 1;
            transform: translateY(-50%) translateX(5px);
        }

        /* Info Panel (Right) */
        .modal-info-panel {
            flex: 1;
            padding: 3rem;
            background: white;
            display: flex;
            flex-direction: column;
            border-left: 1px solid #eee;
            z-index: 2; /* Ensure on top for shadow */
        }

        .modal-3d-title {
            font-size: 2.5rem;
            margin-bottom: 2rem;
            line-height: 1.2;
        }

        .specs-grid {
            display: grid;
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .spec-item label {
            display: block;
            text-transform: uppercase;
            font-size: 0.75rem;
            color: #888;
            font-weight: 700;
            letter-spacing: 0.05em;
            margin-bottom: 0.3rem;
        }

        .spec-item p {
            font-size: 1.1rem;
            font-weight: 500;
        }

        

        .interaction-hint {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(0,0,0,0.1);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            color: #666;
            pointer-events: none;
        }

    </style>
</head>
<body>
    <div id="bg-canvas-container"></div>

    <header>
        <a href="landingpage.html" class="logo">
            <i class="far fa-building"></i>
            Constructa
        </a>
        <nav>
            <a href="landingpage.html">Home</a>
            <?php if(isset($_SESSION['user_id'])): ?>
                <?php if($_SESSION['role'] === 'homeowner'): ?>
                    <a href="homeowner.php" class="btn-start">Dashboard</a>
                <?php else: ?>
                    <a href="engineer.php" class="btn-start">Dashboard</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="login.html" class="btn-start">Login</a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="layout-container">
        <!-- Sidebar Navigation -->
        <aside>
            <h3>Categories</h3>
            <ul class="category-list">
                <li><button class="cat-btn active" onclick="showCategory('structural', this)">Structural</button></li>
                <li><button class="cat-btn" onclick="showCategory('foundation', this)">Foundation & RCC</button></li>
                <li><button class="cat-btn" onclick="showCategory('masonry', this)">Masonry</button></li>
                <li><button class="cat-btn" onclick="showCategory('roofing', this)">Roofing & Waterproofing</button></li>
                <li><button class="cat-btn" onclick="showCategory('flooring', this)">Flooring</button></li>
                <li><button class="cat-btn" onclick="showCategory('wallfinishes', this)">Wall Finishes</button></li>
                <li><button class="cat-btn" onclick="showCategory('doors', this)">Doors & Windows</button></li>
                <li><button class="cat-btn" onclick="showCategory('electrical', this)">Electrical</button></li>
                <li><button class="cat-btn" onclick="showCategory('plumbing', this)">Plumbing</button></li>
                <li><button class="cat-btn" onclick="showCategory('kitchen', this)">Kitchen & Utility</button></li>
                <li><button class="cat-btn" onclick="showCategory('external', this)">External Works</button></li>
            </ul>
        </aside>

        <main>
            <div class="page-header">
                <h1>Material Market</h1>
                <p>Select a category to view materials with interactive 3D previews.</p>
            </div>

            <!-- 1. Structural -->
            <section id="structural" class="category-section active">
                <h2 class="section-title">Structural</h2>
                <div class="market-grid">
                    <div class="material-card">
                        <div class="product-viewer" data-type="steel" data-color="#5a6872">
                            <span class="viewer-hint">Interactive 3D</span>
                        </div>
                        <div class="material-name">TMT Steel Bars (Fe-550)</div>
                        <div class="spec">High tensile strength for core structure.</div>
                        <div class="price-row">
                            <div><div class="price">&#8377;65</div><span class="unit">per kg</span></div>
                            
                        </div>
                    </div>
                    <div class="material-card">
                        <div class="product-viewer" data-type="girder" data-color="#b83b3b">
                            <span class="viewer-hint">Interactive 3D</span>
                        </div>
                        <div class="material-name">I-Beam Girder</div>
                        <div class="spec">Heavy duty structural steel beam.</div>
                        <div class="price-row">
                            <div><div class="price">&#8377;82</div><span class="unit">per kg</span></div>
                            
                        </div>
                    </div>
                </div>
            </section>

            <!-- 2. Foundation & RCC -->
            <section id="foundation" class="category-section">
                <h2 class="section-title">Foundation & RCC</h2>
                <div class="market-grid">
                     <div class="material-card">
                        <div class="product-viewer" data-type="cement" data-color="#a0a09a">
                            <span class="viewer-hint">Interactive 3D</span>
                        </div>
                        <div class="material-name">OPC 53 Grade Cement</div>
                        <div class="spec">Fast setting, high strength cement.</div>
                        <div class="price-row">
                            <div><div class="price">&#8377;390</div><span class="unit">per bag</span></div>
                            
                        </div>
                    </div>
                    <div class="material-card">
                        <div class="product-viewer" data-type="gravel" data-color="#777">
                            <span class="viewer-hint">Interactive 3D</span>
                        </div>
                        <div class="material-name">20mm Aggregate</div>
                        <div class="spec">Blue metal for concrete mixing.</div>
                        <div class="price-row">
                            <div><div class="price">&#8377;45</div><span class="unit">per cft</span></div>
                            
                        </div>
                    </div>
                </div>
            </section>

            <!-- 3. Masonry -->
            <section id="masonry" class="category-section">
                <h2 class="section-title">Masonry</h2>
                <div class="market-grid">
                    <div class="material-card">
                        <div class="product-viewer" data-type="brick" data-color="#a53f3f">
                            <span class="viewer-hint">Interactive 3D</span>
                        </div>
                        <div class="material-name">Red Clay Bricks</div>
                        <div class="spec">Traditional kiln burnt bricks.</div>
                        <div class="price-row">
                            <div><div class="price">&#8377;12</div><span class="unit">per piece</span></div>
                            
                        </div>
                    </div>
                    <div class="material-card">
                        <div class="product-viewer" data-type="block" data-color="#999">
                            <span class="viewer-hint">Interactive 3D</span>
                        </div>
                        <div class="material-name">Solid Concrete Blocks</div>
                        <div class="spec">6 Inch solid blocks for load bearing.</div>
                        <div class="price-row">
                            <div><div class="price">&#8377;38</div><span class="unit">per block</span></div>
                            
                        </div>
                    </div>
                </div>
            </section>

            <!-- 4. Roofing -->
            <section id="roofing" class="category-section">
                <h2 class="section-title">Roofing & Waterproofing</h2>
                <div class="market-grid">
                    <div class="material-card">
                        <div class="product-viewer" data-type="sheet" data-color="#3b82f6">
                            <span class="viewer-hint">Interactive 3D</span>
                        </div>
                        <div class="material-name">Polycarbonate Sheet</div>
                        <div class="spec">UV coated roofing sheet.</div>
                        <div class="price-row">
                            <div><div class="price">&#8377;85</div><span class="unit">per sq.ft</span></div>
                            
                        </div>
                    </div>
                    <div class="material-card">
                        <div class="product-viewer" data-type="bucket" data-color="#f59e0b">
                            <span class="viewer-hint">Interactive 3D</span>
                        </div>
                        <div class="material-name">Waterproof Chemical</div>
                        <div class="spec">Dr. Fixit 20L Bucket</div>
                        <div class="price-row">
                            <div><div class="price">&#8377;4,200</div><span class="unit">per bucket</span></div>
                            
                        </div>
                    </div>
                </div>
            </section>

             <!-- 5. Flooring -->
             <section id="flooring" class="category-section">
                <h2 class="section-title">Flooring</h2>
                <div class="market-grid">
                    <div class="material-card">
                        <div class="product-viewer" data-type="tile" data-color="#e2e8f0">
                            <span class="viewer-hint">Interactive 3D</span>
                        </div>
                        <div class="material-name">Vitrified Tiles (2x2)</div>
                        <div class="spec">Double charge, glossy finish.</div>
                        <div class="price-row">
                            <div><div class="price">&#8377;55</div><span class="unit">per sq.ft</span></div>
                            
                        </div>
                    </div>
                    <div class="material-card">
                        <div class="product-viewer" data-type="tile" data-color="#1e1e1e">
                            <span class="viewer-hint">Interactive 3D</span>
                        </div>
                        <div class="material-name">Granite Slab</div>
                        <div class="spec">Black Galaxy Granite.</div>
                        <div class="price-row">
                            <div><div class="price">&#8377;140</div><span class="unit">per sq.ft</span></div>
                            
                        </div>
                    </div>
                </div>
            </section>

            <!-- 6. Wall Finishes -->
            <section id="wallfinishes" class="category-section">
                <h2 class="section-title">Wall Finishes</h2>
                <div class="market-grid">
                    <div class="material-card">
                        <div class="product-viewer" data-type="bucket" data-color="#ffffff">
                            <span class="viewer-hint">Interactive 3D</span>
                        </div>
                        <div class="material-name">Interior Emulsion</div>
                        <div class="spec">Premium smooth finish (20L).</div>
                        <div class="price-row">
                            <div><div class="price">&#8377;3,800</div><span class="unit">per bucket</span></div>
                            
                        </div>
                    </div>
                </div>
            </section>

            <!-- 7. Doors & Windows -->
            <section id="doors" class="category-section">
                <h2 class="section-title">Doors & Windows</h2>
                <div class="market-grid">
                    <div class="material-card">
                        <div class="product-viewer" data-type="door" data-color="#8b4513">
                            <span class="viewer-hint">Interactive 3D</span>
                        </div>
                        <div class="material-name">Teak Wood Door</div>
                        <div class="spec">Solid teak main door frame & shutter.</div>
                        <div class="price-row">
                            <div><div class="price">&#8377;25,000</div><span class="unit">per unit</span></div>
                            
                        </div>
                    </div>
                </div>
            </section>

             <!-- 8. Electrical -->
             <section id="electrical" class="category-section">
                <h2 class="section-title">Electrical</h2>
                <div class="market-grid">
                    <div class="material-card">
                        <div class="product-viewer" data-type="coil" data-color="#dc2626">
                            <span class="viewer-hint">Interactive 3D</span>
                        </div>
                        <div class="material-name">Copper Wire (2.5 sqmm)</div>
                        <div class="spec">Flame retardant house wiring (90m).</div>
                        <div class="price-row">
                            <div><div class="price">&#8377;1,850</div><span class="unit">per coil</span></div>
                            
                        </div>
                    </div>
                </div>
            </section>

             <!-- 9. Plumbing -->
             <section id="plumbing" class="category-section">
                <h2 class="section-title">Plumbing</h2>
                <div class="market-grid">
                    <div class="material-card">
                        <div class="product-viewer" data-type="pipe" data-color="#f5f5f5">
                            <span class="viewer-hint">Interactive 3D</span>
                        </div>
                        <div class="material-name">PVC Pipe (4 inch)</div>
                        <div class="spec">High pressure water line pipe.</div>
                        <div class="price-row">
                            <div><div class="price">&#8377;450</div><span class="unit">per length</span></div>
                            
                        </div>
                    </div>
                </div>
            </section>

             <!-- 10. Kitchen -->
             <section id="kitchen" class="category-section">
                <h2 class="section-title">Kitchen & Utility</h2>
                <div class="market-grid">
                    <div class="material-card">
                        <div class="product-viewer" data-type="sink" data-color="#c0c0c0">
                            <span class="viewer-hint">Interactive 3D</span>
                        </div>
                        <div class="material-name">SS Kitchen Sink</div>
                        <div class="spec">Single bowl with drainboard.</div>
                        <div class="price-row">
                            <div><div class="price">&#8377;3,200</div><span class="unit">per unit</span></div>
                            
                        </div>
                    </div>
                </div>
            </section>

             <!-- 11. External -->
             <section id="external" class="category-section">
                <h2 class="section-title">External Works</h2>
                <div class="market-grid">
                    <div class="material-card">
                        <div class="product-viewer" data-type="brick" data-color="#808080">
                            <span class="viewer-hint">Interactive 3D</span>
                        </div>
                        <div class="material-name">Interlocking Pavers</div>
                        <div class="spec">Zig-zag heavy duty pavers.</div>
                        <div class="price-row">
                            <div><div class="price">&#8377;42</div><span class="unit">per sq.ft</span></div>
                        </div>
                    </div>
                </div>
            </section>

        </main>
    </div>

    <!-- 3D Product Detail Modal -->
    <!-- 3D Product Detail Modal - High Fidelity -->
    <div id="product-3d-modal">
        <div class="modal-content-3d extended-modal">
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
            
            <div class="modal-layout">
                <!-- Left: 3D Stage -->
                <div class="modal-3d-stage">
                    <div id="modal-3d-canvas"></div>
                    
                    <!-- View Toggle -->
                    <div class="view-toggles">
                        <button class="toggle-btn active" onclick="setDetailedViewMode('product')">
                            <i class="fas fa-cube"></i> Product View
                        </button>
                        <button class="toggle-btn" onclick="setDetailedViewMode('context')">
                            <i class="fas fa-layer-group"></i> Construction Mode
                        </button>
                    </div>

                    <!-- Hotspot Overlay Container -->
                    <div id="hotspot-container"></div>
                    
                    <div class="interaction-hint">
                        <i class="fas fa-mouse"></i> Drag to Rotate &bull; Scroll to Zoom
                    </div>
                </div>

                <!-- Right: Info Panel -->
                <div class="modal-info-panel">
                    <h2 class="modal-3d-title" id="m-name">Product Name</h2>
                    <div class="specs-grid">
                        <div class="spec-item">
                            <label>Specification</label>
                            <p id="m-spec">Details...</p>
                        </div>
                        <div class="spec-item">
                            <label>Grade / Type</label>
                            <p>Premium / Industrial</p>
                        </div>
                    </div>

                    <div class="modal-3d-price">
                        <span id="m-price">&#8377;0</span>
                        <span class="modal-3d-unit" id="m-unit">per unit</span>
                    </div>

                    
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Fix 3D Background - Consistent with Landing Page
            initBackground3D();
            
            // Initial Section
            initViewersForSection('structural');

            // Card Click Listeners
            setupCardListeners();
        });

        function setupCardListeners() {
            document.querySelectorAll('.material-card').forEach(card => {
                card.addEventListener('click', (e) => {
                    // Open modal on any click within the card
                    openModal(card);
                });
            });
        }

        // === MODAL LOGIC ===
        function openModal(card) {
            const name = card.querySelector('.material-name').innerText;
            const spec = card.querySelector('.spec').innerText;
            const price = card.querySelector('.price').innerText;
            const unit = card.querySelector('.unit').innerText;

            document.getElementById('m-name').innerText = name;
            document.getElementById('m-spec').innerText = spec;
            document.getElementById('m-price').innerText = price;
            document.getElementById('m-unit').innerText = unit;

            const modal = document.getElementById('product-3d-modal');
            modal.style.display = 'flex';
            // Force reflow
            void modal.offsetWidth; 
            modal.classList.add('open');
        }

        function closeModal() {
            const modal = document.getElementById('product-3d-modal');
            modal.classList.remove('open');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }

        // Close on clicking outside
        document.getElementById('product-3d-modal').addEventListener('click', (e) => {
            if(e.target.id === 'product-3d-modal') closeModal();
        });

        // === 3D BACKGROUND (From Landing Page) ===
        const initBackground3D = () => {
            const container = document.getElementById('bg-canvas-container');
            if (!container) return;

            const scene = new THREE.Scene();
            scene.background = new THREE.Color('#f6f7f2');

            const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
            camera.position.z = 8;
            camera.position.y = 2;

            const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            container.innerHTML = ''; // Clear existing
            container.appendChild(renderer.domElement);

            const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
            scene.add(ambientLight);

            const mainLight = new THREE.DirectionalLight(0xffffff, 0.8);
            mainLight.position.set(10, 10, 10);
            scene.add(mainLight);

            const cityGroup = new THREE.Group();
            scene.add(cityGroup);

            const buildingGeometry = new THREE.BoxGeometry(1, 1, 1);
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

            // Animation Loop
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

        // === CATEGORY & PRODUCT VIEWERS LOGIC ===
        let activeViewers = [];

        function showCategory(id, btn) {
            document.querySelectorAll('.category-section').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.cat-btn').forEach(el => el.classList.remove('active'));
            
            const target = document.getElementById(id);
            if(target) target.classList.add('active');
            if(btn) btn.classList.add('active');

            clearProductViewers();
            setTimeout(() => {
                initViewersForSection(id);
                setupCardListeners(); // Re-attach listeners for new/visible elements if needed
            }, 50);
        }

        function clearProductViewers() {
            activeViewers.forEach(viewer => {
                cancelAnimationFrame(viewer.frameId);
                if (viewer.renderer) {
                    viewer.renderer.dispose();
                    viewer.renderer.forceContextLoss();
                    viewer.container.removeChild(viewer.renderer.domElement);
                }
                if (viewer.geometry) viewer.geometry.dispose();
                if (viewer.material) {
                    if (Array.isArray(viewer.material)) {
                        viewer.material.forEach(m => m.dispose());
                    } else {
                        viewer.material.dispose();
                    }
                }
            });
            activeViewers = [];
        }

        function initViewersForSection(sectionId) {
            const section = document.getElementById(sectionId);
            if (!section) return;

            const viewers = section.querySelectorAll('.product-viewer');
            
            viewers.forEach(v => {
                const type = v.dataset.type;
                const color = v.dataset.color || '#cccccc';

                // Setup High-Quality Scene
                const scene = new THREE.Scene();
                scene.fog = new THREE.FogExp2(0xffffff, 0.02);

                const camera = new THREE.PerspectiveCamera(45, v.clientWidth/v.clientHeight, 0.1, 100);
                camera.position.set(0, 1, 4.5);
                camera.lookAt(0, 0, 0);
                
                const renderer = new THREE.WebGLRenderer({antialias:true, alpha:true});
                renderer.setSize(v.clientWidth, v.clientHeight);
                renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
                renderer.physicallyCorrectLights = true;
                renderer.outputEncoding = THREE.sRGBEncoding;
                renderer.toneMapping = THREE.ACESFilmicToneMapping;
                v.innerHTML = '';
                v.appendChild(renderer.domElement);

                // Simple Lighting
                const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
                scene.add(ambientLight);
                const mainLight = new THREE.DirectionalLight(0xffffff, 2.5);
                mainLight.position.set(5, 10, 7);
                scene.add(mainLight);

                const meshGroup = new THREE.Group();
                scene.add(meshGroup);

                // Quick Material Helper
                const materialStandard = (col) => new THREE.MeshStandardMaterial({color: col, roughness: 0.5, metalness: 0.1});
                
                let geo, mat, mesh;

                // --- PROCEDURAL 3D MODELS (Simplified for brevity) ---
                if(type === 'cement') {
                    mat = materialStandard(color);
                    geo = new THREE.BoxGeometry(1.2, 0.4, 0.7);
                    mesh = new THREE.Mesh(geo, mat);
                    meshGroup.add(mesh);
                } 
                else if(type === 'steel' || type === 'pipe') {
                     mat = materialStandard(color);
                     geo = new THREE.CylinderGeometry(0.1, 0.1, 3, 12);
                     mesh = new THREE.Mesh(geo, mat);
                     mesh.rotation.z = Math.PI / 4;
                     meshGroup.add(mesh);
                }
                else {
                    geo = new THREE.BoxGeometry(1, 1, 1);
                    mat = materialStandard(color);
                    mesh = new THREE.Mesh(geo, mat);
                    meshGroup.add(mesh);
                }

                // Animation
                const animateViewer = () => {
                    const frameId = requestAnimationFrame(animateViewer);
                    meshGroup.rotation.y += 0.01;
                    renderer.render(scene, camera);
                    
                    // Track for cleanup
                    // (Note: simplified tracking logic for this update)
                };
                
                const frameId = requestAnimationFrame(animateViewer);
                activeViewers.push({ frameId, renderer, container: v, geometry: geo, material: mat });
            });
        }
        // === DETAILED MODAL VIEWER SYSTEM ===
        let detailScene, detailCamera, detailRenderer, detailControls;
        let detailFrameId;
        let currentMaterialType = '';
        let viewMode = 'product'; // 'product' or 'context'
        let detailObjects = []; // Store meshes for easy disposal
        let hotspots = [];

        // Helper: Generate Noise Texture for Bump Maps (Procedural Realism)
        function createNoiseTexture(size = 512, scale = 1) {
            const canvas = document.createElement('canvas');
            canvas.width = size;
            canvas.height = size;
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = '#808080'; // Neutral gray base
            ctx.fillRect(0, 0, size, size);
            
            const imageData = ctx.getImageData(0, 0, size, size);
            const data = imageData.data;
            
            for (let i = 0; i < data.length; i += 4) {
                const noise = (Math.random() - 0.5) * 50 * scale;
                data[i] = Math.min(255, Math.max(0, data[i] + noise));
                data[i+1] = Math.min(255, Math.max(0, data[i+1] + noise));
                data[i+2] = Math.min(255, Math.max(0, data[i+2] + noise));
            }
            ctx.putImageData(imageData, 0, 0);
            
            const texture = new THREE.CanvasTexture(canvas);
            texture.wrapS = THREE.RepeatWrapping;
            texture.wrapT = THREE.RepeatWrapping;
            return texture;
        }

        const bumpMapTexture = createNoiseTexture(512, 1.5);
        const roughMapTexture = createNoiseTexture(512, 1.0);

        function openModal(card) {
            const name = card.querySelector('.material-name').innerText;
            const spec = card.querySelector('.spec').innerText;
            const price = card.querySelector('.price').innerText;
            const unit = card.querySelector('.unit').innerText;
            
            // Extract type from the viewer in the card
            const viewer = card.querySelector('.product-viewer');
            const type = viewer ? viewer.dataset.type : 'box';
            const color = viewer ? (viewer.dataset.color || '#cccccc') : '#cccccc';

            document.getElementById('m-name').innerText = name;
            document.getElementById('m-spec').innerText = spec;
            document.getElementById('m-price').innerText = price;
            document.getElementById('m-unit').innerText = unit;

            const modal = document.getElementById('product-3d-modal');
            modal.style.display = 'flex';
            // Force reflow
            void modal.offsetWidth; 
            modal.classList.add('open');

            // Initialize the Detailed 3D Scene
            setTimeout(() => {
                initDetailedViewer(type, color);
            }, 100);
        }

        function closeModal() {
            const modal = document.getElementById('product-3d-modal');
            modal.classList.remove('open');
            setTimeout(() => {
                modal.style.display = 'none';
                disposeDetailedViewer();
            }, 500);
        }

        function disposeDetailedViewer() {
            cancelAnimationFrame(detailFrameId);
            if (detailRenderer) {
                detailRenderer.dispose();
                const container = document.getElementById('modal-3d-canvas');
                if (container && detailRenderer.domElement) {
                    container.innerHTML = '';
                }
            }
            if(detailObjects.length > 0) {
                detailObjects.forEach(obj => {
                    if(obj.geometry) obj.geometry.dispose();
                    if(obj.material) {
                        if(Array.isArray(obj.material)) obj.material.forEach(m => m.dispose());
                        else obj.material.dispose();
                    }
                });
            }
            detailObjects = [];
            // Clear hotspots DOM
            document.getElementById('hotspot-container').innerHTML = '';
        }

        function initDetailedViewer(type, colorHex) {
            disposeDetailedViewer();
            currentMaterialType = type;
            viewMode = 'product'; // Reset mode
            
            // Toggle UI Reset
            document.querySelectorAll('.toggle-btn').forEach(b => b.classList.remove('active'));
            document.querySelector('.toggle-btn:first-child').classList.add('active');

            const container = document.getElementById('modal-3d-canvas');
            
            // Scene Setup
            detailScene = new THREE.Scene();
            detailScene.background = new THREE.Color(0xf0f2f5); // Studio grey
            detailScene.fog = new THREE.Fog(0xf0f2f5, 5, 20);

            // Camera
            detailCamera = new THREE.PerspectiveCamera(45, container.clientWidth / container.clientHeight, 0.1, 100);
            detailCamera.position.set(3, 2, 4);

            // Renderer
            detailRenderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            detailRenderer.setSize(container.clientWidth, container.clientHeight);
            detailRenderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            detailRenderer.shadowMap.enabled = true;
            detailRenderer.shadowMap.type = THREE.PCFSoftShadowMap;
            detailRenderer.physicallyCorrectLights = true;
            detailRenderer.outputEncoding = THREE.sRGBEncoding;
            detailRenderer.toneMapping = THREE.ACESFilmicToneMapping;
            container.appendChild(detailRenderer.domElement);

            // Orbit Controls
            detailControls = new THREE.OrbitControls(detailCamera, detailRenderer.domElement);
            detailControls.enableDamping = true;
            detailControls.dampingFactor = 0.05;
            detailControls.minDistance = 2;
            detailControls.maxDistance = 10;

            // Lighting (Studio Setup)
            const ambient = new THREE.AmbientLight(0xffffff, 0.5);
            detailScene.add(ambient);

            const sun = new THREE.DirectionalLight(0xffeeb1, 1.5);
            sun.position.set(5, 8, 5);
            sun.castShadow = true;
            sun.shadow.mapSize.width = 2048;
            sun.shadow.mapSize.height = 2048;
            detailScene.add(sun);

            const fill = new THREE.DirectionalLight(0xddeeff, 0.8);
            fill.position.set(-5, 3, -5);
            detailScene.add(fill);

            // Ground Plane (Shadow Catcher)
            const planeGeo = new THREE.PlaneGeometry(20, 20);
            const planeMat = new THREE.ShadowMaterial({ opacity: 0.1 });
            const plane = new THREE.Mesh(planeGeo, planeMat);
            plane.rotation.x = -Math.PI / 2;
            plane.position.y = -0.5; // Slightly below model
            plane.receiveShadow = true;
            detailScene.add(plane);
            detailObjects.push(plane);

            // Build Object
            buildDetailedObject(type, colorHex);

            // Animation Loop
            const animate = () => {
                detailFrameId = requestAnimationFrame(animate);
                detailControls.update();
                updateHotspots();
                detailRenderer.render(detailScene, detailCamera);
            };
            animate();
        }

        // Global function to toggle modes
        window.setDetailedViewMode = function(mode) {
            if(viewMode === mode) return;
            viewMode = mode;
            
            // UI Update
            document.querySelectorAll('.toggle-btn').forEach(b => b.classList.remove('active'));
            if(mode === 'product') document.querySelector('.toggle-btn:first-child').classList.add('active');
            else document.querySelector('.toggle-btn:last-child').classList.add('active');

            // Rebuild scene content
            // Simple approach: Clear objects (except plane/lights) and rebuild
            // More complex: Tween positions. For now, we rebuild to ensure structural changes logic is clean.
            
            // Remove previous objects group
            const group = detailScene.getObjectByName('mainGroup');
            if(group) {
                detailScene.remove(group);
                // clean up logic omitted for brevity, handled by detailObjects array conceptually
            }
            
            // Clear hotspots
            document.getElementById('hotspot-container').innerHTML = '';
            
            if(mode === 'product') {
                // Reset camera
                new TWEEN.Tween(detailCamera.position)
                    .to({x: 3, y: 2, z: 4}, 1000)
                    .easing(TWEEN.Easing.Cubic.Out)
                    .start();
                
                buildDetailedObject(currentMaterialType, null, false);
            } else {
                // Construction Mode
                // Move camera back
                new TWEEN.Tween(detailCamera.position)
                    .to({x: 6, y: 4, z: 6}, 1000)
                    .easing(TWEEN.Easing.Cubic.Out)
                    .start();

                buildConstructionContext(currentMaterialType);
            }
        };

        function buildDetailedObject(type, colorHex, isReload = true) {
            const group = new THREE.Group();
            group.name = 'mainGroup';
            
            const col = colorHex ? new THREE.Color(colorHex) : new THREE.Color(0xa53f3f); 
            const createMat = (c, r=0.9, m=0.1) => new THREE.MeshStandardMaterial({
                color: c,
                roughness: r,
                metalness: m,
                bumpMap: bumpMapTexture,
                bumpScale: 0.01
            });

            if (type === 'brick' || type === 'block') {
                const w=0.215, h=0.065, d=0.102;
                const mesh = new THREE.Mesh(new THREE.BoxGeometry(w, h, d, 2, 2, 2), createMat(col));
                mesh.castShadow = true; mesh.receiveShadow = true;
                group.add(mesh);
                if(viewMode === 'product') {
                    addHotspot(0.1, 0.03, 0.05, "Dimension: 215x102x65mm");
                    addHotspot(-0.05, 0, 0.05, "Compressive Strength: 3.5N/mm²");
                }
            } 
            else if (type === 'steel' || type === 'pipe') {
                 // Enhanced Pipe/Steel
                 const len = 1.0;
                 const rad = (type==='pipe') ? 0.08 : 0.05; // Thicker if pipe
                 const mat = createMat(type==='pipe'?0xdddddd:0x5a6872, 0.4, 0.5);
                 const geo = new THREE.CylinderGeometry(rad, rad, len, 32);
                 const mesh = new THREE.Mesh(geo, mat);
                 mesh.rotation.z = Math.PI/2;
                 mesh.castShadow = true;
                 group.add(mesh);
                 
                 // If Steel, add ribs
                 if(type === 'steel') {
                     for(let i=0; i<10; i++) {
                         const ring = new THREE.Mesh(new THREE.TorusGeometry(rad, 0.003, 8, 32), mat);
                         ring.rotation.y = Math.PI/2;
                         ring.position.x = (i/10)*len - len/2 + 0.05;
                         group.add(ring);
                     }
                 }

                 if(viewMode === 'product') {
                     if(type === 'steel') addHotspot(0.2, 0.05, 0, "Grade: Fe-550 TMT");
                     else addHotspot(0.2, 0.08, 0, "Pressure: 6kg/cm²");
                 }
            }
            else if (type === 'sink') {
                // Stainless Steel Sink Detail
                const metalMat = createMat(0xaaaaaa, 0.3, 0.8);
                const sinkW = 0.8, sinkD = 0.5, sinkH = 0.25;
                
                // Rim/Deck
                const csgGroup = new THREE.Group();
                const deck = new THREE.Mesh(new THREE.BoxGeometry(sinkW, 0.02, sinkD), metalMat);
                deck.castShadow = true;
                csgGroup.add(deck);
                
                // Bowl (visual approximation)
                const bowl = new THREE.Mesh(new THREE.BoxGeometry(sinkW*0.5, sinkH, sinkD*0.8), metalMat);
                bowl.position.y = -sinkH/2;
                bowl.position.x = -sinkW*0.2; // Offset bowl
                bowl.castShadow = true;
                csgGroup.add(bowl);
                
                // Drain hole (simple)
                const drain = new THREE.Mesh(new THREE.CylinderGeometry(0.04, 0.04, 0.05), new THREE.MeshStandardMaterial({color:0x222222}));
                drain.position.set(-sinkW*0.2, -sinkH, 0);
                csgGroup.add(drain);

                group.add(csgGroup);
                
                if(viewMode === 'product') {
                    addHotspot(-0.2, 0, 0, "Grade: SS 304");
                    addHotspot(0.2, 0.01, 0.1, "Thickness: 1mm");
                }
            }
            else if (type === 'door') {
                // Wooden Door
                const doorH = 1.0, doorW = 0.5, doorT = 0.04;
                const woodMat = createMat(0x8b4513, 0.7, 0);
                const door = new THREE.Mesh(new THREE.BoxGeometry(doorW, doorH, doorT), woodMat);
                door.castShadow = true;
                
                // Panels
                const pW = doorW*0.35, pH=doorH*0.35;
                const panel1 = new THREE.Mesh(new THREE.BoxGeometry(pW, pH, 0.05), createMat(0x5c2e0c));
                panel1.position.set(0, 0.2, 0);
                const panel2 = new THREE.Mesh(new THREE.BoxGeometry(pW, pH, 0.05), createMat(0x5c2e0c));
                panel2.position.set(0, -0.2, 0);
                door.add(panel1); door.add(panel2);
                
                // Handle
                const knob = new THREE.Mesh(new THREE.SphereGeometry(0.03), createMat(0xdddddd, 0.2, 0.9));
                knob.position.set(doorW/2 - 0.05, 0, 0.04);
                door.add(knob);

                group.add(door);
                 if(viewMode === 'product') {
                    addHotspot(0, 0.4, 0, "Material: Solid Teak");
                    addHotspot(0.2, 0, 0.05, "Finish: Polyurethane");
                }
            }
            else {
                // Generic Fallback
                const geo = new THREE.BoxGeometry(0.5, 0.5, 0.5);
                const mesh = new THREE.Mesh(geo, createMat(col));
                mesh.castShadow = true;
                group.add(mesh);
                addHotspot(0, 0.25, 0, "Specification Pending");
            }

            detailScene.add(group);
            detailObjects.push(group);
        }

        function buildConstructionContext(type) {
            const group = new THREE.Group();
            group.name = 'mainGroup';
            
            const mat = new THREE.MeshStandardMaterial({
                 color: 0xa53f3f, roughness: 0.9, bumpMap: bumpMapTexture, bumpScale: 0.02
            });
            const dim = {w:0.4, h:0.2, d:0.2}; // scaled up for viz visibility

            if(type === 'brick' || type === 'block') {
                // Animate Wall Construction
                const rows = 5;
                const cols = 4;
                
                for(let r=0; r<rows; r++) {
                    for(let c=0; c<cols; c++) {
                        const offsetX = (r%2 === 0) ? 0 : dim.w/2;
                        if(c === cols-1 && r%2!==0) continue; // skip last in offset rows for clean look

                        const mesh = new THREE.Mesh(new THREE.BoxGeometry(dim.w-0.01, dim.h-0.01, dim.d), mat);
                        
                        // Final Position
                        const targetX = (c * dim.w) + offsetX - (cols*dim.w/2);
                        const targetY = (r * dim.h);
                        const targetZ = 0;

                        // Start Position (Flying in)
                        mesh.position.set(
                            targetX, 
                            targetY + 5 + Math.random()*2, 
                            targetZ + (Math.random()-0.5)*2
                        );
                        
                        mesh.castShadow = true;
                        mesh.receiveShadow = true;
                        group.add(mesh);
                        
                        // Animation
                        const delay = (r * cols + c) * 100;
                        new TWEEN.Tween(mesh.position)
                            .to({x: targetX, y: targetY, z: targetZ}, 800)
                            .delay(delay)
                            .easing(TWEEN.Easing.Bounce.Out)
                            .start();
                    }
                }
            } 
            else if (type === 'sink') {
                 // Kitchen Context
                 const graniteMat = new THREE.MeshStandardMaterial({ color: 0x111111, roughness: 0.1, metalness: 0.1 }); // Black granite
                 const cabinetMat = new THREE.MeshStandardMaterial({ color: 0xf5f5dc, roughness: 0.8 }); // Beige wood
                 
                 // 1. Cabinet Base
                 const cabinet = new THREE.Mesh(new THREE.BoxGeometry(1.5, 0.9, 0.8), cabinetMat);
                 cabinet.position.set(0, 0.45, 0);
                 cabinet.castShadow = true;
                 group.add(cabinet);
                 
                 // 2. Granite Countertop
                 const counter = new THREE.Mesh(new THREE.BoxGeometry(1.6, 0.04, 0.85), graniteMat);
                 counter.position.set(0, 0.92, 0);
                 counter.castShadow = true;
                 group.add(counter);

                 // 3. The Sink (Dropping in)
                 const sinkW = 0.8, sinkD = 0.5, sinkH = 0.25; 
                 // Simple representation for context
                 const sinkMesh = new THREE.Mesh(new THREE.BoxGeometry(sinkW, 0.02, sinkD), new THREE.MeshStandardMaterial({color: 0xaaaaaa, metalness: 0.8}));
                 sinkMesh.position.set(0, 2.0, 0); // Start high
                 group.add(sinkMesh);
                 
                 new TWEEN.Tween(sinkMesh.position)
                    .to({y: 0.94}, 1200)
                    .easing(TWEEN.Easing.Bounce.Out)
                    .start();
                    
                 // 4. Faucet
                 const faucetGroup = new THREE.Group();
                 const fStick = new THREE.Mesh(new THREE.CylinderGeometry(0.03,0.03,0.4), new THREE.MeshStandardMaterial({color:0xffffff, metalness:0.9}));
                 fStick.position.y = 0.2;
                 const fSpout = new THREE.Mesh(new THREE.CylinderGeometry(0.02,0.02,0.3), new THREE.MeshStandardMaterial({color:0xffffff, metalness:0.9}));
                 fSpout.rotation.z = Math.PI/2;
                 fSpout.position.set(0.15, 0.4, 0);
                 faucetGroup.add(fStick); faucetGroup.add(fSpout);
                 faucetGroup.position.set(0, 0.94, -0.3);
                 group.add(faucetGroup);
            }
            else if (type === 'door') {
                // Wall with opening
                const wallMat = new THREE.MeshStandardMaterial({ color: 0xffffff });
                // Left Wall
                const w1 = new THREE.Mesh(new THREE.BoxGeometry(1.0, 2.5, 0.2), wallMat);
                w1.position.set(-1.0, 1.25, 0);
                group.add(w1);
                // Right Wall
                const w2 = new THREE.Mesh(new THREE.BoxGeometry(1.0, 2.5, 0.2), wallMat);
                w2.position.set(1.0, 1.25, 0);
                group.add(w2);
                // Lintel
                const w3 = new THREE.Mesh(new THREE.BoxGeometry(1.0, 0.5, 0.2), wallMat);
                w3.position.set(0, 2.25, 0);
                group.add(w3);
                
                // Door Frame (Animates in)
                const frameGeo = new THREE.BoxGeometry(1.1, 2.1, 0.22); // slightly larger than opening
                // Represent frame as thin outline? Simplification: Just a dark border
                const frame = new THREE.Mesh(frameGeo, new THREE.MeshStandardMaterial({color:0x3e2723}));
                // Make it hollow-ish visual by putting door inside
                frame.position.set(0, 1.05, 0);
                frame.scale.set(0.1, 0.1, 0.1); 
                group.add(frame);
                
                new TWEEN.Tween(frame.scale).to({x:1, y:1, z:1}, 800).easing(TWEEN.Easing.Back.Out).start();

                // Door Leaf (Swings in)
                const doorLeaf = new THREE.Mesh(new THREE.BoxGeometry(0.95, 2.0, 0.05), new THREE.MeshStandardMaterial({color:0x8b4513}));
                doorLeaf.position.set(0, 1.05, 0);
                doorLeaf.rotation.y = Math.PI/2; // Start open
                group.add(doorLeaf);
                
                new TWEEN.Tween(doorLeaf.rotation)
                    .to({y: 0}, 1000)
                    .delay(800)
                    .easing(TWEEN.Easing.Cubic.Out)
                    .start();
            }
            else if (type === 'tile' || type === 'flooring') {
                 // Floor Installation
                 const floorBase = new THREE.Mesh(new THREE.PlaneGeometry(3, 3), new THREE.MeshStandardMaterial({color:0x999999})); // cement bed
                 floorBase.rotation.x = -Math.PI/2;
                 group.add(floorBase);
                 
                 const tileGeo = new THREE.BoxGeometry(0.58, 0.02, 0.58);
                 const tileMat = new THREE.MeshStandardMaterial({color: 0xf5f5f5, roughness:0.1});
                 
                 for(let x=-2; x<=2; x++) {
                     for(let z=-2; z<=2; z++) {
                         const tile = new THREE.Mesh(tileGeo, tileMat);
                         const targetX = x*0.6;
                         const targetZ = z*0.6;
                         
                         tile.position.set(targetX, 0.5 + Math.random(), targetZ); // Hover
                         tile.rotation.y = Math.random();
                         group.add(tile);
                         
                         const delay = (x+2 + z+2)*100;
                         new TWEEN.Tween(tile.position).to({y: 0.02}, 600).delay(delay).easing(TWEEN.Easing.Cubic.Out).start();
                         new TWEEN.Tween(tile.rotation).to({y: 0}, 600).delay(delay).start();
                     }
                 }
            }
            else if (type === 'steel') {
                // Column Cage Animation (Existing)
                 const matSteel = new THREE.MeshStandardMaterial({ color: 0x5a6872, metalness: 0.8, roughness: 0.4 });
                 // ... existing logic refined ...
                 // 4 vertical bars
                 const bars = [{x:-0.3, z:-0.3}, {x:0.3, z:-0.3}, {x:0.3, z:0.3}, {x:-0.3, z:0.3}];
                 bars.forEach((pos, i) => {
                     const bar = new THREE.Mesh(new THREE.CylinderGeometry(0.04, 0.04, 2.5, 16), matSteel);
                     bar.position.set(pos.x, 3, pos.z);
                     group.add(bar);
                     new TWEEN.Tween(bar.position).to({y: 1.25}, 1000).delay(i*100).easing(TWEEN.Easing.Cubic.Out).start();
                 });
                 // Stirrups
                 for(let j=0; j<5; j++) {
                     const ring = new THREE.Mesh(new THREE.TorusGeometry(0.45, 0.02, 4, 4), matSteel);
                     ring.rotation.x = Math.PI/2; ring.rotation.z = Math.PI/4;
                     ring.position.set(0, 3 + j*0.5, 0);
                     group.add(ring);
                     new TWEEN.Tween(ring.position).to({y: 0.3 + j*0.5}, 800).delay(800 + j*100).start();
                 }
            }
            else if (type === 'pipe' || type === 'plumbing') {
                // Trench Installation
                const ground = new THREE.Mesh(new THREE.BoxGeometry(3, 0.5, 3), new THREE.MeshStandardMaterial({color:0x8B4513}));
                ground.position.y = -0.5;
                group.add(ground);
                
                // Pipe segments connecting
                const pipeMat = new THREE.MeshStandardMaterial({color:0xffffff});
                const p1 = new THREE.Mesh(new THREE.CylinderGeometry(0.1, 0.1, 1.2, 20), pipeMat);
                p1.rotation.z = Math.PI/2;
                p1.position.set(-1.5, 0.1, 0); // Slide in from left
                group.add(p1);
                
                const p2 = new THREE.Mesh(new THREE.CylinderGeometry(0.1, 0.1, 1.2, 20), pipeMat);
                p2.rotation.z = Math.PI/2;
                p2.position.set(1.5, 0.1, 0); // Slide in from right
                group.add(p2);
                
                const elbow = new THREE.Mesh(new THREE.SphereGeometry(0.12), pipeMat); // Simple elbow joint
                elbow.position.set(0, 2, 0); // Drop in
                group.add(elbow);
                
                new TWEEN.Tween(p1.position).to({x: -0.6}, 1000).easing(TWEEN.Easing.Cubic.Out).start();
                new TWEEN.Tween(p2.position).to({x: 0.6}, 1000).easing(TWEEN.Easing.Cubic.Out).start();
                new TWEEN.Tween(elbow.position).to({y: 0.1}, 1000).delay(800).easing(TWEEN.Easing.Bounce.Out).start();
            }
            else {
                // Fallback (Generic Stacking)
                for(let i=0; i<3; i++) {
                    const mesh = new THREE.Mesh(new THREE.BoxGeometry(0.5,0.5,0.5), mat);
                    mesh.position.y = 5;
                    group.add(mesh);
                    new TWEEN.Tween(mesh.position).to({y: i*0.5}, 600).delay(i*200).start();
                }
            }

            detailScene.add(group);
            detailObjects.push(group);
        }

        // --- Hotspot UI System ---
        function addHotspot(x, y, z, labelText) {
            const container = document.getElementById('hotspot-container');
            const spot = document.createElement('div');
            spot.className = 'hotspot';
            
            const label = document.createElement('div');
            label.className = 'hotspot-label';
            label.innerText = labelText;
            spot.appendChild(label);
            
            container.appendChild(spot);
            
            hotspots.push({
                element: spot,
                position: new THREE.Vector3(x, y, z)
            });
        }

        function updateHotspots() {
            // Need to update TWEENs here too
            TWEEN.update();

            if(!detailCamera || hotspots.length === 0) return;

            const tempV = new THREE.Vector3();
            
            hotspots.forEach(h => {
                // Project 3D position to 2D screen
                tempV.copy(h.position);
                tempV.project(detailCamera);
                
                const x = (tempV.x * .5 + .5) * detailRenderer.domElement.clientWidth;
                const y = (tempV.y * -.5 + .5) * detailRenderer.domElement.clientHeight;
                
                // Hide if behind camera
                if (Math.abs(tempV.z) > 1) { // z is not distance, it's clip space z
                     h.element.style.display = 'none';
                } else {
                     h.element.style.display = 'flex';
                     h.element.style.left = `${x}px`;
                     h.element.style.top = `${y}px`;
                }
            });
        }
        
        // TWEEN.js is required. Since we cannot import external libraries easily if not present,
        // let's include a Minimal Tween Implementation if not generic. 
        // Actually, we should check if TWEEN is available. If not, include minified version.
        // Assuming user doesn't have it, I'll append a CDN to the head in a separate call if needed, but for now I'll bundle a tiny tween class.
        
        const TWEEN = {
            tweens: [],
            getAll() { return this.tweens; },
            removeAll() { this.tweens = []; },
            add(tween) { this.tweens.push(tween); },
            remove(tween) { 
                const i = this.tweens.indexOf(tween); 
                if (i !== -1) this.tweens.splice(i, 1); 
            },
            update(time) {
                if (this.tweens.length === 0) return false;
                const i = Date.now();
                time = time !== undefined ? time : i;
                this.tweens.forEach(t => t.update(time));
                return true;
            },
            Easing: { 
                Cubic: { Out: (t) => --t * t * t + 1 },
                Bounce: { Out: (k) => {
                    if (k < (1 / 2.75)) { return 7.5625 * k * k; } 
                    else if (k < (2 / 2.75)) { return 7.5625 * (k -= (1.5 / 2.75)) * k + 0.75; } 
                    else if (k < (2.5 / 2.75)) { return 7.5625 * (k -= (2.25 / 2.75)) * k + 0.9375; } 
                    else { return 7.5625 * (k -= (2.625 / 2.75)) * k + 0.984375; }
                }},
                Back: { Out: (k) => { const s = 1.70158; return --k * k * ((s + 1) * k + s) + 1; } }
            },
            Tween: class {
                constructor(obj) { this.obj = obj; this._valuesEnd = {}; this._duration = 1000; this._delayTime = 0; this._startTime = null; this._easingFunction = TWEEN.Easing.Cubic.Out; this._onUpdateCallback = null; }
                to(prop, duration) { this._valuesEnd = prop; if(duration !== undefined) this._duration = duration; return this; }
                delay(amount) { this._delayTime = amount; return this; }
                easing(fn) { this._easingFunction = fn; return this; }
                onUpdate(fn) { this._onUpdateCallback = fn; return this; }
                start() { 
                    this._startTime = Date.now() + this._delayTime; 
                    this._valuesStart = {};
                    for(let p in this._valuesEnd) this._valuesStart[p] = this.obj[p];
                    TWEEN.add(this); 
                    return this; 
                }
                update(time) {
                    if (time < this._startTime) return true;
                    let elapsed = (time - this._startTime) / this._duration;
                    elapsed = elapsed > 1 ? 1 : elapsed;
                    const value = this._easingFunction(elapsed);
                    for(let property in this._valuesEnd) {
                        const start = this._valuesStart[property];
                        const end = this._valuesEnd[property];
                        this.obj[property] = start + (end - start) * value;
                    }
                    if (this._onUpdateCallback) this._onUpdateCallback(this.obj);
                    if (elapsed === 1) TWEEN.remove(this);
                    return true;
                }
            }
        };
    </script>                    
</body>
</html>




