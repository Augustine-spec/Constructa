<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'engineer') {
    header('Location: login.html');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earnings & Payments - Constructa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <style>
        :root {
            --bg-color: #f6f7f2;
            --text-dark: #1f2937;
            --text-gray: #64748b;
            --primary-emerald: #1E7F5C;
            --soft-mint: #DFF6EC;
            --alert-red: #E57373;
            --card-bg: #ffffff;
            --glass-border: rgba(255, 255, 255, 0.5);
            --shadow-soft: 0 10px 30px -10px rgba(0, 0, 0, 0.05);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }

        body {
            background-color: var(--bg-color);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* 3D Background Canvas */
        #canvas-container {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            z-index: -1; background: #f6f7f2; pointer-events: none;
        }

        /* Navbar & Header (Consistent with engineer.php) */
        header {
            padding: 1.5rem 3rem;
            display: flex; justify-content: space-between; align-items: center;
            max-width: 1600px; margin: 0 auto; width: 100%;
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px);
            position: sticky; top: 0; z-index: 100;
            border-bottom: 1px solid rgba(0,0,0,0.06);
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        }

        .logo {
            font-weight: 800; font-size: 1.6rem;
            background: linear-gradient(90deg, #294033, #1E7F5C);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            text-decoration: none;
        }

        .nav-btn {
            background: white; border: 1px solid rgba(0,0,0,0.08);
            padding: 0.75rem 1.5rem; border-radius: 8px;
            font-weight: 600; font-size: 0.9rem; color: var(--text-dark);
            text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;
            transition: all 0.3s ease;
        }
        .nav-btn:hover {
            transform: translateY(-2px); box-shadow: 0 4px 12px rgba(30, 127, 92, 0.15);
            border-color: var(--primary-emerald); color: var(--primary-emerald);
        }

        /* Main Content */
        .dashboard-container {
            max-width: 1200px; margin: 0 auto; width: 100%; padding: 2rem 3rem;
            display: flex; flex-direction: column; gap: 2rem; z-index: 2;
        }

        .page-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 1rem;
        }
        .page-title h1 { font-size: 2.2rem; font-weight: 700; color: #111827; }
        .page-title p { color: var(--text-gray); margin-top: 0.5rem; }

        .req-payment-btn {
            background: linear-gradient(135deg, #1E7F5C, #059669);
            color: white; padding: 1rem 2rem; border-radius: 12px;
            font-weight: 600; border: none; cursor: pointer;
            box-shadow: 0 4px 15px rgba(5, 150, 105, 0.3);
            transition: all 0.3s ease; display: flex; align-items: center; gap: 0.5rem;
        }
        .req-payment-btn:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(5, 150, 105, 0.4); }

        /* 1. Earnings Overview */
        .overview-grid {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem;
        }
        .stat-card {
            background: rgba(255,255,255,0.9); backdrop-filter: blur(10px);
            padding: 1.5rem; border-radius: 16px; border: 1px solid var(--glass-border);
            box-shadow: var(--shadow-soft); transition: transform 0.3s;
            position: relative; overflow: hidden;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-label { font-size: 0.85rem; color: var(--text-gray); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-value { font-size: 2rem; font-family: 'Rajdhani', sans-serif; font-weight: 700; color: #111827; margin-top: 0.5rem; }
        .indicator-line { height: 4px; width: 40px; border-radius: 2px; margin-top: 1rem; }

        /* 2. Client Breakdown */
        .section-card {
            background: white; border-radius: 20px; padding: 2rem;
            box-shadow: var(--shadow-soft); border: 1px solid rgba(0,0,0,0.03);
        }
        .section-title { font-size: 1.2rem; font-weight: 700; margin-bottom: 1.5rem; color: #111827; }

        .client-table { width: 100%; border-collapse: separate; border-spacing: 0 0.5rem; }
        .client-table th { text-align: left; padding: 1rem; color: var(--text-gray); font-weight: 600; font-size: 0.9rem; }
        .client-table td { background: #f8fafc; padding: 1rem; font-weight: 500; font-size: 0.95rem; }
        .client-table tr td:first-child { border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
        .client-table tr td:last-child { border-top-right-radius: 10px; border-bottom-right-radius: 10px; }
        .client-table tr:hover td { background: #f1f5f9; }

        .status-badge {
            padding: 0.25rem 0.75rem; border-radius: 99px; font-size: 0.75rem; font-weight: 700;
            display: inline-block;
        }
        .status-paid { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef9c3; color: #854d0e; }
        .status-overdue { background: #fee2e2; color: #991b1b; }

        /* 3. Invoice History */
        .invoice-list { display: flex; flex-direction: column; gap: 0.8rem; }
        .invoice-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 1rem 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0;
            transition: all 0.2s; cursor: pointer;
        }
        .invoice-item:hover { background: #f8fafc; border-color: #cbd5e1; transform: translateX(5px); }
        .inv-details h4 { font-size: 0.95rem; font-weight: 600; }
        .inv-details span { font-size: 0.8rem; color: var(--text-gray); }
        .download-btn {
            color: var(--primary-emerald); background: var(--soft-mint);
            padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.8rem; font-weight: 600;
            text-decoration: none; display: flex; align-items: center; gap: 0.4rem;
        }

        @media (max-width: 1024px) { .overview-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 600px) { .overview-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <!-- 3D Canvas Background -->
    <div id="canvas-container"></div>

    <header>
        <a href="engineer.php" class="logo">CONSTRUCTA</a>
        <nav>
            <a href="engineer.php" class="nav-btn"><i class="fas fa-arrow-left"></i> Dashboard</a>
            <a href="logout.php" class="nav-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </header>

    <main class="dashboard-container">
        
        <!-- Header & Action -->
        <div class="page-header">
            <div class="page-title">
                <h1>Earnings & Payments</h1>
                <p>Track your project earnings, pending payments, and invoices.</p>
            </div>
            <button class="req-payment-btn">
                <i class="fas fa-paper-plane"></i> Request Payment
            </button>
        </div>

        <!-- 1. Overview Cards -->
        <div class="overview-grid">
            <div class="stat-card">
                <div class="stat-label">Total Lifetime Earnings</div>
                <div class="stat-value animate-num" data-val="1245000">₹ 0</div>
                <div class="indicator-line" style="background:#059669;"></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">This Month Earnings</div>
                <div class="stat-value animate-num" data-val="85000">₹ 0</div>
                <div class="indicator-line" style="background:#10b981;"></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Pending Payments</div>
                <div class="stat-value animate-num" data-val="42000">₹ 0</div>
                <div class="indicator-line" style="background:#eab308;"></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Overdue Payments</div>
                <div class="stat-value animate-num" data-val="12500" style="color:#ef4444;">₹ 0</div>
                <div class="indicator-line" style="background:#ef4444;"></div>
            </div>
        </div>

        <!-- 2. Client Breakdown -->
        <div class="section-card">
            <h3 class="section-title">Client-wise Payment Breakdown</h3>
            <table class="client-table">
                <thead>
                    <tr>
                        <th>Client Name</th>
                        <th>Total Value</th>
                        <th>Received</th>
                        <th>Balance</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Skyline Residency</strong><br><span style="font-size:0.8rem; color:#64748b;">Mr. Sharma</span></td>
                        <td>₹ 25,00,000</td>
                        <td>₹ 15,00,000</td>
                        <td>₹ 10,00,000</td>
                        <td><span class="status-badge status-pending">Pending</span></td>
                    </tr>
                    <tr>
                        <td><strong>Green Valley Villa</strong><br><span style="font-size:0.8rem; color:#64748b;">Mrs. Iyer</span></td>
                        <td>₹ 18,00,000</td>
                        <td>₹ 18,00,000</td>
                        <td>₹ 0</td>
                        <td><span class="status-badge status-paid">Paid</span></td>
                    </tr>
                    <tr>
                        <td><strong>Tech Park Block C</strong><br><span style="font-size:0.8rem; color:#64748b;">Innovate Corp</span></td>
                        <td>₹ 50,00,000</td>
                        <td>₹ 20,00,000</td>
                        <td>₹ 30,00,000</td>
                        <td><span class="status-badge status-pending">In Progress</span></td>
                    </tr>
                    <tr>
                        <td><strong>City Center Renovation</strong><br><span style="font-size:0.8rem; color:#64748b;">Urban Dev</span></td>
                        <td>₹ 5,00,000</td>
                        <td>₹ 1,00,000</td>
                        <td style="color:#ef4444;">₹ 4,00,000</td>
                        <td><span class="status-badge status-overdue">Overdue</span></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- 3. Invoice History -->
        <div class="section-card">
            <h3 class="section-title">Recent Invoices</h3>
            <div class="invoice-list">
                <div class="invoice-item">
                    <div class="inv-details">
                        <h4>Invoice #INV-2023-001</h4>
                        <span>Issued: Oct 24, 2023 &bull; Skyline Residency</span>
                    </div>
                    <div>
                        <span class="status-badge status-paid" style="margin-right:1rem;">Paid</span>
                        <span style="font-weight:700;">₹ 2,50,000</span>
                    </div>
                    <a href="#" class="download-btn"><i class="fas fa-download"></i> PDF</a>
                </div>
                <div class="invoice-item">
                    <div class="inv-details">
                        <h4>Invoice #INV-2023-002</h4>
                        <span>Issued: Nov 01, 2023 &bull; Tech Park Block C</span>
                    </div>
                    <div>
                        <span class="status-badge status-pending" style="margin-right:1rem;">Pending</span>
                        <span style="font-weight:700;">₹ 5,00,000</span>
                    </div>
                    <a href="#" class="download-btn"><i class="fas fa-download"></i> PDF</a>
                </div>
                 <div class="invoice-item">
                    <div class="inv-details">
                        <h4>Invoice #INV-2023-003</h4>
                        <span>Issued: Nov 10, 2023 &bull; City Center</span>
                    </div>
                    <div>
                        <span class="status-badge status-overdue" style="margin-right:1rem;">Overdue</span>
                        <span style="font-weight:700; color:#ef4444;">₹ 1,00,000</span>
                    </div>
                    <a href="#" class="download-btn"><i class="fas fa-download"></i> PDF</a>
                </div>
            </div>
        </div>

    </main>

    <script>
        // Number Animation
        const animateValue = (obj, start, end, duration) => {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                const val = Math.floor(progress * (end - start) + start);
                obj.innerHTML = '₹ ' + val.toLocaleString('en-IN');
                if (progress < 1) window.requestAnimationFrame(step);
            };
            window.requestAnimationFrame(step);
        };

        document.querySelectorAll('.animate-num').forEach(el => {
            const val = parseInt(el.getAttribute('data-val'));
            animateValue(el, 0, val, 1500);
        });

        // Background Logic (Simplified from original)
        const initBackground3D = () => {
            const container = document.getElementById('canvas-container');
            if(!container) return;
            const scene = new THREE.Scene();
            scene.background = new THREE.Color('#f6f7f2');
            scene.fog = new THREE.Fog('#f6f7f2', 10, 45);
            const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
            camera.position.set(0, 5, 10);
            camera.lookAt(0,0,0);
            
            const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            container.appendChild(renderer.domElement);
            
            const cityGroup = new THREE.Group();
            scene.add(cityGroup);
            
            // Simple Grid
            const geometry = new THREE.BoxGeometry(0.8, 1, 0.8);
            const material = new THREE.MeshBasicMaterial({ color: 0x1E7F5C, transparent: true, opacity: 0.1 });
            const edges = new THREE.EdgesGeometry(geometry);
            const lineMat = new THREE.LineBasicMaterial({ color: 0x1E7F5C, transparent:true, opacity:0.2 });

            for(let x=-8; x<8; x+=2) {
                for(let z=-8; z<8; z+=2) {
                    const h = Math.random() * 2 + 0.5;
                    const mesh = new THREE.Mesh(geometry, material);
                    mesh.scale.y = h;
                    mesh.position.set(x, h/2 - 2, z);
                    const line = new THREE.LineSegments(edges, lineMat);
                    line.scale.y = h;
                    line.position.set(x, h/2 - 2, z);
                    cityGroup.add(mesh);
                    cityGroup.add(line);
                }
            }

            function animate() {
                requestAnimationFrame(animate);
                cityGroup.rotation.y += 0.002;
                renderer.render(scene, camera);
            }
            animate();
            
            window.addEventListener('resize', () => {
                camera.aspect = window.innerWidth / window.innerHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(window.innerWidth, window.innerHeight);
            });
        };
        if (typeof THREE !== 'undefined') initBackground3D();
    </script>
</body>
</html>
