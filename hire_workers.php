<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'homeowner') { header('Location: login.html'); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hire Workers - Constructa</title>
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
        .workers-categories { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 2rem; }
        .category-card { background: white; border-radius: 20px; padding: 2.5rem; text-align: center; transition: all 0.3s; box-shadow: 0 5px 20px rgba(0,0,0,0.06); }
        .category-card:hover { transform: translateY(-10px); box-shadow: 0 15px 40px rgba(0,0,0,0.12); }
        .category-icon { width: 80px; height: 80px; margin: 0 auto 1.5rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: white; }
        .category-title { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.8rem; }
        .category-desc { color: var(--text-gray); margin-bottom: 1rem; line-height: 1.6; }
        .category-rate { font-size: 1.3rem; font-weight: 700; color: var(--primary-green); margin-bottom: 1.5rem; }
        .btn-find { background: linear-gradient(135deg, #294033, #3d5a49); color: white; padding: 1rem 2rem; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; width: 100%; }
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
            <h1 class="page-title"><i class="fas fa-user-friends"></i> Hire Workers</h1>
            <p style="color: var(--text-gray); font-size: 1.1rem;">Find skilled laborers, electricians, and plumbers</p>
        </div>
        <div class="workers-categories">
            <div class="category-card">
                <div class="category-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);"><i class="fas fa-bolt"></i></div>
                <h3 class="category-title">Electricians</h3>
                <p class="category-desc">Licensed electricians for wiring, repairs, and installations</p>
                <div class="category-rate">$45-75/hr</div>
                <button class="btn-find"><i class="fas fa-search"></i> Find Electrician</button>
            </div>
            <div class="category-card">
                <div class="category-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb);"><i class="fas fa-wrench"></i></div>
                <h3 class="category-title">Plumbers</h3>
                <p class="category-desc">Expert plumbing services for repairs and new installations</p>
                <div class="category-rate">$50-80/hr</div>
                <button class="btn-find"><i class="fas fa-search"></i> Find Plumber</button>
            </div>
            <div class="category-card">
                <div class="category-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);"><i class="fas fa-paint-roller"></i></div>
                <h3 class="category-title">Painters</h3>
                <p class="category-desc">Professional painters for interior and exterior work</p>
                <div class="category-rate">$30-55/hr</div>
                <button class="btn-find"><i class="fas fa-search"></i> Find Painter</button>
            </div>
            <div class="category-card">
                <div class="category-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);"><i class="fas fa-hammer"></i></div>
                <h3 class="category-title">Carpenters</h3>
                <p class="category-desc">Skilled carpentry for framing, cabinets, and furniture</p>
                <div class="category-rate">$40-70/hr</div>
                <button class="btn-find"><i class="fas fa-search"></i> Find Carpenter</button>
            </div>
            <div class="category-card">
                <div class="category-icon" style="background: linear-gradient(135deg, #10b981, #059669);"><i class="fas fa-hard-hat"></i></div>
                <h3 class="category-title">General Laborers</h3>
                <p class="category-desc">Reliable workers for demolition, cleanup, and assistance</p>
                <div class="category-rate">$25-40/hr</div>
                <button class="btn-find"><i class="fas fa-search"></i> Find Laborer</button>
            </div>
            <div class="category-card">
                <div class="category-icon" style="background: linear-gradient(135deg, #ec4899, #db2777);"><i class="fas fa-trowel"></i></div>
                <h3 class="category-title">Masons</h3>
                <p class="category-desc">Expert masonry for brickwork, stonework, and concrete</p>
                <div class="category-rate">$45-75/hr</div>
                <button class="btn-find"><i class="fas fa-search"></i> Find Mason</button>
            </div>
        </div>
    </main>
</body>
</html>
