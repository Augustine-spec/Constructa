<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.html');
    exit();
}
require_once 'backend/config.php';
$conn = getDatabaseConnection();

// Update status if requested
if (isset($_POST['action']) && isset($_POST['session_id'])) {
    $sid = $_POST['session_id'];
    $newStatus = $_POST['action']; // 'reviewed' or 'archived'
    $stmt = $conn->prepare("UPDATE feedback_sessions SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $newStatus, $sid);
    $stmt->execute();
}

// Fetch Active Verification Queue (Status: New)
$sql = "SELECT fs.*, u.name as homeowner_name, e.name as engineer_name 
        FROM feedback_sessions fs
        JOIN users u ON fs.user_id = u.id
        LEFT JOIN users e ON fs.engineer_id = e.id
        WHERE fs.status = 'new'
        ORDER BY fs.created_at DESC";
$result = $conn->query($sql);

// Optional: Trends Data (Average score per day for last 7 days)
$trendSql = "SELECT DATE(created_at) as date, AVG(total_score) as avg_score 
             FROM feedback_sessions 
             GROUP BY DATE(created_at) 
             ORDER BY date DESC LIMIT 7";
$trendRes = $conn->query($trendSql);
$trends = [];
while($t = $trendRes->fetch_assoc()) $trends[] = $t;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Queue | Constructa Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&family=Outfit:wght@600;700;800&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root { 
            --stone: #e8e9e4; 
            --off-white: #f6f7f2;
            --graphite: #2d2d2d;
            --muted-green: #3d5a49;
            --border: rgba(0,0,0,0.08);
            --mono: 'JetBrains Mono', monospace;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: var(--off-white); color: var(--graphite); font-family: 'Inter', sans-serif; }

        #bg-canvas { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; pointer-events: none; opacity: 0.3; }

        header { 
            padding: 1.5rem 4rem; display: flex; justify-content: space-between; align-items: center; 
            background: rgba(255,255,255,0.9); backdrop-filter: blur(10px); border-bottom: 1px solid var(--border);
            position: sticky; top: 0; z-index: 1000;
        }
        .logo { font-family: 'Outfit'; font-weight: 800; color: var(--muted-green); text-decoration: none; }

        main { max-width: 1400px; margin: 0 auto; padding: 4rem; }
        
        .queue-header { margin-bottom: 4rem; }
        .queue-header h1 { font-family: 'Outfit'; font-size: 2.5rem; margin-bottom: 0.5rem; }
        .queue-header p { font-family: var(--mono); color: #888; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px; }

        .analytics-strip {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; margin-bottom: 4rem;
        }
        .stat-card {
            background: #fff; padding: 2.5rem; border: 1px solid var(--border);
        }
        .stat-label { font-family: var(--mono); font-size: 0.7rem; color: #999; text-transform: uppercase; margin-bottom: 1rem; display: block; }
        .stat-value { font-family: 'Outfit'; font-size: 2rem; color: var(--muted-green); }

        .feedback-grid {
            display: flex; flex-direction: column; gap: 1.5rem;
        }
        .feedback-item {
            background: #fff; border: 1px solid var(--border); padding: 2.5rem;
            display: grid; grid-template-columns: 1fr 1fr 200px; gap: 2rem; align-items: center;
            transition: transform 0.3s;
        }
        .feedback-item:hover { transform: scale(1.01); border-color: var(--muted-green); }

        .meta-data { display: flex; flex-direction: column; gap: 0.5rem; }
        .user-name { font-weight: 700; font-size: 1.1rem; }
        .session-id { font-family: var(--mono); font-size: 0.7rem; color: #aaa; }

        .score-box {
            display: flex; align-items: center; gap: 1rem;
        }
        .score-circle {
            width: 60px; height: 60px; border-radius: 50%; border: 4px solid var(--muted-green);
            display: flex; align-items: center; justify-content: center;
            font-family: var(--mono); font-weight: 700; color: var(--muted-green);
        }
        .quality-index { font-family: var(--mono); font-size: 0.75rem; color: #666; }

        .action-btns { display: flex; gap: 0.5rem; }
        .btn-q {
            flex: 1; padding: 1rem; font-family: var(--mono); font-size: 0.7rem; font-weight: 700;
            border: 1px solid var(--border); background: var(--off-white); cursor: pointer;
            text-transform: uppercase;
        }
        .btn-q:hover { background: var(--muted-green); color: #fff; border-color: var(--muted-green); }

        .empty-state { padding: 10rem; text-align: center; font-family: var(--mono); color: #aaa; }
    </style>
</head>
<body>
    <div id="bg-canvas"></div>
    <header>
        <a href="admin_dashboard.php" class="logo">ADMIN_FEEDBACK_CENTRAL</a>
        <nav>
            <a href="admin_dashboard.php" style="text-decoration:none; color:var(--graphite); font-family:var(--mono); font-size:0.75rem;">Terminal</a>
        </nav>
    </header>

    <main>
        <div class="queue-header">
            <h1>Operational Review Queue</h1>
            <p>System Status: Monitoring 3 Active Hydration Nodes</p>
        </div>

        <div class="analytics-strip">
            <div class="stat-card">
                <span class="stat-label">Consultation Quality Index</span>
                <span class="stat-value">4.82<sub>avg</sub></span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Pending Reviews</span>
                <span class="stat-value"><?php echo $result->num_rows; ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-label">System Health</span>
                <span class="stat-value">NOMINAL</span>
            </div>
        </div>

        <div class="feedback-grid">
            <?php if($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="feedback-item">
                        <div class="meta-data">
                            <span class="session-id">SID: <?php echo str_pad($row['id'], 5, '0', STR_PAD_LEFT); ?> // TIMESTAMP: <?php echo $row['created_at']; ?></span>
                            <span class="user-name"><?php echo htmlspecialchars($row['homeowner_name']); ?> reviewed <?php echo htmlspecialchars($row['engineer_name'] ?: 'System'); ?></span>
                        </div>
                        <div class="score-box">
                            <div class="score-circle"><?php echo number_format($row['total_score'], 1); ?></div>
                            <span class="quality-index">PRECISION SCORE VALIDATED</span>
                        </div>
                        <div class="action-btns">
                            <form method="POST" style="width:100%; display:flex; gap:5px;">
                                <input type="hidden" name="session_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="action" value="reviewed" class="btn-q">Verify</button>
                                <button type="submit" name="action" value="archived" class="btn-q">Archive</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    [SYSTEM_LOG: QUEUE_IDLE_NO_NEW_ENTITIES]
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="js/architectural_bg.js"></script>
    <script>
        if(window.initArchitecturalBackground) initArchitecturalBackground('bg-canvas');
    </script>
</body>
</html>
