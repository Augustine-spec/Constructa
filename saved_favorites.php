<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'homeowner') { header('Location: login.html'); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Favorites - Constructa</title>
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
        
        .tabs { display: flex; gap: 1rem; justify-content: center; margin-bottom: 3rem; }
        .tab-btn { padding: 1rem 2rem; border: 2px solid var(--primary-green); background: transparent; color: var(--primary-green); font-weight: 600; border-radius: 10px; cursor: pointer; transition: all 0.3s; }
        .tab-btn.active, .tab-btn:hover { background: var(--primary-green); color: white; }
        
        .favorites-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 2rem; }
        .favorite-item { background: white; border-radius: 20px; padding: 2rem; box-shadow: 0 5px 20px rgba(0,0,0,0.06); position: relative; transition: all 0.3s; }
        .favorite-item:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.1); }
        .favorite-icon { width: 70px; height: 70px; border-radius: 50%; margin: 0 auto 1.5rem; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: white; }
        .favorite-title { font-size: 1.4rem; font-weight: 700; margin-bottom: 0.8rem; text-align: center; }
        .favorite-desc { color: var(--text-gray); text-align: center; line-height: 1.6; margin-bottom: 1.5rem; }
        .favorite-meta { display: flex; justify-content: space-around; padding-top: 1rem; border-top: 1px solid #eee; font-size: 0.9rem; color: var(--text-gray); }
        .btn-remove { position: absolute; top: 1rem; right: 1rem; background: #ef4444; color: white; border: none; width: 35px; height: 35px; border-radius: 50%; cursor: pointer; font-size: 1rem; }
        .btn-view { background: linear-gradient(135deg, #294033, #3d5a49); color: white; padding: 0.8rem 1.5rem; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; width: 100%; margin-top: 1rem; }
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
            <h1 class="page-title"><i class="fas fa-heart"></i> Saved Favorites</h1>
            <p style="color: var(--text-gray); font-size: 1.1rem;">Quick access to your liked designs, products, and experts</p>
        </div>

        <div class="tabs">
            <button class="tab-btn active">All</button>
            <button class="tab-btn">Designs</button>
            <button class="tab-btn">Materials</button>
            <button class="tab-btn">Professionals</button>
        </div>

        <div class="favorites-grid">
            <!-- Favorite 1: Design -->
            <div class="favorite-item">
                <button class="btn-remove"><i class="fas fa-times"></i></button>
                <div class="favorite-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb);"><i class="fas fa-home"></i></div>
                <h3 class="favorite-title">Modern Villa</h3>
                <p class="favorite-desc">Luxurious 3-bedroom contemporary villa design</p>
                <div class="favorite-meta">
                    <span><i class="fas fa-ruler-combined"></i> 2500 sq ft</span>
                    <span><i class="fas fa-tag"></i> Design</span>
                </div>
                <button class="btn-view"><i class="fas fa-eye"></i> View Details</button>
            </div>

            <!-- Favorite 2: Material -->
            <div class="favorite-item">
                <button class="btn-remove"><i class="fas fa-times"></i></button>
                <div class="favorite-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);"><i class="fas fa-box"></i></div>
                <h3 class="favorite-title">Premium Cement</h3>
                <p class="favorite-desc">High-strength Portland cement for construction</p>
                <div class="favorite-meta">
                    <span><i class="fas fa-dollar-sign"></i> $8.50/bag</span>
                    <span><i class="fas fa-tag"></i> Material</span>
                </div>
                <button class="btn-view"><i class="fas fa-shopping-cart"></i> Buy Now</button>
            </div>

            <!-- Favorite 3: Engineer -->
            <div class="favorite-item">
                <button class="btn-remove"><i class="fas fa-times"></i></button>
                <div class="favorite-icon" style="background: linear-gradient(135deg, #10b981, #059669);"><i class="fas fa-user-tie"></i></div>
                <h3 class="favorite-title">Robert Chen</h3>
                <p class="favorite-desc">Earthquake engineering specialist</p>
                <div class="favorite-meta">
                    <span><i class="fas fa-star"></i> 5.0 Rating</span>
                    <span><i class="fas fa-tag"></i> Engineer</span>
                </div>
                <button class="btn-view"><i class="fas fa-phone"></i> Contact</button>
            </div>

            <!-- Favorite 4: Contractor -->
            <div class="favorite-item">
                <button class="btn-remove"><i class="fas fa-times"></i></button>
                <div class="favorite-icon" style="background: linear-gradient(135deg, #ec4899, #db2777);"><i class="fas fa-hammer"></i></div>
                <h3 class="favorite-title">BuildPro Construction</h3>
                <p class="favorite-desc">Full-service construction company</p>
                <div class="favorite-meta">
                    <span><i class="fas fa-star"></i> 4.9 Rating</span>
                    <span><i class="fas fa-tag"></i> Contractor</span>
                </div>
                <button class="btn-view"><i class="fas fa-phone"></i> Hire Now</button>
            </div>

            <!-- Favorite 5: Design -->
            <div class="favorite-item">
                <button class="btn-remove"><i class="fas fa-times"></i></button>
                <div class="favorite-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);"><i class="fas fa-building"></i></div>
                <h3 class="favorite-title">Eco-Friendly Home</h3>
                <p class="favorite-desc">Sustainable design with solar panels</p>
                <div class="favorite-meta">
                    <span><i class="fas fa-ruler-combined"></i> 1800 sq ft</span>
                    <span><i class="fas fa-tag"></i> Design</span>
                </div>
                <button class="btn-view"><i class="fas fa-eye"></i> View Details</button>
            </div>

            <!-- Favorite 6: Material -->
            <div class="favorite-item">
                <button class="btn-remove"><i class="fas fa-times"></i></button>
                <div class="favorite-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);"><i class="fas fa-th-large"></i></div>
                <h3 class="favorite-title">Red Clay Bricks</h3>
                <p class="favorite-desc">Traditional red bricks with excellent durability</p>
                <div class="favorite-meta">
                    <span><i class="fas fa-dollar-sign"></i> $0.55/piece</span>
                    <span><i class="fas fa-tag"></i> Material</span>
                </div>
                <button class="btn-view"><i class="fas fa-shopping-cart"></i> Buy Now</button>
            </div>
        </div>
    </main>

    <script>
        document.querySelectorAll('.btn-remove').forEach(btn => {
            btn.addEventListener('click', function() {
                if (confirm('Remove this item from favorites?')) {
                    this.closest('.favorite-item').remove();
                }
            });
        });
    </script>
</body>
</html>
