<?php
// admin_material_procurement.php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.html');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Constructa | Nexus Command</title>
    
    <!-- Fonts & Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Rajdhani:wght@400;500;600;700&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    
    <!-- Core Engine -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <!-- Post Processing for Glow (Simulated via CSS for performance here) -->

    <style>
        :root {
            /* 🏳️ PLATINUM & HOLOGRAPHIC THEME */
            --bg-core: #F0F2F5;  /* Engineering Grey */
            --glass-pure: rgba(255, 255, 255, 0.65);
            --glass-frosted: rgba(255, 255, 255, 0.85);
            
            /* 🌈 Prism Accents */
            --neon-cyan: #00f2ea;
            --neon-purple: #ff00ff;
            --holo-gradient: linear-gradient(135deg, rgba(255,255,255,0.4) 0%, rgba(255,255,255,0.1) 100%);
            --border-prism: linear-gradient(135deg, rgba(255,255,255,1) 0%, rgba(200,200,255,0.5) 50%, rgba(255,255,255,1) 100%);
            
            /* Text */
            --txt-dark: #0f172a;
            --txt-dim: #64748b;
            
            /* UI */
            --radius-tech: 16px;
            --shadow-float: 0 20px 50px -10px rgba(0,0,0,0.1);
            --shadow-glow: 0 0 20px rgba(0, 242, 234, 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: var(--bg-core);
            font-family: 'Space Grotesk', sans-serif;
            height: 100vh;
            overflow: hidden;
            color: var(--txt-dark);
            perspective: 2000px; /* For 3D CSS elements */
        }

        /* 🌐 1. ADVANCED 3D VIEWPORT */
        #canvas-viewport {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            z-index: 0; pointer-events: none;
            /* Faded radial mask for focus */
            mask-image: radial-gradient(circle at center, black 40%, transparent 100%);
        }

        /* Navbar */
        header {
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            z-index: 100;
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            flex-shrink: 0;
            height: 90px; /* Fixed height */
        }

        /* AI 3D DYNAMIC HEADER */
        .ai-title-container {
            position: relative;
            font-family: 'Rajdhani', sans-serif;
            font-weight: 700;
            text-transform: uppercase;
            perspective: 500px;
            cursor: pointer;
        }
        
        .ai-title-3d {
            font-size: 2rem;
            color: #1e293b;
            letter-spacing: 4px;
            position: relative;
            transform-style: preserve-3d;
            transition: transform 0.2s ease-out;
            display: inline-block;
            text-shadow: 
                2px 2px 0px rgba(0, 242, 234, 0.4),
                -2px -2px 0px rgba(255, 0, 255, 0.4);
        }
        
        .ai-title-3d::before {
            content: attr(data-text);
            position: absolute; top:0; left:0; width:100%; height:100%;
            color: var(--neon-cyan);
            opacity: 0.3;
            transform: translateZ(-5px);
            filter: blur(2px);
        }
        
        /* Sub-caption below title */
        .ai-caption {
            font-size: 0.7rem;
            color: var(--txt-dim);
            font-family: 'Space Grotesk', monospace;
            text-align: right;
            margin-top: -5px;
            opacity: 0.8;
            letter-spacing: 1px;
        }

        /* 🖥️ 2. UI HUD LAYER */
        /* 🖥️ 2. UI HUD LAYER */
        .hud-layer {
            position: relative; z-index: 10;
            width: 100%; height: 100%;
            display: flex; flex-direction: column; /* Changed to flex column */
            /* Removed grid-template-rows */
            padding: 0; /* Padding moved to children */
        }

        /* --- HEADER --- */
        .tech-header {
            display: flex; justify-content: space-between; align-items: center;
            background: var(--glass-pure);
            backdrop-filter: blur(25px) saturate(180%);
            border-radius: 20px;
            padding: 0 2rem;
            border: 1px solid rgba(255,255,255,0.8);
            box-shadow: var(--shadow-float);
            position: relative;
            overflow: hidden;
        }
        
        /* Scanning Line Effect */
        .scan-line {
            position: absolute; top:0; left: 0; width: 4px; height: 100%;
            background: linear-gradient(to bottom, transparent, var(--neon-cyan), transparent);
            animation: scanX 8s linear infinite;
            filter: blur(2px);
            opacity: 0.5;
        }
        @keyframes scanX { 0% { left: -10%; } 100% { left: 110%; } }

        .brand-cluster h1 {
            font-family: 'Rajdhani', sans-serif;
            font-weight: 700; text-transform: uppercase; letter-spacing: 2px;
            font-size: 1.8rem;
            background: linear-gradient(90deg, #1e293b, #475569);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .status-pill {
            font-size: 0.7rem; color: var(--neon-cyan); background: rgba(0,0,0,0.8);
            padding: 4px 12px; border-radius: 99px; font-family: 'Courier New', monospace;
            border: 1px solid var(--neon-cyan); text-transform: uppercase;
            box-shadow: 0 0 10px rgba(0, 242, 234, 0.4);
        }

        /* System Buttons - HIGH CONTRAST */
        /* System Buttons - Updated to match admin_dashboard.php */
        .sys-btn {
            padding: 0.8rem 1.5rem;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 4px;
            text-decoration: none;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8rem;
            color: #121212; /* var(--text-dark) */
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .sys-btn:hover {
            background: #294033; /* var(--primary-green) */
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            border-color: #294033;
        }
        
        .sys-btn i { color: inherit; } /* Icon inherits text color */


        /* --- MAIN DASHBOARD GRID --- */
        .main-grid {
            display: grid;
            grid-template-columns: 320px 1fr 380px;
            gap: 1.5rem;
            flex: 1; /* Take remaining height */
            overflow: hidden; 
            padding: 1.5rem 2rem; /* Add padding here */
        }

        .panel-container {
            background: var(--glass-frosted);
            backdrop-filter: blur(40px);
            border-radius: 24px;
            border: 1px solid rgba(255,255,255,0.6);
            display: flex; flex-direction: column;
            position: relative;
            padding: 1.5rem;
            transition: 0.4s cubic-bezier(0.2, 0.8, 0.2, 1);
            transform-style: preserve-3d;
        }
        .panel-container:hover {
            transform: translateY(-5px) scale(1.005);
            box-shadow: var(--shadow-float), var(--shadow-glow);
            border-color: rgba(255,255,255,1);
        }

        /* Floating Badge */
        .panel-badge {
            position: absolute; top: -10px; left: 20px;
            background: #0f172a; color: #fff;
            padding: 0.4rem 1rem; border-radius: 8px;
            font-size: 0.7rem; font-weight: 700; letter-spacing: 1px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            z-index: 5;
            display: flex; gap: 0.5rem; align-items: center;
        }
        .panel-badge::before {
            content:''; width: 8px; height: 8px; background: var(--neon-cyan); border-radius: 50%;
            box-shadow: 0 0 8px var(--neon-cyan); animation: pulse 2s infinite;
        }
        @keyframes pulse { 0%{opacity:0.5; transform:scale(0.8);} 50%{opacity:1; transform:scale(1.2);} 100%{opacity:0.5; transform:scale(0.8);} }

        /* 🔥 COL 1: METRICS & PIPELINE */
        .t-stat {
            background: rgba(255,255,255,0.5);
            border-radius: 16px; padding: 1.2rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(255,255,255,0.5);
            transition: 0.3s;
        }
        .t-stat:hover { background: #fff; transform: translateX(5px); border-left: 4px solid var(--neon-cyan); }
        .t-val { font-size: 2rem; font-family: 'Rajdhani'; font-weight: 700; color: var(--txt-dark); }
        .t-lbl { font-size: 0.75rem; text-transform: uppercase; color: var(--txt-dim); letter-spacing: 1px; }

        .pipeline-track {
            margin-top: 2rem; /* Reduced gap */
            position: relative;
            padding-left: 20px;
            border-left: 1px dashed #cbd5e1;
        }
        .p-node {
            position: relative; margin-bottom: 1.5rem; padding-left: 1rem; cursor: pointer;
            opacity: 0.6; transition: 0.3s;
        }
        .p-node::before {
            content:''; position: absolute; left: -24px; top: 4px;
            width: 7px; height: 7px; background: #94a3b8; border-radius: 50%;
            border: 2px solid var(--bg-core); transition: 0.3s;
        }
        .p-node:hover, .p-node.active { opacity: 1; }
        .p-node.active::before { background: var(--neon-cyan); box-shadow: 0 0 10px var(--neon-cyan); transform: scale(1.5); }
        .p-node-name { font-weight: 700; letter-spacing: 0.5px; }
        .p-node-count { font-size: 0.7rem; font-family: 'Courier New'; }


        /* 🔥 COL 2: HOLOGRAPHIC GRID */
        .holo-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1.2rem; align-content: start; overflow-y: auto;
            padding: 1rem;
            /* Scrollbar styling */
            scrollbar-width: thin;
        }
        
        .holo-card {
            background: linear-gradient(160deg, rgba(255,255,255,0.9) 0%, rgba(255,255,255,0.5) 100%);
            border-radius: 16px; padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            border: 1px solid rgba(255,255,255,0.8);
            position: relative; overflow: hidden;
            transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
        }
        /* Iridescent Border Top */
        .holo-card::after {
            content:''; position: absolute; top:0; left:0; width:100%; height:4px;
            background: linear-gradient(90deg, #ff00ff, #00f2ea);
            opacity: 0; transition: 0.3s;
        }
        .holo-card:hover {
            transform: translateY(-8px) rotateX(5deg);
            background: #fff;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .holo-card:hover::after { opacity: 1; }
        
        .h-id { font-family: 'Courier New'; font-size: 0.7rem; color: var(--txt-dim); }
        .h-title { font-weight: 700; margin: 0.5rem 0; font-size: 1rem; color: var(--txt-dark); }
        .h-status {
            display: inline-block; padding: 3px 10px; border-radius: 8px;
            font-size: 0.65rem; font-weight: 800; text-transform: uppercase;
            background: #f1f5f9; color: #475569; letter-spacing: 0.5px;
        }
        .h-status.active { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
        .h-status.transit { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; animation: pulse 2s infinite; }
        

        /* 🔥 COL 3: INTELLIGENCE FEED */
        .intel-feed {
            display: flex; flex-direction: column; gap: 1rem;
            height: 100%; overflow-y: auto; /* Enable scroll */
            padding-right: 5px; /* Space for scrollbar */
        }
        .feed-msg {
            background: rgba(255,255,255,0.6);
            border-left: 2px solid #cbd5e1;
            padding: 1rem; border-radius: 0 12px 12px 0;
            font-size: 0.8rem; position: relative;
            animation: slideIn 0.5s ease-out;
        }
        @keyframes slideIn { from{opacity:0; transform:translateX(20px);} to{opacity:1; transform:translateX(0);} }
        .feed-msg.alert { border-color: #ef4444; background: #fef2f2; }
        .feed-head { display: flex; justify-content: space-between; margin-bottom: 0.3rem; opacity: 0.7; font-size: 0.7rem; }
        
        /* 3D MAP CONTAINER (Mini) */
        #mini-map-3d {
            height: 200px;
            border-radius: 16px;
            background: rgba(15, 23, 42, 0.03);
            margin-bottom: 1rem;
            overflow: hidden;
            position: relative;
        }
        #mini-map-3d canvas { width: 100% !important; height: 100% !important; }

        /* DRAWER */
        .cyber-drawer {
            position: fixed; right: -600px; top: 0; width: 550px; height: 100vh;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(50px);
            z-index: 100;
            box-shadow: -20px 0 80px rgba(0,0,0,0.15);
            transition: 0.5s cubic-bezier(0.19, 1, 0.22, 1);
            padding: 3rem; display: flex; flex-direction: column; gap: 2rem;
        }
        .cyber-drawer.open { right: 0; }
        
        .analytics-panel {
            display: flex; flex-direction: column; gap: 1rem;
            height: 100%;
        }
        .metric-card {
            background: white; border-radius: 12px; padding: 1rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border-left: 3px solid var(--neon-cyan);
            transition: 0.3s;
        }
        .metric-card:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.08); }
        .metric-label { font-size: 0.7rem; text-transform: uppercase; color: #64748b; margin-bottom: 0.3rem; font-weight: 700; letter-spacing: 0.5px; }
        .metric-value { font-size: 1.5rem; font-family: 'Rajdhani'; font-weight: 700; color: #0f172a; }
        .material-bar { height: 6px; background: #e2e8f0; border-radius: 3px; margin-top: 5px; overflow:hidden; }
        .material-fill { height: 100%; background: var(--neon-cyan); width: 0%; transition: width 1s; }
        
        .notification-toast {
            position: fixed; bottom: 20px; right: 20px;
            background: #059669; color: white;
            padding: 1rem 2rem; border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 600;
            z-index: 1000;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s ease;
        }
        .notification-toast.show { transform: translateY(0); opacity: 1; }

        /* 3D ITEM VIEWER */

        .notification-toast.show { transform: translateY(0); opacity: 1; }

        /* --- NEW INTEL PANEL STYLES --- */
        .intel-badge {
            background: rgba(16, 185, 129, 0.1); color: #059669; border: 1px solid rgba(16, 185, 129, 0.2);
            font-size: 0.6rem; padding: 2px 8px; border-radius: 99px; font-weight: 700; letter-spacing: 0.5px;
            display: inline-flex; align-items: center; gap: 4px;
        }
        .intel-badge::before { content:''; width:4px; height:4px; background:#059669; border-radius:50%; animation: pulse 2s infinite; }

        .kpi-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 1rem; }
        .kpi-box {
            background: #fff; border-radius: 12px; padding: 0.8rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02); border: 1px solid #f1f5f9;
            transition: 0.3s;
        }
        .kpi-box:hover { transform: translateY(-2px); box-shadow: 0 10px 15px rgba(0,0,0,0.05); }
        .kpi-val { font-size: 1.1rem; font-family: 'Rajdhani'; font-weight: 700; color: #0f172a; }
        .kpi-lbl { font-size: 0.6rem; color: #64748b; font-weight: 600; text-transform: uppercase; margin-bottom: 2px; }
        .kpi-trend { font-size: 0.6rem; display: flex; align-items: center; gap: 3px; }
        .trend-up { color: #059669; } .trend-down { color: #ef4444; }

        .res-row { margin-bottom: 0.8rem; }
        .res-head { display: flex; justify-content: space-between; font-size: 0.7rem; font-weight: 600; margin-bottom: 4px; color: #334155; }
        .prog-track { height: 6px; background: #f1f5f9; border-radius: 3px; overflow: hidden; }
        .prog-fill { height: 100%; border-radius: 3px; width: 0%; transition: width 1s cubic-bezier(0.22, 1, 0.36, 1); }

        .alert-item {
            background: #fff; border-left: 3px solid #ef4444; border-radius: 8px; padding: 0.6rem;
            margin-bottom: 0.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.03);
            display: flex; justify-content: space-between; align-items: center;
            opacity: 0; animation: slideUp 0.3s forwards;
        }
        @keyframes slideUp { from{opacity:0; transform:translateY(10px);} to{opacity:1; transform:translateY(0);} }
    </style>
</head>
<body>
    <div id="toast-container" class="notification-toast">Action Successful</div>

    <!-- 🌐 3D BACKGROUND LAYER -->
    <div id="canvas-viewport"></div>

    <!-- 🖥️ UI LAYER -->
    <div class="hud-layer">
        
        <!-- HEADER -->
        <header>
            <div class="ai-title-container" onmousemove="tiltTitle(event)" onmouseleave="resetTitle()">
                <div class="ai-title-3d" id="dynamic-title" data-text="CONSTRUCTA">CONSTRUCTA</div>
                <div class="ai-caption" id="dynamic-caption">SYSTEM ONLINE</div>
            </div>
            
            <nav style="display:flex; gap:1rem;">
                <a href="admin_dashboard.php" class="sys-btn"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="backend/logout.php" class="sys-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </header>

        <!-- MAIN DASHBOARD -->
        <div class="main-grid">
            
            <!-- LEFT: CONTROL -->
            <div class="panel-container">
                <div class="panel-badge"><i class="fas fa-network-wired"></i> CONTROL_NODE</div>
                
                <div style="margin-top: 2rem;">
                    <div class="t-stat">
                        <div class="t-val" id="total-val">0</div>
                        <div class="t-lbl">Active Orders</div>
                    </div>
                    <div class="t-stat">
                        <div class="t-val" style="color:#ef4444;" id="risk-val">0</div>
                        <div class="t-lbl">Delay Risk</div>
                    </div>
                </div>

                <div class="pipeline-track">
                    <div class="p-node" onclick="filterGrid('Requested')" id="node-Requested">
                        <div class="p-node-name">REQUESTED</div>
                        <div class="p-node-count">Awaiting Approval</div>
                    </div>
                    <div class="p-node" onclick="filterGrid('In Transit')" id="node-Transit">
                        <div class="p-node-name">IN TRANSIT</div>
                        <div class="p-node-count">Logistics Motion</div>
                    </div>
                    <div class="p-node" onclick="filterGrid('At Site')" id="node-Site">
                        <div class="p-node-name">AT SITE</div>
                        <div class="p-node-count">Delivery Confirmed</div>
                    </div>
                    <div class="p-node" onclick="filterGrid('Verified')" id="node-Verified">
                        <div class="p-node-name">VERIFIED</div>
                        <div class="p-node-count">Quality Check</div>
                    </div>
                </div>
            </div>

            <!-- CENTER: DATA MATRIX -->
            <div class="panel-container" style="padding: 1rem;">
                <div class="panel-badge" style="background:var(--neon-purple);"><i class="fas fa-layer-group"></i> DATA_MATRIX</div>
                <div class="holo-grid" id="main-feed">
                    <!-- Cards Injected JS -->
                </div>
            </div>

            <!-- RIGHT: INTELLIGENCE -->
            <div class="panel-container" style="background: rgba(255,255,255,0.9);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                    <span style="font-weight:700; font-size:0.9rem; letter-spacing:0.5px; display:flex; gap:0.5rem; align-items:center;">
                        <i class="fas fa-brain" style="color:#059669;"></i> AI INTELLIGENCE
                    </span>
                    <span class="intel-badge">LIVE ANALYTICS</span>
                </div>
                
                <div class="analytics-panel" id="analytics-container">
                    
                    <!-- 1. EXECUTIVE KPI PANEL -->
                    <div class="kpi-grid">
                        <div class="kpi-box">
                            <div class="kpi-lbl">Active Value</div>
                            <div class="kpi-val" id="kpi-value">₹ 0</div>
                            <div class="kpi-trend trend-up"><i class="fas fa-arrow-up"></i> <span id="kpi-growth">0%</span></div>
                        </div>
                        <div class="kpi-box">
                            <div class="kpi-lbl">Orders</div>
                            <div class="kpi-val" id="kpi-count">0</div>
                            <div class="kpi-trend" style="color:#64748b;">In Progress</div>
                        </div>
                        <div class="kpi-box">
                            <div class="kpi-lbl">Delay Risk</div>
                            <div class="kpi-val" id="kpi-risk" style="color:#ef4444;">0%</div>
                            <div class="kpi-trend trend-down">Predicted</div>
                        </div>
                        <div class="kpi-box">
                            <div class="kpi-lbl">Health Score</div>
                            <div class="kpi-val" style="color:#059669;">94<span style="font-size:0.7rem;">/100</span></div>
                            <div class="kpi-trend trend-up">Excellent</div>
                        </div>
                    </div>

                    <!-- 2. SMART RESOURCE INSIGHTS -->
                    <div class="metric-card" style="border-left:none; border: 1px solid #e2e8f0; box-shadow:none;">
                        <div class="metric-label" style="display:flex; justify-content:space-between;">
                            <span>SMART RESOURCE INSIGHTS</span>
                            <i class="fas fa-cubes" style="opacity:0.5;"></i>
                        </div>
                        <div id="smart-resource-bars" style="margin-top:0.8rem;">
                            <!-- Injected JS -->
                        </div>
                    </div>

                    <!-- 3. AI PRIORITY ALERTS -->
                    <div style="flex:1; display:flex; flex-direction:column; margin-top:0.5rem;">
                        <div class="metric-label" style="margin-bottom:0.5rem;">AI PRIORITY ALERTS</div>
                        <div id="ai-alerts" style="overflow-y:auto; flex:1; padding-bottom:5px;">
                            <!-- Injected JS -->
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- CYBER DRAWER -->
    <div class="cyber-drawer" id="drawer">
        <button onclick="toggleDrawer(false)" style="position:absolute; top:2rem; right:2rem; background:none; border:none; font-size:2rem; cursor:pointer;">&times;</button>
        <h2 style="font-family:'Rajdhani'; font-size:2.5rem;" id="d-id">#000</h2>
        <h3 id="d-proj" style="color:var(--txt-dim);">Project Name</h3>
        
        <!-- 3D ITEM PREVIEW -->

        
        <div style="margin-top:2rem;">
            <label style="font-size:0.7rem; text-transform:uppercase; letter-spacing:1px; font-weight:700;">Update Vector</label>
            <select id="d-status" onchange="updateStatusDb()" style="width:100%; padding:1rem; border-radius:12px; border:1px solid #cbd5e1; margin-top:0.5rem; font-family:'Space Grotesk'; font-weight:600;">
                <option value="Requested">Requested</option>
                <option value="In Transit">In Transit</option>
                <option value="At Site">At Site</option>
                <option value="Verified">Verified</option>
            </select>
        </div>

        <div style="flex:1; background:#f8fafc; border-radius:16px; padding:1.5rem; overflow-y:auto;" id="d-items">
            <!-- Items -->
        </div>
    </div>

    <script>
        // --- 1. ADVANCED 3D NETWORK BACKGROUND (Three.js) ---
        const initNetwork3D = () => {
            const container = document.getElementById('canvas-viewport');
            const scene = new THREE.Scene();
            // Light grey atmospheric fog
            scene.fog = new THREE.FogExp2(0xF0F2F5, 0.02);

            const camera = new THREE.PerspectiveCamera(75, window.innerWidth/window.innerHeight, 0.1, 1000);
            camera.position.z = 20;

            const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            container.appendChild(renderer.domElement);

            // Create Network of Nodes
            const group = new THREE.Group();
            scene.add(group);

            const geometry = new THREE.IcosahedronGeometry(0.5, 1);
            const material = new THREE.MeshBasicMaterial({ color: 0x94a3b8, wireframe: true });
            
            // Generate 50 Random Floating Nodes
            const nodes = [];
            for(let i=0; i<40; i++) {
                const mesh = new THREE.Mesh(geometry, material);
                mesh.position.set(
                    (Math.random() - 0.5) * 40,
                    (Math.random() - 0.5) * 40,
                    (Math.random() - 0.5) * 20
                );
                // Add random scale
                const s = Math.random() * 0.5 + 0.2;
                mesh.scale.set(s,s,s);
                
                // Add velocity custom prop
                mesh.userData = { 
                    vel: new THREE.Vector3((Math.random()-0.5)*0.02, (Math.random()-0.5)*0.02, 0)
                };
                
                nodes.push(mesh);
                group.add(mesh);
            }

            // Connection Lines Geometry
            const lineMat = new THREE.LineBasicMaterial({ color: 0xcbd5e1, opacity: 0.3, transparent: true });
            
            // Animate
            const animate = () => {
                requestAnimationFrame(animate);
                
                // Move camera slowly for parallax
                const time = Date.now() * 0.0005;
                camera.position.x = Math.sin(time) * 2;
                camera.position.y = Math.cos(time) * 2;
                camera.lookAt(0,0,0);

                // Update Nodes
                nodes.forEach(n => {
                    n.rotation.x += 0.01;
                    n.rotation.y += 0.01;
                    n.position.add(n.userData.vel);
                    
                    // Bounce
                    if(Math.abs(n.position.x) > 25) n.userData.vel.x *= -1;
                    if(Math.abs(n.position.y) > 25) n.userData.vel.y *= -1;
                });

                // Dynamic Lines drawing (drawing lines between close nodes)
                // (Optimized: just drawing a few fixed conceptual lines would be better for perf, but lets try dynamic)
                // *For simplicity/perf, lets just rotate the whole group slightly*
                group.rotation.y += 0.001;

                renderer.render(scene, camera);
            };
            animate();
            
            window.addEventListener('resize', () => {
                camera.aspect = window.innerWidth/window.innerHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(window.innerWidth, window.innerHeight);
            });
        };

        // --- 2. PRODUCT 3D VIEWER (High-Fidelity) ---
        let itemScene, itemCamera, itemRenderer, itemGroup;


        // --- 2. MINI 3D MAP OBJECT (Globe or Abstract) ---


        // --- 3. LOGIC APP ---
        let ordersData = [];
        let currentEditingId = null;

        async function appStart() {
            // Run UI animations first to ensure visibility
            animateLogo();
            
            try { initNetwork3D(); } catch(e) { console.error("3D Network Error", e); }
            // MiniMap Removed
            
            loadOrders();
            // AI Stream Removed
        }

        /* --- AI HEADER LOGIC --- */
        const phrases = [
            "CONSTRUCTA",
            "NEXUS CORE",
            "PROCUREMENT AI",
            "GLOBAL OPS"
        ];
        let phraseIndex = 0;

        function animateLogo() {
            cycleTitle();
            setInterval(cycleTitle, 5000); // Change every 5s
        }

        function cycleTitle() {
            const el = document.getElementById('dynamic-title');
            const cap = document.getElementById('dynamic-caption');
            if(!el) return;

            const targetText = phrases[phraseIndex];
            phraseIndex = (phraseIndex + 1) % phrases.length;
            
            // Random Caption based on phrase
            const caps = ["SYSTEM ONLINE", "DATA SYNCING...", "OPTIMIZING...", "LIVE FEED"];
            if(cap) cap.innerText = caps[Math.floor(Math.random()*caps.length)];

            let iteration = 0;
            const letters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789#@$%&";
            
            clearInterval(el.interval);
            
            el.interval = setInterval(() => {
                el.innerText = targetText
                    .split("")
                    .map((letter, index) => {
                        if(index < iteration) {
                            return targetText[index];
                        }
                        return letters[Math.floor(Math.random() * 26)];
                    })
                    .join("");
                
                // Update 3D shadow attr
                el.setAttribute('data-text', el.innerText);

                if(iteration >= targetText.length) { 
                    clearInterval(el.interval);
                }
                
                iteration += 1 / 3; // Speed of decode
            }, 30);
        }



        function animateValue(obj, start, end, duration) {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                const val = Math.floor(progress * (end - start) + start);
                obj.innerText = '₹ ' + val.toLocaleString();
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        }

        // 3D Tilt Effect for Header
        function tiltTitle(e) {
            const el = document.querySelector('.ai-title-3d');
            const rect = el.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const cx = rect.width / 2;
            const cy = rect.height / 2;
            
            const rx = (y - cy) / 5;
            const ry = (x - cx) / -5;
            
            el.style.transform = `rotateX(${rx}deg) rotateY(${ry}deg)`;
        }
        function resetTitle() {
            const el = document.querySelector('.ai-title-3d');
            el.style.transform = 'rotateX(0) rotateY(0)';
        }

        async function loadOrders() {
            try {
                const res = await fetch('backend/admin_fetch_all_orders.php');
                const data = await res.json();
                if(data.status==='success') {
                    ordersData = data.data;
                    // Default View: Hide Verified (Completed)
                    renderTiles(ordersData.filter(x => x.delivery_stage !== 'Verified'));
                    renderAnalytics(ordersData); // Updated Intelligence Panel
                    
                    // Left Panel Stats (Legacy Sync)
                    const active = ordersData.filter(x => !['Verified','Cancelled','Rejected'].includes(x.delivery_stage));
                    document.getElementById('total-val').innerText = active.length;
                    const hazard = active.filter(x => x.delivery_stage === 'Requested').length;
                    document.getElementById('risk-val').innerText = hazard + '⚠️';
                }
            } catch(e) { console.error("Data Sync Failure", e); }
        }

        function renderTiles(list) {
            const grid = document.getElementById('main-feed');
            grid.innerHTML = '';
            
            list.forEach((o, i) => {
                const el = document.createElement('div');
                el.className = 'holo-card';
                el.style.opacity = 0;
                
                // Determine Status Style
                let stClass = '';
                if(o.delivery_stage === 'In Transit') stClass = 'transit';
                if(o.delivery_stage === 'At Site') stClass = 'active';

                const total = parseFloat(o.total_amount).toLocaleString();
                
                el.innerHTML = `
                    <div style="display:flex; justify-content:space-between;">
                        <span class="h-id">#${o.id}</span>
                        <div class="h-status ${stClass}">${o.delivery_stage}</div>
                    </div>
                    <div class="h-title"><i class="fas fa-cube"></i> ${o.project_name || 'Project'}</div>
                    <div style="font-size:0.8rem; color:#64748b;">${o.full_name}</div>
                    <div style="margin-top:1rem; font-weight:700; font-family:'Rajdhani'; font-size:1.2rem;">₹${total}</div>
                `;
                
                el.onclick = () => openOrder(o);
                grid.appendChild(el);

                // GSAP Entrance
                setTimeout(() => {
                    gsap.to(el, { opacity: 1, duration: 0.5, y: -5, ease: 'back.out(1.7)' });
                }, i * 50);
            });
        }

        function openOrder(o) {
            currentEditingId = o.id;
            const d = document.getElementById('drawer');
            d.classList.add('open');
            document.getElementById('d-id').innerText = '#' + o.id;
            document.getElementById('d-proj').innerText = o.project_name;
            document.getElementById('d-status').value = o.delivery_stage;
            
            const list = document.getElementById('d-items');
            list.innerHTML = (o.items||[]).map(item => `
                <div style="display:flex; justify-content:space-between; border-bottom:1px solid #e2e8f0; padding:10px 0;">
                    <b>${item.name}</b>
                    <span style="font-size:0.8rem; color:#64748b;">${item.quantity} ${item.unit}</span>
                </div>
            `).join('');
        }
        
        function toggleDrawer(open) {
            const d = document.getElementById('drawer');
            if(open) d.classList.add('open'); else d.classList.remove('open');
        }

        function renderAnalytics(data) {
            // Data Subsets
            const active = data.filter(o => !['Verified', 'Cancelled', 'Rejected'].includes(o.delivery_stage));
            
            // 1. KPI UPDATES (TOTALS)
            // Total Spend (All Time)
            const totalVal = data.reduce((a,c) => a + parseFloat(c.total_amount||0), 0);
            animateValue(document.getElementById('kpi-value'), 
                parseFloat(document.getElementById('kpi-value').innerText.replace(/[^\d]/g,''))||0, 
                totalVal, 800
            );
            // Dynamic Label Update
            const labelEl = document.querySelector('.kpi-box:nth-child(1) .kpi-lbl');
            if(labelEl) labelEl.innerText = 'Total Spend'; 

            // Order Count (All Time)
            document.getElementById('kpi-count').innerText = data.length;
            // Update subtext to show active portion
            const countLabel = document.querySelector('.kpi-box:nth-child(2) .kpi-trend');
            if(countLabel) countLabel.innerHTML = `<span style="color:#059669; font-weight:700;">${active.length}</span> Active`;

            // Delay Risk (Calculated on Active)
            const risky = active.filter(o => o.delivery_stage === 'Requested').length;
            const riskPct = active.length ? Math.round((risky/active.length)*100) : 0;
            document.getElementById('kpi-risk').innerText = riskPct + '%';
            document.getElementById('kpi-growth').innerText = '4.2%'; 
            
            // 2. RESOURCE INSIGHTS (TOTAL VOLUME)
            const resCounts = {};
            data.forEach(o => { // Use ALL data
                if(o.items) o.items.forEach(i => {
                    const n = i.name.split(' ')[0];
                    resCounts[n] = (resCounts[n]||0) + parseInt(i.quantity);
                });
            });
            const topRes = Object.entries(resCounts).sort((a,b)=>b[1]-a[1]).slice(0,3);
            const maxRes = topRes[0] ? topRes[0][1] : 1;
            
            const resContainer = document.getElementById('smart-resource-bars');
            if(topRes.length === 0) resContainer.innerHTML = '<div style="color:#94a3b8; font-size:0.7rem;">No data available</div>';
            else {
                resContainer.innerHTML = topRes.map(([n, c], i) => {
                    const colors = ['#059669', '#3b82f6', '#8b5cf6'];
                    return `
                    <div class="res-row">
                        <div class="res-head">
                            <span><i class="fas fa-cube" style="color:${colors[i]}; margin-right:4px;"></i> ${n}</span>
                            <span>${c} units</span>
                        </div>
                        <div class="prog-track">
                            <div class="prog-fill" style="width:${(c/maxRes)*100}%; background: linear-gradient(90deg, ${colors[i]}, #fff);"></div>
                        </div>
                    </div>`;
                }).join('');
            }

            // 3. AI ALERTS (Active Only)
            const alertContainer = document.getElementById('ai-alerts');
            let alertsHTML = '';
            
            // Active Requests
            active.filter(o => o.delivery_stage === 'Requested').forEach(o => {
                alertsHTML += `
                <div class="alert-item" onclick="openOrder({id:${o.id}, project_name:'${o.project_name}', delivery_stage:'${o.delivery_stage}', items:${JSON.stringify(o.items).replace(/"/g, "&quot;")}, total_amount:'${o.total_amount}', full_name:'${o.full_name}'})" style="cursor:pointer;">
                    <div>
                        <div style="font-size:0.75rem; font-weight:700; color:#1e293b;">Action: Order #${o.id}</div>
                        <div style="font-size:0.65rem; color:#64748b;">${o.project_name.substring(0,12)}...</div>
                    </div>
                    <div style="font-size:0.7rem; color:#ef4444; font-weight:700;">REVIEW</div>
                </div>`;
            });
            
            if(alertsHTML === '') alertsHTML = '<div style="text-align:center; color:#cbd5e1; font-size:0.8rem; margin-top:1rem;">No Priority Alerts</div>';
            alertContainer.innerHTML = alertsHTML;
        }

        let currentFilter = null;

        function filterGrid(stage) {
            // Toggle Logic: If clicking active filter, reset to default view
            if(currentFilter === stage) {
                currentFilter = null;
                // Show All Active (Excluding Verified)
                const activeOnly = ordersData.filter(x => x.delivery_stage !== 'Verified');
                renderTiles(activeOnly);
                
                document.querySelectorAll('.p-node').forEach(n => n.classList.remove('active'));
                return;
            }

            currentFilter = stage;
            const filtered = ordersData.filter(x => x.delivery_stage === stage);
            renderTiles(filtered);
            
            // Show "No Results" state
            if(filtered.length === 0) {
                document.getElementById('main-feed').innerHTML = `
                    <div style="grid-column:1/-1; display:flex; flex-direction:column; align-items:center; justify-content:center; opacity:0.5; padding:3rem; color:var(--txt-dark);">
                        <i class="fas fa-folder-open" style="font-size:2rem; margin-bottom:1rem;"></i>
                        <span style="font-family:'Rajdhani'; font-size:1.2rem;">No Orders in '${stage}'</span>
                    </div>`;
            }
            
            // Highlight Active Node (Fixed ID Mapping)
            document.querySelectorAll('.p-node').forEach(n => n.classList.remove('active'));
            
            const idMap = {
                'Requested': 'node-Requested',
                'In Transit': 'node-Transit',
                'At Site': 'node-Site',
                'Verified': 'node-Verified'
            };
            
            const nodeId = idMap[stage] || ('node-' + stage.split(' ')[0]);
            const node = document.getElementById(nodeId);
            if(node) node.classList.add('active');
        }

        function updateStats(data) {
            document.getElementById('total-val').innerText = data.length;
            // Fake logic for risk
            const risk = data.filter(x => x.delivery_stage === 'Requested').length;
            document.getElementById('risk-val').innerHTML = `${risk} <span style='font-size:0.8rem'>HIGH</span>`;
        }

        async function updateStatusDb() {
            const val = document.getElementById('d-status').value;
            if(!currentEditingId) return;
            
            try {
                const res = await fetch('backend/admin_update_order_status.php', {
                    method: 'POST', 
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({order_id: currentEditingId, new_status: val})
                });
                
                if(!res.ok) throw new Error(`HTTP Error: ${res.status}`);
                
                // Read text first to debug if not JSON
                const text = await res.text();
                let j;
                try {
                    j = JSON.parse(text);
                } catch(e) {
                    throw new Error("Invalid JSON Access: " + text.substring(0, 50));
                }

                if(j.status === 'success') {
                    // Local Update
                    const order = ordersData.find(x => x.id == currentEditingId);
                    if(order) order.delivery_stage = val;
                    
                    renderTiles(ordersData);
                    updateStats(ordersData);
                    renderAnalytics(ordersData);
                    
                    toggleDrawer(false);
                    toggleDrawer(false);
                    showToast(`Order #${currentEditingId} status updated to ${val}`);
                } else {
                    alert("Update Failed: " + (j.message || 'Unknown Error'));
                }
            } catch(e) { 
                console.error(e);
                alert("Sync Error: " + e.message); 
            }
        }

        function showToast(msg) {
            const toast = document.getElementById('toast-container');
            if(!toast) return;
            toast.innerText = msg;
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }


        window.onload = appStart;

    </script>
</body>
</html>
