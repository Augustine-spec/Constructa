<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'engineer') {
    header('Location: login.html');
    exit();
}
$username = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Engineer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Requests - Constructa</title>
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
        
        .requests-list { display: grid; gap: 1.5rem; }
        .request-card { background: white; border-radius: 16px; padding: 2rem; box-shadow: 0 5px 20px rgba(0,0,0,0.06); transition: all 0.3s; border-left: 4px solid #0ea5e9; }
        .request-card:hover { transform: translateX(5px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .request-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; }
        .request-title { font-size: 1.5rem; font-weight: 700; color: var(--text-dark); }
        .request-badge { padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        .badge-new { background: #dbeafe; color: #1e40af; }
        .badge-urgent { background: #fee2e2; color: #991b1b; }
        .request-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 1.5rem 0; }
        .detail-item { display: flex; align-items: center; gap: 0.5rem; color: var(--text-gray); }
        .request-desc { color: var(--text-gray); line-height: 1.6; margin-bottom: 1.5rem; }
        .request-actions { display: flex; gap: 1rem; }
        .btn { padding: 0.8rem 1.5rem; border-radius: 10px; font-weight: 600; cursor: pointer; border: none; transition: all 0.3s; }
        .btn-primary { background: linear-gradient(135deg, #294033, #3d5a49); color: white; }
        .btn-secondary { background: #e5e7eb; color: var(--text-dark); }
        .btn:hover { transform: translateY(-2px); }
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
            <h1 class="page-title"><i class="fas fa-clipboard-list"></i> Project Requests</h1>
            <p style="color: var(--text-gray); font-size: 1.1rem;">View new incoming project leads and homeowner requirements</p>
        </div>

        <div class="requests-list">
            <div class="request-card">
                <div class="request-header">
                    <h3 class="request-title">Modern Villa Construction</h3>
                    <span class="request-badge badge-new">New</span>
                </div>
                <div class="request-details">
                    <div class="detail-item"><i class="fas fa-user"></i> John Davidson</div>
                    <div class="detail-item"><i class="fas fa-map-marker-alt"></i> San Francisco, CA</div>
                    <div class="detail-item"><i class="fas fa-ruler-combined"></i> 3500 sq ft</div>
                    <div class="detail-item"><i class="fas fa-calendar"></i> Posted 2 days ago</div>
                </div>
                <p class="request-desc">Looking for experienced structural engineer for luxury villa project. Need complete structural design, foundation planning, and earthquake-resistant framework. Budget: $50,000-$75,000</p>
                <div class="request-actions">
                    <button class="btn btn-primary"><i class="fas fa-file-alt"></i> Submit Proposal</button>
                    <button class="btn btn-secondary"><i class="fas fa-eye"></i> View Details</button>
                </div>
            </div>

            <div class="request-card">
                <div class="request-header">
                    <h3 class="request-title">Commercial Building Renovation</h3>
                    <span class="request-badge badge-urgent">Urgent</span>
                </div>
                <div class="request-details">
                    <div class="detail-item"><i class="fas fa-user"></i> Sarah Martinez</div>
                    <div class="detail-item"><i class="fas fa-map-marker-alt"></i> Los Angeles, CA</div>
                    <div class="detail-item"><i class="fas fa-building"></i> 5-story building</div>
                    <div class="detail-item"><i class="fas fa-calendar"></i> Posted 1 day ago</div>
                </div>
                <p class="request-desc">Urgent requirement for civil engineer to assess and redesign structural elements of existing commercial building. Need ASAP for renovation project. Budget: $35,000-$45,000</p>
                <div class="request-actions">
                    <button class="btn btn-primary"><i class="fas fa-file-alt"></i> Submit Proposal</button>
                    <button class="btn btn-secondary"><i class="fas fa-eye"></i> View Details</button>
                </div>
            </div>

            <div class="request-card">
                <div class="request-header">
                    <h3 class="request-title">Residential Complex Foundation</h3>
                    <span class="request-badge badge-new">New</span>
                </div>
                <div class="request-details">
                    <div class="detail-item"><i class="fas fa-user"></i> Robert Chen</div>
                    <div class="detail-item"><i class="fas fa-map-marker-alt"></i> Seattle, WA</div>
                    <div class="detail-item"><i class="fas fa-home"></i> 12 units</div>
                    <div class="detail-item"><i class="fas fa-calendar"></i> Posted 3 days ago</div>
                </div>
                <p class="request-desc">Need structural engineer for foundation design of residential complex. Soil testing completed. Looking for comprehensive foundation plan and supervision. Budget: $60,000-$80,000</p>
                <div class="request-actions">
                    <button class="btn btn-primary"><i class="fas fa-file-alt"></i> Submit Proposal</button>
                    <button class="btn btn-secondary"><i class="fas fa-eye"></i> View Details</button>
                </div>
            </div>

            <div class="request-card">
                <div class="request-header">
                    <h3 class="request-title">Eco-Friendly Home Design</h3>
                    <span class="request-badge badge-new">New</span>
                </div>
                <div class="request-details">
                    <div class="detail-item"><i class="fas fa-user"></i> Linda Patel</div>
                    <div class="detail-item"><i class="fas fa-map-marker-alt"></i> Portland, OR</div>
                    <div class="detail-item"><i class="fas fa-leaf"></i> Sustainable</div>
                    <div class="detail-item"><i class="fas fa-calendar"></i> Posted 4 days ago</div>
                </div>
                <p class="request-desc">Seeking environmental engineer for sustainable home design. Focus on green building practices, solar integration, and energy efficiency. Budget: $25,000-$35,000</p>
                <div class="request-actions">
                    <button class="btn btn-primary"><i class="fas fa-file-alt"></i> Submit Proposal</button>
                    <button class="btn btn-secondary"><i class="fas fa-eye"></i> View Details</button>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
