<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'engineer') {
    header('Location: login.html');
    exit();
}
require_once 'backend/config.php';
$conn = getDatabaseConnection();
$engineer_id = $_SESSION['user_id'];

// Fetch engineer details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $engineer_id);
$stmt->execute();
$engineer = $stmt->get_result()->fetch_assoc();

$username = $engineer['name'] ?: 'Engineer';
$experience = $engineer['experience'] ?: 0;

// Fetch project stats
$total_projects = 0;
// Using the same logic as my_projects.php: status 'accepted' or 'completed'
$stmt_projects = $conn->prepare("SELECT COUNT(*) as count FROM project_requests WHERE engineer_id = ? AND (status = 'accepted' OR status = 'completed')");
$stmt_projects->bind_param("i", $engineer_id);
$stmt_projects->execute();
$project_result = $stmt_projects->get_result()->fetch_assoc();
$total_projects = $project_result['count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Identity - Constructa</title>
    
    <!-- Libraries -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>

    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.4);
            --primary: #294033;
            --accent: #10b981;
            --text-main: #1e293b;
            --text-dim: #64748b;
            --soft-shadow: 0 20px 50px rgba(0, 0, 0, 0.05);
            --card-radius: 32px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        body {
            background-color: #f8fafc;
            color: var(--text-main);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* 3D Background */
        #canvas-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            pointer-events: none;
        }

        .bg-gradient {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 10% 20%, rgba(16, 185, 129, 0.05) 0%, transparent 40%),
                        radial-gradient(circle at 90% 80%, rgba(41, 64, 51, 0.05) 0%, transparent 40%);
            z-index: -1;
            pointer-events: none;
        }

        /* Navbar */
        nav {
            padding: 1.5rem 4rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
        }

        .nav-logo {
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--primary);
            text-decoration: none;
            letter-spacing: -1px;
        }
        
        /* 3D Brand Title Styling */
        .brand-3d {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.5rem;
            font-weight: 900;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 4px;
            display: flex;
            cursor: pointer;
            perspective: 1000px;
            transform-style: preserve-3d;
        }

        .brand-3d span {
            display: inline-block;
            opacity: 0;
            transform: translateY(10px) rotateX(-90deg);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .brand-3d span.visible {
            opacity: 1;
            transform: translateY(0) rotateX(0);
        }

        .nav-actions {
            display: flex;
            gap: 1rem;
        }

        .nav-btn {
            background: white;
            border: 1px solid rgba(0, 0, 0, 0.05);
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--text-main);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.05);
            border-color: rgba(0, 0, 0, 0.1);
        }

        /* Layout */
        .main-wrapper {
            max-width: 1400px;
            margin: 0 auto;
            padding: 4rem 2rem;
            display: grid;
            grid-template-columns: 420px 1fr;
            gap: 3rem;
            align-items: start;
        }

        /* Left Column: Identity Card */
        .identity-column {
            position: sticky;
            top: 100px;
            perspective: 2000px;
        }

        .id-card {
            background: #ffffff;
            border-radius: var(--card-radius);
            padding: 5rem 2.5rem 4rem;
            min-height: 800px;
            text-align: center;
            box-shadow: 0 30px 60px rgba(0,0,0,0.06);
            transform-style: preserve-3d;
            transition: transform 0.2s cubic-bezier(0.2, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            border: 1px solid #f1f5f9;
        }

        .id-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.03) 0%, transparent 100%);
            pointer-events: none;
        }

        .profile-ring {
            position: relative;
            width: 160px;
            height: 160px;
            margin: 0 auto 2.5rem;
            border-radius: 50%;
            border: 8px solid #294033;
            display: flex;
            align-items: center;
            justify-content: center;
            transform: translateZ(50px);
            background: white;
            box-shadow: 0 10px 30px rgba(41, 64, 51, 0.15);
        }

        .profile-avatar {
            font-size: 4.5rem;
            font-weight: 800;
            color: #294033;
            line-height: 1;
        }

        .verified-badge {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: #4f87ff;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            border: 3px solid #fff;
            box-shadow: 0 4px 10px rgba(79, 135, 255, 0.4);
            z-index: 2;
        }

        .id-name {
            font-size: 2.2rem;
            font-weight: 800;
            color: #294033;
            margin-bottom: 0.25rem;
            transform: translateZ(40px);
            letter-spacing: -0.5px;
        }

        .id-role {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 0.15em;
            margin-bottom: 1.5rem;
            transform: translateZ(30px);
        }

        .rating-container {
            margin-bottom: 2rem;
            transform: translateZ(25px);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: center;
        }

        .star-rating {
            font-size: 1.1rem;
            color: #f59e0b;
            display: flex;
            gap: 3px;
        }

        .rating-value {
            font-weight: 800;
            font-size: 1.2rem;
            color: #294033;
        }

        .divider {
            width: 80%;
            height: 1px;
            background: #f1f5f9;
            margin-bottom: 2rem;
            transform: translateZ(10px);
        }

        .id-stats {
            display: flex;
            justify-content: space-around;
            width: 100%;
            margin-bottom: 2.5rem;
            transform: translateZ(20px);
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 800;
            color: #294033;
            line-height: 1.2;
        }

        .stat-label {
            font-size: 0.7rem;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .online-status-pill {
            background: #e6f7f1;
            color: var(--accent);
            padding: 0.6rem 1.2rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            transform: translateZ(15px);
        }

        .status-dot {
            width: 7px;
            height: 7px;
            background: var(--accent);
            border-radius: 50%;
            box-shadow: 0 0 8px var(--accent);
        }

        @keyframes pulse {
            0% { transform: scale(0.95); opacity: 0.8; }
            50% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(0.95); opacity: 0.8; }
        }

        /* Right Column Styles */
        .content-column {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: var(--card-radius);
            padding: 2.5rem;
            box-shadow: var(--soft-shadow);
            opacity: 0;
            transform: translateY(30px);
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .section-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 1.2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        /* Metrics */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .metric-box {
            background: white;
            padding: 1.5rem;
            border-radius: 20px;
            text-align: center;
            border: 1px solid rgba(0,0,0,0.03);
            transition: all 0.3s ease;
        }

        .metric-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }

        .metric-value {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--accent);
            display: block;
        }

        .metric-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-dim);
            text-transform: uppercase;
        }

        /* Expertise Ring Skills */
        .skills-container {
            display: flex;
            flex-wrap: wrap;
            gap: 3rem;
            justify-content: center;
        }

        .skill-ring-wrapper {
            position: relative;
            width: 100px;
            height: 100px;
        }

        .skill-svg {
            transform: rotate(-90deg);
        }

        .skill-circle-bg {
            fill: none;
            stroke: #f1f5f9;
            stroke-width: 8;
        }

        .skill-circle-progress {
            fill: none;
            stroke: var(--accent);
            stroke-width: 8;
            stroke-linecap: round;
            stroke-dasharray: 251.2;
            stroke-dashoffset: 251.2;
            transition: stroke-dashoffset 1.5s ease-out;
        }

        .skill-value {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-weight: 800;
            font-size: 0.9rem;
        }

        .skill-name {
            display: block;
            text-align: center;
            margin-top: 1rem;
            font-weight: 700;
            font-size: 0.8rem;
            color: var(--text-dim);
        }

        /* Timeline */
        .timeline {
            position: relative;
            padding-left: 2rem;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 2px;
            height: 100%;
            background: var(--glass-border);
        }

        .timeline-item {
            position: relative;
            padding-bottom: 2rem;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -2.35rem;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: white;
            border: 2px solid var(--accent);
        }

        .timeline-year {
            font-weight: 800;
            color: var(--accent);
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
            display: block;
        }

        .timeline-content {
            background: white;
            padding: 1.25rem;
            border-radius: 16px;
            font-size: 0.9rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.02);
        }

        /* Projects Carousel */
        .projects-wrapper {
            overflow: hidden;
            margin: 0 -1rem;
            padding: 1rem;
        }

        .projects-carousel {
            display: flex;
            gap: 1.5rem;
            cursor: grab;
        }

        .project-mini-card {
            min-width: 280px;
            background: white;
            border-radius: 24px;
            padding: 1.5rem;
            border: 1px solid rgba(0,0,0,0.02);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            transform-style: preserve-3d;
        }

        .project-mini-card:hover {
            transform: translateY(-10px) rotateY(10deg);
            box-shadow: 0 15px 35px rgba(0,0,0,0.08);
            border-color: var(--accent);
        }

        .project-3d-placeholder {
            width: 100%;
            height: 120px;
            background: #f8fafc;
            border-radius: 16px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: var(--accent);
            opacity: 0.5;
        }

        /* Sticky Footer Actions */
        .sticky-actions {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            z-index: 1000;
        }

        .action-fab {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: none;
        }

        .action-fab:hover {
            transform: scale(1.1) rotate(15deg);
            background: var(--accent);
        }

        /* Save Button State Change */
        .btn-save-wrap {
            position: relative;
            overflow: hidden;
        }

        .btn-profile-save {
            background: var(--primary);
            color: white;
            padding: 1.2rem 3rem;
            border-radius: 16px;
            font-weight: 800;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            transition: all 0.4s ease;
            width: 100%;
            justify-content: center;
        }

        .btn-profile-save.success {
            background: var(--accent);
        }

        /* Profile Bio Expandable */
        .bio-text {
            color: var(--text-dim);
            line-height: 1.8;
            font-size: 0.95rem;
            max-height: 120px;
            overflow: hidden;
            transition: max-height 0.5s ease;
        }

        .bio-text.expanded {
            max-height: 1000px;
        }

        .read-more-btn {
            color: var(--accent);
            font-weight: 700;
            cursor: pointer;
            margin-top: 1rem;
            display: inline-block;
            font-size: 0.85rem;
        }

        /* Verification Icon */
        .verify-icon {
            color: #3b82f6;
            margin-left: 0.5rem;
        }

        /* Trust & Verification */
        .trust-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .trust-badge-card {
            background: white;
            padding: 1rem;
            border-radius: 16px;
            text-align: center;
            border: 1px solid #f1f5f9;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0,0,0,0.02);
        }

        .trust-badge-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.1);
        }

        .trust-icon {
            font-size: 1.5rem;
            color: var(--accent);
            margin-bottom: 0.5rem;
        }

        .trust-label {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--text-dim);
            text-transform: uppercase;
        }

        /* Dynamic Portfolio */
        .portfolio-filters {
            display: flex;
            gap: 0.8rem;
            margin-bottom: 1.5rem;
            overflow-x: auto;
            padding-bottom: 0.5rem;
        }

        .p-filter-btn {
            background: white;
            border: 1px solid rgba(0,0,0,0.05);
            padding: 0.6rem 1.2rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.8rem;
            color: var(--text-dim);
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .p-filter-btn.active, .p-filter-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(41, 64, 51, 0.2);
        }

        .portfolio-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .portfolio-card-3d {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            position: relative;
        }

        .portfolio-card-3d:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 50px rgba(0,0,0,0.1);
            z-index: 10;
        }

        .p-card-img {
            height: 160px;
            background: #eee;
            position: relative;
            overflow: hidden;
        }
        
        .p-card-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .portfolio-card-3d:hover .p-card-img img {
            transform: scale(1.1);
        }

        .p-card-overlay {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .p-card-content {
            padding: 1.2rem;
        }

        .p-card-title {
            font-weight: 800;
            font-size: 1rem;
            margin-bottom: 0.3rem;
            color: var(--primary);
        }

        .p-card-meta {
            font-size: 0.8rem;
            color: var(--text-dim);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Construction Lifecycle Timeline */
        .lifecycle-container {
            position: relative;
            padding: 2rem 0; 
        }

        .lc-node {
            display: flex;
            align-items: flex-start;
            gap: 1.5rem;
            margin-bottom: 2rem;
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .lc-node:hover .lc-marker {
            transform: scale(1.2);
            background: var(--accent);
            border-color: white;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.2);
        }

        .lc-node::before {
            content: '';
            position: absolute;
            left: 20px; /* Adjust based on valid marker width */
            top: 35px;
            bottom: -35px;
            width: 2px;
            background: #e2e8f0;
            z-index: 0;
        }

        .lc-node:last-child::before { display: none; }

        .lc-marker {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: white;
            border: 2px solid var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            position: relative;
            z-index: 1;
            color: var(--primary);
            font-size: 0.9rem;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }

        .lc-info h4 {
            font-size: 1rem;
            font-weight: 800;
            margin-bottom: 0.3rem;
            color: var(--text-main);
        }

        .lc-info p {
            font-size: 0.85rem;
            color: var(--text-dim);
            line-height: 1.5;
        }

        /* Live Activity Map */
        .map-placeholder {
            width: 100%;
            height: 300px;
            background: #e0e7ff;
            border-radius: 20px;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: url('https://api.mapbox.com/styles/v1/mapbox/light-v10/static/0,0,2,0/800x600?access_token=YOUR_TOKEN'); /* Fallback/Placeholder pattern */
            background-size: cover;
        }
        
        .map-pin {
            width: 40px;
            height: 40px;
            background: var(--accent);
            border: 3px solid white;
            border-radius: 50%;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            position: absolute;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        /* Reviews & Badges */
        .review-carousel {
            display: flex;
            gap: 1.5rem;
            overflow-x: auto;
            padding: 1rem 0.5rem 2rem 0.5rem;
        }

        .review-card {
            min-width: 300px;
            background: white;
            padding: 2rem;
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            border: 1px solid rgba(0,0,0,0.02);
            position: relative;
        }

        .review-card::before {
            content: '\f10d';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            top: 20px;
            left: 20px;
            font-size: 2rem;
            color: #f1f5f9;
            z-index: 0;
        }

        .review-text {
            position: relative;
            z-index: 1;
            font-style: italic;
            color: var(--text-main);
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .reviewer-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .reviewer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #cbd5e1;
        }

        .gamification-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 1.5rem;
        }

        .game-badge {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 0.5rem;
            opacity: 0.5;
            transition: all 0.3s;
            cursor: help;
        }

        .game-badge.earned {
            opacity: 1;
        }

        .badge-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--text-dim);
            position: relative;
        }
        
        .game-badge.earned .badge-icon {
            background: linear-gradient(135deg, #fce7f3 0%, #fae8ff 100%);
            color: #d946ef;
            box-shadow: 0 10px 20px rgba(217, 70, 239, 0.15);
        }
        
        .game-badge.earned:nth-child(2) .badge-icon {
             background: linear-gradient(135deg, #dcfce7 0%, #d1fae5 100%);
             color: #10b981;
             box-shadow: 0 10px 20px rgba(16, 185, 129, 0.15);
        }

        /* Animated Request Button */
        .btn-request-project {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
            width: 100%;
            padding: 1.2rem;
            border-radius: 16px;
            border: none;
            font-weight: 800;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.25);
            transition: all 0.3s ease;
            margin-top: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
        }
        
        .btn-request-project:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 20px 40px rgba(16, 185, 129, 0.35);
        }

        .btn-request-project::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.2), transparent);
            transform: rotate(45deg);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%) rotate(45deg); }
            100% { transform: translateX(100%) rotate(45deg); }
        }
    </style>
