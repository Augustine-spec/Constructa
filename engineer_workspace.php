<?php
session_start();
require_once 'backend/config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'engineer') {
    header('Location: login.html');
    exit();
}

$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$conn = getDatabaseConnection();
$username = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Engineer';

// Fetch project details
$stmt = $conn->prepare("
    SELECT pr.*, u.name as homeowner_name, u.email as homeowner_email 
    FROM project_requests pr 
    JOIN users u ON pr.homeowner_id = u.id 
    WHERE pr.id = ?
");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

if (!$project) {
    die("Project not found.");
}

// Construction Stages Configuration
$stages = [
    ["id" => "gathering", "label" => "Data Gathering & Requirements", "icon" => "fa-file-invoice", "desc" => "Comprehensive collection of homeowner requirements and site constraints."],
    ["id" => "survey", "label" => "Site Inspection & Survey", "icon" => "fa-map-marked-alt", "desc" => "Physical site inspection, plot measurement, and road access verification."],
    ["id" => "planning", "label" => "Planning & Design", "icon" => "fa-pencil-ruler", "desc" => "Architectural layout, floor plans, and spatial organization."],
    ["id" => "estimation", "label" => "Cost Estimation & BOQ", "icon" => "fa-file-invoice-dollar", "desc" => "Detailed cost estimation and Bill of Quantities preparation."],
    ["id" => "approvals", "label" => "Approval & Permissions", "icon" => "fa-file-signature", "desc" => "Tracking and managing legal approvals, permits, and NOCs required for construction."],
    ["id" => "execution", "label" => "Construction Execution", "icon" => "fa-hard-hat", "desc" => "On-site construction activities and structural work."],
    ["id" => "handover", "label" => "Handover & Closure", "icon" => "fa-key", "desc" => "Final inspection, documentation handover, and project closure."]
];

$current_project_stage = (int)$project['current_stage'];
$view_stage_idx = isset($_GET['view']) ? (int)$_GET['view'] : $current_project_stage;
$current_stage_idx = min(max(0, $view_stage_idx), count($stages) - 1);
$current_stage = $stages[$current_stage_idx];

$project_details_raw = $project['project_details'] ?? '';
$project_details = !empty($project_details_raw) ? json_decode($project_details_raw, true) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workspace | <?php echo htmlspecialchars($project['project_title']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <style>
        :root {
            --primary: #1a2e23;
            --primary-light: #2c4a3b;
            --accent: #d4af37;
            --bg-base: #f6f7f2;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.8);
            --transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1);
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

        /* Navbar (Consistent) */
        nav {
            background: rgba(255, 255, 255, 0.6);
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

        .nav-links { display: flex; gap: 1.5rem; align-items: center; }

        .nav-btn {
            background: white; border: 1px solid rgba(0, 0, 0, 0.05);
            padding: 0.75rem 1.5rem; border-radius: 12px;
            font-weight: 700; font-size: 0.85rem; text-transform: uppercase;
            color: var(--text-main); text-decoration: none;
            display: inline-flex; align-items: center; gap: 0.75rem;
            transition: var(--transition); box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        .nav-btn:hover {
            transform: translateY(-2px); background: var(--primary); color: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        /* Workspace Layout */
        .workspace-layout {
            flex: 1; display: grid; grid-template-columns: 320px 1fr 380px;
            height: calc(100vh - 80px); overflow: hidden;
        }

        /* Sidebar Tracker */
        .lifecycle-sidebar {
            background: var(--glass-bg); backdrop-filter: blur(15px);
            border-right: 1px solid var(--glass-border);
            padding: 2rem 1.5rem; overflow-y: auto; scrollbar-width: none;
        }
        .lifecycle-sidebar::-webkit-scrollbar { display: none; }

        .sidebar-title {
            font-size: 0.8rem; font-weight: 800; color: var(--primary);
            text-transform: uppercase; letter-spacing: 2px; margin-bottom: 2rem;
            padding-left: 1rem; opacity: 0.6;
        }

        .stage-entry {
            display: flex; align-items: center; gap: 1rem;
            padding: 1rem; border-radius: 16px; cursor: pointer;
            transition: var(--transition); position: relative;
            margin-bottom: 0.5rem; border: 1px solid transparent;
        }

        .stage-entry i {
            width: 40px; height: 40px; border-radius: 50%;
            background: white; display: flex; align-items: center; justify-content: center;
            font-size: 0.9rem; color: var(--text-muted);
            box-shadow: 0 4px 10px rgba(0,0,0,0.05); transition: var(--transition);
        }

        .stage-label { font-size: 0.95rem; font-weight: 600; color: var(--text-muted); }

        .stage-entry.active {
            background: white; border-color: var(--primary);
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        .stage-entry.active i { background: var(--primary); color: white; transform: scale(1.1); }
        .stage-entry.active .stage-label { color: var(--primary); font-weight: 800; }
        .stage-entry.view-active { border: 2px solid var(--accent); background: white; box-shadow: 0 0 15px rgba(217, 119, 6, 0.2); }
        .stage-entry.completed i { color: var(--accent); background: #fef9e7; }
        .stage-entry.completed .stage-label { color: var(--text-main); }
        .stage-entry.locked { opacity: 0.4; pointer-events: none; }

        /* Main Workspace */
        .canvas-area {
            padding: 3.5rem; overflow-y: auto; position: relative;
        }

        .stage-header { margin-bottom: 3rem; }
        .stage-header h1 { font-size: 3rem; font-weight: 800; color: var(--primary); letter-spacing: -1.5px; }
        .stage-header p { color: var(--text-muted); font-size: 1.1rem; margin-top: 0.5rem; }

        .workspace-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }

        .tool-card {
            background: white; border: 1px solid var(--glass-border);
            padding: 2rem; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15), 0 0 1px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
        }
        .tool-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0,0,0,0.06); }

        .tool-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; }
        .tool-icon {
            width: 50px; height: 50px; border-radius: 12px; background: #eef2f1;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem; color: var(--primary);
        }

        /* Context Panel */
        .context-sidebar {
            background: var(--glass-bg); backdrop-filter: blur(15px);
            border-left: 1px solid var(--glass-border);
            padding: 2.5rem; display: flex; flex-direction: column; gap: 3rem;
        }

        .context-title {
            font-size: 0.75rem; font-weight: 800; color: var(--accent);
            text-transform: uppercase; letter-spacing: 2px; margin-bottom: 1.5rem;
        }

        .task-box {
            background: white; border: 1px solid var(--glass-border);
            padding: 1.5rem; border-radius: 20px; display: flex; align-items: center;
            gap: 1rem; margin-bottom: 1rem; transition: var(--transition);
            cursor: pointer;
        }
        .task-box:hover { border-color: var(--primary); transform: translateX(5px); }

        /* Progress Bar (Standardized) */
        .status-header {
            background: white; padding: 1rem 3rem; border-bottom: 1px solid var(--glass-border);
            display: flex; justify-content: space-between; align-items: center;
        }
        .bar-container { width: 300px; height: 8px; background: #eee; border-radius: 10px; overflow: hidden; }
        .bar-fill { height: 100%; width: 0%; background: var(--primary); transition: 1.5s ease-out; }

        /* Actions */
        .btn-action {
            background: var(--primary); color: white; border: none; padding: 1rem 2rem;
            border-radius: 12px; font-weight: 700; cursor: pointer; transition: var(--transition);
            display: inline-flex; align-items: center; gap: 0.75rem; width: 100%; justify-content: center;
        }
        .btn-action:hover { background: var(--primary-light); transform: scale(1.02); }

        #house-preview {
            position: fixed; right: 50px; bottom: 100px; width: 450px; height: 450px;
            pointer-events: none; z-index: 10; opacity: 1; filter: drop-shadow(0 20px 40px rgba(0,0,0,0.1));
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        /* Premium Gathering Wizard with 3D Depth */
        .gathering-wizard {
            display: flex; flex-direction: column; gap: 2rem;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px) saturate(180%);
            padding: 3rem; border-radius: 32px;
            box-shadow: 
                0 20px 50px rgba(0,0,0,0.05),
                0 1px 1px rgba(255,255,255,0.8) inset;
            margin-top: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.5);
            transform-style: preserve-3d;
            perspective: 1000px;
            transition: transform 0.6s cubic-bezier(0.23, 1, 0.32, 1);
        }

        .gathering-step { display: none; }
        .gathering-step.active { display: flex; flex-direction: column; gap: 2rem; animation: slideUp 0.6s cubic-bezier(0.23, 1, 0.32, 1) forwards; }

        .input-group { 
            display: flex; flex-direction: column; gap: 0.85rem; 
            position: relative;
        }
        .input-group label { 
            font-size: 0.75rem; font-weight: 800; color: var(--primary); 
            text-transform: uppercase; letter-spacing: 1.5px;
            opacity: 0.7; transition: var(--transition);
        }
        
        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .c-input { 
            width: 100%;
            padding: 1.2rem 1.5rem; border-radius: 16px; 
            border: 2px solid transparent;
            font-size: 1.1rem; font-weight: 500;
            transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1); 
            background: rgba(248, 250, 252, 0.8);
            box-shadow: 
                0 4px 6px rgba(0,0,0,0.02),
                inset 0 2px 4px rgba(0,0,0,0.05);
            color: var(--text-main);
        }

        .c-input:focus { 
            border-color: var(--primary); 
            outline: none; 
            background: white; 
            box-shadow: 
                0 10px 25px rgba(26, 46, 35, 0.08),
                0 0 0 4px rgba(26, 46, 35, 0.03); 
            transform: translateY(-2px);
        }

        /* Live Validation Styles */
        .input-group.valid .c-input { border-color: #10b981; background: #f0fdf4; }
        .input-group.valid::after {
            content: '\f058'; font-family: 'Font Awesome 6 Free'; font-weight: 900;
            position: absolute; right: 1.5rem; top: 3.2rem; color: #10b981;
            font-size: 1.2rem; animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .input-group.invalid .c-input { border-color: #ef4444; background: #fef2f2; }
        .input-group.invalid::after {
            content: '\f06a'; font-family: 'Font Awesome 6 Free'; font-weight: 900;
            position: absolute; right: 1.5rem; top: 3.2rem; color: #ef4444;
            font-size: 1.2rem; animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .validation-msg {
            font-size: 0.65rem; color: #ef4444; font-weight: 700;
            margin-top: 0.35rem; display: none; text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .input-group.invalid .validation-msg { display: block; }

        .choice-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .choice-card {
            padding: 2.5rem; border: 2px solid transparent; border-radius: 24px;
            background: white; text-align: center; cursor: pointer; 
            transition: all 0.5s cubic-bezier(0.23, 1, 0.32, 1);
            display: flex; flex-direction: column; gap: 1.2rem; align-items: center;
            box-shadow: 0 10px 20px rgba(0,0,0,0.03);
        }
        .choice-card i { font-size: 2.5rem; color: var(--text-muted); transition: inherit; }
        .choice-card:hover { transform: translateY(-8px) scale(1.02); box-shadow: 0 20px 40px rgba(0,0,0,0.07); }
        .choice-card.active { border-color: var(--primary); background: #f0f4f2; transform: translateY(-8px); }
        .choice-card.active i { color: var(--primary); transform: scale(1.1); }

        .wizard-footer { display: flex; justify-content: space-between; margin-top: 2rem; }

        /* Document Repo */
        .doc-repo {
            display: flex; flex-direction: column; gap: 1rem;
            background: white; padding: 1.5rem; border-radius: 20px;
            border: 1px solid var(--glass-border); margin-top: 2rem;
        }
        .doc-list { display: flex; flex-direction: column; gap: 0.5rem; }
        .doc-item {
            display: flex; align-items: center; justify-content: space-between;
            padding: 1rem; background: #fff; border-radius: 12px;
            font-size: 0.85rem; border: 1px solid #edf2f7; transition: var(--transition);
        }
        .doc-item:hover { border-color: var(--accent); transform: translateX(5px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .doc-item button:hover, .doc-action-btn:hover { background: rgba(0,0,0,0.05); transform: scale(1.1); }
        .doc-action-btn.delete:hover { background: #fee2e2 !important; }
        .doc-action-btn.download:hover { background: #eef2f1 !important; }

        .upload-zone {
            border: 2px dashed #ddd; padding: 1.5rem; border-radius: 15px;
            text-align: center; color: var(--text-muted); cursor: pointer;
            font-size: 0.85rem; transition: var(--transition);
        }
        .upload-zone:hover { border-color: var(--primary); color: var(--primary); }

        /* Stage controls */
        .stage-footer-actions {
            position: sticky; bottom: 0; background: rgba(255,255,255,0.8);
            backdrop-filter: blur(10px); padding: 1.5rem 0; border-top: 1px solid #eee;
            display: flex; justify-content: flex-end; gap: 1rem; margin-top: 3rem;
            z-index: 10;
        }

        @keyframes popIn { 0% { opacity: 0; transform: scale(0.5); } 70% { transform: scale(1.1); } 100% { opacity: 1; transform: scale(1); } }

        /* Workspace Transition Overlay */
        #entry-transition {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background: #020617; z-index: 10000;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            color: white; overflow: hidden;
        }

        #transition-canvas {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        }

        .transition-content {
            position: relative; z-index: 10; text-align: center;
            display: flex; flex-direction: column; align-items: center; gap: 1.5rem;
        }

        .loader-ring {
            width: 80px; height: 80px; border: 3px solid rgba(212, 175, 55, 0.1);
            border-top: 3px solid var(--accent); border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .transition-status {
            font-family: 'Outfit'; font-size: 0.8rem; font-weight: 800;
            letter-spacing: 5px; text-transform: uppercase; color: var(--accent);
            opacity: 0.8;
        }

        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

        .svg-lbl { font-weight: 700; fill: var(--primary); font-family: 'JetBrains Mono', monospace; }

        /* Floating 3D Elements */
        .form-3d-icon {
            position: absolute; right: -40px; top: -40px;
            width: 80px; height: 80px; opacity: 0.1;
            pointer-events: none; transform: rotate(-15deg);
        }

        /* Planning Specific Styles */
        .c-slider {
            -webkit-appearance: none; width: 100%; height: 6px;
            background: #e2e8f0; border-radius: 5px; outline: none;
            transition: 0.3s;
        }
        .c-slider::-webkit-slider-thumb {
            -webkit-appearance: none; width: 20px; height: 20px;
            background: var(--primary); border-radius: 50%; cursor: pointer;
            border: 3px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .val-display { font-family: 'JetBrains Mono'; font-weight: 800; color: var(--accent); font-size: 1.1rem; min-width: 20px; }

        /* Estimation Specific Styles */
        .cost-card {
            background: white; border-radius: 20px; border: 1px solid #eee;
            padding: 1.5rem; transition: var(--transition);
        }
        .cost-card:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .cost-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; }
        .cost-title { font-size: 0.8rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; }
        .cost-value { font-size: 1.4rem; font-weight: 800; font-family: 'Outfit'; color: var(--primary); }
        .cost-qty { font-size: 0.75rem; color: var(--text-muted); font-weight: 600; }
        
        .estimation-table {
            width: 100%; border-collapse: separate; border-spacing: 0 8px;
        }
        .estimation-table th {
            text-align: left; padding: 1rem; font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;
        }
        .estimation-table td {
            background: white; padding: 1rem; font-size: 0.85rem; border-top: 1px solid #f1f1f1; border-bottom: 1px solid #f1f1f1;
        }
        .estimation-table td:first-child { border-left: 1px solid #f1f1f1; border-top-left-radius: 12px; border-bottom-left-radius: 12px; }
        .estimation-table td:last-child { border-right: 1px solid #f1f1f1; border-top-right-radius: 12px; border-bottom-right-radius: 12px; }

        .budget-meter {
            height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden; margin: 1rem 0;
        }
        .budget-fill { height: 100%; width: 0%; transition: width 1s ease, background 0.5s ease; }
        
        .quality-btn {
            padding: 0.5rem 1rem; border-radius: 10px; border: 1px solid #eee; background: white;
            font-size: 0.75rem; font-weight: 800; cursor: pointer; transition: var(--transition);
        }
        .quality-btn.active { background: var(--primary); color: white; border-color: var(--primary); }
        
        .rate-input {
            width: 130px; 
            padding: 10px 16px; 
            border: 2px solid #eef2f1; 
            border-radius: 12px;
            font-family: 'JetBrains Mono'; 
            font-size: 1.1rem; 
            font-weight: 800; 
            text-align: right;
            transition: var(--transition);
            background: #f8fafc;
            color: var(--primary);
        }
        .rate-input:focus {
            border-color: var(--primary);
            background: white;
            outline: none;
            box-shadow: 0 4px 12px rgba(26, 46, 35, 0.08);
        }
        .rate-input-label {
            font-size: 0.6rem;
            font-weight: 800;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-right: 8px;
        }
        /* Approvals Stage Specific Styles */
        .approvals-workspace {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 2rem;
            align-items: start;
        }

        .approval-card {
            background: white;
            border-radius: 20px;
            border: 1px solid #eef2f1;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: var(--transition);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15), 0 0 1px rgba(0, 0, 0, 0.05);
        }

        .approval-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }

        .approval-card h4 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .status-pill {
            padding: 0.4rem 0.8rem;
            border-radius: 100px;
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-not-submitted { background: #f1f5f9; color: #64748b; }
        .status-submitted { background: #e0f2fe; color: #0369a1; }
        .status-under-review { background: #fef3c7; color: #b45309; }
        .status-approved { background: #dcfce7; color: #15803d; }
        .status-rejected { background: #fee2e2; color: #b91c1c; }

        .approval-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 12px;
            margin-bottom: 0.75rem;
        }

        .approval-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .approval-title {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-main);
        }

        .approval-meta {
            font-size: 0.7rem;
            color: var(--text-muted);
        }

        .checklist-dashboard {
            background: white;
            border-radius: 24px;
            padding: 2rem;
            border: 1px solid var(--glass-border);
        }

        .dashboard-stat {
            text-align: center;
            padding: 1rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
        }

        .stat-label {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .indicator-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }

        .dot-green { background: #22c55e; }
        .dot-yellow { background: #f59e0b; }
        .dot-red { background: #ef4444; }

        .legal-indicator {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 800;
            color: #166534;
        }

        .read-only-ref {
            background: #f1f5f9;
            border: 1px dashed #cbd5e1;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.8rem;
        }
        textarea.c-input {
            resize: none;
            line-height: 1.6;
            padding: 1rem !important;
        }

        /* Premium Scrollbar for Textareas */
        textarea.c-input::-webkit-scrollbar {
            width: 6px;
        }
        textarea.c-input::-webkit-scrollbar-track {
            background: transparent;
        }
        textarea.c-input::-webkit-scrollbar-thumb {
            background: rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        textarea.c-input::-webkit-scrollbar-thumb:hover {
            background: rgba(0,0,0,0.2);
        }
        /* Stage 6: Construction Execution */
        .execution-workspace {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 2rem;
            align-items: start;
        }

        .phase-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            border: 1px solid #eef2f6;
            transition: var(--transition);
            margin-bottom: 1rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15), 0 0 1px rgba(0, 0, 0, 0.05);
        }

        .phase-card:hover {
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border-color: var(--accent);
        }

        .phase-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .phase-title {
            font-family: 'Outfit';
            font-weight: 800;
            color: var(--primary);
            font-size: 1.1rem;
        }

        .work-log-entry {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1rem;
            border-left: 4px solid var(--accent);
        }

        .variance-alert {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 4px 8px;
            border-radius: 6px;
            margin-top: 4px;
        }

        .variance-critical { background: #fee2e2; color: #dc2626; }
        .variance-ok { background: #f0fdf4; color: #16a34a; }

        .timeline-container {
            background: white;
            border-radius: 24px;
            padding: 2rem;
            border: 1px solid #eef2f6;
            position: sticky;
            top: 2rem;
        }

        .gantt-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.2rem;
        }

        .gantt-label {
            font-size: 0.75rem;
            font-weight: 700;
            width: 100px;
            color: var(--text-muted);
        }

        .gantt-bar-bg {
            flex: 1;
            height: 12px;
            background: #f1f5f9;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }

        .gantt-bar-fill {
            height: 100%;
            background: var(--primary);
            transition: width 1s cubic-bezier(0.23, 1, 0.32, 1);
        }

        .issue-card {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1rem;
        }

        .step-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }

        .step-completed { background: #22c55e; }
        .step-progress { background: #eab308; }
        .step-pending { background: #e2e8f0; }
        /* Stage 9: Handover & Closure */
        .handover-workspace {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .checklist-item {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #fff;
            padding: 1rem;
            border-radius: 12px;
            border: 1px solid #f1f5f9;
            margin-bottom: 0.8rem;
            transition: var(--transition);
        }

        .checklist-item:hover {
            border-color: var(--accent);
            background: #fffbeb;
        }

        .checklist-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--accent);
            cursor: pointer;
        }

        .settlement-card {
            background: #f8fafc;
            border-radius: 20px;
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
        }

        .settlement-row {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0;
            border-bottom: 1px solid #eef2f6;
            font-size: 0.9rem;
        }

        .settlement-row:last-child {
            border-bottom: none;
            padding-top: 1.2rem;
            font-weight: 800;
            font-size: 1.1rem;
            color: var(--primary);
        }

        .status-badge-possession {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .badge-ready { background: #dcfce7; color: #166534; }
        .badge-pending { background: #fef9e7; color: #854d0e; }

        .closure-banner {
            background: var(--primary);
            color: white;
            padding: 2rem;
            border-radius: 24px;
            text-align: center;
            margin-top: 2rem;
            position: relative;
            overflow: hidden;
        }

        .closure-banner i {
            font-size: 4rem;
            opacity: 0.1;
            position: absolute;
            right: -10px;
            bottom: -10px;
        }

        /* === PROJECT CLOSURE MODAL SYSTEM === */
        .closure-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(10px);
            z-index: 10000;
            display: none;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }

        .closure-modal-overlay.active {
            display: flex;
        }

        .closure-modal {
            background: white;
            border-radius: 24px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.4s cubic-bezier(0.23, 1, 0.32, 1);
            position: relative;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .closure-modal-header {
            padding: 2.5rem 2.5rem 1.5rem;
            border-bottom: 2px solid #f1f5f9;
            text-align: center;
        }

        .closure-modal-header h2 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .closure-modal-header p {
            font-size: 0.9rem;
            color: var(--text-muted);
            line-height: 1.6;
        }

        .closure-modal-body {
            padding: 2rem 2.5rem;
        }

        .closure-modal-footer {
            padding: 1.5rem 2.5rem 2.5rem;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .closure-step-indicator {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }

        .closure-step-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #e2e8f0;
            transition: all 0.3s;
        }

        .closure-step-dot.active {
            background: var(--primary);
            width: 30px;
            border-radius: 5px;
        }

        .closure-step-dot.completed {
            background: #22c55e;
        }

        .closure-checklist {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin: 1.5rem 0;
        }

        .closure-checklist-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.25rem;
            background: #f8fafc;
            border-radius: 12px;
            border: 2px solid transparent;
            transition: all 0.3s;
            cursor: pointer;
        }

        .closure-checklist-item:hover {
            background: #f1f5f9;
        }

        .closure-checklist-item.checked {
            background: #f0fdf4;
            border-color: #22c55e;
        }

        .closure-checklist-item input[type="checkbox"] {
            width: 24px;
            height: 24px;
            cursor: pointer;
            accent-color: #22c55e;
        }

        .closure-checklist-item label {
            flex: 1;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-main);
            cursor: pointer;
            line-height: 1.5;
        }

        .closure-checklist-item small {
            display: block;
            font-size: 0.8rem;
            color: var(--text-muted);
            font-weight: 400;
            margin-top: 0.25rem;
        }

        .closure-warning-box {
            background: #fff7ed;
            border: 2px solid #fb923c;
            border-radius: 12px;
            padding: 1.25rem;
            margin: 1.5rem 0;
            display: flex;
            gap: 1rem;
            align-items: flex-start;
        }

        .closure-warning-box i {
            color: #ea580c;
            font-size: 1.5rem;
        }

        .closure-warning-box div {
            flex: 1;
        }

        .closure-warning-box h4 {
            font-size: 0.95rem;
            font-weight: 700;
            color: #9a3412;
            margin-bottom: 0.5rem;
        }

        .closure-warning-box p {
            font-size: 0.85rem;
            color: #9a3412;
            line-height: 1.5;
        }

        .closure-verification-input {
            margin: 1.5rem 0;
        }

        .closure-verification-input label {
            display: block;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .closure-verification-input input {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            font-family: 'JetBrains Mono', monospace;
            transition: all 0.3s;
        }

        .closure-verification-input input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(26, 46, 35, 0.1);
        }

        .closure-verification-input input.valid {
            border-color: #22c55e;
            background: #f0fdf4;
        }

        .closure-btn {
            padding: 1rem 2rem;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .closure-btn-primary {
            background: var(--primary);
            color: white;
        }

        .closure-btn-primary:hover:not(:disabled) {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(26, 46, 35, 0.2);
        }

        .closure-btn-primary:disabled {
            background: #cbd5e1;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .closure-btn-secondary {
            background: #f1f5f9;
            color: var(--text-main);
        }

        .closure-btn-secondary:hover {
            background: #e2e8f0;
        }

        .closure-btn-danger {
            background: #ef4444;
            color: white;
        }

        .closure-btn-danger:hover:not(:disabled) {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(239, 68, 68, 0.3);
        }

        .hold-to-confirm-btn {
            position: relative;
            overflow: hidden;
            background: #ef4444;
            color: white;
            padding: 1.25rem 2.5rem;
            font-size: 1.1rem;
            font-weight: 800;
            border-radius: 16px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            user-select: none;
        }

        .hold-to-confirm-btn .hold-progress {
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            background: #dc2626;
            width: 0%;
            transition: width 0.1s linear;
            z-index: 0;
        }

        .hold-to-confirm-btn span {
            position: relative;
            z-index: 1;
        }

        .hold-to-confirm-btn:active .hold-progress {
            animation: holdProgress 2.5s linear forwards;
        }

        @keyframes holdProgress {
            from { width: 0%; }
            to { width: 100%; }
        }

        .closure-success-animation {
            text-align: center;
            padding: 3rem 2rem;
        }

        .closure-success-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: #22c55e;
            margin: 0 auto 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: successPop 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .closure-success-icon i {
            color: white;
            font-size: 3rem;
        }

        @keyframes successPop {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); opacity: 1; }
        }

        .closure-success-animation h3 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .closure-success-animation p {
            font-size: 1rem;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .closure-action-cards {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 2rem;
        }

        .closure-action-card {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .closure-action-card:hover {
            background: white;
            border-color: var(--primary);
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .closure-action-card i {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .closure-action-card h4 {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text-main);
        }
    </style>
</head>
<body>

    <!-- Premium Entry Transition -->
    <div id="entry-transition">
        <canvas id="transition-canvas"></canvas>
        <div class="transition-content">
            <div class="loader-ring"></div>
            <div style="margin-top: 0.5rem;">
                <h2 style="font-weight: 800; letter-spacing: -1px; font-size: 2.2rem;">SECURE STUDIO LINK</h2>
                <p style="opacity: 0.5; font-size: 0.85rem; font-weight: 500;">SYNCHRONIZING PROJECT VAULT & 3D ASSETS</p>
            </div>
            <div class="transition-status">INITIALIZING...</div>
        </div>
    </div>

    <div id="canvas-container"></div>

    <nav>
        <a href="engineer.php" class="nav-logo">
            <i class="far fa-building"></i> Constructa
        </a>
        <div class="nav-links">
            <div style="text-align: right; margin-right: 1.5rem;">
                <div style="font-size: 0.9rem; font-weight: 800;"><?php echo htmlspecialchars($project['project_title']); ?></div>
                <div style="font-size: 0.7rem; color: var(--accent); font-weight: 800;">PHASE: <?php echo strtoupper($current_stage['label']); ?></div>
            </div>
            <a href="my_projects.php" class="nav-btn">
                <i class="fas fa-arrow-left"></i> BACK TO PROJECTS
            </a>
        </div>
    </nav>

    <div class="status-header">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <span style="font-size: 0.8rem; font-weight: 800; color: var(--text-muted);">MILESTONE PROGRESS</span>
            <div class="bar-container">
                <div class="bar-fill" id="progress-bar"></div>
            </div>
            <span id="progress-text" style="font-size: 1rem; font-weight: 800; color: var(--primary);">0%</span>
        </div>
        <div style="font-size: 0.8rem; font-weight: 800; color: #4ade80;">
            <i class="fas fa-circle-check"></i> SECURE STUDIO LINK ACTIVE
        </div>
    </div>

    <div class="workspace-layout">
        <!-- Sidebar Tracker -->
        <aside class="lifecycle-sidebar">
            <div class="sidebar-title">Lifecycle Stages</div>
            <?php foreach($stages as $idx => $stage): 
                $status = ($idx < $current_project_stage) ? "completed" : (($idx === $current_project_stage) ? "active" : "locked");
                $view_active = ($idx === $current_stage_idx) ? "view-active" : "";
            ?>
                <div class="stage-entry <?php echo $status; ?> <?php echo $view_active; ?>" onclick="switchStage('<?php echo $stage['id']; ?>', <?php echo $idx; ?>, this)">
                    <i class="fas <?php echo $stage['icon']; ?>"></i>
                    <span class="stage-label"><?php echo $stage['label']; ?></span>
                </div>
            <?php endforeach; ?>
        </aside>

        <!-- Main Workspace -->
        <main class="canvas-area">
            <div id="main-content">
                <div class="stage-header">
                    <p id="stage-meta" style="font-size: 0.75rem; font-weight: 800; color: var(--accent); text-transform: uppercase;">Stage <?php echo str_pad($current_stage_idx + 1, 2, "0", STR_PAD_LEFT); ?> / Active</p>
                    <h1 id="stage-title"><?php echo $current_stage['label']; ?></h1>
                    <p id="stage-desc"><?php echo $current_stage['desc']; ?></p>
                </div>

                <!-- Stage Content Containers -->
                <div id="stage-specific-content" style="margin-top: 2rem;">
                    <?php if ($current_stage_idx === 0): ?>
                        <!-- DATA GATHERING WIZARD -->
                        <div class="gathering-wizard">
                            <!-- Step 1: Site & Structure (Now the first step) -->
                            <div class="gathering-step active" id="gather-step-1">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <h3 style="font-family: 'Outfit'; font-size: 1.8rem; font-weight: 800; color: var(--primary);">Site & Structure Details</h3>
                                    <div style="background: var(--primary); color: white; padding: 0.5rem 1rem; border-radius: 100px; font-size: 0.7rem; font-weight: 800;">1 / 2</div>
                                </div>
                                <div class="input-grid">
                                    <div class="input-group">
                                        <label>Plot Area (Sq.Ft)</label>
                                        <div class="input-wrapper">
                                            <input type="number" class="c-input" oninput="updateGatherPreview('plot_area', this.value)" placeholder="e.g. 1200" min="100" max="1000000" value="<?php echo htmlspecialchars($project_details['gathering']['plot_area'] ?? ''); ?>">
                                        </div>
                                        <div class="validation-msg">Enter a valid area (100 - 1M Sq.Ft)</div>
                                    </div>
                                    <div class="input-group">
                                        <label>Soil Type</label>
                                        <div class="input-wrapper">
                                            <select class="c-input" onchange="updateGatherPreview('soil_type', this.value)">
                                                <option value="">Select Type</option>
                                                <option value="Hard Rock" <?php echo (($project_details['gathering']['soil_type'] ?? '') == 'Hard Rock') ? 'selected' : ''; ?>>Hard Rock</option>
                                                <option value="Soft Rock" <?php echo (($project_details['gathering']['soil_type'] ?? '') == 'Soft Rock') ? 'selected' : ''; ?>>Soft Rock</option>
                                                <option value="Loose Sand" <?php echo (($project_details['gathering']['soil_type'] ?? '') == 'Loose Sand') ? 'selected' : ''; ?>>Loose Sand</option>
                                                <option value="Silty Clay" <?php echo (($project_details['gathering']['soil_type'] ?? '') == 'Silty Clay') ? 'selected' : ''; ?>>Silty Clay</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="input-group">
                                        <label>Number of Floors</label>
                                        <div class="input-wrapper">
                                            <input type="number" class="c-input" oninput="updateGatherPreview('floors', this.value)" min="1" max="3" value="<?php echo htmlspecialchars($project_details['gathering']['floors'] ?? ''); ?>">
                                        </div>
                                        <div class="validation-msg">Architectural limit: 1 to 3 floors</div>
                                    </div>
                                    <div class="input-group">
                                        <label>Total Budget (₹)</label>
                                        <div class="input-wrapper">
                                            <input type="number" class="c-input" oninput="updateGatherPreview('budget', this.value)" placeholder="e.g. 5000000" min="500000" value="<?php echo htmlspecialchars($project_details['gathering']['budget'] ?? ''); ?>">
                                        </div>
                                        <div class="validation-msg">Minimum budget: ₹5,00,000</div>
                                    </div>
                                </div>
                                <div class="wizard-footer">
                                    <div></div>
                                    <button class="btn-action" style="width: auto; padding: 1rem 2rem; border-radius: 18px;" onclick="nextGatherStep(2)">NEXT STEP <i class="fas fa-arrow-right"></i></button>
                                </div>
                            </div>

                            <!-- Step 2: Requirements -->
                            <div class="gathering-step" id="gather-step-2">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <h3 style="font-family: 'Outfit'; font-size: 1.8rem; font-weight: 800; color: var(--primary);">Space Requirements</h3>
                                    <div style="background: var(--primary); color: white; padding: 0.5rem 1rem; border-radius: 100px; font-size: 0.7rem; font-weight: 800;">2 / 2</div>
                                </div>
                                <div class="input-grid">
                                    <div class="input-group">
                                        <label>BHK Count</label>
                                        <div class="input-wrapper">
                                            <select class="c-input" onchange="updateGatherPreview('bhk', this.value)">
                                                <?php $bhk = $project_details['gathering']['bhk'] ?? '3 BHK'; ?>
                                                <option value="1 BHK" <?php echo ($bhk == '1 BHK') ? 'selected' : ''; ?>>1 BHK</option>
                                                <option value="2 BHK" <?php echo ($bhk == '2 BHK') ? 'selected' : ''; ?>>2 BHK</option>
                                                <option value="3 BHK" <?php echo ($bhk == '3 BHK') ? 'selected' : ''; ?>>3 BHK</option>
                                                <option value="4 BHK" <?php echo ($bhk == '4 BHK') ? 'selected' : ''; ?>>4 BHK</option>
                                                <option value="5+ BHK" <?php echo ($bhk == '5+ BHK') ? 'selected' : ''; ?>>5+ BHK</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="input-group">
                                        <label>Kitchen Type</label>
                                        <div class="input-wrapper">
                                            <select class="c-input" onchange="updateGatherPreview('kitchen', this.value)">
                                                <?php $kt = $project_details['gathering']['kitchen'] ?? 'Standard'; ?>
                                                <option value="Standard" <?php echo ($kt == 'Standard') ? 'selected' : ''; ?>>Standard</option>
                                                <option value="Modular" <?php echo ($kt == 'Modular') ? 'selected' : ''; ?>>Modular</option>
                                                <option value="Open Kitchen" <?php echo ($kt == 'Open Kitchen') ? 'selected' : ''; ?>>Open Kitchen</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="input-group">
                                        <label>Site Location</label>
                                        <div class="input-wrapper">
                                            <input type="text" class="c-input" oninput="updateGatherPreview('location', this.value)" placeholder="e.g. Colombo, Sri Lanka" value="<?php echo htmlspecialchars($project_details['gathering']['location'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="input-group">
                                        <label>Target Timeline</label>
                                        <div class="input-wrapper">
                                            <input type="text" class="c-input" oninput="updateGatherPreview('timeline', this.value)" placeholder="e.g. 12 Months" value="<?php echo htmlspecialchars($project_details['gathering']['timeline'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="input-group" style="grid-column: span 2;">
                                        <label>Special Requirements</label>
                                        <div class="input-wrapper">
                                            <textarea class="c-input" oninput="updateGatherPreview('notes', this.value)" placeholder="Enter any additional homeowner notes or constraints..." style="min-height: 120px;"><?php echo htmlspecialchars($project_details['gathering']['notes'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="wizard-footer">
                                    <button class="btn-action" style="width: auto; padding: 1rem 2rem; border-radius: 18px; background: #f1f5f9; color: var(--primary);" onclick="nextGatherStep(1)">BACK</button>
                                    <button class="btn-action" style="width: auto; padding: 1rem 2rem; border-radius: 18px; background: var(--primary);" onclick="finishGathering()">FINALIZE & SYNC <i class="fas fa-check-circle"></i></button>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($current_stage_idx === 1): ?>
                        <!-- SITE INSPECTION & SURVEY WIZARD -->
                        <div class="gathering-wizard">
                            <!-- Step 0: Plot Dimensions -->
                            <div class="gathering-step active" id="survey-step-0">
                                <h3 style="font-family: 'Space Grotesk';">Plot Dimensions</h3>
                                <p style="color: var(--text-muted); font-size: 0.9rem;">Enter accurate site measurements for the plot sketch.</p>
                                <div class="input-grid">
                                    <div class="input-group">
                                        <label>Front Width (ft)</label>
                                        <div class="input-wrapper">
                                            <input type="number" class="c-input" oninput="updateSurveyPreview('f_width', this.value)" placeholder="Enter Width" min="5" value="<?php echo htmlspecialchars($project_details['survey']['f_width'] ?? ''); ?>">
                                        </div>
                                        <div class="validation-msg">Minimum 5 ft required</div>
                                    </div>
                                    <div class="input-group">
                                        <label>Rear Width (ft)</label>
                                        <div class="input-wrapper">
                                            <input type="number" class="c-input" oninput="updateSurveyPreview('r_width', this.value)" placeholder="Enter Width" min="5" value="<?php echo htmlspecialchars($project_details['survey']['r_width'] ?? ''); ?>">
                                        </div>
                                        <div class="validation-msg">Minimum 5 ft required</div>
                                    </div>
                                    <div class="input-group">
                                        <label>Left Depth (ft)</label>
                                        <div class="input-wrapper">
                                            <input type="number" class="c-input" oninput="updateSurveyPreview('l_depth', this.value)" placeholder="Enter Depth" min="5" value="<?php echo htmlspecialchars($project_details['survey']['l_depth'] ?? ''); ?>">
                                        </div>
                                        <div class="validation-msg">Minimum 5 ft required</div>
                                    </div>
                                    <div class="input-group">
                                        <label>Right Depth (ft)</label>
                                        <div class="input-wrapper">
                                            <input type="number" class="c-input" oninput="updateSurveyPreview('r_depth', this.value)" placeholder="Enter Depth" min="5" value="<?php echo htmlspecialchars($project_details['survey']['r_depth'] ?? ''); ?>">
                                        </div>
                                        <div class="validation-msg">Minimum 5 ft required</div>
                                    </div>
                                    <div class="input-group">
                                        <label>Total Site Area (sq.ft)</label>
                                        <div class="input-wrapper">
                                            <input type="number" class="c-input" id="survey-total-area" oninput="updateSurveyPreview('total_area', this.value)" placeholder="Enter Total Area" min="100" value="<?php echo htmlspecialchars($project_details['survey']['total_area'] ?? ''); ?>" style="background: #f0f7ff; font-weight: 700;">
                                        </div>
                                        <div class="validation-msg">Area must be at least 100 sq.ft</div>
                                    </div>
                                </div>
                                <div class="auto-calc" style="margin-top: 1rem; background: #eef2ff; border-color: #c7d2fe;">
                                    <div class="auto-calc-label" style="color: #4338ca;">Live Area Approximation</div>
                                    <div class="auto-calc-value" id="calc-site-area" style="color: #312e81;">0 sq.ft</div>
                                </div>
                                <div class="wizard-footer">
                                    <div></div>
                                    <button class="btn-action" style="width: auto; padding: 0.8rem 1.5rem;" onclick="nextSurveyStep(1)">ROAD ACCESS <i class="fas fa-arrow-right"></i></button>
                                </div>
                            </div>

                            <!-- Step 1: Road & Access -->
                            <div class="gathering-step" id="survey-step-1">
                                <h3 style="font-family: 'Space Grotesk';">Road & Access Verification</h3>
                                <div class="input-grid">
                                    <div class="input-group">
                                        <label>Road Width (ft)</label>
                                        <input type="number" class="c-input" oninput="updateSurveyPreview('road_width', this.value)" placeholder="e.g. 20">
                                    </div>
                                    <div class="input-group">
                                        <label>Road Type</label>
                                        <select class="c-input" onchange="updateSurveyPreview('road_type', this.value)">
                                            <option value="">Select Type</option>
                                            <option value="Asphalt">Asphalt (Tar)</option>
                                            <option value="Concrete">Concrete</option>
                                            <option value="Mud">Mud Road</option>
                                        </select>
                                    </div>
                                    <div class="input-group" style="grid-column: span 2;">
                                        <label>Access Constraints</label>
                                        <textarea class="c-input" style="height: 100px;" oninput="updateSurveyPreview('constraints', this.value)" placeholder="e.g. Sharp turning radius, slope at entry, narrow street..."></textarea>
                                    </div>
                                </div>
                                <div class="wizard-footer">
                                    <button class="btn-action" style="width: auto; padding: 0.8rem 1.5rem; background: #eee; color: #333;" onclick="nextSurveyStep(0)">BACK</button>
                                    <button class="btn-action" style="width: auto; padding: 0.8rem 1.5rem; background: var(--primary);" onclick="finishSurvey()">SAVE SURVEY DATA</button>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($current_stage_idx === 2): ?>
                        <!-- PLANNING & DESIGN WIZARD -->
                        <div class="planning-workspace" style="display: flex; flex-direction: column; gap: 2rem;">
                            <!-- Reference Panels (Read-Only) -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="tool-card" style="padding: 1.2rem; background: #f8fafc; border: 1px dashed #cbd5e1;">
                                    <h4 style="font-size: 0.75rem; color: var(--primary); opacity: 0.6; margin-bottom: 0.5rem;"><i class="fas fa-info-circle"></i> SITE REFERENCE (STAGE 01)</h4>
                                    <div style="font-size: 0.85rem; font-weight: 700;">
                                        Area: <?php echo $project_details['survey']['total_area'] ?? '0'; ?> sq.ft | 
                                        Front: <?php echo $project_details['survey']['f_width'] ?? '0'; ?>' | 
                                        Depth: <?php echo $project_details['survey']['l_depth'] ?? '0'; ?>'
                                    </div>
                                </div>
                                <div class="tool-card" style="padding: 1.2rem; background: #f8fafc; border: 1px dashed #cbd5e1;">
                                    <h4 style="font-size: 0.75rem; color: var(--primary); opacity: 0.6; margin-bottom: 0.5rem;"><i class="fas fa-bullseye"></i> TARGET SPECS (STAGE 00)</h4>
                                    <div style="font-size: 0.85rem; font-weight: 700;">
                                        <?php echo $project_details['gathering']['bhk'] ?? '3 BHK'; ?> | 
                                        <?php echo $project_details['gathering']['floors'] ?? '1'; ?> Floors | 
                                        Budget: ₹<?php echo number_format((float)($project_details['gathering']['budget'] ?? 0)); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="gathering-wizard" style="margin-top: 0;">
                                <!-- Step 0: Floor Planning -->
                                <div class="gathering-step active" id="plan-step-0">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <h3 style="font-family: 'Outfit'; font-weight: 800;">Floor-wise Room Planning</h3>
                                        <span id="floor-indicator" style="background: var(--primary); color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 800;">CURRENT: GROUND FLOOR</span>
                                    </div>
                                    <div style="display: flex; gap: 0.5rem; margin-top: 1rem; overflow-x: auto; padding-bottom: 0.5rem; scrollbar-width: none;">
                                        <?php 
                                        $f_count = (int)($project_details['gathering']['floors'] ?? 1);
                                        for($i=0; $i<$f_count; $i++): ?>
                                            <button class="nav-btn" style="white-space: nowrap; font-size: 0.65rem; padding: 6px 12px;" onclick="switchPlanFloor(<?php echo $i; ?>)">
                                                <?php echo ($i === 0) ? 'GROUND' : 'FLOOR '.$i; ?>
                                            </button>
                                        <?php endfor; ?>
                                    </div>

                                    <div class="input-grid" style="margin-top: 1.5rem;">
                                        <div class="input-group">
                                            <label>Bedrooms</label>
                                            <div style="display: flex; align-items: center; gap: 1rem;">
                                                <input type="range" min="0" max="6" value="1" class="c-slider" oninput="updatePlanning('bedrooms', this.value)">
                                                <span class="val-display">1</span>
                                            </div>
                                        </div>
                                        <div class="input-group">
                                            <label>Bathrooms</label>
                                            <div style="display: flex; align-items: center; gap: 1rem;">
                                                <input type="range" min="0" max="4" value="1" class="c-slider" oninput="updatePlanning('bathrooms', this.value)">
                                                <span class="val-display">1</span>
                                            </div>
                                        </div>
                                        <div class="input-group">
                                            <label>Kitchen Type</label>
                                            <select class="c-input" onchange="updatePlanning('kitchen_pos', this.value)">
                                                <option value="NE">North-East</option>
                                                <option value="SE">South-East (Agneya)</option>
                                                <option value="NW">North-West</option>
                                            </select>
                                        </div>
                                        <div class="input-group">
                                            <label>Staircase Core</label>
                                            <select class="c-input" onchange="updatePlanning('stairs', this.value)">
                                                <option value="Internal">Internal (Duplex)</option>
                                                <option value="External">External (Rental Spec)</option>
                                                <option value="Hidden">Rear Hidden</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="wizard-footer">
                                        <div></div>
                                        <button class="btn-action" style="width: auto; padding: 0.8rem 1.5rem;" onclick="nextPlanStep(1)">ELEVATION STYLE <i class="fas fa-arrow-right"></i></button>
                                    </div>
                                </div>

                                <!-- Step 1: Elevation & Style -->
                                <div class="gathering-step" id="plan-step-1">
                                    <h3 style="font-family: 'Outfit'; font-weight: 800;">Aesthetic & Elevation Style</h3>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-top: 1rem;">
                                        <div class="choice-card active" onclick="updateStyle('modern', this)">
                                            <i class="fas fa-cube"></i>
                                            <div class="choice-label">MODERN</div>
                                            <div style="font-size: 0.6rem; opacity: 0.6;">Boxy, Glass, Concrete</div>
                                        </div>
                                        <div class="choice-card" onclick="updateStyle('traditional', this)">
                                            <i class="fas fa-place-of-worship"></i>
                                            <div class="choice-label">TRADITIONAL</div>
                                            <div style="font-size: 0.6rem; opacity: 0.6;">Sloped Roofs, Pillars</div>
                                        </div>
                                        <div class="choice-card" onclick="updateStyle('minimal', this)">
                                            <i class="fas fa-leaf"></i>
                                            <div class="choice-label">MINIMALIST</div>
                                            <div style="font-size: 0.6rem; opacity: 0.6;">Simple Planes, Zen</div>
                                        </div>
                                    </div>

                                    <div class="input-grid" style="margin-top: 2rem;">
                                        <div class="input-group">
                                            <label>Floor Height (ft)</label>
                                            <input type="number" class="c-input" value="10.5" step="0.5" oninput="updatePlanning('floor_height', this.value)">
                                        </div>
                                        <div class="input-group">
                                            <label>Paraper Height (ft)</label>
                                            <input type="number" class="c-input" value="3.5" step="0.1" oninput="updatePlanning('parapet', this.value)">
                                        </div>
                                    </div>

                                    <div class="wizard-footer">
                                        <button class="btn-action" style="width: auto; padding: 0.8rem 1.5rem; background: #eee; color: #333;" onclick="nextPlanStep(0)">BACK</button>
                                        <button class="btn-action" style="width: auto; padding: 0.8rem 1.5rem;" onclick="nextPlanStep(2)">COMPLIANCE & FINISH <i class="fas fa-arrow-right"></i></button>
                                    </div>
                                </div>

                                <!-- Step 2: Compliance & Validation -->
                                <div class="gathering-step" id="plan-step-2">
                                    <h3 style="font-family: 'Outfit'; font-weight: 800;">Structural Compliance Checks</h3>
                                    
                                    <div style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem;">
                                        <div class="compliance-item" id="comp-setback" style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #f0fdf4; border-radius: 12px; border: 1px solid #bbf7d0;">
                                            <i class="fas fa-check-circle" style="color: #22c55e;"></i>
                                            <div style="flex: 1;">
                                                <div style="font-size: 0.85rem; font-weight: 800;">MUNICIPAL SETBACKS</div>
                                                <div style="font-size: 0.7rem; color: #166534;">Front: 5ft, Rear: 3ft - COMPLIANT</div>
                                            </div>
                                        </div>
                                        <div class="compliance-item" id="comp-fsi" style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #fffbeb; border-radius: 12px; border: 1px solid #fde68a;">
                                            <i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i>
                                            <div style="flex: 1;">
                                                <div style="font-size: 0.85rem; font-weight: 800;">FAR / FSI UTILIZATION</div>
                                                <div style="font-size: 0.7rem; color: #92400e;">Current: 1.85 / Max: 2.0 - APPROACHING LIMIT</div>
                                            </div>
                                            <div class="fsi-meter" style="width: 80px; height: 6px; background: #eee; border-radius: 3px; position: relative; overflow: hidden;">
                                                <div style="position: absolute; left: 0; top: 0; height: 100%; width: 85%; background: #f59e0b;"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="input-group" style="margin-top: 2rem;">
                                        <label>Engineer Technical Notes</label>
                                        <textarea class="c-input" oninput="updatePlanning('notes', this.value)" placeholder="Enter structural assumptions, column grid logic, or material preferences..." style="height: 120px;"></textarea>
                                    </div>

                                    <div class="wizard-footer">
                                        <button class="btn-action" style="width: auto; padding: 0.8rem 1.5rem; background: #eee; color: #333;" onclick="nextPlanStep(1)">BACK</button>
                                        <button class="btn-action" style="width: auto; padding: 0.8rem 1.5rem; background: var(--primary);" onclick="finishPlanning()">FINALIZE PLANNING DESIGN</button>
                                    </div>
                                </div>
                            </div>

                            <!-- 2D Floor Plan Live Canvas Overlay -->
                            <div class="doc-repo" style="background: white; border: 1px solid #e2e8f0; position: relative; overflow: hidden;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                    <h4 style="font-family: 'Space Grotesk'; color: var(--primary);"><i class="fas fa-draw-polygon"></i> 2D SPATIAL PREVIEW</h4>
                                </div>
                                <div id="spatial-canvas-container" style="width: 100%; height: 300px; background: #f8fafc; border-radius: 12px; display: flex; align-items: center; justify-content: center; position: relative;">
                                    <svg id="spatial-svg" width="100%" height="100%" viewBox="-100 -100 200 200">
                                        <rect id="spatial-plot" x="-80" y="-80" width="160" height="160" fill="none" stroke="#cbd5e1" stroke-dasharray="4" />
                                        <path id="spatial-build" d="M -60 -60 L 60 -60 L 60 60 L -60 60 Z" fill="rgba(26, 46, 35, 0.05)" stroke="var(--primary)" stroke-width="2" />
                                        <!-- Room Grid Overlay -->
                                        <g id="spatial-rooms"></g>
                                    </svg>
                                    <div style="position: absolute; top: 10px; left: 10px; font-size: 0.6rem; color: #94a3b8; font-family: 'JetBrains Mono';">UNIT SCALE: 1:100 (Metric Ref)</div>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($current_stage_idx === 4): ?>
                        <!-- APPROVAL & PERMISSIONS WORKSPACE -->
                        <div class="approvals-workspace">
                            
                            <!-- Left Panel: Input & Checklist -->
                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                
                                <!-- Read-Only Reference Data -->
                                <div class="read-only-ref">
                                    <h5 style="margin-bottom: 0.5rem; color: var(--primary); font-weight: 800;"><i class="fas fa-info-circle"></i> PROJECT REFERENCE DATA</h5>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; font-size: 0.75rem;">
                                        <span><strong>Design Style:</strong> <?php echo ucfirst($project_details['planning']['elevation_style'] ?? 'Not Set'); ?></span>
                                        <span><strong>Total Area:</strong> <?php echo $project_details['survey']['total_area'] ?? '0'; ?> Sq.Ft</span>
                                        <span><strong>Floors:</strong> <?php echo $project_details['gathering']['floors'] ?? '0'; ?></span>
                                        <span><strong>Estimated Cost:</strong> ₹<?php echo number_format($project_details['estimation']['total_cost'] ?? 0); ?></span>
                                    </div>
                                </div>

                                <!-- 1. Plan Approval -->
                                <div class="approval-card">
                                    <h4><i class="fas fa-map"></i> 1. Plan Approval (Local Authority)</h4>
                                    <div class="input-group" style="margin-bottom: 1rem;">
                                        <label>Approval Status</label>
                                        <select class="c-input" onchange="updateApprovalStatus('plan_approval', this.value)">
                                            <option value="not_submitted">Not Submitted</option>
                                            <option value="submitted">Submitted</option>
                                            <option value="under_review">Under Review</option>
                                            <option value="approved">Approved</option>
                                            <option value="rejected">Rejected</option>
                                        </select>
                                    </div>
                                    <div class="input-group">
                                        <label>Authority Remarks <span style="font-size:0.75rem; color:#64748b;">(Max: 500 characters)</span></label>
                                        <textarea id="plan_remarks" class="c-input" style="height: 80px;" placeholder="Enter any conditions or remarks from the authority..." maxlength="500" oninput="validateTextArea(this, 500, 'plan_remarks_counter')"></textarea>
                                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:0.5rem;">
                                            <div id="plan_remarks_error" style="display:none; color:#ef4444; font-size:0.75rem; font-weight:600;">
                                                <i class="fas fa-exclamation-circle"></i> <span></span>
                                            </div>
                                            <div id="plan_remarks_counter" style="font-size:0.75rem; color:#64748b; margin-left:auto;">0 / 500</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 2. Structural Safety Certification -->
                                <div class="approval-card">
                                    <h4><i class="fas fa-shield-alt"></i> 2. Structural Safety Certification</h4>
                                    <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                                        <div class="input-group" style="flex: 1;">
                                            <label>Structural Status</label>
                                            <select class="c-input" onchange="updateApprovalStatus('structural_safety', this.value)">
                                                <option value="pending">Pending</option>
                                                <option value="approved">Approved / Certified</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="input-group">
                                        <label>Licensed Engineer ID</label>
                                        <?php 
                                            $username = $_SESSION['username'] ?? 'ENG';
                                            $prefix = strtoupper(substr($username, 0, 3));
                                            $engineerId = "LIC-ENG-2024-" . $prefix;
                                        ?>
                                        <input type="text" class="c-input" placeholder="e.g. LIC-ENG-2024-XXXX" value="<?php echo htmlspecialchars($engineerId); ?>" readonly style="background-color: #f8fafc; cursor: not-allowed;" oninput="updateApprovalData('engineer_license', this.value)">
                                        <small style="color: #64748b; font-size: 0.75rem; margin-top: 0.25rem; display: block;">
                                            <i class="fas fa-info-circle"></i> Auto-generated from username
                                        </small>
                                    </div>
                                </div>

                                <!-- 3. Land & Ownership Verification -->
                                <div class="approval-card">
                                    <h4><i class="fas fa-file-contract"></i> 3. Land & Ownership Verification</h4>
                                    <div style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; padding: 1rem; border-radius: 12px;">
                                        <span style="font-size: 0.85rem; font-weight: 600;">Verification Status</span>
                                        <div style="display: flex; gap: 10px;">
                                            <button class="quality-btn" onclick="updateApprovalStatus('land_verification', 'verified', this)">VERIFIED</button>
                                            <button class="quality-btn" onclick="updateApprovalStatus('land_verification', 'pending', this)">PENDING</button>
                                        </div>
                                    </div>
                                </div>

                                <!-- 4. Utility & Service Permissions -->
                                <div class="approval-card">
                                    <h4><i class="fas fa-faucet"></i> 4. Utility & Service Permissions</h4>
                                    <div class="approval-item">
                                        <div class="approval-info">
                                            <span class="approval-title">Water Connection</span>
                                            <span class="approval-meta">Municipal Supply</span>
                                        </div>
                                        <select class="c-input" style="width: auto; padding: 0.5rem;" onchange="updateApprovalStatus('utility_water', this.value)">
                                            <option value="pending">Pending</option>
                                            <option value="approved">Approved</option>
                                        </select>
                                    </div>
                                    <div class="approval-item">
                                        <div class="approval-info">
                                            <span class="approval-title">Electricity Connection</span>
                                            <span class="approval-meta">Standard Domestic Power</span>
                                        </div>
                                        <select class="c-input" style="width: auto; padding: 0.5rem;" onchange="updateApprovalStatus('utility_electricity', this.value)">
                                            <option value="pending">Pending</option>
                                            <option value="approved">Approved</option>
                                        </select>
                                    </div>
                                    <div class="approval-item">
                                        <div class="approval-info">
                                            <span class="approval-title">Sewer / Drainage</span>
                                            <span class="approval-meta">Sanitation Network</span>
                                        </div>
                                        <select class="c-input" style="width: auto; padding: 0.5rem;" onchange="updateApprovalStatus('utility_sewer', this.value)">
                                            <option value="pending">Pending</option>
                                            <option value="approved">Approved</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- 5. Special NOCs (Conditional) -->
                                <div class="approval-card" id="special-nocs-container">
                                    <h4><i class="fas fa-exclamation-circle"></i> 5. Special NOCs</h4>
                                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                            <input type="checkbox" onchange="toggleNOC('fire_noc', this.checked)"> Fire NOC
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                            <input type="checkbox" onchange="toggleNOC('airport_noc', this.checked)"> Airport Authority Clearance
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                            <input type="checkbox" onchange="toggleNOC('env_noc', this.checked)"> Environmental Clearance
                                        </label>
                                    </div>
                                </div>

                                <!-- 6. Fee & Receipt Tracking -->
                                <div class="approval-card">
                                    <h4><i class="fas fa-receipt"></i> 6. Fee & Receipt Tracking</h4>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                        <div class="input-group">
                                            <label>Total Approval Fee (₹)</label>
                                            <input type="number" class="c-input" placeholder="0" oninput="updateApprovalData('fee_amount', this.value)">
                                        </div>
                                        <div class="input-group">
                                            <label>Payment Status</label>
                                            <select class="c-input" onchange="updateApprovalStatus('payment_status', this.value)">
                                                <option value="unpaid">Unpaid</option>
                                                <option value="paid">Paid</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Panel: Status Dashboard -->
                            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                                
                                <div class="checklist-dashboard">
                                    <h4 style="margin-bottom: 2rem; border-bottom: 1px solid #eee; padding-bottom: 1rem;"><i class="fas fa-chart-pie"></i> APPROVAL SUMMARY</h4>
                                    
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 2rem;">
                                        <div class="dashboard-stat" style="background: #f0fdf4; border-radius: 16px;">
                                            <div class="stat-value" id="stats-completed">0</div>
                                            <div class="stat-label">Completed</div>
                                        </div>
                                        <div class="dashboard-stat" style="background: #fffbeb; border-radius: 16px;">
                                            <div class="stat-value" id="stats-pending">6</div>
                                            <div class="stat-label">Pending</div>
                                        </div>
                                    </div>

                                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <span style="font-size: 0.8rem; font-weight: 600;"><i class="indicator-dot dot-green"></i> Plan Approval</span>
                                            <span id="status-plan-approval" class="status-pill status-not-submitted">Not Submitted</span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <span style="font-size: 0.8rem; font-weight: 600;"><i class="indicator-dot dot-yellow"></i> Structural Safety</span>
                                            <span id="status-structural-safety" class="status-pill status-not-submitted">Pending</span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <span style="font-size: 0.8rem; font-weight: 600;"><i class="indicator-dot dot-red"></i> Land Verification</span>
                                            <span id="status-land-verification" class="status-pill status-not-submitted">Pending</span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <span style="font-size: 0.8rem; font-weight: 600;"><i class="indicator-dot dot-yellow"></i> Utilities</span>
                                            <span id="status-utilities" class="status-pill status-not-submitted">0 / 3 Done</span>
                                        </div>
                                    </div>

                                    <div style="margin-top: 2rem; padding: 1.5rem; background: var(--primary); border-radius: 20px; color: white;">
                                        <div style="font-size: 0.7rem; opacity: 0.7; text-transform: uppercase; letter-spacing: 1px;">Overall Progress</div>
                                        <div style="font-size: 2rem; font-weight: 800; margin: 0.5rem 0;" id="approval-progress-pct">0%</div>
                                        <div class="bar-container" style="width: 100%; height: 6px; background: rgba(255,255,255,0.1);">
                                            <div class="bar-fill" id="approval-progress-fill" style="width: 0%; background: #22c55e;"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Engineer Confirmation -->
                                <div class="tool-card" style="border: 2px solid var(--accent);">
                                    <h4 style="font-size: 0.9rem; color: var(--primary); margin-bottom: 1rem;">ENGINEER CONFIRMATION</h4>
                                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 1.5rem;">I hereby confirm that all legal approvals and clearances listed above have been verified and documentation is stored in the vault.</p>
                                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; background: #fffbeb; padding: 1rem; border-radius: 12px; border: 1px solid #fde68a;">
                                        <input type="checkbox" id="engineer-legal-confirm" onchange="updateConfirm()">
                                        <span style="font-size: 0.8rem; font-weight: 700; color: #92400e;">CONFIRM LEGAL READINESS</span>
                                    </label>
                                </div>

                                <button class="btn-action" style="background: white; border: 1px solid #ddd; color: var(--primary);" onclick="downloadApprovalReport()">
                                    <i class="fas fa-file-pdf"></i> DOWNLOAD APPROVAL REPORT
                                </button>
                            </div>
                        </div>
                    <?php elseif ($current_stage_idx === 3): ?>
                        <!-- STAGE 04: COST ESTIMATION & BOQ WORKSPACE -->
                        <div class="estimation-workspace" style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 2rem; align-items: start;">
                            
                            <!-- Left: Pricing Breakdown -->
                            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                                <div class="tool-card" style="padding: 1.5rem; position: relative; overflow: hidden;">
                                    <div class="form-3d-icon"><i class="fas fa-coins"></i></div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                                        <div>
                                            <h4 style="font-size: 1.1rem; font-weight: 800; color: var(--primary);">Cost Breakdown & Rates</h4>
                                            <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">Customize material and labor rates for specific project phases.</p>
                                        </div>
                                        <div style="display: flex; gap: 8px;">
                                            <button class="quality-btn <?php echo ($project_details['estimation']['quality'] ?? 'basic') === 'basic' ? 'active' : ''; ?>" onclick="updateQuality('basic', this)">BASIC</button>
                                            <button class="quality-btn <?php echo ($project_details['estimation']['quality'] ?? 'standard') === 'standard' ? 'active' : ''; ?>" onclick="updateQuality('standard', this)">STANDARD</button>
                                            <button class="quality-btn <?php echo ($project_details['estimation']['quality'] ?? 'premium') === 'premium' ? 'active' : ''; ?>" onclick="updateQuality('premium', this)">PREMIUM</button>
                                        </div>
                                    </div>

                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                        <!-- Cost Cards -->
                                        <div class="cost-card">
                                            <div class="cost-header">
                                                <div class="cost-title">Excavation & Foundation</div>
                                                <i class="fas fa-shovel" style="color: #0d9488; opacity: 0.3;"></i>
                                            </div>
                                            <div class="cost-value" id="cost-foundation">₹0</div>
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #f8fafc;">
                                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                                    <span class="rate-input-label">UNIT RATE (₹)</span>
                                                    <input type="number" class="rate-input" value="0" oninput="updateRate('foundation', this.value)">
                                                </div>
                                                <div style="text-align: right;">
                                                    <div class="rate-input-label" style="margin-right:0;">QUANTITY</div>
                                                    <span class="cost-qty" id="qty-foundation">0 Cu.Ft</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="cost-card">
                                            <div class="cost-header">
                                                <div class="cost-title">RCC Structure</div>
                                                <i class="fas fa-cubes" style="color: #4338ca; opacity: 0.3;"></i>
                                            </div>
                                            <div class="cost-value" id="cost-rcc">₹0</div>
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #f8fafc;">
                                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                                    <span class="rate-input-label">UNIT RATE (₹)</span>
                                                    <input type="number" class="rate-input" value="0" oninput="updateRate('rcc', this.value)">
                                                </div>
                                                <div style="text-align: right;">
                                                    <div class="rate-input-label" style="margin-right:0;">QUANTITY</div>
                                                    <span class="cost-qty" id="qty-rcc">0 Sq.Ft</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="cost-card">
                                            <div class="cost-header">
                                                <div class="cost-title">Masonry (Walls)</div>
                                                <i class="fas fa-th-large" style="color: #b45309; opacity: 0.3;"></i>
                                            </div>
                                            <div class="cost-value" id="cost-masonry">₹0</div>
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #f8fafc;">
                                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                                    <span class="rate-input-label">UNIT RATE (₹)</span>
                                                    <input type="number" class="rate-input" value="0" oninput="updateRate('masonry', this.value)">
                                                </div>
                                                <div style="text-align: right;">
                                                    <div class="rate-input-label" style="margin-right:0;">QUANTITY</div>
                                                    <span class="cost-qty" id="qty-masonry">0 Sq.Ft</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="cost-card">
                                            <div class="cost-header">
                                                <div class="cost-title">Flooring & Tiles</div>
                                                <i class="fas fa-border-all" style="color: #0369a1; opacity: 0.3;"></i>
                                            </div>
                                            <div class="cost-value" id="cost-flooring">₹0</div>
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #f8fafc;">
                                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                                    <span class="rate-input-label">UNIT RATE (₹)</span>
                                                    <input type="number" class="rate-input" value="0" oninput="updateRate('flooring', this.value)">
                                                </div>
                                                <div style="text-align: right;">
                                                    <div class="rate-input-label" style="margin-right:0;">QUANTITY</div>
                                                    <span class="cost-qty" id="qty-flooring">0 Sq.Ft</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="cost-card">
                                            <div class="cost-header">
                                                <div class="cost-title">Electrical & Plumbing</div>
                                                <i class="fas fa-plug" style="color: #7c3aed; opacity: 0.3;"></i>
                                            </div>
                                            <div class="cost-value" id="cost-mep">₹0</div>
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #f8fafc;">
                                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                                    <span class="rate-input-label">UNIT RATE (₹)</span>
                                                    <input type="number" class="rate-input" value="0" oninput="updateRate('mep', this.value)">
                                                </div>
                                                <div style="text-align: right;">
                                                    <div class="rate-input-label" style="margin-right:0;">QUANTITY</div>
                                                    <span class="cost-qty" id="qty-mep">Lump Sum</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="cost-card">
                                            <div class="cost-header">
                                                <div class="cost-title">Painting & Finishes</div>
                                                <i class="fas fa-paint-roller" style="color: #be185d; opacity: 0.3;"></i>
                                            </div>
                                            <div class="cost-value" id="cost-finishes">₹0</div>
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #f8fafc;">
                                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                                    <span class="rate-input-label">UNIT RATE (₹)</span>
                                                    <input type="number" class="rate-input" value="0" oninput="updateRate('finishes', this.value)">
                                                </div>
                                                <div style="text-align: right;">
                                                    <div class="rate-input-label" style="margin-right:0;">QUANTITY</div>
                                                    <span class="cost-qty" id="qty-finishes">0 Sq.Ft</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Analysis & Summary -->
                            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                                <!-- Live Total Display -->
                                <div class="tool-card" style="padding: 2rem; background: var(--primary); color: white; border: none; position: relative; overflow: hidden;">
                                    <div style="position: absolute; right: -20px; top: -20px; font-size: 8rem; opacity: 0.05; transform: rotate(15deg);"><i class="fas fa-calculator"></i></div>
                                    <div style="font-size: 0.7rem; font-weight: 700; opacity: 0.7; text-transform: uppercase;">Estimated Total Cost</div>
                                    <div style="font-size: 2.8rem; font-weight: 800; font-family: 'Space Grotesk';" id="est-total-cost">₹0</div>
                                    
                                    <div style="margin-top: 1.5rem;">
                                        <div style="display: flex; justify-content: space-between; font-size: 0.7rem; font-weight: 700; margin-bottom: 0.5rem;">
                                            <span>COMPARED TO BUDGET (₹<?php echo number_format((float)($project_details['gathering']['budget'] ?? 0)); ?>)</span>
                                            <span id="budget-status-text">WITHIN LIMIT</span>
                                        </div>
                                        <div class="budget-meter">
                                            <div class="budget-fill" id="budget-fill-bar" style="width: 0%; background: #22c55e;"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Material Quantities Table -->
                                <div class="tool-card" style="padding: 1.5rem;">
                                    <h4 style="font-size: 0.85rem; display: flex; justify-content: space-between; align-items: center;">
                                        MATERIAL QUANTITIES 
                                        <span style="font-size: 0.65rem; color: var(--text-muted);">(BOQ PREVIEW)</span>
                                    </h4>
                                    <div style="max-height: 250px; overflow-y: auto; margin-top: 1rem;">
                                        <table class="estimation-table">
                                            <thead>
                                                <tr>
                                                    <th>Material</th>
                                                    <th>Quantity</th>
                                                    <th>Unit</th>
                                                </tr>
                                            </thead>
                                            <tbody id="material-boq-body">
                                                <!-- To be populated via JS -->
                                            </tbody>
                                        </table>
                                    </div>
                                    <button class="btn-action" style="margin-top: 1rem; background: #f8fafc; border: 1px solid #ddd; color: var(--primary); font-size: 0.7rem;" onclick="generateBOQ()">
                                        <i class="fas fa-file-pdf"></i> DOWNLOAD DETAILED BOQ (PDF)
                                    </button>
                                </div>

                                <!-- Optimization Box (Contextual) -->
                                <div id="opt-box" class="tool-card" style="padding: 1.2rem; background: #fff7ed; border: 1px solid #ffedd5; display: none;">
                                    <h4 style="font-size: 0.75rem; color: #9a3412;"><i class="fas fa-lightbulb"></i> COST OPTIMIZATION SUGGESTED</h4>
                                    <ul style="font-size: 0.7rem; color: #9a3412; margin-top: 0.5rem; padding-left: 1rem; line-height: 1.4;">
                                        <li>Consider reduction in wall finish thickness.</li>
                                        <li>Opt for standard floor tile sizes to reduce wastage by 12%.</li>
                                        <li>Switch foundation to Plum Concrete if soil allows.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($current_stage_idx === 4): ?>
                        <!-- STAGE 05: APPROVALS & PERMISSIONS WORKSPACE -->
                        <div class="approvals-workspace">
                            
                            <!-- Left: Approval Tracking Cards -->
                            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                                
                                <div class="approval-card">
                                    <h4><i class="fas fa-drafting-dot"></i> Layout & Plan Approval</h4>
                                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 1.5rem;">Official municipal approval of architectural flow and built-up area.</p>
                                    
                                    <div class="input-grid" style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 1.5rem;">
                                        <div class="input-group">
                                            <label>Status</label>
                                            <select class="c-input" onchange="updateApprovalStatus('plan_approval', this.value, this)">
                                                <option value="not_submitted">Not Submitted</option>
                                                <option value="submitted">Submitted</option>
                                                <option value="under_review">Under Review</option>
                                                <option value="approved">Approved</option>
                                                <option value="rejected">Rejected</option>
                                            </select>
                                        </div>
                                        <div class="input-group">
                                            <label>Authority Remarks / File No. <span style="font-size:0.75rem; color:#64748b;">(Max: 100 characters)</span></label>
                                            <input type="text" id="plan_file_no" class="c-input" placeholder="e.g. BMC/2024/XP-908" maxlength="100" oninput="validateFileNo(this, 'plan_file_no_counter')">
                                            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:0.5rem;">
                                                <div id="plan_file_no_error" style="display:none; color:#ef4444; font-size:0.75rem; font-weight:600;">
                                                    <i class="fas fa-exclamation-circle"></i> <span></span>
                                                </div>
                                                <div id="plan_file_no_counter" style="font-size:0.75rem; color:#64748b; margin-left:auto;">0 / 100</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="approval-card">
                                    <h4><i class="fas fa-shield-alt"></i> Structural Safety & Land</h4>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                        <div class="input-group">
                                            <label>Structural Stability Audit</label>
                                            <select class="c-input" onchange="updateApprovalStatus('structural_safety', this.value, this)">
                                                <option value="pending">Pending Audit</option>
                                                <option value="verified">Verified & Signed</option>
                                                <option value="failed">Correction Required</option>
                                            </select>
                                        </div>
                                        <div class="input-group">
                                            <label>Clear Land Verification</label>
                                            <select class="c-input" onchange="updateApprovalStatus('land_verification', this.value, this)">
                                                <option value="pending">Unchecked</option>
                                                <option value="verified">Verified Clear Title</option>
                                                <option value="disputed">Title Issue</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="approval-card">
                                    <h4><i class="fas fa-faucet"></i> Utility Connection Permits</h4>
                                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                                        <div class="input-group">
                                            <label>Water (MNC)</label>
                                            <select class="c-input" onchange="updateApprovalStatus('utility_water', this.value, this)">
                                                <option value="pending">No</option>
                                                <option value="approved">Yes</option>
                                            </select>
                                        </div>
                                        <div class="input-group">
                                            <label>Electricity</label>
                                            <select class="c-input" onchange="updateApprovalStatus('utility_electricity', this.value, this)">
                                                <option value="pending">No</option>
                                                <option value="approved">Yes</option>
                                            </select>
                                        </div>
                                        <div class="input-group">
                                            <label>Sewer/Drain</label>
                                            <select class="c-input" onchange="updateApprovalStatus('utility_sewer', this.value, this)">
                                                <option value="pending">No</option>
                                                <option value="approved">Yes</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="approval-card">
                                    <h4><i class="fas fa-file-contract"></i> Special NOCs</h4>
                                    <div style="display: flex; gap: 1.5rem;">
                                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                            <input type="checkbox" onchange="toggleNOC('fire_noc', this.checked)"> <span style="font-size: 0.8rem; font-weight: 600;">Fire Dept.</span>
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                            <input type="checkbox" onchange="toggleNOC('airport_noc', this.checked)"> <span style="font-size: 0.8rem; font-weight: 600;">Airport Height</span>
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                            <input type="checkbox" onchange="toggleNOC('env_noc', this.checked)"> <span style="font-size: 0.8rem; font-weight: 600;">Environment</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Approval Summary Dashboard -->
                            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                                
                                <div class="checklist-dashboard">
                                    <h3 style="font-family: 'Space Grotesk'; font-weight: 800; margin-bottom: 1.5rem;">APPROVAL SUMMARY</h3>
                                    
                                    <div style="background: var(--bg-base); padding: 1.5rem; border-radius: 20px; text-align: center; margin-bottom: 2rem;">
                                        <div style="font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Overall Compliance</div>
                                        <div style="font-size: 3rem; font-weight: 800; color: var(--primary);" id="approval-progress-pct">0%</div>
                                        <div class="bar-container" style="width: 100%; margin-top: 1rem;">
                                            <div class="bar-fill" id="approval-progress-fill" style="width: 0%; background: var(--accent);"></div>
                                        </div>
                                    </div>

                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 2rem;">
                                        <div class="dashboard-stat" style="background: #f0fdf4; border-radius: 15px;">
                                            <div class="stat-value" id="stats-completed" style="color: #166534;">0</div>
                                            <div class="stat-label">Approved</div>
                                        </div>
                                        <div class="dashboard-stat" style="background: #fff7ed; border-radius: 15px;">
                                            <div class="stat-value" id="stats-pending" style="color: #9a3412;">6</div>
                                            <div class="stat-label">Pending</div>
                                        </div>
                                    </div>

                                    <div class="input-group">
                                        <label>Engineer License Tracking</label>
                                        <input type="text" class="c-input" placeholder="Registered Engineer ID" oninput="updateApprovalData('engineer_license', this.value)">
                                    </div>

                                    <hr style="margin: 2rem 0; border: none; border-top: 1px solid #eee;">

                                    <h4 style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1rem;">LIVE STATUS MONITOR</h4>
                                    <div style="display: flex; flex-direction: column; gap: 10px;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem;">
                                            <span>Muncipal Plan</span>
                                            <span class="status-pill status-not-submitted" id="status-plan-approval">NOT SUBMITTED</span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem;">
                                            <span>Structural Safety</span>
                                            <span class="status-pill status-not-submitted" id="status-structural-safety">PENDING</span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem;">
                                            <span>Utility Clearances</span>
                                            <span class="status-pill status-not-submitted" id="status-utilities">0 / 3 DONE</span>
                                        </div>
                                    </div>
                                    
                                    <label style="display: flex; align-items: center; gap: 12px; margin-top: 2rem; cursor: pointer; background: #f8fafc; padding: 1rem; border-radius: 12px;">
                                        <input type="checkbox" id="engineer-legal-confirm" onchange="updateConfirm()">
                                        <span style="font-size: 0.75rem; font-weight: 700;">Engineer Certifies Legal Documentation Compliance</span>
                                    </label>

                                    <button class="btn-action" style="margin-top: 1.5rem; background: white; border: 1px solid #ddd; color: var(--primary);" onclick="downloadApprovalReport()">
                                        <i class="fas fa-file-pdf"></i> GENERATE APPROVAL REPORT
                                    </button>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($current_stage_idx === 5): ?>
                        <!-- STAGE 06: CONSTRUCTION EXECUTION -->
                        <div class="execution-workspace">
                            
                            <!-- Left Panel: Controls & Input Cards -->
                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                
                                <!-- Read-Only Reference (Quick Glance) -->
                                <div class="read-only-ref" style="background: #f8fafc; border-color: #cbd5e1;">
                                    <h5 style="margin-bottom: 0.5rem; color: var(--primary); font-weight: 800;"><i class="fas fa-link"></i> LINKED PROJECT REFERENCE</h5>
                                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; font-size: 0.7rem;">
                                        <span><strong>Budget:</strong> ₹<?php echo number_format($project_details['estimation']['total_cost'] ?? 0); ?></span>
                                        <span><strong>Builtup:</strong> <?php echo $project_details['survey']['total_area'] ?? 0; ?> sq.ft</span>
                                        <span><strong>Design:</strong> <?php echo ucfirst($project_details['planning']['elevation_style'] ?? 'Modern'); ?></span>
                                    </div>
                                </div>

                                <!-- Phase Breakdown -->
                                <div id="execution-phases-container">
                                    <!-- Populated by JS -->
                                </div>

                                <!-- Material Consumption Tracker -->
                                <div class="phase-card">
                                    <h4 class="phase-title" style="margin-bottom: 1.5rem;"><i class="fas fa-truck-loading"></i> Material Usage Tracking</h4>
                                    <div style="overflow-x: auto;">
                                        <table style="width: 100%; font-size: 0.8rem; border-collapse: collapse;">
                                            <thead>
                                                <tr style="border-bottom: 2px solid #f1f5f9; text-align: left;">
                                                    <th style="padding: 0.8rem;">Material</th>
                                                    <th style="padding: 0.8rem;">Estimated (BOQ)</th>
                                                    <th style="padding: 0.8rem;">Actual Used</th>
                                                    <th style="padding: 0.8rem;">Variance</th>
                                                </tr>
                                            </thead>
                                            <tbody id="material-usage-body">
                                                <!-- Populated by JS -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Contractor Management -->
                                <div class="phase-card">
                                    <h4 class="phase-title"><i class="fas fa-user-hard-hat"></i> Site Management & Instructions</h4>
                                    <div class="input-grid" style="margin-top: 1.5rem;">
                                        <div class="input-group">
                                            <label>Primary Contractor</label>
                                            <input type="text" class="c-input" placeholder="Name or Firm" oninput="updateExecutionData('contractor_name', this.value)">
                                        </div>
                                        <div class="input-group">
                                            <label>Labor Compliance</label>
                                            <select class="c-input" onchange="updateExecutionData('labor_status', this.value)">
                                                <option value="compliant">Safety Compliant</option>
                                                <option value="review">Under Review</option>
                                                <option value="warning">Safety Warning Issued</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="input-group" style="margin-top: 1rem;">
                                        <label>Special Construction Instructions</label>
                                        <textarea class="c-input" style="height: 100px;" placeholder="Enter specific instructions for the site supervisor..." oninput="updateExecutionData('site_instructions', this.value)"></textarea>
                                    </div>
                                </div>

                                <!-- Issue & Delay Tracking -->
                                <div class="phase-card" style="border-left: 5px solid #ef4444;">
                                    <h4 class="phase-title"><i class="fas fa-exclamation-triangle"></i> Delay & Issue Log</h4>
                                    <div id="issue-list-container">
                                        <!-- Issues will be listed here -->
                                    </div>
                                    <button class="btn-action" style="background: #fef2f2; border: 1px solid #fecdd3; color: #ef4444; margin-top: 1rem; font-size: 0.7rem; width: auto; padding: 0.5rem 1rem;" onclick="addExecutionIssue()">
                                        <i class="fas fa-plus"></i> LOG NEW SITE ISSUE
                                    </button>
                                </div>
                            </div>

                            <!-- Right Panel: Live Dash & Timeline -->
                            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                                
                                <div class="timeline-container">
                                    <h4 style="margin-bottom: 2rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem; font-family: 'Space Grotesk';">CONSTRUCTION TIMELINE</h4>
                                    <div id="timeline-gantt">
                                        <!-- Gantt rows populated by JS -->
                                    </div>

                                    <div style="margin-top: 2.5rem; padding: 2rem; background: var(--primary); border-radius: 24px; color: white; position: relative; overflow: hidden;">
                                        <div style="position: absolute; right: -20px; top: -10px; font-size: 8rem; opacity: 0.05;"><i class="fas fa-hard-hat"></i></div>
                                        <div style="font-size: 0.7rem; font-weight: 700; opacity: 0.7; text-transform: uppercase; letter-spacing: 1px;">Overall Execution</div>
                                        <div style="font-size: 3rem; font-weight: 800; font-family: 'Space Grotesk';" id="overall-execution-pct">0%</div>
                                        <div class="bar-container" style="width: 100%; height: 8px; background: rgba(255,255,255,0.1); margin-top: 1rem;">
                                            <div class="bar-fill" id="overall-execution-fill" style="width: 0%; background: #4ade80;"></div>
                                        </div>
                                    </div>

                                    <div style="margin-top: 2rem;">
                                        <h4 style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1rem;">ENGINEER VERIFICATION</h4>
                                        <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; background: #f0fdf4; padding: 1rem; border-radius: 16px; border: 1px solid #bbf7d0;">
                                            <input type="checkbox" id="engineer-execution-confirm" onchange="updateExecutionData('confirmed', this.checked)">
                                            <span style="font-size: 0.8rem; font-weight: 800; color: #166534;">CERTIFY SITE WORK AS PER DRAWINGS</span>
                                        </label>
                                    </div>

                                    <button class="btn-action" style="margin-top: 2rem; background: white; border: 1px solid #ddd; color: var(--primary);" onclick="downloadExecutionReport()">
                                        <i class="fas fa-file-pdf"></i> DOWNLOAD EXECUTION SUMMARY
                                    </button>
                                </div>

                                <!-- Daily Log Shortcut -->
                                <div class="phase-card" style="background: #fdfcf6; border: 1px solid #f1eec5;">
                                    <h4 style="font-size: 0.85rem; color: #854d0e;"><i class="fas fa-clipboard-list"></i> Quick Work Log</h4>
                                    <p style="font-size: 0.7rem; color: #a16207; margin: 0.5rem 0 1rem;">Record today's site activity for the active phase.</p>
                                    <div class="input-group" style="margin-bottom: 0.8rem;">
                                        <select id="log-phase-select" class="c-input">
                                            <!-- Phases populated by JS -->
                                        </select>
                                    </div>
                                    <textarea id="quick-log-text" class="c-input" style="height: 60px; font-size: 0.75rem;" placeholder="e.g. Slabs casted for first floor, curing started..."></textarea>
                                    <button class="btn-action" style="margin-top: 0.8rem; background: #854d0e; font-size: 0.75rem;" onclick="submitQuickLog()">SUBMIT SITE LOG</button>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($current_stage_idx === 6): ?>
                        <!-- STAGE 07: PROJECT HANDOVER & CLOSURE -->
                        <div class="handover-workspace">
                            
                            <!-- Left: Walkthrough & Possession -->
                            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                                
                                <div class="phase-card">
                                    <h4 class="phase-title"><i class="fas fa-walking"></i> Final Walkthrough Checklist</h4>
                                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 1.5rem;">Verify site readiness with the homeowner.</p>
                                    
                                    <div class="checklist-item">
                                        <input type="checkbox" id="chk-work-done" onchange="updateHandoverData('check_completed', this.checked)">
                                        <label for="chk-work-done" style="font-size: 0.85rem; font-weight: 600; cursor: pointer;">All construction work finished as per plan</label>
                                    </div>
                                    <div class="checklist-item">
                                        <input type="checkbox" id="chk-defects-clear" onchange="updateHandoverData('check_defects', this.checked)">
                                        <label for="chk-defects-clear" style="font-size: 0.85rem; font-weight: 600; cursor: pointer;">Snag list / Minor defects resolved</label>
                                    </div>
                                    <div class="checklist-item">
                                        <input type="checkbox" id="chk-cleaned" onchange="updateHandoverData('check_cleaning', this.checked)">
                                        <label for="chk-cleaned" style="font-size: 0.85rem; font-weight: 600; cursor: pointer;">Debris removed & site professionally cleaned</label>
                                    </div>
                                    <div class="checklist-item">
                                        <input type="checkbox" id="chk-fixtures-ok" onchange="updateHandoverData('check_fixtures', this.checked)">
                                        <label for="chk-fixtures-ok" style="font-size: 0.85rem; font-weight: 600; cursor: pointer;">All fixtures, electricals & plumbing tested</label>
                                    </div>

                                    <div class="input-group" style="margin-top: 1.5rem;">
                                        <label>Engineer's Walkthrough Remarks</label>
                                        <textarea class="c-input" id="handover-remarks" style="height: 100px;" placeholder="Summarize the walkthrough findings..." oninput="updateHandoverData('walkthrough_notes', this.value)"></textarea>
                                    </div>
                                </div>

                                <!-- Key & Utilities -->
                                <div class="phase-card">
                                    <h4 class="phase-title"><i class="fas fa-key"></i> Possession & Utilities</h4>
                                    <div class="input-grid" style="margin-top: 1.5rem;">
                                        <div class="input-group">
                                            <label>Possession Date</label>
                                            <input type="date" class="c-input" id="possession-date" onchange="updateHandoverData('possession_date', this.value)">
                                        </div>
                                        <div class="input-group">
                                            <label>Utility Transfer Status</label>
                                            <select class="c-input" id="utility-status" onchange="updateHandoverData('utility_status', this.value)">
                                                <option value="pending">Transfer Pending</option>
                                                <option value="in_progress">In Progress</option>
                                                <option value="completed">All Utilities Handed Over</option>
                                            </select>
                                        </div>
                                    </div>
                                    <label style="display: flex; align-items: center; gap: 10px; margin-top: 1rem; cursor: pointer;">
                                        <input type="checkbox" id="chk-keys" onchange="updateHandoverData('keys_handed', this.checked)">
                                        <span style="font-size: 0.8rem; font-weight: 700;">Physical Keys and Access Cards Handed Over</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Right: Financial Settlement & Closure -->
                            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                                
                                <div class="settlement-card">
                                    <h4 style="font-family: 'Space Grotesk'; margin-bottom: 2rem;"><i class="fas fa-file-invoice-dollar"></i> FINAL COST SETTLEMENT</h4>
                                    
                                    <div class="settlement-row">
                                        <span>Initial Estimated Budget</span>
                                        <span style="font-weight: 700;">₹<?php echo number_format($project_details['estimation']['total_cost'] ?? 0); ?></span>
                                    </div>
                                    <div class="settlement-row">
                                        <span>Variations & Extra Work</span>
                                        <span style="color: #ef4444; font-weight: 700;">+ ₹<span id="settle-variations">0</span></span>
                                    </div>
                                    <div class="settlement-row" style="background: #f1f5f9; padding: 1rem; margin: 0.5rem -1.5rem; border-radius: 0;">
                                        <span>Actual Final Construction Cost</span>
                                        <span style="font-weight: 700;">₹<span id="settle-final">0</span></span>
                                    </div>
                                    <div class="settlement-row">
                                        <span>Payment Status</span>
                                        <span class="status-badge-possession badge-ready" id="settle-payment-badge">FULLY PAID</span>
                                    </div>
                                    <div class="settlement-row" style="margin-top: 1rem; padding-top: 1.5rem; border-top: 2px solid #e2e8f0;">
                                        <span>Outstanding Balance</span>
                                        <span style="color: #ef4444; font-weight: 800;" id="settle-balance">₹0</span>
                                    </div>

                                    <div class="input-group" style="margin-top: 1.5rem;">
                                        <label>Record Extra Work / Variation Cost (₹)</label>
                                        <input type="number" class="c-input" id="variation-input" placeholder="0" oninput="calculateSettlement(this.value)">
                                    </div>
                                </div>

                                <div class="phase-card" style="background: #f0fdf4; border-color: #bbf7d0;">
                                    <h4 style="font-size: 0.85rem; color: #166534;"><i class="fas fa-shield-check"></i> LIABILITY PERIOD</h4>
                                    <p style="font-size: 0.7rem; color: #15803d; margin: 0.5rem 0;">Defect Liability Period (DLP) starts from possession date.</p>
                                    <div class="input-group">
                                        <label style="color: #15803d;">DLP Duration (Months)</label>
                                        <input type="number" class="c-input" id="dlp-duration" value="12" oninput="updateHandoverData('dlp_months', this.value)">
                                    </div>
                                </div>

                                <div class="closure-banner">
                                    <i class="fas fa-award"></i>
                                    <h3 style="font-family: 'Space Grotesk'; font-weight: 800; margin-bottom: 0.5rem;">READY FOR CLOSURE?</h3>
                                    <p style="font-size: 0.75rem; opacity: 0.8; line-height: 1.4;">Project closure is permanent. Ensure all documents are uploaded and handover is legally certified.</p>
                                    <button class="btn-action" style="margin-top: 1.5rem; background: var(--accent); color: var(--primary); font-weight: 800; box-shadow: 0 10px 20px rgba(0,0,0,0.1);" onclick="finalizeProjectClosure()">
                                        <i class="fas fa-lock"></i> PERMANENTLY CLOSE PROJECT
                                    </button>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                        <!-- Standard Stage Tools (Placeholders for other stages) -->
                        <div class="workspace-grid">
                            <div class="tool-card">
                                <div class="tool-top">
                                    <div class="tool-icon"><i class="fas fa-flask"></i></div>
                                </div>
                                <h3>Stage Utilities</h3>
                                <p style="color: var(--text-muted); font-size: 0.85rem;">Access tools specific to the current construction phase.</p>
                                <div style="margin-top: 1rem; padding: 1rem; background: #f8fafc; border-radius: 12px; font-size: 0.8rem;">
                                    <i class="fas fa-info-circle"></i> No specific active tools for this stage.
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- GLOBAL STAGE REPOSITORY -->
                <div class="doc-repo" id="document-repository">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h4 style="font-family: 'Space Grotesk';"><i class="fas fa-folder-open"></i> STAGE DOCUMENT REPOSITORY</h4>
                        <span style="font-size: 0.7rem; font-weight: 800; color: var(--text-muted);"><?php echo strtoupper($current_stage['id']); ?>_DOCS_v1.0</span>
                    </div>
                    <div class="doc-list" id="stage-docs">
                        <!-- Documents will be loaded here via AJAX -->
                        <div style="text-align: center; padding: 1rem; color: #999; font-size: 0.8rem;">Loading documents...</div>
                    </div>
                    <input type="file" id="file-upload-input" style="display: none;" onchange="handleFileSelect(this)">
                    <div class="upload-zone" onclick="document.getElementById('file-upload-input').click()" id="upload-zone">
                        <i class="fas fa-cloud-arrow-up" style="font-size: 1.2rem; margin-bottom: 0.5rem;"></i>
                        <div style="font-weight: 700;">DRAG & DROP OR CLICK TO UPLOAD</div>
                        <div style="font-size: 0.7rem; opacity: 0.6; margin-top: 4px;">Supports PDF, CAD, JPG (Max 10MB)</div>
                    </div>
                </div>

                <div class="stage-footer-actions">
                    <button class="btn-action" style="width: auto; padding: 0.8rem 1.5rem; background: #f8fafc; border: 1px solid #ddd; color: var(--text-main);" onclick="saveDraft()">
                        <i class="fas fa-save"></i> SAVE DRAFT
                    </button>
                    <!-- Completion only allowed if viewing the current active project stage -->
                    <?php if ($current_stage_idx === $current_project_stage): ?>
                        <button class="btn-action" style="width: auto; padding: 0.8rem 1.5rem; background: var(--accent);" onclick="approveCurrentStage()">
                            <i class="fas fa-check-circle"></i> MARK STAGE COMPLETED
                        </button>
                    <?php else: ?>
                        <button class="btn-action" style="width: auto; padding: 0.8rem 1.5rem; background: #eee; color: #999; cursor: not-allowed;" disabled title="You can only complete the project's current active stage.">
                            <i class="fas fa-lock"></i> STAGE LOCKED
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div id="house-preview"></div>
        </main>

        <!-- Right Side Context -->
        <aside class="context-sidebar">
            <!-- Requirement Preview (Dynamically shown during Stage 0) -->
            <section id="gathering-preview" style="display: <?php echo ($current_stage_idx === 0) ? 'block' : 'none'; ?>;">
                <div class="context-title"><i class="fas fa-eye"></i> LIVE PREVIEW</div>
                <div style="background: white; padding: 1.2rem; border-radius: 15px; border: 1px solid #eee;">
                    <div id="preview-mode-tag" style="font-size: 0.65rem; font-weight: 800; color: var(--accent); margin-bottom: 1rem;">COLLECTION: IN-PERSON</div>
                    <div style="display: flex; flex-direction: column; gap: 0.8rem;" id="gather-live-values">
                        <div style="border-bottom: 1px solid #f1f1f1; padding-bottom: 0.5rem;">
                            <div style="font-size: 0.7rem; color: var(--text-muted); font-weight: 700;">PLOT AREA</div>
                            <div style="font-weight: 800;" id="val-plot_area">-</div>
                        </div>
                        <div style="border-bottom: 1px solid #f1f1f1; padding-bottom: 0.5rem;">
                            <div style="font-size: 0.7rem; color: var(--text-muted); font-weight: 700;">STRUCTURE</div>
                            <div style="font-weight: 800;" id="val-floors">1 Floor</div>
                        </div>
                        <div style="border-bottom: 1px solid #f1f1f1; padding-bottom: 0.5rem;">
                            <div style="font-size: 0.7rem; color: var(--text-muted); font-weight: 700;">ROOMS</div>
                            <div style="font-weight: 800;" id="val-bhk">3 BHK</div>
                        </div>
                        <div style="border-bottom: 1px solid #f1f1f1; padding-bottom: 0.5rem;">
                            <div style="font-size: 0.7rem; color: var(--text-muted); font-weight: 700;">BUDGET TARGET</div>
                            <div style="font-weight: 800;" id="val-budget">-</div>
                        </div>
                        <div style="border-bottom: 1px solid #f1f1f1; padding-bottom: 0.5rem;">
                            <div style="font-size: 0.7rem; color: var(--text-muted); font-weight: 700;">LOCATION</div>
                            <div style="font-weight: 800;" id="val-location">-</div>
                        </div>
                        <div style="border-bottom: 1px solid #f1f1f1; padding-bottom: 0.5rem;">
                            <div style="font-size: 0.7rem; color: var(--text-muted); font-weight: 700;">TIMELINE</div>
                            <div style="font-weight: 800;" id="val-timeline">-</div>
                        </div>
                    </div>
                    <button class="btn-action" style="margin-top: 1.5rem; font-size: 0.75rem; background: var(--primary);" onclick="downloadGatheredData()">
                        <i class="fas fa-download"></i> DOWNLOAD EXPORT
                    </button>
                </div>
            </section>

            <!-- Survey Preview (Dynamically shown during Stage 1) -->
            <section id="survey-preview" style="display: <?php echo ($current_stage_idx === 1) ? 'block' : 'none'; ?>;">
                <div class="context-title"><i class="fas fa-ruler-combined"></i> SURVEY INSIGHTS</div>
                <div style="background: white; padding: 1.2rem; border-radius: 15px; border: 1px solid #eee;">
                    <div style="margin-bottom: 1rem; padding: 0.5rem; background: #f0fdf4; border-radius: 8px; border: 1px solid #86efac; font-size: 0.7rem; color: #166534; font-weight: 700;">
                        <i class="fas fa-check-circle"></i> SITE ACCESSIBILITY: <span id="val-site-access">NORMAL</span>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 0.8rem;" id="survey-live-values">
                        <div style="border-bottom: 1px solid #f1f1f1; padding-bottom: 0.5rem;">
                            <div style="font-size: 0.7rem; color: var(--text-muted); font-weight: 700;">TOTAL SITE AREA</div>
                            <div style="font-weight: 800; color: var(--primary); font-size: 1.1rem;" id="val-site-area">0 sq.ft</div>
                        </div>
                        <div style="border-bottom: 1px solid #f1f1f1; padding-bottom: 0.5rem;">
                            <div style="font-size: 0.7rem; color: var(--text-muted); font-weight: 700;">ROAD WIDTH</div>
                            <div style="font-weight: 800;" id="val-road_width">-</div>
                        </div>
                        <div style="border-bottom: 1px solid #f1f1f1; padding-bottom: 0.5rem;">
                            <div style="font-size: 0.7rem; color: var(--text-muted); font-weight: 700;">ROAD TYPE</div>
                            <div style="font-weight: 800;" id="val-road_type">-</div>
                        </div>
                    </div>
            </section>
 
            <!-- Planning Insights (During Stage 2) -->
            <section id="planning-preview" style="display: <?php echo ($current_stage_idx === 2) ? 'block' : 'none'; ?>;">
                <div class="context-title"><i class="fas fa-layer-group"></i> PLANNING INSIGHTS</div>
                <div style="background: white; padding: 1.2rem; border-radius: 15px; border: 1px solid #eee;">
                    <div style="display: flex; flex-direction: column; gap: 0.8rem;">
                        <div style="border-bottom: 1px solid #f1f1f1; padding-bottom: 0.5rem; display: flex; justify-content: space-between;">
                            <div>
                                <div style="font-size: 0.65rem; color: var(--text-muted); font-weight: 700;">FLOOR HEIGHT</div>
                                <div style="font-weight: 800;" id="val-floor_height">10.5 ft</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 0.65rem; color: var(--text-muted); font-weight: 700;">STYLE</div>
                                <div style="font-weight: 800;" id="val-elevation_style">Modern</div>
                            </div>
                        </div>
                        <div style="border-bottom: 1px solid #f1f1f1; padding-bottom: 0.5rem;">
                            <div style="font-size: 0.65rem; color: var(--text-muted); font-weight: 700;">ROOM ALLOCATION</div>
                            <div style="font-weight: 800;" id="val-room_count">1 Bed, 1 Bath</div>
                        </div>
                        <div style="border-bottom: 1px solid #f1f1f1; padding-bottom: 0.5rem;">
                            <div style="font-size: 0.65rem; color: var(--text-muted); font-weight: 700;">STRUCTURAL ALIGNMENT</div>
                            <div style="font-weight: 800; color: #22c55e;" id="val-structural_status"><i class="fas fa-check-circle"></i> VIBRATION-STABLE</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Estimation Insights (During Stage 3) -->
            <section id="estimation-preview" style="display: <?php echo ($current_stage_idx === 3) ? 'block' : 'none'; ?>;">
                <div class="context-title"><i class="fas fa-coins"></i> ESTIMATION INSIGHTS</div>
                <div style="background: white; padding: 1.2rem; border-radius: 15px; border: 1px solid #eee;">
                    <div style="display: flex; flex-direction: column; gap: 0.8rem;">
                        <div style="border-bottom: 1px solid #f1f1f1; padding-bottom: 0.5rem;">
                            <div style="font-size: 0.65rem; color: var(--text-muted); font-weight: 700;">TOTAL ESTIMATED COST</div>
                            <div style="font-weight: 800; color: var(--accent); font-size: 1.2rem;" id="val-est-total">₹0</div>
                        </div>
                        <div style="border-bottom: 1px solid #f1f1f1; padding-bottom: 0.5rem; display: flex; justify-content: space-between;">
                            <div>
                                <div style="font-size: 0.65rem; color: var(--text-muted); font-weight: 700;">QUALITY</div>
                                <div style="font-weight: 800; text-transform: uppercase;" id="val-est-quality">Basic</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 0.65rem; color: var(--text-muted); font-weight: 700;">BUDGET SCORE</div>
                                <div style="font-weight: 800;" id="val-est-delta">100%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>


            <section>
                <div class="context-title"><i class="fas fa-history"></i> ACTIVITY FEED</div>
                <div style="font-size: 0.8rem; color: var(--text-muted); border-left: 2px solid var(--accent); padding-left: 1rem; margin-bottom: 1.5rem;">
                    <div style="font-weight: 800; color: var(--text-main);">Project Initialized</div>
                    <div style="font-size: 0.7rem; opacity: 0.6;">JUST NOW</div>
                </div>
            </section>
        </aside>
    </div>

    <script>
        // === 3D BACKGROUND (Matching Website) ===
        const initBackground = () => {
            const container = document.getElementById('canvas-container');
            const scene = new THREE.Scene();
            scene.background = new THREE.Color('#f6f7f2');
            // Add soft fog for atmospheric perspective (fades distant objects)
            scene.fog = new THREE.Fog('#f6f7f2', 10, 45);
            const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
            camera.position.set(0, 2, 8);

            const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            container.appendChild(renderer.domElement);

            const cityGroup = new THREE.Group();
            scene.add(cityGroup);
            
            const buildingMat = new THREE.MeshPhongMaterial({ color: 0x294033, transparent: true, opacity: 0.05 });
            const edgeMat = new THREE.LineBasicMaterial({ color: 0x294033, transparent: true, opacity: 0.1 });
            
            const gridSize = 30;
            for (let x = -gridSize; x < gridSize; x += 3) {
                for (let z = -gridSize; z < gridSize; z += 3) {
                    if (Math.random() > 0.3) continue;
                    const h = Math.random() * 2 + 0.5;
                    const geo = new THREE.BoxGeometry(1, h, 1);
                    const mesh = new THREE.Mesh(geo, buildingMat);
                    mesh.position.set(x, h/2 - 2, z);
                    const edges = new THREE.EdgesGeometry(geo);
                    const line = new THREE.LineSegments(edges, edgeMat);
                    line.position.copy(mesh.position);
                    cityGroup.add(mesh);
                    cityGroup.add(line);
                }
            }

            scene.add(new THREE.AmbientLight(0xffffff, 0.8));
            
            const animate = () => {
                requestAnimationFrame(animate);
                cityGroup.rotation.y += 0.0003;
                renderer.render(scene, camera);
            };
            animate();

            // Entry Transition Logic
            initEntryTransition();

            window.addEventListener('resize', () => {
                camera.aspect = window.innerWidth / window.innerHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(window.innerWidth, window.innerHeight);
            });
        };

        // === 3D HOUSE MODEL (Evolving) ===
        let houseScene, houseCamera, houseRenderer, houseGroup;
        const initHouseModel = () => {
            const container = document.getElementById('house-preview');
            houseScene = new THREE.Scene();
            houseCamera = new THREE.PerspectiveCamera(50, 1, 0.1, 1000);
            houseCamera.position.set(8, 6, 8);
            houseCamera.lookAt(0, 1, 0);

            houseRenderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            houseRenderer.setSize(400, 400);
            container.appendChild(houseRenderer.domElement);

            houseGroup = new THREE.Group();
            houseScene.add(houseGroup);
            houseScene.add(new THREE.AmbientLight(0xffffff, 0.6));
            const light = new THREE.DirectionalLight(0xd4af37, 1);
            light.position.set(5, 10, 5);
            houseScene.add(light);

            updateHouseGeometry(<?php echo $current_stage_idx; ?>);

            const animateHouse = () => {
                requestAnimationFrame(animateHouse);
                houseGroup.rotation.y += 0.005;
                houseRenderer.render(houseScene, houseCamera);
            };
            animateHouse();
        };

        const updateHouseGeometry = (idx) => {
            while(houseGroup.children.length > 0) houseGroup.remove(houseGroup.children[0]);
            
            // Premium Crystal Glass Material
            const mat = new THREE.MeshPhongMaterial({ 
                color: 0xffffff, 
                transparent: true, 
                opacity: 0.15, 
                shininess: 100,
                specular: 0xd4af37
            });
            const lineMat = new THREE.LineBasicMaterial({ 
                color: 0xd4af37, 
                transparent: true, 
                opacity: 0.5 
            });
            
            const errorMat = new THREE.MeshPhongMaterial({ color: 0xef4444, transparent: true, opacity: 0.4 });

            const addPart = (geo, y, isError = false) => {
                const mesh = new THREE.Mesh(geo, isError ? errorMat : mat);
                mesh.position.y = y;
                const edges = new THREE.EdgesGeometry(geo);
                const lines = new THREE.LineSegments(edges, lineMat);
                lines.position.y = y;
                houseGroup.add(mesh);
                houseGroup.add(lines);
            };

            const numFloors = parseInt(gatherData.floors) || 1;
            const floorsValid = numFloors >= 1 && numFloors <= 200;

            if (idx === 1) {
                // Live 3D Plot Validation
                const fw = parseFloat(surveyData.f_width);
                const rw = parseFloat(surveyData.r_width);
                const ld = parseFloat(surveyData.l_depth);
                const rd = parseFloat(surveyData.r_depth);
                const area = parseFloat(surveyData.total_area);
                
                const dimsValid = fw >= 5 && rw >= 5 && ld >= 5 && rd >= 5 && area >= 100;
                
                const safeFw = isNaN(fw) || fw <= 0 ? 0.1 : fw;
                const safeRw = isNaN(rw) || rw <= 0 ? 0.1 : rw;
                const safeLd = isNaN(ld) || ld <= 0 ? 0.1 : ld;
                const safeRd = isNaN(rd) || rd <= 0 ? 0.1 : rd;

                const scale = 0.08;
                const fs = safeFw * scale;
                const rs = safeRw * scale;
                const ls = safeLd * scale;
                const rsd = safeRd * scale;

                const shape = new THREE.Shape();
                shape.moveTo(-rs/2, ls/2);
                shape.lineTo(rs/2, ls/2);
                shape.lineTo(fs/2, -ls/2);
                shape.lineTo(-fs/2, -ls/2);
                shape.lineTo(-rs/2, ls/2);

                const extrudeSettings = { depth: 0.2, bevelEnabled: false };
                const geometry = new THREE.ExtrudeGeometry(shape, extrudeSettings);
                const mesh = new THREE.Mesh(geometry, dimsValid ? mat : errorMat);
                mesh.rotation.x = -Math.PI / 2;
                
                const edges = new THREE.EdgesGeometry(geometry);
                const lines = new THREE.LineSegments(edges, lineMat);
                lines.rotation.copy(mesh.rotation);
                
                houseGroup.add(mesh);
                houseGroup.add(lines);

                // Add floors on top of survey plot
                for(let i=0; i < Math.min(numFloors, 50); i++) {
                    addPart(new THREE.BoxGeometry(fs * 0.8, 0.4, ls * 0.8), 0.4 + (i * 0.45), !floorsValid || !dimsValid);
                }
                return;
            }

            if (idx === 2) { // Planning & Design 3D Model
                const fw = parseFloat(surveyData.f_width) || 30;
                const ld = parseFloat(surveyData.l_depth) || 40;
                const scale = 0.08;
                const ws = fw * scale;
                const ds = ld * scale;
                const fh = parseFloat(planningData.floor_height) || 10;
                const hScale = fh * 0.04;

                // Plot Slab
                addPart(new THREE.BoxGeometry(ws + 1, 0.1, ds + 1), 0);
                
                // Build-up massing based on floors
                for(let i=0; i < Math.min(numFloors, 10); i++) {
                    let geo = new THREE.BoxGeometry(ws * 0.85, hScale, ds * 0.85);
                    if (planningData.elevation_style === 'traditional' && i === numFloors - 1) {
                         // Add a sloped roof for traditional last floor
                         const roof = new THREE.Mesh(new THREE.ConeGeometry(ws * 0.6, 1.5, 4), mat);
                         roof.position.y = (i * (hScale + 0.1)) + hScale + 0.7;
                         roof.rotation.y = Math.PI / 4;
                         houseGroup.add(roof);
                    }
                    addPart(geo, (hScale/2 + 0.1) + (i * (hScale + 0.1)));
                }

                if (planningData.elevation_style === 'modern') {
                    // Modern cantilever effect or glass highlights
                    const glass = new THREE.Mesh(new THREE.BoxGeometry(ws * 0.9, hScale * numFloors, ws * 0.2), 
                        new THREE.MeshPhongMaterial({ color: 0x87ceeb, transparent: true, opacity: 0.5 }));
                    glass.position.set(0, (hScale * numFloors)/2, ds * 0.35);
                    houseGroup.add(glass);
                }
                return;
            }

            if (idx === 0) {
                const area = parseFloat(gatherData.plot_area);
                const areaValid = !isNaN(area) && area >= 100 && area <= 1000000;
                
                if (isNaN(area) || area <= 0) {
                    addPart(new THREE.BoxGeometry(4, 0.2, 4), 0);
                    // Default floor even if area missing
                    addPart(new THREE.BoxGeometry(3.2, 0.8, 3.2), 0.5, !floorsValid);
                } else {
                    const dim = Math.sqrt(area) * 0.1;
                    const finalDim = Math.min(dim, 15);
                    addPart(new THREE.BoxGeometry(finalDim, 0.2, finalDim), 0, !areaValid);
                    
                    for(let i=0; i < Math.min(numFloors, 20); i++) {
                        addPart(new THREE.BoxGeometry(finalDim * 0.8, 0.6, finalDim * 0.8), 0.5 + (i * 0.65), !floorsValid);
                    }
                }
                return;
            }

            if (idx >= 0) addPart(new THREE.BoxGeometry(4, 0.2, 4), 0);
            if (idx >= 3) addPart(new THREE.BoxGeometry(4, 2, 4), 1.1);
            if (idx >= 5) addPart(new THREE.BoxGeometry(4.2, 0.2, 4.2), 2.2);
            if (idx >= 8) addPart(new THREE.BoxGeometry(3, 1.5, 3), 3);
            if (idx >= 11) {
                const roofGeo = new THREE.ConeGeometry(3, 2, 4);
                const roof = new THREE.Mesh(roofGeo, mat);
                roof.position.y = 4.5;
                roof.rotation.y = Math.PI/4;
                houseGroup.add(roof);
            }
        };

        // === Gathering Wizard Logic ===
        let gatherData = <?php echo !empty($project_details['gathering']) ? json_encode($project_details['gathering']) : "{
            mode: 'in-person',
            plot_area: '',
            soil_type: '',
            floors: '',
            budget: '',
            bhk: '',
            kitchen: '',
            location: '',
            timeline: '',
            notes: ''
        }"; ?>;

        function setGatherMode(mode, el) {
            gatherData.mode = mode;
            document.querySelectorAll('.choice-card').forEach(c => c.classList.remove('active'));
            el.classList.add('active');
            document.getElementById('preview-mode-tag').innerText = `COLLECTION: ${mode.toUpperCase().replace('-', ' ')}`;
        }

        function nextGatherStep(step) {
            document.querySelectorAll('.gathering-step').forEach(s => s.classList.remove('active'));
            document.getElementById(`gather-step-${step}`).classList.add('active');
            
            // Update task label based on step (Removed - No Active Task UI)
        }

        function updateGatherPreview(key, val) {
            gatherData[key] = val || '';
            const displayVal = (key === 'floors') ? (val ? val + ' Floor' + (val > 1 ? 's' : '') : '') : (val || '');
            const el = document.getElementById(`val-${key}`);
            if (el) el.innerText = displayVal;
            
            // Live Validation Visuals
            const inputEl = event.target;
            const group = inputEl.closest('.input-group');
            if (group) {
                let isValid = val && val.toString().length > 0;
                
                // Strict logical validation
                if (isValid) {
                    const numVal = parseFloat(val);
                    if (key === 'floors' && (numVal < 1 || numVal > 200)) isValid = false;
                    if (key === 'plot_area' && (numVal < 100 || numVal > 1000000)) isValid = false;
                    if (key === 'budget' && numVal < 10000) isValid = false;
                    
                    // Prevent non-numeric if it's supposed to be a number
                    if (inputEl.type === 'number' && isNaN(numVal)) isValid = false;
                }

                if (isValid) {
                    group.classList.remove('invalid');
                    group.classList.add('valid');
                } else {
                    group.classList.remove('valid');
                    group.classList.add('invalid');
                }
            }

            // Animate detail update
            gsap.fromTo(`#val-${key}`, { color: 'var(--accent)' }, { color: 'var(--text-main)', duration: 1 });

            // Trigger 3D Validation for ALL inputs
            updateHouseGeometry(0);
        }

        async function finishGathering() {
            // Save draft first
            await saveDraft();
            
            // Show feedback
            const container = document.getElementById('stage-specific-content');
            gsap.to(container, { opacity: 0, scale: 0.98, duration: 0.5, onComplete: () => {
                container.innerHTML = `
                    <div style="text-align: center; padding: 4rem 2rem; background: white; border-radius: 30px; border: 1px solid #eee;">
                        <div style="font-size: 4rem; color: #22c55e; margin-bottom: 1.5rem;"><i class="fas fa-check-circle"></i></div>
                        <h2 style="font-family: 'Outfit'; font-weight: 800; font-size: 2rem;">Requirement Gathering Finalized</h2>
                        <p style="color: var(--text-muted); max-width: 500px; margin: 1rem auto;">Your project requirements and structural constraints have been successfully captured in the project vault. You can now proceed to the Site Inspection stage.</p>
                        <button class="btn-action" style="width: auto; padding: 1rem 2rem; margin-top: 2rem; background: var(--accent);" onclick="approveCurrentStage()">NEXT: SITE INSPECTION & SURVEY <i class="fas fa-arrow-right"></i></button>
                    </div>
                `;
                gsap.to(container, { opacity: 1, scale: 1, duration: 0.5 });
            }});
        }

        function downloadGatheredData() {
            const content = `CONSTRUCTA PROJECT REQUIREMENT EXPORT\n` +
                          `====================================\n` +
                          `Project ID: ${projectID}\n` +
                          `Mode: ${gatherData.mode}\n` +
                          `Plot Area: ${gatherData.plot_area} sq.ft\n` +
                          `Soil Type: ${gatherData.soil_type}\n` +
                          `Floors: ${gatherData.floors}\n` +
                          `Budget: ${gatherData.budget}\n` +
                          `BHK: ${gatherData.bhk}\n` +
                          `Kitchen: ${gatherData.kitchen}\n` +
                          `Location: ${gatherData.location}\n` +
                          `Timeline: ${gatherData.timeline}\n` +
                          `Notes: ${gatherData.notes}\n` +
                          `====================================\n` +
                          `Generated on: ${new Date().toLocaleString()}`;
            
            const blob = new Blob([content], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `Project_${projectID}_Requirements.txt`;
            a.click();
            URL.revokeObjectURL(url);
        }

        // === Survey & Inspection Logic ===
        let surveyData = <?php echo !empty($project_details['survey']) ? json_encode($project_details['survey']) : "{
            f_width: 0,
            r_width: 0,
            l_depth: 0,
            r_depth: 0,
            total_area: 0,
            road_width: 0,
            road_type: '',
            constraints: ''
        }"; ?>;

        function nextSurveyStep(step) {
            document.querySelectorAll('.gathering-step').forEach(s => s.classList.remove('active'));
            document.getElementById(`survey-step-${step}`).classList.add('active');
            const labels = ["Plot Dimensions", "Road & Access"];
            // document.getElementById('active-task-label').innerText = labels[step] || "Site Survey";
        }

        function updateSurveyPreview(key, val) {
            surveyData[key] = val || (key.includes('width') || key.includes('depth') || key === 'total_area' ? 0 : '');
            
            // Validation Visuals
            const inputEl = event.target;
            const group = inputEl.closest('.input-group');
            if (group) {
                let isValid = val && val.toString().length > 0;
                const numVal = parseFloat(val);
                
                if (key.includes('width') || key.includes('depth')) {
                    if (isNaN(numVal) || numVal < 5) isValid = false;
                } else if (key === 'total_area') {
                    if (isNaN(numVal) || numVal < 100) isValid = false;
                }

                if (isValid) {
                    group.classList.remove('invalid');
                    group.classList.add('valid');
                } else {
                    group.classList.remove('valid');
                    group.classList.add('invalid');
                }
            }

            if (['f_width', 'r_width', 'l_depth', 'r_depth'].includes(key)) {
                calculateSurveyArea();
            }

            if (key === 'road_width' || key === 'road_type') {
                const el = document.getElementById(`val-${key}`);
                if (el) el.innerText = val + (key === 'road_width' ? ' ft' : '');
            }

            if (key === 'total_area') {
                const liveEl = document.getElementById('val-site-area');
                if (liveEl) liveEl.innerText = val + ' sq.ft';
            }

            // Always trigger 3D Validation for Survey Inputs
            updateHouseGeometry(1);
        }

        function calculateSurveyArea() {
            // Shoelace-like approximation for 4-sided plot
            const avgWidth = (parseFloat(surveyData.f_width) + parseFloat(surveyData.r_width)) / 2;
            const avgDepth = (parseFloat(surveyData.l_depth) + parseFloat(surveyData.r_depth)) / 2;
            const area = Math.round(avgWidth * avgDepth);
            
            document.getElementById('calc-site-area').innerText = `${area} sq.ft`;
            
            // Auto-populate the input field if it hasn't been manually touched (or just always keep in sync)
            const areaInput = document.getElementById('survey-total-area');
            if (areaInput) {
                areaInput.value = area;
                surveyData.total_area = area;
            }

            const valArea = document.getElementById('val-site-area');
            if (valArea) valArea.innerText = `${area} sq.ft`;
            
            gsap.fromTo('#val-site-area', { scale: 1.2, color: 'var(--accent)' }, { scale: 1, color: 'var(--primary)', duration: 0.5 });
        }

        async function finishSurvey() {
            // Save draft first
            await saveDraft();
            
            // Show feedback
            const container = document.getElementById('stage-specific-content');
            gsap.to(container, { opacity: 0, scale: 0.98, duration: 0.5, onComplete: () => {
                container.innerHTML = `
                    <div style="text-align: center; padding: 4rem 2rem; background: white; border-radius: 30px; border: 1px solid #eee;">
                        <div style="font-size: 4rem; color: #22c55e; margin-bottom: 1.5rem;"><i class="fas fa-check-circle"></i></div>
                        <h2 style="font-family: 'Outfit'; font-weight: 800; font-size: 2rem;">Site Survey Completed</h2>
                        <p style="color: var(--text-muted); max-width: 500px; margin: 1rem auto;">Accurate site measurements and access assessments have been successfully recorded. You are now ready for the Planning & Design phase.</p>
                        <button class="btn-action" style="width: auto; padding: 1rem 2rem; margin-top: 2rem; background: var(--accent);" onclick="approveCurrentStage()">NEXT: PLANNING & DESIGN <i class="fas fa-arrow-right"></i></button>
                    </div>
                `;
                gsap.to(container, { opacity: 1, scale: 1, duration: 0.5 });
            }});
        }

        function downloadSurveyData() {
            const area = document.getElementById('val-site-area').innerText;
            const content = `CONSTRUCTA SITE SURVEY EXPORT\n` +
                          `==============================\n` +
                          `Project ID: ${projectID}\n` +
                          `Front Width: ${surveyData.f_width} ft\n` +
                          `Rear Width: ${surveyData.r_width} ft\n` +
                          `Left Depth: ${surveyData.l_depth} ft\n` +
                          `Right Depth: ${surveyData.r_depth} ft\n` +
                          `Total Area: ${surveyData.total_area} sq.ft\n` +
                          `Road Width: ${surveyData.road_width} ft\n` +
                          `Road Type: ${surveyData.road_type}\n` +
                          `Constraints: ${surveyData.constraints}\n` +
                          `==============================\n` +
                          `Generated on: ${new Date().toLocaleString()}`;
            
            const blob = new Blob([content], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `Project_${projectID}_Survey.txt`;
            a.click();
            URL.revokeObjectURL(url);
        }

        let planningData = <?php echo !empty($project_details['planning']) ? json_encode($project_details['planning']) : "{
            elevation_style: 'modern',
            floor_height: 10.5,
            parapet: 3.5,
            bedrooms: 1,
            bathrooms: 1,
            kitchen_pos: 'SE',
            stairs: 'Internal',
            notes: '',
            versions: []
        }"; ?>;
        
        let currentPlanFloor = 0;

        function switchPlanFloor(idx) {
            currentPlanFloor = idx;
            document.getElementById('floor-indicator').innerText = (idx === 0) ? 'CURRENT: GROUND FLOOR' : `CURRENT: FLOOR ${idx}`;
            // In a more complex app, this would load floor-specific room counts from an array
        }

        function nextPlanStep(step) {
            document.querySelectorAll('#stage-specific-content .gathering-step').forEach(s => s.classList.remove('active'));
            document.getElementById(`plan-step-${step}`).classList.add('active');
        }

        function updateStyle(style, el) {
            planningData.elevation_style = style;
            document.querySelectorAll('#plan-step-1 .choice-card').forEach(c => c.classList.remove('active'));
            el.classList.add('active');
            document.getElementById('val-elevation_style').innerText = style.charAt(0).toUpperCase() + style.slice(1);
            updateHouseGeometry(2);
        }

        function updatePlanning(key, val) {
            planningData[key] = val;
            
            // Update local numeric label next to slider
            const target = window.event?.target;
            if (target && target.nextElementSibling && target.nextElementSibling.classList.contains('val-display')) {
                target.nextElementSibling.innerText = val;
            }

            // Sync sidebar
            if (key === 'bedrooms' || key === 'bathrooms') {
                const b = planningData.bedrooms || 0;
                const ba = planningData.bathrooms || 0;
                document.getElementById('val-room_count').innerText = `${b} Bed, ${ba} Bath`;
            }
            if (key === 'floor_height') {
                document.getElementById('val-floor_height').innerText = val + ' ft';
            }

            updateSpatialPreview();
            updateHouseGeometry(2);
        }

        function updateSpatialPreview() {
            const svgGroup = document.getElementById('spatial-rooms');
            if (!svgGroup) return;
            svgGroup.innerHTML = '';
            
            const count = parseInt(planningData.bedrooms || 0) + parseInt(planningData.bathrooms || 0);
            for(let i=0; i < Math.min(count, 8); i++) {
                const rect = document.createElementNS("http://www.w3.org/2000/svg", "rect");
                const size = 30;
                rect.setAttribute("x", -50 + (i % 3) * 35);
                rect.setAttribute("y", -50 + Math.floor(i / 3) * 35);
                rect.setAttribute("width", size);
                rect.setAttribute("height", size);
                rect.setAttribute("fill", "rgba(0,0,0,0.03)");
                rect.setAttribute("stroke", "var(--primary)");
                rect.setAttribute("stroke-width", "0.5");
                rect.setAttribute("stroke-dasharray", "2");
                svgGroup.appendChild(rect);
            }
        }

        async function finishPlanning() {
            // Save draft first
            await saveDraft();
            
            // Show feedback
            const container = document.getElementById('stage-specific-content');
            gsap.to(container, { opacity: 0, scale: 0.98, duration: 0.5, onComplete: () => {
                container.innerHTML = `
                    <div style="text-align: center; padding: 4rem 2rem; background: white; border-radius: 30px; border: 1px solid #eee;">
                        <div style="font-size: 4rem; color: #22c55e; margin-bottom: 1.5rem;"><i class="fas fa-check-circle"></i></div>
                        <h2 style="font-family: 'Outfit'; font-weight: 800; font-size: 2rem;">Planning Phase Finalized</h2>
                        <p style="color: var(--text-muted); max-width: 500px; margin: 1rem auto;">Your architectural layout and design parameters have been synchronized with the project vault. You can now proceed to the Structural Detail phase.</p>
                        <button class="btn-action" style="width: auto; padding: 1rem 2rem; margin-top: 2rem; background: var(--accent);" onclick="approveCurrentStage()">NEXT: STRUCTURAL DETAIL <i class="fas fa-arrow-right"></i></button>
                    </div>
                `;
                gsap.to(container, { opacity: 1, scale: 1, duration: 0.5 });
            }});
        }

        function downloadPlanningReport() {
            const content = `CONSTRUCTA PLANNING REPORT\n` +
                          `==============================\n` +
                          `Project ID: ${projectID}\n` +
                          `Style: ${planningData.elevation_style}\n` +
                          `Floor Height: ${planningData.floor_height} ft\n` +
                          `Parapet: ${planningData.parapet} ft\n` +
                          `Bedrooms: ${planningData.bedrooms}\n` +
                          `Bathrooms: ${planningData.bathrooms}\n` +
                          `Kitchen: ${planningData.kitchen_pos}\n` +
                          `Stairs: ${planningData.stairs}\n` +
                          `------------------------------\n` +
                          `Engineer Notes: ${planningData.notes}\n` +
                          `==============================\n` +
                          `Generated on: ${new Date().toLocaleString()}`;
            
            const blob = new Blob([content], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `Planning_Report_PJ${projectID}.txt`;
            a.click();
            URL.revokeObjectURL(url);
        }

        // === Cost Estimation & BOQ Logic ===
        let estimationData = <?php echo !empty($project_details['estimation']) ? json_encode($project_details['estimation']) : "{
            quality: 'basic',
            rates: {
                foundation: 0,
                rcc: 0,
                masonry: 0,
                flooring: 0,
                mep: 0,
                finishes: 0
            },
            notes: ''
        }"; ?>;

        const qualityRates = {
            basic: { foundation: 250, rcc: 1200, masonry: 180, flooring: 150, mep: 85000, finishes: 65 },
            standard: { foundation: 380, rcc: 1650, masonry: 240, flooring: 280, mep: 145000, finishes: 110 },
            premium: { foundation: 550, rcc: 2200, masonry: 350, flooring: 650, mep: 280000, finishes: 240 }
        };

        function updateQuality(level, el) {
            estimationData.quality = level;
            document.querySelectorAll('.quality-btn').forEach(b => b.classList.remove('active'));
            el.classList.add('active');
            
            // Sync rates
            estimationData.rates = { ...qualityRates[level] };
            
            // Update UI inputs
            Object.keys(estimationData.rates).forEach(key => {
                const input = document.querySelector(`input[oninput*="updateRate('${key}'"]`);
                if (input) input.value = estimationData.rates[key];
            });
            
            calculateEstimation();
        }

        function updateRate(phase, val) {
            estimationData.rates[phase] = parseFloat(val) || 0;
            calculateEstimation();
        }

        function calculateEstimation() {
            // Allow calculation if we are viewing stage 3 OR if we want to run it in background (e.g. for reports) check removed for robustness
            // if (viewStageIdx !== 3) return; 

            // Robust Data Retrieval with Fallbacks
            let area = parseFloat(surveyData.total_area);
            if (!area || isNaN(area) || area === 0) {
                // Try to recover area from dimensions
                 const avgWidth = (parseFloat(surveyData.f_width) + parseFloat(surveyData.r_width)) / 2;
                 const avgDepth = (parseFloat(surveyData.l_depth) + parseFloat(surveyData.r_depth)) / 2;
                 area = Math.round(avgWidth * avgDepth) || 0;
            }

             // Hard Fallback to ensure Estimation Tool works even without Survey Data
             if (area <= 0) {
                 area = 1000; 
                 console.warn("Using Fallback Default Area (1000 sq.ft) for Estimation");
             }

            const floors = parseInt(gatherData.floors) || 1; // Default to 1 floor if missing
            const totalBuiltUp = area * floors;
            
            console.log("Estimation Calc:", { area, floors, totalBuiltUp });

            // Quantities Logic (Standard ratios)
            const quantities = {
                foundation: Math.round(area * 1.8),
                rcc: totalBuiltUp,
                masonry: Math.round(totalBuiltUp * 2.5),
                flooring: Math.round(totalBuiltUp * 0.9),
                mep: 1,
                finishes: Math.round(totalBuiltUp * 3.2)
            };

            // Update Qty Labels
            const qtyFoundation = document.getElementById('qty-foundation');
            if (qtyFoundation) qtyFoundation.innerText = quantities.foundation + ' Cu.Ft';
            
            const qtyRcc = document.getElementById('qty-rcc');
            if (qtyRcc) qtyRcc.innerText = quantities.rcc + ' Sq.Ft';
            
            const qtyMasonry = document.getElementById('qty-masonry');
            if (qtyMasonry) qtyMasonry.innerText = quantities.masonry + ' Sq.Ft';
            
            const qtyFlooring = document.getElementById('qty-flooring');
            if (qtyFlooring) qtyFlooring.innerText = quantities.flooring + ' Sq.Ft';
            
            const qtyFinishes = document.getElementById('qty-finishes');
            if (qtyFinishes) qtyFinishes.innerText = quantities.finishes + ' Sq.Ft';

            // Calculate Totals
            let total = 0;
            Object.keys(quantities).forEach(key => {
                const rowTotal = quantities[key] * (estimationData.rates[key] || 0);
                const el = document.getElementById(`cost-${key}`);
                if (el) el.innerText = '₹' + rowTotal.toLocaleString();
                total += rowTotal;
            });

            // Update Hero Total
            const totalEl = document.getElementById('est-total-cost');
            if (totalEl) {
                const targetTotal = total;
                let currentTotal = { val: parseFloat(totalEl.innerText.replace(/[₹,]/g, '')) || 0 };
                
                gsap.to(currentTotal, {
                    val: targetTotal,
                    duration: 1,
                    onUpdate: () => {
                        totalEl.innerText = '₹' + Math.round(currentTotal.val).toLocaleString();
                        estimationData.total_cost = Math.round(currentTotal.val);
                    }
                });
            }

            // Budget Comparison
            const clientBudget = parseFloat(gatherData.budget) || 0;
            const statusText = document.getElementById('budget-status-text');
            const fillBar = document.getElementById('budget-fill-bar');
            const optBox = document.getElementById('opt-box');
            
            if (statusText && fillBar && optBox) {
                const pct = Math.min((total / clientBudget) * 100, 100);
                gsap.to(fillBar, { width: pct + '%', duration: 1 });

                if (total <= clientBudget) {
                    statusText.innerText = 'WITHIN BUDGET';
                    statusText.style.color = '#22c55e';
                    fillBar.style.background = '#22c55e';
                    optBox.style.display = 'none';
                } else if (total <= clientBudget * 1.15) {
                    statusText.innerText = 'SLIGHTLY OVER';
                    statusText.style.color = '#f59e0b';
                    fillBar.style.background = '#f59e0b';
                    optBox.style.display = 'block';
                } else {
                    statusText.innerText = 'CRITICAL OVER BUDGET';
                    statusText.style.color = '#ef4444';
                    fillBar.style.background = '#ef4444';
                    optBox.style.display = 'block';
                }
            }

            // Sync sidebar
            const sideTotal = document.getElementById('val-est-total');
            if (sideTotal) sideTotal.innerText = '₹' + Math.round(total).toLocaleString();
            
            const sideQuality = document.getElementById('val-est-quality');
            if (sideQuality) sideQuality.innerText = estimationData.quality;
            
            const sideDelta = document.getElementById('val-est-delta');
            if (sideDelta) {
                const delta = Math.round((clientBudget / total) * 100); 
                sideDelta.innerText = delta + '%';
                sideDelta.style.color = total > clientBudget ? '#ef4444' : '#22c55e';
            }

            updateMaterialBOQ(totalBuiltUp);
        }

        function updateMaterialBOQ(builtUp) {
            // High-level BOQ ratios
            const materials = [
                { name: 'Cement', qty: Math.round(builtUp * 0.45), unit: 'Bags' },
                { name: 'Steel', qty: (builtUp * 3.8 / 1000).toFixed(2), unit: 'Tons' },
                { name: 'Bricks', qty: Math.round(builtUp * 18), unit: 'Pcs' },
                { name: 'Sand', qty: Math.round(builtUp * 1.2), unit: 'Cu.Ft' },
                { name: 'Aggregate', qty: Math.round(builtUp * 0.6), unit: 'Cu.Ft' }
            ];

            const tbody = document.getElementById('material-boq-body');
            if (tbody) {
                tbody.innerHTML = materials.map(m => `
                    <tr>
                        <td style="font-weight: 700;">${m.name}</td>
                        <td style="font-family: 'JetBrains Mono'; font-weight: 800;">${m.qty}</td>
                        <td style="font-size: 0.7rem; color: var(--text-muted);">${m.unit}</td>
                    </tr>
                `).join('');
            }
        }

        function generateBOQ() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Header
            doc.setFontSize(22);
            doc.setTextColor(26, 46, 35);
            doc.text("CONSTRUCTA | BILL OF QUANTITIES", 14, 22);
            
            doc.setFontSize(10);
            doc.setTextColor(100);
            doc.text(`Project ID: PJ-${projectID}`, 14, 30);
            doc.text(`Generated on: ${new Date().toLocaleString()}`, 14, 35);
            doc.text(`Quality Standard: ${estimationData.quality.toUpperCase()}`, 14, 40);
            
            // Project Summary
            doc.setFontSize(14);
            doc.setTextColor(212, 175, 55);
            doc.text("Project Summary", 14, 55);
            
            const summaryData = [
                ["Total Built-up Area", `${surveyData.total_area * (gatherData.floors || 1)} Sq.Ft`],
                ["Client Budget Target", `Rs. ${parseFloat(gatherData.budget || 0).toLocaleString()}`],
                ["Estimated Total Cost", document.getElementById('est-total-cost').innerText]
            ];
            
            doc.autoTable({
                startY: 60,
                head: [['Parameter', 'Value']],
                body: summaryData,
                theme: 'striped',
                headStyles: { fillColor: [26, 46, 35] }
            });
            
            // Material Quantities
            doc.text("Material Quantities & BOQ", 14, doc.lastAutoTable.finalY + 15);
            
            const materials = [
                ['Cement', Math.round((surveyData.total_area * (gatherData.floors || 1)) * 0.45), 'Bags'],
                ['Steel', ((surveyData.total_area * (gatherData.floors || 1)) * 3.8 / 1000).toFixed(2), 'Tons'],
                ['Bricks/Blocks', Math.round((surveyData.total_area * (gatherData.floors || 1)) * 18), 'Pcs'],
                ['Sand', Math.round((surveyData.total_area * (gatherData.floors || 1)) * 1.2), 'Cu.Ft'],
                ['Aggregate', Math.round((surveyData.total_area * (gatherData.floors || 1)) * 0.6), 'Cu.Ft']
            ];
            
            doc.autoTable({
                startY: doc.lastAutoTable.finalY + 20,
                head: [['Material Description', 'Estimated Quantity', 'Unit']],
                body: materials,
                theme: 'grid',
                headStyles: { fillColor: [212, 175, 55] }
            });
            
            // Phase Breakdown
            doc.text("Phase-wise Cost Estimation", 14, doc.lastAutoTable.finalY + 15);
            
            const phases = [
                ['Excavation & Foundation', document.getElementById('qty-foundation').innerText, 'Rs. ' + estimationData.rates.foundation, document.getElementById('cost-foundation').innerText],
                ['RCC Structure', document.getElementById('qty-rcc').innerText, 'Rs. ' + estimationData.rates.rcc, document.getElementById('cost-rcc').innerText],
                ['Masonry (Walls)', document.getElementById('qty-masonry').innerText, 'Rs. ' + estimationData.rates.masonry, document.getElementById('cost-masonry').innerText],
                ['Flooring & Tiles', document.getElementById('qty-flooring').innerText, 'Rs. ' + estimationData.rates.flooring, document.getElementById('cost-flooring').innerText],
                ['Electrical & Plumbing', document.getElementById('qty-mep').innerText, 'Rs. ' + estimationData.rates.mep, document.getElementById('cost-mep').innerText],
                ['Painting & Finishes', document.getElementById('qty-finishes').innerText, 'Rs. ' + estimationData.rates.finishes, document.getElementById('cost-finishes').innerText]
            ];
            
            doc.autoTable({
                startY: doc.lastAutoTable.finalY + 20,
                head: [['Phase', 'Quantity', 'Rate', 'Total']],
                body: phases,
                theme: 'grid',
                headStyles: { fillColor: [26, 46, 35] }
            });
            
            // Footer
            doc.setFontSize(8);
            doc.setTextColor(150);
            doc.text("This is an AI-generated estimation. On-site verification by a certified engineer is mandatory.", 14, doc.lastAutoTable.finalY + 20);
            
            doc.save(`Constructa_BOQ_PJ${projectID}.pdf`);
        }

        async function finishEstimation() {
            await saveDraft();
            const container = document.getElementById('stage-specific-content');
            gsap.to(container, { opacity: 0, scale: 0.98, duration: 0.5, onComplete: () => {
                container.innerHTML = `
                    <div style="text-align: center; padding: 4rem 2rem; background: white; border-radius: 30px; border: 1px solid #eee;">
                        <div style="font-size: 4rem; color: #d4af37; margin-bottom: 1.5rem;"><i class="fas fa-file-invoice-dollar"></i></div>
                        <h2 style="font-family: 'Outfit'; font-weight: 800; font-size: 2rem;">Estimation & BOQ Locked</h2>
                        <p style="color: var(--text-muted); max-width: 500px; margin: 1rem auto;">Budget analysis and material quantities have been finalized and recorded. Project is now ready for legal approvals.</p>
                        <button class="btn-action" style="width: auto; padding: 1rem 2rem; margin-top: 2rem; background: var(--accent);" onclick="approveCurrentStage()">NEXT: APPROVAL & PERMISSIONS <i class="fas fa-arrow-right"></i></button>
                    </div>
                `;
                gsap.to(container, { opacity: 1, scale: 1, duration: 0.5 });
            }});
        }

        // === Approval & Permissions Logic ===
        let approvalsData = <?php echo !empty($project_details['approvals']) ? json_encode($project_details['approvals']) : "{
            plan_approval: 'not_submitted',
            structural_safety: 'pending',
            land_verification: 'pending',
            utility_water: 'pending',
            utility_electricity: 'pending',
            utility_sewer: 'pending',
            plan_remarks: '',
            engineer_license: '',
            fee_amount: 0,
            payment_status: 'unpaid',
            nocs: {
                fire_noc: false,
                airport_noc: false,
                env_noc: false
            },
            confirmed: false
        }"; ?>;

        function updateApprovalStatus(key, val, el) {
            approvalsData[key] = val;
            
            // UI Feedback
            if (el && el.tagName === 'BUTTON') {
                const parent = el.parentElement;
                parent.querySelectorAll('button').forEach(b => b.classList.remove('active'));
                el.classList.add('active');
            }

            calculateApprovalProgress();
            
            // Update Dashboard UI
            const dashboardEl = document.getElementById(`status-${key.replace('_', '-')}`);
            if (dashboardEl) {
                dashboardEl.className = `status-pill status-${val.replace('_', '-')}`;
                dashboardEl.innerText = val.replace('_', ' ').toUpperCase();
            }

            // Grouped status update for utilities
            if (key.startsWith('utility_')) {
                const utilCount = ['utility_water', 'utility_electricity', 'utility_sewer'].filter(k => approvalsData[k] === 'approved').length;
                const utilDash = document.getElementById('status-utilities');
                if (utilDash) {
                    utilDash.innerText = `${utilCount} / 3 DONE`;
                    utilDash.className = `status-pill status-${utilCount === 3 ? 'approved' : (utilCount > 0 ? 'under-review' : 'not-submitted')}`;
                }
            }
        }

        function updateApprovalData(key, val) {
            approvalsData[key] = val;
        }

        function toggleNOC(key, checked) {
            approvalsData.nocs[key] = checked;
            calculateApprovalProgress();
        }

        function updateConfirm() {
            approvalsData.confirmed = document.getElementById('engineer-legal-confirm').checked;
        }

        function calculateApprovalProgress() {
            const mandatory = ['plan_approval', 'structural_safety', 'land_verification', 'utility_water', 'utility_electricity', 'utility_sewer'];
            const completed = mandatory.filter(k => approvalsData[k] === 'approved' || approvalsData[k] === 'verified').length;
            const total = mandatory.length;
            
            const pct = Math.round((completed / total) * 100);
            
            const pctEl = document.getElementById('approval-progress-pct');
            const fillEl = document.getElementById('approval-progress-fill');
            const statsComp = document.getElementById('stats-completed');
            const statsPend = document.getElementById('stats-pending');

            if (pctEl) pctEl.innerText = pct + '%';
            if (fillEl) gsap.to(fillEl, { width: pct + '%', duration: 0.5 });
            if (statsComp) statsComp.innerText = completed;
            if (statsPend) statsPend.innerText = total - completed;
        }

        // === Stage 06: Construction Execution Logic ===
        let executionData = <?php echo !empty($project_details['execution']) ? json_encode($project_details['execution']) : "{
            phases: [
                { id: 'site_prep', name: 'Site Preparation', progress: 0, status: 'pending', start: '', end: '' },
                { id: 'excavation', name: 'Excavation & Foundation', progress: 0, status: 'pending', start: '', end: '' },
                { id: 'rcc', name: 'RCC Structure', progress: 0, status: 'pending', start: '', end: '' },
                { id: 'masonry', name: 'Masonry Work', progress: 0, status: 'pending', start: '', end: '' },
                { id: 'plastering', name: 'Plastering', progress: 0, status: 'pending', start: '', end: '' },
                { id: 'flooring', name: 'Flooring', progress: 0, status: 'pending', start: '', end: '' },
                { id: 'mep', name: 'Electrical & Plumbing', progress: 0, status: 'pending', start: '', end: '' },
                { id: 'finishing', name: 'Painting & Finishing', progress: 0, status: 'pending', start: '', end: '' }
            ],
            materials: [],
            issues: [],
            logs: [],
            contractor_name: '',
            labor_status: 'compliant',
            site_instructions: '',
            confirmed: false
        }"; ?>;

        function updateExecutionData(key, val) {
            executionData[key] = val;
            if (key === 'confirmed') updateConfirmationState();
        }

        function updatePhase(idx, field, val) {
            executionData.phases[idx][field] = val;
            if (field === 'progress') {
                if (val == 100) executionData.phases[idx].status = 'completed';
                else if (val > 0) executionData.phases[idx].status = 'progress';
                else executionData.phases[idx].status = 'pending';

                // Incremental UI update (no re-render to avoid focus loss)
                const lb = document.getElementById(`phase-pct-label-${idx}`);
                if (lb) lb.innerText = val + '%';
                const gt = document.getElementById(`gantt-pct-${idx}`);
                if (gt) gt.innerText = val + '%';
                const bar = document.getElementById(`gantt-bar-${idx}`);
                if (bar) bar.style.width = val + '%';
                const step = document.getElementById(`phase-step-${idx}`);
                if (step) step.className = `step-indicator step-${executionData.phases[idx].status}`;

                // Update overall
                const total = executionData.phases.reduce((acc, p) => acc + parseInt(p.progress), 0);
                const overall = Math.round(total / executionData.phases.length);
                document.getElementById('overall-execution-pct').innerText = overall + '%';
                document.getElementById('overall-execution-fill').style.width = overall + '%';
            }
        }

        function renderExecutionUI() {
            const container = document.getElementById('execution-phases-container');
            const gantt = document.getElementById('timeline-gantt');
            const select = document.getElementById('log-phase-select');
            
            if (!container || !gantt) return;

            // Render Phases
            container.innerHTML = executionData.phases.map((p, i) => `
                <div class="phase-card">
                    <div class="phase-header">
                        <div>
                            <span class="step-indicator step-${p.status}" id="phase-step-${i}"></span>
                            <span class="phase-title">${p.name}</span>
                        </div>
                        <div style="font-size: 0.8rem; font-weight: 800; color: var(--accent);" id="phase-pct-label-${i}">${p.progress}%</div>
                    </div>
                    <div class="input-grid" style="grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                        <div class="input-group">
                            <label>Start Date</label>
                            <input type="date" class="c-input" value="${p.start}" onchange="updatePhase(${i}, 'start', this.value)">
                        </div>
                        <div class="input-group">
                            <label>End Date</label>
                            <input type="date" class="c-input" value="${p.end}" onchange="updatePhase(${i}, 'end', this.value)">
                        </div>
                        <div class="input-group">
                            <label>Progress</label>
                            <input type="range" class="c-input" style="padding:0; margin-top:8px" min="0" max="100" value="${p.progress}" oninput="updatePhase(${i}, 'progress', this.value)">
                        </div>
                    </div>
                </div>
            `).join('');

            // Render Gantt
            gantt.innerHTML = executionData.phases.map((p, i) => `
                <div class="gantt-row">
                    <div class="gantt-label">${p.name}</div>
                    <div class="gantt-bar-bg">
                        <div class="gantt-bar-fill" id="gantt-bar-${i}" style="width: ${p.progress}%"></div>
                    </div>
                    <div style="font-size: 0.65rem; font-weight: 800; width: 30px;" id="gantt-pct-${i}">${p.progress}%</div>
                </div>
            `).join('');

            // Update Log Select
            if (select) {
                const currentVal = select.value;
                select.innerHTML = executionData.phases.map(p => `<option value="${p.id}" ${currentVal === p.id ? 'selected' : ''}>${p.name}</option>`).join('');
            }

            // Overall Progress
            const total = executionData.phases.reduce((acc, p) => acc + parseInt(p.progress), 0);
            const overall = Math.round(total / executionData.phases.length);
            
            document.getElementById('overall-execution-pct').innerText = overall + '%';
            gsap.to('#overall-execution-fill', { width: overall + '%', duration: 1 });
            
            renderMaterialUsage();
            renderIssues();
        }

        function renderMaterialUsage() {
            const builtUp = surveyData.total_area * (gatherData.floors || 1);
            const boq = [
                { name: 'Cement', est: Math.round(builtUp * 0.45), unit: 'Bags' },
                { name: 'Steel', est: (builtUp * 3.8 / 1000).toFixed(2), unit: 'Tons' },
                { name: 'Bricks', est: Math.round(builtUp * 18), unit: 'Pcs' },
                { name: 'Sand', est: Math.round(builtUp * 1.2), unit: 'Cu.Ft' }
            ];

            const tbody = document.getElementById('material-usage-body');
            if (tbody) {
                tbody.innerHTML = boq.map((m, i) => {
                    const actual = executionData.materials[i] || 0;
                    const variance = actual > 0 ? ((actual - m.est) / m.est * 100).toFixed(1) : 0;
                    const vClass = variance > 5 ? 'variance-critical' : 'variance-ok';
                    return `
                        <tr>
                            <td style="padding: 1rem; font-weight: 700;">${m.name}</td>
                            <td style="padding: 1rem; font-family: 'JetBrains Mono';">${m.est} ${m.unit}</td>
                            <td style="padding: 1rem;">
                                <input type="number" class="c-input" style="padding: 0.4rem; font-size: 0.8rem;" value="${actual}" oninput="updateActualMaterial(${i}, this.value, ${m.est})">
                            </td>
                            <td style="padding: 1rem;">
                                <span class="variance-alert ${vClass}" id="variance-${i}">${variance}%</span>
                            </td>
                        </tr>
                    `;
                }).join('');
            }
        }

        function updateActualMaterial(idx, val, est) {
            executionData.materials[idx] = parseFloat(val) || 0;
            const variance = val > 0 ? ((val - est) / est * 100).toFixed(1) : 0;
            const vEl = document.getElementById(`variance-${idx}`);
            if (vEl) {
                vEl.innerText = variance + '%';
                vEl.className = `variance-alert ${variance > 5 ? 'variance-critical' : 'variance-ok'}`;
            }
        }

        function addExecutionIssue() {
            const reason = prompt("Enter Issue Detail / Delay Reason:");
            if (reason) {
                executionData.issues.push({
                    reason: reason,
                    status: 'pending',
                    date: new Date().toLocaleDateString()
                });
                renderIssues();
            }
        }

        function renderIssues() {
            const container = document.getElementById('issue-list-container');
            if (container) {
                container.innerHTML = executionData.issues.map((iss, i) => `
                    <div class="issue-card">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <span style="font-size: 0.8rem; font-weight: 700; color: #991b1b;">${iss.reason}</span>
                            <button onclick="resolveIssue(${i})" style="font-size: 0.6rem; background: #991b1b; color: white; border: none; padding: 2px 6px; border-radius: 4px; cursor: pointer;">RESOLVE</button>
                        </div>
                        <div style="font-size: 0.65rem; color: #dc2626; margin-top: 4px;">Log Date: ${iss.date} | Status: ${iss.status.toUpperCase()}</div>
                    </div>
                `).join('');
            }
        }

        function resolveIssue(i) {
            executionData.issues[i].status = 'resolved';
            renderIssues();
        }

        function submitQuickLog() {
            const phaseId = document.getElementById('log-phase-select').value;
            const logText = document.getElementById('quick-log-text').value;
            if (!logText) return;

            executionData.logs.push({
                phase: phaseId,
                text: logText,
                date: new Date().toLocaleString()
            });

            document.getElementById('quick-log-text').value = '';
            alert("Site log recorded successfully.");
        }

        function downloadExecutionReport() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            doc.setFillColor(26, 46, 35);
            doc.rect(0, 0, 210, 40, 'F');
            doc.setTextColor(255, 255, 255);
            doc.text("CONSTRUCTA | SITE EXECUTION REPORT", 14, 25);
            
            doc.setTextColor(0);
            const executionSummary = executionData.phases.map(p => [p.name, p.status.toUpperCase(), p.progress + '%', p.start || '-', p.end || '-']);
            
            doc.autoTable({
                startY: 50,
                head: [['Phase', 'Status', 'Progress', 'Start Date', 'End Date']],
                body: executionSummary,
                theme: 'grid'
            });

            doc.text("Material Variance Log", 14, doc.lastAutoTable.finalY + 15);
            const materialLog = [['Material', 'Consumption Variance']]; // Simplified for report
            
            doc.save(`Execution_Report_PJ${projectID}.pdf`);
        }

        function updateConfirmationState() {
             // Sync UI checkbox if needed, but here we just store
        }

        // Re-inject the stagesList and other globals properly after this block
        // === Stage 09: Handover & Closure Logic ===
        let handoverData = <?php echo !empty($project_details['handover']) ? json_encode($project_details['handover']) : "{
            check_completed: false,
            check_defects: false,
            check_cleaning: false,
            check_fixtures: false,
            walkthrough_notes: '',
            possession_date: '',
            utility_status: 'pending',
            keys_handed: false,
            variation_cost: 0,
            dlp_months: 12,
            is_closed: false
        }"; ?>;

        function updateHandoverData(key, val) {
            handoverData[key] = val;
        }

        function calculateSettlement(variationVal) {
            const variation = parseFloat(variationVal) || 0;
            const estimate = <?php echo (int)($project_details['estimation']['total_cost'] ?? 0); ?>;
            const finalTotal = estimate + variation;
            
            handoverData.variation_cost = variation;

            document.getElementById('settle-variations').innerText = variation.toLocaleString();
            document.getElementById('settle-final').innerText = finalTotal.toLocaleString();
            
            // Assume fully paid for simulation, but could link to payments table
            document.getElementById('settle-balance').innerText = '₹0';
        }

        // === PROJECT CLOSURE MODAL SYSTEM ===
        let closureStep = 1;
        let closureChecklist = {
            construction: false,
            documents: false,
            walkthrough: false,
            payments: false
        };
        let engineerName = "<?php echo htmlspecialchars($_SESSION['username'] ?? 'Engineer'); ?>";
        let verificationPassed = false;

        async function finalizeProjectClosure() {
            // Open modal and start Step 1
            closureStep = 1;
            document.getElementById('closureModalOverlay').classList.add('active');
            showClosureStep(1);
        }

        function showClosureStep(step) {
            closureStep = step;
            updateStepIndicator();
            
            const content = document.getElementById('closureModalContent');
            
            if (step === 1) {
                // Step 1: Legal Confirmation
                content.innerHTML = `
                    <div class="closure-modal-header">
                        <h2>Confirm Permanent Project Closure</h2>
                        <p>Please review and confirm all critical requirements before proceeding with project closure.</p>
                    </div>
                    <div class="closure-modal-body">
                        <div class="closure-warning-box">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div>
                                <h4>Important Notice</h4>
                                <p>Once closed, this project will become <strong>read-only</strong> and cannot be reopened. Your engineering responsibility ends except for the defect liability period.</p>
                            </div>
                        </div>

                        <div class="closure-checklist">
                            <div class="closure-checklist-item" onclick="toggleClosureCheck('construction', this)">
                                <input type="checkbox" id="check-construction" onchange="toggleClosureCheck('construction', this.parentElement)">
                                <label for="check-construction">
                                    All construction work completed as per approved plans
                                    <small>Structural work, finishes, MEP installations verified</small>
                                </label>
                            </div>

                            <div class="closure-checklist-item" onclick="toggleClosureCheck('documents', this)">
                                <input type="checkbox" id="check-documents" onchange="toggleClosureCheck('documents', this.parentElement)">
                                <label for="check-documents">
                                    All project documents uploaded and archived
                                    <small>Drawings, approvals, certificates, test reports</small>
                                </label>
                            </div>

                            <div class="closure-checklist-item" onclick="toggleClosureCheck('walkthrough', this)">
                                <input type="checkbox" id="check-walkthrough" onchange="toggleClosureCheck('walkthrough', this.parentElement)">
                                <label for="check-walkthrough">
                                    Final walkthrough completed with homeowner
                                    <small>Snag list cleared, site cleaned, keys handed over</small>
                                </label>
                            </div>

                            <div class="closure-checklist-item" onclick="toggleClosureCheck('payments', this)">
                                <input type="checkbox" id="check-payments" onchange="toggleClosureCheck('payments', this.parentElement)">
                                <label for="check-payments">
                                    No pending payments or unresolved defects
                                    <small>Financial settlement completed, no outstanding issues</small>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="closure-modal-footer">
                        <button class="closure-btn closure-btn-secondary" onclick="closeClosureModal()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button class="closure-btn closure-btn-primary" id="step1Continue" disabled onclick="showClosureStep(2)">
                            Continue <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                `;
            } else if (step === 2) {
                // Step 2: Engineer Identity Verification
                content.innerHTML = `
                    <div class="closure-modal-header">
                        <h2>Engineer Identity Verification</h2>
                        <p>Confirm your identity to authorize this permanent action.</p>
                    </div>
                    <div class="closure-modal-body">
                        <div class="closure-verification-input">
                            <label>Type your name to certify completion</label>
                            <input type="text" id="engineerNameInput" placeholder="Enter your name" 
                                   oninput="validateEngineerName(this.value)" autocomplete="off">
                            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.5rem;">
                                ${engineerName ? `Expected: <strong>${engineerName}</strong> (or at least 3 characters)` : 'Type at least 3 characters to continue'}
                            </p>
                        </div>

                        <div class="closure-warning-box">
                            <i class="fas fa-shield-alt"></i>
                            <div>
                                <h4>Digital Declaration</h4>
                                <p>By entering your name, you certify that all construction work has been completed to professional standards and all documentation is accurate.</p>
                            </div>
                        </div>
                    </div>
                    <div class="closure-modal-footer">
                        <button class="closure-btn closure-btn-secondary" onclick="showClosureStep(1)">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                        <button class="closure-btn closure-btn-primary" id="step2Continue" disabled onclick="showClosureStep(3)">
                            Verify & Continue <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                `;
            } else if (step === 3) {
                // Step 3: Final Irreversible Warning
                content.innerHTML = `
                    <div class="closure-modal-header">
                        <h2>Final Confirmation Required</h2>
                        <p>This is your last chance to review before permanent closure.</p>
                    </div>
                    <div class="closure-modal-body">
                        <div class="closure-warning-box" style="border-color: #ef4444; background: #fef2f2;">
                            <i class="fas fa-exclamation-circle" style="color: #dc2626;"></i>
                            <div>
                                <h4 style="color: #991b1b;">This Action Cannot Be Undone</h4>
                                <p style="color: #991b1b;">The project will be permanently archived. All workspace access will be locked. Only the handover certificate and defect liability tracking will remain active.</p>
                            </div>
                        </div>

                        <div style="text-align: center; margin: 2rem 0;">
                            <p style="font-size: 1.1rem; font-weight: 600; color: var(--text-main); margin-bottom: 2rem;">
                                Click the button below to confirm permanent closure
                            </p>
                            <button class="closure-btn closure-btn-danger" style="font-size: 1.1rem; padding: 1.25rem 2.5rem;" onclick="executeProjectClosure()">
                                <i class="fas fa-lock"></i> YES, CLOSE PROJECT PERMANENTLY
                            </button>
                        </div>
                    </div>
                    <div class="closure-modal-footer">
                        <button class="closure-btn closure-btn-secondary" onclick="closeClosureModal()">
                            <i class="fas fa-times"></i> Cancel & Go Back
                        </button>
                    </div>
                `;
            } else if (step === 4) {
                // Step 4: Success & Feedback
                content.innerHTML = `
                    <div class="closure-success-animation">
                        <div class="closure-success-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <h3>Project Successfully Closed</h3>
                        <p>All records have been archived and the homeowner has been notified.<br>
                        Your project closure certificate is being generated.</p>

                        <div class="closure-action-cards">
                            <div class="closure-action-card" onclick="downloadClosureCertificate()">
                                <i class="fas fa-file-pdf"></i>
                                <h4>Download Closure Certificate</h4>
                            </div>
                            <div class="closure-action-card" onclick="downloadHandoverDocuments()">
                                <i class="fas fa-file-archive"></i>
                                <h4>Download Handover Docs (ZIP)</h4>
                            </div>
                        </div>

                        <button class="closure-btn closure-btn-primary" style="margin-top: 2rem; width: 100%;" onclick="redirectToDashboard()">
                            <i class="fas fa-home"></i> Return to Engineer Dashboard
                        </button>
                    </div>
                `;
            }
        }

        function updateStepIndicator() {
            document.querySelectorAll('.closure-step-dot').forEach((dot, index) => {
                const stepNum = index + 1;
                dot.classList.remove('active', 'completed');
                if (stepNum < closureStep) {
                    dot.classList.add('completed');
                } else if (stepNum === closureStep) {
                    dot.classList.add('active');
                }
            });
        }

        function toggleClosureCheck(key, element) {
            const checkbox = element.querySelector('input[type="checkbox"]');
            checkbox.checked = !checkbox.checked;
            closureChecklist[key] = checkbox.checked;
            
            if (checkbox.checked) {
                element.classList.add('checked');
            } else {
                element.classList.remove('checked');
            }

            // Enable/disable continue button
            const allChecked = Object.values(closureChecklist).every(v => v === true);
            document.getElementById('step1Continue').disabled = !allChecked;
        }

        function validateEngineerName(input) {
            const inputField = document.getElementById('engineerNameInput');
            const continueBtn = document.getElementById('step2Continue');
            
            // Trim and normalize input
            const normalizedInput = input.trim().toLowerCase();
            const normalizedEngineerName = engineerName.trim().toLowerCase();
            
            // Debug logging
            console.log('Input:', normalizedInput);
            console.log('Expected:', normalizedEngineerName);
            
            // More flexible validation: accept if input matches at least 3 characters
            // or if engineerName is empty (fallback for testing)
            const isValid = normalizedEngineerName === '' || 
                           normalizedInput === normalizedEngineerName ||
                           (normalizedInput.length >= 3 && normalizedEngineerName.includes(normalizedInput));
            
            if (isValid) {
                inputField.classList.add('valid');
                continueBtn.disabled = false;
                verificationPassed = true;
            } else {
                inputField.classList.remove('valid');
                continueBtn.disabled = true;
                verificationPassed = false;
            }
        }

        let holdTimer = null;
        let holdProgress = 0;

        function startHoldConfirm() {
            const btn = document.getElementById('holdConfirmBtn');
            const progressBar = btn.querySelector('.hold-progress');
            
            holdProgress = 0;
            holdTimer = setInterval(() => {
                holdProgress += 4; // 100% in 2.5 seconds (25 intervals * 4%)
                progressBar.style.width = holdProgress + '%';
                
                if (holdProgress >= 100) {
                    clearInterval(holdTimer);
                    executeProjectClosure();
                }
            }, 100);
        }

        function cancelHoldConfirm() {
            if (holdTimer) {
                clearInterval(holdTimer);
                holdTimer = null;
            }
            const btn = document.getElementById('holdConfirmBtn');
            if (btn) {
                const progressBar = btn.querySelector('.hold-progress');
                progressBar.style.width = '0%';
            }
            holdProgress = 0;
        }

        async function executeProjectClosure() {
            // Animate progress bar to 100%
            const maxStageIdx = stagesList.length - 1;
            gsap.to("#progress-bar", { width: "100%", duration: 2, ease: "power2.out" });
            gsap.to({ val: parseInt(document.getElementById('progress-text').innerText) || 0 }, {
                val: 100,
                duration: 2,
                snap: { val: 1 },
                onUpdate: function() {
                    document.getElementById('progress-text').innerText = Math.floor(this.targets()[0].val) + "%";
                }
            });

            // Mark as closed in data
            handoverData.is_closed = true;

            // Backend call to mark project as completed
            const nextIdx = stagesList.length; // Stage 7 marks completion
            
            try {
                const response = await fetch('backend/update_project_stage.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ project_id: projectID, new_stage: nextIdx })
                });
                
                const result = await response.json();
                if (result.success) {
                    // Redirect immediately to dashboard
                    window.location.href = 'engineer.php';
                } else {
                    alert("Closure failed: " + result.message);
                    closeClosureModal();
                }
            } catch (error) {
                console.error("Closure error:", error);
                alert("Server error occurred during project closure.");
                closeClosureModal();
            }
        }

        function closeClosureModal() {
            document.getElementById('closureModalOverlay').classList.remove('active');
            closureStep = 1;
            closureChecklist = { construction: false, documents: false, walkthrough: false, payments: false };
            verificationPassed = false;
        }

        function downloadClosureCertificate() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Header
            doc.setFillColor(26, 46, 35);
            doc.rect(0, 0, 210, 50, 'F');
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(24);
            doc.setFont(undefined, 'bold');
            doc.text("PROJECT CLOSURE CERTIFICATE", 105, 25, { align: 'center' });
            doc.setFontSize(12);
            doc.text("CONSTRUCTA ENGINEERING SERVICES", 105, 35, { align: 'center' });
            
            // Body
            doc.setTextColor(0, 0, 0);
            doc.setFontSize(14);
            doc.text("Certificate of Completion", 14, 70);
            
            doc.setFontSize(10);
            doc.text(`Project ID: PJ-${projectID}`, 14, 85);
            doc.text(`Project Title: <?php echo addslashes($project['project_title']); ?>`, 14, 95);
            doc.text(`Completion Date: ${new Date().toLocaleDateString()}`, 14, 105);
            doc.text(`Engineer: ${engineerName}`, 14, 115);
            
            doc.text("This certifies that the above project has been successfully completed,", 14, 135);
            doc.text("handed over to the homeowner, and all documentation archived.", 14, 145);
            
            // Signature
            doc.setFontSize(12);
            doc.text("_________________________", 14, 180);
            doc.setFontSize(10);
            doc.text("Engineer Signature", 14, 190);
            
            // Footer
            doc.setFontSize(8);
            doc.text(`Generated on ${new Date().toLocaleString()} | Constructa Platform`, 14, 280);
            
            doc.save(`Project_Closure_Certificate_PJ${projectID}.pdf`);
        }

        function downloadHandoverDocuments() {
            alert("Handover documents ZIP generation initiated. This feature will be available in the next update.");
        }

        function redirectToDashboard() {
            window.location.href = 'engineer.php';
        }

        function downloadHandoverReport() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            // Implementation similar to execution report
            doc.text("CONSTRUCTA | PROJECT HANDOVER CERTIFICATE", 14, 25);
            doc.save(`Handover_Certificate_PJ${projectID}.pdf`);
        }

        // Re-inject the downloadApprovalReport etc.
        function downloadApprovalReport() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Header
            doc.setFillColor(26, 46, 35);
            doc.rect(0, 0, 210, 40, 'F');
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(22);
            doc.text("CONSTRUCTA", 14, 25);
            doc.setFontSize(10);
            doc.text("LEGAL APPROVAL & COMPLIANCE REPORT", 14, 32);
            
            // Project Context
            doc.setTextColor(26, 46, 35);
            doc.setFontSize(12);
            doc.text("PROJECT IDENTIFICATION", 14, 50);
            
            const contextData = [
                ["Project ID", `PJ-${projectID}`],
                ["Property Location", gatherData.location || "Not Specified"],
                ["Engineer ID", approvalsData.engineer_license || "N/A"],
                ["Generated On", new Date().toLocaleString()]
            ];
            
            doc.autoTable({
                startY: 55,
                body: contextData,
                theme: 'plain',
                styles: { fontSize: 9, cellPadding: 2 }
            });

            // Approval Matrix
            doc.text("MANDATORY CLEARANCES & STATUS", 14, doc.lastAutoTable.finalY + 15);
            
            const tableData = [
                ["1. Plan Approval", approvalsData.plan_approval.toUpperCase(), approvalsData.plan_remarks || "Standard building code compliance."],
                ["2. Structural Safety", approvalsData.structural_safety.toUpperCase(), `Certified by Engineer ID: ${approvalsData.engineer_license || 'PENDING'}`],
                ["3. Land Verification", approvalsData.land_verification.toUpperCase(), "Sale deed & land-use cross-verified."],
                ["4. Water Supply", approvalsData.utility_water.toUpperCase(), "Municipal supply connection approval."],
                ["5. Electricity", approvalsData.utility_electricity.toUpperCase(), "Domestic power load clearance."],
                ["6. Sewerage", approvalsData.utility_sewer.toUpperCase(), "Drainage system network approval."]
            ];

            doc.autoTable({
                startY: doc.lastAutoTable.finalY + 20,
                head: [['Clearance Type', 'Status', 'Legal Context / Remarks']],
                body: tableData,
                theme: 'grid',
                headStyles: { fillColor: [26, 46, 35] },
                columnStyles: {
                    1: { fontStyle: 'bold' }
                }
            });

            // Special Clearances (Conditional)
            doc.text("SPECIAL PERMISSIONS & NOCs", 14, doc.lastAutoTable.finalY + 15);
            const nocData = [
                ["Fire Authority NOC", approvalsData.nocs.fire_noc ? "REQUIRED & OBTAINED" : "NOT APPLICABLE"],
                ["Airport Authority Clearance", approvalsData.nocs.airport_noc ? "REQUIRED & OBTAINED" : "NOT APPLICABLE"],
                ["Environmental Clearance", approvalsData.nocs.env_noc ? "REQUIRED & OBTAINED" : "NOT APPLICABLE"]
            ];

            doc.autoTable({
                startY: doc.lastAutoTable.finalY + 20,
                body: nocData,
                theme: 'striped',
                styles: { fontSize: 9 }
            });

            // Financial Summary
            doc.text("FEE SETTLEMENT", 14, doc.lastAutoTable.finalY + 15);
            const financeData = [
                ["Total Approval Fees", `INR ${parseFloat(approvalsData.fee_amount).toLocaleString()}`],
                ["Payment Status", approvalsData.payment_status.toUpperCase()]
            ];

            doc.autoTable({
                startY: doc.lastAutoTable.finalY + 20,
                body: financeData,
                theme: 'grid',
                headStyles: { fillColor: [212, 175, 55] }
            });

            // Final Certification
            doc.setFontSize(10);
            doc.setTextColor(100);
            doc.text("ENGINEER CERTIFICATION:", 14, doc.lastAutoTable.finalY + 25);
            doc.setFontSize(8);
            doc.text(`I hereby certify that all legal approvals listed above have been verified by me. 
Current Readiness: ${approvalsData.confirmed ? 'CERTIFIED FOR CONSTRUCTION' : 'PENDING FINAL REVIEW'}`, 14, doc.lastAutoTable.finalY + 30);

            // Footer
            doc.setDrawColor(200);
            doc.line(14, 280, 196, 280);
            doc.setFontSize(7);
            doc.text("Constructa Legal Vault - Audit Log Ref: " + Math.random().toString(36).substr(2, 9).toUpperCase(), 14, 285);

            doc.save(`Approval_Report_PJ${projectID}.pdf`);
        }

        // Pass stages data to JS
        const stagesList = <?php echo json_encode($stages); ?>;
        const projectID = <?php echo (int)$project_id; ?>;
        let projectStageIdx = <?php echo (int)$current_project_stage; ?>;
        let viewStageIdx = <?php echo (int)$current_stage_idx; ?>;
        
        // --- Live Preview Updates ---
        let projectData = {
            gathering: <?php echo json_encode($project_details['gathering'] ?? []); ?>,
            survey: <?php echo json_encode($project_details['survey'] ?? []); ?>,
            planning: <?php echo json_encode($project_details['planning'] ?? []); ?>
        };

        function updateGatherPreview(key, val) {
            // Live validation logic
            const group = event.target.closest('.input-group');
            let isValid = true;
            let finalVal = val;

            // Trim string values
            if (typeof val === 'string') val = val.trim();

            if (key === 'plot_area') {
                const area = parseFloat(val);
                if (!val || isNaN(area) || area < 100 || area > 1000000) isValid = false;
                else finalVal = area; 
            } else if (key === 'floors') {
                let fl = parseFloat(val);
                if (!val || isNaN(fl) || fl < 1) {
                    isValid = false;
                } else if (fl > 3) {
                    // Auto-limit to 3
                    fl = 3;
                    finalVal = 3;
                    // Update input value directly to reflect correction
                    if (event.target) event.target.value = 3;
                } else {
                    finalVal = fl;
                }
            } else if (key === 'budget') {
                const bg = parseFloat(val);
                if (!val || isNaN(bg) || bg < 500000) isValid = false;
                else finalVal = bg;
            } else {
                // Generic non-empty check for other fields
                if (!val) isValid = false;
            }

            if (group) {
                if (isValid) {
                    group.classList.remove('invalid');
                    group.classList.add('valid');
                } else {
                    group.classList.remove('valid');
                    group.classList.add('invalid');
                }
            }

            if (isValid) {
                projectData.gathering[key] = finalVal;
                // Update 3D model properties if relevant
                if (key === 'plot_area') updateHouseGeometry(); 
                if (key === 'floors') updateHouseGeometry();
            } else {
                // If invalid, clear from data to prevent saving bad state if submitted
                delete projectData.gathering[key];
            }
        }

        function updateSurveyPreview(key, val) {
            const group = event.target.closest('.input-group');
            let isValid = true;
            
            if (typeof val === 'string') val = val.trim();

            // Dimension checks
            if (['f_width', 'r_width', 'l_depth', 'r_depth'].includes(key)) {
                const dim = parseFloat(val);
                if (!val || isNaN(dim) || dim < 5) isValid = false;
                else val = dim;
            } else if (key === 'total_area') {
                const area = parseFloat(val);
                if (!val || isNaN(area) || area < 100) isValid = false;
                else val = area;
            } else if (key === 'road_width') {
                const rw = parseFloat(val);
                if (!val || isNaN(rw) || rw <= 0) isValid = false;
                else val = rw;
            } else if (key === 'constraints' || key === 'road_type') {
                // Optional fields? No, strict validation requested prevents empty
                if (!val) isValid = false; 
            }

            if (group) {
                if (isValid) {
                    group.classList.remove('invalid');
                    group.classList.add('valid');
                } else {
                    group.classList.remove('valid');
                    group.classList.add('invalid');
                }
            }
            
            if (isValid) {
                projectData.survey[key] = val;
                
                // Auto-calc logic for survey dimensions to total area (approx)
                if (['f_width', 'r_width', 'l_depth', 'r_depth'].includes(key)) {
                    calcSurveyArea();
                }
                if (key === 'total_area') {
                    document.getElementById('calc-site-area').innerText = val + " sq.ft (Manual)";
                }
            } else {
                delete projectData.survey[key];
            }
        }

        // === Interactions ===
        async function approveCurrentStage() {

            if (projectStageIdx >= stagesList.length - 1) {
                alert("Project is already at final stage!");
                return;
            }

            const nextIdx = projectStageIdx + 1;
            
            try {
                const response = await fetch('backend/update_project_stage.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ project_id: projectID, new_stage: nextIdx })
                });
                
                const result = await response.json();
                if (result.success) {
                    // Update actual progress
                    projectStageIdx = nextIdx;
                    viewStageIdx = nextIdx;
                    
                    // Update progress bar (cap at 100%)
                    const maxStageIdx = stagesList.length - 1;
                    const actualProgress = Math.min(projectStageIdx, maxStageIdx);
                    const pct = Math.round((actualProgress / maxStageIdx) * 100);
                    
                    // Animate and then reload
                    gsap.to("#progress-bar", { width: pct + "%", duration: 1.0, ease: "power2.out" });
                    
                    let progressObj = { val: parseInt(document.getElementById('progress-text').innerText) || 0 };
                    gsap.to(progressObj, { 
                        val: pct, 
                        duration: 1.0, 
                        snap: { val: 1 },
                        onUpdate: function() { 
                            document.getElementById('progress-text').innerText = Math.floor(progressObj.val) + "%"; 
                        },
                        onComplete: function() {
                            alert("Stage completed! Moving to " + stagesList[nextIdx].label);
                            // Force reload to render validation/server-side content for the new stage
                            window.location.href = `engineer_workspace.php?id=${projectID}&view=${nextIdx}&internal=1`;
                        }
                    });

                } else {
                    alert("Error: " + result.message);
                }
            } catch (error) {
                console.error("Failed to update stage:", error);
                alert("Server error occurred while updating stage.");
            }
        }

        function updateStageUI(idx) {
            const stage = stagesList[idx];
            document.getElementById('stage-title').innerText = stage.label;
            document.getElementById('stage-meta').innerText = `Stage ${String(idx + 1).padStart(2, '0')} / Active`;
            document.getElementById('stage-desc').innerText = stage.desc;
            
            // Update Repo label
            const repoLabel = document.querySelector('.doc-repo span');
            if (repoLabel) repoLabel.innerText = `${stage.id.toUpperCase()}_DOCS_v1.0`;

            // Toggle Stage Specific Content
            const specificContent = document.getElementById('stage-specific-content');
            
            viewStageIdx = idx; // Update global state
            
            // Manage Sidebar Previews
            const gatherPreview = document.getElementById('gathering-preview');
            const surveyPreview = document.getElementById('survey-preview');
            const planningPreview = document.getElementById('planning-preview');
            const estimationPreview = document.getElementById('estimation-preview');
            
            if (gatherPreview) gatherPreview.style.display = (idx === 0) ? 'block' : 'none';
            if (surveyPreview) surveyPreview.style.display = (idx === 1) ? 'block' : 'none';
            if (planningPreview) planningPreview.style.display = (idx === 2) ? 'block' : 'none';
            if (estimationPreview) estimationPreview.style.display = (idx === 3) ? 'block' : 'none';
 
            // Refresh Documents for this stage
            const list = document.getElementById('stage-docs');
            if (list) list.innerHTML = '<div style="text-align: center; padding: 1rem; color: #999; font-size: 0.8rem;">Switching repository...</div>';
            loadDocuments();

            if (idx <= 6) {
                // For all core development stages, we rely on PHP rendering or specific JS initializers.
                if (idx !== viewStageIdx) {
                    window.location.href = `engineer_workspace.php?id=${projectID}&view=${idx}&internal=1`;
                    return;
                }
            } else {
                specificContent.innerHTML = `
                    <div class="workspace-grid">
                        <div class="tool-card">
                            <div class="tool-top">
                                <div class="tool-icon"><i class="fas fa-flask"></i></div>
                            </div>
                            <h3>Stage Utilities</h3>
                            <p style="color: var(--text-muted); font-size: 0.85rem;">Access tools specific to the current construction phase.</p>
                            <div style="margin-top: 1rem; padding: 1rem; background: #f8fafc; border-radius: 12px; font-size: 0.8rem;">
                                <i class="fas fa-info-circle"></i> No specific active tools for this stage.
                            </div>
                        </div>
                    </div>
                `;
            }

            updateHouseGeometry(idx);
        }

        function switchStage(id, idx, el) {
            // Redirection for all standard stage wizards (0-6)
            if (idx >= 0 && idx <= 6) {
                if (viewStageIdx !== idx) {
                    window.location.href = `engineer_workspace.php?id=${projectID}&view=${idx}&internal=1`;
                    return;
                }
            }

            document.querySelectorAll('.stage-entry').forEach(node => node.classList.remove('view-active'));
            el.classList.add('view-active');

            gsap.to("#main-content", { 
                opacity: 0, x: 20, duration: 0.3,
                onComplete: () => {
                    updateStageUI(idx);
                    gsap.to("#main-content", { opacity: 1, x: 0, duration: 0.5 });
                }
            });
        }

        // === PREMIUM ENTRY TRANSITION ===
        function initEntryTransition() {
            const params = new URLSearchParams(window.location.search);
            if (params.get('internal') === '1') {
                document.getElementById('entry-transition').style.display = 'none';
                return;
            }

            const canvas = document.getElementById('transition-canvas');
            const scene = new THREE.Scene();
            const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
            const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
            renderer.setSize(window.innerWidth, window.innerHeight);

            // Create a complex wireframe house
            const group = new THREE.Group();
            scene.add(group);

            const mat = new THREE.MeshBasicMaterial({ color: 0xffffff, wireframe: true, transparent: true, opacity: 0.1 });
            const goldMat = new THREE.MeshBasicMaterial({ color: 0xd4af37, wireframe: true, transparent: true, opacity: 0.4 });
            
            // Foundation
            const base = new THREE.Mesh(new THREE.BoxGeometry(4, 0.1, 4), goldMat);
            group.add(base);

            // Walls
            const walls = new THREE.Mesh(new THREE.BoxGeometry(3.5, 2.5, 3.5), mat);
            walls.position.y = 1.3;
            group.add(walls);

            // Roof
            const roof = new THREE.Mesh(new THREE.ConeGeometry(3, 2, 4), mat);
            roof.position.y = 3.5;
            roof.rotation.y = Math.PI / 4;
            group.add(roof);

            camera.position.z = 8;
            camera.position.y = 3;
            camera.lookAt(0, 1, 0);

            // Animate building up
            group.scale.set(0.1, 0.1, 0.1);
            gsap.to(group.scale, { x: 1, y: 1, z: 1, duration: 2, ease: "expo.out" });
            gsap.to(mat, { opacity: 0.4, duration: 2 });

            function anim() {
                if (document.getElementById('entry-transition').style.display === 'none') return;
                requestAnimationFrame(anim);
                group.rotation.y += 0.01;
                renderer.render(scene, camera);
            }
            anim();

            // Sequence out
            setTimeout(() => {
                const status = document.querySelector('.transition-status');
                if (status) status.innerText = "AUTHENTICATED";
                
                gsap.to(".transition-content", { 
                    opacity: 0, scale: 0.9, duration: 0.8, ease: "power2.inOut",
                    onComplete: () => {
                        gsap.to("#entry-transition", { 
                            y: "-100%", duration: 1.2, ease: "expo.inOut",
                            onComplete: () => {
                                document.getElementById('entry-transition').style.display = 'none';
                                // Reveal main content
                                gsap.from(".workspace-layout", { opacity: 0, x: -20, duration: 1, ease: "power3.out" });
                                gsap.from(".lifecycle-sidebar", { x: -100, opacity: 0, duration: 1, ease: "power3.out" });
                            }
                        });
                    }
                });
            }, 2500);
        }

        document.addEventListener('DOMContentLoaded', () => {
            initBackground();
            initHouseModel();
            
            // Premium 3D Tilt Effect on Wizard
            const wizard = document.querySelector('.gathering-wizard');
            if (wizard) {
                document.addEventListener('mousemove', (e) => {
                    const rect = wizard.getBoundingClientRect();
                    const x = (e.clientX - rect.left) / rect.width - 0.5;
                    const y = (e.clientY - rect.top) / rect.height - 0.5;
                    gsap.to(wizard, {
                        rotationY: x * 2,
                        rotationX: -y * 2,
                        duration: 0.5,
                        ease: "power2.out"
                    });
                });
            }

            // Progress calculation based on actual project progress (projectStageIdx)
            // Cap at 100% when project reaches or exceeds final stage
            const maxStageIdx = stagesList.length - 1; // 6 for 7 stages (0-6)
            const actualProgress = Math.min(projectStageIdx, maxStageIdx);
            const pct = Math.round((actualProgress / maxStageIdx) * 100);
            gsap.to("#progress-bar", { width: pct + "%", duration: 1.5, ease: "power2.out" });
            
            let progressObj = { val: 0 };
            gsap.to(progressObj, { 
                val: pct, 
                duration: 1.5, 
                snap: { val: 1 },
                onUpdate: function() { 
                    document.getElementById('progress-text').innerText = Math.floor(progressObj.val) + "%"; 
                }
            });

            // Make stage read-only if it's already completed
            if (viewStageIdx < projectStageIdx) {
                const content = document.getElementById('stage-specific-content');
                if (content) {
                    const inputs = content.querySelectorAll('input, select, textarea, button:not(.nav-btn):not(.btn-action)');
                    inputs.forEach(el => {
                        el.disabled = true;
                        if (el.classList.contains('quality-btn') || el.classList.contains('choice-card')) {
                            el.style.pointerEvents = 'none';
                            el.style.opacity = '0.7';
                        }
                    });
                }
            }

            if (viewStageIdx === 4) {
                // Initialize Approvals UI with saved data
                Object.keys(approvalsData).forEach(key => {
                    if (key === 'nocs' || key === 'confirmed') return;
                    
                    const el = document.querySelector(`select[onchange*="'${key}'"]`);
                    if (el) el.value = approvalsData[key];
                    
                    const txt = document.querySelector(`textarea[oninput*="'${key}'"], input[oninput*="'${key}'"]`);
                    if (txt) txt.value = approvalsData[key];

                    // Special handling for dashboard pills
                    const dash = document.getElementById(`status-${key.replace('_', '-')}`);
                    if (dash) {
                        dash.className = `status-pill status-${approvalsData[key].replace('_', '-')}`;
                        dash.innerText = approvalsData[key].replace('_', ' ').toUpperCase();
                    }
                });

                // Initialize NOCs
                Object.keys(approvalsData.nocs).forEach(key => {
                    const chk = document.querySelector(`input[onchange*="'${key}'"]`);
                    if (chk) chk.checked = approvalsData.nocs[key];
                });

                // Initialize Confirmation
                const confirmChk = document.getElementById('engineer-legal-confirm');
                if (confirmChk) confirmChk.checked = approvalsData.confirmed;

                calculateApprovalProgress();
                updateHouseGeometry(4);
            } else if (viewStageIdx === 1) {
                // Initialize Survey Side Panel
                Object.keys(surveyData).forEach(key => {
                    const el = document.getElementById(`val-${key}`);
                    if (el) {
                        let suffix = '';
                        if (key.includes('width') || key.includes('depth') || key === 'road_width') suffix = ' ft';
                        if (key === 'total_area') suffix = ' sq.ft';
                        el.innerText = (surveyData[key] || '-') + suffix;
                    }
                });
                calculateSurveyArea();
                updateHouseGeometry(1);
            } else if (viewStageIdx === 2) {
                // Initialize Planning Side Panel
                document.getElementById('val-floor_height').innerText = (planningData.floor_height || '10.5') + ' ft';
                document.getElementById('val-elevation_style').innerText = (planningData.elevation_style || 'modern').charAt(0).toUpperCase() + (planningData.elevation_style || 'modern').slice(1);
                document.getElementById('val-room_count').innerText = `${planningData.bedrooms || 1} Bed, ${planningData.bathrooms || 1} Bath`;

                // Initialize Wizard Inputs
                const bedroomsSlider = document.querySelector('input[oninput*="bedrooms"]');
                if (bedroomsSlider) {
                    bedroomsSlider.value = planningData.bedrooms || 1;
                    if (bedroomsSlider.nextElementSibling) bedroomsSlider.nextElementSibling.innerText = bedroomsSlider.value;
                }
                const bathroomsSlider = document.querySelector('input[oninput*="bathrooms"]');
                if (bathroomsSlider) {
                    bathroomsSlider.value = planningData.bathrooms || 1;
                    if (bathroomsSlider.nextElementSibling) bathroomsSlider.nextElementSibling.innerText = bathroomsSlider.value;
                }

                updateSpatialPreview();
                updateHouseGeometry(2);
            } else if (viewStageIdx === 0) {
                // Initialize Gathering Side Panel
                Object.keys(gatherData).forEach(key => {
                    const el = document.getElementById(`val-${key}`);
                    if (el) el.innerText = gatherData[key] || '-';
                });
                updateHouseGeometry(0);
            } else if (viewStageIdx === 3) {
                // Initialize Estimation panel
                calculateEstimation();
                updateHouseGeometry(3);
            } else if (viewStageIdx === 5) {
                renderExecutionUI();
                updateHouseGeometry(5);
            } else if (viewStageIdx === 6) {
                // Initialize Handover UI
                document.getElementById('chk-work-done').checked = handoverData.check_completed;
                document.getElementById('chk-defects-clear').checked = handoverData.check_defects;
                document.getElementById('chk-cleaned').checked = handoverData.check_cleaning;
                document.getElementById('chk-fixtures-ok').checked = handoverData.check_fixtures;
                document.getElementById('handover-remarks').value = handoverData.walkthrough_notes;
                document.getElementById('possession-date').value = handoverData.possession_date;
                document.getElementById('utility-status').value = handoverData.utility_status;
                document.getElementById('chk-keys').checked = handoverData.keys_handed;
                document.getElementById('variation-input').value = handoverData.variation_cost;
                document.getElementById('dlp-duration').value = handoverData.dlp_months;
                calculateSettlement(handoverData.variation_cost);
                updateHouseGeometry(6);
            }

            loadDocuments();
        });
        // === Document Management Logic ===
        async function loadDocuments() {
            try {
                const response = await fetch(`backend/get_project_documents.php?project_id=${projectID}&stage_idx=${viewStageIdx}`);
                const result = await response.json();
                
                const list = document.getElementById('stage-docs');
                if (result.success) {
                    if (result.documents.length === 0) {
                        list.innerHTML = '<div style="text-align: center; padding: 1rem; color: #999; font-size: 0.8rem;">No documents uploaded for this stage.</div>';
                    } else {
                        list.innerHTML = result.documents.map(doc => `
                            <div class="doc-item" id="doc-${doc.id}">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <i class="fas ${getFileIcon(doc.file_name)}" style="font-size: 1.2rem; color: ${getIconColor(doc.file_name)};"></i>
                                    <div>
                                        <a href="${doc.file_path}" target="_blank" download="${doc.file_name}" style="font-weight: 600; text-decoration: none; color: inherit; font-size: 0.85rem;">${doc.file_name}</a>
                                        <div style="font-size: 0.65rem; color: #999;">${(doc.file_size / 1024 / 1024).toFixed(2)} MB • ${new Date(doc.uploaded_at).toLocaleDateString()}</div>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="${doc.file_path}" download="${doc.file_name}" class="doc-action-btn download" style="color: var(--primary); padding: 8px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.3s;" title="Download">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <button onclick="deleteDocument(${doc.id})" class="doc-action-btn delete" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 8px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.3s;" title="Delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                        `).join('');
                    }
                }
            } catch (error) {
                console.error("Load docs error:", error);
            }
        }

        function getFileIcon(filename) {
            const ext = filename.split('.').pop().toLowerCase();
            if (ext === 'pdf') return 'fa-file-pdf';
            if (['jpg', 'jpeg', 'png'].includes(ext)) return 'fa-file-image';
            if (['dwg', 'dxf'].includes(ext)) return 'fa-file-code';
            return 'fa-file-alt';
        }

        function getIconColor(filename) {
            const ext = filename.split('.').pop().toLowerCase();
            if (ext === 'pdf') return '#ef4444';
            if (['jpg', 'jpeg', 'png'].includes(ext)) return '#3b82f6';
            if (['dwg', 'dxf'].includes(ext)) return '#8b5cf6';
            return '#6b7280';
        }

        function handleFileSelect(input) {
            if (input.files && input.files[0]) {
                uploadFile(input.files[0]);
            }
        }

        async function uploadFile(file) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('project_id', projectID);
            formData.append('stage_idx', viewStageIdx);

            const zone = document.getElementById('upload-zone');
            const originalContent = zone.innerHTML;
            zone.innerHTML = '<i class="fas fa-spinner fa-spin"></i> PREPARING SECURE UPLOAD...';
            zone.style.opacity = '0.6';

            try {
                const response = await fetch('backend/upload_project_document.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    loadDocuments();
                } else {
                    alert("Upload failed: " + result.message);
                }
            } catch (error) {
                console.error("Upload error:", error);
            } finally {
                zone.innerHTML = originalContent;
                zone.style.opacity = '1';
            }
        }

        async function deleteDocument(id) {
            if (!confirm("Are you sure you want to delete this document permanently?")) return;
            
            try {
                const response = await fetch('backend/delete_project_document.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ doc_id: id })
                });
                const result = await response.json();
                if (result.success) {
                    const el = document.getElementById(`doc-${id}`);
                    if (el) el.remove();
                    if (document.getElementById('stage-docs').children.length === 0) {
                        loadDocuments();
                    }
                } else {
                    alert("Delete failed: " + result.message);
                }
            } catch (error) {
                console.error("Delete error:", error);
            }
        }

        async function saveDraft() {
            const btn = event.currentTarget;
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> SYNCING VAULT...';
            btn.disabled = true;

            const payload = {
                project_id: projectID,
                details: {
                    gathering: gatherData,
                    survey: surveyData,
                    planning: planningData,
                    estimation: estimationData,
                    approvals: approvalsData,
                    execution: executionData,
                    handover: handoverData
                }
            };

            try {
                const response = await fetch('backend/save_project_details.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                if (result.success) {
                    gsap.to(btn, { background: '#4ade80', duration: 0.3 });
                    btn.innerHTML = '<i class="fas fa-check"></i> SAVED TO VAULT';
                    setTimeout(() => {
                        gsap.to(btn, { background: '#f8fafc', duration: 0.3 });
                        btn.innerHTML = originalHTML;
                        btn.disabled = false;
                    }, 2000);
                } else {
                    alert("Sync failed: " + result.message);
                }
            } catch (error) {
                console.error("Save draft error:", error);
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            }
        }

        // === VALIDATION FUNCTIONS FOR APPROVAL & PERMISSIONS ===
        function validateTextArea(textarea, maxLength, counterId) {
            const value = textarea.value;
            const length = value.length;
            const counter = document.getElementById(counterId);
            const errorDiv = document.getElementById(textarea.id + '_error');
            
            // Update counter
            if (counter) {
                counter.textContent = `${length} / ${maxLength}`;
                
                // Change color based on usage
                if (length > maxLength * 0.9) {
                    counter.style.color = '#ef4444'; // Red when near limit
                } else if (length > maxLength * 0.7) {
                    counter.style.color = '#f59e0b'; // Orange when approaching
                } else {
                    counter.style.color = '#64748b'; // Gray default
                }
            }
            
            // Visual feedback on textarea
            textarea.classList.remove('valid', 'invalid');
            if (length > 0) {
                if (length <= maxLength) {
                    textarea.classList.add('valid');
                    textarea.style.borderColor = '#10b981';
                    if (errorDiv) errorDiv.style.display = 'none';
                } else {
                    textarea.classList.add('invalid');
                    textarea.style.borderColor = '#ef4444';
                    if (errorDiv) {
                        errorDiv.style.display = 'block';
                        errorDiv.querySelector('span').textContent = 'Character limit exceeded';
                    }
                }
            } else {
                textarea.style.borderColor = '';
                if (errorDiv) errorDiv.style.display = 'none';
            }
            
            // Call original update function if it exists
            if (typeof updateApprovalData === 'function') {
                updateApprovalData('plan_remarks', value);
            }
        }
        
        function validateFileNo(input, counterId) {
            const value = input.value;
            const length = value.length;
            const counter = document.getElementById(counterId);
            const errorDiv = document.getElementById(input.id + '_error');
            
            // Update counter
            if (counter) {
                counter.textContent = `${length} / 100`;
                
                // Change color based on usage
                if (length > 90) {
                    counter.style.color = '#ef4444';
                } else if (length > 70) {
                    counter.style.color = '#f59e0b';
                } else {
                    counter.style.color = '#64748b';
                }
            }
            
            // Visual feedback
            input.classList.remove('valid', 'invalid');
            if (length > 0) {
                if (length <= 100) {
                    input.classList.add('valid');
                    input.style.borderColor = '#10b981';
                    if (errorDiv) errorDiv.style.display = 'none';
                } else {
                    input.classList.add('invalid');
                    input.style.borderColor = '#ef4444';
                    if (errorDiv) {
                        errorDiv.style.display = 'block';
                        errorDiv.querySelector('span').textContent = 'Maximum 100 characters allowed';
                    }
                }
            } else {
                input.style.borderColor = '';
                if (errorDiv) errorDiv.style.display = 'none';
            }
            
            // Call original update function if it exists
            if (typeof updateApprovalData === 'function') {
                updateApprovalData('plan_remarks', value);
            }
        }
    </script>

    <!-- === PROJECT CLOSURE MODAL SYSTEM === -->
    <div class="closure-modal-overlay" id="closureModalOverlay">
        <div class="closure-modal" id="closureModal">
            <!-- Step Indicator -->
            <div class="closure-step-indicator" style="padding: 1.5rem 2.5rem 0;">
                <div class="closure-step-dot" data-step="1"></div>
                <div class="closure-step-dot" data-step="2"></div>
                <div class="closure-step-dot" data-step="3"></div>
                <div class="closure-step-dot" data-step="4"></div>
            </div>

            <!-- Modal Content Container -->
            <div id="closureModalContent"></div>
        </div>
    </div>

    <script src="js/architectural_bg.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if(typeof initArchitecturalBackground === 'function') {
                initArchitecturalBackground('canvas-container');
            }
        });
    </script>
</body>
</html>
