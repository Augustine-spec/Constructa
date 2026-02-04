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
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15), 0 0 1px rgba(0, 0, 0, 0.05);
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

    <!-- PROGRESSIVE TEMPLATE BUILDER (One Question at a Time) -->
    <div class="modal-overlay" id="templateBuilder">
        <!-- Close Button (Absolute) -->
        <button class="btn-close-fixed" onclick="closeBuilder()"><i class="fas fa-times"></i></button>

        <div class="app-container-modal">
            <!-- Left: Single Focused Question -->
            <div class="wizard-section">
                <!-- Progress Header -->
                <div class="progress-header">
                    <div class="step-indicator">Step <span id="currentStepNum">1</span> of 6</div>
                    <div class="progress-track">
                        <div class="progress-fill" id="progressBar"></div>
                    </div>
                </div>

                <form id="wizardForm">
                    <!-- STEP 1: Plot Area -->
                    <div class="step active" id="step1">
                        <h2 class="step-title-focused">Let's start with the plot.</h2>
                        <p class="step-desc-focused">Enter the total area of your plot in square feet.</p>
                        
                        <div class="focused-input-container">
                            <input type="number" 
                                   name="area_sqft" 
                                   id="plotAreaInput"
                                   class="ultra-big-input" 
                                   value="1200" 
                                   min="500"
                                   max="10000"
                                   oninput="updatePreview('area', this.value); validateStep()">
                            <div class="input-unit">sq.ft</div>
                        </div>
                        
                        <!-- Optional: Range Slider for visual feedback -->
                        <div class="slider-container">
                            <input type="range" 
                                   class="area-slider" 
                                   min="500" 
                                   max="10000" 
                                   value="1200" 
                                   step="50"
                                   oninput="syncSliderToInput(this.value)">
                            <div class="slider-labels">
                                <span>500 sq.ft</span>
                                <span>10,000 sq.ft</span>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: Number of Floors -->
                    <div class="step" id="step2">
                        <h2 class="step-title-focused">How many floors are planned?</h2>
                        <p class="step-desc-focused">Select the number of floors for this template.</p>
                        
                        <div class="floor-selector-container">
                            <button type="button" class="floor-btn-large" onclick="adjustFloorsLarge(-1)">
                                <i class="fas fa-minus"></i>
                            </button>
                            <div class="floor-display-large" id="floorDisplayLarge">
                                <div class="floor-number" id="floorNumber">1</div>
                                <div class="floor-label">Floor</div>
                            </div>
                            <button type="button" class="floor-btn-large" onclick="adjustFloorsLarge(1)">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        
                        <!-- Visual Stack Representation -->
                        <div class="floor-stack-visual" id="floorStack">
                            <div class="floor-layer active"></div>
                        </div>
                        
                        <input type="hidden" name="floors" id="floorInput" value="1">
                    </div>

                    <!-- STEP 3: Construction Quality -->
                    <div class="step" id="step3">
                        <h2 class="step-title-focused">Choose the construction quality.</h2>
                        <p class="step-desc-focused">This affects the cost per square foot and overall finish.</p>
                        
                        <div class="quality-options">
                            <div class="quality-card selected" onclick="selectQuality('Basic', 1200, this)" data-quality="Basic">
                                <div class="quality-check"><i class="fas fa-check"></i></div>
                                <div class="quality-icon"><i class="fas fa-home"></i></div>
                                <div class="quality-name">Basic</div>
                                <div class="quality-desc">Standard materials, functional design</div>
                                <div class="quality-cost">₹1,200/sq.ft</div>
                            </div>
                            
                            <div class="quality-card" onclick="selectQuality('Standard', 1800, this)" data-quality="Standard">
                                <div class="quality-check"><i class="fas fa-check"></i></div>
                                <div class="quality-icon"><i class="fas fa-building"></i></div>
                                <div class="quality-name">Standard</div>
                                <div class="quality-desc">Quality materials, modern amenities</div>
                                <div class="quality-cost">₹1,800/sq.ft</div>
                            </div>
                            
                            <div class="quality-card" onclick="selectQuality('Premium', 2500, this)" data-quality="Premium">
                                <div class="quality-check"><i class="fas fa-check"></i></div>
                                <div class="quality-icon"><i class="fas fa-crown"></i></div>
                                <div class="quality-name">Premium</div>
                                <div class="quality-desc">Luxury finishes, high-end materials</div>
                                <div class="quality-cost">₹2,500/sq.ft</div>
                            </div>
                        </div>
                        
                        <input type="hidden" name="quality" id="qualityInput" value="Basic">
                        <input type="hidden" name="cost_per_sqft" id="costPerSqftInput" value="1200">
                    </div>

                    <!-- STEP 4: Location Type -->
                    <div class="step" id="step4">
                        <h2 class="step-title-focused">Where will this house be constructed?</h2>
                        <p class="step-desc-focused">Location affects permits, labor costs, and timelines.</p>
                        
                        <div class="location-options">
                            <div class="location-card selected" onclick="selectLocation('Urban', 1.2, this)" data-location="Urban">
                                <div class="location-check"><i class="fas fa-check"></i></div>
                                <div class="location-icon"><i class="fas fa-city"></i></div>
                                <div class="location-name">Urban</div>
                                <div class="location-desc">City center, high accessibility</div>
                                <div class="location-modifier">+20% cost</div>
                            </div>
                            
                            <div class="location-card" onclick="selectLocation('Semi-Urban', 1.0, this)" data-location="Semi-Urban">
                                <div class="location-check"><i class="fas fa-check"></i></div>
                                <div class="location-icon"><i class="fas fa-home"></i></div>
                                <div class="location-name">Semi-Urban</div>
                                <div class="location-desc">Suburban areas, balanced costs</div>
                                <div class="location-modifier">Standard cost</div>
                            </div>
                            
                            <div class="location-card" onclick="selectLocation('Rural', 0.85, this)" data-location="Rural">
                                <div class="location-check"><i class="fas fa-check"></i></div>
                                <div class="location-icon"><i class="fas fa-tree"></i></div>
                                <div class="location-name">Rural</div>
                                <div class="location-desc">Countryside, lower material costs</div>
                                <div class="location-modifier">-15% cost</div>
                            </div>
                        </div>
                        
                        <input type="hidden" name="location" id="locationInput" value="Urban">
                        <input type="hidden" name="location_modifier" id="locationModifierInput" value="1.2">
                    </div>

                    <!-- STEP 5: Special Features -->
                    <div class="step" id="step5">
                        <h2 class="step-title-focused">Any special requirements?</h2>
                        <p class="step-desc-focused">Select features that make this template unique.</p>
                        
                        <div class="features-toggle-grid">
                            <div class="feature-toggle" onclick="toggleFeature('Vastu Compliant', this)">
                                <div class="toggle-icon"><i class="fas fa-compass"></i></div>
                                <div class="toggle-content">
                                    <div class="toggle-name">Vastu Compliant</div>
                                    <div class="toggle-desc">Aligned with Vastu principles</div>
                                </div>
                                <div class="toggle-switch">
                                    <div class="toggle-knob"></div>
                                </div>
                            </div>
                            
                            <div class="feature-toggle" onclick="toggleFeature('Solar Ready', this)">
                                <div class="toggle-icon"><i class="fas fa-solar-panel"></i></div>
                                <div class="toggle-content">
                                    <div class="toggle-name">Solar Ready</div>
                                    <div class="toggle-desc">Pre-wired for solar panels</div>
                                </div>
                                <div class="toggle-switch">
                                    <div class="toggle-knob"></div>
                                </div>
                            </div>
                            
                            <div class="feature-toggle" onclick="toggleFeature('Smart Home', this)">
                                <div class="toggle-icon"><i class="fas fa-brain"></i></div>
                                <div class="toggle-content">
                                    <div class="toggle-name">Smart Home</div>
                                    <div class="toggle-desc">IoT-enabled automation</div>
                                </div>
                                <div class="toggle-switch">
                                    <div class="toggle-knob"></div>
                                </div>
                            </div>
                            
                            <div class="feature-toggle" onclick="toggleFeature('Rainwater Harvesting', this)">
                                <div class="toggle-icon"><i class="fas fa-tint"></i></div>
                                <div class="toggle-content">
                                    <div class="toggle-name">Rainwater Harvesting</div>
                                    <div class="toggle-desc">Eco-friendly water management</div>
                                </div>
                                <div class="toggle-switch">
                                    <div class="toggle-knob"></div>
                                </div>
                            </div>
                        </div>
                        
                        <input type="hidden" name="features" id="featuresInput" value="">
                        <input type="hidden" name="style" id="styleInput" value="Modern">
                        <input type="hidden" name="title" id="titleInput" value="">
                        <input type="hidden" name="description" id="descriptionInput" value="">
                    </div>

                    <!-- STEP 6: Review & Finalize -->
                    <div class="step" id="step6">
                        <h2 class="step-title-focused">Review your template configuration.</h2>
                        <p class="step-desc-focused">Confirm all details before publishing.</p>
                        
                        <div class="review-summary-card">
                            <div class="summary-header">
                                <div class="summary-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div>
                                    <h4>Template Ready</h4>
                                    <p>All configurations have been set</p>
                                </div>
                            </div>
                            
                            <div class="summary-grid">
                                <div class="summary-item">
                                    <div class="summary-label">Plot Area</div>
                                    <div class="summary-value" id="summaryArea">1200 sq.ft</div>
                                </div>
                                <div class="summary-item">
                                    <div class="summary-label">Floors</div>
                                    <div class="summary-value" id="summaryFloors">1</div>
                                </div>
                                <div class="summary-item">
                                    <div class="summary-label">Quality</div>
                                    <div class="summary-value" id="summaryQuality">Basic</div>
                                </div>
                                <div class="summary-item">
                                    <div class="summary-label">Location</div>
                                    <div class="summary-value" id="summaryLocation">Urban</div>
                                </div>
                                <div class="summary-item summary-item-full">
                                    <div class="summary-label">Special Features</div>
                                    <div class="summary-value" id="summaryFeatures">None selected</div>
                                </div>
                            </div>
                            
                            <div class="final-actions">
                                <div class="form-group">
                                    <label class="final-label">Template Name</label>
                                    <input type="text" 
                                           class="final-input" 
                                           placeholder="e.g., Modern Urban Villa"
                                           oninput="updatePreview('title', this.value); document.getElementById('titleInput').value = this.value">
                                </div>
                                
                                <div class="upload-zone-compact" id="dropZone" onclick="document.getElementById('fileInput').click()">
                                    <input type="file" name="image" id="fileInput" hidden accept="image/*">
                                    <i class="fas fa-image"></i>
                                    <span>Upload Template Image</span>
                                </div>
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

            <!-- Right: Live Preview (Sticky) -->
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
                                    <div class="ai-title" id="previewTitle">New Template</div>
                                    <div class="reasoning-grid" id="previewTags">
                                        <div class="reasoning-chip"><i class="fas fa-check-circle"></i> Modern Design</div>
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
                                        <div class="metric-val green" id="previewCost">₹17.28 Lakhs</div>
                                    </div>
                                    <div class="metric-group">
                                        <div>Timeline</div>
                                        <div class="metric-val" id="previewTime">7 Months</div>
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

    <!-- Progressive Wizard Styles -->
    <style>
        /* === PROGRESSIVE WIZARD STYLES === */
        
        /* Modal Overlay */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255, 255, 255, 0.92); backdrop-filter: blur(12px);
            z-index: 1000;
            display: none; justify-content: center; align-items: center;
            opacity: 0; transition: opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .modal-overlay.active { display: flex; opacity: 1; }

        /* Main Container */
        .app-container-modal {
            display: flex; width: 95%; max-width: 1600px; height: 92vh;
            background: white; border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden; border: 1px solid #e2e8f0; 
            position: relative;
        }

        .btn-close-fixed {
            position: absolute; top: 1.5rem; right: 1.5rem; z-index: 1001;
            background: #f1f5f9; border: none; width: 45px; height: 45px;
            cursor: pointer; color: #64748b; transition: 0.2s; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
        }
        .btn-close-fixed:hover { background: #e2e8f0; color: #dc2626; transform: rotate(90deg); }

        /* Left Section - Question Area */
        .wizard-section {
            flex: 1.5; padding: 5rem 6rem; display: flex; flex-direction: column; 
            overflow-y: auto; background: #ffffff; justify-content: space-between;
        }
        
        /* Right Section - Live Preview */
        .preview-section-sidebar {
            flex: 1; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); 
            border-left: 1px solid #e2e8f0; padding: 3rem 2.5rem;
            display: flex; flex-direction: column; position: sticky; top: 0; height: 100vh;
        }

        /* Step Transitions */
        .step { 
            display: none; opacity: 0; transform: translateY(30px); 
            transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .step.active { display: block; opacity: 1; transform: translateY(0); }

        /* Focused Typography */
        .step-title-focused { 
            font-size: 2.8rem; font-weight: 800; color: #1e293b; 
            margin-bottom: 1rem; letter-spacing: -1.5px; line-height: 1.1;
        }
        .step-desc-focused { 
            font-size: 1.3rem; color: #64748b; margin-bottom: 4rem; 
            font-weight: 400; line-height: 1.6;
        }

        /* Progress Bar */
        .progress-header { margin-bottom: 3.5rem; }
        .step-indicator { 
            font-weight: 700; color: #94a3b8; margin-bottom: 1rem; 
            font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.15em; 
        }
        .progress-track { 
            width: 100%; height: 6px; background: #e2e8f0; 
            border-radius: 3px; overflow: hidden; 
        }
        .progress-fill { 
            height: 100%; background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%); 
            width: 16.66%; transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1); 
        }

        /* === STEP 1: ULTRA BIG INPUT === */
        .focused-input-container {
            display: flex; align-items: baseline; gap: 1.5rem; margin-bottom: 3rem;
        }
        .ultra-big-input {
            width: 100%; font-size: 5rem; font-weight: 800; padding: 0.5rem 0;
            border: none; border-bottom: 4px solid #e2e8f0; outline: none; color: #1e293b;
            transition: all 0.3s ease; background: transparent; font-family: 'Inter', sans-serif;
        }
        .ultra-big-input:focus { border-bottom-color: #3b82f6; }
        .ultra-big-input::placeholder { color: #cbd5e1; }
        .input-unit {
            font-size: 2rem; color: #94a3b8; font-weight: 600; white-space: nowrap;
        }

        /* Slider */
        .slider-container { margin-top: 2rem; }
        .area-slider {
            width: 100%; height: 8px; border-radius: 4px; outline: none;
            background: linear-gradient(to right, #e2e8f0 0%, #e2e8f0 100%);
            -webkit-appearance: none; cursor: pointer;
        }
        .area-slider::-webkit-slider-thumb {
            -webkit-appearance: none; width: 24px; height: 24px; border-radius: 50%;
            background: #3b82f6; cursor: pointer; box-shadow: 0 2px 8px rgba(59, 130, 246, 0.4);
            transition: 0.2s;
        }
        .area-slider::-webkit-slider-thumb:hover {
            transform: scale(1.2); box-shadow: 0 4px 12px rgba(59, 130, 246, 0.6);
        }
        .slider-labels {
            display: flex; justify-content: space-between; margin-top: 0.8rem;
            font-size: 0.9rem; color: #94a3b8; font-weight: 500;
        }

        /* === STEP 2: FLOOR SELECTOR === */
        .floor-selector-container {
            display: flex; align-items: center; justify-content: center; gap: 4rem; margin-bottom: 3rem;
        }
        .floor-btn-large {
            width: 80px; height: 80px; border-radius: 50%; 
            border: 3px solid #e2e8f0; background: white; 
            font-size: 2rem; cursor: pointer; transition: 0.3s;
            display: flex; align-items: center; justify-content: center; color: #64748b;
        }
        .floor-btn-large:hover { 
            border-color: #3b82f6; background: #3b82f6; color: white; 
            transform: scale(1.05); box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
        }
        .floor-display-large {
            text-align: center;
        }
        .floor-number {
            font-size: 6rem; font-weight: 900; color: #1e293b; line-height: 1;
        }
        .floor-label {
            font-size: 1.2rem; color: #94a3b8; font-weight: 600; margin-top: 0.5rem;
            text-transform: uppercase; letter-spacing: 0.1em;
        }

        /* Floor Stack Visual */
        .floor-stack-visual {
            display: flex; flex-direction: column-reverse; gap: 8px; 
            align-items: center; margin-top: 2rem;
        }
        .floor-layer {
            width: 200px; height: 40px; background: #e2e8f0; border-radius: 8px;
            border: 2px solid #cbd5e1; transition: all 0.3s ease;
            position: relative; overflow: hidden;
        }
        .floor-layer.active {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            border-color: #3b82f6; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        /* === STEP 3: QUALITY CARDS === */
        .quality-options {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem;
        }
        .quality-card {
            background: white; border: 3px solid #e2e8f0; border-radius: 20px; 
            padding: 2.5rem 1.5rem; cursor: pointer; transition: all 0.3s ease;
            position: relative; text-align: center; display: flex; flex-direction: column; gap: 1rem;
        }
        .quality-card:hover { 
            transform: translateY(-8px); box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1); 
            border-color: #cbd5e1; 
        }
        .quality-card.selected { 
            border-color: #3b82f6; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); 
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); 
        }
        .quality-check {
            position: absolute; top: 1rem; right: 1rem; width: 28px; height: 28px;
            background: #3b82f6; border-radius: 50%; color: white;
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transform: scale(0); transition: 0.3s;
        }
        .quality-card.selected .quality-check { opacity: 1; transform: scale(1); }
        .quality-icon { font-size: 3rem; color: #94a3b8; margin-bottom: 0.5rem; }
        .quality-card.selected .quality-icon { color: #3b82f6; }
        .quality-name { font-size: 1.4rem; font-weight: 700; color: #1e293b; }
        .quality-desc { font-size: 0.95rem; color: #64748b; line-height: 1.5; }
        .quality-cost { 
            font-size: 1.1rem; font-weight: 700; color: #10b981; 
            margin-top: 0.5rem; padding-top: 1rem; border-top: 1px solid #e2e8f0;
        }

        /* === STEP 4: LOCATION CARDS === */
        .location-options {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem;
        }
        .location-card {
            background: white; border: 3px solid #e2e8f0; border-radius: 20px; 
            padding: 2.5rem 1.5rem; cursor: pointer; transition: all 0.3s ease;
            position: relative; text-align: center; display: flex; flex-direction: column; gap: 1rem;
        }
        .location-card:hover { 
            transform: translateY(-8px); box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1); 
            border-color: #cbd5e1; 
        }
        .location-card.selected { 
            border-color: #3b82f6; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); 
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); 
        }
        .location-check {
            position: absolute; top: 1rem; right: 1rem; width: 28px; height: 28px;
            background: #3b82f6; border-radius: 50%; color: white;
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transform: scale(0); transition: 0.3s;
        }
        .location-card.selected .location-check { opacity: 1; transform: scale(1); }
        .location-icon { font-size: 3rem; color: #94a3b8; margin-bottom: 0.5rem; }
        .location-card.selected .location-icon { color: #3b82f6; }
        .location-name { font-size: 1.4rem; font-weight: 700; color: #1e293b; }
        .location-desc { font-size: 0.95rem; color: #64748b; line-height: 1.5; }
        .location-modifier { 
            font-size: 1rem; font-weight: 600; color: #f59e0b; 
            margin-top: 0.5rem; padding-top: 1rem; border-top: 1px solid #e2e8f0;
        }

        /* === STEP 5: FEATURE TOGGLES === */
        .features-toggle-grid {
            display: flex; flex-direction: column; gap: 1.5rem;
        }
        .feature-toggle {
            background: white; border: 2px solid #e2e8f0; border-radius: 16px;
            padding: 1.8rem 2rem; cursor: pointer; transition: all 0.3s ease;
            display: flex; align-items: center; gap: 1.5rem;
        }
        .feature-toggle:hover {
            border-color: #cbd5e1; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        .feature-toggle.active {
            border-color: #10b981; background: #f0fdf4;
        }
        .toggle-icon {
            width: 50px; height: 50px; border-radius: 12px; background: #f1f5f9;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; color: #64748b; transition: 0.3s;
        }
        .feature-toggle.active .toggle-icon {
            background: #10b981; color: white;
        }
        .toggle-content { flex: 1; }
        .toggle-name { font-size: 1.2rem; font-weight: 700; color: #1e293b; margin-bottom: 0.3rem; }
        .toggle-desc { font-size: 0.9rem; color: #64748b; }
        .toggle-switch {
            width: 60px; height: 32px; background: #e2e8f0; border-radius: 16px;
            position: relative; transition: 0.3s;
        }
        .feature-toggle.active .toggle-switch {
            background: #10b981;
        }
        .toggle-knob {
            width: 26px; height: 26px; background: white; border-radius: 50%;
            position: absolute; top: 3px; left: 3px; transition: 0.3s;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        .feature-toggle.active .toggle-knob {
            left: 31px;
        }

        /* === STEP 6: REVIEW SUMMARY === */
        .review-summary-card {
            background: white; border: 2px solid #e2e8f0; border-radius: 20px;
            padding: 2.5rem; display: flex; flex-direction: column; gap: 2rem;
        }
        .summary-header {
            display: flex; align-items: center; gap: 1.5rem;
            padding-bottom: 1.5rem; border-bottom: 2px solid #e2e8f0;
        }
        .summary-icon {
            width: 60px; height: 60px; background: #dcfce7; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #16a34a; font-size: 2rem;
        }
        .summary-header h4 {
            font-size: 1.5rem; font-weight: 800; color: #1e293b; margin-bottom: 0.3rem;
        }
        .summary-header p {
            font-size: 1rem; color: #64748b;
        }
        .summary-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;
        }
        .summary-item {
            background: #f8fafc; padding: 1.2rem; border-radius: 12px;
        }
        .summary-item-full {
            grid-column: 1 / -1;
        }
        .summary-label {
            font-size: 0.8rem; color: #94a3b8; text-transform: uppercase;
            letter-spacing: 0.05em; margin-bottom: 0.5rem; font-weight: 600;
        }
        .summary-value {
            font-size: 1.2rem; font-weight: 700; color: #1e293b;
        }
        .final-actions {
            display: flex; flex-direction: column; gap: 1.5rem;
        }
        .final-label {
            font-size: 0.9rem; color: #64748b; font-weight: 600;
            margin-bottom: 0.5rem; display: block;
        }
        .final-input {
            width: 100%; padding: 1rem 1.2rem; border: 2px solid #e2e8f0;
            border-radius: 12px; font-size: 1.1rem; outline: none; transition: 0.2s;
        }
        .final-input:focus {
            border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }
        .upload-zone-compact {
            background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 12px;
            padding: 1.5rem; text-align: center; cursor: pointer; transition: 0.3s;
            display: flex; align-items: center; justify-content: center; gap: 1rem;
        }
        .upload-zone-compact:hover {
            border-color: #3b82f6; background: #eff6ff;
        }
        .upload-zone-compact i {
            font-size: 1.5rem; color: #64748b;
        }
        .upload-zone-compact span {
            font-size: 1rem; font-weight: 600; color: #64748b;
        }

        /* Navigation Buttons */
        .wizard-nav { 
            margin-top: 3rem; padding-top: 2rem; 
            display: flex; justify-content: space-between; 
            border-top: 2px solid #f1f5f9;
        }
        .btn { 
            padding: 1.2rem 2.5rem; border-radius: 14px; font-weight: 700; 
            cursor: pointer; border: none; font-size: 1.1rem; transition: all 0.3s ease; 
            display: flex; align-items: center; gap: 0.8rem; 
        }
        .btn-secondary { 
            background: #f1f5f9; color: #64748b; 
        }
        .btn-secondary:hover:not(:disabled) { 
            background: #e2e8f0; color: #1e293b; transform: translateX(-4px);
        }
        .btn-primary { 
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); 
            color: white; box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3); 
        }
        .btn-primary:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 20px 25px -5px rgba(59, 130, 246, 0.4); 
        }
        .btn:disabled { 
            opacity: 0.4; cursor: not-allowed; transform: none !important; 
            box-shadow: none !important; 
        }

        /* === LIVE PREVIEW PANEL === */
        .preview-title { 
            font-size: 1.1rem; text-transform: uppercase; font-weight: 800; 
            color: #1e293b; letter-spacing: 0.05em; margin-bottom: 2.5rem; 
            display: flex; gap: 0.5rem; align-items: center; 
        }
        .preview-container-3d { 
            transform: scale(0.85); transform-origin: top center; 
            width: 100%; max-width: 500px; padding: 1rem; 
        }
        .preview-card {
            margin: 0 auto;
        }
    </style>

    <script>
        // === PROGRESSIVE WIZARD LOGIC ===
        let currentStep = 1;
        const totalSteps = 6;
        let uploadedFile = null;
        let selectedFeatures = new Set();
        
        // State object to track all selections
        let wizardState = {
            area: 1200,
            floors: 1,
            quality: 'Basic',
            costPerSqft: 1200,
            location: 'Urban',
            locationModifier: 1.2,
            features: [],
            title: '',
            style: 'Modern'
        };

        function openModal() {
            document.getElementById('templateBuilder').classList.add('active');
            currentStep = 1;
            updateUI();
            recalculateCost();
        }

        function closeBuilder() {
            document.getElementById('templateBuilder').classList.remove('active');
            // Reset wizard
            currentStep = 1;
            selectedFeatures.clear();
            wizardState = {
                area: 1200,
                floors: 1,
                quality: 'Basic',
                costPerSqft: 1200,
                location: 'Urban',
                locationModifier: 1.2,
                features: [],
                title: '',
                style: 'Modern'
            };
        }

        function changeStep(dir) {
            // Validation before moving forward
            if (dir === 1) {
                if(currentStep === 1 && !validateStep1()) return;
                if(currentStep === 6 && dir === 1) {
                    submitWizard();
                    return;
                }
            }

            if (dir === 1 && currentStep < totalSteps) {
                currentStep++;
            } else if (dir === -1 && currentStep > 1) {
                currentStep--;
            }
            
            updateUI();
            updateSummary(); // Update summary when navigating
        }

        function validateStep1() {
            const area = document.getElementById('plotAreaInput').value;
            if (!area || area < 500) {
                alert('Please enter a valid plot area (minimum 500 sq.ft)');
                return false;
            }
            return true;
        }

        function validateStep() {
            // Real-time validation for enabling next button
            const nextBtn = document.getElementById('nextBtn');
            if (currentStep === 1) {
                const area = document.getElementById('plotAreaInput').value;
                nextBtn.disabled = !area || area < 500;
            }
        }

        function updateUI() {
            // Steps Toggle
            document.querySelectorAll('.step').forEach(el => el.classList.remove('active'));
            const activeStep = document.getElementById(`step${currentStep}`);
            if (activeStep) activeStep.classList.add('active');
            
            // Progress Bar (16.66% per step for 6 steps)
            const progress = (currentStep / totalSteps) * 100;
            document.getElementById('progressBar').style.width = `${progress}%`;
            document.getElementById('currentStepNum').textContent = currentStep;

            // Buttons State
            document.getElementById('prevBtn').disabled = (currentStep === 1);
            const nextBtn = document.getElementById('nextBtn');
            if (currentStep === totalSteps) {
                nextBtn.innerHTML = 'Publish Template <i class="fas fa-rocket"></i>';
                nextBtn.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
            } else {
                nextBtn.innerHTML = 'Next Step <i class="fas fa-arrow-right"></i>';
                nextBtn.style.background = 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)';
            }
        }

        // === STEP 1: SLIDER SYNC ===
        function syncSliderToInput(value) {
            document.getElementById('plotAreaInput').value = value;
            wizardState.area = parseInt(value);
            updatePreview('area', value);
            recalculateCost();
        }

        // Sync input to slider
        document.addEventListener('DOMContentLoaded', () => {
            const plotInput = document.getElementById('plotAreaInput');
            const slider = document.querySelector('.area-slider');
            
            if (plotInput && slider) {
                plotInput.addEventListener('input', (e) => {
                    slider.value = e.target.value;
                    wizardState.area = parseInt(e.target.value);
                    recalculateCost();
                });
            }
        });

        // === STEP 2: FLOOR SELECTOR ===
        function adjustFloorsLarge(delta) {
            const input = document.getElementById('floorInput');
            let val = parseInt(input.value) + delta;
            if(val < 1) val = 1;
            if(val > 5) val = 5;
            input.value = val;
            document.getElementById('floorNumber').textContent = val;
            
            // Update floor label (singular/plural)
            document.querySelector('.floor-label').textContent = val === 1 ? 'Floor' : 'Floors';
            
            // Update visual stack
            updateFloorStack(val);
            
            wizardState.floors = val;
            updatePreview('floors', val);
            recalculateCost();
        }

        function updateFloorStack(numFloors) {
            const stackContainer = document.getElementById('floorStack');
            stackContainer.innerHTML = '';
            
            for (let i = 0; i < numFloors; i++) {
                const layer = document.createElement('div');
                layer.className = 'floor-layer active';
                stackContainer.appendChild(layer);
            }
        }

        // === STEP 3: QUALITY SELECTION ===
        function selectQuality(quality, costPerSqft, cardElement) {
            document.querySelectorAll('.quality-card').forEach(c => c.classList.remove('selected'));
            cardElement.classList.add('selected');
            
            document.getElementById('qualityInput').value = quality;
            document.getElementById('costPerSqftInput').value = costPerSqft;
            
            wizardState.quality = quality;
            wizardState.costPerSqft = costPerSqft;
            
            updatePreview('quality', quality);
            recalculateCost();
        }

        // === STEP 4: LOCATION SELECTION ===
        function selectLocation(location, modifier, cardElement) {
            document.querySelectorAll('.location-card').forEach(c => c.classList.remove('selected'));
            cardElement.classList.add('selected');
            
            document.getElementById('locationInput').value = location;
            document.getElementById('locationModifierInput').value = modifier;
            
            wizardState.location = location;
            wizardState.locationModifier = modifier;
            
            updatePreview('location', location);
            recalculateCost();
        }

        // === STEP 5: FEATURE TOGGLES ===
        function toggleFeature(feature, element) {
            if (element.classList.contains('active')) {
                element.classList.remove('active');
                selectedFeatures.delete(feature);
            } else {
                element.classList.add('active');
                selectedFeatures.add(feature);
            }
            
            wizardState.features = Array.from(selectedFeatures);
            document.getElementById('featuresInput').value = wizardState.features.join(',');
            
            updatePreview('features', wizardState.features);
        }

        // === LIVE PREVIEW UPDATES ===
        function updatePreview(field, val) {
            if(field === 'title') {
                wizardState.title = val;
                document.getElementById('previewTitle').textContent = val || 'New Template';
            }
            
            if(field === 'area') {
                document.getElementById('previewArea').textContent = `${parseInt(val).toLocaleString()} sqft`;
                if (document.getElementById('summaryArea')) {
                    document.getElementById('summaryArea').textContent = `${parseInt(val).toLocaleString()} sq.ft`;
                }
            }
            
            if(field === 'floors' || field === 'style') {
                const style = wizardState.style;
                const floors = wizardState.floors;
                document.getElementById('previewStyleFloor').textContent = `${style} / ${floors} Flr`;
                
                // Update timeline
                const time = (parseInt(floors) * 3) + 4;
                document.getElementById('previewTime').textContent = time + ' Months';
                
                if (document.getElementById('summaryFloors')) {
                    document.getElementById('summaryFloors').textContent = floors + (floors == 1 ? ' Floor' : ' Floors');
                }
            }
            
            if(field === 'quality') {
                if (document.getElementById('summaryQuality')) {
                    document.getElementById('summaryQuality').textContent = val;
                }
            }
            
            if(field === 'location') {
                if (document.getElementById('summaryLocation')) {
                    document.getElementById('summaryLocation').textContent = val;
                }
            }
            
            if(field === 'features') {
                const featuresText = val.length > 0 ? val.join(', ') : 'None';
                // Update tags in preview
                const container = document.getElementById('previewTags');
                if (container) {
                    container.innerHTML = '';
                    if (val.length > 0) {
                        val.slice(0, 3).forEach(tag => {
                            container.innerHTML += `<div class="reasoning-chip"><i class="fas fa-check-circle"></i> ${tag}</div>`;
                        });
                    } else {
                        container.innerHTML = '<div class="reasoning-chip"><i class="fas fa-check-circle"></i> Modern Design</div>';
                    }
                }
                
                if (document.getElementById('summaryFeatures')) {
                    document.getElementById('summaryFeatures').textContent = val.length > 0 ? featuresText : 'None selected';
                }
            }
        }

        // === COST CALCULATION ===
        function recalculateCost() {
            const area = wizardState.area;
            const floors = wizardState.floors;
            const costPerSqft = wizardState.costPerSqft;
            const locationModifier = wizardState.locationModifier;
            
            // Base cost calculation
            const baseCost = area * floors * costPerSqft;
            
            // Apply location modifier
            const finalCost = baseCost * locationModifier;
            
            // Convert to lakhs
            const costInLakhs = finalCost / 100000;
            
            // Update display with animation
            const costElement = document.getElementById('previewCost');
            if (costElement) {
                costElement.style.transform = 'scale(1.1)';
                setTimeout(() => {
                    costElement.textContent = `₹${costInLakhs.toFixed(2)} Lakhs`;
                    costElement.style.transform = 'scale(1)';
                }, 100);
            }
        }

        // === SUMMARY UPDATE ===
        function updateSummary() {
            if (currentStep === 6) {
                document.getElementById('summaryArea').textContent = `${wizardState.area.toLocaleString()} sq.ft`;
                document.getElementById('summaryFloors').textContent = wizardState.floors + (wizardState.floors == 1 ? ' Floor' : ' Floors');
                document.getElementById('summaryQuality').textContent = wizardState.quality;
                document.getElementById('summaryLocation').textContent = wizardState.location;
                const featuresText = wizardState.features.length > 0 ? wizardState.features.join(', ') : 'None selected';
                document.getElementById('summaryFeatures').textContent = featuresText;
            }
        }

        // === FILE UPLOAD ===
        document.addEventListener('DOMContentLoaded', () => {
            const fileInput = document.getElementById('fileInput');
            
            if(fileInput) {
                fileInput.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        handleFile(this.files[0]);
                    }
                });
            }
        });

        function handleFile(file) {
            uploadedFile = file;
            
            if(!file.type.startsWith('image/')) {
                alert('Please upload a valid image file');
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                // Update preview image
                const previewImg = document.getElementById('previewImg');
                if (previewImg) {
                    previewImg.src = e.target.result;
                }
                
                // Update upload zone text
                const dropZone = document.getElementById('dropZone');
                if(dropZone) {
                    const span = dropZone.querySelector('span');
                    if (span) {
                        span.textContent = file.name;
                        dropZone.style.borderColor = '#10b981';
                        dropZone.style.background = '#f0fdf4';
                    }
                }
            };
            reader.readAsDataURL(file);
        }

        // === SUBMIT WIZARD ===
        async function submitWizard() {
            const form = document.getElementById('wizardForm');
            const formData = new FormData(form);
            formData.append('action', 'create');
            
            // Ensure all hidden fields are populated
            formData.set('title', wizardState.title || 'Untitled Template');
            formData.set('area_sqft', wizardState.area);
            formData.set('floors', wizardState.floors);
            formData.set('style', wizardState.style);
            formData.set('location', wizardState.location);
            formData.set('description', wizardState.features.join(','));
            
            if(uploadedFile) {
                formData.append('image', uploadedFile);
            }

            const btn = document.getElementById('nextBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Publishing...';
            btn.disabled = true;

            try {
                const response = await fetch('backend/manage_templates_api.php', { method: 'POST', body: formData });
                const result = await response.json();
                if(result.success) {
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            } catch(e) {
                console.error(e);
                alert('Server connection error. Check console.');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        // --- Delete Template ---
        async function deleteTemplate(id) {
            if(!confirm('Delete this template permanently?')) return;
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);

            try {
                const res = await fetch('backend/manage_templates_api.php', { method: 'POST', body: formData });
                const json = await res.json();
                if(json.success) location.reload();
                else alert('Error: ' + json.message);
            } catch(e) { console.error(e); }
        }

        // --- 3D Background Logic (Synced with saved_favorites.php) ---
        const initBackground3D = () => {
            const container = document.getElementById('canvas-container');
            if (!container) return;

            const scene = new THREE.Scene();
            scene.background = new THREE.Color('#f8fafc');
            scene.fog = new THREE.Fog('#f8fafc', 10, 45);

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

            // Hero House
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
                
                // Interactive tilt from saved_favorites.php
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
        
        document.addEventListener('DOMContentLoaded', initBackground3D);
    </script>
</body>
</html>
