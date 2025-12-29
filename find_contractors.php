<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'homeowner') { header('Location: login.html'); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Contractors - Constructa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg-color: #f6f7f2; --text-dark: #121212; --text-gray: #555555; --primary-green: #294033; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-color); color: var(--text-dark); min-height: 100vh; display: flex; flex-direction: column; }
        header { padding: 1.5rem 3rem; display: flex; justify-content: space-between; max-width: 1600px; margin: 0 auto; width: 100%; background: rgba(246, 247, 242, 0.9); position: sticky; top: 0; z-index: 100; }
        .logo { display: flex; align-items: center; gap: 0.5rem; font-size: 1.25rem; font-weight: 700; color: var(--primary-green); text-decoration: none; }
        nav { display: flex; gap: 2rem; }
        nav a { text-decoration: none; color: var(--text-dark); font-weight: 500; transition: color 0.2s; }
        nav a:hover { color: var(--primary-green); }
        main { flex: 1; max-width: 1400px; margin: 0 auto; width: 100%; padding: 3rem; }
        .page-header { text-align: center; margin-bottom: 3rem; }
        .page-title { font-size: 3rem; font-weight: 700; margin-bottom: 0.5rem; background: linear-gradient(135deg, #294033, #3d5a49); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .page-subtitle { color: var(--text-gray); font-size: 1.1rem; }
        .contractors-list { display: grid; gap: 1.5rem; }
        .contractor-card { background: white; border-radius: 16px; padding: 2rem; display: flex; gap: 2rem; align-items: center; box-shadow: 0 5px 20px rgba(0,0,0,0.06); transition: all 0.3s; }
        .contractor-card:hover { transform: translateX(10px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .contractor-icon { width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #ec4899, #db2777); display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; flex-shrink: 0; }
        .contractor-info { flex: 1; }
        .contractor-info h3 { font-size: 1.5rem; margin-bottom: 0.5rem; }
        .contractor-info p { color: var(--text-gray); margin-bottom: 0.8rem; }
        .contractor-meta { display: flex; gap: 2rem; font-size: 0.9rem; }
        .btn-hire { background: linear-gradient(135deg, #294033, #3d5a49); color: white; padding: 1rem 2rem; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; }
    </style>
</head>
<body>
    <header>
        <a href="homeowner.php" class="logo"><i class="far fa-building"></i> Constructa</a>
        <nav>
            <a href="homeowner.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="login.html">Logout</a>
        </nav>
    </header>
    <main>
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-tools"></i> Find Contractors</h1>
            <p class="page-subtitle">Hire reliable contractors for your specific needs</p>
        </div>
        <div class="contractors-list">
            <div class="contractor-card">
                <div class="contractor-icon" style="background: linear-gradient(135deg, #ec4899, #db2777);"><i class="fas fa-hammer"></i></div>
                <div class="contractor-info">
                    <h3>BuildPro Construction</h3>
                    <p>Full-service construction company specializing in residential and commercial projects</p>
                    <div class="contractor-meta">
                        <span><i class="fas fa-star" style="color: #f59e0b;"></i> 4.9/5</span>
                        <span><i class="fas fa-briefcase"></i> 120+ Projects</span>
                        <span><i class="fas fa-clock"></i> 15 Years Experience</span>
                    </div>
                </div>
                <button class="btn-hire"><i class="fas fa-check-circle"></i> Hire Now</button>
            </div>
            <div class="contractor-card">
                <div class="contractor-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb);"><i class="fas fa-hard-hat"></i></div>
                <div class="contractor-info">
                    <h3>Elite Renovations</h3>
                    <p>Expert renovation and remodeling services for homes and offices</p>
                    <div class="contractor-meta">
                        <span><i class="fas fa-star" style="color: #f59e0b;"></i> 4.8/5</span>
                        <span><i class="fas fa-briefcase"></i> 95+ Projects</span>
                        <span><i class="fas fa-clock"></i> 12 Years Experience</span>
                    </div>
                </div>
                <button class="btn-hire"><i class="fas fa-check-circle"></i> Hire Now</button>
            </div>
            <div class="contractor-card">
                <div class="contractor-icon" style="background: linear-gradient(135deg, #10b981, #059669);"><i class="fas fa-building"></i></div>
                <div class="contractor-info">
                    <h3>GreenBuild Solutions</h3>
                    <p>Sustainable construction with eco-friendly materials and practices</p>
                    <div class="contractor-meta">
                        <span><i class="fas fa-star" style="color: #f59e0b;"></i> 5.0/5</span>
                        <span><i class="fas fa-briefcase"></i> 78+ Projects</span>
                        <span><i class="fas fa-clock"></i> 10 Years Experience</span>
                    </div>
                </div>
                <button class="btn-hire"><i class="fas fa-check-circle"></i> Hire Now</button>
            </div>
            <div class="contractor-card">
                <div class="contractor-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);"><i class="fas fa-city"></i></div>
                <div class="contractor-info">
                    <h3>UrbanCraft Builders</h3>
                    <p>Specialized in modern urban development and high-rise construction</p>
                    <div class="contractor-meta">
                        <span><i class="fas fa-star" style="color: #f59e0b;"></i> 4.7/5</span>
                        <span><i class="fas fa-briefcase"></i> 145+ Projects</span>
                        <span><i class="fas fa-clock"></i> 18 Years Experience</span>
                    </div>
                </div>
                <button class="btn-hire"><i class="fas fa-check-circle"></i> Hire Now</button>
            </div>
            <div class="contractor-card">
                <div class="contractor-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);"><i class="fas fa-home"></i></div>
                <div class="contractor-info">
                    <h3>Luxury Home Builders</h3>
                    <p>Custom luxury home construction with premium finishes and craftsmanship</p>
                    <div class="contractor-meta">
                        <span><i class="fas fa-star" style="color: #f59e0b;"></i> 5.0/5</span>
                        <span><i class="fas fa-briefcase"></i> 56+ Projects</span>
                        <span><i class="fas fa-clock"></i> 20 Years Experience</span>
                    </div>
                </div>
                <button class="btn-hire"><i class="fas fa-check-circle"></i> Hire Now</button>
            </div>
        </div>
    </main>
</body>
</html>
