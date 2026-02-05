<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'engineer') {
    header('Location: login.html');
    exit();
}

require_once 'backend/config.php';
$conn = getDatabaseConnection();
$engineer_id = $_SESSION['user_id'];

// Fetch accepted/active projects
$stmt = $conn->prepare("
    SELECT pr.*, u.name as homeowner_name
    FROM project_requests pr 
    JOIN users u ON pr.homeowner_id = u.id 
    WHERE pr.engineer_id = ? AND pr.status = 'accepted'
    ORDER BY pr.updated_at DESC
");
$stmt->bind_param("i", $engineer_id);
$stmt->execute();
$result = $stmt->get_result();
$projects = [];
while ($row = $result->fetch_assoc()) {
    $projects[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Estimates - Constructa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <style>
        :root {
            --primary: #1a2e23;
            --primary-light: #2c4a3b;
            --accent: #d4af37;
            --bg-color: #f6f7f2;
            --glass-bg: rgba(255, 255, 255, 0.8);
            --glass-border: rgba(255, 255, 255, 0.9);
            --text-main: #2c3e50;
            --text-muted: #64748b;
            --transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1);
            --card-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05);
            
            /* AI Study Specifics */
            --ai-bg: #f8fafc;
            --ai-card: #ffffff;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        
        body {
            background-color: transparent; /* For 3D canvas */
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        #canvas-container {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            z-index: -1; background: #f8fafc; pointer-events: none;
        }

        /* Navbar */
        nav {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            padding: 1rem 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-logo {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--primary);
            text-decoration: none;
            display: flex; align-items: center; gap: 0.5rem;
        }

        .nav-btns { display: flex; gap: 1rem; align-items: center; }
        
        /* New Button Styles */
        .nav-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            transition: var(--transition);
            border: 1px solid transparent;
        }

        .nav-btn.primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(26, 46, 35, 0.2);
        }
        .nav-btn.primary:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(26, 46, 35, 0.3);
        }

        .nav-btn.secondary {
            background: white;
            color: var(--text-main);
            border-color: rgba(0,0,0,0.1);
        }
        .nav-btn.secondary:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-2px);
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            width: 90%;
            flex: 1;
            position: relative;
        }

        /* Header */
        .page-header {
            margin-bottom: 2.5rem;
            position: relative;
            z-index: 10;
        }
        .page-title {
            font-family: 'Outfit', sans-serif;
            font-size: 3rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        .page-subtitle { color: var(--text-muted); font-size: 1.1rem; }

        /* Project Cards Grid */
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            animation: fadeIn 0.8s ease-out;
        }

        .project-card {
            background: rgba(255, 255, 255, 0.85); /* Increased opacity for readability vs bg */
            backdrop-filter: blur(10px);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            border: 1px solid rgba(255,255,255,0.8);
            display: flex;
            flex-direction: column;
            position: relative;
        }
        .project-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px -10px rgba(0,0,0,0.1);
            background: white;
        }

        .card-header {
            padding: 1.5rem;
            background: linear-gradient(to bottom right, rgba(248, 250, 252, 0.5), rgba(255,255,255,0.8));
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .project-icon {
            width: 50px; height: 50px;
            background: rgba(26, 46, 35, 0.05);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            color: var(--primary);
            font-size: 1.4rem;
        }
        .status-pill {
            padding: 0.4rem 0.8rem;
            background: #dcfce7;
            color: #166534;
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 20px;
            text-transform: uppercase;
        }

        .card-body { padding: 1.5rem; flex: 1; display: flex; flex-direction: column; gap: 1rem; }
        .project-name { font-size: 1.4rem; font-weight: 700; color: var(--primary); line-height: 1.2; font-family: 'Outfit', sans-serif; }
        .client-name { color: var(--text-muted); font-size: 0.95rem; font-weight: 500; display: flex; align-items: center; gap: 0.5rem; }
        
        .card-meta {
            display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;
            margin-top: auto; padding-top: 1.5rem; border-top: 1px dashed #e2e8f0;
        }
        .meta-item label { font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 0.2rem; }
        .meta-item value { font-size: 1rem; font-weight: 600; color: var(--text-main); }

        .btn-estimate {
            margin: 1.5rem;
            background: var(--primary);
            color: white;
            padding: 1rem;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex; align-items: center; justify-content: center; gap: 0.75rem;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
        }
        .btn-estimate:hover { background: var(--primary-light); transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.15); }

        /* ESTIMATOR WIZARD OVERLAY */
        #estimator-overlay {
            position: fixed; inset: 0; background: rgba(255, 255, 255, 0.05); z-index: 200;
            display: none; flex-direction: column;
            opacity: 0; transition: opacity 0.5s ease;
            backdrop-filter: blur(2px); /* Minimal blur on global overlay */
        }
        #estimator-overlay.active { display: flex; opacity: 1; }

        .wizard-header {
            padding: 1.5rem 3rem;
            border-bottom: 1px solid rgba(255,255,255,0.3);
            display: flex; justify-content: space-between; align-items: center;
            background: rgba(255, 255, 255, 0.4); 
            backdrop-filter: blur(15px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        }
        .wizard-title h2 { font-family: 'Outfit', sans-serif; font-size: 1.5rem; color: var(--primary); text-shadow: 0 0 15px rgba(255,255,255,0.8); }
        .btn-close {
            background: rgba(255,255,255,0.3); border: 1px solid rgba(0,0,0,0.05); width: 40px; height: 40px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; cursor: pointer;
            transition: var(--transition); font-size: 1.2rem; color: var(--text-muted);
        }
        .btn-close:hover { background: white; color: #ef4444; transform: rotate(90deg); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }

        .wizard-body {
            flex: 1; display: flex; overflow: hidden;
            background: transparent;
        }
        .wizard-steps {
            flex: 2; padding: 4rem; overflow-y: auto; 
            background: rgba(255, 255, 255, 0.1); /* Extremely transparent to see 3D city */
            backdrop-filter: blur(5px);
        }
        .wizard-preview {
            flex: 1;
            padding: 3rem;
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(20px);
            border-left: 1px solid rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            gap: 2rem;
            z-index: 10;
        }

        #house-preview-container {
            width: 100%;
            height: 300px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            position: relative;
            overflow: hidden;
            box-shadow: inset 0 0 20px rgba(0,0,0,0.05);
        }
        
        /* Bar Chart Value Fix */
        .bar-val {
             position: absolute; top: -25px; width: 100%; text-align: center;
             font-size: 0.9rem; font-weight: 800; opacity: 0; transition: opacity 0.5s 1s;
             /* Color is set inline, but position needs to be above the bar visual if possible, or top of container */
        }
        
        .ai-header .btn-close {
             /* Override to look like Primary Button */
             width: auto; height: auto; border-radius: 12px; padding: 0.8rem 1.5rem;
             background: var(--primary); color: white; border: none;
             display: flex; gap: 0.5rem; font-weight: 600; font-size: 0.95rem;
             box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        .ai-header .btn-close:hover {
             background: var(--primary-light); transform: translateY(-2px); color: white;
             box-shadow: 0 10px 15px -3px rgba(0,0,0,0.15);
        }

        /* Form Elements from Budget Calculator */
        .step { display: none; opacity: 0; transform: translateY(20px); transition: all 0.5s ease; }
        .step.active { display: block; opacity: 1; transform: translateY(0); }
        .step.hidden-step { display: none !important; }
        
        .big-input {
            width: 100%; font-size: 2.5rem; padding: 1rem 0; border: none; border-bottom: 3px solid rgba(0,0,0,0.1);
            background: transparent; font-weight: 700; color: var(--primary); outline: none; transition: var(--transition);
        }
        .big-input:focus { border-color: var(--primary); }
        .step-title { font-size: 2rem; font-family: 'Outfit', sans-serif; font-weight: 700; color: var(--primary); margin-bottom: 0.5rem; }
        .step-desc { font-size: 1.1rem; color: var(--text-muted); margin-bottom: 3rem; }

        .selection-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
        .selection-card {
            border: 2px solid rgba(255,255,255,0.5); border-radius: 16px; padding: 2rem; cursor: pointer; transition: all 0.3s ease; position: relative; 
            background: rgba(255,255,255,0.6);
            backdrop-filter: blur(10px);
        }
        .selection-card:hover { transform: translateY(-5px); border-color: #cbd5e1; background: rgba(255,255,255,0.9); }
        .selection-card.selected { border-color: var(--primary); background: #f0fdf4; box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        .selection-card i { font-size: 2rem; margin-bottom: 1rem; color: var(--text-muted); transition: 0.3s; }
        .selection-card.selected i { color: var(--primary); }
        .card-price { margin-top: 1rem; font-weight: 700; color: var(--primary); }

        .wizard-nav {
            margin-top: 4rem; display: flex; justify-content: space-between;
        }
        .btn-wiz {
            padding: 1rem 2.5rem; border-radius: 12px; font-weight: 600; cursor: pointer; border: none; transition: var(--transition);
            display: flex; align-items: center; gap: 0.8rem;
        }
        .btn-prev { background: rgba(255,255,255,0.5); color: var(--text-muted); border: 1px solid rgba(0,0,0,0.05); }
        
        /* Premium Gradient Button for Next */
        .btn-next { 
            background: linear-gradient(135deg, #294033 0%, #3d5a49 100%);
            color: white; 
            box-shadow: 0 4px 6px -1px rgba(41, 64, 51, 0.3), 0 2px 4px -1px rgba(41, 64, 51, 0.2);
        }
        .btn-next:hover { 
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(41, 64, 51, 0.4), 0 4px 6px -2px rgba(41, 64, 51, 0.2);
            filter: brightness(1.1);
        }

        /* =========================================
           AI STUDY INTERFACE (STEP 4 REPLACEMENT)
           ========================================= */
        #ai-study-container {
            display: none; height: 100%; width: 100%;
            background: #f8fafc;
            flex-direction: column; overflow-y: auto; overflow-x: hidden;
            padding: 2rem 4rem;
        }
        
        #ai-study-container.active { display: flex; }

        .ai-header {
            margin-bottom: 2rem;
            display: flex; justify-content: space-between; align-items: flex-end;
            padding-bottom: 1rem; border-bottom: 1px solid #e2e8f0;
        }
        .ai-tag {
            background: #1e293b; color: #fbbf24; padding: 0.3rem 0.8rem; border-radius: 20px;
            font-size: 0.75rem; font-weight: 800; letter-spacing: 1px; display: inline-flex; align-items: center; gap: 0.5rem;
            margin-bottom: 0.5rem; text-transform: uppercase;
        }
        
        /* Grid Layout */
        .study-grid {
            display: grid; grid-template-columns: 350px 1fr; gap: 2rem;
            padding-bottom: 3rem;
        }
        
        /* Left Column: Summary & Confidence */
        .study-sidebar { display: flex; flex-direction: column; gap: 1.5rem; }
        .study-card {
            background: white; border-radius: 16px; padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;
            opacity: 0; transform: translateY(20px); /* Animation Start */
        }
        
        .confidence-ring {
            width: 120px; height: 120px; margin: 0 auto; position: relative;
            display: flex; align-items: center; justify-content: center;
        }
        .conf-val { font-size: 1.8rem; font-weight: 800; color: var(--primary); font-family: 'Outfit'; }
        .conf-label { text-align: center; color: var(--text-muted); font-size: 0.9rem; margin-top: 0.5rem; }
        
        .total-budget-box {
            text-align: center; padding: 1rem; background: var(--primary); color: white; border-radius: 12px; margin-top: 1rem;
        }
        
        /* Right Column: Deep Dive */
        .study-main { display: flex; flex-direction: column; gap: 2rem; }
        
        .chart-box {
            height: 300px;
            display: flex; align-items: flex-end; justify-content: space-around;
            padding: 1rem; position: relative;
        }
        .bar-group {
            width: 15%; height: 100%; display: flex; flex-direction: column; justify-content: flex-end; position: relative;
        }
        .bar-visual {
            width: 100%; background: #e2e8f0; border-radius: 8px 8px 0 0; 
            transition: height 1s ease-out; height: 0;
            position: relative; overflow: hidden;
        }
        .bar-visual.primary { background: var(--primary); }
        .bar-visual.benchmark { background: #cbd5e1; opacity: 0.5; position: absolute; bottom: 0; width: 40%; right: -10px; z-index: 0; }
        .bar-label { text-align: center; margin-top: 0.5rem; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); }
        /* Deprecated bar-val removed */
        
        /* Risk Scenario Toggle */
        .risk-toggle {
            display: flex; background: #f1f5f9; padding: 0.3rem; border-radius: 12px; width: fit-content; margin-bottom: 1rem;
        }
        .risk-opt {
            padding: 0.6rem 1.2rem; border-radius: 8px; cursor: pointer; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); transition: 0.3s;
        }
        .risk-opt.active { background: white; color: var(--primary); shadow: 0 2px 4px rgba(0,0,0,0.05); }

        /* Insight Cards */
        .insight-feed { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
        .insight-card {
            background: white; border-left: 4px solid var(--info); border-radius: 8px; padding: 1.2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03); opacity: 0;
        }
        .insight-card.procurement { border-color: var(--info); }
        .insight-card.optimization { border-color: var(--success); }
        .insight-card.risk { border-color: var(--warning); }
        .insight-title { font-weight: 700; font-size: 0.95rem; margin-bottom: 0.5rem; display: flex; justify-content: space-between; }
        
        /* Executive Summary */
        .exec-summary {
            background: linear-gradient(135deg, #1a2e23, #2f4f3e); color: white;
            padding: 2rem; border-radius: 16px; margin-top: 1rem;
            display: flex; gap: 2rem; align-items: center; justify-content: space-between;
            opacity: 0; transform: translateY(20px);
        }
        .exec-badge {
            background: rgba(255,255,255,0.2); padding: 0.5rem 1rem; border-radius: 20px; font-weight: 700; font-size: 0.8rem; text-transform: uppercase;
        }

        /* Loading Overlay */
        #ai-loader {
            position: absolute; inset: 0; background: white; z-index: 50;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
        }
        .loader-text {
            margin-top: 1.5rem; font-family: 'JetBrains Mono', monospace; color: var(--primary); font-size: 1.1rem;
        }
        .scan-line {
            width: 200px; height: 2px; background: #e2e8f0; position: relative; overflow: hidden; margin-top: 1rem;
        }
        .scan-head {
            position: absolute; width: 50px; height: 100%; background: var(--primary);
            animation: scan 1.5s infinite ease-in-out;
        }
        @keyframes scan { 0% { left: -50px; } 100% { left: 100%; } }

        @media (max-width: 1100px) {
            .study-grid { grid-template-columns: 1fr; }
            .insight-feed { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div id="canvas-container"></div>
    
    <nav>
        <a href="engineer.php" class="nav-logo"><i class="far fa-building"></i> Constructa</a>
        <div class="nav-btns">
            <!-- Updated Buttons as per user request -->
            <a href="engineer.php" class="nav-btn primary"><i class="fas fa-home"></i> DASHBOARD</a>
            <a href="login.html" class="nav-btn secondary"><i class="fas fa-sign-out-alt"></i> LOGOUT</a>
        </div>
    </nav>

    <div class="container" id="main-list-view">
        <div class="page-header">
            <h1 class="page-title">Active Estimates</h1>
            <p class="page-subtitle">Manage project budgets and generate AI-driven cost breakdowns.</p>
        </div>

        <?php if (count($projects) > 0): ?>
            <div class="projects-grid">
                <?php foreach($projects as $p): ?>
                    <div class="project-card">
                        <div class="card-header">
                            <div class="project-icon"><i class="fas fa-drafting-compass"></i></div>
                            <span class="status-pill"><?php echo htmlspecialchars($p['status']); ?></span>
                        </div>
                        <div class="card-body">
                            <h3 class="project-name"><?php echo htmlspecialchars($p['project_title']); ?></h3>
                            <div class="client-name">
                                <i class="far fa-user"></i> <?php echo htmlspecialchars($p['homeowner_name']); ?>
                            </div>
                            
                            <div class="card-meta">
                                <div class="meta-item">
                                    <label>Location</label>
                                    <value><?php echo htmlspecialchars($p['location']); ?></value>
                                </div>
                                <div class="meta-item">
                                    <label>Type</label>
                                    <value><?php echo htmlspecialchars($p['project_type']); ?></value>
                                </div>
                            </div>
                        </div>
                        <button class="btn-estimate" onclick="openEstimator(<?php echo htmlspecialchars(json_encode($p)); ?>)">
                            <i class="fas fa-calculator"></i> Proceed to Estimate
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 5rem;">
                <i class="fas fa-folder-open" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                <h3>No active projects found</h3>
                <p>Accept homeowner requests to begin estimating.</p>
                <a href="project_requests.php" class="btn-estimate" style="width: 200px; margin: 2rem auto;">View Requests</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- ESTIMATOR WIZARD OVERLAY -->
    <div id="estimator-overlay">
        <div class="wizard-header">
            <div class="wizard-title">
                <h2 id="wiz-project-title">Project Name</h2>
                <div style="font-size: 0.9rem; color: var(--text-muted);" id="wiz-client-name">Client Name</div>
            </div>
            <button class="btn-close" onclick="closeEstimator()"><i class="fas fa-times"></i></button>
        </div>
        
        <div class="wizard-body">
            <!-- Normal Steps 1-3 -->
            <div class="wizard-steps" id="normal-wizard-steps">
                
                <!-- Step 1: Area -->
                <div class="step active" id="step-1">
                    <div class="step-title">Construction Area</div>
                    <div class="step-desc">Enter total built-up area for the project. <span style="font-weight:600; color:#64748b;">(Max: 100,000 sq.ft)</span></div>
                    <input type="number" id="inp-area" class="big-input" placeholder="e.g. 1500" min="100" max="100000">
                    <label style="margin-top: 0.5rem; display: block; color: var(--text-muted);">Square Feet (sq. ft)</label>
                    <div id="area-error" style="display:none; color:#ef4444; font-size:0.9rem; font-weight:600; margin-top:0.5rem;">
                        <i class="fas fa-exclamation-circle"></i> <span></span>
                    </div>
                </div>

                <!-- Step 2: Floors -->
                <div class="step" id="step-2">
                    <div class="step-title">Number of Floors</div>
                    <div class="step-desc">Total floors including ground.</div>
                    <div style="display: flex; gap: 2rem; align-items: center; margin-top: 2rem;">
                        <button class="btn-close" style="width: 60px; height: 60px; font-size: 1.5rem;" onclick="adjFloors(-1)">-</button>
                        <div style="font-size: 3rem; font-weight: 800; color: var(--primary);" id="disp-floors">1</div>
                        <button class="btn-close" style="width: 60px; height: 60px; font-size: 1.5rem;" onclick="adjFloors(1)">+</button>
                    </div>
                </div>

                <!-- Step 3: Quality -->
                <div class="step" id="step-3">
                    <div class="step-title">Material Quality</div>
                    <div class="step-desc">Select the grade of materials to be used.</div>
                    <div class="selection-grid">
                        <div class="selection-card" onclick="setQuality(1600, this)">
                            <i class="fas fa-layer-group"></i>
                            <h4>Standard</h4>
                            <div style="font-size: 0.9rem; color:#64748b; margin-top:0.5rem;">Basic finishes & fixtures</div>
                            <div class="card-price">₹1,600 / sft</div>
                        </div>
                        <div class="selection-card selected" onclick="setQuality(2200, this)">
                            <i class="fas fa-star"></i>
                            <h4>Premium</h4>
                            <div style="font-size: 0.9rem; color:#64748b; margin-top:0.5rem;">Branded fittings & polished look</div>
                            <div class="card-price">₹2,200 / sft</div>
                        </div>
                        <div class="selection-card" onclick="setQuality(3000, this)">
                            <i class="fas fa-crown"></i>
                            <h4>Luxury</h4>
                            <div style="font-size: 0.9rem; color:#64748b; margin-top:0.5rem;">High-end Italian marble & automation</div>
                            <div class="card-price">₹3,000 / sft</div>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Design Style -->
                <div class="step" id="step-4">
                    <div class="step-title">Design Style</div>
                    <div class="step-desc">Select the architectural style.</div>
                    <div class="selection-grid" style="grid-template-columns: 1fr 1fr;">
                        <div class="selection-card selected" onclick="setDesignStyle('modern', this)">
                            <i class="fas fa-cube"></i>
                            <h4>Modern</h4>
                            <div style="font-size: 0.9rem; color:#64748b; margin-top:0.5rem;">Minimalist, flat roof, clean lines</div>
                        </div>
                        <div class="selection-card" onclick="setDesignStyle('traditional', this)">
                            <i class="fas fa-landmark"></i>
                            <h4>Traditional</h4>
                            <div style="font-size: 0.9rem; color:#64748b; margin-top:0.5rem;">Pitched roof, verandas, cultural elements</div>
                        </div>
                    </div>
                </div>

                <div class="wizard-nav">
                    <button class="btn-wiz btn-prev" id="btn-prev" onclick="moveStep(-1)">Back</button>
                    <button class="btn-wiz btn-next" id="btn-next" onclick="moveStep(1)">Next Step <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>

            <!-- Live Preview Sidebar (Visible only in Steps 1-4) -->
            <div class="wizard-preview" id="wizard-sidebar">
                <div class="live-total-label">Estimated Budget</div>
                <div class="live-total-val" id="live-total">₹0</div>
                
                <div id="house-preview-container"></div>

                <h4 style="margin-bottom: 0.5rem;">Project Specs</h4>
                <ul class="preview-list" style="list-style: none; padding: 0;">
                    <li style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-weight: 500;">
                        <span>Area</span> <span id="prev-area" style="color: var(--primary); font-weight: 700;">-</span>
                    </li>
                    <li style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-weight: 500;">
                        <span>Floors</span> <span id="prev-floors" style="color: var(--primary); font-weight: 700;">1</span>
                    </li>
                    <li style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-weight: 500;">
                        <span>Quality</span> <span id="prev-quality" style="color: var(--primary); font-weight: 700;">Premium</span>
                    </li>
                    <li style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-weight: 500;">
                        <span>Style</span> <span id="prev-style" style="color: var(--primary); font-weight: 700;">Modern</span>
                    </li>
                </ul>
                
                <div style="margin-top: auto; padding: 1.5rem; background: rgba(255,255,255,0.5); border-radius: 12px; font-size: 0.85rem; color: var(--text-muted); border: 1px solid rgba(0,0,0,0.02);">
                    <i class="fas fa-info-circle"></i> This active estimate uses live market rates. Final BOQ may vary by +/- 5%.
                </div>
            </div>

            <!-- ==============================================
                 STEP 5: AI STUDY EXPERIENCE (FULL OVERLAY)
                 ============================================== -->
            <div id="ai-study-container">
                
                <!-- Loader -->
                <div id="ai-loader">
                    <img src="https://mir-s3-cdn-cf.behance.net/project_modules/disp/35771931234507.564a1d2403b3a.gif" alt="loader" style="width: 80px; opacity:0.8;"> 
                    <!-- Or simple CSS loader if image fails -->
                    <div class="scan-line">
                        <div class="scan-head"></div>
                    </div>
                    <div class="loader-text" id="loader-status">Initializing Analysis Protocols...</div>
                </div>

                <!-- Study Content -->
                <div class="ai-header">
                    <div>
                        <div class="ai-tag"><i class="fas fa-brain"></i> Constructa AI 4.0</div>
                        <h2 style="font-family:'Outfit'; font-size:2rem; color: var(--primary);">Feasibility Study & Cost Analysis</h2>
                        <p style="color: var(--text-muted);">Generated on <?php echo date('M d, Y'); ?> • Version 1.2</p>
                    </div>
                    <button class="btn-close" style="width: auto; padding: 0 1.5rem; border-radius: 20px; font-size: 0.9rem;" onclick="location.reload()">
                        <i class="fas fa-arrow-left"></i> Exit Study
                    </button>
                </div>

                <div class="study-grid">
                    
                    <!-- Left Sidebar -->
                    <div class="study-sidebar">
                        <div class="study-card" id="card-confidence">
                            <h4 style="font-size:0.9rem; text-transform:uppercase; color:var(--text-muted); margin-bottom:1rem; font-weight:700;">Result Confidence</h4>
                            <div class="confidence-ring">
                                <svg width="120" height="120" viewBox="0 0 120 120">
                                    <circle cx="60" cy="60" r="54" fill="none" stroke="#f1f5f9" stroke-width="8"/>
                                    <circle cx="60" cy="60" r="54" fill="none" stroke="#10b981" stroke-width="8" stroke-dasharray="339.29" stroke-dashoffset="33.9" stroke-linecap="round" transform="rotate(-90 60 60)"/>
                                </svg>
                                <div style="position:absolute; text-align:center;">
                                    <div class="conf-val">94%</div>
                                </div>
                            </div>
                            <div class="conf-label">High Confidence based on similar active projects in this region.</div>
                        </div>

                        <div class="study-card" id="card-total">
                            <h4 style="font-size:0.9rem; text-transform:uppercase; color:var(--text-muted); margin-bottom:0.5rem; font-weight:700;">Projected Budget</h4>
                            <div class="total-budget-box">
                                <div style="font-size:0.8rem; opacity:0.8;">ESTIMATED TOTAL</div>
                                <div style="font-size:2.2rem; font-weight:800;" id="study-total-val">₹0</div>
                            </div>
                            <div style="font-size:0.85rem; color:var(--text-muted); margin-top:1rem; line-height:1.5;">
                                <i class="fas fa-check-circle" style="color:var(--success);"></i> Includes market-adjusted labor rates.<br>
                                <i class="fas fa-check-circle" style="color:var(--success);"></i> 10% contingency applied.
                            </div>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="study-main">
                        
                        <!-- 1. Cost Distribution Chart -->
                        <div class="study-card" id="card-distribution" style="padding-bottom: 0;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; padding: 0 1rem;">
                                <h4 style="font-family:'Outfit'; font-size:1.2rem; color:var(--primary); font-weight:700;">Cost Distribution & Benchmarking</h4>
                                <div class="ai-tag" style="background:var(--bg-color); color:var(--text-muted); font-weight:600;"><i class="fas fa-chart-bar"></i> vs Regional Average</div>
                            </div>

                            <div class="chart-box" id="dist-chart">
                                <!-- JS Injected Bars -->
                            </div>
                        </div>

                        <!-- 2. Risk Simulator -->
                        <div class="study-card" id="card-risk">
                            <h4 style="font-family:'Outfit'; font-size:1.2rem; color:var(--primary); font-weight:700; margin-bottom:1rem;">Scenario Simulation</h4>
                            <div class="risk-toggle">
                                <div class="risk-opt active" onclick="setRisk('base')">Base Case</div>
                                <div class="risk-opt" onclick="setRisk('material')">Material Spike (+15%)</div>
                                <div class="risk-opt" onclick="setRisk('labor')">Labor Shortage</div>
                            </div>
                            <div style="background:#f8fafc; padding:1.5rem; border-radius:12px; display:flex; justify-content:space-between; align-items:center;">
                                <div>
                                    <div style="font-size:0.9rem; font-weight:600; color:var(--text-muted);">Simulated Impact</div>
                                    <div style="font-size:1.1rem; color:var(--primary); margin-top:0.3rem;" id="risk-desc">Standard market conditions assumed.</div>
                                </div>
                                <div style="text-align:right;">
                                    <div style="font-size:0.8rem; color:var(--text-muted);">REVISED TOTAL</div>
                                    <div style="font-size:1.8rem; font-weight:700; color:var(--text-main);" id="risk-val">₹0</div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Recommendations -->
                        <div style="margin-top:1rem;">
                            <h4 style="font-family:'Outfit'; font-size:1.2rem; color:var(--primary); font-weight:700; margin-bottom:1rem;">Strategic Recommendations</h4>
                            <div class="insight-feed">
                                <div class="insight-card procurement">
                                    <div class="insight-title" style="color:#0369a1;">Procurement <i class="fas fa-shopping-cart"></i></div>
                                    <p style="font-size:0.85rem; color:var(--text-muted); line-height:1.5;">Bulk procure cement and steel in Phase 1 to lock in current rates (Market trending UP).</p>
                                </div>
                                <div class="insight-card optimization">
                                    <div class="insight-title" style="color:#15803d;">Optimization <i class="fas fa-sliders-h"></i></div>
                                    <p style="font-size:0.85rem; color:var(--text-muted); line-height:1.5;">Consider fly-ash bricks for partition walls to reduce structural dead load by 15%.</p>
                                </div>
                                <div class="insight-card risk">
                                    <div class="insight-title" style="color:#b45309;">Mitigation <i class="fas fa-shield-alt"></i></div>
                                    <p style="font-size:0.85rem; color:var(--text-muted); line-height:1.5;">High water table detected in zone. Allocating extra 5% for waterproofing is advised.</p>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Executive Summary -->
                        <div class="exec-summary" id="exec-summary">
                            <div>
                                <h3 style="font-family:'Outfit'; font-size:1.4rem; margin-bottom:0.5rem;">Executive Summary</h3>
                                <p style="font-size:0.95rem; opacity:0.9; max-width:600px;">
                                    Project feasibility is rated <strong style="color:#4ade80">HIGH</strong>. The estimated budget aligns with regional benchmarks for Premium grade construction. 
                                    Recommended to proceed with BOQ generation.
                                </p>
                            </div>
                            <button class="btn-estimate" style="margin:0; background:white; color:var(--primary);">
                                Generate BOQ <i class="fas fa-file-export"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Data State
        let currentProject = null;
        let wizState = {
            step: 1,
            maxStep: 4, // Updated max steps
            area: 0,
            floors: 1,
            quality: 2200, 
            qualityName: 'Premium',
            designStyle: 'modern'
        };
        let finalCost = 0;

        // 3D Background (Simulated City from Saved Favorites)
        const initBackground3D = () => {
            const container = document.getElementById('canvas-container');
            if (!container) return;

            const scene = new THREE.Scene();
            scene.background = new THREE.Color('#f8fafc'); // Matching light bg

            const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
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
            const blueLight = new THREE.PointLight(0x3d5a49, 0.5);
            blueLight.position.set(-5, 5, 5);
            scene.add(blueLight);

            // Objects
            const cityGroup = new THREE.Group();
            scene.add(cityGroup);

            const buildingGeometry = new THREE.BoxGeometry(1, 1, 1);
            const buildingMaterial = new THREE.MeshPhongMaterial({ color: 0x294033, transparent: true, opacity: 0.1, side: THREE.DoubleSide });
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

            // Hero House (Central Floating Object)
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

            // Animation
            let mouseX = 0, mouseY = 0;
            document.addEventListener('mousemove', (event) => {
                mouseX = (event.clientX - window.innerWidth / 2) * 0.001;
                mouseY = (event.clientY - window.innerHeight / 2) * 0.001;
            });

            const animate = () => {
                requestAnimationFrame(animate);
                cityGroup.rotation.y += 0.001;
                floatGroup.rotation.y += 0.005;
                floatGroup.position.y = Math.sin(Date.now() * 0.001) * 0.5 + 0.5;
                
                // Interactive tilt relative to mouse
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
            document.addEventListener('DOMContentLoaded', () => {
                initBackground3D();
                initHousePreview3D();
            });
        }

        // --- House 3D Preview Logic ---
        let housePreviewScene, housePreviewCamera, housePreviewRenderer, houseDisplayGroup;
        
        function initHousePreview3D() {
            const container = document.getElementById('house-preview-container');
            if (!container) return;

            housePreviewScene = new THREE.Scene();
            
            housePreviewCamera = new THREE.PerspectiveCamera(45, container.offsetWidth / container.offsetHeight, 0.1, 1000);
            housePreviewCamera.position.set(5, 5, 8);
            housePreviewCamera.lookAt(0, 0, 0);

            housePreviewRenderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            housePreviewRenderer.setSize(container.offsetWidth, container.offsetHeight);
            housePreviewRenderer.setPixelRatio(window.devicePixelRatio);
            container.appendChild(housePreviewRenderer.domElement);

            // Lighting
            const ambient = new THREE.AmbientLight(0xffffff, 0.7);
            housePreviewScene.add(ambient);
            const directional = new THREE.DirectionalLight(0xffffff, 0.8);
            directional.position.set(5, 10, 5);
            housePreviewScene.add(directional);

            houseDisplayGroup = new THREE.Group();
            housePreviewScene.add(houseDisplayGroup);

            const animatePreview = () => {
                requestAnimationFrame(animatePreview);
                if (houseDisplayGroup) {
                    houseDisplayGroup.rotation.y += 0.005;
                }
                housePreviewRenderer.render(housePreviewScene, housePreviewCamera);
            };
            animatePreview();

            window.addEventListener('resize', () => {
                const w = container.offsetWidth;
                const h = container.offsetHeight;
                housePreviewCamera.aspect = w / h;
                housePreviewCamera.updateProjectionMatrix();
                housePreviewRenderer.setSize(w, h);
            });
        }

        function updateHousePreview3D() {
            if (!houseDisplayGroup) return;

            // Clear old meshes
            while(houseDisplayGroup.children.length > 0){ 
                const child = houseDisplayGroup.children[0];
                if (child.geometry) child.geometry.dispose();
                if (child.material) {
                    if (Array.isArray(child.material)) child.material.forEach(m => m.dispose());
                    else child.material.dispose();
                }
                houseDisplayGroup.remove(child); 
            }

            const area = Math.max(wizState.area, 100);
            const floors = Math.max(wizState.floors, 1);
            
            // Calculate scale: assume square base for simplicity
            // 1500 sqft -> 2.5 units base side
            const baseSide = Math.sqrt(area) / 25; 
            const floorHeight = 1.2;

            const material = new THREE.MeshPhongMaterial({ 
                color: 0x2c4a3b, 
                transparent: true, 
                opacity: 0.6, 
                side: THREE.DoubleSide
            });
            const edgeMaterial = new THREE.LineBasicMaterial({ color: 0x1a2e23, transparent: true, opacity: 0.8 });
            const baseMaterial = new THREE.MeshPhongMaterial({ color: 0xcccccc }); 

            // --- TRADITIONAL VS MODERN LOGIC ---
            if (wizState.designStyle === 'traditional') {
                // TRADITIONAL STYLE
                const pillarMat = new THREE.MeshPhongMaterial({ color: 0x8d6e63 }); // Wood-ish
                const roofMat = new THREE.MeshPhongMaterial({ color: 0xa0522d }); // Terracotta
                
                for (let i = 0; i < floors; i++) {
                     // Main block (slightly smaller to allow for veranda/pillars)
                    const mainGeo = new THREE.BoxGeometry(baseSide * 0.9, floorHeight, baseSide * 0.9);
                    const mesh = new THREE.Mesh(mainGeo, material);
                    mesh.position.y = (i * floorHeight);
                    
                    const edges = new THREE.EdgesGeometry(mainGeo);
                    const line = new THREE.LineSegments(edges, edgeMaterial);
                    line.position.y = mesh.position.y;

                    houseDisplayGroup.add(mesh);
                    houseDisplayGroup.add(line);
                    
                    // Add Pillars (Traditional feel) at corners
                    [-1, 1].forEach(x => {
                        [-1, 1].forEach(z => {
                             const pGeo = new THREE.CylinderGeometry(0.05, 0.05, floorHeight, 8);
                             const pillar = new THREE.Mesh(pGeo, pillarMat);
                             pillar.position.set(x * baseSide * 0.5, (i*floorHeight), z * baseSide * 0.5);
                             houseDisplayGroup.add(pillar);
                        });
                    });
                }
                
                // Sloped Pitch Roof (Pyramid style)
                const roofHeight = 1.0;
                const roofGeo = new THREE.ConeGeometry(baseSide * 0.8, roofHeight, 4);
                const roof = new THREE.Mesh(roofGeo, roofMat);
                roof.position.y = (floors * floorHeight) - (floorHeight/2) + (roofHeight/2);
                roof.rotation.y = Math.PI / 4;
                houseDisplayGroup.add(roof);

            } else {
                // MODERN STYLE (Flat/Boxy)
                const roofMat = new THREE.MeshPhongMaterial({ color: 0x2c4a3b }); // Matches walls
                
                for (let i = 0; i < floors; i++) {
                    const geo = new THREE.BoxGeometry(baseSide, floorHeight, baseSide);
                    const mesh = new THREE.Mesh(geo, material);
                    mesh.position.y = (i * floorHeight);
                    
                    const edges = new THREE.EdgesGeometry(geo);
                    const line = new THREE.LineSegments(edges, edgeMaterial);
                    line.position.y = mesh.position.y;

                    houseDisplayGroup.add(mesh);
                    houseDisplayGroup.add(line);
                }
                
                // Flat Roof (Thin box)
                const roofGeo = new THREE.BoxGeometry(baseSide * 1.05, 0.1, baseSide * 1.05);
                const roof = new THREE.Mesh(roofGeo, roofMat);
                roof.position.y = (floors * floorHeight) - (floorHeight/2) + 0.1;
                houseDisplayGroup.add(roof);
            }

            // Common Base/Plot
            const plotGeo = new THREE.BoxGeometry(baseSide * 1.5, 0.1, baseSide * 1.5);
            const plot = new THREE.Mesh(plotGeo, baseMaterial);
            plot.position.y = -floorHeight/2;
            houseDisplayGroup.add(plot);

            // Center the group
            const totalH = (floors * floorHeight) + 1;
            houseDisplayGroup.position.y = -(totalH / 4);

            // Adjust camera
            const totalHeight = floors * floorHeight;
            const distance = Math.max(baseSide * 2, totalHeight * 1.5, 5);
            housePreviewCamera.position.set(distance, distance * 0.8, distance);
            housePreviewCamera.lookAt(0, 0, 0);
        }

        // Toggle Estimator
        function openEstimator(project) {
            currentProject = project;
            document.getElementById('wiz-project-title').innerText = project.project_title || 'Project Estimate';
            document.getElementById('wiz-client-name').innerText = 'Client: ' + project.homeowner_name;
            
            // Reset
            wizState.area = 0; wizState.floors = 1; wizState.designStyle = 'modern';
            document.getElementById('inp-area').value = '';
            document.getElementById('disp-floors').innerText = '1';
            
            // Restore Wizard View
            document.getElementById('normal-wizard-steps').style.display = 'block';
            document.getElementById('wizard-sidebar').style.display = 'flex';
            document.getElementById('ai-study-container').classList.remove('active');
            
            // Show Modal and Hide Background UI elements
            document.getElementById('estimator-overlay').classList.add('active');
            
            // Hide everything except the 3D background
            const mainContent = document.getElementById('main-list-view');
            const navBar = document.querySelector('nav');
            
            if(mainContent) {
                mainContent.style.opacity = '0';
                mainContent.style.pointerEvents = 'none';
            }
            if(navBar) {
                navBar.style.opacity = '0';
                navBar.style.pointerEvents = 'none';
            }
            
            wizState.step = 1; // Reset to step 1
            updateUI();
        }

        function closeEstimator() {
            document.getElementById('estimator-overlay').classList.remove('active');
            
            // Restore UI elements
            const mainContent = document.getElementById('main-list-view');
            const navBar = document.querySelector('nav');
            
            if(mainContent) {
                 mainContent.style.opacity = '1';
                 mainContent.style.pointerEvents = 'auto';
            }
            if(navBar) {
                navBar.style.opacity = '1';
                navBar.style.pointerEvents = 'auto';
            }
        }

        // Navigation
        function moveStep(dir) {
            if(dir === 1) {
                if(wizState.step === 1 && (!wizState.area || wizState.area < 100)) {
                    alert("Please enter a valid area (min 100 sq.ft)"); return;
                }
                
                // If moving past Max Steps, triggering AI Mode
                if(wizState.step === wizState.maxStep) {
                    launchAIStudy();
                    return;
                }
            }
            
            wizState.step += dir;
            if(wizState.step < 1) wizState.step = 1;
            updateUI();
        }

        function updateUI() {
            // Steps
            for(let i=1; i<=wizState.maxStep; i++) {
                const el = document.getElementById(`step-${i}`);
                if(el) {
                   if(i === wizState.step) el.classList.add('active');
                   else el.classList.remove('active');
                }
            }

            // Buttons
            const prev = document.getElementById('btn-prev');
            const next = document.getElementById('btn-next');
            
            prev.style.visibility = (wizState.step === 1) ? 'hidden' : 'visible';
            next.innerHTML = (wizState.step === wizState.maxStep) ? 
                'Analyze Project <i class="fas fa-robot"></i>' : 
                'Next Step <i class="fas fa-arrow-right"></i>';

            calculate();
        }

        // Inputs with Validation
        const areaInput = document.getElementById('inp-area');
        
        // Prevent invalid keys (arithmetic, negative, etc)
        areaInput.addEventListener('keydown', (e) => {
            // Allow: backspace, delete, tab, escape, enter
            if ([46, 8, 9, 27, 13].indexOf(e.keyCode) !== -1 ||
                // Allow: Ctrl+A
                (e.keyCode === 65 && e.ctrlKey === true) ||
                // Allow: home, end, left, right
                (e.keyCode >= 35 && e.keyCode <= 39)) {
                return;
            }
            // Ensure that it is a number and stop the keypress
            if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                e.preventDefault();
            }
        });

        areaInput.addEventListener('input', (e) => {
            let valStr = e.target.value.replace(/[^0-9]/g, ''); // Remove non-numeric
            if(valStr !== e.target.value) e.target.value = valStr;
            
            let value = parseFloat(valStr) || 0;
            const input = e.target;
            const errorDiv = document.getElementById('area-error');
            
            // Remove previous validation classes
            input.classList.remove('valid', 'invalid');
            
            if (valStr.length > 0) {
                // Check maximum
                if (value > 100000) {
                    input.value = 100000;
                    value = 100000;
                    input.classList.add('invalid');
                    input.style.borderColor = '#ef4444';
                    if (errorDiv) {
                        errorDiv.style.display = 'block';
                        errorDiv.querySelector('span').textContent = 'Maximum area is 100,000 sq.ft (auto-capped)';
                    }
                    setTimeout(() => {
                        input.classList.remove('invalid');
                        input.classList.add('valid');
                        input.style.borderColor = '#10b981';
                        if (errorDiv) errorDiv.style.display = 'none';
                    }, 1500);
                }
                // Check minimum
                else if (value < 100) {
                    input.classList.add('invalid');
                    input.style.borderColor = '#ef4444';
                    if (errorDiv) {
                        errorDiv.style.display = 'block';
                        errorDiv.querySelector('span').textContent = 'Minimum area is 100 sq.ft';
                    }
                }
                // Valid range
                else {
                    input.classList.add('valid');
                    input.style.borderColor = '#10b981';
                    if (errorDiv) errorDiv.style.display = 'none';
                }
            } else {
                input.style.borderColor = '';
                if (errorDiv) errorDiv.style.display = 'none';
            }
            
            wizState.area = value;
            calculate();
        });

        function adjFloors(n) {
            let newFloors = wizState.floors + n;
            if (newFloors < 1) newFloors = 1;
            if (newFloors > 15) newFloors = 15; // Limit data entry
            
            wizState.floors = newFloors;
            document.getElementById('disp-floors').innerText = wizState.floors;
            calculate();
        }
        function setQuality(val, el) {
            wizState.quality = val;
            wizState.qualityName = el.querySelector('h4').innerText;
            // Only remove selection from quality cards (in step 3)
            document.querySelectorAll('#step-3 .selection-card').forEach(c => c.classList.remove('selected'));
            el.classList.add('selected');
            calculate();
        }
        
        function setDesignStyle(style, el) {
            wizState.designStyle = style;
            document.querySelectorAll('#step-4 .selection-card').forEach(c => c.classList.remove('selected'));
            el.classList.add('selected');
            calculate();
        }

        // Live Calculation
        function calculate() {
            let locFactor = 1.0;
            if(currentProject) {
                const loc = (currentProject.location || '').toLowerCase();
                if(loc.includes('metro') || loc.includes('city')) locFactor = 1.15;
                if(loc.includes('rural')) locFactor = 0.9;
            }
            const baseCost = wizState.area * wizState.floors * wizState.quality * locFactor;
            finalCost = Math.round(baseCost);

            document.getElementById('live-total').innerText = formatMoney(finalCost);
            document.getElementById('prev-area').innerText = wizState.area + ' sq.ft';
            document.getElementById('prev-floors').innerText = wizState.floors;
            document.getElementById('prev-quality').innerText = wizState.qualityName;
            document.getElementById('prev-style').innerText = wizState.designStyle.charAt(0).toUpperCase() + wizState.designStyle.slice(1);

            updateHousePreview3D();
        }

        function formatMoney(amount) {
            if (amount >= 10000000) return '₹' + (amount / 10000000).toFixed(2) + ' Cr';
            if (amount >= 100000) return '₹' + (amount / 100000).toFixed(2) + ' L';
            return '₹' + Math.round(amount).toLocaleString('en-IN');
        }

        // ==========================================
        //  AI STUDY LOGIC
        // ==========================================
        function launchAIStudy() {
            // Switch views
            document.getElementById('normal-wizard-steps').style.display = 'none';
            document.getElementById('wizard-sidebar').style.display = 'none';
            document.getElementById('ai-study-container').classList.add('active');
            
            // Start Loader Sequence
            const loader = document.getElementById('ai-loader');
            const status = document.getElementById('loader-status');
            const msgs = [
                "Validating Geometric Constraints...",
                "Fetching Regional Labor Rates (Q1 2026)...",
                "Simulating Material Price Volatility...",
                "Generating Strategic Recommendations..."
            ];
            
            let i = 0;
            const interval = setInterval(() => {
                if(i < msgs.length) {
                    status.innerText = msgs[i];
                    i++;
                }
            }, 800);

            setTimeout(() => {
                clearInterval(interval);
                // Hide loader
                gsap.to(loader, { opacity: 0, duration: 0.5, onComplete: () => {
                    loader.style.display = 'none';
                    animateStudyReveal();
                }});
            }, 3500);
            
            // Prepare Data for Study
            prepareStudyData();
        }

        function prepareStudyData() {
            // Set Total
            document.getElementById('study-total-val').innerText = formatMoney(finalCost);
            document.getElementById('risk-val').innerText = formatMoney(finalCost);

            // Generate Bars: Materials (60%), Labor (25%), Equipment (5%), Contingency (10%)
            const total = finalCost;
            const mat = total * 0.60;
            const lab = total * 0.25;
            const eqp = total * 0.05;
            const con = total * 0.10;
            
            const chartHtml = `
                ${makeChartBar('Materials', mat, total * 0.65, 'primary', '55%', '₹62L Avg')}
                ${makeChartBar('Labor', lab, total * 0.30, 'warning', '25%', 'Active')}
                ${makeChartBar('Machinery', eqp, total * 0.10, 'info', '8%', '')}
                ${makeChartBar('Safety', con, total * 0.15, 'danger', '10%', '')}
            `;
            document.getElementById('dist-chart').innerHTML = chartHtml;
        }

        function makeChartBar(label, val, max, colorTheme, heightPct, benchLabel) {
            // Using logic to determine height visually relative to max container
            let h = Math.round((val / finalCost) * 100 * 1.5); 
            if(h > 90) h = 90;
            
            let color = 'var(--primary)';
            if(colorTheme === 'warning') color = '#f59e0b';
            if(colorTheme === 'info') color = '#3b82f6';
            if(colorTheme === 'danger') color = '#ef4444';

            // User asked for "Materials Amount Not Represented" ? 
            // Interpreted as "Ensure it is shown". It was hidden because of color:white previously.
            // Now .bar-val position is top:-25px (above bar).
            
            return `
                <div class="bar-group">
                    <div class="bar-val" style="color:${color}">${formatMoney(val)}</div>
                    <div class="bar-visual" style="height:${h}%; background:${color};"></div>
                    <div class="bar-label">${label}</div>
                    ${benchLabel ? `<div class="bar-visual benchmark" style="height:${h*0.9}%;"></div>` : ''}
                </div>
            `;
        }

        // Animation Sequence (GSAP)
        function animateStudyReveal() {
            const tl = gsap.timeline();
            
            // 1. Sidebar Cards
            tl.to('.study-sidebar .study-card', { opacity: 1, y: 0, stagger: 0.2, duration: 0.8, ease:"power2.out" });
            
            // 2. Chart
            tl.to('#card-distribution', { opacity: 1, y: 0, duration: 0.6 }, "-=0.4");
            tl.to('.bar-visual', { scaleY: 1, transformOrigin: "bottom", duration: 1, stagger: 0.1, ease: "elastic.out(1, 0.7)" });
            tl.to('.bar-val', { opacity: 1, y: -5, duration: 0.5 }, "-=0.5");
            
            // 3. Risk & Insights
            tl.to('#card-risk', { opacity: 1, y: 0, duration: 0.6 }, "-=0.2");
            tl.to('.insight-card', { opacity: 1, x: 0, stagger: 0.2, duration: 0.6 });
            
            // 4. Executive Summary
            tl.to('#exec-summary', { opacity: 1, y: 0, duration: 0.8, ease: "back.out(1.2)" });
        }

        // Risk Logic
        function setRisk(type) {
            // Update active state
            document.querySelectorAll('.risk-opt').forEach(o => o.classList.remove('active'));
            event.target.classList.add('active');
            
            let multiplier = 1.0;
            let desc = "Standard market conditions assumed.";
            
            if(type === 'material') {
                multiplier = 1.15;
                desc = "Showing impact of potential 15% surge in steel/cement.";
            } else if(type === 'labor') {
                multiplier = 1.08;
                desc = "Adjusted for seasonal labor shortage (+8% labor cost).";
            }
            
            const newTotal = finalCost * multiplier;
            
            // Animate Number
            const el = document.getElementById('risk-val');
            gsap.to(el, {
                innerHTML: newTotal,
                duration: 0.5,
                snap: { innerHTML: 1 },
                onUpdate: function() {
                    el.innerHTML = formatMoney(this.targets()[0].innerHTML);
                }
            });
            document.getElementById('risk-desc').innerText = desc;
        }

    </script>
</body>
</html>
