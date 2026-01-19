<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.html');
    exit();
}

require_once 'backend/config.php';
$conn = getDatabaseConnection();

$query = "SELECT * FROM house_templates ORDER BY created_at DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Templates - Constructa Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <style>
        :root {
            --bg-color: #f0f2f5;
            --text-dark: #1e293b;
            --text-gray: #64748b;
            --primary-blue: #3b82f6;
            --primary-green: #10b981;
            --card-border: rgba(255, 255, 255, 0.5);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }

        body {
            background-color: transparent;
            color: var(--text-dark);
            min-height: 100vh;
            display: flex; flex-direction: column;
        }

        #canvas-container {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            z-index: -1; pointer-events: none;
        }

        /* Navbar */
        header {
            padding: 1.2rem 3rem;
            display: flex; justify-content: space-between; align-items: center;
            max-width: 1600px; margin: 0 auto; width: 100%;
            background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(20px);
            position: sticky; top: 0; z-index: 100;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .logo {
            display: flex; align-items: center; gap: 0.8rem;
            font-size: 1.4rem; font-weight: 700; color: #1e293b; text-decoration: none;
        }
        .top-nav-btn {
            padding: 0.6rem 1.2rem; background: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(0,0,0,0.05); border-radius: 8px;
            text-decoration: none; font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem; color: var(--text-dark); font-weight: 600;
            transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem;
        }
        .top-nav-btn:hover { background: #fff; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }

        .container {
            max-width: 1400px; margin: 3rem auto; padding: 0 2rem; width: 100%;
            animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .page-header {
            display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem;
        }
        .page-title h1 {
            font-size: 2.5rem; font-weight: 800;
            background: linear-gradient(135deg, #1e293b 0%, #475569 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        /* Controls */
        .controls-bar {
            background: rgba(255, 255, 255, 0.6); backdrop-filter: blur(15px);
            padding: 0.8rem; border-radius: 16px; border: 1px solid rgba(255,255,255,0.6);
            display: flex; gap: 1rem; align-items: center; margin-bottom: 2rem;
        }

        .btn-primary {
            background: var(--primary-blue); color: white; border: none;
            padding: 0.8rem 1.5rem; border-radius: 12px; font-weight: 600; cursor: pointer;
            transition: 0.3s; display: flex; align-items: center; gap: 0.5rem;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(59, 130, 246, 0.3); }

        /* List Container */
        .template-grid {
            display: flex;
            flex-direction: column;
            gap: 2rem;
            max-width: 1000px;
            margin: 0 auto;
        }

        /* --- AI CARD STYLE (Split View) --- */
        .ai-card {
            background: white;
            border: 1px solid rgba(0,0,0,0.08);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 380px;
        }
        
        .ai-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.1);
        }

        /* Left: Image Side */
        .compare-view {
            position: relative;
            height: 100%;
            background: #f1f5f9;
            overflow: hidden;
        }
        
        .compare-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .verified-badge {
            position: absolute;
            top: 1rem; left: 1rem;
            background: rgba(16, 185, 129, 0.9);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            backdrop-filter: blur(4px);
            z-index: 20;
            display: flex; align-items: center; gap: 0.4rem;
        }

        /* Right: Content Side */
        .ai-content {
            padding: 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .ai-header { margin-bottom: 1rem; }
        
        .ai-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            line-height: 1.2;
        }

        /* Chips */
        .reasoning-grid {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }
        .reasoning-chip {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-size: 0.75rem;
            color: var(--text-gray);
            font-weight: 500;
            display: flex; align-items: center; gap: 0.4rem;
        }
        .reasoning-chip i { color: var(--primary-green); }

        /* Metrics */
        .metrics-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .metric-group div:first-child { 
            font-size: 0.75rem; color: var(--text-gray); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;
        }
        .metric-val { font-size: 1.4rem; font-weight: 700; color: var(--text-dark); }
        .metric-val.green { color: var(--primary-green); }

        /* Timeline Box */
        .timeline-box { margin-bottom: 1.5rem; }
        .timeline-bar {
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 0.5rem;
            display: flex;
        }
        .t-seg { height: 100%; }
        .t-seg.design { width: 15%; background: #94a3b8; }
        .t-seg.approval { width: 25%; background: #f59e0b; }
        .t-seg.build { width: 60%; background: #10b981; }

        /* Actions Footer */
        .action-row {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        /* Delete Button - Styled like Back to Dashboard Nav Button */
        .btn-delete-lg {
            padding: 0.6rem 1.2rem; 
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(0,0,0,0.05); 
            border-radius: 8px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem; 
            color: var(--text-dark); 
            font-weight: 600;
            transition: all 0.3s ease; 
            display: inline-flex; 
            align-items: center; 
            gap: 0.5rem;
            cursor: pointer;
            width: auto; height: auto; /* Reset from previous square style */
        }
        .btn-delete-lg:hover { 
            background: #fff; 
            transform: translateY(-2px); 
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            color: #dc2626; /* Red text on hover for indication */
        }

        /* Edit Button (Custom adaptation of btn-view style) */
        .btn-edit-lg {
            padding: 0.8rem 1.5rem; border-radius: 12px; border: none;
            background: linear-gradient(135deg, #294033 0%, #3d5a49 100%);
            color: white; font-weight: 600; cursor: pointer;
            box-shadow: 0 4px 6px -1px rgba(41, 64, 51, 0.3), 0 2px 4px -1px rgba(41, 64, 51, 0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex; align-items: center; justify-content: center; gap: 0.6rem;
        }
        .btn-edit-lg:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(41, 64, 51, 0.4), 0 4px 6px -2px rgba(41, 64, 51, 0.2);
            filter: brightness(1.1);
        }

        .static-info-grid {
             display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;
             margin-bottom: 1.5rem;
             background: #f8fafc; padding: 1rem; border-radius: 12px;
        }
        .info-item label { font-size: 0.75rem; color: var(--text-gray); display: block; }
        .info-item span { font-weight: 600; color: var(--text-dark); }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        @media (max-width: 768px) {
            .ai-card { grid-template-columns: 1fr; }
            .compare-view { height: 250px; }
        }
        /* Modal */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); backdrop-filter: blur(5px);
            z-index: 1000; display: none; align-items: center; justify-content: center;
        }
        .modal-overlay.active { display: flex; animation: fadeIn 0.3s; }
        
        .modal-content {
            background: white; width: 90%; max-width: 600px; padding: 2.5rem;
            border-radius: 24px; box-shadow: 0 25px 50px rgba(0,0,0,0.2);
            max-height: 90vh; overflow-y: auto;
        }

        .form-group { margin-bottom: 1.2rem; }
        .form-label { display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem; }
        .form-input, .form-textarea {
            width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 8px;
            font-size: 0.95rem; outline: none; transition: 0.2s;
        }
        .form-input:focus { border-color: var(--primary-blue); box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

    </style>
</head>
<body>
    <div id="canvas-container"></div>

    <header>
        <a href="admin_dashboard.php" class="logo"><i class="fas fa-shield-alt"></i> Constructa Admin</a>
        <nav>
            <a href="admin_dashboard.php" class="top-nav-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </nav>
    </header>

    <div class="container">
        <div class="page-header">
            <div class="page-title">
                <h1>Template Management</h1>
                <p style="color:var(--text-gray);">Add, edit, and organize house plan templates.</p>
            </div>
            <button class="btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> Add New Template</button>
        </div>

        <div class="template-grid">
            <?php 
            while($row = $result->fetch_assoc()): 
                $chips = array_filter(explode(',', $row['description']));
                $timeline = ($row['floors'] * 3) + 4; // Simplified timeline algo
                $efficiency = rand(88, 99); // Mock efficiency score
            ?>
            <div class="ai-card">
                <!-- Left: Visual -->
                <div class="compare-view">
                    <div class="verified-badge"><i class="fas fa-certificate"></i> Verified</div>
                    <img src="<?php echo htmlspecialchars($row['image_url'] ?: 'https://via.placeholder.com/600x400?text=No+Image'); ?>" class="compare-img" alt="Plan">
                </div>

                <!-- Right: Content -->
                <div class="ai-content">
                    <div>
                        <div class="ai-header">
                            <div class="ai-title"><?php echo htmlspecialchars($row['title']); ?></div>
                            <div class="reasoning-grid">
                                <?php foreach(array_slice($chips, 0, 3) as $chip): ?>
                                <div class="reasoning-chip"><i class="fas fa-check-circle"></i> <?php echo trim($chip); ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Static Specs (No Sliders) -->
                        <div class="static-info-grid">
                            <div class="info-item">
                                <label>Built-up Area</label>
                                <span><?php echo $row['area_sqft']; ?> sqft</span>
                            </div>
                            <div class="info-item">
                                <label>Style / Floors</label>
                                <span><?php echo $row['style']; ?> / <?php echo $row['floors']; ?> Flr</span>
                            </div>
                        </div>

                        <div class="metrics-row">
                            <div class="metric-group">
                                <div>Est. Cost</div>
                                <div class="metric-val green">₹<?php echo number_format($row['budget_min']/100000, 2); ?> Lakhs</div>
                            </div>
                            <div class="metric-group">
                                <div>Timeline</div>
                                <div class="metric-val"><?php echo $timeline; ?> Months</div>
                            </div>
                        </div>

                        <div class="timeline-box">
                            <div class="timeline-bar">
                                <div class="t-seg design"></div>
                                <div class="t-seg approval"></div>
                                <div class="t-seg build"></div>
                            </div>
                             <div style="display:flex; justify-content:space-between; font-size:0.7rem; color:var(--text-gray); margin-top:4px;">
                                <span>Design</span>
                                <span>Permits</span>
                                <span>Construction</span>
                            </div>
                        </div>
                    </div>

                    <div class="action-row">
                        <button class="btn-action-lg btn-delete-lg" onclick="deleteTemplate(<?php echo $row['id']; ?>)">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- ADVANCED TEMPLATE BUILDER WIZARD (Budget Calculator Style) -->
    <div class="modal-overlay" id="templateBuilder">
        <!-- Close Button (Absolute) -->
        <button class="btn-close-fixed" onclick="closeBuilder()"><i class="fas fa-times"></i></button>

        <div class="app-container-modal">
            <!-- Left: Wizard Form -->
            <div class="wizard-section">
                <!-- Progress Header -->
                <div class="progress-header">
                    <div class="step-indicator">Step <span id="currentStepNum">1</span> of 4</div>
                    <div class="progress-track">
                        <div class="progress-fill" id="progressBar"></div>
                    </div>
                </div>

                <form id="wizardForm">
                    <!-- Step 1: Design Identity -->
                    <div class="step active" id="step1">
                        <h2 class="step-title">Let's name your masterpiece.</h2>
                        <p class="step-desc">Give your template a catchy title and define its architectural identity.</p>
                        
                        <div class="form-group mb-4" style="margin-top:2rem;">
                            <!-- MATCHING BUDGET CALC STYLE: Input first, barely visible label below or placeholder -->
                            <input type="text" name="title" class="big-input" placeholder="e.g. Modern Sunset Villa" oninput="updatePreview('title', this.value)">
                            <label style="display:block; margin-top:0.8rem; color:var(--text-muted); font-size:0.9rem;">Template Title</label>
                        </div>

                        <div class="form-group" style="margin-top:3rem;">
                            <h3 style="font-size:1.5rem; color:#1e293b; font-weight:700; margin-bottom:1rem;">Architectural Style</h3>
                            <div class="options-grid">
                                <div class="selection-card style-opt selected" onclick="selectStyle('Modern', this)">
                                    <div class="check-mark"><i class="fas fa-check"></i></div>
                                    <div class="icon"><i class="fas fa-city"></i></div>
                                    <div class="card-title">Modern</div>
                                    <div class="card-subtitle">Clean lines, minimalism, and glass facades.</div>
                                </div>
                                <div class="selection-card style-opt" onclick="selectStyle('Traditional', this)">
                                    <div class="check-mark"><i class="fas fa-check"></i></div>
                                    <div class="icon"><i class="fas fa-gopuram"></i></div>
                                    <div class="card-title">Traditional</div>
                                    <div class="card-subtitle">Cultural heritage with intricate details.</div>
                                </div>
                                <div class="selection-card style-opt" onclick="selectStyle('Contemporary', this)">
                                    <div class="check-mark"><i class="fas fa-check"></i></div>
                                    <div class="icon"><i class="fas fa-layer-group"></i></div>
                                    <div class="card-title">Contemporary</div>
                                    <div class="card-subtitle">Current trends, fluid curves, and eco-friendly.</div>
                                </div>
                                <div class="selection-card style-opt" onclick="selectStyle('Vastu', this)">
                                    <div class="check-mark"><i class="fas fa-check"></i></div>
                                    <div class="icon"><i class="fas fa-compass"></i></div>
                                    <div class="card-title">Vastu Compliant</div>
                                    <div class="card-subtitle">Optimized for cosmic energy and harmony.</div>
                                </div>
                            </div>
                            <input type="hidden" name="style" id="styleInput" value="Modern">
                        </div>
                    </div>

                    <!-- Step 2: Specifications -->
                    <div class="step" id="step2">
                        <h2 class="step-title">Technical Specifications.</h2>
                        <p class="step-desc">Define the area, floors, and dimensions for this template.</p>
                        
                        <div class="form-group mb-4" style="margin-top:2rem;">
                            <input type="number" name="area_sqft" class="big-input" value="1200" oninput="updatePreview('area', this.value)">
                            <label style="display:block; margin-top:0.8rem; color:var(--text-muted); font-size:0.9rem;">Built-up Area (sq. ft)</label>
                        </div>

                        <div class="form-group mb-4" style="margin-top:3rem;">
                            <h3 style="font-size:1.5rem; color:#1e293b; font-weight:700; margin-bottom:1rem;">Number of Floors</h3>
                            <div class="counter-wrapper">
                                <button type="button" class="counter-btn" onclick="adjustFloors(-1)"><i class="fas fa-minus"></i></button>
                                <div class="counter-display" id="floorDisplay">1</div>
                                <button type="button" class="counter-btn" onclick="adjustFloors(1)"><i class="fas fa-plus"></i></button>
                            </div>
                            <input type="hidden" name="floors" id="floorInput" value="1">
                        </div>

                        <div class="form-group" style="margin-top:3rem;">
                             <h3 style="font-size:1.5rem; color:#1e293b; font-weight:700; margin-bottom:1rem;">Budget Estimate</h3>
                             <div style="display:flex; gap:1rem; align-items:center;">
                                <span style="font-size:2.5rem; font-weight:700; color:#cbd5e1;">₹</span>
                                <input type="number" name="budget_min" class="big-input" placeholder="2500000" oninput="updatePreview('cost', this.value)">
                             </div>
                             <label style="display:block; margin-top:0.8rem; color:var(--text-muted); font-size:0.9rem;">Min Cost Estimate</label>
                        </div>
                    </div>

                    <!-- Step 3: Visuals & Tags -->
                    <div class="step" id="step3">
                        <h2 class="step-title">Visual Assets.</h2>
                        <p class="step-desc">Upload the render and tag key features.</p>
                        
                        <div class="upload-zone" id="dropZone" style="margin-top:2rem;">
                            <input type="file" name="image" id="fileInput" hidden accept="image/*">
                            <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                            <h3>Drag & Drop 3D Render</h3>
                            <p>or <button type="button" id="browseBtn" style="background:none; border:none; color:var(--primary-blue); font-weight:700; cursor:pointer;">browse files</button></p>
                            <p class="file-meta">High-res JPG/PNG recommended</p>
                        </div>
                        
                         <div class="form-group mt-4" style="margin-top:3rem;">
                            <h3 style="font-size:1.5rem; color:#1e293b; font-weight:700; margin-bottom:1rem;">Smart Tags</h3>
                            <div class="tags-grid">
                                <div class="tag-card" onclick="toggleTag('Eco-Friendly', this)">Eco-Friendly</div>
                                <div class="tag-card" onclick="toggleTag('Luxury', this)">Luxury</div>
                                <div class="tag-card" onclick="toggleTag('Budget', this)">Budget</div>
                                <div class="tag-card" onclick="toggleTag('Garden', this)">Garden</div>
                                <div class="tag-card" onclick="toggleTag('Open Plan', this)">Open Plan</div>
                                <div class="tag-card" onclick="toggleTag('Duplex', this)">Duplex</div>
                                <div class="tag-card" onclick="toggleTag('Compact', this)">Compact</div>
                                <div class="tag-card" onclick="toggleTag('Smart Home', this)">Smart Home</div>
                            </div>
                            <input type="hidden" name="description" value="">
                        </div>
                    </div>

                    <!-- Step 4: Publish -->
                    <div class="step" id="step4">
                        <h2 class="step-title">Ready to Publish?</h2>
                        <p class="step-desc">Review the preview card on the right perfectly matches your intent.</p>
                        
                        <div class="publish-status-card" style="margin-top:2rem;">
                            <div style="display:flex; align-items:center; gap:1.5rem;">
                                <div style="width:60px; height:60px; background:#dcfce7; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#16a34a; font-size:1.8rem;">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div>
                                    <h4 style="font-weight:800; font-size:1.2rem; margin-bottom:0.2rem; color:#1e293b;">Ready to Launch</h4>
                                    <p style="font-size:1rem; color:#64748b;">This template will be visible to all users.</p>
                                </div>
                            </div>
                            <div class="toggle-switch">
                                <input type="checkbox" checked>
                                <span class="slider"></span>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Navigation -->
                <div class="wizard-nav">
                    <button type="button" class="btn btn-secondary" id="prevBtn" onclick="changeStep(-1)" disabled>
                        <i class="fas fa-arrow-left"></i> Back
                    </button>
                    <button type="button" class="btn btn-primary" id="nextBtn" onclick="changeStep(1)">
                        Next Step <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- Right: Live Preview -->
            <div class="preview-section-sidebar">
                <h3 class="preview-title"><i class="fas fa-bolt"></i> Live Card Preview</h3>
                <div class="preview-container-3d">
                    <!-- REUSED AI CARD STRUCTURE -->
                    <div class="ai-card preview-card">
                        <div class="compare-view">
                            <div class="verified-badge"><i class="fas fa-certificate"></i> Engineer Verified</div>
                            <img src="https://via.placeholder.com/600x400?text=Upload+Render" id="previewImg" class="compare-img">
                        </div>
                        <div class="ai-content">
                            <div>
                                <div class="ai-header">
                                    <div class="ai-title" id="previewTitle">Untitled Template</div>
                                    <div class="reasoning-grid" id="previewTags">
                                        <div class="reasoning-chip"><i class="fas fa-check-circle"></i> New Design</div>
                                    </div>
                                </div>
                                <div class="static-info-grid">
                                    <div class="info-item">
                                        <label>Built-up Area</label>
                                        <span id="previewArea">1200 sqft</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Style / Floors</label>
                                        <span id="previewStyleFloor">Modern / 1 Flr</span>
                                    </div>
                                </div>
                                <div class="metrics-row">
                                    <div class="metric-group">
                                        <div>Est. Cost</div>
                                        <div class="metric-val green" id="previewCost">₹0.0 Lakhs</div>
                                    </div>
                                    <div class="metric-group">
                                        <div>Timeline</div>
                                        <div class="metric-val" id="previewTime">6 Months</div>
                                    </div>
                                </div>
                                <div class="timeline-box">
                                    <div class="timeline-bar">
                                        <div class="t-seg design"></div>
                                        <div class="t-seg approval"></div>
                                        <div class="t-seg build"></div>
                                    </div>
                                     <div style="display:flex; justify-content:space-between; font-size:0.7rem; color:var(--text-gray); margin-top:4px;">
                                        <span>Design</span>
                                        <span>Permits</span>
                                        <span>Construct</span>
                                    </div>
                                </div>
                            </div>
                            <div class="action-row">
                                <button class="btn-action-lg btn-edit-lg" style="pointer-events:none; opacity:0.7;">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Strict Budget Calculator Styling & Logic -->
    <style>
        /* Shared Roots matching Budget Calculator */
        :root {
            --primary: #1e293b; /* Adapted for Admin (Dark Blue/Slate) */
            --primary-light: #334155;
            --accent: #3b82f6;
            --bg-color: #f8fafc;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Modal Overlay */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);
            z-index: 1000;
            display: none; justify-content: center; align-items: center;
            opacity: 0; transition: opacity 0.3s;
        }
        .modal-overlay.active { display: flex; opacity: 1; }

        /* Main Modal Container (Mimics .app-container) */
        .app-container-modal {
            display: flex; width: 95%; max-width: 1400px; height: 90vh;
            background: white; border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden; border: 1px solid #e2e8f0; 
            position: relative;
        }

        .btn-close-fixed {
            position: absolute; top: 1.5rem; right: 1.5rem; z-index: 1001;
            background: #f1f5f9; border: none; width: 40px; height: 40px;
            cursor: pointer; color: #64748b; transition: 0.2s; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
        }
        .btn-close-fixed:hover { background: #fee2e2; color: #ef4444; }

        /* Left: Wizard Section */
        .wizard-section {
            flex: 2; padding: 4rem; position: relative;
            display: flex; flex-direction: column;
            background: #ffffff;
        }

        /* Right: Preview Section */
        .preview-section-sidebar {
            flex: 1; background: #f8fafc; border-left: 1px solid #e2e8f0; padding: 3rem;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            position: relative;
        }

        /* Progress Bar */
        .progress-header { margin-bottom: 3rem; }
        .step-indicator { font-weight: 700; color: var(--text-muted); margin-bottom: 0.8rem; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.1em; }
        .progress-track { width: 100%; height: 6px; background: #f1f5f9; border-radius: 3px; overflow: hidden; }
        .progress-fill { height: 100%; background: var(--accent); width: 0%; transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1); }

        /* Steps with Absolute Positioning for Transitions */
        .wizard-content-wrapper { position: relative; flex: 1; }
        .step { 
            position: absolute; top: 0; left: 0; width: 100%;
            opacity: 0; transform: translateY(20px); pointer-events: none;
            transition: var(--transition);
        }
        .step.active { opacity: 1; transform: translateY(0); pointer-events: all; }

        /* Typography - Strict Match */
        .step-title { font-size: 2.5rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.5rem; letter-spacing: -0.02em; }
        .step-desc { font-size: 1.1rem; color: var(--text-muted); margin-bottom: 2.5rem; }

        /* Big Input (Budget Calc Style) */
        .big-input {
            width: 100%; font-size: 2.5rem; padding: 1rem 0;
            border: none; border-bottom: 3px solid #e2e8f0;
            background: transparent; font-weight: 700; color: var(--text-main);
            outline: none; transition: 0.3s; font-family: 'Inter', sans-serif;
        }
        .big-input:focus { border-bottom-color: var(--accent); }
        .big-input::placeholder { color: #cbd5e1; }
        
        /* Helper labels below input */
        .input-helper { display: block; margin-top: 0.8rem; color: var(--text-muted); font-size: 0.9rem; font-weight: 500; }

        /* Cards Grid */
        .options-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; }
        .selection-card {
            background: white; border: 2px solid #e2e8f0; border-radius: 16px; padding: 2rem;
            cursor: pointer; transition: 0.3s; position: relative;
            display: flex; flex-direction: column; gap: 0.8rem;
        }
        .selection-card:hover { transform: translateY(-4px); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); border-color: #cbd5e1; }
        .selection-card.selected { border-color: var(--accent); background: #eff6ff; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
        .selection-card .icon { font-size: 2rem; color: #94a3b8; margin-bottom: 0.5rem; transition: 0.3s; }
        .selection-card.selected .icon { color: var(--accent); }
        .card-title { font-weight: 700; font-size: 1.2rem; color: var(--text-main); }
        .card-subtitle { font-size: 0.95rem; color: #64748b; line-height: 1.5; }
        
        .check-mark {
            position: absolute; top: 1.5rem; right: 1.5rem; width: 24px; height: 24px;
            background: var(--accent); border-radius: 50%; color: white;
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transform: scale(0); transition: 0.3s;
        }
        .selection-card.selected .check-mark { opacity: 1; transform: scale(1); }

        /* Counter */
        .counter-wrapper { display: flex; align-items: center; gap: 2rem; margin-top: 1rem; }
        .counter-btn { width: 64px; height: 64px; border-radius: 50%; border: 2px solid #e2e8f0; background: white; font-size: 1.5rem; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; color: #64748b; }
        .counter-btn:hover { border-color: var(--accent); background: var(--accent); color: white; }
        .counter-display { font-size: 3.5rem; font-weight: 800; min-width: 80px; text-align: center; color: var(--text-main); }

        /* Upload Zone */
        .upload-zone {
            border: 3px dashed #cbd5e1; border-radius: 20px;
            padding: 4rem 2rem; text-align: center; cursor: pointer; transition: 0.3s;
            background: #f8fafc;
        }
        .upload-zone:hover { border-color: #94a3b8; transform: translateY(-2px); }
        .upload-zone.dragover { border-color: var(--accent); background: #eff6ff; }
        .upload-icon { font-size: 4rem; color: #94a3b8; margin-bottom: 1.5rem; }

        /* Tag Chips */
        .tags-grid { display: flex; flex-wrap: wrap; gap: 0.8rem; margin-top: 1rem; }
        .tag-card {
            background: #f1f5f9; padding: 0.8rem 1.5rem; border-radius: 50px;
            font-weight: 600; color: #64748b; cursor: pointer; transition: 0.2s; border: 1px solid transparent;
        }
        .tag-card:hover { background: #e2e8f0; color: #1e293b; }
        .tag-card.selected { background: #dcfce7; color: #166534; border-color: #22c55e; }

        /* Nav Buttons */
        .wizard-nav { margin-top: auto; padding-top: 2rem; display: flex; justify-content: space-between; align-items: center; }
        .btn-wiz { padding: 1rem 2rem; border-radius: 12px; font-weight: 600; cursor: pointer; border: none; font-size: 1.1rem; transition: 0.2s; display: flex; align-items: center; gap: 0.8rem; }
        .btn-prev { background: transparent; color: #94a3b8; }
        .btn-prev:hover { color: #1e293b; background: #f1f5f9; }
        .btn-next { background: var(--accent); color: white; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); }
        .btn-next:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(59, 130, 246, 0.4); }
        .btn-next:disabled { opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none; }

        /* Toast */
        .toast {
            position: fixed; bottom: 2rem; left: 50%; transform: translateX(-50%);
            background: #ef4444; color: white; padding: 1rem 2rem; border-radius: 50px;
            box-shadow: 0 10px 20px rgba(239, 68, 68, 0.3); display: none; z-index: 2000;
            font-weight: 600; animation: popUp 0.3s ease-out;
        }
        @keyframes popUp { from { transform: translate(-50%, 20px); opacity: 0; } to { transform: translate(-50%, 0); opacity: 1; } }

        /* Live Preview Card */
        .preview-title { font-size: 0.9rem; text-transform: uppercase; font-weight: 800; color: #94a3b8; letter-spacing: 0.1em; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.5rem; }
        .preview-container-3d { width: 100%; max-width: 450px; }
    </style>

    <div class="toast" id="errorToast"><i class="fas fa-exclamation-circle"></i> <span id="toastMsg">Error</span></div>

    <script>
        // State
        let currentStep = 1;
        const totalSteps = 4;
        let uploadedFile = null;
        const selectedTags = new Set();

        // Initialization
        const initBackground3D = () => {
            const container = document.getElementById('canvas-container');
            if (!container) return;
            // Clear existing
            /* simple simplistic protection against multiple inits not needed if we replace whole file content properly or strictly rely on DOMContentLoaded */
            if(container.children.length > 0) return;

            const scene = new THREE.Scene();
            scene.background = new THREE.Color('#f8fafc'); // Match CSS variable
            const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
            camera.position.set(0, 2, 8);

            const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            container.appendChild(renderer.domElement);

            // Light
            const amb = new THREE.AmbientLight(0xffffff, 0.6);
            scene.add(amb);
            const dir = new THREE.DirectionalLight(0xffffff, 0.8);
            dir.position.set(10, 10, 10);
            scene.add(dir);

            // City Grid
            const cityGroup = new THREE.Group();
            const geo = new THREE.BoxGeometry(1, 1, 1);
            const mat = new THREE.MeshPhongMaterial({ color: 0x1e293b, transparent: true, opacity: 0.05 });
            const edges = new THREE.EdgesGeometry(geo);
            const lineMat = new THREE.LineBasicMaterial({ color: 0x94a3b8, transparent: true, opacity: 0.2 });

            for(let x=-8; x<=8; x+=2) {
                for(let z=-8; z<=8; z+=2) {
                     if(x===0 && z===0) continue; // Hole for hero
                     const h = Math.random() * 2 + 0.5;
                     const mesh = new THREE.Mesh(new THREE.BoxGeometry(1, h, 1), mat);
                     mesh.position.set(x, h/2 - 2, z);
                     
                     const l = new THREE.LineSegments(new THREE.EdgesGeometry(mesh.geometry), lineMat);
                     l.position.copy(mesh.position);
                     
                     cityGroup.add(mesh);
                     cityGroup.add(l);
                }
            }
            scene.add(cityGroup);

            // Hero House
            const house = new THREE.Group();
            const base = new THREE.LineSegments(new THREE.EdgesGeometry(new THREE.BoxGeometry(2,2,2)), new THREE.LineBasicMaterial({ color: 0x3b82f6, linewidth: 2 }));
            house.add(base);
            const roof = new THREE.LineSegments(new THREE.EdgesGeometry(new THREE.ConeGeometry(1.5,1.5,4)), new THREE.LineBasicMaterial({ color: 0x3b82f6, linewidth: 2 }));
            roof.position.y = 1.75;
            roof.rotation.y = Math.PI/4;
            house.add(roof);
            
            house.position.y = 0;
            scene.add(house);

            // Anim Loop
            let mouseX = 0, mouseY = 0;
            document.addEventListener('mousemove', e => {
                 mouseX = (e.clientX - window.innerWidth/2) * 0.001;
                 mouseY = (e.clientY - window.innerHeight/2) * 0.001;
            });

            const animate = () => {
                requestAnimationFrame(animate);
                cityGroup.rotation.y += 0.001;
                
                // Interactive tilt
                cityGroup.rotation.x += (mouseY - cityGroup.rotation.x) * 0.05;
                cityGroup.rotation.y += (mouseX - cityGroup.rotation.y) * 0.05;

                // House float
                house.position.y = Math.sin(Date.now() * 0.002) * 0.2;
                house.rotation.y -= 0.005;

                renderer.render(scene, camera);
            };
            animate();
            
            window.addEventListener('resize', () => {
                camera.aspect = window.innerWidth / window.innerHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(window.innerWidth, window.innerHeight);
            });
        };

        // Modal Logic
        function openModal() {
            document.getElementById('templateBuilder').classList.add('active');
            // Init 3D BG if not already
            initBackground3D();
            currentStep = 1;
            updateUI();
        }

        function closeBuilder() {
            document.getElementById('templateBuilder').classList.remove('active');
        }

        function showToast(msg) {
            const t = document.getElementById('errorToast');
            document.getElementById('toastMsg').innerText = msg;
            t.style.display = 'block';
            setTimeout(() => t.style.display = 'none', 3000);
        }

        // Navigation
        function changeStep(dir) {
            if (dir === 1) {
                // Validate Step 1
                if (currentStep === 1) {
                    const title = document.querySelector('input[name="title"]').value;
                    if(!title.trim()) { showToast("Please categorize your masterpiece with a title."); return; }
                }
                // Validate Step 2
                if (currentStep === 2) {
                     // Check basics?
                }
            }

            const newStep = currentStep + dir;
            if (newStep >= 1 && newStep <= totalSteps) {
                currentStep = newStep;
                updateUI();
            } else if (newStep > totalSteps) {
                submitWizard();
            }
        }

        function updateUI() {
            // Update Active Step
            document.querySelectorAll('.step').forEach((el, idx) => {
                if (idx + 1 === currentStep) {
                    el.classList.add('active');
                } else {
                    el.classList.remove('active');
                }
            });

            // Update Progress
            const pct = (currentStep / totalSteps) * 100;
            document.getElementById('progressBar').style.width = pct + '%';
            document.getElementById('currentStepNum').innerText = currentStep;

            // Update Buttons
            document.getElementById('prevBtn').disabled = (currentStep === 1);
            const nextBtn = document.getElementById('nextBtn');
            if (currentStep === totalSteps) {
                nextBtn.innerHTML = 'Publish Template <i class="fas fa-magic"></i>';
                nextBtn.style.background = '#10b981';
            } else {
                nextBtn.innerHTML = 'Next Step <i class="fas fa-arrow-right"></i>';
                nextBtn.style.background = '#3b82f6';
            }
        }

        // Inputs
        function updatePreview(field, val) {
            if (field === 'title') document.getElementById('previewTitle').innerText = val || 'Untitled Template';
            if (field === 'area') document.getElementById('previewArea').innerText = val + ' sqft';
            if (field === 'cost') document.getElementById('previewCost').innerText = '₹' + (parseFloat(val)||0).toLocaleString() + ' Est.';
            if (field === 'tags') {
                const con = document.getElementById('previewTags');
                con.innerHTML = val.map(t => `<div class="reasoning-chip"><i class="fas fa-check"></i> ${t}</div>`).join('');
            }
            if (field === 'floors') {
                document.getElementById('previewStyleFloor').innerText = document.getElementById('styleInput').value + ' / ' + val + ' Flr';
            }
        }

        function selectStyle(style, el) {
            document.getElementById('styleInput').value = style;
            document.querySelectorAll('.selection-card.style-opt').forEach(c => c.classList.remove('selected'));
            el.classList.add('selected');
            
            // Update Text
            const floors = document.getElementById('floorInput').value;
             document.getElementById('previewStyleFloor').innerText = style + ' / ' + floors + ' Flr';
        }

        function adjustFloors(d) {
            const field = document.getElementById('floorInput');
            let v = parseInt(field.value) + d;
            if (v < 1) v = 1;
            field.value = v;
            document.getElementById('floorDisplay').innerText = v;
            updatePreview('floors', v);
        }

        function toggleTag(tag, el) {
            if(selectedTags.has(tag)) {
                selectedTags.delete(tag);
                el.classList.remove('selected');
            } else {
                if(selectedTags.size >= 3) {
                     const first = selectedTags.values().next().value;
                     selectedTags.delete(first);
                     [...document.querySelectorAll('.tag-card')].find(c => c.innerText === first).classList.remove('selected');
                }
                selectedTags.add(tag);
                el.classList.add('selected');
            }
            // Update Input
            document.querySelector('input[name="description"]').value = Array.from(selectedTags).join(',');
            updatePreview('tags', Array.from(selectedTags));
        }

        // File Handler
        document.addEventListener('DOMContentLoaded', () => {
             const drop = document.getElementById('dropZone');
             const inp = document.getElementById('fileInput');
             
             document.getElementById('browseBtn').onclick = () => inp.click();
             inp.onchange = e => handleFile(e.target.files[0]);
             
             drop.ondragover = e => { e.preventDefault(); drop.classList.add('dragover'); };
             drop.ondragleave = e => { drop.classList.remove('dragover'); };
             drop.ondrop = e => { e.preventDefault(); drop.classList.remove('dragover'); handleFile(e.dataTransfer.files[0]); };
        });

        function handleFile(f) {
            if(!f || !f.type.startsWith('image/')) { showToast('Please upload an image.'); return; }
            uploadedFile = f;
            const reader = new FileReader();
            reader.onload = e => {
                 document.getElementById('previewImg').src = e.target.result;
                 document.querySelector('.upload-zone h3').innerText = "Image Selected";
                 document.querySelector('.upload-zone .file-meta').innerText = f.name;
            };
            reader.readAsDataURL(f);
        }

        async function submitWizard() {
            const form = document.getElementById('wizardForm');
            const data = new FormData(form);
            data.append('action', 'create');
            if(uploadedFile) data.append('image', uploadedFile);

            const btn = document.getElementById('nextBtn');
            const old = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            btn.disabled = true;

            try {
                const res = await fetch('backend/manage_templates_api.php', { method: 'POST', body: data });
                const json = await res.json();
                if(json.success) location.reload();
                else { showToast('Error: ' + json.message); btn.innerHTML = old; btn.disabled = false; }
            } catch(e) {
                showToast('Server Error');
                btn.innerHTML = old; btn.disabled = false;
            }
        }
        
        async function deleteTemplate(id) {
            if(!confirm("Are you sure?")) return;
             const data = new FormData(); data.append('action', 'delete'); data.append('id', id);
             await fetch('backend/manage_templates_api.php', { method:'POST', body:data });
             location.reload();
        }
        
        // Auto-init on load if needed
        document.addEventListener('DOMContentLoaded', initBackground3D);
    </script>
</body>
</html>
