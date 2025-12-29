<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'homeowner') { header('Location: login.html'); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Engineer Directory - Constructa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg-color: #f6f7f2; --text-dark: #121212; --text-gray: #555555; --primary-green: #294033; --accent-green: #3d5a49; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-color); color: var(--text-dark); min-height: 100vh; display: flex; flex-direction: column; }
        header { padding: 1.5rem 3rem; display: flex; justify-content: space-between; align-items: center; max-width: 1600px; margin: 0 auto; width: 100%; background: rgba(246, 247, 242, 0.9); backdrop-filter: blur(10px); position: sticky; top: 0; z-index: 100; }
        .logo { display: flex; align-items: center; gap: 0.5rem; font-size: 1.25rem; font-weight: 700; color: var(--primary-green); text-decoration: none; }
        nav { display: flex; gap: 2rem; align-items: center; }
        nav a { text-decoration: none; color: var(--text-dark); font-weight: 500; transition: color 0.2s; }
        nav a:hover { color: var(--primary-green); }
        main { flex: 1; max-width: 1400px; margin: 0 auto; width: 100%; padding: 3rem; }
        .page-header { text-align: center; margin-bottom: 3rem; }
        .page-title { font-size: 3rem; font-weight: 700; margin-bottom: 0.5rem; background: linear-gradient(135deg, #294033 0%, #3d5a49 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .page-subtitle { color: var(--text-gray); font-size: 1.1rem; }
        
        .engineers-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 2rem; }
        .engineer-card { background: white; border-radius: 20px; padding: 2.5rem; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); transition: all 0.4s; }
        .engineer-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12); }
        .engineer-header { display: flex; align-items: center; gap: 1.5rem; margin-bottom: 1.5rem; }
        .engineer-avatar { width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #6366f1, #4f46e5); display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; font-weight: 700; }
        .engineer-info h3 { font-size: 1.5rem; margin-bottom: 0.3rem; }
        .engineer-info p { color: var(--text-gray); font-size: 0.9rem; }
        .engineer-stats { display: flex; gap: 1.5rem; margin: 1.5rem 0; }
        .stat { text-align: center; }
        .stat-value { font-size: 1.5rem; font-weight: 700; color: var(--primary-green); }
        .stat-label { font-size: 0.85rem; color: var(--text-gray); }
        .engineer-skills { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1.5rem; }
        .skill-tag { padding: 0.5rem 1rem; background: rgba(41, 64, 51, 0.08); border-radius: 20px; font-size: 0.85rem; font-weight: 500; }
        .btn-contact { background: linear-gradient(135deg, #294033, #3d5a49); color: white; padding: 1rem 2rem; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; width: 100%; transition: all 0.3s; }
        .btn-contact:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(41, 64, 51, 0.3); }
    </style>
</head>
<body>
    <header>
        <a href="homeowner.php" class="logo"><i class="far fa-building"></i> Constructa</a>
        <nav>
            <a href="homeowner.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="landingpage.html">Home</a>
            <a href="login.html">Logout</a>
        </nav>
    </header>

    <main>
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-user-tie"></i> Engineer Directory</h1>
            <p class="page-subtitle">Connect with vetted structural and civil engineers</p>
        </div>

        <div class="engineers-grid">
            <div class="engineer-card">
                <div class="engineer-header">
                    <div class="engineer-avatar">JD</div>
                    <div class="engineer-info">
                        <h3>John Davidson</h3>
                        <p><i class="fas fa-star" style="color: #f59e0b;"></i> 4.9 (127 reviews)</p>
                    </div>
                </div>
                <div class="engineer-stats">
                    <div class="stat"><div class="stat-value">15</div><div class="stat-label">Years Exp</div></div>
                    <div class="stat"><div class="stat-value">89</div><div class="stat-label">Projects</div></div>
                    <div class="stat"><div class="stat-value">$500</div><div class="stat-label">Per Day</div></div>
                </div>
                <div class="engineer-skills">
                    <span class="skill-tag">Structural Engineering</span>
                    <span class="skill-tag">Residential</span>
                    <span class="skill-tag">Commercial</span>
                </div>
                <button class="btn-contact"><i class="fas fa-phone"></i> Contact Engineer</button>
            </div>

            <div class="engineer-card">
                <div class="engineer-header">
                    <div class="engineer-avatar" style="background: linear-gradient(135deg, #ec4899, #db2777);">SM</div>
                    <div class="engineer-info">
                        <h3>Sarah Martinez</h3>
                        <p><i class="fas fa-star" style="color: #f59e0b;"></i> 4.8 (95 reviews)</p>
                    </div>
                </div>
                <div class="engineer-stats">
                    <div class="stat"><div class="stat-value">12</div><div class="stat-label">Years Exp</div></div>
                    <div class="stat"><div class="stat-value">76</div><div class="stat-label">Projects</div></div>
                    <div class="stat"><div class="stat-value">$450</div><div class="stat-label">Per Day</div></div>
                </div>
                <div class="engineer-skills">
                    <span class="skill-tag">Civil Engineering</span>
                    <span class="skill-tag">Roads & Bridges</span>
                    <span class="skill-tag">Urban Planning</span>
                </div>
                <button class="btn-contact"><i class="fas fa-phone"></i> Contact Engineer</button>
            </div>

            <div class="engineer-card">
                <div class="engineer-header">
                    <div class="engineer-avatar" style="background: linear-gradient(135deg, #10b981, #059669);">RC</div>
                    <div class="engineer-info">
                        <h3>Robert Chen</h3>
                        <p><i class="fas fa-star" style="color: #f59e0b;"></i> 5.0 (142 reviews)</p>
                    </div>
                </div>
                <div class="engineer-stats">
                    <div class="stat"><div class="stat-value">18</div><div class="stat-label">Years Exp</div></div>
                    <div class="stat"><div class="stat-value">112</div><div class="stat-label">Projects</div></div>
                    <div class="stat"><div class="stat-value">$600</div><div class="stat-label">Per Day</div></div>
                </div>
                <div class="engineer-skills">
                    <span class="skill-tag">Earthquake Engineering</span>
                    <span class="skill-tag">Retrofit Projects</span>
                    <span class="skill-tag">High-rise Buildings</span>
                </div>
                <button class="btn-contact"><i class="fas fa-phone"></i> Contact Engineer</button>
            </div>

            <div class="engineer-card">
                <div class="engineer-header">
                    <div class="engineer-avatar" style="background: linear-gradient(135deg, #f59e0b, #d97706);">LP</div>
                    <div class="engineer-info">
                        <h3>Linda Patel</h3>
                        <p><i class="fas fa-star" style="color: #f59e0b;"></i> 4.7 (88 reviews)</p>
                    </div>
                </div>
                <div class="engineer-stats">
                    <div class="stat"><div class="stat-value">10</div><div class="stat-label">Years Exp</div></div>
                    <div class="stat"><div class="stat-value">65</div><div class="stat-label">Projects</div></div>
                    <div class="stat"><div class="stat-value">$400</div><div class="stat-label">Per Day</div></div>
                </div>
                <div class="engineer-skills">
                    <span class="skill-tag">Environmental Engineering</span>
                    <span class="skill-tag">Sustainable Design</span>
                    <span class="skill-tag">Green Buildings</span>
                </div>
                <button class="btn-contact"><i class="fas fa-phone"></i> Contact Engineer</button>
            </div>

            <div class="engineer-card">
                <div class="engineer-header">
                    <div class="engineer-avatar" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">MT</div>
                    <div class="engineer-info">
                        <h3>Michael Thompson</h3>
                        <p><i class="fas fa-star" style="color: #f59e0b;"></i> 4.9 (156 reviews)</p>
                    </div>
                </div>
                <div class="engineer-stats">
                    <div class="stat"><div class="stat-value">20</div><div class="stat-label">Years Exp</div></div>
                    <div class="stat"><div class="stat-value">134</div><div class="stat-label">Projects</div></div>
                    <div class="stat"><div class="stat-value">$650</div><div class="stat-label">Per Day</div></div>
                </div>
                <div class="engineer-skills">
                    <span class="skill-tag">Industrial Projects</span>
                    <span class="skill-tag">Factory Design</span>
                    <span class="skill-tag">Heavy Structures</span>
                </div>
                <button class="btn-contact"><i class="fas fa-phone"></i> Contact Engineer</button>
            </div>

            <div class="engineer-card">
                <div class="engineer-header">
                    <div class="engineer-avatar" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">AK</div>
                    <div class="engineer-info">
                        <h3>Aisha Khan</h3>
                        <p><i class="fas fa-star" style="color: #f59e0b;"></i> 4.8 (103 reviews)</p>
                    </div>
                </div>
                <div class="engineer-stats">
                    <div class="stat"><div class="stat-value">14</div><div class="stat-label">Years Exp</div></div>
                    <div class="stat"><div class="stat-value">92</div><div class="stat-label">Projects</div></div>
                    <div class="stat"><div class="stat-value">$550</div><div class="stat-label">Per Day</div></div>
                </div>
                <div class="engineer-skills">
                    <span class="skill-tag">Architectural Engineering</span>
                    <span class="skill-tag">Luxury Homes</span>
                    <span class="skill-tag">Interior Integration</span>
                </div>
                <button class="btn-contact"><i class="fas fa-phone"></i> Contact Engineer</button>
            </div>
        </div>
    </main>
</body>
</html>
