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
    <title>Profile & Portfolio - Constructa</title>
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
        main { flex: 1; max-width: 1200px; margin: 0 auto; width: 100%; padding: 3rem; }
        .page-header { text-align: center; margin-bottom: 3rem; }
        .page-title { font-size: 3rem; font-weight: 700; margin-bottom: 0.5rem; background: linear-gradient(135deg, #294033, #3d5a49); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        .profile-section { background: white; border-radius: 20px; padding: 3rem; margin-bottom: 2rem; box-shadow: 0 5px 20px rgba(0,0,0,0.06); }
        .profile-header { display: flex; gap: 2rem; align-items: start; margin-bottom: 2rem; }
        .profile-avatar { width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, #ec4899, #db2777); display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem; font-weight: 700; }
        .profile-info h2 { font-size: 2rem; margin-bottom: 0.5rem; }
        .profile-info p { color: var(--text-gray); margin-bottom: 0.5rem; }
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
        .form-group { display: flex; flex-direction: column; }
        .form-label { font-weight: 600; margin-bottom: 0.5rem; }
        .form-input, .form-textarea { padding: 0.8rem 1rem; border: 1px solid #e5e7eb; border-radius: 10px; }
        .form-textarea { resize: vertical; min-height: 100px; grid-column: 1 / -1; }
        .certifications { display: flex; flex-wrap: wrap; gap: 1rem; margin-top: 1rem; }
        .cert-badge { padding: 0.5rem 1rem; background: #dbeafe; color: #1e40af; border-radius: 20px; font-size: 0.9rem; font-weight: 600; }
        .portfolio-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
        .portfolio-item { aspect-ratio: 1; border-radius: 16px; background: linear-gradient(135deg, #3b82f6, #2563eb); display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem; cursor: pointer; transition: all 0.3s; }
        .portfolio-item:hover { transform: scale(1.05); }
        .btn-save { background: linear-gradient(135deg, #294033, #3d5a49); color: white; padding: 1rem 2rem; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; margin-top: 1.5rem; }
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
            <h1 class="page-title"><i class="fas fa-id-card"></i> Profile & Portfolio</h1>
            <p style="color: var(--text-gray); font-size: 1.1rem;">Update your certifications, past work gallery, and contact info</p>
        </div>

        <div class="profile-section">
            <div class="profile-header">
                <div class="profile-avatar"><?php echo strtoupper(substr($username, 0, 2)); ?></div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($username); ?></h2>
                    <p><i class="fas fa-briefcase"></i> Structural Engineer</p>
                    <p><i class="fas fa-star"></i> 4.9 Rating • 127 Reviews</p>
                    <p><i class="fas fa-map-marker-alt"></i> San Francisco, CA</p>
                </div>
            </div>

            <form>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-input" value="<?php echo htmlspecialchars($username); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-input" value="<?php echo htmlspecialchars($_SESSION['email']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" class="form-input" placeholder="+1 (555) 123-4567">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Years of Experience</label>
                        <input type="number" class="form-input" value="15">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Specialization</label>
                        <input type="text" class="form-input" value="Structural Engineering, Seismic Design">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Hourly Rate</label>
                        <input type="text" class="form-input" value="$150/hour">
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Professional Bio</label>
                        <textarea class="form-textarea">Licensed structural engineer with 15+ years of experience in residential and commercial projects. Specialized in earthquake-resistant design and sustainable construction practices.</textarea>
                    </div>
                </div>

                <h3 style="margin: 2rem 0 1rem;">Certifications & Licenses</h3>
                <div class="certifications">
                    <span class="cert-badge"><i class="fas fa-certificate"></i> PE License - California</span>
                    <span class="cert-badge"><i class="fas fa-certificate"></i> LEED Accredited Professional</span>
                    <span class="cert-badge"><i class="fas fa-certificate"></i> Seismic Design Specialist</span>
                    <span class="cert-badge"><i class="fas fa-certificate"></i> OSHA Safety Certified</span>
                </div>

                <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save Changes</button>
            </form>
        </div>

        <div class="profile-section">
            <h3 style="margin-bottom: 1.5rem;">Portfolio Gallery</h3>
            <div class="portfolio-grid">
                <div class="portfolio-item" style="background: linear-gradient(135deg, #3b82f6, #2563eb);"><i class="fas fa-image"></i></div>
                <div class="portfolio-item" style="background: linear-gradient(135deg, #10b981, #059669);"><i class="fas fa-image"></i></div>
                <div class="portfolio-item" style="background: linear-gradient(135deg, #f59e0b, #d97706);"><i class="fas fa-image"></i></div>
                <div class="portfolio-item" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);"><i class="fas fa-image"></i></div>
                <div class="portfolio-item" style="background: linear-gradient(135deg, #ec4899, #db2777);"><i class="fas fa-image"></i></div>
                <div class="portfolio-item" style="background: linear-gradient(135deg, #64748b, #475569);"><i class="fas fa-plus"></i></div>
            </div>
        </div>
    </main>
</body>
</html>
