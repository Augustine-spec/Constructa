<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'homeowner') {
    header('Location: login.html');
    exit();
}

require_once 'backend/config.php';

// Get engineer ID from URL
$engineer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($engineer_id === 0) {
    header('Location: engineer_directory.php');
    exit();
}

// Fetch engineer details
$conn = getDatabaseConnection();
$stmt = $conn->prepare("SELECT id, name, email, specialization, experience FROM users WHERE id = ? AND role = 'engineer' AND status = 'approved'");
$stmt->bind_param("i", $engineer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: engineer_directory.php');
    exit();
}

$engineer = $result->fetch_assoc();
$homeowner_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Homeowner';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact <?php echo htmlspecialchars($engineer['name']); ?> - Constructa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <style>
        :root {
            --primary: #294033;
            --primary-light: #3d5a49;
            --secondary: #6366f1;
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15), 0 0 1px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: transparent;
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* 3D Background Canvas */
        #canvas-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: -1;
            background: #f8fafc;
            pointer-events: none;
        }

        /* Navbar */
        nav {
            background: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .nav-logo {
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-link {
            text-decoration: none;
            color: var(--text-muted);
            font-weight: 500;
            font-size: 0.95rem;
            transition: var(--transition);
        }

        .nav-link:hover {
            color: var(--primary);
        }

        .nav-btn {
            background: white;
            border: 1px solid var(--border-color);
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-weight: 800;
            font-size: 0.85rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--text-main);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.2s ease;
        }

        .nav-btn:hover {
            background: #fff;
            border-color: var(--text-main);
            color: var(--text-main);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .nav-btn i {
            font-size: 1rem;
        }

        /* Main Layout */
        .app-container {
            display: flex;
            flex: 1;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            height: calc(100vh - 70px);
        }

        /* Wizard Section (Left) */
        .wizard-section {
            flex: 2;
            padding: 3rem;
            position: relative;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        /* Live Preview Section (Right) */
        .preview-section {
            flex: 1;
            background: #ffffff;
            border-left: 1px solid var(--border-color);
            padding: 2.5rem;
            display: flex;
            flex-direction: column;
            box-shadow: -5px 0 20px rgba(0,0,0,0.02);
            overflow-y: auto;
        }

        /* Progress Bar */
        .progress-header {
            margin-bottom: 3rem;
        }
        
        .progress-track {
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .progress-fill {
            height: 100%;
            background: var(--primary);
            width: 0%;
            transition: width 0.5s ease-in-out;
        }

        .step-indicator {
            font-size: 0.9rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        /* Step Content */
        .step-container {
            position: relative;
            flex: 1;
            display: flex;
        }

        .step {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            opacity: 0;
            transform: translateY(20px);
            pointer-events: none;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            display: none;
        }

        .step.active {
            opacity: 1;
            transform: translateY(0);
            pointer-events: all;
            display: block;
            position: relative;
        }

        .step-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .step-desc {
            font-size: 1.1rem;
            color: var(--text-muted);
            margin-bottom: 2.5rem;
        }

        /* Input Styles */
        .big-input {
            width: 100%;
            font-size: 2rem;
            padding: 1rem;
            border: none;
            border-bottom: 3px solid var(--border-color);
            background: transparent;
            font-weight: 600;
            color: var(--primary);
            outline: none;
            transition: var(--transition);
        }

        .big-input:focus {
            border-bottom-color: var(--primary);
        }

        .big-input::placeholder {
            color: #cbd5e1;
        }

        textarea.big-input {
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            font-size: 1.1rem;
            min-height: 200px;
            resize: vertical;
        }

        textarea.big-input:focus {
            border-color: var(--primary);
        }

        /* Grid Cards */
        .options-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .selection-card {
            background: white;
            border: 2px solid var(--border-color);
            border-radius: 16px;
            padding: 2rem;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .selection-card:hover {
            border-color: #cbd5e1;
            transform: translateY(-4px);
            box-shadow: var(--shadow);
        }

        .selection-card.selected {
            border-color: var(--primary);
            background: #f0fdf4;
            box-shadow: 0 0 0 2px rgba(41, 64, 51, 0.1);
        }

        .selection-card .icon {
            font-size: 2rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
            transition: var(--transition);
        }

        .selection-card.selected .icon {
            color: var(--primary);
        }

        .card-title {
            font-weight: 700;
            font-size: 1.2rem;
        }

        .card-subtitle {
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .check-mark {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transform: scale(0);
            transition: var(--transition);
        }

        .selection-card.selected .check-mark {
            opacity: 1;
            transform: scale(1);
        }

        /* Navigation Buttons */
        .wizard-nav {
            margin-top: auto;
            padding-top: 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn {
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-secondary {
            background: var(--bg-color);
            color: var(--text-muted);
        }

        .btn-secondary:hover {
            background: #e2e8f0;
            color: var(--text-main);
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(41, 64, 51, 0.3);
        }

        .btn-primary:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(41, 64, 51, 0.4);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }

        /* Preview Sidebar */
        .preview-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .engineer-preview {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            color: white;
        }

        .engineer-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .engineer-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .engineer-spec {
            opacity: 0.9;
            font-size: 0.95rem;
        }

        .preview-card {
            background: var(--bg-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .preview-label {
            font-size: 0.85rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .preview-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .preview-item {
            display: flex;
            justify-content: space-between;
            font-size: 0.95rem;
        }
        
        .preview-item span:first-child {
            color: var(--text-muted);
        }
        
        .preview-item span:last-child {
            font-weight: 600;
        }

        /* 3D Preview Container */
        #preview3D {
            width: 100%;
            height: 300px;
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            border-radius: 12px;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        /* Toast */
        .error-toast {
            position: fixed;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            background: #ef4444;
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            box-shadow: 0 10px 20px rgba(239, 68, 68, 0.3);
            display: none;
            z-index: 100;
            animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes popIn {
            from { transform: translateX(-50%) translateY(20px) scale(0.8); opacity: 0; }
            to { transform: translateX(-50%) translateY(0) scale(1); opacity: 1; }
        }

        /* 3D Validation Feedback */
        .validation-wrapper {
            position: relative;
        }

        .validation-icon {
            position: absolute;
            right: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            opacity: 0;
            transform: translateY(-50%) scale(0) rotateY(180deg);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .validation-icon.show {
            opacity: 1;
            transform: translateY(-50%) scale(1) rotateY(0deg);
        }

        .validation-icon.valid {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }

        .validation-icon.invalid {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }

        .validation-message {
            margin-top: 0.75rem;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        .validation-message.show {
            opacity: 1;
            transform: translateY(0);
        }

        .validation-message.valid {
            background: #d1fae5;
            color: #065f46;
            border-left: 3px solid #10b981;
        }

        .validation-message.invalid {
            background: #fee2e2;
            color: #991b1b;
            border-left: 3px solid #ef4444;
        }

        .validation-message i {
            font-size: 1.1rem;
        }

        /* Input States */
        .big-input.valid {
            border-bottom-color: #10b981;
        }

        .big-input.invalid {
            border-bottom-color: #ef4444;
        }

        textarea.big-input.valid {
            border-color: #10b981;
        }

        textarea.big-input.invalid {
            border-color: #ef4444;
        }

        /* Animated Checkmark */
        @keyframes checkmark {
            0% {
                stroke-dashoffset: 50;
            }
            100% {
                stroke-dashoffset: 0;
            }
        }

        @keyframes cross {
            0% {
                stroke-dashoffset: 50;
            }
            100% {
                stroke-dashoffset: 0;
            }
        }

        /* Pulse animation for invalid */
        @keyframes pulse {
            0%, 100% {
                transform: translateY(-50%) scale(1);
            }
            50% {
                transform: translateY(-50%) scale(1.1);
            }
        }

        .validation-icon.invalid {
            animation: pulse 0.5s ease-in-out;
        }

        /* Success Screen */
        .success-screen {
            text-align: center;
            padding: 3rem;
        }

        .success-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 2rem;
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: scaleIn 0.5s ease-out;
        }

        .success-icon i {
            font-size: 3rem;
            color: white;
        }

        @keyframes scaleIn {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }

        @media (max-width: 968px) {
            .app-container {
                flex-direction: column;
                height: auto;
            }
            .preview-section {
                border-left: none;
                border-top: 1px solid var(--border-color);
            }
        }
    </style>
</head>
<body>
    <div id="canvas-container"></div>
    
    <!-- Navbar -->
    <nav>
        <a href="homeowner.php" class="nav-logo">
            <i class="far fa-building"></i>
            Constructa
        </a>
        <a href="engineer_directory.php" class="nav-btn">
            <i class="fas fa-arrow-left"></i> BACK TO DIRECTORY
        </a>
    </nav>

    <div class="app-container">
        <!-- Toast -->
        <div id="errorToast" class="error-toast">
            <i class="fas fa-exclamation-circle"></i> <span id="errorMsg">Please fill in this field</span>
        </div>

        <!-- Left Wizard -->
        <div class="wizard-section">
            <div class="progress-header">
                <div class="step-indicator">Step <span id="currentStepNum">1</span> of 8</div>
                <div class="progress-track">
                    <div class="progress-fill" id="progressBar"></div>
                </div>
            </div>

            <form id="wizardForm">
                <input type="hidden" name="engineer_id" value="<?php echo $engineer_id; ?>">
                <input type="hidden" name="homeowner_id" value="<?php echo $_SESSION['user_id']; ?>">

                <!-- STEP 1: Project Title -->
                <div class="step active" id="step1">
                    <h2 class="step-title">What's your project called?</h2>
                    <p class="step-desc">Give your project a descriptive name</p>
                    <div class="form-group validation-wrapper">
                        <input type="text" id="project_title" name="project_title" class="big-input" placeholder="e.g., Modern Villa Construction" required onkeypress="return /[a-zA-Z\s]/.test(event.key)">
                        <div class="validation-icon" id="validation_project_title">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="validation-message" id="message_project_title"></div>
                    </div>
                </div>

                <!-- STEP 2: Contact Phone -->
                <div class="step" id="step2">
                    <h2 class="step-title">How can we reach you?</h2>
                    <p class="step-desc">Your contact phone number (optional but recommended)</p>
                    <div class="form-group validation-wrapper">
                        <input type="tel" id="contact_phone" name="contact_phone" class="big-input" placeholder="e.g., +1 (555) 123-4567">
                        <div class="validation-icon" id="validation_contact_phone">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="validation-message" id="message_contact_phone"></div>
                    </div>
                </div>

                <!-- STEP 3: Project Type -->
                <div class="step" id="step3">
                    <h2 class="step-title">What type of project is this?</h2>
                    <p class="step-desc">Select the category that best describes your project</p>
                    <div class="options-grid">
                        <div class="selection-card selected" onclick="selectCard('project_type', 'Residential', this)" data-value="Residential">
                            <div class="check-mark"><i class="fas fa-check"></i></div>
                            <div class="icon"><i class="fas fa-home"></i></div>
                            <div class="card-title">Residential</div>
                            <div class="card-subtitle">Houses, apartments, villas</div>
                        </div>
                        <div class="selection-card" onclick="selectCard('project_type', 'Commercial', this)" data-value="Commercial">
                            <div class="check-mark"><i class="fas fa-check"></i></div>
                            <div class="icon"><i class="fas fa-building"></i></div>
                            <div class="card-title">Commercial</div>
                            <div class="card-subtitle">Offices, shops, warehouses</div>
                        </div>
                        <div class="selection-card" onclick="selectCard('project_type', 'Industrial', this)" data-value="Industrial">
                            <div class="check-mark"><i class="fas fa-check"></i></div>
                            <div class="icon"><i class="fas fa-industry"></i></div>
                            <div class="card-title">Industrial</div>
                            <div class="card-subtitle">Factories, plants</div>
                        </div>
                        <div class="selection-card" onclick="selectCard('project_type', 'Renovation', this)" data-value="Renovation">
                            <div class="check-mark"><i class="fas fa-check"></i></div>
                            <div class="icon"><i class="fas fa-tools"></i></div>
                            <div class="card-title">Renovation</div>
                            <div class="card-subtitle">Remodeling existing structures</div>
                        </div>
                    </div>
                    <input type="hidden" id="project_type" name="project_type" value="Residential">
                </div>

                <!-- STEP 4: Location -->
                <div class="step" id="step4">
                    <h2 class="step-title">Where is your project located?</h2>
                    <p class="step-desc">City and state/region</p>
                    <div class="form-group validation-wrapper">
                        <input type="text" id="location" name="location" class="big-input" placeholder="e.g., San Francisco, CA" required>
                        <div class="validation-icon" id="validation_location">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="validation-message" id="message_location"></div>
                    </div>
                </div>

                <!-- STEP 5: Project Size -->
                <div class="step" id="step5">
                    <h2 class="step-title">What's the size of your project?</h2>
                    <p class="step-desc">Approximate area in square feet <span style="font-weight:600; color:#64748b;">(Max: 10,000 sq.ft)</span></p>
                    <div class="form-group validation-wrapper">
                        <input type="number" id="project_size" name="project_size" class="big-input" placeholder="e.g., 3500" min="1" max="10000">
                        <div class="validation-icon" id="validation_project_size">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="validation-message" id="message_project_size"></div>
                    </div>
                </div>

                <!-- STEP 6: Description -->
                <div class="step" id="step6">
                    <h2 class="step-title">Tell us about your project</h2>
                    <p class="step-desc">Describe your requirements, goals, and any specific needs</p>
                    <div class="form-group validation-wrapper">
                        <textarea id="description" name="description" class="big-input" placeholder="Share your vision, specific requirements, challenges, and what you hope to achieve..." required></textarea>
                        <div class="validation-message" id="message_description"></div>
                    </div>
                </div>

                <!-- STEP 7: Budget -->
                <div class="step" id="step7">
                    <h2 class="step-title">What's your estimated budget?</h2>
                    <p class="step-desc">Provide a range or specific amount</p>
                    <div class="form-group validation-wrapper">
                        <input type="text" id="budget" name="budget" class="big-input" placeholder="e.g., $50,000 - $75,000" required>
                        <div class="validation-icon" id="validation_budget">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="validation-message" id="message_budget"></div>
                    </div>
                </div>

                <!-- STEP 8: Timeline -->
                <div class="step" id="step8">
                    <h2 class="step-title">What's your expected timeline?</h2>
                    <p class="step-desc">When do you want to start and complete?</p>
                    <div class="form-group validation-wrapper">
                        <input type="text" id="timeline" name="timeline" class="big-input" placeholder="e.g., 6-8 months" required>
                        <div class="validation-icon" id="validation_timeline">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="validation-message" id="message_timeline"></div>
                    </div>
                </div>

            </form>

            <div class="wizard-nav" id="wizardNav">
                <button type="button" class="btn btn-secondary" id="prevBtn" onclick="changeStep(-1)" disabled>
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <button type="button" class="btn btn-primary" id="nextBtn" onclick="changeStep(1)">
                    Next Step <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- Right Preview -->
        <div class="preview-section">
            <h3 class="preview-title"><i class="fas fa-eye"></i> Live Preview</h3>
            
            <!-- Engineer Info -->
            <div class="engineer-preview">
                <div class="engineer-avatar">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="engineer-name"><?php echo htmlspecialchars($engineer['name']); ?></div>
                <div class="engineer-spec"><?php echo htmlspecialchars($engineer['specialization'] ?: 'Structural Engineer'); ?></div>
                <div style="margin-top: 1rem; opacity: 0.8; font-size: 0.9rem;">
                    <i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($engineer['experience']); ?> Years Experience
                </div>
            </div>

            <!-- 3D Preview -->
            <div id="preview3D"></div>

            <!-- Current Selections -->
            <div class="preview-card">
                <div class="preview-label">Your Request Details</div>
                <ul class="preview-list">
                    <li class="preview-item">
                        <span>Project Title</span>
                        <span id="prevTitle">-</span>
                    </li>
                    <li class="preview-item">
                        <span>Type</span>
                        <span id="prevType">Residential</span>
                    </li>
                    <li class="preview-item">
                        <span>Location</span>
                        <span id="prevLocation">-</span>
                    </li>
                    <li class="preview-item">
                        <span>Size</span>
                        <span id="prevSize">-</span>
                    </li>
                    <li class="preview-item">
                        <span>Budget</span>
                        <span id="prevBudget">-</span>
                    </li>
                    <li class="preview-item">
                        <span>Timeline</span>
                        <span id="prevTimeline">-</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // State
        let currentStep = 1;
        const totalSteps = 8;
        
        // DOM Elements
        const steps = document.querySelectorAll('.step');
        const progressBar = document.getElementById('progressBar');
        const currentStepNum = document.getElementById('currentStepNum');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const toast = document.getElementById('errorToast');
        
        // Initial
        updateProgress();
        init3DPreview();

        // Navigation
        function changeStep(direction) {
            if (direction === 1 && !validateStep(currentStep)) return;

            const currentEl = document.getElementById(`step${currentStep}`);
            currentEl.classList.remove('active');
            
            currentStep += direction;
            
            if (currentStep < 1) currentStep = 1;
            if (currentStep > totalSteps) currentStep = totalSteps;

            const nextEl = document.getElementById(`step${currentStep}`);
            nextEl.classList.add('active');

            updateProgress();
            updatePreview();

            // Handle last step
            if (currentStep === totalSteps) {
                nextBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Request';
                nextBtn.onclick = submitForm;
            } else {
                nextBtn.innerHTML = 'Next Step <i class="fas fa-arrow-right"></i>';
                nextBtn.onclick = () => changeStep(1);
            }

            prevBtn.disabled = currentStep === 1;
        }

        function validateStep(step) {
            let valid = true;
            let message = 'Please fill in this field';

            switch(step) {
                case 1:
                    const title = document.getElementById('project_title').value.trim();
                    if (!title) {
                        valid = false;
                        message = 'Please enter a project title';
                    }
                    else if (!/^[a-zA-Z\s]+$/.test(title)) {
                        valid = false;
                        message = 'Title must contain only letters and spaces';
                    }
                    break;
                case 4:
                    const location = document.getElementById('location').value.trim();
                    if (!location) {
                        valid = false;
                        message = 'Please enter project location';
                    }
                    break;
                case 6:
                    const desc = document.getElementById('description').value.trim();
                    if (!desc) {
                        valid = false;
                        message = 'Please describe your project';
                    }
                    break;
                case 7:
                    const budget = document.getElementById('budget').value.trim();
                    if (!budget) {
                        valid = false;
                        message = 'Please enter your budget';
                    }
                    break;
                case 8:
                    const timeline = document.getElementById('timeline').value.trim();
                    if (!timeline) {
                        valid = false;
                        message = 'Please enter expected timeline';
                    }
                    break;
            }

            if (!valid) showToast(message);
            return valid;
        }

        function updateProgress() {
            const progress = (currentStep / totalSteps) * 100;
            progressBar.style.width = progress + '%';
            currentStepNum.textContent = currentStep;
        }

        function selectCard(fieldName, value, element) {
            // Remove selected from siblings
            const siblings = element.parentElement.querySelectorAll('.selection-card');
            siblings.forEach(card => card.classList.remove('selected'));
            
            // Add selected to clicked
            element.classList.add('selected');
            
            // Update hidden input
            document.getElementById(fieldName).value = value;
            
            updatePreview();
        }

        function updatePreview() {
            document.getElementById('prevTitle').textContent = document.getElementById('project_title').value || '-';
            document.getElementById('prevType').textContent = document.getElementById('project_type').value || '-';
            document.getElementById('prevLocation').textContent = document.getElementById('location').value || '-';
            const sizeValue = document.getElementById('project_size').value;
            document.getElementById('prevSize').textContent = sizeValue ? `${sizeValue} sq.ft` : '-';
            document.getElementById('prevBudget').textContent = document.getElementById('budget').value || '-';
            document.getElementById('prevTimeline').textContent = document.getElementById('timeline').value || '-';
        }

        // Comprehensive Validation System
        const validators = {
            project_title: (value) => {
                if (!value || value.trim().length === 0) {
                    return { valid: false, message: 'Project title is required' };
                }
                const strictRegex = /^[a-zA-Z\s]+$/;
                if (!strictRegex.test(value)) {
                    return { valid: false, message: '✗ No numbers or special symbols allowed' };
                }
                if (value.trim().length < 3) {
                    return { valid: false, message: 'Title must be at least 3 characters' };
                }
                if (value.trim().length > 100) {
                    return { valid: false, message: 'Title must be less than 100 characters' };
                }
                return { valid: true, message: '✓ Perfect! Great project title' };
            },
            
            contact_phone: (value) => {
                // Optional field
                if (!value || value.trim().length === 0) {
                    return { valid: true, message: 'Phone number is optional' };
                }
                
                // Remove all non-digit characters for validation
                const digitsOnly = value.replace(/\D/g, '');
                
                // Check if contains only valid phone characters
                const validChars = /^[0-9\s\-\+\(\)\.]+$/;
                if (!validChars.test(value)) {
                    return { valid: false, message: '✗ Phone can only contain numbers, spaces, +, -, (, )' };
                }
                
                // Check minimum digits
                if (digitsOnly.length < 10) {
                    return { valid: false, message: `✗ Need at least 10 digits (currently ${digitsOnly.length})` };
                }
                
                // Check maximum digits
                if (digitsOnly.length > 15) {
                    return { valid: false, message: '✗ Phone number is too long (max 15 digits)' };
                }
                
                return { valid: true, message: `✓ Valid phone number (${digitsOnly.length} digits)` };
            },
            
            location: (value) => {
                if (!value || value.trim().length === 0) {
                    return { valid: false, message: '✗ Location is required' };
                }
                if (value.trim().length < 3) {
                    return { valid: false, message: '✗ Please enter a valid location' };
                }
                // Check if it contains at least one letter
                if (!/[a-zA-Z]/.test(value)) {
                    return { valid: false, message: '✗ Location must contain letters' };
                }
                return { valid: true, message: '✓ Location looks good!' };
            },
            
            project_size: (value) => {
                // Optional field
                if (!value || value.trim().length === 0) {
                    return { valid: true, message: 'Project size is optional' };
                }
                
                // Parse numeric value
                const numValue = parseFloat(value);
                
                // Check if it's a valid number
                if (isNaN(numValue)) {
                    return { valid: false, message: '✗ Please enter a valid number (e.g., 3500)' };
                }
                
                // Check minimum
                if (numValue < 1) {
                    return { valid: false, message: '✗ Project size must be at least 1 sq.ft' };
                }
                
                // Check maximum
                if (numValue > 10000) {
                    // Auto-cap the value
                    const input = document.getElementById('project_size');
                    input.value = 10000;
                    setTimeout(() => {
                        validateField('project_size');
                    }, 500);
                    return { valid: false, message: '✗ Project size cannot exceed 10,000 sq.ft (auto-capped)' };
                }
                
                return { valid: true, message: `✓ Size format is good (${numValue.toLocaleString()} sq.ft)` };
            },
            
            description: (value) => {
                if (!value || value.trim().length === 0) {
                    return { valid: false, message: '✗ Project description is required' };
                }
                const length = value.trim().length;
                if (length < 20) {
                    return { valid: false, message: `✗ Please provide more details (${length}/20 characters minimum)` };
                }
                if (length > 2000) {
                    return { valid: false, message: '✗ Description is too long (max 2000 characters)' };
                }
                return { valid: true, message: `✓ Great description! (${length} characters)` };
            },
            
            budget: (value) => {
                if (!value || value.trim().length === 0) {
                    return { valid: false, message: '✗ Budget estimate is required' };
                }
                
                // Extract all numbers from the string (removes currency symbols, commas, etc)
                // We handle "50k" or "50,000" logic if user types k/lakh? 
                // For now, let's just look for raw digits.
                // If user types "12", matches ["12"].
                const matches = value.replace(/,/g, '').match(/(\d+)/g);
                
                if (!matches) {
                    return { valid: false, message: '✗ Please include a numeric budget amount' };
                }
                
                // Find the largest number mentioned (in case of a range "10000-20000", both valid)
                // We want to ensure at least one number or the implied budget is significant.
                // If user enters "12", max is 12 -> Invalid.
                const maxVal = Math.max(...matches.map(n => parseInt(n)));
                
                if (maxVal < 10000) {
                    return { valid: false, message: '✗ Minimum budget should be at least 10,000' };
                }
                
                return { valid: true, message: '✓ Budget amount looks reasonable' };
            },
            
            timeline: (value) => {
                if (!value || value.trim().length === 0) {
                    return { valid: false, message: '✗ Timeline is required' };
                }
                if (value.trim().length < 3) {
                    return { valid: false, message: '✗ Please provide expected timeline' };
                }

                // Check for range pattern like "6-8" or "6 - 8"
                // matches "6-8 months", "6-8", etc.
                const rangeMatch = value.match(/(\d+)\s*-\s*(\d+)/);
                if (rangeMatch) {
                    const start = parseInt(rangeMatch[1]);
                    const end = parseInt(rangeMatch[2]);
                    
                    if (start >= end) {
                        return { valid: false, message: '✗ Start range must be smaller than end range (e.g., 4-6)' };
                    }
                }

                return { valid: true, message: '✓ Timeline looks good!' };
            }
        };

        function validateField(fieldId, showFeedback = true) {
            const input = document.getElementById(fieldId);
            if (!input) return true;
            
            const value = input.value;
            const validator = validators[fieldId];
            
            if (!validator) return true;
            
            const result = validator(value);
            
            if (showFeedback) {
                showValidationFeedback(fieldId, result.valid, result.message);
            }
            
            return result.valid;
        }

        function showValidationFeedback(fieldId, isValid, message) {
            const input = document.getElementById(fieldId);
            const icon = document.getElementById(`validation_${fieldId}`);
            const messageEl = document.getElementById(`message_${fieldId}`);
            
            if (!input) return;
            
            // Update input class
            input.classList.remove('valid', 'invalid');
            if (input.value.trim().length > 0) {
                input.classList.add(isValid ? 'valid' : 'invalid');
            }
            
            // Update icon (if exists - not for textarea)
            if (icon) {
                icon.classList.remove('show', 'valid', 'invalid');
                if (input.value.trim().length > 0) {
                    icon.classList.add('show', isValid ? 'valid' : 'invalid');
                    icon.querySelector('i').className = isValid ? 'fas fa-check' : 'fas fa-times';
                }
            }
            
            // Update message
            if (messageEl && message) {
                messageEl.classList.remove('show', 'valid', 'invalid');
                if (input.value.trim().length > 0 || !isValid) {
                    messageEl.innerHTML = `<i class="fas fa-${isValid ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
                    messageEl.classList.add('show', isValid ? 'valid' : 'invalid');
                } else {
                    messageEl.innerHTML = '';
                }
            }
        }

        // Attach real-time validation to all fields
        const fieldsToValidate = ['project_title', 'contact_phone', 'location', 'project_size', 'description', 'budget', 'timeline'];
        
        fieldsToValidate.forEach(fieldId => {
            const input = document.getElementById(fieldId);
            if (input) {
                input.addEventListener('input', () => {
                    validateField(fieldId, true);
                    updatePreview();
                });
                
                input.addEventListener('blur', () => {
                    validateField(fieldId, true);
                });
            }
        });

        // Update validateStep to use new validators
        function validateStepEnhanced(step) {
            let fieldToValidate = null;
            
            switch(step) {
                case 1: fieldToValidate = 'project_title'; break;
                case 2: fieldToValidate = 'contact_phone'; break;
                case 4: fieldToValidate = 'location'; break;
                case 6: fieldToValidate = 'description'; break;
                case 7: fieldToValidate = 'budget'; break;
                case 8: fieldToValidate = 'timeline'; break;
            }
            
            if (fieldToValidate) {
                const isValid = validateField(fieldToValidate, true);
                if (!isValid) {
                    const input = document.getElementById(fieldToValidate);
                    if (input) input.focus();
                }
                return isValid;
            }
            
            return true;
        }

        // Override the original validateStep
        const originalValidateStep = validateStep;
        validateStep = function(step) {
            return validateStepEnhanced(step);
        };


        function showToast(message) {
            document.getElementById('errorMsg').textContent = message;
            toast.style.display = 'block';
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }

        async function submitForm() {
            if (!validateStep(8)) return;

            nextBtn.disabled = true;
            nextBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

            const formData = new FormData(document.getElementById('wizardForm'));

            try {
                const response = await fetch('backend/submit_project_request.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Show success
                    document.querySelector('.wizard-section').innerHTML = `
                        <div class="success-screen">
                            <div class="success-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <h2 class="step-title">Request Submitted!</h2>
                            <p class="step-desc">${result.message || 'Your project request has been sent to the engineer.'}</p>
                            <p style="color: var(--text-muted); margin-top: 2rem;">
                                <i class="fas fa-clock"></i> Redirecting to dashboard...
                            </p>
                        </div>
                    `;
                    
                    setTimeout(() => {
                        window.location.href = 'homeowner.php';
                    }, 2500);
                } else {
                    showToast(result.message || 'Failed to submit request');
                    nextBtn.disabled = false;
                    nextBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Request';
                }
            } catch (error) {
                showToast('An error occurred. Please try again.');
                nextBtn.disabled = false;
                nextBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Request';
            }
        }

        // 3D Preview
        function init3DPreview() {
            const container = document.getElementById('preview3D');
            const scene = new THREE.Scene();
            scene.background = new THREE.Color('#e0f2fe');
            
            const camera = new THREE.PerspectiveCamera(50, container.clientWidth / container.clientHeight, 0.1, 1000);
            camera.position.set(5, 5, 8);
            camera.lookAt(0, 0, 0);

            const renderer = new THREE.WebGLRenderer({ antialias: true });
            renderer.setSize(container.clientWidth, container.clientHeight);
            container.appendChild(renderer.domElement);

            // Lights
            const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
            scene.add(ambientLight);
            const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
            directionalLight.position.set(5, 10, 5);
            scene.add(directionalLight);

            // Simple house
            const houseMat = new THREE.MeshPhongMaterial({ color: 0x294033, transparent: true, opacity: 0.3 });
            const edgeMat = new THREE.LineBasicMaterial({ color: 0x294033 });

            // Base
            const baseGeo = new THREE.BoxGeometry(3, 2, 3);
            const baseMesh = new THREE.Mesh(baseGeo, houseMat);
            baseMesh.position.y = 1;
            scene.add(baseMesh);
            
            const baseEdges = new THREE.EdgesGeometry(baseGeo);
            const baseLine = new THREE.LineSegments(baseEdges, edgeMat);
            baseLine.position.copy(baseMesh.position);
            scene.add(baseLine);

            // Roof
            const roofGeo = new THREE.ConeGeometry(2.5, 1.5, 4);
            const roofMesh = new THREE.Mesh(roofGeo, houseMat);
            roofMesh.position.y = 2.75;
            roofMesh.rotation.y = Math.PI / 4;
            scene.add(roofMesh);
            
            const roofEdges = new THREE.EdgesGeometry(roofGeo);
            const roofLine = new THREE.LineSegments(roofEdges, edgeMat);
            roofLine.position.copy(roofMesh.position);
            roofLine.rotation.copy(roofMesh.rotation);
            scene.add(roofLine);

            // Animate
            function animate() {
                requestAnimationFrame(animate);
                baseMesh.rotation.y += 0.005;
                baseLine.rotation.y += 0.005;
                roofMesh.rotation.y += 0.005;
                roofLine.rotation.y += 0.005;
                renderer.render(scene, camera);
            }
            animate();

            // Resize
            window.addEventListener('resize', () => {
                camera.aspect = container.clientWidth / container.clientHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(container.clientWidth, container.clientHeight);
            });
        }

        // 3D Background
        const initBackground3D = () => {
            const container = document.getElementById('canvas-container');
            if (!container) return;

            const scene = new THREE.Scene();
            scene.background = new THREE.Color('#f8fafc');
            scene.fog = new THREE.Fog('#f8fafc', 5, 45);
            const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
            camera.position.set(0, 5, 10);

            const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            container.appendChild(renderer.domElement);

            const ambientLight = new THREE.AmbientLight(0xffffff, 0.7);
            scene.add(ambientLight);

            const mainLight = new THREE.DirectionalLight(0xffffff, 0.8);
            mainLight.position.set(10, 20, 10);
            scene.add(mainLight);

            const floorGroup = new THREE.Group();
            scene.add(floorGroup);

            const buildMat = new THREE.MeshPhongMaterial({ 
                color: 0x294033, 
                transparent: true, 
                opacity: 0.08, 
                side: THREE.DoubleSide 
            });
            const edgeMat = new THREE.LineBasicMaterial({ 
                color: 0x294033, 
                transparent: true, 
                opacity: 0.15 
            });

            const gridSize = 6;
            const spacing = 4;

            for (let x = -gridSize; x <= gridSize; x++) {
                for (let z = -gridSize; z <= gridSize; z++) {
                    const h = Math.random() * 3 + 1;
                    const geo = new THREE.BoxGeometry(1.2, h, 1.2);
                    const mesh = new THREE.Mesh(geo, buildMat);
                    mesh.position.set(x * spacing, h / 2 - 5, z * spacing);

                    const edges = new THREE.EdgesGeometry(geo);
                    const line = new THREE.LineSegments(edges, edgeMat);
                    line.position.copy(mesh.position);

                    floorGroup.add(mesh);
                    floorGroup.add(line);
                }
            }

            let mouseX = 0, mouseY = 0;
            document.addEventListener('mousemove', (e) => {
                mouseX = (e.clientX - window.innerWidth / 2) * 0.0003;
                mouseY = (e.clientY - window.innerHeight / 2) * 0.0003;
            });

            const animate = () => {
                requestAnimationFrame(animate);
                floorGroup.rotation.y += 0.001;
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
        };

        if (typeof THREE !== 'undefined') initBackground3D();
    </script>
</body>
</html>
