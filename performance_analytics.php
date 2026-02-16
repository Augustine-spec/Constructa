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
    <title>Performance Analytics - Constructa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            --glass-border: rgba(255, 255, 255, 0.6);
            --shadow-soft: 0 10px 30px -10px rgba(0, 0, 0, 0.06);
            --shadow-hover: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
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

        /* 3D Canvas Background */
        #canvas-container {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            z-index: -1; background: #f6f7f2; pointer-events: none;
        }

        /* Navbar */
        header {
            padding: 1.5rem 3rem;
            display: flex; justify-content: space-between; align-items: center;
            max-width: 1600px; margin: 0 auto; width: 100%;
            background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(20px);
            position: sticky; top: 0; z-index: 100;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .logo {
            font-family: 'Outfit', sans-serif;
            font-weight: 800; font-size: 1.6rem;
            background: linear-gradient(90deg, #1E7F5C, #10b981);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            text-decoration: none; letter-spacing: -0.5px;
        }

        .nav-btn {
            background: white; border: 1px solid rgba(0,0,0,0.08);
            padding: 0.6rem 1.2rem; border-radius: 10px;
            font-weight: 600; font-size: 0.9rem; color: var(--text-dark);
            text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;
            transition: all 0.3s ease;
        }
        .nav-btn:hover {
            transform: translateY(-2px); box-shadow: 0 4px 12px rgba(30, 127, 92, 0.15);
            border-color: var(--primary-emerald); color: var(--primary-emerald);
        }

        /* Dashboard Container */
        .dashboard-container {
            max-width: 1400px; margin: 0 auto; width: 100%; padding: 2.5rem;
            display: flex; flex-direction: column; gap: 2.5rem; z-index: 2;
        }

        .page-header {
            margin-bottom: 1rem;
        }
        .page-header h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 2.5rem; font-weight: 700; color: #111827;
            letter-spacing: -0.02em;
        }
        .page-header p { color: var(--text-gray); font-size: 1.1rem; margin-top: 0.5rem; }

        /* KPI Grid */
        .kpi-grid {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem;
        }

        .kpi-card {
            background: rgba(255,255,255,0.9); backdrop-filter: blur(12px);
            border-radius: 20px; padding: 1.8rem;
            border: 1px solid var(--glass-border);
            box-shadow: var(--shadow-soft);
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            position: relative; overflow: hidden;
            display: flex; flex-direction: column; justify-content: space-between;
        }
        .kpi-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-hover); }

        .kpi-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; }
        .kpi-label { font-size: 0.85rem; font-weight: 600; color: var(--text-gray); text-transform: uppercase; letter-spacing: 0.5px; }
        .kpi-icon { color: var(--primary-emerald); font-size: 1.2rem; opacity: 0.8; }

        .kpi-value-wrapper { display: flex; align-items: baseline; gap: 0.5rem; }
        .kpi-value { font-family: 'Outfit', sans-serif; font-size: 2.2rem; font-weight: 700; color: #111827; }
        .kpi-trend { 
            font-size: 0.85rem; font-weight: 600; padding: 4px 8px; border-radius: 20px; 
            display: flex; align-items: center; gap: 4px;
        }
        .trend-up { background: #ecfdf5; color: #059669; }
        .trend-down { background: #fef2f2; color: #dc2626; }
        
        .sparkline { height: 40px; width: 100%; margin-top: 1rem; opacity: 0.7; }

        /* Charts & Score Sections */
        .analytics-main {
            display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;
        }

        .chart-panel {
            background: white; border-radius: 24px; padding: 2rem;
            box-shadow: var(--shadow-soft); border: 1px solid rgba(0,0,0,0.03);
        }

        .panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .panel-title { font-family: 'Outfit', sans-serif; font-size: 1.25rem; font-weight: 700; color: #111827; }
        
        .score-panel {
            background: white; border-radius: 24px; padding: 2rem;
            box-shadow: var(--shadow-soft); border: 1px solid rgba(0,0,0,0.03);
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            text-align: center;
        }
        
        /* Circular Progress */
        .progress-circle {
            position: relative; width: 220px; height: 220px;
            border-radius: 50%;
            background: conic-gradient(var(--primary-emerald) var(--degrees, 0deg), #f1f5f9 0deg);
            display: flex; align-items: center; justify-content: center;
            margin: 1.5rem 0;
            transition: --degrees 2s ease-out;
        }
        .progress-inner {
            width: 180px; height: 180px; background: white; border-radius: 50%;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            box-shadow: inset 0 5px 15px rgba(0,0,0,0.05);
        }
        .score-val { font-family: 'Outfit', sans-serif; font-size: 3rem; font-weight: 800; color: #111827; line-height: 1; }
        .score-label { font-size: 0.9rem; font-weight: 600; color: var(--text-gray); margin-top: 0.5rem; }

        /* Insights Panel */
        .insights-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem;
        }
        .insight-card {
            background: linear-gradient(135deg, white, #f8fafc); border-radius: 16px; padding: 1.5rem;
            border: 1px solid #e2e8f0; border-left: 4px solid var(--primary-emerald);
            box-shadow: var(--shadow-soft); transition: transform 0.3s;
        }
        .insight-card:hover { transform: translateY(-3px); border-color: var(--soft-mint); }
        .insight-icon {
            width: 32px; height: 32px; background: var(--soft-mint); border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: var(--primary-emerald); font-size: 0.9rem; margin-bottom: 1rem;
        }
        .insight-text { font-size: 0.95rem; font-weight: 500; color: #334155; line-height: 1.5; }

        @media (max-width: 1100px) { 
            .kpi-grid { grid-template-columns: repeat(2, 1fr); } 
            .analytics-main { grid-template-columns: 1fr; }
        }
        @media (max-width: 600px) { 
            .kpi-grid, .insights-grid { grid-template-columns: 1fr; } 
        }

        /* Micro-animations */
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .animate-in { animation: fadeIn 0.6s ease-out forwards; opacity: 0; }
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }

        /* Chart Container */
        .chart-container { position: relative; height: 300px; width: 100%; }

    </style>
</head>
<body>

    <!-- 3D Canvas Background -->
    <div id="canvas-container"></div>

    <header>
        <a href="engineer.php" class="logo">CONSTRUCTA <span style="font-weight:400; font-size:1rem; opacity:0.7; margin-left:5px;">ANALYTICS</span></a>
        <nav>
            <a href="engineer.php" class="nav-btn"><i class="fas fa-arrow-left"></i> Dashboard</a>
            <a href="logout.php" class="nav-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </header>

    <main class="dashboard-container">
        
        <div class="page-header animate-in">
            <h1>Performance Analytics</h1>
            <p>Real-time insights into your project success, client satisfaction, and business growth.</p>
        </div>

        <!-- 1. KPI Cards -->
        <div class="kpi-grid">
            <!-- Completion Rate -->
            <div class="kpi-card animate-in delay-1">
                <div class="kpi-top">
                    <span class="kpi-label">Completion Rate</span>
                    <i class="fas fa-check-circle kpi-icon"></i>
                </div>
                <div>
                    <div class="kpi-value-wrapper">
                        <span class="kpi-value" data-target="94">0</span><span class="kpi-value">%</span>
                        <div class="kpi-trend trend-up"><i class="fas fa-arrow-up"></i> 4.2%</div>
                    </div>
                     <canvas id="spark1" class="sparkline"></canvas>
                </div>
            </div>

            <!-- Client Rating -->
            <div class="kpi-card animate-in delay-2">
                <div class="kpi-top">
                    <span class="kpi-label">Client Rating</span>
                    <i class="fas fa-star kpi-icon"></i>
                </div>
                <div>
                    <div class="kpi-value-wrapper">
                        <span class="kpi-value" data-target="4.8">0.0</span>
                        <div class="kpi-trend trend-up" style="background:#fff7ed; color:#c2410c;"><i class="fas fa-minus"></i> Stable</div>
                    </div>
                    <canvas id="spark2" class="sparkline"></canvas>
                </div>
            </div>

            <!-- Response Time -->
            <div class="kpi-card animate-in delay-3">
                <div class="kpi-top">
                    <span class="kpi-label">Avg. Response Time</span>
                    <i class="fas fa-clock kpi-icon"></i>
                </div>
                <div>
                    <div class="kpi-value-wrapper">
                        <span class="kpi-value" data-target="1.5">0.0</span><span style="font-size:1rem; font-weight:600; color:#64748b;">hrs</span>
                        <div class="kpi-trend trend-up"><i class="fas fa-arrow-down"></i> 12%</div>
                    </div>
                    <canvas id="spark3" class="sparkline"></canvas>
                </div>
            </div>

            <!-- Bid Success -->
            <div class="kpi-card animate-in delay-1">
                <div class="kpi-top">
                    <span class="kpi-label">Bid Acceptance</span>
                    <i class="fas fa-handshake kpi-icon"></i>
                </div>
                <div>
                     <div class="kpi-value-wrapper">
                        <span class="kpi-value" data-target="72">0</span><span class="kpi-value">%</span>
                        <div class="kpi-trend trend-up"><i class="fas fa-arrow-up"></i> 8.5%</div>
                    </div>
                    <canvas id="spark4" class="sparkline"></canvas>
                </div>
            </div>
        </div>

        <!-- 2. Charts & Score -->
        <div class="analytics-main animate-in delay-2">
            <!-- Growth Chart -->
            <div class="chart-panel">
                <div class="panel-header">
                    <h3 class="panel-title">Growth Trajectory</h3>
                    <select style="padding:0.4rem; border-radius:8px; border:1px solid #ddd; font-size:0.85rem;">
                        <option>Last 6 Months</option>
                        <option>This Year</option>
                    </select>
                </div>
                <div class="chart-container">
                    <canvas id="growthChart"></canvas>
                </div>
            </div>

            <!-- Reputation Score -->
            <div class="score-panel">
                <h3 class="panel-title">Professional Score</h3>
                <p style="font-size:0.9rem; color:#64748b; margin-top:0.5rem;">Based on aggregate performance metrics</p>
                
                <div class="progress-circle" id="scoreCircle">
                    <div class="progress-inner">
                        <span class="score-val" data-target="92">0</span>
                        <span class="score-label">Excellent</span>
                    </div>
                </div>

                <div style="display:flex; gap:1rem; margin-top:1rem;">
                    <div style="font-size:0.8rem; color:#64748b;"><span style="color:#10b981;">●</span> Quality</div>
                    <div style="font-size:0.8rem; color:#64748b;"><span style="color:#0ea5e9;">●</span> Speed</div>
                    <div style="font-size:0.8rem; color:#64748b;"><span style="color:#f59e0b;">●</span> Reliability</div>
                </div>
            </div>
        </div>

        <!-- 3. Insights -->
        <div style="margin-top:1rem;" class="animate-in delay-3">
             <h3 style="font-size:1.1rem; font-weight:700; margin-bottom:1rem; color:#111827;">AI Performance Insights</h3>
             <div class="insights-grid">
                 <div class="insight-card">
                     <div class="insight-icon"><i class="fas fa-bolt"></i></div>
                     <p class="insight-text">Your <strong>response time</strong> has improved by 12% this month, placing you in the top 5% of engineers.</p>
                 </div>
                 <div class="insight-card">
                     <div class="insight-icon"><i class="fas fa-chart-line"></i></div>
                     <p class="insight-text"><strong>Bid success rate</strong> for residential projects is trending upward. Consider focusing more on villa renovations.</p>
                 </div>
                 <div class="insight-card">
                     <div class="insight-icon"><i class="fas fa-user-shield"></i></div>
                     <p class="insight-text">Your <strong>client retention score</strong> is perfect this quarter. 3 clients have requested repeat consultations.</p>
                 </div>
             </div>
        </div>

    </main>

    <script>
        // --- 1. Animated Numbers ---
        const animateNumbers = () => {
            document.querySelectorAll('.kpi-value, .score-val').forEach(el => {
                const target = parseFloat(el.getAttribute('data-target'));
                const isFloat = target % 1 !== 0;
                let current = 0;
                const increment = target / 50; 
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }
                    el.innerText = isFloat ? current.toFixed(1) : Math.floor(current);
                }, 20);
            });
        };

        // --- 2. Chart.js Implementation ---
        const initCharts = () => {
            const ctx = document.getElementById('growthChart').getContext('2d');
            
            // Gradient for the chart area
            const gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, 'rgba(30, 127, 92, 0.2)');
            gradient.addColorStop(1, 'rgba(30, 127, 92, 0)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Project Revenue',
                        data: [120, 145, 135, 180, 210, 245],
                        borderColor: '#1E7F5C',
                        backgroundColor: gradient,
                        borderWidth: 3,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#1E7F5C',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.4
                    }, {
                         label: 'New Clients',
                         data: [5, 8, 6, 12, 10, 14],
                         borderColor: '#10b981',
                         borderWidth: 2,
                         borderDash: [5, 5],
                         pointRadius: 0,
                         tension: 0.4,
                         fill: false,
                         yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top', align: 'end', labels: { usePointStyle: true, boxWidth: 8 } }
                    },
                    scales: {
                        y: { 
                            grid: { display: true, borderDash: [5, 5], color: '#f1f5f9' },
                            ticks: { display: false }
                        },
                        y1: { display: false, position: 'right', grid: { display: false } },
                        x: { grid: { display: false } }
                    },
                    interaction: { mode: 'index', intersect: false }
                }
            });

            // Sparklines (Simplified)
            const sparkOptions = {
                type: 'line',
                options: { responsive: true, plugins:{legend:{display:false}}, scales:{x:{display:false}, y:{display:false}}, elements:{point:{radius:0}, line:{borderWidth:2, tension:0.3}} }
            };
            
            new Chart(document.getElementById('spark1'), { ...sparkOptions, data: { labels:[1,2,3,4,5], datasets:[{data:[10,12,15,14,20], borderColor:'#1E7F5C'}] } });
            new Chart(document.getElementById('spark2'), { ...sparkOptions, data: { labels:[1,2,3,4,5], datasets:[{data:[4.5,4.6,4.7,4.8,4.8], borderColor:'#f59e0b'}] } });
            new Chart(document.getElementById('spark3'), { ...sparkOptions, data: { labels:[1,2,3,4,5], datasets:[{data:[3,2.5,2,1.8,1.5], borderColor:'#0ea5e9'}] } });
            new Chart(document.getElementById('spark4'), { ...sparkOptions, data: { labels:[1,2,3,4,5], datasets:[{data:[50,55,60,65,72], borderColor:'#1E7F5C'}] } });
        };

        // --- 3. Circular Progress Animation ---
        const initScore = () => {
            // 92% = 331 degrees roughly
            setTimeout(() => {
                document.getElementById('scoreCircle').style.setProperty('--degrees', '331deg');
            }, 500);
        };

        // --- Initialization ---
        document.addEventListener('DOMContentLoaded', () => {
            animateNumbers();
            initCharts();
            initScore();
            
            // 3D Background (Simplified)
            const initBackground3D = () => {
                const container = document.getElementById('canvas-container');
                if(!container) return;
                const scene = new THREE.Scene();
                scene.background = new THREE.Color('#f6f7f2');
                const camera = new THREE.PerspectiveCamera(60, window.innerWidth/window.innerHeight, 0.1, 1000);
                camera.position.set(0, 5, 10);
                camera.lookAt(0,0,0);
                const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
                renderer.setSize(window.innerWidth, window.innerHeight);
                container.appendChild(renderer.domElement);
                
                const gridHelper = new THREE.GridHelper(50, 50, 0x1E7F5C, 0x1E7F5C);
                gridHelper.material.opacity = 0.1;
                gridHelper.material.transparent = true;
                scene.add(gridHelper);

                function animate() {
                    requestAnimationFrame(animate);
                    gridHelper.rotation.y += 0.001;
                    renderer.render(scene, camera);
                }
                animate();
                window.addEventListener('resize', () => {
                    camera.aspect = window.innerWidth / window.innerHeight;
                    camera.updateProjectionMatrix();
                    renderer.setSize(window.innerWidth, window.innerHeight);
                });
            };
            if(typeof THREE !== 'undefined') initBackground3D();
        });
    </script>
</body>
</html>
