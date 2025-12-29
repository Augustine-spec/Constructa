<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'engineer') { header('Location: login.html'); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resources - Constructa</title>
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
        
        .resources-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 2rem; }
        .resource-card { background: white; border-radius: 20px; padding: 2rem; box-shadow: 0 5px 20px rgba(0,0,0,0.06); transition: all 0.3s; }
        .resource-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.1); }
        .resource-icon { width: 70px; height: 70px; border-radius: 50%; margin: 0 auto 1.5rem; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: white; }
        .resource-title { font-size: 1.4rem; font-weight: 700; text-align: center; margin-bottom: 0.8rem; }
        .resource-desc { color: var(--text-gray); text-align: center; line-height: 1.6; margin-bottom: 1.5rem; }
        .resource-meta { display: flex; justify-content: space-around; padding-top: 1rem; border-top: 1px solid #f3f4f6; font-size: 0.85rem; color: var(--text-gray); }
        .btn-download { background: linear-gradient(135deg, #294033, #3d5a49); color: white; padding: 0.8rem 1.5rem; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; width: 100%; }
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
            <h1 class="page-title"><i class="fas fa-book"></i> Resources</h1>
            <p style="color: var(--text-gray); font-size: 1.1rem;">Access building codes, material specifications, and regulatory docs</p>
        </div>

        <div class="resources-grid">
            <div class="resource-card">
                <div class="resource-icon" style="background: linear-gradient(135deg, #64748b, #475569);"><i class="fas fa-file-pdf"></i></div>
                <h3 class="resource-title">IBC 2021 Building Code</h3>
                <p class="resource-desc">International Building Code 2021 edition - Complete reference guide</p>
                <div class="resource-meta">
                    <span><i class="fas fa-file"></i> PDF</span>
                    <span><i class="fas fa-download"></i> 2.4k downloads</span>
                </div>
                <button class="btn-download"><i class="fas fa-download"></i> Download</button>
            </div>

            <div class="resource-card">
                <div class="resource-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb);"><i class="fas fa-book-open"></i></div>
                <h3 class="resource-title">Seismic Design Manual</h3>
                <p class="resource-desc">Comprehensive guide for earthquake-resistant structural design</p>
                <div class="resource-meta">
                    <span><i class="fas fa-file"></i> PDF</span>
                    <span><i class="fas fa-download"></i> 1.8k downloads</span>
                </div>
                <button class="btn-download"><i class="fas fa-download"></i> Download</button>
            </div>

            <div class="resource-card">
                <div class="resource-icon" style="background: linear-gradient(135deg, #10b981, #059669);"><i class="fas fa-clipboard-list"></i></div>
                <h3 class="resource-title">Material Specifications</h3>
                <p class="resource-desc">Standard specifications for construction materials and testing</p>
                <div class="resource-meta">
                    <span><i class="fas fa-file"></i> PDF</span>
                    <span><i class="fas fa-download"></i> 3.1k downloads</span>
                </div>
                <button class="btn-download"><i class="fas fa-download"></i> Download</button>
            </div>

            <div class="resource-card">
                <div class="resource-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);"><i class="fas fa-hard-hat"></i></div>
                <h3 class="resource-title">OSHA Safety Standards</h3>
                <p class="resource-desc">Occupational safety and health regulations for construction sites</p>
                <div class="resource-meta">
                    <span><i class="fas fa-file"></i> PDF</span>
                    <span><i class="fas fa-download"></i> 1.5k downloads</span>
                </div>
                <button class="btn-download"><i class="fas fa-download"></i> Download</button>
            </div>

            <div class="resource-card">
                <div class="resource-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);"><i class="fas fa-leaf"></i></div>
                <h3 class="resource-title">LEED Green Building</h3>
                <p class="resource-desc">Leadership in Energy and Environmental Design certification guide</p>
                <div class="resource-meta">
                    <span><i class="fas fa-file"></i> PDF</span>
                    <span><i class="fas fa-download"></i> 2.2k downloads</span>
                </div>
                <button class="btn-download"><i class="fas fa-download"></i> Download</button>
            </div>

            <div class="resource-card">
                <div class="resource-icon" style="background: linear-gradient(135deg, #ec4899, #db2777);"><i class="fas fa-calculator"></i></div>
                <h3 class="resource-title">Load Calculation Tables</h3>
                <p class="resource-desc">Structural load calculation reference tables and formulas</p>
                <div class="resource-meta">
                    <span><i class="fas fa-file"></i> PDF</span>
                    <span><i class="fas fa-download"></i> 2.9k downloads</span>
                </div>
                <button class="btn-download"><i class="fas fa-download"></i> Download</button>
            </div>
        </div>
    </main>
</body>
</html>
