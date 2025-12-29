<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'engineer') { header('Location: login.html'); exit(); }
$username = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Engineer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Projects - Constructa</title>
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
        
        .projects-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 2rem; }
        .project-card { background: white; border-radius: 20px; padding: 2rem; box-shadow: 0 5px 20px rgba(0,0,0,0.06); transition: all 0.3s; }
        .project-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.1); }
        .project-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; }
        .project-name { font-size: 1.4rem; font-weight: 700; }
        .status-badge { padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-planning { background: #dbeafe; color: #1e40af; }
        .status-review { background: #fef3c7; color: #92400e; }
        .progress-section { margin: 1.5rem 0; }
        .progress-label { display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.9rem; color: var(--text-gray); }
        .progress-bar { height: 8px; background: #e5e7eb; border-radius: 10px; overflow: hidden; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #10b981, #059669); transition: width 0.3s; }
        .project-details { display: grid; gap: 0.8rem; margin: 1rem 0; }
        .detail { display: flex; align-items: center; gap: 0.5rem; color: var(--text-gray); font-size: 0.9rem; }
        .btn-view { background: linear-gradient(135deg, #294033, #3d5a49); color: white; padding: 0.8rem 1.5rem; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; width: 100%; margin-top: 1rem; }
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
            <h1 class="page-title"><i class="fas fa-hard-hat"></i> My Projects</h1>
            <p style="color: var(--text-gray); font-size: 1.1rem;">Track progress of ongoing construction and design projects</p>
        </div>

        <div class="projects-grid">
            <div class="project-card">
                <div class="project-header">
                    <h3 class="project-name">Sunset Villa</h3>
                    <span class="status-badge status-active">Active</span>
                </div>
                <div class="progress-section">
                    <div class="progress-label">
                        <span>Overall Progress</span>
                        <span style="font-weight: 700; color: #10b981;">65%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 65%;"></div>
                    </div>
                </div>
                <div class="project-details">
                    <div class="detail"><i class="fas fa-user"></i> Client: John Davidson</div>
                    <div class="detail"><i class="fas fa-map-marker-alt"></i> San Francisco, CA</div>
                    <div class="detail"><i class="fas fa-calendar"></i> Started: Jan 15, 2025</div>
                    <div class="detail"><i class="fas fa-clock"></i> Deadline: Jun 30, 2025</div>
                </div>
                <button class="btn-view"><i class="fas fa-folder-open"></i> View Project Details</button>
            </div>

            <div class="project-card">
                <div class="project-header">
                    <h3 class="project-name">Downtown Office Complex</h3>
                    <span class="status-badge status-planning">Planning</span>
                </div>
                <div class="progress-section">
                    <div class="progress-label">
                        <span>Overall Progress</span>
                        <span style="font-weight: 700; color: #3b82f6;">25%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 25%; background: linear-gradient(90deg, #3b82f6, #2563eb);"></div>
                    </div>
                </div>
                <div class="project-details">
                    <div class="detail"><i class="fas fa-user"></i> Client: Sarah Martinez</div>
                    <div class="detail"><i class="fas fa-map-marker-alt"></i> Los Angeles, CA</div>
                    <div class="detail"><i class="fas fa-calendar"></i> Started: Dec 20, 2024</div>
                    <div class="detail"><i class="fas fa-clock"></i> Deadline: Aug 15, 2025</div>
                </div>
                <button class="btn-view"><i class="fas fa-folder-open"></i> View Project Details</button>
            </div>

            <div class="project-card">
                <div class="project-header">
                    <h3 class="project-name">Green Homes Community</h3>
                    <span class="status-badge status-active">Active</span>
                </div>
                <div class="progress-section">
                    <div class="progress-label">
                        <span>Overall Progress</span>
                        <span style="font-weight: 700; color: #10b981;">45%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 45%;"></div>
                    </div>
                </div>
                <div class="project-details">
                    <div class="detail"><i class="fas fa-user"></i> Client: Robert Chen</div>
                    <div class="detail"><i class="fas fa-map-marker-alt"></i> Seattle, WA</div>
                    <div class="detail"><i class="fas fa-calendar"></i> Started: Nov 10, 2024</div>
                    <div class="detail"><i class="fas fa-clock"></i> Deadline: May 20, 2025</div>
                </div>
                <button class="btn-view"><i class="fas fa-folder-open"></i> View Project Details</button>
            </div>

            <div class="project-card">
                <div class="project-header">
                    <h3 class="project-name">Heritage Building Restoration</h3>
                    <span class="status-badge status-review">Under Review</span>
                </div>
                <div class="progress-section">
                    <div class="progress-label">
                        <span>Overall Progress</span>
                        <span style="font-weight: 700; color: #f59e0b;">85%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 85%; background: linear-gradient(90deg, #f59e0b, #d97706);"></div>
                    </div>
                </div>
                <div class="project-details">
                    <div class="detail"><i class="fas fa-user"></i> Client: Linda Patel</div>
                    <div class="detail"><i class="fas fa-map-marker-alt"></i> Portland, OR</div>
                    <div class="detail"><i class="fas fa-calendar"></i> Started: Aug 5, 2024</div>
                    <div class="detail"><i class="fas fa-clock"></i> Deadline: Feb 28, 2025</div>
                </div>
                <button class="btn-view"><i class="fas fa-folder-open"></i> View Project Details</button>
            </div>
        </div>
    </main>
</body>
</html>
