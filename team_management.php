<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'engineer') { header('Location: login.html'); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Management - Constructa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg-color: #f6f7f2; --text-dark: #121212; --text-gray: #555555; --primary-green: #294033; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-color); min-height: 100vh; display: flex; flex-direction: column; }
        header { padding: 1.5rem 3rem; display: flex; justify-content: space-between; max-width: 1600px; margin: 0 auto; width: 100%; background: rgba(246, 247, 242, 0.9); position: sticky; top: 0; z-index: 100; }
        .logo { display: flex; align-items: center; gap: 0.5rem; font-size: 1.25rem; font-weight: 700; color: var(--primary-green); text-decoration: none; }
        nav { display: flex; gap: 2rem; }
        nav a { text-decoration: none; color: var(--text-dark); font-weight: 500; }
        main { flex: 1; max-width: 1400px; margin: 0 auto; width: 100%; padding: 3rem; }
        .page-header { text-align: center; margin-bottom: 3rem; }
        .page-title { font-size: 3rem; font-weight: 700; margin-bottom: 0.5rem; background: linear-gradient(135deg, #294033, #3d5a49); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        .team-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 2rem; }
        .team-card { background: white; border-radius: 20px; padding: 2rem; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.06); transition: all 0.3s; }
        .team-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.1); }
        .team-avatar { width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; font-weight: 700; }
        .team-name { font-size: 1.3rem; font-weight: 700; margin-bottom: 0.3rem; }
        .team-role { color: var(--text-gray); margin-bottom: 1rem; }
        .team-stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin: 1.5rem 0; }
        .stat { text-align: center; }
        .stat-value { font-size: 1.5rem; font-weight: 700; color: var(--primary-green); }
        .stat-label { font-size: 0.8rem; color: var(--text-gray); }
        .status-indicator { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 0.5rem; }
        .status-active { background: #10b981; }
        .status-offline { background: #ef4444; }
        .btn-contact { background: #e5e7eb; color: var(--text-dark); padding: 0.6rem 1.2rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%; margin-top: 1rem; }
    </style>
</head>
<body>
    <header>
        <a href="engineer.php" class="logo"><i class="far fa-building"></i> Constructa</a>
        <nav>
            <a href="engineer.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="login.html">Logout</a>
        </nav>
    </header>
    <main>
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-users"></i> Team Management</h1>
            <p style="color: var(--text-gray); font-size: 1.1rem;">Manage your on-site teams, assign tasks, and track roles</p>
        </div>

        <div class="team-grid">
            <div class="team-card">
                <div class="team-avatar" style="background: linear-gradient(135deg, #be123c, #9f1239);">MJ</div>
                <h3 class="team-name">Mike Johnson</h3>
                <p class="team-role"><i class="fas fa-hard-hat"></i> Site Supervisor</p>
                <p style="font-size: 0.85rem; color: var(--text-gray);"><span class="status-indicator status-active"></span>Active on site</p>
                <div class="team-stats">
                    <div class="stat"><div class="stat-value">3</div><div class="stat-label">Active Projects</div></div>
                    <div class="stat"><div class="stat-value">12</div><div class="stat-label">Tasks Assigned</div></div>
                </div>
                <button class="btn-contact"><i class="fas fa-phone"></i> Contact</button>
            </div>

            <div class="team-card">
                <div class="team-avatar" style="background: linear-gradient(135deg, #0f766e, #115e59);">EW</div>
                <h3 class="team-name">Emily Wilson</h3>
                <p class="team-role"><i class="fas fa-drafting-compass"></i> CAD Designer</p>
                <p style="font-size: 0.85rem; color: var(--text-gray);"><span class="status-indicator status-active"></span>Active on site</p>
                <div class="team-stats">
                    <div class="stat"><div class="stat-value">5</div><div class="stat-label">Active Projects</div></div>
                    <div class="stat"><div class="stat-value">8</div><div class="stat-label">Tasks Assigned</div></div>
                </div>
                <button class="btn-contact"><i class="fas fa-phone"></i> Contact</button>
            </div>

            <div class="team-card">
                <div class="team-avatar" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">DB</div>
                <h3 class="team-name">David Brown</h3>
                <p class="team-role"><i class="fas fa-tools"></i> Lead Carpenter</p>
                <p style="font-size: 0.85rem; color: var(--text-gray);"><span class="status-indicator status-offline"></span>Off duty</p>
                <div class="team-stats">
                    <div class="stat"><div class="stat-value">2</div><div class="stat-label">Active Projects</div></div>
                    <div class="stat"><div class="stat-value">6</div><div class="stat-label">Tasks Assigned</div></div>
                </div>
                <button class="btn-contact"><i class="fas fa-phone"></i> Contact</button>
            </div>

            <div class="team-card">
                <div class="team-avatar" style="background: linear-gradient(135deg, #10b981, #059669);">AG</div>
                <h3 class="team-name">Anna Garcia</h3>
                <p class="team-role"><i class="fas fa-clipboard-check"></i> Quality Inspector</p>
                <p style="font-size: 0.85rem; color: var(--text-gray);"><span class="status-indicator status-active"></span>Active on site</p>
                <div class="team-stats">
                    <div class="stat"><div class="stat-value">4</div><div class="stat-label">Active Projects</div></div>
                    <div class="stat"><div class="stat-value">10</div><div class="stat-label">Tasks Assigned</div></div>
                </div>
                <button class="btn-contact"><i class="fas fa-phone"></i> Contact</button>
            </div>

            <div class="team-card">
                <div class="team-avatar" style="background: linear-gradient(135deg, #f59e0b, #d97706);">TL</div>
                <h3 class="team-name">Tom Lee</h3>
                <p class="team-role"><i class="fas fa-bolt"></i> Electrician</p>
                <p style="font-size: 0.85rem; color: var(--text-gray);"><span class="status-indicator status-active"></span>Active on site</p>
                <div class="team-stats">
                    <div class="stat"><div class="stat-value">3</div><div class="stat-label">Active Projects</div></div>
                    <div class="stat"><div class="stat-value">7</div><div class="stat-label">Tasks Assigned</div></div>
                </div>
                <button class="btn-contact"><i class="fas fa-phone"></i> Contact</button>
            </div>

            <div class="team-card">
                <div class="team-avatar" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">SM</div>
                <h3 class="team-name">Sarah Miller</h3>
                <p class="team-role"><i class="fas fa-wrench"></i> Plumber</p>
                <p style="font-size: 0.85rem; color: var(--text-gray);"><span class="status-indicator status-offline"></span>Off duty</p>
                <div class="team-stats">
                    <div class="stat"><div class="stat-value">2</div><div class="stat-label">Active Projects</div></div>
                    <div class="stat"><div class="stat-value">5</div><div class="stat-label">Tasks Assigned</div></div>
                </div>
                <button class="btn-contact"><i class="fas fa-phone"></i> Contact</button>
            </div>
        </div>
    </main>
</body>
</html>
