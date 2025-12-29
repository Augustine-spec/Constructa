<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'homeowner') { header('Location: login.html'); exit(); }
$username = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - Constructa</title>
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
        main { flex: 1; max-width: 800px; margin: 0 auto; width: 100%; padding: 3rem; }
        .page-header { text-align: center; margin-bottom: 3rem; }
        .page-title { font-size: 3rem; font-weight: 700; margin-bottom: 0.5rem; background: linear-gradient(135deg, #294033, #3d5a49); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .feedback-card { background: white; border-radius: 24px; padding: 3rem; box-shadow: 0 10px 40px rgba(0,0,0,0.08); }
        .form-group { margin-bottom: 2rem; }
        .form-label { display: block; margin-bottom: 0.8rem; font-weight: 600; font-size: 1.1rem; }
        .form-input, .form-textarea, .form-select { width: 100%; padding: 1rem 1.5rem; border: 2px solid #e0e0e0; border-radius: 12px; font-size: 1rem; transition: all 0.3s; }
        .form-input:focus, .form-textarea:focus, .form-select:focus { outline: none; border-color: var(--primary-green); box-shadow: 0 0 0 3px rgba(41, 64, 51, 0.1); }
        .form-textarea { resize: vertical; min-height: 150px; }
        .rating-group { display: flex; gap: 1rem; justify-content: center; margin: 1.5rem 0; }
        .star-btn { font-size: 2.5rem; color: #ddd; cursor: pointer; transition: all 0.2s; border: none; background: none; }
        .star-btn:hover, .star-btn.active { color: #f59e0b; transform: scale(1.2); }
        .btn-submit { background: linear-gradient(135deg, #294033, #3d5a49); color: white; padding: 1.2rem 3rem; border: none; border-radius: 12px; font-size: 1.1rem; font-weight: 600; cursor: pointer; width: 100%; transition: all 0.3s; }
        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(41, 64, 51, 0.3); }
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
            <h1 class="page-title"><i class="fas fa-comment-dots"></i> Feedback</h1>
            <p style="color: var(--text-gray); font-size: 1.1rem;">Share your experience to help us improve</p>
        </div>
        <div class="feedback-card">
            <form id="feedbackForm">
                <div class="form-group">
                    <label class="form-label">Your Name</label>
                    <input type="text" class="form-input" value="<?php echo htmlspecialchars($username); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Rate Your Experience</label>
                    <div class="rating-group" id="ratingStars">
                        <button type="button" class="star-btn" data-rating="1"><i class="fas fa-star"></i></button>
                        <button type="button" class="star-btn" data-rating="2"><i class="fas fa-star"></i></button>
                        <button type="button" class="star-btn" data-rating="3"><i class="fas fa-star"></i></button>
                        <button type="button" class="star-btn" data-rating="4"><i class="fas fa-star"></i></button>
                        <button type="button" class="star-btn" data-rating="5"><i class="fas fa-star"></i></button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Feedback Category</label>
                    <select class="form-select" required>
                        <option value="">Select Category</option>
                        <option value="feature">Feature Request</option>
                        <option value="bug">Bug Report</option>
                        <option value="service">Service Quality</option>
                        <option value="general">General Feedback</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Your Feedback</label>
                    <textarea class="form-textarea" placeholder="Tell us about your experience..." required></textarea>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Submit Feedback
                </button>
            </form>
        </div>
    </main>
    <script>
        const stars = document.querySelectorAll('.star-btn');
        let selectedRating = 0;
        
        stars.forEach(star => {
            star.addEventListener('click', () => {
                selectedRating = star.dataset.rating;
                stars.forEach((s, index) => {
                    if (index < selectedRating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
        });

        document.getElementById('feedbackForm').addEventListener('submit', (e) => {
            e.preventDefault();
            alert('Thank you for your feedback! We appreciate your input.');
            window.location.href = 'homeowner.php';
        });
    </script>
</body>
</html>
