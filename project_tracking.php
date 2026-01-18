<?php
session_start();
require_once 'backend/config.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'homeowner') {
    header('Location: login.html');
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = getDatabaseConnection();
$username = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Homeowner';

// Fetch the user's active project
// Modified to support specific project ID if passed, otherwise default to latest
$project_id_param = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

if ($project_id_param > 0) {
    $stmt = $conn->prepare("
        SELECT pr.*, u.name as engineer_name, u.email as engineer_email 
        FROM project_requests pr 
        LEFT JOIN users u ON pr.engineer_id = u.id 
        WHERE pr.homeowner_id = ? AND pr.id = ?
    ");
    $stmt->bind_param("ii", $user_id, $project_id_param);
} else {
    $stmt = $conn->prepare("
        SELECT pr.*, u.name as engineer_name, u.email as engineer_email 
        FROM project_requests pr 
        LEFT JOIN users u ON pr.engineer_id = u.id 
        WHERE pr.homeowner_id = ?
        ORDER BY pr.created_at DESC LIMIT 1
    ");
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

// Stages Configuration (Mirrored from Engineer Workspace)
$stages = [
    ["id" => "gathering", "label" => "Data Gathering & Requirements", "icon" => "fa-file-invoice", "desc" => "Comprehensive collection of homeowner requirements and site constraints."],
    ["id" => "survey", "label" => "Site Inspection & Survey", "icon" => "fa-map-marked-alt", "desc" => "Physical site inspection, plot measurement, and road access verification."],
    ["id" => "planning", "label" => "Planning & Design", "icon" => "fa-pencil-ruler", "desc" => "Architectural layout, floor plans, and spatial organization."],
    ["id" => "estimation", "label" => "Cost Estimation & BOQ", "icon" => "fa-file-invoice-dollar", "desc" => "Detailed cost estimation and Bill of Quantities preparation."],
    ["id" => "approvals", "label" => "Approval & Permissions", "icon" => "fa-file-signature", "desc" => "Tracking and managing legal approvals, permits, and NOCs required for construction."],
    ["id" => "execution", "label" => "Construction Execution", "icon" => "fa-hard-hat", "desc" => "On-site construction activities and structural work."],
    ["id" => "handover", "label" => "Handover & Closure", "icon" => "fa-key", "desc" => "Final inspection, documentation handover, and project closure."]
];

$current_project_stage = $project ? (int)$project['current_stage'] : 0;
// Default to viewing the current active stage, or the first one if not started
$view_stage_idx = isset($_GET['view']) ? (int)$_GET['view'] : ($current_project_stage > 0 ? $current_project_stage - 1 : 0);
// Ensure bounds
$view_stage_idx = max(0, min($view_stage_idx, count($stages) - 1));

$current_stage = $stages[$view_stage_idx];

$project_details_raw = $project['project_details'] ?? '';
$project_data = !empty($project_details_raw) ? json_decode($project_details_raw, true) : [];

// Fetch Documents for Current Stage from project_documents table
$stage_documents = [];
if ($project) {
    // Current view stage index is $view_stage_idx
    // We already have $view_stage_idx derived above
    $stmt_docs = $conn->prepare("SELECT id, file_name, file_path, file_size, uploaded_at FROM project_documents WHERE project_id = ? AND stage_idx = ? ORDER BY uploaded_at DESC");
    $stmt_docs->bind_param("ii", $project['id'], $view_stage_idx);
    $stmt_docs->execute();
    $result_docs = $stmt_docs->get_result();
    while ($row = $result_docs->fetch_assoc()) {
        $stage_documents[] = $row;
    }
    $stmt_docs->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homeowner Dashboard - Constructa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <style>
        :root {
            --primary: #1a2e23;
            --primary-light: #2c4a3b;
            --accent: #d4af37; /* Gold accent */
            --bg-base: #f6f7f2;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(255, 255, 255, 0.8);
            --transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1);
            --success: #22c55e;
            --warning: #f59e0b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        
        body { 
            background: var(--bg-base); 
            color: var(--text-main); 
            height: 100vh; 
            overflow: hidden; 
            display: flex;
            flex-direction: column;
        }

        #canvas-container {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            z-index: -1; pointer-events: none;
        }

        /* Navbar */
        nav {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            padding: 1rem 3rem;
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid var(--glass-border);
            z-index: 1000;
        }

        .nav-logo {
            font-weight: 800; font-size: 1.6rem; color: var(--primary);
            text-decoration: none; display: flex; align-items: center; gap: 0.75rem;
        }

        .nav-links { display: flex; gap: 1rem; align-items: center; }

        .nav-btn {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;font-weight: 700; color: var(--text-muted);
            text-decoration: none; transition: var(--transition);
            display: flex; align-items: center; gap: 0.5rem;
        }
        .nav-btn:hover { color: var(--primary); transform: translateY(-1px); }

        /* Main Layout */
        .dashboard-layout {
            flex: 1; display: grid; grid-template-columns: 300px 1fr;
            height: calc(100vh - 80px); overflow: hidden;
        }

        /* Sidebar */
        .sidebar {
            background: var(--glass-bg); 
            backdrop-filter: blur(15px);
            border-right: 1px solid var(--glass-border);
            padding: 2rem 1.5rem; 
            overflow-y: auto;
            display: flex; flex-direction: column; gap: 0.5rem;
        }

        .project-meta-card {
            background: white; padding: 1.5rem; border-radius: 16px;
            margin-bottom: 2rem; border: 1px solid rgba(0,0,0,0.05);
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
        }

        .project-title { font-size: 1.1rem; font-weight: 800; color: var(--primary); margin-bottom: 0.25rem; }
        .project-id { font-size: 0.75rem; color: var(--text-muted); font-family: 'JetBrains Mono'; }

        .stage-entry {
            display: flex; align-items: center; gap: 1rem;
            padding: 1rem; border-radius: 12px; cursor: pointer;
            transition: var(--transition); position: relative;
            text-decoration: none; color: var(--text-muted);
        }

        .stage-entry:hover { background: rgba(255,255,255,0.5); }

        .stage-entry.active {
            background: var(--primary); color: white;
            box-shadow: 0 8px 20px rgba(26, 46, 35, 0.2);
        }
        .stage-entry.active .stage-icon { background: rgba(255,255,255,0.2); color: white; }
        .stage-entry.active .stage-label { color: white; }

        .stage-entry.completed .stage-icon { background: #dcfce7; color: #166534; }
        .stage-entry.completed .stage-label { color: var(--primary); }

        /* Fix for invisible text when stage is both active and completed */
        .stage-entry.active.completed .stage-label { color: white; }
        .stage-entry.active.completed .stage-icon { background: rgba(255,255,255,0.2); color: white; }

        .stage-icon {
            width: 36px; height: 36px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.9rem; background: whitel 
            transition: var(--transition); background: rgba(0,0,0,0.05);
        }

        .stage-label { font-size: 0.9rem; font-weight: 600; }
        .stage-status-icon { margin-left: auto; font-size: 0.8rem; }

        /* Content Area */
        .content-area {
            padding: 3rem; overflow-y: auto;
            background: rgba(255,255,255,0.4);
        }

        .header-section { margin-bottom: 2rem; }
        .stage-subtitle { font-size: 0.8rem; font-weight: 800; color: var(--accent); letter-spacing: 2px; text-transform: uppercase; margin-bottom: 0.5rem; }
        .stage-title-main { font-size: 2.5rem; font-weight: 800; color: var(--primary); letter-spacing: -1px; margin-bottom: 1rem; }
        
        /* Progress Bar */
        .progress-container {
            background: rgba(0,0,0,0.05); border-radius: 100px; height: 8px; width: 100%; max-width: 600px;
            margin-bottom: 3rem; overflow: hidden;
        }
        .progress-fill {
            height: 100%; background: var(--primary); width: 0%; border-radius: 100px;
            transition: width 1s cubic-bezier(0.23, 1, 0.32, 1);
        }

        /* Reading Cards */
        .reading-card {
            background: white; border-radius: 20px; padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid white;
            margin-bottom: 2rem; position: relative; overflow: hidden;
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .card-header {
             display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;
             border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem;
        }
        .card-title { font-size: 1.1rem; font-weight: 700; color: var(--primary); display: flex; align-items: center; gap: 0.5rem; }
        
        .data-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 2rem;
        }

        .data-item label {
            font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;
            display: block; margin-bottom: 0.5rem;
        }
        .data-item .value {
            font-family: 'Inter', sans-serif; font-size: 1.1rem; font-weight: 500; color: var(--text-main);
            background: #f8fafc; padding: 0.75rem 1rem; border-radius: 8px; border: 1px solid #e2e8f0;
        }

        /* NEW STYLES FROM REDESIGN */
        .info-group .info-label {
            font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;
            display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;
        }
        .info-group .info-value {
            font-size: 1.25rem; font-weight: 600; color: var(--primary);
        }
        .info-group .unit {
            font-size: 0.9rem; color: var(--text-muted); font-weight: 500;
        }
        .status-badge {
            display: inline-block; padding: 0.25rem 0.75rem; border-radius: 100px;
            font-size: 0.7rem; font-weight: 800; letter-spacing: 0.5px;
            background: #f1f5f9; color: var(--text-muted);
        }
        .status-completed { background: #dcfce7; color: #166534; }


        /* Document Section */
        .doc-list { display: flex; flex-direction: column; gap: 0.75rem; }
        .doc-item {
            display: flex; align-items: center; justify-content: space-between;
            padding: 1rem 1.5rem; background: #f8fafc; border: 1px solid #e2e8f0;
            border-radius: 12px; transition: var(--transition);
        }
        .doc-item:hover { border-color: var(--primary); background: white; transform: translateX(5px); }
        .doc-name { font-weight: 600; color: var(--text-main); display: flex; align-items: center; gap: 1rem; }
        .doc-action { 
            color: var(--primary); font-weight: 700; font-size: 0.8rem; text-decoration: none;
            padding: 0.5rem 1rem; background: rgba(26, 46, 35, 0.1); border-radius: 8px;
        }
        .doc-action:hover { background: var(--primary); color: white; }

        /* Empty State */
        .empty-state {
            text-align: center; padding: 4rem 2rem;
            color: var(--text-muted);
        }
        .empty-icon { font-size: 3rem; margin-bottom: 1rem; opacity: 0.3; }

        /* Animations */
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Status Badges */
        .status-badge {
            padding: 0.25rem 0.75rem; border-radius: 100px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;
        }
        .status-completed { background: #dcfce7; color: #166534; }
        .status-inprogress { background: #fef9e7; color: #854d0e; }
        .status-pending { background: #f1f5f9; color: #64748b; }

        /* Layout for Activity Feed */
        .feed-item {
            display: flex; gap: 1rem; padding-bottom: 1.5rem; border-left: 2px solid #e2e8f0; padding-left: 1.5rem; position: relative;
        }
        .feed-item::before {
            content: ''; position: absolute; left: -6px; top: 0; width: 10px; height: 10px; border-radius: 50%; background: var(--primary);
        }
        .feed-date { font-size: 0.75rem; color: var(--text-muted); font-weight: 600; margin-bottom: 0.25rem; }
        .feed-text { font-size: 0.9rem; font-weight: 500; }

    </style>
</head>
<body>

    <div id="canvas-container"></div>

    <nav>
        <a href="landingpage.html" class="nav-logo">
            <i class="fas fa-cube"></i> Constructa
        </a>
        <div class="nav-links">
            <a href="homeowner.php" class="nav-btn"><i class="fas fa-th-large"></i> Home</a>
            <a href="login.html" class="nav-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <?php if (!$project): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-folder-open"></i></div>
            <h2>No Active Projects Found</h2>
            <p>It looks like you don't have any ongoing construction projects tracked here.</p>
            <a href="start_project.php" class="doc-action" style="display:inline-block; margin-top:1rem;">Start a New Project</a>
        </div>
    <?php else: ?>
        <div class="dashboard-layout">
            <!-- Sidebar -->
            <aside class="sidebar">
                <div class="project-meta-card">
                    <div class="project-title"><?php echo htmlspecialchars($project['project_title']); ?></div>
                    <div class="project-id">Tracking ID: #<?php echo str_pad($project['id'], 6, '0', STR_PAD_LEFT); ?></div>
                    <?php if($project['engineer_id']): ?>
                        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee;">
                            <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Assigned Engineer</div>
                            <div style="font-weight: 600; font-size: 0.9rem; color: var(--primary); margin-top: 0.25rem;">
                                <?php echo htmlspecialchars($project['engineer_name']); ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div style="margin-top: 1rem; padding: 0.75rem; background: #fff1f2; border-radius: 8px; color: #be123c; font-size: 0.8rem; font-weight: 600;">
                            <i class="fas fa-exclamation-circle"></i> No Engineer Assigned
                        </div>
                    <?php endif; ?>
                </div>

                <div style="font-size: 0.75rem; font-weight: 800; color: var(--text-muted); margin-bottom: 1rem; padding-left: 0.5rem; text-transform: uppercase;">Project Lifecycle</div>
                
                <?php foreach($stages as $idx => $stage): 
                    // If pending, everything is locked/inactive
                    if ($project['status'] === 'pending') {
                        $is_completed = false;
                        $is_current = false;
                        $is_locked = true;
                        $status_class = '';
                    } else {
                        $is_completed = $idx < $current_project_stage;
                        $is_current = $idx === $current_project_stage;
                        $is_locked = $idx > $current_project_stage;
                        $status_class = $is_completed ? 'completed' : ($is_current ? 'active' : '');
                    }
                    $active_view = ($idx === $view_stage_idx) ? 'active' : '';
                ?>
                <a href="<?php echo ($project['status'] === 'pending') ? '#' : '?view=' . $idx . ($project_id_param > 0 ? '&project_id=' . $project_id_param : ''); ?>" 
                   class="stage-entry <?php echo $active_view; ?> <?php echo $status_class; ?>"
                   style="<?php echo ($project['status'] === 'pending') ? 'opacity: 0.6; cursor: default;' : ''; ?>">
                    <div class="stage-icon">
                        <i class="fas <?php echo $stage['icon']; ?>"></i>
                    </div>
                    <div class="stage-label"><?php echo $stage['label']; ?></div>
                    <?php if ($project['status'] === 'pending'): ?>
                        <i class="fas fa-clock stage-status-icon"></i>
                    <?php elseif ($is_completed): ?>
                        <i class="fas fa-check-circle stage-status-icon" style="color: var(--success);"></i>
                    <?php elseif ($is_current): ?>
                        <i class="fas fa-spinner stage-status-icon fa-spin"></i>
                    <?php else: ?>
                        <i class="fas fa-lock stage-status-icon" style="opacity: 0.3;"></i>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </aside>

            <!-- Content -->
            <main class="content-area">
                
                <?php if ($project['status'] === 'pending'): ?>
                    <div class="reading-card" style="text-align: center; padding: 5rem 2rem;">
                        <div style="width: 80px; height: 80px; background: #fff7ed; color: #ea580c; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; margin: 0 auto 2rem;">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <h2 style="font-size: 2rem; color: var(--primary); margin-bottom: 1rem;">Waiting for Engineer Approval</h2>
                        <p style="color: var(--text-muted); font-size: 1.1rem; max-width: 600px; margin: 0 auto 2rem; line-height: 1.6;">
                            Your project request has been sent to <strong><?php echo htmlspecialchars($project['engineer_name'] ?? 'the engineer'); ?></strong>. 
                            <br>Once they review and accept your project, you'll be able to track progress, view designs, and manage documents here.
                        </p>
                        <div style="background: white; border: 1px solid #e2e8f0; display: inline-block; padding: 1rem 2rem; border-radius: 12px; font-weight: 600; color: var(--text-muted);">
                            Status: <span style="color: #ea580c; font-weight: 800;">PENDING REVIEW</span>
                        </div>
                    </div>
                <?php else: ?>
                
                <div class="header-section">
                    <div class="stage-subtitle">
                        <?php echo ($view_stage_idx + 1) . '. ' . $current_stage['label']; ?>
                    </div>
                    <h1 class="stage-title-main"><?php echo $current_stage['label']; ?></h1>
                    
                    <div class="progress-container">
                        <?php 
                            // Calculate progress for this stage. If previous -> 100%, if future -> 0%, if current -> partial?
                            // For simplicity: Past=100%, Future=0%, Current=50% (or specific if available)
                            $pct = 0;
                            if ($view_stage_idx < $current_project_stage) $pct = 100;
                            elseif ($view_stage_idx > $current_project_stage) $pct = 0;
                            else $pct = 50; // Active default
                        ?>
                        <div class="progress-fill" style="width: <?php echo $pct; ?>%"></div>
                    </div>
                    
                    <p style="color: var(--text-muted); max-width: 600px; line-height: 1.6;">
                        <?php echo $current_stage['desc']; ?>
                    </p>
                </div>

                <?php if ($view_stage_idx > $current_project_stage): ?>
                    <div class="reading-card" style="text-align: center; padding: 4rem;">
                        <i class="fas fa-lock" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1.5rem;"></i>
                        <h3>Stage Scheduled</h3>
                        <p style="color: var(--text-muted);">This stage has not started yet. Data will appear here once the engineer begins work.</p>
                    </div>
                <?php else: ?>
                    
                        <!-- NEW: Data Gathering Stage Design -->
                    <?php if ($stages[$view_stage_idx]['id'] === 'gathering'): ?>
                        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; align-items: start;">
                            
                            <!-- Main Data Card -->
                            <div class="reading-card">
                                <div class="card-header">
                                    <div class="card-title">
                                        <i class="fas fa-clipboard-list"></i> Project Overview
                                        <span class="status-badge status-completed" style="margin-left: auto; font-size: 0.65rem; padding: 0.25rem 0.75rem;">ENGINEER VERIFIED</span>
                                    </div>
                                </div>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; row-gap: 2.5rem;">
                                    <div class="info-group">
                                        <div class="info-label"><i class="fas fa-ruler-combined"></i> Plot Area</div>
                                        <div class="info-value"><?php echo $project_data['gathering']['plot_area'] ?? '-'; ?> <span class="unit">sq.ft</span></div>
                                    </div>
                                    <div class="info-group">
                                        <div class="info-label"><i class="fas fa-layer-group"></i> Soil Type</div>
                                        <div class="info-value"><?php echo $project_data['gathering']['soil_type'] ?? '-'; ?></div>
                                    </div>
                                    <div class="info-group">
                                        <div class="info-label"><i class="fas fa-map-marker-alt"></i> Location</div>
                                        <div class="info-value"><?php echo $project_data['gathering']['location'] ?? '-'; ?></div>
                                    </div>
                                    <div class="info-group">
                                        <div class="info-label"><i class="fas fa-building"></i> Structure</div>
                                        <div class="info-value"><?php echo $project_data['gathering']['floors'] ?? '-'; ?> <span class="unit">Floors</span></div>
                                    </div>
                                    <div class="info-group">
                                        <div class="info-label"><i class="fas fa-door-open"></i> Configuration</div>
                                        <div class="info-value"><?php echo $project_data['gathering']['bhk'] ?? '-'; ?> BHK</div>
                                    </div>
                                    <div class="info-group">
                                        <div class="info-label"><i class="fas fa-utensils"></i> Kitchen</div>
                                        <div class="info-value"><?php echo $project_data['gathering']['kitchen'] ?? '-'; ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Side Cards: Budget & Timeline + Vision -->
                            <div style="display: flex; flex-direction: column; gap: 2rem;">
                                
                                <!-- Budget & Timeline -->
                                <div class="reading-card" style="padding: 2rem;">
                                    <div style="margin-bottom: 2rem;">
                                        <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Estimated Timeline</label>
                                        <div style="font-size: 1.5rem; font-weight: 800; color: var(--primary); margin: 0.25rem 0 0.5rem;">
                                            <?php echo $project_data['gathering']['timeline'] ?? '0'; ?> <span style="font-size: 1rem; font-weight: 500;">Months</span>
                                        </div>
                                        <div style="height: 6px; background: #e2e8f0; border-radius: 4px; overflow: hidden;">
                                            <div style="width: 60%; height: 100%; background: var(--accent);"></div>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Total Budget Target</label>
                                        <div style="font-size: 1.5rem; font-weight: 800; color: var(--success); margin: 0.25rem 0 0.5rem;">
                                            ₹<?php echo number_format((float)($project_data['gathering']['budget'] ?? 0)); ?>
                                        </div>
                                        <div style="height: 6px; background: #e2e8f0; border-radius: 4px; overflow: hidden;">
                                            <div style="width: 80%; height: 100%; background: var(--success);"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Vision / Note Card -->
                                <div class="reading-card" style="padding: 2rem; background: #fffbeb; border: 1px solid #fef3c7;">
                                    <div style="font-weight: 700; color: #b45309; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                                        <i class="fas fa-lightbulb"></i> Client Vision
                                    </div>
                                    <div style="font-style: italic; color: #92400e; font-size: 0.95rem; line-height: 1.6;">
                                        "<?php echo !empty($project_data['gathering']['notes']) ? htmlspecialchars($project_data['gathering']['notes']) : 'No specific notes provided.'; ?>"
                                    </div>
                                </div>

                            </div>
                        </div>

                    <?php elseif ($stages[$view_stage_idx]['id'] === 'survey'): ?>
                        <div class="reading-card">
                            <div class="card-header">
                                <div class="card-title">
                                    <i class="fas fa-map-marked-alt"></i> Site Survey Report
                                    <span class="status-badge" style="margin-left: auto; background: #eff6ff; color: #3b82f6; border: 1px solid #bfdbfe;">MEASURED ON SITE</span>
                                </div>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; margin-bottom: 3rem;">
                                <div class="survey-stat-box" style="background: #f8fafc; padding: 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0; text-align: center;">
                                    <div style="color: var(--text-muted); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.5rem;">Net Plot Area</div>
                                    <div style="font-size: 2rem; font-weight: 800; color: var(--primary);"><?php echo $project_data['survey']['total_area'] ?? '0'; ?> <span style="font-size: 1rem; color: var(--text-muted);">sq.ft</span></div>
                                </div>
                                
                                <div class="survey-stat-box" style="background: #f8fafc; padding: 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0; text-align: center;">
                                    <div style="color: var(--text-muted); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.5rem;">Frontage</div>
                                    <div style="font-size: 2rem; font-weight: 800; color: var(--primary);"><?php echo $project_data['survey']['f_width'] ?? '0'; ?> <span style="font-size: 1rem; color: var(--text-muted);">ft</span></div>
                                </div>

                                <div class="survey-stat-box" style="background: #f8fafc; padding: 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0; text-align: center;">
                                    <div style="color: var(--text-muted); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.5rem;">Depth</div>
                                    <div style="font-size: 2rem; font-weight: 800; color: var(--primary);"><?php echo $project_data['survey']['l_depth'] ?? '0'; ?> <span style="font-size: 1rem; color: var(--text-muted);">ft</span></div>
                                </div>
                            </div>

                            <div class="info-group" style="padding: 1.5rem; background: #fff1f2; border: 1px solid #ffe4e6; border-radius: 12px;">
                                <div class="info-label" style="color: #be123c;"><i class="fas fa-exclamation-triangle"></i> Access & Constraints</div>
                                <div class="info-value" style="color: #9f1239; font-weight: 500; margin-top: 0.5rem;">
                                    <?php echo !empty($project_data['survey']['constraints']) ? htmlspecialchars($project_data['survey']['constraints']) : 'No major physical constraints reported for this site.'; ?>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($stages[$view_stage_idx]['id'] === 'planning'): ?>
                        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                            <!-- Design Specs -->
                            <div class="reading-card">
                                <div class="card-header">
                                    <div class="card-title"><i class="fas fa-drafting-compass"></i> Structural Design Specs</div>
                                    <span class="status-badge" style="margin-left: auto; text-transform: uppercase;">
                                        <?php echo $project_data['planning']['elevation_style'] ?? 'Modern'; ?> Style
                                    </span>
                                </div>
                                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem;">
                                    <div class="info-group">
                                        <div class="info-label"><i class="fas fa-arrows-alt-v"></i> Floor Height</div>
                                        <div class="info-value"><?php echo $project_data['planning']['floor_height'] ?? '10.5'; ?>'</div>
                                    </div>
                                    <div class="info-group">
                                        <div class="info-label"><i class="fas fa-shield-alt"></i> Parapet Height</div>
                                        <div class="info-value"><?php echo $project_data['planning']['parapet'] ?? '3.5'; ?>'</div>
                                    </div>
                                    <div class="info-group">
                                        <div class="info-label"><i class="fas fa-stairs"></i> Staircase</div>
                                        <div class="info-value" style="text-transform: capitalize;"><?php echo $project_data['planning']['stairs'] ?? 'Internal'; ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Layout Config -->
                            <div class="reading-card">
                                <div class="card-header">
                                    <div class="card-title"><i class="fas fa-th-large"></i> Layout</div>
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem;">
                                        <span style="font-size: 0.9rem; color: var(--text-muted); font-weight: 500;">Bedrooms</span>
                                        <span style="font-weight: 700; color: var(--primary);"><?php echo $project_data['planning']['bedrooms'] ?? '2'; ?></span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem;">
                                        <span style="font-size: 0.9rem; color: var(--text-muted); font-weight: 500;">Bathrooms</span>
                                        <span style="font-weight: 700; color: var(--primary);"><?php echo $project_data['planning']['bathrooms'] ?? '2'; ?></span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span style="font-size: 0.9rem; color: var(--text-muted); font-weight: 500;">Kitchen Pos</span>
                                        <span style="font-weight: 700; color: var(--primary);"><?php echo $project_data['planning']['kitchen_pos'] ?? 'SE'; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($stages[$view_stage_idx]['id'] === 'estimation'): ?>
                        <div class="reading-card">
                            <div class="card-header">
                                <div class="card-title"><i class="fas fa-coins"></i> Project Cost Estimation</div>
                                <span class="status-badge" style="margin-left: auto; background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa; text-transform: uppercase;">
                                    <?php echo $project_data['estimation']['quality'] ?? 'Standard'; ?> Quality
                                </span>
                            </div>
                            
                            <div style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; padding: 2rem; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 2rem;">
                                <div>
                                    <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Estimated Grand Total</div>
                                    <div style="font-size: 3rem; font-weight: 800; color: var(--primary); letter-spacing: -1px; line-height: 1;">
                                        ₹<?php echo number_format((float)($project_data['estimation']['total_cost'] ?? 0)); ?>
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.5rem;">Includes material & labor estimates</div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Total Built-Up Area</div>
                                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--text-main);">
                                        <?php 
                                            // Re-calculate visual estimate of area if not saved explicitly, or rely on Survey
                                            $area = $project_data['survey']['total_area'] ?? 0;
                                            $fl = $project_data['gathering']['floors'] ?? 1;
                                            echo number_format($area * $fl); 
                                        ?> sq.ft
                                    </div>
                                </div>
                            </div>

                            <div style="padding: 1.5rem; background: #fffbeb; border-radius: 12px; border: 1px solid #fef3c7;">
                                <div style="font-weight: 700; color: #b45309; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-info-circle"></i> Note from Engineer
                                </div>
                                <p style="color: #92400e; font-size: 0.9rem; margin: 0;">
                                    <?php echo !empty($project_data['estimation']['notes']) ? htmlspecialchars($project_data['estimation']['notes']) : 'No additional notes attached to this estimation.'; ?>
                                </p>
                            </div>
                        </div>

                    <?php elseif ($stages[$view_stage_idx]['id'] === 'approvals'): ?>
                        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                            <!-- Permissions Grid -->
                            <div class="reading-card">
                                <div class="card-header">
                                    <div class="card-title"><i class="fas fa-stamp"></i> Permissions Tracking</div>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <?php 
                                        $apps = $project_data['approvals'] ?? [];
                                        $app_items = [
                                            'Plan Sanction' => $apps['plan_approval'] ?? 'pending',
                                            'Structural' => $apps['structural_safety'] ?? 'pending',
                                            'Land Verification' => $apps['land_verification'] ?? 'pending',
                                            'Water' => $apps['utility_water'] ?? 'pending',
                                            'Electricity' => $apps['utility_electricity'] ?? 'pending',
                                            'Sewerage' => $apps['utility_sewer'] ?? 'pending'
                                        ];
                                        foreach($app_items as $name => $status):
                                            $st_color = ($status === 'approved') ? '#22c55e' : (($status === 'rejected') ? '#ef4444' : '#f59e0b');
                                            $st_bg = ($status === 'approved') ? '#dcfce7' : (($status === 'rejected') ? '#fee2e2' : '#fef3c7');
                                            $st_icon = ($status === 'approved') ? 'fa-check' : (($status === 'rejected') ? 'fa-times' : 'fa-hourglass-start');
                                    ?>
                                    <div style="background: <?php echo $st_bg; ?>; padding: 1rem; border-radius: 10px; border: 1px solid <?php echo $st_color; ?>40; display: flex; flex-direction: column; gap: 0.5rem;">
                                        <div style="font-size: 0.8rem; font-weight: 700; color: <?php echo $st_color; ?>; text-transform: uppercase;"><?php echo $status; ?></div>
                                        <div style="font-weight: 600; font-size: 0.95rem; color: var(--primary); display: flex; align-items: center; justify-content: space-between;">
                                            <?php echo $name; ?>
                                            <i class="fas <?php echo $st_icon; ?>" style="color: <?php echo $st_color; ?>;"></i>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Fees & NOCs -->
                            <div style="display: flex; flex-direction: column; gap: 2rem;">
                                <div class="reading-card">
                                    <div class="card-header">
                                        <div class="card-title"><i class="fas fa-receipt"></i> Official Fees</div>
                                    </div>
                                    <div style="text-align: center; padding: 1rem 0;">
                                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.25rem;">Total Challan Amount</div>
                                        <div style="font-size: 1.8rem; font-weight: 800; color: var(--primary);">₹<?php echo number_format((float)($apps['fee_amount'] ?? 0)); ?></div>
                                        <div class="status-badge" style="margin-top: 1rem; font-size: 0.65rem; 
                                            background: <?php echo ($apps['payment_status']??'unpaid')==='paid'?'#dcfce7':'#fee2e2'; ?>; 
                                            color: <?php echo ($apps['payment_status']??'unpaid')==='paid'?'#166534':'#991b1b'; ?>;">
                                            PAYMENT: <?php echo strtoupper($apps['payment_status'] ?? 'UNPAID'); ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="reading-card">
                                    <div class="card-header">
                                        <div class="card-title" style="font-size: 1rem;"><i class="fas fa-shield-alt"></i> NOC Status</div>
                                    </div>
                                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                        <?php 
                                            // Mapping NOCs
                                            $nocs = $apps['nocs'] ?? [];
                                            $noc_list = [
                                                'Fire Dept' => $nocs['fire_noc'] ?? false,
                                                'Airport Auth' => $nocs['airport_noc'] ?? false,
                                                'Environment' => $nocs['env_noc'] ?? false
                                            ];
                                            foreach($noc_list as $nlabel => $nval):
                                        ?>
                                        <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.9rem;">
                                            <span style="color: var(--text-muted);"><?php echo $nlabel; ?></span>
                                            <i class="fas <?php echo $nval ? 'fa-check-circle' : 'fa-circle'; ?>" style="color: <?php echo $nval ? '#22c55e' : '#e2e8f0'; ?>;"></i>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($stages[$view_stage_idx]['id'] === 'execution'): ?>
                         
                         <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                            <!-- Timeline/Progress -->
                            <div class="reading-card">
                                <div class="card-header">
                                    <div class="card-title"><i class="fas fa-hard-hat"></i> Active Construction Phases</div>
                                    <div style="font-weight: 800; color: var(--primary);">
                                        <?php 
                                            $phases = $project_data['execution']['phases'] ?? [];
                                            $total_prog = 0;
                                            if(count($phases) > 0) {
                                                $sum = 0;
                                                foreach($phases as $p) $sum += $p['progress'];
                                                $total_prog = round($sum / count($phases));
                                            }
                                            echo $total_prog . '% OVERALL';
                                        ?>
                                    </div>
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                                    <?php foreach(($project_data['execution']['phases'] ?? []) as $phase): ?>
                                        <div>
                                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                                <span style="font-weight: 600; font-size: 0.95rem;"><?php echo $phase['name']; ?></span>
                                                <span style="font-size: 0.85rem; font-weight: 700; color: <?php echo ($phase['status']=='completed')?'var(--success)':'var(--accent)'; ?>;">
                                                    <?php echo $phase['progress']; ?>%
                                                </span>
                                            </div>
                                            <div style="height: 8px; background: #e2e8f0; border-radius: 6px; overflow: hidden;">
                                                <div style="height: 100%; width: <?php echo $phase['progress']; ?>%; background: <?php echo ($phase['status']=='completed')?'var(--success)':'var(--primary)'; ?>; transition: width 1s;"></div>
                                            </div>
                                            <div style="display: flex; justify-content: space-between; margin-top: 0.4rem; font-size: 0.75rem; color: var(--text-muted);">
                                                <span><?php echo !empty($phase['start']) ? 'Started: '.$phase['start'] : 'Scheduled'; ?></span>
                                                <span><?php echo !empty($phase['end']) ? 'Target: '.$phase['end'] : ''; ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Site Details -->
                            <div class="reading-card">
                                <div class="card-header">
                                    <div class="card-title"><i class="fas fa-truck-loading"></i> Site Logistics</div>
                                </div>
                                <div class="info-group" style="margin-bottom: 1.5rem;">
                                    <div class="info-label"><i class="fas fa-user-hard-hat"></i> Contractor</div>
                                    <div class="info-value" style="font-size: 1.1rem;"><?php echo !empty($project_data['execution']['contractor_name']) ? htmlspecialchars($project_data['execution']['contractor_name']) : 'TBD'; ?></div>
                                </div>
                                <div class="info-group">
                                    <div class="info-label"><i class="fas fa-users-cog"></i> Labor Compliance</div>
                                    <div class="status-badge" style="margin-top: 0.5rem; background: #dcfce7; color: #166534; text-transform: uppercase;">
                                        <?php echo $project_data['execution']['labor_status'] ?? 'Compliant'; ?>
                                    </div>
                                </div>
                                
                                <div style="margin-top: 2rem;">
                                    <div class="info-label" style="margin-bottom: 0.5rem;">Open Issues</div>
                                    <?php 
                                        $issues = $project_data['execution']['issues'] ?? [];
                                        $open_issues = array_filter($issues, function($i) { return $i['status'] !== 'resolved'; });
                                    ?>
                                    <?php if(empty($open_issues)): ?>
                                        <div style="font-size: 0.85rem; color: var(--text-muted); font-style: italic;">No open issues reported.</div>
                                    <?php else: ?>
                                        <?php foreach($open_issues as $iss): ?>
                                            <div style="padding: 0.75rem; background: #fee2e2; border-radius: 8px; border: 1px solid #fecaca; font-size: 0.85rem; color: #991b1b; margin-bottom: 0.5rem;">
                                                <?php echo htmlspecialchars($iss['reason']); ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                         </div>

                    <?php endif; ?>

                    <!-- SHARED: Documents Section -->
                    <div class="reading-card">
                        <div class="card-header">
                            <div class="card-title"><i class="fas fa-folder"></i> Documents & Reports</div>
                        </div>
                        <div class="doc-list">
                            <?php if (empty($stage_documents) && empty($project_details_raw)): ?>
                                <p style="color: var(--text-muted); text-align: center; padding: 1rem;">No documents available yet.</p>
                            <?php elseif (empty($stage_documents)): ?>
                                <p style="color: var(--text-muted); font-size: 0.9rem;">No documents uploaded for this stage.</p>
                            <?php else: ?>
                                <?php foreach($stage_documents as $doc): ?>
                                    <div class="doc-item">
                                        <div class="doc-name">
                                            <?php 
                                            // Determine Icon
                                            $ext = strtolower(pathinfo($doc['file_name'], PATHINFO_EXTENSION));
                                            $icon = 'fa-file-alt';
                                            $color = 'var(--primary)';
                                            
                                            if ($ext == 'pdf') { $icon = 'fa-file-pdf'; $color = '#ef4444'; }
                                            elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) { $icon = 'fa-file-image'; $color = '#3b82f6'; }
                                            elseif (in_array($ext, ['dwg', 'dxf'])) { $icon = 'fa-drafting-compass'; $color = '#8b5cf6'; }
                                            elseif (in_array($ext, ['xls', 'xlsx'])) { $icon = 'fa-file-excel'; $color = '#16a34a'; }
                                            ?>
                                            <i class="fas <?php echo $icon; ?>" style="color: <?php echo $color; ?>;"></i>
                                            <div>
                                                <div style="font-weight: 600;"><?php echo htmlspecialchars($doc['file_name']); ?></div>
                                                <div style="font-size: 0.65rem; color: #999;">
                                                    <?php echo number_format($doc['file_size'] / 1024 / 1024, 2); ?> MB • <?php echo date('M d, Y', strtotime($doc['uploaded_at'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" class="doc-action" target="_blank" download><i class="fas fa-download"></i> Download</a>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <!-- Fallback for specific known generated reports (Legacy) -->
                            <?php if ($view_stage_idx >= 3 && isset($project_data['estimation']['boq_file'])): ?>
                                <div class="doc-item">
                                    <div class="doc-name"><i class="fas fa-file-excel" style="color: #16a34a;"></i> BOQ Estimation</div>
                                    <a href="<?php echo $project_data['estimation']['boq_file']; ?>" class="doc-action"><i class="fas fa-download"></i></a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Activity Feed (Simulated) -->
                    <div style="margin-top: 3rem;">
                        <h3 style="font-size: 1rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 1.5rem;">Recent Activity</h3>
                        <div style="display: flex; flex-direction: column; gap: 0;">
                            <div class="feed-item">
                                <div>
                                    <div class="feed-date">Today</div>
                                    <div class="feed-text">You viewed the <?php echo $current_stage['label']; ?> dashboard.</div>
                                </div>
                            </div>
                            <?php if($project['updated_at']): ?>
                            <div class="feed-item">
                                <div>
                                    <div class="feed-date"><?php echo date('M d, Y', strtotime($project['updated_at'])); ?></div>
                                    <div class="feed-text">Project details were updated by Engineer.</div>
                                </div>
                            </div>
                            <?php endif; ?>
                            <div class="feed-item">
                                <div>
                                    <div class="feed-date"><?php echo date('M d, Y', strtotime($project['created_at'])); ?></div>
                                    <div class="feed-text">Project created started.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php endif; ?>
                <?php endif; ?>
            </main>
        </div>
    <?php endif; ?>

    <script>
        // 3D Background Logic (Premium Sync)
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.getElementById('canvas-container');
            if (container && typeof THREE !== 'undefined') {
                const scene = new THREE.Scene();
                scene.background = new THREE.Color('#f6f7f2');

                const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
                camera.position.z = 10;
                camera.position.y = 2;

                const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
                renderer.setSize(window.innerWidth, window.innerHeight);
                renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
                container.appendChild(renderer.domElement);

                // Lighting
                const ambientLight = new THREE.AmbientLight(0xffffff, 0.7);
                scene.add(ambientLight);

                const mainLight = new THREE.DirectionalLight(0xffffff, 0.8);
                mainLight.position.set(10, 20, 10);
                scene.add(mainLight);

                // Reusable wireframe building elements
                const floorGroup = new THREE.Group();
                scene.add(floorGroup);
                
                const buildMat = new THREE.MeshPhongMaterial({ 
                    color: 0x1a2e23, 
                    transparent: true, 
                    opacity: 0.05, 
                    side: THREE.DoubleSide 
                });
                const edgeMat = new THREE.LineBasicMaterial({ 
                    color: 0x1a2e23, 
                    transparent: true, 
                    opacity: 0.15 
                });

                // Generate a city grid
                const gridSize = 8;
                const spacing = 4;
                for (let x = -gridSize; x <= gridSize; x++) {
                    for (let z = -gridSize; z <= gridSize; z++) {
                        if (Math.abs(x) < 2 && Math.abs(z) < 2) continue; // Clear center

                        const h = Math.random() * 4 + 1;
                        const geo = new THREE.BoxGeometry(1.5, h, 1.5);
                        const mesh = new THREE.Mesh(geo, buildMat);
                        mesh.position.y = h / 2;
                        
                        const edges = new THREE.EdgesGeometry(geo);
                        const line = new THREE.LineSegments(edges, edgeMat);
                        line.position.y = h / 2;
                        
                        const building = new THREE.Group();
                        building.add(mesh);
                        building.add(line);
                        
                        building.position.set(x * spacing, -5, z * spacing);
                        floorGroup.add(building);
                    }
                }

                // Primary Hero Asset (Floating Wireframe House)
                const heroGroup = new THREE.Group();
                
                // Base
                const floorGeo = new THREE.BoxGeometry(4, 0.2, 4);
                const floorLine = new THREE.LineSegments(new THREE.EdgesGeometry(floorGeo), new THREE.LineBasicMaterial({color: 0x1a2e23, opacity: 0.8}));
                heroGroup.add(floorLine);

                // Walls
                const wallGeo = new THREE.BoxGeometry(3.5, 2.5, 3.5);
                const wallLines = new THREE.LineSegments(new THREE.EdgesGeometry(wallGeo), new THREE.LineBasicMaterial({color: 0x1a2e23}));
                wallLines.position.y = 1.35;
                heroGroup.add(wallLines);

                // Roof
                const roofGeo = new THREE.ConeGeometry(3, 2, 4);
                const roofLines = new THREE.LineSegments(new THREE.EdgesGeometry(roofGeo), new THREE.LineBasicMaterial({color: 0xd4af37})); // Gold accent
                roofLines.position.y = 3.6;
                roofLines.rotation.y = Math.PI / 4;
                heroGroup.add(roofLines);

                heroGroup.position.set(0, 0, 0);
                scene.add(heroGroup);

                // Parallax Mouse Effect
                let mouseX = 0, mouseY = 0;
                document.addEventListener('mousemove', (e) => {
                    mouseX = (e.clientX - window.innerWidth / 2) * 0.0005;
                    mouseY = (e.clientY - window.innerHeight / 2) * 0.0005;
                });

                const animate = () => {
                    requestAnimationFrame(animate);
                    const time = Date.now() * 0.001;
                    
                    // Floating rotation
                    heroGroup.rotation.y += 0.005;
                    heroGroup.position.y = Math.sin(time) * 0.5;
                    
                    // Grid movement
                    floorGroup.rotation.y += 0.002;
                    
                    // Mouse effect
                    floorGroup.rotation.x += 0.05 * (mouseY - floorGroup.rotation.x);
                    floorGroup.rotation.y += 0.05 * (mouseX - floorGroup.rotation.y);
                    
                    renderer.render(scene, camera);
                };
                animate();

                window.addEventListener('resize', () => {
                    camera.aspect = window.innerWidth / window.innerHeight;
                    camera.updateProjectionMatrix();
                    renderer.setSize(window.innerWidth, window.innerHeight);
                });
            }
        });
    </script>
</body>
</html>