</head>
<body>
    <div id="canvas-container"></div>
    <div class="bg-gradient"></div>

    <nav>
        <div class="brand-3d" id="brand-3d-text" onclick="window.location.href='engineer.php'">CONSTRUCTA</div>
        <div class="nav-actions">
            <a href="engineer.php" class="nav-btn">
                <i class="fas fa-home"></i> DASHBOARD
            </a>
            <a href="login.html" class="nav-btn">
                <i class="fas fa-sign-out-alt"></i> LOGOUT
            </a>
        </div>
    </nav>

    <main class="main-wrapper">
        <!-- Identity Column -->
        <div class="identity-column">
            <div class="id-card" id="tiltCard">
                <div class="profile-ring">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($username, 0, 1)); ?>
                    </div>
                    <div class="verified-badge" title="Verified Professional">
                        <i class="fas fa-check"></i>
                    </div>
                </div>
                
                <h2 class="id-name"><?php echo htmlspecialchars($username); ?></h2>
                <div class="id-role"><?php echo htmlspecialchars($engineer['specialization'] ?: 'Senior Structural Engineer'); ?></div>
                
                <div class="rating-container">
                    <div class="star-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <span class="rating-value" id="ratingDisplay">0.0</span>
                </div>

                <div class="divider"></div>

                <div style="flex-grow: 1;"></div>

                <div class="id-stats">
                    <div class="stat-item">
                        <span class="stat-value" data-target="<?php echo $total_projects; ?>">0</span>
                        <span class="stat-label">Projects</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value" data-target="<?php echo $experience; ?>">0</span>
                        <span class="stat-label">Experience</span>
                    </div>
                </div>

                <div class="online-status-pill">
                    <div class="status-dot"></div>
                    Available for New Projects
                </div>

                <button class="btn-request-project" onclick="window.location.href='my_projects.php'">
                    <i class="fas fa-briefcase"></i> My Projects
                </button>
            </div>
        </div>

        <!-- Content Column -->
        <div class="content-column">
            <!-- OverView Section -->
            <section class="glass-card section-reveal">
                <div class="section-header">
                    <div class="section-icon"><i class="fas fa-id-card"></i></div>
                    <h3 class="section-title">Professional Overview</h3>
                </div>

                <form id="profileForm" action="backend/update_engineer_profile.php" method="POST">
                    <div class="metrics-grid">
                        <div class="metric-box">
                            <span class="metric-value"><?php echo $experience; ?>Y</span>
                            <span class="metric-label">Seniority</span>
                        </div>
                        <div class="metric-box">
                            <span class="metric-value">PE</span>
                            <span class="metric-label">License Type</span>
                        </div>
                        <div class="metric-box">
                            <span class="metric-value">A+</span>
                            <span class="metric-label">Performance</span>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <label style="font-size: 0.7rem; font-weight: 800; color: var(--text-dim); text-transform: uppercase;">Full Name</label>
                            <input type="text" name="name" style="background: white; border: 1px solid #f1f5f9; padding: 1rem; border-radius: 12px; font-weight: 600;" value="<?php echo htmlspecialchars($username); ?>" required>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <label style="font-size: 0.7rem; font-weight: 800; color: var(--text-dim); text-transform: uppercase;">Email Identity</label>
                            <input type="email" style="background: #f8fafc; border: 1px solid #f1f5f9; padding: 1rem; border-radius: 12px; font-weight: 600; color: var(--text-dim);" value="<?php echo htmlspecialchars($engineer['email']); ?>" disabled>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <label style="font-size: 0.7rem; font-weight: 800; color: var(--text-dim); text-transform: uppercase;">Direct Phone</label>
                            <input type="tel" name="phone" style="background: white; border: 1px solid #f1f5f9; padding: 1rem; border-radius: 12px; font-weight: 600;" value="<?php echo htmlspecialchars($engineer['phone']); ?>">
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <label style="font-size: 0.7rem; font-weight: 800; color: var(--text-dim); text-transform: uppercase;">Professional License <i class="fas fa-shield-alt verify-icon"></i></label>
                            <input type="text" name="license_number" style="background: white; border: 1px solid #f1f5f9; padding: 1rem; border-radius: 12px; font-weight: 600;" value="<?php echo htmlspecialchars($engineer['license_number']); ?>" placeholder="e.g. LIC-XXXX">
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 1rem;">
                         <label style="font-size: 0.7rem; font-weight: 800; color: var(--text-dim); text-transform: uppercase;">Professional Bio</label>
                         <textarea id="bioTextArea" name="bio" style="background: white; border: 1px solid #f1f5f9; padding: 1.5rem; border-radius: 16px; font-weight: 500; min-height: 150px; line-height: 1.6;"><?php echo htmlspecialchars($engineer['bio'] ?: 'Building high-performance structural systems with precision and architectural integrity.'); ?></textarea>
                    </div>

                    <div class="btn-save-wrap" style="margin-top: 2rem;">
                        <button type="submit" class="btn-profile-save" id="saveProfileBtn">
                            <i class="fas fa-save"></i> <span>Sync Changes</span>
                        </button>
                    </div>
                </form>
            </section>

            <!-- Expertise Section -->
            <section class="glass-card section-reveal">
                <div class="section-header">
                    <div class="section-icon"><i class="fas fa-brain"></i></div>
                    <h3 class="section-title">Core Expertise</h3>
                </div>
                
                <div class="skills-container">
                    <div class="skill-item">
                        <div class="skill-ring-wrapper">
                            <svg class="skill-svg" width="100" height="100">
                                <circle class="skill-circle-bg" cx="50" cy="50" r="40"></circle>
                                <circle class="skill-circle-progress" cx="50" cy="50" r="40" data-pct="92"></circle>
                            </svg>
                            <span class="skill-value">92%</span>
                        </div>
                        <span class="skill-name">Structural Analysis</span>
                    </div>
                    <div class="skill-item">
                        <div class="skill-ring-wrapper">
                            <svg class="skill-svg" width="100" height="100">
                                <circle class="skill-circle-bg" cx="50" cy="50" r="40"></circle>
                                <circle class="skill-circle-progress" cx="50" cy="50" r="40" data-pct="85"></circle>
                            </svg>
                            <span class="skill-value">85%</span>
                        </div>
                        <span class="skill-name">RCC Design</span>
                    </div>
                    <div class="skill-item">
                        <div class="skill-ring-wrapper">
                            <svg class="skill-svg" width="100" height="100">
                                <circle class="skill-circle-bg" cx="50" cy="50" r="40"></circle>
                                <circle class="skill-circle-progress" cx="50" cy="50" r="40" data-pct="78"></circle>
                            </svg>
                            <span class="skill-value">78%</span>
                        </div>
                        <span class="skill-name">Site Supervision</span>
                    </div>
                    <div class="skill-item">
                        <div class="skill-ring-wrapper">
                            <svg class="skill-svg" width="100" height="100">
                                <circle class="skill-circle-bg" cx="50" cy="50" r="40"></circle>
                                <circle class="skill-circle-progress" cx="50" cy="50" r="40" data-pct="95"></circle>
                            </svg>
                            <span class="skill-value">95%</span>
                        </div>
                        <span class="skill-name">Seismic Engineering</span>
                    </div>
                    <div class="skill-item">
                        <div class="skill-ring-wrapper">
                            <svg class="skill-svg" width="100" height="100">
                                <circle class="skill-circle-bg" cx="50" cy="50" r="40"></circle>
                                <circle class="skill-circle-progress" cx="50" cy="50" r="40" data-pct="95"></circle>
                            </svg>
                            <span class="skill-value">95%</span>
                        </div>
                        <span class="skill-name">Cost Estimation</span>
                    </div>
                </div>
            </section>

            <!-- Trust & Credentials Section -->
            <section class="glass-card section-reveal">
                <div class="section-header">
                    <div class="section-icon"><i class="fas fa-shield-check"></i></div>
                    <h3 class="section-title">Verified Credentials</h3>
                </div>
                
                <div class="trust-grid">
                    <div class="trust-badge-card">
                        <div class="trust-icon"><i class="fas fa-id-badge"></i></div>
                        <div class="trust-label">Govt Licensed</div>
                    </div>
                    <div class="trust-badge-card">
                        <div class="trust-icon"><i class="fas fa-building"></i></div>
                        <div class="trust-label">ISO 9001 Certified</div>
                    </div>
                    <div class="trust-badge-card">
                        <div class="trust-icon"><i class="fas fa-file-contract"></i></div>
                        <div class="trust-label">Insured Practice</div>
                    </div>
                    <div class="trust-badge-card">
                        <div class="trust-icon"><i class="fas fa-hard-hat"></i></div>
                        <div class="trust-label">Safety Compliant</div>
                    </div>
                </div>
            </section>



            <!-- Construction Lifecycle Timeline -->
            <section class="glass-card section-reveal">
                <div class="section-header">
                    <div class="section-icon"><i class="fas fa-stream"></i></div>
                    <h3 class="section-title">My Project Lifecycle</h3>
                </div>
                
                <div class="lifecycle-container">
                    <div class="lc-node">
                        <div class="lc-marker">01</div>
                        <div class="lc-info">
                            <h4>Feasibility & Planning</h4>
                            <p>Initial site survey, soil testing, and architectural feasibility reports to ensure a solid foundation.</p>
                        </div>
                    </div>
                    <div class="lc-node">
                        <div class="lc-marker">02</div>
                        <div class="lc-info">
                            <h4>Design & Approval</h4>
                            <p>Creating structural blueprints and liaising with municipal bodies for legal clearances.</p>
                        </div>
                    </div>
                    <div class="lc-node">
                        <div class="lc-marker">03</div>
                        <div class="lc-info">
                            <h4>Execution & Monitoring</h4>
                            <p>On-site supervision, quality control checks at every slab level, and material management.</p>
                        </div>
                    </div>
                    <div class="lc-node">
                        <div class="lc-marker">04</div>
                        <div class="lc-info">
                            <h4>Handover & Closure</h4>
                            <p>Final walkthrough, defect rectification, and issuing completion certificates.</p>
                        </div>
                    </div>
                </div>
            </section>





            <!-- Performance Insights (Gamification) -->
            <section class="glass-card section-reveal">
                <div class="section-header">
                    <div class="section-icon"><i class="fas fa-trophy"></i></div>
                    <h3 class="section-title">Achievements & Badges</h3>
                </div>
                
                <div class="gamification-grid">
                    <div class="game-badge earned" title="Completed 10+ Projects">
                        <div class="badge-icon"><i class="fas fa-medal"></i></div>
                        <span style="font-size: 0.7rem; font-weight: 700;">Veteran Builder</span>
                    </div>
                    <div class="game-badge earned" title="100% Safety Record">
                        <div class="badge-icon"><i class="fas fa-hard-hat"></i></div>
                        <span style="font-size: 0.7rem; font-weight: 700;">Safety First</span>
                    </div>
                    <div class="game-badge" title="Under 5% Budget Deviation">
                        <div class="badge-icon"><i class="fas fa-wallet"></i></div>
                        <span style="font-size: 0.7rem; font-weight: 700;">Cost Master</span>
                    </div>
                    <div class="game-badge earned" title="5 Star Average Rating">
                        <div class="badge-icon"><i class="fas fa-star"></i></div>
                        <span style="font-size: 0.7rem; font-weight: 700;">Top Rated</span>
                    </div>
                    <!-- Add Achievement Button -->
                    <div class="game-badge add-new" title="Add New Achievement" style="cursor: pointer; opacity: 1;" onclick="addNewBadge()">
                        <div class="badge-icon" style="border: 2px dashed #cbd5e1; background: transparent; transition: all 0.3s ease;">
                            <i class="fas fa-plus" style="color: #94a3b8;"></i>
                        </div>
                        <span style="font-size: 0.7rem; font-weight: 700; color: #94a3b8;">Add New</span>
                    </div>
                </div>
            </section>


        </div>
    </main>



    <script>
        // GSAP Initialization
        gsap.registerPlugin(ScrollTrigger);

        document.addEventListener('DOMContentLoaded', () => {
            // Reveal Cards on Scroll
            gsap.utils.toArray('.section-reveal').forEach(card => {
                gsap.to(card, {
                    scrollTrigger: {
                        trigger: card,
                        start: "top 85%",
                        toggleActions: "play none none none"
                    },
                    opacity: 1,
                    y: 0,
                    duration: 1,
                    ease: "power2.out"
                });
            });

            // Count up Animations
            const stats = document.querySelectorAll('.stat-value');
            stats.forEach(stat => {
                const target = parseInt(stat.getAttribute('data-target'));
                gsap.to(stat, {
                    innerText: target,
                    duration: 2,
                    snap: { innerText: 1 },
                    ease: "power1.inOut"
                });
            });

            // Rating Animation
            gsap.to("#ratingDisplay", {
                innerText: 4.9,
                duration: 2,
                snap: { innerText: 0.1 },
                ease: "power3.out"
            });

            // Skill Circles Animation
            ScrollTrigger.create({
                trigger: ".skills-container",
                start: "top 80%",
                onEnter: () => {
                    document.querySelectorAll('.skill-circle-progress').forEach(circle => {
                        const pct = circle.getAttribute('data-pct');
                        const radius = circle.r.baseVal.value;
                        const circumference = 2 * Math.PI * radius;
                        circle.style.strokeDasharray = circumference;
                        circle.style.strokeDashoffset = circumference - (pct / 100) * circumference;
                    });
                }
            });

            // 3D Tilt Effect for Identity Card
            const idCard = document.getElementById('tiltCard');
            document.addEventListener('mousemove', (e) => {
                const rect = idCard.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                if (x < 0 || x > rect.width || y < 0 || y > rect.height) {
                    idCard.style.transform = `rotateX(0deg) rotateY(0deg)`;
                    return;
                }

                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                const rotateX = ((y - centerY) / centerY) * -8;
                const rotateY = ((x - centerX) / centerX) * 8;

                idCard.style.transform = `perspective(2000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
            });

            idCard.addEventListener('mouseleave', () => {
                idCard.style.transform = `rotateX(0deg) rotateY(0deg)`;
            });

            // Save Button Morph
            const saveBtn = document.getElementById('saveProfileBtn');
            const form = document.getElementById('profileForm');
            
            form.addEventListener('submit', (e) => {
                // We'll let the form submit normally, but this illustrates the visual logic if AJAX'd
                saveBtn.classList.add('success');
                saveBtn.querySelector('span').innerText = "Identity Synced!";
                saveBtn.querySelector('i').className = "fas fa-check-double";
            });

            // Check for update success from URL
            if (window.location.search.includes('updated=true')) {
                const btn = document.getElementById('saveProfileBtn');
                btn.classList.add('success');
                btn.querySelector('span').innerText = "Identity Synced!";
                btn.querySelector('i').className = "fas fa-check-double";
                setTimeout(() => {
                    btn.classList.remove('success');
                    btn.querySelector('span').innerText = "Sync Changes";
                    btn.querySelector('i').className = "fas fa-save";
                }, 3000);
            }
        });

        // Add New Badge Function
        function addNewBadge() {
            const name = prompt("Enter Achievement Name:");
            if (name) {
                const grid = document.querySelector('.gamification-grid');
                const newBadge = document.createElement('div');
                newBadge.className = 'game-badge earned';
                newBadge.title = name;
                newBadge.innerHTML = `
                    <div class="badge-icon" style="background: linear-gradient(135deg, #e0f2fe 0%, #dbeafe 100%); color: #3b82f6; box-shadow: 0 10px 20px rgba(59, 130, 246, 0.15);">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <span style="font-size: 0.7rem; font-weight: 700;">${name}</span>
                `;
                // Insert before the "Add New" button (last element)
                const addButton = grid.querySelector('.add-new');
                grid.insertBefore(newBadge, addButton);
            }
        }

        // 3D Background (City) - Matches engineer.php
        const initBackground3D = () => {
            const container = document.getElementById('canvas-container');
            if (!container) return;
            const scene = new THREE.Scene();
            scene.background = new THREE.Color('#f6f7f2');
            const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
            camera.position.z = 8;
            camera.position.y = 2;
            const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            container.appendChild(renderer.domElement);
            const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
            scene.add(ambientLight);
            const mainLight = new THREE.DirectionalLight(0xffffff, 0.8);
            mainLight.position.set(10, 10, 10);
            scene.add(mainLight);
            const blueLight = new THREE.PointLight(0x3d5a49, 0.5);
            blueLight.position.set(-5, 5, 5);

            const cityGroup = new THREE.Group();
            scene.add(cityGroup);
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

            const houseGroup = new THREE.Group();
            const baseGeo = new THREE.BoxGeometry(2, 2, 2);
            const baseLine = new THREE.LineSegments(new THREE.EdgesGeometry(baseGeo), new THREE.LineBasicMaterial({ color: 0x294033, linewidth: 2 }));
            houseGroup.add(baseLine);
            const roofGeo = new THREE.ConeGeometry(1.5, 1.2, 4);
            const roofLine = new THREE.LineSegments(new THREE.EdgesGeometry(roofGeo), new THREE.LineBasicMaterial({ color: 0x3d5a49, linewidth: 2 }));
            roofLine.position.y = 1.6;
            roofLine.rotation.y = Math.PI / 4;
            houseGroup.add(roofLine);

            const floatGroup = new THREE.Group();
            floatGroup.add(houseGroup);
            floatGroup.position.set(0, 0, 2);
            scene.add(floatGroup);

            let mouseX = 0;
            let mouseY = 0;
            document.addEventListener('mousemove', (event) => {
                mouseX = (event.clientX - window.innerWidth / 2) * 0.001;
                mouseY = (event.clientY - window.innerHeight / 2) * 0.001;
            });

            // Sync with scroll
            let scrollY = 0;
            window.addEventListener('scroll', () => {
                scrollY = window.scrollY * 0.0005; 
            });

            const animate = () => {
                requestAnimationFrame(animate);
                cityGroup.rotation.y += 0.001;
                floatGroup.rotation.y += 0.005;
                floatGroup.position.y = Math.sin(Date.now() * 0.001) * 0.5 + 0.5;
                
                // Mouse parallax
                cityGroup.rotation.x += 0.05 * (mouseY - cityGroup.rotation.x);
                // Combine auto-rotation with mouse interaction for Y
                
                camera.position.y = 2 - scrollY * 2;
                // camera.position.z = 8 + scrollY * 5; 
                
                renderer.render(scene, camera);
            };
            animate();

            window.addEventListener('resize', () => {
                camera.aspect = window.innerWidth / window.innerHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(window.innerWidth, window.innerHeight);
            });
        };
        initBackground3D();

        // 3D Brand Animation Logic
        document.addEventListener('DOMContentLoaded', () => {
            const brandContainer = document.getElementById('brand-3d-text');
            if(brandContainer) {
                const originalText = "CONSTRUCTA";
                brandContainer.innerHTML = originalText.split('').map(char => `<span>${char}</span>`).join('');
                const brandSpans = brandContainer.querySelectorAll('span');

                const animateBrandLoop = () => {
                    // Show letters one by one
                    brandSpans.forEach((span, i) => {
                        setTimeout(() => {
                            span.classList.add('visible');
                        }, i * 150);
                    });

                    // Hide letters one by one after a delay
                    setTimeout(() => {
                        brandSpans.forEach((span, i) => {
                            setTimeout(() => {
                                span.classList.remove('visible');
                            }, i * 100);
                        });

                        // Restart loop after hiding
                        setTimeout(animateBrandLoop, brandSpans.length * 100 + 1000);
                    }, brandSpans.length * 150 + 4000);
                };

                // Start the animation
                animateBrandLoop();
            }
        });
    </script>
</body>
</html>
